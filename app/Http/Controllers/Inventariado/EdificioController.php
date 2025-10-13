<?php

namespace App\Http\Controllers\inventariado;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Edificio;

class EdificioController extends Controller
{
    public function index(Request $request)
    {
        $query = Edificio::query();
        return $query->get();
    }
}
