<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Pedido extends Model
{
    protected $table = 'pedidos';
    protected $fillable = [
        'estadopedido_id',
        'user_id',
        'cliente_id',
        'caja_id',
        'comanda',
        'mesa_id',
        'fecha',
        'total',
        'cantidad',
        'facturado',
        'estado',
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

    public function mesa()
    {
        return $this->belongsTo(Mesa::class, foreignKey: 'mesa_id');
    }

    public function estado()
    {
        return $this->belongsTo(EstadoPedido::class, foreignKey: 'estadopedido_id');
    }


    public function detalles()
    {
        return $this->hasMany(DetallePedido::class, 'pedido_id');
    }




    public static function getDetalleByPedido($pedidoId, $estado)
    {
        return self::select('producto_id', DB::raw('SUM(cantidad) as total_cantidad'), DB::raw('SUM(subtotal) as total_subtotal'))
            ->where('pedido_id', $pedidoId)
            ->groupBy('producto_id')
            ->with('producto:id,nombre,precio') // Asegúrate de que el modelo Producto tenga los campos 'id', 'nombre' y 'precio'
            ->get();
    }

    public static function getAll()
    {
        return self::with(['mesa', 'estado', 'user'])
            ->orderByDesc('id')
            ->get();
    }

    public static function getFilter($fecha_inicio = null, $fecha_fin = null, $user_id=null, $estadoPedido_id=1)
    {
        $query = self::with(['mesa', 'estado', 'user']);
        if ($fecha_inicio !== null && $fecha_fin !== null) {
            $query->whereBetween('fecha', [$fecha_inicio, $fecha_fin]);
        }
        if ($user_id !== null) {
            $query->where('user_id', $user_id);
        }
        if ($estadoPedido_id !== null) {
            $query->where('estadopedido_id', $estadoPedido_id);
        }
        return $query->orderByDesc('id')->get();
    }

    public static function getDetallePorMesaYFecha($mesa_id, $fecha, $estado, $entregado, $pedido_id)
    {
        return DetallePedido::whereHas('pedido', function ($query) use ($mesa_id, $pedido_id, $estado, $entregado) {
            $query->where('estadopedido_id', $estado)
                ->where('id', $pedido_id)
                ->where('mesa_id', $mesa_id)
                ->where('entregado', $entregado);
                //->whereDate('fecha', $fecha);
        })
            ->with('producto:id,nombre,precio')
            ->get()
            ->map(function ($detalle) {
                $detalle->mesa_id = $detalle->pedido->mesa_id; // Obtener la mesa_id del pedido
                return $detalle;
            });
    }

    public static function getHistorialPorMesaYFecha($mesa_id, $fecha, $pedido_id)
    {
        return DetallePedido::whereHas('pedido', function ($query) use ($mesa_id, $fecha, $pedido_id) {
            $query->where('mesa_id', $mesa_id)
                    ->where('estadopedido_id', 1)
                    ->where('id', $pedido_id);
                //->whereDate('fecha', $fecha);
        })
            ->with('producto:id,nombre,precio')
            ->where('entregado', 1)
            ->get()
            ->map(function ($detalle) {
                $detalle->mesa_id = $detalle->pedido->mesa_id; // Obtener la mesa_id del pedido
                return $detalle;
            });
    }

        public static function getPedidosCerrados($fechaInicio, $fechaFin, $estadoPedidoId)
        {
            return self::with('user', 'mesa')
                ->where('estadopedido_id', $estadoPedidoId)
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->whereNotIn('id', function($query) {
                    $query->select('pedido_id')->from('ventas');
                })
                ->get();
        }
    public static function getTotalPedidos($fechaInicio, $fechaFin, $caja=null, $user=null, $estadopedido)
    {
        return self::where('estadopedido_id', $estadopedido)
            ->when(is_null($caja), function ($query) use ($fechaInicio, $fechaFin) {
                return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            })
            ->when($caja, function ($query) use ($caja) {
                return $query->where('caja_id', $caja);
            })
            ->when($user, function ($query) use ($user) {
                return $query->where('user_id', $user);
            })

            ->count();
    }

    public static function getTotalPedidosAbiertosByCaja($cajaId)
    {
        return self::where('estadopedido_id', 1)
            ->count();
    }


}
