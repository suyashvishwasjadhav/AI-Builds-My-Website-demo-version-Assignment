# AI-Builds-My-Website-demo-version-Assignment

A full-stack **Laravel + MySQL + React** web application where a small business owner shares what they already have (business name, city, social links, Google Business link, PDFs, price lists, photos, and notes). The app reads those sources, verifies facts with provenance, resolves conflicts with the owner, and uses a resilient multi-provider AI cascade to build a ready-to-publish website.

> **Golden Rule:** Never invent facts. Every phone number, address, business hour, price, and service on a published website comes strictly from a confirmed or user-provided fact.

---

## Project Structure

```text
├── backend/    # Laravel 13 application, services, controllers, Blade templates & tests
├── frontend/   # React + Vite + Tailwind + GSAP intake UI (builds into backend/public/landing)
├── database/   # Shared migrations, factories, seeders & SQLite/MySQL schema
└── run.sh      # One-command setup, build & server/queue runner
```

---

## Key Features

- **Multi-Source Ingestion:** Business name/city, Google Business URL, social links, PDF brochures, CSV/XLSX spreadsheets, photos, and optional dynamic price lists.
- **Fact Provenance & Edge-Case Handling:**
  - Every extracted fact stores its verbatim source snippet (`facts.snippet`).
  - **Same-name businesses:** `pick_business` disambiguation prompt.
  - **Disagreeing sources:** `resolve_conflict` prompt (e.g., two different phone numbers).
  - **Blocked social sites:** Detects login walls and asks the owner directly.
  - **Prompt injection defense:** `SafetyGuard` strips hidden instructions in uploaded files + `<DATA>` prompt isolation.
  - **Graceful AI fallback:** Multi-provider cascade (`Groq` → `OpenRouter` → `Gemini` → `Mistral` → `Nvidia`) plus a deterministic **"Build basic site without AI"** fallback.
- **Split-Screen Studio & Inline Editing:** Desktop/Tablet/Mobile viewport toggles, click-to-edit headlines/copy with version history & rollback, AI section rewrite modifiers, and brand accent color + typography customizer.
- **3 Responsive Templates (`clean`, `bold`, `warm`) with GSAP + Lenis:** Live template demos at `/templates/clean`, `/templates/bold`, and `/templates/warm`.
- **SME Conversion & Export Tools:**
  - Floating **Chat on WhatsApp** widget & live **Open Now / Closed** business hours badge.
  - Interactive Google Maps embed & **Get Directions** link.
  - **Instant Printable QR Code** (SVG download + printable counter poster).
  - **Multi-Language Translation** (Hindi, Marathi, Gujarati, Tamil, Spanish, French) with native script switcher.
  - **1-Click Static Website ZIP Export**, dynamic `/s/{slug}/sitemap.xml`, `robots.txt`, OpenGraph SVG cards, custom domain CNAME verification, and Leads Inbox with CSV export.
- **Security Hardening:** HTTP security headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `CSP frame-ancestors`), SSRF `UrlGuard`, cross-site IDOR checks, strict image-only upload validation, honeypot spam protection, and route rate limiting.

---

## Requirements

- PHP 8.2+ (with `pdo_mysql` / `pdo_sqlite`, `mbstring`, `xml`, `zip`, `gd`)
- Composer 2.x
- MySQL 8.0+ (or SQLite for quick local runs)
- Node.js 20+ & npm

---

## Quick Start (Recommended)

```sh
chmod +x run.sh
./run.sh --seed --build
```

This installs dependencies, prepares `backend/.env`, runs migrations + seeders, builds `frontend/` and `backend/` assets, and starts the server + queue worker at **http://localhost:8000**.

Demo login: `demo@example.com` / `password`

---

## Manual Setup

1. **Backend setup:**
   ```sh
   cd backend
   composer install
   npm install
   cp .env.example .env
   php artisan key:generate
   php artisan storage:link
   php artisan migrate --seed
   npm run build
   ```
2. **Frontend intake UI build:**
   ```sh
   cd ../frontend
   npm install
   npm run build
   ```
3. **Start web server & queue worker:**
   ```sh
   cd ../backend
   php artisan serve --host=0.0.0.0 --port=8000
   php artisan queue:listen --tries=1 --timeout=0
   ```

---

## Environment Variables (`backend/.env`)

| Variable | Purpose |
|---|---|
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL or SQLite database connection. |
| `QUEUE_CONNECTION` | Set to `database` for async source reading and site generation jobs. |
| `GROQ_API_KEY`, `OPENROUTER_API_KEY`, `GEMINI_API_KEY`, `MISTRAL_API_KEY`, `NVIDIA_API_KEY` | Optional AI provider keys (cascades automatically; leave empty to use no-AI fallback). |
| `SEARCH_DRIVER`, `SEARCH_API_KEY`, `SEARCH_ENGINE_ID` | Web search driver (`null`, `google_cse`, or `places`). |

---

## Running Tests

```sh
cd backend
php artisan test
```

All external AI and search HTTP calls are mocked cleanly in the test suite (**130 tests, 604 assertions**).
