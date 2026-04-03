<template>
  <Head :title="`Audit Log #${log.id}`" />
  <StaffLayout
    :page-title="`Event: ${formatEvent(log.event)}`"
    :page-subtitle="`Log #${log.id} · ${formatDate(log.created_at)}`"
  >
    <div class="max-w-3xl mx-auto space-y-5">

      <!-- ─── Back ─────────────────────────────────────────────────────────── -->
      <Link :href="route('superadmin.audit-logs.index')" class="btn btn-ghost btn-sm gap-1.5 text-slate-500">
        <ArrowLeftIcon class="w-4 h-4" />
        Back to Audit Trail
      </Link>

      <!-- ─── Event Hero Card ──────────────────────────────────────────────── -->
      <div class="card overflow-hidden">
        <!-- Severity band -->
        <div :class="['h-1.5 w-full', severityBand]"></div>

        <div class="p-6">
          <div class="flex items-start justify-between gap-4 flex-wrap mb-6">
            <div>
              <span :class="['badge', eventBadgeClass(log.event), 'mb-2']">
                {{ formatEvent(log.event) }}
              </span>
              <h1 class="text-xl font-bold text-slate-800">{{ log.description || 'No description' }}</h1>
              <p class="text-sm text-slate-400 mt-0.5">{{ formatDateLong(log.created_at) }}</p>
            </div>
            <!-- Fraud warning -->
            <div v-if="isFraud" class="flex items-center gap-2 px-4 py-2 bg-danger-50 border border-danger-200 rounded-xl shrink-0">
              <ShieldExclamationIcon class="w-5 h-5 text-danger-600" />
              <div>
                <p class="text-xs font-bold text-danger-700">Security Alert</p>
                <p class="text-[10px] text-danger-500">This event flagged as potentially fraudulent</p>
              </div>
            </div>
          </div>

          <!-- Core meta grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="info-block">
              <p class="info-label">Log ID</p>
              <p class="info-value font-mono">#{{ log.id }}</p>
            </div>
            <div class="info-block">
              <p class="info-label">Event Type</p>
              <p class="info-value">{{ log.event }}</p>
            </div>
            <div class="info-block">
              <p class="info-label">Actor</p>
              <p class="info-value font-semibold">{{ log.user?.name ?? 'System / Guest' }}</p>
              <p class="text-[11px] text-slate-400 capitalize mt-0.5">{{ log.user_type ?? '—' }} role</p>
            </div>
            <div class="info-block">
              <p class="info-label">IP Address</p>
              <p class="info-value font-mono">{{ log.ip_address ?? '—' }}</p>
            </div>
            <div class="info-block sm:col-span-2">
              <p class="info-label">URL</p>
              <p class="info-value font-mono text-[11px] truncate">{{ log.url ?? '—' }}</p>
            </div>
            <div v-if="log.auditable_type" class="info-block sm:col-span-2">
              <p class="info-label">Related Record</p>
              <p class="info-value">
                <span class="font-mono text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">
                  {{ log.auditable_type.split('\\').pop() }} #{{ log.auditable_id }}
                </span>
              </p>
            </div>
            <div v-if="log.tags" class="info-block">
              <p class="info-label">Tags</p>
              <div class="flex flex-wrap gap-1.5 mt-1">
                <span v-for="tag in log.tags.split(',')" :key="tag"
                  class="badge badge-sm badge-neutral capitalize">
                  {{ tag.trim() }}
                </span>
              </div>
            </div>
            <div class="info-block">
              <p class="info-label">User Agent</p>
              <p class="info-value text-[10px] font-mono text-slate-400 leading-relaxed truncate" :title="log.user_agent ?? ''">
                {{ log.user_agent ?? '—' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── Diff: Old → New Values ──────────────────────────────────────── -->
      <div v-if="log.old_values || log.new_values" class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <!-- Old values -->
        <div class="card overflow-hidden">
          <div class="px-5 py-3 border-b border-slate-100 flex items-center gap-2 bg-danger-50">
            <MinusCircleIcon class="w-4 h-4 text-danger-500" />
            <h3 class="text-sm font-semibold text-danger-700">Before (Old Values)</h3>
          </div>
          <div v-if="log.old_values && Object.keys(log.old_values).length" class="divide-y divide-slate-50">
            <div v-for="(val, key) in log.old_values" :key="key"
              class="flex items-start gap-3 px-5 py-2.5">
              <span class="text-xs font-mono text-slate-400 min-w-[110px] shrink-0 pt-0.5">{{ key }}</span>
              <span class="text-xs text-danger-700 bg-danger-50 px-2 py-0.5 rounded font-mono break-all">
                {{ formatValue(val) }}
              </span>
            </div>
          </div>
          <div v-else class="px-5 py-8 text-center text-slate-400 text-xs">
            No old values recorded
          </div>
        </div>

        <!-- New values -->
        <div class="card overflow-hidden">
          <div class="px-5 py-3 border-b border-slate-100 flex items-center gap-2 bg-success-50">
            <PlusCircleIcon class="w-4 h-4 text-success-600" />
            <h3 class="text-sm font-semibold text-success-700">After (New Values)</h3>
          </div>
          <div v-if="log.new_values && Object.keys(log.new_values).length" class="divide-y divide-slate-50">
            <div v-for="(val, key) in log.new_values" :key="key"
              class="flex items-start gap-3 px-5 py-2.5">
              <span class="text-xs font-mono text-slate-400 min-w-[110px] shrink-0 pt-0.5">{{ key }}</span>
              <span class="text-xs text-success-700 bg-success-50 px-2 py-0.5 rounded font-mono break-all">
                {{ formatValue(val) }}
              </span>
            </div>
          </div>
          <div v-else class="px-5 py-8 text-center text-slate-400 text-xs">
            No new values recorded
          </div>
        </div>
      </div>

      <!-- ─── No diff notice ───────────────────────────────────────────────── -->
      <div v-else class="card px-5 py-6 flex items-center gap-3 text-slate-400">
        <InformationCircleIcon class="w-5 h-5 shrink-0" />
        <p class="text-sm">This event has no data change record (read-only or system event).</p>
      </div>

      <!-- ─── Footer nav ─────────────────────────────────────────────────────── -->
      <div class="flex justify-between">
        <Link :href="route('superadmin.audit-logs.index')" class="btn btn-secondary gap-1.5">
          <ArrowLeftIcon class="w-4 h-4" />
          Back to All Logs
        </Link>
        <a
          :href="route('superadmin.audit-logs.export')"
          class="btn btn-ghost gap-1.5 text-slate-500"
          download
        >
          <ArrowDownTrayIcon class="w-4 h-4" />
          Export CSV
        </a>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  ArrowLeftIcon, ShieldExclamationIcon, MinusCircleIcon,
  PlusCircleIcon, InformationCircleIcon, ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({ log: Object })

// ─── Event classification ──────────────────────────────────────────────────────
const FRAUD_KEYS = ['double_claim', 'qr_scan_failed', 'login_failed']
const WARN_KEYS  = ['deleted', 'destroy', 'failed']
const INFO_KEYS  = ['login', 'created', 'grant_released', 'qr_scanned']
const COMP_KEYS  = ['compliance']

const isFraud = computed(() => FRAUD_KEYS.some(f => props.log.event?.includes(f)))

const eventBadgeClass = (e) => {
  if (!e) return 'badge-neutral'
  if (FRAUD_KEYS.some(f => e.includes(f))) return 'badge-danger'
  if (WARN_KEYS.some(f => e.includes(f)))  return 'badge-warning'
  if (COMP_KEYS.some(f => e.includes(f)))  return 'badge-success'
  if (INFO_KEYS.some(f => e.includes(f)))  return 'badge-info'
  return 'badge-neutral'
}

const severityBand = computed(() => {
  const e = props.log.event ?? ''
  if (FRAUD_KEYS.some(f => e.includes(f))) return 'bg-danger-500'
  if (WARN_KEYS.some(f => e.includes(f)))  return 'bg-amber-400'
  if (COMP_KEYS.some(f => e.includes(f)))  return 'bg-emerald-500'
  if (INFO_KEYS.some(f => e.includes(f)))  return 'bg-blue-500'
  return 'bg-slate-300'
})

// ─── Formatters ──────────────────────────────────────────────────────────────
const formatEvent = (e) =>
  (e ?? '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())

const formatDate = (d) =>
  d ? new Date(d).toLocaleString('en-PH', { dateStyle: 'short', timeStyle: 'short' }) : '—'

const formatDateLong = (d) =>
  d ? new Date(d).toLocaleString('en-PH', {
    weekday: 'long', year: 'numeric', month: 'long',
    day: 'numeric', hour: '2-digit', minute: '2-digit',
  }) : '—'

const formatValue = (v) => {
  if (v === null || v === undefined) return 'null'
  if (typeof v === 'boolean') return v ? 'true' : 'false'
  if (typeof v === 'object') return JSON.stringify(v, null, 2)
  return String(v)
}
</script>

<style scoped>
.info-block {
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-radius: 0.75rem;
  border: 1px solid #f1f5f9;
}
.info-label {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #94a3b8;
  margin-bottom: 0.25rem;
}
.info-value {
  font-size: 0.8125rem;
  color: #1e293b;
}
</style>
