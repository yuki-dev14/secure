<template>
  <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
    <div class="flex items-center gap-3">
      <div class="w-12 h-12 rounded-lg overflow-hidden bg-white border border-slate-200 flex-shrink-0">
        <img v-if="beneficiary.photo_url" :src="beneficiary.photo_url" :alt="beneficiary.full_name" class="w-full h-full object-cover" />
        <UserIcon v-else class="w-6 h-6 text-slate-300 m-3" />
      </div>
      <div>
        <p class="font-semibold text-slate-800">{{ beneficiary.full_name }}</p>
        <p class="text-xs text-slate-500 font-mono">{{ beneficiary.unique_id }}</p>
        <span :class="['badge badge-sm mt-1', beneficiary.is_compliant ? 'badge-success' : 'badge-danger']">
          {{ beneficiary.is_compliant ? 'Compliant' : 'Non-Compliant' }}
        </span>
      </div>
    </div>
    <div class="text-xs text-slate-500 grid grid-cols-2 gap-1">
      <span>Brgy. {{ beneficiary.barangay }}</span>
      <span>{{ beneficiary.sex }}, {{ beneficiary.age }} yrs</span>
    </div>
  </div>
</template>

<script setup>
import { UserIcon } from '@heroicons/vue/24/outline'
defineProps({ beneficiary: Object, readonly: Boolean })
</script>
