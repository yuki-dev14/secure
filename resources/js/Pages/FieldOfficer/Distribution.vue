<template>
  <Head title="My Distribution Log" />
  <StaffLayout page-title="My Distributions" page-subtitle="History of cash grant releases you have processed">
    <div class="space-y-4">

      <!-- Active event banner -->
      <div v-if="activeEvent" class="rounded-2xl p-4 flex items-center gap-3 text-white"
        style="background: linear-gradient(135deg, #10b981 0%, #0d9488 100%)"
      >
        <BoltIcon class="w-5 h-5 shrink-0" />
        <p class="text-sm font-medium">
          <strong>Active:</strong> {{ activeEvent.title }} — {{ activeEvent.venue }}
        </p>
        <Link :href="route('officer.scanner')" class="ml-auto btn btn-sm bg-white text-emerald-700 hover:bg-emerald-50 shrink-0 gap-1.5">
          <QrCodeIcon class="w-4 h-4" />
          Scanner
        </Link>
      </div>

      <!-- Table card -->
      <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <ClipboardDocumentListIcon class="w-5 h-5 text-brand-600" />
            <h2 class="font-semibold text-slate-800 text-sm">Release Log</h2>
          </div>
          <span class="badge badge-neutral text-xs">{{ distributions.total }} total</span>
        </div>

        <div v-if="!distributions.data?.length" class="px-5 py-16 text-center text-slate-400">
          <ClipboardDocumentListIcon class="w-12 h-12 opacity-20 mx-auto mb-3" />
          <p class="font-medium">No distributions recorded yet.</p>
          <p class="text-sm mt-1">Scan a beneficiary QR code to start releasing grants.</p>
          <Link :href="route('officer.scanner')" class="btn btn-primary btn-sm mt-4 inline-flex gap-1.5">
            <QrCodeIcon class="w-4 h-4" />
            Open Scanner
          </Link>
        </div>

        <div v-else>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Beneficiary</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Event</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Claimed By</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date & Time</th>
                  <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Reference</th>
                  <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Amount</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <tr v-for="dist in distributions.data" :key="dist.id" class="hover:bg-slate-50/60 group">
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                      <div class="w-7 h-7 rounded-full bg-brand-100 flex items-center justify-center shrink-0">
                        <span class="text-[10px] font-bold text-brand-600">
                          {{ initials(dist.beneficiary?.full_name) }}
                        </span>
                      </div>
                      <div>
                        <p class="font-medium text-slate-700 text-xs">{{ dist.beneficiary?.full_name ?? '—' }}</p>
                        <p class="text-[10px] text-slate-400 font-mono">{{ dist.beneficiary?.unique_id }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-5 py-3 text-xs text-slate-500 max-w-[130px] truncate">
                    {{ dist.distribution_event?.title ?? '—' }}
                  </td>
                  <td class="px-5 py-3">
                    <span :class="['badge badge-sm', dist.claimed_by_type === 'proxy' ? 'badge-warning' : 'badge-success']">
                      {{ dist.claimed_by_type === 'proxy' ? 'Via Proxy' : 'Self' }}
                    </span>
                    <p v-if="dist.proxy" class="text-[10px] text-slate-400 mt-0.5">{{ dist.proxy.full_name }}</p>
                  </td>
                  <td class="px-5 py-3 text-xs text-slate-500">
                    {{ formatDateTime(dist.claimed_at) }}
                  </td>
                  <td class="px-5 py-3">
                    <span class="text-[10px] font-mono text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                      {{ dist.transaction_reference ?? '—' }}
                    </span>
                  </td>
                  <td class="px-5 py-3 text-right font-bold text-success-600">
                    ₱{{ Number(dist.amount_released).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="distributions.last_page > 1" class="p-4 border-t border-slate-100 flex items-center justify-between">
            <p class="text-sm text-slate-500">
              Showing {{ distributions.from }}–{{ distributions.to }} of {{ distributions.total }}
            </p>
            <div class="flex gap-1">
              <Link
                v-for="link in distributions.links"
                :key="link.label"
                :href="link.url ?? '#'"
                :class="['btn btn-sm', link.active ? 'btn-primary' : 'btn-secondary', !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : '']"
                v-html="link.label"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { BoltIcon, QrCodeIcon, ClipboardDocumentListIcon } from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

defineProps({
  distributions: Object,
  activeEvent:   Object,
})

const initials = (name) =>
  (name ?? '—').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()

const formatDateTime = (d) =>
  d ? new Date(d).toLocaleString('en-PH', { dateStyle: 'short', timeStyle: 'short' }) : '—'
</script>
