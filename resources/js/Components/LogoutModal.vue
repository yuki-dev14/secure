<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-labelledby="logout-modal-title"
      >
        <!-- Backdrop overlay -->
        <div
          class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
          @click="onCancel"
        ></div>

        <!-- Modal dialog box -->
        <div
          class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-100 p-6 sm:p-8 transform transition-all z-10"
        >
          <!-- Top badge / icon -->
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center text-red-600 shrink-0 shadow-sm">
              <ArrowRightOnRectangleIcon class="w-6 h-6" />
            </div>
            <div>
              <h3 id="logout-modal-title" class="text-lg font-bold text-slate-800">
                Confirm Logout
              </h3>
              <p class="text-xs text-slate-400 font-medium">
                SECURE 4Ps Session
              </p>
            </div>
          </div>

          <!-- Message content -->
          <div class="mb-6 bg-slate-50 rounded-2xl p-4 border border-slate-100">
            <p class="text-sm text-slate-600 leading-relaxed font-medium">
              Are you sure you want to log out of your session?
            </p>
            <p class="text-xs text-slate-400 mt-1">
              You will need to sign in again to access your account dashboard.
            </p>
          </div>

          <!-- Actions: No / Yes -->
          <div class="flex items-center justify-end gap-3">
            <button
              type="button"
              @click="onCancel"
              class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 text-sm font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-slate-300"
            >
              No, Stay Logged In
            </button>

            <button
              type="button"
              @click="onConfirm"
              :disabled="loading"
              class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 active:bg-red-800 text-white text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50"
            >
              <span v-if="loading" class="animate-spin text-xs">⏳</span>
              <ArrowRightOnRectangleIcon v-else class="w-4 h-4" />
              <span>Yes, Log Out</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { onMounted, onUnmounted, watch } from 'vue'
import { ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  show: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'confirm'])

const onCancel = () => emit('close')
const onConfirm = () => emit('confirm')

const handleKeydown = (e) => {
  if (props.show && e.key === 'Escape') {
    onCancel()
  }
}

watch(() => props.show, (val) => {
  if (val) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>
