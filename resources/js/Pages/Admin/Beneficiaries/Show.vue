<template>
  <Head :title="`${beneficiary.full_name} — Profile`" />
  <StaffLayout :page-title="beneficiary.unique_id" :page-subtitle="beneficiary.full_name">
    <div class="space-y-5">

      <!-- Flash messages -->
      <div v-if="$page.props.flash?.success"
        class="flex items-center gap-3 p-4 bg-success-50 border border-success-200 rounded-xl text-success-800 text-sm">
        <CheckCircleIcon class="w-5 h-5 flex-shrink-0 text-success-500" />
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error"
        class="flex items-center gap-3 p-4 bg-danger-50 border border-danger-200 rounded-xl text-danger-800 text-sm">
        <ExclamationCircleIcon class="w-5 h-5 flex-shrink-0 text-danger-500" />
        {{ $page.props.flash.error }}
      </div>

      <!-- Top bar: status + back + activate -->
      <div class="flex flex-wrap items-center gap-3">
        <Link :href="route('admin.beneficiaries.index')" class="btn btn-ghost btn-sm">
          <ChevronLeftIcon class="w-4 h-4" /> Back
        </Link>
        <span :class="['badge', statusBadge(beneficiary.status)]">{{ beneficiary.status }}</span>
        <span :class="['badge', beneficiary.is_compliant ? 'badge-success' : 'badge-warning']">
          {{ beneficiary.is_compliant ? '✓ Compliant' : '○ Pending Compliance' }}
        </span>

        <!-- Activate button -->
        <div class="ml-auto relative group">
          <button
            v-if="beneficiary.status === 'inactive'"
            @click="activateBeneficiary"
            :disabled="activating || !allRequiredPresent"
            :class="[
              'btn btn-sm',
              allRequiredPresent ? 'btn-success' : 'btn-ghost opacity-60 cursor-not-allowed'
            ]"
          >
            <CheckCircleIcon class="w-4 h-4" />
            {{ activating ? 'Activating…' : 'Activate & Issue Card' }}
          </button>
          <!-- Tooltip when docs incomplete -->
          <div v-if="beneficiary.status === 'inactive' && !allRequiredPresent"
            class="hidden group-hover:block absolute right-0 top-full mt-1 z-10 w-56 p-2 bg-slate-800 text-white text-xs rounded-lg shadow-lg">
            Upload all 3 required documents before activating.
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <!-- LEFT: Profile + Family + Documents -->
        <div class="lg:col-span-2 space-y-5">

          <!-- Profile Card -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Beneficiary Profile</h3>
              <button @click="editing = !editing" class="btn btn-ghost btn-sm">
                <PencilIcon class="w-4 h-4" />
                {{ editing ? 'Cancel' : 'Edit' }}
              </button>
            </div>
            <div class="card-body">
              <div class="flex gap-5">
                <!-- Photo -->
                <div class="flex-shrink-0">
                  <div class="w-24 h-28 rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
                    <img v-if="beneficiary.photo_path"
                      :src="`/storage/${beneficiary.photo_path}`"
                      class="w-full h-full object-cover" :alt="beneficiary.full_name" />
                    <div v-else class="w-full h-full flex items-center justify-center">
                      <UserIcon class="w-10 h-10 text-slate-300" />
                    </div>
                  </div>
                </div>

                <!-- Fields -->
                <div class="flex-1 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                  <InfoRow label="Full Name"   :value="beneficiary.full_name" />
                  <InfoRow label="Unique ID"   :value="beneficiary.unique_id" mono />
                  <InfoRow label="Birthdate"   :value="beneficiary.birthdate" />
                  <InfoRow label="Age"         :value="`${beneficiary.age} years old`" />
                  <InfoRow label="Sex"         :value="beneficiary.sex" capitalize />
                  <InfoRow label="Civil Status" :value="beneficiary.civil_status" capitalize />
                  <InfoRow label="Contact"     :value="beneficiary.contact_number || '—'" />
                  <InfoRow label="Barangay"    :value="`Brgy. ${beneficiary.barangay}`" />
                </div>
              </div>

              <!-- Edit form -->
              <div v-if="editing" class="mt-5 pt-5 border-t border-slate-100 space-y-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                  <div>
                    <label class="form-label">Status</label>
                    <select v-model="editForm.status" class="form-select">
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                      <option value="suspended">Suspended</option>
                      <option value="graduated">Graduated</option>
                      <option value="delisted">Delisted</option>
                    </select>
                  </div>
                  <div>
                    <label class="form-label">Contact Number</label>
                    <input v-model="editForm.contact_number" type="text" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">Barangay</label>
                    <input v-model="editForm.barangay" type="text" class="form-input" />
                  </div>
                </div>
                <div>
                  <label class="form-label">Remarks</label>
                  <textarea v-model="editForm.remarks" class="form-input" rows="2"></textarea>
                </div>
                <div class="flex gap-2">
                  <button @click="saveEdit" :disabled="editForm.processing" class="btn btn-primary btn-sm">
                    Save Changes
                  </button>
                  <button @click="editing = false" class="btn btn-ghost btn-sm">Cancel</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Family Members -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Family Members</h3>
              <span class="badge badge-neutral">{{ beneficiary.family_members?.length ?? 0 }}</span>
            </div>
            <div class="table-wrapper">
              <table class="table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Relationship</th>
                    <th>Age</th>
                    <th>Education</th>
                    <th>Flags</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!beneficiary.family_members?.length">
                    <td colspan="5" class="text-center text-slate-400 py-8">No family members recorded.</td>
                  </tr>
                  <tr v-for="m in beneficiary.family_members" :key="m.id">
                    <td class="font-medium text-slate-700 text-sm">{{ m.first_name }} {{ m.last_name }}</td>
                    <td class="text-sm text-slate-500 capitalize">{{ m.relationship }}</td>
                    <td class="text-sm text-slate-500">{{ m.age }} yrs</td>
                    <td class="text-sm text-slate-500 capitalize">{{ m.education_level || '—' }}</td>
                    <td class="flex gap-1">
                      <span v-if="m.is_school_age" class="badge badge-info badge-sm">School-age</span>
                      <span v-if="m.is_under_five" class="badge badge-warning badge-sm">Under 5</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ── Physical Documents Submitted to Office ── -->
          <div class="card">
            <div class="card-header">
              <div>
                <h3 class="font-semibold text-slate-800">Physical Documents</h3>
                <p class="text-xs text-slate-400 mt-0.5">Documents physically submitted by beneficiary at the DSWD office</p>
              </div>
              <!-- Progress pill -->
              <span :class="[
                'badge badge-sm',
                allRequiredPresent ? 'badge-success' : 'badge-warning'
              ]">
                {{ docChecklist.filter(d => d.submitted).length }}/{{ docChecklist.length }} Submitted
              </span>
            </div>
            <div class="card-body space-y-4">

              <!-- Document Slots -->
              <div class="space-y-3">
                <div
                  v-for="slot in docChecklist"
                  :key="slot.type"
                  :class="[
                    'rounded-xl border p-4 transition-all',
                    slot.submitted
                      ? (slot.doc.is_verified ? 'bg-success-50 border-success-200' : 'bg-amber-50 border-amber-200')
                      : 'bg-slate-50 border-dashed border-slate-300'
                  ]"
                >
                  <div class="flex items-start gap-4">
                    <!-- Status icon -->
                    <div :class="[
                      'flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center',
                      slot.submitted
                        ? (slot.doc.is_verified ? 'bg-success-100' : 'bg-amber-100')
                        : 'bg-slate-200'
                    ]">
                      <CheckCircleIcon v-if="slot.submitted && slot.doc.is_verified" class="w-5 h-5 text-success-600" />
                      <ClockIcon v-else-if="slot.submitted && !slot.doc.is_verified" class="w-5 h-5 text-amber-600" />
                      <DocumentPlusIcon v-else class="w-5 h-5 text-slate-400" />
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-sm font-semibold text-slate-800">{{ slot.label }}</p>
                        <span v-if="slot.submitted && slot.doc.is_verified" class="badge badge-success badge-sm">✓ Verified</span>
                        <span v-else-if="slot.submitted" class="badge badge-warning badge-sm">Pending Verification</span>
                        <span v-else class="badge badge-neutral badge-sm">Not Submitted</span>
                      </div>

                      <!-- Uploaded file info -->
                      <div v-if="slot.submitted" class="mt-1 flex items-center gap-3 flex-wrap">
                        <p class="text-xs text-slate-500">
                          Uploaded {{ slot.doc.uploaded_at }}
                          <span v-if="slot.doc.uploaded_by"> by <span class="font-medium">{{ slot.doc.uploaded_by }}</span></span>
                        </p>
                      </div>

                      <!-- Upload form (for this slot) -->
                      <div v-if="uploadingSlot === slot.type" class="mt-3 space-y-2">
                        <input
                          type="file"
                          class="form-input text-sm"
                          accept=".pdf,.jpg,.jpeg,.png"
                          @change="e => slotFile = e.target.files[0]"
                        />
                        <p class="text-xs text-slate-400">Accepted: PDF, JPG, PNG — max 10 MB</p>
                        <div class="flex gap-2">
                          <button
                            @click="uploadSlot(slot.type)"
                            :disabled="!slotFile || uploadingDoc"
                            class="btn btn-primary btn-sm"
                          >
                            <ArrowUpTrayIcon class="w-3.5 h-3.5" />
                            {{ uploadingDoc ? 'Uploading…' : (slot.submitted ? 'Replace File' : 'Upload') }}
                          </button>
                          <button @click="cancelUpload" class="btn btn-ghost btn-sm">Cancel</button>
                        </div>
                      </div>
                    </div>

                    <!-- Action buttons -->
                    <div v-if="uploadingSlot !== slot.type" class="flex items-center gap-1 flex-shrink-0">
                      <!-- View -->
                      <a v-if="slot.submitted"
                        :href="`/storage/${slot.doc.file_path}`"
                        target="_blank"
                        class="btn btn-ghost btn-sm text-brand-600"
                        title="View file"
                      >
                        <EyeIcon class="w-4 h-4" />
                      </a>
                      <!-- Verify toggle -->
                      <button
                        v-if="slot.submitted"
                        @click="toggleVerify(slot.doc.id)"
                        :class="['btn btn-ghost btn-sm', slot.doc.is_verified ? 'text-amber-600' : 'text-success-600']"
                        :title="slot.doc.is_verified ? 'Mark as unverified' : 'Mark as verified'"
                      >
                        <ShieldCheckIcon class="w-4 h-4" />
                      </button>
                      <!-- Upload / Replace -->
                      <button
                        @click="startUpload(slot.type)"
                        class="btn btn-ghost btn-sm text-slate-600"
                        :title="slot.submitted ? 'Replace document' : 'Upload document'"
                      >
                        <ArrowUpTrayIcon class="w-4 h-4" />
                      </button>
                      <!-- Delete -->
                      <button
                        v-if="slot.submitted"
                        @click="removeDoc(slot.doc.id)"
                        class="btn btn-ghost btn-sm text-danger-600"
                        title="Remove"
                      >
                        <TrashIcon class="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Activation readiness summary -->
              <div :class="[
                'flex items-center gap-3 p-3 rounded-lg text-sm',
                allRequiredPresent ? 'bg-success-50 text-success-800' : 'bg-amber-50 text-amber-800'
              ]">
                <component
                  :is="allRequiredPresent ? CheckCircleIcon : ExclamationCircleIcon"
                  class="w-4 h-4 flex-shrink-0"
                />
                <span v-if="allRequiredPresent">
                  All required documents submitted. You may now activate this beneficiary.
                </span>
                <span v-else>
                  {{ docChecklist.filter(d => !d.submitted).length }} document(s) still required before activation.
                </span>
              </div>
            </div>
          </div>

          <!-- Compliance History -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Compliance Records</h3>
            </div>
            <div class="table-wrapper">
              <table class="table">
                <thead>
                  <tr>
                    <th>Period</th>
                    <th>Education</th>
                    <th>Health</th>
                    <th>FDS</th>
                    <th>Overall</th>
                    <th>Verified By</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!beneficiary.compliance_records?.length">
                    <td colspan="6" class="text-center text-slate-400 py-8">No compliance records yet.</td>
                  </tr>
                  <tr v-for="cr in beneficiary.compliance_records" :key="cr.id">
                    <td class="text-sm font-medium text-slate-700">{{ cr.period }}</td>
                    <td><ComplianceDot :value="cr.edu_attendance_compliant" /></td>
                    <td><ComplianceDot :value="cr.health_compliant" /></td>
                    <td><ComplianceDot :value="cr.fds_compliant" /></td>
                    <td>
                      <span :class="['badge badge-sm', cr.is_fully_compliant ? 'badge-success' : 'badge-danger']">
                        {{ cr.is_fully_compliant ? 'Complete' : 'Incomplete' }}
                      </span>
                    </td>
                    <td class="text-sm text-slate-500">{{ cr.verifier?.name ?? '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- RIGHT: Grant summary + distributions -->
        <div class="space-y-5">

          <!-- Grant calculations -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Grant History</h3>
            </div>
            <div class="card-body space-y-3">
              <div v-if="!beneficiary.grant_calculations?.length" class="text-center text-slate-400 text-sm py-4">
                No grant records yet.
              </div>
              <div v-for="g in beneficiary.grant_calculations" :key="g.id"
                class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between">
                  <p class="text-xs text-slate-500">{{ g.distribution_event?.period ?? 'Period unknown' }}</p>
                  <p class="font-bold text-success-600 text-sm">
                    ₱{{ Number(g.total_grant_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                  </p>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">{{ g.months_covered }} months</p>
              </div>
            </div>
          </div>

          <!-- Distribution records -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Cash Grant Claims</h3>
            </div>
            <div class="card-body space-y-2">
              <div v-if="!beneficiary.distributions?.length" class="text-center text-slate-400 text-sm py-4">
                No claims recorded.
              </div>
              <div v-for="d in beneficiary.distributions" :key="d.id"
                class="flex items-center justify-between text-sm py-2 border-b border-slate-100 last:border-0">
                <div>
                  <p class="text-slate-700 font-medium">{{ d.distribution_event?.period ?? '—' }}</p>
                  <p class="text-xs text-slate-400">{{ d.claimed_by_type === 'proxy' ? '👤 Via Proxy' : '✓ Self' }}</p>
                </div>
                <p class="font-bold text-success-600">₱{{ Number(d.amount_released).toLocaleString() }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import {
  UserIcon, PencilIcon, EyeIcon, TrashIcon,
  ChevronLeftIcon, ArrowUpTrayIcon, DocumentPlusIcon,
  CheckCircleIcon, ExclamationCircleIcon, ClockIcon, ShieldCheckIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  beneficiary:       Object,
  docChecklist:      Array,
  requiredDocTypes:  Array,
  allRequiredPresent: Boolean,
})

const editing    = ref(false)
const activating = ref(false)

// ── Activate ─────────────────────────────────────────────────────────────────
const activateBeneficiary = () => {
  if (!confirm(
    'Activate this beneficiary?\n\nThis confirms that all required documents have been physically received and verified at the DSWD office.\n\nA QR card will be automatically issued.'
  )) return
  activating.value = true
  router.post(route('admin.beneficiaries.activate', props.beneficiary.id), {}, {
    onFinish: () => { activating.value = false },
  })
}

// ── Edit form ─────────────────────────────────────────────────────────────────
const editForm = useForm({
  first_name:     props.beneficiary.first_name,
  last_name:      props.beneficiary.last_name,
  middle_name:    props.beneficiary.middle_name,
  contact_number: props.beneficiary.contact_number,
  barangay:       props.beneficiary.barangay,
  office_id:      props.beneficiary.office_id,
  status:         props.beneficiary.status,
  remarks:        props.beneficiary.remarks,
})

const saveEdit = () => {
  editForm.put(route('admin.beneficiaries.update', props.beneficiary.id), {
    onSuccess: () => { editing.value = false },
  })
}

// ── Per-slot document upload ───────────────────────────────────────────────────
const uploadingSlot = ref(null)   // which doc type slot is open
const slotFile      = ref(null)
const uploadingDoc  = ref(false)

const startUpload = (type) => {
  uploadingSlot.value = type
  slotFile.value = null
}

const cancelUpload = () => {
  uploadingSlot.value = null
  slotFile.value = null
}

const uploadSlot = (type) => {
  if (!slotFile.value) return
  uploadingDoc.value = true

  const form = new FormData()
  form.append('document_type', type)
  form.append('file', slotFile.value)

  router.post(route('admin.beneficiaries.documents.upload', props.beneficiary.id), form, {
    forceFormData: true,
    onSuccess: () => {
      uploadingSlot.value = null
      slotFile.value = null
    },
    onFinish: () => { uploadingDoc.value = false },
  })
}

// ── Verify toggle ─────────────────────────────────────────────────────────────
const toggleVerify = (docId) => {
  router.patch(route('admin.beneficiaries.documents.verify', {
    id: props.beneficiary.id,
    docId,
  }))
}

// ── Delete document ───────────────────────────────────────────────────────────
const removeDoc = (docId) => {
  if (!confirm('Remove this document? You will need to upload it again.')) return
  router.delete(route('admin.beneficiaries.documents.delete', {
    id: props.beneficiary.id,
    docId,
  }))
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const statusBadge = (s) => ({
  active: 'badge-success', inactive: 'badge-neutral',
  suspended: 'badge-danger', graduated: 'badge-info', delisted: 'badge-danger',
}[s] ?? 'badge-neutral')

const InfoRow = {
  props: ['label', 'value', 'mono', 'capitalize'],
  template: `
    <div>
      <p class="text-xs text-slate-400 mb-0.5">{{ label }}</p>
      <p :class="['font-medium text-slate-700', mono ? 'font-mono text-xs' : '', capitalize ? 'capitalize' : '']">
        {{ value ?? '—' }}
      </p>
    </div>
  `
}

const ComplianceDot = {
  props: ['value'],
  template: `
    <span :class="value === true ? 'text-success-600' : value === false ? 'text-danger-600' : 'text-slate-300'">
      {{ value === true ? '✓' : value === false ? '✗' : '—' }}
    </span>
  `
}
</script>
