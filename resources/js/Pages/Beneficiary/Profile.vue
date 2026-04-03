<template>
  <Head title="My Profile" />
  <BeneficiaryLayout :unread-count="unread_count ?? 0">

    <div class="space-y-5">
      <!-- Profile Header Card -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
        <div class="h-24 gradient-dswd relative">
          <div class="absolute inset-0 opacity-20"
            style="background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,.05) 10px, rgba(255,255,255,.05) 20px);">
          </div>
        </div>
        <div class="px-6 pb-6 -mt-12 flex flex-col sm:flex-row items-start sm:items-end gap-4">
          <!-- Photo -->
          <div class="w-24 h-24 rounded-2xl overflow-hidden ring-4 ring-white shadow-xl flex-shrink-0 bg-slate-100">
            <img v-if="beneficiary.photo_path"
              :src="`/storage/${beneficiary.photo_path}`" :alt="beneficiary.full_name"
              class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center">
              <UserIcon class="w-10 h-10 text-slate-300" />
            </div>
          </div>
          <div class="flex-1 min-w-0 sm:pb-1">
            <h1 class="text-xl font-bold text-slate-800 truncate">{{ beneficiary.full_name }}</h1>
            <p class="text-sm font-mono text-slate-500">{{ beneficiary.unique_id }}</p>
            <div class="flex flex-wrap gap-2 mt-2">
              <span :class="['badge', beneficiary.is_compliant ? 'badge-success' : 'badge-danger']">
                {{ beneficiary.is_compliant ? '✓ Compliant' : '✗ Non-Compliant' }}
              </span>
              <span class="badge badge-neutral capitalize">{{ beneficiary.status }}</span>
              <span class="badge badge-info">{{ beneficiary.enrollment_date ? 'Enrolled ' + formatYear(beneficiary.enrollment_date) : '4Ps Member' }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Personal Information -->
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
            <IdentificationIcon class="w-5 h-5 text-brand-600" />
            <h2 class="font-semibold text-slate-800">Personal Information</h2>
          </div>
          <div class="p-5 grid grid-cols-2 gap-4">
            <ProfileField label="First Name"    :value="beneficiary.first_name" />
            <ProfileField label="Middle Name"   :value="beneficiary.middle_name || '—'" />
            <ProfileField label="Last Name"     :value="beneficiary.last_name" />
            <ProfileField label="Suffix"        :value="beneficiary.suffix || 'None'" />
            <ProfileField label="Birthdate"     :value="formatDate(beneficiary.birthdate)" />
            <ProfileField label="Age"           :value="`${beneficiary.age} years old`" />
            <ProfileField label="Sex"           :value="beneficiary.sex" capitalize />
            <ProfileField label="Civil Status"  :value="beneficiary.civil_status" capitalize />
          </div>
        </div>

        <!-- Address & Contact -->
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
            <MapPinIcon class="w-5 h-5 text-brand-600" />
            <h2 class="font-semibold text-slate-800">Address & Contact</h2>
          </div>
          <div class="p-5 space-y-4">
            <!-- Full address block -->
            <div>
              <p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Home Address</p>
              <p class="font-medium text-slate-700">
                {{ [beneficiary.house_no, beneficiary.street, beneficiary.purok ? 'Purok ' + beneficiary.purok : ''].filter(Boolean).join(', ') || '—' }}
              </p>
              <p class="font-medium text-slate-700">
                Brgy. {{ beneficiary.barangay }}, Lipa City, Batangas
              </p>
            </div>
            <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-100">
              <ProfileField label="Contact Number" :value="beneficiary.contact_number || 'Not provided'" />
              <ProfileField label="Assigned Office" :value="beneficiary.office?.name ?? 'N/A'" small />
            </div>
          </div>
        </div>

        <!-- Program Enrollment -->
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
            <ClipboardDocumentListIcon class="w-5 h-5 text-brand-600" />
            <h2 class="font-semibold text-slate-800">Program Enrollment</h2>
          </div>
          <div class="p-5 grid grid-cols-2 gap-4">
            <ProfileField label="Listahanan ID"   :value="beneficiary.listahanan_id || 'Not recorded'" />
            <ProfileField label="Enrollment Date" :value="formatDate(beneficiary.enrollment_date) || 'Not recorded'" />
            <ProfileField label="4Ps Status"      :value="beneficiary.status" capitalize />
            <ProfileField label="Family Size"     :value="`${(beneficiary.family_members?.length ?? 0) + 1} members`" />
          </div>
        </div>

        <!-- ID Card & Portal Access -->
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
            <CreditCardIcon class="w-5 h-5 text-brand-600" />
            <h2 class="font-semibold text-slate-800">ID Card & Portal Access</h2>
          </div>
          <div class="p-5 space-y-4">
            <div v-if="beneficiary.card" class="grid grid-cols-2 gap-4">
              <ProfileField label="Card Number"  :value="beneficiary.card.card_number" mono />
              <ProfileField label="Card Status"  :value="beneficiary.card.status" capitalize />
              <ProfileField label="Issued Date"  :value="beneficiary.card.issued_at ? formatDate(beneficiary.card.issued_at) : 'Pending'" />
            </div>
            <div v-else class="flex items-center gap-3 p-3 bg-warning-50 rounded-xl border border-yellow-200">
              <ExclamationTriangleIcon class="w-5 h-5 text-warning-600 shrink-0" />
              <p class="text-sm text-warning-700">No ID card issued yet. Please visit your Barangay Social Welfare Center.</p>
            </div>

            <!-- Portal account -->
            <div class="pt-3 border-t border-slate-100">
              <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-2">Portal Account</p>
              <div class="flex items-center gap-3 p-3 bg-success-50 rounded-xl border border-green-200">
                <CheckCircleIcon class="w-5 h-5 text-success-600 shrink-0" />
                <div>
                  <p class="text-sm font-medium text-success-700">Account Active</p>
                  <p class="text-xs text-success-600 mt-0.5">You are logged in as {{ beneficiary.unique_id }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Privacy Notice -->
      <div class="bg-white/30 backdrop-blur-sm rounded-xl px-5 py-3 text-center">
        <p class="text-white/60 text-xs">
          🔒 Your personal information is protected under the Data Privacy Act of 2012 (RA 10173).
          For corrections, contact your Barangay Social Welfare Center.
        </p>
      </div>
    </div>
  </BeneficiaryLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import {
  UserIcon, IdentificationIcon, MapPinIcon,
  ClipboardDocumentListIcon, CreditCardIcon,
  ExclamationTriangleIcon, CheckCircleIcon,
} from '@heroicons/vue/24/outline'
import BeneficiaryLayout from '@/Layouts/BeneficiaryLayout.vue'

const props = defineProps({
  beneficiary:  Object,
  unread_count: Number,
})

const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' }) : '—'

const formatYear = (d) => d ? new Date(d).getFullYear() : ''

// Inline sub-component for cleanliness
const ProfileField = {
  props: ['label', 'value', 'capitalize', 'mono', 'small'],
  template: `
    <div>
      <p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">{{ label }}</p>
      <p :class="[
        'font-medium text-slate-700',
        capitalize ? 'capitalize' : '',
        mono ? 'font-mono text-xs' : '',
        small ? 'text-sm' : ''
      ]">{{ value ?? '—' }}</p>
    </div>
  `
}
</script>
