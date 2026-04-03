<template>
  <Head title="Beneficiary Report" />
  <StaffLayout page-title="Beneficiary Report" page-subtitle="Full household registry — filterable and exportable">
    <div class="space-y-4">

      <!-- Back + export -->
      <div class="flex items-center justify-between">
        <Link :href="route('superadmin.reports.index')" class="btn btn-ghost btn-sm gap-1.5 text-slate-500">
          <ArrowLeftIcon class="w-4 h-4" /> Reports Hub
        </Link>
        <a :href="exportUrl" class="btn btn-primary btn-sm gap-1.5" download>
          <ArrowDownTrayIcon class="w-4 h-4" />
          Export CSV ({{ filters.barangay || filters.status ? 'filtered' : 'all' }})
        </a>
      </div>

      <!-- Filters -->
      <div class="card p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <select v-model="filters.barangay" class="form-select" @change="applyFilters">
          <option value="">All Barangays</option>
          <option v-for="b in barangays" :key="b" :value="b">{{ b }}</option>
        </select>
        <select v-model="filters.status" class="form-select" @change="applyFilters">
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="suspended">Suspended</option>
          <option value="graduated">Graduated</option>
          <option value="delisted">Delisted</option>
        </select>
        <select v-model="filters.compliant" class="form-select" @change="applyFilters">
          <option value="">All Compliance</option>
          <option value="1">Compliant Only</option>
          <option value="0">Non-Compliant Only</option>
        </select>
      </div>

      <!-- Stats row -->
      <div class="grid grid-cols-3 gap-3">
        <StatMini label="Total Records" :value="beneficiaries.total" color="brand" />
        <StatMini label="Active" :value="beneficiaries.data.filter(b => b.status === 'active').length + ' of ' + beneficiaries.data.length" color="success" />
        <StatMini label="Compliant" :value="beneficiaries.data.filter(b => b.is_compliant).length + ' of ' + beneficiaries.data.length" color="green" />
      </div>

      <!-- Table -->
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Unique ID</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Name</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Barangay</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Members</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Compliance</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Card</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Registered</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-if="!beneficiaries.data.length">
                <td colspan="8" class="text-center py-12 text-slate-400">No records match filters.</td>
              </tr>
              <tr v-for="b in beneficiaries.data" :key="b.id" class="hover:bg-slate-50/60 transition-colors">
                <td class="px-5 py-3 font-mono text-xs text-brand-700">{{ b.unique_id }}</td>
                <td class="px-5 py-3 font-medium text-slate-700 text-sm">{{ b.full_name ?? '—' }}</td>
                <td class="px-5 py-3 text-sm text-slate-500">{{ b.barangay }}</td>
                <td class="px-5 py-3">
                  <span :class="['badge badge-sm', statusClass(b.status)]">{{ b.status }}</span>
                </td>
                <td class="px-5 py-3 text-sm text-slate-500">{{ b.family_members_count }}</td>
                <td class="px-5 py-3">
                  <span :class="['badge badge-sm', b.is_compliant ? 'badge-success' : 'badge-danger']">
                    {{ b.is_compliant ? '✓ Compliant' : '✗ Non-Compliant' }}
                  </span>
                </td>
                <td class="px-5 py-3">
                  <span v-if="b.card" class="badge badge-sm badge-info">Issued</span>
                  <span v-else class="text-slate-300 text-xs">None</span>
                </td>
                <td class="px-5 py-3 text-xs text-slate-400">{{ formatDate(b.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="beneficiaries.last_page > 1" class="p-4 border-t border-slate-100 flex items-center justify-between">
          <p class="text-sm text-slate-500">{{ beneficiaries.from }}–{{ beneficiaries.to }} of {{ beneficiaries.total }}</p>
          <div class="flex gap-1 flex-wrap">
            <Link v-for="link in beneficiaries.links" :key="link.label" :href="link.url ?? '#'"
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

const props = defineProps({ beneficiaries: Object, barangays: Array })

const filters = reactive({
  barangay: new URLSearchParams(window.location.search).get('barangay') ?? '',
  status:   new URLSearchParams(window.location.search).get('status') ?? '',
  compliant:new URLSearchParams(window.location.search).get('compliant') ?? '',
})

const applyFilters = () => {
  router.get(route('superadmin.reports.beneficiaries'), {
    barangay:  filters.barangay  || undefined,
    status:    filters.status    || undefined,
    compliant: filters.compliant !== '' ? filters.compliant : undefined,
  }, { preserveState: true, preserveScroll: true, replace: true })
}

const exportUrl = computed(() =>
  route('superadmin.reports.beneficiaries.export') + '?' +
  new URLSearchParams({
    ...(filters.barangay  && { barangay:  filters.barangay }),
    ...(filters.status    && { status:    filters.status }),
    ...(filters.compliant !== '' && { compliant: filters.compliant }),
  }).toString()
)

const statusClass = (s) => ({
  active: 'badge-success', inactive: 'badge-neutral',
  suspended: 'badge-danger', graduated: 'badge-info', delisted: 'badge-danger',
}[s] ?? 'badge-neutral')

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-PH') : '—'

const StatMini = {
  props: ['label', 'value', 'color'],
  template: `
    <div class="card px-4 py-3 flex items-center gap-3">
      <div>
        <p class="text-xs text-slate-400">{{ label }}</p>
        <p class="text-lg font-bold text-slate-800">{{ value }}</p>
      </div>
    </div>
  `,
}
</script>
