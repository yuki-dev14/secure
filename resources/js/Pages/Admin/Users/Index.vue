<template>
  <Head title="Staff Management" />
  <StaffLayout page-title="Staff Management" page-subtitle="Manage DSWD field officers, verifiers, and admins">
    <div class="space-y-4">

      <!-- Header row -->
      <div class="flex flex-wrap items-center gap-3 justify-between">
        <div class="flex flex-wrap gap-3">
          <!-- Search -->
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input
              v-model="filters.search"
              @input="applyFilters"
              type="text"
              placeholder="Search name, email, ID…"
              class="form-input pl-9 w-60"
            />
          </div>

          <!-- Role filter -->
          <select v-model="filters.role" @change="applyFilters" class="form-select w-44">
            <option value="">All Roles</option>
            <option value="admin">Admin</option>
            <option value="compliance_verifier">Compliance Verifier</option>
            <option value="field_officer">Field Officer</option>
          </select>

          <!-- Status filter -->
          <select v-model="filters.status" @change="applyFilters" class="form-select w-36">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>

        <Link :href="route('admin.users.create')" class="btn btn-primary">
          <UserPlusIcon class="w-4 h-4" />
          Add Staff Account
        </Link>
      </div>

      <!-- Role stat pills -->
      <div class="flex flex-wrap gap-2">
        <div v-for="stat in roleStats" :key="stat.role"
          :class="['flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold border', stat.color]">
          <component :is="stat.icon" class="w-3.5 h-3.5" />
          {{ stat.count }} {{ stat.label }}
        </div>
      </div>

      <!-- Table -->
      <div class="card">
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Staff Member</th>
                <th>Role</th>
                <th>Employee ID</th>
                <th>Office</th>
                <th>Status</th>
                <th>Last Active</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="users.data.length === 0">
                <td colspan="7" class="text-center py-14 text-slate-400">
                  <UsersIcon class="w-10 h-10 opacity-20 mx-auto mb-2" />
                  <p>No staff accounts found.</p>
                </td>
              </tr>

              <tr v-for="user in users.data" :key="user.id" class="group">
                <!-- Name + avatar -->
                <td>
                  <div class="flex items-center gap-3">
                    <div :class="['w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0', roleColor(user.role)]">
                      {{ initials(user.name) }}
                    </div>
                    <div>
                      <p class="font-medium text-slate-800 text-sm">{{ user.name }}</p>
                      <p class="text-xs text-slate-400">{{ user.email }}</p>
                    </div>
                  </div>
                </td>

                <!-- Role badge -->
                <td>
                  <span :class="['badge', roleBadge(user.role)]">
                    <component :is="roleIcon(user.role)" class="w-3 h-3" />
                    {{ user.role_display ?? user.role }}
                  </span>
                </td>

                <!-- Employee ID -->
                <td class="text-sm font-mono text-slate-500">{{ user.employee_id ?? '—' }}</td>

                <!-- Office -->
                <td class="text-sm text-slate-500 max-w-[180px] truncate">
                  {{ user.office?.name ?? 'Unassigned' }}
                </td>

                <!-- Active toggle -->
                <td>
                  <button
                    @click="toggleActive(user)"
                    :class="[
                      'relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none',
                      user.is_active ? 'bg-success-500' : 'bg-slate-300'
                    ]"
                    :title="user.is_active ? 'Click to deactivate' : 'Click to activate'"
                  >
                    <span :class="[
                      'inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform',
                      user.is_active ? 'translate-x-4' : 'translate-x-0.5'
                    ]" />
                  </button>
                </td>

                <!-- Last active -->
                <td class="text-xs text-slate-400">
                  {{ user.last_login_at ? formatDate(user.last_login_at) : 'Never' }}
                </td>

                <!-- Actions -->
                <td class="text-right">
                  <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <Link :href="route('admin.users.show', user.id)" class="btn btn-ghost btn-sm" title="View">
                      <EyeIcon class="w-4 h-4" />
                    </Link>
                    <Link :href="route('admin.users.edit', user.id)" class="btn btn-ghost btn-sm" title="Edit">
                      <PencilIcon class="w-4 h-4" />
                    </Link>
                    <button
                      @click="confirmDelete(user)"
                      class="btn btn-ghost btn-sm text-danger-600"
                      title="Delete"
                      :disabled="user.id === $page.props.auth.user.id"
                    >
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="users.last_page > 1" class="p-4 border-t border-slate-100 flex items-center justify-between">
          <p class="text-sm text-slate-500">
            Showing {{ users.from }}–{{ users.to }} of {{ users.total }} staff accounts
          </p>
          <div class="flex gap-1">
            <Link
              v-for="link in users.links"
              :key="link.label"
              :href="link.url ?? '#'"
              :class="[
                'btn btn-sm',
                link.active ? 'btn-primary' : 'btn-secondary',
                !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
              ]"
              v-html="link.label"
            />
          </div>
        </div>
      </div>

      <!-- Delete confirmation modal -->
      <Teleport to="body">
        <div v-if="deleteTarget" class="modal-backdrop" @click.self="deleteTarget = null">
          <div class="modal-container max-w-sm">
            <div class="modal-header">
              <h3 class="font-semibold text-slate-800">Confirm Delete</h3>
              <button @click="deleteTarget = null" class="btn btn-ghost btn-icon">✕</button>
            </div>
            <div class="modal-body space-y-3">
              <div class="flex items-center gap-3 p-3 bg-danger-50 rounded-xl border border-danger-200">
                <ExclamationTriangleIcon class="w-6 h-6 text-danger-600 flex-shrink-0" />
                <p class="text-sm text-danger-700">
                  Delete <strong>{{ deleteTarget.name }}</strong>? This action cannot be undone.
                </p>
              </div>
              <p class="text-xs text-slate-400">Their audit logs will be preserved for accountability.</p>
            </div>
            <div class="modal-footer">
              <button @click="deleteTarget = null" class="btn btn-secondary btn-sm">Cancel</button>
              <button @click="executeDelete" :disabled="deleting" class="btn btn-danger btn-sm">
                {{ deleting ? 'Deleting…' : 'Yes, Delete Account' }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import {
  MagnifyingGlassIcon, UserPlusIcon, UsersIcon,
  EyeIcon, PencilIcon, TrashIcon,
  ExclamationTriangleIcon,
  ShieldCheckIcon, ClipboardDocumentCheckIcon,
  UserGroupIcon, CogIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  users:   Object,
  offices: Array,
})

const page        = usePage()
const deleteTarget = ref(null)
const deleting     = ref(false)

const filters = reactive({ search: '', role: '', status: '' })

let filterTimeout = null
const applyFilters = () => {
  clearTimeout(filterTimeout)
  filterTimeout = setTimeout(() => {
    router.get(route('admin.users.index'), filters, {
      preserveState: true, preserveScroll: true, replace: true,
    })
  }, 400)
}

// Role stats from current page data
const roleStats = computed(() => {
  const data = props.users.data
  return [
    {
      role: 'admin', label: 'Admins',
      count: data.filter(u => u.role === 'admin').length,
      color: 'bg-brand-50 text-brand-700 border-brand-200',
      icon: CogIcon,
    },
    {
      role: 'compliance_verifier', label: 'Verifiers',
      count: data.filter(u => u.role === 'compliance_verifier').length,
      color: 'bg-warning-50 text-warning-700 border-yellow-200',
      icon: ClipboardDocumentCheckIcon,
    },
    {
      role: 'field_officer', label: 'Field Officers',
      count: data.filter(u => u.role === 'field_officer').length,
      color: 'bg-success-50 text-success-700 border-green-200',
      icon: UserGroupIcon,
    },
  ]
})

const initials = (name) =>
  (name ?? '').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()

const roleColor = (role) => ({
  superadmin:          'bg-red-500',
  admin:               'bg-brand-600',
  compliance_verifier: 'bg-amber-500',
  field_officer:       'bg-green-600',
}[role] ?? 'bg-slate-400')

const roleBadge = (role) => ({
  superadmin:          'badge-danger',
  admin:               'badge-info',
  compliance_verifier: 'badge-warning',
  field_officer:       'badge-success',
}[role] ?? 'badge-neutral')

const roleIcon = (role) => ({
  superadmin:          ShieldCheckIcon,
  admin:               CogIcon,
  compliance_verifier: ClipboardDocumentCheckIcon,
  field_officer:       UserGroupIcon,
}[role] ?? UsersIcon)

const toggleActive = (user) => {
  router.patch(route('admin.users.toggle', user.id), {}, {
    preserveScroll: true,
    onSuccess: () => { user.is_active = !user.is_active },
  })
}

const confirmDelete = (user) => { deleteTarget.value = user }

const executeDelete = () => {
  if (!deleteTarget.value) return
  deleting.value = true
  router.delete(route('admin.users.destroy', deleteTarget.value.id), {
    onSuccess: () => { deleteTarget.value = null },
    onFinish:  () => { deleting.value = false },
  })
}

const formatDate = (d) =>
  d ? new Date(d).toLocaleString('en-PH', { dateStyle: 'short', timeStyle: 'short' }) : '—'
</script>
