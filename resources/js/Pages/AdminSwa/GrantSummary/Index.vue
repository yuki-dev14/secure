<template>
  <Head title="Grant Summary" />
  <StaffLayout page-title="Grant Summary" page-subtitle="Bimonthly grant computation with non-compliance adjustments">
    <div class="space-y-6">

      <!-- Period selector & compute button -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <select v-model="selectedPeriod" class="input w-64" @change="changePeriod">
            <option v-for="p in periods" :key="p.value" :value="p.value">{{ p.label }}</option>
          </select>
        </div>
        <button @click="showComputeModal = true" class="btn btn-primary gap-2">
          <CalculatorIcon class="w-4 h-4" />
          Compute Grants
        </button>
      </div>

      <!-- Financial KPI cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-5 relative overflow-hidden">
          <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Total Grant Amount</p>
          <p class="text-2xl font-bold text-emerald-600 mt-1">₱{{ formatMoney(summary.total_amount) }}</p>
          <p class="text-xs text-slate-400 mt-1">{{ summary.eligible }} eligible beneficiaries</p>
          <div class="absolute top-3 right-3 w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
            <CurrencyDollarIcon class="w-4 h-4 text-emerald-500" />
          </div>
        </div>

        <div class="card p-5 relative overflow-hidden">
          <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Health Grants</p>
          <p class="text-2xl font-bold text-blue-600 mt-1">₱{{ formatMoney(summary.health_total) }}</p>
          <p class="text-xs text-slate-400 mt-1">
            <span class="text-red-500 font-semibold">{{ ncImpact.health_nc }}</span> zeroed
          </p>
          <div class="absolute top-3 right-3 w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
            <HeartIcon class="w-4 h-4 text-blue-500" />
          </div>
        </div>

        <div class="card p-5 relative overflow-hidden">
          <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Education Grants</p>
          <p class="text-2xl font-bold text-violet-600 mt-1">₱{{ formatMoney(summary.edu_total) }}</p>
          <p class="text-xs text-slate-400 mt-1">
            <span class="text-red-500 font-semibold">{{ ncImpact.education_elem_nc + ncImpact.education_jhs_nc + ncImpact.education_shs_nc }}</span> zeroed
          </p>
          <div class="absolute top-3 right-3 w-8 h-8 rounded-lg bg-violet-50 flex items-center justify-center">
            <AcademicCapIcon class="w-4 h-4 text-violet-500" />
          </div>
        </div>

        <div class="card p-5 relative overflow-hidden">
          <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Rice Subsidy</p>
          <p class="text-2xl font-bold text-amber-600 mt-1">₱{{ formatMoney(summary.rice_total) }}</p>
          <p class="text-xs text-slate-400 mt-1">
            <span class="text-red-500 font-semibold">{{ ncImpact.rice_nc }}</span> zeroed
          </p>
          <div class="absolute top-3 right-3 w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
            <ShoppingBagIcon class="w-4 h-4 text-amber-500" />
          </div>
        </div>
      </div>

      <!-- Status summary -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-4 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center">
            <UsersIcon class="w-4 h-4 text-slate-500" />
          </div>
          <div>
            <p class="text-lg font-bold text-slate-700">{{ summary.total_beneficiaries }}</p>
            <p class="text-xs text-slate-400">Total Active</p>
          </div>
        </div>
        <div class="card p-4 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center">
            <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
          </div>
          <div>
            <p class="text-lg font-bold text-emerald-600">{{ summary.eligible }}</p>
            <p class="text-xs text-slate-400">Fully Eligible</p>
          </div>
        </div>
        <div class="card p-4 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center">
            <ExclamationTriangleIcon class="w-4 h-4 text-amber-600" />
          </div>
          <div>
            <p class="text-lg font-bold text-amber-600">{{ summary.nc_adjusted }}</p>
            <p class="text-xs text-slate-400">Partial (NC Adjusted)</p>
          </div>
        </div>
        <div class="card p-4 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center">
            <XCircleIcon class="w-4 h-4 text-red-600" />
          </div>
          <div>
            <p class="text-lg font-bold text-red-600">{{ summary.ineligible }}</p>
            <p class="text-xs text-slate-400">Ineligible</p>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="card p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <input v-model="filterForm.search" type="text" placeholder="Search name or ID..."
                 class="input col-span-2" @input="debouncedFilter" />
          <select v-model="filterForm.eligible" class="input" @change="applyFilters">
            <option value="">All Status</option>
            <option value="true">Eligible</option>
            <option value="false">Ineligible</option>
          </select>
          <select v-model="filterForm.barangay" class="input" @change="applyFilters">
            <option value="">All Barangays</option>
            <option v-for="b in barangays" :key="b" :value="b">{{ b }}</option>
          </select>
        </div>
      </div>

      <!-- Grants table -->
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="px-4 py-3 text-left">Beneficiary</th>
                <th class="px-4 py-3 text-right">Health</th>
                <th class="px-4 py-3 text-right">Education</th>
                <th class="px-4 py-3 text-right">Rice</th>
                <th class="px-4 py-3 text-right font-bold">Total</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Notes</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-if="grants.data.length === 0">
                <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                  No grant calculations found. Select a period and click "Compute Grants".
                </td>
              </tr>
              <tr v-for="g in grants.data" :key="g.id" class="hover:bg-slate-25 transition-colors">
                <td class="px-4 py-3">
                  <p class="font-medium text-slate-700">{{ g.beneficiary?.full_name }}</p>
                  <p class="text-xs text-slate-400">{{ g.beneficiary?.unique_id }} · {{ g.beneficiary?.barangay }}</p>
                </td>
                <td class="px-4 py-3 text-right font-mono text-sm" :class="g.health_grant_eligible ? 'text-slate-700' : 'text-red-400 line-through'">
                  ₱{{ formatMoney(g.health_grant_amount) }}
                </td>
                <td class="px-4 py-3 text-right">
                  <span class="font-mono text-sm text-slate-700">₱{{ formatMoney(g.education_grant_total) }}</span>
                  <p v-if="g.elementary_children_count || g.junior_high_children_count || g.senior_high_children_count"
                     class="text-xs text-slate-400 mt-0.5">
                    {{ [
                      g.elementary_children_count ? `${g.elementary_children_count} elem` : '',
                      g.junior_high_children_count ? `${g.junior_high_children_count} JHS` : '',
                      g.senior_high_children_count ? `${g.senior_high_children_count} SHS` : '',
                    ].filter(Boolean).join(', ') }}
                  </p>
                </td>
                <td class="px-4 py-3 text-right font-mono text-sm" :class="g.rice_subsidy_eligible ? 'text-slate-700' : 'text-red-400 line-through'">
                  ₱{{ formatMoney(g.rice_subsidy_amount) }}
                </td>
                <td class="px-4 py-3 text-right font-mono text-sm font-bold"
                    :class="g.total_grant_amount > 0 ? 'text-emerald-600' : 'text-red-500'">
                  ₱{{ formatMoney(g.total_grant_amount) }}
                </td>
                <td class="px-4 py-3">
                  <span :class="[statusBadge(g), 'px-2 py-0.5 rounded-full text-xs font-semibold']">
                    {{ g.is_eligible ? (g.computation_notes ? 'Partial' : 'Eligible') : 'Ineligible' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <p v-if="g.computation_notes" class="text-xs text-red-500 max-w-[200px] truncate" :title="g.computation_notes">
                    {{ g.computation_notes }}
                  </p>
                  <p v-else-if="g.ineligibility_reason" class="text-xs text-slate-400 max-w-[200px] truncate" :title="g.ineligibility_reason">
                    {{ g.ineligibility_reason }}
                  </p>
                  <span v-else class="text-xs text-slate-300">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="grants.last_page > 1" class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
          <span class="text-xs text-slate-400">
            Showing {{ grants.from }}–{{ grants.to }} of {{ grants.total }}
          </span>
          <div class="flex gap-1">
            <Link v-for="link in grants.links" :key="link.label"
                  :href="link.url ?? '#'" v-html="link.label"
                  :class="['px-3 py-1.5 text-xs rounded-lg transition-colors',
                    link.active ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50',
                    !link.url ? 'opacity-40 pointer-events-none' : '']" />
          </div>
        </div>
      </div>

      <!-- Compute Modal -->
      <Teleport to="body">
        <div v-if="showComputeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40" @click="showComputeModal = false"></div>
          <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-lg font-bold text-slate-800">Compute Bimonthly Grants</h3>
            <p class="text-sm text-slate-500">
              This will calculate grants for <strong>all active beneficiaries</strong> using the non-compliance
              zero-out logic. Each component (Health, Education, Rice) is independently evaluated.
            </p>
            <div class="p-3.5 bg-brand-50/50 border border-brand-100 rounded-xl space-y-1">
              <p class="text-xs text-brand-600 uppercase tracking-wide font-bold">Selected Period</p>
              <p class="text-lg font-bold text-slate-800">{{ selectedPeriod }}</p>
              <p class="text-xs text-slate-500">
                Grants will be calculated for all active 4Ps households using bimonthly RA 11310 rates.
              </p>
            </div>
            <div v-if="computeResult" class="p-3 rounded-xl" :class="computeResult.success ? 'bg-emerald-50' : 'bg-red-50'">
              <p class="text-sm font-medium" :class="computeResult.success ? 'text-emerald-700' : 'text-red-700'">
                {{ computeResult.message }}
              </p>
              <div v-if="computeResult.results" class="mt-2 grid grid-cols-2 gap-1 text-xs text-slate-600">
                <span>Computed: {{ computeResult.results.computed }}</span>
                <span>Eligible: {{ computeResult.results.eligible }}</span>
                <span>Partial: {{ computeResult.results.partial }}</span>
                <span>Ineligible: {{ computeResult.results.ineligible }}</span>
                <span class="col-span-2 font-semibold text-emerald-700 mt-1">
                  Total: ₱{{ formatMoney(computeResult.results.total_amount) }}
                </span>
              </div>
            </div>
            <div class="flex justify-end gap-2">
              <button @click="showComputeModal = false" class="btn btn-secondary">Close</button>
              <button @click="runCompute" :disabled="computing"
                      class="btn btn-primary gap-2">
                <CalculatorIcon class="w-4 h-4" />
                {{ computing ? 'Computing...' : 'Run Computation' }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>

    </div>
  </StaffLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import {
  CurrencyDollarIcon, UsersIcon, ExclamationTriangleIcon,
  AcademicCapIcon, HeartIcon, CalculatorIcon,
} from '@heroicons/vue/24/outline'
import { CheckCircleIcon, XCircleIcon, ShoppingBagIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
  grants:        Object,
  summary:       Object,
  ncImpact:      Object,
  barangays:     Array,
  periods:       Array,
  events:        Array,
  filters:       Object,
  currentPeriod: String,
})

const selectedPeriod = ref(props.currentPeriod)
const filterForm = ref({ ...props.filters })
let debounceTimer = null

const changePeriod = () => {
  filterForm.value.period = selectedPeriod.value
  applyFilters()
}

const applyFilters = () => {
  router.get(route('adminswa.grant-summary.index'), {
    ...filterForm.value,
    period: selectedPeriod.value,
  }, { preserveState: true, preserveScroll: true })
}

const debouncedFilter = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(applyFilters, 400)
}

// Compute modal
const showComputeModal = ref(false)
const computeEventId   = ref('')
const computing        = ref(false)
const computeResult    = ref(null)

const runCompute = async () => {
  computing.value = true
  computeResult.value = null

  try {
    const res = await fetch(route('adminswa.grant-summary.compute'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        event_id: computeEventId.value || null,
        period: selectedPeriod.value,
      }),
    })
    computeResult.value = await res.json()
    if (computeResult.value.success) {
      // Refresh the page data
      router.reload({ only: ['grants', 'summary', 'ncImpact'] })
    }
  } catch {
    computeResult.value = { success: false, message: 'Network error. Please try again.' }
  } finally {
    computing.value = false
  }
}

// Helpers
const formatMoney = (v) => Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const statusBadge = (g) => {
  if (!g.is_eligible) return 'bg-red-100 text-red-700'
  if (g.computation_notes) return 'bg-amber-100 text-amber-700'
  return 'bg-emerald-100 text-emerald-700'
}
</script>
