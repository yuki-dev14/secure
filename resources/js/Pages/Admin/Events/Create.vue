<template>
  <Head title="Create Distribution Event" />
  <StaffLayout page-title="Schedule Distribution Event" page-subtitle="Plan a new cash grant claiming schedule">
    <div class="max-w-3xl mx-auto">
      <form @submit.prevent="submit" class="space-y-5">
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-slate-800">Event Details</h3>
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
                <label class="form-label">Quarter <span class="text-danger-500">*</span></label>
                <select v-model="selectedPeriod" class="form-select" @change="onQuarterSelect" required>
                  <option value="">-- Select Quarter --</option>
                  <optgroup v-for="yr in quarterYears" :key="yr.year" :label="`${yr.year}`">
                    <option v-for="q in yr.quarters" :key="q.value" :value="q.value">{{ q.label }}</option>
                  </optgroup>
                </select>
                <p class="form-hint">Select the 4Ps compliance quarter for this event.</p>
              </div>
              <div>
                <label class="form-label">Months Covered <span class="text-danger-500">*</span></label>
                <input v-model.number="form.months_covered" type="number" min="1" max="3" class="form-input" required />
                <p class="form-hint">One quarter = 3 months (used for grant computation)</p>
              </div>
            </div>

            <!-- Quarter date range (auto-filled, read-only) -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Quarter Start Date</label>
                <input v-model="form.period_start" type="date" class="form-input bg-slate-50" readonly />
              </div>
              <div>
                <label class="form-label">Quarter End Date</label>
                <input v-model="form.period_end" type="date" class="form-input bg-slate-50" readonly />
              </div>
            </div>

            <!-- Info banner when quarter is selected -->
            <div v-if="selectedPeriodObj" class="flex items-start gap-3 p-3 bg-brand-50 border border-brand-100 rounded-xl text-sm text-brand-700">
              <InformationCircleIcon class="w-5 h-5 shrink-0 mt-0.5" />
              <div>
                <p class="font-semibold">{{ selectedPeriodObj.label }}</p>
                <p class="text-xs text-brand-500 mt-0.5">
                  Only beneficiaries marked <strong>complete</strong> by the verifier for this quarter are eligible to receive their cash grant.
                </p>
              </div>
            </div>

          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-slate-800">Distribution Schedule</h3>
          </div>
          <div class="card-body space-y-5">

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Start Date <span class="text-danger-500">*</span></label>
                <input v-model="form.distribution_date_start" type="date" class="form-input" required />
              </div>
              <div>
                <label class="form-label">End Date <span class="text-danger-500">*</span></label>
                <input v-model="form.distribution_date_end" type="date" class="form-input" required />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Start Time</label>
                <input v-model="form.distribution_time_start" type="time" class="form-input" />
              </div>
              <div>
                <label class="form-label">End Time</label>
                <input v-model="form.distribution_time_end" type="time" class="form-input" />
              </div>
            </div>

            <div>
              <label class="form-label">Venue <span class="text-danger-500">*</span></label>
              <input v-model="form.venue" type="text" class="form-input"
                placeholder="e.g. Lipa City Hall, Function Room A" required />
            </div>

            <div>
              <label class="form-label">Venue Address</label>
              <input v-model="form.venue_address" type="text" class="form-input"
                placeholder="Full address for notification" />
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
        <div v-if="form.title" class="alert alert-info">
          <InformationCircleIcon class="w-5 h-5 shrink-0" />
          <div class="text-sm">
            <p class="font-semibold">{{ form.title }}</p>
            <p v-if="form.distribution_date_start">
              {{ formatDate(form.distribution_date_start) }}
              <span v-if="form.distribution_date_end !== form.distribution_date_start">
                &ndash; {{ formatDate(form.distribution_date_end) }}
              </span>
              <span v-if="form.venue"> @ {{ form.venue }}</span>
            </p>
          </div>
        </div>

        <div class="flex gap-3">
          <Link :href="route('admin.events.index')" class="btn btn-secondary">Cancel</Link>
          <button type="submit" :disabled="form.processing" class="btn btn-primary flex-1">
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
// Q1: January 1   - March 31
// Q2: April 1     - June 30
// Q3: July 1      - September 30
// Q4: October 1   - December 31
const QUARTER_DEFS = [
  { q: 1, name: 'Q1 (First Quarter)',  start: '01-01', end: '03-31', range: 'Jan 1 - Mar 31' },
  { q: 2, name: 'Q2 (Second Quarter)', start: '04-01', end: '06-30', range: 'Apr 1 - Jun 30' },
  { q: 3, name: 'Q3 (Third Quarter)',  start: '07-01', end: '09-30', range: 'Jul 1 - Sep 30' },
  { q: 4, name: 'Q4 (Fourth Quarter)', start: '10-01', end: '12-31', range: 'Oct 1 - Dec 31' },
]

const buildYear = (year) => ({
  year,
  quarters: QUARTER_DEFS.map(q => ({
    value: `${year}-Q${q.q}`,
    label: `${q.name}: ${q.range}`,
    start: `${year}-${q.start}`,
    end:   `${year}-${q.end}`,
  }))
})

const currentYear = new Date().getFullYear()
const quarterYears = [buildYear(currentYear), buildYear(currentYear + 1)]

// Flat list for easy lookup
const allQuarters = quarterYears.flatMap(yr => yr.quarters)

const selectedPeriod = ref('')
const selectedPeriodObj = computed(() => allQuarters.find(q => q.value === selectedPeriod.value) ?? null)

const onQuarterSelect = () => {
  const p = selectedPeriodObj.value
  if (!p) return
  form.period         = p.value
  form.period_start   = p.start
  form.period_end     = p.end
  form.months_covered = 3   // one quarter = 3 months
  if (!form.title) {
    form.title = `4Ps Cash Grant Distribution - ${p.label} ${selectedPeriod.value.split('-')[0]}`
  }
}

const form = useForm({
  title:                    '',
  period:                   '',
  period_start:             '',
  period_end:               '',
  months_covered:           3,
  distribution_date_start:  '',
  distribution_date_end:    '',
  distribution_time_start:  '08:00',
  distribution_time_end:    '17:00',
  office_id:                '',
  venue:                    '',
  venue_address:            '',
  notes:                    '',
})

const submit = () => {
  form.post(route('admin.events.store'))
}

const formatDate = (d) =>
  d ? new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' }) : '-'
</script>
