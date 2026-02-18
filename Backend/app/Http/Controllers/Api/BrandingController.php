<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Filament\Pages\BrandingSettings;
use Illuminate\Http\JsonResponse;

class BrandingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => BrandingSettings::getApiData(),
        ]);
    }
}

