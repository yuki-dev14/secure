<template>
  <Head title="Flag Non-Compliance" />
  <StaffLayout page-title="Flag Non-Compliance" page-subtitle="Record a new non-compliance entry reported by School Representative or Midwife">
    <div class="max-w-3xl mx-auto space-y-6">

      <!-- Period & Source selector -->
      <div class="card p-6 space-y-5">
        <h2 class="text-lg font-bold text-slate-700 flex items-center gap-2">
          <ExclamationTriangleIcon class="w-5 h-5 text-amber-500" />
          Report Details
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Period <span class="text-red-500">*</span></label>
            <select v-model="form.period" class="input w-full">
              <option value="">Select period...</option>
              <option v-for="p in periods" :key="p.value" :value="p.value">{{ p.label }}</option>
            </select>
          </div>

          <div>
            <label class="label">Category <span class="text-red-500">*</span></label>
            <div class="flex gap-3 mt-1">
              <label class="flex-1 cursor-pointer">
                <input type="radio" v-model="form.category" value="education" class="sr-only peer" />
                <div class="p-3 rounded-xl border-2 border-slate-200 peer-checked:border-violet-500 peer-checked:bg-violet-50 text-center transition-all">
                  <AcademicCapIcon class="w-6 h-6 mx-auto text-violet-500 mb-1" />
                  <span class="text-sm font-medium text-slate-700">Education</span>
                </div>
              </label>
              <label class="flex-1 cursor-pointer">
                <input type="radio" v-model="form.category" value="health" class="sr-only peer" />
                <div class="p-3 rounded-xl border-2 border-slate-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 text-center transition-all">
                  <HeartIcon class="w-6 h-6 mx-auto text-emerald-500 mb-1" />
                  <span class="text-sm font-medium text-slate-700">Health</span>
                </div>
              </label>
            </div>
          </div>

          <div>
            <label class="label">Source <span class="text-red-500">*</span></label>
            <select v-model="form.source" class="input w-full">
              <option value="">Select source...</option>
              <option value="school_rep">School Representative</option>
              <option value="midwife">Midwife</option>
            </select>
          </div>

          <div>
            <label class="label">Reporter Name</label>
            <input v-model="form.reporter_name" type="text" class="input w-full" placeholder="e.g. Maria Santos" />
          </div>

          <div class="md:col-span-2">
            <label class="label">Reporter Institution</label>
            <input v-model="form.reporter_institution" type="text" class="input w-full" placeholder="e.g. Anilao Elementary School / Lipa City Health Center" />
          </div>
        </div>
      </div>

      <!-- Beneficiary selector -->
      <div class="card p-6 space-y-5">
        <h2 class="text-lg font-bold text-slate-700 flex items-center gap-2">
          <UsersIcon class="w-5 h-5 text-brand-500" />
          Select Beneficiary
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Filter by Barangay</label>
            <select v-model="selectedBarangay" class="input w-full">
              <option value="">All Barangays</option>
              <option v-for="b in barangays" :key="b" :value="b">{{ b }}</option>
            </select>
          </div>

          <div>
            <label class="label">Search Beneficiary</label>
            <input v-model="searchQuery" type="text" class="input w-full" placeholder="Type name or ID..." />
          </div>
        </div>

        <!-- Beneficiary list -->
        <div class="border border-slate-200 rounded-xl max-h-64 overflow-y-auto divide-y divide-slate-50">
          <div v-if="filteredBeneficiaries.length === 0" class="p-6 text-center text-sm text-slate-400">
            No beneficiaries found. Try adjusting filters.
          </div>
          <label v-for="b in filteredBeneficiaries" :key="b.id"
                 class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-brand-25 transition-colors"
                 :class="form.beneficiary_id === b.id ? 'bg-brand-50' : ''">
            <input type="radio" :value="b.id" v-model="form.beneficiary_id" class="rounded-full border-slate-300 text-brand-600" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-slate-700">{{ b.full_name }}</p>
              <p class="text-xs text-slate-400">{{ b.unique_id }} · {{ b.barangay }}</p>
            </div>
          </label>
        </div>

        <!-- Family member selector (if applicable) -->
        <div v-if="selectedBeneficiary?.family_members?.length" class="mt-4">
          <label class="label">Family Member (optional — for child-specific non-compliance)</label>
          <select v-model="form.family_member_id" class="input w-full">
            <option :value="null">Household-level (no specific member)</option>
            <option v-for="fm in selectedBeneficiary.family_members" :key="fm.id" :value="fm.id">
              {{ fm.full_name }} — {{ fm.relationship }} (age {{ fm.age }}) · {{ fm.education_level || 'N/A' }}
            </option>
          </select>
        </div>
      </div>

      <!-- Non-compliance details -->
      <div class="card p-6 space-y-5">
        <h2 class="text-lg font-bold text-slate-700 flex items-center gap-2">
          <ClipboardDocumentListIcon class="w-5 h-5 text-red-500" />
          Non-Compliance Details
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Reason <span class="text-red-500">*</span></label>
            <select v-model="form.reason" class="input w-full">
              <option value="">Select reason...</option>
              <optgroup v-if="form.category === 'education'" label="Education">
                <option value="Attendance below 85%">Attendance below 85%</option>
                <option value="Not enrolled in school">Not enrolled in school</option>
                <option value="Dropped out of school">Dropped out of school</option>
                <option value="Excessive absences">Excessive absences</option>
              </optgroup>
              <optgroup v-if="form.category === 'health'" label="Health">
                <option value="Missed immunization schedule">Missed immunization schedule</option>
                <option value="Missed growth monitoring checkup">Missed growth monitoring checkup</option>
                <option value="Missed prenatal checkup">Missed prenatal checkup</option>
                <option value="Missed postnatal checkup">Missed postnatal checkup</option>
                <option value="Missed deworming">Missed deworming (ages 6-14)</option>
                <option value="No professional delivery">No professional delivery</option>
              </optgroup>
            </select>
          </div>

          <div>
            <label class="label">Grant Affected <span class="text-red-500">*</span></label>
            <select v-model="form.grant_affected" class="input w-full">
              <option value="">Select grant...</option>
              <optgroup v-if="form.category === 'education'" label="Education Grants">
                <option value="education_elementary">Elementary (₱300/mo)</option>
                <option value="education_junior_high">Junior High (₱500/mo)</option>
                <option value="education_senior_high">Senior High (₱700/mo)</option>
              </optgroup>
              <optgroup v-if="form.category === 'health'" label="Health Grants">
                <option value="health_grant">Health Grant (₱750/mo)</option>
              </optgroup>
              <option value="rice_subsidy">Rice Subsidy (₱600/mo)</option>
            </select>
          </div>

          <div class="md:col-span-2">
            <label class="label">Additional Details</label>
            <textarea v-model="form.details" class="input w-full h-24 resize-none"
                      placeholder="Additional context, dates, notes..."></textarea>
          </div>
        </div>
      </div>

      <!-- Submit -->
      <div class="flex items-center justify-between">
        <Link :href="route('adminswa.non-compliance.index')" class="btn btn-secondary">
          ← Back to List
        </Link>
        <button @click="submit" :disabled="!isValid || form.processing"
                class="btn btn-primary gap-2">
          <ExclamationTriangleIcon class="w-4 h-4" />
          Submit Non-Compliance Record
        </button>
      </div>

    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import {
  ExclamationTriangleIcon, UsersIcon, AcademicCapIcon, HeartIcon,
  ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  beneficiaries: Array,
  barangays:     Array,
  periods:       Array,
})

const form = useForm({
  beneficiary_id:       null,
  family_member_id:     null,
  category:             '',
  source:               '',
  reporter_name:        '',
  reporter_institution: '',
  reason:               '',
  details:              '',
  period:               '',
  grant_affected:       '',
})

const selectedBarangay = ref('')
const searchQuery      = ref('')

const filteredBeneficiaries = computed(() => {
  let list = props.beneficiaries ?? []
  if (selectedBarangay.value) {
    list = list.filter(b => b.barangay === selectedBarangay.value)
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(b =>
      b.full_name.toLowerCase().includes(q) ||
      b.unique_id.toLowerCase().includes(q)
    )
  }
  return list.slice(0, 50) // Limit for performance
})

const selectedBeneficiary = computed(() =>
  props.beneficiaries?.find(b => b.id === form.beneficiary_id)
)

const isValid = computed(() =>
  form.beneficiary_id && form.category && form.source &&
  form.reason && form.period && form.grant_affected
)

const submit = () => {
  form.post(route('adminswa.non-compliance.store'))
}
</script>
