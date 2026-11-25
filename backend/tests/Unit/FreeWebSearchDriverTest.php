<?php

namespace Tests\Unit;

use App\Services\Search\FreeWebSearchDriver;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FreeWebSearchDriverTest extends TestCase
{
    public function test_returns_candidates_from_nominatim_and_duckduckgo(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'osm_type' => 'node',
                    'osm_id' => 12345,
                    'name' => 'Sydney Opera House',
                    'display_name' => 'Sydney Opera House, Bennelong Point, Sydney, Australia',
                    'extratags' => [
                        'phone' => '+61 2 9250 7111',
                        'website' => 'https://www.sydneyoperahouse.com',
                    ],
                ],
            ], 200),
            'api.duckduckgo.com/*' => Http::response([
                'Heading' => 'Sydney Opera House',
                'AbstractURL' => 'https://en.wikipedia.org/wiki/Sydney_Opera_House',
                'Results' => [],
            ], 200),
        ]);

        $driver = new FreeWebSearchDriver();
        $this->assertTrue($driver->isConfigured());

        $results = $driver->search('Sydney Opera House', 'Sydney');
        $this->assertCount(2, $results);
        $this->assertSame('Sydney Opera House', $results[0]['name']);
        $this->assertSame('+61 2 9250 7111', $results[0]['phone']);
        $this->assertSame('https://www.sydneyoperahouse.com', $results[0]['website']);
        $this->assertSame('https://www.openstreetmap.org/node/12345', $results[0]['source_url']);
    }
}
