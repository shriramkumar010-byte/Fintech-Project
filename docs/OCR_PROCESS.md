# OCR & Document Processing — Implementation Guide

Ye document OCR integration aur document-processing pipeline ke liye step-by-step details provide karta hai.

## Goal
Automatically extract structured data (PAN, Aadhaar, name, DOB, amounts, etc.) from uploaded documents (images, scanned PDFs, text PDFs, docx) and attach/update models (`LoanApplication`, `CibilReport`).

## Recommended Packages
- `thiagoalessio/tesseract_ocr` — Tesseract PHP wrapper (offline OCR for images).
- `smalot/pdfparser` — extract text from text-based PDFs quickly.
- `imagick` (PHP extension) — render scanned PDF pages to images for OCR.
- `phpoffice/phpword` — read `.docx` files.
- `spatie/laravel-medialibrary` — already used for uploads and media handling.
- (Optional paid) Google Cloud Vision / Document AI, AWS Textract, Azure Form Recognizer — higher accuracy, structured output.

## High-level Workflow
1. User uploads a document via the app (handled by Spatie MediaLibrary on a model).
2. On `mediaAdded` (or after upload in controller), dispatch a queued job `ProcessDocument` with the Media record id.
3. `ProcessDocument` determines file type and extracts text using the appropriate tool:
   - PDF: try `smalot/pdfparser` first; if text is empty (scanned), use Imagick to render pages and run Tesseract on each page.
   - Image: run Tesseract directly.
   - DOCX: use PHPWord to extract text.
4. Run parsing/heuristics over extracted text (regex + normalization) to find PAN, Aadhaar, DOB, numbers, amounts, names.
5. Find or create related records (e.g. locate `CibilReport` by `pan_number`) and update the `LoanApplication` or the `CibilReport` fields with extracted values (mark them as `extracted` for review).
6. Store raw extracted text and processing metadata (`confidence`, `processor`, `timestamps`) for audit and manual review.

## Job Skeleton (example)
```php
class ProcessDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected \Spatie\MediaLibrary\MediaCollections\Models\Media $media) {}

    public function handle()
    {
        $path = $this->media->getPath();
        $mime = $this->media->mime_type;
        $text = '';

        if (str_contains($mime, 'pdf')) {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($path);
            $text = $pdf->getText();

            if (trim($text) === '') {
                // scanned PDF -> render images and OCR
                $images = renderPdfToImages($path); // use Imagick
                foreach ($images as $img) {
                    $text .= (new \TesseractOCR($img))->run();
                }
            }
        } elseif (str_contains($mime, 'image')) {
            $text = (new \TesseractOCR($path))->run();
        } elseif (str_ends_with($this->media->file_name, '.docx')) {
            // extract using PHPWord
        }

        // Normalize text
        $normalized = normalize_text($text);

        // Extract fields
        $pan = extract_pan($normalized);
        $aadhaar = extract_aadhaar($normalized);
        $dob = extract_dob($normalized);
        $amounts = extract_amounts($normalized);

        // Update models (example)
        if ($pan) {
            $cibil = \App\Models\CibilReport::firstWhere('pan_number', $pan);
            if ($cibil) {
                // attach or update relationship
            }
        }

        // Save raw extracted text for audit
        $this->media->setCustomProperty('extracted_text', substr($normalized, 0, 10000));
        $this->media->save();
    }
}
```

## Useful Regex Patterns
- PAN: `/\b([A-Z]{5}[0-9]{4}[A-Z])\b/`
- Aadhaar: `/\b(\d{4}\s?\d{4}\s?\d{4})\b/` (normalize to digits)
- DOB: `/\b(0[1-9]|[12][0-9]|3[01])[-\/](0[1-9]|1[0-2])[-\/](19|20)\d{2}\b/`
- Amounts: `/₹?\s?[\d,]+(?:\.\d{1,2})?/`

Note: regexes should be robust with normalization (remove extra whitespace, OCR noise like `0` vs `O`, etc.).

## Rendering PDFs for OCR
- Install Imagick and GhostScript on the server.
- Use Imagick to convert each PDF page to a high-resolution PNG (300 DPI recommended) then OCR each PNG with Tesseract.
- Example fallback flow: `smalot/pdfparser` -> if no text -> Imagick render pages -> Tesseract per page.

## Cloud vs Local OCR
- Local (Tesseract): free, quick to start, no external credentials, but lower accuracy on messy documents.
- Cloud (Google/AWS/Azure): better structured output and higher accuracy, supports table/field extraction, but costs money and requires secure credentials.

## Confidence & Manual Review
- Always mark extracted values as `extracted_*` and show them in an admin review UI before accepting them into canonical fields.
- Keep the raw extracted text and a processing log (timestamp, processor used, duration, errors).

## Security & Privacy
- Secure uploads with proper access controls and storage encryption if handling PII.
- Log minimal plaintext; prefer saving hashed identifiers where appropriate; remove raw OCR results after a retention period if not needed.
- Use queues and separate worker processes to process files outside web requests.

## UI/UX Recommendations
- Show a processing status on the media item (`processing`, `completed`, `failed`).
- Allow manual "Confirm" or "Edit" of extracted fields before saving to `LoanApplication`/`CibilReport`.
- Provide a recent processing log for admins to review failed or low-confidence extracts.

## Quick Dev Commands
- Install packages (example):
```bash
composer require smalot/pdfparser phpoffice/phpword
# Imagick is a PHP extension; install via OS package manager and enable extension
``` 

- Queue worker (run locally):
```bash
php artisan queue:work --tries=3
```

## Next Steps I Can Implement
- Scaffold `ProcessDocument` job and queue wiring.
- Hook the job to `media-added` event from `spatie/laravel-medialibrary`.
- Add an admin review UI to confirm extracted fields.

---

If you want, I can implement the job scaffold now and wire it to uploads (Tesseract + PDF fallback). Which approach do you prefer: local Tesseract (free) or cloud OCR (higher accuracy)?
