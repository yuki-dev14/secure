<template>
  <Head title="QR Scanner" />
  <StaffLayout page-title="QR Scanner" page-subtitle="Scan beneficiary card to verify identity and compliance">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

      <!-- Left: Scanner -->
      <div class="space-y-4">
        <!-- Active Event Banner -->
        <div v-if="activeEvent" class="alert alert-info">
          <CalendarDaysIcon class="w-5 h-5 flex-shrink-0" />
          <div>
            <p class="font-semibold">Active Event: {{ activeEvent.title }}</p>
            <p class="text-xs mt-0.5">{{ activeEvent.venue }} • {{ activeEvent.period }}</p>
          </div>
        </div>
        <div v-else class="alert alert-warning">
          <ExclamationTriangleIcon class="w-5 h-5 flex-shrink-0" />
          <p>No active distribution event. Contact your administrator.</p>
        </div>

        <!-- Scanner Card -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-slate-800">Scan Beneficiary QR Code</h3>
            <div class="flex items-center gap-2">
              <span :class="['badge', scanning ? 'badge-success' : 'badge-neutral']">
                {{ scanning ? '● Live' : '○ Idle' }}
              </span>
            </div>
          </div>
          <div class="card-body space-y-4">
            <!-- Camera view -->
            <div class="relative bg-slate-900 rounded-2xl overflow-hidden" style="aspect-ratio: 4/3;">
              <div id="qr-scanner-view" class="w-full h-full"></div>

              <!-- Overlay corners -->
              <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                <div class="relative w-48 h-48">
                  <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-brand-400 rounded-tl-lg"></div>
                  <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-brand-400 rounded-tr-lg"></div>
                  <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-brand-400 rounded-bl-lg"></div>
                  <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-brand-400 rounded-br-lg"></div>
                  <div v-if="scanning" class="absolute inset-x-0 top-1/2 h-0.5 bg-brand-400 shadow-[0_0_12px_2px_rgba(99,102,241,0.7)] animate-bounce"></div>
                </div>
              </div>

              <!-- Camera error -->
              <div v-if="cameraError" class="absolute inset-0 flex items-center justify-center bg-slate-800 p-6">
                <div class="text-center text-white">
                  <CameraIcon class="w-12 h-12 opacity-40 mx-auto mb-3" />
                  <p class="text-sm opacity-70">{{ cameraError }}</p>
                </div>
              </div>
            </div>

            <!-- Manual input fallback -->
            <div>
              <label class="form-label">Or enter Unique ID manually</label>
              <div class="flex gap-2">
                <input
                  v-model="manualId"
                  type="text"
                  placeholder="4PS-LPA-000001"
                  class="form-input flex-1 uppercase font-mono tracking-widest"
                  @keyup.enter="searchManual"
                />
                <button @click="searchManual" :disabled="loading" class="btn btn-primary">
                  <MagnifyingGlassIcon class="w-4 h-4" />
                  Search
                </button>
              </div>
            </div>

            <!-- Camera toggle -->
            <div class="flex gap-2">
              <button @click="toggleScanner" class="btn btn-secondary flex-1">
                <VideoCameraIcon v-if="!scanning" class="w-4 h-4" />
                <VideoCameraSlashIcon v-else class="w-4 h-4" />
                {{ scanning ? 'Stop Camera' : 'Start Camera' }}
              </button>
              <button v-if="result" @click="clearResult" class="btn btn-ghost">
                <XMarkIcon class="w-4 h-4" />
                Clear
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Result panel -->
      <div class="space-y-4">
        <!-- Loading -->
        <div v-if="loading" class="card">
          <div class="card-body text-center py-12">
            <div class="animate-spin w-10 h-10 border-4 border-brand-600 border-t-transparent rounded-full mx-auto mb-4"></div>
            <p class="text-slate-500">Verifying beneficiary…</p>
          </div>
        </div>

        <!-- Error -->
        <div v-else-if="scanError" class="card border-danger-200">
          <div class="card-body text-center py-10">
            <div class="w-16 h-16 bg-danger-50 rounded-full flex items-center justify-center mx-auto mb-4">
              <XCircleIcon class="w-8 h-8 text-danger-600" />
            </div>
            <h3 class="font-semibold text-slate-800 mb-1">Verification Failed</h3>
            <p class="text-sm text-danger-600">{{ scanError }}</p>
            <button @click="clearResult" class="btn btn-secondary mt-4">Try Again</button>
          </div>
        </div>

        <!-- Already Claimed Warning -->
        <div v-else-if="result?.already_claimed" class="card border-danger-300">
          <div class="card-body">
            <div class="alert alert-danger mb-4">
              <ShieldExclamationIcon class="w-5 h-5 flex-shrink-0" />
              <div>
                <p class="font-bold">DOUBLE CLAIM DETECTED</p>
                <p class="text-xs">This beneficiary has already claimed their grant for this event. This incident has been logged.</p>
              </div>
            </div>
            <BeneficiaryCard :beneficiary="result.beneficiary" readonly />
          </div>
        </div>

        <!-- Success Result -->
        <template v-else-if="result">
          <!-- Beneficiary Info -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Beneficiary Verified</h3>
              <span :class="['badge', result.beneficiary.is_compliant ? 'badge-success' : 'badge-danger']">
                {{ result.beneficiary.is_compliant ? '✓ Compliant' : '✗ Non-Compliant' }}
              </span>
            </div>
            <div class="card-body">
              <div class="flex gap-4">
                <div class="w-20 h-20 rounded-xl overflow-hidden bg-slate-100 flex-shrink-0">
                  <img v-if="result.beneficiary.photo_url"
                    :src="result.beneficiary.photo_url"
                    :alt="result.beneficiary.full_name"
                    class="w-full h-full object-cover"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                    <UserIcon class="w-8 h-8" />
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="text-lg font-bold text-slate-800">{{ result.beneficiary.full_name }}</h4>
                  <p class="text-sm text-slate-500 font-mono">{{ result.beneficiary.unique_id }}</p>
                  <p class="text-sm text-slate-500 mt-1">Brgy. {{ result.beneficiary.barangay }}</p>
                  <p class="text-sm text-slate-500">
                    {{ result.beneficiary.sex === 'male' ? 'Male' : 'Female' }}, {{ result.beneficiary.age }} yrs old
                  </p>
                </div>
              </div>

              <!-- Grant Amount -->
              <div v-if="result.grant" class="mt-4 p-4 bg-brand-50 rounded-xl border border-brand-200">
                <p class="text-xs text-brand-600 font-semibold uppercase tracking-wider mb-1">Grant Amount</p>
                <p class="text-3xl font-bold text-brand-700">
                  ₱{{ Number(result.grant.total_grant_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                </p>
                <p class="text-xs text-brand-500 mt-1">For {{ result.grant.months_covered }} months</p>
              </div>

              <!-- Documents summary -->
              <div class="mt-4">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Submitted Documents</p>
                <div class="flex flex-wrap gap-2">
                  <span v-for="doc in result.documents" :key="doc.id"
                    :class="['badge', doc.is_verified ? 'badge-success' : 'badge-neutral']"
                  >
                    {{ doc.type_label }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Proxies -->
          <div v-if="result.proxies?.length" class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Authorized Proxies</h3>
              <span class="badge badge-info">{{ result.proxies.length }} registered</span>
            </div>
            <div class="card-body space-y-3">
              <div v-for="proxy in result.proxies" :key="proxy.id"
                class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                <UserGroupIcon class="w-8 h-8 text-slate-400 flex-shrink-0" />
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-slate-700">{{ proxy.full_name }}</p>
                  <p class="text-xs text-slate-500 capitalize">{{ proxy.relationship }}</p>
                </div>
                <span :class="['badge badge-sm', proxy.has_docs ? 'badge-success' : 'badge-warning']">
                  {{ proxy.has_docs ? 'Docs OK' : 'Missing Docs' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Release Button -->
          <div v-if="result.beneficiary.is_compliant && !result.already_claimed && activeEvent" class="card">
            <div class="card-body">
              <p class="text-sm font-semibold text-slate-700 mb-3">Release Cash Grant</p>
              <div class="space-y-3">
                <div>
                  <label class="form-label">Claimed By</label>
                  <select v-model="releaseForm.claimed_by_type" class="form-select">
                    <option value="beneficiary">Beneficiary (Self)</option>
                    <option v-for="proxy in result.proxies" :key="proxy.id" :value="proxy.id + '___proxy'">
                      Proxy: {{ proxy.full_name }}
                    </option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Verification Notes</label>
                  <textarea v-model="releaseForm.verification_notes" class="form-input" rows="2"
                    placeholder="ID verified, documents checked…"></textarea>
                </div>
                <button @click="release" :disabled="releasing" class="btn btn-success w-full btn-lg">
                  <BanknotesIcon class="w-5 h-5" />
                  {{ releasing ? 'Recording…' : 'Confirm Release — ₱' + Number(result.grant?.total_grant_amount ?? 0).toLocaleString() }}
                </button>
              </div>
            </div>
          </div>

          <div v-else-if="!result.beneficiary.is_compliant" class="alert alert-danger">
            <ExclamationTriangleIcon class="w-5 h-5 flex-shrink-0" />
            <p>Beneficiary is <strong>non-compliant</strong>. Grant cannot be released. Refer to Compliance Verifier.</p>
          </div>
        </template>

        <!-- Empty state -->
        <div v-else class="card">
          <div class="card-body text-center py-16">
            <QrCodeIcon class="w-16 h-16 text-slate-200 mx-auto mb-4" />
            <p class="text-slate-400 font-medium">No beneficiary scanned yet</p>
            <p class="text-sm text-slate-300 mt-1">Scan a QR code or enter a Unique ID</p>
          </div>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import {
  CalendarDaysIcon, ExclamationTriangleIcon, QrCodeIcon,
  CameraIcon, VideoCameraIcon, VideoCameraSlashIcon,
  XMarkIcon, XCircleIcon, ShieldExclamationIcon,
  MagnifyingGlassIcon, UserIcon, UserGroupIcon,
  BanknotesIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import BeneficiaryCard from '@/Components/BeneficiaryCard.vue'

const props = defineProps({
  activeEvent: Object,
})

const scanning   = ref(false)
const loading    = ref(false)
const releasing  = ref(false)
const scanError  = ref('')
const cameraError = ref('')
const result     = ref(null)
const manualId   = ref('')
let html5QrCode  = null

const releaseForm = ref({
  claimed_by_type:      'beneficiary',
  proxy_id:             null,
  verification_notes:   '',
})

const toggleScanner = async () => {
  if (scanning.value) {
    await html5QrCode?.stop()
    scanning.value = false
    return
  }

  const { Html5Qrcode } = await import('html5-qrcode')
  html5QrCode = new Html5Qrcode('qr-scanner-view')
  cameraError.value = ''

  try {
    await html5QrCode.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: { width: 220, height: 220 } },
      async (decoded) => {
        await html5QrCode.stop()
        scanning.value = false
        await processPayload(decoded)
      },
      () => {}
    )
    scanning.value = true
  } catch {
    cameraError.value = 'Camera unavailable. Use manual ID entry.'
  }
}

const processPayload = async (payload) => {
  loading.value = true
  scanError.value = ''
  result.value = null

  try {
    const resp = await axios.post(route('officer.scanner.scan'), {
      payload,
      event_id: props.activeEvent?.id ?? null,
    })
    result.value = resp.data
  } catch (e) {
    scanError.value = e.response?.data?.message ?? 'Scan failed. Try again.'
  } finally {
    loading.value = false
  }
}

const searchManual = async () => {
  if (!manualId.value.trim()) return
  // Build a fake "unique_id" payload for the unique_id path
  await processPayload(manualId.value.trim().toUpperCase())
}

const clearResult = () => {
  result.value  = null
  scanError.value = ''
  manualId.value = ''
}

const release = async () => {
  if (!result.value || !props.activeEvent) return
  releasing.value = true

  const claimed_by_type = releaseForm.value.claimed_by_type.includes('___proxy') ? 'proxy' : 'beneficiary'
  const proxy_id = releaseForm.value.claimed_by_type.includes('___proxy')
    ? parseInt(releaseForm.value.claimed_by_type) : null

  try {
    await axios.post(route('officer.distribution.release'), {
      beneficiary_id:             result.value.beneficiary.id,
      distribution_event_id:      props.activeEvent.id,
      cash_grant_calculation_id:  result.value.grant?.id ?? null,
      claimed_by_type,
      proxy_id,
      amount_released:            result.value.grant?.total_grant_amount ?? 0,
      payment_mode:               'cash',
      verification_notes:         releaseForm.value.verification_notes,
    })
    result.value.already_claimed = true
    clearResult()
    alert('✅ Cash grant successfully recorded!')
  } catch (e) {
    alert('❌ ' + (e.response?.data?.message ?? 'Failed to record grant.'))
  } finally {
    releasing.value = false
  }
}

onUnmounted(() => { html5QrCode?.stop().catch(() => {}) })
</script>
