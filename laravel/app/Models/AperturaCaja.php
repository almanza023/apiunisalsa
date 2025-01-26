<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
class AperturaCaja extends Model
{
    protected $table = 'apertura_caja';

    protected $fillable = [
        'user_id',
        'monto_inicial',
        'monto_final',
        'fecha',
        'fecha_cierre',
        'totalventas',
        'totalgastos',
        'utilidad',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    /**
     * Get the user that owns the AperturaCaja
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public static function validarAperturaFecha($fecha){
        return AperturaCaja::where('fecha', $fecha)
        ->where('estado', 1)->get();
    }

    public static function getAll()
    {
        return self::with(['user'])
            ->orderByDesc('id')
            ->get();
    }

    public static function getCajaAbierta()
    {
        return self::with(['user'])
            ->where('estado', 1)
            ->orderByDesc('id')
            ->first();
    }

public static function getByDateRange($startDate, $endDate)
{
    return self::with(['user'])
        ->whereBetween('fecha', [$startDate, $endDate])
        ->orderByDesc('id')
        ->get();
}



}
