<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class ProductoController extends Controller
{
    protected $model;

    public function __construct()
    {
        $this->model = Producto::class;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Listamos todos los productos
        $productos = $this->model::getAll();
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $productos
        ], Response::HTTP_OK);
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
        $data = $request->only('categoria_id', 'user_id', 'nombre', 'descripcion', 'imagen', 'precio', 'stock_actual');
        $validator = Validator::make($data, [
            'categoria_id' => 'required|exists:categorias,id',
            'nombre' => 'required|max:200|string',
            'precio' => 'required|numeric',
            'user_id' => 'required',
            'stock_actual' => 'required',
            //'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validación de imagen
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }


        $rutaimagen = null;
        // Verificamos si se ha subido una imagen
        if ($request->hasFile('imagen')) {
            $imagen = $request->file('imagen');
            $rutaimagen = 'productos/' . time() . '.' . $imagen->getClientOriginalExtension();
            $imagen->move(public_path('productos'), $rutaimagen); // Guardamos la imagen
        }
        $inventario=false;
        DB::transaction(function () use ($request, $rutaimagen) {
            // Creamos el producto en la BD
            $producto = $this->model::create([
                'categoria_id' => $request->categoria_id,
                'nombre' => strtoupper($request->nombre),
                'descripcion' => $request->descripcion,
                'rutaimagen' => $rutaimagen, // Usamos la ruta de la imagen guardada
                'precio' => $request->precio,
                'stock_actual' => $request->stock_actual,
            ]);
            $productoId = $producto->id;
            $cantidad = $request->stock_actual;
            $user_id = $request->user_id;
            $precioVenta = $request->precio;
            $descripcion = "ENTRADA INICIAL";
            $tipoMovimiento = 1; // ENTRADA

            $inventario = MovimientoInventario::modificarStock($productoId, $user_id, $cantidad,
                    $precioVenta, 0, $descripcion, $tipoMovimiento);
        });

        // Respuesta en caso de que todo vaya bien

            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'message' => 'Producto Creado Exitosamente',
            ], Response::HTTP_OK);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Buscamos el producto
        $producto = $this->model::find($id);

        // Si el producto no existe devolvemos error no encontrado
        if (!$producto) {
            return response()->json([
                'code' => 404,
                'isSuccess' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $producto
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {



        // Validamos los datos
        $data = $request->only('categoria_id', 'nombre', 'descripcion', 'imagen', );
        $validator = Validator::make($data, [
            'categoria_id' => 'required|exists:categorias,id',
            'nombre' => 'required|max:200|string',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validación de imagen
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        $rutaimagen = null;
        // Verificamos si se ha subido una imagen
        if ($request->hasFile('imagen')) {
            $imagen = $request->file('imagen');
            $rutaimagen = 'productos/' . time() . '.' . $imagen->getClientOriginalExtension();
            $imagen->move(public_path('productos'), $rutaimagen); // Guardamos la imagen
        }
        // Buscamos el producto
        $producto = $this->model::findOrFail($id);

        // Actualizamos el producto.
        $producto->update([
            'categoria_id' => $request->categoria_id,
            'nombre' => strtoupper($request->nombre),
            'descripcion' => $request->descripcion,
            'rutaimagen' => $rutaimagen
        ]);

        // Respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Producto Actualizado Exitosamente',
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Buscamos el producto
        $producto = $this->model::findOrFail($id);

        // Eliminamos el producto
        $producto->delete();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Producto Eliminado Exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Cambiar el estado de un producto (Activo/Inactivo)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
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

        // Buscamos el producto
        $producto = $this->model::findOrFail($request->id);

        // Cambiamos el estado
        $producto->estado = ($producto->estado == 1) ? 2 : 1;
        $producto->save();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Estado del Producto Actualizado Exitosamente',
        ], Response::HTTP_OK);
    }

    /**
     * Listar todos los productos activos.
     *
     * @return \Illuminate\Http\Response
     */
    public function activos()
    {
        // Listamos todos los registros activos
        $productos = $this->model::getActivos();
        if ($productos) {
            return response()->json([
                'code' => 200,
                'data' => $productos
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'data' => []
            ], Response::HTTP_OK);
        }
    }

    public function movimientosInventario(Request $request)
    {
        // Validación de datos
        $data = $request->only('producto_id');
        $validator = Validator::make($data, [
            'producto_id' => 'required'
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Buscamos el producto
        $data = MovimientoInventario::getMovimientosPorProducto($request->producto_id);
        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => '',
            'data'=>$data
        ], Response::HTTP_OK);
    }

    public function postDetalle(Request $request){

        $data = $request->only('producto_id', 'cantidad', 'precio_venta', 'user_id' );
        $validator = Validator::make($data, [
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required',
            'precio_venta' => 'required'
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }
        $producto = $this->model::findOrFail($request->producto_id);
        if($producto){
            // Actualizamos el producto.

            DB::transaction(function () use ($request, $producto) {
                $producto->update([
                    'stock_actual' => $request->cantidad,
                    'precio' => $request->precio_venta,
                ]);

                $movimiento = MovimientoInventario::create([
                    'producto_id' => $request->producto_id,
                    'user_id' => $request->user_id,
                    'tipo' => 3,
                    'cantidad' => $request->cantidad,
                    'precio_venta' => $request->precio_venta,
                    'saldo' => $request->cantidad,
                    'fecha' => now(),
                    'descripcion' => 'ACTUALIZACION DE STOCK Y PRECIO',
                ]);
            });
            // Respuesta
            return response()->json([
                'code' => 200,
                'isSuccess' => true,
                'message' => 'Stock Actualizado Exitosamente',
            ], Response::HTTP_OK);
        }else{
            return response()->json([
                'code' => 404,
                'isSuccess' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

    }
}
