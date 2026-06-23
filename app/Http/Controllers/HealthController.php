<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class HealthController extends Controller
{
    public function __invoke(): Response
    {
        return response('OK', 200);
    }
}
