<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Compra extends Model
{
    protected $table = 'compras';
    protected $fillable = [
        'proveedor_id',
        'user_id',
        'fecha',
        'total',
        'cantidad',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, foreignKey: 'user_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCompra::class, 'compra_id');
    }

    public static function getAll()
    {
        return self::with(relations: ['proveedor', 'user'])
            ->orderByDesc('id')
            ->get();
    }

    public static function getFilter($estado = null, $proveedor_id = null, $fecha_inicio = null, $fecha_fin = null)
    {
        $query = self::with(['proveedor', 'user']);

        if ($estado !== null && $estado != 0) {
            $query->where('estado', $estado);
        }

        if ($proveedor_id !== null) {
            $query->where('proveedor_id', $proveedor_id);
        }

        if ($fecha_inicio !== null && $fecha_fin !== null) {
            $query->whereBetween('fecha', [$fecha_inicio, $fecha_fin]);
        }

        return $query->orderByDesc('id')->get();
    }



}
