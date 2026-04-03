<template>
  <Head title="Admin Dashboard" />
  <StaffLayout page-title="Dashboard" page-subtitle="Overview of program operations — DSWD Lipa City">
    <div class="space-y-6">

      <!-- ─── Greeting + date ──────────────────────────────────────────────── -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 class="text-xl font-bold text-slate-800">
            Good {{ timeOfDay }}, {{ firstName }}! 👋
          </h1>
          <p class="text-sm text-slate-400 mt-0.5">{{ todayLong }}</p>
        </div>
        <Link :href="route('admin.events.create')" class="btn btn-primary gap-2 self-start">
          <PlusIcon class="w-4 h-4" />
          New Distribution Event
        </Link>
      </div>

      <!-- ─── KPI Cards Row ─────────────────────────────────────────────────── -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- Beneficiaries -->
        <div class="card p-5 flex items-start gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
            <UsersIcon class="w-5 h-5 text-brand-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Active Beneficiaries</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.beneficiaries.toLocaleString() }}</p>
            <p class="text-xs text-slate-400 mt-1">
              <span class="text-success-600 font-semibold">{{ complianceRate }}%</span> compliant
            </p>
          </div>
          <div class="absolute bottom-0 right-0 w-16 h-16 bg-brand-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <!-- Compliant -->
        <div class="card p-5 flex items-start gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-success-50 flex items-center justify-center shrink-0">
            <CheckCircleIcon class="w-5 h-5 text-success-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Compliant HH</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.compliant.toLocaleString() }}</p>
            <p class="text-xs text-slate-400 mt-1">
              <span class="text-danger-500 font-semibold">{{ stats.pending_compliance }}</span> pending review
            </p>
          </div>
          <div class="absolute bottom-0 right-0 w-16 h-16 bg-success-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <!-- Staff -->
        <div class="card p-5 flex items-start gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
            <UserGroupIcon class="w-5 h-5 text-amber-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Active Staff</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">
              {{ (stats.field_officers + stats.verifiers).toLocaleString() }}
            </p>
            <p class="text-xs text-slate-400 mt-1">
              <span class="text-amber-600 font-semibold">{{ stats.field_officers }}</span> officers ·
              <span class="text-purple-600 font-semibold">{{ stats.verifiers }}</span> verifiers
            </p>
          </div>
          <div class="absolute bottom-0 right-0 w-16 h-16 bg-amber-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <!-- Released this month -->
        <div class="card p-5 flex items-start gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
            <BanknotesIcon class="w-5 h-5 text-purple-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Released This Month</p>
            <p class="text-xl font-bold text-slate-800 mt-0.5">
              ₱{{ Number(stats.total_released_month).toLocaleString('en-PH', { minimumFractionDigits: 0 }) }}
            </p>
            <p class="text-xs text-slate-400 mt-1">
              <span class="text-purple-600 font-semibold">{{ stats.claimed_this_month }}</span> claims processed
            </p>
          </div>
          <div class="absolute bottom-0 right-0 w-16 h-16 bg-purple-50 rounded-tl-3xl opacity-50"></div>
        </div>
      </div>

      <!-- ─── Compliance Progress Bar ──────────────────────────────────── -->
      <div class="card p-5">
        <div class="flex items-center justify-between mb-3">
          <div>
            <p class="font-semibold text-slate-800 text-sm">Program Compliance Overview</p>
            <p class="text-xs text-slate-400 mt-0.5">Household compliance rate for this period</p>
          </div>
          <span class="text-2xl font-bold text-brand-600">{{ complianceRate }}%</span>
        </div>
        <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-1000"
            :style="`width: ${complianceRate}%; background: linear-gradient(90deg, #6366f1, #8b5cf6)`"
          ></div>
        </div>
        <div class="flex items-center justify-between mt-2 text-xs text-slate-400">
          <span class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-success-400 inline-block"></span>
            Compliant: {{ stats.compliant }}
          </span>
          <span class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-danger-400 inline-block"></span>
            Non-Compliant: {{ stats.pending_compliance }}
          </span>
          <span class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-slate-300 inline-block"></span>
            Total Active: {{ stats.beneficiaries }}
          </span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ─── Distribution Events Status ──────────────────────────────── -->
        <div class="card overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <CalendarDaysIcon class="w-5 h-5 text-brand-600" />
              <h2 class="font-semibold text-slate-800 text-sm">Distribution Events</h2>
            </div>
            <Link :href="route('admin.events.index')" class="text-xs text-brand-600 hover:underline">
              View All →
            </Link>
          </div>

          <div class="p-5 space-y-3">
            <!-- Ongoing -->
            <div :class="['flex items-center gap-3 p-3 rounded-xl border', stats.ongoing_events > 0 ? 'bg-success-50 border-green-200' : 'bg-slate-50 border-slate-200']">
              <div :class="['w-9 h-9 rounded-lg flex items-center justify-center shrink-0', stats.ongoing_events > 0 ? 'bg-success-100' : 'bg-slate-100']">
                <BoltIcon :class="['w-4 h-4', stats.ongoing_events > 0 ? 'text-success-600' : 'text-slate-400']" />
              </div>
              <div class="flex-1">
                <p :class="['text-sm font-semibold', stats.ongoing_events > 0 ? 'text-success-700' : 'text-slate-500']">Ongoing</p>
                <p class="text-xs text-slate-400">Currently active distributions</p>
              </div>
              <span :class="['text-xl font-bold', stats.ongoing_events > 0 ? 'text-success-600' : 'text-slate-400']">
                {{ stats.ongoing_events }}
              </span>
            </div>

            <!-- Upcoming -->
            <div :class="['flex items-center gap-3 p-3 rounded-xl border', stats.upcoming_events > 0 ? 'bg-brand-50 border-brand-200' : 'bg-slate-50 border-slate-200']">
              <div :class="['w-9 h-9 rounded-lg flex items-center justify-center shrink-0', stats.upcoming_events > 0 ? 'bg-brand-100' : 'bg-slate-100']">
                <ClockIcon :class="['w-4 h-4', stats.upcoming_events > 0 ? 'text-brand-600' : 'text-slate-400']" />
              </div>
              <div class="flex-1">
                <p :class="['text-sm font-semibold', stats.upcoming_events > 0 ? 'text-brand-700' : 'text-slate-500']">Upcoming</p>
                <p class="text-xs text-slate-400">Scheduled events</p>
              </div>
              <span :class="['text-xl font-bold', stats.upcoming_events > 0 ? 'text-brand-600' : 'text-slate-400']">
                {{ stats.upcoming_events }}
              </span>
            </div>

            <!-- Latest Event Details -->
            <template v-if="stats.latest_event">
              <div class="pt-3 border-t border-slate-100">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Latest Event</p>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                  <p class="text-sm font-semibold text-slate-800">{{ stats.latest_event.name }}</p>
                  <p class="text-xs text-slate-400 mt-0.5">
                    {{ stats.latest_event.office?.name ?? 'All offices' }}
                  </p>
                  <div class="flex items-center gap-2 mt-2">
                    <span :class="['badge badge-sm', eventStatusBadge(stats.latest_event.status)]">
                      {{ stats.latest_event.status }}
                    </span>
                    <span class="text-xs text-slate-400">
                      {{ formatDate(stats.latest_event.distribution_date_start) }}
                    </span>
                  </div>
                </div>
              </div>
            </template>

            <div v-else class="pt-3 border-t border-slate-100 text-center py-4 text-slate-400">
              <CalendarDaysIcon class="w-8 h-8 opacity-20 mx-auto mb-1" />
              <p class="text-xs">No events yet.</p>
              <Link :href="route('admin.events.create')" class="text-xs text-brand-600 hover:underline mt-1 inline-block">
                Create first event →
              </Link>
            </div>
          </div>
        </div>

        <!-- ─── Recent Claims ─────────────────────────────────────────────── -->
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
            <p class="text-xs mt-1">Claims will appear here after a distribution event.</p>
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
                <tr v-for="dist in stats.recent_distributions" :key="dist.id" class="hover:bg-slate-50/50 transition-colors">
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                      <div class="w-7 h-7 rounded-full bg-brand-100 flex items-center justify-center shrink-0">
                        <span class="text-[10px] font-bold text-brand-600">
                          {{ initials(dist.beneficiary?.full_name) }}
                        </span>
                      </div>
                      <div>
                        <p class="font-medium text-slate-700 text-xs">{{ dist.beneficiary?.full_name ?? '—' }}</p>
                        <p class="text-[10px] text-slate-400 font-mono">{{ dist.beneficiary?.unique_id }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-5 py-3 text-xs text-slate-500 max-w-[130px] truncate">
                    {{ dist.distribution_event?.name ?? '—' }}
                  </td>
                  <td class="px-5 py-3">
                    <span v-if="dist.claimed_by_type === 'proxy'" class="badge badge-sm badge-warning">
                      Via Proxy
                    </span>
                    <span v-else class="badge badge-sm badge-success">Self</span>
                  </td>
                  <td class="px-5 py-3 text-xs text-slate-400">
                    {{ timeAgo(dist.claimed_at) }}
                  </td>
                  <td class="px-5 py-3 text-right font-bold text-success-600 text-sm">
                    ₱{{ Number(dist.amount_released).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ─── Quick Actions ─────────────────────────────────────────────────── -->
      <div class="card p-5">
        <p class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
          <BoltIcon class="w-4 h-4 text-brand-500" />
          Quick Actions
        </p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <Link :href="route('admin.users.create')" class="quick-action group">
            <div class="quick-action-icon bg-brand-50 group-hover:bg-brand-100">
              <UserPlusIcon class="w-5 h-5 text-brand-600" />
            </div>
            <p class="text-xs font-medium text-slate-700">Add Staff</p>
            <p class="text-[10px] text-slate-400">Create account</p>
          </Link>

          <Link :href="route('admin.events.create')" class="quick-action group">
            <div class="quick-action-icon bg-success-50 group-hover:bg-success-100">
              <CalendarDaysIcon class="w-5 h-5 text-success-600" />
            </div>
            <p class="text-xs font-medium text-slate-700">New Event</p>
            <p class="text-[10px] text-slate-400">Schedule distribution</p>
          </Link>

          <Link :href="route('admin.beneficiaries.index')" class="quick-action group">
            <div class="quick-action-icon bg-amber-50 group-hover:bg-amber-100">
              <ClipboardDocumentListIcon class="w-5 h-5 text-amber-600" />
            </div>
            <p class="text-xs font-medium text-slate-700">View Beneficiaries</p>
            <p class="text-[10px] text-slate-400">{{ stats.beneficiaries }} active</p>
          </Link>

          <Link :href="route('admin.users.index')" class="quick-action group">
            <div class="quick-action-icon bg-purple-50 group-hover:bg-purple-100">
              <UsersIcon class="w-5 h-5 text-purple-600" />
            </div>
            <p class="text-xs font-medium text-slate-700">Manage Staff</p>
            <p class="text-[10px] text-slate-400">{{ stats.field_officers + stats.verifiers }} active</p>
          </Link>
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
  CalendarDaysIcon, BoltIcon, ClockIcon, PlusIcon,
  UserPlusIcon, ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  stats: Object,
})

// ─── Greeting ─────────────────────────────────────────────────────────────────
const timeOfDay = computed(() => {
  const h = new Date().getHours()
  return h < 12 ? 'morning' : h < 17 ? 'afternoon' : 'evening'
})

const todayLong = computed(() =>
  new Date().toLocaleDateString('en-PH', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
  })
)

const page = usePage()

const firstName = computed(() => {
  const name = page.props.auth?.user?.name ?? 'Administrator'
  return name.split(' ')[0]
})

// ─── Stats ────────────────────────────────────────────────────────────────────
const complianceRate = computed(() => {
  if (!props.stats.beneficiaries) return 0
  return Math.round((props.stats.compliant / props.stats.beneficiaries) * 100)
})

const initials = (name) =>
  (name ?? '—').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()

// ─── Helpers ──────────────────────────────────────────────────────────────────
const eventStatusBadge = (status) => ({
  upcoming:  'badge-info',
  ongoing:   'badge-success',
  completed: 'badge-neutral',
  cancelled: 'badge-danger',
}[status] ?? 'badge-neutral')

const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'

const timeAgo = (d) => {
  if (!d) return '—'
  const diff = Math.floor((Date.now() - new Date(d)) / 1000)
  if (diff < 60)   return `${diff}s ago`
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  return formatDate(d)
}
</script>

<style scoped>
.quick-action {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
  text-align: center;
  cursor: pointer;
  transition: all 0.15s ease;
}
.quick-action:hover {
  border-color: #a5b4fc;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
.quick-action-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.15s ease;
}
</style>
