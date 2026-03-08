<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->getTitle(),
            'body' => $this->getBody(),
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }

    protected function getTitle(): ?string
    {
        // Handle session_scheduled notifications
        if (isset($this->data['type']) && $this->data['type'] === 'session_scheduled') {
            $title = $this->data['title'] ?? null;
            if ($title) {
                return is_string($title) ? $title : null;
            }
            // Fallback to message if title is not available
            $message = $this->data['message'] ?? null;
            return is_string($message) ? $message : null;
        }

        if (isset($this->data['message'])) {
            // Check if message is a translation key (for old notifications)
            $message = $this->data['message'];
            if ($message === 'reset_password') {
                return __('passwords.reset_password');
            }
            
            // Only use program_application translation if message looks like a key (no spaces, simple format)
            // Otherwise, treat it as already translated text
            if (is_string($message) && !preg_match('/\s/', $message) && strlen($message) < 50) {
                $translated = __('program_application.' . $message);
                // If translation returns the key itself (not found), return the message as is
                if ($translated === 'program_application.' . $message) {
                    return $message;
                }
                return $translated;
            }
            
            // Message is already translated text, return as is
            return $message;
        }

        $title = $this->data['title'] ?? null;

        if (is_array($title)) {
            return $title[app()->getLocale()] ?? $title['en'] ?? $title['ar'] ?? null;
        }

        return $title;
    }

    protected function getBody(): ?string
    {
        if (isset($this->data['message'])) {
            return null;
        }

        /*$body = $this->data['body'] ?? null;

        if (is_array($body)) {
            return $body[app()->getLocale()] ?? $body['en'] ?? $body['ar'] ?? null;
        }

        return $body;*/
        $body = $this->data['body'] ?? null;

        if (is_array($body)) {
            $locale = app()->getLocale();
            $text = $body[$locale] ?? $body['en'] ?? $body['ar'] ?? null;
            return $text ? strip_tags($text) : null;
        }

        return $body ? strip_tags($body) : null;
    }
}
