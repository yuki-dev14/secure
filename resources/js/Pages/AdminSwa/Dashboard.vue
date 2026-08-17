<template>
  <Head title="SWA Dashboard" />
  <StaffLayout page-title="SWA Dashboard" page-subtitle="Health & Education Compliance Monitoring">
    <div class="space-y-6">

      <!-- Greeting -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 class="text-xl font-bold text-slate-800">Good {{ timeOfDay }}, {{ firstName }}! 👋</h1>
          <p class="text-sm text-slate-400 mt-0.5">{{ todayLong }} · Period: <span class="font-semibold text-amber-600">{{ currentPeriod.label }}</span></p>
        </div>
        <div class="flex items-center gap-2">
          <Link :href="route('adminswa.non-compliance.import')" class="btn btn-secondary gap-2">
            <ArrowUpTrayIcon class="w-4 h-4" />
            Import Records
          </Link>
          <Link :href="route('adminswa.non-compliance.create')" class="btn btn-primary gap-2">
            <PlusIcon class="w-4 h-4" />
            Flag Non-Compliance
          </Link>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="card p-5 flex items-start gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
            <UsersIcon class="w-5 h-5 text-brand-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Beneficiaries</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.total_beneficiaries?.toLocaleString() }}</p>
            <p class="text-xs text-slate-400 mt-1"><span class="text-emerald-600 font-semibold">{{ stats.compliant }}</span> compliant</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-brand-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <div class="card p-5 flex items-start gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
            <ExclamationTriangleIcon class="w-5 h-5 text-amber-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Pending Review</p>
            <p class="text-2xl font-bold text-amber-600 mt-0.5">{{ stats.nc_pending }}</p>
            <p class="text-xs text-slate-400 mt-1">this period</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-amber-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <div class="card p-5 flex items-start gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
            <XCircleIcon class="w-5 h-5 text-red-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Confirmed NC</p>
            <p class="text-2xl font-bold text-red-600 mt-0.5">{{ stats.nc_confirmed }}</p>
            <p class="text-xs text-slate-400 mt-1">grants zeroed</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-red-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <div class="card p-5 flex items-start gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center shrink-0">
            <AcademicCapIcon class="w-5 h-5 text-violet-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Education NC</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.nc_education }}</p>
            <p class="text-xs text-slate-400 mt-1">attendance &lt;85%</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-violet-50 rounded-tl-3xl opacity-50"></div>
        </div>

        <div class="card p-5 flex items-start gap-3 relative overflow-hidden">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
            <HeartIcon class="w-5 h-5 text-emerald-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Health NC</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.nc_health }}</p>
            <p class="text-xs text-slate-400 mt-1">immunization / checkups</p>
          </div>
          <div class="absolute bottom-0 right-0 w-14 h-14 bg-emerald-50 rounded-tl-3xl opacity-50"></div>
        </div>
      </div>

      <!-- Two-column layout -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Non-compliance by barangay -->
        <div class="card">
          <div class="p-5 border-b border-slate-100">
            <h2 class="font-semibold text-slate-700">Non-Compliance by Barangay</h2>
            <p class="text-xs text-slate-400 mt-0.5">{{ currentPeriod.label }}</p>
          </div>
          <div class="p-5">
            <div v-if="ncByBarangay.length === 0" class="text-center py-8 text-slate-400 text-sm">
              No non-compliance records for this period.
            </div>
            <div v-else class="space-y-3">
              <div v-for="item in ncByBarangay" :key="item.barangay" class="flex items-center gap-3">
                <span class="text-sm text-slate-600 w-36 truncate">{{ item.barangay }}</span>
                <div class="flex-1 bg-slate-100 rounded-full h-5 overflow-hidden">
                  <div class="bg-amber-500 h-full rounded-full transition-all duration-500"
                       :style="{ width: `${Math.min(100, (item.total / maxBarangayNC) * 100)}%` }">
                  </div>
                </div>
                <span class="text-sm font-semibold text-slate-700 w-8 text-right">{{ item.total }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent records -->
        <div class="card">
          <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
              <h2 class="font-semibold text-slate-700">Recent Non-Compliance Records</h2>
              <p class="text-xs text-slate-400 mt-0.5">Latest flagged entries</p>
            </div>
            <Link :href="route('adminswa.non-compliance.index')" class="text-sm text-brand-600 hover:text-brand-700 font-medium">
              View All →
            </Link>
          </div>
          <div class="divide-y divide-slate-50">
            <div v-if="recentRecords.length === 0" class="text-center py-8 text-slate-400 text-sm">
              No records yet.
            </div>
            <div v-for="record in recentRecords" :key="record.id"
                 class="px-5 py-3.5 flex items-center gap-3 hover:bg-slate-25 transition-colors">
              <div class="shrink-0">
                <span :class="[statusBadge(record.status), 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium']">
                  {{ record.status }}
                </span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-700 truncate">{{ record.beneficiary?.full_name }}</p>
                <p class="text-xs text-slate-400 truncate">{{ record.reason }} · {{ record.category }}</p>
              </div>
              <span :class="[record.category === 'education' ? 'text-violet-600 bg-violet-50' : 'text-emerald-600 bg-emerald-50',
                    'text-xs px-2 py-0.5 rounded-full font-medium capitalize']">
                {{ record.category }}
              </span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </StaffLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import {
  UsersIcon, ExclamationTriangleIcon, XCircleIcon,
  AcademicCapIcon, HeartIcon, PlusIcon, ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  stats:          Object,
  ncByBarangay:   Array,
  recentRecords:  Array,
  currentPeriod:  Object,
  periods:        Array,
})

const page = usePage()
const firstName = computed(() => (page.props.auth?.user?.name ?? 'Admin').split(' ')[0])

const now = new Date()
const timeOfDay = computed(() => {
  const h = now.getHours()
  return h < 12 ? 'morning' : h < 17 ? 'afternoon' : 'evening'
})
const todayLong = computed(() => now.toLocaleDateString('en-PH', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
}))

const maxBarangayNC = computed(() => {
  if (!props.ncByBarangay?.length) return 1
  return Math.max(...props.ncByBarangay.map(b => b.total), 1)
})

const statusBadge = (status) => ({
  pending:   'bg-amber-100 text-amber-700',
  confirmed: 'bg-red-100 text-red-700',
  dismissed: 'bg-slate-100 text-slate-500',
}[status] ?? 'bg-slate-100 text-slate-500')
</script>
