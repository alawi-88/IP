<?php

namespace App\Http\Controllers;

use App\Http\Resources\SocialResource;
use App\Models\Social;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SocialController extends Controller
{
    public function __invoke(): ResourceCollection
    {
        return SocialResource::collection(Social::all());
    }
}
