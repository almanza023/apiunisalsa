<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DetallePedido extends Model
{
    protected $table = 'detalles_pedidos';

    protected $fillable = [
        'pedido_id',
        'producto_id',
        'precio',
        'cantidad',
        'subtotal',
        'entregado',
        'cantidad_entregada',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, foreignKey: 'pedido_id');
    }

    // Relación con el producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public static function getDetalleByPedido($pedidoId, $estado){
    return self::select('producto_id',
    DB::raw('SUM(cantidad_entregada) as total_entregadas'),
    DB::raw('SUM(cantidad) as total_cantidad'),
    DB::raw('SUM(subtotal) as total_subtotal'),
    'precio')
        ->where('pedido_id', $pedidoId)
        ->groupBy('producto_id', 'precio')
        ->with('producto:id,nombre') // Asegúrate de que el modelo Producto tenga los campos 'id' y 'nombre'
        ->get();
    }

    public static function getDetallePendienteByPedido($pedidoId, $estado){
        return self::where('pedido_id', $pedidoId)
        ->where('entregado', 0)
        ->with(['producto:id,nombre'])
        ->get();
}
}
