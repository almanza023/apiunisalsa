<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DetalleVenta extends Model
{
    protected $table = 'detalles_ventas';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'precio',
        'cantidad',
        'subtotal',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function compra()
    {
        return $this->belongsTo(Venta::class, foreignKey: 'venta_id');
    }

    // Relación con el producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

public static function getVentasPorCaja($cajaId)
{
    return self::join('productos', 'detalles_ventas.producto_id', '=', 'productos.id')
        ->join('ventas', 'detalles_ventas.venta_id', '=', 'ventas.id')
        ->where('ventas.caja_id', $cajaId)
        ->where('detalles_ventas.estado', 1)
        ->select(
            'productos.nombre as producto',
            'productos.precio as precio',
            DB::raw('SUM(detalles_ventas.cantidad) as cantidad'),
            DB::raw('SUM(detalles_ventas.subtotal) as total')
        )
        ->groupBy('productos.id', 'productos.nombre', 'productos.precio')
        ->orderBy('total', 'desc')
        ->get();
}







}
