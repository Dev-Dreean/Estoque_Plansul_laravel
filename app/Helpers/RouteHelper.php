<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;

class RouteHelper
{
    /**
     * Verifica se uma rota existe no arquivo de rotas
     * 
     * @param string $routeName Nome da rota (ex: 'patrimonios.index')
     * @return bool
     */
    public static function exists(string $routeName): bool
    {
        return (bool) Route::getRoutes()->getByName($routeName);
    }
}

