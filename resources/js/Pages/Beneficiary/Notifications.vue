<template>
  <Head title="Notifications" />
  <BeneficiaryLayout :unread-count="0">
    <div class="space-y-4">

      <!-- Header -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-5 shadow-lg border border-white/50 flex items-center justify-between">
        <div>
          <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
            <BellIcon class="w-5 h-5 text-brand-600" />
            Notifications & Alerts
          </h1>
          <p class="text-sm text-slate-500 mt-0.5">DSWD announcements and system updates</p>
        </div>
        <span v-if="hasUnread" class="badge badge-danger animate-pulse">
          New
        </span>
      </div>

      <!-- Empty state -->
      <div v-if="!notifications.data?.length"
        class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg border border-white/50 py-16 text-center">
        <BellSlashIcon class="w-12 h-12 text-slate-200 mx-auto mb-3" />
        <p class="text-slate-400 font-medium">No notifications yet</p>
        <p class="text-sm text-slate-300 mt-1 max-w-xs mx-auto">
          You'll be notified here when DSWD schedules a distribution event or sends an announcement.
        </p>
      </div>

      <!-- Notification list -->
      <div v-else class="space-y-3">
        <div
          v-for="notif in notifications.data"
          :key="notif.id"
          :class="[
            'bg-white/90 backdrop-blur-sm rounded-2xl shadow-sm border transition-all',
            !notif.read_at ? 'border-brand-300 shadow-brand-500/10 shadow-md' : 'border-white/50'
          ]"
        >
          <div class="p-5 flex items-start gap-4">
            <!-- Icon -->
            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shrink-0', notifIconBg(notif)]">
              <component :is="notifIcon(notif)" class="w-5 h-5" :class="notifIconColor(notif)" />
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-3">
                <p class="font-semibold text-slate-800 text-sm">{{ notifData(notif).title ?? 'System Notification' }}</p>
                <span v-if="!notif.read_at" class="badge badge-info badge-sm shrink-0">New</span>
              </div>

              <p class="text-sm text-slate-600 mt-1">{{ notifData(notif).message ?? notifData(notif).body ?? '—' }}</p>

              <!-- Distribution event details -->
              <template v-if="notifData(notif).type === 'distribution_schedule'">
                <div class="mt-3 p-3 bg-brand-50 rounded-xl border border-brand-100 space-y-1">
                  <p class="text-xs font-semibold text-brand-700">📅 Distribution Event Details</p>
                  <p v-if="notifData(notif).venue" class="text-xs text-brand-600">
                    <strong>Venue:</strong> {{ notifData(notif).venue }}
                  </p>
                  <p v-if="notifData(notif).date" class="text-xs text-brand-600">
                    <strong>Date:</strong> {{ notifData(notif).date }}
                  </p>
                  <p v-if="notifData(notif).amount" class="text-xs text-brand-600 font-bold">
                    Expected: ₱{{ Number(notifData(notif).amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                  </p>
                </div>
              </template>

              <p class="text-xs text-slate-400 mt-2">{{ formatDate(notif.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="notifications.last_page > 1"
        class="bg-white/50 backdrop-blur-sm rounded-xl p-3 flex items-center justify-between">
        <p class="text-sm text-white/70">
          {{ notifications.from }}–{{ notifications.to }} of {{ notifications.total }}
        </p>
        <div class="flex gap-1">
          <Link
            v-for="link in notifications.links"
            :key="link.label"
            :href="link.url ?? '#'"
            :class="[
              'px-3 py-1 rounded-lg text-xs font-medium transition-all',
              link.active ? 'bg-white text-brand-700' : 'bg-white/20 text-white/70 hover:bg-white/30',
              !link.url ? 'opacity-40 pointer-events-none' : ''
            ]"
            v-html="link.label"
          />
        </div>
      </div>

    </div>
  </BeneficiaryLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  BellIcon, BellSlashIcon, CalendarDaysIcon,
  MegaphoneIcon, InformationCircleIcon,
  ExclamationTriangleIcon, CheckCircleIcon,
} from '@heroicons/vue/24/outline'
import BeneficiaryLayout from '@/Layouts/BeneficiaryLayout.vue'

const props = defineProps({
  notifications: Object,   // paginated
  unread_count:  Number,
})

const hasUnread = computed(() =>
  props.notifications.data?.some(n => !n.read_at)
)

const notifData = (n) => {
  try { return JSON.parse(n.data ?? '{}') } catch { return {} }
}

const notifIcon = (n) => {
  const type = notifData(n).type ?? ''
  if (type.includes('distribution')) return CalendarDaysIcon
  if (type.includes('alert') || type.includes('fraud')) return ExclamationTriangleIcon
  if (type.includes('compliance')) return CheckCircleIcon
  if (type.includes('announce')) return MegaphoneIcon
  return InformationCircleIcon
}

const notifIconBg = (n) => {
  const type = notifData(n).type ?? ''
  if (type.includes('distribution')) return 'bg-brand-50'
  if (type.includes('alert')) return 'bg-danger-50'
  if (type.includes('compliance')) return 'bg-success-50'
  return 'bg-slate-50'
}

const notifIconColor = (n) => {
  const type = notifData(n).type ?? ''
  if (type.includes('distribution')) return 'text-brand-600'
  if (type.includes('alert')) return 'text-danger-600'
  if (type.includes('compliance')) return 'text-success-600'
  return 'text-slate-400'
}

const formatDate = (d) =>
  d ? new Date(d).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—'
</script>
