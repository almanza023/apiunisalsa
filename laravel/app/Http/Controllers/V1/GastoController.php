<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\AperturaCaja;
use App\Models\Gasto;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class GastoController extends Controller
{
    protected $model;

    public function __construct()
    {
        $this->model = Gasto::class;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Listamos todos los gastos
        $gastos = $this->model::getAll();
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $gastos
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
        $data = $request->only('tipogasto_id', 'fecha', 'descripcion', 'valortotal');
        $validator = Validator::make($data, [
            'tipogasto_id' => 'required|exists:tipo_gastos,id',
            'fecha' => 'required|date',
            'valortotal' => 'required|numeric|min:0',
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

        $validarExiste=$this->model::where('caja_id', $caja->id)
        ->where('tipogasto_id', $request->tipogasto_id)
        ->where('estado', 1)->count();

        if($validarExiste > 0){
            return response()->json([
                'code' => 400,
                'isSuccess' => false,
                'message' => 'Ya existe un registro de la NOMINA para el día',
                'data'=>[]
            ], Response::HTTP_OK);
        }

        // Creamos el gasto en la BD
        $gasto = $this->model::create([
            'tipogasto_id' => $request->tipogasto_id,
            'fecha' => $request->fecha,
            'caja_id' => $caja->id,
            'descripcion' => $request->descripcion,
            'valortotal' => $request->valortotal,
        ]);

        // Respuesta en caso de que todo vaya bien
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Gasto Creado Exitosamente',
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
        // Buscamos el gasto
        $gasto = $this->model::find($id);

        // Si el gasto no existe devolvemos error no encontrado
        if (!$gasto) {
            return response()->json([
                'code' => 404,
                'isSuccess' => false,
                'message' => 'Gasto no encontrado'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'data' => $gasto
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
        // Validación de datos
        $data = $request->only('tipogasto_id', 'fecha', 'descripcion', 'valortotal');
        $validator = Validator::make($data, [
            'tipogasto_id' => 'required|exists:tipo_gastos,id',
            'fecha' => 'required|date',
            'valortotal' => 'required|numeric|min:0',
        ]);

        // Si falla la validación error.
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // Buscamos el gasto
        $gasto = $this->model::findOrFail($id);

        // Actualizamos el gasto.
        $gasto->update([
            'tipogasto_id' => $request->tipogasto_id,
            'fecha' => $request->fecha,
            'descripcion' => $request->descripcion,
            'valortotal' => $request->valortotal,
        ]);

        // Respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Gasto Actualizado Exitosamente',
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
        // Buscamos el gasto
        $gasto = $this->model::findOrFail($id);

        // Eliminamos el gasto
        $gasto->delete();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Gasto Eliminado Exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Cambiar el estado de un gasto (Activo/Inactivo)
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

        // Buscamos el gasto
        $gasto = $this->model::findOrFail($request->id);

        // Cambiamos el estado
        $gasto->estado = ($gasto->estado == 1) ? 2 : 1;
        $gasto->save();

        // Devolvemos la respuesta
        return response()->json([
            'code' => 200,
            'isSuccess' => true,
            'message' => 'Estado del Gasto Actualizado Exitosamente',
        ], Response::HTTP_OK);
    }

    /**
     * Listar todos los gastos activos.
     *
     * @return \Illuminate\Http\Response
     */
    public function activos()
    {
        // Listamos todos los registros activos
        $gastos = $this->model::where('estado', 1)->get();
        if ($gastos) {
            return response()->json([
                'code' => 200,
                'data' => $gastos
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'data' => []
            ], Response::HTTP_OK);
        }
    }

    /**
     * Listar todos los gastos por tipo de gasto.
     *
     * @param  int  $tipogasto_id
     * @return \Illuminate\Http\Response
     */
    public function gastosPorTipo($tipogasto_id)
    {
        // Listamos todos los gastos del tipo solicitado
        $gastos = $this->model::where('tipogasto_id', $tipogasto_id)->get();
        if ($gastos) {
            return response()->json([
                'code' => 200,
                'data' => $gastos
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'data' => []
            ], Response::HTTP_OK);
        }
    }

    public function getFilterFechas(Request $request)
    {
        $data = $request->only('fechaInicio', 'fechaFinal');
        $validator = Validator::make($data, [
            'fechaInicio' => 'required|date',
            'fechaFinal' => 'required|date',
        ]);
        // Listamos todos los registros activos
        $data = $this->model::getByDateRange($request->fechaInicio, $request->fechaFinal);
        if ($data) {
            return response()->json([
                'code' => 200,
                'data' => $data
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'code' => 200,
                'data' => []
            ], Response::HTTP_OK);
        }
    }
}
