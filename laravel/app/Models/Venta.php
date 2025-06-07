<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Venta extends Model
{
    protected $table = 'ventas';
    protected $fillable = [
        'cliente_id',
        'user_id',
        'pedido_id',
        'caja_id',
        'fecha',
        'total',
        'propina',
        'cantidad',
        'observaciones',
        'estado',
        'especial'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, foreignKey: 'pedido_id');
    }


    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function pagos()
    {
        return $this->hasMany(VentaTipoPago::class, foreignKey: 'venta_id');
    }

    public static function getAll()
    {
        return self::with(['pedido', 'detalles.producto', 'user', 'pagos.tipopago'])
            ->orderByDesc('id')
            ->get();
    }

    public static function getTotalByDate($startDate, $endDate, $caja=null)
    {
        $query = self::where('estado', 1)->where('especial', 0);
        if ($caja != null) {
            $query->where('caja_id', $caja);
        } else {
            $query->whereBetween('fecha', [$startDate, $endDate]);
        }
        return $query->sum('total');
    }
    public static function getTotalEspecialesByDate($startDate, $endDate, $caja=null)
    {
        $query = self::where('estado', 1)->where('especial', 1);
        if ($caja != null) {
            $query->where('caja_id', $caja);
        } else {
            $query->whereBetween('fecha', [$startDate, $endDate]);
        }
        return $query->sum('total');
    }


    public static function getVentasByDate($startDate, $endDate, $caja_id=null)
    {
        $query = self::with(['pedido.user', 'detalles.producto', 'user', 'pagos.tipopago']);
        if ($caja_id != null) {
            $query->where('caja_id', $caja_id);
        } else {
            $query->whereBetween('fecha', [$startDate, $endDate]);
        }
        $query->where('especial', 0);
        $query->orderByDesc('id');
        return $query->get();
    }

    public static function getTotalByTipoPagoAndDate($startDate, $endDate, $caja_id=null)
    {
        $query = VentaTipoPago::selectRaw('tipo_pagos.nombre, SUM(ventas_tipo_pagos.valor) as total')
            ->join('ventas', 'ventas.id', '=', 'ventas_tipo_pagos.venta_id')
            ->join('tipo_pagos', 'tipo_pagos.id', '=', 'ventas_tipo_pagos.tipopago_id')
            ->where('ventas.estado', 1);
        if ($caja_id != null) {
            $query->where('ventas.caja_id', $caja_id);
        }else{
            $query->whereBetween('ventas.fecha', [$startDate, $endDate]);
        }
        $query->where('ventas.especial', 0);
        return $query->groupBy('tipo_pagos.nombre')->get();
    }


    public static function getFilter($fecha_inicio = null, $fecha_fin = null)
    {
        $query = self::with(['pedido', 'detalles.producto', 'user', 'pagos.tipopago']);
        if ($fecha_inicio !== null && $fecha_fin !== null) {
            $query->whereBetween('fecha', [$fecha_inicio, $fecha_fin]);
        }
        return $query->orderByDesc('id')->get();
    }






}
