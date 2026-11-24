# Architecture & Product Decisions

## Key Choices (and Why)

1. **Blade Templates + JSON Copy (AI never writes HTML/CSS)**
   - *Why:* Separating layout (hand-crafted Tailwind Blade templates `clean`, `bold`, `warm`) from copy (strictly validated JSON from `Writer`) guarantees mobile responsiveness, XSS safety (`{{ }}` escaping), and instant theme switching without re-calling the AI.
2. **Fact Provenance & Grounding (`facts.snippet` + `Verifier`)**
   - *Why:* `FactExtractor` requires an exact verbatim `snippet` from the source text for every AI-extracted fact and drops any fact whose snippet is missing. `Verifier` normalizes values (e.g. `+91 98200 12345` vs `9820012345`) before flagging conflicts, ensuring the AI never guesses or invents business details.
3. **Plain Service Classes in `app/Services/`**
   - *Why:* Keeping `Reader`, `UrlGuard`, `WebSearcher`, `SafetyGuard`, `FactExtractor`, `Verifier`, `Writer`, `SitePresenter`, and `Publisher` as focused, single-purpose services makes them easy to unit-test with `Http::fake()` and keeps controllers thin.
4. **Database Queue Driver + Live Progress Polling**
   - *Why:* File reading, link scraping, and AI generation are slow I/O tasks. Using Laravel's built-in `database` queue avoids extra Redis infrastructure while keeping the UI responsive with `Reading… Checking… Writing…` status polling.
5. **Graceful Degradation & No-AI Fallback**
   - *Why:* If Gemini times out or hits `429` rate limits, `Writer` retries twice with backoff, logs the failure to `ai_runs`, preserves all user data, and offers a one-click **"Build basic site without AI"** fallback directly from confirmed facts.
6. **SSRF & Prompt-Injection Defense in Depth**
   - *Why:* `UrlGuard` blocks non-HTTP schemes and private/loopback IP ranges before any outbound fetch. `SafetyGuard` strips instruction-like patterns from untrusted uploads, flags them as `flagged_content` questions, and all prompts isolate untrusted text inside `--- BEGIN DATA ---` blocks.

## What I Cut

- **Billing, custom domains, team roles, social auto-posting, analytics, e-commerce, and multi-language auto-translation** (explicitly deferred per `AGENTS.md` section 9).
- **Dedicated Media & Settings dashboard tabs**: The sidebar links exist as placeholders; uploaded photos are supported in templates/preview via `media` records, while full media library management and site settings editing were left for a next iteration.

## What's Next

- Custom domain mapping (`sites.custom_domain`) and subdomain routing (`{slug}.yourdomain.com`).
- Full Media manager UI (upload/reorder gallery photos, pick hero image, edit alt text) and Site Settings UI (change language, delete site).
- Scheduled re-crawling of business links to detect updated hours or prices.
- Multi-language copy generation UI selector.

## Known Limits

- **Social media walls**: Facebook, Instagram, and LinkedIn frequently block automated fetches with login walls or `403`/`429`; the app intentionally marks them `blocked` and asks the owner to paste details or upload a screenshot rather than attempting to bypass blocks.
- **Scanned PDFs without `GEMINI_API_KEY`**: Text PDFs are parsed locally with `smalot/pdfparser`, but image-only PDFs and photos require a valid `GEMINI_API_KEY` for OCR/transcription.
- **Local storage for uploads**: Uploaded files are stored on the `local` disk outside `public/`; moving to S3-compatible object storage would be needed for multi-server horizontal scaling.
