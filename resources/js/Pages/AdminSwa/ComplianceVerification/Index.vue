<template>
  <Head title="Compliance Verification" />
  <StaffLayout page-title="Compliance Verification" page-subtitle="Send beneficiary lists to School Reps & Midwives, import compliance results">
    <div class="space-y-6">

      <!-- NC Summary Pills -->
      <div class="flex flex-wrap gap-3">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-violet-50 border border-violet-100">
          <AcademicCapIcon class="w-4 h-4 text-violet-500" />
          <span class="text-xs text-violet-500 uppercase tracking-wide">Education NC</span>
          <span class="text-lg font-bold text-violet-700">{{ ncSummary.education_nc }}</span>
        </div>
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-50 border border-emerald-100">
          <HeartIcon class="w-4 h-4 text-emerald-500" />
          <span class="text-xs text-emerald-500 uppercase tracking-wide">Health NC</span>
          <span class="text-lg font-bold text-emerald-700">{{ ncSummary.health_nc }}</span>
        </div>
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-50 border border-red-100">
          <ExclamationTriangleIcon class="w-4 h-4 text-red-500" />
          <span class="text-xs text-red-400 uppercase tracking-wide">Total NC</span>
          <span class="text-lg font-bold text-red-600">{{ ncSummary.total_nc }}</span>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- SECTION 1: Send Verification Lists                                 -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div class="card">
        <div class="p-5 border-b border-slate-100">
          <h2 class="text-lg font-bold text-slate-700 flex items-center gap-2">
            <EnvelopeIcon class="w-5 h-5 text-brand-500" />
            Send Verification Lists
          </h2>
          <p class="text-xs text-slate-400 mt-0.5">
            Generate Excel files with beneficiary lists and email them to School Representatives or Midwives
          </p>
        </div>

        <div class="p-5 space-y-5">
          <!-- Period selector -->
          <div class="max-w-xs">
            <label class="label">Period <span class="text-red-500">*</span></label>
            <select v-model="sendForm.period" class="input w-full">
              <option value="">Select period...</option>
              <option v-for="p in periods" :key="p.value" :value="p.value">{{ p.label }}</option>
            </select>
          </div>

          <!-- Two cards: Education & Health -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <!-- Education Card -->
            <div class="rounded-2xl border-2 border-violet-100 bg-gradient-to-br from-violet-50/50 to-white p-5 space-y-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center">
                  <AcademicCapIcon class="w-5 h-5 text-violet-600" />
                </div>
                <div>
                  <h3 class="font-semibold text-slate-700">Education Compliance</h3>
                  <p class="text-xs text-slate-400">
                    <span class="font-semibold text-violet-600">{{ eduCount }}</span> beneficiaries with school-age children
                  </p>
                </div>
              </div>

              <div class="space-y-3">
                <div>
                  <label class="label text-xs">Recipient Email <span class="text-red-500">*</span></label>
                  <input v-model="sendForm.edu_email" type="email" class="input w-full"
                         placeholder="school.representative@email.com" />
                </div>
                <div>
                  <label class="label text-xs">Recipient Name</label>
                  <input v-model="sendForm.edu_name" type="text" class="input w-full"
                         placeholder="e.g. Maria Santos" />
                </div>
              </div>

              <div class="flex items-center gap-2 pt-1">
                <button @click="sendVerification('education')"
                        :disabled="!canSendEducation || sendingEdu"
                        class="btn bg-violet-600 text-white hover:bg-violet-700 gap-2 flex-1">
                  <PaperAirplaneIcon class="w-4 h-4" />
                  {{ sendingEdu ? 'Sending...' : 'Generate & Send' }}
                </button>
                <a :href="templateUrl('education')"
                   :class="['btn btn-secondary btn-sm gap-1', !sendForm.period ? 'pointer-events-none opacity-50' : '']">
                  <ArrowDownTrayIcon class="w-4 h-4" />
                  Download
                </a>
              </div>
            </div>

            <!-- Health Card -->
            <div class="rounded-2xl border-2 border-emerald-100 bg-gradient-to-br from-emerald-50/50 to-white p-5 space-y-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                  <HeartIcon class="w-5 h-5 text-emerald-600" />
                </div>
                <div>
                  <h3 class="font-semibold text-slate-700">Health & Nutrition Compliance</h3>
                  <p class="text-xs text-slate-400">
                    <span class="font-semibold text-emerald-600">{{ healthCount }}</span> beneficiaries with under-5 / pregnant members
                  </p>
                </div>
              </div>

              <div class="space-y-3">
                <div>
                  <label class="label text-xs">Recipient Email <span class="text-red-500">*</span></label>
                  <input v-model="sendForm.health_email" type="email" class="input w-full"
                         placeholder="midwife@email.com" />
                </div>
                <div>
                  <label class="label text-xs">Recipient Name</label>
                  <input v-model="sendForm.health_name" type="text" class="input w-full"
                         placeholder="e.g. Ana Reyes" />
                </div>
              </div>

              <div class="flex items-center gap-2 pt-1">
                <button @click="sendVerification('health')"
                        :disabled="!canSendHealth || sendingHealth"
                        class="btn bg-emerald-600 text-white hover:bg-emerald-700 gap-2 flex-1">
                  <PaperAirplaneIcon class="w-4 h-4" />
                  {{ sendingHealth ? 'Sending...' : 'Generate & Send' }}
                </button>
                <a :href="templateUrl('health')"
                   :class="['btn btn-secondary btn-sm gap-1', !sendForm.period ? 'pointer-events-none opacity-50' : '']">
                  <ArrowDownTrayIcon class="w-4 h-4" />
                  Download
                </a>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- SECTION 2: Import Returned Results                                 -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div class="card">
        <div class="p-5 border-b border-slate-100">
          <h2 class="text-lg font-bold text-slate-700 flex items-center gap-2">
            <ArrowUpTrayIcon class="w-5 h-5 text-amber-500" />
            Import Compliance Results
          </h2>
          <p class="text-xs text-slate-400 mt-0.5">
            Import the returned Excel with non-compliant beneficiaries flagged by the School Rep or Midwife
          </p>
        </div>

        <div class="p-5 space-y-5">
          <!-- Info box -->
          <div class="flex gap-3 p-4 rounded-xl bg-amber-50 border border-amber-100">
            <InformationCircleIcon class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
            <div class="text-sm text-amber-700 space-y-1">
              <p class="font-medium">How this works:</p>
              <ul class="text-xs list-disc list-inside space-y-0.5">
                <li>Upload the returned Excel file from the School Rep or Midwife.</li>
                <li>Only rows marked as <strong>NON_COMPLIANT</strong> will be processed.</li>
                <li>Non-compliance records will be <strong>auto-confirmed</strong> and grants will be zeroed out.</li>
                <li>Compliant beneficiaries (default) will be left unchanged.</li>
              </ul>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="label">Period <span class="text-red-500">*</span></label>
              <select v-model="importForm.period" class="input w-full">
                <option value="">Select period...</option>
                <option v-for="p in periods" :key="p.value" :value="p.value">{{ p.label }}</option>
              </select>
            </div>

            <div>
              <label class="label">Category <span class="text-red-500">*</span></label>
              <div class="flex gap-3 mt-1">
                <label class="flex-1 cursor-pointer">
                  <input type="radio" v-model="importForm.category" value="education" class="sr-only peer" />
                  <div class="p-2.5 rounded-xl border-2 border-slate-200 peer-checked:border-violet-500 peer-checked:bg-violet-50 text-center transition-all">
                    <AcademicCapIcon class="w-4 h-4 mx-auto text-violet-500 mb-0.5" />
                    <span class="text-xs font-medium">Education</span>
                  </div>
                </label>
                <label class="flex-1 cursor-pointer">
                  <input type="radio" v-model="importForm.category" value="health" class="sr-only peer" />
                  <div class="p-2.5 rounded-xl border-2 border-slate-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 text-center transition-all">
                    <HeartIcon class="w-4 h-4 mx-auto text-emerald-500 mb-0.5" />
                    <span class="text-xs font-medium">Health</span>
                  </div>
                </label>
              </div>
            </div>

            <div>
              <label class="label">Link to Sent Batch</label>
              <select v-model="importForm.batch_id" class="input w-full">
                <option value="">None (standalone import)</option>
                <option v-for="b in sentBatches" :key="b.id" :value="b.id">
                  {{ b.category === 'education' ? '📚' : '💚' }} {{ b.period }} → {{ b.recipient_email }}
                </option>
              </select>
            </div>
          </div>

          <!-- Drop zone -->
          <div @drop.prevent="onDrop" @dragover.prevent="isDragging = true" @dragleave="isDragging = false"
               :class="['border-2 border-dashed rounded-2xl p-8 text-center transition-all cursor-pointer',
                 isDragging ? 'border-brand-400 bg-brand-50' : 'border-slate-200 hover:border-brand-300 hover:bg-slate-25']"
               @click="$refs.fileInput.click()">
            <input ref="fileInput" type="file" class="hidden" accept=".xlsx,.xls,.csv"
                   @change="onFileSelect" />

            <div v-if="!importForm.file">
              <ArrowUpTrayIcon class="w-8 h-8 mx-auto text-slate-300 mb-2" />
              <p class="text-sm text-slate-500">Drag & drop the returned Excel file here</p>
              <p class="text-xs text-slate-400 mt-1">or click to browse · .xlsx, .xls, .csv · Max 5 MB</p>
            </div>

            <div v-else class="flex items-center justify-center gap-3">
              <DocumentIcon class="w-7 h-7 text-brand-500" />
              <div class="text-left">
                <p class="text-sm font-medium text-slate-700">{{ importForm.file.name }}</p>
                <p class="text-xs text-slate-400">{{ (importForm.file.size / 1024).toFixed(1) }} KB</p>
              </div>
              <button @click.stop="importForm.file = null" class="p-1 rounded-full hover:bg-slate-100">
                <XMarkIcon class="w-4 h-4 text-slate-400" />
              </button>
            </div>
          </div>

          <div class="flex justify-end">
            <button @click="submitImport" :disabled="!canImport || importForm.processing"
                    class="btn btn-primary gap-2">
              <ArrowUpTrayIcon class="w-4 h-4" />
              {{ importForm.processing ? 'Importing...' : 'Import Results' }}
            </button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- SECTION 3: Generate Superadmin Report                              -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div class="card">
        <div class="p-5 border-b border-slate-100">
          <h2 class="text-lg font-bold text-slate-700 flex items-center gap-2">
            <DocumentChartBarIcon class="w-5 h-5 text-red-500" />
            Generate Report for Superadmin
          </h2>
          <p class="text-xs text-slate-400 mt-0.5">
            Export a summary report of non-compliant beneficiaries for Superadmin review
          </p>
        </div>

        <div class="p-5">
          <div class="flex flex-wrap items-end gap-4">
            <div>
              <label class="label">Period <span class="text-red-500">*</span></label>
              <select v-model="reportPeriod" class="input">
                <option value="">Select period...</option>
                <option v-for="p in periods" :key="p.value" :value="p.value">{{ p.label }}</option>
              </select>
            </div>
            <a :href="reportUrl('education')"
               :class="['btn bg-violet-600 text-white hover:bg-violet-700 gap-2',
                 !reportPeriod ? 'pointer-events-none opacity-50' : '']">
              <AcademicCapIcon class="w-4 h-4" />
              Education NC Report
            </a>
            <a :href="reportUrl('health')"
               :class="['btn bg-emerald-600 text-white hover:bg-emerald-700 gap-2',
                 !reportPeriod ? 'pointer-events-none opacity-50' : '']">
              <HeartIcon class="w-4 h-4" />
              Health NC Report
            </a>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- SECTION 4: Verification History                                    -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div class="card">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h2 class="text-lg font-bold text-slate-700 flex items-center gap-2">
              <ClockIcon class="w-5 h-5 text-slate-400" />
              Verification History
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Past verification batches sent and imported</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="px-4 py-3 text-left">Period</th>
                <th class="px-4 py-3 text-left">Category</th>
                <th class="px-4 py-3 text-left">Recipient</th>
                <th class="px-4 py-3 text-center">Beneficiaries</th>
                <th class="px-4 py-3 text-center">Non-Compliant</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Sent</th>
                <th class="px-4 py-3 text-left">Imported</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-if="history.data.length === 0">
                <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                  No verification batches yet. Start by sending a verification list above.
                </td>
              </tr>
              <tr v-for="batch in history.data" :key="batch.id"
                  class="hover:bg-slate-25 transition-colors">
                <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ batch.period }}</td>
                <td class="px-4 py-3">
                  <span :class="[batch.category === 'education' ? 'bg-violet-100 text-violet-700' : 'bg-emerald-100 text-emerald-700',
                        'px-2 py-0.5 rounded-full text-xs font-medium capitalize']">
                    {{ batch.category }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <p class="text-sm text-slate-700">{{ batch.recipient_email }}</p>
                  <p v-if="batch.recipient_name" class="text-xs text-slate-400">{{ batch.recipient_name }}</p>
                </td>
                <td class="px-4 py-3 text-center font-semibold text-slate-600">{{ batch.beneficiary_count }}</td>
                <td class="px-4 py-3 text-center">
                  <span v-if="batch.non_compliant_count !== null"
                        :class="batch.non_compliant_count > 0 ? 'text-red-600 font-bold' : 'text-emerald-600 font-semibold'">
                    {{ batch.non_compliant_count }}
                  </span>
                  <span v-else class="text-slate-400">—</span>
                </td>
                <td class="px-4 py-3">
                  <span :class="[batch.status === 'imported' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700',
                        'px-2.5 py-1 rounded-full text-xs font-semibold capitalize']">
                    {{ batch.status }}
                  </span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-500">
                  {{ formatDate(batch.sent_at) }}
                  <p v-if="batch.sender" class="text-slate-400">by {{ batch.sender.name }}</p>
                </td>
                <td class="px-4 py-3 text-xs text-slate-500">
                  <template v-if="batch.imported_at">
                    {{ formatDate(batch.imported_at) }}
                    <p v-if="batch.importer" class="text-slate-400">by {{ batch.importer.name }}</p>
                  </template>
                  <span v-else class="text-amber-500 font-medium">Awaiting return</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="history.last_page > 1" class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
          <span class="text-xs text-slate-400">
            Showing {{ history.from }}–{{ history.to }} of {{ history.total }}
          </span>
          <div class="flex gap-1">
            <Link v-for="link in history.links" :key="link.label"
                  :href="link.url ?? '#'" v-html="link.label"
                  :class="['px-3 py-1.5 text-xs rounded-lg transition-colors',
                    link.active ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50',
                    !link.url ? 'opacity-40 pointer-events-none' : '']" />
          </div>
        </div>
      </div>

    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import {
  AcademicCapIcon, HeartIcon, EnvelopeIcon, ArrowUpTrayIcon,
  ArrowDownTrayIcon, PaperAirplaneIcon, DocumentIcon, XMarkIcon,
  InformationCircleIcon, ExclamationTriangleIcon, DocumentChartBarIcon,
  ClockIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  periods:       Array,
  currentPeriod: Object,
  eduCount:      Number,
  healthCount:   Number,
  history:       Object,
  ncSummary:     Object,
  filters:       Object,
})

// ─── Send Form ──────────────────────────────────────────────────────────────

const sendForm = ref({
  period:       props.currentPeriod?.value ?? '',
  edu_email:    '',
  edu_name:     '',
  health_email: '',
  health_name:  '',
})

const sendingEdu    = ref(false)
const sendingHealth = ref(false)

const canSendEducation = computed(() =>
  sendForm.value.period && sendForm.value.edu_email && isValidEmail(sendForm.value.edu_email)
)

const canSendHealth = computed(() =>
  sendForm.value.period && sendForm.value.health_email && isValidEmail(sendForm.value.health_email)
)

const sendVerification = (category) => {
  const isEdu = category === 'education'
  if (isEdu) sendingEdu.value = true
  else sendingHealth.value = true

  router.post(route('adminswa.compliance-verification.send'), {
    period:          sendForm.value.period,
    category,
    recipient_email: isEdu ? sendForm.value.edu_email : sendForm.value.health_email,
    recipient_name:  isEdu ? sendForm.value.edu_name : sendForm.value.health_name,
  }, {
    onFinish: () => {
      if (isEdu) sendingEdu.value = false
      else sendingHealth.value = false
    },
    onSuccess: () => {
      if (isEdu) { sendForm.value.edu_email = ''; sendForm.value.edu_name = '' }
      else { sendForm.value.health_email = ''; sendForm.value.health_name = '' }
    },
  })
}

const templateUrl = (category) => {
  if (!sendForm.value.period) return '#'
  return route('adminswa.compliance-verification.template', {
    category,
    period: sendForm.value.period,
  })
}

// ─── Import Form ────────────────────────────────────────────────────────────

const importForm = useForm({
  file:     null,
  period:   props.currentPeriod?.value ?? '',
  category: '',
  batch_id: '',
})

const isDragging = ref(false)

const onDrop = (e) => {
  isDragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) importForm.file = file
}

const onFileSelect = (e) => {
  const file = e.target.files[0]
  if (file) importForm.file = file
}

const canImport = computed(() =>
  importForm.file && importForm.period && importForm.category
)

const submitImport = () => {
  importForm.post(route('adminswa.compliance-verification.import'), {
    forceFormData: true,
  })
}

// Sent batches available for linking (status = 'sent')
const sentBatches = computed(() =>
  (props.history?.data ?? []).filter(b => b.status === 'sent')
)

// ─── Report ─────────────────────────────────────────────────────────────────

const reportPeriod = ref(props.currentPeriod?.value ?? '')

const reportUrl = (category) => {
  if (!reportPeriod.value) return '#'
  return route('adminswa.compliance-verification.report', {
    period: reportPeriod.value,
    category,
  })
}

// ─── Helpers ────────────────────────────────────────────────────────────────

const isValidEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: 'numeric', minute: '2-digit',
  })
}
</script>
