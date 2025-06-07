<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
        'rutaimagen',
        'precio',
        'stock_actual',
        'stock_minimo',
        'cambio_precio',
        'estado'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public static function getAll(){
        return Producto::with('categoria')->get()->map(function($producto) {
            if (!empty($producto->rutaimagen)) {
                $producto->rutaimagen = url('laravel/public/'.$producto->rutaimagen);
            }
            return $producto;
        });
    }

    public static function getActivos(){
        return Producto::with('categoria')->where('estado',1)->get()->map(function($producto) {
            if (!empty($producto->rutaimagen)) {
                $producto->rutaimagen = url('laravel/public/'.$producto->rutaimagen);
            }
            return $producto;
        });
    }

    public static function getProductosStockMinimo()
    {
        return self::with('categoria')
            ->whereRaw('stock_actual <= stock_minimo')
            ->where('estado', 1)
            ->count();
    }

    public static function getProductosPorCategoria()
    {
        return self::where('estado', 1)
            ->orderBy('categoria_id')
            ->get();
    }






}
