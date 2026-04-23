<template>
  <Head title="My Profile" />
  <BeneficiaryLayout :unread-count="unread_count ?? 0">

    <div class="space-y-5">
      <!-- Profile Header Card -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg border border-white/50 overflow-hidden">
        <!-- Banner with photo anchored to its bottom edge -->
        <div class="h-28 gradient-dswd relative px-6 flex items-end">
          <div class="absolute inset-0 opacity-20"
            style="background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,.05) 10px, rgba(255,255,255,.05) 20px);">
          </div>
          <!-- Photo overlaps banner -->
          <div class="relative z-10 w-24 h-24 rounded-2xl overflow-hidden ring-4 ring-white shadow-xl shrink-0 bg-slate-100 translate-y-12">
            <img v-if="beneficiary.photo_path"
              :src="`/storage/${beneficiary.photo_path}`" :alt="beneficiary.full_name"
              class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center">
              <UserIcon class="w-10 h-10 text-slate-300" />
            </div>
          </div>
        </div>

        <!-- Name, ID, badges — padded to clear the overlapping photo -->
        <div class="px-6 pb-6 pt-14 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
          <div>
            <h1 class="text-xl font-bold text-slate-800">{{ beneficiary.full_name }}</h1>
            <p class="text-sm font-mono text-slate-500 mt-0.5">{{ beneficiary.unique_id }}</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <span :class="['badge', beneficiary.is_compliant ? 'badge-success' : 'badge-danger']">
              {{ beneficiary.is_compliant ? '✓ Compliant' : '✗ Non-Compliant' }}
            </span>
            <span class="badge badge-neutral capitalize">{{ beneficiary.status }}</span>
            <span class="badge badge-info">{{ beneficiary.enrollment_date ? 'Enrolled ' + formatYear(beneficiary.enrollment_date) : '4Ps Member' }}</span>
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
            <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">First Name</p><p class="font-medium text-slate-700">{{ beneficiary.first_name ?? '—' }}</p></div>
            <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Middle Name</p><p class="font-medium text-slate-700">{{ beneficiary.middle_name || '—' }}</p></div>
            <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Last Name</p><p class="font-medium text-slate-700">{{ beneficiary.last_name ?? '—' }}</p></div>
            <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Suffix</p><p class="font-medium text-slate-700">{{ beneficiary.suffix || 'None' }}</p></div>
            <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Birthdate</p><p class="font-medium text-slate-700">{{ formatDate(beneficiary.birthdate) }}</p></div>
            <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Age</p><p class="font-medium text-slate-700">{{ calcAge(beneficiary.birthdate) }}</p></div>
            <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Sex</p><p class="font-medium text-slate-700 capitalize">{{ beneficiary.sex ?? '—' }}</p></div>
            <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Civil Status</p><p class="font-medium text-slate-700 capitalize">{{ beneficiary.civil_status ?? '—' }}</p></div>
          </div>
        </div>

        <!-- Address & Contact -->
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
            <MapPinIcon class="w-5 h-5 text-brand-600" />
            <h2 class="font-semibold text-slate-800">Address &amp; Contact</h2>
          </div>
          <div class="p-5 space-y-4">
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
              <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Contact Number</p><p class="font-medium text-slate-700">{{ beneficiary.contact_number || 'Not provided' }}</p></div>
              <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Assigned Office</p><p class="font-medium text-slate-700 text-sm">{{ beneficiary.office?.name ?? 'N/A' }}</p></div>
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
            <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Listahanan ID</p><p class="font-medium text-slate-700">{{ beneficiary.listahanan_id || 'Not recorded' }}</p></div>
            <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Enrollment Date</p><p class="font-medium text-slate-700">{{ formatDate(beneficiary.enrollment_date) || 'Not recorded' }}</p></div>
            <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">4Ps Status</p><p class="font-medium text-slate-700 capitalize">{{ beneficiary.status ?? '—' }}</p></div>
            <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Family Size</p><p class="font-medium text-slate-700">{{ (beneficiary.family_members?.length ?? 0) + 1 }} members</p></div>
          </div>
        </div>

        <!-- ID Card & Portal Access -->
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden border border-white/50">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
            <CreditCardIcon class="w-5 h-5 text-brand-600" />
            <h2 class="font-semibold text-slate-800">ID Card &amp; Portal Access</h2>
          </div>
          <div class="p-5 space-y-4">
            <div v-if="beneficiary.card" class="grid grid-cols-2 gap-4">
              <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Card Number</p><p class="font-medium text-slate-700 font-mono text-xs">{{ beneficiary.card.card_number }}</p></div>
              <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Card Status</p><p class="font-medium text-slate-700 capitalize">{{ beneficiary.card.is_active ? 'Active' : 'Inactive' }}</p></div>
              <div><p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wider">Issued Date</p><p class="font-medium text-slate-700">{{ beneficiary.card.issued_at ? formatDate(beneficiary.card.issued_at) : 'Pending' }}</p></div>
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

defineProps({
  beneficiary:  Object,
  unread_count: Number,
})

const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' }) : '—'

const formatYear = (d) => d ? new Date(d).getFullYear() : ''

// Fallback age calculation — age is now in Beneficiary::$appends so it arrives via props.
// This is used only if the backend accessor somehow returns null (e.g. birthdate missing).
const calcAge = (birthdate) => {
  if (!birthdate) return '—'
  const birth = new Date(birthdate)
  const today = new Date()
  let age = today.getFullYear() - birth.getFullYear()
  const m = today.getMonth() - birth.getMonth()
  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--
  return `${age} years old`
}
</script>


