<template>
  <Head title="My Grants" />
  <BeneficiaryLayout :unread-count="unread_count ?? 0">
    <div class="space-y-5">

      <!-- Summary Hero -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl overflow-hidden shadow-lg border border-white/50">
        <div class="px-6 pt-6 pb-5 flex flex-col sm:flex-row sm:items-center gap-4">
          <div class="flex-1">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Grants Received</p>
            <p class="text-4xl font-bold text-brand-700">
              ₱{{ totalReceived.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
            </p>
            <p class="text-sm text-slate-400 mt-1">across {{ distributions.length }} claiming event(s)</p>
          </div>
          <div class="grid grid-cols-3 gap-3 sm:gap-4">
            <div class="text-center">
              <p class="text-2xl font-bold text-success-600">{{ distributions.length }}</p>
              <p class="text-xs text-slate-400 mt-0.5">Claimed</p>
            </div>
            <div class="text-center">
              <p class="text-2xl font-bold text-brand-600">{{ calculations.length }}</p>
              <p class="text-xs text-slate-400 mt-0.5">Computed</p>
            </div>
            <div class="text-center">
              <p class="text-2xl font-bold" :class="beneficiary.is_compliant ? 'text-success-600' : 'text-danger-600'">
                {{ beneficiary.is_compliant ? '✓' : '✗' }}
              </p>
              <p class="text-xs text-slate-400 mt-0.5">Compliant</p>
            </div>
          </div>
        </div>

        <!-- Grant rate table -->
        <div class="px-6 pb-6">
          <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Your Grant Breakdown (Per Release)</p>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="p-3 bg-success-50 rounded-xl border border-green-200">
              <div class="flex items-center gap-2 mb-1">
                <HeartIcon class="w-4 h-4 text-success-600" />
                <p class="text-xs font-semibold text-success-700">Health</p>
              </div>
              <p class="text-lg font-bold text-success-700">₱1,500</p>
              <p class="text-xs text-success-500">₱750 × 2 months</p>
            </div>
            <div class="p-3 bg-brand-50 rounded-xl border border-brand-200">
              <div class="flex items-center gap-2 mb-1">
                <AcademicCapIcon class="w-4 h-4 text-brand-600" />
                <p class="text-xs font-semibold text-brand-700">Education</p>
              </div>
              <p class="text-lg font-bold text-brand-700">
                ₱{{ educationEstimate.toLocaleString() }}
              </p>
              <p class="text-xs text-brand-500">Based on enrolled children</p>
            </div>
            <div class="p-3 bg-warning-50 rounded-xl border border-yellow-200">
              <div class="flex items-center gap-2 mb-1">
                <ShoppingBagIcon class="w-4 h-4 text-warning-600" />
                <p class="text-xs font-semibold text-warning-700">Rice Subsidy</p>
              </div>
              <p class="text-lg font-bold text-warning-700">₱1,200</p>
              <p class="text-xs text-warning-500">₱600 × 2 months</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Computed Grant History -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <CalculatorIcon class="w-5 h-5 text-brand-600" />
            <h2 class="font-semibold text-slate-800">Computed Grant Amounts</h2>
          </div>
          <span class="badge badge-neutral">{{ calculations.length }} periods</span>
        </div>

        <div v-if="calculations.length === 0" class="px-5 py-12 text-center text-slate-400">
          <CalculatorIcon class="w-10 h-10 opacity-20 mx-auto mb-2" />
          <p>No grants computed yet for your household.</p>
          <p class="text-xs mt-1">Grants are computed by admin before each distribution event.</p>
        </div>

        <div v-else class="divide-y divide-slate-50">
          <div v-for="(calc, idx) in calculations" :key="calc.id"
            class="px-5 py-4">
            <div class="flex items-start justify-between gap-4 mb-3">
              <div>
                <p class="font-semibold text-slate-700">{{ calc.distribution_event?.period ?? 'Period ' + (idx + 1) }}</p>
                <p class="text-xs text-slate-400">{{ calc.months_covered }} months covered</p>
              </div>
              <p class="text-xl font-bold text-brand-700 shrink-0">
                ₱{{ Number(calc.total_grant_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
              </p>
            </div>

            <!-- Breakdown if available -->
            <div v-if="breakdowns[idx]" class="grid grid-cols-3 gap-2">
              <div class="p-2 bg-success-50 rounded-lg text-center">
                <p class="text-xs text-success-500">Health</p>
                <p class="text-sm font-bold text-success-700">₱{{ Number(breakdowns[idx].health.amount).toLocaleString() }}</p>
              </div>
              <div class="p-2 bg-brand-50 rounded-lg text-center">
                <p class="text-xs text-brand-500">Education</p>
                <p class="text-sm font-bold text-brand-700">₱{{ Number(breakdowns[idx].education.total).toLocaleString() }}</p>
              </div>
              <div class="p-2 bg-warning-50 rounded-lg text-center">
                <p class="text-xs text-warning-500">Rice</p>
                <p class="text-sm font-bold text-warning-700">₱{{ Number(breakdowns[idx].rice.amount).toLocaleString() }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Claiming History -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <BanknotesIcon class="w-5 h-5 text-success-600" />
            <h2 class="font-semibold text-slate-800">Claiming History</h2>
          </div>
          <span class="badge badge-success">{{ distributions.length }} claim(s)</span>
        </div>

        <div v-if="distributions.length === 0" class="px-5 py-12 text-center text-slate-400">
          <BanknotesIcon class="w-10 h-10 opacity-20 mx-auto mb-2" />
          <p>No cash grants claimed yet.</p>
          <p class="text-xs mt-1">You will be notified when a distribution event is scheduled.</p>
        </div>

        <!-- Table view for desktop, card for mobile -->
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Period</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Claimed By</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date Claimed</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Amount</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="dist in distributions" :key="dist.id" class="hover:bg-slate-50/50">
                <td class="px-5 py-3 font-medium text-slate-700">
                  {{ dist.distribution_event?.period ?? '—' }}
                </td>
                <td class="px-5 py-3">
                  <span v-if="dist.claimed_by_type === 'proxy'" class="flex items-center gap-1.5 text-slate-600">
                    <UserGroupIcon class="w-4 h-4 text-slate-400" />
                    Via Proxy
                  </span>
                  <span v-else class="flex items-center gap-1.5 text-success-600 font-medium">
                    <CheckCircleIcon class="w-4 h-4" />
                    Self
                  </span>
                </td>
                <td class="px-5 py-3 text-slate-500">
                  {{ formatDateTime(dist.claimed_at) }}
                </td>
                <td class="px-5 py-3 text-right font-bold text-success-600">
                  ₱{{ Number(dist.amount_released).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                </td>
              </tr>
            </tbody>
            <tfoot class="border-t-2 border-slate-200 bg-slate-50">
              <tr>
                <td colspan="3" class="px-5 py-3 font-semibold text-slate-700 text-sm">Total Received</td>
                <td class="px-5 py-3 text-right font-bold text-brand-700 text-base">
                  ₱{{ totalReceived.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Legal footnote -->
      <div class="bg-white/20 backdrop-blur-sm rounded-xl px-5 py-3 text-center">
        <p class="text-white/50 text-xs">
          Grant amounts are computed per RA 11310 (Pantawid Pamilyang Pilipino Program Act).
          For disputes, contact your DSWD office.
        </p>
      </div>
    </div>
  </BeneficiaryLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import {
  HeartIcon, AcademicCapIcon, ShoppingBagIcon,
  BanknotesIcon, CalculatorIcon,
  CheckCircleIcon, UserGroupIcon,
} from '@heroicons/vue/24/outline'
import BeneficiaryLayout from '@/Layouts/BeneficiaryLayout.vue'

const props = defineProps({
  beneficiary:   Object,
  calculations:  Array,
  breakdowns:    Array,
  unread_count:  Number,
})

// Lazily load distributions from within calculations (or empty)
const distributions = computed(() =>
  props.beneficiary?.distributions ?? []
)

const totalReceived = computed(() =>
  distributions.value.reduce((sum, d) => sum + Number(d.amount_released ?? 0), 0)
)

// Estimate education based on family_members
const educationEstimate = computed(() => {
  const members = props.beneficiary?.family_members ?? []
  const schoolAge = members.filter(m => m.is_school_age)
  const capped = schoolAge.slice(0, 3) // max 3
  return capped.reduce((sum, m) => {
    const rates = { elementary: 600, junior_high: 1000, senior_high: 1400, daycare: 0, preschool: 0 }
    return sum + (rates[m.education_level] ?? 0)
  }, 0)
})

const formatDateTime = (d) =>
  d ? new Date(d).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—'
</script>
