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
      <div style="background: linear-gradient(135deg, #003087 0%, #0051a8 100%);" class="px-6 py-5 flex items-center gap-4">
        <div class="w-16 h-16 rounded-xl overflow-hidden bg-white/20 flex-shrink-0 border-2 border-white/40">
          <img v-if="beneficiary.photo_path"
            :src="`/storage/${beneficiary.photo_path}`"
            :alt="beneficiary.full_name"
            class="w-full h-full object-cover"
          />
          <div v-else class="w-full h-full flex items-center justify-center">
            <UserIcon class="w-8 h-8 text-white/60" />
          </div>
        </div>
        <div class="flex-1 min-w-0">
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
      <div class="px-6 py-4 bg-slate-50 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
        <div>
          <p class="text-xs text-slate-400 mb-0.5">Barangay</p>
          <p class="font-medium text-slate-700">Brgy. {{ beneficiary.barangay }}</p>
        </div>
        <div>
          <p class="text-xs text-slate-400 mb-0.5">Family Members</p>
          <p class="font-medium text-slate-700">{{ beneficiary.family_members?.length ?? 0 }} members</p>
        </div>
        <div>
          <p class="text-xs text-slate-400 mb-0.5">Proxies</p>
          <p class="font-medium text-slate-700">{{ beneficiary.proxies?.length ?? 0 }} registered</p>
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

    <!-- Claim History -->
    <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
      <div class="px-6 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-slate-800">Claim History</h3>
        <p class="text-xs text-slate-400">Your recent cash grant transactions</p>
      </div>
      <div v-if="claim_history?.length" class="divide-y divide-slate-100">
        <div v-for="claim in claim_history" :key="claim.id"
          class="px-6 py-4 flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-700">{{ claim.distribution_event?.period ?? '—' }}</p>
            <p class="text-xs text-slate-400">{{ formatDate(claim.claimed_at) }}</p>
            <span class="badge badge-neutral badge-sm mt-1">
              {{ claim.claimed_by_type === 'proxy' ? '👤 Via Proxy' : '✓ Self' }}
            </span>
          </div>
          <p class="font-bold text-success-600">
            ₱{{ Number(claim.amount_released).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
          </p>
        </div>
      </div>
      <div v-else class="px-6 py-12 text-center text-slate-400">
        <ReceiptRefundIcon class="w-10 h-10 mx-auto mb-2 opacity-40" />
        <p class="text-sm">No claim history yet</p>
      </div>
    </div>

  </BeneficiaryLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import {
  UserIcon, BellAlertIcon, HeartIcon,
  AcademicCapIcon, ShoppingBagIcon, ReceiptRefundIcon,
} from '@heroicons/vue/24/outline'
import BeneficiaryLayout from '@/Layouts/BeneficiaryLayout.vue'

const props = defineProps({
  beneficiary:   Object,
  breakdown:     Object,
  claim_history: Array,
  notifications: Array,
  unread_count:  Number,
})

// notifications data is already decoded by Laravel/Inertia — it's a plain object, NOT a JSON string.
// Safe accessor to get data from a notification object
const getNotifData = (n) => {
  if (!n?.data) return {}
  // Laravel serializes the notification data as an object in Inertia props
  if (typeof n.data === 'object') return n.data
  // Fallback: if somehow it arrives as a string, parse it
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
// Safe number formatter — handles null, string, and number inputs
const fmt = (val) => {
  const n = Number(val ?? 0)
  return isNaN(n) ? '0.00' : n.toLocaleString('en-PH', { minimumFractionDigits: 2 })
}

const formatDate = (d) =>
  d ? new Date(d).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—'
</script>
