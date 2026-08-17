<template>
  <Head title="Reports & Exports" />
  <StaffLayout page-title="Reports & Exports" page-subtitle="Beneficiary data overview and expected grant projections">
    <div class="space-y-6">

      <!-- ─── Summary KPI strip ────────────────────────────────────────────── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-5 flex items-center gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
            <UsersIcon class="w-5 h-5 text-brand-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-medium">Total Beneficiaries</p>
            <p class="text-2xl font-bold text-slate-800">{{ summary.beneficiaries.total }}</p>
            <p class="text-[10px] text-success-600">{{ summary.beneficiaries.active }} active</p>
          </div>
          <div class="absolute -bottom-2 -right-2 w-12 h-12 bg-brand-50 rounded-full opacity-40"></div>
        </div>

        <div class="card p-5 flex items-center gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
            <CurrencyDollarIcon class="w-5 h-5 text-emerald-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-medium">Expected Next 2 Months</p>
            <p class="text-2xl font-bold text-slate-800">₱{{ formatAmount(summary.expected_grant) }}</p>
            <p class="text-[10px] text-emerald-600">{{ summary.next_period_label }}</p>
          </div>
          <div class="absolute -bottom-2 -right-2 w-12 h-12 bg-emerald-50 rounded-full opacity-40"></div>
        </div>

        <div class="card p-5 flex items-center gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
            <AcademicCapIcon class="w-5 h-5 text-blue-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-medium">School-Age Children</p>
            <p class="text-2xl font-bold text-slate-800">{{ summary.school_age_children }}</p>
            <p class="text-[10px] text-blue-600">eligible for education grant</p>
          </div>
          <div class="absolute -bottom-2 -right-2 w-12 h-12 bg-blue-50 rounded-full opacity-40"></div>
        </div>

        <div class="card p-5 flex items-center gap-4 relative overflow-hidden">
          <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
            <MapPinIcon class="w-5 h-5 text-purple-600" />
          </div>
          <div>
            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-medium">Barangay Coverage</p>
            <p class="text-2xl font-bold text-slate-800">{{ summary.barangay_count }}</p>
            <p class="text-[10px] text-purple-600">barangays with beneficiaries</p>
          </div>
          <div class="absolute -bottom-2 -right-2 w-12 h-12 bg-purple-50 rounded-full opacity-40"></div>
        </div>
      </div>

      <!-- ─── Expected Grant Breakdown ──────────────────────────────────────── -->
      <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <CurrencyDollarIcon class="w-5 h-5 text-emerald-600" />
            <div>
              <h2 class="font-semibold text-slate-800 text-sm">Expected Grant Breakdown — {{ summary.next_period_label }}</h2>
              <p class="text-[10px] text-slate-400">Projected grants for all active beneficiaries (before deductions)</p>
            </div>
          </div>
        </div>
        <div class="p-5">
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-rose-50 rounded-2xl p-4 text-center">
              <p class="text-xs text-rose-600 font-medium mb-1">Health Grant</p>
              <p class="text-xl font-bold text-rose-700">₱{{ formatAmount(summary.breakdown.health) }}</p>
              <p class="text-[10px] text-rose-500 mt-1">₱750 × 2 months × {{ summary.beneficiaries.active }}</p>
            </div>
            <div class="bg-blue-50 rounded-2xl p-4 text-center">
              <p class="text-xs text-blue-600 font-medium mb-1">Education Grant</p>
              <p class="text-xl font-bold text-blue-700">₱{{ formatAmount(summary.breakdown.education) }}</p>
              <p class="text-[10px] text-blue-500 mt-1">{{ summary.school_age_children }} children enrolled</p>
            </div>
            <div class="bg-amber-50 rounded-2xl p-4 text-center">
              <p class="text-xs text-amber-600 font-medium mb-1">Rice Subsidy</p>
              <p class="text-xl font-bold text-amber-700">₱{{ formatAmount(summary.breakdown.rice) }}</p>
              <p class="text-[10px] text-amber-500 mt-1">₱600 × 2 months × {{ summary.beneficiaries.active }}</p>
            </div>
            <div class="bg-emerald-50 rounded-2xl p-4 text-center border-2 border-emerald-200">
              <p class="text-xs text-emerald-600 font-medium mb-1">Total Expected</p>
              <p class="text-xl font-bold text-emerald-700">₱{{ formatAmount(summary.expected_grant) }}</p>
              <p class="text-[10px] text-emerald-500 mt-1">all components combined</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── Report cards grid ─────────────────────────────────────────────── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Beneficiary Report -->
        <div class="card p-5 flex flex-col gap-4 hover:shadow-md transition-shadow">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
              <UsersIcon class="w-5 h-5 text-brand-600" />
            </div>
            <div>
              <h3 class="font-bold text-slate-800 text-sm">Beneficiary Report</h3>
              <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">All registered households — status, barangay, and family members.</p>
            </div>
          </div>
          <div class="flex gap-2 mt-auto">
            <Link :href="route('superadmin.reports.beneficiaries')" class="btn btn-secondary btn-sm flex-1 justify-center text-xs">
              View Report
            </Link>
            <a :href="route('superadmin.reports.beneficiaries.export')" class="btn btn-primary btn-sm flex-1 justify-center gap-1 text-xs" download>
              <ArrowDownTrayIcon class="w-3.5 h-3.5" />
              Export CSV
            </a>
          </div>
        </div>

        <!-- Grant Computation Report -->
        <div class="card p-5 flex flex-col gap-4 hover:shadow-md transition-shadow">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
              <CurrencyDollarIcon class="w-5 h-5 text-emerald-600" />
            </div>
            <div>
              <h3 class="font-bold text-slate-800 text-sm">Grant Computation Report</h3>
              <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">Per-beneficiary computed grants — health, education, rice subsidy.</p>
            </div>
          </div>
          <div class="flex gap-2 mt-auto">
            <Link :href="route('superadmin.reports.grants')" class="btn btn-secondary btn-sm flex-1 justify-center text-xs">
              View Report
            </Link>
            <a :href="route('superadmin.reports.grants.export')" class="btn btn-primary btn-sm flex-1 justify-center gap-1 text-xs" download>
              <ArrowDownTrayIcon class="w-3.5 h-3.5" />
              Export CSV
            </a>
          </div>
        </div>
      </div>

    </div>
  </StaffLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import {
  UsersIcon, CurrencyDollarIcon, AcademicCapIcon,
  MapPinIcon, ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  summary: Object,
})

const formatAmount = (n) =>
  Number(n ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
</script>
