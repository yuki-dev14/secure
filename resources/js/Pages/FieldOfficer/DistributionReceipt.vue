<template>
  <Head :title="`Receipt — ${distribution.transaction_reference}`" />
  <StaffLayout
    :page-title="`Transaction Receipt`"
    :page-subtitle="`${distribution.transaction_reference} · ${distribution.distribution_event?.title}`"
  >
    <div class="max-w-2xl mx-auto space-y-4">

      <!-- ─── Top Actions ──────────────────────────────────────────────────── -->
      <div class="flex flex-wrap items-center justify-between gap-3">
        <Link :href="route('officer.distribution')" class="btn btn-ghost btn-sm gap-1.5 text-slate-500">
          <ArrowLeftIcon class="w-4 h-4" />
          Back to Log
        </Link>
        <button @click="printReceipt" class="btn btn-primary btn-sm gap-1.5">
          <PrinterIcon class="w-4 h-4" />
          Print Receipt
        </button>
      </div>

      <!-- ─── Receipt Card ─────────────────────────────────────────────────── -->
      <div id="receipt-print" class="card overflow-hidden">

        <!-- Header band -->
        <div class="p-6 text-white" style="background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 100%)">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                  <span class="text-white font-bold text-xs">4P</span>
                </div>
                <div>
                  <p class="text-xs font-bold uppercase tracking-widest opacity-80">DSWD · Lipa City, Batangas</p>
                  <p class="text-xs opacity-60">Republic Act No. 10973</p>
                </div>
              </div>
              <h1 class="text-xl font-extrabold">Cash Grant Distribution Receipt</h1>
              <p class="text-sm opacity-70 mt-0.5">{{ distribution.distribution_event?.title }}</p>
            </div>

            <!-- Status badge -->
            <div class="shrink-0 text-right">
              <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-400/20 border border-emerald-400/40">
                <CheckCircleIcon class="w-4 h-4 text-emerald-300" />
                <span class="text-xs font-bold text-emerald-200 uppercase tracking-wide">Claimed</span>
              </div>
              <p class="text-[10px] opacity-50 mt-1.5 font-mono">{{ distribution.transaction_reference }}</p>
            </div>
          </div>

          <!-- Amount featured -->
          <div class="mt-5 pt-5 border-t border-white/20 flex items-end justify-between">
            <div>
              <p class="text-xs opacity-60 uppercase tracking-widest mb-1">Amount Released</p>
              <p class="text-4xl font-extrabold tracking-tight">
                ₱{{ Number(distribution.amount_released).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
              </p>
            </div>
            <div class="text-right">
              <p class="text-xs opacity-60 uppercase tracking-widest mb-1">Payment Mode</p>
              <p class="text-sm font-bold capitalize">{{ distribution.payment_mode }}</p>
            </div>
          </div>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-6">

          <!-- ── Beneficiary ─────────────────────────────────────────────── -->
          <div>
            <p class="section-label">Beneficiary Information</p>
            <div class="info-grid">
              <div class="info-row">
                <span class="info-key">Full Name</span>
                <span class="info-val font-semibold">{{ distribution.beneficiary?.full_name ?? '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-key">4Ps Unique ID</span>
                <span class="info-val font-mono text-brand-600">{{ distribution.beneficiary?.unique_id ?? '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-key">Barangay</span>
                <span class="info-val">{{ distribution.beneficiary?.barangay ?? '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-key">Household Members</span>
                <span class="info-val">{{ distribution.beneficiary?.family_members?.length ?? 0 }} member(s)</span>
              </div>
            </div>
          </div>

          <!-- ── Claimed By ──────────────────────────────────────────────── -->
          <div>
            <p class="section-label">Claiming Details</p>
            <div class="info-grid">
              <div class="info-row">
                <span class="info-key">Claimed By</span>
                <span class="info-val flex items-center gap-2">
                  <span :class="['badge badge-sm', distribution.claimed_by_type === 'proxy' ? 'badge-warning' : 'badge-success']">
                    {{ distribution.claimed_by_type === 'proxy' ? 'Authorized Proxy' : 'Beneficiary (Self)' }}
                  </span>
                </span>
              </div>
              <template v-if="distribution.proxy">
                <div class="info-row">
                  <span class="info-key">Proxy Name</span>
                  <span class="info-val font-semibold">{{ distribution.proxy.full_name }}</span>
                </div>
                <div class="info-row">
                  <span class="info-key">Relationship</span>
                  <span class="info-val capitalize">{{ distribution.proxy.relationship }}</span>
                </div>
              </template>
              <div class="info-row">
                <span class="info-key">Claim Date & Time</span>
                <span class="info-val">{{ formatDateTime(distribution.claimed_at) }}</span>
              </div>
            </div>
          </div>

          <!-- ── Grant Breakdown ─────────────────────────────────────────── -->
          <div v-if="distribution.calculation">
            <p class="section-label">Grant Breakdown</p>
            <div class="rounded-xl border border-slate-200 overflow-hidden">
              <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Component</th>
                    <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Amount</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-if="distribution.calculation.health_grant_amount > 0">
                    <td class="px-4 py-2.5 flex items-center gap-2">
                      <HeartIcon class="w-4 h-4 text-pink-500 shrink-0" />
                      <span class="text-slate-700">Health Grant</span>
                    </td>
                    <td class="px-4 py-2.5 text-right font-medium text-slate-700">
                      ₱{{ Number(distribution.calculation.health_grant_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                    </td>
                  </tr>
                  <tr v-if="distribution.calculation.education_grant_amount > 0">
                    <td class="px-4 py-2.5 flex items-center gap-2">
                      <AcademicCapIcon class="w-4 h-4 text-blue-500 shrink-0" />
                      <span class="text-slate-700">Education Grant</span>
                    </td>
                    <td class="px-4 py-2.5 text-right font-medium text-slate-700">
                      ₱{{ Number(distribution.calculation.education_grant_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                    </td>
                  </tr>
                  <tr v-if="distribution.calculation.rice_subsidy_amount > 0">
                    <td class="px-4 py-2.5 flex items-center gap-2">
                      <ShoppingBagIcon class="w-4 h-4 text-amber-500 shrink-0" />
                      <span class="text-slate-700">Rice Subsidy</span>
                    </td>
                    <td class="px-4 py-2.5 text-right font-medium text-slate-700">
                      ₱{{ Number(distribution.calculation.rice_subsidy_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                    </td>
                  </tr>
                </tbody>
                <tfoot class="border-t-2 border-slate-300 bg-slate-50">
                  <tr>
                    <td class="px-4 py-3 font-bold text-slate-800 text-sm">Total</td>
                    <td class="px-4 py-3 text-right font-extrabold text-brand-700">
                      ₱{{ Number(distribution.amount_released).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <p class="text-xs text-slate-400 mt-2">
              Coverage: {{ distribution.calculation.months_covered ?? '—' }} month(s) ·
              Period: {{ distribution.distribution_event?.period }}
            </p>
          </div>

          <!-- ── Event & Officer ────────────────────────────────────────────── -->
          <div>
            <p class="section-label">Distribution Details</p>
            <div class="info-grid">
              <div class="info-row">
                <span class="info-key">Event</span>
                <span class="info-val">{{ distribution.distribution_event?.title ?? '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-key">Venue</span>
                <span class="info-val">{{ distribution.distribution_event?.venue ?? '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-key">Released By</span>
                <span class="info-val">{{ distribution.released_by?.name ?? '—' }}</span>
              </div>
              <div v-if="distribution.verification_notes" class="info-row items-start">
                <span class="info-key">Notes</span>
                <span class="info-val leading-relaxed">{{ distribution.verification_notes }}</span>
              </div>
            </div>
          </div>

          <!-- ── Documents Verified ─────────────────────────────────────────── -->
          <div v-if="distribution.beneficiary?.documents?.length">
            <p class="section-label">Documents on File</p>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="doc in distribution.beneficiary.documents"
                :key="doc.id"
                :class="['badge badge-sm', doc.is_verified ? 'badge-success' : 'badge-neutral']"
              >
                <CheckCircleIcon v-if="doc.is_verified" class="w-3 h-3" />
                {{ doc.document_name ?? doc.type_label }}
              </span>
            </div>
          </div>

          <!-- ── Footer ──────────────────────────────────────────────────────── -->
          <div class="pt-5 border-t border-slate-200">
            <div class="flex items-center justify-between text-xs text-slate-400">
              <div>
                <p class="font-mono font-semibold text-slate-500">{{ distribution.transaction_reference }}</p>
                <p class="mt-0.5">Generated {{ formatDateTime(new Date().toISOString()) }}</p>
              </div>
              <div class="text-right">
                <p>SECURE 4Ps Verification System</p>
                <p class="mt-0.5">DSWD Field Office IV-A · Lipa City, Batangas</p>
                <p class="mt-0.5">Data Privacy Act of 2012 Compliant</p>
              </div>
            </div>

            <!-- Signature lines -->
            <div class="mt-8 grid grid-cols-2 gap-8">
              <div class="border-t border-slate-300 pt-2 text-center">
                <p class="text-xs text-slate-500 font-semibold">{{ distribution.beneficiary?.full_name }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">Beneficiary / Authorized Proxy</p>
              </div>
              <div class="border-t border-slate-300 pt-2 text-center">
                <p class="text-xs text-slate-500 font-semibold">{{ distribution.released_by?.name }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">Field Officer / Releasing Officer</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── Action buttons ────────────────────────────────────────────────── -->
      <div class="flex gap-3 justify-end no-print">
        <Link :href="route('officer.scanner')" class="btn btn-secondary gap-1.5">
          <QrCodeIcon class="w-4 h-4" />
          Next Scan
        </Link>
        <button @click="printReceipt" class="btn btn-primary gap-1.5">
          <PrinterIcon class="w-4 h-4" />
          Print
        </button>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import {
  ArrowLeftIcon, PrinterIcon, CheckCircleIcon,
  HeartIcon, AcademicCapIcon, ShoppingBagIcon, QrCodeIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

defineProps({
  distribution: Object,
})

const formatDateTime = (d) =>
  d ? new Date(d).toLocaleString('en-PH', {
    year: 'numeric', month: 'long', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  }) : '—'

const printReceipt = () => window.print()
</script>

<style>
/* ── Print styles ─────────────────────────────────────────────────────────── */
@media print {
  /* Hide everything except the receipt card */
  body > * { display: none !important; }
  #app > * { display: none !important; }
  #app { display: block !important; }
  .no-print { display: none !important; }

  /* Show only receipt */
  #receipt-print {
    display: block !important;
    box-shadow: none !important;
    border: 1px solid #e2e8f0 !important;
    page-break-inside: avoid;
  }

  /* Reset layout */
  * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
}
</style>

<style scoped>
.section-label {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #94a3b8;
  margin-bottom: 0.5rem;
}
.info-grid {
  display: flex;
  flex-direction: column;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  overflow: hidden;
}
.info-row {
  display: flex;
  align-items: center;
  padding: 0.625rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  gap: 1rem;
}
.info-row:last-child { border-bottom: none; }
.info-key {
  font-size: 0.72rem;
  color: #94a3b8;
  font-weight: 500;
  min-width: 9rem;
  flex-shrink: 0;
}
.info-val {
  font-size: 0.825rem;
  color: #1e293b;
  flex: 1;
}
</style>
