<?php
namespace App\Traits;

use App\Models\NotificationMessage;

trait HasNotificationMessage
{
    /**
     * Render email template from DB with replacements
     */
    public function renderNotificationMessage(string $key, array $vars = [], ?string $lang = null): ?NotificationMessage
    {
        $lang = $lang ?? app()->getLocale();
        $template = NotificationMessage::where('key', $key)->first();
        if(!$template || $template->is_default){
            return null;
        }
        $subject_en = $template?->subject['en'] ;
        $subject_ar = $template?->subject['ar'] ;
        $body_en = $template?->body['en'] ;
        $body_ar = $template?->body['ar'] ;

        // استبدال المتغيرات مثل {{name}} , {{competition}}
        foreach ($vars as $k => $v) {
            $body_en = str_replace('{{'.$k.'}}', $v, $body_en);
            $body_ar = str_replace('{{'.$k.'}}', $v, $body_ar);
            $subject_en = str_replace('{{'.$k.'}}', $v, $subject_en);
            $subject_ar = str_replace('{{'.$k.'}}', $v, $subject_ar);
        }

        if($template){
            $template->subject = [
                'en' => $subject_en,
                'ar' => $subject_ar,
            ];
            $template->body = [
                'en' => $body_en,
                'ar' => $body_ar,
            ];
            return $template;
        } else {
            return null;
        }
    }
}