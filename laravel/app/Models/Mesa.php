<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    protected $table = 'mesas';

    protected $fillable = [
        'nombre',
        'numero',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function get(){
        return Mesa::all();
    }

    public static function active(){
        return Mesa::where('estado', 1)->get();
    }

public static function mesasConPedidoActivo($estado)
{
    return Pedido::select('p.id AS pedido_id', 'm.id', 'm.nombre', 'u.nombre as usuario', 'p.fecha', 'p.comanda')
        ->from('pedidos as p')
        ->join('mesas as m', 'm.id', '=', 'p.mesa_id')
        ->join('users as u', 'u.id', '=', 'p.user_id')
        ->where('p.estadopedido_id', $estado)
        ->get();
    }
}
