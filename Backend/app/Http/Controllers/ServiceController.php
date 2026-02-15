<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
class ServiceController extends Controller
{
    /**
     * Display a listing of services.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        //$lang = $lang ?? app()->getLocale() ?? 'en';
        //$lang = request()->header('Accept-Language') ?? app()->getLocale() ?? 'en';
        $services = Service::published()
            ->ordered()
            ->select('id', 'title', 'metadata')
            ->get()
            ->map(function ($service) {
                $locale = app()->getLocale() ?? 'en';
                $service5 = json_decode($service, true);
                if(isset($service5['metadata']['description'][$locale])){
                    $description = $service5['metadata']['description'][$locale];
                }else{
                    $description = null;
                }
               
                return [
                    'id' => $service->id,
                    'title' => $service->title,
                    'description' => $description   ,
                ];
            });

        return response()->json($services);
    }

    /**
     * Display the specified service.
     *
     * @param int $request
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        // Validate the id parameter
        if (!is_numeric($id) || !Service::published()->where('id', $id)->exists()) {
            return response()->json(['error' => 'Service not found.'], 404);
        }

        $service = Service::published()->findOrFail($id);
       // logger($service);
            // Get current locale or default to 'en'
        $locale = app()->getLocale() ?? 'en';
        // Parse JSON fields
        $service5 = json_decode($service, true);
        //logger($service5);
        $description_ar = $service5['metadata']['description'][$locale];
        // Extract data based on locale
        // paymentChannels
        $paymentChannelsCollection = collect($service5['metadata']['paymentChannels'] ?? [])->map(function ($item) {
            $meta = $item['metadata']['paymentChannels'] ?? $item['paymentChannels'] ?? $item;
            $locale2 = app()->getLocale() ?? 'en';
            return [
                'logo' => !empty($meta['image']) ? Storage::url($meta['image']) : null,
                'alt' => $meta['alt'][$locale2] ?? null,
            ];
        })->filter(function ($item) {
            // Remove empty items (both logo and alt are null)
            return !empty($item['logo']) || !empty($item['alt']);
        });

        $paymentChannels = $paymentChannelsCollection->isEmpty() ? null : $paymentChannelsCollection->values()->toArray();
        $serviceData = [
            'id' => $service->id,
            'title' =>  $service->title,
            'description' => $description_ar ?? null,
            'tags' => !empty($service5['metadata']['tags'][$locale]) ? $service5['metadata']['tags'][$locale] : null,
            'startServiceLink' => $service5['metadata']['startServiceLink'][$locale] ?? null,
            'serviceLevelLink' => $service5['metadata']['serviceLevelLink'][$locale] ?? null,

            //'steps' => $service5['content']['steps'][$locale] ??  null,
            'steps' => isset($service5['content']['steps'][$locale])
    ? $this->replaceTrixVideo($service5['content']['steps'][$locale])
    : null,
    'requiredDocuments' => isset($service5['content']['requiredDocuments'][$locale])
    ? $this->replaceTrixVideo($service5['content']['requiredDocuments'][$locale])
    : null,
    'termsOfUse' => isset($service5['content']['conditions'][$locale])
    ? $this->replaceTrixVideo($service5['content']['conditions'][$locale])
    : null,
    
            //'termsOfUse' => $service5['content']['termsOfUse'][$locale] ??  null,
            //'requiredDocuments' => $service5['content']['requiredDocuments'][$locale] ??  null,
            
            'targetAudience' => $service5['metadata']['targetAudience'][$locale] ??  null,
            'serviceChannels' => $service5['metadata']['serviceChannels'][$locale] ??  null,
            'serviceDuration' => $service5['metadata']['serviceDuration'][$locale] ??  null,
            'serviceCost' => $service5['metadata']['serviceCost'][$locale] ??  null,
            'paymentChannels' => $paymentChannels ?? null,

            'FAQsLink' => $service5['metadata']['FAQsLink']['url'][$locale] ??  null,
            'phone' => $service5['metadata']['phone'] ??  null,
            'email' => $service5['metadata']['email'] ??  null,
            'userManual' => isset($service5['metadata']['userManual'][$locale]) ? Storage::url($service5['metadata']['userManual'][$locale]) : null,
            'mobileApp' => (function () use ($service5, $locale) {
                $apps = collect($service5['metadata']['mobileApp'] ?? [])->map(function ($item) use ($locale) {
                    return [
                        'logo' => !empty($item['image']) ? Storage::url($item['image']) : null,
                        'alt' => $item['alt'][$locale] ?? null,
                        'link' => $item['url'][$locale] ?? null,
                    ];
                })->filter(function ($item) {
                    // Remove empty items (all fields null or empty)
                    return !empty($item['logo']) || !empty($item['alt']) || !empty($item['link']);
                })->values()->toArray();
                return empty($apps) ? null : $apps;
            })(),
            'relatedService' => (function () use ($service5, $locale) {
                $title = $service5['relatedServices']['title'][$locale] ?? null;
                $description = $service5['relatedServices']['description'][$locale] ?? null;
                $list = collect($service5['relatedServices']['list'] ?? [])->map(function ($item) use ($locale) {
                    return [
                        'title' => $item['title'][$locale] ??  null,
                        'description' => $item['description'][$locale] ??  null,
                        'tags' => !empty($item['tags'][$locale]) ? $item['tags'][$locale] : null,
                        'icon' => !empty($item['icon']) ? Storage::url($item['icon']) : null,
                        'main_action' => [
                            'title' => $item['main_action']['title'][$locale] ?? null,
                            'link' => $item['main_action']['url'][$locale] ?? null,
                        ],
                        'secondary_action' => [
                            'title' => $item['secondary_action']['title'][$locale] ?? null,
                            'link' => $item['secondary_action']['url'][$locale] ?? null,
                        ],
                    ];
                })->filter(function ($item) {
                    // Remove empty items (all fields null or empty)
                    return !empty($item['title']) || !empty($item['description']) || !empty($item['tags']) || !empty($item['icon']) || !empty($item['main_action']['title']) || !empty($item['main_action']['link']) || !empty($item['secondary_action']['title']) || !empty($item['secondary_action']['link']);
                })->values()->toArray();

                // If all fields are empty, return null
                if (empty($title) && empty($description) && empty($list)) {
                    return null;
                }

                return [
                    'title' => $title,
                    'description' => $description,
                    'list' => $list,
                ];
            })(),

            
        ];

        return response()->json($serviceData);
    }
private function replaceTrixVideo($html)
{
    // ✅ استبدال الفيديوهات
    $html = preg_replace_callback(
        '/<figure[^>]*data-trix-attachment="([^"]+)"[^>]*><a[^>]*><figcaption.*?<\/figure>/s',
        function ($matches) {
            $json = html_entity_decode($matches[1]);
            $data = json_decode($json, true);

            if ($data && isset($data['url']) && str_contains($data['contentType'] ?? '', 'video')) {
                return '<video src="' . e($data['url']) . '" controls style="max-width:100%;display:block;margin:10px auto;"></video>';
            }

            // ✅ إذا كانت صورة، نحافظ عليها لكن نحذف الكابتشن
            if ($data && isset($data['url']) && str_contains($data['contentType'] ?? '', 'image')) {
                return '<img src="' . e($data['url']) . '" style="max-width:100%;display:block;margin:10px auto;" />';
            }

            return ''; // احذف العنصر إن لم يُعرف نوعه
        },
        $html
    );

    // ✅ تنظيف أي figcaption متبقي (احتياطي)
    $html = preg_replace('/<figcaption[^>]*>.*?<\/figcaption>/is', '', $html);

    return $html;
}


    /**
     * Reorder services.
     *
     * @return JsonResponse
     */
    public function reorder(): JsonResponse
    {
        $request = request();
        
        // Validate the request
        $request->validate([
            'services' => 'required|array',
            'services.*.id' => 'required|integer|exists:services,id',
            'services.*.order' => 'required|integer|min:0',
        ]);

        $services = $request->input('services');
        
        // Update the order for each service
        foreach ($services as $serviceData) {
            Service::where('id', $serviceData['id'])
                ->update(['order' => $serviceData['order']]);
        }

        return response()->json([
            'message' => 'Services reordered successfully',
            'services' => Service::published()
                ->ordered()
                ->select('id', 'title', 'order')
                ->get()
                ->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'title' => $service->title,
                        'order' => $service->order,
                    ];
                })
        ]);
    }
}
