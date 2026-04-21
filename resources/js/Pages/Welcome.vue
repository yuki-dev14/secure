<template>
  <Head>
    <title>SECURE 4Ps — Pantawid Pamilyang Pilipino Program | Lipa City, Batangas</title>
    <meta name="description" content="System for Eligibility Checking, Unified Records, and Evaluation for the 4Ps program in Lipa City, Batangas. Verify your compliance, check your grants, and manage your household records." />
  </Head>

  <div class="min-h-screen bg-slate-50 flex flex-col">

    <!-- ─── Header ─────────────────────────────────────────────────────────── -->
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-xl border-b border-slate-200">
      <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(135deg, #6366f1, #8b5cf6)">
            <span class="text-white font-bold text-sm">4P</span>
          </div>
          <div>
            <p class="text-sm font-bold text-slate-800 leading-tight">SECURE 4Ps</p>
            <p class="text-[10px] text-slate-400 leading-tight">Lipa City, Batangas</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <template v-if="authUser">
            <Link :href="dashboardRoute" class="btn btn-primary btn-sm gap-1.5">
              <ShieldCheckIcon class="w-4 h-4" />
              Go to Dashboard
            </Link>
          </template>
          <template v-else>
            <Link :href="route('beneficiary.login')" class="btn btn-secondary btn-sm gap-1.5">
              <QrCodeIcon class="w-4 h-4" />
              Beneficiary Portal
            </Link>
            <Link :href="route('staff.login')" class="btn btn-primary btn-sm">
              Staff Login
            </Link>
          </template>
        </div>
      </div>
    </header>

    <!-- ─── Hero Section ───────────────────────────────────────────────────── -->
    <section class="relative overflow-hidden flex-1 flex items-center" style="min-height: 88vh">
      <!-- Background gradient -->
      <div class="absolute inset-0" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 70%, #6366f1 100%)"></div>

      <!-- Decorative blobs -->
      <div class="absolute top-1/4 right-0 w-96 h-96 rounded-full opacity-10 pointer-events-none" style="background: radial-gradient(circle, #a78bfa, transparent); transform: translate(30%, -20%)"></div>
      <div class="absolute bottom-0 left-0 w-72 h-72 rounded-full opacity-10 pointer-events-none" style="background: radial-gradient(circle, #818cf8, transparent); transform: translate(-30%, 30%)"></div>

      <!-- Grid pattern overlay -->
      <div class="absolute inset-0 opacity-5 pointer-events-none"
        style="background-image: repeating-linear-gradient(0deg, transparent, transparent 40px, #fff 40px, #fff 41px), repeating-linear-gradient(90deg, transparent, transparent 40px, #fff 40px, #fff 41px)">
      </div>

      <div class="relative z-10 max-w-6xl mx-auto px-6 py-24 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
        <!-- Left: Copy -->
        <div>
          <!-- DSWD badge -->
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-white/20 bg-white/10 backdrop-blur-sm mb-6">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-xs text-white/80 font-medium">DSWD — Lipa City, Batangas</span>
          </div>

          <h1 class="text-4xl sm:text-5xl font-extrabold text-white leading-tight mb-4">
            Pantawid Pamilyang<br />
            <span style="background: linear-gradient(90deg, #a5f3fc, #a78bfa); -webkit-background-clip: text; -webkit-text-fill-color: transparent">
              Pilipino Program
            </span>
          </h1>

          <p class="text-lg text-white/70 leading-relaxed mb-8 max-w-lg">
            SECURE — <em>System for Eligibility Checking, Unified Records, and Evaluation</em>.
            A digital verification system for 4Ps beneficiaries in Lipa City, Batangas ensuring transparent, fraud-free cash grant distribution.
          </p>

          <div class="flex flex-wrap gap-3">
            <template v-if="authUser">
              <Link :href="dashboardRoute"
                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-slate-900 hover:opacity-90 transition-opacity"
                style="background: linear-gradient(135deg, #a5f3fc, #6ee7f7)"
              >
                <ShieldCheckIcon class="w-5 h-5" />
                Go to Your Dashboard
              </Link>
            </template>
            <template v-else>
              <Link :href="route('beneficiary.login')"
                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-slate-900 hover:opacity-90 transition-opacity"
                style="background: linear-gradient(135deg, #a5f3fc, #6ee7f7)"
              >
                <QrCodeIcon class="w-5 h-5" />
                Access Beneficiary Portal
              </Link>
            </template>
            <a href="#how-it-works"
              class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-white border border-white/30 hover:bg-white/10 transition-colors"
            >
              Learn More
              <ArrowDownIcon class="w-4 h-4" />
            </a>
          </div>
        </div>

        <!-- Right: Feature cards -->
        <div class="grid grid-cols-2 gap-3">
          <div v-for="feat in features" :key="feat.label"
            class="p-4 rounded-2xl border border-white/15"
            style="background: rgba(255,255,255,0.07); backdrop-filter: blur(12px)"
          >
            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center mb-3', feat.bg]">
              <component :is="feat.icon" class="w-5 h-5 text-white" />
            </div>
            <p class="text-sm font-semibold text-white">{{ feat.label }}</p>
            <p class="text-xs text-white/60 mt-1 leading-relaxed">{{ feat.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ─── How It Works ──────────────────────────────────────────────────── -->
    <section id="how-it-works" class="py-20 bg-white">
      <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-14">
          <p class="text-xs font-bold text-brand-600 uppercase tracking-widest mb-3">How It Works</p>
          <h2 class="text-3xl font-extrabold text-slate-900">Simple. Secure. Transparent.</h2>
          <p class="text-slate-500 mt-3 max-w-xl mx-auto">
            From compliance verification to cash grant release — every step is digitally tracked and fraud-protected.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 relative">
          <!-- Connector line (desktop) -->
          <div class="hidden md:block absolute top-10 left-[12.5%] right-[12.5%] h-0.5 bg-gradient-to-r from-brand-200 via-brand-400 to-emerald-400"></div>

          <div v-for="(step, i) in steps" :key="i" class="flex flex-col items-center text-center">
            <div :class="['w-20 h-20 rounded-2xl flex items-center justify-center mb-4 relative z-10 shadow-lg', step.bg]">
              <component :is="step.icon" class="w-9 h-9 text-white" />
            </div>
            <span class="text-xs font-bold text-brand-600 uppercase tracking-widest mb-1">Step {{ i + 1 }}</span>
            <p class="font-bold text-slate-800 text-sm">{{ step.title }}</p>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed max-w-[160px]">{{ step.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ─── Access Cards ──────────────────────────────────────────────────── -->
    <section class="py-20 bg-slate-50">
      <div class="max-w-4xl mx-auto px-6">
        <div class="text-center mb-12">
          <h2 class="text-2xl font-extrabold text-slate-900">System Access</h2>
          <p class="text-slate-500 mt-2">Choose your role to access the system</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

          <!-- Beneficiary Portal -->
          <div class="card p-8 flex flex-col items-center text-center group hover:shadow-xl transition-shadow">
            <div class="w-16 h-16 rounded-2xl mb-5 flex items-center justify-center"
              style="background: linear-gradient(135deg, #818cf8, #6366f1)">
              <QrCodeIcon class="w-8 h-8 text-white" />
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">Beneficiary Portal</h3>
            <p class="text-sm text-slate-500 leading-relaxed mb-6">
              Access your 4Ps account, check compliance status, view your cash grants, and manage your household members.
            </p>
            <template v-if="authUser && authUser.role === 'beneficiary'">
              <Link :href="dashboardRoute" class="btn btn-primary w-full gap-2">
                <QrCodeIcon class="w-4 h-4" />
                Go to My Dashboard
              </Link>
            </template>
            <template v-else-if="!authUser">
              <Link :href="route('beneficiary.login')" class="btn btn-primary w-full gap-2">
                <QrCodeIcon class="w-4 h-4" />
                Enter with your 4Ps ID
              </Link>
            </template>
          </div>

          <!-- Staff Login -->
          <div class="card p-8 flex flex-col items-center text-center group hover:shadow-xl transition-shadow">
            <div class="w-16 h-16 rounded-2xl mb-5 flex items-center justify-center"
              style="background: linear-gradient(135deg, #34d399, #10b981)">
              <ShieldCheckIcon class="w-8 h-8 text-white" />
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">DSWD Staff Login</h3>
            <p class="text-sm text-slate-500 leading-relaxed mb-6">
              For DSWD personnel — administrators, compliance verifiers, and field officers assigned to Lipa City.
            </p>
            <template v-if="authUser && authUser.role !== 'beneficiary'">
              <Link :href="dashboardRoute" class="btn btn-secondary w-full gap-2">
                <ShieldCheckIcon class="w-4 h-4" />
                Go to My Dashboard
              </Link>
            </template>
            <template v-else-if="!authUser">
              <Link :href="route('staff.login')" class="btn btn-secondary w-full gap-2">
                <ShieldCheckIcon class="w-4 h-4" />
                Staff Sign In
              </Link>
            </template>
          </div>
        </div>
      </div>
    </section>

    <!-- ─── Footer ─────────────────────────────────────────────────────────── -->
    <footer class="bg-slate-900 text-slate-400 py-10">
      <div class="max-w-6xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #6366f1, #8b5cf6)">
            <span class="text-white font-bold text-xs">4P</span>
          </div>
          <div>
            <p class="text-sm font-bold text-white">SECURE 4Ps Verification System</p>
            <p class="text-[11px]">DSWD Field Office IV-A — Lipa City, Batangas</p>
          </div>
        </div>
        <div class="text-center sm:text-right">
          <p class="text-xs">Republic Act No. 10973 — Pantawid Pamilyang Pilipino Program</p>
          <p class="text-xs mt-0.5">Data Privacy Act of 2012 Compliant · {{ new Date().getFullYear() }}</p>
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

const page    = usePage()
const authUser = computed(() => page.props.auth?.user ?? null)

const dashboardRoute = computed(() => {
  const role = authUser.value?.role
  if (!role) return route('home')
  return {
    superadmin:          route('superadmin.dashboard'),
    admin:               route('admin.dashboard'),
    compliance_verifier: route('verifier.dashboard'),
    field_officer:       route('officer.dashboard'),
    beneficiary:         route('beneficiary.dashboard'),
  }[role] ?? route('home')
})

const features = [
  {
    icon: IdentificationIcon,
    label: 'QR-Based ID Cards',
    desc: 'Household representatives carry secure QR-coded ID cards for quick verification.',
    bg: 'bg-brand-500',
  },
  {
    icon: ClipboardDocumentCheckIcon,
    label: 'Compliance Tracking',
    desc: 'School attendance and health check-ups monitored per disbursement period.',
    bg: 'bg-purple-500',
  },
  {
    icon: ShieldCheckIcon,
    label: 'Fraud Prevention',
    desc: 'Double-claim detection, audit trails, and real-time QR validation.',
    bg: 'bg-emerald-500',
  },
  {
    icon: BanknotesIcon,
    label: 'Cash Grant Records',
    desc: 'Full transparency on grant computations, releases, and claiming history.',
    bg: 'bg-amber-500',
  },
]

const steps = [
  {
    icon: ClipboardDocumentCheckIcon,
    title: 'Compliance Verified',
    desc: 'DSWD verifiers record school attendance and health check compliance.',
    bg: 'bg-brand-500',
  },
  {
    icon: DocumentCheckIcon,
    title: 'Grants Computed',
    desc: 'Admins batch-compute individual grant amounts based on household data.',
    bg: 'bg-purple-500',
  },
  {
    icon: QrCodeIcon,
    title: 'QR Card Scanned',
    desc: 'Field officers scan the beneficiary\'s QR ID card at the claiming venue.',
    bg: 'bg-emerald-500',
  },
  {
    icon: BanknotesIcon,
    title: 'Grant Released',
    desc: 'Compliant beneficiaries receive their cash grant. The transaction is logged.',
    bg: 'bg-amber-500',
  },
]
</script>
