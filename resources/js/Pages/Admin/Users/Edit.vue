<template>
  <Head :title="`Edit — ${user.name}`" />
  <StaffLayout :page-title="`Edit: ${user.name}`" page-subtitle="Update staff account details">
    <div class="max-w-2xl mx-auto">
      <form @submit.prevent="submit" class="space-y-5">

        <!-- Alert: editing own account -->
        <div v-if="isSelf" class="alert alert-warning">
          <ExclamationTriangleIcon class="w-5 h-5 flex-shrink-0" />
          <p class="text-sm">You are editing your own account. Changing your role will affect your current session permissions.</p>
        </div>

        <!-- Personal Details -->
        <div class="card">
          <div class="card-header">
            <div class="flex items-center gap-3">
              <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold', roleColor(user.role)]">
                {{ initials(user.name) }}
              </div>
              <div>
                <p class="font-semibold text-slate-800">{{ user.name }}</p>
                <p class="text-xs text-slate-400">{{ user.email }}</p>
              </div>
              <span :class="['badge ml-auto', roleBadge(user.role)]">{{ user.role_display ?? user.role }}</span>
            </div>
          </div>
          <div class="card-body space-y-4">

            <div>
              <label class="form-label" for="name">Full Name <span class="text-danger-500">*</span></label>
              <input id="name" v-model="form.name" type="text" class="form-input"
                :class="{ 'border-danger-500': form.errors.name }" required />
              <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label" for="email">Email <span class="text-danger-500">*</span></label>
                <input id="email" v-model="form.email" type="email" class="form-input"
                  :class="{ 'border-danger-500': form.errors.email }" required />
                <p v-if="form.errors.email" class="form-error">{{ form.errors.email }}</p>
              </div>
              <div>
                <label class="form-label">Username</label>
                <input :value="user.username" type="text" class="form-input" disabled />
                <p class="form-hint">Usernames cannot be changed</p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Contact Number</label>
                <input v-model="form.contact_number" type="text" class="form-input" placeholder="09XX-XXX-XXXX" />
              </div>
              <div>
                <label class="form-label">Position</label>
                <input v-model="form.position" type="text" class="form-input" placeholder="Field Officer I" />
              </div>
            </div>

          </div>
        </div>

        <!-- Role & Office -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-slate-800">Role & Office</h3>
          </div>
          <div class="card-body space-y-4">

            <div>
              <label class="form-label">System Role <span class="text-danger-500">*</span></label>
              <div class="grid grid-cols-3 gap-3">
                <label
                  v-for="r in roles"
                  :key="r.value"
                  :class="[
                    'relative flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 cursor-pointer transition-all text-center',
                    form.role === r.value
                      ? 'border-brand-500 bg-brand-50'
                      : 'border-slate-200 hover:border-slate-300'
                  ]"
                >
                  <input type="radio" v-model="form.role" :value="r.value" class="sr-only" />
                  <div :class="['w-8 h-8 rounded-lg flex items-center justify-center', r.bg]">
                    <component :is="r.icon" class="w-4 h-4" :class="r.iconColor" />
                  </div>
                  <span class="text-xs font-medium text-slate-700">{{ r.label }}</span>
                  <div v-if="form.role === r.value"
                    class="absolute top-1.5 right-1.5 w-4 h-4 bg-brand-600 rounded-full flex items-center justify-center">
                    <CheckIcon class="w-2.5 h-2.5 text-white" />
                  </div>
                </label>
              </div>
            </div>

            <div>
              <label class="form-label">Assigned Office</label>
              <select v-model="form.office_id" class="form-select">
                <option value="">No specific office</option>
                <option v-for="o in offices" :key="o.id" :value="o.id">{{ o.name }}</option>
              </select>
            </div>

          </div>
        </div>

        <!-- Account Status -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-slate-800">Account Status</h3>
          </div>
          <div class="card-body">
            <div class="flex items-center justify-between p-4 rounded-xl border"
              :class="form.is_active ? 'bg-success-50 border-green-200' : 'bg-slate-50 border-slate-200'">
              <div>
                <p class="font-medium text-sm text-slate-800">
                  {{ form.is_active ? 'Account Active' : 'Account Inactive' }}
                </p>
                <p class="text-xs text-slate-400 mt-0.5">
                  {{ form.is_active
                    ? 'This staff member can log in and use the system.'
                    : 'This staff member cannot log in. Toggle to re-activate.' }}
                </p>
              </div>
              <button
                type="button"
                @click="form.is_active = !form.is_active"
                :disabled="isSelf"
                :class="[
                  'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none',
                  form.is_active ? 'bg-success-500' : 'bg-slate-300',
                  isSelf ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                ]"
              >
                <span :class="[
                  'inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform',
                  form.is_active ? 'translate-x-6' : 'translate-x-1'
                ]" />
              </button>
            </div>
          </div>
        </div>

        <!-- Change Password (optional) -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-slate-800">Change Password</h3>
            <span class="text-xs text-slate-400">Leave blank to keep current password</span>
          </div>
          <div class="card-body space-y-4">

            <div>
              <label class="form-label">New Password</label>
              <div class="relative">
                <input
                  v-model="form.password"
                  :type="showPass ? 'text' : 'password'"
                  placeholder="Leave blank to keep current"
                  class="form-input pr-10"
                  :class="{ 'border-danger-500': form.errors.password }"
                />
                <button type="button" @click="showPass = !showPass"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                  <EyeIcon v-if="!showPass" class="w-4 h-4" />
                  <EyeSlashIcon v-else class="w-4 h-4" />
                </button>
              </div>
              <p v-if="form.errors.password" class="form-error">{{ form.errors.password }}</p>
            </div>

            <div v-if="form.password">
              <label class="form-label">Confirm New Password</label>
              <input
                v-model="form.password_confirmation"
                :type="showPass ? 'text' : 'password'"
                placeholder="Repeat new password"
                class="form-input"
                :class="{ 'border-danger-500': !passwordsMatch && form.password_confirmation }"
              />
              <p v-if="form.password_confirmation && !passwordsMatch" class="form-error">
                Passwords do not match
              </p>
            </div>

          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3">
          <Link :href="route('admin.users.index')" class="btn btn-secondary">← Cancel</Link>
          <button
            type="submit"
            :disabled="form.processing || (form.password && !passwordsMatch)"
            class="btn btn-primary flex-1"
          >
            <CheckIcon class="w-4 h-4" />
            {{ form.processing ? 'Saving Changes…' : 'Save Changes' }}
          </button>
        </div>

        <!-- Danger Zone -->
        <div v-if="!isSelf" class="card border-danger-200">
          <div class="card-header">
            <h3 class="font-semibold text-danger-600 text-sm">Danger Zone</h3>
          </div>
          <div class="card-body flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-700">Delete this account</p>
              <p class="text-xs text-slate-400">This will permanently remove the staff account.</p>
            </div>
            <button type="button" @click="confirmDelete = true" class="btn btn-danger btn-sm">
              <TrashIcon class="w-4 h-4" />
              Delete Account
            </button>
          </div>
        </div>

      </form>

      <!-- Delete modal -->
      <Teleport to="body">
        <div v-if="confirmDelete" class="modal-backdrop" @click.self="confirmDelete = false">
          <div class="modal-container max-w-sm">
            <div class="modal-header">
              <h3 class="font-semibold text-slate-800">Delete Account</h3>
            </div>
            <div class="modal-body space-y-3">
              <div class="alert alert-danger">
                <ExclamationTriangleIcon class="w-5 h-5 flex-shrink-0" />
                <p>Are you sure you want to delete <strong>{{ user.name }}</strong>'s account? This cannot be undone.</p>
              </div>
            </div>
            <div class="modal-footer">
              <button @click="confirmDelete = false" class="btn btn-secondary btn-sm">Cancel</button>
              <Link
                :href="route('admin.users.destroy', user.id)"
                method="delete"
                as="button"
                class="btn btn-danger btn-sm"
              >
                Delete Account
              </Link>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import {
  ExclamationTriangleIcon, CheckIcon, TrashIcon,
  EyeIcon, EyeSlashIcon,
  CogIcon, ClipboardDocumentCheckIcon, UserGroupIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({ user: Object, offices: Array })

const page          = usePage()
const showPass      = ref(false)
const confirmDelete = ref(false)

const isSelf = computed(() => props.user.id === page.props.auth.user?.id)

const roles = [
  { value: 'admin',               label: 'Admin',      icon: CogIcon,                    bg: 'bg-brand-50',   iconColor: 'text-brand-600' },
  { value: 'compliance_verifier', label: 'Verifier',   icon: ClipboardDocumentCheckIcon, bg: 'bg-warning-50', iconColor: 'text-warning-600' },
  { value: 'field_officer',       label: 'Field Officer', icon: UserGroupIcon,            bg: 'bg-success-50', iconColor: 'text-success-600' },
]

const form = useForm({
  name:                 props.user.name,
  email:                props.user.email,
  contact_number:       props.user.contact_number ?? '',
  position:             props.user.position ?? '',
  role:                 props.user.role,
  office_id:            props.user.office_id ?? '',
  is_active:            props.user.is_active,
  password:             '',
  password_confirmation:'',
})

const passwordsMatch = computed(() =>
  !form.password_confirmation || form.password === form.password_confirmation
)

const submit = () => {
  form.put(route('admin.users.update', props.user.id))
}

const initials = (name) =>
  name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()

const roleColor = (role) => ({
  admin: 'bg-brand-600', compliance_verifier: 'bg-amber-500', field_officer: 'bg-green-600',
}[role] ?? 'bg-slate-400')

const roleBadge = (role) => ({
  admin: 'badge-info', compliance_verifier: 'badge-warning', field_officer: 'badge-success',
}[role] ?? 'badge-neutral')
</script>
