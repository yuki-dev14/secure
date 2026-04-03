<template>
  <div class="min-h-screen" style="background: linear-gradient(135deg, #003087 0%, #0051a8 40%, #1e40af 100%);">
    <!-- Animated background pattern -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-40 -right-40 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-blue-300/10 rounded-full blur-3xl"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 px-6 py-4 flex items-center justify-between border-b border-white/10">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
          <span class="text-white font-bold text-sm">4P</span>
        </div>
        <div>
          <p class="text-white font-bold text-sm">SECURE 4Ps</p>
          <p class="text-white/60 text-xs">Beneficiary Portal</p>
        </div>
      </div>

      <div class="flex items-center gap-4">
        <!-- Unread notifications badge -->
        <Link :href="route('beneficiary.notifications')" class="relative text-white/70 hover:text-white transition-colors">
          <BellIcon class="w-6 h-6" />
          <span
            v-if="$page.props.auth.user && unreadCount > 0"
            class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-[9px] text-white flex items-center justify-center font-bold"
          >
            {{ unreadCount > 9 ? '9+' : unreadCount }}
          </span>
        </Link>

        <!-- User info -->
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
            <span class="text-white text-xs font-bold">{{ initials }}</span>
          </div>
          <div class="hidden sm:block">
            <p class="text-white text-sm font-medium">{{ $page.props.auth.user?.beneficiary?.full_name }}</p>
            <p class="text-white/60 text-xs">{{ $page.props.auth.user?.beneficiary?.unique_id }}</p>
          </div>
        </div>

        <!-- Logout -->
        <Link
          :href="route('logout')" method="post" as="button"
          class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs rounded-lg transition-colors border border-white/20"
        >
          Logout
        </Link>
      </div>
    </header>

    <!-- Navigation tabs -->
    <nav class="relative z-10 px-6 py-2 flex items-center gap-1 overflow-x-auto no-scrollbar border-b border-white/10">
      <Link
        v-for="item in navItems"
        :key="item.route"
        :href="route(item.route)"
        :class="[
          'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap',
          isActive(item.routePrefix)
            ? 'bg-white text-brand-700 shadow-sm'
            : 'text-white/70 hover:text-white hover:bg-white/10'
        ]"
      >
        <component :is="item.icon" class="w-4 h-4" />
        {{ item.label }}
      </Link>
    </nav>

    <!-- Main content -->
    <main class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 py-6">
      <!-- Flash messages -->
      <FlashMessage class="mb-4" />
      <slot />
    </main>

    <!-- Footer -->
    <footer class="relative z-10 text-center py-4 text-white/40 text-xs pb-8">
      SECURE 4Ps — DSWD Lipa City, Batangas | Data Privacy Act of 2012 Compliant
    </footer>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  HomeIcon, UserIcon, DocumentTextIcon,
  CurrencyDollarIcon, UsersIcon, BellIcon,
} from '@heroicons/vue/24/outline'
import FlashMessage from '@/Components/FlashMessage.vue'

defineProps({ unreadCount: { type: Number, default: 0 } })

const page = usePage()

const navItems = [
  { route: 'beneficiary.dashboard',     label: 'Dashboard',  icon: HomeIcon,            routePrefix: '/portal/dashboard' },
  { route: 'beneficiary.profile',       label: 'Profile',    icon: UserIcon,            routePrefix: '/portal/profile' },
  { route: 'beneficiary.documents',     label: 'Documents',  icon: DocumentTextIcon,    routePrefix: '/portal/documents' },
  { route: 'beneficiary.grants',        label: 'Grants',     icon: CurrencyDollarIcon,  routePrefix: '/portal/grants' },
  { route: 'beneficiary.family',        label: 'Family',     icon: UsersIcon,           routePrefix: '/portal/family' },
  { route: 'beneficiary.notifications', label: 'Alerts',     icon: BellIcon,            routePrefix: '/portal/notifications' },
]

const isActive = (prefix) => page.url.startsWith(prefix)

const initials = computed(() => {
  const name = page.props.auth?.user?.beneficiary?.full_name ?? 'B'
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
})
</script>
