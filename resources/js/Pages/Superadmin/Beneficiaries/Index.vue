<template>
  <Head title="Beneficiaries" />
  <StaffLayout page-title="Beneficiaries" page-subtitle="All registered 4Ps beneficiaries in Lipa City">
    <div class="space-y-4">

      <!-- Flash -->
      <div v-if="$page.props.flash?.success"
        class="flex items-center gap-3 px-4 py-3 rounded-xl bg-success-50 border border-success-200 text-success-700 text-sm">
        <CheckCircleIcon class="w-5 h-5 shrink-0" />
        {{ $page.props.flash.success }}
      </div>

      <!-- Header row -->
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-3 flex-wrap">
          <!-- Search -->
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input v-model="filters.search" type="text" placeholder="Search name or ID…"
              class="form-input pl-9 w-64" @input="search" />
          </div>
          <!-- Barangay -->
          <select v-model="filters.barangay" class="form-select w-48" @change="search">
            <option value="">All Barangays</option>
            <option v-for="b in barangays" :key="b" :value="b">{{ b }}</option>
          </select>
          <!-- Status -->
          <select v-model="filters.status" class="form-select w-40" @change="search">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
            <option value="graduated">Graduated</option>
            <option value="delisted">Delisted</option>
          </select>
          <!-- Card filter -->
          <select v-model="filters.card" class="form-select w-44" @change="search">
            <option value="">All (Cards)</option>
            <option value="none">No Card Issued</option>
            <option value="issued">Card Issued</option>
          </select>
        </div>

        <div class="flex items-center gap-2">
          <Link :href="route('superadmin.beneficiaries.import')" class="btn btn-secondary">
            <ArrowUpTrayIcon class="w-4 h-4" />
            Bulk Import
          </Link>
          <Link :href="route('superadmin.beneficiaries.create')" class="btn btn-primary">
            <UserPlusIcon class="w-4 h-4" />
            Register Beneficiary
          </Link>
        </div>
      </div>

      <!-- Stats strip -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="card px-4 py-3 flex items-center gap-3">
          <div class="w-8 h-8 bg-brand-50 rounded-lg flex items-center justify-center">
            <UsersIcon class="w-4 h-4 text-brand-600" />
          </div>
          <div>
            <p class="text-lg font-bold text-slate-800">{{ beneficiaries.total }}</p>
            <p class="text-xs text-slate-400">Total</p>
          </div>
        </div>
        <div class="card px-4 py-3 flex items-center gap-3">
          <div class="w-8 h-8 bg-success-50 rounded-lg flex items-center justify-center">
            <CheckBadgeIcon class="w-4 h-4 text-success-600" />
          </div>
          <div>
            <p class="text-lg font-bold text-slate-800">{{ beneficiaries.data.filter(b => b.is_compliant).length }}</p>
            <p class="text-xs text-slate-400">Compliant (page)</p>
          </div>
        </div>
        <div class="card px-4 py-3 flex items-center gap-3">
          <div class="w-8 h-8 bg-warning-50 rounded-lg flex items-center justify-center">
            <CreditCardIcon class="w-4 h-4 text-warning-600" />
          </div>
          <div>
            <p class="text-lg font-bold text-slate-800">{{ noCardCount }}</p>
            <p class="text-xs text-slate-400">No Card (page)</p>
          </div>
        </div>
        <div class="card px-4 py-3 flex items-center gap-3">
          <div class="w-8 h-8 bg-brand-50 rounded-lg flex items-center justify-center">
            <CheckCircleIcon class="w-4 h-4 text-brand-600" />
          </div>
          <div>
            <p class="text-lg font-bold text-slate-800">{{ selected.size }}</p>
            <p class="text-xs text-slate-400">Selected</p>
          </div>
        </div>
      </div>

      <!-- Table card -->
      <div class="card">
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <!-- Select-all checkbox -->
                <th class="w-10">
                  <input type="checkbox" class="checkbox"
                    :checked="isAllSelected"
                    :indeterminate="isIndeterminate"
                    @change="toggleAll" />
                </th>
                <th>Beneficiary</th>
                <th>Unique ID</th>
                <th>Barangay</th>
                <th>Status</th>
                <th>Compliance</th>
                <th>Card</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="beneficiaries.data.length === 0">
                <td colspan="8" class="text-center py-12 text-slate-400">
                  <UserGroupIcon class="w-10 h-10 mx-auto mb-2 opacity-30" />
                  <p>No beneficiaries found</p>
                </td>
              </tr>
              <tr v-for="b in beneficiaries.data" :key="b.id"
                :class="['cursor-pointer transition-colors', selected.has(b.id) ? 'bg-brand-50/50' : 'hover:bg-slate-50/60']"
                @click.stop>
                <!-- Checkbox -->
                <td @click.stop>
                  <input type="checkbox" class="checkbox"
                    :checked="selected.has(b.id)"
                    @change="toggleOne(b.id)" />
                </td>
                <!-- Name + avatar -->
                <td>
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg overflow-hidden bg-slate-100 shrink-0">
                      <img v-if="b.photo_path" :src="`/storage/${b.photo_path}`"
                        class="w-full h-full object-cover" :alt="b.last_name" />
                      <div v-else class="w-full h-full flex items-center justify-center">
                        <UserIcon class="w-4 h-4 text-slate-400" />
                      </div>
                    </div>
                    <div>
                      <p class="font-medium text-slate-800 text-sm">{{ b.last_name }}, {{ b.first_name }}</p>
                      <p class="text-xs text-slate-400">{{ b.family_members_count }} members</p>
                    </div>
                  </div>
                </td>
                <td class="font-mono text-xs text-slate-600">{{ b.unique_id }}</td>
                <td class="text-sm text-slate-600">{{ b.barangay }}</td>
                <td>
                  <span :class="['badge', statusBadge(b.status)]">{{ b.status }}</span>
                </td>
                <td>
                  <span :class="['badge', b.is_compliant ? 'badge-success' : 'badge-danger']">
                    {{ b.is_compliant ? '✓ Compliant' : '✗ Non-Compliant' }}
                  </span>
                </td>
                <td>
                  <span v-if="b.card" class="badge badge-success">Issued</span>
                  <span v-else class="badge badge-warning">No Card</span>
                </td>
                <td>
                  <div class="flex items-center gap-1">
                    <Link :href="route('superadmin.beneficiaries.show', b.id)"
                      class="btn btn-ghost btn-sm" title="View profile">
                      <EyeIcon class="w-4 h-4" />
                    </Link>
                    <button v-if="b.card_path" @click="downloadCard(b.id)"
                      class="btn btn-ghost btn-sm text-brand-600" title="Download Card PDF">
                      <ArrowDownTrayIcon class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="beneficiaries.last_page > 1" class="p-4 border-t border-slate-100 flex items-center justify-between">
          <p class="text-sm text-slate-500">
            Showing {{ beneficiaries.from }}–{{ beneficiaries.to }} of {{ beneficiaries.total }}
          </p>
          <div class="flex gap-1">
            <Link v-for="link in beneficiaries.links" :key="link.label"
              :href="link.url ?? '#'"
              :class="['btn btn-sm', link.active ? 'btn-primary' : 'btn-secondary', !link.url ? 'opacity-40 cursor-not-allowed' : '']"
              v-html="link.label" />
          </div>
        </div>
      </div>
    </div>

    <!-- ── Sticky Batch Action Bar ─────────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="bar">
        <div v-if="selected.size > 0"
          class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50
                 flex items-center gap-4 px-6 py-4
                 bg-slate-900 text-white rounded-2xl shadow-2xl
                 border border-slate-700 backdrop-blur-sm">

          <!-- Count badge -->
          <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-full bg-brand-500 flex items-center justify-center text-xs font-bold">
              {{ selected.size }}
            </div>
            <span class="text-sm font-medium">beneficiar{{ selected.size === 1 ? 'y' : 'ies' }} selected</span>
          </div>

          <div class="w-px h-6 bg-slate-700"></div>

          <!-- Issue Cards button -->
          <button @click="batchIssueCards" :disabled="issuing"
            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500
                   disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm font-medium">
            <CreditCardIcon class="w-4 h-4" />
            {{ issuing ? 'Issuing…' : 'Issue Cards' }}
          </button>

          <!-- Download All -->
          <button @click="downloadSelected"
            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-600
                   transition-colors text-sm font-medium">
            <ArrowDownTrayIcon class="w-4 h-4" />
            Download PDFs
          </button>

          <!-- Deselect -->
          <button @click="clearSelection"
            class="p-2 rounded-xl hover:bg-slate-700 transition-colors text-slate-400 hover:text-white"
            title="Clear selection">
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
      </Transition>
    </Teleport>

    <!-- Confirm batch modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showConfirm = false"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-5">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-2xl bg-brand-100 flex items-center justify-center shrink-0">
                <CreditCardIcon class="w-6 h-6 text-brand-600" />
              </div>
              <div>
                <h2 class="font-bold text-slate-800 text-lg">Issue Batch Cards?</h2>
                <p class="text-sm text-slate-500 mt-0.5">
                  {{ selected.size }} beneficiar{{ selected.size === 1 ? 'y' : 'ies' }} selected
                </p>
              </div>
            </div>

            <div class="bg-slate-50 rounded-xl p-4 space-y-2 text-sm text-slate-600">
              <div class="flex items-start gap-2">
                <CheckCircleIcon class="w-4 h-4 text-success-600 mt-0.5 shrink-0" />
                <span>A new QR-coded ID card and PDF will be generated for each beneficiary.</span>
              </div>
              <div class="flex items-start gap-2">
                <CheckCircleIcon class="w-4 h-4 text-success-600 mt-0.5 shrink-0" />
                <span>Beneficiaries who already have an active card will be <strong>skipped</strong>.</span>
              </div>
              <div class="flex items-start gap-2">
                <CheckCircleIcon class="w-4 h-4 text-success-600 mt-0.5 shrink-0" />
                <span>All actions are audit-logged automatically.</span>
              </div>
            </div>

            <div class="flex gap-3">
              <button @click="confirmBatch" :disabled="issuing"
                class="btn btn-primary flex-1">
                {{ issuing ? 'Processing…' : `Issue ${selected.size} Card${selected.size > 1 ? 's' : ''}` }}
              </button>
              <button @click="showConfirm = false" class="btn btn-ghost flex-1">Cancel</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </StaffLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
  MagnifyingGlassIcon, UserPlusIcon, UsersIcon,
  CheckBadgeIcon, UserGroupIcon, UserIcon,
  EyeIcon, ArrowDownTrayIcon, ArrowUpTrayIcon, CreditCardIcon,
  XMarkIcon, CheckCircleIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  beneficiaries: Object,
  offices:       Array,
  barangays:     Array,
})

// ── Filters ──────────────────────────────────────────────────────────────────
const filters = reactive({ search: '', barangay: '', status: '', card: '' })

let searchTimeout = null
const search = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(route('superadmin.beneficiaries.index'), filters, {
      preserveState: true, preserveScroll: true, replace: true,
    })
  }, 400)
}

// ── Selection state ──────────────────────────────────────────────────────────
const selected = reactive(new Set())

const isAllSelected   = computed(() =>
  props.beneficiaries.data.length > 0 &&
  props.beneficiaries.data.every(b => selected.has(b.id))
)
const isIndeterminate = computed(() =>
  selected.size > 0 && !isAllSelected.value
)
const noCardCount     = computed(() => props.beneficiaries.data.filter(b => !b.card).length)

const toggleOne = (id) => {
  if (selected.has(id)) selected.delete(id)
  else selected.add(id)
}
const toggleAll = () => {
  if (isAllSelected.value) {
    props.beneficiaries.data.forEach(b => selected.delete(b.id))
  } else {
    props.beneficiaries.data.forEach(b => selected.add(b.id))
  }
}
const clearSelection = () => selected.clear()

// ── Batch card issuance ──────────────────────────────────────────────────────
const showConfirm = ref(false)
const issuing     = ref(false)

const batchIssueCards = () => { showConfirm.value = true }

const confirmBatch = () => {
  issuing.value = true
  router.post(
    route('superadmin.beneficiaries.cards.batch'),
    { ids: [...selected] },
    {
      onSuccess: () => {
        selected.clear()
        showConfirm.value = false
      },
      onFinish: () => { issuing.value = false },
    }
  )
}

// ── Download helpers ─────────────────────────────────────────────────────────
const downloadCard = (id) => {
  window.open(route('superadmin.beneficiaries.card.download', id), '_blank')
}

const downloadSelected = () => {
  const withCards = props.beneficiaries.data.filter(b => b.card_path && selected.has(b.id))
  if (!withCards.length) {
    alert('None of the selected beneficiaries have a generated card PDF yet. Issue cards first.')
    return
  }
  withCards.forEach(b => {
    setTimeout(() => window.open(route('superadmin.beneficiaries.card.download', b.id), '_blank'), 200)
  })
}

// ── Misc helpers ─────────────────────────────────────────────────────────────
const statusBadge = (s) => ({
  active:    'badge-success',
  inactive:  'badge-neutral',
  suspended: 'badge-danger',
  graduated: 'badge-info',
  delisted:  'badge-danger',
}[s] ?? 'badge-neutral')
</script>

<style scoped>
.bar-enter-active, .bar-leave-active { transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); }
.bar-enter-from, .bar-leave-to { opacity: 0; transform: translateX(-50%) translateY(2rem); }

.modal-enter-active, .modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.96); }

.checkbox {
  width: 1rem;
  height: 1rem;
  border-radius: 0.25rem;
  border: 1.5px solid #cbd5e1;
  cursor: pointer;
  accent-color: #4f46e5;
}
</style>
