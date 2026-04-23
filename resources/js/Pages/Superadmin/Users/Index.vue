<template>
  <Head title="User Management" />
  <StaffLayout page-title="User Management" page-subtitle="All system users — superadmins, admins, verifiers, officers & beneficiaries">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 class="text-xl font-bold text-slate-800">All System Users</h1>
          <p class="text-sm text-slate-400 mt-0.5">{{ counts.all.toLocaleString() }} total users across all roles</p>
        </div>
        <!-- Search -->
        <div class="relative w-full sm:w-72">
          <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            v-model="search"
            @input="debouncedSearch"
            type="search"
            placeholder="Search name, email, ID…"
            class="form-input pl-9 text-sm w-full"
          />
        </div>
      </div>

      <!-- Role Tabs -->
      <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-0">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="setTab(tab.key)"
          :class="[
            'px-4 py-2.5 text-sm font-medium rounded-t-lg border-b-2 transition-colors -mb-px',
            activeTab === tab.key
              ? 'border-brand-600 text-brand-600 bg-brand-50'
              : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'
          ]"
        >
          {{ tab.label }}
          <span :class="['ml-1.5 text-xs font-bold px-1.5 py-0.5 rounded-full', activeTab === tab.key ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-600']">
            {{ counts[tab.key] }}
          </span>
        </button>
      </div>

      <!-- ── Staff Table (all tabs except beneficiary) ───────────────────── -->
      <div v-if="activeTab !== 'beneficiary'" class="card overflow-hidden">
        <div v-if="!staff?.data?.length" class="py-16 text-center text-slate-400">
          <UserGroupIcon class="w-12 h-12 opacity-20 mx-auto mb-2" />
          <p class="text-sm">No users found{{ search ? ' matching your search' : '' }}.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">User</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Role</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Office</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Employee ID</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Joined</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="user in staff.data" :key="user.id" class="hover:bg-slate-50/60 transition-colors">
                <td class="px-5 py-3">
                  <div class="flex items-center gap-3">
                    <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0', roleAvatarColor(user.role)]">
                      {{ initials(user.name) }}
                    </div>
                    <div>
                      <p class="font-medium text-slate-700">{{ user.name }}</p>
                      <p class="text-xs text-slate-400">{{ user.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-3">
                  <span :class="['badge badge-sm', roleBadgeClass(user.role)]">
                    {{ roleLabel(user.role) }}
                  </span>
                </td>
                <td class="px-5 py-3 text-xs text-slate-500">{{ user.office?.name ?? '—' }}</td>
                <td class="px-5 py-3 text-xs font-mono text-slate-500">{{ user.employee_id ?? '—' }}</td>
                <td class="px-5 py-3 text-center">
                  <span :class="['badge badge-sm', user.is_active ? 'badge-success' : 'badge-danger']">
                    {{ user.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-5 py-3 text-xs text-slate-400">{{ formatDate(user.created_at) }}</td>
                <td class="px-5 py-3 text-center">
                  <button
                    v-if="user.id !== $page.props.auth.user.id"
                    @click="toggleUser(user)"
                    :disabled="toggling === user.id"
                    :class="['btn btn-xs', user.is_active ? 'btn-danger' : 'btn-success']"
                  >
                    {{ toggling === user.id ? '…' : user.is_active ? 'Deactivate' : 'Activate' }}
                  </button>
                  <span v-else class="text-xs text-slate-400 italic">You</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div v-if="staff?.last_page > 1" class="px-5 py-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <span>Page {{ staff.current_page }} of {{ staff.last_page }} · {{ staff.total }} users</span>
          <div class="flex gap-1">
            <button v-if="staff.prev_page_url" @click="goPage(staff.current_page - 1)"
              class="btn btn-ghost btn-xs">← Prev</button>
            <button v-if="staff.next_page_url" @click="goPage(staff.current_page + 1)"
              class="btn btn-ghost btn-xs">Next →</button>
          </div>
        </div>
      </div>

      <!-- ── Beneficiaries Table ─────────────────────────────────────────── -->
      <div v-else class="card overflow-hidden">
        <div v-if="!beneficiaries?.data?.length" class="py-16 text-center text-slate-400">
          <UsersIcon class="w-12 h-12 opacity-20 mx-auto mb-2" />
          <p class="text-sm">No beneficiaries found{{ search ? ' matching your search' : '' }}.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Beneficiary</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Unique ID</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Barangay</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Compliance</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Portal</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="ben in beneficiaries.data" :key="ben.id" class="hover:bg-slate-50/60 transition-colors">
                <td class="px-5 py-3">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 text-xs font-bold shrink-0">
                      {{ initials(ben.full_name) }}
                    </div>
                    <div>
                      <p class="font-medium text-slate-700">{{ ben.full_name }}</p>
                      <p class="text-xs text-slate-400">{{ ben.user?.email ?? 'No portal account' }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-3 text-xs font-mono text-slate-600">{{ ben.unique_id }}</td>
                <td class="px-5 py-3 text-xs text-slate-500">{{ ben.barangay }}</td>
                <td class="px-5 py-3">
                  <span :class="['badge badge-sm', ben.is_compliant ? 'badge-success' : 'badge-warning']">
                    {{ ben.is_compliant ? 'Compliant' : 'Pending' }}
                  </span>
                </td>
                <td class="px-5 py-3 text-center">
                  <span :class="['badge badge-sm', ben.user ? 'badge-info' : 'badge-neutral']">
                    {{ ben.user ? 'Active' : 'No Account' }}
                  </span>
                </td>
                <td class="px-5 py-3 text-center">
                  <span :class="['badge badge-sm', ben.is_active ? 'badge-success' : 'badge-danger']">
                    {{ ben.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-5 py-3 text-center">
                  <button
                    @click="toggleBeneficiary(ben)"
                    :disabled="toggling === `b-${ben.id}`"
                    :class="['btn btn-xs', ben.is_active ? 'btn-danger' : 'btn-success']"
                  >
                    {{ toggling === `b-${ben.id}` ? '…' : ben.is_active ? 'Deactivate' : 'Activate' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div v-if="beneficiaries?.last_page > 1" class="px-5 py-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <span>Page {{ beneficiaries.current_page }} of {{ beneficiaries.last_page }} · {{ beneficiaries.total }} beneficiaries</span>
          <div class="flex gap-1">
            <button v-if="beneficiaries.prev_page_url" @click="goPage(beneficiaries.current_page - 1)"
              class="btn btn-ghost btn-xs">← Prev</button>
            <button v-if="beneficiaries.next_page_url" @click="goPage(beneficiaries.current_page + 1)"
              class="btn btn-ghost btn-xs">Next →</button>
          </div>
        </div>
      </div>

    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import {
  MagnifyingGlassIcon, UsersIcon, UserGroupIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  staff:         Object,
  beneficiaries: Object,
  counts:        Object,
  filters:       Object,
})

const $page  = usePage()
const search  = ref(props.filters?.search ?? '')
const activeTab = ref(props.filters?.tab ?? 'all')
const toggling  = ref(null)

// ── Tabs ──────────────────────────────────────────────────────────────────────
const tabs = [
  { key: 'all',                 label: 'All Users' },
  { key: 'superadmin',         label: 'Superadmins' },
  { key: 'admin',              label: 'Admins' },
  { key: 'compliance_verifier',label: 'Verifiers' },
  { key: 'field_officer',      label: 'Field Officers' },
  { key: 'beneficiary',        label: 'Beneficiaries' },
]

const setTab = (tab) => {
  activeTab.value = tab
  router.get(route('superadmin.users.index'), { tab, search: search.value || undefined }, {
    preserveState: true, preserveScroll: true, replace: true,
  })
}

// ── Debounced search ──────────────────────────────────────────────────────────
let timer = null
const debouncedSearch = () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get(route('superadmin.users.index'), {
      tab: activeTab.value,
      search: search.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true })
  }, 350)
}

// ── Toggle actions ────────────────────────────────────────────────────────────
const toggleUser = (user) => {
  toggling.value = user.id
  router.patch(route('superadmin.users.toggle', user.id), {}, {
    preserveScroll: true,
    onFinish: () => { toggling.value = null },
  })
}

const toggleBeneficiary = (ben) => {
  toggling.value = `b-${ben.id}`
  router.patch(route('superadmin.beneficiaries.toggle-active', ben.id), {}, {
    preserveScroll: true,
    onFinish: () => { toggling.value = null },
  })
}

const goPage = (page) => {
  router.get(route('superadmin.users.index'), {
    tab: activeTab.value,
    search: search.value || undefined,
    page,
  }, { preserveScroll: true })
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const initials = (name) =>
  (name ?? '?').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()

const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'

const roleLabel = (role) => ({
  superadmin:           'Superadmin',
  admin:                'Admin',
  compliance_verifier:  'Verifier',
  field_officer:        'Field Officer',
}[role] ?? role)

const roleBadgeClass = (role) => ({
  superadmin:           'badge-danger',
  admin:                'badge-info',
  compliance_verifier:  'badge-warning',
  field_officer:        'badge-success',
}[role] ?? 'badge-neutral')

const roleAvatarColor = (role) => ({
  superadmin:           'bg-red-500',
  admin:                'bg-brand-600',
  compliance_verifier:  'bg-amber-500',
  field_officer:        'bg-emerald-600',
}[role] ?? 'bg-slate-400')
</script>
