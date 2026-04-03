<template>
  <div class="min-h-screen flex items-center justify-center p-4"
       style="background: linear-gradient(135deg, #003087 0%, #0051a8 50%, #1e40af 100%);">
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
      <div class="absolute top-20 right-20 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 left-20 w-80 h-80 bg-blue-300/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 w-full max-w-lg">
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
            <div class="text-center text-sm text-slate-500 mb-4">
              Point your camera at the QR code on your 4Ps ID card
            </div>
            <div class="relative bg-slate-900 rounded-2xl overflow-hidden aspect-square max-w-xs mx-auto">
              <div id="qr-reader" class="w-full h-full"></div>
              <!-- Scanning overlay -->
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
import { ref, onMounted, onUnmounted } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import {
  QrCodeIcon, IdentificationIcon,
  LockClosedIcon, EyeIcon, EyeSlashIcon, ExclamationCircleIcon,
} from '@heroicons/vue/24/outline'

const activeTab    = ref('id')
const showPassword = ref(false)
const scanning     = ref(false)
const qrError      = ref('')
let html5QrCode    = null

const tabs = [
  { id: 'id', label: 'Unique ID',  icon: IdentificationIcon },
  { id: 'qr', label: 'Scan QR Code', icon: QrCodeIcon },
]

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

// Initialize QR scanner when QR tab is active
const initQrScanner = async () => {
  const { Html5Qrcode } = await import('html5-qrcode')
  html5QrCode = new Html5Qrcode('qr-reader')
  scanning.value = true
  qrError.value = ''

  try {
    await html5QrCode.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: { width: 200, height: 200 } },
      (decoded) => {
        scanning.value = false
        html5QrCode.stop()
        // Set decoded payload and switch to form tab for password entry
        form.identifier = decoded
        activeTab.value = 'id'
      },
      () => {} // ignore scan errors
    )
  } catch (err) {
    qrError.value = 'Camera not available. Please use Unique ID instead.'
    scanning.value = false
  }
}

onUnmounted(() => {
  if (html5QrCode) {
    html5QrCode.stop().catch(() => {})
  }
})

import { watch } from 'vue'
watch(activeTab, (tab) => {
  if (tab === 'qr') {
    setTimeout(initQrScanner, 100)
  } else if (html5QrCode) {
    html5QrCode.stop().catch(() => {})
    scanning.value = false
  }
})
</script>
