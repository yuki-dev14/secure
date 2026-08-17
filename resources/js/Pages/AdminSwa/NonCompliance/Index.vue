<template>
  <Head title="Non-Compliance Records" />
  <StaffLayout page-title="Non-Compliance Records" page-subtitle="Review and manage non-compliance flags from School Reps and Midwives">
    <div class="space-y-6">

      <!-- Summary pills -->
      <div class="flex flex-wrap gap-3">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-50 border border-slate-100">
          <span class="text-xs text-slate-400 uppercase tracking-wide">Total</span>
          <span class="text-lg font-bold text-slate-700">{{ summary.total }}</span>
        </div>
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-50 border border-amber-100">
          <span class="text-xs text-amber-500 uppercase tracking-wide">Pending</span>
          <span class="text-lg font-bold text-amber-600">{{ summary.pending }}</span>
        </div>
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-50 border border-red-100">
          <span class="text-xs text-red-400 uppercase tracking-wide">Confirmed</span>
          <span class="text-lg font-bold text-red-600">{{ summary.confirmed }}</span>
        </div>
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-50 border border-slate-100">
          <span class="text-xs text-slate-400 uppercase tracking-wide">Dismissed</span>
          <span class="text-lg font-bold text-slate-500">{{ summary.dismissed }}</span>
        </div>
      </div>

      <!-- Filters -->
      <div class="card p-4">
        <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
          <input v-model="filterForm.search" type="text" placeholder="Search name or ID..."
                 class="input col-span-2" @input="debouncedFilter" />

          <select v-model="filterForm.period" class="input" @change="applyFilters">
            <option value="">All Periods</option>
            <option v-for="p in periods" :key="p.value" :value="p.value">{{ p.label }}</option>
          </select>

          <select v-model="filterForm.category" class="input" @change="applyFilters">
            <option value="">All Categories</option>
            <option value="education">Education</option>
            <option value="health">Health</option>
          </select>

          <select v-model="filterForm.status" class="input" @change="applyFilters">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="dismissed">Dismissed</option>
          </select>

          <select v-model="filterForm.barangay" class="input" @change="applyFilters">
            <option value="">All Barangays</option>
            <option v-for="b in barangays" :key="b" :value="b">{{ b }}</option>
          </select>
        </div>

        <!-- Batch actions -->
        <div v-if="selectedIds.length > 0" class="mt-3 flex items-center gap-3 p-3 bg-amber-50 rounded-xl border border-amber-100">
          <span class="text-sm text-amber-700 font-medium">{{ selectedIds.length }} selected</span>
          <button @click="batchAction('confirm')" class="btn btn-sm bg-red-600 text-white hover:bg-red-700">
            Confirm All
          </button>
          <button @click="batchAction('dismiss')" class="btn btn-sm btn-secondary">
            Dismiss All
          </button>
        </div>
      </div>

      <!-- Records table -->
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="px-4 py-3 text-left">
                  <input type="checkbox" :checked="allSelected" @change="toggleAll" class="rounded border-slate-300" />
                </th>
                <th class="px-4 py-3 text-left">Beneficiary</th>
                <th class="px-4 py-3 text-left">Category</th>
                <th class="px-4 py-3 text-left">Source</th>
                <th class="px-4 py-3 text-left">Reason</th>
                <th class="px-4 py-3 text-left">Grant Affected</th>
                <th class="px-4 py-3 text-left">Period</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-center">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-if="records.data.length === 0">
                <td colspan="9" class="px-4 py-12 text-center text-slate-400">
                  No non-compliance records found.
                </td>
              </tr>
              <tr v-for="record in records.data" :key="record.id"
                  class="hover:bg-slate-25 transition-colors">
                <td class="px-4 py-3">
                  <input type="checkbox" :value="record.id" v-model="selectedIds"
                         :disabled="record.status !== 'pending'" class="rounded border-slate-300" />
                </td>
                <td class="px-4 py-3">
                  <p class="font-medium text-slate-700">{{ record.beneficiary?.full_name }}</p>
                  <p class="text-xs text-slate-400">{{ record.beneficiary?.unique_id }} · {{ record.beneficiary?.barangay }}</p>
                  <p v-if="record.family_member" class="text-xs text-violet-500 mt-0.5">
                    → {{ record.family_member.first_name }} {{ record.family_member.last_name }}
                  </p>
                </td>
                <td class="px-4 py-3">
                  <span :class="[record.category === 'education' ? 'bg-violet-100 text-violet-700' : 'bg-emerald-100 text-emerald-700',
                        'px-2 py-0.5 rounded-full text-xs font-medium capitalize']">
                    {{ record.category }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span class="text-xs text-slate-500">{{ sourceLabel(record.source) }}</span>
                  <p v-if="record.reporter_name" class="text-xs text-slate-400 mt-0.5">{{ record.reporter_name }}</p>
                </td>
                <td class="px-4 py-3">
                  <p class="text-sm text-slate-600 max-w-[200px] truncate">{{ record.reason }}</p>
                </td>
                <td class="px-4 py-3">
                  <span class="text-xs text-slate-500 font-mono">{{ grantLabel(record.grant_affected) }}</span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ record.period }}</td>
                <td class="px-4 py-3">
                  <span :class="[statusBadge(record.status), 'px-2.5 py-1 rounded-full text-xs font-semibold']">
                    {{ record.status }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <div v-if="record.status === 'pending'" class="flex items-center justify-center gap-1">
                    <button @click="openConfirmModal(record)" title="Confirm"
                            class="p-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                      <CheckIcon class="w-4 h-4" />
                    </button>
                    <button @click="openDismissModal(record)" title="Dismiss"
                            class="p-1.5 rounded-lg bg-slate-50 text-slate-500 hover:bg-slate-100 transition-colors">
                      <XMarkIcon class="w-4 h-4" />
                    </button>
                  </div>
                  <span v-else class="text-xs text-slate-400">{{ record.processor?.name ?? '—' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="records.last_page > 1" class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
          <span class="text-xs text-slate-400">
            Showing {{ records.from }}–{{ records.to }} of {{ records.total }}
          </span>
          <div class="flex gap-1">
            <Link v-for="link in records.links" :key="link.label"
                  :href="link.url ?? '#'" v-html="link.label"
                  :class="['px-3 py-1.5 text-xs rounded-lg transition-colors',
                    link.active ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50',
                    !link.url ? 'opacity-40 pointer-events-none' : '']" />
          </div>
        </div>
      </div>

      <!-- Confirm Modal -->
      <Teleport to="body">
        <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40" @click="showConfirmModal = false"></div>
          <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-lg font-bold text-slate-800">Confirm Non-Compliance</h3>
            <p class="text-sm text-slate-500">
              This will mark <strong>{{ activeRecord?.beneficiary?.full_name }}</strong> as non-compliant
              for <strong>{{ activeRecord?.category }}</strong>. The {{ grantLabel(activeRecord?.grant_affected) }}
              grant component will be zeroed out for period {{ activeRecord?.period }}.
            </p>
            <textarea v-model="actionNotes" placeholder="Processing notes (optional)..."
                      class="input w-full h-24 resize-none"></textarea>
            <div class="flex justify-end gap-2">
              <button @click="showConfirmModal = false" class="btn btn-secondary">Cancel</button>
              <button @click="submitAction('confirm')" :disabled="processing"
                      class="btn bg-red-600 text-white hover:bg-red-700">
                Confirm Non-Compliance
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Dismiss Modal -->
      <Teleport to="body">
        <div v-if="showDismissModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40" @click="showDismissModal = false"></div>
          <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-lg font-bold text-slate-800">Dismiss Record</h3>
            <p class="text-sm text-slate-500">
              Dismissing this record for <strong>{{ activeRecord?.beneficiary?.full_name }}</strong>.
              The beneficiary will remain compliant for this period.
            </p>
            <textarea v-model="actionNotes" placeholder="Reason for dismissal (required)..."
                      class="input w-full h-24 resize-none" required></textarea>
            <div class="flex justify-end gap-2">
              <button @click="showDismissModal = false" class="btn btn-secondary">Cancel</button>
              <button @click="submitAction('dismiss')" :disabled="processing || !actionNotes.trim()"
                      class="btn bg-slate-700 text-white hover:bg-slate-800">
                Dismiss Record
              </button>
            </div>
          </div>
        </div>
      </Teleport>

    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import { CheckIcon, XMarkIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
  records:   Object,
  barangays: Array,
  periods:   Array,
  summary:   Object,
  filters:   Object,
})

// Filters
const filterForm = ref({ ...props.filters })
let debounceTimer = null

const applyFilters = () => {
  router.get(route('adminswa.non-compliance.index'), filterForm.value, {
    preserveState: true, preserveScroll: true,
  })
}

const debouncedFilter = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(applyFilters, 400)
}

// Selection
const selectedIds = ref([])
const allSelected = computed(() => {
  const pending = props.records.data.filter(r => r.status === 'pending')
  return pending.length > 0 && pending.every(r => selectedIds.value.includes(r.id))
})

const toggleAll = () => {
  const pending = props.records.data.filter(r => r.status === 'pending').map(r => r.id)
  if (allSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = [...pending]
  }
}

// Modals
const showConfirmModal = ref(false)
const showDismissModal = ref(false)
const activeRecord = ref(null)
const actionNotes  = ref('')
const processing   = ref(false)

const openConfirmModal = (record) => {
  activeRecord.value = record
  actionNotes.value  = ''
  showConfirmModal.value = true
}

const openDismissModal = (record) => {
  activeRecord.value = record
  actionNotes.value  = ''
  showDismissModal.value = true
}

const submitAction = (action) => {
  processing.value = true
  const routeName = action === 'confirm' ? 'adminswa.non-compliance.confirm' : 'adminswa.non-compliance.dismiss'
  router.patch(route(routeName, activeRecord.value.id), {
    processing_notes: actionNotes.value,
  }, {
    onFinish: () => {
      processing.value = false
      showConfirmModal.value = false
      showDismissModal.value = false
    },
  })
}

const batchAction = (action) => {
  if (!confirm(`Are you sure you want to ${action} ${selectedIds.value.length} records?`)) return

  router.post(route('adminswa.non-compliance.batch'), {
    record_ids: selectedIds.value,
    action,
    processing_notes: `Batch ${action}ed by Admin SWA`,
  }, {
    onFinish: () => { selectedIds.value = [] },
  })
}

// Helpers
const statusBadge = (s) => ({
  pending:   'bg-amber-100 text-amber-700',
  confirmed: 'bg-red-100 text-red-700',
  dismissed: 'bg-slate-100 text-slate-500',
}[s] ?? 'bg-slate-100 text-slate-500')

const sourceLabel = (s) => ({
  school_rep: 'School Rep',
  midwife:    'Midwife',
}[s] ?? s)

const grantLabel = (g) => ({
  health_grant:            'Health (₱750)',
  education_elementary:    'Edu – Elem (₱300)',
  education_junior_high:   'Edu – JHS (₱500)',
  education_senior_high:   'Edu – SHS (₱700)',
  rice_subsidy:            'Rice (₱600)',
}[g] ?? g)
</script>
