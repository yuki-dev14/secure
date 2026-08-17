<template>
  <Head title="Import Non-Compliance Records" />
  <StaffLayout page-title="Import Non-Compliance" page-subtitle="Upload Google Forms extracts or Excel files with non-compliance flags">
    <div class="max-w-3xl mx-auto space-y-6">

      <!-- Instructions card -->
      <div class="card p-6 bg-amber-50 border-amber-200">
        <div class="flex gap-3">
          <InformationCircleIcon class="w-6 h-6 text-amber-600 shrink-0 mt-0.5" />
          <div>
            <h3 class="font-semibold text-amber-800">Import Instructions</h3>
            <ul class="mt-2 text-sm text-amber-700 space-y-1 list-disc list-inside">
              <li>Download the template, fill in non-compliant beneficiaries, then upload the file.</li>
              <li>The template expects columns: <code class="bg-amber-100 px-1 rounded">beneficiary_unique_id</code>, <code class="bg-amber-100 px-1 rounded">family_member_name</code>, <code class="bg-amber-100 px-1 rounded">reason</code>, <code class="bg-amber-100 px-1 rounded">details</code>, <code class="bg-amber-100 px-1 rounded">grant_affected</code>.</li>
              <li>Period, category, and source are set here — they apply to all rows in the file.</li>
              <li>Duplicates (same beneficiary + category + period) are automatically skipped.</li>
              <li>All imported records start as <strong>Pending</strong> for your review.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Import settings -->
      <div class="card p-6 space-y-5">
        <h2 class="text-lg font-bold text-slate-700 flex items-center gap-2">
          <CogIcon class="w-5 h-5 text-slate-400" />
          Import Settings
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
                  <AcademicCapIcon class="w-5 h-5 mx-auto text-violet-500 mb-1" />
                  <span class="text-sm font-medium">Education</span>
                </div>
              </label>
              <label class="flex-1 cursor-pointer">
                <input type="radio" v-model="form.category" value="health" class="sr-only peer" />
                <div class="p-3 rounded-xl border-2 border-slate-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 text-center transition-all">
                  <HeartIcon class="w-5 h-5 mx-auto text-emerald-500 mb-1" />
                  <span class="text-sm font-medium">Health</span>
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
            <input v-model="form.reporter_institution" type="text" class="input w-full"
                   placeholder="e.g. Anilao Elementary School" />
          </div>
        </div>
      </div>

      <!-- File upload -->
      <div class="card p-6 space-y-5">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-bold text-slate-700 flex items-center gap-2">
            <ArrowUpTrayIcon class="w-5 h-5 text-brand-500" />
            Upload File
          </h2>
          <a :href="route('adminswa.non-compliance.import.template')"
             class="btn btn-secondary btn-sm gap-1.5">
            <ArrowDownTrayIcon class="w-4 h-4" />
            Download Template
          </a>
        </div>

        <!-- Drop zone -->
        <div @drop.prevent="onDrop" @dragover.prevent="isDragging = true" @dragleave="isDragging = false"
             :class="['border-2 border-dashed rounded-2xl p-10 text-center transition-all cursor-pointer',
               isDragging ? 'border-brand-400 bg-brand-50' : 'border-slate-200 hover:border-brand-300 hover:bg-slate-25']"
             @click="$refs.fileInput.click()">
          <input ref="fileInput" type="file" class="hidden" accept=".xlsx,.xls,.csv"
                 @change="onFileSelect" />

          <div v-if="!form.file">
            <ArrowUpTrayIcon class="w-10 h-10 mx-auto text-slate-300 mb-3" />
            <p class="text-sm text-slate-500">Drag & drop your Excel/CSV file here</p>
            <p class="text-xs text-slate-400 mt-1">or click to browse · .xlsx, .xls, .csv · Max 5 MB</p>
          </div>

          <div v-else class="flex items-center justify-center gap-3">
            <DocumentIcon class="w-8 h-8 text-brand-500" />
            <div class="text-left">
              <p class="text-sm font-medium text-slate-700">{{ form.file.name }}</p>
              <p class="text-xs text-slate-400">{{ (form.file.size / 1024).toFixed(1) }} KB</p>
            </div>
            <button @click.stop="form.file = null" class="p-1 rounded-full hover:bg-slate-100">
              <XMarkIcon class="w-4 h-4 text-slate-400" />
            </button>
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
          <ArrowUpTrayIcon class="w-4 h-4" />
          {{ form.processing ? 'Importing...' : 'Import Records' }}
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
  ArrowUpTrayIcon, ArrowDownTrayIcon, CogIcon, AcademicCapIcon,
  HeartIcon, InformationCircleIcon, DocumentIcon, XMarkIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  periods:   Array,
  barangays: Array,
})

const form = useForm({
  file:                  null,
  period:                '',
  category:              '',
  source:                '',
  reporter_name:         '',
  reporter_institution:  '',
})

const isDragging = ref(false)

const onDrop = (e) => {
  isDragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) form.file = file
}

const onFileSelect = (e) => {
  const file = e.target.files[0]
  if (file) form.file = file
}

const isValid = computed(() =>
  form.file && form.period && form.category && form.source
)

const submit = () => {
  form.post(route('adminswa.non-compliance.import.store'), {
    forceFormData: true,
  })
}
</script>
