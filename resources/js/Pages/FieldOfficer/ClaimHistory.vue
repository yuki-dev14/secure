<template>
  <Head title="Claim History" />
  <StaffLayout page-title="Claim History" page-subtitle="Complete record of claimed beneficiaries and double-claim attempts">
    <div class="space-y-6">

      <!-- ── Summary Stats ────────────────────────────────────────────────── -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

        <div class="card p-5 flex items-start gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
            <CheckCircleIcon class="w-5 h-5 text-emerald-600" />
          </div>
          <div>
            <p class="text-[11px] text-slate-400 uppercase tracking-wide font-medium">Total Claims</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.total_claims.toLocaleString() }}</p>
            <p class="text-[10px] text-slate-400 mt-1">All-time successful</p>
          </div>
          <div class="absolute bottom-0 right-0 w-12 h-12 bg-emerald-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <div class="card p-5 flex items-start gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
            <BanknotesIcon class="w-5 h-5 text-purple-600" />
          </div>
          <div>
            <p class="text-[11px] text-slate-400 uppercase tracking-wide font-medium">Total Disbursed</p>
            <p class="text-lg font-bold text-slate-800 mt-0.5">
              ₱{{ Number(stats.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 0 }) }}
            </p>
            <p class="text-[10px] text-slate-400 mt-1">All-time disbursement</p>
          </div>
          <div class="absolute bottom-0 right-0 w-12 h-12 bg-purple-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <div class="card p-5 flex items-start gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
            <UsersIcon class="w-5 h-5 text-brand-600" />
          </div>
          <div>
            <p class="text-[11px] text-slate-400 uppercase tracking-wide font-medium">Unique Beneficiaries</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.unique_beneficiaries.toLocaleString() }}</p>
            <p class="text-[10px] text-slate-400 mt-1">Households served</p>
          </div>
          <div class="absolute bottom-0 right-0 w-12 h-12 bg-brand-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <div class="card p-5 flex items-start gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
            <ExclamationTriangleIcon class="w-5 h-5 text-red-500" />
          </div>
          <div>
            <p class="text-[11px] text-slate-400 uppercase tracking-wide font-medium">Double-Claim Attempts</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.total_double_claims.toLocaleString() }}</p>
            <p class="text-[10px] text-slate-400 mt-1">Fraud flags detected</p>
          </div>
          <div class="absolute bottom-0 right-0 w-12 h-12 bg-red-50 rounded-tl-3xl opacity-50"></div>
        </div>

      </div>

      <!-- ── Filters ──────────────────────────────────────────────────────── -->
      <div class="card p-4 flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[200px]">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
          <input
            id="claim-search"
            v-model="localSearch"
            type="text"
            placeholder="Search by name, Unique ID or reference…"
            class="input input-sm pl-9 w-full"
            @keydown.enter="applyFilters"
          />
        </div>
        <select
          id="event-filter"
          v-model="localEventId"
          class="input input-sm w-44"
          @change="applyFilters"
        >
          <option value="">All Events</option>
          <option v-for="ev in events" :key="ev.id" :value="ev.id">
            {{ ev.title }}
          </option>
        </select>
        <button id="apply-filters-btn" class="btn btn-primary btn-sm gap-1.5" @click="applyFilters">
          <FunnelIcon class="w-4 h-4" />
          Filter
        </button>
        <button v-if="isFiltered" id="clear-filters-btn" class="btn btn-ghost btn-sm gap-1.5 text-slate-500" @click="clearFilters">
          <XMarkIcon class="w-4 h-4" />
          Clear
        </button>
      </div>

      <!-- ── Tabs ─────────────────────────────────────────────────────────── -->
      <div class="card overflow-hidden">

        <!-- Tab header -->
        <div class="flex border-b border-slate-100">
          <button
            id="tab-claims"
            :class="['px-5 py-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2',
              activeTab === 'claims'
                ? 'border-brand-600 text-brand-700 bg-brand-50/50'
                : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50']"
            @click="switchTab('claims')"
          >
            <CheckCircleIcon class="w-4 h-4" />
            Claimed Beneficiaries
            <span :class="['text-[10px] px-1.5 py-0.5 rounded-full font-semibold',
              activeTab === 'claims' ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-500']">
              {{ claims.total.toLocaleString() }}
            </span>
          </button>
          <button
            id="tab-double-claims"
            :class="['px-5 py-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2',
              activeTab === 'double_claims'
                ? 'border-red-500 text-red-700 bg-red-50/50'
                : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50']"
            @click="switchTab('double_claims')"
          >
            <ExclamationTriangleIcon class="w-4 h-4" />
            Double-Claim Attempts
            <span v-if="doubleClaims.total > 0"
              :class="['text-[10px] px-1.5 py-0.5 rounded-full font-semibold',
                activeTab === 'double_claims' ? 'bg-red-100 text-red-700' : 'bg-red-50 text-red-500']">
              {{ doubleClaims.total.toLocaleString() }}
            </span>
          </button>
        </div>

        <!-- ── TAB: Claimed Beneficiaries ───────────────────────────────── -->
        <div v-if="activeTab === 'claims'">
          <div v-if="!claims.data?.length" class="py-20 flex flex-col items-center text-slate-400">
            <CheckCircleIcon class="w-12 h-12 opacity-20 mb-3" />
            <p class="font-medium">No claims found</p>
            <p class="text-sm mt-1">
              {{ isFiltered ? 'Try adjusting your search or filter.' : 'No beneficiaries have claimed grants yet.' }}
            </p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Beneficiary</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Barangay</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Event</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Claimed By</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date & Time</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Reference</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Mode</th>
                  <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Amount</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <tr v-for="dist in claims.data" :key="dist.id" class="hover:bg-slate-50/60 group">

                  <!-- Beneficiary -->
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-2.5">
                      <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center shrink-0 border border-emerald-100">
                        <span class="text-[10px] font-bold text-emerald-700">
                          {{ initials(dist.beneficiary?.full_name) }}
                        </span>
                      </div>
                      <div>
                        <p class="font-medium text-slate-700 text-xs leading-tight">{{ dist.beneficiary?.full_name ?? '—' }}</p>
                        <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ dist.beneficiary?.unique_id ?? '—' }}</p>
                      </div>
                    </div>
                  </td>

                  <!-- Barangay -->
                  <td class="px-5 py-3 text-xs text-slate-500">
                    {{ dist.beneficiary?.barangay ?? '—' }}
                  </td>

                  <!-- Event -->
                  <td class="px-5 py-3 text-xs">
                    <p class="text-slate-600 font-medium leading-tight max-w-[130px] truncate">{{ dist.event?.title ?? '—' }}</p>
                    <span v-if="dist.event?.period" class="text-[10px] text-slate-400">{{ dist.event.period }}</span>
                  </td>

                  <!-- Claimed by -->
                  <td class="px-5 py-3">
                    <span :class="['badge badge-sm', dist.claimed_by_type === 'proxy' ? 'badge-warning' : 'badge-success']">
                      {{ dist.claimed_by_type === 'proxy' ? 'Via Proxy' : 'Self' }}
                    </span>
                    <p v-if="dist.proxy" class="text-[10px] text-slate-400 mt-0.5">{{ dist.proxy.full_name }}</p>
                  </td>

                  <!-- Date & Time -->
                  <td class="px-5 py-3 text-xs text-slate-500 whitespace-nowrap">
                    {{ dist.claimed_at }}
                  </td>

                  <!-- Transaction ref -->
                  <td class="px-5 py-3">
                    <span class="text-[10px] font-mono text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                      {{ dist.transaction_reference ?? '—' }}
                    </span>
                  </td>

                  <!-- Payment mode -->
                  <td class="px-5 py-3">
                    <span :class="['badge badge-sm', paymentModeBadge(dist.payment_mode)]">
                      {{ dist.payment_mode ?? '—' }}
                    </span>
                  </td>

                  <!-- Amount -->
                  <td class="px-5 py-3 text-right">
                    <span class="font-bold text-emerald-600">
                      ₱{{ Number(dist.amount_released).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                    </span>
                  </td>

                </tr>
              </tbody>
            </table>
          </div>

          <!-- Claims pagination -->
          <div v-if="claims.last_page > 1" class="p-4 border-t border-slate-100 flex items-center justify-between">
            <p class="text-sm text-slate-500">Showing {{ claims.from }}–{{ claims.to }} of {{ claims.total }}</p>
            <div class="flex gap-1">
              <Link
                v-for="link in claims.links"
                :key="link.label"
                :href="link.url ? paginationUrl(link.url, 'claims_page') : '#'"
                :class="['btn btn-sm', link.active ? 'btn-primary' : 'btn-secondary', !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : '']"
                v-html="link.label"
              />
            </div>
          </div>
        </div>

        <!-- ── TAB: Double-Claim Attempts ───────────────────────────────── -->
        <div v-if="activeTab === 'double_claims'">

          <!-- Alert banner -->
          <div class="mx-5 mt-4 rounded-xl p-3 bg-red-50 border border-red-200 flex items-start gap-2.5">
            <ExclamationTriangleIcon class="w-4 h-4 text-red-500 mt-0.5 shrink-0" />
            <p class="text-xs text-red-700">
              These are <strong>fraud alerts</strong> — beneficiaries who attempted to claim a grant they already received for the same event.
              Each attempt is automatically logged and blocked by the system.
            </p>
          </div>

          <div v-if="!doubleClaims.data?.length" class="py-20 flex flex-col items-center text-slate-400">
            <ShieldCheckIcon class="w-12 h-12 opacity-20 mb-3" />
            <p class="font-medium">No double-claim attempts recorded</p>
            <p class="text-sm mt-1">
              {{ isFiltered ? 'Try adjusting your search or filter.' : 'The system has not detected any fraud attempts.' }}
            </p>
          </div>

          <div v-else class="overflow-x-auto mt-4">
            <table class="w-full text-sm">
              <thead class="bg-red-50 border-b border-red-100">
                <tr>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-red-500 uppercase tracking-wide">Beneficiary</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-red-500 uppercase tracking-wide">Barangay</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-red-500 uppercase tracking-wide">Distribution Event</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-red-500 uppercase tracking-wide">Detected At</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-red-500 uppercase tracking-wide">Description</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-red-50/60">
                <tr v-for="dc in doubleClaims.data" :key="dc.id" class="hover:bg-red-50/40 group">

                  <!-- Beneficiary -->
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-2.5">
                      <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center shrink-0 border border-red-100">
                        <span class="text-[10px] font-bold text-red-500">
                          {{ initials(dc.beneficiary?.full_name) }}
                        </span>
                      </div>
                      <div>
                        <p class="font-medium text-slate-700 text-xs leading-tight">{{ dc.beneficiary?.full_name ?? 'Unknown' }}</p>
                        <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ dc.beneficiary?.unique_id ?? '—' }}</p>
                      </div>
                    </div>
                  </td>

                  <!-- Barangay -->
                  <td class="px-5 py-3 text-xs text-slate-500">
                    {{ dc.beneficiary?.barangay ?? '—' }}
                  </td>

                  <!-- Event -->
                  <td class="px-5 py-3 text-xs">
                    <p class="text-slate-600 font-medium leading-tight max-w-[140px] truncate">{{ dc.event?.title ?? '—' }}</p>
                    <span v-if="dc.event?.period" class="text-[10px] text-slate-400">{{ dc.event.period }}</span>
                  </td>

                  <!-- Detected at -->
                  <td class="px-5 py-3 text-xs text-slate-500 whitespace-nowrap">
                    {{ dc.detected_at }}
                  </td>

                  <!-- Description / Alert -->
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-1.5">
                      <span class="inline-flex items-center gap-1 badge badge-sm bg-red-100 text-red-700 border border-red-200">
                        <ExclamationTriangleIcon class="w-3 h-3" />
                        FRAUD FLAG
                      </span>
                      <span class="text-[10px] text-slate-400 truncate max-w-[180px]">{{ dc.description }}</span>
                    </div>
                  </td>

                </tr>
              </tbody>
            </table>
          </div>

          <!-- Double claims pagination -->
          <div v-if="doubleClaims.last_page > 1" class="p-4 border-t border-red-50 flex items-center justify-between">
            <p class="text-sm text-slate-500">Showing {{ doubleClaims.from }}–{{ doubleClaims.to }} of {{ doubleClaims.total }}</p>
            <div class="flex gap-1">
              <Link
                v-for="link in doubleClaims.links"
                :key="link.label"
                :href="link.url ? paginationUrl(link.url, 'dc_page') : '#'"
                :class="['btn btn-sm', link.active ? 'btn-primary' : 'btn-secondary', !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : '']"
                v-html="link.label"
              />
            </div>
          </div>

        </div>
      </div>

    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
  CheckCircleIcon, BanknotesIcon, UsersIcon, ExclamationTriangleIcon,
  MagnifyingGlassIcon, FunnelIcon, XMarkIcon, ShieldCheckIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  claims:       Object,
  doubleClaims: Object,
  events:       Array,
  stats:        Object,
  filters:      Object,
})

// ── Reactive filter state ─────────────────────────────────────────────────────
const localSearch  = ref(props.filters?.search ?? '')
const localEventId = ref(props.filters?.event_id ?? '')
const activeTab    = ref(props.filters?.tab ?? 'claims')

const isFiltered = computed(() => localSearch.value || localEventId.value)

// ── Tab switching ─────────────────────────────────────────────────────────────
const switchTab = (tab) => {
  activeTab.value = tab
  router.get(route('officer.claim-history'), {
    search:   localSearch.value,
    event_id: localEventId.value,
    tab,
  }, { preserveScroll: true, preserveState: true })
}

// ── Filter actions ────────────────────────────────────────────────────────────
const applyFilters = () => {
  router.get(route('officer.claim-history'), {
    search:   localSearch.value,
    event_id: localEventId.value,
    tab:      activeTab.value,
  }, { preserveScroll: true })
}

const clearFilters = () => {
  localSearch.value  = ''
  localEventId.value = ''
  router.get(route('officer.claim-history'), { tab: activeTab.value }, { preserveScroll: true })
}

// ── Pagination with current filters preserved ─────────────────────────────────
const paginationUrl = (url, pageParam) => {
  const u = new URL(url, window.location.origin)
  if (localSearch.value)  u.searchParams.set('search', localSearch.value)
  if (localEventId.value) u.searchParams.set('event_id', localEventId.value)
  u.searchParams.set('tab', activeTab.value)
  return u.pathname + u.search
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const initials = (name) =>
  (name ?? '—').split(' ').filter(Boolean).map(w => w[0]).join('').slice(0, 2).toUpperCase()

const paymentModeBadge = (mode) => ({
  cash:    'badge-success',
  check:   'badge-info',
  ewallet: 'badge-warning',
}[mode] ?? 'badge-neutral')
</script>
