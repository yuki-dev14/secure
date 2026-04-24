<template>
  <Head title="Completion Verification" />
  <StaffLayout :page-title="`Verify: ${beneficiary.full_name ?? beneficiary.unique_id}`"
    :page-subtitle="`${beneficiary.unique_id} — Brgy. ${beneficiary.barangay ?? '—'}`">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

      <!-- LEFT: Beneficiary info + current completion status -->
      <div class="space-y-5">

        <!-- Profile mini-card -->
        <div class="card">
          <div class="card-body">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-14 h-14 rounded-xl overflow-hidden bg-slate-100 shrink-0">
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
                  {{ beneficiary.is_compliant ? '✓ Currently Complete' : '✗ Incomplete' }}
                </span>
              </div>
            </div>

            <!-- Household composition -->
            <div class="space-y-1.5 text-sm">
              <div class="flex justify-between">
                <span class="text-slate-400">Family Members</span>
                <span class="font-medium text-slate-700">{{ beneficiary.family_members?.length ?? 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">School-age (3–18 yrs)</span>
                <span :class="['font-medium', schoolAgeCount > 0 ? 'text-brand-700' : 'text-slate-400']">{{ schoolAgeCount }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">Children 0–5 yrs</span>
                <span :class="['font-medium', underFiveCount > 0 ? 'text-warning-700' : 'text-slate-400']">{{ underFiveCount }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">Children 0–23 months</span>
                <span :class="['font-medium', underTwoCount > 0 ? 'text-warning-700' : 'text-slate-400']">{{ underTwoCount }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">Children 24–72 months</span>
                <span :class="['font-medium', twoToSixCount > 0 ? 'text-warning-700' : 'text-slate-400']">{{ twoToSixCount }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">Pregnant Members</span>
                <span :class="['font-medium', pregnantCount > 0 ? 'text-pink-700' : 'text-slate-400']">{{ pregnantCount }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Latest completion record -->
        <div class="card" v-if="latestCompliance">
          <div class="card-header">
            <h3 class="font-semibold text-slate-800 text-sm">Latest Record</h3>
            <span class="badge badge-neutral badge-sm">{{ latestCompliance.period }}</span>
          </div>
          <div class="card-body space-y-2 text-sm">
            <div v-if="schoolAgeCount > 0" class="flex items-center justify-between">
              <span class="text-slate-500">Education (85% attendance)</span>
              <span :class="latestCompliance.edu_attendance_compliant === true ? 'text-success-600 font-medium' : latestCompliance.edu_attendance_compliant === false ? 'text-danger-600 font-medium' : 'text-slate-300'">
                {{ latestCompliance.edu_attendance_compliant === true ? '✓ Yes' : latestCompliance.edu_attendance_compliant === false ? '✗ No' : '—' }}
              </span>
            </div>
            <div v-if="underFiveCount > 0" class="flex items-center justify-between">
              <span class="text-slate-500">Child Immunized</span>
              <span :class="latestCompliance.health_immunization_complete === true ? 'text-success-600 font-medium' : latestCompliance.health_immunization_complete === false ? 'text-danger-600 font-medium' : 'text-slate-300'">
                {{ latestCompliance.health_immunization_complete === true ? '✓ Yes' : latestCompliance.health_immunization_complete === false ? '✗ No' : '—' }}
              </span>
            </div>
            <div v-if="underFiveCount > 0" class="flex items-center justify-between">
              <span class="text-slate-500">Child Weighed</span>
              <span :class="latestCompliance.health_weight_monitored === true ? 'text-success-600 font-medium' : latestCompliance.health_weight_monitored === false ? 'text-danger-600 font-medium' : 'text-slate-300'">
                {{ latestCompliance.health_weight_monitored === true ? '✓ Yes' : latestCompliance.health_weight_monitored === false ? '✗ No' : '—' }}
              </span>
            </div>
            <div v-if="pregnantCount > 0" class="flex items-center justify-between">
              <span class="text-slate-500">Pre/Post-natal Check-up</span>
              <span :class="latestCompliance.pregnancy_prenatal_compliant === true ? 'text-success-600 font-medium' : latestCompliance.pregnancy_prenatal_compliant === false ? 'text-danger-600 font-medium' : 'text-slate-300'">
                {{ latestCompliance.pregnancy_prenatal_compliant === true ? '✓ Yes' : latestCompliance.pregnancy_prenatal_compliant === false ? '✗ No' : '—' }}
              </span>
            </div>
            <div v-if="pregnantCount > 0" class="flex items-center justify-between">
              <span class="text-slate-500">Professional Delivery</span>
              <span :class="latestCompliance.pregnancy_professional_delivery === true ? 'text-success-600 font-medium' : latestCompliance.pregnancy_professional_delivery === false ? 'text-danger-600 font-medium' : 'text-slate-300'">
                {{ latestCompliance.pregnancy_professional_delivery === true ? '✓ Yes' : latestCompliance.pregnancy_professional_delivery === false ? '✗ No' : '—' }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-500">FDS Attendance</span>
              <span :class="latestCompliance.fds_attended === true ? 'text-success-600 font-medium' : latestCompliance.fds_attended === false ? 'text-danger-600 font-medium' : 'text-slate-300'">
                {{ latestCompliance.fds_attended === true ? '✓ Yes' : latestCompliance.fds_attended === false ? '✗ No' : '—' }}
              </span>
            </div>
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
              <span class="font-semibold text-slate-700">OVERALL</span>
              <span :class="latestCompliance.is_fully_compliant === true ? 'text-success-600 font-medium' : latestCompliance.is_fully_compliant === false ? 'text-danger-600 font-medium' : 'text-slate-300'">
                {{ latestCompliance.is_fully_compliant === true ? '✓ Yes' : latestCompliance.is_fully_compliant === false ? '✗ No' : '—' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Quarter reference -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-slate-800 text-sm">Quarter Reference</h3>
          </div>
          <div class="card-body space-y-1.5 text-xs text-slate-500">
            <div class="flex justify-between"><span class="font-medium text-slate-600">Q1</span><span>Jan 1 – Mar 31</span></div>
            <div class="flex justify-between"><span class="font-medium text-slate-600">Q2</span><span>Apr 1 – Jun 30</span></div>
            <div class="flex justify-between"><span class="font-medium text-slate-600">Q3</span><span>Jul 1 – Sep 30</span></div>
            <div class="flex justify-between"><span class="font-medium text-slate-600">Q4</span><span>Oct 1 – Dec 31</span></div>
          </div>
        </div>
      </div>

      <!-- RIGHT: Completion entry form -->
      <div class="lg:col-span-2 space-y-5">

        <!-- Quarter selector -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-slate-800">Record Completion</h3>
            <select v-model="selectedPeriod" class="form-select w-64">
              <option value="">Select Quarter...</option>
              <option v-for="p in periods" :key="p.value" :value="p">{{ p.label }}</option>
            </select>
          </div>
          <div v-if="selectedPeriod" class="px-5 pb-4">
            <div class="flex items-center gap-2 p-3 bg-brand-50 border border-brand-100 rounded-xl text-xs text-brand-700">
              <InformationCircleIcon class="w-4 h-4 shrink-0" />
              <span>Recording completion for <strong>{{ selectedPeriod.label }}</strong>. Only sections relevant to this household are shown. Toggle each condition based on the physical records received.</span>
            </div>
          </div>
        </div>

        <form v-if="selectedPeriod" @submit.prevent="submitCompliance" class="space-y-4">

          <!-- ── EDUCATION — one row per school-age child ── -->
          <div class="card" v-if="schoolAgeChildren.length > 0">
            <div class="card-header">
              <div class="flex items-center gap-2">
                <AcademicCapIcon class="w-5 h-5 text-brand-600" />
                <h3 class="font-semibold text-slate-800">Education</h3>
                <span class="badge badge-info badge-sm">{{ schoolAgeChildren.length }} school-age child{{ schoolAgeChildren.length > 1 ? 'ren' : '' }} (3–18 yrs)</span>
              </div>
              <span class="text-xs text-slate-400">Enrolled in daycare, preschool, elementary or high school</span>
            </div>
            <div class="card-body space-y-4">

              <!-- Per-child card -->
              <div
                v-for="child in schoolAgeChildren"
                :key="child.id"
                class="p-4 border border-slate-100 rounded-xl space-y-3 bg-slate-50/50"
              >
                <!-- Child header -->
                <div class="flex items-center justify-between">
                  <div>
                    <p class="font-medium text-slate-700 text-sm">{{ child.first_name }} {{ child.last_name }}</p>
                    <p class="text-xs text-slate-400">Age {{ getAge(child.birthdate) }}</p>
                  </div>
                  <span class="badge badge-sm badge-neutral capitalize">{{ (child.education_level ?? '').replace('_', ' ') }}</span>
                </div>

                <!-- Enrolled? -->
                <YesNoField
                  v-model="childEnrolled[child.id]"
                  label="Is this child enrolled in school?"
                  hint="Based on the school enrollment record / Form 138."
                />

                <!-- Attendance rate (only if enrolled) -->
                <div v-if="childEnrolled[child.id] === true">
                  <label class="form-label">
                    Class Attendance Rate (%) <span class="text-danger-500">*</span>
                  </label>
                  <p class="text-xs text-slate-400 mb-1.5">From the school report card. Must meet the 85% threshold.</p>
                  <div class="relative max-w-xs">
                    <input
                      v-model.number="childRates[child.id]"
                      type="number" min="0" max="100" step="0.1"
                      class="form-input pr-8" :placeholder="`e.g. 92.5`"
                    />
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">%</span>
                  </div>
                  <p v-if="childRates[child.id] !== null && childRates[child.id] !== undefined && childRates[child.id] !== ''"
                    class="text-xs mt-1.5 font-medium"
                    :class="childRates[child.id] >= 85 ? 'text-success-600' : 'text-danger-600'">
                    {{ childRates[child.id] >= 85 ? '✓ Meets the 85% attendance requirement' : '✗ Below the 85% threshold — will be marked incomplete' }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- ── CHILDREN 0–5 HEALTH ── -->
          <div class="card" v-if="underFiveCount > 0">
            <div class="card-header">
              <div class="flex items-center gap-2">
                <HeartIcon class="w-5 h-5 text-success-600" />
                <h3 class="font-semibold text-slate-800">Children 0–5 Health</h3>
                <span class="badge badge-warning badge-sm">{{ underFiveCount }} child{{ underFiveCount > 1 ? 'ren' : '' }} (0–5 yrs)</span>
              </div>
            </div>
            <div class="card-body space-y-5">

              <!-- Immunization -->
              <YesNoField
                v-model="compForm.health_immunization_complete"
                label="Are all children 0–5 years old fully immunized?"
                hint="Based on the immunization record / health card received."
              />

              <!-- Weighing — 0–23 months -->
              <div v-if="underTwoCount > 0">
                <YesNoField
                  v-model="compForm.health_weight_monitored_monthly"
                  :label="`Monthly weighing done for children 0–23 months? (${underTwoCount} child${underTwoCount > 1 ? 'ren' : ''})`"
                  hint="Children 0–23 months must be weighed every month."
                />
              </div>

              <!-- Weighing — 24–72 months -->
              <div v-if="twoToSixCount > 0">
                <YesNoField
                  v-model="compForm.health_weight_monitored_bimonthly"
                  :label="`Bimonthly weighing done for children 24–72 months? (${twoToSixCount} child${twoToSixCount > 1 ? 'ren' : ''})`"
                  hint="Children 24–72 months must be weighed every two months."
                />
              </div>

            </div>
          </div>

          <!-- ── PREGNANCY ── -->
          <div class="card" v-if="pregnantCount > 0">
            <div class="card-header">
              <div class="flex items-center gap-2">
                <HeartIcon class="w-5 h-5 text-pink-500" />
                <h3 class="font-semibold text-slate-800">Pregnancy Conditions</h3>
                <span class="badge badge-sm" style="background:#fce7f3;color:#be185d;">{{ pregnantCount }} pregnant member{{ pregnantCount > 1 ? 's' : '' }}</span>
              </div>
            </div>
            <div class="card-body space-y-5">
              <YesNoField
                v-model="compForm.pregnancy_prenatal_compliant"
                label="Pre-natal health check-up availed?"
                hint="Pregnant member must avail of pre-natal health check-up from a healthcare provider."
              />
              <YesNoField
                v-model="compForm.pregnancy_postnatal_compliant"
                label="Post-natal health check-up availed?"
                hint="Post-natal check-up must be done after delivery."
              />
              <YesNoField
                v-model="compForm.pregnancy_professional_delivery"
                label="Child delivery attended by a professional healthcare provider?"
                hint="Delivery must be assisted by a licensed midwife, nurse, or doctor."
              />
            </div>
          </div>

          <!-- ── FDS — always shown ── -->
          <div class="card">
            <div class="card-header">
              <div class="flex items-center gap-2">
                <ClipboardDocumentListIcon class="w-5 h-5 text-warning-600" />
                <h3 class="font-semibold text-slate-800">Family Development Session (FDS)</h3>
              </div>
              <span class="text-xs text-slate-400">Required for all parent beneficiaries</span>
            </div>
            <div class="card-body space-y-4">
              <YesNoField
                v-model="compForm.fds_attended"
                label="Did the parent beneficiary attend the FDS this quarter?"
                hint="Based on the FDS attendance sheet or certificate received."
              />
              <div class="grid grid-cols-2 gap-4" v-if="compForm.fds_attended === true">
                <div>
                  <label class="form-label">Date of FDS</label>
                  <input v-model="compForm.fds_date" type="date" class="form-input" />
                </div>
                <div>
                  <label class="form-label">Venue</label>
                  <input v-model="compForm.fds_venue" type="text" class="form-input" placeholder="Barangay Hall" />
                </div>
              </div>
            </div>
          </div>

          <!-- ── OVERALL ASSESSMENT ── -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Overall Assessment</h3>
            </div>
            <div class="card-body space-y-4">

              <!-- Auto-computed status -->
              <div class="p-4 rounded-xl border-2 flex items-center justify-between"
                :class="autoComplete ? 'border-success-300 bg-success-50' : 'border-danger-300 bg-danger-50'">
                <div>
                  <p class="font-semibold" :class="autoComplete ? 'text-success-700' : 'text-danger-700'">
                    {{ autoComplete ? '✓ This household is COMPLETE' : '✗ This household is INCOMPLETE' }}
                  </p>
                  <p class="text-xs mt-0.5" :class="autoComplete ? 'text-success-500' : 'text-danger-500'">
                    Auto-computed based on the conditions above
                  </p>
                </div>
                <button type="button" @click="overrideCompliant = !overrideCompliant" class="btn btn-ghost btn-sm">
                  {{ overrideCompliant ? 'Cancel Override' : 'Override' }}
                </button>
              </div>

              <!-- Override toggle -->
              <div v-if="overrideCompliant" class="p-4 rounded-xl border border-warning-300 bg-warning-50 space-y-3">
                <p class="text-sm font-medium text-warning-700">Manual Override</p>
                <div class="flex gap-4">
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" v-model="manualCompliant" :value="true" class="text-success-600" />
                    <span class="text-sm font-medium text-success-700">Mark as COMPLETE</span>
                  </label>
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" v-model="manualCompliant" :value="false" class="text-danger-600" />
                    <span class="text-sm font-medium text-danger-700">Mark as INCOMPLETE</span>
                  </label>
                </div>
              </div>

              <!-- Non-completion reason -->
              <div v-if="!finalComplete">
                <label class="form-label">Non-Completion Reasons <span class="text-danger-500">*</span></label>
                <textarea v-model="compForm.non_compliance_reasons" class="form-input" rows="2"
                  placeholder="Reason for non-completion..."></textarea>
              </div>

              <!-- Remarks -->
              <div>
                <label class="form-label">Verifier Remarks / Notes</label>
                <textarea v-model="compForm.remarks" class="form-input" rows="2"
                  placeholder="Optional notes from the verifier..."></textarea>
              </div>

              <button type="submit" :disabled="compForm.processing" class="btn btn-primary w-full">
                <CheckBadgeIcon class="w-5 h-5" />
                {{ compForm.processing ? 'Saving...' : `Save Completion Record — ${selectedPeriod.label}` }}
              </button>
            </div>
          </div>

        </form>

        <div v-else class="card card-body text-center py-16 text-slate-400">
          <ClipboardDocumentCheckIcon class="w-12 h-12 mx-auto mb-3 opacity-30" />
          <p class="font-medium">Select a quarter to begin recording completion.</p>
          <p class="text-sm mt-1">Completion records are checked every quarter.</p>
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
  InformationCircleIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import YesNoField from '@/Components/YesNoField.vue'

const props = defineProps({
  beneficiary:      Object,
  periods:          Array,
  latestCompliance: Object,
})

const selectedPeriod    = ref(null)
const overrideCompliant = ref(false)
const manualCompliant   = ref(null)

// ── Household composition ──────────────────────────────────────────────────
const members = computed(() => props.beneficiary.family_members ?? [])

const schoolAgeCount = computed(() => members.value.filter(m => m.is_school_age).length)
const underFiveCount = computed(() => members.value.filter(m => m.is_under_five).length)
const pregnantCount  = computed(() => members.value.filter(m => m.is_pregnant).length)

// 0–23 months (under_five AND age < 2 years — derived from birthdate)
const underTwoCount = computed(() =>
  members.value.filter(m => {
    if (!m.birthdate) return false
    const ageMonths = Math.floor((Date.now() - new Date(m.birthdate)) / (30.4375 * 86400000))
    return ageMonths >= 0 && ageMonths <= 23
  }).length
)

// 24–72 months
const twoToSixCount = computed(() =>
  members.value.filter(m => {
    if (!m.birthdate) return false
    const ageMonths = Math.floor((Date.now() - new Date(m.birthdate)) / (30.4375 * 86400000))
    return ageMonths >= 24 && ageMonths <= 72
  }).length
)

// ── Form ───────────────────────────────────────────────────────────────────
const compForm = useForm({
  family_member_id:                null,
  period:                          '',
  period_start:                    '',
  period_end:                      '',
  is_fully_compliant:              null,

  // Education
  edu_enrolled:                    null,
  edu_attendance_rate:             null,
  edu_attendance_compliant:        null,

  // Children 0–5 health
  health_immunization_complete:    null,
  health_weight_monitored:         null,        // combined flag sent to backend
  health_weight_monitored_monthly:    null,     // frontend-only split field
  health_weight_monitored_bimonthly:  null,     // frontend-only split field
  health_last_checkup:             null,
  health_compliant:                null,

  // Pregnancy
  pregnancy_prenatal_compliant:    null,
  pregnancy_postnatal_compliant:   null,
  pregnancy_professional_delivery: null,
  pregnancy_compliant:             null,

  // FDS
  fds_attended:                    null,
  fds_date:                        null,
  fds_venue:                       '',
  fds_compliant:                   null,

  remarks:                         '',
  non_compliance_reasons:          '',
})

// ── Per-child education state ──────────────────────────────────────────────
const schoolAgeChildren = computed(() =>
  members.value.filter(m => m.is_school_age &&
    ['daycare','preschool','elementary','junior_high','senior_high'].includes(m.education_level)
  )
)

const childEnrolled = ref({})   // { [memberId]: true|false|null }
const childRates    = ref({})   // { [memberId]: number|null }

const getAge = (birthdate) => {
  if (!birthdate) return '?'
  return Math.floor((Date.now() - new Date(birthdate)) / (365.25 * 86400000))
}

// ── Attendance auto-check (kept for backward compat) ──────────────────────
const autoCheckAttendance = () => {
  if (compForm.edu_attendance_rate !== null && compForm.edu_attendance_rate !== '') {
    compForm.edu_attendance_compliant = Number(compForm.edu_attendance_rate) >= 85
  }
}

// ── Auto-compute overall completion ───────────────────────────────────────
// Each section only counts if it's applicable to the household.
// null = not yet answered → treated as incomplete to force an explicit decision.
const autoComplete = computed(() => {
  // Education: ALL enrolled school-age children must meet 85%
  const eduOk = schoolAgeChildren.value.length === 0 || schoolAgeChildren.value.every(child => {
    if (childEnrolled.value[child.id] !== true) return false
    const rate = childRates.value[child.id]
    // daycare/preschool: no attendance threshold
    if (['daycare','preschool'].includes(child.education_level)) return true
    return rate !== null && rate !== undefined && Number(rate) >= 85
  })

  // Health 0–5: required if under-five children exist
  const immunizationOk = underFiveCount.value === 0 || compForm.health_immunization_complete === true
  const monthlyOk      = underTwoCount.value === 0   || compForm.health_weight_monitored_monthly === true
  const bimonthlyOk    = twoToSixCount.value === 0   || compForm.health_weight_monitored_bimonthly === true
  const healthOk       = immunizationOk && monthlyOk && bimonthlyOk

  // Pregnancy: required if pregnant members exist
  const pregnancyOk = pregnantCount.value === 0
    || (compForm.pregnancy_prenatal_compliant === true
        && compForm.pregnancy_postnatal_compliant === true
        && compForm.pregnancy_professional_delivery === true)

  // FDS: always required — must be explicitly Yes
  const fdsOk = compForm.fds_attended === true

  return eduOk && healthOk && pregnancyOk && fdsOk
})

// Final result respects manual override
const finalComplete = computed(() =>
  overrideCompliant.value ? (manualCompliant.value === true) : autoComplete.value
)

// ── Submit ─────────────────────────────────────────────────────────────────
const submitCompliance = () => {
  compForm.period       = selectedPeriod.value?.value ?? ''
  compForm.period_start = selectedPeriod.value?.start ?? ''
  compForm.period_end   = selectedPeriod.value?.end   ?? ''

  // Build per-child data for the backend
  const childAttendances = schoolAgeChildren.value.map(child => ({
    id:       child.id,
    enrolled: childEnrolled.value[child.id] ?? false,
    rate:     childEnrolled.value[child.id] === true ? (childRates.value[child.id] ?? null) : null,
  }))

  // Aggregate edu fields for the compliance_record row
  compForm.edu_enrolled = childAttendances.some(c => c.enrolled)
  const allEduOk = schoolAgeChildren.value.length === 0 || schoolAgeChildren.value.every(child => {
    if (childEnrolled.value[child.id] !== true) return false
    if (['daycare','preschool'].includes(child.education_level)) return true
    const rate = childRates.value[child.id]
    return rate !== null && Number(rate) >= 85
  })
  compForm.edu_attendance_compliant = allEduOk
  // Use the lowest rate as the representative for the compliance record
  const rates = childAttendances.filter(c => c.rate !== null).map(c => Number(c.rate))
  compForm.edu_attendance_rate = rates.length ? Math.min(...rates) : null

  // Combine split weighing fields into the single backend column
  if (underFiveCount.value > 0) {
    const monthlyOk    = underTwoCount.value === 0   || compForm.health_weight_monitored_monthly === true
    const bimonthlyOk  = twoToSixCount.value === 0   || compForm.health_weight_monitored_bimonthly === true
    compForm.health_weight_monitored = monthlyOk && bimonthlyOk
  }

  // Sync derived fields
  compForm.health_compliant    = underFiveCount.value > 0
    ? (compForm.health_immunization_complete === true && compForm.health_weight_monitored === true)
    : null

  compForm.fds_compliant = compForm.fds_attended

  compForm.pregnancy_compliant = pregnantCount.value > 0
    ? (compForm.pregnancy_prenatal_compliant === true
        && compForm.pregnancy_postnatal_compliant === true
        && compForm.pregnancy_professional_delivery === true)
    : null

  compForm.is_fully_compliant = finalComplete.value

  compForm
    .transform(data => {
      // Strip frontend-only split fields before sending
      const { health_weight_monitored_monthly, health_weight_monitored_bimonthly, ...rest } = data
      const out = {}
      for (const [k, v] of Object.entries(rest)) {
        if (v === true)       out[k] = 1
        else if (v === false) out[k] = 0
        else                  out[k] = v
      }
      // Attach per-child attendance array (not in compForm fields, passed separately)
      out.child_attendances = childAttendances
      return out
    })
    .post(route('verifier.compliance.store', props.beneficiary.id), {
      onSuccess: () => {
        compForm.reset()
        selectedPeriod.value    = null
        overrideCompliant.value = false
        manualCompliant.value   = null
      },
    })
}

</script>
