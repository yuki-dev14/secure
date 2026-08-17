<template>
  <Head title="Admin4Ps Dashboard" />
  <StaffLayout page-title="Admin 4Ps Dashboard" page-subtitle="FDS attendance monitoring and reporting">
    <div class="space-y-5">

      <!-- Period selector -->
      <div class="flex items-center gap-3">
        <CalendarDaysIcon class="w-5 h-5 text-brand-500 shrink-0" />
        <span class="text-sm font-semibold text-slate-700">Period:</span>
        <span class="badge badge-info">{{ currentPeriod?.label }}</span>
      </div>

      <!-- KPI Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-4 flex items-center gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
            <UsersIcon class="w-5 h-5 text-brand-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-medium">Active Beneficiaries</p>
            <p class="text-2xl font-bold text-slate-800">{{ stats.total_beneficiaries }}</p>
          </div>
        </div>
        <div class="card p-4 flex items-center gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
            <CheckBadgeIcon class="w-5 h-5 text-emerald-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-medium">Complete Attendance</p>
            <p class="text-2xl font-bold text-emerald-700">{{ stats.fds_complete }}</p>
            <p class="text-[10px] text-slate-400">{{ stats.fds_unique_attendees }} unique</p>
          </div>
        </div>
        <div class="card p-4 flex items-center gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
            <ExclamationTriangleIcon class="w-5 h-5 text-amber-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-medium">Incomplete</p>
            <p class="text-2xl font-bold text-amber-700">{{ stats.fds_incomplete }}</p>
            <p class="text-[10px] text-slate-400">check-in only</p>
          </div>
        </div>
        <div class="card p-4 flex items-center gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
            <ChartBarIcon class="w-5 h-5 text-blue-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-medium">Attendance Rate</p>
            <p class="text-2xl font-bold text-blue-700">{{ stats.attendance_rate }}%</p>
            <p class="text-[10px] text-slate-400">{{ stats.fds_unique_attendees }} / {{ stats.total_beneficiaries }}</p>
          </div>
        </div>
      </div>

      <!-- Reporting status -->
      <div :class="[
        'card p-4 flex items-center gap-4',
        stats.fds_unreported > 0 ? 'border-amber-200 bg-amber-50/60' : 'border-emerald-200 bg-emerald-50/60'
      ]">
        <div :class="[
          'w-10 h-10 rounded-xl flex items-center justify-center shrink-0',
          stats.fds_unreported > 0 ? 'bg-amber-100' : 'bg-emerald-100'
        ]">
          <PaperAirplaneIcon :class="['w-5 h-5', stats.fds_unreported > 0 ? 'text-amber-600' : 'text-emerald-600']" />
        </div>
        <div class="flex-1">
          <p class="text-sm font-bold" :class="stats.fds_unreported > 0 ? 'text-amber-800' : 'text-emerald-800'">
            {{ stats.fds_unreported > 0
              ? `${stats.fds_unreported} complete records not yet reported to Superadmin`
              : 'All records reported to Superadmin ✓'
            }}
          </p>
          <p class="text-xs text-slate-500 mt-0.5">{{ stats.fds_reported }} already reported this period</p>
        </div>
        <Link :href="route('admin4ps.fds.index')" class="btn btn-sm" :class="stats.fds_unreported > 0 ? 'btn-primary' : 'btn-secondary'">
          {{ stats.fds_unreported > 0 ? 'Review & Report' : 'View Records' }}
        </Link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Attendance by Barangay -->
        <div class="lg:col-span-2 card overflow-hidden">
          <div class="card-header">
            <h3 class="font-semibold text-slate-800 text-sm">Attendance by Barangay</h3>
            <span class="text-xs text-slate-400">{{ currentPeriod?.label }}</span>
          </div>
          <div v-if="fdsByBarangay.length === 0" class="p-8 text-center text-slate-400 text-sm">
            No attendance data yet for this period.
          </div>
          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                  <th class="px-4 py-2.5 text-left">Barangay</th>
                  <th class="px-4 py-2.5 text-right">Complete</th>
                  <th class="px-4 py-2.5 text-right">Incomplete</th>
                  <th class="px-4 py-2.5 text-right">Total</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <tr v-for="row in fdsByBarangay" :key="row.barangay" class="hover:bg-slate-50/60 transition-colors">
                  <td class="px-4 py-2.5 font-medium text-slate-700">{{ row.barangay }}</td>
                  <td class="px-4 py-2.5 text-right">
                    <span class="text-emerald-600 font-semibold">{{ row.complete }}</span>
                  </td>
                  <td class="px-4 py-2.5 text-right">
                    <span v-if="row.incomplete > 0" class="text-amber-600 font-semibold">{{ row.incomplete }}</span>
                    <span v-else class="text-slate-300">0</span>
                  </td>
                  <td class="px-4 py-2.5 text-right font-semibold text-slate-700">{{ row.total }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Quick Actions + Recent -->
        <div class="space-y-5">
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800 text-sm">Quick Actions</h3>
            </div>
            <div class="card-body space-y-3">
              <Link :href="route('admin4ps.fds.scanner')" class="btn btn-primary w-full justify-start gap-3">
                <QrCodeIcon class="w-5 h-5" />
                Open FDS Scanner
              </Link>
              <Link :href="route('admin4ps.fds.index')" class="btn btn-secondary w-full justify-start gap-3">
                <ClipboardDocumentCheckIcon class="w-5 h-5" />
                View Attendance Records
              </Link>
            </div>
          </div>

          <!-- Barangay Assistants -->
          <div class="card p-4">
            <div class="flex items-center gap-2 mb-2">
              <UsersIcon class="w-4 h-4 text-slate-400" />
              <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Barangay Assistants</span>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ stats.barangay_assistants }}</p>
            <p class="text-xs text-slate-400 mt-0.5">active scanner operators</p>
          </div>
        </div>
      </div>

    </div>
  </StaffLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import {
  UsersIcon, CalendarDaysIcon, QrCodeIcon,
  CheckBadgeIcon, ExclamationTriangleIcon, ChartBarIcon,
  PaperAirplaneIcon, ClipboardDocumentCheckIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

defineProps({
  stats:             Object,
  fdsByBarangay:     Array,
  recentAttendance:  Array,
  currentPeriod:     Object,
  periods:           Array,
})
</script>
