<template>
  <Head title="FDS Scanner" />
  <StaffLayout page-title="FDS Attendance Scanner" page-subtitle="Scan beneficiary QR codes at Family Development Sessions">
    <div class="max-w-2xl mx-auto space-y-5">

      <!-- Session config -->
      <div class="card p-5 bg-blue-50/60 border-blue-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="label text-blue-700">Period</label>
            <select v-model="sessionConfig.period" class="input w-full text-sm">
              <option v-for="p in periods" :key="p.value" :value="p.value">{{ p.label }}</option>
            </select>
          </div>
          <div>
            <label class="label text-blue-700">Session Title</label>
            <input v-model="sessionConfig.session_title" type="text" class="input w-full text-sm"
                   placeholder="e.g. FDS Module 3" />
          </div>
          <div>
            <label class="label text-blue-700">Venue</label>
            <input v-model="sessionConfig.venue" type="text" class="input w-full text-sm"
                   placeholder="e.g. Barangay Hall" />
          </div>
        </div>
      </div>

      <!-- Scan Mode Toggle -->
      <div class="card p-4">
        <p class="text-xs text-slate-400 font-medium uppercase tracking-wide mb-3">Scan Mode</p>
        <div class="grid grid-cols-2 gap-3">
          <button
            @click="scanMode = 'check_in'"
            :class="[
              'flex flex-col items-center gap-2 p-4 rounded-2xl border-2 transition-all',
              scanMode === 'check_in'
                ? 'border-emerald-500 bg-emerald-50 shadow-sm'
                : 'border-slate-200 bg-white hover:border-slate-300'
            ]"
          >
            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', scanMode === 'check_in' ? 'bg-emerald-100' : 'bg-slate-100']">
              <ArrowRightStartOnRectangleIcon :class="['w-5 h-5', scanMode === 'check_in' ? 'text-emerald-600' : 'text-slate-400']" />
            </div>
            <span :class="['text-sm font-bold', scanMode === 'check_in' ? 'text-emerald-700' : 'text-slate-500']">Check-In</span>
            <span class="text-[10px] text-slate-400">Entry scan</span>
          </button>

          <button
            @click="scanMode = 'check_out'"
            :class="[
              'flex flex-col items-center gap-2 p-4 rounded-2xl border-2 transition-all',
              scanMode === 'check_out'
                ? 'border-orange-500 bg-orange-50 shadow-sm'
                : 'border-slate-200 bg-white hover:border-slate-300'
            ]"
          >
            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', scanMode === 'check_out' ? 'bg-orange-100' : 'bg-slate-100']">
              <ArrowLeftStartOnRectangleIcon :class="['w-5 h-5', scanMode === 'check_out' ? 'text-orange-600' : 'text-slate-400']" />
            </div>
            <span :class="['text-sm font-bold', scanMode === 'check_out' ? 'text-orange-700' : 'text-slate-500']">Check-Out</span>
            <span class="text-[10px] text-slate-400">Exit scan</span>
          </button>
        </div>
      </div>

      <!-- Today's Stats -->
      <div class="grid grid-cols-3 gap-3">
        <div class="card p-3 text-center">
          <p class="text-xl font-bold text-emerald-700">{{ stats.checked_in }}</p>
          <p class="text-[10px] text-slate-400 font-medium mt-0.5">Checked In</p>
        </div>
        <div class="card p-3 text-center">
          <p class="text-xl font-bold text-orange-700">{{ stats.checked_out }}</p>
          <p class="text-[10px] text-slate-400 font-medium mt-0.5">Checked Out</p>
        </div>
        <div class="card p-3 text-center">
          <p class="text-xl font-bold text-blue-700">{{ stats.complete }}</p>
          <p class="text-[10px] text-slate-400 font-medium mt-0.5">Complete</p>
        </div>
      </div>

      <!-- Scanner card -->
      <div :class="[
        'card overflow-hidden border-2 transition-colors',
        scanMode === 'check_in' ? 'border-emerald-200' : 'border-orange-200'
      ]">
        <div class="p-6 text-center space-y-4">
          <div :class="[
            'w-16 h-16 mx-auto rounded-2xl flex items-center justify-center transition-colors',
            scanState === 'success' ? 'bg-emerald-100' :
            scanState === 'error'   ? 'bg-red-100' :
            scanMode === 'check_in' ? 'bg-emerald-50' : 'bg-orange-50'
          ]">
            <QrCodeIcon :class="[
              'w-8 h-8 transition-colors',
              scanState === 'success' ? 'text-emerald-600' :
              scanState === 'error'   ? 'text-red-600' :
              scanMode === 'check_in' ? 'text-emerald-500' : 'text-orange-500'
            ]" />
          </div>

          <div>
            <h2 class="text-lg font-bold text-slate-700">
              {{ scanMode === 'check_in' ? 'Scan for Check-In' : 'Scan for Check-Out' }}
            </h2>
            <p class="text-sm text-slate-400">
              {{ scanMode === 'check_in'
                ? 'Scan the beneficiary\'s QR code upon arrival.'
                : 'Scan the beneficiary\'s QR code after the FDS session.'
              }}
            </p>
          </div>

          <!-- Manual input -->
          <div class="flex gap-2 max-w-md mx-auto">
            <input v-model="manualId" type="text" class="input flex-1" ref="scanInput"
                   placeholder="4PS-LPA-000001 or scan QR..."
                   @keydown.enter="submitScan"
                   :disabled="scanning" />
            <button @click="submitScan" :disabled="!manualId.trim() || scanning"
                    :class="[
                      'btn gap-1.5 shrink-0',
                      scanMode === 'check_in' ? 'btn-primary' : 'bg-orange-600 hover:bg-orange-500 text-white'
                    ]">
              <MagnifyingGlassIcon class="w-4 h-4" />
              {{ scanning ? 'Scanning...' : scanMode === 'check_in' ? 'Check In' : 'Check Out' }}
            </button>
          </div>
        </div>

        <!-- Result display -->
        <Transition name="slide">
          <div v-if="lastResult" class="border-t border-slate-100">
            <!-- Success -->
            <div v-if="lastResult.success" :class="[
              'p-6',
              lastResult.scan_type === 'check_out' ? 'bg-blue-50' : 'bg-emerald-50'
            ]">
              <div class="flex items-start gap-4">
                <div :class="[
                  'w-14 h-14 rounded-xl flex items-center justify-center shrink-0',
                  lastResult.scan_type === 'check_out' ? 'bg-blue-100' : 'bg-emerald-100'
                ]">
                  <CheckCircleIcon :class="[
                    'w-8 h-8',
                    lastResult.scan_type === 'check_out' ? 'text-blue-600' : 'text-emerald-600'
                  ]" />
                </div>
                <div class="flex-1">
                  <h3 :class="[
                    'text-lg font-bold',
                    lastResult.scan_type === 'check_out' ? 'text-blue-800' : 'text-emerald-800'
                  ]">{{ lastResult.message }}</h3>
                  <div class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                    <div>
                      <span class="text-slate-500 font-medium">Name:</span>
                      <span class="text-slate-700 ml-1">{{ lastResult.beneficiary?.full_name }}</span>
                    </div>
                    <div>
                      <span class="text-slate-500 font-medium">ID:</span>
                      <span class="text-slate-700 ml-1 font-mono">{{ lastResult.beneficiary?.unique_id }}</span>
                    </div>
                    <div>
                      <span class="text-slate-500 font-medium">Barangay:</span>
                      <span class="text-slate-700 ml-1">{{ lastResult.beneficiary?.barangay }}</span>
                    </div>
                    <div v-if="lastResult.attendance?.checked_in_at">
                      <span class="text-slate-500 font-medium">In:</span>
                      <span class="text-emerald-700 ml-1 font-medium">{{ lastResult.attendance.checked_in_at }}</span>
                    </div>
                    <div v-if="lastResult.attendance?.checked_out_at">
                      <span class="text-slate-500 font-medium">Out:</span>
                      <span class="text-blue-700 ml-1 font-medium">{{ lastResult.attendance.checked_out_at }}</span>
                    </div>
                    <div v-if="lastResult.attendance?.is_complete">
                      <span class="badge badge-success badge-sm">✓ Complete</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Error -->
            <div v-else class="p-6 bg-red-50">
              <div class="flex items-start gap-4">
                <div class="w-14 h-14 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
                  <ExclamationCircleIcon class="w-8 h-8 text-red-600" />
                </div>
                <div>
                  <h3 class="text-lg font-bold text-red-800">{{ lastResult.message }}</h3>
                  <p v-if="lastResult.beneficiary" class="text-sm text-red-600 mt-1">
                    {{ lastResult.beneficiary.full_name }} ({{ lastResult.beneficiary.unique_id }})
                  </p>
                  <p v-if="lastResult.checked_in_at" class="text-xs text-red-500 mt-0.5">
                    Checked in at {{ lastResult.checked_in_at }}
                  </p>
                  <p v-if="lastResult.checked_out_at" class="text-xs text-red-500 mt-0.5">
                    Checked out at {{ lastResult.checked_out_at }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </div>

      <!-- Recent Scans -->
      <div class="card overflow-hidden">
        <div class="card-header">
          <h3 class="font-semibold text-slate-800 text-sm">Recent Scans Today</h3>
        </div>
        <div v-if="recentScans.length === 0" class="p-8 text-center text-slate-400 text-sm">
          No scans yet today.
        </div>
        <div v-else class="divide-y divide-slate-50">
          <div v-for="scan in recentScans" :key="scan.id"
               class="flex items-center gap-3 px-5 py-3">
            <div :class="[
              'w-8 h-8 rounded-lg flex items-center justify-center shrink-0',
              scan.is_complete ? 'bg-blue-100' : 'bg-emerald-100'
            ]">
              <CheckCircleIcon v-if="scan.is_complete" class="w-4 h-4 text-blue-600" />
              <ArrowRightStartOnRectangleIcon v-else class="w-4 h-4 text-emerald-600" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-slate-700 truncate">{{ scan.beneficiary }}</p>
              <p class="text-[10px] text-slate-400">{{ scan.unique_id }}</p>
            </div>
            <div class="text-right shrink-0">
              <span :class="['badge badge-sm', scan.is_complete ? 'badge-success' : 'badge-warning']">
                {{ scan.is_complete ? 'Complete' : 'Checked In' }}
              </span>
              <p class="text-[10px] text-slate-400 mt-0.5">{{ scan.time }}</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import {
  QrCodeIcon, MagnifyingGlassIcon, CheckCircleIcon,
  ExclamationCircleIcon,
  ArrowRightStartOnRectangleIcon, ArrowLeftStartOnRectangleIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  periods:       Array,
  currentPeriod: Object,
  todayStats:    Object,
})

const scanMode = ref('check_in')
const manualId = ref('')
const scanning = ref(false)
const scanState = ref('idle') // idle | success | error
const lastResult = ref(null)
const scanInput = ref(null)
const recentScans = ref([])

const stats = reactive({
  checked_in:  props.todayStats?.checked_in ?? 0,
  checked_out: props.todayStats?.checked_out ?? 0,
  complete:    props.todayStats?.complete ?? 0,
})

const sessionConfig = reactive({
  period:        props.currentPeriod?.value ?? props.periods?.[0]?.value ?? '',
  session_title: '',
  venue:         '',
})

const submitScan = async () => {
  if (!manualId.value.trim() || scanning.value) return
  scanning.value  = true
  scanState.value = 'idle'
  lastResult.value = null

  // Determine route based on page context
  const scanRoute = route('admin4ps.fds.scan', {}, false)
    ? route('admin4ps.fds.scan')
    : route('fds.scan', {}, false)
      ? route('fds.scan')
      : route('barangay.scan')

  try {
    const res = await axios.post(scanRoute, {
      payload:       manualId.value.trim(),
      scan_type:     scanMode.value,
      period:        sessionConfig.period,
      session_title: sessionConfig.session_title,
      venue:         sessionConfig.venue,
    })
    lastResult.value = res.data
    scanState.value  = 'success'

    // Update stats
    if (scanMode.value === 'check_in') stats.checked_in++
    if (scanMode.value === 'check_out') { stats.checked_out++; stats.complete++ }

    // Add to recent scans
    recentScans.value.unshift({
      id: res.data.attendance?.id ?? Date.now(),
      beneficiary: res.data.beneficiary?.full_name ?? '—',
      unique_id: res.data.beneficiary?.unique_id ?? '—',
      is_complete: res.data.attendance?.is_complete ?? false,
      time: res.data.attendance?.checked_in_at ?? res.data.attendance?.checked_out_at ?? 'now',
    })
    if (recentScans.value.length > 20) recentScans.value.pop()
  } catch (err) {
    lastResult.value = err.response?.data ?? { success: false, message: 'Network error.' }
    scanState.value = 'error'
  } finally {
    scanning.value = false
    manualId.value = ''
    scanInput.value?.focus()
  }
}

onMounted(() => scanInput.value?.focus())
</script>

<style scoped>
.slide-enter-active { transition: all 0.3s ease; }
.slide-leave-active { transition: all 0.2s ease; }
.slide-enter-from   { opacity: 0; max-height: 0; }
.slide-leave-to     { opacity: 0; max-height: 0; }
</style>
