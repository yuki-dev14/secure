<template>
  <Head title="Grant Computation Report" />
  <StaffLayout page-title="Grant Computation Report" page-subtitle="Per-beneficiary grant breakdown — health, education, and rice subsidy">
    <div class="space-y-4">

      <div class="flex items-center justify-between">
        <Link :href="route('superadmin.reports.index')" class="btn btn-ghost btn-sm gap-1.5 text-slate-500">
          <ArrowLeftIcon class="w-4 h-4" /> Reports Hub
        </Link>
        <a :href="exportUrl" class="btn btn-primary btn-sm gap-1.5" download>
          <ArrowDownTrayIcon class="w-4 h-4" />
          Export CSV
        </a>
      </div>

      <!-- Filter -->
      <div class="card p-4 flex flex-wrap gap-3">
        <select v-model="filters.event_id" class="form-select w-64" @change="applyFilters">
          <option value="">All Events</option>
          <option v-for="e in events" :key="e.id" :value="e.id">{{ e.title }} ({{ e.period }})</option>
        </select>
      </div>

      <!-- Grant totals banner -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="card p-4 text-center">
          <p class="text-xs text-slate-400 mb-1">Health Grants</p>
          <p class="text-xl font-bold text-blue-700">₱{{ fmt(totals.total_health) }}</p>
          <p class="text-[10px] text-slate-400 mt-0.5">₱750/hh/month</p>
        </div>
        <div class="card p-4 text-center">
          <p class="text-xs text-slate-400 mb-1">Education Grants</p>
          <p class="text-xl font-bold text-purple-700">₱{{ fmt(totals.total_education) }}</p>
          <p class="text-[10px] text-slate-400 mt-0.5">₱300–700/child/month</p>
        </div>
        <div class="card p-4 text-center">
          <p class="text-xs text-slate-400 mb-1">Rice Subsidy</p>
          <p class="text-xl font-bold text-amber-700">₱{{ fmt(totals.total_rice) }}</p>
          <p class="text-[10px] text-slate-400 mt-0.5">₱600/hh/month</p>
        </div>
        <div class="card p-4 text-center relative overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-br from-success-50 to-emerald-50 opacity-60"></div>
          <p class="relative text-xs text-slate-400 mb-1">Grand Total</p>
          <p class="relative text-xl font-bold text-success-700">₱{{ fmt(totals.grand_total) }}</p>
          <p class="relative text-[10px] text-slate-400 mt-0.5">All grant types combined</p>
        </div>
      </div>

      <!-- Table -->
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr>
                <th class="th">Beneficiary</th>
                <th class="th">Event</th>
                <th class="th">Period</th>
                <th class="th">Months</th>
                <th class="th text-right">Health</th>
                <th class="th text-right">Education</th>
                <th class="th text-right">Rice</th>
                <th class="th text-right">Total</th>
                <th class="th">School-Age</th>
                <th class="th">Under-5</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-if="!grants.data.length">
                <td colspan="10" class="text-center py-12 text-slate-400">No grant computations found.</td>
              </tr>
              <tr v-for="g in grants.data" :key="g.id" class="hover:bg-slate-50/60 transition-colors">
                <td class="td font-mono text-xs text-brand-700">{{ g.beneficiary?.unique_id ?? '—' }}</td>
                <td class="td text-xs text-slate-600">{{ g.distribution_event?.title ?? '—' }}</td>
                <td class="td text-xs text-slate-500">{{ g.distribution_event?.period ?? '—' }}</td>
                <td class="td text-center text-xs text-slate-600">{{ g.months_covered }}</td>
                <td class="td text-right font-mono text-xs text-blue-700">₱{{ fmtCell(g.health_grant_amount) }}</td>
                <td class="td text-right font-mono text-xs text-purple-700">₱{{ fmtCell(g.education_grant_total) }}</td>
                <td class="td text-right font-mono text-xs text-amber-700">₱{{ fmtCell(g.rice_subsidy_amount) }}</td>
                <td class="td text-right font-mono text-sm font-bold text-success-700">₱{{ fmtCell(g.total_grant_amount) }}</td>
                <td class="td text-center text-xs text-slate-500">{{ g.elementary_children_count ?? 0 }}</td>
                <td class="td text-center text-xs text-slate-500">{{ g.junior_high_children_count ?? 0 }}</td>
              </tr>
            </tbody>

            <!-- Subtotal row -->
            <tfoot v-if="grants.data.length" class="bg-slate-50 border-t-2 border-slate-200">
              <tr>
                <td colspan="4" class="px-5 py-3 text-xs font-bold text-slate-500 uppercase">Page Subtotal</td>
                <td class="px-5 py-3 text-right font-bold text-xs text-blue-700">₱{{ fmtCell(pageSubtotals.health) }}</td>
                <td class="px-5 py-3 text-right font-bold text-xs text-purple-700">₱{{ fmtCell(pageSubtotals.education) }}</td>
                <td class="px-5 py-3 text-right font-bold text-xs text-amber-700">₱{{ fmtCell(pageSubtotals.rice) }}</td>
                <td class="px-5 py-3 text-right font-bold text-sm text-success-700">₱{{ fmtCell(pageSubtotals.total) }}</td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div v-if="grants.last_page > 1" class="p-4 border-t border-slate-100 flex items-center justify-between">
          <p class="text-sm text-slate-500">{{ grants.from }}–{{ grants.to }} of {{ grants.total }}</p>
          <div class="flex gap-1 flex-wrap">
            <Link v-for="link in grants.links" :key="link.label" :href="link.url ?? '#'"
              :class="['btn btn-sm', link.active ? 'btn-primary' : 'btn-secondary', !link.url ? 'opacity-40 pointer-events-none' : '']"
              v-html="link.label" />
          </div>
        </div>
      </div>

      <!-- Grant rate legend -->
      <div class="card p-4">
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">RA 11310 Grant Rates (Encoded)</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 text-xs">
          <div class="bg-blue-50 rounded-xl px-3 py-2 text-center">
            <p class="font-bold text-blue-700">₱750</p><p class="text-blue-500">Health / hh / mo</p>
          </div>
          <div class="bg-purple-50 rounded-xl px-3 py-2 text-center">
            <p class="font-bold text-purple-700">₱300</p><p class="text-purple-500">Elementary / child / mo</p>
          </div>
          <div class="bg-purple-50 rounded-xl px-3 py-2 text-center">
            <p class="font-bold text-purple-700">₱500</p><p class="text-purple-500">Jr. High / child / mo</p>
          </div>
          <div class="bg-purple-50 rounded-xl px-3 py-2 text-center">
            <p class="font-bold text-purple-700">₱700</p><p class="text-purple-500">Sr. High / child / mo</p>
          </div>
          <div class="bg-amber-50 rounded-xl px-3 py-2 text-center">
            <p class="font-bold text-amber-700">₱600</p><p class="text-amber-500">Rice / hh / mo</p>
          </div>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeftIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({ grants: Object, events: Array, totals: Object })

const filters = reactive({
  event_id: new URLSearchParams(window.location.search).get('event_id') ?? '',
})

const applyFilters = () => {
  router.get(route('superadmin.reports.grants'), {
    event_id: filters.event_id || undefined,
  }, { preserveState: true, preserveScroll: true, replace: true })
}

const exportUrl = computed(() =>
  route('superadmin.reports.grants.export') + '?' +
  new URLSearchParams({
    ...(filters.event_id && { event_id: filters.event_id }),
  }).toString()
)

const fmt     = (n) => Number(n ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })
const fmtCell = (n) => Number(n ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })

const pageSubtotals = computed(() => ({
  health:    props.grants.data.reduce((s, g) => s + Number(g.health_grant_amount ?? 0), 0),
  education: props.grants.data.reduce((s, g) => s + Number(g.education_grant_total ?? 0), 0),
  rice:      props.grants.data.reduce((s, g) => s + Number(g.rice_subsidy_amount ?? 0), 0),
  total:     props.grants.data.reduce((s, g) => s + Number(g.total_grant_amount ?? 0), 0),
}))
</script>
