<?php

namespace App\Http\Controllers;

use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CityController extends Controller
{
    public function __invoke(): ResourceCollection
    {
        request()->validate([
            'country_id' => 'required|integer|exists:countries,id',
        ]);

        return CityResource::collection(City::where('country_id', request('country_id'))->get());
    }
}
