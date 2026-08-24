<template>
  <Head>
    <title>SECURE 4Ps — Pantawid Pamilyang Pilipino Program | Lipa City, Batangas</title>
    <meta name="description" content="System for Eligibility Checking, Unified Records, and Evaluation for the 4Ps program in Lipa City, Batangas. Verify your compliance, check your grants, and manage your household records." />
  </Head>

  <div class="min-h-screen bg-slate-900 flex flex-col font-sans">

    <!-- ─── Header ─────────────────────────────────────────────────────────── -->
    <header class="sticky top-0 z-50 bg-slate-900/90 backdrop-blur-xl border-b border-red-950/80 shadow-md">
      <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <img src="/logo.png" alt="SECURE 4Ps Logo" class="w-10 h-10 object-contain shrink-0" />
          <div>
            <p class="text-sm font-bold text-white leading-tight tracking-tight">SECURE 4Ps</p>
            <p class="text-[10px] text-red-200/70 font-medium leading-tight">Lipa City, Batangas</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <template v-if="authUser">
            <Link :href="dashboardRoute" class="btn btn-primary btn-sm gap-1.5 shadow-sm">
              <ShieldCheckIcon class="w-4 h-4" />
              Go to Dashboard
            </Link>
          </template>
          <template v-else>
            <Link :href="route('beneficiary.login')" class="btn btn-secondary btn-sm gap-1.5">
              <QrCodeIcon class="w-4 h-4 text-brand-600" />
              Beneficiary Portal
            </Link>
            <Link :href="route('staff.login')" class="btn btn-primary btn-sm shadow-sm">
              Staff Login
            </Link>
          </template>
        </div>
      </div>
    </header>

    <!-- ─── Hero Section ───────────────────────────────────────────────────── -->
    <section class="relative overflow-hidden flex-1 flex items-center py-20" style="min-height: 88vh; background: linear-gradient(135deg, #330000 0%, #4d0000 40%, #800000 70%, #990000 100%);">
      <!-- Decorative Crimson & Gold glow blobs -->
      <div class="absolute top-1/4 right-0 w-96 h-96 rounded-full opacity-20 pointer-events-none" style="background: radial-gradient(circle, #f87171, transparent); transform: translate(30%, -20%)"></div>
      <div class="absolute bottom-0 left-0 w-80 h-80 rounded-full opacity-15 pointer-events-none" style="background: radial-gradient(circle, #ef4444, transparent); transform: translate(-30%, 30%)"></div>

      <!-- Subtle Grid Overlay -->
      <div class="absolute inset-0 opacity-5 pointer-events-none"
        style="background-image: repeating-linear-gradient(0deg, transparent, transparent 40px, #fff 40px, #fff 41px), repeating-linear-gradient(90deg, transparent, transparent 40px, #fff 40px, #fff 41px)">
      </div>

      <div class="relative z-10 max-w-6xl mx-auto px-6 py-12 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
        <!-- Left: Copy -->
        <div>
          <!-- DSWD badge -->
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border border-red-500/30 bg-black/20 backdrop-blur-md mb-6">
            <span class="w-2 h-2 rounded-full bg-red-400 animate-pulse"></span>
            <span class="text-xs text-red-100 font-semibold tracking-wide">DSWD — Lipa City, Batangas</span>
          </div>

          <h1 class="text-4xl sm:text-5xl font-extrabold text-white leading-tight mb-5 tracking-tight">
            Pantawid Pamilyang<br />
            <span style="background: linear-gradient(90deg, #fca5a5, #fecdd3); -webkit-background-clip: text; -webkit-text-fill-color: transparent">
              Pilipino Program
            </span>
          </h1>

          <p class="text-base sm:text-lg text-red-100/80 leading-relaxed mb-8 max-w-lg">
            SECURE — <em>System for Eligibility Checking, Unified Records, and Evaluation</em>.
            A digital verification system for 4Ps beneficiaries in Lipa City, Batangas ensuring transparent, fraud-free cash grant distribution.
          </p>

          <div class="flex flex-wrap gap-3">
            <template v-if="authUser">
              <Link :href="dashboardRoute"
                class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-semibold text-slate-900 shadow-lg hover:opacity-95 transition-opacity"
                style="background: linear-gradient(135deg, #fca5a5, #fecdd3)"
              >
                <ShieldCheckIcon class="w-5 h-5 text-slate-900" />
                Go to Your Dashboard
              </Link>
            </template>
            <template v-else>
              <Link :href="route('beneficiary.login')"
                class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-semibold text-slate-900 shadow-lg hover:opacity-95 transition-opacity"
                style="background: linear-gradient(135deg, #fca5a5, #fecdd3)"
              >
                <QrCodeIcon class="w-5 h-5 text-slate-900" />
                Access Beneficiary Portal
              </Link>
            </template>
            <a href="#how-it-works"
              class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-semibold text-white border border-white/30 hover:bg-white/10 transition-colors"
            >
              Learn More
              <ArrowDownIcon class="w-4 h-4 text-red-200" />
            </a>
          </div>
        </div>

        <!-- Right: Feature cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div v-for="feat in features" :key="feat.label"
            class="p-5 rounded-2xl border border-white/15 shadow-lg"
            style="background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(14px)"
          >
            <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3.5 bg-red-950/80 border border-red-700/50 text-white">
              <component :is="feat.icon" class="w-5 h-5" />
            </div>
            <p class="text-sm font-bold text-white">{{ feat.label }}</p>
            <p class="text-xs text-red-100/70 mt-1 leading-relaxed">{{ feat.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ─── How It Works ──────────────────────────────────────────────────── -->
    <section id="how-it-works" class="py-20 bg-white border-t border-b border-slate-200/60">
      <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-14">
          <p class="text-xs font-bold text-brand-600 uppercase tracking-widest mb-2">How It Works</p>
          <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Simple. Secure. Unified.</h2>
          <p class="text-slate-500 mt-2.5 max-w-xl mx-auto text-sm sm:text-base">
            From compliance verification to cash grant release — every step is digitally tracked and verified.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 relative">
          <!-- Connector line (desktop) -->
          <div class="hidden md:block absolute top-10 left-[12.5%] right-[12.5%] h-0.5 bg-brand-100"></div>

          <div v-for="(step, i) in steps" :key="i" class="flex flex-col items-center text-center group">
            <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-4 relative z-10 bg-white border-2 border-brand-100 text-brand-600 shadow-sm group-hover:border-brand-600 group-hover:bg-brand-600 group-hover:text-white transition-all">
              <component :is="step.icon" class="w-9 h-9" />
            </div>
            <span class="text-xs font-bold text-brand-600 uppercase tracking-widest mb-1">Step {{ i + 1 }}</span>
            <p class="font-bold text-slate-900 text-sm">{{ step.title }}</p>
            <p class="text-xs text-slate-500 mt-1.5 leading-relaxed max-w-[170px]">{{ step.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ─── Access Cards ──────────────────────────────────────────────────── -->
    <section class="py-20 bg-slate-50">
      <div class="max-w-4xl mx-auto px-6">
        <div class="text-center mb-12">
          <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">System Access</h2>
          <p class="text-slate-500 mt-2 text-sm">Choose your portal to sign in to SECURE 4Ps</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

          <!-- Beneficiary Portal -->
          <div class="card p-8 flex flex-col items-center text-center group hover:shadow-lg transition-all border border-slate-200">
            <div class="w-14 h-14 rounded-2xl mb-5 flex items-center justify-center bg-brand-50 text-brand-600 group-hover:bg-brand-600 group-hover:text-white transition-colors">
              <QrCodeIcon class="w-7 h-7" />
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-2">Beneficiary Portal</h3>
            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed mb-6">
              Access your 4Ps account, check compliance status, view cash grant computation, and manage your household records.
            </p>
            <template v-if="authUser && authUser.role === 'beneficiary'">
              <Link :href="dashboardRoute" class="btn btn-primary w-full gap-2 py-3 shadow-xs">
                <QrCodeIcon class="w-4 h-4" />
                Go to My Dashboard
              </Link>
            </template>
            <template v-else-if="!authUser">
              <Link :href="route('beneficiary.login')" class="btn btn-primary w-full gap-2 py-3 shadow-xs">
                <QrCodeIcon class="w-4 h-4" />
                Enter with your 4Ps ID
              </Link>
            </template>
          </div>

          <!-- Staff Login -->
          <div class="card p-8 flex flex-col items-center text-center group hover:shadow-lg transition-all border border-slate-200">
            <div class="w-14 h-14 rounded-2xl mb-5 flex items-center justify-center bg-slate-100 text-slate-700 group-hover:bg-slate-900 group-hover:text-white transition-colors">
              <ShieldCheckIcon class="w-7 h-7" />
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-2">DSWD Staff Login</h3>
            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed mb-6">
              For DSWD personnel — administrators, SWA officers, 4Ps officers, and barangay assistants assigned to Lipa City.
            </p>
            <template v-if="authUser && authUser.role !== 'beneficiary'">
              <Link :href="dashboardRoute" class="btn btn-secondary w-full gap-2 py-3 shadow-xs">
                <ShieldCheckIcon class="w-4 h-4" />
                Go to My Dashboard
              </Link>
            </template>
            <template v-else-if="!authUser">
              <Link :href="route('staff.login')" class="btn btn-secondary w-full gap-2 py-3 shadow-xs">
                <ShieldCheckIcon class="w-4 h-4" />
                Staff Sign In
              </Link>
            </template>
          </div>
        </div>
      </div>
    </section>

    <!-- ─── Footer ─────────────────────────────────────────────────────────── -->
    <footer class="bg-slate-950 text-slate-400 py-10 border-t border-red-950/80">
      <div class="max-w-6xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <img src="/logo.png" alt="SECURE 4Ps Logo" class="w-9 h-9 object-contain shrink-0" />
          <div>
            <p class="text-sm font-bold text-white">SECURE 4Ps Verification System</p>
            <p class="text-[11px] text-slate-400">DSWD Field Office IV-A — Lipa City, Batangas</p>
          </div>
        </div>
        <div class="text-center sm:text-right">
          <p class="text-xs">Republic Act No. 11310 — Pantawid Pamilyang Pilipino Program</p>
          <p class="text-xs mt-0.5 text-slate-500">Data Privacy Act of 2012 Compliant · {{ new Date().getFullYear() }}</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
  QrCodeIcon, ShieldCheckIcon, ArrowDownIcon,
  DocumentCheckIcon, UserGroupIcon, BanknotesIcon,
  ClipboardDocumentCheckIcon, IdentificationIcon,
} from '@heroicons/vue/24/outline'

const page     = usePage()
const authUser = computed(() => page.props.auth?.user ?? null)

const dashboardRoute = computed(() => {
  const role = authUser.value?.role
  if (!role) return route('home')
  return {
    superadmin:          route('superadmin.dashboard'),
    admin:               route('admin.dashboard'),
    admin_4ps:           route('admin4ps.dashboard'),
    admin_swa:           route('adminswa.dashboard'),
    barangay_assistant:  route('barangay.scanner'),
    beneficiary:         route('beneficiary.dashboard'),
  }[role] ?? route('home')
})

const features = [
  {
    icon: IdentificationIcon,
    label: 'QR-Based ID Cards',
    desc: 'Household representatives carry secure QR-coded ID cards for instant verification.',
  },
  {
    icon: ClipboardDocumentCheckIcon,
    label: 'Compliance Tracking',
    desc: 'School attendance and health check-ups monitored per bimonthly period.',
  },
  {
    icon: ShieldCheckIcon,
    label: 'Automated Verification',
    desc: 'Automated eligibility checking, unified records, and audit trail logging.',
  },
  {
    icon: BanknotesIcon,
    label: 'Cash Grant Records',
    desc: 'Full transparency on grant computations, releases, and claiming history.',
  },
]

const steps = [
  {
    icon: ClipboardDocumentCheckIcon,
    title: 'Compliance Verified',
    desc: 'Admin SWA reviews health and education compliance records from school reps and midwives.',
  },
  {
    icon: DocumentCheckIcon,
    title: 'Grants Computed',
    desc: 'Admins batch-compute individual grant amounts based on household data.',
  },
  {
    icon: QrCodeIcon,
    title: 'QR Card Scanned',
    desc: 'Barangay assistants scan the beneficiary\'s QR ID card at FDS sessions.',
  },
  {
    icon: BanknotesIcon,
    title: 'Grant Released',
    desc: 'Compliant beneficiaries receive their cash grant. The transaction is logged.',
  },
]
</script>
