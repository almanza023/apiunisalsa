<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MovimientoInventario extends Model
{
    protected $table = 'movimiento_inventarios';

    protected $fillable = [
        'producto_id',
        'user_id',
        'tipo',
        'cantidad',
        'precio_venta',
        'precio_compra',
        'saldo',
        'fecha',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, foreignKey: 'user_id');
    }

    // Relación con el producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

public static function modificarStock($productoId, $userId, $cantidad, $precioVenta, $precioCompra, $descripcion, $tipoMovimiento)
{
    DB::transaction(function () use ($productoId, $userId, $cantidad, $precioVenta, $precioCompra, $descripcion, $tipoMovimiento) {
        // Obtener el producto
        $producto = Producto::find($productoId);

        if (!$producto) {
            return false;
            //throw new \Exception('Producto no encontrado'); // Lanzar excepción si el producto no existe
        }

        // Calcular el nuevo saldo
        $nuevoSaldo = $tipoMovimiento === 1 ? $producto->stock_actual : $producto->stock_actual - $cantidad;

        // Crear el movimiento de inventario
        self::create([
            'producto_id' => $productoId,
            'user_id' => $userId, // Asumiendo que el usuario está autenticado
            'tipo' => $tipoMovimiento, // Tipo de movimiento ENTRADA o SALIDA
            'cantidad' => $cantidad,
            'precio_venta' => $precioVenta,
            'precio_compra' => $precioCompra,
            'saldo' => $nuevoSaldo,
            'fecha' => now(),
            'descripcion' => $descripcion,
        ]);
        // Actualizar el saldo del producto
        $producto->stock_actual = $nuevoSaldo;
        $producto->save();
    });

    return true; // Retornar true si el stock se modificó exitosamente
}

public static function getMovimientosPorProducto($productoId)
{
    return self::where('producto_id', $productoId)
        ->orderBy('id', 'desc')
        ->get();
}








}
