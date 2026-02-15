<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompetitionTabResource;
use App\Models\CompetitionTab;
use Illuminate\Http\Request;

class CompetitionTabController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'competition_id' => 'required|exists:competitions,id',
        ]);

        return CompetitionTabResource::collection(CompetitionTab::where('competition_id', $request->competition_id)->get());
    }
}
