<template>
  <Head title="My Documents" />
  <BeneficiaryLayout :unread-count="unreadCount ?? 0">
    <div class="space-y-5">

      <!-- Flash messages -->
      <div v-if="$page.props.flash?.success"
        class="flex items-center gap-3 px-4 py-3 rounded-xl bg-green-500/20 border border-green-400/30 text-green-100 text-sm">
        <CheckCircleIcon class="w-5 h-5 flex-shrink-0" />
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error"
        class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-500/20 border border-red-400/30 text-red-100 text-sm">
        <ExclamationCircleIcon class="w-5 h-5 flex-shrink-0" />
        {{ $page.props.flash.error }}
      </div>

      <!-- Header -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-5 shadow-lg border border-white/50">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
              <DocumentTextIcon class="w-5 h-5 text-brand-600" />
              My Documentary Requirements
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
              Upload and track the status of all documents submitted to DSWD.
            </p>
          </div>
          <div class="flex items-center gap-3 text-sm">
            <span class="badge badge-success">{{ verifiedCount }} Verified</span>
            <span class="badge badge-warning">{{ pendingCount }} Pending</span>
            <span v-if="rejectedCount" class="badge badge-danger">{{ rejectedCount }} Rejected</span>
          </div>
        </div>
      </div>

      <!-- Progress Bar -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-5 shadow-lg border border-white/50">
        <div class="flex items-center justify-between mb-2">
          <p class="text-sm font-semibold text-slate-700">Overall Submission Progress</p>
          <p class="text-sm font-bold text-brand-600">{{ progressPercent }}%</p>
        </div>
        <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden">
          <div class="h-full rounded-full transition-all duration-700"
            :style="`width: ${progressPercent}%; background: linear-gradient(90deg, #4f46e5, #7c3aed)`">
          </div>
        </div>
        <div class="flex items-center justify-between mt-2 text-xs text-slate-400">
          <span>{{ verifiedCount }} of {{ totalRequired }} required documents verified</span>
          <span v-if="progressPercent === 100" class="text-success-600 font-medium">✓ Complete</span>
        </div>
      </div>

      <!-- Document Type Groups -->
      <div v-for="group in documentGroups" :key="group.type"
        class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">

        <!-- Group header -->
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

        <!-- Submitted docs -->
        <div v-if="group.docs.length" class="divide-y divide-slate-50">
          <div v-for="doc in group.docs" :key="doc.id" class="px-5 py-4 flex items-center gap-4">
            <!-- Status icon -->
            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', statusBg(doc.is_verified)]">
              <component :is="statusIcon(doc.is_verified)" class="w-5 h-5" :class="statusIconColor(doc.is_verified)" />
            </div>
            <!-- File info -->
            <div class="flex-1 min-w-0">
              <p class="font-medium text-slate-700 text-sm">{{ doc.document_name }}</p>
              <div class="flex items-center gap-2 mt-0.5">
                <span class="text-xs text-slate-400">{{ doc.file_size_kb ? doc.file_size_kb + ' KB' : '' }}</span>
                <span v-if="doc.file_size_kb" class="text-slate-300">·</span>
                <a v-if="doc.file_path"
                  :href="`/storage/${doc.file_path}`"
                  target="_blank"
                  class="text-xs text-brand-600 hover:underline flex items-center gap-1">
                  <ArrowTopRightOnSquareIcon class="w-3 h-3" />
                  View file
                </a>
              </div>
            </div>
            <!-- Status badge + delete -->
            <div class="flex items-center gap-3 flex-shrink-0">
              <div class="text-right">
                <span :class="['badge badge-sm', statusBadge(doc.is_verified)]">
                  {{ doc.is_verified ? 'Verified' : 'Pending Review' }}
                </span>
                <p v-if="doc.verified_at" class="text-xs text-slate-400 mt-1">
                  {{ formatDate(doc.verified_at) }}
                </p>
              </div>
              <!-- Only allow deleting unverified docs -->
              <button v-if="!doc.is_verified"
                @click="confirmDelete(doc)"
                class="p-1.5 rounded-lg text-slate-400 hover:text-danger-600 hover:bg-danger-50 transition-colors"
                title="Remove document">
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Upload form (collapsed by default, expands on click) -->
        <div class="px-5 py-4 bg-slate-50/60">
          <!-- Trigger -->
          <button v-if="!uploadingType || uploadingType !== group.type"
            @click="openUpload(group.type)"
            class="w-full flex items-center justify-center gap-2 py-3 border-2 border-dashed border-slate-300 rounded-xl text-slate-400 hover:border-brand-400 hover:text-brand-600 hover:bg-brand-50/30 transition-all text-sm font-medium">
            <ArrowUpTrayIcon class="w-4 h-4" />
            {{ group.docs.length ? 'Upload another' : 'Upload document' }}
          </button>

          <!-- Upload form panel -->
          <div v-if="uploadingType === group.type"
            class="space-y-3 border border-brand-200 bg-white rounded-xl p-4">
            <div class="flex items-center justify-between">
              <p class="text-sm font-semibold text-slate-700">Upload {{ group.label }}</p>
              <button @click="cancelUpload" class="text-slate-400 hover:text-slate-600">
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>

            <form @submit.prevent="submitUpload(group.type)" class="space-y-3">
              <!-- File picker -->
              <div>
                <label class="form-label">File <span class="text-danger-500">*</span></label>
                <div class="relative">
                  <input
                    type="file"
                    @change="onFileChange"
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="block w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 cursor-pointer border border-slate-200 rounded-xl px-3 py-2"
                    required />
                </div>
                <p class="text-xs text-slate-400 mt-1">PDF, JPG, or PNG — max 10 MB</p>
                <p v-if="uploadErrors.file" class="text-xs text-danger-600 mt-1">{{ uploadErrors.file[0] }}</p>
              </div>

              <!-- Custom name -->
              <div>
                <label class="form-label">Document Name <span class="text-slate-400 text-xs">(optional)</span></label>
                <input v-model="uploadForm.document_name" type="text" class="form-input"
                  placeholder="e.g. PSA Birth Certificate — Juan Dela Cruz" />
              </div>

              <!-- Description -->
              <div>
                <label class="form-label">Description <span class="text-slate-400 text-xs">(optional)</span></label>
                <input v-model="uploadForm.description" type="text" class="form-input"
                  placeholder="Any notes for the DSWD reviewer" />
              </div>

              <!-- Validity date (for IDs) -->
              <div v-if="group.hasExpiry">
                <label class="form-label">ID Expiry Date</label>
                <input v-model="uploadForm.validity_date" type="date" class="form-input" />
              </div>

              <!-- Actions -->
              <div class="flex gap-2 pt-1">
                <button type="submit" :disabled="uploading"
                  class="btn btn-primary btn-sm flex-1">
                  <ArrowUpTrayIcon class="w-4 h-4" />
                  {{ uploading ? 'Uploading…' : 'Upload' }}
                </button>
                <button type="button" @click="cancelUpload" class="btn btn-ghost btn-sm">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Footer note -->
      <div class="bg-white/30 backdrop-blur-sm rounded-xl px-5 py-4 text-center space-y-1">
        <p class="text-white/80 text-sm font-medium">Need help with your documents?</p>
        <p class="text-white/60 text-xs">
          Visit your assigned Barangay Social Welfare Center or contact DSWD — Lipa City at (043) XXX-XXXX.
          Uploaded documents will be reviewed within 3–5 working days.
        </p>
      </div>
    </div>

    <!-- Delete confirmation modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="deletingDoc" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="deletingDoc = null"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 space-y-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-danger-100 flex items-center justify-center">
                <TrashIcon class="w-5 h-5 text-danger-600" />
              </div>
              <div>
                <h3 class="font-semibold text-slate-800">Remove Document?</h3>
                <p class="text-xs text-slate-400 mt-0.5">This cannot be undone.</p>
              </div>
            </div>
            <p class="text-sm text-slate-600">
              Are you sure you want to remove
              <strong>{{ deletingDoc?.document_name }}</strong>?
            </p>
            <div class="flex gap-3">
              <button @click="executeDelete" class="btn btn-danger flex-1 btn-sm">Yes, Remove</button>
              <button @click="deletingDoc = null" class="btn btn-ghost flex-1 btn-sm">Cancel</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </BeneficiaryLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import {
  DocumentTextIcon, CheckCircleIcon, XCircleIcon, ClockIcon,
  IdentificationIcon, AcademicCapIcon, HeartIcon, HomeIcon, UserGroupIcon,
  ArrowUpTrayIcon, TrashIcon, XMarkIcon, ArrowTopRightOnSquareIcon,
  CheckCircleIcon as CheckCircleIconSolid,
  ExclamationCircleIcon,
} from '@heroicons/vue/24/outline'
import BeneficiaryLayout from '@/Layouts/BeneficiaryLayout.vue'

const props = defineProps({
  beneficiary:  Object,
  docGroups:    Object,
  unreadCount:  Number,
})

// ── Document type definitions ────────────────────────────────────────────────
const allDocTypes = [
  {
    type:        'valid_id',
    label:       'Valid Government ID',
    description: 'Any valid government-issued ID of the household representative',
    icon:        IdentificationIcon,
    iconBg:      'bg-brand-50',
    iconColor:   'text-brand-600',
    required:    true,
    hasExpiry:   true,
  },
  {
    type:        'birth_certificate',
    label:       'Birth Certificate (PSA)',
    description: 'Of the household representative and all children',
    icon:        DocumentTextIcon,
    iconBg:      'bg-success-50',
    iconColor:   'text-success-600',
    required:    true,
    hasExpiry:   false,
  },
  {
    type:        'report_card',
    label:       'Report Card / Enrollment Form',
    description: 'For each school-age child — required for Education compliance',
    icon:        AcademicCapIcon,
    iconBg:      'bg-warning-50',
    iconColor:   'text-warning-600',
    required:    false,
    hasExpiry:   false,
  },
  {
    type:        'vaccination_booklet',
    label:       'Immunization Card / Vaccination Booklet',
    description: 'For children 0–5 years old — required for Health compliance',
    icon:        HeartIcon,
    iconBg:      'bg-danger-50',
    iconColor:   'text-danger-600',
    required:    false,
    hasExpiry:   false,
  },
  {
    type:        'health_record',
    label:       'Health Records / Medical Certificate',
    description: 'Weight monitoring records and annual health check-up',
    icon:        HeartIcon,
    iconBg:      'bg-pink-50',
    iconColor:   'text-pink-600',
    required:    false,
    hasExpiry:   false,
  },
  {
    type:        'barangay_certificate',
    label:       'Proof of Residency (Barangay Certificate)',
    description: 'Confirming current residence in Lipa City, Batangas',
    icon:        HomeIcon,
    iconBg:      'bg-slate-50',
    iconColor:   'text-slate-600',
    required:    true,
    hasExpiry:   true,
  },
  {
    type:        'certificate_of_indigency',
    label:       'Certificate of Indigency',
    description: 'Issued by the Barangay — confirms beneficiary status',
    icon:        DocumentTextIcon,
    iconBg:      'bg-amber-50',
    iconColor:   'text-amber-600',
    required:    false,
    hasExpiry:   false,
  },
  {
    type:        'photo_1x1',
    label:       '1×1 ID Photo',
    description: 'Recent photo with white background — for your 4Ps ID card',
    icon:        UserGroupIcon,
    iconBg:      'bg-indigo-50',
    iconColor:   'text-indigo-600',
    required:    true,
    hasExpiry:   false,
  },
]

const documentGroups = computed(() =>
  allDocTypes.map(dt => ({
    ...dt,
    docs: props.docGroups?.[dt.type] ?? [],
  }))
)

// ── Stats ────────────────────────────────────────────────────────────────────
const allDocs        = computed(() => Object.values(props.docGroups ?? {}).flat())
const verifiedCount  = computed(() => allDocs.value.filter(d => d.is_verified).length)
const pendingCount   = computed(() => allDocs.value.filter(d => !d.is_verified).length)
const rejectedCount  = computed(() => 0)   // reserved for future reject flow
const totalRequired  = computed(() => allDocTypes.filter(d => d.required).length)
const progressPercent = computed(() =>
  totalRequired.value ? Math.round((verifiedCount.value / totalRequired.value) * 100) : 0
)

// ── Upload state ─────────────────────────────────────────────────────────────
const uploadingType  = ref(null)
const uploading      = ref(false)
const uploadErrors   = ref({})
const uploadForm     = ref({ document_name: '', description: '', validity_date: '', file: null })

const openUpload    = (type) => { uploadingType.value = type; uploadErrors.value = {}; uploadForm.value = { document_name: '', description: '', validity_date: '', file: null } }
const cancelUpload  = () => { uploadingType.value = null; uploadErrors.value = {} }
const onFileChange  = (e) => { uploadForm.value.file = e.target.files[0] ?? null }

const submitUpload = (docType) => {
  if (!uploadForm.value.file) return
  uploading.value = true
  uploadErrors.value = {}

  const data = new FormData()
  data.append('document_type', docType)
  data.append('file', uploadForm.value.file)
  if (uploadForm.value.document_name) data.append('document_name', uploadForm.value.document_name)
  if (uploadForm.value.description)   data.append('description',   uploadForm.value.description)
  if (uploadForm.value.validity_date) data.append('validity_date', uploadForm.value.validity_date)

  router.post(route('beneficiary.documents.upload'), data, {
    forceFormData: true,
    onSuccess: () => { cancelUpload() },
    onError:   (errors) => { uploadErrors.value = errors },
    onFinish:  () => { uploading.value = false },
  })
}

// ── Delete state ─────────────────────────────────────────────────────────────
const deletingDoc = ref(null)
const confirmDelete = (doc) => { deletingDoc.value = doc }
const executeDelete = () => {
  if (!deletingDoc.value) return
  router.delete(route('beneficiary.documents.delete', deletingDoc.value.id), {
    onSuccess: () => { deletingDoc.value = null },
  })
}

// ── Status helpers ────────────────────────────────────────────────────────────
const statusBg        = (v) => v ? 'bg-success-50'  : 'bg-slate-50'
const statusIcon      = (v) => v ? CheckCircleIcon   : ClockIcon
const statusIconColor = (v) => v ? 'text-success-600': 'text-slate-300'
const statusBadge     = (v) => v ? 'badge-success'   : 'badge-neutral'
const formatDate      = (d) =>
  d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.96); }
</style>
