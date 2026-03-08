<?php

namespace App\Http\Controllers;

use App\Http\Resources\RegistrationFormConfigResource;
use App\Models\Program;
use Illuminate\Http\Request;

class RegistrationConfigController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'program_id' => 'required|integer|exists:programs,id',
        ]);

        $program = Program::find($request->program_id);

        $registrationConfig = $program->registrationFormConfig;

        if (!$registrationConfig) {
            return response()->json([
                'message' => 'No active registration configuration found for this program.',
            ], 404);
        }

        return new RegistrationFormConfigResource($registrationConfig);
    }
}
