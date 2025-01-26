<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


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








}
