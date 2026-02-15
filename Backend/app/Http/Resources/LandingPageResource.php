<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LandingPageResource extends JsonResource
{
    protected $lang;

    public function __construct($resource)
{
    parent::__construct($resource);
    $this->lang = request()->getPreferredLanguage(['en', 'ar']);
}
    public function toArray($request)
    {
        $data = $this->content ?? []; // JSON من DB
        // Add Branding info
        $branding = \App\Models\BrandingSetting::first();
        $result = [
            'data' => [],
            'last_update' => $this->updated_at?->toDateTimeString(),
            'logo' => isset($branding->logo) ? Storage::url($branding->logo) : null,
            'white_logo' => isset($branding->white_logo) ? Storage::url($branding->white_logo) : null,
            'government_verification_banner_enabled' => $this->government_verification_banner_enabled ?? false,
            'dga_registration_number' => $this->dga_registration_number ?? null,
            'dga_certificate_url' => $this->dga_certificate_url ?? null,
        ];


    foreach ($data as $block) {
        switch ($block['type']) {
            case 'banner':
                $result['data'][] = [
                    'type' => 'banner',
                        'data' => collect($block['data']['items'] ?? [])->map(fn ($item) => [
                        'title'       => $item['title'][$this->lang] ?? '',
                        'description' => $item['text'][$this->lang] ?? '',
                        'image'       => isset($item['image'][$this->lang])
                            ? Storage::url($item['image'][$this->lang])
                            : null,
                            'main_action' => (!empty($item['main_action']['title'][$this->lang] ?? null) 
                                            || !empty($item['main_action']['url'][$this->lang] ?? null))
                                ? array_filter([
                                    'title' => $item['main_action']['title'][$this->lang] ?? null,
                                    'link'  => $item['main_action']['url'][$this->lang] ?? null,
                                ])
                                : null,
                ])->toArray()
                ];
                break;

            case 'about':
                $result['data'][] = [
                    'type' => 'about',
                    'data' => [
                        'title' => $block['data']['title'][$this->lang] ?? '',
                        'description' => $block['data']['text'][$this->lang] ?? '',
                        'main_action' => (!empty($block['data']['main_action']['title'][$this->lang] ?? null) 
                                            || !empty($block['data']['main_action']['url'][$this->lang] ?? null))
                                ? array_filter([
                                    'title' => $block['data']['main_action']['title'][$this->lang] ?? null,
                                    'link'  => $block['data']['main_action']['url'][$this->lang] ?? null,
                                ])
                                : null,
                        'list' => collect($block['data']['list'] ?? [])->map(fn ($item) => [
                            'title'  => $item['title'][$this->lang] ?? '',
                            'icon'   => isset($item['icon']) ? Storage::url($item['icon']) : null,
                            'number' => $item['number'] ?? '',
                        ])->toArray(),
                    ]
                ];
                break;

            case 'services':
                $result['data'][] = [
                    'type' => 'services',
                    'data' => [
                        'title' => $block['data']['title'][$this->lang] ?? '',
                        'description' => $block['data']['text'][$this->lang] ?? '',
                        'list' => collect($block['data']['services'] ?? [])->map(fn ($item) => [
                            'title' => $item['title'][$this->lang] ?? '',
                            'description' => $item['description'][$this->lang] ?? '',
                            'tags' => $item['tags'][$this->lang] ?? [],
                            'main_action' => (!empty($item['main_action']['title'][$this->lang] ?? null) 
                                            || !empty($item['main_action']['url'][$this->lang] ?? null))
                                ? array_filter([
                                    'title' => $item['main_action']['title'][$this->lang] ?? null,
                                    'link'  => $item['main_action']['url'][$this->lang] ?? null,
                                ])
                                : null,

                            'secondary_action' => (!empty($item['secondary_action']['title'][$this->lang] ?? null) 
                                            || !empty($item['secondary_action']['url'][$this->lang] ?? null))
                                ? array_filter([
                                    'title' => $item['secondary_action']['title'][$this->lang] ?? null,
                                    'link'  => $item['secondary_action']['url'][$this->lang] ?? null,
                                ])
                                : null,
                            'icon' => isset($item['icon']) ? Storage::url($item['icon']) : null,
                        ])->toArray(),
                    ]
                ];
                break;

            case 'partners':
                $result['data'][] = [
                    'type' => 'partners',
                    'data' => [
                        'title' => $block['data']['title'][$this->lang] ?? '',
                        'logos' => collect($block['data']['logos'] ?? [])->map(fn ($logo) => [
                            'image' => Storage::url($logo['image']),
                            'title' => $logo['title'][$this->lang] ?? '',
                        ])->toArray(),
                    ]
                ];
                break;
        }
    }
        

        
        return $result;
    }
}
