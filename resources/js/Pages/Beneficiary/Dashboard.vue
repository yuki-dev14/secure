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
          <div class="relative bg-slate-900 rounded-3xl shadow-2xl w-full max-w-lg p-6 space-y-5 border border-white/20">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-white/10 pb-3">
              <div class="flex items-center gap-2">
                <IdentificationIcon class="w-5 h-5 text-amber-400" />
                <h3 class="font-bold text-white text-base">Digital 4Ps Identification Card</h3>
              </div>
              <button @click="showCardModal = false" class="p-1 rounded-lg text-white/60 hover:text-white hover:bg-white/10">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <!-- 3D Card Scene -->
            <div class="py-2">
              <div class="card-scene-modal mx-auto" @click="cardFlipped = !cardFlipped">
                <div :class="['card-3d-modal', { 'is-flipped': cardFlipped }]">

                  <!-- FRONT FACE -->
                  <div class="card-face-modal card-face--front-modal shadow-2xl rounded-2xl overflow-hidden border border-amber-300/40">
                    <div class="bg-gradient-to-r from-red-950 via-rose-900 to-red-950 px-4 py-2.5 flex items-center justify-between border-b border-amber-400/30">
                      <div class="flex items-center gap-2">
                        <img src="/logo.png" alt="DSWD Logo" class="w-7 h-7 object-contain p-0.5 bg-white/10 rounded-full" />
                        <div>
                          <p class="text-[9px] font-black text-amber-200 uppercase tracking-widest leading-none">Republic of the Philippines</p>
                          <p class="text-[11px] font-extrabold text-white leading-tight">Pantawid Pamilyang Pilipino Program</p>
                        </div>
                      </div>
                      <span class="text-[9px] font-black px-2 py-0.5 rounded bg-amber-400 text-slate-950 uppercase tracking-wider">
                        4Ps ID
                      </span>
                    </div>

                    <div class="p-4 flex gap-3 bg-gradient-to-br from-slate-900 via-rose-950 to-slate-900 relative">
                      <div class="absolute top-3 right-4 w-9 h-7 rounded bg-gradient-to-tr from-amber-500 via-amber-200 to-amber-600 border border-amber-300/60 shadow-md flex items-center justify-center opacity-90">
                        <div class="w-5 h-4 border border-amber-900/40 rounded-sm"></div>
                      </div>

                      <div class="w-20 h-24 rounded-xl overflow-hidden ring-2 ring-amber-400/50 bg-slate-950 shrink-0 shadow-lg">
                        <img v-if="beneficiary.photo_path && !imageError"
                             :src="`/storage/${beneficiary.photo_path}`" alt=""
                             @error="imageError = true"
                             class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center bg-slate-900">
                          <UserIcon class="w-9 h-9 text-slate-400" />
                        </div>
                      </div>

                      <div class="flex-1 min-w-0 space-y-1.5 pt-0.5">
                        <div>
                          <p class="text-[10px] text-amber-300/80 font-bold uppercase tracking-wider">Household Head</p>
                          <p class="text-sm font-black text-white truncate leading-tight uppercase">
                            {{ beneficiary.last_name }}, {{ beneficiary.first_name }}
                          </p>
                        </div>
                        <div>
                          <p class="text-[9px] text-slate-400 uppercase font-semibold">Unique ID</p>
                          <p class="text-xs font-mono font-bold text-amber-300 tracking-wider">
                            {{ beneficiary.unique_id }}
                          </p>
                        </div>
                        <div>
                          <p class="text-[9px] text-slate-400 uppercase font-semibold">Barangay / City</p>
                          <p class="text-xs font-medium text-white/90 truncate">
                            Brgy. {{ beneficiary.barangay }}, Lipa City
                          </p>
                        </div>
                      </div>
                    </div>

                    <div class="bg-slate-950 px-4 py-1.5 border-t border-white/10 flex items-center justify-between text-[9px] text-white/60">
                      <span>DSWD Field Office IV-A</span>
                      <span class="font-mono text-amber-300">CARD NO: {{ card?.card_number ?? '4PS-LPA-ACTIVE' }}</span>
                    </div>
                  </div>

                  <!-- BACK FACE -->
                  <div class="card-face-modal card-face--back-modal shadow-2xl rounded-2xl overflow-hidden border border-amber-300/40 bg-gradient-to-br from-slate-950 via-rose-950 to-slate-950 p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between border-b border-white/10 pb-2">
                      <div>
                        <p class="text-[10px] font-black text-amber-300 uppercase tracking-wider">OFFICIAL 4Ps QR CARD</p>
                        <p class="text-[9px] text-slate-400">Scan for FDS Attendance & Distribution</p>
                      </div>
                      <span class="text-[9px] font-mono text-emerald-400 bg-emerald-950 px-2 py-0.5 rounded border border-emerald-500/30">
                        VERIFIED
                      </span>
                    </div>

                    <div class="flex items-center gap-4 py-2">
                      <div class="w-24 h-24 bg-white p-1.5 rounded-xl shrink-0 shadow-lg border-2 border-amber-400/50 flex items-center justify-center">
                        <img v-if="qr_base64" :src="qr_base64" alt="QR Code" class="w-full h-full object-contain" />
                        <QrCodeIcon v-else class="w-16 h-16 text-slate-400" />
                      </div>
                      <div class="space-y-1 text-xs text-left">
                        <p class="text-white font-bold text-xs">{{ beneficiary.full_name }}</p>
                        <p class="text-amber-300 font-mono text-[11px]">{{ beneficiary.unique_id }}</p>
                        <p class="text-slate-400 text-[10px] leading-tight">
                          Present this QR code during Barangay FDS sessions and Grant distributions.
                        </p>
                      </div>
                    </div>

                    <div class="text-[8px] text-slate-400/80 text-center border-t border-white/10 pt-1.5 leading-tight">
                      Government Property &bull; Non-Transferable &bull; DSWD Lipa City Batangas Hotline (043) 756-1234
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-between pt-2 border-t border-white/10">
              <button @click="cardFlipped = !cardFlipped" class="btn btn-secondary btn-sm gap-2 text-xs">
                <ArrowsRightLeftIcon class="w-4 h-4" />
                {{ cardFlipped ? 'See Front' : 'See Back / QR Code' }}
              </button>
              <button @click="showCardModal = false" class="btn btn-primary btn-sm text-xs">
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
  XMarkIcon, QrCodeIcon, ArrowsRightLeftIcon,
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

const formatDate = (d) =>
  d ? new Date(d).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—'
</script>

<style>
.card-scene-modal {
  width: 324px;
  height: 204px;
  perspective: 1000px;
  cursor: pointer;
}

.card-3d-modal {
  width: 100%;
  height: 100%;
  position: relative;
  transform-style: preserve-3d;
  transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-3d-modal.is-flipped {
  transform: rotateY(180deg);
}

.card-face-modal {
  position: absolute;
  inset: 0;
  backface-visibility: hidden;
  -webkit-backface-visibility: hidden;
}

.card-face--back-modal {
  transform: rotateY(180deg);
}
</style>
