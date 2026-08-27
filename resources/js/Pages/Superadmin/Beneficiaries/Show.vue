<template>
  <Head :title="`${beneficiary.full_name} — Profile`" />
  <StaffLayout :page-title="beneficiary.unique_id" :page-subtitle="beneficiary.full_name">
    <div class="space-y-5">

      <!-- Flash messages -->
      <div v-if="$page.props.flash?.success"
        class="flex items-center gap-3 px-4 py-3 rounded-xl bg-success-50 border border-success-200 text-success-700 text-sm">
        <CheckCircleIcon class="w-5 h-5 flex-shrink-0" />
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error"
        class="flex items-center gap-3 px-4 py-3 rounded-xl bg-danger-50 border border-danger-200 text-danger-700 text-sm">
        <ExclamationCircleIcon class="w-5 h-5 flex-shrink-0" />
        {{ $page.props.flash.error }}
      </div>

      <!-- Top bar: status + actions -->
      <div class="flex flex-wrap items-center gap-3">
        <span :class="['badge', statusBadge(beneficiary.status)]">{{ beneficiary.status }}</span>

        <div class="ml-auto flex gap-2">
          <!-- Activate button: shown only for inactive/pending beneficiaries -->
          <button
            v-if="beneficiary.status === 'inactive'"
            @click="activateBeneficiary"
            :disabled="activating"
            class="btn btn-success btn-sm"
          >
            <CheckCircleIcon class="w-4 h-4" />
            {{ activating ? 'Activating…' : 'Activate & Issue Card' }}
          </button>
          <button @click="issueNewCard" :disabled="issuingCard" class="btn btn-secondary btn-sm">
            <CreditCardIcon class="w-4 h-4" />
            {{ issuingCard ? 'Issuing…' : 'Re-issue Card' }}
          </button>
          <Link v-if="beneficiary.card"
            :href="route('superadmin.beneficiaries.card.preview', beneficiary.id)"
            class="btn btn-secondary btn-sm">
            <EyeIcon class="w-4 h-4" />
            Preview Card
          </Link>
          <a v-if="beneficiary.card_path" :href="route('superadmin.beneficiaries.card.download', beneficiary.id)"
            target="_blank" class="btn btn-primary btn-sm">
            <ArrowDownTrayIcon class="w-4 h-4" />
            Download Card PDF
          </a>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <!-- LEFT: Profile + Family + Compliance + Proxies -->
        <div class="lg:col-span-2 space-y-5">

          <!-- Profile Card -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Beneficiary Profile</h3>
              <button @click="editing = !editing" class="btn btn-ghost btn-sm">
                <PencilIcon class="w-4 h-4" />
                {{ editing ? 'Cancel' : 'Edit' }}
              </button>
            </div>
            <div class="card-body">
              <div class="flex gap-5">
                <!-- Photo -->
                <div class="flex-shrink-0">
                  <div class="w-24 h-28 rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
                    <img v-if="beneficiary.photo_path && !imageError"
                      :src="`/storage/${beneficiary.photo_path}`"
                      @error="imageError = true" alt=""
                      class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center bg-slate-200">
                      <UserIcon class="w-10 h-10 text-slate-400" />
                    </div>
                  </div>
                </div>
                <!-- Fields -->
                <div class="flex-1 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                  <InfoRow label="Full Name"    :value="beneficiary.full_name" />
                  <InfoRow label="Unique ID"    :value="beneficiary.unique_id" mono />
                  <InfoRow label="Birthdate"    :value="beneficiary.birthdate" />
                  <InfoRow label="Age"          :value="`${beneficiary.age} years old`" />
                  <InfoRow label="Sex"          :value="beneficiary.sex" capitalize />
                  <InfoRow label="Civil Status" :value="beneficiary.civil_status" capitalize />
                  <InfoRow label="Contact"      :value="beneficiary.contact_number || '—'" />
                  <InfoRow label="Barangay"     :value="`Brgy. ${beneficiary.barangay}`" />
                </div>
              </div>
              <!-- Edit form -->
              <div v-if="editing" class="mt-5 pt-5 border-t border-slate-100 space-y-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                  <div>
                    <label class="form-label">Status</label>
                    <select v-model="editForm.status" class="form-select">
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                      <option value="suspended">Suspended</option>
                      <option value="graduated">Graduated</option>
                      <option value="delisted">Delisted</option>
                    </select>
                  </div>
                  <div>
                    <label class="form-label">Contact Number</label>
                    <input v-model="editForm.contact_number" type="text" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">Barangay</label>
                    <input v-model="editForm.barangay" type="text" class="form-input" />
                  </div>
                </div>
                <div>
                  <label class="form-label">Remarks</label>
                  <textarea v-model="editForm.remarks" class="form-input" rows="2"></textarea>
                </div>
                <div class="flex gap-2">
                  <button @click="saveEdit" :disabled="editForm.processing" class="btn btn-primary btn-sm">Save Changes</button>
                  <button @click="editing = false" class="btn btn-ghost btn-sm">Cancel</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Family Members -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Family Members</h3>
              <span class="badge badge-neutral">{{ beneficiary.family_members?.length ?? 0 }}</span>
            </div>
            <div class="table-wrapper">
              <table class="table">
                <thead>
                  <tr>
                    <th>Name</th><th>Relationship</th><th>Age</th><th>Education</th><th>Flags</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!beneficiary.family_members?.length">
                    <td colspan="5" class="text-center text-slate-400 py-8">No family members recorded.</td>
                  </tr>
                  <tr v-for="m in beneficiary.family_members" :key="m.id">
                    <td class="font-medium text-slate-700 text-sm">{{ m.first_name }} {{ m.last_name }}</td>
                    <td class="text-sm text-slate-500 capitalize">{{ m.relationship }}</td>
                    <td class="text-sm text-slate-500">{{ m.age }} yrs</td>
                    <td class="text-sm text-slate-500 capitalize">{{ m.education_level === 'not_applicable' ? '—' : (m.education_level || '—') }}</td>
                    <td class="flex gap-1">
                      <span v-if="m.is_school_age" class="badge badge-info badge-sm">School-age</span>
                      <span v-if="m.is_under_five" class="badge badge-warning badge-sm">Under 5</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>




          <!-- Compliance History -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Compliance Records</h3>
            </div>
            <div class="table-wrapper">
              <table class="table">
                <thead>
                  <tr>
                    <th>Period</th>
                    <th>Education</th>
                    <th>Health</th>
                    <th>FDS</th>
                    <th>Overall</th>
                    <th>Verified By</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!beneficiary.compliance_records?.length">
                    <td colspan="6" class="text-center text-slate-400 py-8">No compliance records yet.</td>
                  </tr>
                  <tr v-for="cr in beneficiary.compliance_records" :key="cr.id">
                    <td class="text-sm font-medium text-slate-700">{{ cr.period }}</td>
                    <td>
                      <span v-if="cr.edu_attendance_compliant === false" class="badge badge-danger badge-sm">✗ Non-Compliant</span>
                      <span v-else-if="cr.edu_attendance_compliant === true" class="badge badge-success badge-sm">✓ Compliant</span>
                      <span v-else class="badge badge-neutral badge-sm">— N/A</span>
                    </td>
                    <td>
                      <span v-if="cr.health_compliant === false" class="badge badge-danger badge-sm">✗ Non-Compliant</span>
                      <span v-else-if="cr.health_compliant === true" class="badge badge-success badge-sm">✓ Compliant</span>
                      <span v-else class="badge badge-neutral badge-sm">— N/A</span>
                    </td>
                    <td>
                      <span v-if="cr.fds_compliant === false" class="badge badge-danger badge-sm">✗ Non-Compliant</span>
                      <span v-else-if="cr.fds_compliant === true" class="badge badge-success badge-sm">✓ Compliant</span>
                      <span v-else class="badge badge-neutral badge-sm">— N/A</span>
                    </td>
                    <td>
                      <span :class="['badge badge-sm', cr.is_fully_compliant ? 'badge-success' : 'badge-danger']">
                        {{ cr.is_fully_compliant ? '✓ Compliant' : '✗ Non-Compliant' }}
                      </span>
                    </td>
                    <td class="text-sm text-slate-500">{{ cr.verifier?.name ?? '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- RIGHT: Card + Grants -->
        <div class="space-y-5">
          <!-- Card info -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">ID Card</h3>
            </div>
            <div class="card-body text-sm space-y-2">
              <template v-if="beneficiary.card">
                <InfoRow label="Card No."  :value="beneficiary.card.card_number" mono />
                <InfoRow label="Status"    :value="beneficiary.card.status" capitalize />
                <InfoRow label="Issued"    :value="beneficiary.card.issued_at ?? 'Pending'" />
              </template>
              <p v-else class="text-slate-400 text-center py-4">No card issued yet.</p>
            </div>
          </div>

          <!-- Grant calculations -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Grant History</h3>
            </div>
            <div class="card-body space-y-3">
              <div v-if="!beneficiary.grant_calculations?.length" class="text-center text-slate-400 text-sm py-4">
                No grant records yet.
              </div>
              <div v-for="g in beneficiary.grant_calculations" :key="g.id"
                class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between">
                  <span class="badge badge-neutral text-xs font-semibold">{{ g.distribution_event?.period ?? 'Period unknown' }}</span>
                  <p class="font-bold text-emerald-600 text-sm">
                    ₱{{ Number(g.total_grant_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                  </p>
                </div>
                <div class="mt-2 text-xs text-slate-500 grid grid-cols-3 gap-1 pt-2 border-t border-slate-100">
                  <span>Health: ₱{{ Number(g.health_grant_amount ?? 0).toLocaleString() }}</span>
                  <span>Edu: ₱{{ Number(g.education_grant_total ?? 0).toLocaleString() }}</span>
                  <span>Rice: ₱{{ Number(g.rice_subsidy_amount ?? 0).toLocaleString() }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>


  </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import {
  UserIcon, PencilIcon, CreditCardIcon, ArrowDownTrayIcon, EyeIcon,
  UsersIcon, PlusIcon, TrashIcon, XMarkIcon,
  IdentificationIcon, MapPinIcon, InformationCircleIcon,
  CheckCircleIcon, ExclamationCircleIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({ beneficiary: Object })

// ── Profile editing ─────────────────────────────────────────────────────────
const editing     = ref(false)
const issuingCard = ref(false)
const activating  = ref(false)
const imageError  = ref(false)

const editForm = useForm({
  first_name:     props.beneficiary.first_name,
  last_name:      props.beneficiary.last_name,
  middle_name:    props.beneficiary.middle_name,
  contact_number: props.beneficiary.contact_number,
  barangay:       props.beneficiary.barangay,
  office_id:      props.beneficiary.office_id,
  status:         props.beneficiary.status,
  remarks:        props.beneficiary.remarks,
})

const saveEdit = () => {
  editForm.put(route('superadmin.beneficiaries.update', props.beneficiary.id), {
    onSuccess: () => { editing.value = false },
  })
}

const activateBeneficiary = () => {
  if (!confirm(
    'Activate this beneficiary? This confirms that their documentary requirements have been verified.\n\nA QR card will be automatically issued.'
  )) return
  activating.value = true
  router.post(route('superadmin.beneficiaries.activate', props.beneficiary.id), {}, {
    onFinish: () => { activating.value = false },
  })
}

const issueNewCard = () => {
  if (!confirm('Re-issue a new card? The old card will be deactivated.')) return
  issuingCard.value = true
  router.post(route('superadmin.beneficiaries.card.issue', props.beneficiary.id), {}, {
    onFinish: () => { issuingCard.value = false },
  })
}



const statusBadge = (s) => ({
  active: 'badge-success', inactive: 'badge-neutral',
  suspended: 'badge-danger', graduated: 'badge-info', delisted: 'badge-danger',
}[s] ?? 'badge-neutral')

// Inline sub-components
const InfoRow = {
  props: ['label', 'value', 'mono', 'capitalize'],
  template: `
    <div>
      <p class="text-xs text-slate-400 mb-0.5">{{ label }}</p>
      <p :class="['font-medium text-slate-700', mono ? 'font-mono text-xs' : '', capitalize ? 'capitalize' : '']">
        {{ value ?? '—' }}
      </p>
    </div>
  `
}


</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.96); }
</style>
