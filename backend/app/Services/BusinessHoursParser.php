<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Throwable;

class BusinessHoursParser
{
    /**
     * Day name mapping to ISO-8601 day of week (1 = Mon .. 7 = Sun).
     *
     * @var array<string, int>
     */
    private const DAY_MAP = [
        'mon' => 1, 'monday' => 1,
        'tue' => 2, 'tues' => 2, 'tuesday' => 2,
        'wed' => 3, 'wednesday' => 3,
        'thu' => 4, 'thur' => 4, 'thurs' => 4, 'thursday' => 4,
        'fri' => 5, 'friday' => 5,
        'sat' => 6, 'saturday' => 6,
        'sun' => 7, 'sunday' => 7,
    ];

    private const SHORT_DAYS = [
        1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun',
    ];

    /**
     * Parse confirmed hours text and evaluate live open/closed status.
     *
     * @return array{is_open: bool, label: string}|null
     */
    public function evaluate(?string $hoursText, string $timezone = 'Asia/Kolkata', ?DateTimeInterface $now = null): ?array
    {
        $raw = trim((string) $hoursText);
        if ($raw === '') {
            return null;
        }

        try {
            $tz = in_array($timezone, timezone_identifiers_list(), true) ? $timezone : 'Asia/Kolkata';
            $current = $now !== null
                ? CarbonImmutable::instance($now)->setTimezone($tz)
                : CarbonImmutable::now($tz);
        } catch (Throwable) {
            return null;
        }

        if (preg_match('/\b(24\s*\/?\s*7|24\s*hours?)\b/i', $raw)) {
            return [
                'is_open' => true,
                'label' => 'Open 24 Hours',
            ];
        }

        $schedule = $this->parseSchedule($raw);
        if ($schedule === []) {
            return null;
        }

        $todayIso = $current->dayOfWeekIso;
        $nowMinutes = ($current->hour * 60) + $current->minute;

        $todayWindow = $schedule[$todayIso] ?? null;

        if ($todayWindow !== null) {
            [$openMin, $closeMin] = $todayWindow;
            // Handle overnight closing (e.g. 6:00 PM - 2:00 AM)
            $effectiveClose = $closeMin <= $openMin ? $closeMin + 1440 : $closeMin;

            if ($nowMinutes >= $openMin && $nowMinutes < $effectiveClose) {
                return [
                    'is_open' => true,
                    'label' => 'Open Now · Closes at ' . $this->formatMinutes($closeMin),
                ];
            }

            if ($nowMinutes < $openMin) {
                return [
                    'is_open' => false,
                    'label' => 'Closed · Opens at ' . $this->formatMinutes($openMin),
                ];
            }
        }

        // Find next open day within the next 7 days
        for ($offset = 1; $offset <= 7; $offset++) {
            $nextIso = (($todayIso - 1 + $offset) % 7) + 1;
            if (isset($schedule[$nextIso])) {
                [$nextOpenMin] = $schedule[$nextIso];
                $dayPrefix = $offset === 1 ? 'tomorrow' : self::SHORT_DAYS[$nextIso];

                return [
                    'is_open' => false,
                    'label' => "Closed · Opens {$dayPrefix} at " . $this->formatMinutes($nextOpenMin),
                ];
            }
        }

        return [
            'is_open' => false,
            'label' => 'Closed',
        ];
    }

    /**
     * @return array<int, array{0: int, 1: int}|null>
     */
    private function parseSchedule(string $raw): array
    {
        $segments = preg_split('/\s*[,;\n]+\s*/', $raw) ?: [];
        $schedule = [];

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            $days = $this->extractDays($segment);
            if ($days === []) {
                // If no explicit day prefix (e.g., "9:00 AM - 8:00 PM"), apply to all 7 days only if it's the sole segment
                if (count($segments) === 1) {
                    $days = [1, 2, 3, 4, 5, 6, 7];
                } else {
                    continue;
                }
            }

            if (preg_match('/\bclosed\b/i', $segment)) {
                foreach ($days as $day) {
                    $schedule[$day] = null;
                }
                continue;
            }

            $window = $this->extractTimeWindow($segment);
            if ($window === null) {
                continue;
            }

            foreach ($days as $day) {
                $schedule[$day] = $window;
            }
        }

        return array_filter($schedule, fn ($w) => $w !== null || array_key_exists(1, $schedule));
    }

    /**
     * @return list<int>
     */
    private function extractDays(string $segment): array
    {
        if (preg_match('/\b(daily|every\s*day|all\s*days)\b/i', $segment)) {
            return [1, 2, 3, 4, 5, 6, 7];
        }

        $dayPattern = 'mon(?:day)?|tue(?:s|sday)?|wed(?:nesday)?|thu(?:r|rs|rsday)?|fri(?:day)?|sat(?:urday)?|sun(?:day)?';

        // Range e.g. Mon-Sat or Monday to Friday
        if (preg_match("/\b({$dayPattern})\s*(?:-|–|—|to)\s*({$dayPattern})\b/i", $segment, $m)) {
            $start = self::DAY_MAP[strtolower($m[1])] ?? null;
            $end = self::DAY_MAP[strtolower($m[2])] ?? null;
            if ($start !== null && $end !== null) {
                $days = [];
                $cursor = $start;
                for ($i = 0; $i < 7; $i++) {
                    $days[] = $cursor;
                    if ($cursor === $end) {
                        break;
                    }
                    $cursor = ($cursor % 7) + 1;
                }

                return $days;
            }
        }

        // Single day e.g. Sat 10am-4pm
        if (preg_match_all("/\b({$dayPattern})\b/i", $segment, $matches)) {
            $days = [];
            foreach ($matches[1] as $name) {
                $iso = self::DAY_MAP[strtolower($name)] ?? null;
                if ($iso !== null) {
                    $days[] = $iso;
                }
            }

            return array_values(array_unique($days));
        }

        return [];
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function extractTimeWindow(string $segment): ?array
    {
        $timeRegex = '(\d{1,2}(?::\d{2})?\s*(?:am|pm)?)';
        if (! preg_match("/{$timeRegex}\s*(?:-|–|—|to)\s*{$timeRegex}/i", $segment, $m)) {
            return null;
        }

        $startMin = $this->parseTimeToMinutes($m[1]);
        $endMin = $this->parseTimeToMinutes($m[2]);

        if ($startMin === null || $endMin === null) {
            return null;
        }

        return [$startMin, $endMin];
    }

    private function parseTimeToMinutes(string $token): ?int
    {
        $token = strtolower(trim($token));
        if (! preg_match('/^(\d{1,2})(?::(\d{2}))?\s*(am|pm)?$/', $token, $m)) {
            return null;
        }

        $hour = (int) $m[1];
        $minute = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;
        $meridiem = $m[3] ?? '';

        if ($minute < 0 || $minute > 59) {
            return null;
        }

        if ($meridiem === 'am') {
            if ($hour === 12) {
                $hour = 0;
            }
        } elseif ($meridiem === 'pm') {
            if ($hour < 12) {
                $hour += 12;
            }
        }

        if ($hour < 0 || $hour > 23) {
            return null;
        }

        return ($hour * 60) + $minute;
    }

    private function formatMinutes(int $totalMinutes): string
    {
        $normalized = (($totalMinutes % 1440) + 1440) % 1440;
        $hour24 = intdiv($normalized, 60);
        $minute = $normalized % 60;
        $suffix = $hour24 >= 12 ? 'PM' : 'AM';
        $hour12 = $hour24 % 12;
        if ($hour12 === 0) {
            $hour12 = 12;
        }

        return sprintf('%d:%02d %s', $hour12, $minute, $suffix);
    }
}
