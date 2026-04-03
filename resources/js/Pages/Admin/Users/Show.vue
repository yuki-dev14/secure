<template>
  <Head :title="`${user.name} — Staff Profile`" />
  <StaffLayout :page-title="user.name" :page-subtitle="user.role_display ?? user.role">
    <div class="max-w-4xl mx-auto space-y-5">

      <!-- Top bar -->
      <div class="flex items-center gap-3">
        <Link :href="route('admin.users.index')" class="btn btn-secondary btn-sm">
          ← All Staff
        </Link>
        <Link :href="route('admin.users.edit', user.id)" class="btn btn-primary btn-sm ml-auto">
          <PencilIcon class="w-4 h-4" />
          Edit Account
        </Link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <!-- LEFT: Profile card -->
        <div class="space-y-5">
          <div class="card">
            <div class="card-body text-center pt-8 pb-6">
              <!-- Avatar -->
              <div :class="[
                'w-20 h-20 rounded-full flex items-center justify-center text-white text-2xl font-bold mx-auto mb-4',
                roleColor(user.role)
              ]">
                {{ initials(user.name) }}
              </div>
              <h2 class="text-lg font-bold text-slate-800">{{ user.name }}</h2>
              <p class="text-sm text-slate-500 mt-0.5">{{ user.email }}</p>
              <div class="flex items-center justify-center gap-2 mt-3">
                <span :class="['badge', roleBadge(user.role)]">{{ user.role_display ?? user.role }}</span>
                <span :class="['badge', user.is_active ? 'badge-success' : 'badge-neutral']">
                  {{ user.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </div>

            <div class="card-footer space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-slate-400">Username</span>
                <span class="font-mono text-slate-600 text-xs">{{ user.username ?? '—' }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-400">Employee ID</span>
                <span class="font-mono text-slate-600 text-xs">{{ user.employee_id ?? '—' }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-400">Contact</span>
                <span class="text-slate-600 text-xs">{{ user.contact_number ?? '—' }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-400">Position</span>
                <span class="text-slate-600 text-xs">{{ user.position ?? '—' }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-400">Office</span>
                <span class="text-slate-600 text-xs text-right max-w-[160px]">{{ user.office?.name ?? 'All Offices' }}</span>
              </div>
            </div>
          </div>

          <!-- Quick actions -->
          <div class="card">
            <div class="card-header">
              <h3 class="text-sm font-semibold text-slate-700">Quick Actions</h3>
            </div>
            <div class="card-body space-y-2">
              <Link :href="route('admin.users.edit', user.id)" class="btn btn-secondary w-full justify-start gap-2">
                <PencilIcon class="w-4 h-4" />
                Edit Account Details
              </Link>
              <button
                @click="toggleActive"
                class="btn w-full justify-start gap-2"
                :class="user.is_active ? 'btn-secondary text-warning-600' : 'btn-secondary text-success-600'"
              >
                <component :is="user.is_active ? LockClosedIcon : LockOpenIcon" class="w-4 h-4" />
                {{ user.is_active ? 'Deactivate Account' : 'Activate Account' }}
              </button>
            </div>
          </div>
        </div>

        <!-- RIGHT: Activity + Stats -->
        <div class="lg:col-span-2 space-y-5">

          <!-- Role permissions -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Role Permissions</h3>
              <span :class="['badge', roleBadge(user.role)]">{{ user.role_display ?? user.role }}</span>
            </div>
            <div class="card-body">
              <ul class="space-y-2">
                <li v-for="perm in rolePermissions" :key="perm" class="flex items-start gap-2 text-sm">
                  <CheckCircleIcon class="w-4 h-4 text-success-500 flex-shrink-0 mt-0.5" />
                  <span class="text-slate-600">{{ perm }}</span>
                </li>
              </ul>
            </div>
          </div>

          <!-- Account Meta -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Account Information</h3>
            </div>
            <div class="card-body grid grid-cols-2 gap-4 text-sm">
              <div>
                <p class="text-slate-400 text-xs mb-0.5">Account Created</p>
                <p class="font-medium text-slate-700">{{ formatDate(user.created_at) }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-xs mb-0.5">Last Updated</p>
                <p class="font-medium text-slate-700">{{ formatDate(user.updated_at) }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-xs mb-0.5">Last Login</p>
                <p class="font-medium text-slate-700">{{ user.last_login_at ? formatDate(user.last_login_at) : 'Never' }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-xs mb-0.5">Account ID</p>
                <p class="font-mono text-xs text-slate-500">#{{ user.id }}</p>
              </div>
            </div>
          </div>

          <!-- System access summary -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">System Access</h3>
            </div>
            <div class="card-body grid grid-cols-3 gap-3">
              <AccessBadge
                v-for="access in accessList"
                :key="access.label"
                :label="access.label"
                :icon="access.icon"
                :granted="access.roles.includes(user.role)"
              />
            </div>
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
  PencilIcon, CheckCircleIcon,
  LockClosedIcon, LockOpenIcon,
  CogIcon, ClipboardDocumentCheckIcon, UserGroupIcon,
  QrCodeIcon, UsersIcon, CalendarDaysIcon, ShieldCheckIcon,
  DocumentChartBarIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({ user: Object })

const rolePermissionsMap = {
  admin: [
    'Manage staff accounts (create, edit, deactivate)',
    'View and update all beneficiary records',
    'Create and manage distribution events',
    'Batch compute cash grant amounts',
    'Send claiming notifications to beneficiaries',
    'Generate and export reports',
  ],
  compliance_verifier: [
    'View beneficiary list and profiles',
    'Record education compliance (85% attendance rule)',
    'Record health and immunization compliance',
    'Record FDS (Family Development Session) attendance',
    'Mark households as compliant or non-compliant',
    'View compliance history by period',
  ],
  field_officer: [
    'Scan beneficiary QR codes during distribution events',
    'View beneficiary identity and compliance status',
    'Record cash grant releases with proxy support',
    'Detect and log double-claim attempts',
    'View own daily distribution history',
  ],
}

const rolePermissions = computed(() => rolePermissionsMap[props.user.role] ?? [])

const accessList = [
  { label: 'Beneficiary Records', icon: UsersIcon, roles: ['superadmin', 'admin', 'compliance_verifier', 'field_officer'] },
  { label: 'Compliance Entry',    icon: ClipboardDocumentCheckIcon, roles: ['superadmin', 'admin', 'compliance_verifier'] },
  { label: 'Distribution Events', icon: CalendarDaysIcon, roles: ['superadmin', 'admin'] },
  { label: 'QR Scanner',          icon: QrCodeIcon, roles: ['superadmin', 'admin', 'field_officer'] },
  { label: 'Staff Management',    icon: CogIcon, roles: ['superadmin', 'admin'] },
  { label: 'Audit Trail',         icon: ShieldCheckIcon, roles: ['superadmin'] },
]

const initials = (name) => name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()

const roleColor = (role) => ({
  admin: 'bg-brand-600', compliance_verifier: 'bg-amber-500', field_officer: 'bg-green-600',
}[role] ?? 'bg-slate-400')

const roleBadge = (role) => ({
  admin: 'badge-info', compliance_verifier: 'badge-warning', field_officer: 'badge-success',
}[role] ?? 'badge-neutral')

const toggleActive = () => {
  router.patch(route('admin.users.toggle', props.user.id), {}, { preserveScroll: true })
}

const formatDate = (d) =>
  d ? new Date(d).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—'

// Inline component
const AccessBadge = {
  props: ['label', 'icon', 'granted'],
  template: `
    <div :class="[
      'flex items-center gap-2 p-2.5 rounded-xl border text-xs',
      granted ? 'bg-success-50 border-green-200 text-success-700' : 'bg-slate-50 border-slate-200 text-slate-400'
    ]">
      <component :is="icon" class="w-4 h-4 flex-shrink-0" />
      <span class="font-medium leading-tight">{{ label }}</span>
      <span class="ml-auto flex-shrink-0">{{ granted ? '✓' : '✗' }}</span>
    </div>
  `,
}
</script>
