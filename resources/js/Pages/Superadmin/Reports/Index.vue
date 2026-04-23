<template>
  <Head title="Reports & Exports" />
  <StaffLayout page-title="Reports & Exports" page-subtitle="Generate and download system data in CSV or PDF format">
    <div class="space-y-6">

      <!-- ─── Summary KPI strip ────────────────────────────────────────────── -->
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="card p-4 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
            <UsersIcon class="w-5 h-5 text-brand-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide">Beneficiaries</p>
            <p class="text-xl font-bold text-slate-800">{{ summary.beneficiaries.total }}</p>
            <p class="text-[10px] text-success-600">{{ summary.beneficiaries.active }} active</p>
          </div>
        </div>
        <div class="card p-4 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-success-50 flex items-center justify-center shrink-0">
            <BanknotesIcon class="w-5 h-5 text-success-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide">Released</p>
            <p class="text-xl font-bold text-slate-800">₱{{ formatAmount(summary.distributions.total_released) }}</p>
            <p class="text-[10px] text-success-600">{{ summary.distributions.claimed }} claims</p>
          </div>
        </div>
        <div class="card p-4 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
            <ClipboardDocumentCheckIcon class="w-5 h-5 text-blue-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide">Completion Records</p>
            <p class="text-xl font-bold text-slate-800">{{ summary.compliance.total }}</p>
            <p class="text-[10px] text-success-600">{{ summary.compliance.fully_compliant }} complete</p>
          </div>
        </div>
        <div class="card p-4 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
            <CalendarDaysIcon class="w-5 h-5 text-purple-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide">Events</p>
            <p class="text-xl font-bold text-slate-800">{{ summary.events.total }}</p>
            <p class="text-[10px] text-green-600">{{ summary.events.ongoing }} ongoing</p>
          </div>
        </div>
        <div class="card p-4 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-danger-50 flex items-center justify-center shrink-0">
            <ShieldExclamationIcon class="w-5 h-5 text-danger-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide">Fraud Flags</p>
            <p class="text-xl font-bold text-slate-800">{{ summary.audit.fraud }}</p>
            <p class="text-[10px] text-slate-400">{{ summary.audit.today }} events today</p>
          </div>
        </div>
      </div>

      <!-- ─── Report cards grid ─────────────────────────────────────────────── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <ReportCard
          v-for="r in reports" :key="r.id"
          :report="r"
        />
      </div>

      <!-- ─── Barangay compliance breakdown ─────────────────────────────────── -->
      <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <MapPinIcon class="w-5 h-5 text-brand-600" />
            <h2 class="font-semibold text-slate-800 text-sm">Compliance by Barangay</h2>
          </div>
          <a :href="route('superadmin.reports.beneficiaries.export')" class="btn btn-secondary btn-sm gap-1.5" download>
            <ArrowDownTrayIcon class="w-4 h-4" />
            Export Beneficiaries
          </a>
        </div>
        <div v-if="barangayBreakdown.length" class="divide-y divide-slate-50">
          <div v-for="row in barangayBreakdown" :key="row.barangay"
            class="flex items-center gap-4 px-5 py-3">
            <div class="w-32 shrink-0">
              <span class="text-sm font-medium text-slate-700">{{ row.barangay }}</span>
            </div>
            <div class="flex-1">
              <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                <div
                  class="h-2 bg-success-500 rounded-full transition-all"
                  :style="`width: ${row.total > 0 ? Math.round((row.compliant / row.total) * 100) : 0}%`"
                ></div>
              </div>
            </div>
            <div class="w-24 text-right shrink-0">
              <span class="text-xs text-slate-400">{{ row.compliant }}/{{ row.total }}</span>
              <span class="ml-2 text-xs font-bold text-success-600">
                {{ row.total > 0 ? Math.round((row.compliant / row.total) * 100) : 0 }}%
              </span>
            </div>
          </div>
        </div>
        <div v-else class="px-5 py-10 text-center text-slate-400 text-sm">
          No barangay data yet.
        </div>
      </div>

      <!-- ─── Recent Distributions ──────────────────────────────────────────── -->
      <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <BanknotesIcon class="w-5 h-5 text-success-600" />
            <h2 class="font-semibold text-slate-800 text-sm">Recent Distributions</h2>
          </div>
          <Link :href="route('superadmin.reports.distributions')" class="text-xs text-brand-600 hover:underline">
            Full report →
          </Link>
        </div>
        <div v-if="recentDistributions.length" class="divide-y divide-slate-50">
          <div v-for="d in recentDistributions" :key="d.id"
            class="flex items-center justify-between px-5 py-3">
            <div>
              <p class="text-sm font-medium text-slate-700">{{ d.beneficiary?.unique_id ?? '—' }}</p>
              <p class="text-xs text-slate-400">{{ d.distribution_event?.title ?? '—' }} · {{ d.claimed_by_type }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-bold text-success-700">₱{{ Number(d.amount_released).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
              <span :class="['badge badge-sm', d.status === 'claimed' ? 'badge-success' : 'badge-neutral']">{{ d.status }}</span>
            </div>
          </div>
        </div>
        <div v-else class="px-5 py-10 text-center text-slate-400 text-sm">No distributions yet.</div>
      </div>

    </div>
  </StaffLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import {
  UsersIcon, BanknotesIcon, ClipboardDocumentCheckIcon,
  CalendarDaysIcon, ShieldExclamationIcon, MapPinIcon,
  ArrowDownTrayIcon, DocumentChartBarIcon,
  ClipboardDocumentListIcon, CurrencyDollarIcon,
  CheckBadgeIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  summary:              Object,
  recentDistributions:  Array,
  barangayBreakdown:    Array,
})

const formatAmount = (n) =>
  Number(n ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const reports = [
  {
    id:          'beneficiaries',
    title:       'Beneficiary Report',
    description: 'All registered households — status, compliance, barangay breakdown.',
    icon:        'UsersIcon',
    color:       'brand',
    viewRoute:   'superadmin.reports.beneficiaries',
    exportRoute: 'superadmin.reports.beneficiaries.export',
    count:       props.summary.beneficiaries.total,
    countLabel:  'records',
  },
  {
    id:          'compliance',
    title:       'Compliance Report',
    description: 'Education, health, and FDS completion records per quarter.',
    icon:        'CheckBadgeIcon',
    color:       'success',
    viewRoute:   'superadmin.reports.compliance',
    exportRoute: 'superadmin.reports.compliance.export',
    count:       props.summary.compliance.total,
    countLabel:  'records',
  },
  {
    id:          'distributions',
    title:       'Distribution Report',
    description: 'All cash grant claiming transactions per distribution event.',
    icon:        'BanknotesIcon',
    color:       'green',
    viewRoute:   'superadmin.reports.distributions',
    exportRoute: 'superadmin.reports.distributions.export',
    count:       props.summary.distributions.total,
    countLabel:  'transactions',
  },
  {
    id:          'grants',
    title:       'Grant Computation Report',
    description: 'Per-beneficiary grant calculations — health, education, rice subsidy.',
    icon:        'CurrencyDollarIcon',
    color:       'amber',
    viewRoute:   'superadmin.reports.grants',
    exportRoute: 'superadmin.reports.grants.export',
    count:       null,
    countLabel:  '',
  },
]

// Inline ReportCard sub-component
const ReportCard = {
  props: ['report'],
  components: { ArrowDownTrayIcon, Link },
  setup(props) {
    const colorMap = {
      brand:   { bg: 'background: #eff6ff', icon: '#3b82f6', border: '#bfdbfe' },
      success: { bg: 'background: #f0fdf4', icon: '#22c55e', border: '#bbf7d0' },
      green:   { bg: 'background: #f0fdf4', icon: '#16a34a', border: '#86efac' },
      amber:   { bg: 'background: #fffbeb', icon: '#f59e0b', border: '#fde68a' },
    }
    return { colorMap }
  },
  template: `
    <div class="card p-5 flex flex-col gap-4 hover:shadow-md transition-shadow">
      <div class="flex items-start justify-between">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
          :style="'background:' + (colorMap[report.color]?.bg.split(':')[1] ?? '#f1f5f9')">
          <component :is="report.icon === 'UsersIcon' ? $options.components.UsersIconC
            : report.icon === 'CheckBadgeIcon' ? $options.components.CheckBadgeIconC
            : report.icon === 'BanknotesIcon' ? $options.components.BanknotesIconC
            : $options.components.CurrencyIconC" class="w-5 h-5" style="color: #6366f1" />
        </div>
        <span v-if="report.count !== null" class="badge badge-neutral text-[10px]">{{ report.count }} {{ report.countLabel }}</span>
      </div>
      <div>
        <h3 class="font-bold text-slate-800 text-sm leading-tight">{{ report.title }}</h3>
        <p class="text-xs text-slate-400 mt-1 leading-relaxed">{{ report.description }}</p>
      </div>
      <div class="flex gap-2 mt-auto">
        <a :href="route(report.viewRoute)" class="btn btn-secondary btn-sm flex-1 justify-center text-xs">
          View
        </a>
        <a :href="route(report.exportRoute)" class="btn btn-primary btn-sm flex-1 justify-center gap-1 text-xs" download>
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
          </svg>
          Export CSV
        </a>
      </div>
    </div>
  `,
}
</script>
