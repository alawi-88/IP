<?php

namespace App\Http\Controllers;

use App\Models\DisclaimerAcceptance;
use Illuminate\Http\Request;

class JudgeDisclaimerController extends Controller
{
    public function checkStatus(Request $request)
    {
        $user = $request->user();

        // Check if judge is archived
        if ($user && method_exists($user, 'isArchived') && $user->isArchived()) {
            abort(401, 'Account has been archived');
        }

        $data = DisclaimerAcceptance::where('form_id', $request->form_id)
            ->where('judge_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'disclaimer_accepted' => $data->accepted,
            'disclaimer_accepted_at' => $data->accepted_at
        ]);
    }

    public function acceptDisclaimer(Request $request)
    {
        $request->validate([
            'accepted'  => 'required|boolean',
            'form_id'   => 'required|integer|exists:forms,id',
            'stage_id'  => 'required|integer|exists:stages,id',
        ]);

        $user = $request->user();

        // Check if judge is archived
        if ($user && method_exists($user, 'isArchived') && $user->isArchived()) {
            abort(401, 'Account has been archived');
        }

        // Save or update disclaimer acceptance
        $acceptance = DisclaimerAcceptance::updateOrCreate(
            [
                'judge_id' => $user->id,
                'form_id' => $request->form_id,
                'stage_id' => $request->stage_id,
            ],
            [
                'accepted'     => $request->boolean('accepted'),
                'accepted_at'  => $request->boolean('accepted') ? now() : null,
            ]
        );

        return response()->json([
            'message' => $acceptance->accepted
                ? 'Disclaimer accepted successfully'
                : 'Disclaimer rejected',
            'disclaimer_accepted' => $acceptance->accepted,
            'disclaimer_accepted_at' => $acceptance->accepted_at,
        ]);
    }

}
