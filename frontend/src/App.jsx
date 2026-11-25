import { useEffect, useRef, useState } from 'react'
import gsap from 'gsap'
import { ArrowRight } from 'lucide-react'

const DEFAULT_BRIDGE = {
  authenticated: false,
  user: null,
  routes: {
    login: '/login',
    register: '/register',
    dashboard: '/dashboard',
    createSite: '/sites/create',
    apiLandingData: '/api/landing-data',
  },
}

export default function App() {
  const rootRef = useRef(null)
  const [bridge, setBridge] = useState(() => window.__APP_BRIDGE__ || DEFAULT_BRIDGE)
  const [businessName, setBusinessName] = useState('')
  const [city, setCity] = useState('')
  const [googleUrl, setGoogleUrl] = useState('')

  useEffect(() => {
    const endpoint = bridge?.routes?.apiLandingData || '/api/landing-data'
    fetch(endpoint, { headers: { Accept: 'application/json' } })
      .then((res) => (res.ok ? res.json() : null))
      .then((data) => {
        if (data && data.routes) {
          setBridge(data)
        }
      })
      .catch(() => {})
  }, [])

  useEffect(() => {
    const mm = gsap.matchMedia()

    mm.add('(prefers-reduced-motion: no-preference)', () => {
      const ctx = gsap.context(() => {
        gsap.from('.hero-stagger', {
          y: 14,
          opacity: 0,
          duration: 0.5,
          ease: 'power2.out',
          clearProps: 'transform,opacity',
        })
      }, rootRef)

      return () => ctx.revert()
    })

    return () => mm.revert()
  }, [])

  const handleQuickStart = (e) => {
    e.preventDefault()
    const params = new URLSearchParams()
    if (businessName.trim()) params.set('business_name', businessName.trim())
    if (city.trim()) params.set('city', city.trim())
    if (googleUrl.trim()) params.set('google_business_url', googleUrl.trim())

    const target = `${bridge.routes.createSite}?${params.toString()}`
    if (bridge.authenticated) {
      window.location.href = target
    } else {
      window.location.href = bridge.routes.login
    }
  }

  return (
    <div ref={rootRef} className="min-h-screen bg-slate-50 text-slate-900 flex flex-col justify-between antialiased">
      {/* Crisp Minimal Header */}
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-3.5 sm:px-6">
          <a href="/" className="flex items-center gap-2.5 text-sm font-semibold tracking-tight text-slate-900">
            <span className="flex h-7 w-7 items-center justify-center rounded-md bg-slate-900 text-white" aria-hidden="true">
              <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <rect x="3" y="4" width="18" height="16" rx="2" />
                <path d="M3 9h18" />
                <path d="M9 20V9" />
              </svg>
            </span>
            <span>AI Builds My Website</span>
          </a>

          <div className="flex items-center gap-2.5">
            <a
              href="/templates/clean"
              className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition"
            >
              Templates
            </a>
            {bridge.authenticated ? (
              <a
                href={bridge.routes.dashboard}
                className="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 transition"
              >
                <span>Dashboard</span>
                <ArrowRight className="h-3.5 w-3.5" />
              </a>
            ) : (
              <>
                <a
                  href={bridge.routes.login}
                  className="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-900 transition"
                >
                  Log in
                </a>
                <a
                  href={bridge.routes.register}
                  className="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 transition"
                >
                  <span>Get started</span>
                  <ArrowRight className="h-3.5 w-3.5" />
                </a>
              </>
            )}
          </div>
        </div>
      </header>

      {/* Clean Centered Business Intake Card */}
      <main className="flex-1 flex items-center justify-center px-4 py-12 sm:px-6">
        <div className="hero-stagger w-full max-w-md rounded-xl border border-slate-200 bg-white p-7 sm:p-8 shadow-sm">
          <div className="mb-6">
            <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              Step 1 of 3
            </p>
            <h1 id="builder-form-title" className="mt-1 text-xl font-semibold tracking-tight text-slate-900">
              Create your business website
            </h1>
            <p className="mt-1.5 text-xs leading-relaxed text-slate-500">
              Enter your business name and city to upload documents, verify facts, and publish.
            </p>
          </div>

          <form onSubmit={handleQuickStart} aria-labelledby="builder-form-title" className="space-y-4">
            <div>
              <label htmlFor="landing_business_name" className="mb-1.5 block text-xs font-medium text-slate-700">
                Business name <span aria-hidden="true" className="text-rose-600">*</span>
              </label>
              <input
                id="landing_business_name"
                name="business_name"
                type="text"
                required
                value={businessName}
                onChange={(e) => setBusinessName(e.target.value)}
                placeholder="e.g. Oceanic Engineers & Consultants"
                className="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none transition"
              />
            </div>

            <div>
              <label htmlFor="landing_city" className="mb-1.5 block text-xs font-medium text-slate-700">
                City <span aria-hidden="true" className="text-rose-600">*</span>
              </label>
              <input
                id="landing_city"
                name="city"
                type="text"
                required
                value={city}
                onChange={(e) => setCity(e.target.value)}
                placeholder="e.g. Mumbai"
                className="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none transition"
              />
            </div>

            <div>
              <label htmlFor="landing_google_url" className="mb-1.5 block text-xs font-medium text-slate-700">
                Google Business or website link <span className="text-slate-400 font-normal">(optional)</span>
              </label>
              <input
                id="landing_google_url"
                name="google_business_url"
                type="url"
                value={googleUrl}
                onChange={(e) => setGoogleUrl(e.target.value)}
                placeholder="https://maps.app.goo.gl/..."
                autoCorrect="off"
                autoCapitalize="none"
                spellCheck={false}
                className="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none transition"
              />
            </div>

            <button
              type="submit"
              className="mt-2 flex w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition cursor-pointer"
            >
              <span>{bridge.authenticated ? 'Continue to File Upload' : 'Sign In & Continue'}</span>
              <ArrowRight className="h-4 w-4" />
            </button>
          </form>

          <div className="mt-6 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4 text-xs text-slate-500">
            <span>Demo: demo@example.com / password</span>
            <a href={bridge.routes.dashboard} className="font-medium text-slate-900 hover:underline">
              Open Dashboard &rarr;
            </a>
          </div>
        </div>
      </main>

      <footer className="border-t border-slate-200 bg-white py-4 text-center text-xs text-slate-500">
        AI Builds My Website &middot; Fact-verified business websites
      </footer>
    </div>
  )
}
