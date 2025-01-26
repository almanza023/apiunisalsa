<?php

use App\Http\Controllers\V1\ProductoController;
use Illuminate\Support\Facades\Route;

        Route::get('productos', [ProductoController::class, 'index']);
        Route::post('productos', [ProductoController::class, 'store']);
        Route::get('productos/{id}', [ProductoController::class, 'show']);
        Route::patch('productos/{id}', [ProductoController::class, 'update']);
        Route::delete('productos/{id}', [ProductoController::class, 'destroy']);
        Route::post('productos/cambiarEstado', [ProductoController::class, 'cambiarEstado']);
        Route::get('productos-activos', [ProductoController::class, 'activos']);
        Route::post('productos-movimientos', [ProductoController::class, 'movimientosInventario']);
        Route::post('productos-detalles', [ProductoController::class, 'postDetalle']);


?>
