<template>
  <div class="min-h-screen flex items-center justify-center p-4"
       style="background: linear-gradient(135deg, #003087 0%, #0051a8 50%, #1e40af 100%);">
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
      <div class="absolute top-20 right-20 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 left-20 w-80 h-80 bg-blue-300/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 w-full max-w-lg">
      <!-- Back to home -->
      <div class="mb-5">
        <Link :href="route('home')" class="inline-flex items-center gap-1.5 text-white/70 hover:text-white text-sm font-medium transition-colors group">
          <ArrowLeftIcon class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" />
          Back to Home
        </Link>
      </div>
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4">
          <QrCodeIcon class="w-8 h-8 text-white" />
        </div>
        <h1 class="text-2xl font-bold text-white">Beneficiary Portal</h1>
        <p class="text-white/70 text-sm mt-1">Pantawid Pamilyang Pilipino Program (4Ps)</p>
        <p class="text-white/50 text-xs mt-0.5">Lipa City, Batangas</p>
      </div>

      <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
        <!-- Tabs: QR or ID -->
        <div class="flex border-b border-slate-100">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'flex-1 py-4 text-sm font-medium flex items-center justify-center gap-2 transition-all',
              activeTab === tab.id
                ? 'text-brand-600 border-b-2 border-brand-600 bg-brand-50/50'
                : 'text-slate-500 hover:text-slate-700'
            ]"
          >
            <component :is="tab.icon" class="w-4 h-4" />
            {{ tab.label }}
          </button>
        </div>

        <div class="p-8">
          <!-- QR Scan Tab -->
          <div v-if="activeTab === 'qr'" class="space-y-4">

            <!-- Scanning view -->
            <template v-if="!qrScanning && !qrLoginError">
              <div class="text-center text-sm text-slate-500 mb-4">
                Point your camera at the QR code on your 4Ps ID card
              </div>
              <div class="relative bg-slate-900 rounded-2xl overflow-hidden aspect-square max-w-xs mx-auto">
                <div id="qr-reader" class="w-full h-full"></div>
                <!-- Scanning frame overlay -->
                <div class="absolute inset-0 pointer-events-none">
                  <div class="absolute inset-6 border-2 border-white/30 rounded-xl"></div>
                  <div class="absolute top-6 left-6 w-6 h-6 border-t-2 border-l-2 border-brand-400 rounded-tl-lg"></div>
                  <div class="absolute top-6 right-6 w-6 h-6 border-t-2 border-r-2 border-brand-400 rounded-tr-lg"></div>
                  <div class="absolute bottom-6 left-6 w-6 h-6 border-b-2 border-l-2 border-brand-400 rounded-bl-lg"></div>
                  <div class="absolute bottom-6 right-6 w-6 h-6 border-b-2 border-r-2 border-brand-400 rounded-br-lg"></div>
                  <div v-if="scanning" class="absolute inset-x-6 h-0.5 bg-brand-400 shadow-[0_0_8px_#6366f1] animate-bounce top-1/2"></div>
                </div>
              </div>
              <p v-if="qrError" class="text-center text-sm text-danger-600">{{ qrError }}</p>
            </template>

            <!-- Logging in (auto-submit in progress) -->
            <template v-else-if="qrScanning">
              <div class="flex flex-col items-center justify-center py-10 gap-4">
                <div class="w-14 h-14 rounded-full bg-brand-50 flex items-center justify-center">
                  <svg class="w-7 h-7 text-brand-600 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                  </svg>
                </div>
                <div class="text-center">
                  <p class="text-sm font-semibold text-slate-700">QR Code Detected</p>
                  <p class="text-xs text-slate-400 mt-1">Signing you in…</p>
                </div>
              </div>
            </template>

            <!-- Login error (invalid QR, inactive account, etc.) -->
            <template v-else-if="qrLoginError">
              <div class="flex flex-col items-center gap-4 py-6">
                <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center">
                  <ExclamationCircleIcon class="w-6 h-6 text-danger-600" />
                </div>
                <div class="text-center">
                  <p class="text-sm font-semibold text-slate-700">Login Failed</p>
                  <p class="text-xs text-slate-500 mt-1">{{ qrLoginError }}</p>
                </div>
                <button @click="resetQrScan" class="btn btn-outline text-sm px-5 py-2">
                  Try Again
                </button>
              </div>
            </template>

            <p class="text-center text-xs text-slate-400">
              Or
              <button @click="activeTab = 'id'" class="text-brand-600 hover:underline font-medium">enter your Unique ID manually</button>
            </p>
          </div>

          <!-- Manual ID Tab -->
          <form v-else @submit.prevent="submit" class="space-y-5">
            <div>
              <label class="form-label" for="identifier">4Ps Unique ID</label>
              <input
                id="identifier"
                v-model="form.identifier"
                type="text"
                placeholder="e.g. 4PS-LPA-000001"
                class="form-input uppercase tracking-widest font-mono"
                :class="{ 'border-danger-500': form.errors.identifier }"
                required
              />
              <p v-if="form.errors.identifier" class="form-error">
                <ExclamationCircleIcon class="w-3.5 h-3.5" />
                {{ form.errors.identifier }}
              </p>
              <p class="form-hint">Found on your DSWD-issued ID card</p>
            </div>

            <div>
              <label class="form-label" for="password">Password</label>
              <div class="relative">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="••••••••"
                  class="form-input pr-10"
                  required
                />
                <button type="button" @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                  <EyeIcon v-if="!showPassword" class="w-4 h-4" />
                  <EyeSlashIcon v-else class="w-4 h-4" />
                </button>
              </div>
              <p v-if="form.errors.password" class="form-error">
                <ExclamationCircleIcon class="w-3.5 h-3.5" />
                {{ form.errors.password }}
              </p>
            </div>

            <button type="submit" :disabled="form.processing" class="btn btn-primary w-full btn-lg">
              <LockClosedIcon class="w-4 h-4" />
              {{ form.processing ? 'Signing in…' : 'Access My Account' }}
            </button>
          </form>

          <div class="mt-6 pt-4 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-400">
              Staff login?
              <Link :href="route('staff.login')" class="text-brand-600 hover:underline font-medium">Click here</Link>
            </p>
          </div>
        </div>
      </div>

      <p class="text-center text-white/40 text-xs mt-6">
        DSWD Lipa City — Data Privacy Act Compliant
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue'
import { useForm, router, Link } from '@inertiajs/vue3'
import {
  QrCodeIcon, IdentificationIcon,
  LockClosedIcon, EyeIcon, EyeSlashIcon, ExclamationCircleIcon, ArrowLeftIcon,
} from '@heroicons/vue/24/outline'

const activeTab     = ref('id')
const showPassword  = ref(false)
const scanning      = ref(false)       // camera is active
const qrScanning    = ref(false)       // auto-login request in flight
const qrError       = ref('')          // camera init error
const qrLoginError  = ref('')          // server-side QR rejection
let html5QrCode     = null

const tabs = [
  { id: 'id',  label: 'Unique ID',    icon: IdentificationIcon },
  { id: 'qr',  label: 'Scan QR Code', icon: QrCodeIcon },
]

// Manual ID + Password form
const form = useForm({
  identifier: '',
  password:   '',
  remember:   false,
})

const submit = () => {
  form.post(route('beneficiary.login.post'), {
    onFinish: () => form.reset('password'),
  })
}

/** Stop and destroy the scanner instance. */
const stopScanner = async () => {
  if (html5QrCode) {
    await html5QrCode.stop().catch(() => {})
    html5QrCode = null
  }
}

/** Reset QR state so the user can re-scan. */
const resetQrScan = async () => {
  qrLoginError.value = ''
  qrScanning.value   = false
  await stopScanner()
  setTimeout(initQrScanner, 150)
}

/** Called when a QR code is successfully decoded — auto-login immediately. */
const handleQrDecoded = async (payload) => {
  await stopScanner()
  scanning.value   = false
  qrScanning.value = true  // show spinner

  router.post(
    route('beneficiary.qr-login.post'),
    { payload },
    {
      onError: (errors) => {
        qrScanning.value  = false
        qrLoginError.value = errors.payload ?? 'QR login failed. Please try again.'
      },
      // onSuccess: Inertia will redirect automatically
    }
  )
}

/** Start the html5-qrcode scanner. */
const initQrScanner = async () => {
  qrError.value    = ''
  scanning.value   = true

  try {
    const { Html5Qrcode } = await import('html5-qrcode')
    html5QrCode = new Html5Qrcode('qr-reader')

    await html5QrCode.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: { width: 200, height: 200 } },
      (decoded) => handleQrDecoded(decoded),
      () => {} // ignore per-frame errors
    )
  } catch (err) {
    qrError.value  = 'Camera not available. Please use Unique ID instead.'
    scanning.value = false
  }
}

onUnmounted(stopScanner)

watch(activeTab, async (tab) => {
  if (tab === 'qr') {
    qrLoginError.value = ''
    qrScanning.value   = false
    setTimeout(initQrScanner, 100)
  } else {
    await stopScanner()
    scanning.value = false
  }
})
</script>
