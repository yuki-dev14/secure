<template>
  <StaffLayout>
    <div class="h-[calc(100vh-8rem)] flex flex-col md:flex-row gap-4 overflow-hidden">

      <!-- LEFT: Contact List -->
      <div class="w-full md:w-80 lg:w-96 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col shrink-0">
        <!-- Header & Search -->
        <div class="p-4 border-b border-slate-100 space-y-3">
          <div class="flex items-center justify-between">
            <h2 class="font-bold text-slate-800 text-lg flex items-center gap-2">
              <ChatBubbleLeftRightIcon class="w-5 h-5 text-emerald-600" />
              Staff Messaging
            </h2>
            <span v-if="totalUnreadCount > 0" class="badge badge-danger text-xs font-bold px-2 py-0.5">
              {{ totalUnreadCount }} unread
            </span>
          </div>

          <div class="relative">
            <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search staff contacts..."
              class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition"
            />
          </div>
        </div>

        <!-- Contacts List -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-50 p-2 space-y-1 no-scrollbar">
          <button
            v-for="contact in filteredContacts"
            :key="contact.id"
            @click="selectContact(contact)"
            :class="[
              'w-full text-left p-3 rounded-xl transition flex items-start gap-3 relative group',
              activeContactId === contact.id ? 'bg-emerald-50/80 border border-emerald-200/60 shadow-xs' : 'hover:bg-slate-50'
            ]"
          >
            <!-- Avatar -->
            <div :class="['w-10 h-10 rounded-full flex items-center justify-center font-bold text-white text-xs shrink-0 shadow-xs', getAvatarColor(contact.role)]">
              {{ getInitials(contact.name) }}
            </div>

            <!-- Details -->
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-1">
                <p class="text-xs font-bold text-slate-800 truncate">{{ contact.name }}</p>
                <span v-if="contact.last_message" class="text-[10px] text-slate-400 shrink-0">
                  {{ contact.last_message.created_at }}
                </span>
              </div>

              <div class="flex items-center gap-1.5 mt-0.5">
                <span :class="['text-[10px] font-semibold px-1.5 py-0.2 rounded-md shrink-0', getBadgeClass(contact.role)]">
                  {{ getShortRole(contact.role) }}
                </span>
                <span v-if="contact.assigned_barangay" class="text-[10px] text-slate-500 truncate">
                  • Brgy. {{ contact.assigned_barangay }}
                </span>
              </div>

              <p class="text-xs text-slate-500 truncate mt-1">
                <span v-if="contact.last_message?.sender_id === $page.props.auth?.user?.id" class="text-slate-400">You: </span>
                {{ contact.last_message?.text ?? 'No messages yet' }}
              </p>
            </div>

            <!-- Unread badge -->
            <span
              v-if="contact.unread_count > 0"
              class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] font-bold flex items-center justify-center shrink-0 self-center shadow-xs"
            >
              {{ contact.unread_count }}
            </span>
          </button>

          <div v-if="filteredContacts.length === 0" class="p-6 text-center text-slate-400 text-xs">
            No contacts match your search.
          </div>
        </div>
      </div>

      <!-- RIGHT: Active Chat Window -->
      <div class="flex-1 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden min-w-0">

        <!-- Active Contact Header -->
        <template v-if="selectedContact">
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-3">
              <div :class="['w-10 h-10 rounded-full flex items-center justify-center font-bold text-white text-xs shrink-0 shadow-xs', getAvatarColor(selectedContact.role)]">
                {{ getInitials(selectedContact.name) }}
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                  {{ selectedContact.name }}
                  <span :class="['text-[10px] font-semibold px-2 py-0.5 rounded-full', getBadgeClass(selectedContact.role)]">
                    {{ selectedContact.role_display }}
                  </span>
                </h3>
                <p class="text-xs text-slate-400">
                  <span v-if="selectedContact.assigned_barangay">Assigned to Brgy. {{ selectedContact.assigned_barangay }} • </span>
                  Internal Staff Communication
                </p>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full font-medium border border-emerald-200/50">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Active Chat
              </span>
            </div>
          </div>

          <!-- Messages Stream -->
          <div ref="chatContainer" class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50/30">
            <div v-if="localMessages.length === 0" class="h-full flex flex-col items-center justify-center text-center p-8">
              <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                <ChatBubbleLeftRightIcon class="w-6 h-6" />
              </div>
              <p class="text-sm font-semibold text-slate-700">No messages yet</p>
              <p class="text-xs text-slate-400 max-w-xs mt-1">Start a conversation with {{ selectedContact.name }}. All messages are confidential to SECURE 4Ps staff.</p>
            </div>

            <template v-else>
              <div
                v-for="msg in localMessages"
                :key="msg.id"
                :class="['flex flex-col', msg.is_me ? 'items-end' : 'items-start']"
              >
                <!-- Sender label for incoming -->
                <span v-if="!msg.is_me" class="text-[10px] font-medium text-slate-400 mb-1 ml-1">
                  {{ msg.sender_name }} ({{ msg.sender_role }})
                </span>

                <!-- Message bubble -->
                <div
                  :class="[
                    'max-w-[75%] px-4 py-2.5 rounded-2xl text-xs leading-relaxed shadow-xs relative group transition-all',
                    msg.is_me
                      ? 'bg-emerald-600 text-white rounded-br-xs'
                      : 'bg-white border border-slate-200 text-slate-800 rounded-bl-xs'
                  ]"
                >
                  <p class="whitespace-pre-wrap break-words">{{ msg.message }}</p>

                  <div :class="['flex items-center gap-1 mt-1 text-[9px]', msg.is_me ? 'text-emerald-100 justify-end' : 'text-slate-400 justify-start']">
                    <span>{{ msg.created_at }}</span>
                    <span v-if="msg.is_me" title="Status">
                      <CheckIcon v-if="!msg.read_at" class="w-3 h-3 text-emerald-200" />
                      <span v-else class="text-emerald-100 font-bold">✓✓ Read</span>
                    </span>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Message Input Bar -->
          <div class="p-4 border-t border-slate-100 bg-white">
            <form @submit.prevent="sendMessage" class="flex items-center gap-2">
              <input
                v-model="newMessage"
                type="text"
                placeholder="Type your message..."
                :disabled="sending"
                class="flex-1 px-4 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition disabled:opacity-50"
                @keydown.enter.exact.prevent="sendMessage"
              />

              <button
                type="submit"
                :disabled="!newMessage.trim() || sending"
                class="btn btn-emerald btn-sm px-4 py-2.5 gap-1.5 rounded-xl text-xs font-semibold shadow-sm transition disabled:opacity-40 disabled:cursor-not-allowed"
              >
                <span>Send</span>
                <PaperAirplaneIcon class="w-3.5 h-3.5" />
              </button>
            </form>
          </div>
        </template>

        <!-- No Contact Selected Placeholder -->
        <div v-else class="h-full flex flex-col items-center justify-center p-8 text-center bg-slate-50/20">
          <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
            <ChatBubbleLeftRightIcon class="w-8 h-8" />
          </div>
          <h3 class="text-base font-bold text-slate-800">Select a Staff Member</h3>
          <p class="text-xs text-slate-400 max-w-sm mt-1">Choose a staff contact from the left list to start messaging Superadmin or Administrators.</p>
        </div>

      </div>

    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import {
  ChatBubbleLeftRightIcon,
  MagnifyingGlassIcon,
  PaperAirplaneIcon,
  CheckIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  contacts: { type: Array, default: () => [] },
  activeContact: { type: Object, default: null },
  messages: { type: Array, default: () => [] },
  totalUnreadCount: { type: Number, default: 0 },
})

const searchQuery = ref('')
const activeContactId = ref(props.activeContact?.id ?? null)
const selectedContact = ref(props.activeContact)
const localMessages = ref([...props.messages])
const newMessage = ref('')
const sending = ref(false)
const chatContainer = ref(null)

let pollTimer = null

const filteredContacts = computed(() => {
  if (!searchQuery.value.trim()) return props.contacts
  const q = searchQuery.value.toLowerCase()
  return props.contacts.filter(c =>
    c.name.toLowerCase().includes(q) ||
    c.role_display.toLowerCase().includes(q) ||
    (c.assigned_barangay && c.assigned_barangay.toLowerCase().includes(q))
  )
})

function getInitials(name) {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
}

function getAvatarColor(role) {
  return matchRole(role, {
    superadmin: 'bg-rose-600',
    admin_swa: 'bg-amber-600',
    admin_4ps: 'bg-blue-600',
    barangay_assistant: 'bg-emerald-600',
    default: 'bg-slate-600',
  })
}

function getBadgeClass(role) {
  return matchRole(role, {
    superadmin: 'bg-rose-100 text-rose-800 border border-rose-200/50',
    admin_swa: 'bg-amber-100 text-amber-800 border border-amber-200/50',
    admin_4ps: 'bg-blue-100 text-blue-800 border border-blue-200/50',
    barangay_assistant: 'bg-emerald-100 text-emerald-800 border border-emerald-200/50',
    default: 'bg-slate-100 text-slate-800',
  })
}

function getShortRole(role) {
  return matchRole(role, {
    superadmin: 'Superadmin',
    admin_swa: 'SWA Admin',
    admin_4ps: '4Ps Admin',
    barangay_assistant: 'Brgy Assistant',
    default: role,
  })
}

function matchRole(role, cases) {
  return cases[role] ?? cases.default
}

function scrollToBottom() {
  nextTick(() => {
    if (chatContainer.value) {
      chatContainer.value.scrollTop = chatContainer.value.scrollHeight
    }
  })
}

async function selectContact(contact) {
  activeContactId.value = contact.id
  selectedContact.value = contact
  contact.unread_count = 0

  await fetchLatestMessages()
  scrollToBottom()
}

async function fetchLatestMessages() {
  if (!activeContactId.value) return

  try {
    const res = await fetch(`/staff/chat/messages/${activeContactId.value}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    })
    if (res.ok) {
      const data = await res.json()
      if (data.success) {
        localMessages.value = data.messages
      }
    }
  } catch (err) {
    console.error('Error fetching chat messages:', err)
  }
}

async function sendMessage() {
  if (!newMessage.value.trim() || !activeContactId.value || sending.value) return

  const textToSend = newMessage.value.trim()
  newMessage.value = ''
  sending.value = true

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? ''
    const res = await fetch('/staff/chat/send', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        recipient_id: activeContactId.value,
        message: textToSend,
      }),
    })

    if (res.ok) {
      const data = await res.json()
      if (data.success && data.message) {
        localMessages.value.push(data.message)
        scrollToBottom()
      }
    } else {
      const errData = await res.json().catch(() => ({}))
      console.error('Failed to send staff message:', res.status, errData)
      newMessage.value = textToSend
    }
  } catch (err) {
    console.error('Error sending staff message:', err)
    newMessage.value = textToSend
  } finally {
    sending.value = false
  }
}

onMounted(() => {
  scrollToBottom()
  // Poll for new messages every 4 seconds
  pollTimer = setInterval(fetchLatestMessages, 4000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
})

watch(() => props.messages, (newVal) => {
  localMessages.value = [...newVal]
  scrollToBottom()
})
</script>
