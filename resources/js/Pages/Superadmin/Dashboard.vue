<template>
  <Head title="Dashboard" />
  <StaffLayout page-title="Superadmin Dashboard" page-subtitle="System overview — Lipa City, Batangas">
    <div class="space-y-6">

      <!-- KPI Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          v-for="kpi in kpis" :key="kpi.label"
          :label="kpi.label" :value="kpi.value"
          :icon="kpi.icon" :color="kpi.color"
        />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Audit Logs -->
        <div class="lg:col-span-2 card">
          <div class="card-header">
            <div>
              <h3 class="font-semibold text-slate-800">Recent Audit Trail</h3>
              <p class="text-xs text-slate-400 mt-0.5">Latest system activity</p>
            </div>
            <Link :href="route('superadmin.audit-logs.index')" class="btn btn-secondary btn-sm">
              View All
            </Link>
          </div>
          <div class="overflow-x-auto">
            <table class="table">
              <thead>
                <tr>
                  <th>Event</th>
                  <th>User</th>
                  <th>IP</th>
                  <th>When</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in stats.recent_logs" :key="log.id">
                  <td>
                    <span :class="['badge', eventBadge(log.event)]">{{ log.event }}</span>
                  </td>
                  <td class="text-slate-600">{{ log.user?.name ?? 'System' }}</td>
                  <td class="text-slate-500 font-mono text-xs">{{ log.ip_address }}</td>
                  <td class="text-slate-400 text-xs whitespace-nowrap">
                    {{ formatDate(log.created_at) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-slate-800">Quick Actions</h3>
          </div>
          <div class="card-body space-y-3">
            <Link :href="route('superadmin.beneficiaries.create')" class="btn btn-primary w-full justify-start gap-3">
              <UserPlusIcon class="w-5 h-5" />
              Register Beneficiary
            </Link>
            <Link :href="route('superadmin.audit-logs.index')" class="btn btn-secondary w-full justify-start gap-3">
              <ShieldCheckIcon class="w-5 h-5" />
              View Audit Trail
            </Link>
            <Link :href="route('admin.events.create')" class="btn btn-secondary w-full justify-start gap-3">
              <CalendarDaysIcon class="w-5 h-5" />
              Schedule Distribution
            </Link>
            <Link :href="route('admin.users.index')" class="btn btn-secondary w-full justify-start gap-3">
              <UsersIcon class="w-5 h-5" />
              Manage Staff
            </Link>
          </div>

          <!-- System Health -->
          <div class="card-footer">
            <p class="text-xs font-medium text-slate-600 mb-2">System Coverage</p>
            <div class="flex items-center gap-2">
              <MapPinIcon class="w-4 h-4 text-brand-500" />
              <span class="text-xs text-slate-500">{{ stats.barangay_coverage }} barangays active</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  UsersIcon, ShieldCheckIcon, CalendarDaysIcon,
  UserPlusIcon, MapPinIcon, CheckBadgeIcon,
  ExclamationTriangleIcon, ClipboardDocumentCheckIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import StatCard from '@/Components/StatCard.vue'

const props = defineProps({ stats: Object })

const kpis = computed(() => [
  {
    label: 'Total Beneficiaries',
    value: props.stats.total_beneficiaries.toLocaleString(),
    icon: UsersIcon,
    color: 'bg-brand-50 text-brand-600',
  },
  {
    label: 'Active & Compliant',
    value: props.stats.compliant.toLocaleString(),
    icon: CheckBadgeIcon,
    color: 'bg-success-50 text-success-600',
  },
  {
    label: 'Non-Compliant',
    value: props.stats.non_compliant.toLocaleString(),
    icon: ExclamationTriangleIcon,
    color: 'bg-warning-50 text-warning-600',
  },
  {
    label: 'Staff Members',
    value: props.stats.total_staff.toLocaleString(),
    icon: ClipboardDocumentCheckIcon,
    color: 'bg-slate-100 text-slate-600',
  },
])

const eventBadge = (event) => {
  if (event.includes('login'))  return 'badge-info'
  if (event.includes('fraud') || event.includes('double')) return 'badge-danger'
  if (event.includes('delete')) return 'badge-warning'
  if (event.includes('create') || event.includes('grant')) return 'badge-success'
  return 'badge-neutral'
}

const formatDate = (d) => d ? new Date(d).toLocaleString('en-PH', { dateStyle: 'short', timeStyle: 'short' }) : '—'
</script>
