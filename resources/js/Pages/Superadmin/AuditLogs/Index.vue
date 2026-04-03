<template>
  <Head title="Audit Trail" />
  <StaffLayout page-title="Audit Trail" page-subtitle="Complete system activity log — Superadmin access only">
    <div class="space-y-5">

      <!-- ─── Summary KPIs ────────────────────────────────────────────────── -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="card p-5 flex items-center gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
            <CalendarDaysIcon class="w-5 h-5 text-brand-600" />
          </div>
          <div>
            <p class="text-xs text-slate-400 uppercase tracking-wide font-medium">Events Today</p>
            <p class="text-2xl font-bold text-slate-800">{{ summary.today.toLocaleString() }}</p>
          </div>
          <div class="absolute bottom-0 right-0 w-12 h-12 bg-brand-50 rounded-tl-3xl opacity-50"></div>
        </div>
        <div class="card p-5 flex items-center gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-success-50 flex items-center justify-center shrink-0">
            <UserCircleIcon class="w-5 h-5 text-success-600" />
          </div>
          <div>
            <p class="text-xs text-slate-400 uppercase tracking-wide font-medium">Total Logins</p>
            <p class="text-2xl font-bold text-slate-800">{{ summary.logins.toLocaleString() }}</p>
          </div>
          <div class="absolute bottom-0 right-0 w-12 h-12 bg-success-50 rounded-tl-3xl opacity-50"></div>
        </div>
        <div class="card p-5 flex items-center gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-danger-50 flex items-center justify-center shrink-0">
            <ShieldExclamationIcon class="w-5 h-5 text-danger-600" />
          </div>
          <div>
            <p class="text-xs text-slate-400 uppercase tracking-wide font-medium">Fraud Attempts</p>
            <p class="text-2xl font-bold text-slate-800">{{ summary.fraud }}</p>
            <p v-if="summary.fraud > 0" class="text-xs text-danger-500 mt-0.5 font-medium">⚠ Requires review</p>
          </div>
          <div class="absolute bottom-0 right-0 w-12 h-12 bg-danger-50 rounded-tl-3xl opacity-50"></div>
        </div>
      </div>

      <!-- ─── Filters ──────────────────────────────────────────────────────── -->
      <div class="card p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <!-- Search -->
          <div class="relative lg:col-span-1">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
            <input
              v-model="filters.search"
              type="text"
              placeholder="Search description or IP…"
              class="form-input pl-9"
              @input="debounceFilter"
            />
          </div>

          <!-- Event type -->
          <select v-model="filters.event" class="form-select" @change="applyFilters">
            <option value="">All Event Types</option>
            <option v-for="e in events" :key="e" :value="e">{{ formatEvent(e) }}</option>
          </select>

          <!-- Date range -->
          <input v-model="filters.date_from" type="date" class="form-input" @change="applyFilters" title="From date" />
          <input v-model="filters.date_to"   type="date" class="form-input" @change="applyFilters" title="To date" />
        </div>

        <div class="flex items-center gap-2 mt-3">
          <button @click="applyFilters" class="btn btn-primary btn-sm gap-1.5">
            <MagnifyingGlassIcon class="w-4 h-4" />
            Search
          </button>
          <button @click="resetFilters" class="btn btn-secondary btn-sm gap-1.5">
            <ArrowPathIcon class="w-4 h-4" />
            Reset
          </button>
          <a
            :href="route('superadmin.audit-logs.export') + '?' + exportParams"
            class="btn btn-secondary btn-sm gap-1.5 ml-auto"
            download
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
            Export CSV
          </a>
        </div>
      </div>

      <!-- ─── Fraud alert banner ──────────────────────────────────────────── -->
      <div v-if="summary.fraud > 0"
        class="flex items-start gap-3 p-4 rounded-2xl border border-danger-200 bg-danger-50">
        <ShieldExclamationIcon class="w-5 h-5 text-danger-600 shrink-0 mt-0.5" />
        <div>
          <p class="text-sm font-bold text-danger-800">
            {{ summary.fraud }} double-claim attempt{{ summary.fraud > 1 ? 's' : '' }} detected
          </p>
          <p class="text-xs text-danger-600 mt-0.5">
            These events have been logged and flagged. Filter by "double_claim_attempt" to review.
          </p>
        </div>
        <button
          @click="filters.event = 'double_claim_attempt'; applyFilters()"
          class="ml-auto btn btn-danger btn-sm shrink-0"
        >
          Review Now
        </button>
      </div>

      <!-- ─── Logs Table ────────────────────────────────────────────────────── -->
      <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <ClipboardDocumentListIcon class="w-5 h-5 text-brand-600" />
            <h2 class="font-semibold text-slate-800 text-sm">Activity Log</h2>
          </div>
          <span class="badge badge-neutral text-xs">{{ logs.total.toLocaleString() }} entries</span>
        </div>

        <div v-if="!logs.data?.length" class="px-5 py-16 text-center text-slate-400">
          <ClipboardDocumentListIcon class="w-12 h-12 opacity-20 mx-auto mb-3" />
          <p class="font-medium">No audit records match your filters.</p>
          <p class="text-sm mt-1">Try widening your date range or clearing filters.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide w-8">#</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Event</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">User / Actor</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Description</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Model</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">IP Address</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date & Time</th>
                <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide w-10"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr
                v-for="log in logs.data"
                :key="log.id"
                :class="[
                  'hover:bg-slate-50/60 transition-colors group',
                  isFraud(log.event) ? 'bg-danger-50/40' : '',
                ]"
              >
                <!-- ID -->
                <td class="px-5 py-3 text-[10px] text-slate-300 font-mono">{{ log.id }}</td>

                <!-- Event badge -->
                <td class="px-5 py-3">
                  <span :class="['badge badge-sm', eventBadgeClass(log.event)]">
                    {{ formatEvent(log.event) }}
                  </span>
                </td>

                <!-- Actor -->
                <td class="px-5 py-3">
                  <p class="font-medium text-slate-700 text-xs">{{ log.user?.name ?? 'System' }}</p>
                  <span class="text-[10px] text-slate-400 capitalize">{{ log.user_type ?? '—' }}</span>
                </td>

                <!-- Description -->
                <td class="px-5 py-3 max-w-[240px]">
                  <p class="text-xs text-slate-600 truncate" :title="log.description ?? ''">
                    {{ log.description || '—' }}
                  </p>
                </td>

                <!-- Model -->
                <td class="px-5 py-3">
                  <span v-if="log.auditable_type" class="text-[10px] font-mono bg-slate-100 text-slate-500 px-2 py-0.5 rounded">
                    {{ log.auditable_type.split('\\').pop() }}#{{ log.auditable_id }}
                  </span>
                  <span v-else class="text-slate-300 text-xs">—</span>
                </td>

                <!-- IP -->
                <td class="px-5 py-3 text-xs text-slate-500 font-mono">{{ log.ip_address ?? '—' }}</td>

                <!-- Date -->
                <td class="px-5 py-3 text-xs text-slate-400 whitespace-nowrap">
                  {{ formatDate(log.created_at) }}
                </td>

                <!-- View -->
                <td class="px-5 py-3 text-right">
                  <Link
                    :href="route('superadmin.audit-logs.show', log.id)"
                    class="btn btn-ghost btn-icon btn-sm opacity-0 group-hover:opacity-100 transition-opacity"
                    title="View details"
                  >
                    <EyeIcon class="w-4 h-4" />
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="logs.last_page > 1" class="p-4 border-t border-slate-100 flex items-center justify-between">
          <p class="text-sm text-slate-500">
            Showing {{ logs.from }}–{{ logs.to }} of {{ logs.total.toLocaleString() }}
          </p>
          <div class="flex gap-1 flex-wrap">
            <Link
              v-for="link in logs.links"
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

      <!-- ─── Event type legend ─────────────────────────────────────────────── -->
      <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 px-1">
        <span class="badge badge-danger badge-sm">Fraud / Security</span>
        <span class="badge badge-warning badge-sm">Delete / Fail</span>
        <span class="badge badge-info badge-sm">Login / Create / Grant</span>
        <span class="badge badge-success badge-sm">Compliance</span>
        <span class="badge badge-neutral badge-sm">Other</span>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
  CalendarDaysIcon, UserCircleIcon, ShieldExclamationIcon,
  MagnifyingGlassIcon, ArrowPathIcon, ArrowDownTrayIcon,
  EyeIcon, ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  logs:    Object,
  events:  Array,
  summary: Object,
})

// ─── Filters ─────────────────────────────────────────────────────────────────
const filters = reactive({
  search:    new URLSearchParams(window.location.search).get('search')    ?? '',
  event:     new URLSearchParams(window.location.search).get('event')     ?? '',
  date_from: new URLSearchParams(window.location.search).get('date_from') ?? '',
  date_to:   new URLSearchParams(window.location.search).get('date_to')   ?? '',
})

let debounceTimer = null
const debounceFilter = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(applyFilters, 400)
}

const applyFilters = () => {
  router.get(route('superadmin.audit-logs.index'), {
    search:    filters.search    || undefined,
    event:     filters.event     || undefined,
    date_from: filters.date_from || undefined,
    date_to:   filters.date_to   || undefined,
  }, { preserveState: true, preserveScroll: true, replace: true })
}

const resetFilters = () => {
  filters.search = filters.event = filters.date_from = filters.date_to = ''
  applyFilters()
}

const exportParams = computed(() =>
  new URLSearchParams({
    ...(filters.date_from && { date_from: filters.date_from }),
    ...(filters.date_to   && { date_to:   filters.date_to   }),
    ...(filters.event     && { event:     filters.event     }),
  }).toString()
)

// ─── Event helpers ─────────────────────────────────────────────────────────────
const FRAUD_EVENTS  = ['double_claim_attempt', 'qr_scan_failed', 'login_failed']
const WARN_EVENTS   = ['deleted', 'destroy', 'failed']
const INFO_EVENTS   = ['login', 'created', 'grant_released', 'qr_scanned']
const COMP_EVENTS   = ['compliance_recorded', 'compliance_updated']

const isFraud = (e) => FRAUD_EVENTS.some(f => e?.includes(f))

const eventBadgeClass = (e) => {
  if (!e) return 'badge-neutral'
  if (FRAUD_EVENTS.some(f => e.includes(f)))  return 'badge-danger'
  if (WARN_EVENTS.some(f => e.includes(f)))   return 'badge-warning'
  if (COMP_EVENTS.some(f => e.includes(f)))   return 'badge-success'
  if (INFO_EVENTS.some(f => e.includes(f)))   return 'badge-info'
  return 'badge-neutral'
}

const formatEvent = (e) => (e ?? '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())

const formatDate = (d) =>
  d ? new Date(d).toLocaleString('en-PH', { dateStyle: 'short', timeStyle: 'short' }) : '—'
</script>
