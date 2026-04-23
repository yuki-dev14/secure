<template>
  <div class="min-h-screen bg-slate-50 flex">
    <!-- Sidebar -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-40 flex flex-col bg-white border-r border-slate-200 transition-all duration-300',
        sidebarOpen ? 'w-64' : 'w-16'
      ]"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-4 py-5 border-b border-slate-100">
        <div class="w-8 h-8 rounded-xl gradient-dswd flex items-center justify-center flex-shrink-0">
          <span class="text-white font-bold text-xs">4P</span>
        </div>
        <Transition name="fade">
          <div v-if="sidebarOpen" class="min-w-0">
            <p class="text-xs font-bold text-slate-800 truncate">SECURE 4Ps</p>
            <p class="text-[10px] text-slate-400 truncate">Lipa City, Batangas</p>
          </div>
        </Transition>
      </div>

      <!-- User info -->
      <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50/50">
        <div :class="['w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold', roleColor]">
          {{ initials }}
        </div>
        <Transition name="fade">
          <div v-if="sidebarOpen" class="min-w-0 flex-1">
            <p class="text-sm font-medium text-slate-800 truncate">{{ $page.props.auth.user?.name }}</p>
            <p class="text-[11px] text-slate-400">{{ $page.props.auth.user?.role_display }}</p>
          </div>
        </Transition>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto no-scrollbar">
        <template v-for="item in navItems" :key="item.route">
          <Link
            :href="route(item.route)"
            :class="[
              'nav-item',
              currentRoute.startsWith(item.routePrefix ?? item.route)
                ? 'nav-item-active' : 'nav-item-inactive'
            ]"
          >
            <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
            <Transition name="fade">
              <span v-if="sidebarOpen" class="truncate">{{ item.label }}</span>
            </Transition>
          </Link>
        </template>
      </nav>

      <!-- Collapse toggle -->
      <div class="p-2 border-t border-slate-100">
        <button
          @click="sidebarOpen = !sidebarOpen"
          class="nav-item nav-item-inactive w-full"
          :title="sidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'"
        >
          <ChevronLeftIcon v-if="sidebarOpen" class="w-5 h-5" />
          <ChevronRightIcon v-else class="w-5 h-5" />
          <Transition name="fade">
            <span v-if="sidebarOpen">Collapse</span>
          </Transition>
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div :class="['flex-1 flex flex-col min-h-screen transition-all duration-300', sidebarOpen ? 'ml-64' : 'ml-16']">
      <!-- Top bar -->
      <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-xl border-b border-slate-200 px-6 py-3 flex items-center justify-between">
        <div>
          <h1 class="text-sm font-semibold text-slate-800">{{ pageTitle }}</h1>
          <p class="text-xs text-slate-400">{{ pageSubtitle }}</p>
        </div>

        <div class="flex items-center gap-3">
          <!-- Flash messages -->
          <FlashMessage />

          <!-- Logout -->
          <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="btn btn-ghost btn-sm gap-1.5 text-slate-500"
          >
            <ArrowRightOnRectangleIcon class="w-4 h-4" />
            <span>Logout</span>
          </Link>
        </div>
      </header>

      <!-- Page -->
      <main class="flex-1 p-6">
        <slot />
      </main>

      <!-- Footer -->
      <footer class="px-6 py-3 border-t border-slate-100 text-xs text-slate-400 flex items-center justify-between">
        <span>SECURE 4Ps v1.0 — DSWD Lipa City, Batangas</span>
        <span>{{ new Date().getFullYear() }} Data Privacy Act Compliant</span>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  HomeIcon, UsersIcon, ClipboardDocumentCheckIcon,
  QrCodeIcon, DocumentChartBarIcon, ShieldCheckIcon,
  ChevronLeftIcon, ChevronRightIcon, ArrowRightOnRectangleIcon,
  BellIcon, CogIcon, CalendarDaysIcon,
} from '@heroicons/vue/24/outline'
import FlashMessage from '@/Components/FlashMessage.vue'

const props = defineProps({
  pageTitle:    { type: String, default: 'Dashboard' },
  pageSubtitle: { type: String, default: '' },
})

const page        = usePage()
const sidebarOpen = ref(true)
const currentRoute = computed(() => page.url)
const role = computed(() => page.props.auth?.user?.role)

// Map role to the route prefix used in web.php
const roleRoutePrefix = computed(() => ({
  superadmin: 'superadmin',
  admin: 'admin',
  compliance_verifier: 'verifier',
  field_officer: 'officer',
}[role.value] ?? role.value))

const navItems = computed(() => {
  const base = [
    { route: `${roleRoutePrefix.value}.dashboard`, label: 'Dashboard', icon: HomeIcon, routePrefix: `/${roleRoutePrefix.value}/dashboard` },
  ]

  if (role.value === 'superadmin') {
    base.push(
      { route: 'superadmin.beneficiaries.index', label: 'Beneficiaries',  icon: UsersIcon,               routePrefix: '/superadmin/beneficiaries' },
      { route: 'superadmin.audit-logs.index',    label: 'Audit Trail',    icon: ShieldCheckIcon,          routePrefix: '/superadmin/audit' },
      { route: 'superadmin.reports.index',       label: 'Reports',        icon: DocumentChartBarIcon,     routePrefix: '/superadmin/reports' },
      { route: 'admin.users.index',              label: 'Users',          icon: CogIcon,                  routePrefix: '/admin/users' },
      { route: 'superadmin.settings.index',      label: 'Settings',       icon: CogIcon,                  routePrefix: '/superadmin/settings' },
    )
  }

  if (role.value === 'admin' || role.value === 'superadmin') {
    base.push(
      { route: 'admin.users.index',   label: 'Staff Management', icon: UsersIcon, routePrefix: '/admin/users' },
      { route: 'admin.events.index',  label: 'Distribution Events', icon: CalendarDaysIcon, routePrefix: '/admin/distribution' },
      { route: 'admin.beneficiaries.index', label: 'Beneficiaries', icon: UsersIcon, routePrefix: '/admin/beneficiaries' },
    )
  }

  if (role.value === 'compliance_verifier') {
    base.push(
      { route: 'verifier.beneficiaries', label: 'Record Completion', icon: ClipboardDocumentCheckIcon, routePrefix: '/verifier/beneficiaries' },
    )
  }

  if (role.value === 'field_officer') {
    base.push(
      { route: 'officer.scanner',      label: 'QR Scanner',   icon: QrCodeIcon, routePrefix: '/officer/scanner' },
      { route: 'officer.distribution', label: 'Distribution', icon: DocumentChartBarIcon, routePrefix: '/officer/distribution' },
    )
  }

  return base
})

const initials = computed(() => {
  const name = page.props.auth?.user?.name ?? 'U'
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
})

const roleColor = computed(() => ({
  superadmin:          'bg-red-500',
  admin:               'bg-brand-600',
  compliance_verifier: 'bg-amber-500',
  field_officer:       'bg-green-600',
}[role.value] ?? 'bg-slate-500'))
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
