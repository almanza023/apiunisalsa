<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\AperturaCaja;
use App\Models\Pedido;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class VentaController extends Controller
{
    protected $user;
    protected $model;

    public function __construct(Request $request)
    {
        $this->model = Venta::class;
        // $token = $request->header('Authorization');
        // if($token != '')
        //     //En caso de que requiera autentifiación la ruta obtenemos el usuario y lo almacenamos en una variable, nosotros no lo utilizaremos.
        //     $this->user = JWTAuth::parseToken()->authenticate();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Listamos todas las mesas
        $objeto = $this->model::getAll();
        if ($objeto) {
            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'data' => $objeto
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'isSuccess' => false,
                'data' => []
            ], Response::HTTP_OK);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validamos los datos
        $data = $request->only('user_id','fecha', 'pedido_id', 'cliente_id',
        'total', 'cantidad', 'detalles', 'pagos', 'observaciones', 'propina', 'especial');
        $validator = Validator::make($data, [
            'user_id' => 'required',
            'fecha' => 'required',
            'pedido_id' => 'required',
            'cliente_id' => 'required',
            'total' => 'required',
            'cantidad' => 'required',
            'detalles' => 'required',
            'pagos' => 'required',
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }
        $caja=AperturaCaja::getCajaAbierta();
        if(!$caja){
            return response()->json([
                'code' => 400,
                'isSuccess' => false,
                'message' => 'No Existe una Apertura de Caja',
                'data'=>[]
            ], Response::HTTP_OK);
        }


        DB::transaction(function () use ($request, &$objeto) {

            $caja=AperturaCaja::getCajaAbierta();

            // Creamos la mesa en la BD
            $objeto = $this->model::create([
                'cliente_id' => $request->cliente_id,
                'pedido_id' => $request->pedido_id,
                'user_id' => $request->user_id,
                'caja_id' => $caja->id,
                'fecha' => $request->fecha,
                'total' => $request->total,
                'cantidad' => $request->cantidad,
                'propina' => $request->propina,
                'observaciones' => $request->observaciones,
                'especial' => $request->especial,
            ]);

            foreach ($request->detalles as $detalle) {
                // Validamos los datos de cada detalle
                $detalleValidator = Validator::make($detalle, [
                    'producto_id' => 'required',
                    'cantidad' => 'required|integer|min:1',
                    'precio' => 'required|numeric',
                ]);

                // Si falla la validación del detalle, lanzamos una excepción
                if ($detalleValidator->fails()) {
                    throw new \Exception(implode(", ", $detalleValidator->messages()->all()));
                }

                // Creamos el detalle de la venta
                $objeto->detalles()->create([
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'subtotal' => $detalle['subtotal'],
                    'precio' => $detalle['precio'],
                    'venta_id' => $objeto->id, // Asociamos el detalle a la venta creada
                ]);
            }

            foreach ($request->pagos as $pago) {
                // Validamos los datos de cada pago
                $pagoValidator = Validator::make($pago, [
                    'tipopago_id' => 'required',
                    'valor' => 'required|numeric|min:0',
                ]);

                // Si falla la validación del pago, lanzamos una excepción
                if ($pagoValidator->fails()) {
                    throw new \Exception(implode(", ", $pagoValidator->messages()->all()));
                }

                // Creamos el registro de pago
                $objeto->pagos()->create([
                    'tipopago_id' => $pago['tipopago_id'],
                    'valor' => $pago['valor'],
                    'venta_id' => $objeto->id, // Asociamos el pago a la venta creada
                ]);
            }

                $pedido = Pedido::findOrFail($request->pedido_id);
                $pedido->facturado = 1;
                $pedido->estadopedido_id = 4;
                $pedido->save();
        });
        $venta=[
            'venta' => [
                'id' => $objeto->id,
                'created_at' => $objeto->created_at,
                'total' => $objeto->total,
            ],
            'pedido' => [
                'created_at' => $objeto->pedido->created_at,
                'comanda' => $objeto->pedido->comanda,
            ],
            'detalles' => $objeto->detalles->map(function($detalle) {
                return [
                    'id' => $detalle->id,
                    'cantidad' => $detalle->cantidad,
                    'precio' => $detalle->precio,
                    'subtotal' => $detalle->subtotal,
                    'producto_nombre' => $detalle->producto->nombre, // Asegúrate de que la relación 'producto' esté definida en el modelo de Detalle
                ];
            }),
            'pagos'=>$objeto->pagos
        ];

        // Respuesta en caso de que todo vaya bien.
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Factura N° '.$objeto->id.' Creada Exitosamente',
            'data'=>$venta
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Mesa  $mesa
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Buscamos la mesa
        $objeto = $this->model::with('pedido', 'detalles', 'pago', 'user')->find($id);

        // Si la mesa no existe devolvemos error no encontrado
        if (!$objeto) {
            return response()->json([
                'code' => 200,
                'isSuccess' => false,
                'message' => 'Registro no encontrado en la base de datos.'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $objeto
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Mesa  $mesa
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validación de datos
        $data = $request->only('nombre','numero');
        $validator = Validator::make($data, [
            'nombre' => 'required',
            'numero' => 'required',
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Buscamos la mesa
        $objeto = $this->model::findOrFail($id);

        // Actualizamos la mesa.
        $objeto->update([
            'nombre' => $request->nombre,
            'numero' => $request->numero,
        ]);

        // Devolvemos los datos actualizados.
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Mesa Actualizada Exitosamente',
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Mesa  $mesa
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Buscamos la mesa
        $objeto = $this->model::findOrFail($id);

        // Eliminamos la mesa
        $objeto->delete();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Factura Eliminada Exitosamente'
        ], Response::HTTP_OK);
    }

    public function cambiarEstado(Request $request)
    {
        // Validación de datos
        $data = $request->only('id');
        $validator = Validator::make($data, [
            'id' => 'required'
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Buscamos la mesa
        $objeto = $this->model::findOrFail($request->id);

        // Cambiamos el estado
        $objeto->estado = ($objeto->estado == 1) ? 2 : 1;
        $objeto->save();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Estado Actualizado Exitosamente',
        ], Response::HTTP_OK);
    }

    public function activos()
    {
        // Listamos todos los registros activos
        $objeto = $this->model::where('estado', 1)->get();
        if ($objeto) {
            return response()->json([
                'code' => 200,
                'data' => $objeto
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'data' => []
            ], Response::HTTP_OK);
        }
    }

    public function getFilter(Request $request)
    {

        $fecha_inicio = \Carbon\Carbon::parse($request->fecha_inicio)->format('Y-m-d');
        $fecha_final = \Carbon\Carbon::parse($request->fecha_final)->format('Y-m-d');
        // Listamos todas las mesas
        $objeto = $this->model::getFilter($fecha_inicio,
            $fecha_final);
        if ($objeto) {
            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'data' => $objeto
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'isSuccess' => false,
                'data' => []
            ], Response::HTTP_OK);
        }
    }




}
