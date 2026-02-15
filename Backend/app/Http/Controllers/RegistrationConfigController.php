<?php

namespace App\Http\Controllers;

use App\Http\Resources\RegistrationFormConfigResource;
use App\Models\Competition;
use Illuminate\Http\Request;

class RegistrationConfigController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'competition_id' => 'required|integer|exists:competitions,id',
        ]);

        $competition = Competition::find($request->competition_id);

        $registrationConfig = $competition->registrationFormConfig;

        if (!$registrationConfig) {
            return response()->json([
                'message' => 'No active registration configuration found for this program.',
            ], 404);
        }

        return new RegistrationFormConfigResource($registrationConfig);
    }
}
