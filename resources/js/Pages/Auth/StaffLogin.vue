<template>
  <div class="min-h-screen flex" style="background: linear-gradient(135deg, #003087 0%, #0051a8 50%, #1e40af 100%);">
    <!-- Left panel — branding -->
    <div class="hidden lg:flex lg:w-1/2 flex-col justify-between p-12 relative overflow-hidden">
      <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-0 right-0 w-72 h-72 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-blue-300/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>
      </div>

      <div class="relative">
        <div class="flex items-center gap-3 mb-12">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
            <span class="text-white font-bold text-base">4P</span>
          </div>
          <div>
            <p class="text-white font-bold text-lg leading-tight">SECURE 4Ps</p>
            <p class="text-white/60 text-xs">DSWD — Lipa City, Batangas</p>
          </div>
        </div>

        <h1 class="text-4xl font-bold text-white leading-tight mb-4">
          System for<br>
          <span style="color: #fcd116;">Eligibility Checking,</span><br>
          Unified Records &<br>Evaluation
        </h1>
        <p class="text-white/70 text-sm leading-relaxed max-w-sm">
          A secure digital verification platform for the Pantawid Pamilyang
          Pilipino Program (4Ps) — ensuring accurate, fraud-free delivery of
          cash grants to qualified beneficiaries.
        </p>
      </div>

      <div class="relative">
        <div class="flex gap-6 text-white/60 text-xs">
          <div><p class="text-2xl font-bold text-white mb-1">4Ps</p><p>Program</p></div>
          <div class="border-l border-white/20 pl-6"><p class="text-2xl font-bold text-white mb-1">RA 11310</p><p>Legal Basis</p></div>
          <div class="border-l border-white/20 pl-6"><p class="text-2xl font-bold text-white mb-1">Lipa City</p><p>Coverage</p></div>
        </div>
      </div>
    </div>

    <!-- Right panel — login form -->
    <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
      <div class="w-full max-w-md">
        <!-- Back to home -->
        <div class="mb-5">
          <Link :href="route('home')" class="inline-flex items-center gap-1.5 text-white/70 hover:text-white text-sm font-medium transition-colors group">
            <ArrowLeftIcon class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" />
            Back to Home
          </Link>
        </div>
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
          <!-- Card header -->
          <div class="px-8 pt-8 pb-6 border-b border-slate-100">
            <div class="flex items-center gap-3 mb-2 lg:hidden">
              <div class="w-8 h-8 rounded-xl gradient-dswd flex items-center justify-center">
                <span class="text-white font-bold text-xs">4P</span>
              </div>
              <span class="font-bold text-slate-800">SECURE 4Ps</span>
            </div>
            <h2 class="text-2xl font-bold text-slate-800">Staff Login</h2>
            <p class="text-sm text-slate-500 mt-1">
              For DSWD personnel only. Beneficiaries use the
              <Link :href="route('beneficiary.login')" class="text-brand-600 hover:underline font-medium">Beneficiary Portal</Link>.
            </p>
          </div>

          <form @submit.prevent="submit" class="px-8 py-6 space-y-5">
            <div>
              <label for="email" class="form-label">Email or Username</label>
              <input
                id="email"
                v-model="form.email"
                type="text"
                autocomplete="username"
                placeholder="staff@dswd.gov.ph"
                class="form-input"
                :class="{ 'border-danger-500 focus:border-danger-500': form.errors.email }"
                required
              />
              <p v-if="form.errors.email" class="form-error">
                <ExclamationCircleIcon class="w-3.5 h-3.5" />
                {{ form.errors.email }}
              </p>
            </div>

            <div>
              <label for="password" class="form-label">Password</label>
              <div class="relative">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="current-password"
                  placeholder="••••••••"
                  class="form-input pr-10"
                  required
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                >
                  <EyeIcon v-if="!showPassword" class="w-4 h-4" />
                  <EyeSlashIcon v-else class="w-4 h-4" />
                </button>
              </div>
              <p v-if="form.errors.password" class="form-error">
                <ExclamationCircleIcon class="w-3.5 h-3.5" />
                {{ form.errors.password }}
              </p>
            </div>

            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.remember" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-brand-600">
                <span class="text-sm text-slate-600">Remember me</span>
              </label>
            </div>

            <button
              type="submit"
              :disabled="form.processing"
              class="btn btn-primary w-full btn-lg"
            >
              <span v-if="form.processing" class="animate-spin">⏳</span>
              <LockClosedIcon v-else class="w-4 h-4" />
              {{ form.processing ? 'Signing in…' : 'Sign In' }}
            </button>
          </form>

          <div class="px-8 pb-6 text-center">
            <p class="text-xs text-slate-400">
              Protected by Data Privacy Act of 2012 (RA 10173)
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import {
  LockClosedIcon, ExclamationCircleIcon,
  EyeIcon, EyeSlashIcon, ArrowLeftIcon,
} from '@heroicons/vue/24/outline'

const showPassword = ref(false)

const form = useForm({
  email:    '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post(route('staff.login.post'), {
    onFinish: () => form.reset('password'),
  })
}
</script>
