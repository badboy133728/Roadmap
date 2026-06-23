<?php

namespace App\Http\Controllers;

use App\Services\CityService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(CityService $cityService): View
    {
        return view('home', [
            'cities' => $cityService->all(),
            'currentCity' => $cityService->current(),
        ]);
    }
}
