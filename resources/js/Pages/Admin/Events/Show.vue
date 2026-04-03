<template>
  <Head :title="`Event — ${event.title}`" />
  <StaffLayout
    :page-title="event.title"
    :page-subtitle="`${event.period} · ${event.venue}`"
  >
    <div class="space-y-6">

      <!-- ─── Back + Actions bar ──────────────────────────────────────────── -->
      <div class="flex flex-wrap items-center justify-between gap-3">
        <Link :href="route('admin.events.index')" class="btn btn-ghost btn-sm gap-1.5 text-slate-500">
          <ArrowLeftIcon class="w-4 h-4" />
          Back to Events
        </Link>

        <div class="flex flex-wrap items-center gap-2">
          <!-- Status quick-update -->
          <select
            v-model="newStatus"
            @change="updateStatus"
            :class="['form-select text-sm', statusSelectColor]"
            :disabled="updatingStatus"
          >
            <option value="upcoming">🔵 Upcoming</option>
            <option value="ongoing">🟢 Ongoing</option>
            <option value="completed">⚫ Completed</option>
            <option value="cancelled">🔴 Cancelled</option>
          </select>

          <!-- Notify beneficiaries -->
          <button
            @click="notify"
            :disabled="notifying || !event.is_active"
            class="btn btn-secondary btn-sm gap-1.5"
          >
            <BellIcon class="w-4 h-4" />
            <span>{{ notifying ? 'Sending…' : 'Notify All' }}</span>
          </button>

          <!-- Compute grants -->
          <button
            @click="computeGrants"
            :disabled="computing || event.status === 'completed'"
            class="btn btn-primary btn-sm gap-1.5"
          >
            <CalculatorIcon class="w-4 h-4" />
            <span>{{ computing ? 'Computing…' : 'Compute Grants' }}</span>
          </button>
        </div>
      </div>

      <!-- ─── Event Hero Card ──────────────────────────────────────────────── -->
      <div class="card overflow-hidden">
        <!-- Status colour band -->
        <div :class="['h-1.5 w-full', statusBand]"></div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

          <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2 flex items-center gap-1.5">
              <CalendarDaysIcon class="w-3.5 h-3.5" />Distribution Date
            </p>
            <p class="font-semibold text-slate-800">{{ formatDate(event.distribution_date_start) }}</p>
            <p v-if="event.distribution_date_end !== event.distribution_date_start" class="text-sm text-slate-500">
              to {{ formatDate(event.distribution_date_end) }}
            </p>
            <p v-if="event.distribution_time_start" class="text-sm text-slate-400 mt-1">
              🕐 {{ event.distribution_time_start }} – {{ event.distribution_time_end ?? '—' }}
            </p>
          </div>

          <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2 flex items-center gap-1.5">
              <MapPinIcon class="w-3.5 h-3.5" />Venue
            </p>
            <p class="font-semibold text-slate-800">{{ event.venue }}</p>
            <p v-if="event.venue_address" class="text-sm text-slate-500">{{ event.venue_address }}</p>
          </div>

          <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2 flex items-center gap-1.5">
              <ClockIcon class="w-3.5 h-3.5" />Coverage Period
            </p>
            <p class="font-semibold text-slate-800">{{ event.period }}</p>
            <p class="text-sm text-slate-500">
              {{ formatDate(event.period_start) }} – {{ formatDate(event.period_end) }}
            </p>
            <p class="text-xs text-slate-400 mt-1">{{ event.months_covered }} month(s) covered</p>
          </div>

          <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2 flex items-center gap-1.5">
              <BuildingOfficeIcon class="w-3.5 h-3.5" />Managed By
            </p>
            <p class="font-semibold text-slate-800">{{ event.office?.name ?? 'All Offices' }}</p>
            <p class="text-sm text-slate-500">{{ event.creator?.name ?? '—' }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ formatDateTime(event.created_at) }}</p>
          </div>
        </div>

        <div v-if="event.notes" class="px-6 pb-5 pt-4 border-t border-slate-100">
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Notes</p>
          <p class="text-sm text-slate-600 leading-relaxed">{{ event.notes }}</p>
        </div>
      </div>

      <!-- ─── Summary KPI Row ─────────────────────────────────────────────── -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-5 text-center">
          <p class="text-xs text-slate-400 uppercase tracking-wide font-medium">Total Beneficiaries</p>
          <p class="text-3xl font-bold text-slate-800 mt-1">{{ summary.total_beneficiaries.toLocaleString() }}</p>
          <p class="text-xs text-slate-400 mt-1">Active in Lipa City</p>
        </div>
        <div class="card p-5 text-center">
          <p class="text-xs text-slate-400 uppercase tracking-wide font-medium">Grants Computed</p>
          <p class="text-3xl font-bold text-brand-600 mt-1">{{ summary.computed.toLocaleString() }}</p>
          <p class="text-xs text-slate-400 mt-1">
            {{ summary.total_beneficiaries
              ? Math.round(summary.computed / summary.total_beneficiaries * 100)
              : 0 }}% of total
          </p>
        </div>
        <div class="card p-5 text-center">
          <p class="text-xs text-slate-400 uppercase tracking-wide font-medium">Claims Processed</p>
          <p class="text-3xl font-bold text-success-600 mt-1">{{ summary.claimed.toLocaleString() }}</p>
          <p class="text-xs text-slate-400 mt-1">
            {{ summary.computed
              ? Math.round(summary.claimed / summary.computed * 100)
              : 0 }}% of computed
          </p>
        </div>
        <div class="card p-5 text-center">
          <p class="text-xs text-slate-400 uppercase tracking-wide font-medium">Total Released</p>
          <p class="text-2xl font-bold text-purple-600 mt-1">
            ₱{{ Number(summary.total_released).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
          </p>
          <p class="text-xs text-slate-400 mt-1">Cash grants paid out</p>
        </div>
      </div>

      <!-- ─── Claiming Progress Bar ─────────────────────────────────────────── -->
      <div class="card p-5">
        <div class="flex items-center justify-between mb-3">
          <p class="text-sm font-semibold text-slate-700">Claiming Progress</p>
          <span class="text-sm font-bold text-brand-600">{{ claimRate }}%</span>
        </div>
        <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-1000"
            :style="`width: ${claimRate}%; background: linear-gradient(90deg, #10b981, #6366f1)`"
          ></div>
        </div>
        <div class="flex justify-between mt-1.5 text-xs text-slate-400">
          <span>{{ summary.claimed }} claimed</span>
          <span>{{ summary.computed - summary.claimed }} remaining</span>
        </div>
      </div>

      <!-- ─── Two-column data tables ────────────────────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Computed Grants -->
        <div class="card overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <CalculatorIcon class="w-5 h-5 text-brand-600" />
              <h2 class="font-semibold text-slate-800 text-sm">Computed Grants</h2>
            </div>
            <span class="badge badge-neutral text-xs">{{ event.calculations?.length ?? 0 }}</span>
          </div>

          <div v-if="!event.calculations?.length" class="flex-1 px-5 py-14 text-center text-slate-400">
            <CalculatorIcon class="w-10 h-10 opacity-20 mx-auto mb-2" />
            <p class="text-sm font-medium">No grants computed yet.</p>
            <p class="text-xs mt-1 mb-4">Click "Compute Grants" to calculate amounts for all eligible beneficiaries.</p>
            <button @click="computeGrants" :disabled="computing" class="btn btn-primary btn-sm">
              <CalculatorIcon class="w-4 h-4" />
              Compute Now
            </button>
          </div>

          <div v-else class="overflow-y-auto max-h-80">
            <table class="w-full text-sm">
              <thead class="bg-slate-50 border-b border-slate-100 sticky top-0">
                <tr>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Beneficiary</th>
                  <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Amount</th>
                  <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <tr v-for="calc in event.calculations" :key="calc.id" class="hover:bg-slate-50/60 transition-colors">
                  <td class="px-5 py-3">
                    <p class="font-medium text-slate-700 text-xs">{{ calc.beneficiary?.full_name ?? '—' }}</p>
                    <p class="text-[10px] text-slate-400 font-mono">{{ calc.beneficiary?.unique_id }}</p>
                  </td>
                  <td class="px-5 py-3 text-right font-bold text-brand-600 text-sm">
                    ₱{{ Number(calc.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                  </td>
                  <td class="px-5 py-3 text-center">
                    <span :class="['badge badge-sm', calc.is_eligible ? 'badge-success' : 'badge-danger']">
                      {{ calc.is_eligible ? 'Eligible' : 'Ineligible' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Claims Processed -->
        <div class="card overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <BanknotesIcon class="w-5 h-5 text-success-600" />
              <h2 class="font-semibold text-slate-800 text-sm">Claims Processed</h2>
            </div>
            <span class="badge badge-neutral text-xs">{{ event.distributions?.length ?? 0 }}</span>
          </div>

          <div v-if="!event.distributions?.length" class="flex-1 px-5 py-14 text-center text-slate-400">
            <BanknotesIcon class="w-10 h-10 opacity-20 mx-auto mb-2" />
            <p class="text-sm font-medium">No claims recorded yet.</p>
            <p class="text-xs mt-1">Field officers record claims via QR scan during the distribution event.</p>
          </div>

          <div v-else class="overflow-y-auto max-h-64 flex-1">
            <table class="w-full text-sm">
              <thead class="bg-slate-50 border-b border-slate-100 sticky top-0">
                <tr>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Beneficiary</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Claimed By</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Time</th>
                  <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Released</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <tr v-for="dist in event.distributions" :key="dist.id" class="hover:bg-slate-50/60 transition-colors">
                  <td class="px-5 py-3">
                    <p class="font-medium text-slate-700 text-xs">{{ dist.beneficiary?.full_name ?? '—' }}</p>
                    <p class="text-[10px] text-slate-400 font-mono">{{ dist.beneficiary?.unique_id }}</p>
                  </td>
                  <td class="px-5 py-3">
                    <span :class="['badge badge-sm', dist.claimed_by_type === 'proxy' ? 'badge-warning' : 'badge-success']">
                      {{ dist.claimed_by_type === 'proxy' ? 'Proxy' : 'Self' }}
                    </span>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ dist.released_by?.name ?? '—' }}</p>
                  </td>
                  <td class="px-5 py-3 text-xs text-slate-500">{{ timeAgo(dist.claimed_at) }}</td>
                  <td class="px-5 py-3 text-right font-bold text-success-600">
                    ₱{{ Number(dist.amount_released).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-slate-50 border-t-2 border-slate-200">
                <tr>
                  <td colspan="3" class="px-5 py-3 text-xs font-bold text-slate-600 uppercase tracking-wide">Total</td>
                  <td class="px-5 py-3 text-right text-sm font-extrabold text-success-700">
                    ₱{{ Number(summary.total_released).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <!-- ─── Edit Details Panel ─────────────────────────────────────────────── -->
      <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
          <PencilSquareIcon class="w-5 h-5 text-slate-500" />
          <h2 class="font-semibold text-slate-800 text-sm">Edit Event Details</h2>
        </div>
        <div class="p-5">
          <form @submit.prevent="saveEdit" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="form-label">Event Title</label>
              <input v-model="form.title" type="text" class="form-input" required />
            </div>
            <div>
              <label class="form-label">Venue</label>
              <input v-model="form.venue" type="text" class="form-input" required />
            </div>
            <div class="sm:col-span-2">
              <label class="form-label">Notes / Instructions</label>
              <textarea v-model="form.notes" rows="3" class="form-input"></textarea>
            </div>
            <div class="sm:col-span-2 flex justify-end">
              <button type="submit" :disabled="saving" class="btn btn-primary btn-sm gap-1.5">
                <CheckIcon class="w-4 h-4" />
                {{ saving ? 'Saving…' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ─── Danger Zone ─────────────────────────────────────────────────────── -->
      <div v-if="!event.distributions?.length" class="card border border-danger-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-danger-100 bg-danger-50 flex items-center gap-2">
          <ExclamationTriangleIcon class="w-5 h-5 text-danger-600" />
          <h2 class="font-semibold text-danger-700 text-sm">Danger Zone</h2>
        </div>
        <div class="p-5 flex items-center justify-between gap-4">
          <div>
            <p class="text-sm font-medium text-slate-700">Delete this event</p>
            <p class="text-xs text-slate-400 mt-0.5">Only possible when no claims exist. This action is irreversible.</p>
          </div>
          <button @click="confirmDelete = true" class="btn btn-danger btn-sm gap-1.5 shrink-0">
            <TrashIcon class="w-4 h-4" />
            Delete Event
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Delete Confirmation Modal ──────────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="confirmDelete" class="modal-backdrop" @click.self="confirmDelete = false">
        <div class="modal-container max-w-sm">
          <div class="modal-header">
            <h3 class="font-semibold text-slate-800">Confirm Delete</h3>
            <button @click="confirmDelete = false" class="btn btn-ghost btn-icon">✕</button>
          </div>
          <div class="modal-body">
            <div class="flex items-start gap-3 p-3 bg-danger-50 rounded-xl border border-danger-200">
              <ExclamationTriangleIcon class="w-6 h-6 text-danger-600 shrink-0 mt-0.5" />
              <p class="text-sm text-danger-700">
                Delete <strong>{{ event.title }}</strong>? This cannot be undone.
              </p>
            </div>
          </div>
          <div class="modal-footer">
            <button @click="confirmDelete = false" class="btn btn-secondary btn-sm">Cancel</button>
            <button @click="executeDelete" :disabled="deleting" class="btn btn-danger btn-sm">
              {{ deleting ? 'Deleting…' : 'Yes, Delete' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </StaffLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
  ArrowLeftIcon, CalendarDaysIcon, MapPinIcon, ClockIcon,
  BuildingOfficeIcon, BellIcon, BanknotesIcon,
  TrashIcon, PencilSquareIcon, CheckIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import { CalculatorIcon } from '@heroicons/vue/24/solid'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  event:   Object,
  summary: Object,
})

// ─── Status ──────────────────────────────────────────────────────────────────
const newStatus      = ref(props.event.status)
const updatingStatus = ref(false)

const statusBand = computed(() => ({
  upcoming:  'bg-blue-400',
  ongoing:   'bg-emerald-500',
  completed: 'bg-slate-400',
  cancelled: 'bg-red-500',
}[props.event.status] ?? 'bg-slate-300'))

const statusSelectColor = computed(() => ({
  upcoming:  'text-blue-700',
  ongoing:   'text-emerald-700',
  completed: 'text-slate-600',
  cancelled: 'text-red-700',
}[newStatus.value] ?? ''))

const updateStatus = () => {
  updatingStatus.value = true
  router.patch(route('admin.events.update', props.event.id), {
    title:  props.event.title,
    venue:  props.event.venue,
    status: newStatus.value,
    notes:  props.event.notes ?? '',
  }, {
    preserveScroll: true,
    onFinish: () => { updatingStatus.value = false },
  })
}

// ─── Actions ─────────────────────────────────────────────────────────────────
const notifying = ref(false)
const notify = () => {
  notifying.value = true
  router.post(route('admin.events.notify', props.event.id), {}, {
    preserveScroll: true,
    onFinish: () => { notifying.value = false },
  })
}

const computing = ref(false)
const computeGrants = () => {
  computing.value = true
  router.post(route('admin.events.compute', props.event.id), {}, {
    preserveScroll: true,
    onFinish: () => { computing.value = false },
  })
}

// ─── Progress ────────────────────────────────────────────────────────────────
const claimRate = computed(() => {
  if (!props.summary.computed) return 0
  return Math.round((props.summary.claimed / props.summary.computed) * 100)
})

// ─── Edit Form ───────────────────────────────────────────────────────────────
const saving = ref(false)
const form = reactive({
  title:  props.event.title,
  venue:  props.event.venue,
  notes:  props.event.notes ?? '',
  status: props.event.status,
})

const saveEdit = () => {
  saving.value = true
  router.patch(route('admin.events.update', props.event.id), form, {
    preserveScroll: true,
    onFinish: () => { saving.value = false },
  })
}

// ─── Delete ──────────────────────────────────────────────────────────────────
const confirmDelete = ref(false)
const deleting      = ref(false)

const executeDelete = () => {
  deleting.value = true
  router.delete(route('admin.events.destroy', props.event.id), {
    onSuccess: () => { confirmDelete.value = false },
    onFinish:  () => { deleting.value = false },
  })
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' }) : '—'

const formatDateTime = (d) =>
  d ? new Date(d).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—'

const timeAgo = (d) => {
  if (!d) return '—'
  const sec = Math.floor((Date.now() - new Date(d)) / 1000)
  if (sec < 60)    return `${sec}s ago`
  if (sec < 3600)  return `${Math.floor(sec / 60)}m ago`
  if (sec < 86400) return `${Math.floor(sec / 3600)}h ago`
  return formatDate(d)
}
</script>
