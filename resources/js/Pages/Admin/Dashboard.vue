<template>
  <Head title="Admin Dashboard" />
  <StaffLayout page-title="Dashboard" page-subtitle="Overview of program operations — DSWD Lipa City">
    <div class="space-y-6">

      <!-- Greeting -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 class="text-xl font-bold text-slate-800">Good {{ timeOfDay }}, {{ firstName }}! 👋</h1>
          <p class="text-sm text-slate-400 mt-0.5">{{ todayLong }}</p>
        </div>
        <div class="flex items-center gap-2">
          <a :href="route('admin.reports.dashboard-pdf')" target="_blank"
            class="btn btn-secondary gap-2 self-start">
            <ArrowDownTrayIcon class="w-4 h-4" />
            Download PDF Report
          </a>
          <Link :href="route('admin.events.create')" class="btn btn-primary gap-2 self-start">
            <PlusIcon class="w-4 h-4" />
            New Distribution Event
          </Link>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="card p-5 flex items-start gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
            <UsersIcon class="w-5 h-5 text-brand-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Beneficiaries</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.beneficiaries.toLocaleString() }}</p>
            <p class="text-xs text-slate-400 mt-1"><span class="text-success-600 font-semibold">{{ complianceRate }}%</span> compliant</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-brand-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <div class="card p-5 flex items-start gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-success-50 flex items-center justify-center shrink-0">
            <CheckCircleIcon class="w-5 h-5 text-success-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Compliant HH</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.compliant.toLocaleString() }}</p>
            <p class="text-xs text-slate-400 mt-1"><span class="text-danger-500 font-semibold">{{ stats.pending_compliance }}</span> pending</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-success-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <div class="card p-5 flex items-start gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
            <BanknotesIcon class="w-5 h-5 text-purple-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">This Month</p>
            <p class="text-lg font-bold text-slate-800 mt-0.5">₱{{ Number(stats.total_released_month).toLocaleString('en-PH', { minimumFractionDigits: 0 }) }}</p>
            <p class="text-xs text-slate-400 mt-1"><span class="text-purple-600 font-semibold">{{ stats.claimed_this_month }}</span> claims</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-purple-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <div class="card p-5 flex items-start gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
            <UserGroupIcon class="w-5 h-5 text-amber-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Active Staff</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ (stats.field_officers + stats.verifiers).toLocaleString() }}</p>
            <p class="text-xs text-slate-400 mt-1"><span class="text-amber-600 font-semibold">{{ stats.field_officers }}</span> officers · <span class="text-purple-600 font-semibold">{{ stats.verifiers }}</span> verifiers</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-amber-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <div class="card p-5 flex items-start gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
            <ExclamationTriangleIcon class="w-5 h-5 text-red-500" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Double Claims</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.double_claim_count.toLocaleString() }}</p>
            <p class="text-xs text-slate-400 mt-1">Fraud flags detected</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-red-50 rounded-tl-3xl opacity-50"></div>
        </div>
      </div>

      <!-- CHART ROW 1: Line chart (claims over time) + Double claims bar -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Line Chart: Claims over time -->
        <div class="card p-5 lg:col-span-2">
          <div class="flex items-center justify-between mb-4">
            <div>
              <p class="font-semibold text-slate-800 text-sm">Claims Over Time</p>
              <p class="text-xs text-slate-400 mt-0.5">Monthly claim count — last 12 months</p>
            </div>
            <ChartBarIcon class="w-5 h-5 text-brand-400" />
          </div>
          <div style="height:220px">
            <Line :data="lineChartData" :options="lineChartOptions" />
          </div>
        </div>

        <!-- Double-claim attempts per event -->
        <div class="card p-5">
          <div class="flex items-center justify-between mb-1">
            <p class="font-semibold text-slate-800 text-sm">Double-Claim Flags</p>
            <ExclamationTriangleIcon class="w-5 h-5 text-red-400" />
          </div>
          <p class="text-xs text-slate-400 mb-4">Fraud attempts per distribution event</p>

          <div v-if="!doubleClaimsByEvent?.length" class="py-8 text-center text-slate-400">
            <ShieldCheckIcon class="w-10 h-10 opacity-20 mx-auto mb-2" />
            <p class="text-xs">No fraud flags detected.</p>
          </div>

          <div v-else class="space-y-3">
            <div v-for="dc in doubleClaimsByEvent" :key="dc.event_id" class="space-y-1">
              <div class="flex items-center justify-between">
                <p class="text-xs text-slate-600 truncate max-w-[160px]" :title="dc.event_title">{{ dc.event_title }}</p>
                <span class="text-xs font-bold text-red-600 shrink-0 ml-2">{{ dc.attempts }}</span>
              </div>
              <div class="w-full h-1.5 bg-red-50 rounded-full overflow-hidden">
                <div class="h-full rounded-full bg-linear-to-r from-red-500 to-red-300 transition-all"
                  :style="`width:${maxDC > 0 ? Math.round((dc.attempts / maxDC) * 100) : 0}%`" />
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- CHART ROW 2: 3 horizontal bar charts -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Claims per barangay -->
        <div class="card p-5">
          <div class="flex items-center justify-between mb-1">
            <p class="font-semibold text-slate-800 text-sm">Claims per Barangay</p>
            <MapPinIcon class="w-5 h-5 text-brand-400" />
          </div>
          <p class="text-xs text-slate-400 mb-4">Top barangays by claims processed</p>
          <div style="height:280px">
            <Bar :data="claimsByBarangayData" :options="hBarOptions('#6366f1')" />
          </div>
        </div>

        <!-- Beneficiaries per barangay -->
        <div class="card p-5">
          <div class="flex items-center justify-between mb-1">
            <p class="font-semibold text-slate-800 text-sm">Beneficiaries per Barangay</p>
            <UsersIcon class="w-5 h-5 text-emerald-400" />
          </div>
          <p class="text-xs text-slate-400 mb-4">Active enrolled households per barangay</p>
          <div style="height:280px">
            <Bar :data="beneficiariesByBarangayData" :options="hBarOptions('#10b981')" />
          </div>
        </div>

        <!-- Unique claimers per barangay -->
        <div class="card p-5">
          <div class="flex items-center justify-between mb-1">
            <p class="font-semibold text-slate-800 text-sm">Claimers per Barangay</p>
            <CheckCircleIcon class="w-5 h-5 text-purple-400" />
          </div>
          <p class="text-xs text-slate-400 mb-4">Unique beneficiaries who have claimed</p>
          <div style="height:280px">
            <Bar :data="claimingByBarangayData" :options="hBarOptions('#8b5cf6')" />
          </div>
        </div>

      </div>

      <!-- Recent Claims + Events side by side -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Distribution Events Status -->
        <div class="card overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <CalendarDaysIcon class="w-5 h-5 text-brand-600" />
              <h2 class="font-semibold text-slate-800 text-sm">Distribution Events</h2>
            </div>
            <Link :href="route('admin.events.index')" class="text-xs text-brand-600 hover:underline">View All →</Link>
          </div>
          <div class="p-5 space-y-3">
            <div :class="['flex items-center gap-3 p-3 rounded-xl border', stats.ongoing_events > 0 ? 'bg-success-50 border-green-200' : 'bg-slate-50 border-slate-200']">
              <div :class="['w-9 h-9 rounded-lg flex items-center justify-center shrink-0', stats.ongoing_events > 0 ? 'bg-success-100' : 'bg-slate-100']">
                <BoltIcon :class="['w-4 h-4', stats.ongoing_events > 0 ? 'text-success-600' : 'text-slate-400']" />
              </div>
              <div class="flex-1">
                <p :class="['text-sm font-semibold', stats.ongoing_events > 0 ? 'text-success-700' : 'text-slate-500']">Ongoing</p>
                <p class="text-xs text-slate-400">Currently active</p>
              </div>
              <span :class="['text-xl font-bold', stats.ongoing_events > 0 ? 'text-success-600' : 'text-slate-400']">{{ stats.ongoing_events }}</span>
            </div>
            <div :class="['flex items-center gap-3 p-3 rounded-xl border', stats.upcoming_events > 0 ? 'bg-brand-50 border-brand-200' : 'bg-slate-50 border-slate-200']">
              <div :class="['w-9 h-9 rounded-lg flex items-center justify-center shrink-0', stats.upcoming_events > 0 ? 'bg-brand-100' : 'bg-slate-100']">
                <ClockIcon :class="['w-4 h-4', stats.upcoming_events > 0 ? 'text-brand-600' : 'text-slate-400']" />
              </div>
              <div class="flex-1">
                <p :class="['text-sm font-semibold', stats.upcoming_events > 0 ? 'text-brand-700' : 'text-slate-500']">Upcoming</p>
                <p class="text-xs text-slate-400">Scheduled events</p>
              </div>
              <span :class="['text-xl font-bold', stats.upcoming_events > 0 ? 'text-brand-600' : 'text-slate-400']">{{ stats.upcoming_events }}</span>
            </div>
          </div>
        </div>

        <!-- Recent Claims -->
        <div class="card overflow-hidden lg:col-span-2">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <BanknotesIcon class="w-5 h-5 text-success-600" />
              <h2 class="font-semibold text-slate-800 text-sm">Recent Cash Grant Claims</h2>
            </div>
            <span class="badge badge-neutral text-xs">Last 7</span>
          </div>
          <div v-if="!stats.recent_distributions?.length" class="px-5 py-14 text-center text-slate-400">
            <BanknotesIcon class="w-10 h-10 opacity-20 mx-auto mb-2" />
            <p class="text-sm">No claims recorded yet.</p>
          </div>
          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Beneficiary</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Event</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Claimed By</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Time</th>
                  <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Amount</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <tr v-for="dist in stats.recent_distributions" :key="dist.id" class="hover:bg-slate-50/50">
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                      <div class="w-7 h-7 rounded-full bg-brand-100 flex items-center justify-center shrink-0">
                        <span class="text-[10px] font-bold text-brand-600">{{ initials(dist.beneficiary?.full_name) }}</span>
                      </div>
                      <div>
                        <p class="font-medium text-slate-700 text-xs">{{ dist.beneficiary?.full_name ?? '—' }}</p>
                        <p class="text-[10px] text-slate-400 font-mono">{{ dist.beneficiary?.unique_id }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-5 py-3 text-xs text-slate-500 max-w-[130px] truncate">{{ dist.distribution_event?.title ?? '—' }}</td>
                  <td class="px-5 py-3">
                    <span :class="['badge badge-sm', dist.claimed_by_type === 'proxy' ? 'badge-warning' : 'badge-success']">
                      {{ dist.claimed_by_type === 'proxy' ? 'Via Proxy' : 'Self' }}
                    </span>
                  </td>
                  <td class="px-5 py-3 text-xs text-slate-400">{{ timeAgo(dist.claimed_at) }}</td>
                  <td class="px-5 py-3 text-right font-bold text-success-600 text-sm">
                    ₱{{ Number(dist.amount_released).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

    </div>
  </StaffLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
  UsersIcon, CheckCircleIcon, UserGroupIcon, BanknotesIcon,
  CalendarDaysIcon, BoltIcon, ClockIcon, PlusIcon, MapPinIcon,
  ExclamationTriangleIcon, ShieldCheckIcon, ArrowDownTrayIcon, ChartBarIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import { Line, Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale, LinearScale, PointElement, LineElement, BarElement,
  Title, Tooltip, Legend, Filler,
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler)

const props = defineProps({
  stats:                   Object,
  claimsByMonth:           Array,
  claimsByBarangay:        Array,
  beneficiariesByBarangay: Array,
  claimingByBarangay:      Array,
  doubleClaimsByEvent:     Array,
})

// ── Greeting ──────────────────────────────────────────────────────────────────
const timeOfDay = computed(() => {
  const h = new Date().getHours()
  return h < 12 ? 'morning' : h < 17 ? 'afternoon' : 'evening'
})
const todayLong = computed(() =>
  new Date().toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
)
const page = usePage()
const firstName = computed(() => (page.props.auth?.user?.name ?? 'Administrator').split(' ')[0])
const complianceRate = computed(() => {
  if (!props.stats.beneficiaries) return 0
  return Math.round((props.stats.compliant / props.stats.beneficiaries) * 100)
})

// ── Double-claim max for bar width ────────────────────────────────────────────
const maxDC = computed(() => Math.max(...(props.doubleClaimsByEvent?.map(d => d.attempts) ?? [0]), 1))

// ── LINE CHART: Claims over time ──────────────────────────────────────────────
const lineChartData = computed(() => ({
  labels: props.claimsByMonth?.map(r => r.label) ?? [],
  datasets: [{
    label: 'Claims',
    data: props.claimsByMonth?.map(r => r.total) ?? [],
    fill: true,
    borderColor: '#6366f1',
    backgroundColor: 'rgba(99,102,241,0.1)',
    borderWidth: 2,
    pointBackgroundColor: '#6366f1',
    pointRadius: 3,
    tension: 0.4,
  }],
}))

const lineChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: ctx => ` ${ctx.parsed.y} claim${ctx.parsed.y !== 1 ? 's' : ''}`,
      },
    },
  },
  scales: {
    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
    y: {
      beginAtZero: true,
      ticks: { stepSize: 1, font: { size: 10 } },
      grid: { color: 'rgba(0,0,0,0.04)' },
    },
  },
}

// ── HORIZONTAL BAR factory ────────────────────────────────────────────────────
const hBarOptions = (color) => ({
  indexAxis: 'y',
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.x.toLocaleString()}` } },
  },
  scales: {
    x: { beginAtZero: true, ticks: { font: { size: 9 }, stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.04)' } },
    y: { ticks: { font: { size: 9 } }, grid: { display: false } },
  },
})

const makeHBarData = (arr, labelKey, valueKey, color) => ({
  labels: arr?.map(r => r[labelKey]) ?? [],
  datasets: [{
    data: arr?.map(r => r[valueKey]) ?? [],
    backgroundColor: color + 'CC',
    borderColor: color,
    borderWidth: 1,
    borderRadius: 4,
  }],
})

const claimsByBarangayData      = computed(() => makeHBarData(props.claimsByBarangay,        'barangay', 'total_claims', '#6366f1'))
const beneficiariesByBarangayData = computed(() => makeHBarData(props.beneficiariesByBarangay, 'barangay', 'total',        '#10b981'))
const claimingByBarangayData    = computed(() => makeHBarData(props.claimingByBarangay,      'barangay', 'claimers',     '#8b5cf6'))

// ── Helpers ───────────────────────────────────────────────────────────────────
const initials = (name) =>
  (name ?? '—').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()

const timeAgo = (d) => {
  if (!d) return '—'
  const diff = Math.floor((Date.now() - new Date(d)) / 1000)
  if (diff < 60)    return `${diff}s ago`
  if (diff < 3600)  return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  return new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
}
</script>
