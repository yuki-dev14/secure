<template>
  <Head title="Distribution Report" />
  <StaffLayout page-title="Distribution Report" page-subtitle="All cash grant claiming transactions and amounts released">
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

      <!-- Filters -->
      <div class="card p-4 flex flex-wrap gap-3">
        <select v-model="filters.event_id" class="form-select w-64" @change="applyFilters">
          <option value="">All Events</option>
          <option v-for="e in events" :key="e.id" :value="e.id">{{ e.title }} ({{ e.period }})</option>
        </select>
        <select v-model="filters.status" class="form-select w-44" @change="applyFilters">
          <option value="">All Statuses</option>
          <option value="claimed">Claimed</option>
          <option value="pending">Pending</option>
          <option value="forfeited">Forfeited</option>
        </select>
      </div>

      <!-- Totals banner -->
      <div class="grid grid-cols-3 gap-3">
        <div class="card px-4 py-4 text-center">
          <p class="text-2xl font-bold text-slate-800">{{ totals.count }}</p>
          <p class="text-xs text-slate-400 mt-0.5">Total Transactions</p>
        </div>
        <div class="card px-4 py-4 text-center">
          <p class="text-2xl font-bold text-success-700">{{ totals.claimed }}</p>
          <p class="text-xs text-slate-400 mt-0.5">Successfully Claimed</p>
        </div>
        <div class="card px-4 py-4 text-center relative overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-br from-success-50 to-emerald-50 opacity-60"></div>
          <p class="relative text-2xl font-bold text-success-700">
            ₱{{ Number(totals.released).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
          </p>
          <p class="relative text-xs text-slate-400 mt-0.5">Total Released</p>
        </div>
      </div>

      <!-- Table -->
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Transaction ID</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Beneficiary</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Event / Period</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Amount</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Claimed By</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Released By</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Claim Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-if="!distributions.data.length">
                <td colspan="8" class="text-center py-12 text-slate-400">No distribution records found.</td>
              </tr>
              <tr v-for="d in distributions.data" :key="d.id" class="hover:bg-slate-50/60 transition-colors">
                <td class="px-5 py-3 font-mono text-[11px] text-slate-500">{{ d.transaction_reference ?? '#' + d.id }}</td>
                <td class="px-5 py-3 font-mono text-xs text-brand-700">{{ d.beneficiary?.unique_id ?? '—' }}</td>
                <td class="px-5 py-3">
                  <p class="font-medium text-slate-700 text-xs">{{ d.distribution_event?.title ?? '—' }}</p>
                  <p class="text-[10px] text-slate-400">{{ d.distribution_event?.period ?? '' }}</p>
                </td>
                <td class="px-5 py-3 font-bold text-success-700">
                  ₱{{ Number(d.amount_released).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                </td>
                <td class="px-5 py-3">
                  <span :class="['badge badge-sm', d.status === 'claimed' ? 'badge-success' : d.status === 'forfeited' ? 'badge-danger' : 'badge-neutral']">
                    {{ d.status }}
                  </span>
                </td>
                <td class="px-5 py-3">
                  <span :class="['badge badge-sm', d.claimed_by_type === 'proxy' ? 'badge-warning' : 'badge-info']">
                    {{ d.claimed_by_type === 'proxy' ? '👤 Proxy' : '✓ Self' }}
                  </span>
                </td>
                <td class="px-5 py-3 text-xs text-slate-500">{{ d.releasedBy?.name ?? '—' }}</td>
                <td class="px-5 py-3 text-xs text-slate-400">{{ formatDate(d.claimed_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="distributions.last_page > 1" class="p-4 border-t border-slate-100 flex items-center justify-between">
          <p class="text-sm text-slate-500">{{ distributions.from }}–{{ distributions.to }} of {{ distributions.total }}</p>
          <div class="flex gap-1 flex-wrap">
            <Link v-for="link in distributions.links" :key="link.label" :href="link.url ?? '#'"
              :class="['btn btn-sm', link.active ? 'btn-primary' : 'btn-secondary', !link.url ? 'opacity-40 pointer-events-none' : '']"
              v-html="link.label" />
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

const props = defineProps({ distributions: Object, events: Array, totals: Object })

const filters = reactive({
  event_id: new URLSearchParams(window.location.search).get('event_id') ?? '',
  status:   new URLSearchParams(window.location.search).get('status') ?? '',
})

const applyFilters = () => {
  router.get(route('superadmin.reports.distributions'), {
    event_id: filters.event_id || undefined,
    status:   filters.status   || undefined,
  }, { preserveState: true, preserveScroll: true, replace: true })
}

const exportUrl = computed(() =>
  route('superadmin.reports.distributions.export') + '?' +
  new URLSearchParams({
    ...(filters.event_id && { event_id: filters.event_id }),
    ...(filters.status   && { status:   filters.status }),
  }).toString()
)

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-PH') : '—'
</script>
