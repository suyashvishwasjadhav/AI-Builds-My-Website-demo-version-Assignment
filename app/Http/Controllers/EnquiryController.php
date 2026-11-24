<?php

namespace App\Http\Controllers;

use App\Enums\SiteStatus;
use App\Models\Enquiry;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class EnquiryController extends Controller
{
    public function store(Request $request, string $slug): RedirectResponse
    {
        $site = Site::where('slug', $slug)
            ->where('status', SiteStatus::Published)
            ->firstOrFail();

        // Drop spam attempts that fill the hidden honeypot field
        if ($request->filled('website_hp')) {
            return redirect()
                ->route('public.site', $site->slug)
                ->with('enquiry_sent', 'Thank you! Your message has been sent.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $enquiry = $site->enquiries()->create($validated);

        try {
            if ($site->user?->email) {
                Mail::raw(
                    "New enquiry for {$site->business_name}\n\nFrom: {$enquiry->name} ({$enquiry->email})\nPhone: ".($enquiry->phone ?: 'N/A')."\n\n{$enquiry->message}",
                    fn ($mail) => $mail->to($site->user->email)->subject("New enquiry for {$site->business_name}")
                );
            }
        } catch (Throwable $e) {
            Log::warning('Failed to send enquiry email notification', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('public.site', $site->slug)
            ->with('enquiry_sent', 'Thank you! Your message has been sent.');
    }

    public function index(Site $site): View
    {
        Gate::authorize('view', $site);

        $enquiries = $site->enquiries()->latest('id')->get();

        return view('dashboard.enquiries', [
            'site' => $site,
            'enquiries' => $enquiries,
        ]);
    }

    public function markRead(Site $site, Enquiry $enquiry): RedirectResponse
    {
        Gate::authorize('update', $site);
        abort_unless($enquiry->site_id === $site->id, 404);

        $enquiry->update(['read_at' => now()]);

        return redirect()
            ->route('sites.enquiries', $site)
            ->with('status', 'Enquiry marked as read.');
    }
}
