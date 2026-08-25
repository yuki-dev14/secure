<template>
  <Head title="Bulk Import Beneficiaries" />
  <StaffLayout
    page-title="Bulk Import Beneficiaries"
    page-subtitle="Upload an Excel file to register multiple beneficiaries at once"
  >
    <div class="max-w-3xl mx-auto space-y-5">

      <!-- Info banner -->
      <div class="alert alert-info">
        <InformationCircleIcon class="w-5 h-5 shrink-0" />
        <div class="text-sm">
          <p class="font-semibold mb-1">Before you upload:</p>
          <ul class="list-disc ml-4 space-y-0.5">
            <li>Download the official Excel template below and fill it in.</li>
            <li>All imported beneficiaries are set to <strong>INACTIVE</strong> — activate them individually after verifying documents.</li>
            <li>Rows with missing required fields or duplicate Listahanan IDs will be skipped.</li>
            <li>Supports up to 5 family members per beneficiary (columns P–BC).</li>
            <li>Maximum file size: <strong>10 MB</strong>. Accepted formats: <strong>.xlsx, .xls, .csv</strong>.</li>
          </ul>
        </div>
      </div>

      <!-- Template download -->
      <div class="card">
        <div class="card-header">
          <div class="flex items-center gap-2">
            <TableCellsIcon class="w-5 h-5 text-success-600" />
            <h3 class="font-semibold text-slate-800">Step 1 — Download Template</h3>
          </div>
        </div>
        <div class="card-body">
          <p class="text-sm text-slate-500 mb-4">
            Use the official template to ensure correct column structure. The file includes a sample row and an Instructions sheet with valid values for each field.
          </p>
          <div class="flex flex-wrap gap-3">
            <a :href="route('superadmin.beneficiaries.import.template')"
              class="btn btn-success inline-flex items-center gap-2">
              <ArrowDownTrayIcon class="w-4 h-4" />
              Download Excel Template
            </a>
            <a href="/uat_beneficiaries_sample.csv" download="uat_beneficiaries_sample.csv"
              class="btn btn-secondary inline-flex items-center gap-2">
              <ArrowDownTrayIcon class="w-4 h-4 text-brand-600" />
              Download UAT Sample Data (.csv)
            </a>
          </div>
        </div>
      </div>

      <!-- Upload form -->
      <div class="card">
        <div class="card-header">
          <div class="flex items-center gap-2">
            <ArrowUpTrayIcon class="w-5 h-5 text-brand-600" />
            <h3 class="font-semibold text-slate-800">Step 2 — Upload Filled File</h3>
          </div>
        </div>
        <div class="card-body">
          <form @submit.prevent="submitImport" class="space-y-5">

            <!-- Drop zone -->
            <div
              class="border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer"
              :class="[
                isDragging ? 'border-brand-400 bg-brand-50' : 'border-slate-200 hover:border-brand-300 hover:bg-slate-50',
                form.file ? 'border-success-400 bg-success-50' : ''
              ]"
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="handleDrop"
              @click="$refs.fileInput.click()"
            >
              <input
                ref="fileInput"
                type="file"
                accept=".xlsx,.xls,.csv"
                class="hidden"
                @change="handleFileChange"
              />

              <div v-if="!form.file">
                <DocumentArrowUpIcon class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                <p class="font-medium text-slate-600">Click to browse or drag & drop</p>
                <p class="text-sm text-slate-400 mt-1">.xlsx, .xls, or .csv — max 10 MB</p>
              </div>

              <div v-else class="flex items-center justify-center gap-3">
                <DocumentCheckIcon class="w-10 h-10 text-success-500" />
                <div class="text-left">
                  <p class="font-semibold text-success-700">{{ form.file.name }}</p>
                  <p class="text-sm text-success-500">{{ fileSizeLabel }} — Ready to import</p>
                </div>
                <button type="button" @click.stop="clearFile"
                  class="btn btn-ghost btn-sm text-slate-400 ml-4">
                  <XMarkIcon class="w-4 h-4" />
                </button>
              </div>
            </div>

            <p v-if="form.errors.file" class="form-error">{{ form.errors.file }}</p>

            <!-- Submit -->
            <div class="flex items-center gap-3">
              <button
                type="submit"
                :disabled="!form.file || form.processing"
                class="btn btn-primary"
              >
                <ArrowUpTrayIcon class="w-4 h-4" />
                {{ form.processing ? 'Importing… please wait' : 'Import Beneficiaries' }}
              </button>
              <a :href="route('superadmin.beneficiaries.index')" class="btn btn-secondary">
                ← Back to List
              </a>
            </div>
          </form>
        </div>
      </div>

      <!-- Result panel — shown whenever there are import success results OR skipped rows -->
      <div v-if="$page.props.flash?.success || skippedRows.length > 0" class="card border-slate-200 shadow-sm">
        <div class="card-header" :class="skippedRows.length > 0 ? 'bg-amber-50/80 border-b border-amber-200' : 'bg-success-50/80 border-b border-success-200'">
          <div class="flex items-center gap-2">
            <CheckBadgeIcon v-if="skippedRows.length === 0" class="w-5 h-5 text-success-600" />
            <ExclamationTriangleIcon v-else class="w-5 h-5 text-amber-600" />
            <h3 class="font-semibold text-slate-800">
              Import Summary & Row Rejection Details
            </h3>
          </div>
        </div>
        <div class="card-body space-y-4">
          <p v-if="$page.props.flash?.success" class="text-sm font-medium text-slate-700">
            {{ $page.props.flash.success }}
          </p>

          <!-- Skipped rows table -->
          <div v-if="skippedRows.length > 0">
            <div class="flex items-center justify-between mb-2">
              <p class="text-sm font-bold text-amber-800 flex items-center gap-1.5">
                <ExclamationTriangleIcon class="w-4 h-4 text-amber-600" />
                {{ skippedRows.length }} Row(s) Skipped / Rejected
              </p>
              <span class="text-xs text-slate-500">Exact row numbers and rejection reasons below</span>
            </div>

            <div class="overflow-x-auto rounded-xl border border-amber-200 bg-white">
              <table class="w-full text-sm">
                <thead class="bg-amber-50 text-amber-900 border-b border-amber-200">
                  <tr>
                    <th class="px-4 py-2.5 text-left font-semibold">Excel Row #</th>
                    <th class="px-4 py-2.5 text-left font-semibold">Listahanan ID</th>
                    <th class="px-4 py-2.5 text-left font-semibold">Candidate Name</th>
                    <th class="px-4 py-2.5 text-left font-semibold">Exact Rejection Reason / Missing Fields</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-amber-100">
                  <tr v-for="s in skippedRows" :key="s.row" class="hover:bg-amber-50/40 transition-colors">
                    <td class="px-4 py-2.5 font-mono text-xs font-bold text-slate-700">
                      Row {{ s.row }}
                    </td>
                    <td class="px-4 py-2.5 font-mono text-xs text-slate-600">
                      {{ s.listahanan_id || 'N/A' }}
                    </td>
                    <td class="px-4 py-2.5 text-slate-800 font-medium">
                      {{ s.name || 'N/A' }}
                    </td>
                    <td class="px-4 py-2.5 text-slate-600 text-xs">
                      <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium"
                        :class="s.reason?.includes('Duplicate') ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-red-50 text-red-700 border border-red-200'">
                        {{ s.reason }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="pt-2 flex items-center gap-3">
            <a :href="route('superadmin.beneficiaries.index')" class="btn btn-primary btn-sm">
              View Beneficiaries List →
            </a>
          </div>
        </div>
      </div>

    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import {
  InformationCircleIcon, TableCellsIcon, ArrowDownTrayIcon,
  ArrowUpTrayIcon, DocumentArrowUpIcon, DocumentCheckIcon,
  XMarkIcon, CheckBadgeIcon, ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const page       = usePage()
const isDragging = ref(false)

const form = useForm({ file: null })

const skippedRows = computed(() => page.props.flash?.skipped ?? [])

const fileSizeLabel = computed(() => {
  if (!form.file) return ''
  const kb = form.file.size / 1024
  return kb >= 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${Math.round(kb)} KB`
})

const handleFileChange = (e) => {
  const file = e.target.files[0]
  if (file) form.file = file
}

const handleDrop = (e) => {
  isDragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) form.file = file
}

const clearFile = () => {
  form.file = null
}

const submitImport = () => {
  form.post(route('superadmin.beneficiaries.import.store'), {
    forceFormData: true,
  })
}
</script>
