<template>
  <Head title="Create Distribution Event" />
  <StaffLayout page-title="Schedule Distribution Event" page-subtitle="Plan a new cash grant claiming schedule">
    <div class="max-w-3xl mx-auto">
      <form @submit.prevent="submit" class="space-y-5">

        <!-- ── Event Details ─────────────────────────────────────────────── -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-slate-800">Quarter Coverage</h3>
            <p class="text-xs text-slate-400 mt-0.5">The quarter determines when beneficiaries complete requirements and claim their grants.</p>
          </div>
          <div class="card-body space-y-5">

            <div>
              <label class="form-label">Event Title <span class="text-danger-500">*</span></label>
              <input v-model="form.title" type="text" class="form-input"
                placeholder="e.g. 4Ps Cash Grant Distribution – Q2 2026"
                :class="{'border-danger-500': form.errors.title}" required />
              <p v-if="form.errors.title" class="form-error">{{ form.errors.title }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <!-- Quarter Dropdown -->
              <div>
                <label class="form-label">Quarter Covered <span class="text-danger-500">*</span></label>
                <select v-model="selectedPeriod" class="form-select" @change="onQuarterSelect" required>
                  <option value="">-- Select Quarter --</option>
                  <optgroup v-for="yr in quarterYears" :key="yr.year" :label="`${yr.year}`">
                    <option v-for="q in yr.quarters" :key="q.value" :value="q.value">{{ q.label }}</option>
                  </optgroup>
                </select>
                <p class="form-hint">The 4Ps compliance quarter this distribution covers.</p>
              </div>
              <div>
                <label class="form-label">Months Covered</label>
                <input v-model.number="form.months_covered" type="number" class="form-input bg-slate-50" readonly />
                <p class="form-hint">Auto-set to 3 (one full quarter).</p>
              </div>
            </div>

            <!-- Quarter summary banner -->
            <div v-if="selectedPeriodObj"
              class="border-2 border-brand-300 bg-brand-50 rounded-xl p-4 space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-brand-700">{{ selectedPeriodObj.value }} — Full Quarter Window</span>
                <span class="badge badge-info">{{ selectedPeriodObj.range }}</span>
              </div>

              <!-- Timeline bar -->
              <div class="relative h-10 bg-brand-100 rounded-lg overflow-hidden border border-brand-200">
                <!-- Full-width fill = entire quarter is open -->
                <div class="absolute inset-0 bg-linear-to-r from-brand-300 to-indigo-300 opacity-60 rounded-lg"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                  <span class="text-xs font-bold text-brand-800">
                    📅 Open entire quarter — {{ formatDate(form.period_start) }} to {{ formatDate(form.period_end) }}
                  </span>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="bg-white border border-brand-200 rounded-lg px-3 py-2">
                  <p class="text-brand-500 font-medium uppercase tracking-wide text-[10px]">Quarter Opens</p>
                  <p class="text-brand-800 font-bold mt-0.5">{{ formatDate(form.period_start) }}</p>
                  <p class="text-brand-400">Verifier begins recording completions</p>
                </div>
                <div class="bg-white border border-brand-200 rounded-lg px-3 py-2">
                  <p class="text-brand-500 font-medium uppercase tracking-wide text-[10px]">Quarter Closes</p>
                  <p class="text-brand-800 font-bold mt-0.5">{{ formatDate(form.period_end) }}</p>
                  <p class="text-brand-400">Last day to claim grants</p>
                </div>
              </div>

              <p class="text-xs text-brand-600 bg-brand-100 rounded-lg px-3 py-2 border border-brand-200">
                <strong>ℹ️ How it works:</strong>
                During the entire quarter, beneficiaries must complete their requirements (education check-ups, health visits, FDS attendance) verified by the Compliance Verifier.
                Once verified, they can visit the DSWD office anytime within the quarter to claim their cash grant.
              </p>
            </div>

            <!-- Read-only date range (auto-filled) -->
            <div v-if="selectedPeriodObj" class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Quarter Start</label>
                <input v-model="form.period_start" type="date" class="form-input bg-slate-50" readonly />
              </div>
              <div>
                <label class="form-label">Quarter End</label>
                <input v-model="form.period_end" type="date" class="form-input bg-slate-50" readonly />
              </div>
            </div>

          </div>
        </div>

        <!-- ── Claiming Venue & Hours ─────────────────────────────────────── -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-slate-800">Claiming Venue & Hours</h3>
            <p class="text-xs text-slate-400 mt-0.5">Where and during what hours beneficiaries can claim within the quarter.</p>
          </div>
          <div class="card-body space-y-4">

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Office Hours Start</label>
                <input v-model="form.distribution_time_start" type="time" class="form-input" />
                <p class="form-hint">Daily claiming window start (optional)</p>
              </div>
              <div>
                <label class="form-label">Office Hours End</label>
                <input v-model="form.distribution_time_end" type="time" class="form-input" />
              </div>
            </div>

            <div>
              <label class="form-label">Venue <span class="text-danger-500">*</span></label>
              <input v-model="form.venue" type="text" class="form-input"
                placeholder="e.g. DSWD Lipa City Office, Function Room A" required />
            </div>

            <div>
              <label class="form-label">Venue Address</label>
              <input v-model="form.venue_address" type="text" class="form-input"
                placeholder="Full address (included in beneficiary notifications)" />
            </div>

            <div>
              <label class="form-label">Assigned Office</label>
              <select v-model="form.office_id" class="form-select">
                <option value="">All Offices / City-wide</option>
                <option v-for="o in offices" :key="o.id" :value="o.id">{{ o.name }}</option>
              </select>
            </div>

            <div>
              <label class="form-label">Notes / Instructions</label>
              <textarea v-model="form.notes" class="form-input" rows="3"
                placeholder="Special instructions for field officers or beneficiaries…"></textarea>
            </div>
          </div>
        </div>

        <!-- Preview -->
        <div v-if="form.title && selectedPeriodObj" class="alert alert-info">
          <InformationCircleIcon class="w-5 h-5 shrink-0" />
          <div class="text-sm">
            <p class="font-semibold">{{ form.title }}</p>
            <p class="text-brand-600">
              Open all quarter: {{ formatDate(form.period_start) }} – {{ formatDate(form.period_end) }}
              <span v-if="form.venue"> @ {{ form.venue }}</span>
            </p>
          </div>
        </div>

        <div class="flex gap-3">
          <Link :href="route('admin.events.index')" class="btn btn-secondary">Cancel</Link>
          <button type="submit" :disabled="form.processing || !selectedPeriod" class="btn btn-primary flex-1">
            <CalendarDaysIcon class="w-4 h-4" />
            {{ form.processing ? 'Creating...' : 'Create Distribution Event' }}
          </button>
        </div>
      </form>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { CalendarDaysIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({ offices: Array })

// ── Official 4Ps quarterly schedule ──────────────────────────────────────────
const QUARTER_DEFS = [
  { q: 1, name: 'Q1 (First Quarter)',  start: '01-01', end: '03-31', range: 'Jan 1 – Mar 31' },
  { q: 2, name: 'Q2 (Second Quarter)', start: '04-01', end: '06-30', range: 'Apr 1 – Jun 30' },
  { q: 3, name: 'Q3 (Third Quarter)',  start: '07-01', end: '09-30', range: 'Jul 1 – Sep 30' },
  { q: 4, name: 'Q4 (Fourth Quarter)', start: '10-01', end: '12-31', range: 'Oct 1 – Dec 31' },
]

const buildYear = (year) => ({
  year,
  quarters: QUARTER_DEFS.map(q => ({
    value: `${year}-Q${q.q}`,
    label: `${q.name}: ${q.range}`,
    range:  q.range,
    start: `${year}-${q.start}`,
    end:   `${year}-${q.end}`,
  }))
})

const currentYear  = new Date().getFullYear()
const quarterYears = [buildYear(currentYear), buildYear(currentYear + 1)]
const allQuarters  = quarterYears.flatMap(yr => yr.quarters)

const selectedPeriod    = ref('')
const selectedPeriodObj = computed(() => allQuarters.find(q => q.value === selectedPeriod.value) ?? null)

const onQuarterSelect = () => {
  const p = selectedPeriodObj.value
  if (!p) return
  form.period         = p.value
  form.period_start   = p.start
  form.period_end     = p.end
  form.months_covered = 3
  if (!form.title) {
    form.title = `4Ps Cash Grant Distribution - ${p.value}`
  }
}

const form = useForm({
  title:                    '',
  period:                   '',
  period_start:             '',
  period_end:               '',
  months_covered:           3,
  distribution_time_start:  '08:00',
  distribution_time_end:    '17:00',
  office_id:                '',
  venue:                    '',
  venue_address:            '',
  notes:                    '',
})

const submit = () => { form.post(route('admin.events.store')) }

const formatDate = (d) =>
  d ? new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' }) : '—'
</script>
