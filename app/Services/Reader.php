<?php

namespace App\Services;

use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Models\Question;
use App\Models\Source;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

class Reader
{
    private const MAX_RESPONSE_BYTES = 2_097_152; // 2 MB

    public function __construct(
        protected ?UrlGuard $urlGuard = null,
    ) {
        $this->urlGuard ??= new UrlGuard();
    }

    public function read(Source $source): Source
    {
        if ($source->type === SourceType::User) {
            $text = trim((string) $source->raw_text);
            if ($text === '') {
                return $this->markFailed($source, 'No text was provided.');
            }

            return $this->markRead($source, $text);
        }

        if ($source->type === SourceType::File) {
            return $this->readFile($source);
        }

        if ($source->type === SourceType::Link) {
            return $this->readLink($source);
        }

        return $this->markFailed($source, 'This source type is not supported by the reader.');
    }

    protected function readFile(Source $source): Source
    {
        $relativePath = (string) $source->path_or_url;

        if ($relativePath === '' || ! Storage::disk('local')->exists($relativePath)) {
            return $this->markFailed($source, 'The uploaded file could not be found. Please upload another copy.');
        }

        $fullPath = Storage::disk('local')->path($relativePath);
        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => $this->readPdf($source, $fullPath),
            'csv', 'xlsx', 'xls' => $this->readSpreadsheet($source, $fullPath),
            'txt' => $this->readPlainText($source, $fullPath),
            'jpg', 'jpeg', 'png', 'webp' => $this->transcribeWithGemini($source, $fullPath, $this->imageMimeType($extension)),
            default => $this->markFailed($source, 'Unsupported file type. Please upload a PDF, CSV, XLSX, or image.'),
        };
    }

    protected function readPdf(Source $source, string $fullPath): Source
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($fullPath);
            $text = trim($pdf->getText());

            if ($text !== '') {
                return $this->markRead($source, $text);
            }
        } catch (Throwable $e) {
            Log::warning('PDF parsing failed', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);

            return $this->markFailed($source, 'We could not read this PDF file. It may be corrupted—please upload another copy.');
        }

        // Scanned PDF with no embedded text layer: fall back to Gemini transcription.
        return $this->transcribeWithGemini($source, $fullPath, 'application/pdf');
    }

    protected function readSpreadsheet(Source $source, string $fullPath): Source
    {
        try {
            $spreadsheet = IOFactory::load($fullPath);
            $lines = [];

            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $sheetTitle = trim($worksheet->getTitle());
                $sheetLines = [];

                foreach ($worksheet->getRowIterator() as $row) {
                    $cells = [];
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);

                    foreach ($cellIterator as $cell) {
                        $value = trim((string) $cell->getFormattedValue());
                        if ($value !== '') {
                            $cells[] = $value;
                        }
                    }

                    if ($cells !== []) {
                        $sheetLines[] = implode(' | ', $cells);
                    }
                }

                if ($sheetLines !== []) {
                    if ($spreadsheet->getSheetCount() > 1 && $sheetTitle !== '') {
                        $lines[] = "Sheet: {$sheetTitle}";
                    }
                    $lines = array_merge($lines, $sheetLines);
                }
            }

            $text = trim(implode("\n", $lines));

            if ($text === '') {
                return $this->markFailed($source, 'The spreadsheet appears to be empty.');
            }

            return $this->markRead($source, $text);
        } catch (Throwable $e) {
            Log::warning('Spreadsheet parsing failed', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);

            return $this->markFailed($source, 'We could not read this spreadsheet. Please check the file and upload another copy.');
        }
    }

    protected function readPlainText(Source $source, string $fullPath): Source
    {
        $contents = @file_get_contents($fullPath);

        if ($contents === false || trim($contents) === '') {
            return $this->markFailed($source, 'The text file appears to be empty.');
        }

        return $this->markRead($source, trim($contents));
    }

    protected function transcribeWithGemini(Source $source, string $fullPath, string $mimeType): Source
    {
        $apiKey = (string) config('services.gemini.api_key');
        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');

        if ($apiKey === '') {
            return $this->markFailed($source, 'AI transcription is not configured yet. Please paste the text or configure GEMINI_API_KEY.');
        }

        $bytes = @file_get_contents($fullPath);
        if ($bytes === false || $bytes === '') {
            return $this->markFailed($source, 'We could not read this file. Please upload another copy.');
        }

        $promptPath = resource_path('prompts/transcribe_image.txt');
        $prompt = file_exists($promptPath)
            ? trim((string) file_get_contents($promptPath))
            : 'Transcribe the visible text exactly. Do not add, summarize or guess anything. If unreadable, say UNREADABLE.';

        try {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(30)->post($endpoint, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => base64_encode($bytes),
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            if (! $response->successful()) {
                Log::warning('Gemini transcription HTTP error', [
                    'source_id' => $source->id,
                    'status' => $response->status(),
                ]);

                return $this->markFailed($source, 'We could not transcribe this file right now. Please try again.');
            }

            $text = trim((string) $response->json('candidates.0.content.parts.0.text', ''));

            if ($text === '' || strtoupper($text) === 'UNREADABLE') {
                return $this->markFailed($source, 'The text in this file was unreadable. Please upload a clearer copy.');
            }

            return $this->markRead($source, $text);
        } catch (Throwable $e) {
            Log::warning('Gemini transcription exception', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);

            return $this->markFailed($source, 'We could not transcribe this file right now. Please try again.');
        }
    }

    protected function readLink(Source $source): Source
    {
        $url = trim((string) $source->path_or_url);

        if ($error = $this->urlGuard->validate($url)) {
            return $this->markFailed($source, $error);
        }

        try {
            $response = Http::timeout(10)
                ->withUserAgent('Mozilla/5.0 (compatible; WebsiteBuilderBot/1.0)')
                ->withOptions(['max_redirects' => 5])
                ->get($url);
        } catch (ConnectionException $e) {
            Log::warning('Link fetch timed out or failed to connect', [
                'source_id' => $source->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return $this->markFailed($source, 'This link timed out or could not be reached. Please try again or paste the details.');
        } catch (Throwable $e) {
            Log::warning('Link fetch failed', [
                'source_id' => $source->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return $this->markFailed($source, 'We could not fetch this link right now. Please try again.');
        }

        $status = $response->status();

        if (in_array($status, [401, 403, 429], true)) {
            return $this->markBlocked($source, $url);
        }

        if (! $response->successful()) {
            return $this->markFailed($source, "We could not read this link (HTTP {$status}). Please check the URL or paste the details.");
        }

        $body = $response->body();
        if (strlen($body) > self::MAX_RESPONSE_BYTES) {
            $body = substr($body, 0, self::MAX_RESPONSE_BYTES);
        }

        $parsed = $this->parseHtml($body);
        $visibleText = $parsed['text'];

        if ($this->looksLikeLoginWallOrBlocked($url, $parsed['title'], $visibleText)) {
            return $this->markBlocked($source, $url);
        }

        if ($visibleText === '' && $parsed['title'] === '' && $parsed['description'] === '') {
            return $this->markBlocked($source, $url);
        }

        $parts = [];
        if ($parsed['title'] !== '') {
            $parts[] = "Title: {$parsed['title']}";
        }
        if ($parsed['description'] !== '') {
            $parts[] = "Description: {$parsed['description']}";
        }
        if ($visibleText !== '') {
            $parts[] = "Page text:\n{$visibleText}";
        }

        return $this->markRead($source, implode("\n\n", $parts));
    }

    /**
     * @return array{title: string, description: string, text: string}
     */
    protected function parseHtml(string $html): array
    {
        $crawler = new Crawler($html);

        $title = '';
        $titleNode = $crawler->filter('title');
        if ($titleNode->count() > 0) {
            $title = $this->normalizeWhitespace($titleNode->first()->text(''));
        }

        $description = '';
        $metaNode = $crawler->filter('meta[name="description"]');
        if ($metaNode->count() > 0) {
            $description = $this->normalizeWhitespace((string) $metaNode->first()->attr('content'));
        }

        $crawler->filter('script, style, noscript, svg')->each(function (Crawler $node): void {
            foreach ($node as $domNode) {
                $domNode->parentNode?->removeChild($domNode);
            }
        });

        $bodyNode = $crawler->filter('body');
        $rawBody = $bodyNode->count() > 0
            ? $bodyNode->first()->text('')
            : $crawler->text('');

        $text = $this->normalizeWhitespace($rawBody);

        return [
            'title' => $title,
            'description' => $description,
            'text' => $text,
        ];
    }

    protected function looksLikeLoginWallOrBlocked(string $url, string $title, string $text): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $combined = strtolower(trim($title.' '.$text));
        $length = strlen($text);

        $isSocialHost = str_contains($host, 'facebook.com')
            || str_contains($host, 'fb.com')
            || str_contains($host, 'instagram.com')
            || str_contains($host, 'linkedin.com');

        $loginPhrases = ['log in', 'login', 'sign in', 'create new account', 'you must log in', 'enable javascript'];

        if ($isSocialHost) {
            if ($length < 120) {
                return true;
            }

            foreach ($loginPhrases as $phrase) {
                if (str_contains($combined, $phrase) && $length < 500) {
                    return true;
                }
            }
        }

        if ($length < 80) {
            foreach ($loginPhrases as $phrase) {
                if (str_contains($combined, $phrase)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function markBlocked(Source $source, string $url): Source
    {
        $message = "We couldn't read this link. Please paste the details or upload a screenshot.";

        $source->update([
            'status' => SourceStatus::Blocked,
            'error_message' => $message,
        ]);

        $prompt = "We couldn't read this link ({$url}). Please paste the details or upload a screenshot.";

        Question::firstOrCreate(
            [
                'site_id' => $source->site_id,
                'type' => QuestionType::MissingInfo,
                'prompt' => $prompt,
                'status' => QuestionStatus::Open,
            ],
            [
                'options' => [],
            ]
        );

        return $source->fresh();
    }

    private function normalizeWhitespace(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    protected function markRead(Source $source, string $rawText): Source
    {
        $source->update([
            'status' => SourceStatus::Read,
            'raw_text' => $rawText,
            'error_message' => null,
        ]);

        return $source->fresh();
    }

    protected function markFailed(Source $source, string $message): Source
    {
        $source->update([
            'status' => SourceStatus::Failed,
            'error_message' => $message,
        ]);

        return $source->fresh();
    }

    private function imageMimeType(string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
