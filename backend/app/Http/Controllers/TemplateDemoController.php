<?php

namespace App\Http\Controllers;

use App\Enums\SectionType;
use App\Enums\SiteStatus;
use App\Models\Section;
use App\Models\Site;
use App\Services\BusinessHoursParser;
use App\Services\SitePresenter;
use Illuminate\Http\Response;

class TemplateDemoController extends Controller
{
    public function show(string $template, BusinessHoursParser $hoursParser): Response
    {
        abort_unless(in_array($template, SitePresenter::TEMPLATES, true), 404);

        $demos = [
            'clean' => [
                'business_name' => 'Apex Dental Studio',
                'city' => 'Pune',
                'slug' => 'demo-clean',
                'accent' => '#4f46e5',
                'font' => 'modern',
                'headline' => 'Gentle, Modern Dental Care in Pune',
                'subheadline' => 'Digital X-rays, painless root canals, and cosmetic smile design by experienced specialists.',
                'about_heading' => 'About Apex Dental Studio',
                'about_body' => "Established in 2016 in Koregaon Park, Apex Dental Studio combines calm, patient-first care with modern digital dentistry.\n\nOur clinic follows strict sterilization protocols and transparent pricing before every procedure.",
                'services' => [
                    ['name' => 'Comprehensive Dental Checkup', 'description' => 'Full oral exam with digital X-ray and personalized care plan.'],
                    ['name' => 'Painless Root Canal', 'description' => 'Single-sitting rotary endodontics with local anesthesia.'],
                    ['name' => 'Teeth Whitening & Veneers', 'description' => 'Safe chair-side brightening and custom ceramic veneers.'],
                    ['name' => 'Dental Implants', 'description' => 'Titanium implants for natural-feeling tooth replacement.'],
                ],
                'price_items' => [
                    ['name' => 'Consultation & Digital X-Ray', 'price' => '₹500', 'unit' => 'per visit'],
                    ['name' => 'Ultrasonic Scaling & Polishing', 'price' => '₹1,200', 'unit' => 'session'],
                    ['name' => 'Single-Sitting Root Canal', 'price' => '₹4,500', 'unit' => 'per tooth'],
                ],
                'facts' => [
                    'business_name' => ['Apex Dental Studio'],
                    'phone' => ['+91 98200 11223'],
                    'email' => ['hello@apexdentaldemo.example'],
                    'address' => ['Lane 7, Koregaon Park, Pune 411001'],
                    'hours' => ['Mon-Sat 9:30 AM - 8:00 PM'],
                ],
                'hero_image' => 'https://images.unsplash.com/photo-1629909613654-28e377c37b09?auto=format&fit=crop&w=1200&q=80',
                'gallery' => [
                    ['url' => 'https://images.unsplash.com/photo-1629909613654-28e377c37b09?auto=format&fit=crop&w=800&q=80', 'alt' => 'Modern dental treatment room'],
                    ['url' => 'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?auto=format&fit=crop&w=800&q=80', 'alt' => 'Dental consultation and care'],
                    ['url' => 'https://images.unsplash.com/photo-1606811841689-23dfddce3e95?auto=format&fit=crop&w=800&q=80', 'alt' => 'Clinic reception and hygiene'],
                ],
            ],
            'bold' => [
                'business_name' => 'Forge & Iron Athletics',
                'city' => 'Bengaluru',
                'slug' => 'demo-bold',
                'accent' => '#6366f1',
                'font' => 'modern',
                'headline' => 'Strength, Conditioning & Personal Coaching',
                'subheadline' => '24/7 Olympic lifting platforms, turf conditioning, and certified strength coaches in Indiranagar.',
                'about_heading' => 'Built for Real Progress',
                'about_body' => "Forge & Iron Athletics is a strength-first training facility in Indiranagar, Bengaluru.\n\nWhether you are preparing for a marathon, building lean muscle, or starting barbell training safely, our coaches track every milestone.",
                'services' => [
                    ['name' => '1-on-1 Strength Coaching', 'description' => 'Custom programming with form checks and weekly progression.'],
                    ['name' => 'Small Group Conditioning', 'description' => 'High-energy 45-minute metabolic and kettlebell sessions.'],
                    ['name' => '24/7 Open Gym Access', 'description' => 'Competition racks, calibrated plates, and recovery zone.'],
                    ['name' => 'Nutrition & Mobility Audit', 'description' => 'Practical macro guidance and joint mobility drills.'],
                ],
                'price_items' => [
                    ['name' => 'Monthly All-Access Membership', 'price' => '₹3,500', 'unit' => 'per month'],
                    ['name' => '12-Session Personal Coaching', 'price' => '₹14,000', 'unit' => 'package'],
                ],
                'facts' => [
                    'business_name' => ['Forge & Iron Athletics'],
                    'phone' => ['+91 99000 44556'],
                    'email' => ['train@forgeirondemo.example'],
                    'address' => ['100 Feet Road, Indiranagar, Bengaluru 560038'],
                    'hours' => ['Open 24 hours'],
                ],
                'hero_image' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1200&q=80',
                'gallery' => [
                    ['url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=800&q=80', 'alt' => 'Strength training floor'],
                    ['url' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=800&q=80', 'alt' => 'Barbell and conditioning zone'],
                    ['url' => 'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?auto=format&fit=crop&w=800&q=80', 'alt' => 'Personal coaching session'],
                ],
            ],
            'warm' => [
                'business_name' => 'Sunrise Artisan Bakery',
                'city' => 'Mumbai',
                'slug' => 'demo-warm',
                'accent' => '#d97706',
                'font' => 'classic',
                'headline' => 'Fresh Sourdough & Handcrafted Pastries Every Morning',
                'subheadline' => 'Slow-fermented loaves, Belgian chocolate cakes, and freshly brewed filter coffee in Bandra West.',
                'about_heading' => 'Our Story',
                'about_body' => "Sunrise Artisan Bakery started in 2015 with one stone-deck oven and a 36-hour sourdough starter.\n\nEvery loaf is baked fresh at 6:00 AM using stone-ground flour, cultured butter, and zero artificial preservatives.",
                'services' => [
                    ['name' => 'Country Sourdough Loaves', 'description' => 'Naturally leavened, crisp crust, and airy crumb baked daily.'],
                    ['name' => 'Custom Celebration Cakes', 'description' => 'Belgian dark chocolate, fresh berry, and eggless options.'],
                    ['name' => 'Morning Croissants & Viennoiserie', 'description' => 'Flaky butter croissants, pain au chocolat, and cinnamon rolls.'],
                    ['name' => 'Corporate & Event Hampers', 'description' => 'Curated pastry boxes for office meetings and weddings.'],
                ],
                'price_items' => [
                    ['name' => 'Classic Country Sourdough (600g)', 'price' => '₹320', 'unit' => 'per loaf'],
                    ['name' => 'Belgian Chocolate Truffle Cake', 'price' => '₹950', 'unit' => '500g'],
                    ['name' => 'Butter Croissant Box (4 pcs)', 'price' => '₹480', 'unit' => 'per box'],
                ],
                'facts' => [
                    'business_name' => ['Sunrise Artisan Bakery'],
                    'phone' => ['+91 98111 77889'],
                    'email' => ['orders@sunrisebakerydemo.example'],
                    'address' => ['Pali Hill, Bandra West, Mumbai 400050'],
                    'hours' => ['Daily 7:00 AM - 9:30 PM'],
                ],
                'hero_image' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1200&q=80',
                'gallery' => [
                    ['url' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=800&q=80', 'alt' => 'Freshly baked sourdough loaves'],
                    ['url' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=800&q=80', 'alt' => 'Butter croissants and pastries'],
                    ['url' => 'https://images.unsplash.com/photo-1549931319-a545dcf3bc73?auto=format&fit=crop&w=800&q=80', 'alt' => 'Artisan bread display'],
                ],
            ],
        ];

        $cfg = $demos[$template];

        $site = new Site([
            'business_name' => $cfg['business_name'],
            'city' => $cfg['city'],
            'slug' => $cfg['slug'],
            'status' => SiteStatus::Published,
            'template' => $template,
            'language' => 'en',
            'timezone' => 'Asia/Kolkata',
            'whatsapp_enabled' => true,
            'theme_config' => [
                'accent_color' => $cfg['accent'],
                'font_pairing' => $cfg['font'],
            ],
        ]);
        $site->id = 0;

        $sections = collect([
            'hero' => new Section([
                'type' => SectionType::Hero,
                'content' => [
                    'headline' => $cfg['headline'],
                    'subheadline' => $cfg['subheadline'],
                ],
                'is_visible' => true,
            ]),
            'about' => new Section([
                'type' => SectionType::About,
                'content' => [
                    'heading' => $cfg['about_heading'],
                    'body' => $cfg['about_body'],
                ],
                'is_visible' => true,
            ]),
            'services' => new Section([
                'type' => SectionType::Services,
                'content' => [
                    'heading' => 'Our Services',
                    'items' => $cfg['services'],
                ],
                'is_visible' => true,
            ]),
            'contact' => new Section([
                'type' => SectionType::Contact,
                'content' => [
                    'heading' => 'Visit or Contact Us',
                    'intro' => "Reach out to {$cfg['business_name']} in {$cfg['city']} for appointments, orders, or enquiries.",
                    'show_map' => true,
                ],
                'is_visible' => true,
            ]),
        ]);

        $facts = $cfg['facts'];
        $digits = preg_replace('/\D+/', '', $facts['phone'][0]);
        $whatsappUrl = "https://wa.me/{$digits}?text=" . rawurlencode("Hi {$cfg['business_name']}, I saw your website and would like to enquire.");
        $openStatus = $hoursParser->evaluate($facts['hours'][0], 'Asia/Kolkata');
        $mapQuery = $facts['address'][0];

        return response()->view('templates.' . $template, [
            'site' => $site,
            'template' => $template,
            'isPreview' => false,
            'isTemplateDemo' => true,
            'sections' => $sections,
            'facts' => $facts,
            'photos' => collect(),
            'demoHeroImage' => $cfg['hero_image'],
            'showPreviewPlaceholder' => false,
            'seoTitle' => "{$cfg['business_name']} — {$cfg['city']} ({$template} template demo)",
            'seoDescription' => $cfg['subheadline'],
            'jsonLd' => [
                '@context' => 'https://schema.org',
                '@type' => 'LocalBusiness',
                'name' => $cfg['business_name'],
                'telephone' => $facts['phone'][0],
                'address' => $facts['address'][0],
            ],
            'whatsappUrl' => $whatsappUrl,
            'openStatus' => $openStatus,
            'mapEmbedUrl' => 'https://maps.google.com/maps?q=' . rawurlencode($mapQuery) . '&t=&z=15&ie=UTF8&iwloc=&output=embed',
            'mapDirectionsUrl' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($mapQuery),
            'priceItems' => $cfg['price_items'],
            'availableLocales' => ['en' => 'English'],
            'activeLocale' => 'en',
            'accentColor' => $cfg['accent'],
            'fontPairing' => $cfg['font'],
            'socialLinks' => [
                ['platform' => 'instagram', 'label' => 'Instagram', 'url' => 'https://instagram.com/'],
                ['platform' => 'facebook', 'label' => 'Facebook', 'url' => 'https://facebook.com/'],
                ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://linkedin.com/'],
                ['platform' => 'youtube', 'label' => 'YouTube', 'url' => 'https://youtube.com/'],
            ],
        ]);
    }
}
