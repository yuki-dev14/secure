# Security Hardening Report (OWASP 2025 / Defense Pass) — SECURE System

**Target System:** SECURE (System for Eligibility Checking, Unified Records, and Evaluation)  
**Date:** September 17, 2026  
**Scope:** Defensive security hardening pass across OWASP Top 10 categories, utilizing additive and non-breaking middleware, headers, logging, and configuration hardening.

---

## 1. Executive Summary

This hardening pass reviewed the SECURE codebase against current standard secure-coding guidelines (OWASP Top 10), checking access controls, configuration, dependencies, cryptography, input handling, rate limiting, session attributes, data integrity, logging/alerting, and exception handling. 

All improvements made are strictly additive, non-breaking, and preserve existing business logic.

---

## 2. Category-by-Category Findings & Hardening Actions

### A01 - Broken Access Control & SSRF
- **Findings**:
  - Protected routes enforce role middleware (`role:superadmin`, `role:beneficiary`, `role:admin_swa`, etc.) and authenticated session verification.
  - No user-controlled outbound HTTP request mechanisms (such as arbitrary `curl` or `Http::get()` with user URLs) exist in the backend. File operations (`fopen`) are limited to in-memory `php://output` for CSV streaming or static `public_path('logo.svg')`.
- **Hardening Applied**:
  - In [`app/Http/Middleware/CheckRole.php`](file:///c:/Users/Euclid/Desktop/Secure/app/Http/Middleware/CheckRole.php), added security logging (`Log::warning`) on access control failures before throwing HTTP 403, logging user ID, user role, requested role, IP address, and target URL for security visibility.
- **Status**: **Hardened & Verified**.

---

### A02 - Security Misconfiguration
- **Findings**:
  - Standard headers needed HSTS (`Strict-Transport-Security`) and `Cross-Origin-Opener-Policy` to guard against SSL stripping and cross-window origin isolation issues.
- **Hardening Applied**:
  - In [`app/Http/Middleware/SecurityHeaders.php`](file:///c:/Users/Euclid/Desktop/Secure/app/Http/Middleware/SecurityHeaders.php), added:
    - `Cross-Origin-Opener-Policy: same-origin`
    - `Strict-Transport-Security: max-age=31536000; includeSubDomains` (automatically enforced whenever request is over HTTPS or running in production).
- **Status**: **Hardened & Verified**.

---

### A03 - Software Supply Chain & Dependency Audit
- **Findings**:
  - **Composer Audit**: Identified vulnerabilities in transitive dependencies:
    - `phpoffice/phpspreadsheet` (via `maatwebsite/excel`): CVE-2026-34084 (SSRF/RCE in `IOFactory::load`), CVE-2026-40296, CVE-2026-35453.
    - `symfony/yaml`: CVE-2026-45304, CVE-2026-45305, CVE-2026-45133.
  - **NPM Audit**: Identified 11 vulnerabilities in frontend dependencies:
    - `axios` (vulnerabilities reported up to v1.17.0, currently installed v1.14.0).
    - `vite`, `esbuild`, `postcss`, `nanoid`, `concurrently` (via `shell-quote`).
- **Manual Review / Recommended Action**:
  - Run `npm audit fix` to update patchable dev dependencies.
  - Run `composer update phpoffice/phpspreadsheet symfony/yaml --with-dependencies` in a controlled staging test to upgrade transitive packages to patched minor versions without breaking Excel export workflows.
- **Status**: **Audited (Action Flagged for Manual Review)**.

---

### A04 - Cryptographic Failures
- **Findings**:
  - Application passwords use `bcrypt` via Laravel's `Hash` facade with cost factor `BCRYPT_ROUNDS=12` in `.env`, matching high security standards.
  - Database connection strings and application secrets are stored in `.env` and excluded via `.gitignore`.
- **Status**: **Compliant**.

---

### A05 - Injection (SQL, XSS, Command Injection)
- **Findings**:
  - Database queries use Eloquent ORM and Query Builder parameterized statements. Raw clauses in aggregation reports use positional parameter bindings (e.g. `whereRaw("(new_values->>'distribution_event_id')::int = ?", [$event->id])`).
  - Blade templates and Vue 3 frontend templates automatically perform HTML entity escaping on output expressions.
- **Status**: **Compliant**.

---

### A06 - Insecure Design & Rate Limiting
- **Findings**:
  - Login endpoints were previously protected with `throttle:10,1`. However, the beneficiary first-time password change route (`/portal/change-password`) was not throttled.
- **Hardening Applied**:
  - In [`routes/web.php`](file:///c:/Users/Euclid/Desktop/Secure/routes/web.php), added `throttle:6,1` (maximum 6 attempts per minute per IP) to `POST /portal/change-password`.
- **Status**: **Hardened & Verified**.

---

### A07 - Authentication & Session Handling
- **Findings**:
  - In [`config/session.php`](file:///c:/Users/Euclid/Desktop/Secure/config/session.php):
    - `http_only => true` (mitigates session cookie theft via XSS).
    - `same_site => 'lax'` (mitigates CSRF).
    - `lifetime => 120` minutes.
- **Production Recommendation**:
  - Set `SESSION_SECURE_COOKIE=true` in production `.env` to enforce HTTPS-only cookie transmission.
- **Status**: **Compliant**.

---

### A08 - Software and Data Integrity Failures
- **Findings**:
  - Zero usage of native PHP `unserialize()` across the application code.
  - QR payload signatures are cryptographically validated against `beneficiary_cards.qr_code_data` rather than deserializing objects directly.
- **Status**: **Compliant**.

---

### A09 - Security Logging & Alerting
- **Findings**:
  - Failed logins were logged to the `audit_logs` database table, but did not emit standard application log messages that automated monitoring, syslog, SIEM, or error alerting services monitor.
- **Hardening Applied**:
  - In [`app/Services/AuditLogService.php`](file:///c:/Users/Euclid/Desktop/Secure/app/Services/AuditLogService.php), added `Log::warning("Security Alert: Failed login attempt for identifier: {$identifier}", [...])` alongside database audit log entries.
  - In [`app/Http/Middleware/CheckRole.php`](file:///c:/Users/Euclid/Desktop/Secure/app/Http/Middleware/CheckRole.php), added `Log::warning('Security Alert: Access control denied', [...])` on unauthorized attempts.
- **Status**: **Hardened & Verified**.

---

### A10 - Mishandling of Exceptional Conditions (Fail-Closed Enforcement)
- **Findings**:
  - Role-based middleware [`CheckRole.php`](file:///c:/Users/Euclid/Desktop/Secure/app/Http/Middleware/CheckRole.php) explicitly checks `$user` presence and role match before proceeding; if unauthenticated or role does not match, it terminates request processing with HTTP 403 (fails closed).
  - Unauthenticated route visits redirect to staff/beneficiary login via `redirectGuestsTo` in [`bootstrap/app.php`](file:///c:/Users/Euclid/Desktop/Secure/bootstrap/app.php).
- **Status**: **Compliant**.

---

## 3. Files Modified During Hardening

| File | Change Description |
| :--- | :--- |
| [`app/Http/Middleware/CheckRole.php`](file:///c:/Users/Euclid/Desktop/Secure/app/Http/Middleware/CheckRole.php) | Added security warning logging for access-control denials with IP, URL, and user context. |
| [`app/Services/AuditLogService.php`](file:///c:/Users/Euclid/Desktop/Secure/app/Services/AuditLogService.php) | Added structured `Log::warning` alerts for failed authentication attempts. |
| [`app/Http/Middleware/SecurityHeaders.php`](file:///c:/Users/Euclid/Desktop/Secure/app/Http/Middleware/SecurityHeaders.php) | Added `Cross-Origin-Opener-Policy: same-origin` and conditional HSTS enforcement. |
| [`routes/web.php`](file:///c:/Users/Euclid/Desktop/Secure/routes/web.php) | Added `throttle:6,1` rate limiting to the password change endpoint. |

---

## 4. Items for Manual Review (Accepted Risk / Staging Actions)

1. **Dependency Upgrades**:
   - `phpoffice/phpspreadsheet`: Upgrade via `composer update phpoffice/phpspreadsheet` after verifying Excel export compatibility on staging.
   - `axios` & `vite`: Run `npm update axios vite` or `npm audit fix` and test Inertia and camera scanner frontend bundles.
2. **Production Environment Settings**:
   - Ensure `APP_DEBUG=false` in production `.env` to prevent stack trace leaks.
   - Ensure `SESSION_SECURE_COOKIE=true` on HTTPS domains.
