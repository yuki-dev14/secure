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
        <span :class="['badge', beneficiary.is_compliant ? 'badge-success' : 'badge-danger']">
          {{ beneficiary.is_compliant ? '✓ Compliant' : '✗ Non-Compliant' }}
        </span>
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
                    <img v-if="beneficiary.photo_path"
                      :src="`/storage/${beneficiary.photo_path}`"
                      class="w-full h-full object-cover" :alt="beneficiary.full_name" />
                    <div v-else class="w-full h-full flex items-center justify-center">
                      <UserIcon class="w-10 h-10 text-slate-300" />
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

          <!-- ─── Proxy Management ─────────────────────────────────────────── -->
          <div class="card">
            <div class="card-header">
              <div class="flex items-center gap-2">
                <UsersIcon class="w-5 h-5 text-brand-600" />
                <h3 class="font-semibold text-slate-800">Authorized Proxies</h3>
                <span :class="['badge badge-sm', proxyCount >= 2 ? 'badge-danger' : 'badge-neutral']">
                  {{ proxyCount }} / 2
                </span>
              </div>
              <button
                v-if="proxyCount < 2"
                @click="openAddProxy"
                class="btn btn-primary btn-sm">
                <PlusIcon class="w-4 h-4" />
                Add Proxy
              </button>
              <span v-else class="text-xs text-slate-400 italic">Maximum reached</span>
            </div>

            <!-- Proxy Cards -->
            <div class="card-body space-y-3">
              <div v-if="!beneficiary.proxies?.length"
                class="py-10 text-center text-slate-400">
                <UsersIcon class="w-10 h-10 mx-auto mb-2 opacity-30" />
                <p class="text-sm">No proxies registered for this beneficiary.</p>
                <p class="text-xs mt-1 text-slate-300">Proxies can claim grants on the beneficiary's behalf during distribution events.</p>
              </div>

              <div v-for="proxy in beneficiary.proxies" :key="proxy.id"
                class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-slate-50 transition-colors">
                <div class="flex items-start justify-between gap-3">
                  <!-- Avatar + Info -->
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center flex-shrink-0">
                      <UserIcon class="w-5 h-5 text-brand-600" />
                    </div>
                    <div>
                      <p class="font-semibold text-slate-800 text-sm">{{ proxy.first_name }} {{ proxy.middle_name ? proxy.middle_name[0] + '.' : '' }} {{ proxy.last_name }} {{ proxy.suffix ?? '' }}</p>
                      <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xs text-slate-500 capitalize">{{ proxy.relationship }}</span>
                        <span class="text-slate-300">·</span>
                        <span class="text-xs text-slate-500">{{ proxy.sex }}</span>
                        <span class="text-slate-300">·</span>
                        <span class="text-xs text-slate-500">{{ proxyAge(proxy.birthdate) }} yrs</span>
                      </div>
                      <div v-if="proxy.contact_number" class="text-xs text-slate-400 mt-0.5">
                        📞 {{ proxy.contact_number }}
                      </div>
                    </div>
                  </div>
                  <!-- Status + Actions -->
                  <div class="flex items-center gap-2 flex-shrink-0">
                    <span :class="['badge badge-sm', proxy.is_approved ? 'badge-success' : 'badge-warning']">
                      {{ proxy.is_approved ? '✓ Approved' : '⏳ Pending' }}
                    </span>
                    <button @click="editProxy(proxy)" class="btn btn-ghost btn-sm p-1.5" title="Edit">
                      <PencilIcon class="w-4 h-4" />
                    </button>
                    <button @click="removeProxy(proxy)" class="btn btn-ghost btn-sm p-1.5 text-danger-500 hover:bg-danger-50" title="Remove">
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </div>

                <!-- ID info row -->
                <div v-if="proxy.valid_id_type || proxy.valid_id_number" class="mt-3 pt-3 border-t border-slate-200 flex items-center gap-4 text-xs text-slate-500">
                  <div class="flex items-center gap-1">
                    <IdentificationIcon class="w-3.5 h-3.5" />
                    <span>{{ proxy.valid_id_type ?? 'ID not specified' }}</span>
                  </div>
                  <span v-if="proxy.valid_id_number" class="font-mono">{{ proxy.valid_id_number }}</span>
                </div>

                <!-- Address -->
                <div v-if="proxy.address" class="mt-1.5 text-xs text-slate-400 flex items-center gap-1">
                  <MapPinIcon class="w-3.5 h-3.5" />
                  {{ proxy.address }}
                </div>
              </div>
            </div>

            <!-- Info banner -->
            <div class="px-5 pb-4">
              <div class="flex items-start gap-2 p-3 bg-blue-50 rounded-xl border border-blue-100 text-xs text-blue-700">
                <InformationCircleIcon class="w-4 h-4 flex-shrink-0 mt-0.5" />
                <span>Approved proxies may claim cash grants on the beneficiary's behalf during distribution events. Their identity will be verified via their government-issued ID and recorded in the audit log.</span>
              </div>
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
                    <th>Period</th><th>Education</th><th>Health</th><th>FDS</th><th>Overall</th><th>Verified By</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!beneficiary.compliance_records?.length">
                    <td colspan="6" class="text-center text-slate-400 py-8">No compliance records yet.</td>
                  </tr>
                  <tr v-for="cr in beneficiary.compliance_records" :key="cr.id">
                    <td class="text-sm font-medium text-slate-700">{{ cr.period }}</td>
                    <td><ComplianceDot :value="cr.edu_attendance_compliant" /></td>
                    <td><ComplianceDot :value="cr.health_compliant" /></td>
                    <td><ComplianceDot :value="cr.fds_compliant" /></td>
                    <td>
                      <span :class="['badge badge-sm', cr.is_fully_compliant ? 'badge-success' : 'badge-danger']">
                        {{ cr.is_fully_compliant ? 'Compliant' : 'Non-Compliant' }}
                      </span>
                    </td>
                    <td class="text-sm text-slate-500">{{ cr.verifier?.name ?? '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- RIGHT: Card + Grants + Claims -->
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
                  <p class="text-xs text-slate-500">{{ g.distribution_event?.period ?? 'Period unknown' }}</p>
                  <p class="font-bold text-success-600 text-sm">
                    ₱{{ Number(g.total_grant_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                  </p>
                </div>
                <div class="mt-1.5 text-xs text-slate-400 grid grid-cols-3 gap-1">
                  <span>Health: ₱{{ Number(g.health_grant_amount).toLocaleString() }}</span>
                  <span>Edu: ₱{{ Number(g.education_grant_total).toLocaleString() }}</span>
                  <span>Rice: ₱{{ Number(g.rice_subsidy_amount).toLocaleString() }}</span>
                </div>
                <p class="text-xs text-slate-300 mt-1">{{ g.months_covered }} months covered</p>
              </div>
            </div>
          </div>

          <!-- Distribution records -->
          <div class="card">
            <div class="card-header">
              <h3 class="font-semibold text-slate-800">Cash Grant Claims</h3>
            </div>
            <div class="card-body space-y-2">
              <div v-if="!beneficiary.distributions?.length" class="text-center text-slate-400 text-sm py-4">
                No claims recorded.
              </div>
              <div v-for="d in beneficiary.distributions" :key="d.id"
                class="flex items-center justify-between text-sm py-2 border-b border-slate-100 last:border-0">
                <div>
                  <p class="text-slate-700 font-medium">{{ d.distribution_event?.period ?? '—' }}</p>
                  <p class="text-xs text-slate-400">{{ d.claimed_by_type === 'proxy' ? '👤 Via Proxy' : '✓ Self' }}</p>
                </div>
                <p class="font-bold text-success-600">₱{{ Number(d.amount_released).toLocaleString() }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ─── Add / Edit Proxy Modal ──────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showProxyModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
          @click.self="closeProxyModal">
          <!-- Backdrop -->
          <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeProxyModal"></div>

          <!-- Modal panel -->
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-brand-100 flex items-center justify-center">
                  <UsersIcon class="w-4 h-4 text-brand-600" />
                </div>
                <h2 class="font-semibold text-slate-800">
                  {{ editingProxy ? 'Edit Proxy' : 'Add Authorized Proxy' }}
                </h2>
              </div>
              <button @click="closeProxyModal" class="btn btn-ghost btn-sm p-1.5">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <!-- Body -->
            <form @submit.prevent="submitProxy" class="px-6 py-5 space-y-4">
              <!-- Limit notice -->
              <div v-if="!editingProxy" class="flex items-center gap-2 p-3 bg-amber-50 border border-amber-100 rounded-xl text-xs text-amber-700">
                <InformationCircleIcon class="w-4 h-4 flex-shrink-0" />
                <span>A maximum of <strong>2 proxies</strong> are allowed per beneficiary per RA 11310 guidelines.</span>
              </div>

              <!-- Name row -->
              <div class="grid grid-cols-3 gap-3">
                <div class="col-span-1">
                  <label class="form-label">First Name <span class="text-danger-500">*</span></label>
                  <input v-model="proxyForm.first_name" type="text" class="form-input"
                    :class="{ 'border-danger-400': proxyForm.errors.first_name }"
                    placeholder="Maria" required />
                  <p v-if="proxyForm.errors.first_name" class="text-xs text-danger-600 mt-1">{{ proxyForm.errors.first_name }}</p>
                </div>
                <div class="col-span-1">
                  <label class="form-label">Middle Name</label>
                  <input v-model="proxyForm.middle_name" type="text" class="form-input" placeholder="Optional" />
                </div>
                <div class="col-span-1">
                  <label class="form-label">Last Name <span class="text-danger-500">*</span></label>
                  <input v-model="proxyForm.last_name" type="text" class="form-input"
                    :class="{ 'border-danger-400': proxyForm.errors.last_name }"
                    placeholder="Santos" required />
                  <p v-if="proxyForm.errors.last_name" class="text-xs text-danger-600 mt-1">{{ proxyForm.errors.last_name }}</p>
                </div>
              </div>

              <!-- Birthdate + Sex + Suffix -->
              <div class="grid grid-cols-3 gap-3">
                <div>
                  <label class="form-label">Birthdate <span class="text-danger-500">*</span></label>
                  <input v-model="proxyForm.birthdate" type="date" class="form-input" required />
                  <p v-if="proxyForm.errors.birthdate" class="text-xs text-danger-600 mt-1">{{ proxyForm.errors.birthdate }}</p>
                </div>
                <div>
                  <label class="form-label">Sex <span class="text-danger-500">*</span></label>
                  <select v-model="proxyForm.sex" class="form-select" required>
                    <option value="">Select…</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Suffix</label>
                  <input v-model="proxyForm.suffix" type="text" class="form-input" placeholder="Jr., Sr., III" />
                </div>
              </div>

              <!-- Relationship + Contact -->
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="form-label">Relationship <span class="text-danger-500">*</span></label>
                  <select v-model="proxyForm.relationship" class="form-select" required>
                    <option value="">Select…</option>
                    <option value="spouse">Spouse</option>
                    <option value="child">Child</option>
                    <option value="parent">Parent</option>
                    <option value="sibling">Sibling</option>
                    <option value="grandchild">Grandchild</option>
                    <option value="grandparent">Grandparent</option>
                    <option value="in-law">In-law</option>
                    <option value="neighbor">Neighbor</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Contact Number</label>
                  <input v-model="proxyForm.contact_number" type="tel" class="form-input" placeholder="09XX XXX XXXX" />
                </div>
              </div>

              <!-- Address -->
              <div>
                <label class="form-label">Address</label>
                <input v-model="proxyForm.address" type="text" class="form-input" placeholder="House no., Street, Barangay, City" />
              </div>

              <!-- Valid ID -->
              <div class="pt-3 border-t border-slate-100">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Government-Issued ID</p>
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="form-label">ID Type</label>
                    <select v-model="proxyForm.valid_id_type" class="form-select">
                      <option value="">Select ID type…</option>
                      <option value="PhilSys (National ID)">PhilSys (National ID)</option>
                      <option value="Passport">Passport</option>
                      <option value="Driver's License">Driver's License</option>
                      <option value="SSS ID">SSS ID</option>
                      <option value="GSIS ID">GSIS ID</option>
                      <option value="PhilHealth ID">PhilHealth ID</option>
                      <option value="Voter's ID">Voter's ID</option>
                      <option value="Barangay ID">Barangay ID</option>
                      <option value="Senior Citizen ID">Senior Citizen ID</option>
                      <option value="PWD ID">PWD ID</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                  <div>
                    <label class="form-label">ID Number</label>
                    <input v-model="proxyForm.valid_id_number" type="text" class="form-input" placeholder="ID number" />
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                <button type="button" @click="closeProxyModal" class="btn btn-ghost">Cancel</button>
                <button type="submit" :disabled="proxyForm.processing" class="btn btn-primary">
                  <span v-if="proxyForm.processing">Saving…</span>
                  <span v-else>{{ editingProxy ? 'Save Changes' : 'Add Proxy' }}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
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

// ── Proxy management ────────────────────────────────────────────────────────
const showProxyModal = ref(false)
const editingProxy   = ref(null)   // null = add mode, object = edit mode

const proxyCount = computed(() => props.beneficiary.proxies?.length ?? 0)

const blankProxyForm = () => ({
  first_name:      '',
  last_name:       '',
  middle_name:     '',
  suffix:          '',
  birthdate:       '',
  sex:             '',
  relationship:    '',
  contact_number:  '',
  address:         '',
  valid_id_type:   '',
  valid_id_number: '',
})

const proxyForm = useForm(blankProxyForm())

const openAddProxy = () => {
  editingProxy.value = null
  proxyForm.reset()
  Object.assign(proxyForm, blankProxyForm())
  showProxyModal.value = true
}

const editProxy = (proxy) => {
  editingProxy.value = proxy
  proxyForm.first_name      = proxy.first_name      ?? ''
  proxyForm.last_name       = proxy.last_name       ?? ''
  proxyForm.middle_name     = proxy.middle_name     ?? ''
  proxyForm.suffix          = proxy.suffix          ?? ''
  proxyForm.birthdate       = proxy.birthdate       ?? ''
  proxyForm.sex             = proxy.sex             ?? ''
  proxyForm.relationship    = proxy.relationship    ?? ''
  proxyForm.contact_number  = proxy.contact_number  ?? ''
  proxyForm.address         = proxy.address         ?? ''
  proxyForm.valid_id_type   = proxy.valid_id_type   ?? ''
  proxyForm.valid_id_number = proxy.valid_id_number ?? ''
  showProxyModal.value = true
}

const closeProxyModal = () => {
  showProxyModal.value = false
  editingProxy.value   = null
  proxyForm.clearErrors()
}

const submitProxy = () => {
  if (editingProxy.value) {
    proxyForm.put(
      route('superadmin.beneficiaries.proxies.update', {
        beneficiary: props.beneficiary.id,
        proxy:       editingProxy.value.id,
      }),
      { onSuccess: closeProxyModal }
    )
  } else {
    proxyForm.post(
      route('superadmin.beneficiaries.proxies.store', props.beneficiary.id),
      { onSuccess: closeProxyModal }
    )
  }
}

const removeProxy = (proxy) => {
  if (!confirm(`Remove "${proxy.first_name} ${proxy.last_name}" as an authorized proxy? This cannot be undone.`)) return
  router.delete(
    route('superadmin.beneficiaries.proxies.destroy', {
      beneficiary: props.beneficiary.id,
      proxy:       proxy.id,
    })
  )
}

// ── Helpers ─────────────────────────────────────────────────────────────────
const proxyAge = (birthdate) => {
  if (!birthdate) return '?'
  const born = new Date(birthdate)
  const now  = new Date()
  return now.getFullYear() - born.getFullYear() -
    (now < new Date(now.getFullYear(), born.getMonth(), born.getDate()) ? 1 : 0)
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

const ComplianceDot = {
  props: ['value'],
  template: `
    <span :class="value === true ? 'text-success-600' : value === false ? 'text-danger-600' : 'text-slate-300'">
      {{ value === true ? '✓' : value === false ? '✗' : '—' }}
    </span>
  `
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.96); }
</style>
