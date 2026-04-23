<template>
  <Head title="Beneficiary Completion List" />
  <StaffLayout page-title="Beneficiary Completion" page-subtitle="Search, filter and record household quarterly completion">
    <div class="space-y-4">

      <!-- ─── Search & Filters ──────────────────────────────────────────────── -->
      <div class="card p-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- Search -->
          <div class="relative sm:col-span-1">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
            <input
              v-model="filters.search"
              type="text"
              placeholder="Search name or ID…"
              class="form-input pl-9"
              @keyup.enter="applyFilters"
            />
          </div>

          <!-- Barangay -->
          <select v-model="filters.barangay" class="form-select">
            <option value="">All Barangays</option>
            <option v-for="brgy in barangays" :key="brgy" :value="brgy">{{ brgy }}</option>
          </select>

          <!-- Completion status -->
          <select v-model="filters.compliant" class="form-select">
            <option value="">All Statuses</option>
            <option value="true">&#x2713; Complete</option>
            <option value="false">&#x2717; Incomplete</option>
          </select>
        </div>
        <div class="flex items-center gap-2 mt-3">
          <button @click="applyFilters" class="btn btn-primary btn-sm gap-1.5">
            <MagnifyingGlassIcon class="w-4 h-4" />
            Search
          </button>
          <button @click="resetFilters" class="btn btn-secondary btn-sm gap-1.5">
            <ArrowPathIcon class="w-4 h-4" />
            Reset
          </button>
          <span class="text-xs text-slate-400 ml-auto">
            {{ beneficiaries.total.toLocaleString() }} beneficiaries found
          </span>
        </div>
      </div>

      <!-- ─── Table card ─────────────────────────────────────────────────────── -->
      <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <ClipboardDocumentCheckIcon class="w-5 h-5 text-brand-600" />
            <h2 class="font-semibold text-slate-800 text-sm">Beneficiary List</h2>
          </div>
          <span class="badge badge-neutral text-xs">{{ beneficiaries.total }} total</span>
        </div>

        <div v-if="!beneficiaries.data?.length" class="px-5 py-16 text-center text-slate-400">
          <UsersIcon class="w-12 h-12 opacity-20 mx-auto mb-3" />
          <p class="font-medium">No beneficiaries found.</p>
          <p class="text-sm mt-1">Try adjusting your search or filters.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Beneficiary</th>
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Barangay</th>
                <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Members</th>
                <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">School-Age</th>
                <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Under-5</th>
                <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Completion</th>
                <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Last Checked</th>
                <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr
                v-for="ben in beneficiaries.data"
                :key="ben.id"
                class="hover:bg-slate-50/60 transition-colors group"
              >
                <td class="px-5 py-3">
                  <div class="flex items-center gap-3">
                    <!-- Avatar -->
                    <div class="w-9 h-9 rounded-full overflow-hidden bg-brand-100 flex items-center justify-center shrink-0">
                      <img v-if="ben.photo_path"
                        :src="`/storage/${ben.photo_path}`"
                        class="w-full h-full object-cover" />
                      <span v-else class="text-xs font-bold text-brand-600">
                        {{ initials(ben.full_name) }}
                      </span>
                    </div>
                    <div>
                      <p class="font-semibold text-slate-700 text-xs">{{ ben.full_name }}</p>
                      <p class="text-[10px] text-slate-400 font-mono">{{ ben.unique_id }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-3 text-xs text-slate-500">{{ ben.barangay }}</td>
                <td class="px-5 py-3 text-center text-sm font-medium text-slate-700">
                  {{ ben.family_members_count }}
                </td>
                <td class="px-5 py-3 text-center">
                  <span class="text-sm font-medium text-slate-700">
                    {{ ben.family_members?.filter(m => m.is_school_age).length ?? 0 }}
                  </span>
                </td>
                <td class="px-5 py-3 text-center">
                  <span class="text-sm font-medium text-slate-700">
                    {{ ben.family_members?.filter(m => m.is_under_five).length ?? 0 }}
                  </span>
                </td>
                <td class="px-5 py-3 text-center">
                  <span :class="['badge badge-sm', ben.is_compliant ? 'badge-success' : 'badge-danger']">
                    {{ ben.is_compliant ? '&#x2713; Complete' : '&#x2717; Incomplete' }}
                  </span>
                </td>
                <td class="px-5 py-3 text-center text-xs text-slate-400">
                  {{ ben.last_compliance_check ? timeAgo(ben.last_compliance_check) : 'Never' }}
                </td>
                <td class="px-5 py-3 text-right">
                  <Link
                    :href="route('verifier.beneficiaries.show', ben.id)"
                    class="btn btn-primary btn-sm gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity"
                  >
                    <ClipboardDocumentCheckIcon class="w-3.5 h-3.5" />
                    Verify
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ─── Pagination ─────────────────────────────────────────────────── -->
        <div v-if="beneficiaries.last_page > 1" class="p-4 border-t border-slate-100 flex items-center justify-between">
          <p class="text-sm text-slate-500">
            Showing {{ beneficiaries.from }}–{{ beneficiaries.to }} of {{ beneficiaries.total }}
          </p>
          <div class="flex gap-1 flex-wrap">
            <Link
              v-for="link in beneficiaries.links"
              :key="link.label"
              :href="link.url ?? '#'"
              :class="[
                'btn btn-sm',
                link.active ? 'btn-primary' : 'btn-secondary',
                !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : '',
              ]"
              v-html="link.label"
            />
          </div>
        </div>
      </div>

      <!-- ─── Legend ──────────────────────────────────────────────────────────── -->
      <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 px-1">
        <span class="flex items-center gap-1.5">
          <span class="badge badge-success badge-sm">&#x2713; Complete</span>
          All quarterly completion conditions met — eligible for grant
        </span>
        <span class="flex items-center gap-1.5">
          <span class="badge badge-danger badge-sm">&#x2717; Incomplete</span>
          One or more conditions not satisfied — ineligible for grant this quarter
        </span>
        <span class="flex items-center gap-1.5">
          <span class="w-2 h-2 bg-slate-300 rounded-full inline-block"></span>
          hover row to reveal Record button
        </span>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
  MagnifyingGlassIcon, ArrowPathIcon,
  ClipboardDocumentCheckIcon, UsersIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  beneficiaries: Object,
  barangays:     Array,
})

// ─── Filters ──────────────────────────────────────────────────────────────────
const filters = reactive({
  search:    new URLSearchParams(window.location.search).get('search')    ?? '',
  barangay:  new URLSearchParams(window.location.search).get('barangay')  ?? '',
  compliant: new URLSearchParams(window.location.search).get('compliant') ?? '',
})

const applyFilters = () => {
  router.get(route('verifier.beneficiaries'), {
    search:    filters.search    || undefined,
    barangay:  filters.barangay  || undefined,
    compliant: filters.compliant || undefined,
  }, { preserveState: true, replace: true })
}

const resetFilters = () => {
  filters.search = ''
  filters.barangay = ''
  filters.compliant = ''
  applyFilters()
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
const initials = (name) =>
  (name ?? '?').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()

const timeAgo = (d) => {
  const sec = Math.floor((Date.now() - new Date(d)) / 1000)
  if (sec < 60)    return `${sec}s ago`
  if (sec < 3600)  return `${Math.floor(sec / 60)}m ago`
  if (sec < 86400) return `${Math.floor(sec / 3600)}h ago`
  const days = Math.floor(sec / 86400)
  return days === 1 ? 'Yesterday' : `${days}d ago`
}
</script>
