<?php

namespace App\Http\Controllers;

use App\Http\Requests\SatisfactionRequest;
use App\Http\Resources\SatisfactionResource;
use App\Models\Satisfaction;

class SatisfactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return SatisfactionResource::collection(auth()->user()->satisfactions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SatisfactionRequest $request)
    {
        $satisfactions = collect($request->validated())->map(function ($answer, $question) {
            return Satisfaction::create(['question' => $question, 'answer' => $answer,]);
        });

        return SatisfactionResource::collection($satisfactions->values());
    }

    public function isSubmitted()
    {
        return response()->json(['submitted' => auth()->user()->satisfactions()->exists()]);
    }
}
