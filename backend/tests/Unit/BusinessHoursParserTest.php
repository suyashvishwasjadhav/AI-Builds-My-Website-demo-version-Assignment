<?php

namespace Tests\Unit;

use App\Services\BusinessHoursParser;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class BusinessHoursParserTest extends TestCase
{
    public function test_evaluates_open_now_and_closed_states(): void
    {
        $parser = new BusinessHoursParser();

        // Tuesday 11:00 AM IST -> open until 8:00 PM
        $tuesdayMorning = CarbonImmutable::create(2026, 11, 24, 11, 0, 0, 'Asia/Kolkata');
        $openResult = $parser->evaluate('Mon-Sat 9:00 AM - 8:00 PM', 'Asia/Kolkata', $tuesdayMorning);

        $this->assertNotNull($openResult);
        $this->assertTrue($openResult['is_open']);
        $this->assertSame('Open Now · Closes at 8:00 PM', $openResult['label']);

        // Tuesday 9:30 PM IST -> closed, opens tomorrow at 9:00 AM
        $tuesdayNight = CarbonImmutable::create(2026, 11, 24, 21, 30, 0, 'Asia/Kolkata');
        $closedResult = $parser->evaluate('Mon-Sat 9:00 AM - 8:00 PM', 'Asia/Kolkata', $tuesdayNight);

        $this->assertNotNull($closedResult);
        $this->assertFalse($closedResult['is_open']);
        $this->assertSame('Closed · Opens tomorrow at 9:00 AM', $closedResult['label']);
    }

    public function test_handles_sunday_closed_and_24_hours(): void
    {
        $parser = new BusinessHoursParser();

        // Sunday 12:00 PM IST -> closed, opens Mon at 9:00 AM
        $sunday = CarbonImmutable::create(2026, 11, 22, 12, 0, 0, 'Asia/Kolkata');
        $sundayResult = $parser->evaluate('Mon-Sat 9am-6pm, Sun Closed', 'Asia/Kolkata', $sunday);

        $this->assertNotNull($sundayResult);
        $this->assertFalse($sundayResult['is_open']);
        $this->assertSame('Closed · Opens tomorrow at 9:00 AM', $sundayResult['label']);

        $alwaysOpen = $parser->evaluate('Open 24 hours', 'Asia/Kolkata', $sunday);
        $this->assertNotNull($alwaysOpen);
        $this->assertTrue($alwaysOpen['is_open']);
        $this->assertSame('Open 24 Hours', $alwaysOpen['label']);
    }

    public function test_returns_null_when_empty_or_unparseable(): void
    {
        $parser = new BusinessHoursParser();

        $this->assertNull($parser->evaluate(null));
        $this->assertNull($parser->evaluate(''));
        $this->assertNull($parser->evaluate('By appointment only'));
    }
}
