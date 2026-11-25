<?php

namespace Tests\Feature;

use Tests\TestCase;

class TemplateDemoTest extends TestCase
{
    public function test_public_live_template_demos_render_for_clean_bold_and_warm(): void
    {
        $this->get('/templates/clean')
            ->assertOk()
            ->assertSee('Apex Dental Studio')
            ->assertSee('Pune');

        $this->get('/templates/bold')
            ->assertOk()
            ->assertSee('Forge & Iron Athletics')
            ->assertSee('Bengaluru');

        $this->get('/templates/warm')
            ->assertOk()
            ->assertSee('Sunrise Artisan Bakery')
            ->assertSee('Mumbai');
    }

    public function test_unknown_template_demo_returns_404(): void
    {
        $this->get('/templates/nonexistent')->assertNotFound();
    }
}
