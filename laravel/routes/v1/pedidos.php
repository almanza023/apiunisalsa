<?php


use App\Http\Controllers\V1\PedidoController;
use Illuminate\Support\Facades\Route;

        Route::get('pedidos', [PedidoController::class, 'index']);
        Route::post('pedidos', [PedidoController::class, 'store']);
        Route::post('pedidos-detalles', [PedidoController::class, 'storeDetalles']);
        Route::get('pedidos/{id}', [PedidoController::class, 'show']);
        Route::patch('pedidos/{id}', [PedidoController::class, 'update']);
        Route::delete('pedidos/{id}', [PedidoController::class, 'destroy']);
        Route::post('pedidos/cambiarEstado', [PedidoController::class, 'cambiarEstado']);
        Route::get('pedidos-activos', [PedidoController::class, 'activos']);
        Route::post('pedidos-mesa', [PedidoController::class, 'getPedidoFechaMesa']);
        Route::post('pedidos-entrega', [PedidoController::class, 'entregarProductosPedido']);
        Route::post('pedidos-historial', [PedidoController::class, 'getHistorialFechaMesa']);
        Route::post('pedidos-cerrados', [PedidoController::class, 'getPedidosCerrados']);
        Route::post('pedidos-filter', [PedidoController::class, 'filter']);
        Route::post('pedidos-cambio-mesa', [PedidoController::class, 'actualizarMesa']);
        Route::post('pedidos-entrega-todos', [PedidoController::class, 'entregarProductosPedidoTodos']);


?>
