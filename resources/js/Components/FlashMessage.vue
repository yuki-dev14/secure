<template>
  <Teleport to="body">
    <div class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="['alert pointer-events-auto', alertClass(toast.type)]"
        >
          <component :is="iconFor(toast.type)" class="w-5 h-5 flex-shrink-0" />
          <p class="flex-1 text-sm">{{ toast.message }}</p>
          <button @click="dismiss(toast.id)" class="ml-1 opacity-60 hover:opacity-100 transition-opacity">
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {
  CheckCircleIcon, ExclamationCircleIcon,
  InformationCircleIcon, ExclamationTriangleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

let nextId = 0
const toasts = ref([])
const page   = usePage()

const alertClass = (type) => ({
  success: 'alert-success',
  error:   'alert-danger',
  warning: 'alert-warning',
  info:    'alert-info',
}[type] ?? 'alert-info')

const iconFor = (type) => ({
  success: CheckCircleIcon,
  error:   ExclamationCircleIcon,
  warning: ExclamationTriangleIcon,
  info:    InformationCircleIcon,
}[type] ?? InformationCircleIcon)

const addToast = (message, type = 'info') => {
  if (!message) return
  const id = ++nextId
  toasts.value.push({ id, message, type })
  setTimeout(() => dismiss(id), 5000)
}

const dismiss = (id) => {
  toasts.value = toasts.value.filter(t => t.id !== id)
}

watch(() => page.props.flash, (flash) => {
  if (flash?.success) addToast(flash.success, 'success')
  if (flash?.error)   addToast(flash.error,   'error')
  if (flash?.warning) addToast(flash.warning, 'warning')
  if (flash?.info)    addToast(flash.info,    'info')
}, { deep: true, immediate: true })
</script>

<style scoped>
.toast-enter-active { transition: all 0.3s ease; }
.toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from   { opacity: 0; transform: translateX(100%); }
.toast-leave-to     { opacity: 0; transform: translateX(100%) scale(0.95); }
</style>
