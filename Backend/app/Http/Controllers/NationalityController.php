<?php

namespace App\Http\Controllers;

use App\Http\Resources\NationalityResource;
use App\Models\Nationality;
use Illuminate\Http\Resources\Json\ResourceCollection;

class NationalityController extends Controller
{
    public function __invoke(): ResourceCollection
    {
        return NationalityResource::collection(Nationality::all());
    }
}
