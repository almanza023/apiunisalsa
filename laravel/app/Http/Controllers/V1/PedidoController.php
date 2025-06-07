<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\AperturaCaja;
use App\Models\DetallePedido;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class PedidoController extends Controller
{
    protected $user;
    protected $model;

    public function __construct(Request $request)
    {
        $this->model = Pedido::class;
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
        $data = $request->only( 'user_id', 'mesa_id', 'fecha','comanda', 'total', 'cantidad');
        $validator = Validator::make($data, [
            'user_id' => 'required',
            'mesa_id' => 'required',
            'comanda' => 'required',
            'fecha' => 'required',
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

        //Validar que la Comanda Sea Unica
        $pedido=Pedido::where('comanda', $request->comanda)->first();
        if(!empty($pedido)){
            return response()->json([
                'code' => 400,
                'isSuccess' => false,
                'message' => 'Comanda N° '.$request->comanda.' ya Existe',
                'data'=>[]
            ], Response::HTTP_OK);
        }
        // Creamos la mesa en la BD
        $objeto = $this->model::create([
            'user_id' => $request->user_id,
            'mesa_id' => $request->mesa_id,
            'comanda' => $request->comanda,
            'caja_id' => $caja->id,
            'fecha' => $request->fecha
        ]);

        // Respuesta en caso de que todo vaya bien.
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Pedido Creado Exitosamente',
            'data'=>$objeto
        ], Response::HTTP_OK);
    }

    public function storeDetalles(Request $request)
    {
        // Validamos los datos
        $data = $request->only('pedido_id', 'producto_id', 'precio', 'cantidad');
        $validator = Validator::make($data, [
            'pedido_id' => 'required',
            'producto_id' => 'required',
            'precio' => 'required',
            'cantidad' => 'required',
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }
        $subtotal=0;
        if($request->precio<0){
            $subtotal=($request->precio * $request->cantidad)*(-1);
        }else{
            $subtotal=($request->precio * $request->cantidad);
        }

            DetallePedido::create([
                'pedido_id' => $request->pedido_id,
                'producto_id' => $request->producto_id,
                'cantidad' => $request->cantidad,
                'precio' => $request->precio,
                'subtotal' => $subtotal
            ]);

            $data=DetallePedido::getDetalleByPedido($request->pedido_id, 1);
        // Respuesta en caso de que todo vaya bien.
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Producto Agregado Exitosamente',
            'data'=>$data
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
        $objeto = $this->model::with('estado')->find($id);
        $data=[];

        // Si la mesa no existe devolvemos error no encontrado
        if (!$objeto) {

            return response()->json([
                'code' => 200,
                'isSuccess' => false,
                'message' => 'Registro no encontrado en la base de datos.'
            ], 404);
        }

        $detalles=DetallePedido::getDetalleByPedido($objeto->id, $objeto->estadopedido_id);
        $data=[
            'pedido'=>$objeto,
            'detalles'=>$detalles
        ];

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $data
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
        $data = $request->only('total', 'cantidad');
        $validator = Validator::make($data, [
            'total' => 'required',
            'cantidad' => 'required',
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Buscamos la mesa
        $objeto = $this->model::findOrFail($id);
        if($objeto){
            $estado=0; // Pendientes
            $detalles=DetallePedido::getDetallePendienteByPedido($id, $estado);
                // Actualizamos la mesa.
                if(count($detalles)>0){
                    return response()->json([
                        'code' => 200,
                        'isSuccess' => false,
                        'data'=>$detalles,
                        'message' => 'Existen Productos Pendientes por Entregar',
                    ], Response::HTTP_OK);
                }else{
                    $objeto->update([
                        'total' => $request->total,
                        'cantidad' => $request->cantidad,
                        'estadopedido_id'=>2
                    ]);

                    // Devolvemos los datos actualizados.
                    return response()->json([
                        'code' => 200,
                        'isSuccess' => true,
                        'message' => 'Pedido Finalizado Exitosamente',
                    ], Response::HTTP_OK);
                }
        }else{
            return response()->json([
                'code' => 200,
                'isSuccess' => false,
                'message' => 'No se encontro el número del Pedido',
            ], Response::HTTP_OK);
        }



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
            'message' => 'Mesa Eliminada Exitosamente'
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
        $objeto->estado = 2;
        $objeto->estadopedido_id = 3;
        $objeto->total=0;
        $objeto->cantidad=0;
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

    public function getPedidoFechaMesa(Request $request)
    {

        $data = $request->only('mesa_id', 'fecha', 'pedido_id', 'estadopedido_id', 'entregado');
        $validator = Validator::make($data, [
            'mesa_id' => 'required',
            'pedido_id' => 'required',
            'fecha' => 'required',
            'estadopedido_id' => 'required',
            'entregado'=>'required'
        ]);
        $mesa_id=$request->mesa_id;
        $fecha=$request->fecha;
        $pedido_id=$request->pedido_id;
        $estado=$request->estadopedido_id;
        $entregado=$request->entregado;
        $objeto = $this->model::getDetallePorMesaYFecha($mesa_id, $fecha, $estado, $entregado, $pedido_id);

        if (count($objeto)>0) {
            return response()->json([
                'code' => 200,
                'data' => $objeto
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'data' => [],
                'message'=>'No se encontrarón Productos por Entregar a la Mesa '
            ], Response::HTTP_OK);
        }
    }

    public function entregarProductosPedido(Request $request)
    {
        $response = DB::transaction(function () use ($request) {
            $data = $request->only('user_id', 'fecha', 'detallepedido_id', 'cantidad', 'tipomovimiento');
            $validator = Validator::make($data, [
                'detallepedido_id' => 'required',
                'cantidad' => 'required',
                'user_id' => 'required',
                'tipomovimiento' => 'required',
            ]);
            $cantidad = $request->cantidad;
            $detallepedido_id = $request->detallepedido_id;
            $user_id = $request->user_id;
            $tipoMovimiento = $request->tipomovimiento;
            $inventario = false;
            $objeto = DetallePedido::findOrFail($detallepedido_id);
            $descripcion = $tipoMovimiento == 1 ? "ENTRADA" : "SALIDA";
            if ($objeto) {
                $productoId = $objeto->producto_id;
                $precioVenta = $objeto->precio;
                $objeto->entregado = 1;
                $objeto->cantidad_entregada = $objeto->cantidad;
                $objeto->save();
                $inventario = MovimientoInventario::modificarStock($productoId, $user_id, $cantidad,
                $precioVenta, 0, $descripcion, $tipoMovimiento);
                if ($inventario) {
                    return [
                        'code' => 200,
                        'isSuccess' => true,
                        'message' => 'Productos Entregados a Mesa Exitosamente '
                    ];
                } else {
                    return [
                        'code' => 200,
                        'isSuccess' => false,
                        'data' => [],
                        'message' => 'Error al Entregar Productos a la Mesa'
                    ];
                }
            } else {
                return [
                    'code' => 200,
                    'data' => [],
                    'message' => 'No se encontraron productos Pedidos a la Mesa '
                ];
            }
        });

        return response()->json($response, Response::HTTP_OK);
    }

    public function getHistorialFechaMesa(Request $request)
    {

        $data = $request->only('mesa_id', 'fecha', 'pedido_id');
        $validator = Validator::make($data, [
            'mesa_id' => 'required',
            'fecha' => 'required',
            'pedido_id' => 'required',
        ]);
        $mesa_id=$request->mesa_id;
        $fecha=$request->fecha;
        $pedido_id=$request->pedido_id;
        $estado=1; // Pedido Abierto
        $entregado=0; // Sin Entregar
        $objeto = $this->model::getHistorialPorMesaYFecha($mesa_id, $fecha, $pedido_id);

        if (count($objeto)>0) {
            return response()->json([
                'code' => 200,
                'data' => $objeto
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'data' => [],
                'message'=>'No se encontrarón Historial de Productos'
            ], Response::HTTP_OK);
        }
    }

    public function getPedidosCerrados(Request $request)
    {
        $data = $request->only('fecha_inicio', 'fecha_final', 'estadopedido_id');
        $validator = Validator::make($data, [
            'fecha_inicio' => 'required|date',
            'fecha_final' => 'required|date',
            'estadopedido_id' => 'required',
        ]);
        $fecha_inicio = \Carbon\Carbon::parse($request->fecha_inicio)->format('Y-m-d');
        $fecha_final = \Carbon\Carbon::parse($request->fecha_final)->format('Y-m-d');
        $estadopedido_id=$request->estadopedido_id;

        $objeto = $this->model::getPedidosCerrados($fecha_inicio, $fecha_final, $estadopedido_id);

        if (count($objeto)>0) {
            return response()->json([
                'code' => 200,
                'data' => $objeto
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'data' => [],
                'message'=>'No se encontrarón registros para mostrar'
            ], Response::HTTP_OK);
        }
    }

    public function filter(Request $request)
    {
        $fecha_inicio = !empty($request->fecha_inicio) ? \Carbon\Carbon::parse($request->fecha_inicio)->format('Y-m-d') : null;
        $fecha_final = !empty($request->fecha_fin) ? \Carbon\Carbon::parse($request->fecha_fin)->format('Y-m-d') : null;
        // Listamos todas las mesas
        $objeto = $this->model::getFilter($fecha_inicio,
            $fecha_final, $request->user_id, $request->estadopedido_id);
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

    public function actualizarMesa(Request $request)
    {
        // Listamos todas las mesas
        $objeto = $this->model::find($request->pedido_id);
        if ($objeto) {
            $comanda=$request->comanda;
            $pedido=Pedido::where('comanda', $comanda)->first();
            if(!empty($pedido) && $pedido->id != $objeto->id){
                return response()->json([
                    'code' => 200,
                    'isSuccess' => false,
                    'data' => [],
                    'message' =>"Comanda N° ".$comanda." ya se encuentra asignada a este Pedido ".$pedido->id
                ], Response::HTTP_OK);
            }
            $objeto->mesa_id=$request->mesa_id;
            $objeto->comanda=$request->comanda;
            $objeto->save();
            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'data' => $objeto,
                'message' =>"Cambio de Mesa Realizado Exitosamente",
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'isSuccess' => false,
                'data' => [],
                'message' =>"Error al realizar cambio de mesa",
            ], Response::HTTP_OK);
        }
    }

    public function entregarProductosPedidoTodos(Request $request)
    {
        $response = DB::transaction(function () use ($request) {
            $data = $request->only('user_id', 'pedido_id');
            $validator = Validator::make($data, [
                'user_id' => 'required',
                'pedido_id' => 'required',
            ]);
            $user_id = $request->user_id;
            $pedido_id = $request->pedido_id;
            $inventario = false;
            //Obtener los productos que no han sido entregados
            $detallesPedido = DetallePedido::where('pedido_id', $pedido_id)
            ->where('entregado', 0)->get();
            $descripcion =  "SALIDA";
            $tipoMovimiento = 2;
            $totalProductos=0;
            if (count($detallesPedido)>0) {
                foreach ($detallesPedido as $objeto) {
                    $detalle=DetallePedido::find($objeto->id);
                    $productoId = $objeto->producto_id;
                    $precioVenta = $objeto->precio;
                    $detalle->entregado = 1;
                    $detalle->cantidad_entregada = $objeto->cantidad;
                    $detalle->save();
                    $inventario = MovimientoInventario::modificarStock($productoId, $user_id, $objeto->cantidad,
                    $precioVenta, 0, $descripcion, $tipoMovimiento);
                    $totalProductos++;
                }
                if ($totalProductos>0) {
                    return [
                        'code' => 200,
                        'isSuccess' => true,
                        'message' => 'Productos Entregados a Mesa Exitosamente '
                    ];
                } else {
                    return [
                        'code' => 200,
                        'isSuccess' => false,
                        'data' => [],
                        'message' => 'Error al Entregar Productos a la Mesa'
                    ];
                }



            } else {
                return [
                    'code' => 200,
                    'data' => [],
                    'message' => 'No se encontraron productos Pedidos a la Mesa '
                ];
            }
        });

        return response()->json($response, Response::HTTP_OK);
    }

}
