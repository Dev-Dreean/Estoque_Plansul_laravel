<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AlmoxarifadoController extends Controller
{
    /**
     * Mostra página de construção do Almoxarifado
     */
    public function index(): View
    {
        return view('almoxarifado.construcao');
    }
}


