# AI Builds My Website

A Laravel + MySQL web application where a small business owner shares what they already have (business name, city, social links, Google Business link, PDFs, price lists, photos, and notes). The app reads those sources, searches the web, asks the owner to resolve any conflicts or missing details, and uses Google Gemini to write a ready-to-publish website strictly from confirmed facts.

> **Core rule:** The AI only writes words—never page code—and never invents facts. Every phone number, address, hour, and service on a published site comes from a confirmed or user-provided fact in MySQL.

## How the Pipeline Works

1. **Intake (`SiteController`)**: Owner submits business name, city, links, files (stored outside `public/` with random names), and notes.
2. **Read (`Reader` + `UrlGuard`)**: Queued jobs extract text from PDFs, CSV/XLSX spreadsheets, images (via Gemini), and safe public URLs, marking blocked social walls as `blocked`.
3. **Search (`WebSearcher`)**: Optional web/places search finds candidate listings and asks the owner to confirm which business is theirs (`pick_business`).
4. **Extract & Guard (`SafetyGuard` + `FactExtractor`)**: Strips prompt-injection attempts from untrusted text, extracts structured facts with verbatim snippets, and drops any fact not grounded in the source.
5. **Verify (`Verifier`)**: Normalizes values (e.g. phone formats), flags conflicts (`resolve_conflict`) and missing essentials (`missing_info`), and records owner answers.
6. **Write & Publish (`Writer` + `Publisher`)**: Generates section copy from confirmed facts (with a no-AI fallback if Gemini is down), supports section editing/regeneration/version restore, theme switching (`clean`, `bold`, `warm`), and publishing to `/s/{slug}`.

## Requirements

- PHP 8.2+ (with `pdo_mysql`, `mbstring`, `xml`, `zip`, `gd` extensions)
- Composer 2.x
- MySQL 8.0+ (or SQLite for quick local testing)
- Node.js 20+ & npm

## Setup Steps

1. **Clone and install dependencies:**
   ```sh
   composer install
   npm install
   ```
2. **Create `.env` and generate the app key:**
   ```sh
   cp .env.example .env
   php artisan key:generate
   ```
3. **Configure `.env`** (see environment variables below) with your MySQL credentials and optional API keys.
4. **Run migrations and seed the demo user:**
   ```sh
   php artisan migrate --seed
   ```
   Demo login: `demo@example.com` / `password`
5. **Build frontend assets:**
   ```sh
   npm run build
   ```
6. **Start the web server and queue worker** (in separate terminals):
   ```sh
   php artisan serve
   php artisan queue:work
   ```

## Environment Variables (`.env`)

| Variable | Purpose |
|---|---|
| `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | Standard Laravel application settings. |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL connection details. |
| `QUEUE_CONNECTION` | Set to `database` to process source reading and site generation jobs asynchronously. |
| `GEMINI_API_KEY` | Google Gemini API key for image transcription, fact extraction, and copywriting. Leave empty to test the no-AI fallback. |
| `GEMINI_MODEL` | Gemini model ID (e.g. `gemini-2.5-flash`). |
| `SEARCH_DRIVER` | Web search driver: `null` (default, skips search), `google_cse`, or `places`. |
| `SEARCH_API_KEY` | API key for Google Custom Search or Places API (optional). |
| `SEARCH_ENGINE_ID` | Programmable Search Engine ID (`cx`) when using `google_cse` (optional). |

## Running Tests

```sh
php artisan test
```

All HTTP calls to Gemini and search APIs are mocked with `Http::fake()` in the test suite, so tests run fast and offline.
