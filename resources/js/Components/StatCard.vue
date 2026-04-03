<template>
  <div class="stat-card">
    <div class="stat-card-body">
      <div :class="['stat-icon', color]">
        <component :is="icon" class="w-6 h-6" />
      </div>
      <div class="min-w-0">
        <p class="text-2xl font-bold text-slate-800 leading-none">{{ value }}</p>
        <p class="text-sm text-slate-500 mt-1">{{ label }}</p>
        <p v-if="change" :class="['text-xs mt-1 font-medium', change > 0 ? 'text-success-600' : 'text-danger-600']">
          {{ change > 0 ? '▲' : '▼' }} {{ Math.abs(change) }}% vs last month
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label:  { type: String, required: true },
  value:  { type: [String, Number], required: true },
  icon:   { type: Object, required: true },
  color:  { type: String, default: 'bg-brand-50 text-brand-600' },
  change: { type: Number, default: null },
})
</script>
