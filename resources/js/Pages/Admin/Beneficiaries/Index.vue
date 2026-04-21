<template>
  <Head title="Beneficiaries" />
  <StaffLayout page-title="Beneficiaries" page-subtitle="All registered 4Ps beneficiaries">
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3 flex-wrap">
          <!-- Search -->
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input
              v-model="filters.search"
              type="text"
              placeholder="Search name or ID…"
              class="form-input pl-9 w-64"
              @input="search"
            />
          </div>
          <!-- Barangay filter -->
          <select v-model="filters.barangay" class="form-select w-48" @change="search">
            <option value="">All Barangays</option>
            <option v-for="b in barangays" :key="b" :value="b">{{ b }}</option>
          </select>
          <!-- Status filter -->
          <select v-model="filters.status" class="form-select w-40" @change="search">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
            <option value="graduated">Graduated</option>
            <option value="delisted">Delisted</option>
          </select>
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
      </div>

      <!-- Table -->
      <div class="card">
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
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
                <td colspan="7" class="text-center py-12 text-slate-400">
                  <UserGroupIcon class="w-10 h-10 mx-auto mb-2 opacity-30" />
                  <p>No beneficiaries found</p>
                </td>
              </tr>
              <tr v-for="b in beneficiaries.data" :key="b.id">
                <td>
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg overflow-hidden bg-slate-100 flex-shrink-0">
                      <img v-if="b.photo_path" :src="`/storage/${b.photo_path}`" class="w-full h-full object-cover" :alt="b.last_name" />
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
                    <Link :href="route('admin.beneficiaries.show', b.id)" class="btn btn-ghost btn-sm">
                      <EyeIcon class="w-4 h-4" />
                    </Link>
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
            <Link
              v-for="link in beneficiaries.links"
              :key="link.label"
              :href="link.url ?? '#'"
              :class="[
                'btn btn-sm',
                link.active ? 'btn-primary' : 'btn-secondary',
                !link.url ? 'opacity-40 cursor-not-allowed' : ''
              ]"
              v-html="link.label"
            />
          </div>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
  MagnifyingGlassIcon, UsersIcon,
  CheckBadgeIcon, UserGroupIcon, UserIcon,
  EyeIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  beneficiaries: Object,
  barangays:     Array,
})

const filters = reactive({
  search:   '',
  barangay: '',
  status:   '',
})

let searchTimeout = null
const search = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(route('admin.beneficiaries.index'), filters, {
      preserveState: true, preserveScroll: true, replace: true,
    })
  }, 400)
}

const statusBadge = (s) => ({
  active:    'badge-success',
  inactive:  'badge-neutral',
  suspended: 'badge-danger',
  graduated: 'badge-info',
  delisted:  'badge-danger',
}[s] ?? 'badge-neutral')
</script>
