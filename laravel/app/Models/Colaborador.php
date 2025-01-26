<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Colaborador extends Model
{
    protected $table = 'colaboradores';

    protected $fillable = [
        'nombre',
        'numerodocumento',
        'telefono',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function get(){
        return Colaborador::all();
    }

    public static function active(){
        return Colaborador::where('estado', 1)->get();
    }

}
