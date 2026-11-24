<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->social_links)) {
            $filtered = array_values(array_filter(
                $this->social_links,
                fn ($link) => is_string($link) && trim($link) !== ''
            ));

            $this->merge(['social_links' => $filtered]);
        }

        if (is_string($this->google_business_url) && trim($this->google_business_url) === '') {
            $this->merge(['google_business_url' => null]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'google_business_url' => ['nullable', 'url:http,https', 'max:2048'],
            'social_links' => ['nullable', 'array', 'max:10'],
            'social_links.*' => ['required', 'url:http,https', 'max:2048'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'mimes:pdf,csv,txt,xlsx,xls,jpg,jpeg,png,webp', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'google_business_url.url' => 'The Google Business link must be a valid http or https URL.',
            'social_links.*.url' => 'Each social or website link must be a valid http or https URL.',
            'files.*.mimes' => 'Uploaded files must be PDF, CSV, XLSX, XLS, JPG, PNG, or WEBP.',
            'files.*.max' => 'Each uploaded file must not exceed 10 MB.',
        ];
    }
}
