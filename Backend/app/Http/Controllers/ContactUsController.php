<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactUsRequest;
use App\Http\Resources\ContactUsResource;
use App\Models\ContactUs;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

class ContactUsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): ResourceCollection
    {
        $user = auth()->user();
        $inquires = ContactUs::query()
            ->where('model_id', $user->id)
            ->where('model_type', $user->getMorphClass())
            ->active() // Only show non-archived Contact Us records
            ->orderBy('created_at', 'desc')
            ->get();

        return ContactUsResource::collection($inquires);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ContactUsRequest $request): JsonResource
    {
        try {
            $data = Arr::except($request->validated(), 'attachments');

            if ($request->hasFile('attachments')) {
                $data['attachments'] = [];
                foreach ($request->attachments as $attachment) {
                    $data['attachments'][] = $attachment->store('attachments');
                }
            }

            $contactUs = ContactUs::create($data);

            return new ContactUsResource($contactUs->fresh());
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create contact us message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResource
    {
        // Ensure the contact us message belongs to the authenticated user and is not archived
        $contactUs = ContactUs::where('id', $id)
            ->where('model_id', auth()->id())
            ->where('model_type', auth()->user()->getMorphClass())
            ->active() // Only show non-archived Contact Us records
            ->first();

        if (!$contactUs) {
            abort(404, 'Contact Us record not found');
        }

        return new ContactUsResource($contactUs);
    }

    public function isSubmitted()
    {
        $user = auth()->user();
        $hasSubmitted = ContactUs::where('model_id', $user->id)
            ->where('model_type', $user->getMorphClass())
            ->active()
            ->exists();
            
        return response()->json(['submitted' => $hasSubmitted]);
    }
}
