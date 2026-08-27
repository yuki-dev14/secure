<template>
  <Head title="My Dashboard" />
  <BeneficiaryLayout :unread-count="unread_count">

    <!-- Claiming Alert -->
    <div v-if="nextEvent" class="mb-6 p-4 rounded-2xl border-2 border-brand-300 bg-white/90 backdrop-blur-sm flex items-start gap-4 shadow-lg shadow-brand-500/10">
      <div class="w-12 h-12 bg-brand-600 rounded-xl flex items-center justify-center shrink-0">
        <BellAlertIcon class="w-6 h-6 text-white" />
      </div>
      <div class="flex-1">
        <p class="font-bold text-brand-800">{{ nextEvent.title }}</p>
        <p class="text-sm text-brand-600 mt-0.5">{{ nextEvent.details?.venue }} • Quarter {{ nextEvent.details?.period }}</p>
      </div>
      <span class="badge badge-info animate-pulse">OPEN</span>
    </div>

    <!-- Profile Card -->
    <div class="mb-6 bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
      <div style="background: linear-gradient(135deg, #700000 0%, #4a0000 100%);" class="px-6 py-5 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4 min-w-0">
          <div class="w-16 h-16 rounded-xl overflow-hidden bg-white/20 shrink-0 border-2 border-white/40">
            <img v-if="beneficiary.photo_path && !imageError"
              :src="`/storage/${beneficiary.photo_path}`" alt=""
              @error="imageError = true"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center bg-white/20">
              <UserIcon class="w-8 h-8 text-white/70" />
            </div>
          </div>
          <div class="min-w-0">
            <p class="text-white font-bold text-lg truncate">{{ beneficiary.full_name }}</p>
            <p class="text-white/70 text-sm font-mono">{{ beneficiary.unique_id }}</p>
            <div class="flex items-center gap-2 mt-1">
              <span :class="['badge badge-sm', beneficiary.is_compliant ? 'bg-green-400/20 text-green-200' : 'bg-red-400/20 text-red-200']">
                {{ beneficiary.is_compliant ? '✓ Compliant' : '✗ Non-Compliant' }}
              </span>
              <span class="badge badge-sm bg-white/20 text-white/80">{{ beneficiary.status }}</span>
            </div>
          </div>
        </div>

        <!-- View ID Card Action Button -->
        <button type="button" @click="showCardModal = true"
          class="shrink-0 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-xl border border-white/25 shadow transition-all flex items-center gap-2">
          <IdentificationIcon class="w-4 h-4 text-amber-300" />
          <span class="hidden sm:inline">View Digital ID Card</span>
          <span class="sm:hidden">ID Card</span>
        </button>
      </div>
      <div class="px-6 py-4 bg-slate-50 grid grid-cols-2 gap-4 text-sm">
        <div>
          <p class="text-xs text-slate-400 mb-0.5">Barangay</p>
          <p class="font-medium text-slate-700">Brgy. {{ beneficiary.barangay }}</p>
        </div>
        <div>
          <p class="text-xs text-slate-400 mb-0.5">Family Members</p>
          <p class="font-medium text-slate-700">{{ beneficiary.family_members?.length ?? 0 }} members</p>
        </div>
      </div>
    </div>

    <!-- Grant Breakdown -->
    <div v-if="breakdown" class="mb-6 bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h3 class="font-semibold text-slate-800">Grant Breakdown</h3>
          <p class="text-xs text-slate-400">Period: {{ breakdown?.quarter ?? '—' }}</p>
        </div>
        <div class="text-right">
          <p class="text-2xl font-bold text-brand-700">
            ₱{{ fmt(breakdown?.total) }}
          </p>
          <p class="text-xs text-slate-400">Total for {{ breakdown?.months_covered ?? 3 }} months</p>
        </div>
      </div>
      <div class="divide-y divide-slate-100">
        <div class="px-6 py-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-success-50 rounded-lg flex items-center justify-center">
              <HeartIcon class="w-4 h-4 text-success-600" />
            </div>
            <div>
              <p class="text-sm font-medium text-slate-700">Health Grant</p>
              <p class="text-xs text-slate-400">₱750 × {{ breakdown?.months_covered ?? 3 }} months</p>
            </div>
          </div>
          <p class="font-semibold text-slate-700">₱{{ fmt(breakdown?.health?.amount) }}</p>
        </div>

        <div v-if="(breakdown?.education?.total ?? 0) > 0" class="px-6 py-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-brand-50 rounded-lg flex items-center justify-center">
              <AcademicCapIcon class="w-4 h-4 text-brand-600" />
            </div>
            <div>
              <p class="text-sm font-medium text-slate-700">Education Grant</p>
              <p class="text-xs text-slate-400">
                <span v-if="breakdown?.education?.elementary?.count">{{ breakdown.education.elementary.count }}× Elem</span>
                <span v-if="breakdown?.education?.junior_high?.count"> {{ breakdown.education.junior_high.count }}× JHS</span>
                <span v-if="breakdown?.education?.senior_high?.count"> {{ breakdown.education.senior_high.count }}× SHS</span>
              </p>
            </div>
          </div>
          <p class="font-semibold text-slate-700">₱{{ fmt(breakdown?.education?.total) }}</p>
        </div>

        <div class="px-6 py-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-warning-50 rounded-lg flex items-center justify-center">
              <ShoppingBagIcon class="w-4 h-4 text-warning-600" />
            </div>
            <div>
              <p class="text-sm font-medium text-slate-700">Rice Subsidy</p>
              <p class="text-xs text-slate-400">₱600 × {{ breakdown?.months_covered ?? 3 }} months</p>
            </div>
          </div>
          <p class="font-semibold text-slate-700">₱{{ fmt(breakdown?.rice?.amount) }}</p>
        </div>
      </div>
    </div>



    <!-- Digital 4Ps ID Card Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showCardModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" @click="showCardModal = false"></div>
          <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 space-y-5 border border-slate-200">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <div class="flex items-center gap-2">
                <IdentificationIcon class="w-5 h-5 text-brand-600" />
                <h3 class="font-bold text-slate-800 text-base">Digital 4Ps Identification Card</h3>
              </div>
              <button @click="showCardModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <!-- 3D Card Scene (Identical to Superadmin CardPreview.vue) -->
            <div class="py-2 flex flex-col items-center">
              <div class="card-scene mx-auto" @click="cardFlipped = !cardFlipped">
                <div :class="['card-3d', { 'is-flipped': cardFlipped }]">

                  <!-- ═══════════ FRONT ═══════════ -->
                  <div class="card-face card-face--front" id="card-print-front">
                    <!-- Header band -->
                    <div class="cf-header">
                      <div class="cf-logo">
                        <img src="/logo.png" alt="Logo" class="w-full h-full object-contain p-0.5 rounded-full" />
                      </div>
                      <div class="cf-headtext">
                        <div class="cf-agency">Republic of the Philippines — DSWD</div>
                        <div class="cf-program">Pantawid Pamilyang Pilipino Program (4Ps)</div>
                      </div>
                      <div class="cf-badge">BENEFICIARY ID</div>
                    </div>

                    <!-- Body -->
                    <div class="cf-body">
                      <!-- Photo -->
                      <div class="cf-photo">
                        <img v-if="beneficiary.photo_path && !imageError"
                          :src="`/storage/${beneficiary.photo_path}`" alt="Photo"
                          @error="imageError = true" />
                        <div v-else class="cf-photo-placeholder">
                          <UserIcon class="w-8 h-8 text-white/70" />
                        </div>
                      </div>

                      <!-- Info -->
                      <div class="cf-info">
                        <div class="cf-name">
                          {{ upperLast }}, {{ beneficiary.first_name }}
                          <span v-if="beneficiary.middle_name" class="cf-middle">{{ beneficiary.middle_name }}</span>
                        </div>
                        <div class="cf-field">
                          <div class="cf-label">Birthdate</div>
                          <div class="cf-value">{{ formatDateLong(beneficiary.birthdate) }}</div>
                        </div>
                        <div class="cf-field">
                          <div class="cf-label">Address</div>
                          <div class="cf-value cf-small">
                            Brgy. {{ beneficiary.barangay }}, {{ beneficiary.city ?? 'Lipa City' }}, {{ beneficiary.province ?? 'Batangas' }}
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Footer -->
                    <div class="cf-footer">
                      <div>
                        <div class="cf-uidlabel">UNIQUE ID</div>
                        <div class="cf-uid">{{ beneficiary.unique_id }}</div>
                      </div>
                      <div class="cf-city">
                        <div class="cf-cityname">LIPA CITY</div>
                        <div>Batangas</div>
                      </div>
                    </div>
                  </div>

                  <!-- ═══════════ BACK ═══════════ -->
                  <div class="card-face card-face--back" id="card-print-back">
                    <div class="cb-header">
                      SECURE 4Ps — System for Eligibility Checking, Unified Records, and Evaluation
                    </div>

                    <div class="cb-body">
                      <!-- QR code -->
                      <div class="cb-qr-section">
                        <div class="cb-qr-box">
                          <img v-if="qr_base64" :src="qr_base64" alt="QR Code" />
                          <div v-else class="cb-qr-placeholder">QR CODE</div>
                        </div>
                        <div class="cb-qr-label">SCAN TO VERIFY</div>
                      </div>

                      <!-- Credentials -->
                      <div class="cb-creds">
                        <div class="cb-cred-row">
                          <div class="cb-cred-label">Card Number</div>
                          <div class="cb-cred-value cb-cred-small">{{ card?.card_number ?? '—' }}</div>
                        </div>
                        <div class="cb-cred-row">
                          <div class="cb-cred-label">Unique ID</div>
                          <div class="cb-cred-value">{{ beneficiary.unique_id }}</div>
                        </div>
                        <div class="cb-notice">
                          This card is government property. If found, please return to the nearest
                          DSWD office in Lipa City, Batangas. Unauthorized use is punishable by law.
                          Portal: secure4ps.dswd.gov.ph
                        </div>
                      </div>
                    </div>

                    <div class="cb-footer">
                      Issued by: DSWD Lipa City SWDO &bull; Card No: {{ card?.card_number ?? '—' }} &bull; Issued: {{ formatDateShort(card?.issued_at) }}
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <!-- Modal Footer Actions -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
              <button @click="cardFlipped = !cardFlipped" class="btn btn-secondary btn-sm gap-2 text-xs">
                <ArrowsRightLeftIcon class="w-4 h-4" />
                {{ cardFlipped ? 'See Front' : 'See Back / QR Code' }}
              </button>
              <button @click="showCardModal = false" class="btn btn-primary btn-sm text-xs px-5">
                Close
              </button>
            </div>

          </div>
        </div>
      </Transition>
    </Teleport>

  </BeneficiaryLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import {
  UserIcon, BellAlertIcon, HeartIcon,
  AcademicCapIcon, ShoppingBagIcon, IdentificationIcon,
  XMarkIcon, ArrowsRightLeftIcon,
} from '@heroicons/vue/24/outline'
import BeneficiaryLayout from '@/Layouts/BeneficiaryLayout.vue'

const props = defineProps({
  beneficiary:   Object,
  card:          Object,
  qr_base64:     String,
  breakdown:     Object,
  claim_history: Array,
  notifications: Array,
  unread_count:  Number,
})

const imageError    = ref(false)
const showCardModal = ref(false)
const cardFlipped   = ref(false)

const upperLast = computed(() =>
  (props.beneficiary?.last_name ?? '').toUpperCase()
)

const getNotifData = (n) => {
  if (!n?.data) return {}
  if (typeof n.data === 'object') return n.data
  try { return JSON.parse(n.data) } catch { return {} }
}

const nextEvent = computed(() => {
  if (!props.notifications?.length) return null
  const found = props.notifications.find(n => {
    const d = getNotifData(n)
    return (d.type === 'distribution_ongoing' || d.type === 'distribution_schedule') && !n.read_at
  })
  return found ? getNotifData(found) : null
})

const fmt = (val) => {
  const n = Number(val ?? 0)
  return isNaN(n) ? '0.00' : n.toLocaleString('en-PH', { minimumFractionDigits: 2 })
}

const formatDateLong = (d) =>
  d ? new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' }) : '—'

const formatDateShort = (d) =>
  d ? new Date(d).toLocaleDateString('en-PH', { month: '2-digit', day: '2-digit', year: 'numeric' }) : '—'
</script>

<style>
/* ── 3D Flip scene (Exact copy from Superadmin CardPreview.vue) ───────────── */
.card-scene {
  width: 324px;        /* 3.375in @ 96dpi */
  height: 204px;       /* 2.125in @ 96dpi */
  perspective: 900px;
  cursor: pointer;
  flex-shrink: 0;
}

.card-3d {
  width: 100%;
  height: 100%;
  position: relative;
  transform-style: preserve-3d;
  transition: transform 0.65s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.25);
}

.card-3d.is-flipped {
  transform: rotateY(180deg);
}

.card-face {
  position: absolute;
  inset: 0;
  border-radius: 12px;
  backface-visibility: hidden;
  -webkit-backface-visibility: hidden;
  overflow: hidden;
}

.card-face--back {
  transform: rotateY(180deg);
}

/* FRONT face styles */
.card-face--front {
  background: linear-gradient(135deg, #330000 0%, #660000 50%, #990000 100%);
  color: white;
  display: flex;
  flex-direction: column;
  font-family: Arial, sans-serif;
}

.cf-header {
  display: flex;
  align-items: center;
  padding: 7px 10px 5px;
  border-bottom: 1.5px solid rgba(255,255,255,0.3);
  background: rgba(0,0,0,0.2);
  gap: 6px;
}

.cf-logo {
  width: 26px; height: 26px;
  border-radius: 50%;
  background: #ffffff;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: 7.5px;
  color: #800000; flex-shrink: 0;
  box-shadow: 0 1px 3px rgba(0,0,0,0.3);
}

.cf-headtext { flex: 1; }
.cf-agency   { font-size: 6px; opacity: 0.9; letter-spacing: 0.3px; }
.cf-program  { font-size: 8px; font-weight: bold; letter-spacing: 0.4px; }

.cf-badge {
  font-size: 6px;
  background: #ffffff;
  color: #800000;
  padding: 2px 5px;
  border-radius: 3px;
  font-weight: bold;
  white-space: nowrap;
}

.cf-body {
  display: flex; flex: 1;
  padding: 7px 10px; gap: 10px;
}

.cf-photo {
  width: 58px; height: 64px;
  border: 2px solid rgba(255,255,255,0.6);
  border-radius: 4px;
  overflow: hidden;
  background: rgba(0,0,0,0.3);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.cf-photo img { width: 100%; height: 100%; object-fit: cover; }
.cf-photo-placeholder { font-size: 7px; opacity: 0.7; text-align: center; color: white; }

.cf-info { flex: 1; display: flex; flex-direction: column; justify-content: space-between; }

.cf-name {
  font-size: 11px; font-weight: bold;
  text-transform: uppercase; line-height: 1.2;
}
.cf-middle { font-size: 8px; opacity: 0.85; display: block; font-weight: normal; text-transform: none; }

.cf-field { margin-top: 4px; }
.cf-label { font-size: 6px; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 1px; }
.cf-value { font-size: 8px; font-weight: 500; }
.cf-small { font-size: 7px; line-height: 1.3; }

.cf-footer {
  padding: 5px 10px;
  background: rgba(0,0,0,0.3);
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.cf-uidlabel { font-size: 6px; opacity: 0.75; margin-bottom: 1px; }
.cf-uid      { font-size: 9px; font-weight: bold; letter-spacing: 1px; font-family: 'Courier New', monospace; }
.cf-city     { text-align: right; }
.cf-cityname { font-size: 7px; font-weight: bold; }
.cf-city div:last-child { font-size: 6px; opacity: 0.85; }

/* BACK face styles */
.card-face--back {
  background: #ffffff;
  display: flex; flex-direction: column;
  font-family: Arial, sans-serif;
  color: #333;
}

.cb-header {
  background: #4d0000;
  color: white;
  padding: 5px 10px;
  font-size: 6.5px;
  text-align: center;
  letter-spacing: 0.4px;
}

.cb-body {
  display: flex; flex: 1;
  padding: 8px 10px; gap: 10px;
  align-items: center;
}

.cb-qr-section {
  display: flex; flex-direction: column;
  align-items: center; gap: 3px;
  flex-shrink: 0;
}

.cb-qr-box {
  width: 95px; height: 95px;
  border: 2px solid #800000;
  border-radius: 6px;
  overflow: hidden; background: white;
  display: flex; align-items: center; justify-content: center;
  padding: 2px;
}
.cb-qr-box img { width: 100%; height: 100%; object-fit: contain; }
.cb-qr-placeholder { font-size: 7px; color: #800000; text-align: center; padding: 4px; font-weight: bold; }
.cb-qr-label { font-size: 7px; color: #800000; font-weight: 800; letter-spacing: 0.5px; }

.cb-creds { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.cb-cred-label {
  font-size: 6px; color: #666;
  text-transform: uppercase; letter-spacing: 0.4px;
  margin-bottom: 1px;
}
.cb-cred-value {
  font-size: 8.5px; font-weight: bold; color: #800000;
  font-family: 'Courier New', monospace;
  background: #fff1f2;
  border: 1px solid #fecdd3;
  padding: 2px 5px; border-radius: 3px;
  letter-spacing: 0.8px;
}
.cb-cred-small { font-size: 7px; }
.cb-notice {
  font-size: 5.5px; color: #666;
  line-height: 1.4; margin-top: auto;
}

.cb-footer {
  background: #800000;
  color: white;
  padding: 3px 10px;
  font-size: 5.5px;
  text-align: center;
}
</style>
