<template>
  <Head title="Completion Verifier Dashboard" />
  <StaffLayout page-title="Dashboard" page-subtitle="Quarterly completion monitoring overview for Lipa City 4Ps beneficiaries">
    <div class="space-y-6">

      <!-- ─── Greeting ─────────────────────────────────────────────────────── -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 class="text-xl font-bold text-slate-800">
            Good {{ timeOfDay }}, {{ firstName }}! 👋
          </h1>
          <p class="text-sm text-slate-400 mt-0.5">{{ todayLong }}</p>
        </div>
        <Link :href="route('verifier.beneficiaries')" class="btn btn-primary gap-2 self-start">
          <ClipboardDocumentCheckIcon class="w-4 h-4" />
          Verify Beneficiaries
        </Link>
      </div>

      <!-- ─── KPI Cards ─────────────────────────────────────────────────────── -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="card p-5 flex items-start gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
            <UsersIcon class="w-5 h-5 text-brand-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Total for Review</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.total_for_review.toLocaleString() }}</p>
            <p class="text-xs text-slate-400 mt-1">Active beneficiaries</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-brand-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <div class="card p-5 flex items-start gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-success-50 flex items-center justify-center shrink-0">
            <CheckBadgeIcon class="w-5 h-5 text-success-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Verified Today</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.verified_today }}</p>
            <p class="text-xs text-slate-400 mt-1">Records saved today</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-success-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <div class="card p-5 flex items-start gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
            <AcademicCapIcon class="w-5 h-5 text-amber-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Pending Education</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.pending_edu }}</p>
            <p class="text-xs text-slate-400 mt-1">Attendance unverified</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-amber-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <div class="card p-5 flex items-start gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-pink-50 flex items-center justify-center shrink-0">
            <HeartIcon class="w-5 h-5 text-pink-500" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Pending Health</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.pending_health }}</p>
            <p class="text-xs text-slate-400 mt-1">Health check unverified</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-pink-50 rounded-tl-3xl opacity-50"></div>
        </div>
      </div>

      <!-- ─── Completion Overview Bar ────────────────────────────────────────── -->
      <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
          <div>
            <p class="font-semibold text-slate-800 text-sm">Household Completion Rate</p>
            <p class="text-xs text-slate-400 mt-0.5">Complete vs incomplete active beneficiaries this quarter</p>
          </div>
          <span class="text-2xl font-bold text-brand-600">{{ complianceRate }}%</span>
        </div>

        <!-- Segmented bar -->
        <div class="w-full h-4 bg-slate-100 rounded-full overflow-hidden flex">
          <div
            class="h-full transition-all duration-1000 rounded-l-full"
            :style="`width: ${complianceRate}%; background: linear-gradient(90deg, #10b981, #6366f1)`"
          ></div>
        </div>

        <div class="grid grid-cols-3 gap-4 mt-4">
          <div class="text-center p-3 bg-success-50 rounded-xl border border-green-200">
            <p class="text-lg font-bold text-success-700">{{ stats.compliant }}</p>
            <p class="text-xs text-success-600 font-medium mt-0.5">&#x2713; Complete</p>
          </div>
          <div class="text-center p-3 bg-danger-50 rounded-xl border border-red-200">
            <p class="text-lg font-bold text-danger-600">{{ stats.non_compliant }}</p>
            <p class="text-xs text-danger-500 font-medium mt-0.5">&#x2717; Incomplete</p>
          </div>
          <div class="text-center p-3 bg-slate-50 rounded-xl border border-slate-200">
            <p class="text-lg font-bold text-slate-700">{{ stats.total_for_review }}</p>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Total Active</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ─── Pending Actions ───────────────────────────────────────────────── -->
        <div class="card overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
            <ExclamationTriangleIcon class="w-5 h-5 text-amber-500" />
            <h2 class="font-semibold text-slate-800 text-sm">Pending Actions</h2>
          </div>
          <div class="p-4 space-y-3">

            <Link :href="route('verifier.beneficiaries')" class="flex items-center gap-3 p-3 rounded-xl border border-amber-200 bg-amber-50 hover:bg-amber-100 transition-colors group">
              <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                <AcademicCapIcon class="w-4 h-4 text-amber-600" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-amber-800">Education Completion</p>
                <p class="text-xs text-amber-600">{{ stats.pending_edu }} households pending</p>
              </div>
              <span class="text-lg font-bold text-amber-700">{{ stats.pending_edu }}</span>
            </Link>

            <Link :href="route('verifier.beneficiaries')" class="flex items-center gap-3 p-3 rounded-xl border border-pink-200 bg-pink-50 hover:bg-pink-100 transition-colors group">
              <div class="w-9 h-9 rounded-lg bg-pink-100 flex items-center justify-center shrink-0">
                <HeartIcon class="w-4 h-4 text-pink-500" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-pink-800">Health Completion</p>
                <p class="text-xs text-pink-600">{{ stats.pending_health }} households pending</p>
              </div>
              <span class="text-lg font-bold text-pink-700">{{ stats.pending_health }}</span>
            </Link>

            <Link :href="route('verifier.beneficiaries')" class="flex items-center gap-3 p-3 rounded-xl border border-danger-200 bg-danger-50 hover:bg-red-100 transition-colors group">
              <div class="w-9 h-9 rounded-lg bg-danger-100 flex items-center justify-center shrink-0">
                <XCircleIcon class="w-4 h-4 text-danger-600" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-danger-800">Incomplete Households</p>
                <p class="text-xs text-danger-600">Require re-verification this quarter</p>
              </div>
              <span class="text-lg font-bold text-danger-600">{{ stats.non_compliant }}</span>
            </Link>
          </div>
        </div>

        <!-- ─── Recent Completion Records ─────────────────────────────────────── -->
        <div class="card overflow-hidden lg:col-span-2">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <ClipboardDocumentCheckIcon class="w-5 h-5 text-brand-600" />
              <h2 class="font-semibold text-slate-800 text-sm">Recent Completion Records</h2>
            </div>
            <Link :href="route('verifier.beneficiaries')" class="text-xs text-brand-600 hover:underline">
              View All →
            </Link>
          </div>

          <div v-if="!stats.recent_records?.length" class="px-5 py-14 text-center text-slate-400">
            <ClipboardDocumentCheckIcon class="w-10 h-10 opacity-20 mx-auto mb-2" />
            <p class="text-sm font-medium">No completion records yet.</p>
            <p class="text-xs mt-1 mb-4">Start verifying beneficiary completion to populate this list.</p>
            <Link :href="route('verifier.beneficiaries')" class="btn btn-primary btn-sm gap-1.5">
              <ClipboardDocumentCheckIcon class="w-4 h-4" />
              Start Verifying
            </Link>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Beneficiary</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Quarter</th>
                  <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Edu</th>
                  <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Health</th>
                  <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">FDS</th>
                  <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Overall</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">By</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <tr v-for="rec in stats.recent_records" :key="rec.id"
                  class="hover:bg-slate-50/60 transition-colors cursor-pointer"
                  @click="$inertia.visit(route('verifier.beneficiaries.show', rec.beneficiary_id))"
                >
                  <td class="px-5 py-3">
                    <p class="font-medium text-slate-700 text-xs">{{ rec.beneficiary?.full_name ?? '—' }}</p>
                    <p class="text-[10px] text-slate-400 font-mono">{{ rec.beneficiary?.unique_id }}</p>
                  </td>
                  <td class="px-5 py-3">
                    <span class="badge badge-sm badge-neutral">{{ rec.period }}</span>
                  </td>
                  <td class="px-5 py-3 text-center">
                    <StatusDot :value="rec.edu_attendance_compliant" />
                  </td>
                  <td class="px-5 py-3 text-center">
                    <StatusDot :value="rec.health_compliant" />
                  </td>
                  <td class="px-5 py-3 text-center">
                    <StatusDot :value="rec.fds_compliant" />
                  </td>
                  <td class="px-5 py-3 text-center">
                    <span :class="['badge badge-sm', rec.is_fully_compliant ? 'badge-success' : 'badge-danger']">
                      {{ rec.is_fully_compliant ? '&#x2713; Complete' : '&#x2717; Incomplete' }}
                    </span>
                  </td>
                  <td class="px-5 py-3 text-xs text-slate-400">
                    {{ rec.verifier?.name?.split(' ')[0] ?? '—' }}
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
import { Head, Link, router } from '@inertiajs/vue3'
import {
  UsersIcon, CheckBadgeIcon, AcademicCapIcon, HeartIcon,
  ClipboardDocumentCheckIcon, ExclamationTriangleIcon, XCircleIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({ stats: Object })

// ─── Greeting ────────────────────────────────────────────────────────────────
const timeOfDay = computed(() => {
  const h = new Date().getHours()
  return h < 12 ? 'morning' : h < 17 ? 'afternoon' : 'evening'
})
const todayLong = computed(() =>
  new Date().toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
)
const firstName = computed(() => 'Verifier')

// ─── Compliance Rate ──────────────────────────────────────────────────────────
const complianceRate = computed(() => {
  if (!props.stats.total_for_review) return 0
  return Math.round((props.stats.compliant / props.stats.total_for_review) * 100)
})

// ─── Inline StatusDot component ───────────────────────────────────────────────
const StatusDot = {
  props: ['value'],
  template: `
    <span :class="[
      'inline-block w-2.5 h-2.5 rounded-full',
      value === true  ? 'bg-success-500' :
      value === false ? 'bg-danger-500' : 'bg-slate-300'
    ]" :title="value === true ? 'Complete' : value === false ? 'Incomplete' : 'Not recorded'" />
  `,
}
</script>
