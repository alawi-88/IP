<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProgramTabResource;
use App\Models\ProgramTab;
use Illuminate\Http\Request;

class ProgramTabController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
        ]);

        return ProgramTabResource::collection(ProgramTab::where('program_id', $request->program_id)->get());
    }
}
