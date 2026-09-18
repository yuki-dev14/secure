<template>
  <Head title="Change Your Password — SECURE 4Ps" />
  <div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden"
       style="background: linear-gradient(135deg, #330000 0%, #4d0000 50%, #800000 100%);">
    <!-- Ambient background glows -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
      <div class="absolute top-20 right-20 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 left-20 w-80 h-80 bg-red-300/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 w-full max-w-md">
      <!-- Header -->
      <div class="text-center mb-6">
        <img src="/logo.png" alt="SECURE 4Ps Logo" class="w-14 h-14 object-contain mx-auto mb-3 drop-shadow" />
        <h1 class="text-xl font-bold text-white">SECURE 4Ps</h1>
        <p class="text-white/70 text-xs mt-0.5">Beneficiary Account Security • Lipa City</p>
      </div>

      <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-white/20">
        <div class="px-8 pt-8 pb-6 border-b border-slate-100 text-center">
          <div class="w-14 h-14 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-red-100">
            <ShieldExclamationIcon class="w-7 h-7 text-red-600" />
          </div>
          <h2 class="text-xl font-bold text-slate-800">Change Your Password</h2>
          <p class="text-sm text-slate-500 mt-1">
            For security, you must set a new password before accessing your account.
          </p>
        </div>

        <form @submit.prevent="submit" class="px-8 py-6 space-y-5">
          <div class="p-3.5 bg-amber-50/90 border border-amber-200/80 rounded-xl flex items-start gap-3 text-amber-900 text-xs leading-relaxed">
            <InformationCircleIcon class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
            <p>This is your first login. Your default password from your DSWD card must be changed now.</p>
          </div>

          <div>
            <label class="form-label">New Password</label>
            <input
              v-model="form.password"
              type="password"
              placeholder="Min 8 chars, upper + lower + number"
              class="form-input"
              :class="{ 'border-danger-500': form.errors.password }"
              required
            />
            <p v-if="form.errors.password" class="form-error">{{ form.errors.password }}</p>
          </div>

          <div>
            <label class="form-label">Confirm New Password</label>
            <input
              v-model="form.password_confirmation"
              type="password"
              placeholder="Repeat your new password"
              class="form-input"
              required
            />
          </div>

          <!-- Password strength indicator -->
          <div class="space-y-1">
            <p class="text-xs text-slate-500">Password strength</p>
            <div class="flex gap-1">
              <div v-for="i in 4" :key="i"
                :class="[
                  'h-1.5 flex-1 rounded-full transition-all',
                  strength >= i ? strengthColor : 'bg-slate-200'
                ]">
              </div>
            </div>
            <p class="text-xs font-medium" :class="strengthTextColor">{{ strengthLabel }}</p>
          </div>

          <button type="submit" :disabled="form.processing || strength < 2" class="btn btn-primary w-full btn-lg font-semibold shadow-md cursor-pointer transition-all">
            <LockClosedIcon class="w-4 h-4" />
            {{ form.processing ? 'Saving…' : 'Set New Password' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'
import {
  ShieldExclamationIcon, LockClosedIcon, InformationCircleIcon,
} from '@heroicons/vue/24/outline'

const form = useForm({ password: '', password_confirmation: '' })

const strength = computed(() => {
  const p = form.password
  let s = 0
  if (p.length >= 8)         s++
  if (/[A-Z]/.test(p))       s++
  if (/[0-9]/.test(p))       s++
  if (/[^A-Za-z0-9]/.test(p)) s++
  return s
})

const strengthColor = computed(() => ({
  1: 'bg-danger-500', 2: 'bg-warning-500', 3: 'bg-brand-400', 4: 'bg-success-500',
}[strength.value] ?? 'bg-slate-200'))

const strengthTextColor = computed(() => ({
  1: 'text-danger-600', 2: 'text-warning-600', 3: 'text-brand-600', 4: 'text-success-600',
}[strength.value] ?? 'text-slate-400'))

const strengthLabel = computed(() => (['', 'Weak', 'Fair', 'Good', 'Strong'][strength.value] ?? ''))

const submit = () => form.post(route('beneficiary.password.update'))
</script>
