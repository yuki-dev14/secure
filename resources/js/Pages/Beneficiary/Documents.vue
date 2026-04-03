<template>
  <Head title="My Documents" />
  <BeneficiaryLayout :unread-count="unread_count ?? 0">
    <div class="space-y-5">

      <!-- Header + info alert -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-5 shadow-lg border border-white/50">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
              <DocumentTextIcon class="w-5 h-5 text-brand-600" />
              My Documentary Requirements
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
              Track the status of all documents submitted to DSWD.
            </p>
          </div>
          <div class="flex items-center gap-3 text-sm">
            <span class="badge badge-success">{{ verifiedCount }} Verified</span>
            <span class="badge badge-warning">{{ pendingCount }} Pending</span>
            <span class="badge badge-danger">{{ rejectedCount }} Rejected</span>
          </div>
        </div>
      </div>

      <!-- Completion Progress -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-5 shadow-lg border border-white/50">
        <div class="flex items-center justify-between mb-2">
          <p class="text-sm font-semibold text-slate-700">Overall Submission Progress</p>
          <p class="text-sm font-bold text-brand-600">{{ progressPercent }}%</p>
        </div>
        <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-700"
            :style="`width: ${progressPercent}%; background: linear-gradient(90deg, #4f46e5, #7c3aed)`"
          ></div>
        </div>
        <div class="flex items-center justify-between mt-2 text-xs text-slate-400">
          <span>{{ verifiedCount }} of {{ totalRequired }} required documents verified</span>
          <span v-if="progressPercent === 100" class="text-success-600 font-medium">✓ Complete</span>
        </div>
      </div>

      <!-- Document type groups -->
      <div v-for="group in documentGroups" :key="group.type" class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
          <div :class="['w-8 h-8 rounded-lg flex items-center justify-center', group.iconBg]">
            <component :is="group.icon" class="w-4 h-4" :class="group.iconColor" />
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-slate-800 text-sm">{{ group.label }}</h3>
            <p class="text-xs text-slate-400">{{ group.description }}</p>
          </div>
          <span v-if="group.required" class="text-xs text-danger-500 font-medium">Required</span>
        </div>

        <!-- Documents in group -->
        <div v-if="group.docs.length" class="divide-y divide-slate-50">
          <div v-for="doc in group.docs" :key="doc.id" class="px-5 py-4 flex items-center gap-4">
            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', statusBg(doc.is_verified, doc.is_rejected)]">
              <component :is="statusIcon(doc.is_verified, doc.is_rejected)" class="w-5 h-5" :class="statusIconColor(doc.is_verified, doc.is_rejected)" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-slate-700 text-sm">{{ doc.type_label ?? group.label }}</p>
              <p v-if="doc.file_name" class="text-xs text-slate-400 truncate">{{ doc.file_name }}</p>
              <p v-if="doc.remarks" class="text-xs text-danger-600 mt-0.5">⚠ {{ doc.remarks }}</p>
            </div>
            <div class="text-right flex-shrink-0">
              <span :class="['badge badge-sm', statusBadge(doc.is_verified, doc.is_rejected)]">
                {{ statusLabel(doc.is_verified, doc.is_rejected) }}
              </span>
              <p v-if="doc.verified_at" class="text-xs text-slate-400 mt-1">
                {{ formatDate(doc.verified_at) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Not yet submitted -->
        <div v-else class="px-5 py-5 flex items-center gap-3 bg-slate-50/50">
          <ClockIcon class="w-5 h-5 text-slate-300 shrink-0" />
          <div>
            <p class="text-sm text-slate-400">Not yet submitted</p>
            <p class="text-xs text-slate-300 mt-0.5">Please submit at your Barangay Social Welfare Center</p>
          </div>
        </div>
      </div>

      <!-- Info notice -->
      <div class="bg-white/30 backdrop-blur-sm rounded-xl px-5 py-4 text-center space-y-1">
        <p class="text-white/80 text-sm font-medium">Need to update your documents?</p>
        <p class="text-white/60 text-xs">
          Visit your assigned Barangay Social Welfare Center or contact DSWD — Lipa City at (043) XXX-XXXX.
        </p>
      </div>
    </div>
  </BeneficiaryLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import {
  DocumentTextIcon, CheckCircleIcon, XCircleIcon,
  ClockIcon, IdentificationIcon, AcademicCapIcon,
  HeartIcon, HomeIcon, UserGroupIcon,
} from '@heroicons/vue/24/outline'
import BeneficiaryLayout from '@/Layouts/BeneficiaryLayout.vue'

const props = defineProps({
  beneficiary:  Object,
  docGroups:    Object,   // keyed by document_type
  unread_count: Number,
})

// All required document categories for 4Ps
const allDocTypes = [
  {
    type:        'proof_of_identity',
    label:       'Valid Government ID',
    description: 'Any valid ID of the household representative',
    icon:        IdentificationIcon,
    iconBg:      'bg-brand-50',
    iconColor:   'text-brand-600',
    required:    true,
  },
  {
    type:        'birth_certificate',
    label:       'Birth Certificate (PSA)',
    description: 'Of the household representative and all children',
    icon:        DocumentTextIcon,
    iconBg:      'bg-success-50',
    iconColor:   'text-success-600',
    required:    true,
  },
  {
    type:        'school_enrollment',
    label:       'School Enrollment Form / Report Card',
    description: 'For each school-age child (Education condition)',
    icon:        AcademicCapIcon,
    iconBg:      'bg-warning-50',
    iconColor:   'text-warning-600',
    required:    false,
  },
  {
    type:        'health_records',
    label:       'Health Records / Immunization Card',
    description: 'For children 0–5 years old (Health condition)',
    icon:        HeartIcon,
    iconBg:      'bg-danger-50',
    iconColor:   'text-danger-600',
    required:    false,
  },
  {
    type:        'proof_of_address',
    label:       'Proof of Residency',
    description: 'Barangay certificate confirming Lipa City residence',
    icon:        HomeIcon,
    iconBg:      'bg-slate-50',
    iconColor:   'text-slate-600',
    required:    true,
  },
  {
    type:        'marriage_certificate',
    label:       'Marriage Certificate (PSA)',
    description: 'If married — for the household representative',
    icon:        UserGroupIcon,
    iconBg:      'bg-pink-50',
    iconColor:   'text-pink-600',
    required:    false,
  },
]

const documentGroups = computed(() =>
  allDocTypes.map(dt => ({
    ...dt,
    docs: props.docGroups?.[dt.type] ?? [],
  }))
)

const verifiedCount = computed(() =>
  Object.values(props.docGroups ?? {}).flat().filter(d => d.is_verified).length
)
const pendingCount = computed(() =>
  Object.values(props.docGroups ?? {}).flat().filter(d => !d.is_verified && !d.is_rejected).length
)
const rejectedCount = computed(() =>
  Object.values(props.docGroups ?? {}).flat().filter(d => d.is_rejected).length
)
const totalRequired = computed(() => allDocTypes.filter(d => d.required).length)
const progressPercent = computed(() =>
  totalRequired.value ? Math.round((verifiedCount.value / totalRequired.value) * 100) : 0
)

const statusBg = (verified, rejected) =>
  verified ? 'bg-success-50' : rejected ? 'bg-danger-50' : 'bg-slate-50'

const statusIcon = (verified, rejected) =>
  verified ? CheckCircleIcon : rejected ? XCircleIcon : ClockIcon

const statusIconColor = (verified, rejected) =>
  verified ? 'text-success-600' : rejected ? 'text-danger-600' : 'text-slate-300'

const statusBadge = (verified, rejected) =>
  verified ? 'badge-success' : rejected ? 'badge-danger' : 'badge-neutral'

const statusLabel = (verified, rejected) =>
  verified ? 'Verified' : rejected ? 'Rejected' : 'Pending'

const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
</script>
