<template>
  <Head title="Distribution Events" />
  <StaffLayout page-title="Distribution Events" page-subtitle="Manage 4Ps cash grant claiming schedules">
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex gap-2">
          <span v-for="tab in tabs" :key="tab.value"
            @click="activeTab = tab.value"
            :class="['badge cursor-pointer transition-all', activeTab === tab.value ? 'bg-brand-600 text-white' : 'badge-neutral hover:bg-slate-200']">
            {{ tab.label }}
          </span>
        </div>
        <Link :href="route('admin.events.create')" class="btn btn-primary">
          <PlusIcon class="w-4 h-4" />
          New Event
        </Link>
      </div>

      <!-- Events grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div v-if="filteredEvents.length === 0" class="md:col-span-3 card card-body text-center py-16 text-slate-400">
          <CalendarDaysIcon class="w-12 h-12 mx-auto mb-3 opacity-30" />
          <p>No {{ activeTab }} events.</p>
        </div>

        <div v-for="event in filteredEvents" :key="event.id" class="card hover:shadow-md transition-shadow">
          <div class="p-4">
            <div class="flex items-start justify-between mb-3">
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-slate-800 truncate">{{ event.title }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ event.period }}</p>
              </div>
              <span :class="['badge badge-sm ml-2 flex-shrink-0', statusBadge(event.status)]">
                {{ event.status }}
              </span>
            </div>

            <div class="space-y-1.5 text-sm mb-4">
              <div class="flex items-center gap-2 text-slate-600">
                <CalendarDaysIcon class="w-4 h-4 flex-shrink-0" />
                <span>{{ formatDate(event.distribution_date_start) }}</span>
                <span v-if="event.distribution_date_end !== event.distribution_date_start">
                  → {{ formatDate(event.distribution_date_end) }}
                </span>
              </div>
              <div class="flex items-center gap-2 text-slate-600">
                <MapPinIcon class="w-4 h-4 flex-shrink-0" />
                <span class="truncate">{{ event.venue }}</span>
              </div>
              <div class="flex items-center gap-2 text-slate-500">
                <BanknotesIcon class="w-4 h-4 flex-shrink-0" />
                <span>{{ event.distributions_count }} claimed</span>
              </div>
            </div>

            <div class="flex gap-2">
              <Link :href="route('admin.events.show', event.id)" class="btn btn-secondary btn-sm flex-1">
                <EyeIcon class="w-4 h-4" />
                View
              </Link>
              <Link :href="route('admin.events.edit', event.id)" class="btn btn-ghost btn-sm">
                <PencilIcon class="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  PlusIcon, CalendarDaysIcon, MapPinIcon,
  BanknotesIcon, EyeIcon, PencilIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({ events: Object })

const activeTab = ref('all')
const tabs = [
  { value: 'all',       label: 'All' },
  { value: 'upcoming',  label: 'Upcoming' },
  { value: 'ongoing',   label: 'Ongoing' },
  { value: 'completed', label: 'Completed' },
]

const filteredEvents = computed(() => {
  const data = props.events?.data ?? []
  if (activeTab.value === 'all') return data
  return data.filter(e => e.status === activeTab.value)
})

const statusBadge = (s) => ({
  upcoming:  'badge-info',
  ongoing:   'badge-success',
  completed: 'badge-neutral',
  cancelled: 'badge-danger',
}[s] ?? 'badge-neutral')

const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
</script>
