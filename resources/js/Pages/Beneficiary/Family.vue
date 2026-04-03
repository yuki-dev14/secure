<template>
  <Head title="My Family" />
  <BeneficiaryLayout :unread-count="unread_count ?? 0">
    <div class="space-y-5">

      <!-- Family header card -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-5 shadow-lg border border-white/50 flex flex-wrap items-center gap-4">
        <div>
          <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
            <UsersIcon class="w-5 h-5 text-brand-600" />
            Household Members
          </h1>
          <p class="text-sm text-slate-500 mt-0.5">
            {{ totalMembers }} total member{{ totalMembers !== 1 ? 's' : '' }} — including you as household representative
          </p>
        </div>
        <div class="ml-auto flex flex-wrap gap-2">
          <span class="badge badge-info">{{ schoolAgeCount }} school-age</span>
          <span class="badge badge-warning">{{ underFiveCount }} under-5</span>
          <span class="badge badge-neutral">{{ proxiesCount }} {{ proxiesCount === 1 ? 'proxy' : 'proxies' }}</span>
        </div>
      </div>

      <!-- Household Representative -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
        <div class="px-5 py-3 bg-brand-600 flex items-center gap-2">
          <ShieldCheckIcon class="w-4 h-4 text-white" />
          <h2 class="text-sm font-semibold text-white">Household Representative (You)</h2>
        </div>
        <div class="p-5 flex items-start gap-4">
          <div class="w-14 h-14 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0">
            <img v-if="beneficiary.photo_path"
              :src="`/storage/${beneficiary.photo_path}`"
              class="w-full h-full object-cover" :alt="beneficiary.full_name" />
            <div v-else class="w-full h-full flex items-center justify-center">
              <UserIcon class="w-7 h-7 text-slate-300" />
            </div>
          </div>
          <div class="flex-1 grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-2 text-sm">
            <FamilyField label="Full Name"    :value="beneficiary.full_name" />
            <FamilyField label="Unique ID"    :value="beneficiary.unique_id" mono />
            <FamilyField label="Sex"          :value="beneficiary.sex" capitalize />
            <FamilyField label="Birthdate"    :value="formatDate(beneficiary.birthdate)" />
            <FamilyField label="Age"          :value="`${beneficiary.age} yrs`" />
            <FamilyField label="Civil Status" :value="beneficiary.civil_status" capitalize />
          </div>
        </div>
      </div>

      <!-- Family Members -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <UsersIcon class="w-5 h-5 text-slate-500" />
            <h2 class="font-semibold text-slate-800">Family Members</h2>
          </div>
          <span class="badge badge-neutral">{{ beneficiary.family_members?.length ?? 0 }} members</span>
        </div>

        <div v-if="!beneficiary.family_members?.length" class="px-5 py-12 text-center text-slate-400">
          <UsersIcon class="w-10 h-10 opacity-20 mx-auto mb-2" />
          <p>No family members recorded yet.</p>
          <p class="text-xs mt-1">Contact your Barangay Social Welfare Center to update records.</p>
        </div>

        <div v-else class="divide-y divide-slate-50">
          <div v-for="(member, idx) in beneficiary.family_members" :key="member.id"
            class="px-5 py-4">
            <div class="flex items-start gap-4">
              <!-- Avatar with index -->
              <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-sm font-bold text-slate-500 shrink-0">
                {{ idx + 1 }}
              </div>

              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                  <p class="font-semibold text-slate-800">{{ member.first_name }} {{ member.last_name }}</p>
                  <span class="badge badge-neutral badge-sm capitalize">{{ member.relationship }}</span>
                  <span v-if="member.is_school_age" class="badge badge-info badge-sm">School-age</span>
                  <span v-if="member.is_under_five" class="badge badge-warning badge-sm">Under-5</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-1.5 text-sm">
                  <FamilyField label="Birthdate"    :value="formatDate(member.birthdate)" />
                  <FamilyField label="Age"          :value="`${member.age} yrs`" />
                  <FamilyField label="Sex"          :value="member.sex" capitalize />
                  <FamilyField v-if="member.is_school_age"
                    label="Education" :value="educationLabel(member.education_level)" />
                </div>

                <!-- School info -->
                <div v-if="member.school_name || member.grade_level"
                  class="mt-2 flex items-center gap-2 text-xs text-slate-500 bg-brand-50 px-3 py-1.5 rounded-lg border border-brand-100 w-fit">
                  <AcademicCapIcon class="w-3.5 h-3.5 text-brand-500 shrink-0" />
                  <span>{{ [member.school_name, member.grade_level].filter(Boolean).join(' — ') }}</span>
                </div>

                <!-- Attendance rate -->
                <div v-if="member.attendance_rate != null"
                  class="mt-2 flex items-center gap-2">
                  <div class="flex-1 max-w-[200px]">
                    <div class="flex items-center justify-between text-xs mb-0.5">
                      <span class="text-slate-400">Attendance</span>
                      <span :class="member.attendance_rate >= 85 ? 'text-success-600' : 'text-danger-600'" class="font-medium">
                        {{ member.attendance_rate }}%
                      </span>
                    </div>
                    <div class="h-1.5 bg-slate-200 rounded-full overflow-hidden">
                      <div
                        class="h-full rounded-full"
                        :class="member.attendance_rate >= 85 ? 'bg-success-500' : 'bg-danger-500'"
                        :style="`width: ${member.attendance_rate}%`"
                      ></div>
                    </div>
                  </div>
                  <span class="text-xs" :class="member.attendance_rate >= 85 ? 'text-success-600' : 'text-danger-600'">
                    {{ member.attendance_rate >= 85 ? '✓ Compliant' : '✗ Below 85%' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Authorized Proxies -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <UserGroupIcon class="w-5 h-5 text-slate-500" />
            <div>
              <h2 class="font-semibold text-slate-800">Authorized Proxies</h2>
              <p class="text-xs text-slate-400">Can claim your grant on your behalf</p>
            </div>
          </div>
          <span class="badge badge-neutral">Max 2</span>
        </div>

        <div v-if="!beneficiary.proxies?.length" class="px-5 py-10 text-center text-slate-400">
          <UserGroupIcon class="w-10 h-10 opacity-20 mx-auto mb-2" />
          <p>No proxies registered.</p>
          <p class="text-xs mt-1 max-w-xs mx-auto">
            If you cannot personally claim, register a proxy at your Barangay Social Welfare Center.
            Valid proxies must present authorization letter + valid ID.
          </p>
        </div>

        <div v-else class="divide-y divide-slate-50">
          <div v-for="proxy in beneficiary.proxies" :key="proxy.id" class="px-5 py-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">
              <UserIcon class="w-5 h-5 text-slate-400" />
            </div>
            <div class="flex-1">
              <p class="font-semibold text-slate-800 text-sm">{{ proxy.full_name }}</p>
              <p class="text-xs text-slate-500 capitalize">{{ proxy.relationship }} · {{ proxy.contact_number ?? 'No contact' }}</p>
            </div>
            <div class="flex flex-col items-end gap-1">
              <span :class="['badge badge-sm', proxy.is_active ? 'badge-success' : 'badge-neutral']">
                {{ proxy.is_active ? 'Active' : 'Inactive' }}
              </span>
              <span v-if="proxy.has_valid_id" class="badge badge-success badge-sm">ID ✓</span>
              <span v-else class="badge badge-warning badge-sm">No ID on file</span>
            </div>
          </div>
        </div>

        <div class="px-5 py-3 bg-slate-50 border-t border-slate-100">
          <p class="text-xs text-slate-400 text-center">
            To add, change, or remove a proxy, visit your Barangay Social Welfare Center with a valid ID and authorization letter.
          </p>
        </div>
      </div>
    </div>
  </BeneficiaryLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import {
  UsersIcon, UserIcon, UserGroupIcon,
  ShieldCheckIcon, AcademicCapIcon,
} from '@heroicons/vue/24/outline'
import BeneficiaryLayout from '@/Layouts/BeneficiaryLayout.vue'

const props = defineProps({
  beneficiary:  Object,
  unread_count: Number,
})

const totalMembers   = computed(() => (props.beneficiary?.family_members?.length ?? 0) + 1)
const schoolAgeCount = computed(() => props.beneficiary?.family_members?.filter(m => m.is_school_age).length ?? 0)
const underFiveCount = computed(() => props.beneficiary?.family_members?.filter(m => m.is_under_five).length ?? 0)
const proxiesCount   = computed(() => props.beneficiary?.proxies?.length ?? 0)

const educationLabel = (level) => ({
  daycare:     'Day Care',
  preschool:   'Preschool',
  elementary:  'Elementary',
  junior_high: 'Junior High School',
  senior_high: 'Senior High School',
}[level] ?? level ?? '—')

const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'

// Inline field component
const FamilyField = {
  props: ['label', 'value', 'capitalize', 'mono'],
  template: `
    <div>
      <p class="text-xs text-slate-400 mb-0.5">{{ label }}</p>
      <p :class="['text-slate-700 font-medium', capitalize ? 'capitalize' : '', mono ? 'font-mono text-xs' : '']">
        {{ value ?? '—' }}
      </p>
    </div>
  `
}
</script>
