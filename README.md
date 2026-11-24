# AI Builds My Website

A Laravel + MySQL web application where a business owner shares what they have (business name, city, social links, Google Business link, PDFs, price lists, photos). The app reads those sources, searches the web, asks the user to resolve anything unclear, and uses AI (Google Gemini) to write a ready-to-publish website from confirmed facts.

## Tech Stack

- PHP 8.2+ & Laravel
- MySQL
- Blade + Tailwind CSS (Vite) + Alpine.js
- Laravel Database Queue Driver
- Google Gemini API (via HTTP)

## Setup Steps

1. Install PHP dependencies:
   ```sh
   composer install
   ```
2. Copy the environment file and generate an app key:
   ```sh
   cp .env.example .env
   php artisan key:generate
   ```
3. Configure your MySQL database, `GEMINI_API_KEY`, `GEMINI_MODEL`, and search settings in `.env`.
4. Run database migrations and seeders:
   ```sh
   php artisan migrate --seed
   ```
5. Install frontend dependencies and build assets:
   ```sh
   npm install
   npm run build
   ```
6. Start the application and queue worker:
   ```sh
   php artisan serve
   php artisan queue:work
   ```
7. Run tests:
   ```sh
   php artisan test
   ```
