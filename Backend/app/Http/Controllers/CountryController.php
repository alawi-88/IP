<?php

namespace App\Http\Controllers;

use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CountryController extends Controller
{
    public function __invoke(): ResourceCollection
    {
        return CountryResource::collection(Country::all());
    }
}
