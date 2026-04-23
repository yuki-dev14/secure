<template>
  <Head title="Completion Report" />
  <StaffLayout page-title="Completion Report" page-subtitle="Education, health, and FDS completion records per quarter">
    <div class="space-y-4">

      <div class="flex items-center justify-between">
        <Link :href="route('superadmin.reports.index')" class="btn btn-ghost btn-sm gap-1.5 text-slate-500">
          <ArrowLeftIcon class="w-4 h-4" /> Reports Hub
        </Link>
        <a :href="exportUrl" class="btn btn-primary btn-sm gap-1.5" download>
          <ArrowDownTrayIcon class="w-4 h-4" />
          Export CSV
        </a>
      </div>

      <!-- Filters -->
      <div class="card p-4 flex flex-wrap gap-3">
        <select v-model="filters.period" class="form-select w-52" @change="applyFilters">
          <option value="">All Periods</option>
          <option v-for="p in periods" :key="p" :value="p">{{ p }}</option>
        </select>
        <select v-model="filters.compliant" class="form-select w-48" @change="applyFilters">
          <option value="">All Completion</option>
          <option value="1">Fully Complete</option>
          <option value="0">Incomplete</option>
        </select>
      </div>

      <!-- Totals -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="card px-4 py-3 text-center">
          <p class="text-2xl font-bold text-slate-800">{{ records.total }}</p>
          <p class="text-xs text-slate-400 mt-0.5">Total Records</p>
        </div>
        <div class="card px-4 py-3 text-center">
          <p class="text-2xl font-bold text-success-700">{{ compliantCount }}</p>
          <p class="text-xs text-slate-400 mt-0.5">Fully Complete</p>
        </div>
        <div class="card px-4 py-3 text-center">
          <p class="text-2xl font-bold text-danger-700">{{ nonCompliantCount }}</p>
          <p class="text-xs text-slate-400 mt-0.5">Incomplete</p>
        </div>
        <div class="card px-4 py-3 text-center">
          <p class="text-2xl font-bold text-brand-700">{{ complianceRate }}%</p>
          <p class="text-xs text-slate-400 mt-0.5">Completion Rate</p>
        </div>
      </div>

      <!-- Table -->
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Beneficiary</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Period</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Edu Attendance</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Health</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">FDS</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Overall</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Override</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Verified By</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-if="!records.data.length">
                <td colspan="9" class="text-center py-12 text-slate-400">No compliance records found.</td>
              </tr>
              <tr v-for="r in records.data" :key="r.id" class="hover:bg-slate-50/60 transition-colors">
                <td class="px-5 py-3 font-mono text-xs text-brand-700">{{ r.beneficiary?.unique_id ?? r.beneficiary_id }}</td>
                <td class="px-5 py-3 font-medium text-slate-700">{{ r.period }}</td>
                <td class="px-5 py-3">
                  <div class="flex items-center gap-1.5">
                    <span :class="r.edu_attendance_compliant ? 'text-success-600' : 'text-danger-600'" class="font-bold">
                      {{ r.edu_attendance_compliant ? '✓' : '✗' }}
                    </span>
                    <span class="text-xs text-slate-500">{{ r.edu_attendance_rate ? r.edu_attendance_rate + '%' : '—' }}</span>
                  </div>
                </td>
                <td class="px-5 py-3">
                  <span :class="r.health_compliant ? 'text-success-600' : 'text-danger-600'" class="font-bold">
                    {{ r.health_compliant ? '✓' : '✗' }}
                  </span>
                </td>
                <td class="px-5 py-3">
                  <span :class="r.fds_compliant ? 'text-success-600' : 'text-danger-600'" class="font-bold">
                    {{ r.fds_compliant ? '✓' : '✗' }}
                  </span>
                </td>
                <td class="px-5 py-3">
                  <span :class="['badge badge-sm', r.is_fully_compliant ? 'badge-success' : 'badge-danger']">
                    {{ r.is_fully_compliant ? 'Complete' : 'Incomplete' }}
                  </span>
                </td>
                <td class="px-5 py-3">
                  <span v-if="r.compliance_override" class="badge badge-sm badge-warning">Overridden</span>
                  <span v-else class="text-slate-300 text-xs">—</span>
                </td>
                <td class="px-5 py-3 text-slate-500 text-xs">{{ r.verifier?.name ?? '—' }}</td>
                <td class="px-5 py-3 text-xs text-slate-400">{{ formatDate(r.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="records.last_page > 1" class="p-4 border-t border-slate-100 flex items-center justify-between">
          <p class="text-sm text-slate-500">{{ records.from }}–{{ records.to }} of {{ records.total }}</p>
          <div class="flex gap-1 flex-wrap">
            <Link v-for="link in records.links" :key="link.label" :href="link.url ?? '#'"
              :class="['btn btn-sm', link.active ? 'btn-primary' : 'btn-secondary', !link.url ? 'opacity-40 pointer-events-none' : '']"
              v-html="link.label" />
          </div>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeftIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({ records: Object, periods: Array })

const filters = reactive({
  period:    new URLSearchParams(window.location.search).get('period') ?? '',
  compliant: new URLSearchParams(window.location.search).get('compliant') ?? '',
})

const applyFilters = () => {
  router.get(route('superadmin.reports.compliance'), {
    period:    filters.period    || undefined,
    compliant: filters.compliant !== '' ? filters.compliant : undefined,
  }, { preserveState: true, preserveScroll: true, replace: true })
}

const exportUrl = computed(() =>
  route('superadmin.reports.compliance.export') + '?' +
  new URLSearchParams({
    ...(filters.period    && { period:    filters.period }),
    ...(filters.compliant !== '' && { compliant: filters.compliant }),
  }).toString()
)

const compliantCount    = computed(() => props.records.data.filter(r => r.is_fully_compliant).length)
const nonCompliantCount = computed(() => props.records.data.filter(r => !r.is_fully_compliant).length)
const complianceRate    = computed(() =>
  props.records.data.length
    ? Math.round((compliantCount.value / props.records.data.length) * 100)
    : 0
)

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-PH') : '—'
</script>
