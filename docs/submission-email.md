Subject: Internship Assignment Submission — AI Builds My Website (Laravel + MySQL)

Hi Team,

Please find my submission for the "AI Builds My Website" assignment:

- **GitHub Repository:** [GITHUB_REPO_URL]
- **5-Minute Demo Video:** [DEMO_VIDEO_URL]
- **Expected Stipend:** [EXPECTED_STIPEND]
- **Earliest Joining Date:** [EARLIEST_JOINING_DATE]

Key highlights of the implementation:
- **Zero-hallucination pipeline:** Every published fact is grounded in a verified source (`facts.snippet`) and confirmed or provided by the business owner.
- **Safety & resilience:** SSRF protection (`UrlGuard`), prompt-injection stripping (`SafetyGuard`), blocked social-link handling, conflict resolution (`Verifier`), and a no-AI fallback (`Writer::buildBasicFallback`) when Gemini is unavailable.
- **Full test coverage:** 89 automated unit and feature tests (`php artisan test`) covering all edge cases with `Http::fake()`.

Looking forward to your feedback.

Best regards,
[YOUR_NAME]
