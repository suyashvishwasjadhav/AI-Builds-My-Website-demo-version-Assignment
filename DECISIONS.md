# Architecture & Product Decisions

## Key Choices (and Why)

1. **Structured JSON Copy + Hand-Crafted Blade Templates (AI never writes HTML/CSS)**
   - *Why:* Separating layout (`clean`, `bold`, `warm` Tailwind Blade templates) from AI-generated copy (`Writer` JSON schema) guarantees responsive layouts, automatic XSS escaping (`{{ }}`), and instant theme/color switching in Split-Screen Studio without re-calling the LLM.

2. **Fact Provenance & Zero-Hallucination Gate (`facts.snippet` + `Verifier`)**
   - *Why:* `FactExtractor` requires an exact verbatim `snippet` from the source text for every extracted fact and discards ungrounded claims. `Verifier` normalizes values (e.g., phone formats) before raising `PickBusiness` or `ResolveConflict` questions, and missing slots (like address) are asked explicitly or hidden rather than invented.

3. **Multi-LLM Cascade + Non-AI Graceful Fallback (`AiService` + `Writer`)**
   - *Why:* Free-tier LLM APIs can rate-limit (`429`) or time out. `AiService` retries and cascades across `Groq → OpenRouter → Gemini → Mistral → Nvidia`, logs every attempt to `ai_runs`, and preserves user data. If all providers fail, the owner gets **Retry AI** and a one-click **"Build basic site without AI"** fallback directly from confirmed facts.

4. **Hybrid Web Search (`SearchDriver` `auto` mode)**
   - *Why:* Binding `SearchDriver` via `AppServiceProvider` lets the app use **Google Custom Search** or **Google Places** when API keys are provided, while automatically falling back to `FreeWebSearchDriver` (OpenStreetMap Nominatim + DuckDuckGo Instant Answer) at **$0** when no Google key is configured.

5. **Database Queue + Live Polling + Continuous Worker**
   - *Why:* Link fetching, PDF/DOCX parsing, and AI generation are slow I/O tasks. Laravel's `database` queue avoids running a separate Redis service while keeping the dashboard responsive with live stage polling (`Reading… → Checking… → Writing…`), auto-publishing the site on completion.

6. **Security in Depth**
   - *Why:* Untrusted URLs and uploaded files are high-risk surfaces. `UrlGuard` blocks non-HTTP schemes and private/loopback/metadata IP ranges (SSRF protection); `SafetyGuard` strips prompt-injection instructions from uploads and flags them for review; all site routes enforce Laravel `Policy` ownership checks (IDOR protection), rate limiting, strict image MIME validation, and `SecurityHeaders` (`CSP`, `X-Frame-Options`, `nosniff`, `Referrer-Policy`).

7. **Single-Container Deployment on AWS EC2 + Aiven Free Tier MySQL**
   - *Why:* Packaging the React/Vite landing build and Laravel 13 app into one multi-stage Alpine Docker container eliminates CORS complexity and keeps hosting at **$0/month** on AWS Free Tier (`t3.micro` EC2 in `ap-southeast-2`) behind **Caddy** (automatic Let's Encrypt HTTPS via DuckDNS), paired with **Aiven Free Tier MySQL** over TLS. Session and cache drivers use the local `file` store to avoid cross-region DB round-trips on every page load while keeping all application data in MySQL.

---

## What I Cut (Intentional Scope Decisions)

- **Payments, Billing & E-Commerce Checkout:** Deferred to keep focus on the core AI intake → verification → publish workflow.
- **Multi-User Team RBAC & Public Self-Signup:** Kept single-owner workspace authorization and pre-filled demo login for clean evaluation without exposing open registration.
- **Wildcard Subdomain DNS (`*.domain.com`):** Free dynamic DNS (`duckdns.org`) does not support wildcard sub-subdomains, so published sites use clean `/s/{slug}` paths plus an optional `custom_domain` field.
- **Traffic Analytics Dashboard:** Focused instead on actionable lead capture (Enquiries inbox, CSV export, WhatsApp click-to-chat, and printable QR posters).

---

## What's Next

- **Wildcard Subdomain Routing (`{slug}.yourdomain.com`):** Point a root domain with wildcard DNS (`*`) to Caddy so every business gets a dedicated subdomain automatically.
- **S3-Compatible Media Storage:** Move uploaded photos from the container's local disk to AWS S3 for multi-instance horizontal scaling.
- **Scheduled Source Re-Crawling:** Periodically re-check linked websites/listings to flag updated opening hours or pricing.

---

## Known Limits

- **Social Media Scraping Walls:** Instagram, Facebook, and LinkedIn frequently block automated fetches with login walls (`403`/`429`). Instead of bypassing blocks, the app marks the source `blocked` and prompts the owner to paste details or upload a screenshot.
- **Image-Only / Scanned PDFs:** Text PDFs are parsed locally via `smalot/pdfparser`, whereas scanned/image-only PDFs require a vision-capable AI API key.
- **Automated Test Coverage:** Verified with `php artisan test` (**131 passed, 609 assertions**) covering fact extraction, verification, SSRF `UrlGuard`, IDOR authorization, AI fallback, search drivers, and public site rendering.
