<?php

namespace App\Traits;

use App\Models\EmailTemplate;
use Illuminate\Support\HtmlString;

trait HasEmailTemplate
{
    /**
     * Render email template from DB with replacements
     */
    public function renderEmailTemplate(string $key, array $vars = [], ?string $lang = null): array
    {
        $lang = $lang ?? app()->getLocale();
        $template = EmailTemplate::where('key', $key)->first();
        if(!$template || $template->is_default){
            return [];
        }
        $subject = $template?->subject[$lang] ;
        $body    = $template?->body[$lang];

        // استبدال المتغيرات مثل {{name}} , {{otpCode}}
        foreach ($vars as $k => $v) {
            $body = str_replace('{{'.$k.'}}', $v, $body);
            $subject = str_replace('{{'.$k.'}}', $v, $subject);
        }

        return [
            'subject' => $subject,
            'body'    => !empty($body) ? new HtmlString($body) : null,
        ];
    }
}
