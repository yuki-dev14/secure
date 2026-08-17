<template>
  <Head title="Grant Computation" />
  <StaffLayout page-title="Grant Computation" page-subtitle="Compute beneficiary grants based on Admin4Ps and AdminSWA reports">
    <div class="space-y-5">

      <!-- ─── Period selector + Update Grants button ──────────────────────── -->
      <div class="card p-4 flex flex-col sm:flex-row items-start sm:items-center gap-3 justify-between">
        <div class="flex items-center gap-3 flex-wrap">
          <div class="flex items-center gap-2">
            <CalendarDaysIcon class="w-5 h-5 text-brand-500 shrink-0" />
            <span class="text-sm font-semibold text-slate-700">Period:</span>
          </div>
          <select
            v-model="selectedPeriod"
            class="form-select w-56"
            @change="changePeriod"
          >
            <option v-for="p in periods" :key="p.value" :value="p.value">
              {{ p.label }}
            </option>
          </select>

          <!-- Event status badge -->
          <span v-if="event" :class="['badge', eventStatusClass(event.status)]">
            {{ event.status }}
          </span>
          <span v-else class="badge badge-neutral text-xs">No event yet</span>
        </div>

        <button
          @click="openConfirmModal"
          :disabled="computing"
          class="btn btn-primary gap-2 shrink-0"
          id="update-grants-btn"
        >
          <ArrowPathIcon v-if="computing" class="w-4 h-4 animate-spin" />
          <CurrencyDollarIcon v-else class="w-4 h-4" />
          {{ computing ? 'Computing…' : 'Update Grants' }}
        </button>
      </div>

      <!-- ─── Compute result flash ──────────────────────────────────────────── -->
      <Transition name="slide-fade">
        <div v-if="computeResult"
          :class="[
            'flex items-start gap-3 p-4 rounded-2xl border text-sm',
            computeResult.success
              ? 'bg-success-50 border-success-200 text-success-800'
              : 'bg-danger-50 border-danger-200 text-danger-800'
          ]"
        >
          <CheckCircleIcon v-if="computeResult.success" class="w-5 h-5 shrink-0 mt-0.5" />
          <ExclamationCircleIcon v-else class="w-5 h-5 shrink-0 mt-0.5" />
          <div>
            <p class="font-semibold">{{ computeResult.success ? 'Grants Updated' : 'Computation Failed' }}</p>
            <p class="text-xs mt-0.5 opacity-80">{{ computeResult.message }}</p>
          </div>
          <button @click="computeResult = null" class="ml-auto btn btn-ghost btn-icon btn-sm">
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
      </Transition>

      <!-- ─── KPI Summary Strip ─────────────────────────────────────────────── -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-4 flex items-center gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
            <UsersIcon class="w-5 h-5 text-brand-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-medium">Active Beneficiaries</p>
            <p class="text-2xl font-bold text-slate-800">{{ summary.total_beneficiaries.toLocaleString() }}</p>
          </div>
          <div class="absolute -bottom-2 -right-2 w-12 h-12 bg-brand-50 rounded-full opacity-40"></div>
        </div>
        <div class="card p-4 flex items-center gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-success-50 flex items-center justify-center shrink-0">
            <CheckCircleIcon class="w-5 h-5 text-success-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-medium">Eligible</p>
            <p class="text-2xl font-bold text-slate-800">{{ summary.eligible.toLocaleString() }}</p>
            <p class="text-[10px] text-slate-400">of {{ summary.computed }} computed</p>
          </div>
          <div class="absolute -bottom-2 -right-2 w-12 h-12 bg-success-50 rounded-full opacity-40"></div>
        </div>
        <div class="card p-4 flex items-center gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-warning-50 flex items-center justify-center shrink-0">
            <ExclamationTriangleIcon class="w-5 h-5 text-warning-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-medium">With Deductions</p>
            <p class="text-2xl font-bold text-slate-800">{{ summary.with_deductions.toLocaleString() }}</p>
            <p class="text-[10px] text-slate-400">partial grants</p>
          </div>
          <div class="absolute -bottom-2 -right-2 w-12 h-12 bg-warning-50 rounded-full opacity-40"></div>
        </div>
        <div class="card p-4 flex items-center gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
            <CurrencyDollarIcon class="w-5 h-5 text-emerald-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-medium">Total Grant</p>
            <p class="text-xl font-bold text-slate-800">₱{{ fmtAmt(summary.total_amount) }}</p>
          </div>
          <div class="absolute -bottom-2 -right-2 w-12 h-12 bg-emerald-50 rounded-full opacity-40"></div>
        </div>
      </div>

      <!-- ─── Report Impact Panel ──────────────────────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- Admin4Ps: FDS Attendance -->
        <div class="card p-5">
          <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
              <ClipboardDocumentCheckIcon class="w-4 h-4 text-blue-600" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-800">Admin4Ps — FDS Attendance</h3>
              <p class="text-[10px] text-slate-400">Based on FDS session records for {{ selectedPeriod }}</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-success-50 rounded-xl p-3 text-center">
              <p class="text-2xl font-bold text-success-700">{{ reportImpact.fds_attended.toLocaleString() }}</p>
              <p class="text-[10px] text-success-600 font-medium mt-0.5">Attended FDS</p>
            </div>
            <div class="bg-warning-50 rounded-xl p-3 text-center">
              <p class="text-2xl font-bold text-warning-700">{{ reportImpact.fds_absent.toLocaleString() }}</p>
              <p class="text-[10px] text-warning-600 font-medium mt-0.5">No FDS Record</p>
            </div>
          </div>
          <p class="text-[10px] text-slate-400 mt-3 leading-relaxed">
            Beneficiaries without FDS attendance records will have their health grant component zeroed out during computation.
          </p>
        </div>

        <!-- AdminSWA: Non-Compliance -->
        <div class="card p-5">
          <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
              <ExclamationTriangleIcon class="w-4 h-4 text-amber-600" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-800">AdminSWA — Non-Compliance Reports</h3>
              <p class="text-[10px] text-slate-400">Based on confirmed NC records for {{ selectedPeriod }}</p>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div class="bg-slate-50 rounded-xl p-3 text-center">
              <p class="text-2xl font-bold text-slate-700">{{ reportImpact.nc_total.toLocaleString() }}</p>
              <p class="text-[10px] text-slate-500 font-medium mt-0.5">Total NC</p>
            </div>
            <div class="bg-blue-50 rounded-xl p-3 text-center">
              <p class="text-2xl font-bold text-blue-700">{{ reportImpact.nc_education.toLocaleString() }}</p>
              <p class="text-[10px] text-blue-600 font-medium mt-0.5">Education</p>
            </div>
            <div class="bg-red-50 rounded-xl p-3 text-center">
              <p class="text-2xl font-bold text-red-700">{{ reportImpact.nc_health.toLocaleString() }}</p>
              <p class="text-[10px] text-red-600 font-medium mt-0.5">Health</p>
            </div>
          </div>
          <p class="text-[10px] text-slate-400 mt-3 leading-relaxed">
            Confirmed non-compliance records zero out the affected grant component (health, education, or rice subsidy) per beneficiary.
          </p>
        </div>
      </div>

      <!-- ─── Grant Breakdown Table ──────────────────────────────────────────── -->
      <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
          <div class="flex items-center gap-2">
            <CurrencyDollarIcon class="w-5 h-5 text-emerald-600" />
            <h2 class="font-semibold text-slate-800 text-sm">Grant Breakdown — {{ selectedPeriod }}</h2>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <!-- Search -->
            <div class="relative">
              <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
              <input v-model="filters.search" type="text" placeholder="Search beneficiary…"
                class="form-input pl-9 w-48 text-xs" @input="debounceSearch" />
            </div>
            <!-- Barangay filter -->
            <select v-model="filters.barangay" class="form-select text-xs w-40" @change="applyFilters">
              <option value="">All Barangays</option>
              <option v-for="b in barangays" :key="b" :value="b">{{ b }}</option>
            </select>
            <!-- Eligible filter -->
            <select v-model="filters.eligible" class="form-select text-xs w-36" @change="applyFilters">
              <option value="">All Grants</option>
              <option value="true">Eligible Only</option>
              <option value="false">Zeroed Out</option>
            </select>
            <!-- Export -->
            <a :href="exportUrl" class="btn btn-secondary btn-sm gap-1.5" download>
              <ArrowDownTrayIcon class="w-4 h-4" />
              Export CSV
            </a>
          </div>
        </div>

        <!-- Grant totals sub-header -->
        <div v-if="summary.computed > 0" class="px-5 py-2.5 bg-emerald-50/60 border-b border-emerald-100 flex flex-wrap gap-4 text-xs">
          <span class="text-slate-500">Health: <strong class="text-emerald-700">₱{{ fmtAmt(summary.health_total) }}</strong></span>
          <span class="text-slate-500">Education: <strong class="text-emerald-700">₱{{ fmtAmt(summary.edu_total) }}</strong></span>
          <span class="text-slate-500">Rice: <strong class="text-emerald-700">₱{{ fmtAmt(summary.rice_total) }}</strong></span>
          <span class="ml-auto font-bold text-slate-700">Total: <strong class="text-emerald-700">₱{{ fmtAmt(summary.total_amount) }}</strong></span>
        </div>

        <!-- Empty state -->
        <div v-if="!grants.data?.length" class="px-5 py-16 text-center text-slate-400">
          <CurrencyDollarIcon class="w-12 h-12 opacity-20 mx-auto mb-3" />
          <p class="font-medium text-slate-500">No grants computed yet for {{ selectedPeriod }}.</p>
          <p class="text-sm mt-1">Click <strong>"Update Grants"</strong> to compute grants based on current reports.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr>
                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Beneficiary</th>
                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Barangay</th>
                <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Health</th>
                <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Education</th>
                <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Rice</th>
                <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Total</th>
                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Notes</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr
                v-for="g in grants.data" :key="g.id"
                :class="['hover:bg-slate-50/60 transition-colors', !g.is_eligible ? 'opacity-60' : '']"
              >
                <td class="px-4 py-3">
                  <p class="text-xs font-bold text-slate-800">{{ g.beneficiary?.unique_id }}</p>
                  <p class="text-[10px] text-slate-400">{{ g.beneficiary?.first_name }} {{ g.beneficiary?.last_name }}</p>
                </td>
                <td class="px-4 py-3 text-xs text-slate-600">{{ g.beneficiary?.barangay }}</td>

                <!-- Health -->
                <td class="px-4 py-3 text-right">
                  <span :class="['text-xs font-semibold', g.health_grant_eligible ? 'text-slate-700' : 'text-danger-500 line-through']">
                    ₱{{ fmtAmt(g.health_grant_amount) }}
                  </span>
                </td>

                <!-- Education -->
                <td class="px-4 py-3 text-right">
                  <span class="text-xs font-semibold text-slate-700">₱{{ fmtAmt(g.education_grant_total) }}</span>
                  <p class="text-[10px] text-slate-400">
                    {{ g.elementary_children_count }}E / {{ g.junior_high_children_count }}J / {{ g.senior_high_children_count }}S
                  </p>
                </td>

                <!-- Rice -->
                <td class="px-4 py-3 text-right">
                  <span :class="['text-xs font-semibold', g.rice_subsidy_eligible ? 'text-slate-700' : 'text-danger-500 line-through']">
                    ₱{{ fmtAmt(g.rice_subsidy_amount) }}
                  </span>
                </td>

                <!-- Total -->
                <td class="px-4 py-3 text-right">
                  <span :class="['text-sm font-bold', g.is_eligible ? 'text-emerald-700' : 'text-slate-300']">
                    ₱{{ fmtAmt(g.total_grant_amount) }}
                  </span>
                </td>

                <!-- Notes -->
                <td class="px-4 py-3 max-w-xs">
                  <span v-if="g.computation_notes" class="badge badge-warning badge-sm truncate block max-w-[180px]" :title="g.computation_notes">
                    {{ g.computation_notes.replace('Non-compliance adjustments: ', '') }}
                  </span>
                  <span v-else-if="g.ineligibility_reason" class="text-[10px] text-danger-500" :title="g.ineligibility_reason">
                    {{ g.ineligibility_reason.substring(0, 40) }}…
                  </span>
                  <span v-else class="text-[10px] text-success-600 font-medium">Full grant</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="grants.last_page > 1" class="p-4 border-t border-slate-100 flex items-center justify-between">
          <p class="text-sm text-slate-500">
            Showing {{ grants.from }}–{{ grants.to }} of {{ grants.total.toLocaleString() }}
          </p>
          <div class="flex gap-1 flex-wrap">
            <Link
              v-for="link in grants.links"
              :key="link.label"
              :href="link.url ?? '#'"
              :class="[
                'btn btn-sm',
                link.active ? 'btn-primary' : 'btn-secondary',
                !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : '',
              ]"
              v-html="link.label"
            />
          </div>
        </div>
      </div>

    </div>

    <!-- ─── Confirm Compute Modal ─────────────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showConfirm"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
          @click.self="showConfirm = false"
        >
          <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 rounded-2xl bg-brand-50 flex items-center justify-center">
                <CurrencyDollarIcon class="w-5 h-5 text-brand-600" />
              </div>
              <div>
                <h2 class="text-base font-bold text-slate-800">Update Grants</h2>
                <p class="text-xs text-slate-400">{{ selectedPeriod }}</p>
              </div>
            </div>

            <div class="bg-amber-50 rounded-2xl p-4 mb-5 text-sm text-amber-800">
              <p class="font-semibold mb-1">⚠ This will recalculate grants for all active beneficiaries.</p>
              <ul class="text-xs space-y-1 mt-2 text-amber-700 list-disc list-inside">
                <li>Based on <strong>Admin4Ps FDS attendance</strong> ({{ reportImpact.fds_absent }} no-records)</li>
                <li>Based on <strong>AdminSWA NC reports</strong> ({{ reportImpact.nc_total }} confirmed)</li>
                <li>Previous computation results will be overwritten.</li>
              </ul>
            </div>

            <div class="flex gap-3">
              <button @click="showConfirm = false" class="btn btn-secondary flex-1">Cancel</button>
              <button @click="runCompute" class="btn btn-primary flex-1 gap-2" id="confirm-compute-btn">
                <CurrencyDollarIcon class="w-4 h-4" />
                Confirm & Update
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </StaffLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import {
  CalendarDaysIcon, CurrencyDollarIcon, UsersIcon,
  CheckCircleIcon, ExclamationTriangleIcon, ExclamationCircleIcon,
  ArrowPathIcon, ArrowDownTrayIcon, MagnifyingGlassIcon,
  ClipboardDocumentCheckIcon, XMarkIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  grants:        Object,
  summary:       Object,
  reportImpact:  Object,
  barangays:     Array,
  periods:       Array,
  currentPeriod: String,
  event:         Object,
  filters:       Object,
})

// ─── Period + filters ─────────────────────────────────────────────────────────
const selectedPeriod = ref(props.currentPeriod)

const filters = reactive({
  search:   props.filters?.search   ?? '',
  barangay: props.filters?.barangay ?? '',
  eligible: props.filters?.eligible ?? '',
})

const changePeriod = () => {
  router.get(route('superadmin.grant-computation.index'), {
    period: selectedPeriod.value,
  }, { preserveState: false })
}

let debounceTimer = null
const debounceSearch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(applyFilters, 400)
}

const applyFilters = () => {
  router.get(route('superadmin.grant-computation.index'), {
    period:   selectedPeriod.value,
    search:   filters.search   || undefined,
    barangay: filters.barangay || undefined,
    eligible: filters.eligible || undefined,
  }, { preserveState: true, preserveScroll: true, replace: true })
}

const exportUrl = computed(() => {
  const params = new URLSearchParams({ period: selectedPeriod.value })
  return route('superadmin.grant-computation.export') + '?' + params.toString()
})

// ─── Compute ──────────────────────────────────────────────────────────────────
const computing     = ref(false)
const showConfirm   = ref(false)
const computeResult = ref(null)

const openConfirmModal = () => {
  showConfirm.value = true
}

const runCompute = async () => {
  showConfirm.value = false
  computing.value   = true
  computeResult.value = null

  try {
    const res = await axios.post(route('superadmin.grant-computation.compute'), {
      period: selectedPeriod.value,
    })
    computeResult.value = { success: true, message: res.data.message }
    // Reload the page data to reflect new computation
    router.reload({ only: ['grants', 'summary', 'reportImpact', 'event'] })
  } catch (err) {
    computeResult.value = {
      success: false,
      message: err.response?.data?.message ?? 'An error occurred. Please try again.',
    }
  } finally {
    computing.value = false
  }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
const fmtAmt = (n) =>
  Number(n ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const eventStatusClass = (status) => ({
  upcoming:  'badge-info',
  ongoing:   'badge-success',
  completed: 'badge-neutral',
}[status] ?? 'badge-neutral')
</script>

<style scoped>
.slide-fade-enter-active { transition: all 0.3s ease; }
.slide-fade-leave-active { transition: all 0.2s ease; }
.slide-fade-enter-from   { opacity: 0; transform: translateY(-8px); }
.slide-fade-leave-to     { opacity: 0; transform: translateY(-4px); }

.modal-enter-active { transition: all 0.2s ease; }
.modal-leave-active { transition: all 0.15s ease; }
.modal-enter-from   { opacity: 0; }
.modal-leave-to     { opacity: 0; }
.modal-enter-from .bg-white { transform: scale(0.95); }
</style>
