# 5-Minute Demo Script — "AI Builds My Website"

**Goal:** Show how the app builds accurate, verifiable websites for two real-world businesses—one clean case and one messy case—without ever inventing facts.

---

## Setup Before Recording (30 sec)
- Run `php artisan serve` and `php artisan queue:work` in two terminals.
- Log in as `demo@example.com` (`password`).

---

## Part 1: Clean Case — "Sunrise Bakery, Pune" (~2 min)
1. **Intake (`/sites/create`)**:
   - Enter Business Name: `Sunrise Bakery`, City: `Pune`.
   - Upload a clean price-list CSV (`Croissant, Rs 120`, `Sourdough Loaf, Rs 180`) and a text PDF brochure.
   - Add notes: *"Open daily 7am to 9pm. Phone: +91 98200 12345."*
   - Click **Create site** -> lands on `/sites/{id}` Overview.
2. **Sources & Facts**:
   - Open **Sources** in the sidebar: show `read` status and extracted text.
   - Open **Facts**: show each fact's value, source label, verbatim `snippet`, and confidence.
3. **Generate & Preview**:
   - On **Overview**, click **Generate website** -> show live progress (`Reading… Checking… Writing…`).
   - Click **Preview & theme**: switch between **Clean**, **Bold**, and **Warm** themes, pointing out that the copy stays identical and empty slots (like missing gallery photos) are hidden on public render.
4. **Edit, Regenerate & Publish**:
   - Open **Sections**: hand-edit the Hero subheadline (saved as `User-written` version), then click **Regenerate section** on `About` with instruction `"shorter"` (only `About` changes; `Version history` shows both `AI-written` and `User-written` with **Restore this version**).
   - Return to **Overview**, click **Publish website** (confirm dialog), then click **View live site** (`/s/sunrise-bakery`) in an incognito window.
   - Submit a test message in the public contact form and show it arriving in **Enquiries**.

---

## Part 2: Messy Case — "Green Leaf Cafe, Bengaluru" (~2.5 min)
1. **Intake with Messy Inputs**:
   - Create a site with:
     - An Instagram link (`https://www.instagram.com/greenleaf`) -> `Reader` marks it `blocked` and creates a `missing_info` question: *"We couldn't read this link. Please paste the details or upload a screenshot."*
     - Two sources with **conflicting phone numbers** (`080-11112222` in a PDF vs `080-99998888` on a website).
2. **Blocked Publish & Conflict Resolution**:
   - Point out that **Generate website** and **Publish website** are **blocked** while the `resolve_conflict` question is open.
   - In **Needs your attention**, pick the correct phone number (or type a custom one) -> chosen fact becomes `confirmed`, the other becomes `rejected`.
3. **AI Failure Fallback**:
   - Simulate AI outage (e.g. invalid/empty `GEMINI_API_KEY` or rate limit) and click **Generate website**.
   - Show the friendly error message (no raw stack trace) and click **Build basic site without AI**.
   - Show that a clean, working website is still built strictly from confirmed facts and can be published immediately.
