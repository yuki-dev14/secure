<template>
  <Head :title="`ID Card — ${beneficiary.unique_id}`" />
  <StaffLayout
    :page-title="`ID Card Preview`"
    :page-subtitle="`${beneficiary.unique_id} · ${beneficiary.full_name ?? 'Beneficiary'}`"
  >
    <div class="max-w-4xl mx-auto space-y-5">

      <!-- ─── Back + Actions ────────────────────────────────────────────────── -->
      <div class="flex flex-wrap items-center justify-between gap-3">
        <Link
          :href="route('superadmin.beneficiaries.show', beneficiary.id)"
          class="btn btn-ghost btn-sm gap-1.5 text-slate-500"
        >
          <ArrowLeftIcon class="w-4 h-4" />
          Back to Profile
        </Link>
        <div class="flex gap-2">
          <a
            v-if="beneficiary.card_path"
            :href="route('superadmin.beneficiaries.card.download', beneficiary.id)"
            class="btn btn-secondary btn-sm gap-1.5"
            download
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
            Download PDF
          </a>
          <button @click="printCard" class="btn btn-primary btn-sm gap-1.5">
            <PrinterIcon class="w-4 h-4" />
            Print Card
          </button>
        </div>
      </div>

      <!-- ─── No card warning ───────────────────────────────────────────────── -->
      <div v-if="!card" class="card p-6 flex items-start gap-3 border-amber-200 bg-amber-50">
        <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
        <div>
          <p class="text-sm font-bold text-amber-800">No active card issued</p>
          <p class="text-xs text-amber-600 mt-0.5">
            Go back to the beneficiary profile and click "Re-issue Card" to generate a card first.
          </p>
        </div>
      </div>

      <!-- ─── Flip instructions ─────────────────────────────────────────────── -->
      <div class="flex items-center gap-2 text-xs text-slate-400 px-1">
        <ArrowsRightLeftIcon class="w-4 h-4 shrink-0" />
        Click the card to flip it and see both sides
      </div>

      <!-- ─── Card Viewer ───────────────────────────────────────────────────── -->
      <div class="flex flex-col lg:flex-row gap-8 items-start justify-center">

        <!-- Card 3D flip container -->
        <div class="card-scene mx-auto" @click="flipped = !flipped">
          <div :class="['card-3d', { 'is-flipped': flipped }]">

            <!-- ═══════════ FRONT ═══════════ -->
            <div class="card-face card-face--front" id="card-print-front">
              <!-- Header band -->
              <div class="cf-header">
                <div class="cf-logo">DSWD</div>
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
                  <img v-if="photoBase64" :src="photoBase64" alt="Photo" />
                  <div v-else class="cf-photo-placeholder">NO<br>PHOTO</div>
                </div>

                <!-- Info -->
                <div class="cf-info">
                  <div class="cf-name">
                    {{ upperLast }}, {{ beneficiary.first_name }}
                    <span v-if="beneficiary.middle_name" class="cf-middle">{{ beneficiary.middle_name }}</span>
                  </div>
                  <div class="cf-field">
                    <div class="cf-label">Birthdate</div>
                    <div class="cf-value">{{ formatDate(beneficiary.birthdate) }}</div>
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
                    <img v-if="qrBase64" :src="qrBase64" alt="QR Code" />
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
                  <div class="cb-cred-row">
                    <div class="cb-cred-label">Default Password (Change on first login)</div>
                    <div class="cb-cred-value cb-cred-red">{{ defaultPassword }}</div>
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

        <!-- ─── Info panel ──────────────────────────────────────────────────── -->
        <div class="space-y-4 min-w-[220px] flex-1 max-w-xs">

          <!-- Card specs -->
          <div class="card p-4 text-sm space-y-3">
            <h3 class="font-semibold text-slate-800 text-xs uppercase tracking-wide text-slate-400">Card Details</h3>
            <div class="space-y-2 divide-y divide-slate-50">
              <div class="flex justify-between pt-2">
                <span class="text-slate-400 text-xs">Card Number</span>
                <span class="font-mono text-xs text-slate-700">{{ card?.card_number ?? 'Not issued' }}</span>
              </div>
              <div class="flex justify-between pt-2">
                <span class="text-slate-400 text-xs">Status</span>
                <span :class="['badge badge-sm', card?.is_active ? 'badge-success' : 'badge-danger']">
                  {{ card?.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <div class="flex justify-between pt-2">
                <span class="text-slate-400 text-xs">Issued</span>
                <span class="text-xs text-slate-600">{{ formatDateShort(card?.issued_at) }}</span>
              </div>
              <div class="flex justify-between pt-2">
                <span class="text-slate-400 text-xs">First Login</span>
                <span :class="['badge badge-sm', card?.is_first_login ? 'badge-warning' : 'badge-success']">
                  {{ card?.is_first_login ? 'Pending' : 'Completed' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Print tip -->
          <div class="card p-4 bg-brand-50 border border-brand-100">
            <h3 class="text-xs font-bold text-brand-700 mb-2 flex items-center gap-1.5">
              <PrinterIcon class="w-3.5 h-3.5" /> Print Instructions
            </h3>
            <ul class="text-xs text-brand-600 space-y-1 list-disc list-inside">
              <li>Use CR80 card stock (3.375" × 2.125")</li>
              <li>Set paper size to <strong>Custom</strong> in printer dialog</li>
              <li>Print at 100% scale — do not "fit to page"</li>
              <li>Use the <strong>Download PDF</strong> button for best quality</li>
            </ul>
          </div>

          <!-- Flip prompt -->
          <button
            @click="flipped = !flipped"
            class="w-full btn btn-secondary gap-2"
          >
            <ArrowsRightLeftIcon class="w-4 h-4" />
            {{ flipped ? 'See Front' : 'See Back / QR Code' }}
          </button>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  ArrowLeftIcon, ArrowDownTrayIcon, PrinterIcon,
  ExclamationTriangleIcon, ArrowsRightLeftIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  beneficiary:     Object,
  card:            Object,
  qrBase64:        String,
  photoBase64:     String,
  defaultPassword: String,
})

const flipped = ref(false)

const upperLast = computed(() =>
  (props.beneficiary.last_name ?? '').toUpperCase()
)

const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' }) : '—'

const formatDateShort = (d) =>
  d ? new Date(d).toLocaleDateString('en-PH', { month: '2-digit', day: '2-digit', year: 'numeric' }) : '—'

const printCard = () => window.print()
</script>

<style>
/* ── 3D Flip scene ─────────────────────────────────────────────────────────── */
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
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
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

/* ── FRONT face styles ─────────────────────────────────────────────────────── */
.card-face--front {
  background: linear-gradient(135deg, #003087 0%, #0051a8 60%, #1a69c8 100%);
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
  background: rgba(0,0,0,0.15);
  gap: 6px;
}

.cf-logo {
  width: 26px; height: 26px;
  border-radius: 50%;
  background: #fcd116;
  display: flex; align-items: center; justify-content: center;
  font-weight: bold; font-size: 7px;
  color: #003087; flex-shrink: 0;
}

.cf-headtext { flex: 1; }
.cf-agency   { font-size: 6px; opacity: 0.85; letter-spacing: 0.3px; }
.cf-program  { font-size: 8px; font-weight: bold; letter-spacing: 0.4px; }

.cf-badge {
  font-size: 6px;
  background: #fcd116;
  color: #003087;
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
  border: 2px solid rgba(255,255,255,0.5);
  border-radius: 4px;
  overflow: hidden;
  background: rgba(0,0,0,0.2);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.cf-photo img { width: 100%; height: 100%; object-fit: cover; }
.cf-photo-placeholder { font-size: 7px; opacity: 0.6; text-align: center; color: white; }

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
  background: rgba(0,0,0,0.2);
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.cf-uidlabel { font-size: 6px; opacity: 0.7; margin-bottom: 1px; }
.cf-uid      { font-size: 9px; font-weight: bold; letter-spacing: 1px; font-family: 'Courier New', monospace; }
.cf-city     { text-align: right; }
.cf-cityname { font-size: 7px; font-weight: bold; }
.cf-city div:last-child { font-size: 6px; opacity: 0.8; }

/* ── BACK face styles ──────────────────────────────────────────────────────── */
.card-face--back {
  background: #f8f9fa;
  display: flex; flex-direction: column;
  font-family: Arial, sans-serif;
}

.cb-header {
  background: #003087;
  color: white;
  padding: 5px 10px;
  font-size: 6.5px;
  text-align: center;
  letter-spacing: 0.4px;
}

.cb-body {
  display: flex; flex: 1;
  padding: 7px 10px; gap: 12px;
}

.cb-qr-section {
  display: flex; flex-direction: column;
  align-items: center; gap: 3px;
  flex-shrink: 0;
}

.cb-qr-box {
  width: 72px; height: 72px;
  border: 2px solid #003087;
  border-radius: 4px;
  overflow: hidden; background: white;
  display: flex; align-items: center; justify-content: center;
}
.cb-qr-box img { width: 100%; height: 100%; }
.cb-qr-placeholder { font-size: 6px; color: #003087; text-align: center; padding: 4px; }
.cb-qr-label { font-size: 6px; color: #003087; font-weight: bold; }

.cb-creds { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.cb-cred-row {}
.cb-cred-label {
  font-size: 6px; color: #666;
  text-transform: uppercase; letter-spacing: 0.4px;
  margin-bottom: 1px;
}
.cb-cred-value {
  font-size: 8.5px; font-weight: bold; color: #003087;
  font-family: 'Courier New', monospace;
  background: #e8edf8;
  padding: 2px 5px; border-radius: 2px;
  letter-spacing: 0.8px;
}
.cb-cred-small { font-size: 7px; }
.cb-cred-red   { color: #ce1126; }
.cb-notice {
  font-size: 5.5px; color: #888;
  line-height: 1.4; margin-top: auto;
}

.cb-footer {
  background: #ce1126;
  color: white;
  padding: 3px 10px;
  font-size: 5.5px;
  text-align: center;
}

/* ── Print ─────────────────────────────────────────────────────────────────── */
@media print {
  body * { display: none !important; }
  .card-scene, .card-scene * { display: block !important; }
  .card-scene {
    width: 3.375in !important;
    height: 2.125in !important;
    perspective: none !important;
    box-shadow: none !important;
    page-break-inside: avoid;
  }
  .card-3d {
    transform: none !important;
    box-shadow: none !important;
  }
  .card-face--back { display: none !important; }
  * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
}
</style>
