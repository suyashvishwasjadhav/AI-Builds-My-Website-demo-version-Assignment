<?php

namespace App\Services;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Models\Source;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

class Reader
{
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

        return $this->markFailed($source, 'This source type is not supported by the file reader yet.');
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
