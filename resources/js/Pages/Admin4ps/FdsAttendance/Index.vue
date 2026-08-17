<template>
  <Head title="FDS Attendance Records" />
  <StaffLayout page-title="FDS Attendance" page-subtitle="View and manage Family Development Session attendance records">
    <div class="space-y-5">

      <!-- Summary pills + Report button -->
      <div class="flex flex-wrap items-start gap-3 justify-between">
        <div class="flex flex-wrap gap-3">
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-50 border border-slate-100">
            <span class="text-xs text-slate-400 uppercase tracking-wide">Total</span>
            <span class="text-lg font-bold text-slate-700">{{ summary.total }}</span>
          </div>
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-50 border border-emerald-100">
            <span class="text-xs text-emerald-500 uppercase tracking-wide">Complete</span>
            <span class="text-lg font-bold text-emerald-600">{{ summary.complete }}</span>
          </div>
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-50 border border-amber-100">
            <span class="text-xs text-amber-500 uppercase tracking-wide">Incomplete</span>
            <span class="text-lg font-bold text-amber-600">{{ summary.incomplete }}</span>
          </div>
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-50 border border-blue-100">
            <span class="text-xs text-blue-500 uppercase tracking-wide">Unique</span>
            <span class="text-lg font-bold text-blue-600">{{ summary.unique }}</span>
          </div>
        </div>

        <!-- Report to Superadmin button -->
        <button
          @click="openReportModal"
          :disabled="!hasUnreported || reporting"
          class="btn btn-primary gap-2 shrink-0"
        >
          <PaperAirplaneIcon class="w-4 h-4" />
          {{ reporting ? 'Reporting…' : 'Report to Superadmin' }}
          <span v-if="hasUnreported" class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
        </button>
      </div>

      <!-- Report result flash -->
      <Transition name="slide-fade">
        <div v-if="reportResult"
          :class="[
            'flex items-center gap-3 p-4 rounded-2xl border text-sm',
            reportResult.success
              ? 'bg-success-50 border-success-200 text-success-800'
              : 'bg-danger-50 border-danger-200 text-danger-800'
          ]"
        >
          <CheckCircleIcon v-if="reportResult.success" class="w-5 h-5 shrink-0" />
          <ExclamationCircleIcon v-else class="w-5 h-5 shrink-0" />
          <p>{{ reportResult.message }}</p>
          <button @click="reportResult = null" class="ml-auto text-slate-400 hover:text-slate-600">✕</button>
        </div>
      </Transition>

      <!-- Filters -->
      <div class="card p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <input v-model="filterForm.search" type="text" placeholder="Search name or ID..."
                 class="input col-span-2" @input="debouncedFilter" />
          <select v-model="filterForm.period" class="input" @change="applyFilters">
            <option value="">All Periods</option>
            <option v-for="p in periods" :key="p.value" :value="p.value">{{ p.label }}</option>
          </select>
          <select v-model="filterForm.barangay" class="input" @change="applyFilters">
            <option value="">All Barangays</option>
            <option v-for="b in barangays" :key="b" :value="b">{{ b }}</option>
          </select>
        </div>
        <div class="flex gap-2 mt-3">
          <select v-model="filterForm.status" class="input w-48" @change="applyFilters">
            <option value="">All Status</option>
            <option value="complete">Complete Only</option>
            <option value="incomplete">Incomplete Only</option>
          </select>
        </div>
      </div>

      <!-- Records table -->
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="px-4 py-3 text-left">Beneficiary</th>
                <th class="px-4 py-3 text-left">Session</th>
                <th class="px-4 py-3 text-left">Date</th>
                <th class="px-4 py-3 text-left">Period</th>
                <th class="px-4 py-3 text-center">Check-In</th>
                <th class="px-4 py-3 text-center">Check-Out</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3 text-center">Reported</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-if="records.data.length === 0">
                <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                  No FDS attendance records found.
                </td>
              </tr>
              <tr v-for="record in records.data" :key="record.id"
                  class="hover:bg-slate-50/60 transition-colors">
                <td class="px-4 py-3">
                  <p class="font-medium text-slate-700">{{ record.beneficiary?.full_name }}</p>
                  <p class="text-xs text-slate-400">{{ record.beneficiary?.unique_id }} · {{ record.beneficiary?.barangay }}</p>
                </td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ record.session_title || '—' }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ formatDate(record.session_date) }}</td>
                <td class="px-4 py-3">
                  <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">
                    {{ record.period }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span v-if="record.checked_in_at" class="text-xs text-emerald-600 font-medium">
                    {{ formatTime(record.checked_in_at) }}
                  </span>
                  <span v-else class="text-xs text-slate-300">—</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span v-if="record.checked_out_at" class="text-xs text-orange-600 font-medium">
                    {{ formatTime(record.checked_out_at) }}
                  </span>
                  <span v-else class="text-xs text-slate-300">—</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span :class="['badge badge-sm', record.is_complete ? 'badge-success' : 'badge-warning']">
                    {{ record.is_complete ? 'Complete' : 'Incomplete' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span v-if="record.is_reported" class="badge badge-sm badge-info">Reported</span>
                  <span v-else class="text-xs text-slate-300">—</span>
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
                  :class="['btn btn-sm', link.active ? 'btn-primary' : 'btn-secondary',
                    !link.url ? 'opacity-40 pointer-events-none' : '']" />
          </div>
        </div>
      </div>

    </div>

    <!-- Report to Superadmin Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showReportModal"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
          @click.self="showReportModal = false"
        >
          <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 rounded-2xl bg-brand-50 flex items-center justify-center">
                <PaperAirplaneIcon class="w-5 h-5 text-brand-600" />
              </div>
              <div>
                <h2 class="text-base font-bold text-slate-800">Report to Superadmin</h2>
                <p class="text-xs text-slate-400">{{ currentPeriod?.label }}</p>
              </div>
            </div>

            <div class="bg-blue-50 rounded-2xl p-4 mb-5 text-sm text-blue-800">
              <p class="font-semibold mb-1">This will mark all complete attendance records as "Reported".</p>
              <ul class="text-xs space-y-1 mt-2 text-blue-700 list-disc list-inside">
                <li>Only <strong>complete</strong> records (check-in + check-out) will be reported</li>
                <li>Superadmin will see this period's attendance is finalized</li>
                <li>This action is logged in the audit trail</li>
              </ul>
            </div>

            <div class="flex gap-3">
              <button @click="showReportModal = false" class="btn btn-secondary flex-1">Cancel</button>
              <button @click="submitReport" :disabled="reporting" class="btn btn-primary flex-1 gap-2">
                <PaperAirplaneIcon class="w-4 h-4" />
                {{ reporting ? 'Reporting…' : 'Confirm Report' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </StaffLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import {
  CheckCircleIcon, ExclamationCircleIcon, PaperAirplaneIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  records:        Object,
  barangays:      Array,
  periods:        Array,
  summary:        Object,
  hasUnreported:  Boolean,
  currentPeriod:  Object,
  filters:        Object,
})

const filterForm = ref({ ...props.filters })
let debounceTimer = null

const applyFilters = () => {
  router.get(route('admin4ps.fds.index'), filterForm.value, {
    preserveState: true, preserveScroll: true, replace: true,
  })
}
const debouncedFilter = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(applyFilters, 400)
}

// ─── Report to Superadmin ──────────────────────────────────────────────────
const showReportModal = ref(false)
const reporting       = ref(false)
const reportResult    = ref(null)

const openReportModal = () => { showReportModal.value = true }

const submitReport = async () => {
  reporting.value = true
  showReportModal.value = false
  reportResult.value = null

  try {
    const res = await axios.post(route('admin4ps.fds.report'), {
      period: props.currentPeriod?.value ?? props.filters?.period,
    })
    reportResult.value = { success: true, message: res.data.message }
    router.reload({ only: ['records', 'summary', 'hasUnreported'] })
  } catch (err) {
    reportResult.value = {
      success: false,
      message: err.response?.data?.message ?? 'Failed to report. Please try again.',
    }
  } finally {
    reporting.value = false
  }
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
const formatTime = (d) => d ? new Date(d).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }) : '—'
</script>

<style scoped>
.slide-fade-enter-active { transition: all 0.3s ease; }
.slide-fade-leave-active { transition: all 0.2s ease; }
.slide-fade-enter-from   { opacity: 0; transform: translateY(-8px); }
.slide-fade-leave-to     { opacity: 0; transform: translateY(-4px); }

.modal-enter-active { transition: all 0.2s ease; }
.modal-leave-active { transition: all 0.15s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
