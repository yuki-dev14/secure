<template>
  <div class="min-h-screen relative font-sans text-slate-800 antialiased selection:bg-rose-500 selection:text-white"
       style="background: radial-gradient(circle at 50% 0%, #4a0000 0%, #2b0000 40%, #150000 100%);">
    
    <!-- Dynamic Ambient Lighting Grid -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
      <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[700px] h-[500px] bg-rose-600/15 rounded-full blur-[140px]"></div>
      <div class="absolute top-1/3 -right-40 w-96 h-96 bg-amber-500/10 rounded-full blur-[120px]"></div>
      <div class="absolute bottom-10 -left-40 w-96 h-96 bg-rose-800/20 rounded-full blur-[120px]"></div>
    </div>

    <!-- Header Bar -->
    <header class="relative z-20 sticky top-0 backdrop-blur-xl bg-black/40 border-b border-white/10 shadow-2xl">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
        
        <!-- Brand Logo & Title -->
        <Link :href="route('beneficiary.dashboard')" class="flex items-center gap-3 group">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-900 to-red-600 p-0.5 shadow-lg shadow-rose-950/50 group-hover:scale-105 transition-transform duration-200">
            <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center p-1.5">
              <img src="/logo.png" alt="SECURE 4Ps" class="w-full h-full object-contain" />
            </div>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-white font-black tracking-wide text-base">SECURE 4Ps</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30 uppercase tracking-widest">
                Beneficiary Portal
              </span>
            </div>
            <p class="text-white/60 text-xs font-medium">DSWD Lipa City, Batangas</p>
          </div>
        </Link>

        <!-- Right User Actions -->
        <div class="flex items-center gap-3">
          
          <!-- Notifications Bell -->
          <Link :href="route('beneficiary.notifications')"
                class="relative p-2.5 rounded-xl bg-white/5 hover:bg-white/15 text-white/80 hover:text-white transition-all border border-white/10 group">
            <BellIcon class="w-5 h-5 group-hover:scale-110 transition-transform" />
            <span v-if="$page.props.auth.user && unreadCount > 0"
                  class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1 bg-gradient-to-r from-rose-500 to-red-600 rounded-full text-[10px] text-white flex items-center justify-center font-black shadow-md border-2 border-slate-950 animate-pulse">
              {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
          </Link>

          <!-- Profile Badge -->
          <div class="flex items-center gap-2.5 pl-2 border-l border-white/10">
            <div class="w-9 h-9 rounded-xl ring-2 ring-rose-500/40 overflow-hidden bg-rose-950 flex items-center justify-center shrink-0 shadow-inner">
              <img v-if="$page.props.auth.user?.beneficiary?.photo_path && !navImageError"
                   :src="`/storage/${$page.props.auth.user.beneficiary.photo_path}`" alt=""
                   @error="navImageError = true"
                   class="w-full h-full object-cover" />
              <UserIcon v-else class="w-5 h-5 text-rose-300/80" />
            </div>
            <div class="hidden md:block text-left">
              <p class="text-white text-xs font-bold leading-snug tracking-tight">
                {{ $page.props.auth.user?.beneficiary?.full_name }}
              </p>
              <p class="text-rose-300/70 text-[11px] font-mono tracking-wider">
                {{ $page.props.auth.user?.beneficiary?.unique_id }}
              </p>
            </div>
          </div>

          <!-- Logout Button -->
          <button type="button"
                  @click="showLogoutModal = true"
                  class="px-3.5 py-2 bg-rose-950/60 hover:bg-rose-600 text-rose-100 hover:text-white text-xs font-semibold rounded-xl transition-all border border-rose-700/50 shadow-lg flex items-center gap-1.5">
            Logout
          </button>

        </div>
      </div>
    </header>

    <!-- Navigation Bar -->
    <nav class="relative z-10 sticky top-[65px] backdrop-blur-lg bg-black/20 border-b border-white/10 py-2">
      <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5">
          <Link v-for="item in navItems"
                :key="item.route"
                :href="route(item.route)"
                :class="[
                  'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap border',
                  isActive(item.routePrefix)
                    ? 'bg-gradient-to-r from-rose-600 to-red-700 text-white border-rose-500/50 shadow-lg shadow-rose-950/60 scale-[1.02]'
                    : 'text-white/70 hover:text-white hover:bg-white/10 border-transparent'
                ]">
            <component :is="item.icon" class="w-4 h-4 shrink-0" />
            {{ item.label }}
          </Link>
        </div>
      </div>
    </nav>

    <!-- Main Content Container -->
    <main class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 py-8">
      <FlashMessage class="mb-6" />
      <slot />
    </main>

    <!-- Footer -->
    <footer class="relative z-10 text-center py-6 text-white/40 text-xs border-t border-white/5 mt-12">
      SECURE 4Ps — DSWD Lipa City, Batangas | Data Privacy Act of 2012 Compliant
    </footer>

    <!-- Logout Modal -->
    <LogoutModal
      :show="showLogoutModal"
      :loading="loggingOut"
      @close="showLogoutModal = false"
      @confirm="handleLogout"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import {
  HomeIcon, UserIcon, DocumentTextIcon,
  CurrencyDollarIcon, UsersIcon, BellIcon, ShieldCheckIcon,
} from '@heroicons/vue/24/outline'
import FlashMessage from '@/Components/FlashMessage.vue'
import LogoutModal from '@/Components/LogoutModal.vue'

defineProps({ unreadCount: { type: Number, default: 0 } })

const page            = usePage()
const showLogoutModal = ref(false)
const navImageError   = ref(false)
const loggingOut      = ref(false)

const handleLogout = () => {
  loggingOut.value = true
  router.post(route('logout'), {}, {
    onFinish: () => {
      loggingOut.value = false
      showLogoutModal.value = false
    }
  })
}

const navItems = [
  { route: 'beneficiary.dashboard',     label: 'Dashboard',   icon: HomeIcon,            routePrefix: '/portal/dashboard' },
  { route: 'beneficiary.profile',       label: 'Profile',     icon: UserIcon,            routePrefix: '/portal/profile' },
  { route: 'beneficiary.grants',        label: 'Grants',      icon: CurrencyDollarIcon,  routePrefix: '/portal/grants' },
  { route: 'beneficiary.compliance',    label: 'Compliance',  icon: ShieldCheckIcon,     routePrefix: '/portal/compliance' },
  { route: 'beneficiary.family',        label: 'Family',      icon: UsersIcon,           routePrefix: '/portal/family' },
  { route: 'beneficiary.notifications', label: 'Alerts',      icon: BellIcon,            routePrefix: '/portal/notifications' },
]

const isActive = (prefix) => page.url.startsWith(prefix)

const initials = computed(() => {
  const name = page.props.auth?.user?.beneficiary?.full_name ?? 'B'
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
})
</script>
