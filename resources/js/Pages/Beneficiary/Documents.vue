<template>
  <Head title="My Documents" />
  <BeneficiaryLayout :unread-count="unreadCount ?? 0">
    <div class="space-y-5">

      <!-- Flash messages -->
      <div v-if="$page.props.flash?.success"
        class="flex items-center gap-3 px-4 py-3 rounded-xl bg-green-500/20 border border-green-400/30 text-green-100 text-sm">
        <CheckCircleIcon class="w-5 h-5 shrink-0" />
        {{ $page.props.flash.success }}
      </div>

      <!-- Header -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-5 shadow-lg border border-white/50">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
              <DocumentTextIcon class="w-5 h-5 text-brand-600" />
              My Submitted Documents
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
              Documents you physically submitted at the DSWD office, verified by staff.
            </p>
          </div>
          <!-- Progress pill -->
          <div class="flex items-center gap-2">
            <span class="badge badge-success">{{ verifiedCount }} Verified</span>
            <span class="badge badge-warning">{{ pendingCount }} Pending</span>
          </div>
        </div>
      </div>

      <!-- Submission Progress -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-5 shadow-lg border border-white/50">
        <div class="flex items-center justify-between mb-2">
          <p class="text-sm font-semibold text-slate-700">Activation Document Progress</p>
          <p class="text-sm font-bold text-brand-600">{{ submittedCount }}/{{ totalRequired }}</p>
        </div>
        <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden">
          <div class="h-full rounded-full transition-all duration-700"
            :style="`width: ${progressPercent}%; background: linear-gradient(90deg, #4f46e5, #7c3aed)`">
          </div>
        </div>
        <div class="flex items-center justify-between mt-2 text-xs text-slate-400">
          <span>{{ submittedCount }} of {{ totalRequired }} required documents submitted</span>
          <span v-if="progressPercent === 100" class="text-success-600 font-medium">✓ All Submitted</span>
        </div>
      </div>

      <!-- Required Document Slots -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
        <div class="px-5 py-4 border-b border-slate-100">
          <h2 class="font-semibold text-slate-800 text-sm">Required Activation Documents</h2>
          <p class="text-xs text-slate-400 mt-0.5">These must be physically presented and submitted at the DSWD-Lipa City office.</p>
        </div>

        <div class="divide-y divide-slate-50">
          <div v-for="slot in requiredSlots" :key="slot.type"
            class="px-5 py-4 flex items-start gap-4">

            <!-- Icon -->
            <div :class="[
              'w-10 h-10 rounded-xl flex items-center justify-center shrink-0 mt-0.5',
              slot.doc
                ? (slot.doc.is_verified ? 'bg-success-50' : 'bg-amber-50')
                : 'bg-slate-100'
            ]">
              <CheckCircleIcon v-if="slot.doc?.is_verified" class="w-5 h-5 text-success-600" />
              <ClockIcon v-else-if="slot.doc" class="w-5 h-5 text-amber-500" />
              <DocumentPlusIcon v-else class="w-5 h-5 text-slate-400" />
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-slate-800">{{ slot.label }}</p>
              <p class="text-xs text-slate-400 mt-0.5">{{ slot.description }}</p>

              <!-- Submitted file details -->
              <div v-if="slot.doc" class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                <span>{{ slot.doc.file_size_kb ? slot.doc.file_size_kb + ' KB' : '' }}</span>
                <span v-if="slot.doc.file_size_kb">·</span>
                <span v-if="slot.doc.uploaded_at">Submitted {{ slot.doc.uploaded_at }}</span>
                <a v-if="slot.doc.file_path"
                  :href="`/storage/${slot.doc.file_path}`"
                  target="_blank"
                  class="text-brand-600 hover:underline flex items-center gap-1">
                  <ArrowTopRightOnSquareIcon class="w-3 h-3" />
                  View file
                </a>
              </div>
            </div>

            <!-- Status badge -->
            <div class="shrink-0 mt-0.5">
              <span v-if="slot.doc?.is_verified" class="badge badge-success badge-sm">✓ Verified</span>
              <span v-else-if="slot.doc" class="badge badge-warning badge-sm">Pending Review</span>
              <span v-else class="badge badge-neutral badge-sm">Not Yet Submitted</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Info note -->
      <div class="bg-white/30 backdrop-blur-sm rounded-xl px-5 py-4 text-center space-y-1">
        <p class="text-white font-semibold text-sm">How to submit your documents</p>
        <p class="text-white/70 text-xs leading-relaxed">
          Bring the original copies of your <strong class="text-white">Valid ID</strong>,
          <strong class="text-white">Birth Certificate</strong>, and
          <strong class="text-white">Barangay Certificate</strong> to the
          DSWD-Lipa City office. Staff will scan and upload them to the system on your behalf.
          Documents are typically processed within 1–3 working days.
        </p>
        <p class="text-white/50 text-xs mt-1">
          For assistance, contact DSWD — Lipa City: (043) XXX-XXXX
        </p>
      </div>

    </div>
  </BeneficiaryLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import {
  DocumentTextIcon, CheckCircleIcon, ClockIcon, DocumentPlusIcon,
  ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline'
import BeneficiaryLayout from '@/Layouts/BeneficiaryLayout.vue'

const props = defineProps({
  beneficiary:  Object,
  adminDocs:    Array,   // documents physically submitted + uploaded by admin
  unreadCount:  Number,
})

// ── Required slot definitions ────────────────────────────────────────────────
const REQUIRED_TYPES = [
  {
    type:        'valid_id',
    label:       'Valid Government ID',
    description: 'Any valid government-issued ID (PhilSys, Voter\'s ID, Driver\'s License, etc.)',
  },
  {
    type:        'birth_certificate',
    label:       'Birth Certificate (PSA)',
    description: 'PSA-authenticated birth certificate of the household representative.',
  },
  {
    type:        'barangay_certificate',
    label:       'Proof of Residency (Barangay Certificate)',
    description: 'Barangay Certificate confirming current residence in Lipa City.',
  },
]

// Map admin docs by type for easy lookup
const adminDocsByType = computed(() => {
  const map = {}
  ;(props.adminDocs ?? []).forEach(d => { map[d.document_type] = d })
  return map
})

const requiredSlots = computed(() =>
  REQUIRED_TYPES.map(rt => ({
    ...rt,
    doc: adminDocsByType.value[rt.type] ?? null,
  }))
)

// ── Stats ─────────────────────────────────────────────────────────────────────
const totalRequired   = computed(() => REQUIRED_TYPES.length)
const submittedCount  = computed(() => requiredSlots.value.filter(s => s.doc).length)
const verifiedCount   = computed(() => requiredSlots.value.filter(s => s.doc?.is_verified).length)
const pendingCount    = computed(() => requiredSlots.value.filter(s => s.doc && !s.doc.is_verified).length)
const progressPercent = computed(() =>
  totalRequired.value ? Math.round((submittedCount.value / totalRequired.value) * 100) : 0
)
</script>
