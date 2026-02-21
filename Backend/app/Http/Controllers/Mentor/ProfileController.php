<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Http\Resources\MentorResource;
use App\Http\Requests\Mentor\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Get mentor profile
     */
    public function show(): JsonResponse
    {
        $mentor = Auth::guard('mentors')->user() ?? Auth::user();
        
        if (!$mentor) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        // Load relationships
        $mentor->load(['programs', 'track', 'program']);
        
        return response()->json(new MentorResource($mentor));
    }

    /**
     * Update mentor profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $mentor = Auth::guard('mentors')->user() ?? Auth::user();

        // Determine language context from Accept-Language header, fallback to 'en'
        $lang = $request->header('Accept-Language', 'en');

        // Get the validated data first, we'll override the relevant fields below
        $data = $request->validated();

        // Handle translatable fields - update both Arabic and English together
        foreach (['name', 'experience', 'profession', 'brief'] as $field) {
            // Check if field is sent as array with ar/en keys
            if ($request->has($field) && is_array($request->input($field))) {
                $fieldData = $request->input($field);
                // If array contains 'ar' and/or 'en' keys, use them directly
                if (isset($fieldData['ar']) || isset($fieldData['en'])) {
                    $result = [
                        'ar' => $fieldData['ar'] ?? '',
                        'en' => $fieldData['en'] ?? '',
                    ];
                    $data[$field] = $result;
                    continue;
                }
            }
            
            // Check if field is sent as separate _ar and _en fields
            $arField = $field . '_ar';
            $enField = $field . '_en';
            if ($request->has($arField) || $request->has($enField)) {
                $result = [
                    'ar' => $request->input($arField, ''),
                    'en' => $request->input($enField, ''),
                ];
                $data[$field] = $result;
                continue;
            }
            
            // Fallback: if field is sent as single value, update both languages with same value
            if ($request->has($field)) {
                $inputValue = (string) $request->input($field);
                
                // Get the raw DB value (before any casts)
                $rawValue = $mentor->getOriginal($field);
        
                // Decode existing JSON safely
                $current = [];
                if (is_string($rawValue)) {
                    $decoded = json_decode($rawValue, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $current = $decoded;
                    }
                } elseif (is_array($rawValue)) {
                    $current = $rawValue;
                }
        
                // Existing values
                $originalEn = $current['en'] ?? '';
                $originalAr = $current['ar'] ?? '';
        
                // Update both languages with the same value
                $result = [
                    'ar' => $inputValue,
                    'en' => $inputValue,
                ];
        
                // Assign final structure
                $data[$field] = $result;
            }
        }                       
        

        // Handle image - can be either file upload or URL string
        if ($request->hasFile('image')) {
            // Handle file upload
            $imageFile = $request->file('image');
            
            // Validate file type and size for uploads
            $request->validate([
                'image' => [
                    'image',
                    'mimes:jpeg,png,jpg,gif',
                    'max:2048',
                ],
            ]);
            
            if ($imageFile && $imageFile->isValid()) {
                // Delete old image if exists
                if ($mentor->image && Storage::disk('public')->exists($mentor->image)) {
                    Storage::disk('public')->delete($mentor->image);
                }
                // Upload new image
                $data['image'] = $imageFile->store('mentors', 'public');
            }
        } elseif ($request->has('image') && is_string($request->input('image')) && !empty($request->input('image'))) {
            // Handle URL string (already uploaded or external URL)
            $imageUrl = $request->input('image');
            
            // Check if it's a full URL with domain
            if (preg_match('#^https?://#', $imageUrl)) {
                // Full URL - could be our storage or external
                if (preg_match('#/storage/(.*)$#', $imageUrl, $matches)) {
                    // It's from our storage - extract the relative path
                    $relativePath = $matches[1];
                    // Delete old image only if different
                    if ($mentor->image && $mentor->image !== $relativePath && Storage::disk('public')->exists($mentor->image)) {
                        Storage::disk('public')->delete($mentor->image);
                    }
                    $data['image'] = $relativePath;
                } else {
                    // External URL - keep as is
                    $data['image'] = $imageUrl;
                }
            } elseif (preg_match('#^/?storage/#', $imageUrl)) {
                // URL starts with /storage/ - remove it
                $relativePath = preg_replace('#^/?storage/#', '', $imageUrl);
                $data['image'] = $relativePath;
            } else {
                // Relative path - use as is
                $data['image'] = $imageUrl;
            }
        }

        // Normalize social media URLs - add https:// if missing
        foreach (['linkedin', 'facebook', 'instagram'] as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $url = trim($data[$field]);
                // If URL doesn't start with http:// or https://, add https://
                if (!preg_match('/^https?:\/\//i', $url)) {
                    $data[$field] = 'https://' . $url;
                } else {
                    $data[$field] = $url;
                }
            }
        }

        // Remove any possible helper or non-main translatable fields if present (they're already processed above)
        unset($data['name_en'], $data['name_ar'], $data['experience_en'], $data['experience_ar'], $data['profession_en'], $data['profession_ar'], $data['brief_en'], $data['brief_ar']);

        // Update mentor profile
        $mentor->update($data);

        // Reload mentor with relationships
        $mentor->refresh();
        $mentor->load(['programs', 'track', 'program']);

        return response()->json([
            'message' => __('mentor.profile_updated_successfully'),
            'mentor' => new MentorResource($mentor),
        ]);
    }
}

