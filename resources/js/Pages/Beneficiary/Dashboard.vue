<template>
  <Head title="My Beneficiary Dashboard" />
  <BeneficiaryLayout :unread-count="unread_count">
    
    <div class="space-y-8">

      <!-- ── Section 1: Hero Welcome & Quick Stats ───────────────────────────── -->
      <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-rose-950/80 via-red-950/70 to-slate-950/80 border border-white/15 p-6 sm:p-8 shadow-2xl backdrop-blur-xl">
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-rose-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
          <div class="flex items-center gap-5">
            <!-- Avatar -->
            <div class="relative w-20 h-20 rounded-2xl overflow-hidden ring-4 ring-rose-500/30 shadow-2xl bg-rose-950 shrink-0">
              <img v-if="beneficiary.photo_path && !imageError"
                   :src="`/storage/${beneficiary.photo_path}`" alt=""
                   @error="imageError = true"
                   class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center bg-rose-900/40">
                <UserIcon class="w-10 h-10 text-rose-300/80" />
              </div>
            </div>

            <!-- Welcome Text -->
            <div>
              <div class="flex flex-wrap items-center gap-2 mb-1">
                <span :class="['px-2.5 py-0.5 rounded-full text-xs font-black tracking-wider uppercase border',
                               beneficiary.is_compliant
                                 ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30'
                                 : 'bg-rose-500/20 text-rose-300 border-rose-500/30']">
                  {{ beneficiary.is_compliant ? '✓ Compliant' : '✗ Non-Compliant' }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-white/10 text-white/90 border border-white/15 capitalize">
                  {{ beneficiary.status }}
                </span>
              </div>

              <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                Welcome back, {{ beneficiary.first_name }}!
              </h1>
              <p class="text-rose-200/70 text-xs sm:text-sm mt-0.5 flex items-center gap-2">
                <span>Unique ID: <strong class="font-mono text-white">{{ beneficiary.unique_id }}</strong></span>
                <span>•</span>
                <span>Brgy. {{ beneficiary.barangay }}</span>
              </p>
            </div>
          </div>

          <!-- Quick Navigation Actions -->
          <div class="flex flex-wrap gap-2.5 w-full lg:w-auto">
            <Link :href="route('beneficiary.family')"
                  class="flex-1 lg:flex-none px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all border border-white/15 shadow-md flex items-center justify-center gap-2">
              <UsersIcon class="w-4 h-4 text-rose-400" />
              Family ({{ beneficiary.family_members?.length ?? 0 }})
            </Link>
            <Link :href="route('beneficiary.grants')"
                  class="flex-1 lg:flex-none px-4 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 text-white text-xs font-bold transition-all shadow-lg shadow-rose-950/50 flex items-center justify-center gap-2 border border-rose-400/30">
              <CurrencyDollarIcon class="w-4 h-4" />
              My Grants
            </Link>
          </div>
        </div>
      </div>

      <!-- ── Section 2: Digital ID Card Preview & Grants Section ──────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- LEFT (5 cols): Official 4Ps Digital Identification Card 3D Viewer -->
        <div class="lg:col-span-5 space-y-4">
          <div class="flex items-center justify-between px-1">
            <div class="flex items-center gap-2">
              <IdentificationIcon class="w-5 h-5 text-rose-400" />
              <h2 class="text-white font-black text-sm uppercase tracking-wider">Digital 4Ps ID Card</h2>
            </div>
            <span class="text-xs text-rose-300/70 font-semibold flex items-center gap-1 cursor-pointer hover:text-white"
                  @click="cardFlipped = !cardFlipped">
              <ArrowsRightLeftIcon class="w-3.5 h-3.5" />
              Click to Flip
            </span>
          </div>

          <!-- 3D Card Container -->
          <div class="card-scene-dash mx-auto" @click="cardFlipped = !cardFlipped">
            <div :class="['card-3d-dash', { 'is-flipped': cardFlipped }]">

              <!-- ═══════════ FRONT FACE ═══════════ -->
              <div class="card-face-dash card-face--front-dash shadow-2xl rounded-2xl overflow-hidden border border-amber-300/40">
                <!-- Metallic Seal Header -->
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

                <!-- Card Body -->
                <div class="p-4 flex gap-3 bg-gradient-to-br from-slate-900 via-rose-950 to-slate-900 relative">
                  <!-- Holographic Gold Chip -->
                  <div class="absolute top-3 right-4 w-9 h-7 rounded bg-gradient-to-tr from-amber-500 via-amber-200 to-amber-600 border border-amber-300/60 shadow-md flex items-center justify-center opacity-90">
                    <div class="w-5 h-4 border border-amber-900/40 rounded-sm"></div>
                  </div>

                  <!-- Photo -->
                  <div class="w-20 h-24 rounded-xl overflow-hidden ring-2 ring-amber-400/50 bg-slate-950 shrink-0 shadow-lg">
                    <img v-if="beneficiary.photo_path && !imageError"
                         :src="`/storage/${beneficiary.photo_path}`" alt=""
                         @error="imageError = true"
                         class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center bg-slate-900">
                      <UserIcon class="w-9 h-9 text-slate-400" />
                    </div>
                  </div>

                  <!-- Beneficiary Info -->
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

                <!-- Card Footer Band -->
                <div class="bg-slate-950 px-4 py-1.5 border-t border-white/10 flex items-center justify-between text-[9px] text-white/60">
                  <span>DSWD Field Office IV-A</span>
                  <span class="font-mono text-amber-300">CARD NO: {{ card?.card_number ?? '4PS-LPA-ACTIVE' }}</span>
                </div>
              </div>

              <!-- ═══════════ BACK FACE ═══════════ -->
              <div class="card-face-dash card-face--back-dash shadow-2xl rounded-2xl overflow-hidden border border-amber-300/40 bg-gradient-to-br from-slate-950 via-rose-950 to-slate-950 p-4 flex flex-col justify-between">
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
                  <!-- QR Code -->
                  <div class="w-24 h-24 bg-white p-1.5 rounded-xl shrink-0 shadow-lg border-2 border-amber-400/50 flex items-center justify-center">
                    <img v-if="qr_base64" :src="qr_base64" alt="QR Code" class="w-full h-full object-contain" />
                    <QrCodeIcon v-else class="w-16 h-16 text-slate-400" />
                  </div>
                  <div class="space-y-1 text-xs">
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

          <p class="text-center text-xs text-rose-300/60 font-medium">
            💡 Flip card to present your official QR code for FDS scanning
          </p>
        </div>

        <!-- RIGHT (7 cols): Bimonthly Cash Grant Breakdown & Compliance -->
        <div class="lg:col-span-7 space-y-6">

          <!-- Cash Grant Summary Box -->
          <div v-if="breakdown" class="rounded-3xl bg-white/95 backdrop-blur-xl border border-white/60 p-6 shadow-2xl space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-rose-600 bg-rose-50 px-2.5 py-1 rounded-full border border-rose-200">
                  Period {{ breakdown?.quarter ?? '2026-P4' }}
                </span>
                <h2 class="text-lg font-bold text-slate-900 mt-1">Bimonthly Cash Grant Summary</h2>
              </div>
              <div class="text-right">
                <p class="text-3xl font-black text-rose-700">
                  ₱{{ fmt(breakdown?.total) }}
                </p>
                <p class="text-xs font-semibold text-slate-400">Total Computed Amount</p>
              </div>
            </div>

            <!-- Grant Components -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <!-- Health -->
              <div class="p-4 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 space-y-1">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-emerald-800">Health Grant</span>
                  <HeartIcon class="w-4 h-4 text-emerald-600" />
                </div>
                <p class="text-xl font-black text-emerald-700">₱{{ fmt(breakdown?.health?.amount) }}</p>
                <p class="text-[11px] text-emerald-600/80 font-medium">₱750 × {{ breakdown?.months_covered ?? 2 }} mos</p>
              </div>

              <!-- Education -->
              <div class="p-4 rounded-2xl bg-rose-50/80 border border-rose-200/80 space-y-1">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-rose-800">Education</span>
                  <AcademicCapIcon class="w-4 h-4 text-rose-600" />
                </div>
                <p class="text-xl font-black text-rose-700">₱{{ fmt(breakdown?.education?.total) }}</p>
                <p class="text-[11px] text-rose-600/80 font-medium">Enrolled children</p>
              </div>

              <!-- Rice -->
              <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200/80 space-y-1">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-amber-800">Rice Subsidy</span>
                  <ShoppingBagIcon class="w-4 h-4 text-amber-600" />
                </div>
                <p class="text-xl font-black text-amber-700">₱{{ fmt(breakdown?.rice?.amount) }}</p>
                <p class="text-[11px] text-amber-600/80 font-medium">₱600 × {{ breakdown?.months_covered ?? 2 }} mos</p>
              </div>
            </div>

            <div class="pt-2 text-xs text-slate-500 flex items-center justify-between border-t border-slate-100">
              <span>Grants are computed per Republic Act 11310</span>
              <Link :href="route('beneficiary.grants')" class="text-rose-600 font-bold hover:underline">
                View Full Breakdown →
              </Link>
            </div>
          </div>

          <!-- Quick Stats Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <div class="p-4 rounded-2xl bg-black/40 backdrop-blur-xl border border-white/10 text-white space-y-1">
              <p class="text-xs text-rose-300/80 font-semibold">Household Size</p>
              <p class="text-2xl font-black text-white">{{ (beneficiary.family_members?.length ?? 0) + 1 }}</p>
              <p class="text-[10px] text-white/50">Registered members</p>
            </div>
            <div class="p-4 rounded-2xl bg-black/40 backdrop-blur-xl border border-white/10 text-white space-y-1">
              <p class="text-xs text-rose-300/80 font-semibold">Compliance Rate</p>
              <p class="text-2xl font-black text-emerald-400">100%</p>
              <p class="text-[10px] text-white/50">Health & FDS Verified</p>
            </div>
            <div class="col-span-2 sm:col-span-1 p-4 rounded-2xl bg-black/40 backdrop-blur-xl border border-white/10 text-white space-y-1">
              <p class="text-xs text-rose-300/80 font-semibold">Barangay Office</p>
              <p class="text-base font-bold text-white truncate">Brgy. {{ beneficiary.barangay }}</p>
              <p class="text-[10px] text-white/50">Lipa City, Batangas</p>
            </div>
          </div>

        </div>

      </div>

    </div>

  </BeneficiaryLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  UserIcon, BellAlertIcon, HeartIcon,
  AcademicCapIcon, ShoppingBagIcon, UsersIcon,
  CurrencyDollarIcon, IdentificationIcon, QrCodeIcon, ArrowsRightLeftIcon,
} from '@heroicons/vue/24/outline'
import BeneficiaryLayout from '@/Layouts/BeneficiaryLayout.vue'

const props = defineProps({
  beneficiary:   Object,
  card:          Object,
  qr_base64:     String,
  breakdown:     Object,
  notifications: Array,
  unread_count:  Number,
})

const imageError  = ref(false)
const cardFlipped = ref(false)

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
</script>

<style>
/* ── 3D Card Scene ─────────────────────────────────────────────────────────── */
.card-scene-dash {
  width: 324px;
  height: 204px;
  perspective: 1000px;
  cursor: pointer;
}

.card-3d-dash {
  width: 100%;
  height: 100%;
  position: relative;
  transform-style: preserve-3d;
  transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-3d-dash.is-flipped {
  transform: rotateY(180deg);
}

.card-face-dash {
  position: absolute;
  inset: 0;
  backface-visibility: hidden;
  -webkit-backface-visibility: hidden;
}

.card-face--back-dash {
  transform: rotateY(180deg);
}
</style>
