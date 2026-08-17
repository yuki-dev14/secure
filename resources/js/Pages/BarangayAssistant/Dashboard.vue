<template>
  <Head title="Barangay Assistant Dashboard" />
  <StaffLayout :page-title="`FDS Scanner — ${barangay}`" page-subtitle="Family Development Session attendance monitoring">
    <div class="space-y-5">

      <!-- Today's Stats -->
      <div class="grid grid-cols-3 gap-4">
        <div class="card p-5 text-center relative overflow-hidden">
          <div class="w-10 h-10 mx-auto rounded-xl bg-emerald-50 flex items-center justify-center mb-2">
            <ArrowRightStartOnRectangleIcon class="w-5 h-5 text-emerald-600" />
          </div>
          <p class="text-2xl font-bold text-emerald-700">{{ todayStats.checked_in }}</p>
          <p class="text-[10px] text-slate-400 uppercase tracking-wide font-medium mt-1">Checked In Today</p>
          <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-emerald-50 rounded-full opacity-40"></div>
        </div>
        <div class="card p-5 text-center relative overflow-hidden">
          <div class="w-10 h-10 mx-auto rounded-xl bg-orange-50 flex items-center justify-center mb-2">
            <ArrowLeftStartOnRectangleIcon class="w-5 h-5 text-orange-600" />
          </div>
          <p class="text-2xl font-bold text-orange-700">{{ todayStats.checked_out }}</p>
          <p class="text-[10px] text-slate-400 uppercase tracking-wide font-medium mt-1">Checked Out Today</p>
          <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-orange-50 rounded-full opacity-40"></div>
        </div>
        <div class="card p-5 text-center relative overflow-hidden">
          <div class="w-10 h-10 mx-auto rounded-xl bg-blue-50 flex items-center justify-center mb-2">
            <CheckBadgeIcon class="w-5 h-5 text-blue-600" />
          </div>
          <p class="text-2xl font-bold text-blue-700">{{ todayStats.complete }}</p>
          <p class="text-[10px] text-slate-400 uppercase tracking-wide font-medium mt-1">Complete Attendance</p>
          <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-blue-50 rounded-full opacity-40"></div>
        </div>
      </div>

      <!-- Quick Action -->
      <Link :href="route('barangay.scanner')" class="card p-5 flex items-center gap-4 hover:shadow-md transition-shadow group cursor-pointer">
        <div class="w-14 h-14 rounded-2xl bg-brand-50 flex items-center justify-center shrink-0 group-hover:bg-brand-100 transition-colors">
          <QrCodeIcon class="w-7 h-7 text-brand-600" />
        </div>
        <div class="flex-1">
          <h3 class="font-bold text-slate-800">Open FDS Scanner</h3>
          <p class="text-sm text-slate-400 mt-0.5">Scan beneficiary QR codes for check-in and check-out</p>
        </div>
        <ArrowRightIcon class="w-5 h-5 text-slate-300 group-hover:text-brand-500 transition-colors" />
      </Link>

      <!-- Recent Scans -->
      <div class="card overflow-hidden">
        <div class="card-header">
          <h3 class="font-semibold text-slate-800">Recent Scans</h3>
          <span class="text-xs text-slate-400">Your recorded scans</span>
        </div>
        <div v-if="recentScans.length === 0" class="p-8 text-center text-slate-400 text-sm">
          <QrCodeIcon class="w-12 h-12 mx-auto opacity-20 mb-2" />
          <p>No scans recorded yet. Open the scanner to begin.</p>
        </div>
        <div v-else class="divide-y divide-slate-50">
          <div v-for="scan in recentScans" :key="scan.id"
               class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50/60 transition-colors">
            <div :class="[
              'w-9 h-9 rounded-lg flex items-center justify-center shrink-0',
              scan.is_complete ? 'bg-blue-100' : 'bg-amber-100'
            ]">
              <CheckBadgeIcon v-if="scan.is_complete" class="w-4 h-4 text-blue-600" />
              <ArrowRightStartOnRectangleIcon v-else class="w-4 h-4 text-amber-600" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-slate-700 truncate">{{ scan.beneficiary }}</p>
              <p class="text-[10px] text-slate-400">{{ scan.unique_id }} · {{ scan.barangay }}</p>
            </div>
            <div class="text-right shrink-0">
              <span :class="['badge badge-sm', scan.is_complete ? 'badge-success' : 'badge-warning']">
                {{ scan.is_complete ? 'Complete' : 'Check-In Only' }}
              </span>
              <div class="text-[10px] text-slate-400 mt-0.5 space-x-2">
                <span v-if="scan.checked_in_at">In: {{ scan.checked_in_at }}</span>
                <span v-if="scan.checked_out_at">Out: {{ scan.checked_out_at }}</span>
              </div>
              <p class="text-[10px] text-slate-300">{{ scan.session_date }}</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </StaffLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import {
  QrCodeIcon, CheckBadgeIcon, ArrowRightIcon,
  ArrowRightStartOnRectangleIcon, ArrowLeftStartOnRectangleIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

defineProps({
  todayStats:   Object,
  recentScans:  Array,
  barangay:     String,
})
</script>
