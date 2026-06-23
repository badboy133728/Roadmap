<?php

namespace App\Http\Controllers;

use App\Services\CityService;
use Illuminate\Http\RedirectResponse;

class CityController extends Controller
{
    public function switch(string $slug, CityService $cityService): RedirectResponse
    {
        $cityService->set($slug);

        return redirect()->back();
    }
}
