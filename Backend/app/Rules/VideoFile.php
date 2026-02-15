<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class GuidelineFile implements ValidationRule
{
    protected string $fileType;

    public function __construct(string $fileType = 'video')
    {
        $this->fileType = $fileType;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            $fail('The file must be a valid file / يجب أن يكون الملف صحيحاً');
            return;
        }

        // Check file size (25MB max)
        if ($value->getSize() > 25 * 1024 * 1024) {
            $fail('The file size must not exceed 25MB / حجم الملف يجب ألا يتجاوز 25 ميجابايت');
            return;
        }

        // Define allowed file types based on the file type
        $allowedConfig = $this->getAllowedConfig();
        
        // Check MIME type
        if (!in_array($value->getMimeType(), $allowedConfig['mimes'])) {
            $fail($allowedConfig['mime_error']);
            return;
        }

        // Check file extension
        $extension = strtolower($value->getClientOriginalExtension());
        if (!in_array($extension, $allowedConfig['extensions'])) {
            $fail($allowedConfig['extension_error']);
            return;
        }
    }

    private function getAllowedConfig(): array
    {
        return match($this->fileType) {
            'video' => [
                'mimes' => ['video/mp4', 'video/mov', 'video/avi', 'video/quicktime'],
                'extensions' => ['mp4', 'mov', 'avi'],
                'mime_error' => 'The file must be a video file (MP4, MOV, AVI) / يجب أن يكون الملف فيديو (MP4، MOV، AVI)',
                'extension_error' => 'The file must have a valid video extension (MP4, MOV, AVI) / يجب أن يكون للملف امتداد فيديو صحيح (MP4، MOV، AVI)',
            ],
            'document' => [
                'mimes' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                'extensions' => ['pdf', 'doc', 'docx'],
                'mime_error' => 'The file must be a document (PDF, DOC, DOCX) / يجب أن يكون الملف مستند (PDF، DOC، DOCX)',
                'extension_error' => 'The file must have a valid document extension (PDF, DOC, DOCX) / يجب أن يكون للملف امتداد مستند صحيح (PDF، DOC، DOCX)',
            ],
            'image' => [
                'mimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                'mime_error' => 'The file must be an image (JPEG, PNG, GIF, WebP) / يجب أن يكون الملف صورة (JPEG، PNG، GIF، WebP)',
                'extension_error' => 'The file must have a valid image extension (JPEG, PNG, GIF, WebP) / يجب أن يكون للملف امتداد صورة صحيح (JPEG، PNG، GIF، WebP)',
            ],
            default => [
                'mimes' => ['video/mp4', 'video/mov', 'video/avi', 'video/quicktime', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                'extensions' => ['mp4', 'mov', 'avi', 'pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'webp'],
                'mime_error' => 'The file must be a valid file (Video, Document, Image) / يجب أن يكون الملف صحيحاً (فيديو، مستند، صورة)',
                'extension_error' => 'The file must have a valid extension / يجب أن يكون للملف امتداد صحيح',
            ],
        };
    }
}
