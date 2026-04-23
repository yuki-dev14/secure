<template>
  <div>
    <label class="form-label">{{ label }}</label>
    <p v-if="hint" class="text-xs text-slate-400 mb-1.5">{{ hint }}</p>
    <div class="flex gap-5 mt-1">
      <label class="flex items-center gap-2 cursor-pointer select-none">
        <input
          type="radio"
          :checked="modelValue === true"
          @change="$emit('update:modelValue', true)"
          class="w-4 h-4 accent-green-600"
        />
        <span class="text-sm font-semibold text-success-600">Yes</span>
      </label>
      <label class="flex items-center gap-2 cursor-pointer select-none">
        <input
          type="radio"
          :checked="modelValue === false"
          @change="$emit('update:modelValue', false)"
          class="w-4 h-4 accent-red-600"
        />
        <span class="text-sm font-semibold text-danger-600">No</span>
      </label>
    </div>
  </div>
</template>

<script setup>
defineProps(['modelValue', 'label', 'hint'])
defineEmits(['update:modelValue'])
</script>
