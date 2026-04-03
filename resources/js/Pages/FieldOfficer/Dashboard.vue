<template>
  <Head title="Field Officer Dashboard" />
  <StaffLayout page-title="Dashboard" page-subtitle="Your shift overview and recent activity">
    <div class="space-y-6">

      <!-- Active Event Banner -->
      <div v-if="stats.ongoing_event"
        class="rounded-2xl p-5 flex items-start gap-4 text-white"
        style="background: linear-gradient(135deg, #10b981 0%, #0d9488 100%)"
      >
        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center shrink-0">
          <BoltIcon class="w-5 h-5" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-xs font-semibold uppercase tracking-widest opacity-80 mb-0.5">Active Distribution Event</p>
          <p class="font-bold text-lg">{{ stats.ongoing_event.title }}</p>
          <p class="text-sm opacity-80 mt-0.5">
            {{ stats.ongoing_event.venue }} · {{ stats.ongoing_event.period }}
          </p>
        </div>
        <Link :href="route('officer.scanner')" class="btn btn-sm bg-white text-emerald-700 hover:bg-emerald-50 shrink-0 gap-1.5">
          <QrCodeIcon class="w-4 h-4" />
          Open Scanner
        </Link>
      </div>

      <div v-else class="rounded-2xl p-5 flex items-center gap-4 bg-amber-50 border border-amber-200">
        <ExclamationTriangleIcon class="w-6 h-6 text-amber-500 shrink-0" />
        <div>
          <p class="font-semibold text-amber-800">No Active Event</p>
          <p class="text-sm text-amber-600 mt-0.5">No ongoing distribution event. Contact your administrator.</p>
        </div>
      </div>

      <!-- Today's Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="card p-5 flex items-start gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-success-50 flex items-center justify-center shrink-0">
            <CheckCircleIcon class="w-5 h-5 text-success-600" />
          </div>
          <div>
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Released Today</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.claimed_today }}</p>
            <p class="text-xs text-slate-400 mt-1">Claims you processed</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-success-50 rounded-tl-3xl opacity-60"></div>
        </div>

        <div class="card p-5 flex items-start gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
            <BanknotesIcon class="w-5 h-5 text-purple-600" />
          </div>
          <div>
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Amount Released</p>
            <p class="text-xl font-bold text-slate-800 mt-0.5">
              ₱{{ Number(stats.total_released_today).toLocaleString('en-PH', { minimumFractionDigits: 0 }) }}
            </p>
            <p class="text-xs text-slate-400 mt-1">Total cash disbursed today</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-purple-50 rounded-tl-3xl opacity-60"></div>
        </div>

        <div class="card p-5 flex items-start gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
            <ClockIcon class="w-5 h-5 text-amber-600" />
          </div>
          <div>
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Pending in Event</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.pending_in_event }}</p>
            <p class="text-xs text-slate-400 mt-1">Unclaimed this event</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-amber-50 rounded-tl-3xl opacity-60"></div>
        </div>
      </div>

      <!-- Quick Navigation -->
      <div class="grid grid-cols-2 gap-4">
        <Link :href="route('officer.scanner')" class="card p-6 flex flex-col items-center gap-3 text-center hover:shadow-md transition-all group cursor-pointer border-2 hover:border-brand-300">
          <div class="w-14 h-14 rounded-2xl bg-brand-50 group-hover:bg-brand-100 flex items-center justify-center transition-colors">
            <QrCodeIcon class="w-7 h-7 text-brand-600" />
          </div>
          <div>
            <p class="font-bold text-slate-800">QR Scanner</p>
            <p class="text-xs text-slate-400 mt-0.5">Scan beneficiary card</p>
          </div>
        </Link>
        <Link :href="route('officer.distribution')" class="card p-6 flex flex-col items-center gap-3 text-center hover:shadow-md transition-all group cursor-pointer border-2 hover:border-success-300">
          <div class="w-14 h-14 rounded-2xl bg-success-50 group-hover:bg-success-100 flex items-center justify-center transition-colors">
            <ClipboardDocumentListIcon class="w-7 h-7 text-success-600" />
          </div>
          <div>
            <p class="font-bold text-slate-800">My Distributions</p>
            <p class="text-xs text-slate-400 mt-0.5">View your release log</p>
          </div>
        </Link>
      </div>

      <!-- Recent Releases Table -->
      <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <CheckCircleIcon class="w-5 h-5 text-success-600" />
            <h2 class="font-semibold text-slate-800 text-sm">Recent Releases</h2>
          </div>
          <Link :href="route('officer.distribution')" class="text-xs text-brand-600 hover:underline">
            View All →
          </Link>
        </div>

        <div v-if="!stats.recent_releases?.length" class="px-5 py-12 text-center text-slate-400">
          <BanknotesIcon class="w-10 h-10 opacity-20 mx-auto mb-2" />
          <p class="text-sm">No releases recorded yet today.</p>
          <p class="text-xs mt-1">Use the QR Scanner to start processing claims.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Beneficiary</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Claimed By</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Time</th>
                <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Amount</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="dist in stats.recent_releases" :key="dist.id" class="hover:bg-slate-50/50">
                <td class="px-5 py-3">
                  <p class="font-medium text-slate-700 text-xs">{{ dist.beneficiary?.full_name ?? '—' }}</p>
                  <p class="text-[10px] text-slate-400 font-mono">{{ dist.beneficiary?.unique_id }}</p>
                </td>
                <td class="px-5 py-3">
                  <span :class="['badge badge-sm', dist.claimed_by_type === 'proxy' ? 'badge-warning' : 'badge-success']">
                    {{ dist.claimed_by_type === 'proxy' ? 'Proxy' : 'Self' }}
                  </span>
                </td>
                <td class="px-5 py-3 text-xs text-slate-400">{{ timeAgo(dist.claimed_at) }}</td>
                <td class="px-5 py-3 text-right font-bold text-success-600">
                  ₱{{ Number(dist.amount_released).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </StaffLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import {
  BoltIcon, QrCodeIcon, CheckCircleIcon, BanknotesIcon, ClockIcon,
  ExclamationTriangleIcon, ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({ stats: Object })

const timeAgo = (d) => {
  if (!d) return '—'
  const sec = Math.floor((Date.now() - new Date(d)) / 1000)
  if (sec < 60)    return `${sec}s ago`
  if (sec < 3600)  return `${Math.floor(sec / 60)}m ago`
  if (sec < 86400) return `${Math.floor(sec / 3600)}h ago`
  return new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
}
</script>
