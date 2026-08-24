<template>
  <Head title="System Maintenance — SECURE 4Ps" />

  <div class="min-h-screen flex items-center justify-center p-6 bg-slate-900 text-white font-sans relative overflow-hidden">
    <!-- Crimson Background Glow -->
    <div class="absolute top-1/4 right-1/4 w-96 h-96 bg-red-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 left-1/4 w-80 h-80 bg-red-900/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative z-10 max-w-lg w-full text-center space-y-6">
      <div class="w-20 h-20 mx-auto rounded-3xl bg-red-950/80 border border-red-700/50 flex items-center justify-center shadow-xl">
        <WrenchScrewdriverIcon class="w-10 h-10 text-red-400 animate-pulse" />
      </div>

      <div class="space-y-2">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-500/10 border border-red-500/20 text-red-300 text-xs font-semibold uppercase tracking-wider">
          <span class="w-2 h-2 rounded-full bg-red-400 animate-ping"></span>
          Scheduled Maintenance
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight">System Under Maintenance</h1>
        <p class="text-slate-400 text-sm leading-relaxed max-w-md mx-auto">
          The SECURE 4Ps verification system is currently undergoing scheduled system updates. Access is temporarily restricted to system administrators.
        </p>
      </div>

      <div class="pt-4 border-t border-slate-800 flex items-center justify-center gap-4 text-xs text-slate-500">
        <p>DSWD Field Office IV-A — Lipa City, Batangas</p>
        <span>•</span>
        <Link :href="route('staff.login')" class="text-red-400 hover:text-red-300 font-medium underline">
          Staff Sign In
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { WrenchScrewdriverIcon } from '@heroicons/vue/24/outline'
</script>
