<template>
  <Head title="Register Beneficiary" />
  <StaffLayout page-title="Register Beneficiary" page-subtitle="Create a new 4Ps beneficiary from the Listahanan">
    <div class="max-w-4xl mx-auto">

      <!-- Step indicator -->
      <div class="mb-8 flex items-center gap-0">
        <template v-for="(step, i) in steps" :key="step.id">
          <div class="flex items-center gap-2">
            <div :class="[
              'w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-all',
              currentStep > i + 1 ? 'border-green-500 bg-green-500 text-white' :
              currentStep === i + 1 ? 'border-brand-600 bg-brand-600 text-white' :
              'border-slate-300 bg-white text-slate-400'
            ]">
              <CheckIcon v-if="currentStep > i + 1" class="w-4 h-4" />
              <span v-else>{{ i + 1 }}</span>
            </div>
            <span :class="[
              'text-sm font-medium hidden sm:block',
              currentStep === i + 1 ? 'text-slate-800' : 'text-slate-400'
            ]">{{ step.label }}</span>
          </div>
          <div v-if="i < steps.length - 1" class="flex-1 h-0.5 mx-3"
            :class="currentStep > i + 1 ? 'bg-green-500' : 'bg-slate-200'">
          </div>
        </template>
      </div>

      <form @submit.prevent="submitForm">

        <!-- ── Step 1: Personal Information ─────────────────────────────── -->
        <div v-show="currentStep === 1" class="card">
          <div class="card-header">
            <div>
              <h3 class="font-semibold text-slate-800">Personal Information</h3>
              <p class="text-xs text-slate-400">Household representative details from Listahanan</p>
            </div>
          </div>
          <div class="card-body space-y-5">

            <div class="alert alert-info">
              <InformationCircleIcon class="w-5 h-5 flex-shrink-0" />
              <p class="text-sm">Beneficiaries are pre-qualified by <strong>Listahanan (NHTS-PR)</strong>. Only enter records from the official list.</p>
            </div>

            <!-- Listahanan ID -->
            <div>
              <label class="form-label">Listahanan / NHTS-PR Reference ID <span class="text-slate-400">(optional)</span></label>
              <input v-model="form.listahanan_id" type="text" class="form-input" placeholder="NHTS-PR-XXXX" />
            </div>

            <!-- Name -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="form-label">First Name <span class="text-danger-500">*</span></label>
                <input v-model="form.first_name" type="text" class="form-input"
                  :class="{'border-danger-500': errors.first_name}" placeholder="Juan" required />
                <p v-if="errors.first_name" class="form-error">{{ errors.first_name }}</p>
              </div>
              <div>
                <label class="form-label">Middle Name</label>
                <input v-model="form.middle_name" type="text" class="form-input" placeholder="Santos" />
              </div>
              <div>
                <label class="form-label">Last Name <span class="text-danger-500">*</span></label>
                <input v-model="form.last_name" type="text" class="form-input"
                  :class="{'border-danger-500': errors.last_name}" placeholder="dela Cruz" required />
                <p v-if="errors.last_name" class="form-error">{{ errors.last_name }}</p>
              </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div>
                <label class="form-label">Suffix</label>
                <select v-model="form.suffix" class="form-select">
                  <option value="">None</option>
                  <option>Jr.</option><option>Sr.</option>
                  <option>II</option><option>III</option>
                </select>
              </div>
              <div>
                <label class="form-label">Birthdate <span class="text-danger-500">*</span></label>
                <input v-model="form.birthdate" type="date" class="form-input"
                  :class="{'border-danger-500': errors.birthdate}" required />
                <p v-if="errors.birthdate" class="form-error">{{ errors.birthdate }}</p>
              </div>
              <div>
                <label class="form-label">Sex <span class="text-danger-500">*</span></label>
                <select v-model="form.sex" class="form-select" required>
                  <option value="">Select</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                </select>
              </div>
              <div>
                <label class="form-label">Civil Status</label>
                <select v-model="form.civil_status" class="form-select">
                  <option value="married">Married</option>
                  <option value="single">Single</option>
                  <option value="widowed">Widowed</option>
                  <option value="separated">Separated</option>
                  <option value="live-in">Live-in</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="form-label">Contact Number</label>
                <input v-model="form.contact_number" type="text" class="form-input" placeholder="09XX-XXX-XXXX" />
              </div>
              <div>
                <label class="form-label">Enrollment Date</label>
                <input v-model="form.enrollment_date" type="date" class="form-input" />
              </div>
            </div>

            <!-- Photo -->
            <div>
              <label class="form-label">Photo <span class="text-slate-400">(1x1 or 2x2, for ID Card)</span></label>
              <div class="flex items-center gap-4">
                <div class="w-20 h-20 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center flex-shrink-0">
                  <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" />
                  <UserIcon v-else class="w-8 h-8 text-slate-300" />
                </div>
                <label class="btn btn-secondary cursor-pointer">
                  <PhotoIcon class="w-4 h-4" />
                  Choose Photo
                  <input type="file" class="hidden" accept="image/*" @change="handlePhoto" />
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Step 2: Address ──────────────────────────────────────────── -->
        <div v-show="currentStep === 2" class="card">
          <div class="card-header">
            <div>
              <h3 class="font-semibold text-slate-800">Address & Office Assignment</h3>
              <p class="text-xs text-slate-400">Must be a valid Lipa City address</p>
            </div>
          </div>
          <div class="card-body space-y-5">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
              <div>
                <label class="form-label">House / Unit No.</label>
                <input v-model="form.house_no" type="text" class="form-input" placeholder="123" />
              </div>
              <div>
                <label class="form-label">Street</label>
                <input v-model="form.street" type="text" class="form-input" placeholder="Rizal St." />
              </div>
              <div>
                <label class="form-label">Purok</label>
                <input v-model="form.purok" type="text" class="form-input" placeholder="1-A" />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="sm:col-span-2">
                <label class="form-label">Barangay <span class="text-danger-500">*</span></label>
                <select v-model="form.barangay" class="form-select" required
                  :class="{'border-danger-500': errors.barangay}">
                  <option value="">Select Barangay</option>
                  <option v-for="office in offices.filter(o => o.type === 'barangay')" :key="office.id"
                    :value="office.barangay">
                    {{ office.barangay }}
                  </option>
                </select>
                <p v-if="errors.barangay" class="form-error">{{ errors.barangay }}</p>
              </div>
              <div>
                <label class="form-label">Office Assignment</label>
                <select v-model="form.office_id" class="form-select">
                  <option value="">Auto-assign</option>
                  <option v-for="office in offices" :key="office.id" :value="office.id">
                    {{ office.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">City</label>
                <input value="Lipa City" type="text" class="form-input" disabled />
              </div>
              <div>
                <label class="form-label">Province</label>
                <input value="Batangas" type="text" class="form-input" disabled />
              </div>
            </div>

            <div>
              <label class="form-label">Remarks / Notes</label>
              <textarea v-model="form.remarks" class="form-input" rows="3"
                placeholder="Additional notes about this beneficiary…"></textarea>
            </div>
          </div>
        </div>

        <!-- ── Step 3: Family Members ───────────────────────────────────── -->
        <div v-show="currentStep === 3" class="space-y-4">
          <div class="card">
            <div class="card-header">
              <div>
                <h3 class="font-semibold text-slate-800">Family Members</h3>
                <p class="text-xs text-slate-400">Add all members of the household (up to 20)</p>
              </div>
              <button type="button" @click="addMember" class="btn btn-primary btn-sm">
                <PlusIcon class="w-4 h-4" />
                Add Member
              </button>
            </div>
          </div>

          <div v-if="form.family_members.length === 0"
            class="card card-body text-center py-12 text-slate-400">
            <UsersIcon class="w-10 h-10 mx-auto mb-2 opacity-30" />
            <p>No family members added yet. Click "Add Member" to begin.</p>
          </div>

          <div v-for="(member, idx) in form.family_members" :key="idx" class="card">
            <div class="card-header">
              <h4 class="font-medium text-slate-700">Member {{ idx + 1 }}</h4>
              <button type="button" @click="removeMember(idx)"
                class="btn btn-ghost btn-sm text-danger-600">
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
            <div class="card-body space-y-4">
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div>
                  <label class="form-label">First Name <span class="text-danger-500">*</span></label>
                  <input v-model="member.first_name" type="text" class="form-input" placeholder="First name" required />
                </div>
                <div>
                  <label class="form-label">Last Name <span class="text-danger-500">*</span></label>
                  <input v-model="member.last_name" type="text" class="form-input" placeholder="Last name" required />
                </div>
                <div>
                  <label class="form-label">Middle Name</label>
                  <input v-model="member.middle_name" type="text" class="form-input" placeholder="Middle" />
                </div>
              </div>

              <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div>
                  <label class="form-label">Birthdate <span class="text-danger-500">*</span></label>
                  <input v-model="member.birthdate" type="date" class="form-input" required
                    @change="updateMemberFlags(member)" />
                </div>
                <div>
                  <label class="form-label">Sex</label>
                  <select v-model="member.sex" class="form-select">
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Relationship</label>
                  <select v-model="member.relationship" class="form-select">
                    <option value="spouse">Spouse</option>
                    <option value="child">Child</option>
                    <option value="parent">Parent</option>
                    <option value="sibling">Sibling</option>
                    <option value="grandchild">Grandchild</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div class="flex flex-col justify-end gap-1">
                  <span v-if="member._age !== null" class="text-xs text-slate-500">Age: {{ member._age }} yrs</span>
                  <span v-if="member._isSchoolAge" class="badge badge-info badge-sm">School-age (3–18)</span>
                  <span v-if="member._isUnderFive" class="badge badge-warning badge-sm">Under 5</span>
                </div>
              </div>

              <!-- Education fields (shown if school age) -->
              <div v-if="member._isSchoolAge" class="grid grid-cols-2 sm:grid-cols-3 gap-3 py-3 px-4 bg-brand-50 rounded-xl border border-brand-200">
                <div>
                  <label class="form-label text-xs">Education Level</label>
                  <select v-model="member.education_level" class="form-select text-sm">
                    <option value="daycare">Day Care</option>
                    <option value="preschool">Preschool</option>
                    <option value="elementary">Elementary</option>
                    <option value="junior_high">Junior High School</option>
                    <option value="senior_high">Senior High School</option>
                  </select>
                </div>
                <div>
                  <label class="form-label text-xs">School Name</label>
                  <input v-model="member.school_name" type="text" class="form-input text-sm" placeholder="School name" />
                </div>
                <div>
                  <label class="form-label text-xs">Grade / Year Level</label>
                  <input v-model="member.grade_level" type="text" class="form-input text-sm" placeholder="Grade 1" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Step 4: Review ───────────────────────────────────────────── -->
        <div v-show="currentStep === 4" class="space-y-4">
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Review & Confirm</h3>
              <p class="text-xs text-slate-400">Verify all information before generating the beneficiary ID card</p>
            </div>
            <div class="card-body space-y-5">

              <!-- Personal -->
              <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Personal Information</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-2 text-sm">
                  <div><span class="text-slate-400">Name:</span> <span class="font-medium text-slate-700">{{ fullName }}</span></div>
                  <div><span class="text-slate-400">Birthdate:</span> <span class="font-medium text-slate-700">{{ form.birthdate }}</span></div>
                  <div><span class="text-slate-400">Sex:</span> <span class="font-medium text-slate-700 capitalize">{{ form.sex }}</span></div>
                  <div><span class="text-slate-400">Civil Status:</span> <span class="font-medium text-slate-700 capitalize">{{ form.civil_status }}</span></div>
                  <div><span class="text-slate-400">Contact:</span> <span class="font-medium text-slate-700">{{ form.contact_number || '—' }}</span></div>
                </div>
              </div>

              <div class="border-t border-slate-100 pt-4">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Address</p>
                <p class="text-sm text-slate-700">
                  {{ [form.house_no, form.street, form.purok ? 'Purok ' + form.purok : ''].filter(Boolean).join(', ') }}
                  Brgy. {{ form.barangay }}, Lipa City, Batangas
                </p>
              </div>

              <div class="border-t border-slate-100 pt-4">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
                  Family Members ({{ form.family_members.length }})
                </p>
                <div class="space-y-2">
                  <div v-for="(m, i) in form.family_members" :key="i"
                    class="flex items-center gap-3 text-sm">
                    <span class="w-6 h-6 bg-slate-100 rounded-full flex items-center justify-center text-xs font-medium text-slate-500">{{ i + 1 }}</span>
                    <span class="font-medium text-slate-700">{{ m.first_name }} {{ m.last_name }}</span>
                    <span class="text-slate-400 capitalize">{{ m.relationship }}</span>
                    <span v-if="m._isSchoolAge" class="badge badge-info badge-sm">{{ m.education_level }}</span>
                  </div>
                  <p v-if="form.family_members.length === 0" class="text-slate-400 text-sm">None added.</p>
                </div>
              </div>

              <!-- What will be generated -->
              <div class="alert alert-success">
                <CheckBadgeIcon class="w-5 h-5 flex-shrink-0" />
                <div>
                  <p class="font-semibold text-sm">Upon submission, the system will:</p>
                  <ul class="list-disc ml-4 mt-1 text-xs space-y-1">
                    <li>Assign a unique ID (4PS-LPA-XXXXXX)</li>
                    <li>Generate a QR code for the physical card</li>
                    <li>Create the beneficiary portal account</li>
                    <li>Generate the printable ID card PDF with default credentials</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Navigation buttons -->
        <div class="flex items-center justify-between mt-6">
          <button
            v-if="currentStep > 1"
            type="button"
            @click="currentStep--"
            class="btn btn-secondary"
          >
            ← Previous
          </button>
          <div v-else></div>

          <button
            v-if="currentStep < steps.length"
            type="button"
            @click="nextStep"
            class="btn btn-primary"
          >
            Next →
          </button>

          <button
            v-else
            type="submit"
            :disabled="form.processing"
            class="btn btn-success btn-lg"
          >
            <CheckBadgeIcon class="w-5 h-5" />
            {{ form.processing ? 'Registering & Generating Card…' : 'Register & Generate Card' }}
          </button>
        </div>
      </form>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import {
  CheckIcon, InformationCircleIcon, UserIcon, PhotoIcon,
  PlusIcon, TrashIcon, UsersIcon, CheckBadgeIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({ offices: Array })

const currentStep = ref(1)
const photoPreview = ref(null)

const steps = [
  { id: 1, label: 'Personal Info' },
  { id: 2, label: 'Address' },
  { id: 3, label: 'Family Members' },
  { id: 4, label: 'Review & Submit' },
]

const form = useForm({
  listahanan_id:   '',
  first_name:      '',
  middle_name:     '',
  last_name:       '',
  suffix:          '',
  birthdate:       '',
  sex:             'female',
  civil_status:    'married',
  contact_number:  '',
  enrollment_date: '',
  house_no:        '',
  street:          '',
  purok:           '',
  barangay:        '',
  office_id:       '',
  remarks:         '',
  photo:           null,
  family_members:  [],
})

const errors = computed(() => form.errors)

const fullName = computed(() =>
  [form.first_name, form.middle_name, form.last_name, form.suffix].filter(Boolean).join(' ')
)

const handlePhoto = (e) => {
  const file = e.target.files[0]
  if (!file) return
  form.photo = file
  const reader = new FileReader()
  reader.onload = (ev) => { photoPreview.value = ev.target.result }
  reader.readAsDataURL(file)
}

const updateMemberFlags = (member) => {
  if (!member.birthdate) return
  const age = Math.floor((Date.now() - new Date(member.birthdate)) / (365.25 * 86400000))
  member._age = age
  member._isSchoolAge = age >= 3 && age <= 18
  member._isUnderFive = age <= 5
  if (member._isSchoolAge) {
    if (!member.education_level || member.education_level === 'not_applicable') {
      member.education_level = age <= 5 ? 'daycare' : age <= 12 ? 'elementary' : 'junior_high'
    }
  } else {
    member.education_level = 'not_applicable'
  }
}

const addMember = () => {
  if (form.family_members.length >= 20) return
  form.family_members.push({
    first_name: '', last_name: '', middle_name: '',
    birthdate: '', sex: 'female', relationship: 'child',
    education_level: 'not_applicable', school_name: '', grade_level: '',
    _age: null, _isSchoolAge: false, _isUnderFive: false,
  })
}

const removeMember = (i) => form.family_members.splice(i, 1)

const validateStep = () => {
  if (currentStep.value === 1) {
    if (!form.first_name || !form.last_name || !form.birthdate || !form.sex) {
      alert('Please fill in all required fields (First Name, Last Name, Birthdate, Sex).')
      return false
    }
  }
  if (currentStep.value === 2) {
    if (!form.barangay) {
      alert('Please select a barangay.')
      return false
    }
  }
  return true
}

const nextStep = () => {
  if (!validateStep()) return
  currentStep.value++
}

const submitForm = () => {
  // Strip internal _flags before sending
  const members = form.family_members.map(({ _age, _isSchoolAge, _isUnderFive, ...m }) => m)

  form.transform(data => ({ ...data, family_members: members }))
    .post(route('superadmin.beneficiaries.store'), {
      forceFormData: true,
      onSuccess: () => {
        currentStep.value = 1
      },
    })
}
</script>
