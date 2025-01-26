<?php


use App\Http\Controllers\V1\VentaController;
use Illuminate\Support\Facades\Route;

        Route::get('ventas', [VentaController::class, 'index']);
        Route::post('ventas', [VentaController::class, 'store']);
        Route::get('ventas/{id}', [VentaController::class, 'show']);
        Route::patch('ventas/{id}', [VentaController::class, 'update']);
        Route::delete('ventas/{id}', [VentaController::class, 'destroy']);
        Route::post('ventas/cambiarEstado', [VentaController::class, 'cambiarEstado']);
        Route::get('ventas-activos', [VentaController::class, 'activos']);
        Route::post('ventas-filter', [VentaController::class, 'getFilter']);



?>
