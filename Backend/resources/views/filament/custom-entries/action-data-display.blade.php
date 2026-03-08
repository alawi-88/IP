@php
    // New and old data
    $id = '';
    $titleEn = '';
    $titleAr = '';
    $aboutEn = '';
    $aboutAr = '';
    $termsEn = '';
    $termsAr = '';
    $type = '';
    $actionType = '';
    $isPublished = '';
    $banner = '';

    $old_titleEn = '';
    $old_titleAr = '';
    $old_aboutEn = '';
    $old_aboutAr = '';
    $old_termsEn = '';
    $old_termsAr = '';
    $old_type = '';
    $old_actionType_en = '';
    $actionType_ar = '';
    $actionType_en = '';
    $old_isPublished = '';
    $old_banner = '';
    $old_actionType_ar = '';

    $rawData = $getState() ?? null;
    $record = $record ?? null;
    $program_id = $program_id ?? '';

    // Handle null or empty data
    if (empty($rawData)) {
        $displayData = 'No data available / لا توجد بيانات متاحة';
    } else {
        if (!is_array($rawData) && !is_string($rawData)) {
            $displayData = 'Invalid data format / تنسيق بيانات غير صحيح';
        } else {
            if (is_string($rawData)) {
                $decoded = json_decode($rawData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $rawData = $decoded;
                } else {
                    $displayData = $rawData;
                }
            }

            // Extract new (current) values
            if (is_array($rawData)) {
                if($program_id){
                    $program = \App\Models\Program::find($program_id);
                    $old_titleEn = $program->title['en'] ?? '';
                    $old_titleAr = $program->title['ar'] ?? '';
                    $old_aboutEn = $program->about['en'] ?? '';
                    $old_aboutAr = $program->about['ar'] ?? '';
                    $old_termsEn = $program->terms_and_conditions['en'] ?? '';
                    $old_termsAr = $program->terms_and_conditions['ar'] ?? '';
                }else{
                    // If there is a "record" (Eloquent model), extract "old" values from getOriginal for comparison
                if (isset($rawData['old_values'])  && !empty($rawData['old_values'])) {
                    $old_values = $rawData['old_values'];
                    
                    // Handle title - check both formats: title_ar or title['ar']
                    if (isset($old_values['title'])) {
                        if (is_array($old_values['title'])) {
                            $old_titleEn = strip_tags($old_values['title']['en'] ?? '');
                            $old_titleAr = strip_tags($old_values['title_ar'] ?? '');
                        } else {
                            $old_titleEn = strip_tags($old_values['title'] ?? '');
                        }
                    }
                    // Also check for separate title_ar field
                    if (isset($old_values['title_ar']) && empty($old_titleAr)) {
                        $old_titleAr = strip_tags($old_values['title_ar'] ?? '');
                    }
                    
                    // Handle about - check both formats: about_ar or about['ar']
                    if (isset($old_values['about'])) {
                        if (is_array($old_values['about'])) {
                            $old_aboutEn = strip_tags($old_values['about']['en'] ?? '');
                            $old_aboutAr = strip_tags($old_values['about_ar'] ?? '');
                        } else {
                            $old_aboutEn = strip_tags($old_values['about'] ?? '');
                        }
                    }
                    // Also check for separate about_ar field
                    if (isset($old_values['about_ar']) && empty($old_aboutAr)) {
                        $old_aboutAr = strip_tags($old_values['about_ar'] ?? '');
                    }
                    
                    // Handle terms_and_conditions - check both formats: terms_and_conditions_ar or terms_and_conditions['ar']
                    if (isset($old_values['terms_and_conditions'])) {
                        if (is_array($old_values['terms_and_conditions'])) {
                            $old_termsEn = strip_tags($old_values['terms_and_conditions']['en'] ?? '');
                            $old_termsAr = strip_tags($old_values['terms_and_conditions_ar'] ?? '');
                        } else {
                            $old_termsEn = strip_tags($old_values['terms_and_conditions'] ?? '');
                        }
                    }
                    // Also check for separate terms_and_conditions_ar field
                    if (isset($old_values['terms_and_conditions_ar']) && empty($old_termsAr)) {
                        $old_termsAr = strip_tags($old_values['terms_and_conditions_ar'] ?? '');
                    }
                    
                    $types = [
                        'Hackathon' => 'هاكاثون',
                        'Program' => 'مسابقة',
                        'Event' => 'فعالية',
                        'Workshop' => 'ورشة عمل',
                    ];
                    $old_type_en = $old_values['type'] ?? '';
                    $old_type_ar = isset($types[$old_type_en]) ? $types[$old_type_en] : '';
                    $old_type = $old_type_en ? ($old_type_ar ? $old_type_en . ' / ' . $old_type_ar : $old_type_en) : '';
                    
                    $actionTypes = [
                        'create' => 'إنشاء',
                        'update' => 'تحديث',
                        'delete' => 'حذف',
                        'publish' => 'نشر',
                        'archive' => 'أرشيف',   
                        'unpublish' => 'إلغاء النشر'
                    ];
                    $old_actionType_en = $old_values['action'] ?? $old_values['action_type'] ?? '';
                    $old_actionType_ar = isset($actionTypes[$old_actionType_en]) ? $actionTypes[$old_actionType_en] : '';
                    //$old_actionType = $old_actionType_en ? ($old_actionType_ar ? $old_actionType_en . ' / ' . $old_actionType_ar : $old_actionType_en) : '';
                    
                    $old_isPublished = isset($old_values['is_published']) 
                        ? (is_null($old_values['is_published']) ? '' : ($old_values['is_published'] ? '✅ Yes / نعم' : '❌ No / لا'))
                        : '';
                    $old_banner = isset($old_values['banner']) && $old_values['banner'] 
                        ? asset('storage/' . $old_values['banner']) 
                        : '';
                }
                }
                
                
                foreach ($rawData as $key => $value) {
                    if ($key === 'title' && is_array($value)) {
                        $titleAr = is_string($value['ar'] ?? '') ? strip_tags($value['ar']) : '';
                        $titleEn = is_string($value['en'] ?? '') ? strip_tags($value['en']) : '';
                    } elseif ($key === 'about' && is_array($value)) {
                        $aboutAr = is_string($value['ar'] ?? '') ? strip_tags($value['ar']) : '';
                        $aboutEn = is_string($value['en'] ?? '') ? strip_tags($value['en']) : '';
                    } elseif ($key === 'terms_and_conditions' && is_array($value)) {
                        $termsAr = is_string($value['ar'] ?? '') ? strip_tags($value['ar']) : '';
                        $termsEn = is_string($value['en'] ?? '') ? strip_tags($value['en']) : '';
                    } elseif ($key === 'type') {
                        $types = [
                            'Hackathon' => 'هاكاثون',
                            'Program' => 'مسابقة',
                            'Event' => 'فعالية',
                            'Workshop' => 'ورشة عمل'
                        ];
                        if (is_string($value)) {
                            $type_en = $value;
                            $type_ar = isset($types[$value]) ? $types[$value] : '';
                            $type = $type_ar ? $type_en . ' / ' . $type_ar : $type_en;
                        } else {
                            $type = '';
                        }
                    } elseif ($key === 'action_type') {
                        $actionTypes = [
                            'create' => 'إنشاء',
                            'update' => 'تحديث',
                            'delete' => 'حذف',
                            'publish' => 'نشر',
                            'archive' => 'أرشيف',
                            'unpublish' => 'إلغاء النشر'
                        ];
                        if (is_string($value)) {
                            $actionType_en = $value;
                            $actionType_ar = isset($actionTypes[$value]) ? $actionTypes[$value] : '';
                        
                        }
                        
                    } elseif ($key === 'is_published') {
                        $isPublished = is_bool($value) ? ($value ? '✅ Yes / نعم' : '❌ No / لا') : '';
                    } elseif ($key === 'banner' && is_string($value) && !empty($value)) {
                        $banner = asset('storage/' . $value);
                    }
                }
            }
        }
    }
@endphp

@if(isset($displayData))
    <div class="text-gray-600 dark:text-gray-400">
        {{ $displayData }}
    </div>
@else
    <div class="flex flex-col md:flex-row gap-6">
        <!-- English Column (Left) -->
        <div class="flex-1 space-y-4">
            <h3 class="text-lg font-semibold text-blue-600 dark:text-blue-400 border-b border-gray-200 dark:border-gray-700 pb-2">
                English
            </h3>

            @if($titleEn || $old_titleEn)
                <div class="space-y-1">
                    <span class="font-medium text-gray-900 dark:text-white">Title:</span>
                    @if($titleEn)<p class="text-green-700 dark:text-green-400 text-sm">{{ $titleEn }}</p>@endif
                    @if($old_titleEn !== '')
                        <p class="text-red-600 dark:text-red-400 text-xs italic">Old: {{ $old_titleEn }}</p>
                    @endif
                </div>
            @endif

            @if($aboutEn || $old_aboutEn)
                <div class="space-y-1">
                    <span class="font-medium text-gray-900 dark:text-white">About:</span>
                    @if($aboutEn)<p class="text-green-700 dark:text-green-400 text-sm">{{ $aboutEn }}</p>@endif
                    @if($old_aboutEn !== '')
                        <p class="text-red-600 dark:text-red-400 text-xs italic">Old: {{ $old_aboutEn }}</p>
                    @endif
                </div>
            @endif

            @if($termsEn || $old_termsEn)
                <div class="space-y-1">
                    <span class="font-medium text-gray-900 dark:text-white">Terms and conditions:</span>
                    @if($termsEn)<p class="text-green-700 dark:text-green-400 text-sm">{{ $termsEn }}</p>@endif
                    @if($old_termsEn !== '')
                        <p class="text-red-600 dark:text-red-400 text-xs italic">Old: {{ $old_termsEn }}</p>
                    @endif
                </div>
            @endif

            @if($type || $old_type)
                <div class="space-y-1">
                    <span class="font-medium text-gray-900 dark:text-white">Type:</span>
                    @if($type)<p class="text-green-700 dark:text-green-400 text-sm">{{ $type }}</p>@endif
                    @if($old_type !== '')
                        <p class="text-red-600 dark:text-red-400 text-xs italic">Old: {{ $old_type }}</p>
                    @endif
                </div>
            @endif

            @if($actionType_en || $old_actionType_en)
                <div class="space-y-1">
                    <span class="font-medium text-gray-900 dark:text-white">Action Type:</span>
                    @if($actionType_en)<p class="text-green-700 dark:text-green-400 text-sm">{{ $actionType_en }}</p>@endif
                    @if($old_actionType_en !== '')
                        <p class="text-red-600 dark:text-red-400 text-xs italic">Old: {{ $old_actionType_en }}</p>
                    @endif
                </div>
            @endif

            @if($isPublished || $old_isPublished)
                <div class="space-y-1">
                    <span class="font-medium text-gray-900 dark:text-white">Published:</span>
                    @if($isPublished)<p class="text-green-700 dark:text-green-400 text-sm">{{ $isPublished }}</p>@endif
                    @if($old_isPublished !== '')
                        <p class="text-red-600 dark:text-red-400 text-xs italic">Old: {{ $old_isPublished }}</p>
                    @endif
                </div>
            @endif
        </div>

        <!-- Arabic Column (Right) -->
        <div class="flex-1 space-y-4">
            <h3 class="text-lg font-semibold text-blue-600 dark:text-blue-400 border-b border-gray-200 dark:border-gray-700 pb-2">
                العربية
            </h3>

            @if($titleAr || $old_titleAr)
                <div class="space-y-1">
                    <span class="font-medium text-gray-900 dark:text-white">العنوان:</span>
                    @if($titleAr)<p class="text-green-700 dark:text-green-400 text-sm">{{ $titleAr }}</p>@endif
                    @if($old_titleAr !== '')
                        <p class="text-red-600 dark:text-red-400 text-xs italic">السابق: {{ $old_titleAr }}</p>
                    @endif
                </div>
            @endif

            @if($aboutAr || $old_aboutAr)
                <div class="space-y-1">
                    <span class="font-medium text-gray-900 dark:text-white">الوصف:</span>
                    @if($aboutAr)<p class="text-green-700 dark:text-green-400 text-sm">{{ $aboutAr }}</p>@endif
                    @if($old_aboutAr !== '')
                        <p class="text-red-600 dark:text-red-400 text-xs italic">السابق: {{ $old_aboutAr }}</p>
                    @endif
                </div>
            @endif

            @if($termsAr || $old_termsAr)
                <div class="space-y-1">
                    <span class="font-medium text-gray-900 dark:text-white">الشروط والأحكام:</span>
                    @if($termsAr)<p class="text-green-700 dark:text-green-400 text-sm">{{ $termsAr }}</p>@endif
                    @if($old_termsAr !== '')
                        <p class="text-red-600 dark:text-red-400 text-xs italic">السابق: {{ $old_termsAr }}</p>
                    @endif
                </div>
            @endif

            @if($type || $old_type)
                <div class="space-y-1">
                    <span class="font-medium text-gray-900 dark:text-white">النوع:</span>
                    @if($type)<p class="text-green-700 dark:text-green-400 text-sm">{{ $type }}</p>@endif
                    @if($old_type !== '')
                        <p class="text-red-600 dark:text-red-400 text-xs italic">السابق: {{ $old_type }}</p>
                    @endif
                </div>
            @endif

            @if($old_actionType_ar || $actionType_ar)
                <div class="space-y-1">
                    <span class="font-medium text-gray-900 dark:text-white">نوع الإجراء:</span>
                    @if($actionType_ar)<p class="text-green-700 dark:text-green-400 text-sm">{{ $actionType_ar }}</p>@endif
                    @if($old_actionType_ar !== '')
                        <p class="text-red-600 dark:text-red-400 text-xs italic">السابق: {{ $old_actionType_ar }}</p>
                    @endif
                </div>
            @endif

            @if($isPublished || $old_isPublished)
                <div class="space-y-1">
                    <span class="font-medium text-gray-900 dark:text-white">منشور:</span>
                    @if($isPublished)<p class="text-green-700 dark:text-green-400 text-sm">{{ $isPublished }}</p>@endif
                    @if($old_isPublished !== '')
                        <p class="text-red-600 dark:text-red-400 text-xs italic">السابق: {{ $old_isPublished }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Banner Section -->
    @if($banner || $old_banner)
        <div class="mt-6 text-center">
            <h4 class="text-lg font-semibold text-blue-600 dark:text-blue-400 mb-4">
                Banner / البانر
            </h4>
            @if($banner)
                <img src="{{ $banner }}"
                    alt="Banner"
                    class="max-w-full h-auto rounded-lg shadow-lg mx-auto"
                    style="max-height: 100px;">
            @endif
            @if($old_banner)
                <div class="mt-2">
                    <span class="block text-red-600 dark:text-red-400 text-xs italic">Old Banner / البانر السابق:</span>
                    <img src="{{ $old_banner }}"
                        alt="Old Banner"
                        class="max-w-full h-auto rounded shadow mx-auto"
                        style="max-height: 80px; opacity: 0.8;">
                </div>
            @endif
        </div>
    @endif
@endif
