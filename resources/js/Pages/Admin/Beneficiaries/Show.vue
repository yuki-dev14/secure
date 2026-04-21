<template>
  <Head :title="`${beneficiary.full_name} — Profile`" />
  <StaffLayout :page-title="beneficiary.unique_id" :page-subtitle="beneficiary.full_name">
    <div class="space-y-5">

      <!-- Top bar: status + back -->
      <div class="flex flex-wrap items-center gap-3">
        <Link :href="route('admin.beneficiaries.index')" class="btn btn-ghost btn-sm">
          <ChevronLeftIcon class="w-4 h-4" /> Back
        </Link>
        <span :class="['badge', statusBadge(beneficiary.status)]">{{ beneficiary.status }}</span>
        <span :class="['badge', beneficiary.is_compliant ? 'badge-success' : 'badge-danger']">
          {{ beneficiary.is_compliant ? '✓ Compliant' : '✗ Non-Compliant' }}
        </span>
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

          <!-- ── Document Upload Panel ── -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Submitted Requirements</h3>
              <span class="badge badge-neutral">{{ beneficiary.documents?.length ?? 0 }} files</span>
            </div>
            <div class="card-body space-y-5">

              <!-- Upload form -->
              <div class="p-4 bg-slate-50 rounded-xl border border-dashed border-slate-300 space-y-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Upload New Document</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="form-label">Document Type <span class="text-danger-500">*</span></label>
                    <select v-model="docForm.document_type" class="form-select">
                      <option value="">— Select type —</option>
                      <option value="birth_certificate">Birth Certificate</option>
                      <option value="valid_id">Valid ID</option>
                      <option value="school_id">School ID</option>
                      <option value="report_card">Report Card</option>
                      <option value="health_record">Health Record</option>
                      <option value="vaccination_booklet">Vaccination Booklet</option>
                      <option value="medical_certificate">Medical Certificate</option>
                      <option value="barangay_certificate">Barangay Certificate</option>
                      <option value="photo_1x1">1x1 Photo</option>
                      <option value="certificate_of_indigency">Certificate of Indigency</option>
                      <option value="prenatal_record">Prenatal Record</option>
                      <option value="other">Other</option>
                    </select>
                    <p v-if="docForm.errors.document_type" class="text-danger-600 text-xs mt-1">{{ docForm.errors.document_type }}</p>
                  </div>
                  <div>
                    <label class="form-label">Document Name (optional)</label>
                    <input v-model="docForm.document_name" type="text" class="form-input" placeholder="e.g. Juan's Birth Certificate" />
                  </div>
                  <div>
                    <label class="form-label">Validity Date (optional)</label>
                    <input v-model="docForm.validity_date" type="date" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">Description (optional)</label>
                    <input v-model="docForm.description" type="text" class="form-input" placeholder="Short note…" />
                  </div>
                  <div class="sm:col-span-2">
                    <label class="form-label">File <span class="text-danger-500">*</span></label>
                    <input
                      type="file"
                      class="form-input"
                      accept=".pdf,.jpg,.jpeg,.png"
                      @change="e => docForm.file = e.target.files[0]"
                    />
                    <p class="text-xs text-slate-400 mt-1">Accepted: PDF, JPG, PNG — max 10 MB</p>
                    <p v-if="docForm.errors.file" class="text-danger-600 text-xs mt-1">{{ docForm.errors.file }}</p>
                  </div>
                </div>
                <button
                  @click="uploadDoc"
                  :disabled="docForm.processing || !docForm.document_type || !docForm.file"
                  class="btn btn-primary btn-sm"
                >
                  <ArrowUpTrayIcon class="w-4 h-4" />
                  {{ docForm.processing ? 'Uploading…' : 'Upload Document' }}
                </button>
              </div>

              <!-- Existing documents list -->
              <div v-if="!beneficiary.documents?.length" class="text-center text-slate-400 text-sm py-6">
                <DocumentIcon class="w-10 h-10 mx-auto mb-2 opacity-30" />
                No documents uploaded yet.
              </div>
              <div v-else class="divide-y divide-slate-100">
                <div
                  v-for="doc in beneficiary.documents"
                  :key="doc.id"
                  class="flex items-center justify-between py-3"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-brand-50 rounded-lg flex items-center justify-center flex-shrink-0">
                      <DocumentIcon class="w-5 h-5 text-brand-500" />
                    </div>
                    <div>
                      <p class="text-sm font-medium text-slate-700">{{ doc.document_name }}</p>
                      <p class="text-xs text-slate-400 capitalize">
                        {{ doc.document_type_label ?? doc.document_type }}
                        <span v-if="doc.validity_date"> · Valid until {{ doc.validity_date }}</span>
                        <span class="ml-2">{{ doc.file_size_kb }} KB</span>
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <span v-if="doc.is_verified" class="badge badge-success badge-sm">Verified</span>
                    <span v-else class="badge badge-warning badge-sm">Pending</span>
                    <a
                      :href="`/storage/${doc.file_path}`"
                      target="_blank"
                      class="btn btn-ghost btn-sm text-brand-600"
                      title="View file"
                    >
                      <EyeIcon class="w-4 h-4" />
                    </a>
                    <button
                      @click="removeDoc(doc.id)"
                      class="btn btn-ghost btn-sm text-danger-600"
                      title="Remove"
                    >
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </div>
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
                        {{ cr.is_fully_compliant ? 'Compliant' : 'Non-Compliant' }}
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
  ChevronLeftIcon, ArrowUpTrayIcon, DocumentIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({ beneficiary: Object })

const editing = ref(false)

// ── Edit form ──────────────────────────────────────────────────────────
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

// ── Document upload form ───────────────────────────────────────────────
const docForm = useForm({
  document_type: '',
  document_name: '',
  validity_date: '',
  description:   '',
  file:          null,
})

const uploadDoc = () => {
  docForm.post(route('admin.beneficiaries.documents.upload', props.beneficiary.id), {
    forceFormData: true,
    onSuccess: () => {
      docForm.reset()
      // Reset the file input manually
      const fi = document.querySelector('input[type="file"]')
      if (fi) fi.value = ''
    },
  })
}

const removeDoc = (docId) => {
  if (!confirm('Remove this document?')) return
  router.delete(route('admin.beneficiaries.documents.delete', {
    id: props.beneficiary.id,
    docId,
  }))
}

// ── Helpers ────────────────────────────────────────────────────────────
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
