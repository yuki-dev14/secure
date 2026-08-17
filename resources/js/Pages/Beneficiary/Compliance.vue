<template>
  <Head title="My Compliance" />
  <BeneficiaryLayout :unread-count="unread_count">

    <!-- Compliance Status Banner -->
    <div class="mb-6 p-5 rounded-2xl border-2 shadow-lg"
         :class="summary.is_compliant ? 'border-emerald-300 bg-emerald-50/90' : 'border-red-300 bg-red-50/90'">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-xl flex items-center justify-center shrink-0"
             :class="summary.is_compliant ? 'bg-emerald-100' : 'bg-red-100'">
          <component :is="summary.is_compliant ? CheckCircleIcon : XCircleIcon"
                     class="w-8 h-8"
                     :class="summary.is_compliant ? 'text-emerald-600' : 'text-red-600'" />
        </div>
        <div>
          <h2 class="text-lg font-bold" :class="summary.is_compliant ? 'text-emerald-800' : 'text-red-800'">
            {{ summary.is_compliant ? 'You are Compliant ✓' : 'Non-Compliant — Action Required' }}
          </h2>
          <p class="text-sm mt-0.5" :class="summary.is_compliant ? 'text-emerald-600' : 'text-red-600'">
            {{ summary.is_compliant
              ? 'All your compliance requirements are met. Your grants will be computed at full amount.'
              : 'Some compliance flags are affecting your grants. Review the details below.'
            }}
          </p>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
      <div class="bg-white/90 backdrop-blur-sm rounded-xl p-4 shadow border border-white/50">
        <p class="text-xs text-slate-400 uppercase tracking-wide">FDS Sessions</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">{{ summary.fds_sessions }}</p>
        <p class="text-xs text-slate-400 mt-0.5">attended</p>
      </div>
      <div class="bg-white/90 backdrop-blur-sm rounded-xl p-4 shadow border border-white/50">
        <p class="text-xs text-slate-400 uppercase tracking-wide">NC Flags</p>
        <p class="text-2xl font-bold mt-1" :class="summary.confirmed_nc > 0 ? 'text-red-600' : 'text-slate-700'">
          {{ summary.confirmed_nc }}
        </p>
        <p class="text-xs text-slate-400 mt-0.5">confirmed</p>
      </div>
      <div class="bg-white/90 backdrop-blur-sm rounded-xl p-4 shadow border border-white/50">
        <p class="text-xs text-slate-400 uppercase tracking-wide">Pending Review</p>
        <p class="text-2xl font-bold text-amber-600 mt-1">{{ summary.pending_nc }}</p>
        <p class="text-xs text-slate-400 mt-0.5">under review</p>
      </div>
      <div class="bg-white/90 backdrop-blur-sm rounded-xl p-4 shadow border border-white/50">
        <p class="text-xs text-slate-400 uppercase tracking-wide">Dismissed</p>
        <p class="text-2xl font-bold text-slate-500 mt-1">{{ summary.dismissed_nc }}</p>
        <p class="text-xs text-slate-400 mt-0.5">cleared</p>
      </div>
    </div>

    <!-- Non-Compliance Records -->
    <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50 mb-6">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
        <ExclamationTriangleIcon class="w-5 h-5 text-amber-500" />
        <div>
          <h3 class="font-bold text-slate-700">Non-Compliance Records</h3>
          <p class="text-xs text-slate-400">Flags reported by School Representatives and Midwives</p>
        </div>
      </div>

      <div v-if="ncRecords.length === 0" class="px-6 py-10 text-center text-slate-400">
        <ShieldCheckIcon class="w-10 h-10 mx-auto mb-2 opacity-40" />
        <p class="text-sm">No non-compliance records. Keep it up!</p>
      </div>

      <div v-else class="divide-y divide-slate-50">
        <div v-for="nc in ncRecords" :key="nc.id" class="px-6 py-4 hover:bg-slate-25 transition-colors">
          <div class="flex items-start gap-3">
            <!-- Status indicator -->
            <div class="mt-1 shrink-0">
              <span :class="[statusDot(nc.status), 'w-2.5 h-2.5 rounded-full inline-block']"></span>
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <p class="text-sm font-medium text-slate-700">{{ nc.reason }}</p>
                  <p v-if="nc.family_member" class="text-xs text-violet-500 mt-0.5">
                    → {{ nc.family_member.name }} ({{ nc.family_member.relationship }})
                  </p>
                </div>
                <span :class="[statusBadge(nc.status), 'px-2 py-0.5 rounded-full text-xs font-semibold shrink-0']">
                  {{ nc.status }}
                </span>
              </div>

              <div class="flex flex-wrap items-center gap-3 mt-2 text-xs text-slate-400">
                <span :class="[nc.category === 'education' ? 'bg-violet-100 text-violet-600' : 'bg-emerald-100 text-emerald-600',
                      'px-2 py-0.5 rounded-full font-medium capitalize']">
                  {{ nc.category }}
                </span>
                <span>{{ grantLabel(nc.grant_affected) }}</span>
                <span>Period: {{ nc.period }}</span>
                <span>Reported: {{ nc.created_at }}</span>
              </div>

              <p v-if="nc.details" class="text-xs text-slate-500 mt-2 bg-slate-50 p-2 rounded-lg">
                {{ nc.details }}
              </p>

              <!-- Explainer for confirmed NC -->
              <div v-if="nc.status === 'confirmed'" class="mt-2 p-2 bg-red-50 rounded-lg border border-red-100">
                <p class="text-xs text-red-600">
                  <strong>Impact:</strong> The {{ grantLabel(nc.grant_affected) }} component of your grant has been
                  zeroed out for period {{ nc.period }}. Please comply with the requirements to restore it next period.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FDS Attendance History -->
    <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
        <QrCodeIcon class="w-5 h-5 text-blue-500" />
        <div>
          <h3 class="font-bold text-slate-700">FDS Attendance History</h3>
          <p class="text-xs text-slate-400">Family Development Sessions you attended (QR-verified)</p>
        </div>
      </div>

      <div v-if="fdsAttendance.length === 0" class="px-6 py-10 text-center text-slate-400">
        <QrCodeIcon class="w-10 h-10 mx-auto mb-2 opacity-40" />
        <p class="text-sm">No FDS attendance recorded yet.</p>
      </div>

      <div v-else class="divide-y divide-slate-50">
        <div v-for="fds in fdsAttendance" :key="fds.id" class="px-6 py-3.5 flex items-center gap-3">
          <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
               :class="fds.qr_verified ? 'bg-emerald-100' : 'bg-slate-100'">
            <QrCodeIcon class="w-4 h-4" :class="fds.qr_verified ? 'text-emerald-600' : 'text-slate-400'" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-700">{{ fds.session_title }}</p>
            <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5">
              <span>{{ fds.session_date }}</span>
              <span v-if="fds.venue">· {{ fds.venue }}</span>
              <span>· {{ fds.period }}</span>
            </div>
          </div>
          <div class="text-right shrink-0">
            <span v-if="fds.qr_verified" class="text-xs text-emerald-600 font-medium">✓ QR Verified</span>
            <span v-else class="text-xs text-slate-400">Manual</span>
            <p class="text-xs text-slate-400 mt-0.5">{{ fds.scanned_at }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Informational note -->
    <div class="mt-6 p-4 bg-blue-50/80 rounded-xl border border-blue-200/50 text-sm text-blue-700">
      <p class="font-medium">📋 How compliance works</p>
      <ul class="mt-2 space-y-1 text-xs text-blue-600 list-disc list-inside">
        <li><strong>Education:</strong> Children aged 3–18 must maintain ≥85% school attendance.</li>
        <li><strong>Health:</strong> Pregnant members must attend prenatal checkups; children 0–5 must have immunizations; ages 6–14 must take deworming.</li>
        <li><strong>FDS:</strong> Attend Family Development Sessions as scheduled by your barangay.</li>
        <li>Non-compliance confirmed by Admin SWA will zero out the affected grant component for that bimonthly period.</li>
        <li>You can regain full grant amounts by complying in the next period.</li>
      </ul>
    </div>

  </BeneficiaryLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import BeneficiaryLayout from '@/Layouts/BeneficiaryLayout.vue'
import {
  ExclamationTriangleIcon, ShieldCheckIcon, QrCodeIcon,
} from '@heroicons/vue/24/outline'
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/solid'

defineProps({
  beneficiary:   Object,
  ncRecords:     Array,
  fdsAttendance: Array,
  summary:       Object,
  unread_count:  Number,
})

const statusDot = (s) => ({
  pending:   'bg-amber-400',
  confirmed: 'bg-red-500',
  dismissed: 'bg-slate-300',
}[s] ?? 'bg-slate-300')

const statusBadge = (s) => ({
  pending:   'bg-amber-100 text-amber-700',
  confirmed: 'bg-red-100 text-red-700',
  dismissed: 'bg-slate-100 text-slate-500',
}[s] ?? 'bg-slate-100 text-slate-500')

const grantLabel = (g) => ({
  health_grant:            'Health (₱750/mo)',
  education_elementary:    'Education – Elementary (₱300/mo)',
  education_junior_high:   'Education – Junior High (₱500/mo)',
  education_senior_high:   'Education – Senior High (₱700/mo)',
  rice_subsidy:            'Rice Subsidy (₱600/mo)',
}[g] ?? g)
</script>
