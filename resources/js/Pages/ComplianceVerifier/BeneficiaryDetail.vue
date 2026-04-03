<template>
  <Head title="Compliance Verification" />
  <StaffLayout :page-title="`Verify: ${beneficiary.full_name ?? beneficiary.unique_id}`"
    :page-subtitle="`${beneficiary.unique_id} — Brgy. ${beneficiary.barangay ?? '—'}`">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

      <!-- LEFT: Beneficiary info + current compliance -->
      <div class="space-y-5">

        <!-- Profile mini-card -->
        <div class="card">
          <div class="card-body">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-14 h-14 rounded-xl overflow-hidden bg-slate-100 flex-shrink-0">
                <img v-if="beneficiary.photo_path"
                  :src="`/storage/${beneficiary.photo_path}`"
                  class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <UserIcon class="w-7 h-7 text-slate-300" />
                </div>
              </div>
              <div>
                <p class="font-semibold text-slate-800">{{ beneficiary.full_name }}</p>
                <p class="text-xs text-slate-500 font-mono">{{ beneficiary.unique_id }}</p>
                <span :class="['badge badge-sm mt-1', beneficiary.is_compliant ? 'badge-success' : 'badge-danger']">
                  {{ beneficiary.is_compliant ? '✓ Currently Compliant' : '✗ Non-Compliant' }}
                </span>
              </div>
            </div>

            <div class="space-y-1.5 text-sm">
              <div class="flex justify-between">
                <span class="text-slate-400">Family Members</span>
                <span class="font-medium text-slate-700">{{ beneficiary.family_members?.length ?? 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">School-age Children</span>
                <span class="font-medium text-slate-700">{{ schoolAgeCount }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">Children 0–5</span>
                <span class="font-medium text-slate-700">{{ underFiveCount }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Latest compliance -->
        <div class="card" v-if="latestCompliance">
          <div class="card-header">
            <h3 class="font-semibold text-slate-800 text-sm">Latest Record</h3>
            <span class="badge badge-neutral badge-sm">{{ latestCompliance.period }}</span>
          </div>
          <div class="card-body space-y-2 text-sm">
            <ComplianceStatusRow label="Education (85% attendance)" :value="latestCompliance.edu_attendance_compliant" />
            <ComplianceStatusRow label="Health Check-up" :value="latestCompliance.health_compliant" />
            <ComplianceStatusRow label="Immunization" :value="latestCompliance.health_immunization_complete" />
            <ComplianceStatusRow label="FDS Attendance" :value="latestCompliance.fds_compliant" />
            <div class="pt-2 border-t border-slate-100">
              <ComplianceStatusRow label="OVERALL" :value="latestCompliance.is_fully_compliant" bold />
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT: New compliance entry form -->
      <div class="lg:col-span-2 space-y-5">

        <!-- Period selector -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-slate-800">Record Compliance</h3>
            <select v-model="selectedPeriod" class="form-select w-56">
              <option value="">Select Period…</option>
              <option v-for="p in periods" :key="p.value" :value="p">{{ p.label }}</option>
            </select>
          </div>
        </div>

        <form v-if="selectedPeriod" @submit.prevent="submitCompliance" class="space-y-4">

          <!-- Education section -->
          <div class="card" v-if="schoolAgeCount > 0">
            <div class="card-header">
              <div class="flex items-center gap-2">
                <AcademicCapIcon class="w-5 h-5 text-brand-600" />
                <h3 class="font-semibold text-slate-800">Education Compliance</h3>
              </div>
              <span class="text-xs text-slate-400">Requirement: ≥85% attendance</span>
            </div>
            <div class="card-body space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="form-label">Attendance Rate (%)</label>
                  <div class="relative">
                    <input v-model.number="compForm.edu_attendance_rate"
                      type="number" min="0" max="100" step="0.1"
                      class="form-input pr-8" placeholder="e.g. 92.5"
                      @input="autoCheckAttendance"
                    />
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">%</span>
                  </div>
                  <p v-if="compForm.edu_attendance_rate !== null" class="text-xs mt-1"
                    :class="compForm.edu_attendance_rate >= 85 ? 'text-success-600' : 'text-danger-600'">
                    {{ compForm.edu_attendance_rate >= 85 ? '✓ Meets 85% requirement' : '✗ Below 85% threshold' }}
                  </p>
                </div>
                <div>
                  <label class="form-label">Enrolled in School?</label>
                  <div class="flex gap-3 mt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input v-model="compForm.edu_enrolled" type="radio" :value="true" class="text-brand-600" />
                      <span class="text-sm">Yes</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input v-model="compForm.edu_enrolled" type="radio" :value="false" class="text-brand-600" />
                      <span class="text-sm">No</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Health section -->
          <div class="card" v-if="underFiveCount > 0 || true">
            <div class="card-header">
              <div class="flex items-center gap-2">
                <HeartIcon class="w-5 h-5 text-success-600" />
                <h3 class="font-semibold text-slate-800">Health Compliance</h3>
              </div>
            </div>
            <div class="card-body space-y-4">
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <ToggleField v-model="compForm.health_immunization_complete" label="Immunization Complete?" />
                <ToggleField v-model="compForm.health_weight_monitored" label="Weight Monitored?" />
                <div>
                  <label class="form-label">Last Health Check-up</label>
                  <input v-model="compForm.health_last_checkup" type="date" class="form-input" />
                </div>
              </div>

              <!-- Auto-compute health compliance -->
              <div class="flex items-center gap-3">
                <ToggleField v-model="compForm.health_compliant" label="Mark as Health Compliant?" />
              </div>
            </div>
          </div>

          <!-- FDS section -->
          <div class="card">
            <div class="card-header">
              <div class="flex items-center gap-2">
                <ClipboardDocumentListIcon class="w-5 h-5 text-warning-600" />
                <h3 class="font-semibold text-slate-800">Family Development Session (FDS)</h3>
              </div>
            </div>
            <div class="card-body space-y-4">
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <ToggleField v-model="compForm.fds_attended" label="FDS Attended?" />
                <div>
                  <label class="form-label">Date of FDS</label>
                  <input v-model="compForm.fds_date" type="date" class="form-input" />
                </div>
                <div>
                  <label class="form-label">Venue</label>
                  <input v-model="compForm.fds_venue" type="text" class="form-input" placeholder="Barangay Hall" />
                </div>
              </div>
              <ToggleField v-model="compForm.fds_compliant" label="Mark FDS Compliant?" />
            </div>
          </div>

          <!-- Overall assessment -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Overall Assessment</h3>
            </div>
            <div class="card-body space-y-4">
              <div class="p-4 rounded-xl border-2 flex items-center justify-between"
                :class="autoCompliant ? 'border-success-300 bg-success-50' : 'border-danger-300 bg-danger-50'">
                <div>
                  <p class="font-semibold" :class="autoCompliant ? 'text-success-700' : 'text-danger-700'">
                    {{ autoCompliant ? '✓ This household is COMPLIANT' : '✗ This household is NON-COMPLIANT' }}
                  </p>
                  <p class="text-xs mt-0.5" :class="autoCompliant ? 'text-success-500' : 'text-danger-500'">
                    Based on the conditions above
                  </p>
                </div>
                <button type="button" @click="overrideCompliant = !overrideCompliant" class="btn btn-ghost btn-sm">
                  Override
                </button>
              </div>

              <div v-if="!autoCompliant || overrideCompliant">
                <label class="form-label">Non-Compliance Reasons</label>
                <textarea v-model="compForm.non_compliance_reasons" class="form-input" rows="2"
                  placeholder="Reason for non-compliance…"></textarea>
              </div>

              <div>
                <label class="form-label">Remarks / Notes</label>
                <textarea v-model="compForm.remarks" class="form-input" rows="2"
                  placeholder="Verifier notes…"></textarea>
              </div>

              <button type="submit" :disabled="compForm.processing" class="btn btn-primary w-full">
                <CheckBadgeIcon class="w-5 h-5" />
                {{ compForm.processing ? 'Saving…' : `Save Compliance Record — ${selectedPeriod.label}` }}
              </button>
            </div>
          </div>

        </form>

        <div v-else class="card card-body text-center py-16 text-slate-400">
          <ClipboardDocumentCheckIcon class="w-12 h-12 mx-auto mb-3 opacity-30" />
          <p>Select a compliance period to begin recording.</p>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import {
  UserIcon, AcademicCapIcon, HeartIcon,
  ClipboardDocumentListIcon, ClipboardDocumentCheckIcon, CheckBadgeIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  beneficiary:     Object,
  periods:         Array,
  latestCompliance: Object,
})

const selectedPeriod   = ref(null)
const overrideCompliant = ref(false)

const schoolAgeCount = computed(() =>
  props.beneficiary.family_members?.filter(m => m.is_school_age).length ?? 0
)
const underFiveCount = computed(() =>
  props.beneficiary.family_members?.filter(m => m.is_under_five).length ?? 0
)

const compForm = useForm({
  family_member_id:              null,
  period:                        '',
  period_start:                  '',
  period_end:                    '',
  edu_enrolled:                  null,
  edu_attendance_rate:           null,
  edu_attendance_compliant:      null,
  health_immunization_complete:  null,
  health_weight_monitored:       null,
  health_last_checkup:           null,
  health_compliant:              null,
  fds_attended:                  null,
  fds_date:                      null,
  fds_venue:                     '',
  fds_compliant:                 null,
  remarks:                       '',
  non_compliance_reasons:        '',
})

const autoCheckAttendance = () => {
  if (compForm.edu_attendance_rate !== null) {
    compForm.edu_attendance_compliant = compForm.edu_attendance_rate >= 85
  }
}

const autoCompliant = computed(() => {
  const eduOk = schoolAgeCount.value === 0 || compForm.edu_attendance_compliant === true
  const healthOk = compForm.health_compliant !== false
  const fdsOk = compForm.fds_compliant !== false
  return eduOk && healthOk && fdsOk
})

const submitCompliance = () => {
  compForm.period      = selectedPeriod.value?.value ?? ''
  compForm.period_start = selectedPeriod.value?.start ?? ''
  compForm.period_end   = selectedPeriod.value?.end   ?? ''

  compForm.post(route('verifier.compliance.store', props.beneficiary.id), {
    onSuccess: () => {
      compForm.reset()
      selectedPeriod.value = null
    },
  })
}

// Inline subcomponents
const ComplianceStatusRow = {
  props: ['label', 'value', 'bold'],
  template: `
    <div class="flex items-center justify-between">
      <span :class="bold ? 'font-semibold text-slate-700' : 'text-slate-500'">{{ label }}</span>
      <span :class="value === true ? 'text-success-600 font-medium' : value === false ? 'text-danger-600 font-medium' : 'text-slate-300'">
        {{ value === true ? '✓ Yes' : value === false ? '✗ No' : '—' }}
      </span>
    </div>
  `
}

const ToggleField = {
  props: ['modelValue', 'label'],
  emits: ['update:modelValue'],
  template: `
    <div>
      <label class="form-label">{{ label }}</label>
      <div class="flex gap-3 mt-1.5">
        <label class="flex items-center gap-1.5 cursor-pointer">
          <input type="radio" :checked="modelValue === true" @change="$emit('update:modelValue', true)"
            class="text-brand-600" />
          <span class="text-sm text-success-600 font-medium">Yes</span>
        </label>
        <label class="flex items-center gap-1.5 cursor-pointer">
          <input type="radio" :checked="modelValue === false" @change="$emit('update:modelValue', false)"
            class="text-brand-600" />
          <span class="text-sm text-danger-600 font-medium">No</span>
        </label>
        <label class="flex items-center gap-1.5 cursor-pointer">
          <input type="radio" :checked="modelValue === null" @change="$emit('update:modelValue', null)"
            class="text-brand-600" />
          <span class="text-sm text-slate-400">N/A</span>
        </label>
      </div>
    </div>
  `
}
</script>
