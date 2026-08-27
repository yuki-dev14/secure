<template>
  <div class="min-h-screen flex items-center justify-center p-4"
       style="background: linear-gradient(135deg, #330000 0%, #4d0000 50%, #800000 100%);">
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
      <div class="absolute top-20 right-20 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 left-20 w-80 h-80 bg-red-300/10 rounded-full blur-3xl"></div>
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
        <img src="/logo.png" alt="SECURE 4Ps Logo" class="w-16 h-16 object-contain mx-auto mb-4" />
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
              <div class="text-center space-y-1 mb-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-semibold border border-emerald-200 shadow-sm">
                  <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                  {{ scanning ? 'Camera Active — Align QR Code' : 'Initializing Camera…' }}
                </div>
                <p class="text-xs text-slate-500 mt-1">
                  Point your camera at the QR code on your 4Ps ID card
                </p>
                <p class="text-[11px] text-brand-600 font-medium">
                  First time? Scan to sign in and set your password
                </p>
              </div>

              <!-- Sleek Scanner Viewport -->
              <div class="relative bg-slate-950 rounded-2xl overflow-hidden shadow-xl border-2 border-brand-700/40 max-w-xs mx-auto aspect-square group">
                <div id="qr-reader" class="w-full h-full"></div>

                <!-- Custom High-Tech Overlay -->
                <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                  <!-- Target box frame -->
                  <div class="relative w-56 h-56 rounded-xl border border-white/20">
                    <!-- Corner Accent Brackets -->
                    <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-brand-500 rounded-tl-lg shadow-[0_0_10px_#ef4444]"></div>
                    <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-brand-500 rounded-tr-lg shadow-[0_0_10px_#ef4444]"></div>
                    <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-brand-500 rounded-bl-lg shadow-[0_0_10px_#ef4444]"></div>
                    <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-brand-500 rounded-br-lg shadow-[0_0_10px_#ef4444]"></div>

                    <!-- Glowing Scanning Laser Line -->
                    <div v-if="scanning" class="scanner-laser"></div>
                  </div>
                </div>
              </div>

              <p v-if="qrError" class="text-center text-sm text-danger-600 font-medium bg-red-50 p-2.5 rounded-xl border border-red-200">
                {{ qrError }}
              </p>

              <!-- Upload QR Image option -->
              <div class="pt-2 text-center">
                <input type="file" ref="fileInput" accept="image/*" class="hidden" @change="handleFileUpload" />
                <button type="button" @click="triggerFileInput"
                  class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors border border-slate-200">
                  <ArrowUpTrayIcon class="w-4 h-4 text-brand-600" />
                  Upload QR Image from device
                </button>
                <div id="qr-reader-file-temp" class="hidden"></div>
              </div>
            </template>

            <!-- Logging in (auto-submit in progress) -->
            <template v-else-if="qrScanning">
              <div class="flex flex-col items-center justify-center py-12 gap-4">
                <div class="w-16 h-16 rounded-full bg-brand-50 flex items-center justify-center shadow-inner">
                  <svg class="w-8 h-8 text-brand-600 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                  </svg>
                </div>
                <div class="text-center">
                  <p class="text-base font-bold text-slate-800">QR Code Verified!</p>
                  <p class="text-xs text-slate-500 mt-1">Authenticating your beneficiary account…</p>
                </div>
              </div>
            </template>

            <!-- Login error -->
            <template v-else-if="qrLoginError">
              <div class="flex flex-col items-center gap-4 py-8">
                <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center shadow-inner">
                  <ExclamationCircleIcon class="w-8 h-8 text-danger-600" />
                </div>
                <div class="text-center max-w-xs">
                  <p class="text-base font-bold text-slate-800">Scan Unsuccessful</p>
                  <p class="text-xs text-slate-600 mt-1">{{ qrLoginError }}</p>
                </div>
                <button @click="resetQrScan" class="btn btn-primary text-sm px-6 py-2.5 rounded-xl shadow-md">
                  Try Scanning Again
                </button>
              </div>
            </template>

            <div class="pt-3 border-t border-slate-100 text-center">
              <p class="text-xs text-slate-500">
                Having trouble scanning?
                <button @click="activeTab = 'id'" class="text-brand-600 hover:underline font-semibold">
                  Sign in with Unique ID & Password
                </button>
              </p>
            </div>
          </div>

          <!-- Manual ID + Password Tab -->
          <div v-else class="space-y-5">
            <!-- QR-verified banner: shown when redirected after QR gate -->
            <div v-if="qrVerifiedBanner"
              class="flex items-start gap-3 bg-success-50 border border-success-200 rounded-xl px-4 py-3">
              <span class="text-success-600 text-lg leading-none mt-0.5">✓</span>
              <div>
                <p class="text-sm font-semibold text-success-700">QR Code Verified!</p>
                <p class="text-xs text-success-600 mt-0.5">
                  Your identity was confirmed via QR. Please enter your personal password to complete sign-in.
                </p>
              </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
              <!-- Hidden when QR-verified — ID already pre-filled, no need to show it -->
              <div v-if="!qrVerifiedBanner">
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
                    autofocus
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
          </div>

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
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useForm, router, Link } from '@inertiajs/vue3'
import {
  QrCodeIcon, IdentificationIcon,
  LockClosedIcon, EyeIcon, EyeSlashIcon, ExclamationCircleIcon, ArrowLeftIcon, ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  qr_id: { type: String, default: null },
})

const activeTab     = ref('qr')
const showPassword  = ref(false)
const scanning      = ref(false)
const qrScanning    = ref(false)
const qrError       = ref('')
const qrLoginError  = ref('')
const qrVerifiedBanner = ref(false)
const fileInput     = ref(null)
let html5QrCode     = null

const tabs = [
  { id: 'qr',  label: 'Scan QR Card', icon: QrCodeIcon },
  { id: 'id',  label: 'Unique ID + Password', icon: IdentificationIcon },
]

// Manual ID + Password form
const form = useForm({
  identifier: '',
  password:   '',
  remember:   false,
})

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileUpload = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  qrError.value = ''
  try {
    const { Html5Qrcode } = await import('html5-qrcode')
    const html5QrCodeFile = new Html5Qrcode('qr-reader-file-temp')
    const decoded = await html5QrCodeFile.scanFile(file, true)
    handleQrDecoded(decoded)
  } catch (err) {
    qrError.value = 'Could not read a valid QR code from this file. Please ensure image is clear or use camera scanner.'
  }
}

// FRESH PAGE LOAD: onMounted fires when page loads with ?qr_id= in URL (e.g. after browser refresh)
onMounted(() => {
  if (typeof window !== 'undefined') {
    window.history.pushState(null, null, window.location.href);
    window.onpopstate = function () {
      window.history.pushState(null, null, window.location.href);
    };
  }

  if (props.qr_id) {
    form.identifier        = props.qr_id
    activeTab.value        = 'id'
    qrVerifiedBanner.value = true
  } else {
    initQrScanner()
  }
})

// INERTIA CLIENT-SIDE NAVIGATION: when QR scan redirects to same page with new qr_id prop,
// onMounted doesn't re-fire — this watch catches the prop update instead.
watch(() => props.qr_id, async (qrId) => {
  if (qrId) {
    await stopScanner()              // stop camera if it was running
    form.identifier        = qrId
    activeTab.value        = 'id'
    qrVerifiedBanner.value = true
  }
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
      onSuccess: () => {
        qrScanning.value = false
      },
      onError: (errors) => {
        qrScanning.value  = false
        qrLoginError.value = errors.payload ?? 'QR login failed. Please try again.'
      },
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
      { fps: 15, qrbox: { width: 220, height: 220 } },
      (decoded) => handleQrDecoded(decoded),
      () => {} // ignore per-frame errors
    )
  } catch (err) {
    qrError.value  = 'Camera permission not granted or device camera unavailable.'
    scanning.value = false
  }
}

onUnmounted(stopScanner)

watch(activeTab, async (tab) => {
  if (tab === 'qr') {
    qrLoginError.value = ''
    qrScanning.value   = false
    // Don't restart scanner if we came from QR gate redirect
    if (!qrVerifiedBanner.value) {
      setTimeout(initQrScanner, 100)
    }
  } else {
    await stopScanner()
    scanning.value = false
  }
})
</script>

<style>
#qr-reader {
  border: none !important;
  background: transparent !important;
}
#qr-reader video {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
  border-radius: 1rem !important;
}
#qr-reader img {
  display: none !important;
}
#qr-reader__scan_region {
  background: transparent !important;
}
#qr-reader__dashboard {
  display: none !important;
}

.scanner-laser {
  position: absolute;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent, #ef4444 30%, #f59e0b 50%, #ef4444 70%, transparent);
  box-shadow: 0 0 12px #ef4444, 0 0 4px #ef4444;
  animation: laser-sweep 2.2s ease-in-out infinite;
}

@keyframes laser-sweep {
  0% {
    top: 5%;
    opacity: 0.3;
  }
  50% {
    top: 92%;
    opacity: 1;
  }
  100% {
    top: 5%;
    opacity: 0.3;
  }
}
</style>
