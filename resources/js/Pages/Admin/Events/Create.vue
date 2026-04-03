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
                placeholder="e.g. 4Ps Cash Grant Distribution – Period 2, 2026"
                :class="{'border-danger-500': form.errors.title}" required />
              <p v-if="form.errors.title" class="form-error">{{ form.errors.title }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Compliance Period <span class="text-danger-500">*</span></label>
                <input v-model="form.period" type="text" class="form-input" placeholder="e.g. January–June 2026" required />
              </div>
              <div>
                <label class="form-label">Months Covered <span class="text-danger-500">*</span></label>
                <input v-model.number="form.months_covered" type="number" min="1" max="12" class="form-input" required />
                <p class="form-hint">Used for grant computation (typically 2)</p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Compliance Period Start</label>
                <input v-model="form.period_start" type="date" class="form-input" />
              </div>
              <div>
                <label class="form-label">Compliance Period End</label>
                <input v-model="form.period_end" type="date" class="form-input" />
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
          <InformationCircleIcon class="w-5 h-5 flex-shrink-0" />
          <div class="text-sm">
            <p class="font-semibold">{{ form.title }}</p>
            <p v-if="form.distribution_date_start">
              {{ formatDate(form.distribution_date_start) }}
              <span v-if="form.distribution_date_end !== form.distribution_date_start">
                – {{ formatDate(form.distribution_date_end) }}
              </span>
              <span v-if="form.venue"> @ {{ form.venue }}</span>
            </p>
          </div>
        </div>

        <div class="flex gap-3">
          <Link :href="route('admin.events.index')" class="btn btn-secondary">← Cancel</Link>
          <button type="submit" :disabled="form.processing" class="btn btn-primary flex-1">
            <CalendarDaysIcon class="w-4 h-4" />
            {{ form.processing ? 'Creating…' : 'Create Distribution Event' }}
          </button>
        </div>
      </form>
    </div>
  </StaffLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { CalendarDaysIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({ offices: Array })

const form = useForm({
  title:                    '',
  period:                   '',
  period_start:             '',
  period_end:               '',
  months_covered:           2,
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
  d ? new Date(d).toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' }) : '—'
</script>
