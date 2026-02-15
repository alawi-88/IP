<?php

namespace App\Http\Controllers;

use App\Http\Resources\PathResource;
use App\Models\Track;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PathController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): ResourceCollection
    {
        return PathResource::collection(Path::all());
    }
}
