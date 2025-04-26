<?php

namespace App\Http\Controllers\Modulo\Controlcalidad\Procesos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Home;
use App\Models\Modulo\Controlcalidad\Procesos\Analisistendencia;
use App\FormatoEmpresa;
use Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class AnalisistendenciaController extends Controller {

    private $ruta = 'controlcalidad/procesos/analisistendencia/ver';

    public function __construct(Request $request) {
        Session::flush();
        $this->iduser = $request->cookie('iduser');

        $home = new Home();
        $result_02 = $home->consultarPermisoOpcionesMenuV2(1, 'INT', $this->ruta);
        if (count($result_02['registro']) > 0) {
            $this->empresaFormato = new FormatoEmpresa($result_02['registro'][0]->opcionId);
            $this->empresaFormato->ini();
        }
    }

    public function buildDataRevisor(){
        $usuarioid = $this->iduser;
        $estadosCodigo = array();
        $ordenInstancias = array();
        $ary_esp = array();
        $ary_resultado = array();
        
        // Simplificado para la implementación básica
        $ary_esp[0] = array('value'=>'SI','text'=>'SI');
        $ary_esp[1] = array('value'=>'NO','text'=>'NO');

        $ary_resultado['estadosCodigo'] = $estadosCodigo;
        $ary_resultado['opcEsp'] = $ary_esp;
        $ary_resultado['ordenInstancias'] = $ordenInstancias;
        
        return $ary_resultado;
    }

    public function ver() {
        $home = new Home(); 
        $usuarioid = $this->iduser;       
        $result_02 = $home->consultarPermisoOpcionesMenuV2($this->iduser, 'INT', Route::getCurrentRoute()->uri);

        if (count($result_02['registro']) > 0) {
                     
            $dataRevisor = $this->buildDataRevisor();
            $estadosCodigo = $dataRevisor['estadosCodigo'];
            $opcEsp = $dataRevisor['opcEsp'];
            $ordenIns = $dataRevisor['ordenInstancias'];
    
            $result_01 = $home->consultarArbolMenu($this->iduser, 'INT');
            $data['menu_opcion'] = $result_01['registro'];
            $data['opcion_elegida'] = $result_02['registro'][0]->opcionId;
            $data['titulo'] = $result_02['registro'][0]->nombreOpcion;
            $operaciones = $home->consultarOperaciones_new($this->iduser);    
            $operaciones_new = array_values($operaciones['operaciones']);

            $estadosFiltros = [];
            
            // Obtener productos para el combo
            $model = new Analisistendencia();
            $productos = $model->consultarProductos();
            
            $data['operaciones'] = $operaciones_new;
            $data['rol'] = $operaciones['rol'];
            $data['usuarioid'] = $usuarioid;
            $data['estadosCodigo'] = $estadosCodigo;
            $data['estadosFiltros'] = $estadosFiltros;
            $data['opcEsp'] = $opcEsp;
            $data['ordenIns'] = $ordenIns;
            $data['productos'] = $productos['registro'] ?? [];
            $ambiente = config('app.ambiente');

            return view('modulo.controlcalidad.procesos.analisistendencia.v_analisistendencia', ['ambiente' => $ambiente], ['usuarioid' => $usuarioid])->with($data);
        } else {
            return redirect('/home');
        }
    }

    public function consultar(Request $request) {
        $_acc = $request->input('_acc');
        $model = new Analisistendencia();
        
        switch ($_acc) {
            case 'listarPrincipal':
                // Consulta de la tabla principal
                $info[0] = 1; // Tipo de consulta
                $info[1] = is_null($request->input('producto')) ? '' : trim($request->input('producto'));
                $info[2] = is_null($request->input('fechadesde')) ? '' : trim($request->input('fechadesde'));
                $info[3] = is_null($request->input('fechafin')) ? '' : trim($request->input('fechafin'));
                $info[4] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                $info[5] = is_null($request->input('rows')) ? '50' : trim($request->input('rows'));

                $data = $model->consultarTablaInicial($info);
                
                if($data['valor'] == 0) {
                    Log::error('Error al consultar tabla inicial: ' . ($data['mensaje'] ?? 'Error desconocido'));
                    return response()->json(['error' => 'Error al cargar datos', 'details' => $data['mensaje'] ?? '']);
                }
                
                $result["total"] = isset($data['registro'][0]->total) ? $data['registro'][0]->total : 0;
                $result["rows"] = $data['registro'];

                return response()->json($result);
                break;

                case 'listarDetalles':
                    // dd('holis');
                    // Consulta de los detalles para un producto específico
                    $info[0] = 2; // Tipo de consulta
                    $info[1] = is_null($request->input('cod_producto_mae')) ? '' : trim($request->input('cod_producto_mae'));
                    $info[2] = is_null($request->input('fechadesde')) ? '' : trim($request->input('fechadesde'));
                    $info[3] = is_null($request->input('fechafin')) ? '' : trim($request->input('fechafin'));
                    $info[4] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                    $info[5] = is_null($request->input('rows')) ? '10' : trim($request->input('rows'));
                    
                    // Log para diagnóstico
                    Log::info('Consulta de detalles', [
                        'cod_producto_mae' => $info[1],
                        'fechadesde' => $info[2],
                        'fechafin' => $info[3],
                    ]);
                    
                    $data = $model->consultarDetallesInspeccion($info);
                     // Esto mostrará la respuesta
    // dump('Datos recibidos:', $data);
    //                 dd($data);
                    
                    // Log de respuesta
                    Log::info('Respuesta de consulta de detalles', [
                        'status' => $data['valor'],
                        'count' => isset($data['registro']) ? count($data['registro']) : 0,
                        'error' => isset($data['mensaje']) ? $data['mensaje'] : null,
                    ]);
                    
                    if($data['valor'] == 0) {
                        Log::error('Error al consultar detalles: ' . ($data['mensaje'] ?? 'Error desconocido'));
                        return response()->json(['error' => 'Error al cargar detalles', 'details' => $data['mensaje'] ?? '']);
                    }
                    
                    $result["total"] = isset($data['registro'][0]->total) ? $data['registro'][0]->total : 0;
                    $result["rows"] = $data['registro'] ?? [];
                    
                    return response()->json($result);
                    break;

                case 'listarLotesAprobados':
                    try {
                        // Log detallado de los parámetros recibidos
                        Log::channel('daily')->info('Parámetros recibidos para lotes aprobados', [
                            'cod_producto_mae' => $request->input('cod_producto_mae'),
                            'cod_insp_mae' => $request->input('cod_insp_mae'),
                            'fechadesde' => $request->input('fechadesde'),
                            'fechafin' => $request->input('fechafin'),
                            'page' => $request->input('page'),
                            'rows' => $request->input('rows')
                        ]);
                
                        // Preparar los parámetros
                        $info[0] = 3; // Tipo de consulta para lotes aprobados
                        $info[1] = is_null($request->input('cod_producto_mae')) ? '' : trim($request->input('cod_producto_mae'));
                        $info[2] = is_null($request->input('cod_insp_mae')) ? '' : trim($request->input('cod_insp_mae'));
                        $info[3] = is_null($request->input('fechadesde')) ? null : trim($request->input('fechadesde'));
                        $info[4] = is_null($request->input('fechafin')) ? null : trim($request->input('fechafin'));
                        $info[5] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                        $info[6] = is_null($request->input('rows')) ? '10' : trim($request->input('rows'));
                
                        $data = $model->consultarLotesAprobados($info);
                        
                        // Log del resultado de la consulta
                        Log::channel('daily')->info('Resultado de consultarLotesAprobados', [
                            'valor' => $data['valor'],
                            'mensaje' => $data['mensaje'] ?? '',
                            'registros' => count($data['registro'] ?? [])
                        ]);
                
                        if($data['valor'] == 0) {
                            return response()->json([
                                'total' => 0, 
                                'rows' => [], 
                                'message' => $data['mensaje'] ?? 'No se encontraron registros'
                            ], 200);
                        }
                        
                        $result["total"] = isset($data['registro'][0]->total_registros) ? $data['registro'][0]->total_registros : 0;
                        $result["rows"] = $data['registro'] ?? [];
                
                        return response()->json($result);
                    } catch (\Exception $e) {
                        // Log de la excepción completa
                        Log::channel('daily')->error('Excepción en listarLotesAprobados', [
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                
                        return response()->json([
                            'total' => 0, 
                            'rows' => [], 
                            'message' => 'Error interno: ' . $e->getMessage()
                        ], 200);
                    }
                    break;

                        // Agregar este caso en el método consultar del controlador AnalisistendenciaController.php
                case 'listarLotesDesaprobados':
                    try {
                        // Log detallado de los parámetros recibidos
                        Log::channel('daily')->info('Parámetros recibidos para lotes desaprobados', [
                            'cod_producto_mae' => $request->input('cod_producto_mae'),
                            'cod_insp_mae' => $request->input('cod_insp_mae'),
                            'fechadesde' => $request->input('fechadesde'),
                            'fechafin' => $request->input('fechafin'),
                            'page' => $request->input('page'),
                            'rows' => $request->input('rows')
                        ]);

                        // Preparar los parámetros
                        $info[0] = 4; // Tipo de consulta para lotes desaprobados
                        $info[1] = is_null($request->input('cod_producto_mae')) ? '' : trim($request->input('cod_producto_mae'));
                        $info[2] = is_null($request->input('cod_insp_mae')) ? '' : trim($request->input('cod_insp_mae'));
                        $info[3] = is_null($request->input('fechadesde')) ? null : trim($request->input('fechadesde'));
                        $info[4] = is_null($request->input('fechafin')) ? null : trim($request->input('fechafin'));
                        $info[5] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                        $info[6] = is_null($request->input('rows')) ? '10' : trim($request->input('rows'));

                        $data = $model->consultarLotesDesaprobados($info);
                        
                        // Log del resultado de la consulta
                        Log::channel('daily')->info('Resultado de consultarLotesDesaprobados', [
                            'valor' => $data['valor'],
                            'mensaje' => $data['mensaje'],
                            'registros' => count($data['registro'] ?? [])
                        ]);

                        if($data['valor'] == 0) {
                            return response()->json([
                                'total' => 0, 
                                'rows' => [], 
                                'message' => $data['mensaje']
                            ], 200);
                        }
                        
                        $result["total"] = isset($data['registro'][0]->total_registros) ? $data['registro'][0]->total_registros : 0;
                        $result["rows"] = $data['registro'] ?? [];

                        return response()->json($result);
                    } catch (\Exception $e) {
                        // Log de la excepción completa
                        Log::channel('daily')->error('Excepción en listarLotesDesaprobados', [
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);

                        return response()->json([
                            'total' => 0, 
                            'rows' => [], 
                            'message' => 'Error interno: ' . $e->getMessage()
                        ], 200);
                    }
                    break;
                        
                case 'obtenerInfoDetalle':
                    try {
                        // Obtener los parámetros necesarios
                        $cod_producto_mae = $request->input('cod_producto_mae');
                        $cod_insp_mae = $request->input('cod_insp_mae');
                        
                        // Llamar al modelo para obtener los datos
                        $infoDetalle = $model->obtenerInformacionDetalle($cod_producto_mae, $cod_insp_mae);
                        
                        if($infoDetalle['valor'] == 0) {
                            return response()->json([
                                'error' => true, 
                                'mensaje' => $infoDetalle['mensaje'] 
                            ]);
                        }
                        
                        // Devolver la información encontrada
                        return response()->json([
                            'nombreProducto' => isset($infoDetalle['registro'][0]->nom_producto_mae) ? $infoDetalle['registro'][0]->nom_producto_mae : '',
                            'nombreInspeccion' => isset($infoDetalle['registro'][0]->nom_insp_mae) ? $infoDetalle['registro'][0]->nom_insp_mae : '',
                        ]);
                    } catch (\Exception $e) {
                        Log::channel('daily')->error('Error en obtenerInfoDetalle', [
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        return response()->json([
                            'error' => true, 
                            'mensaje' => 'Error interno: ' . $e->getMessage()
                        ]);
                    }
                    break;
                
            default:
                return response()->json(['error' => 'Acción no reconocida']);
                break;
        }
    }

    public function obtenerDatosGrafico(Request $request)
    {
        $model = new Analisistendencia();

        try {
            $cod_producto_mae = $request->input('cod_producto_mae');
            $cod_insp = $request->input('cod_insp');
            
            if (empty($cod_producto_mae)) {
                throw new \Exception('Código de producto no proporcionado');
            }
            
            if (empty($cod_insp)) {
                throw new \Exception('Código de inspección no proporcionado');
            }
            
            $fecha_inicio = null; // Puedes agregar estos parámetros si necesitas filtrar por fechas
            $fecha_fin = null;
            
            $datos = $model->obtenerDatosGrafico($cod_producto_mae, $cod_insp, $fecha_inicio, $fecha_fin);
            
            return [
                'valor' => count($datos) ? 1 : 0,
                'mensaje' => count($datos) ? 'Consulta exitosa' : 'No se encontraron registros',
                'datos' => $datos
            ];
        } catch (\Exception $e) {
            Log::channel('daily')->error('Error en obtenerDatosGrafico', [
                'mensaje' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'valor' => 0,
                'mensaje' => 'Error en la consulta: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }
}