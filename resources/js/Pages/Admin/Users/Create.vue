<template>
  <Head title="Add Staff Account" />
  <StaffLayout page-title="Add Staff Account" page-subtitle="Create a new DSWD staff account">
    <div class="max-w-2xl mx-auto">
      <form @submit.prevent="submit" class="space-y-5">

        <!-- Personal Details -->
        <div class="card">
          <div class="card-header">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-brand-50 rounded-lg flex items-center justify-center">
                <UserIcon class="w-4 h-4 text-brand-600" />
              </div>
              <h3 class="font-semibold text-slate-800">Personal Details</h3>
            </div>
          </div>
          <div class="card-body space-y-4">

            <div>
              <label class="form-label" for="name">Full Name <span class="text-danger-500">*</span></label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                placeholder="e.g. Maria Santos"
                class="form-input"
                :class="{ 'border-danger-500': form.errors.name }"
                required
              />
              <p v-if="form.errors.name" class="form-error">
                <ExclamationCircleIcon class="w-3.5 h-3.5" />
                {{ form.errors.name }}
              </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label" for="email">Email Address <span class="text-danger-500">*</span></label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  placeholder="staff@dswd.gov.ph"
                  class="form-input"
                  :class="{ 'border-danger-500': form.errors.email }"
                  required
                />
                <p v-if="form.errors.email" class="form-error">
                  <ExclamationCircleIcon class="w-3.5 h-3.5" />
                  {{ form.errors.email }}
                </p>
              </div>
              <div>
                <label class="form-label" for="username">Username <span class="text-danger-500">*</span></label>
                <input
                  id="username"
                  v-model="form.username"
                  type="text"
                  placeholder="e.g. msantos"
                  class="form-input"
                  :class="{ 'border-danger-500': form.errors.username }"
                  required
                />
                <p v-if="form.errors.username" class="form-error">
                  <ExclamationCircleIcon class="w-3.5 h-3.5" />
                  {{ form.errors.username }}
                </p>
                <p class="form-hint">Letters, numbers, dashes and underscores only</p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label" for="employee_id">Employee ID</label>
                <input
                  id="employee_id"
                  v-model="form.employee_id"
                  type="text"
                  placeholder="EMP-XXXX-001"
                  class="form-input"
                  :class="{ 'border-danger-500': form.errors.employee_id }"
                />
                <p v-if="form.errors.employee_id" class="form-error">
                  <ExclamationCircleIcon class="w-3.5 h-3.5" />
                  {{ form.errors.employee_id }}
                </p>
              </div>
              <div>
                <label class="form-label" for="contact_number">Contact Number</label>
                <input
                  id="contact_number"
                  v-model="form.contact_number"
                  type="text"
                  placeholder="09XX-XXX-XXXX"
                  class="form-input"
                />
              </div>
            </div>

            <div>
              <label class="form-label" for="position">Position / Designation</label>
              <input
                id="position"
                v-model="form.position"
                type="text"
                placeholder="e.g. Compliance Verifier I"
                class="form-input"
              />
            </div>

          </div>
        </div>

        <!-- Role & Office -->
        <div class="card">
          <div class="card-header">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-warning-50 rounded-lg flex items-center justify-center">
                <ShieldCheckIcon class="w-4 h-4 text-warning-600" />
              </div>
              <h3 class="font-semibold text-slate-800">Role & Office Assignment</h3>
            </div>
          </div>
          <div class="card-body space-y-4">

            <!-- Role picker cards -->
            <div>
              <label class="form-label mb-3">System Role <span class="text-danger-500">*</span></label>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <label
                  v-for="r in roles"
                  :key="r.value"
                  :class="[
                    'relative flex flex-col gap-2 p-4 rounded-xl border-2 cursor-pointer transition-all',
                    form.role === r.value
                      ? 'border-brand-500 bg-brand-50 shadow-sm'
                      : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                  ]"
                >
                  <input type="radio" v-model="form.role" :value="r.value" class="sr-only" />
                  <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', r.bg]">
                    <component :is="r.icon" class="w-5 h-5" :class="r.iconColor" />
                  </div>
                  <div>
                    <p class="font-semibold text-sm text-slate-800">{{ r.label }}</p>
                    <p class="text-xs text-slate-400 mt-0.5 leading-snug">{{ r.desc }}</p>
                  </div>
                  <!-- Selected checkmark -->
                  <div v-if="form.role === r.value"
                    class="absolute top-2 right-2 w-5 h-5 bg-brand-600 rounded-full flex items-center justify-center">
                    <CheckIcon class="w-3 h-3 text-white" />
                  </div>
                </label>
              </div>
              <p v-if="form.errors.role" class="form-error mt-2">
                <ExclamationCircleIcon class="w-3.5 h-3.5" />
                {{ form.errors.role }}
              </p>
            </div>

            <!-- Office -->
            <div>
              <label class="form-label" for="office_id">Assigned Office</label>
              <select id="office_id" v-model="form.office_id" class="form-select">
                <option value="">Select office (optional)</option>
                <option v-for="office in offices" :key="office.id" :value="office.id">
                  {{ office.name }}
                </option>
              </select>
              <p class="form-hint">Leave blank for system-wide assignment</p>
            </div>

          </div>
        </div>

        <!-- Password -->
        <div class="card">
          <div class="card-header">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-success-50 rounded-lg flex items-center justify-center">
                <LockClosedIcon class="w-4 h-4 text-success-600" />
              </div>
              <h3 class="font-semibold text-slate-800">Password</h3>
            </div>
          </div>
          <div class="card-body space-y-4">

            <div>
              <label class="form-label" for="password">Password <span class="text-danger-500">*</span></label>
              <div class="relative">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPass ? 'text' : 'password'"
                  placeholder="Min. 8 characters"
                  class="form-input pr-10"
                  :class="{ 'border-danger-500': form.errors.password }"
                  required
                />
                <button type="button" @click="showPass = !showPass"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                  <EyeIcon v-if="!showPass" class="w-4 h-4" />
                  <EyeSlashIcon v-else class="w-4 h-4" />
                </button>
              </div>
              <p v-if="form.errors.password" class="form-error">
                <ExclamationCircleIcon class="w-3.5 h-3.5" />
                {{ form.errors.password }}
              </p>
            </div>

            <div>
              <label class="form-label" for="password_confirmation">Confirm Password <span class="text-danger-500">*</span></label>
              <div class="relative">
                <input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  :type="showPassConfirm ? 'text' : 'password'"
                  placeholder="Repeat password"
                  class="form-input pr-10"
                  required
                />
                <button type="button" @click="showPassConfirm = !showPassConfirm"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                  <EyeIcon v-if="!showPassConfirm" class="w-4 h-4" />
                  <EyeSlashIcon v-else class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Password strength -->
            <div class="space-y-1.5">
              <div class="flex gap-1">
                <div v-for="i in 4" :key="i"
                  :class="['h-1.5 flex-1 rounded-full transition-all duration-300', strength >= i ? strengthColor : 'bg-slate-200']">
                </div>
              </div>
              <p v-if="form.password" class="text-xs" :class="strengthTextColor">
                {{ strengthLabel }} — {{ strengthTip }}
              </p>
            </div>

            <!-- Match indicator -->
            <p v-if="form.password_confirmation" class="text-xs"
              :class="passwordsMatch ? 'text-success-600' : 'text-danger-600'">
              {{ passwordsMatch ? '✓ Passwords match' : '✗ Passwords do not match' }}
            </p>
          </div>
        </div>

        <!-- Role permissions preview -->
        <div v-if="form.role" class="card border-brand-200">
          <div class="card-header">
            <p class="text-sm font-medium text-slate-700">Permissions for <strong>{{ selectedRole?.label }}</strong></p>
          </div>
          <div class="card-body">
            <ul class="space-y-1">
              <li v-for="perm in selectedRole?.permissions ?? []" :key="perm"
                class="flex items-center gap-2 text-sm text-slate-600">
                <CheckCircleIcon class="w-4 h-4 text-success-500 flex-shrink-0" />
                {{ perm }}
              </li>
            </ul>
          </div>
        </div>

        <!-- Submit row -->
        <div class="flex items-center gap-3">
          <Link :href="route('admin.users.index')" class="btn btn-secondary">
            ← Cancel
          </Link>
          <button
            type="submit"
            :disabled="form.processing || !passwordsMatch || strength < 2"
            class="btn btn-primary flex-1"
          >
            <UserPlusIcon class="w-4 h-4" />
            {{ form.processing ? 'Creating Account…' : 'Create Staff Account' }}
          </button>
        </div>

      </form>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import {
  UserIcon, ShieldCheckIcon, LockClosedIcon, UserPlusIcon,
  ExclamationCircleIcon, CheckIcon, CheckCircleIcon,
  EyeIcon, EyeSlashIcon,
  CogIcon, ClipboardDocumentCheckIcon, UserGroupIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({ offices: Array })

const showPass        = ref(false)
const showPassConfirm = ref(false)

const roles = [
  {
    value: 'admin',
    label: 'Admin',
    desc:  'Manage staff, events, and beneficiary records',
    icon:  CogIcon,
    bg:    'bg-brand-50',
    iconColor: 'text-brand-600',
    permissions: [
      'View and update all beneficiary records',
      'Create and schedule distribution events',
      'Manage field officers and verifiers',
      'Send notifications to beneficiaries',
      'View dashboard and reports',
    ],
  },
  {
    value: 'compliance_verifier',
    label: 'Compliance Verifier',
    desc:  'Record and verify compliance conditions',
    icon:  ClipboardDocumentCheckIcon,
    bg:    'bg-warning-50',
    iconColor: 'text-warning-600',
    permissions: [
      'View beneficiary list and profiles',
      'Record education, health, and FDS compliance',
      'Mark households as compliant or non-compliant',
      'View compliance history per period',
    ],
  },
  {
    value: 'field_officer',
    label: 'Field Officer',
    desc:  'Scan QR codes and release cash grants',
    icon:  UserGroupIcon,
    bg:    'bg-success-50',
    iconColor: 'text-success-600',
    permissions: [
      'Scan beneficiary QR codes during distribution',
      'View beneficiary details at time of claiming',
      'Record and confirm cash grant releases',
      'View own distribution history',
    ],
  },
]

const form = useForm({
  name:                 '',
  email:                '',
  username:             '',
  employee_id:          '',
  contact_number:       '',
  position:             '',
  role:                 '',
  office_id:            '',
  password:             '',
  password_confirmation:'',
})

const selectedRole = computed(() => roles.find(r => r.value === form.role) ?? null)

const passwordsMatch = computed(() =>
  !form.password_confirmation || form.password === form.password_confirmation
)

const strength = computed(() => {
  const p = form.password
  if (!p) return 0
  let s = 0
  if (p.length >= 8)           s++
  if (/[A-Z]/.test(p))         s++
  if (/[0-9]/.test(p))         s++
  if (/[^A-Za-z0-9]/.test(p))  s++
  return s
})

const strengthColor = computed(() => [
  '', 'bg-danger-500', 'bg-warning-500', 'bg-brand-400', 'bg-success-500',
][strength.value] ?? 'bg-slate-200')

const strengthTextColor = computed(() => [
  '', 'text-danger-600', 'text-warning-600', 'text-brand-600', 'text-success-600',
][strength.value] ?? '')

const strengthLabel = computed(() =>
  ['', 'Weak', 'Fair', 'Good', 'Strong'][strength.value] ?? ''
)

const strengthTip = computed(() => {
  const p = form.password
  if (!p.match(/[A-Z]/)) return 'Add an uppercase letter'
  if (!p.match(/[0-9]/)) return 'Add a number'
  if (!p.match(/[^A-Za-z0-9]/)) return 'Add a special character'
  return 'Password looks great!'
})

const submit = () => {
  form.post(route('admin.users.store'), {
    onSuccess: () => form.reset(),
  })
}
</script>
