<template>
  <Head title="System Settings" />
  <StaffLayout page-title="System Settings" page-subtitle="Configure application behaviour, mail delivery, and notifications">
    <div class="space-y-6">

      <!-- Flash messages -->
      <div v-if="$page.props.flash?.success"
        class="flex items-center gap-3 px-4 py-3 rounded-xl bg-success-50 border border-success-200 text-success-700 text-sm">
        <CheckCircleIcon class="w-5 h-5 shrink-0" />{{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error"
        class="flex items-center gap-3 px-4 py-3 rounded-xl bg-danger-50 border border-danger-200 text-danger-700 text-sm">
        <ExclamationCircleIcon class="w-5 h-5 shrink-0" />{{ $page.props.flash.error }}
      </div>

      <form @submit.prevent="saveSettings">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

          <!-- ── LEFT: Settings panels ──────────────────────────────── -->
          <div class="lg:col-span-2 space-y-5">

            <!-- General Settings -->
            <SettingsCard title="General" icon="🏛️" description="Application name, URL and office information">
              <SettingField v-for="s in group('general')" :key="s.key" :setting="s" v-model="form[s.key]" />
            </SettingsCard>

            <!-- Mail / SMTP -->
            <SettingsCard title="Mail & SMTP" icon="📧" description="Configure how the system sends email notifications to beneficiaries">
              <div class="mb-4 p-3 rounded-xl bg-blue-50 border border-blue-100 text-xs text-blue-700 flex items-start gap-2">
                <InformationCircleIcon class="w-4 h-4 shrink-0 mt-0.5" />
                <span>
                  Use <strong>log</strong> as the mail driver to write emails to <code>storage/logs/laravel.log</code> during development.
                  Switch to <strong>smtp</strong> with real credentials for production.
                </span>
              </div>
              <SettingField v-for="s in group('mail')" :key="s.key" :setting="s" v-model="form[s.key]"
                :is-password="s.key === 'mail_password'" />

              <!-- Test email -->
              <div class="pt-4 border-t border-slate-100">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Test Email Delivery</p>
                <div class="flex items-center gap-3">
                  <input v-model="testEmail" type="email" class="form-input flex-1"
                    placeholder="Enter your email to receive a test message" />
                  <button type="button" @click="sendTestEmail" :disabled="testingEmail"
                    class="btn btn-secondary btn-sm shrink-0">
                    <PaperAirplaneIcon class="w-4 h-4" />
                    {{ testingEmail ? 'Sending…' : 'Send Test' }}
                  </button>
                </div>
              </div>
            </SettingsCard>

            <!-- Notifications -->
            <SettingsCard title="Notification Toggles" icon="🔔" description="Control which events trigger email notifications to beneficiaries">
              <div class="space-y-3">
                <div v-for="s in group('notifications')" :key="s.key"
                  class="flex items-center justify-between p-4 rounded-xl border border-slate-200 hover:border-brand-300 transition-colors">
                  <div>
                    <p class="text-sm font-semibold text-slate-800">{{ s.label }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ s.description }}</p>
                  </div>
                  <button type="button"
                    @click="form[s.key] = form[s.key] === '1' ? '0' : '1'"
                    :class="[
                      'relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent',
                      'transition-colors duration-200 cursor-pointer focus:outline-none',
                      form[s.key] === '1' ? 'bg-brand-600' : 'bg-slate-200'
                    ]">
                    <span
                      :class="[
                        'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow',
                        'transform transition-transform duration-200',
                        form[s.key] === '1' ? 'translate-x-5' : 'translate-x-0'
                      ]"
                    />
                  </button>
                </div>
              </div>
            </SettingsCard>

            <!-- Security -->
            <SettingsCard title="Security & Access" icon="🔐" description="Session lifetime and proxy limits">
              <SettingField v-for="s in group('security')" :key="s.key" :setting="s" v-model="form[s.key]" />
            </SettingsCard>

            <!-- Save button -->
            <div class="flex justify-end gap-3">
              <button type="button" @click="resetForm" class="btn btn-ghost">Reset Changes</button>
              <button type="submit" :disabled="form.processing || !isDirty" class="btn btn-primary">
                <CheckCircleIcon class="w-4 h-4" />
                {{ form.processing ? 'Saving…' : 'Save All Settings' }}
              </button>
            </div>
          </div>

          <!-- ── RIGHT: System Info ────────────────────────────────── -->
          <div class="space-y-5">
            <!-- System Info card -->
            <div class="card">
              <div class="card-header">
                <div class="flex items-center gap-2">
                  <ServerIcon class="w-5 h-5 text-brand-600" />
                  <h3 class="font-semibold text-slate-800">System Info</h3>
                </div>
              </div>
              <div class="card-body space-y-3">
                <InfoRow v-for="(val, key) in systemInfo" :key="key"
                  :label="infoLabels[key] || key" :value="val" />
              </div>
            </div>

            <!-- Queue / Worker hint -->
            <div class="card bg-slate-50">
              <div class="card-body space-y-3">
                <div class="flex items-center gap-2">
                  <BoltIcon class="w-5 h-5 text-warning-600" />
                  <h4 class="font-semibold text-slate-800 text-sm">Queue Worker</h4>
                </div>
                <p class="text-xs text-slate-500">Email notifications are processed by the queue worker. Start it with:</p>
                <div class="bg-slate-800 rounded-lg px-4 py-3 text-xs font-mono text-green-400">
                  php artisan queue:work
                </div>
                <p class="text-xs text-slate-400">Run once in a terminal while the server is active. For production, use a process manager (Supervisor).</p>
              </div>
            </div>

            <!-- Maintenance mode quick-toggle -->
            <div :class="['card border-2 transition-colors', form['maintenance_mode'] === '1' ? 'border-warning-400 bg-warning-50' : 'border-slate-200']">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <ExclamationTriangleIcon class="w-5 h-5" :class="form['maintenance_mode'] === '1' ? 'text-warning-600' : 'text-slate-400'" />
                    <h4 class="font-semibold text-sm" :class="form['maintenance_mode'] === '1' ? 'text-warning-800' : 'text-slate-700'">
                      Maintenance Mode
                    </h4>
                  </div>
                  <button type="button"
                    @click="form['maintenance_mode'] = form['maintenance_mode'] === '1' ? '0' : '1'"
                    :class="[
                      'relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 cursor-pointer',
                      form['maintenance_mode'] === '1' ? 'bg-warning-500' : 'bg-slate-200'
                    ]">
                    <span :class="['pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform transition-transform duration-200', form['maintenance_mode'] === '1' ? 'translate-x-5' : 'translate-x-0']" />
                  </button>
                </div>
                <p class="text-xs mt-2" :class="form['maintenance_mode'] === '1' ? 'text-warning-700' : 'text-slate-400'">
                  When enabled, only superadmins can access the system. All other roles see a maintenance page.
                </p>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import {
  CheckCircleIcon, ExclamationCircleIcon, InformationCircleIcon,
  ServerIcon, BoltIcon, ExclamationTriangleIcon, PaperAirplaneIcon,
} from '@heroicons/vue/24/outline'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
  settings:   Object,
  systemInfo: Object,
})

// ── Flatten settings into a key→value map ─────────────────────────────────────
const original = {}
Object.values(props.settings ?? {}).forEach(group => {
  Object.values(group).forEach(s => { original[s.key] = s.value ?? '' })
})

const form = useForm({ ...original })

const group = (name) => {
  const g = props.settings?.[name] ?? {}
  return Object.values(g)
}

const isDirty = computed(() =>
  Object.keys(original).some(k => form[k] !== original[k])
)

const resetForm = () => {
  Object.keys(original).forEach(k => { form[k] = original[k] })
}

const saveSettings = () => {
  form.transform(data => ({ settings: data }))
      .put(route('superadmin.settings.update'), {
        preserveScroll: true,
      })
}

// ── Test Email ────────────────────────────────────────────────────────────────
const testEmail    = ref('')
const testingEmail = ref(false)
const sendTestEmail = () => {
  if (!testEmail.value) return
  testingEmail.value = true
  router.post(route('superadmin.settings.test-email'), { test_email: testEmail.value }, {
    preserveScroll: true,
    onFinish: () => { testingEmail.value = false },
  })
}

// ── Info labels pretty-print ──────────────────────────────────────────────────
const infoLabels = {
  php_version:     'PHP Version',
  laravel_version: 'Laravel Version',
  db_driver:       'DB Driver',
  db_name:         'Database Name',
  mail_driver:     'Mail Driver (.env)',
  queue_driver:    'Queue Driver',
  app_env:         'Environment',
  app_debug:       'Debug Mode',
}

// ── Sub-components ────────────────────────────────────────────────────────────
const SettingsCard = {
  props: ['title', 'icon', 'description'],
  template: `
    <div class="card">
      <div class="card-header">
        <div class="flex items-center gap-2">
          <span class="text-lg">{{ icon }}</span>
          <div>
            <h3 class="font-semibold text-slate-800">{{ title }}</h3>
            <p class="text-xs text-slate-400 mt-0.5">{{ description }}</p>
          </div>
        </div>
      </div>
      <div class="card-body space-y-4">
        <slot />
      </div>
    </div>
  `
}

const SettingField = {
  props: ['setting', 'modelValue', 'isPassword'],
  emits: ['update:modelValue'],
  data() { return { showPw: false } },
  template: `
    <div>
      <label class="form-label">
        {{ setting.label }}
        <span v-if="setting.type === 'boolean'" class="text-xs text-slate-400 font-normal">(toggle in group above)</span>
      </label>
      <div class="relative" v-if="isPassword">
        <input
          :type="showPw ? 'text' : 'password'"
          :value="modelValue"
          @input="$emit('update:modelValue', $event.target.value)"
          class="form-input pr-10"
          :placeholder="setting.description" />
        <button type="button" @click="showPw = !showPw"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
          {{ showPw ? 'Hide' : 'Show' }}
        </button>
      </div>
      <input v-else-if="setting.type !== 'boolean'"
        :type="setting.type === 'integer' ? 'number' : 'text'"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        class="form-input"
        :placeholder="setting.description" />
      <p class="text-xs text-slate-400 mt-1">{{ setting.description }}</p>
    </div>
  `
}

const InfoRow = {
  props: ['label', 'value'],
  template: `
    <div class="flex items-center justify-between text-sm">
      <span class="text-slate-500">{{ label }}</span>
      <span class="font-mono text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded">{{ value }}</span>
    </div>
  `
}
</script>
