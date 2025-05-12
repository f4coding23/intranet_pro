<?php
namespace App\Http\Controllers\Modulo\Comercial\Procesos;

// Requerido
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;  
use App\Models\Home;
use App\Models\Modulo\Comercial\Procesos\Instituciones;
use App\Models\SAP;

/* PROCESOS */
use App\Models\Modulo\Comercial\Procesos_I_CAB;
use App\Models\Modulo\Comercial\Procesos_I_CAB_WEB;
use App\Models\Modulo\Comercial\Procesos_I_DET;
use App\Models\Modulo\Comercial\Procesos_I_DET_WEB;
/* ENTREGAS */
use App\Models\Modulo\Comercial\Entregas_I_CAB;
use App\Models\Modulo\Comercial\Entregas_I_DET;
/* PEDIDOS */
use App\Models\Modulo\Comercial\Pedidos_I_CAB;
use App\Models\Modulo\Comercial\Pedidos_I_DET;
/* PICKING */
use App\Models\Modulo\Comercial\Picking_I_CAB;
use App\Models\Modulo\Comercial\Picking_I_DET;
/* FACTURA */
use App\Models\Modulo\Comercial\Factura_I_CAB;
use App\Models\Modulo\Comercial\Factura_I_DET;
/* CUENTAS X COBRAR */
use App\Models\Modulo\Comercial\Cuentas_x_cobrar;

 // Excel
use Excel;
use App\Models\ExportarArray;
use App\Models\ExportarVista;
use App\Models\ExportarMultiple;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use Storage;
use DB;

// Mail
use Illuminate\Support\Facades\View;
use App\Mails\EnviarMail;
use Illuminate\Support\Facades\Mail;

// Rutas
use App\FormatoEmpresa;
use Route;
use Str;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

// Formateo Fechas SAP
use Carbon\Carbon;
use DateTime;

use Cache;

//use Artisaninweb\SoapWrapper\SoapWrapper;

class InstitucionesController extends Controller
{
    private $ruta = 'comercial/procesos/instituciones/ver';
    public function __construct(Request $request = null){
        Session::flush();
        $this->iduser = ($request) ? $request->cookie('iduser') : 1;       
        
        $home = new Home();
        $result_02 = $home->consultarPermisoOpcionesMenuV2(1,'INT',$this->ruta);
        if(count($result_02['registro'])>0){
            $this->empresaFormato = new FormatoEmpresa($result_02['registro'][0]->opcionId);
            $this->empresaFormato->ini();
        }
    }

    public function ver()
    {
        $home = new Home();

        $result_02 = $home->consultarPermisoOpcionesMenuV2($this->iduser,'INT',Route::getCurrentRoute()->uri);
        if(count($result_02['registro'])>0){
            $usuarioid = $this->iduser;
            $result_01 = $home->consultarArbolMenu($this->iduser,'INT');
 	        $data['menu_opcion'] = $result_01['registro'];
            $data['opcion_elegida'] = $result_02['registro'][0]->opcionId;
            $data['titulo'] = $result_02['registro'][0]->nombreOpcion;
            $data['nro_proceso_prueba'] = env('NRO_PROCESO_PRUEBA','');
            $ambiente = config('app.ambiente');

        	return view('modulo.comercial.procesos.instituciones.v_instituciones',['ambiente' => $ambiente],['usuarioid' => $usuarioid])->with($data);
        }else{
        	return redirect('/home');
        }
    }

    public function consultar(Request $request)
    {
        $_acc = $request->input('_acc');
        $model   = new Instituciones();
        $usuarioid = $this->iduser;
        $ip_maq = $request->ip();
        switch ($_acc) {             
            case 'listarPrincipalProcesoCab':
                $array_doc = [];
                $txt_documento= is_null($request->input('txt_documento')) ? '' : trim($request->input('txt_documento'));
                $txt_entrega= is_null($request->input('txt_entrega')) ? '' : trim($request->input('txt_entrega'));
                $txt_pedido= is_null($request->input('txt_pedido')) ? '' : trim($request->input('txt_pedido'));
                $txt_sctf= is_null($request->input('txt_sctf')) ? '' : trim($request->input('txt_sctf'));
                $txt_ctf= is_null($request->input('txt_ctf')) ? '' : trim($request->input('txt_ctf'));
                $fechadesde= is_null($request->input('fechadesde')) ? '' : trim($request->input('fechadesde'));
                $fechadesde = $this->empresaFormato->formatDateIn($fechadesde);
                $fechafin = is_null($request->input('fechafin')) ? '' : trim($request->input('fechafin'));
                $fechafin= $this->empresaFormato->formatDateIn($fechafin);
                /* FECHAS PARA ACTUALIZAR SAP (SOLO 1 MES) */
                $fechadesdeSAP = Carbon::today()->format('Y-m-d');
                $fechafinSAP = Carbon::today()->subMonth()->format('Y-m-d');
                /* FILTRO DE 41* */
                if($txt_documento != ''){
                    $array_doc[] = $txt_documento;
                }
                /* FILTRO DE FIANZA */
                if($txt_ctf != '' || $txt_sctf != ''){
                    $info[0] = 18;
                    $info[1] = $txt_ctf;
                    $info[2] = $txt_entrega;
                    $info[3] = $txt_sctf;
                    $info[4] = $txt_pedido;

                    $data = $model->consultarData($info);unset($info);
                    $data = $data['registro'];
                    if(count($data) > 0){
                        foreach($data as $value){
                            $array_doc[] = $value->nro_proceso;
                        }
                    }
                }
                $array_doc = array_unique($array_doc);
                // CONSUMO RFC - 41xxx
                /* if($usuarioid == 16){
                    dd($array_doc);
                } */
               
                $rpta_rfc = $this->getPROCESOS_RFC($fechadesdeSAP, $fechafinSAP,$array_doc); 
                $rpta_procesado = (count($rpta_rfc['rows_cab']) > 0 ) ? $this->procesarPROCESOS_WEB($rpta_rfc['rows_cab'], $rpta_rfc['rows_det'],$rpta_rfc['fechadesde'],$rpta_rfc['fechafin']) : [];  
                // dd($rpta_procesado);
                /* $rpta_rfc = $this->getPROCESOS_RFC($fechadesde, $fechafin,$array_doc);    
                $rpta_procesado = $this->procesarPROCESOS_WEB($rpta_rfc['rows_cab'], $rpta_rfc['rows_det'],$rpta_rfc['fechadesde'],$rpta_rfc['fechafin']);  
                $result['o_nres'] = $rpta_procesado['o_nres'];
                $result['o_msj'] = $rpta_procesado['o_msj']; */
                // CONSULTA A BASE DE DATOS
                //if($rpta_procesado['o_nres'] == 1){
                    $info[0] = 1;
                    // PROCESO
                    $info[1] = (count($array_doc)>0) ? '' : $fechadesde;
                    $info[2] = (count($array_doc)>0) ? '' : $fechafin;
                    $info[3] = is_null($request->input('txt_documento')) ? '' : trim($request->input('txt_documento'));
                    $info[4] = is_null($request->input('codigo_org_ventas')) ? '' : trim($request->input('codigo_org_ventas'));
                    $info[5] = is_null($request->input('codigo_cliente')) ? '' : trim($request->input('codigo_cliente'));
                    $info[6] = is_null($request->input('codigo_mot_pedido')) ? '' : trim($request->input('codigo_mot_pedido'));
                    $info[7] = is_null($request->input('codigo_canal_dist')) ? '' : trim($request->input('codigo_canal_dist'));
                    $info[8] = is_null($request->input('codigo_region')) ? '' : trim($request->input('codigo_region'));
                    $info[11] = is_null($request->input('codigo_producto')) ? '' : trim($request->input('codigo_producto'));
                    $info[12] = is_null($request->input('codigo_grupo_art')) ? '' : trim($request->input('codigo_grupo_art'));
                    $info[13] = is_null($request->input('codigo_motivo_rechazo')) ? '' : trim($request->input('codigo_motivo_rechazo'));
                    $info[23] = is_null($request->input('txt_denominacion')) ? '' : trim($request->input('txt_denominacion'));
                    $info[21] = is_null($request->input('flg_8uit')) ? '' : trim($request->input('flg_8uit'));
                    // ENTREGA
                    $info[14] = is_null($request->input('txt_entrega')) ? '' : trim($request->input('txt_entrega'));
                    $info[15] = is_null($request->input('codigo_dst_mercancia')) ? '' : trim($request->input('codigo_dst_mercancia'));
                    $info[16] = is_null($request->input('txt_contrato')) ? '' : trim($request->input('txt_contrato'));
                    // PEDIDO
                    $info[17] = is_null($request->input('txt_pedido')) ? '' : trim($request->input('txt_pedido'));
                    $info[18] = is_null($request->input('txt_gr')) ? '' : trim($request->input('txt_gr'));
                    $info[19] = is_null($request->input('txt_factura')) ? '' : trim($request->input('txt_factura'));
                    // CARTA FIANZA
                    $info[20] = is_null($request->input('txt_ctf')) ? '' : trim($request->input('txt_ctf'));
                    $info[24] = is_null($request->input('txt_sctf')) ? '' : trim($request->input('txt_sctf'));
                    $info[26] = is_null($request->input('grupo_cliente')) ? '' : trim($request->input('grupo_cliente'));
                    $info[27] = is_null($request->input('tipo_venta')) ? '' : trim($request->input('tipo_venta'));
                    // INDICADORES
                    $info[28] = is_null($request->input('facturacion')) ? '' : trim($request->input('facturacion'));
                    $info[29] = is_null($request->input('con_15')) ? '' : trim($request->input('con_15'));
                    $info[30] = '';
                    $info[31] = is_null($request->input('deuda')) ? '' : trim($request->input('deuda'));
                    $info[32] = is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                    $info[33] = is_null($request->input('flg_ctf')) ? '' : trim($request->input('flg_ctf'));
                    $info[34] = is_null($request->input('flg_cnt')) ? '' : trim($request->input('flg_cnt'));

                    $info[9] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                    $info[10] = is_null($request->input('rows')) ? '50' : trim($request->input('rows'));
                    //dd($info);

                    $data   = $model->consultarData($info);unset($info);
                    $cabecera = $data['registro'];

                    $result["total"] = isset($data['registro'][0]->total) ?  $data['registro'][0]->total : 0;
                    $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);
                    $result['cabecera'] = $cabecera[0] ?? [];
                //}

                return response()->json($result);

                break; 

                case 'listarSeguimientoIntegral':
                    try {
                        $proceso = empty($request->input('proceso')) ? null : trim($request->input('proceso'));
                        $entrega = empty($request->input('entrega')) ? null : trim($request->input('entrega'));
                        $pedido = empty($request->input('pedido')) ? null : trim($request->input('pedido'));
                        $numclienteoc = empty($request->input('numclienteoc')) ? null : trim($request->input('numclienteoc'));
                        $picking = empty($request->input('picking')) ? null : trim($request->input('picking'));
                        $factura = empty($request->input('factura')) ? null : trim($request->input('factura'));
                        $fecha_desde = empty($request->input('fecha_desde')) ? null : $this->empresaFormato->formatDateIn(trim($request->input('fecha_desde')));
                        $fecha_hasta = empty($request->input('fecha_hasta')) ? null : $this->empresaFormato->formatDateIn(trim($request->input('fecha_hasta')));
                        // Nuevos parámetros
                        $codigo_producto = empty($request->input('codigo_producto')) ? null : trim($request->input('codigo_producto'));
                        $desc_producto = empty($request->input('desc_producto')) ? null : trim($request->input('desc_producto'));
                        $contrato = empty($request->input('contrato')) ? null : trim($request->input('contrato'));
                        $page = empty($request->input('page')) ? 1 : (int)trim($request->input('page'));
                        $rows = empty($request->input('rows')) ? 50 : (int)trim($request->input('rows'));
                
                        // Capturar los parámetros de ordenamiento
                        $sort = empty($request->input('sort')) ? 'FechaDocumentoPedido' : trim($request->input('sort'));
                        $order = empty($request->input('order')) ? 'desc' : strtolower(trim($request->input('order')));
                
                        // Validar que order sea solo 'asc' o 'desc'
                        if ($order != 'asc' && $order != 'desc') {
                            $order = 'desc';
                        }
                        
                        $data = $model->consultarSeguimientoIntegral([
                            $proceso,                // @NumeroDocumentoProceso
                            $entrega,                // @NumeroDocumentoEntrega
                            $pedido,                 // @NumeroDocumentoPedido
                            $numclienteoc,           // @NumeroClienteOC
                            $fecha_desde,            // @FechaDesde
                            $fecha_hasta,            // @FechaHasta
                            $picking,                // @NumeroDocumentoPicking
                            $factura,                // @NumeroDocumentoFactura
                            $codigo_producto,        // @CodigoProducto (nuevo)
                            $desc_producto,          // @DescripcionProducto (nuevo)
                            $contrato,               // @NumeroContrato (nuevo)
                            $page,                   // @Page 
                            $rows,                   // @Rows
                            $sort,                   // @sort
                            $order                   // @order
                        ]);
                        
                        if (!isset($data['valor']) || $data['valor'] != 1) {
                            throw new \Exception(isset($data['mensaje']) ? $data['mensaje'] : "Error en la consulta");
                        }
                        
                        $result = ["total" => 0, "rows" => []];
                        
                        if (isset($data['registro']) && is_array($data['registro']) && !empty($data['registro'])) {
                            $result["total"] = isset($data['registro'][0]->Total) ? $data['registro'][0]->Total : 0;
                            $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);
                        }
                        
                        return response()->json($result);
                    } catch (\Exception $e) {
                        \Log::error('Error en listarSeguimientoIntegral: ' . $e->getMessage());
                        return response()->json([
                            "total" => 0,
                            "rows" => []
                        ]);
                    }
                    break;

                    case 'listarAdjuntosOC':
                        $info[0] = 1; // accion=listar
                        $info[3] = is_null($request->input('_idpedido')) ? '' : trim($request->input('_idpedido'));
                        
                        $data = $model->consultarAdjuntosOC($info);
                        
                        if ($data['valor'] == 1) {
                            return response()->json([
                                'total' => count($data['registro']),
                                'rows' => $data['registro']
                            ]);
                        } else {
                            return response()->json([
                                'total' => 0,
                                'rows' => []
                            ]);
                        }
                        break;
                        
                        case 'guardarAdjuntoOC':
                            if (!$request->hasFile('archivos')) {
                                return response()->json([
                                    'success' => false,
                                    'mensaje' => 'No se ha seleccionado ningún archivo'
                                ]);
                            }
                            
                            $idpedido = $request->input('_idpedido');
                            $idtipoadjunto = $request->input('tipo');
                            
                            try {
                                $exitosos = 0;
                                $errors = [];
                                
                                foreach ($request->file('archivos') as $file) {
                                    $nombreArchivo = $idpedido . '_' . time() . '.' . $file->getClientOriginalExtension();
                                    $extension = $file->getClientOriginalExtension();
                                    $tamanio = $file->getSize();
                                    
                                    $rutaDestino = $idpedido . '/';
                                    
                                    // Guardar archivo primero
                                    Storage::disk('sftp_contrato_oc')->put($rutaDestino . $nombreArchivo, file_get_contents($file));
                                    
                                    try {
                                        // Ejecutar consulta directamente sin usar el modelo
                                        DB::statement('EXEC [CTF].[usp_adjuntos_oc_gestionar] ?,?,?,?,?,?,?,?,?,?,?,?', [
                                            2, // acción guardar
                                            null, // idadjuntooc
                                            0, // idcontrato
                                            $idpedido,
                                            1, // estado
                                            $idtipoadjunto,
                                            $nombreArchivo,
                                            $rutaDestino,
                                            $extension,
                                            $tamanio,
                                            $usuarioid,
                                            $request->ip()
                                        ]);
                                        
                                        $exitosos++;
                                    } catch (\Exception $dbEx) {
                                        // Si falla la BD, registrar error pero mantener archivo
                                        \Log::error('Error BD al guardar adjunto: ' . $dbEx->getMessage());
                                        $errors[] = $dbEx->getMessage();
                                    }
                                }
                                
                                if ($exitosos > 0) {
                                    return response()->json([
                                        'success' => true,
                                        'mensaje' => "Se guardaron $exitosos archivos correctamente"
                                    ]);
                                } else {
                                    return response()->json([
                                        'success' => false,
                                        'mensaje' => 'Error al guardar en la base de datos: ' . implode(', ', $errors)
                                    ]);
                                }
                                
                            } catch (\Exception $e) {
                                return response()->json([
                                    'success' => false,
                                    'mensaje' => 'Error al guardar: ' . $e->getMessage()
                                ]);
                            }
                            break;
                        
                    case 'DownloadAdjuntosOC':
                        $info[0] = 3; // accion=obtener ruta
                        $info[1] = is_null($request->input('_idadjuntooc')) ? '' : trim($request->input('_idadjuntooc'));
                        
                        $data = $model->consultarAdjuntosOC($info);
                        
                        if ($data['valor'] != 1 || count($data['registro']) == 0) {
                            return response()->json('No se encontraron archivos para descargar.');
                        }
                        
                        $ruta_file = $data['registro'][0]->ruta_descarga;
                        $nombre_archivo = $data['registro'][0]->nombre_archivo;
                        
                        if (!Storage::disk('sftp_contrato_oc')->exists($ruta_file)) {
                            return response()->json('El archivo no existe en el servidor.');
                        }
                        
                        return Storage::disk('sftp_contrato_oc')->download($ruta_file, $nombre_archivo);
                        break;
                        
                    case 'visualizacionPreviaOC':
                        $ruta_file = is_null($request->input('_path')) ? '' : trim($request->input('_path')); 
                        $filename = is_null($request->input('_filename')) ? '' : trim($request->input('_filename'));
                        $flg_tipo = is_null($request->input('_flg_tipo')) ? '' : trim($request->input('_flg_tipo'));
                        
                        $ruta_completa = $ruta_file . $filename;
                        
                        if (!Storage::disk('sftp_contrato_oc')->exists($ruta_completa)) {
                            return response()->json('El archivo no existe en el servidor.');
                        }
                        
                        $contenido = Storage::disk('sftp_contrato_oc')->get($ruta_completa);
                        $extension = pathinfo($filename, PATHINFO_EXTENSION);
                        
                        // Determinar el tipo MIME según la extensión
                        $mime_types = [
                            'pdf' => 'application/pdf',
                            'doc' => 'application/msword',
                            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'xls' => 'application/vnd.ms-excel',
                            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'jpg' => 'image/jpeg',
                            'jpeg' => 'image/jpeg',
                            'png' => 'image/png'
                        ];
                        
                        $mime = isset($mime_types[strtolower($extension)]) ? $mime_types[strtolower($extension)] : 'application/octet-stream';
                        
                        return response($contenido, 200)->header('Content-Type', $mime);
                        break;
                    
                    case 'listarTiposAdjunto':
                        // Obtener tipos de adjunto para el combo
                        $tipos = DB::select('SELECT idtipoadjunto as id, tdjchdstipoadjunto as text FROM CTF.TBCTFTIPO_ADJUNTO WHERE tdjitenidestadodato = 1 AND idtipoadjunto in (6) ORDER BY tdjchdstipoadjunto');
                        
                        return response()->json($tipos);
                        break;
            case 'actualizar41':
                $array_doc = [];
                $proceso= is_null($request->input('proceso')) ? '' : trim($request->input('proceso'));

                $array_doc[] = $proceso;
                // CONSUMO RFC - 41xxx
                $rpta_rfc = $this->getPROCESOS_RFC('', '',$array_doc);   
                
                if(count($rpta_rfc['rows_cab']) > 0 && count($rpta_rfc['rows_det']) > 0){
                    $rpta_procesado = $this->procesarPROCESOS($rpta_rfc['rows_cab'], $rpta_rfc['rows_det'],$rpta_rfc['fechadesde'],$rpta_rfc['fechafin']);
                    $result['o_nres'] = $rpta_procesado['o_nres'];
                    $result['o_msj'] = $rpta_procesado['o_msj'];
                }else{
                    $result['o_nres'] = 0;
                    $result['o_msj'] = 'Proceso no encontrado en SAP.';
                }
                
                return response()->json($result);

                break; 
            case 'listarAdenda':
                $documento = is_null($request->input('documento')) ? '' : trim($request->input('documento'));
                $documento_modelo = is_null($request->input('documento_modelo')) ? '' : trim($request->input('documento_modelo'));
                $array_doc = [];
                $array_doc[] = $documento;
                $array_doc[] = $documento_modelo;
                // CONSUMO RFC - 41xxx
                $rpta_rfc = $this->getPROCESOS_RFC('', '',$array_doc);    
                $rpta_procesado = $this->procesarPROCESOS($rpta_rfc['rows_cab'], $rpta_rfc['rows_det'],$rpta_rfc['fechadesde'],$rpta_rfc['fechafin']);                
                $result['o_nres'] = $rpta_procesado['o_nres'];
                $result['o_msj'] = $rpta_procesado['o_msj'];
                // CONSULTA A BASE DE DATOS
                if($rpta_procesado['o_nres'] == 1){
                    $info[0] = 1;
                    $info[22] = is_null($request->input('documento')) ? '' : trim($request->input('documento'));
                    $info[25] = is_null($request->input('documento_modelo')) ? '' : trim($request->input('documento_modelo'));

                    $info[9] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                    $info[10] = is_null($request->input('rows')) ? '50' : trim($request->input('rows'));
                    //dd($info);

                    $data   = $model->consultarData($info);unset($info);

                    $result["total"] = isset($data['registro'][0]->total) ?  $data['registro'][0]->total : 0;
                    $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);
                }

                return response()->json($result);

                break; 
            case 'listarPrincipalProcesoDet':               
                $info[0] = 2;
                // PROCESO
                $info[1] = is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                // ENTREGA
                $info[2] = is_null($request->input('codigo_producto')) ? '' : trim($request->input('codigo_producto'));
                $info[3] = is_null($request->input('txt_entrega')) ? '' : trim($request->input('txt_entrega'));
                $info[4] = is_null($request->input('codigo_dst_mercancia')) ? '' : trim($request->input('codigo_dst_mercancia'));
                $info[5] = is_null($request->input('txt_contrato')) ? '' : trim($request->input('txt_contrato'));
                // PEDIDO
                $info[6] = is_null($request->input('txt_pedido')) ? '' : trim($request->input('txt_pedido'));
                $info[7] = is_null($request->input('txt_gr')) ? '' : trim($request->input('txt_gr'));
                $info[8] = is_null($request->input('txt_factura')) ? '' : trim($request->input('txt_factura'));
                $info[11] = is_null($request->input('flg_only_sctf')) ? '' : trim($request->input('flg_only_sctf'));
                $info[13] = is_null($request->input('nro_item')) ? '' : trim($request->input('nro_item'));

                /* $info[9] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                $info[10] = is_null($request->input('rows')) ? '50' : trim($request->input('rows')); */

                $data   = $model->consultarData($info);unset($info);

                $result["total"] = count($data['registro']);
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);

                return response()->json($result);

                break; 
            case 'listarPrincipalEntrega':
                // DATOS PARA RFC
                $doc_entregas = [];
                $doc_proceso = is_null($request->input('doc_proceso')) ? '' : trim($request->input('doc_proceso'));
                $posicion = is_null($request->input('posicion')) ? '' : trim($request->input('posicion'));
                // DATOS DE CONSULTA BD
                $info_ped[0] = 3;
                // PROCESO
                $info_ped[1] = is_null($request->input('idprocesodet')) ? '' : trim($request->input('idprocesodet'));
                // ENTREGA
                $info_ped[2] = is_null($request->input('txt_entrega')) ? '' : trim($request->input('txt_entrega'));
                $info_ped[3] = is_null($request->input('txt_contrato')) ? '' : trim($request->input('txt_contrato'));
                $info_ped[4] = is_null($request->input('codigo_dst_mercancia')) ? '' : trim($request->input('codigo_dst_mercancia'));
                // PEDIDO
                $info_ped[5] = is_null($request->input('txt_pedido')) ? '' : trim($request->input('txt_pedido'));
                $info_ped[6] = is_null($request->input('txt_gr')) ? '' : trim($request->input('txt_gr'));
                $info_ped[7] = is_null($request->input('txt_factura')) ? '' : trim($request->input('txt_factura'));
                $info_ped[11] = is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
            
                /* $info_ped[9] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                $info_ped[10] = is_null($request->input('rows')) ? '50' : trim($request->input('rows')); */
                // CONSUMO RFC - 11xxx 
                $rpta_rfc = $this->getENTREGAS_RFC('', '',$doc_proceso,$posicion);    
                //dd($rpta_rfc);
                if(count($rpta_rfc['rows_cab']) > 0 && count($rpta_rfc['rows_det']) > 0){
                    $rpta_procesado = $this->procesarENTREGAS($rpta_rfc['rows_cab'], $rpta_rfc['rows_det'],'','');      
                    $result['o_nres'] = $rpta_procesado['o_nres'];
                    $result['o_msj'] = $rpta_procesado['o_msj'];               
                    // CONSULTA A BASE DE DATOS - 11xxx
                    if($result['o_nres'] == 1){
                        $info_prev[0] = 5;
                        $info_prev[1] = $info_ped[1];
                        $data_prev   = $model->consultarData($info_prev);unset($info_prev);
                        // CONSUMO RFC - 15xxx
                        foreach($data_prev['registro'] as $value){
                            $doc_entregas[] = [
                                'posicion' => $value->posicion,
                                'documento' => $value->documento
                            ];
                        }
                        if($result['o_nres'] == 1){
                            $rpta_rfc = $this->getPEDIDOS_RFC('', '',$doc_entregas);   
                            $rpta_procesado = $this->procesarPEDIDOS($rpta_rfc['rows_cab'], $rpta_rfc['rows_det'],'','');      
                            $result['o_nres'] = $rpta_procesado['o_nres'];
                            $result['o_msj'] = $rpta_procesado['o_msj'];
                            //dd($result);
                        }
                    }
                    // CONSULTA FINAL BD 11/15
                    if($result['o_nres'] == 1){
                        $data   = $model->consultarData($info_ped);unset($info_ped);
                        //dd($data);
                        
                        //$result["total"] = isset($data['registro'][0]->total) ?  $data['registro'][0]->total : 0;
                        $result["total"] = count($data['registro']);
                        $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);
                    }
                }else{
                    $result['o_nres'] = 1;
                    $result['o_msj'] = 'Sin registros';
                    $result["total"] = 0;
                    $result["rows"] = [];
                }

                return response()->json($result);

                break; 
            case 'listarPrincipalEntregaNivelCumplimiento':
                // DATOS DE CONSULTA BD
                $info[0] = 3;
                // PROCESO
                $info[1] = is_null($request->input('idprocesodet')) ? '' : trim($request->input('idprocesodet'));
                // ENTREGA
                $info[2] = is_null($request->input('txt_entrega')) ? '' : trim($request->input('txt_entrega'));
                $info[3] = is_null($request->input('txt_contrato')) ? '' : trim($request->input('txt_contrato'));
                $info[4] = is_null($request->input('codigo_dst_mercancia')) ? '' : trim($request->input('codigo_dst_mercancia'));
                // PEDIDO
                $info[5] = is_null($request->input('txt_pedido')) ? '' : trim($request->input('txt_pedido'));
                $info[6] = is_null($request->input('txt_gr')) ? '' : trim($request->input('txt_gr'));
                $info[7] = is_null($request->input('txt_factura')) ? '' : trim($request->input('txt_factura'));
                $info[11] = is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                // NIVEL CUMPLIMIENTO
                $info[15] = is_null($request->input('anio_nc')) ? '' : trim($request->input('anio_nc'));
                $info[16] = is_null($request->input('mes_nc')) ? '' : trim($request->input('mes_nc'));
                $info[17] = is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));
                $info[18] = is_null($request->input('pendiente_atencion')) ? '' : trim($request->input('pendiente_atencion'));
                $info[19]= is_null($request->input('fecha_desde_cnt_nc')) ? '' : trim($request->input('fecha_desde_cnt_nc'));
                $info[19] = $this->empresaFormato->formatDateIn($info[19]);
                $info[20] = is_null($request->input('fecha_fin_cnt_nc')) ? '' : trim($request->input('fecha_fin_cnt_nc'));
                $info[20]= $this->empresaFormato->formatDateIn($info[20]);
                $info[22] = is_null($request->input('identregacab')) ? '' : trim($request->input('identregacab'));
                $info[23] = is_null($request->input('nro_entrega')) ? '' : trim($request->input('nro_entrega'));
                $info[24] = is_null($request->input('idmaeproducto')) ? '' : trim($request->input('idmaeproducto'));
                $info[25] = is_null($request->input('operador_alias')) ? '' : trim($request->input('operador_alias'));
                $info[26] = is_null($request->input('tipo_producto')) ? '' : trim($request->input('tipo_producto'));
                $info[27] = is_null($request->input('idpedidocab')) ? '' : trim($request->input('idpedidocab'));
                $info[28] = is_null($request->input('idpickingcab')) ? '' : trim($request->input('idpickingcab'));
                $info[29] = is_null($request->input('guia_remision')) ? '' : trim($request->input('guia_remision'));
                $info[30] = is_null($request->input('factura_sunat')) ? '' : trim($request->input('factura_sunat'));
                $info[31] = is_null($request->input('flg_devolucion')) ? '' : trim($request->input('flg_devolucion'));

                $info[9] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                $info[10] = is_null($request->input('rows')) ? '50' : trim($request->input('rows'));
                
                $data   = $model->consultarData($info);unset($info);
                //dd($data);
                
                $result["total"] = isset($data['registro'][0]->total) ?  $data['registro'][0]->total : 0;
                //$result["total"] = count($data['registro']);
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);
                
                return response()->json($result);

                break; 
            case 'listarPrincipalPedidoNivelCumplimiento':
                $info[0] = 4;
                $info[1] = is_null($request->input('identregadet')) ? '' : trim($request->input('identregadet'));
                $info[2] = is_null($request->input('txt_pedido')) ? '' : trim($request->input('txt_pedido'));
                $info[3] = is_null($request->input('txt_gr')) ? '' : trim($request->input('txt_gr'));
                $info[4] = is_null($request->input('txt_factura')) ? '' : trim($request->input('txt_factura'));
                $info[6] = is_null($request->input('operador_alias')) ? '' : trim($request->input('operador_alias'));
                $info[7] = is_null($request->input('flg_producto_sagitario')) ? '' : trim($request->input('flg_producto_sagitario'));
                $info[8] = is_null($request->input('idpedidocab')) ? '' : trim($request->input('idpedidocab'));
                $info[11] = is_null($request->input('idpickingcab')) ? '' : trim($request->input('idpickingcab'));
                $info[12] = is_null($request->input('guia_remision')) ? '' : trim($request->input('guia_remision'));
                $info[13] = is_null($request->input('factura_sunat')) ? '' : trim($request->input('factura_sunat'));
                $info[14] = is_null($request->input('flg_devolucion')) ? '' : trim($request->input('flg_devolucion'));
                $info[15] = is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                $info[16] = is_null($request->input('flg_deuda')) ? '' : trim($request->input('flg_deuda'));
                $info[17] = is_null($request->input('cliente_final')) ? '' : trim($request->input('cliente_final'));
                //dd($info);
                
                $info[9] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                $info[10] = is_null($request->input('rows')) ? '50' : trim($request->input('rows'));
                
                $data   = $model->consultarData($info);unset($info);

                $result["total"] = isset($data['registro'][0]->total) ?  $data['registro'][0]->total : 0;
                //$result["total"] = count($data['registro']);
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);

                return response()->json($result);

                break; 
            case 'listarDetallexLote':
                $info[0] = 30;
                $info[1] = is_null($request->input('operador')) ? '' : trim($request->input('operador'));
                $info[2] = is_null($request->input('serie_cf')) ? '' : trim($request->input('serie_cf'));
                $info[3] = is_null($request->input('nro_cf')) ? '' : trim($request->input('nro_cf'));
                $info[4] = is_null($request->input('idpickingcab')) ? '' : trim($request->input('idpickingcab'));
                $info[5] = is_null($request->input('idpedidodet')) ? '' : trim($request->input('idpedidodet'));

                //dd($info);
                $data   = $model->consultarData($info);unset($info);

                $result["total"] = count($data['registro']);
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);

                return response()->json($result);

                break; 
            case 'listarPrincipalEntregaxContrato':
                // DATOS PARA RFC
                $doc_entregas = [];
                $doc_proceso = is_null($request->input('doc_proceso')) ? '' : trim($request->input('doc_proceso'));
                $idprocesocab = is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                // DATOS DE CONSULTA BD
                /*$info[0] = 5;
                $info[1] = is_null($request->input('ids_procesodet')) ? '' : trim($request->input('ids_procesodet'));
                $info[2] = is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));
                $info[3] = $idprocesocab;
                $data = $model->consultarData($info);unset($info);
                if($idprocesocab == ''){
                    foreach($data['registro'] as $value){
                        $doc_entregas[] = [
                            'posicion' => $value->posicion,
                            'documento' => $value->documento
                        ];
                    }
                    $rpta_rfc = $this->getENTREGAS_RFC('', '','','',$doc_entregas);    
                }else{
                    $rpta_rfc = $this->getENTREGAS_RFC('', '',$doc_proceso,'',[]);    
                } */
                
                //dd($doc_entregas);
                // CONSUMO RFC - 11xxx 
                //dd($rpta_rfc);
                //if(count($rpta_rfc['rows_cab']) > 0 && count($rpta_rfc['rows_det']) > 0){
                    /* $rpta_procesado = $this->procesarENTREGAS($rpta_rfc['rows_cab'], $rpta_rfc['rows_det'],'','');      
                    $result['o_nres'] = $rpta_procesado['o_nres'];
                    $result['o_msj'] = $rpta_procesado['o_msj'];   
                    // CONSULTA A BASE DE DATOS - 11xxx
                    if($result['o_nres'] == 1 && $idprocesocab == ''){ // Para asociación de Entregas x Contrato no se necesita actualizar los pedidos.
                        $rpta_rfc = $this->getPEDIDOS_RFC('', '',$doc_entregas);    
                        $rpta_procesado = $this->procesarPEDIDOS($rpta_rfc['rows_cab'], $rpta_rfc['rows_det'],'','');      
                        $result['o_nres'] = $rpta_procesado['o_nres'];
                        $result['o_msj'] = $rpta_procesado['o_msj'];
                    } */
                    // CONSULTA FINAL BD 11/15
                    //if($result['o_nres'] == 1){
                        $info[0] = 3;
                        $info[8] = is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));
                        $info[9] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                        $info[10] = is_null($request->input('rows')) ? '50' : trim($request->input('rows'));
                        $info[11] = is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                        //dd($info);
                        $data   = $model->consultarData($info);unset($info);
                        //dd($data['registro'][0]->total);
                    
                        $result["total"] = isset($data['registro'][0]->total) ?  $data['registro'][0]->total : 0;
                        $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);

                        return response()->json($result);
                    //}
                /* }else{
                    $result['o_nres'] = 1;
                    $result['o_msj'] = 'Sin registros';
                    $result["total"] = 0;
                    $result["rows"] = [];

                    return response()->json($result);
                } */
            
                break; 
            case 'listarEntregasDB':               
                $info[0] = 3;
                $info[2] = is_null($request->input('entrega')) ? '' : trim($request->input('entrega'));
                $info[11] = is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                $info[12] = is_null($request->input('nro_entrega')) ? '' : trim($request->input('nro_entrega'));
                $info[13] = is_null($request->input('codigo_producto_ent')) ? '' : trim($request->input('codigo_producto_ent'));
                $info[14] = is_null($request->input('flg_libres')) ? '' : trim($request->input('flg_libres'));
                $info[8] = is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));

                $info[9] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                $info[10] = is_null($request->input('rows')) ? '50' : trim($request->input('rows'));
                
                $data   = $model->consultarData($info);unset($info);

                $result["total"] = isset($data['registro'][0]->total) ?  $data['registro'][0]->total : 0;
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);

                return response()->json($result);

                break; 
            case 'listarPrincipalPedido':
                $doc_pedidos = [];
                // $cachedData = Cache::get('sap_pck_fac'); // CACHE
                $info_pf[0] = 4;
                $info_pf[1] = is_null($request->input('identregadet')) ? '' : trim($request->input('identregadet'));
                $info_pf[2] = is_null($request->input('txt_pedido')) ? '' : trim($request->input('txt_pedido'));
                $info_pf[3] = is_null($request->input('txt_gr')) ? '' : trim($request->input('txt_gr'));
                $info_pf[4] = is_null($request->input('txt_factura')) ? '' : trim($request->input('txt_factura'));
                // CONSULTA BD 15xxx
                $info_prev[0] = 6;
                $info_prev[1] = $info_pf[1];
                $data_prev = $model->consultarData($info_prev);unset($info_prev);
                //dd($data_prev['registro']);
                if(count($data_prev['registro']) > 0){
                    foreach($data_prev['registro'] as $value){
                        $doc_pedidos[] = [
                            'posicion' => $value->posicion,
                            'documento' => $value->documento
                        ];
                    }
                    // CONSUMO RFC 
                    $rpta_rfc = $this->getPICKINGREPARTOFACTURA_RFC('','',$doc_pedidos);    
                    $rpta_procesado = $this->procesarPICKINGFACTURA($rpta_rfc['rows_pick_cab'], $rpta_rfc['rows_pick_det'],$rpta_rfc['rows_fact_cab'],$rpta_rfc['rows_fact_det'],'','');      
                    $result['o_nres'] = $rpta_procesado['o_nres'];
                    $result['o_msj'] = $rpta_procesado['o_msj'];
                    // CONSULTA A BASE DE DATOS
                    if($result['o_nres'] == 1){
                        $data   = $model->consultarData($info_pf);unset($info_pf);

                        $result["total"] = count($data['registro']);
                        $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);
                    }
                }else{
                    $result["total"] = 0;
                    $result["rows"] = [];
                }

                return response()->json($result);

                break; 
            case 'listarPrincipalCartaFianza':
                $info[0] = 7;
                $info[1] = is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                $info[2] = is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));
                $info[3] = is_null($request->input('flg_final')) ? '' : trim($request->input('flg_final'));
                $info[4] = is_null($request->input('idcartafianzafinal')) ? '' : trim($request->input('idcartafianzafinal'));
                $info[5] = is_null($request->input('flg_aprobados')) ? '' : trim($request->input('flg_aprobados'));
                $info[6] = is_null($request->input('flg_ctf_aprobadas')) ? '' : trim($request->input('flg_ctf_aprobadas'));
                $info[7] = is_null($request->input('flg_only_sctf')) ? '' : trim($request->input('flg_only_sctf'));
                /* FILTROS DE MAESTRO DE SCTF */
                $info[8] = is_null($request->input('proceso')) ? '' : trim($request->input('proceso'));
                $info[11] = is_null($request->input('solicitante')) ? '' : trim($request->input('solicitante'));
                $info[12] = is_null($request->input('idestadocartafianza')) ? '' : trim($request->input('idestadocartafianza'));
                $info[13] = is_null($request->input('codigo_garantizado')) ? '' : trim($request->input('codigo_garantizado'));
                $info[14] = is_null($request->input('flg_only_migrada')) ? '' : trim($request->input('flg_only_migrada'));
                $info[15] = is_null($request->input('usuarioid')) ? '' : trim($request->input('usuarioid'));
                $info[16] = is_null($request->input('no_flg_only_migrada')) ? '' : trim($request->input('no_flg_only_migrada'));
                $info[17] = is_null($request->input('flg_rechazada')) ? '' : trim($request->input('flg_rechazada'));
                $info[18] = is_null($request->input('no_flg_rechazada')) ? '' : trim($request->input('no_flg_rechazada'));
                $info[19] = is_null($request->input('flg_pdte_gestion')) ? '' : trim($request->input('flg_pdte_gestion'));
                /* */
                /* $info[9] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                $info[10] = is_null($request->input('rows')) ? '50' : trim($request->input('rows')); */
                
                $data   = $model->consultarData($info);unset($info);
                $cabecera = $data['registro'];

                $result["total"] = count($data['registro']); //isset($data['registro'][0]->total) ?  $data['registro'][0]->total : 0;
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);
                $result['cabecera'] = $cabecera[0] ?? [];
                //dd($result);

                return response()->json($result);

                break; 
            case 'listarRespaldo':
                $info[0] = 29;
                $info[1] = is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));
                $info[2] = is_null($request->input('idcartafianzafinal')) ? '' : trim($request->input('idcartafianzafinal'));

                $data   = $model->consultarData($info);unset($info);
                $cabecera = $data['registro'];

                $result["total"] = count($data['registro']); //isset($data['registro'][0]->total) ?  $data['registro'][0]->total : 0;
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);
                $result['cabecera'] = $cabecera[0] ?? [];
                //dd($result);

                return response()->json($result);

                break; 
            case 'listarCTFxProductos':
                $info[0] = 8;
                $info[1] = is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));

                $data   = $model->consultarData($info);unset($info);
                $cabecera = $data['registro'];

                $result["total"] = count($data['registro']);
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);

                return response()->json($result);

                break; 
            case 'listarHistorialCTF':
                $flg_final = is_null($request->input('flg_final')) ? '' : trim($request->input('flg_final'));

                $info[0] = ($flg_final == 1) ? 14 : 9;
                $info[1] = is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));

                $data   = $model->consultarData($info);unset($info);
                $result["total"] = count($data['registro']) > 0 ? count($data['registro']) : 0;
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);

                return response()->json($result);

                break; 
            case 'listarHistorialCTFRNV':
                $info[0] = 24;
                $info[1] = is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));

                $data   = $model->consultarData($info);unset($info);
                $result["total"] = count($data['registro']) > 0 ? count($data['registro']) : 0;
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);

                return response()->json($result);

                break; 
            case 'listarHistorialCNT':
                $info[0] = 20;
                $info[1] = is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));

                $data   = $model->consultarData($info);unset($info);
                $result["total"] = count($data['registro']) > 0 ? count($data['registro']) : 0;
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);

                return response()->json($result);

                break; 
            case 'listarEvaluacionBancaria':
                $info[0] = 10;
                $info[1] = is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));

                $data   = $model->consultarData($info);unset($info);
                $result["total"] = count($data['registro']) > 0 ? count($data['registro']) : 0;
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);

                return response()->json($result);

                break; 
            case 'listarEvaluacionBancariaTotal':
                $info[0] = 11;
                $info[1] = is_null($request->input('idevaluacionbancaria')) ? '' : trim($request->input('idevaluacionbancaria'));

                $data   = $model->consultarData($info);unset($info);
                $result["total"] = count($data['registro']) > 0 ? count($data['registro']) : 0;
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);

                return response()->json($result);

                break; 
            case 'listarEvaluacionBancariaGanador':
                $info[0] = 12;//11;
                $info[1] = is_null($request->input('idevaluacionbancaria')) ? '' : trim($request->input('idevaluacionbancaria'));

                $data   = $model->consultarData($info);unset($info);
                $cabecera = $data['registro'];

                $result["total"] = count($data['registro']) > 0 ? count($data['registro']) : 0;
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);
                $result['cabecera'] = $cabecera[0] ?? [];

                return response()->json($result);

                break; 
            case 'listarAdjuntosCTF':
                $info[0] = 13;
                $info[1] = is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));

                $data   = $model->consultarData($info);unset($info);
                $cabecera = $data['registro'];

                $result["total"] = count($data['registro']);
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);

                return response()->json($result);

                break; 
            case 'listarAdjuntosCTF_F':
                $info[0] = 15;
                $info[1] = is_null($request->input('idcartafianzafinal')) ? '' : trim($request->input('idcartafianzafinal'));
                $info[2] = is_null($request->input('idadjuntocartafianzafinal')) ? '' : trim($request->input('idadjuntocartafianzafinal'));
                $info[3] = is_null($request->input('flg_activos')) ? '' : trim($request->input('flg_activos'));

                $data   = $model->consultarData($info);unset($info);
                $cabecera = json_decode(json_encode($data["registro"]), true);

                $arrayFiltrado = array_filter($cabecera, function ($elemento) {
                    return $elemento['idtipoadjunto'] == '5';
                });

                $result["total"] = count($data['registro']);
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);
                $result['cabecera'] = $arrayFiltrado ?? [];

                return response()->json($result);

                break; 
            case 'listarAdjuntosCNT':
                $info[0] = 21;
                $info[1] = is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));

                $data   = $model->consultarData($info);unset($info);
                $cabecera = $data['registro'];

                $result["total"] = count($data['registro']);
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);

                return response()->json($result);

                break; 
            case 'listarCronogramaVctoCTF':
                $info[0] = 23;
                $info[1] = is_null($request->input('mes')) ? '' : trim($request->input('mes'));
                $info[2] = is_null($request->input('anio')) ? '' : trim($request->input('anio'));

                $data   = $model->consultarData($info);unset($info);
                $cabecera = $data['registro'];

                $result["total"] = count($data['registro']);
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);

                return response()->json($result);

                break; 
            case 'listarContratos':
                $info[0] = 19;
                $info[1] = is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                $info[2] = is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));
                $info[6] = is_null($request->input('flg_aprobados')) ? '' : trim($request->input('flg_aprobados'));
                /* MAE CONTRATO */
                $info[3] = is_null($request->input('proceso')) ? '' : trim($request->input('proceso'));
                $info[4] = is_null($request->input('contrato')) ? '' : trim($request->input('contrato'));
                $info[5] = is_null($request->input('idestadocontrato')) ? '' : trim($request->input('idestadocontrato'));
                $info[7] = is_null($request->input('dias_demora')) ? '' : trim($request->input('dias_demora'));
                $info[8] = is_null($request->input('flg_fianza')) ? '' : trim($request->input('flg_fianza'));

                $data   = $model->consultarData($info);unset($info);
                $cabecera = $data['registro'];

                $result["total"] = count($data['registro']) > 0 ? count($data['registro']) : 0;
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);
                $result['cabecera'] = $cabecera[0] ?? [];

                return response()->json($result);

                break; 
            case 'listarContratosxSituaciones':
                $info[0] = 22;
                $info[1] = is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));
                $info[2] = is_null($request->input('idcartafianzafinal')) ? '' : trim($request->input('idcartafianzafinal'));
                
                $data   = $model->consultarData($info);unset($info);
                $cabecera = $data['registro'];

                $result["total"] = count($data['registro']) > 0 ? count($data['registro']) : 0;
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);
                $result['cabecera'] = $cabecera[0] ?? [];

                return response()->json($result);

                break; 
            case 'sincronizarSAP':
                $documento = is_null($request->input('documento')) ? '' : trim($request->input('documento'));
                $rpta = $this->getTOTAL($documento);
                //dd($rpta);
                $result['o_nres']   = $rpta['o_nres'];
                $result['o_msj'] = ($rpta['o_nres'] == 1) ? 'Procoes actualizado con éxito ['.$documento.'].' : $rpta['o_msj'];

                return response()->json($result);

                break;
            case 'sincronizarTotal':
                $idprocesocab = is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                $documento = is_null($request->input('documento')) ? '' : trim($request->input('documento'));
                $operador_alias = is_null($request->input('operador_alias')) ? '' : trim($request->input('operador_alias'));
                $result['o_nres'] = 1;
                $result['o_msj'] = 'ok';
                
                //dd($operador_alias);
                /* $rpta = $this->getTOTAL($documento);
                //dd($rpta);
                $result['o_nres'] = $rpta['o_nres'] ?? 0;
                $result['o_msj'] = $rpta['o_msj'] ?? 'Fallo sincro total.'; */
                if($result['o_nres'] == 1){
                    $info[0] = 25;
                    $info[1] = $idprocesocab;
                    $info[3] = ($operador_alias) ? $operador_alias : '';
                    $data = $model->consultarData($info);unset($info);
                    $cabecera = $data['registro'];

                    /* $info[0] = 31;
                    $info[1] = $idprocesocab;
                    $info[2] = $cabecera[0]->nivel_cumplimiento;
                    $info[3] = $cabecera[0]->nivel_cumplimiento_ejecucion;
                    $rpta = $model->mantenimientoData($info);unset($info); */
                    
                    $result['cabecera'] = $cabecera[0] ?? [];
                }else{
                    $result['o_nres'] = 1;
                    $result['o_msj'] = 'Sin registros';
                }

                return response()->json($result);

                break;
            case 'listarMaestroCartaFianza':
                $info[0] = 26;
                $info[1] = is_null($request->input('flg_only_migrada')) ? '' : trim($request->input('flg_only_migrada'));
                $info[2] = is_null($request->input('flg_only_faltante_41')) ? '' : trim($request->input('flg_only_faltante_41'));
                $info[3] = is_null($request->input('idcartafianzafinal')) ? '' : trim($request->input('idcartafianzafinal'));
                $info[4] = is_null($request->input('proceso')) ? '' : trim($request->input('proceso'));
                $info[5] = is_null($request->input('fianza')) ? '' : trim($request->input('fianza'));
                $info[6] = is_null($request->input('idestadocartafianzafinal')) ? '' : trim($request->input('idestadocartafianzafinal'));
                $info[7] = is_null($request->input('idmaebanco')) ? '' : trim($request->input('idmaebanco'));
                $info[8] = is_null($request->input('contratista')) ? '' : trim($request->input('contratista'));
                $info[11] = is_null($request->input('linea')) ? '' : trim($request->input('linea'));
                $info[12] = is_null($request->input('dias_demora')) ? '' : trim($request->input('dias_demora'));
                $info[13] = is_null($request->input('anio')) ? '' : trim($request->input('anio'));
                $info[14] = is_null($request->input('mes')) ? '' : trim($request->input('mes'));
                $info[15] = is_null($request->input('flg_rechazada')) ? '' : trim($request->input('flg_rechazada'));
                $info[16] = is_null($request->input('no_flg_rechazada')) ? '' : trim($request->input('no_flg_rechazada'));
                $info[17] = is_null($request->input('flg_contrato')) ? '' : trim($request->input('flg_contrato'));
                $info[18]= is_null($request->input('fecha_desde_mae_cf')) ? '' : trim($request->input('fecha_desde_mae_cf'));
                $info[18] = $this->empresaFormato->formatDateIn($info[18]);
                $info[19] = is_null($request->input('fecha_fin_mae_cf')) ? '' : trim($request->input('fecha_fin_mae_cf'));
                $info[19]= $this->empresaFormato->formatDateIn($info[19]);
                $info[21] = is_null($request->input('indicador_1era')) ? '' : trim($request->input('indicador_1era'));
                $info[23] = is_null($request->input('flg_cumplimiento')) ? '' : trim($request->input('flg_cumplimiento'));
                $info[24] = is_null($request->input('flg_ejecucion')) ? '' : trim($request->input('flg_ejecucion'));
                $info[25] = is_null($request->input('flg_deuda')) ? '' : trim($request->input('flg_deuda'));
                $info[26] = is_null($request->input('idsituaciones')) ? '' : trim($request->input('idsituaciones'));

                /* $info[9] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                $info[10] = is_null($request->input('rows')) ? '50' : trim($request->input('rows')); */

                $data   = $model->consultarData($info);unset($info);
                $cabecera = json_decode(json_encode($data["registro"]), true);

                $result["total"] = count($data['registro']); //isset($data['registro'][0]->total) ?  $data['registro'][0]->total : 0;
                $result["rows"] = $this->empresaFormato->formatDateOut($data['registro']);
                $result['cabecera'] = $cabecera[0] ?? [];

                return response()->json($result);

                break; 
            case 'DownloadExcelMaestroSolicitudes':
                $info[0] = 7;
                $info[1] = is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                $info[2] = is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));
                $info[3] = is_null($request->input('flg_final')) ? '' : trim($request->input('flg_final'));
                $info[4] = is_null($request->input('idcartafianzafinal')) ? '' : trim($request->input('idcartafianzafinal'));
                $info[5] = is_null($request->input('flg_aprobados')) ? '' : trim($request->input('flg_aprobados'));
                $info[6] = is_null($request->input('flg_ctf_aprobadas')) ? '' : trim($request->input('flg_ctf_aprobadas'));
                $info[7] = is_null($request->input('flg_only_sctf')) ? '' : trim($request->input('flg_only_sctf'));
                /* FILTROS DE MAESTRO DE SCTF */
                $info[8] = is_null($request->input('proceso')) ? '' : trim($request->input('proceso'));
                $info[11] = is_null($request->input('solicitante')) ? '' : trim($request->input('solicitante'));
                $info[12] = is_null($request->input('idestadocartafianza')) ? '' : trim($request->input('idestadocartafianza'));
                $info[13] = is_null($request->input('codigo_garantizado')) ? '' : trim($request->input('codigo_garantizado'));
                $info[14] = is_null($request->input('flg_only_migrada')) ? '' : trim($request->input('flg_only_migrada'));
                $info[15] = is_null($request->input('usuarioid')) ? '' : trim($request->input('usuarioid'));
                $info[16] = is_null($request->input('no_flg_only_migrada')) ? '' : trim($request->input('no_flg_only_migrada'));
                $info[17] = is_null($request->input('flg_rechazada')) ? '' : trim($request->input('flg_rechazada'));
                $info[18] = is_null($request->input('no_flg_rechazada')) ? '' : trim($request->input('no_flg_rechazada'));
                $info[19] = is_null($request->input('flg_pdte_gestion')) ? '' : trim($request->input('flg_pdte_gestion'));
                $info[30] = 1;

                $data   = $model->consultarData($info);unset($info);

                $result["res"] = 1;
                $result["msj"] = "";
                $result["nombrefile"] = "";
                if (count($data["registro"]) > 0){
                    $nombrefile = 'Reporte_Maestro_Solicitud_Cartas_Fianzas_'.date('YmdHis').'.xlsx';
                    $list = json_decode(json_encode($data["registro"]), true);
                    foreach ($list as &$item) {
                        $item["Importe Adjudicado"] = floatval($item["Importe Adjudicado"]);
                        $item["Importe Número (10%)"] = floatval($item["Importe Número (10%)"]);
                        $item["Importe Carta Fianza"] = floatval($item["Importe Carta Fianza"]);
                    } 
                    fastexcel($list)->export(Storage::disk('descargas')->path('').$nombrefile);
                    $result["nombrefile"] = $nombrefile;
                }else{
                    $result["res"] = 0;
                    $result["msj"] = "No existe información para descargar.";
                }

                return response()->json($result);

                break;
            case 'DownloadExcelMaestroFianzas':
                $info[0] = 26;
                $info[1] = is_null($request->input('flg_only_migrada')) ? '' : trim($request->input('flg_only_migrada'));
                $info[2] = is_null($request->input('flg_only_faltante_41')) ? '' : trim($request->input('flg_only_faltante_41'));
                $info[3] = is_null($request->input('idcartafianzafinal')) ? '' : trim($request->input('idcartafianzafinal'));
                $info[4] = is_null($request->input('proceso')) ? '' : trim($request->input('proceso'));
                $info[5] = is_null($request->input('fianza')) ? '' : trim($request->input('fianza'));
                $info[6] = is_null($request->input('idestadocartafianzafinal')) ? '' : trim($request->input('idestadocartafianzafinal'));
                $info[7] = is_null($request->input('idmaebanco')) ? '' : trim($request->input('idmaebanco'));
                $info[8] = is_null($request->input('contratista')) ? '' : trim($request->input('contratista'));
                $info[11] = is_null($request->input('linea')) ? '' : trim($request->input('linea'));
                $info[12] = is_null($request->input('dias_demora')) ? '' : trim($request->input('dias_demora'));
                $info[13] = is_null($request->input('anio')) ? '' : trim($request->input('anio'));
                $info[14] = is_null($request->input('mes')) ? '' : trim($request->input('mes'));
                $info[15] = is_null($request->input('flg_rechazada')) ? '' : trim($request->input('flg_rechazada'));
                $info[16] = is_null($request->input('no_flg_rechazada')) ? '' : trim($request->input('no_flg_rechazada'));
                $info[17] = is_null($request->input('flg_contrato')) ? '' : trim($request->input('flg_contrato'));
                $info[18]= is_null($request->input('fecha_desde_mae_cf')) ? '' : trim($request->input('fecha_desde_mae_cf'));
                $info[18] = $this->empresaFormato->formatDateIn($info[18]);
                $info[19] = is_null($request->input('fecha_fin_mae_cf')) ? '' : trim($request->input('fecha_fin_mae_cf'));
                $info[19]= $this->empresaFormato->formatDateIn($info[19]);
                $info[20] = '';
                $info[21] = is_null($request->input('indicador_1era')) ? '' : trim($request->input('indicador_1era'));
                $info[23] = is_null($request->input('flg_cumplimiento')) ? '' : trim($request->input('flg_cumplimiento'));
                $info[24] = is_null($request->input('flg_ejecucion')) ? '' : trim($request->input('flg_ejecucion'));
                $info[25] = is_null($request->input('flg_deuda')) ? '' : trim($request->input('flg_deuda'));
                $info[26] = is_null($request->input('idsituaciones')) ? '' : trim($request->input('idsituaciones'));
                $info[30] = 1;

                $data   = $model->consultarData($info);unset($info);

                $result["res"] = 1;
                $result["msj"] = "";
                $result["nombrefile"] = "";
                if (count($data["registro"]) > 0){
                    $nombrefile = 'Reporte_Maestro_Cartas_Fianzas_'.date('YmdHis').'.xlsx';
                    $list = json_decode(json_encode($data["registro"]), true);
                    foreach ($list as &$item) {
                        $item["Importe"] = floatval($item["Importe"]);
                    } 
                    fastexcel($list)->export(Storage::disk('descargas')->path('').$nombrefile);
                    $result["nombrefile"] = $nombrefile;
                }else{
                    $result["res"] = 0;
                    $result["msj"] = "No existe información para descargar.";
                }

                return response()->json($result);

                break;
            case 'DownloadExcelMaestroContratos':
                $info[0] = 19;
                $info[1] = is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                $info[2] = is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));
                $info[6] = is_null($request->input('flg_aprobados')) ? '' : trim($request->input('flg_aprobados'));
                /* MAE CONTRATO */
                $info[3] = is_null($request->input('proceso')) ? '' : trim($request->input('proceso'));
                $info[4] = is_null($request->input('contrato')) ? '' : trim($request->input('contrato'));
                $info[5] = is_null($request->input('idestadocontrato')) ? '' : trim($request->input('idestadocontrato'));
                $info[7] = is_null($request->input('dias_demora')) ? '' : trim($request->input('dias_demora'));
                $info[8] = is_null($request->input('flg_fianza')) ? '' : trim($request->input('flg_fianza'));
                $info[30] = 1;

                $data   = $model->consultarData($info);unset($info);

                $result["res"] = 1;
                $result["msj"] = "";
                $result["nombrefile"] = "";
                if (count($data["registro"]) > 0){
                    $nombrefile = 'Reporte_Maestro_Contratos_'.date('YmdHis').'.xlsx';
                    $list = json_decode(json_encode($data["registro"]), true);
                    fastexcel($list)->export(Storage::disk('descargas')->path('').$nombrefile);
                    $result["nombrefile"] = $nombrefile;
                }else{
                    $result["res"] = 0;
                    $result["msj"] = "No existe información para descargar.";
                }

                return response()->json($result);
                break; 
            case 'DownloadExcelDeuda':
                $info[0] = 4;
                $info[1] = is_null($request->input('identregadet')) ? '' : trim($request->input('identregadet'));
                $info[2] = is_null($request->input('txt_pedido')) ? '' : trim($request->input('txt_pedido'));
                $info[3] = is_null($request->input('txt_gr')) ? '' : trim($request->input('txt_gr'));
                $info[4] = is_null($request->input('txt_factura')) ? '' : trim($request->input('txt_factura'));
                $info[6] = is_null($request->input('operador_alias')) ? '' : trim($request->input('operador_alias'));
                $info[7] = is_null($request->input('flg_producto_sagitario')) ? '' : trim($request->input('flg_producto_sagitario'));
                $info[8] = is_null($request->input('idpedidocab')) ? '' : trim($request->input('idpedidocab'));
                $info[11] = is_null($request->input('idpickingcab')) ? '' : trim($request->input('idpickingcab'));
                $info[12] = is_null($request->input('guia_remision')) ? '' : trim($request->input('guia_remision'));
                $info[13] = is_null($request->input('factura_sunat')) ? '' : trim($request->input('factura_sunat'));
                $info[14] = is_null($request->input('flg_devolucion')) ? '' : trim($request->input('flg_devolucion'));
                $info[15] = is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                $info[16] = is_null($request->input('flg_deuda')) ? '' : trim($request->input('flg_deuda'));
                $info[17] = is_null($request->input('cliente_final')) ? '' : trim($request->input('cliente_final'));
                $info[18] = 1;

                $data   = $model->consultarData($info);unset($info);

                $result["res"] = 1;
                $result["msj"] = "";
                $result["nombrefile"] = "";
                if (count($data["registro"]) > 0){
                    $nombrefile = 'Reporte_Deuda_x_Proceso_'.date('YmdHis').'.xlsx';
                    $list = json_decode(json_encode($data["registro"]), true);
                    fastexcel($list)->export(Storage::disk('descargas')->path('').$nombrefile);
                    $result["nombrefile"] = $nombrefile;
                }else{
                    $result["res"] = 0;
                    $result["msj"] = "No existe información para descargar.";
                }

                return response()->json($result);
                break; 
            case 'Combo':
                $page = is_null($request->input('page')) ? '' : trim($request->input('page'));
                $rows = is_null($request->input('rows')) ? '' : trim($request->input('rows'));

                $info[0]  = is_null($request->input('opcion')) ? '' : trim($request->input('opcion'));
                $info[1]  = is_null($request->input('q')) ? '' : trim($request->input('q'));
                $info[2]  = is_null($request->input('input2')) ? '' : trim($request->input('input2'));
                $info[3]  = is_null($request->input('input3')) ? '' : trim($request->input('input3'));
                $info[4]  = is_null($request->input('input4')) ? '' : trim($request->input('input4'));
                $info[5]  = is_null($request->input('input5')) ? '' : trim($request->input('input5'));
                $info[6]  = is_null($request->input('accion')) ? '' : trim($request->input('accion'));
                $info[7]  = $usuarioid; 

                $info[9] = $page;
                $info[10] = $rows;
                $data = $model->combo_ctf($info);unset($info);

                $result["total"] = isset($data['registro'][0]->total) ? $data['registro'][0]->total : 0;
                $result["rows"] = $data['registro'];

                if($page != '' && $rows != ''){
                    return response()->json($result);
                }

                return response()->json($data['registro']);
                break;
            case 'DownloadExcel':
                $txt_documento= is_null($request->input('txt_documento')) ? '' : trim($request->input('txt_documento'));
                $txt_entrega= is_null($request->input('txt_entrega')) ? '' : trim($request->input('txt_entrega'));
                $txt_sctf= is_null($request->input('txt_sctf')) ? '' : trim($request->input('txt_sctf'));
                $txt_ctf= is_null($request->input('txt_ctf')) ? '' : trim($request->input('txt_ctf'));
                $fechadesde= is_null($request->input('fechadesde')) ? '' : trim($request->input('fechadesde'));
                $fechadesde = $this->empresaFormato->formatDateIn($fechadesde);
                $fechafin = is_null($request->input('fechafin')) ? '' : trim($request->input('fechafin'));
                $fechafin= $this->empresaFormato->formatDateIn($fechafin);

                if($txt_documento != '' || $txt_entrega != '' || $txt_sctf != '' || $txt_ctf != ''){
                    $fechadesde = '';
                    $fechafin = '';
                }

                $info[0] = 27;
                // PROCESO
                $info[1] = $fechadesde;
                $info[2] = $fechafin;
                $info[3] = is_null($request->input('txt_documento')) ? '' : trim($request->input('txt_documento'));
                $info[4] = is_null($request->input('codigo_org_ventas')) ? '' : trim($request->input('codigo_org_ventas'));
                $info[5] = is_null($request->input('codigo_cliente')) ? '' : trim($request->input('codigo_cliente'));
                $info[6] = is_null($request->input('codigo_mot_pedido')) ? '' : trim($request->input('codigo_mot_pedido'));
                $info[7] = is_null($request->input('codigo_canal_dist')) ? '' : trim($request->input('codigo_canal_dist'));
                $info[8] = is_null($request->input('codigo_region')) ? '' : trim($request->input('codigo_region'));
                $info[11] = is_null($request->input('codigo_producto')) ? '' : trim($request->input('codigo_producto'));
                $info[12] = is_null($request->input('codigo_grupo_art')) ? '' : trim($request->input('codigo_grupo_art'));
                $info[13] = is_null($request->input('codigo_motivo_rechazo')) ? '' : trim($request->input('codigo_motivo_rechazo'));
                $info[23] = is_null($request->input('txt_denominacion')) ? '' : trim($request->input('txt_denominacion'));
                $info[21] = is_null($request->input('flg_8uit')) ? '' : trim($request->input('flg_8uit'));
                // ENTREGA
                $info[14] = is_null($request->input('txt_entrega')) ? '' : trim($request->input('txt_entrega'));
                $info[15] = is_null($request->input('codigo_dst_mercancia')) ? '' : trim($request->input('codigo_dst_mercancia'));
                $info[16] = is_null($request->input('txt_contrato')) ? '' : trim($request->input('txt_contrato'));
                // PEDIDO
                $info[17] = is_null($request->input('txt_pedido')) ? '' : trim($request->input('txt_pedido'));
                $info[18] = is_null($request->input('txt_gr')) ? '' : trim($request->input('txt_gr'));
                $info[19] = is_null($request->input('txt_factura')) ? '' : trim($request->input('txt_factura'));
                // CARTA FIANZA
                $info[20] = is_null($request->input('txt_ctf')) ? '' : trim($request->input('txt_ctf'));
                $info[24] = is_null($request->input('txt_sctf')) ? '' : trim($request->input('txt_sctf'));
                $info[25] = is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                $info[26] = is_null($request->input('operador_alias')) ? '' : trim($request->input('operador_alias'));

                // NIVEL CUMPLIMIENTO
                $info[27] = is_null($request->input('anio_nc')) ? '' : trim($request->input('anio_nc'));
                $info[28] = is_null($request->input('mes_nc')) ? '' : trim($request->input('mes_nc'));
                $info[29] = is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));
                $info[30] = is_null($request->input('pendiente_atencion')) ? '' : trim($request->input('pendiente_atencion'));
                $info[31]= is_null($request->input('fecha_desde_cnt_nc')) ? '' : trim($request->input('fecha_desde_cnt_nc'));
                $info[31] = $this->empresaFormato->formatDateIn($info[31]);
                $info[32] = is_null($request->input('fecha_fin_cnt_nc')) ? '' : trim($request->input('fecha_fin_cnt_nc'));
                $info[32]= $this->empresaFormato->formatDateIn($info[32]);
                $info[33] = is_null($request->input('identregacab')) ? '' : trim($request->input('identregacab'));
                $info[34] = is_null($request->input('nro_entrega')) ? '' : trim($request->input('nro_entrega'));
                $info[35] = is_null($request->input('idmaeproducto')) ? '' : trim($request->input('idmaeproducto'));
                //$info[25] = is_null($request->input('operador_alias')) ? '' : trim($request->input('operador_alias'));
                $info[36] = is_null($request->input('tipo_producto')) ? '' : trim($request->input('tipo_producto'));
                $info[37] = is_null($request->input('idpedidocab')) ? '' : trim($request->input('idpedidocab'));
                $info[38] = is_null($request->input('idpickingcab')) ? '' : trim($request->input('idpickingcab'));

                $data   = $model->consultarData($info);unset($info);
                
                $result["res"] = 1;
                $result["msj"] = "";
                $result["nombrefile"] = "";
                if (count($data["registro"]) > 0){
                    $nombrefile = 'Reporte_Consulta_Integral_Proceso_'.date('YmdHis').'.xlsx';
                    $list = json_decode(json_encode($data["registro"]), true);
                    foreach ($list as &$item) {
                        $item["Importe Proceso"] = floatval($item["Importe Proceso"]);
                        $item["Cantidad"] = floatval($item["Cantidad"]);
                        $item["Importe Producto"] = floatval($item["Importe Producto"]);
                        $item["Importe Entrega"] = floatval($item["Importe Entrega"]);
                        $item["Importe Pedido ACF"] = floatval($item["Importe Pedido ACF"]);
                        $item["Importe S/IGV"] = floatval($item["Importe S/IGV"]);
                        $item["Importe C/IGV"] = floatval($item["Importe C/IGV"]);
                        $item["Importe Adjudicado CTF"] = floatval($item["Importe Adjudicado CTF"]);
                        $item["Importe CTF"] = floatval($item["Importe CTF"]);
                        $item["Monto Contrato"] = floatval($item["Monto Contrato"]);
                    }
                    fastexcel($list)->export(Storage::disk('descargas')->path('').$nombrefile);
                    $result["nombrefile"] = $nombrefile;
                }else{
                    $result["res"] = 0;
                    $result["msj"] = "No existe información para descargar.";
                }

                return response()->json($result);
                break;
            case 'DownloadAdjuntos':
                $info[0] = 13;
                $info[2] = is_null($request->input('_idadjuntocartafianza')) ? '' : trim($request->input('_idadjuntocartafianza'));

                $data   = $model->consultarData($info);unset($info);
                $data = $data['registro'];

                if(count($data) == 0){
                    return response()->json('No se encontraron archivos para descargar.');
                }

                $ruta_file = $data[0]->ruta_descarga;
                
                if(!Storage::disk('sftp_carta_fianza')->exists($ruta_file)){
                    return response()->json('El archivo no existe.');
                }

                return Storage::disk('sftp_carta_fianza')->download($ruta_file);
                break;
            case 'DownloadAdjuntosCNT':
                $info[0] = 21;
                $info[2] = is_null($request->input('_idadjuntocontrato')) ? '' : trim($request->input('_idadjuntocontrato'));

                $data   = $model->consultarData($info);unset($info);
                $data = $data['registro'];

                if(count($data) == 0){
                    return response()->json('No se encontraron archivos para descargar.');
                }

                $ruta_file = $data[0]->ruta_descarga;
                
                if(!Storage::disk('sftp_contrato')->exists($ruta_file)){
                    return response()->json('El archivo no existe.');
                }

                return Storage::disk('sftp_contrato')->download($ruta_file);
                break;
            case 'visualizacionPrevia':
                $ruta_file = is_null($request->input('_path')) ? '' : trim($request->input('_path'));
                $filename = is_null($request->input('_filename')) ? '' : trim($request->input('_filename'));
                $flg_tipo = is_null($request->input('_flg_tipo')) ? '' : trim($request->input('_flg_tipo'));
                $operador_alias = is_null($request->input('_operador_alias')) ? '' : trim($request->input('_operador_alias'));
                $sftp = ($flg_tipo == 6 || $flg_tipo == 7) ? 'sftp_contrato' : 'sftp_carta_fianza';
                if($sftp == 'sftp_contrato' && str_contains($operador_alias, 'SGT')){
                    $sftp = 'sftp_contrato_sagitario';
                }
                //dd($ruta_file,$filename,$flg_importacion);
                return $this->visualizacion_previa_archivo($sftp,$ruta_file.$filename,$filename);

                break;
            case 'DescargarArchivo':
                $name = $request->input('_nombrefile');
                $nuevonombre = $name;
                return Storage::download('descargas/'.$name, $nuevonombre);
                break;
            default:
                break;
        }
    }

    public function mantenimiento(Request $request)
    {
        $_acc = $request->input('_acc');
        $model   = new Instituciones();
        $usuarioid = $this->iduser;
        $ip_maq = $request->ip();
        switch ($_acc) {
            case 'registrarSolicitudCTF':
                // VARIABLES
                $detalle= is_null($request->input('objetoDet')) ? [] : json_decode($request->input('objetoDet'));
                $detalle = json_decode(json_encode($detalle), true);
                $beneficiario= is_null($request->input('beneficiario')) ? '' : trim($request->input('beneficiario'));
                $garantizado= is_null($request->input('garantizado')) ? '' : trim($request->input('garantizado'));
                $importe_adjudicado= is_null(floatval($request->input('importe_adjudicado'))) ? '' : floatval($request->input('importe_adjudicado'));
                $importe_numero= is_null(floatval($request->input('importe_numero'))) ? '' : floatval($request->input('importe_numero'));
                $importe_ctf= is_null(floatval($request->input('importe_ctf'))) ? '' : floatval($request->input('importe_ctf'));
                $respaldo= is_null($request->input('respaldo')) ? '' : trim($request->input('respaldo'));
                $fecha_vcto = is_null($request->input('fecha_vcto')) ? '' : trim($request->input('fecha_vcto'));
                $fecha_vcto = $this->empresaFormato->formatDateIn($fecha_vcto);
                $fecha_esperada = is_null($request->input('fecha_esperada')) ? '' : trim($request->input('fecha_esperada'));
                $fecha_esperada = $this->empresaFormato->formatDateIn($fecha_esperada);
                $files_add = $request->file('archivos_add') ?? []; // Multiple

                /* $array_idprocesodet = array_column($detalle, 'idprocesodet');
                $array_idprocesodet_filtrado = array_filter($array_idprocesodet, function($valor) {
                    return $valor != 0;
                });
                $array_idprocesodet = implode(',', $array_idprocesodet_filtrado); */
                /* Logica para quedarme con Detalle único */
                $collection = collect($detalle);
                $uniqueCollection = $collection->keyBy('idprocesodet')->values();
                $detalle_unq = $uniqueCollection->toArray();
                $o_nres = 1;
                $o_msj = '';
    
                // REGISTRO DE SOLICITUD
                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 7;
                    $info[1] = $beneficiario;
                    $info[2] = $garantizado;
                    $info[3] = $importe_adjudicado;
                    $info[4] = $importe_numero;
                    $info[5] = $importe_ctf;
                    $info[6] = $respaldo;
                    $info[7] = $fecha_vcto;
                    $info[8] = $fecha_esperada;
                    //$info[9] = $array_idprocesodet;
    
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [7 - CTF]';
                    $idcartafianza = $rpta['registro'][0]->idcartafianza ?? 0;
                    $codigo_ctf = $rpta['registro'][0]->codigo_ctf ?? '';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al registrar la solicitud de carta fianza.';
                    }  
                }
                // INSERTO PROCESO x CTF
                if($o_nres == 1){
                    foreach($detalle_unq as $value){
                        $info[0] = 10;
                        $info[1] = $idcartafianza;
                        $info[2] = $value['idprocesodet'];
                        $info[3] = $value['cantidad_seleccionada'];
        
                        $info[19] = $usuarioid;
                        $info[20] = $ip_maq;
                        //dd($info);
    
                        $rpta = $model->mantenimientoData($info);unset($info);
                        $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                        $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [10 - CTF]';
                        if($o_nres == 0){
                            DB::rollBack();
                            $o_nres = 0;
                            $o_msj = ($o_msj != '') ? $o_msj : 'Error al registrar la solicitud de carta fianza con proceso.';
                            break;
                        }  
                    }
                }
                /* VALIDAR SI ES POSIBLE ADJUNTAR LOS ARCHIVOS */
                if($o_nres == 1){
                    $rpta = $this->registrarAdjunto($files_add,$codigo_ctf,$idcartafianza,1,$usuarioid,$ip_maq,2); // 2: tipo adjunto "Solicitud"
                    $o_nres = $rpta['o_nres'] ?? 0;
                    $o_msj = $rpta['o_msj'] ?? 'Error respuesta en función de adjuntos';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error en función de adjuntos.';
                    }  
                }  

                if($o_nres == 1){
                    $mail = $this->enviarMailSolicitud($idcartafianza); // CORREO DE REGISTRO
                    $rpta['o_nres'] = $mail['o_nres'] ?? 0;
                    $rpta['o_msj'] = $mail['o_msj'] ?? 'Falló envío de correo.';
                }
                
                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }else{
                    DB::rollBack();
                    $o_nres = 0;
                    $o_msj = ($o_msj != '') ? $o_msj : 'Error de envío de correo.';
                }

                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Solicitud registrada con éxito ['.$codigo_ctf.'].' : $o_msj;

                return response()->json($result);
            break;
            case 'asociarCTFPRC':
                // VARIABLES
                $idprocesocab= is_null($request->input('idprocesocab')) ? '' : $request->input('idprocesocab');
                $idcartafianzafinal= is_null($request->input('idcartafianzafinal')) ? '' : $request->input('idcartafianzafinal');
                $detalle= is_null($request->input('objetoDet')) ? [] : json_decode($request->input('objetoDet'));
                $detalle = json_decode(json_encode($detalle), true);
                $beneficiario= is_null($request->input('beneficiario')) ? '' : trim($request->input('beneficiario'));
                $importe_adjudicado= is_null(floatval($request->input('importe_adjudicado'))) ? '' : floatval($request->input('importe_adjudicado'));
                $importe_numero= is_null(floatval($request->input('importe_numero'))) ? '' : floatval($request->input('importe_numero'));
                $importe_ctf= is_null(floatval($request->input('importe_ctf'))) ? '' : floatval($request->input('importe_ctf'));

                /* Logica para quedarme con Detalle único */
                $collection = collect($detalle);
                $uniqueCollection = $collection->keyBy('idprocesodet')->values();
                $detalle_unq = $uniqueCollection->toArray();
                $o_nres = 1;
                $o_msj = '';   
                // REGISTRO DE ASOCIACION DE SOLICITUD
                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 32;
                    $info[1] = $idcartafianzafinal;
                    $info[2] = $beneficiario;
                    $info[3] = $importe_adjudicado;
                    $info[4] = $importe_numero;
                    $info[5] = $importe_ctf;

                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [7 - CTF]';
                    $idcartafianza = $rpta['registro'][0]->idcartafianza ?? 0;
                    $codigo_ctf = $rpta['registro'][0]->codigo_ctf ?? '';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al registrar la solicitud de carta fianza.';
                    }  
                }

                // INSERTO PROCESO x CTF
                if($o_nres == 1){
                    foreach($detalle_unq as $value){
                        $info[0] = 10;
                        $info[1] = $idcartafianza;
                        $info[2] = $value['idprocesodet'];
                        $info[3] = $value['cantidad_seleccionada'];
        
                        $info[19] = $usuarioid;
                        $info[20] = $ip_maq;
    
                        $rpta = $model->mantenimientoData($info);unset($info);
                        $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                        $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [10 - CTF]';
                        if($o_nres == 0){
                            DB::rollBack();
                            $o_nres = 0;
                            $o_msj = ($o_msj != '') ? $o_msj : 'Error al registrar la solicitud de carta fianza con proceso.';
                            break;
                        }  
                    }
                }

                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }
                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Proceso asociado con éxito. Solicitud generada: ['.$codigo_ctf.'].' : $o_msj;

                return response()->json($result);
            break;
            case 'cambiarEstado':
                $idcartafianza= is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));
                $idestadocartafianza= is_null($request->input('idestadocartafianza')) ? '' : trim($request->input('idestadocartafianza'));
                $accion= is_null($request->input('accion')) ? '' : trim($request->input('accion'));
                $idcartafianzafinal= is_null($request->input('idcar$idcartafianzafinal')) ? '' : trim($request->input('idcar$idcartafianzafinal'));

                $o_nres = 1;
                $o_msj = '';

                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 8;
                    $info[1] = $idcartafianza;
                    $info[2] = $idestadocartafianza;
                    $info[3] = $accion;
                 
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [8 - CTF]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al cambiar estado de carta fianza.';
                    }  
                }

                if(isset($idcartafianzafinal) && $idcartafianzafinal != ''){
                    $mail = $this->enviarMailFinal($idcartafianzafinal); // CORREO DE REGISTRO
                    $rpta['o_nres'] = $mail['o_nres'] ?? 0;
                    $rpta['o_msj'] = $mail['o_msj'] ?? 'Falló envío de correo.';
                }else{
                    $mail = $this->enviarMailSolicitud($idcartafianza); // CORREO DE REGISTRO
                    $rpta['o_nres'] = $mail['o_nres'] ?? 0;
                    $rpta['o_msj'] = $mail['o_msj'] ?? 'Falló envío de correo.';
                }

                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }else{
                    DB::rollBack();
                    $o_nres = 0;
                    $o_msj = ($o_msj != '') ? $o_msj : 'Error de envío de correo.';
                }

                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Todo conforme.' : $o_msj;

                return response()->json($result);
            break;
            case 'cambiarEstado_F':
                $idcartafianzafinal= is_null($request->input('idcartafianzafinal')) ? '' : trim($request->input('idcartafianzafinal'));
                $idestadocartafianzafinal= is_null($request->input('idestadocartafianzafinal')) ? '' : trim($request->input('idestadocartafianzafinal'));
                $accion= is_null($request->input('accion')) ? '' : trim($request->input('accion'));
                $o_nres = 1;
                $o_msj = '';

                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 13;
                    $info[1] = $idcartafianzafinal;
                    $info[2] = $idestadocartafianzafinal;
                    $info[3] = $accion;
                 
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [13 - CTF]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al cambiar estado de carta fianza.';
                    }  
                }

                if($o_nres == 1){
                    $mail = $this->enviarMailFinal($idcartafianzafinal); // CORREO DE REGISTRO
                    $rpta['o_nres'] = $mail['o_nres'] ?? 0;
                    $rpta['o_msj'] = $mail['o_msj'] ?? 'Falló envío de correo.';
                }

                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }else{
                    DB::rollBack();
                    $o_nres = 0;
                    $o_msj = ($o_msj != '') ? $o_msj : 'Error de envío de correo.';
                }

                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Todo conforme.' : $o_msj;

                return response()->json($result);
            break;
            case 'pendienteRNV':
                $idcartafianzafinal= is_null($request->input('idcartafianzafinal')) ? '' : trim($request->input('idcartafianzafinal'));
                $nro_periodos= is_null($request->input('nro_periodos')) ? '' : trim($request->input('nro_periodos'));

                $o_nres = 1;
                $o_msj = '';

                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 30;
                    $info[1] = $idcartafianzafinal;
                    $info[2] = $nro_periodos;
                 
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [30 - CTF]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al gestionar renovación.';
                    }  
                }

                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }
                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Todo conforme.' : $o_msj;

                return response()->json($result);
            break;
            case 'ganadorEvaluacionBancaria':
                $idcartafianza= is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));
                $idestadocartafianza= is_null($request->input('idestadocartafianza')) ? '' : trim($request->input('idestadocartafianza'));
                $fechaemision = is_null($request->input('fechaemision')) ? '' : trim($request->input('fechaemision'));
                $fechaemision= $this->empresaFormato->formatDateIn($fechaemision);
                $dias= is_null($request->input('dias')) ? '' : trim($request->input('dias'));
                $periodo= is_null($request->input('periodo')) ? '' : trim($request->input('periodo'));
                $contratista= is_null($request->input('contratista')) ? '' : trim($request->input('contratista'));
                $idbanco= is_null($request->input('idbanco')) ? '' : trim($request->input('idbanco'));
                $porte= is_null($request->input('porte')) ? '' : trim($request->input('porte')); // n
                $minimo= is_null($request->input('minimo')) ? '' : trim($request->input('minimo')); // n
                $tipo_cobro= is_null($request->input('tipo_cobro')) ? '' : trim($request->input('tipo_cobro')); // n
                $tasa= is_null($request->input('tasa')) ? '' : trim($request->input('tasa')); 
                $linea= is_null($request->input('linea')) ? '' : trim($request->input('linea'));
                $cobro15= is_null(floatval($request->input('cobro15'))) ? '' : floatval($request->input('cobro15')); // n
                $importe= is_null(floatval($request->input('importe'))) ? '' : floatval($request->input('importe'));
                $flg_renovacion= is_null($request->input('flg_renovacion')) ? '' : trim($request->input('flg_renovacion'));
                //dd($cobro15,$importe);
                
                $o_nres = 1;
                $o_msj = '';

                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 9;
                    $info[1] = $idcartafianza;
                    $info[2] = $idestadocartafianza;
                    $info[3] = $idbanco;
                    $info[5] = $importe;
                    $info[6] = $fechaemision;
                    $info[7] = $dias;
                    $info[8] = $periodo;
                    $info[9] = $contratista;
                    $info[10] = $linea;
                    $info[11] = $tasa;
                    // NUEVO
                    $info[12] = $porte;
                    $info[13] = $minimo;
                    $info[14] = $tipo_cobro;
                    $info[15] = $cobro15;
                    $info[16] = $flg_renovacion;

                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);
                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [9 - CTF]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al cambiar estado de carta fianza.';
                    }  
                }

                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }
                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Todo conforme.' : $o_msj;

                return response()->json($result);
            break;
            case 'cargarAdjuntoCTF':
                $idcartafianza= is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));
                $idestadocartafianza= is_null($request->input('idestadocartafianza')) ? '' : trim($request->input('idestadocartafianza'));
                $codigo_cartafianza= is_null($request->input('codigo_cartafianza')) ? '' : trim($request->input('codigo_cartafianza'));
                $idtipoadjunto= is_null($request->input('idtipoadjunto')) ? '' : trim($request->input('idtipoadjunto'));

                $files_add = $request->file('archivos_add') ?? []; // Multiple
                $o_nres = 1;
                $o_msj = '';

                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    if($idtipoadjunto == 3){ // CUANDO SUBIO LA SOLICITUD DEL BANCO
                        $info[0] = 8;
                        $info[1] = $idcartafianza;
                        $info[2] = $idestadocartafianza;
                        $info[3] = 1;
                    
                        $info[19] = $usuarioid;
                        $info[20] = $ip_maq;

                        $rpta = $model->mantenimientoData($info);unset($info);
                        $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                        $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [8 - CTF]';
                        if($o_nres == 0){
                            DB::rollBack();
                            $o_nres = 0;
                            $o_msj = ($o_msj != '') ? $o_msj : 'Error al cambiar estado de carta fianza.';
                        }  
                    }
                    if($o_nres == 1){
                        $rpta = $this->registrarAdjunto($files_add,$codigo_cartafianza,$idcartafianza,$idestadocartafianza,$usuarioid,$ip_maq,$idtipoadjunto);
                        $o_nres = $rpta['o_nres'] ?? 0;
                        $o_msj = $rpta['o_msj'] ?? 'Error respuesta en función de adjuntos';
                        if($o_nres == 0){
                            DB::rollBack();
                            $o_nres = 0;
                            $o_msj = ($o_msj != '') ? $o_msj : 'Error en función de adjuntos.';
                        }  
                    } 
                }
                
                if($idtipoadjunto == 3 && $idestadocartafianza == 3){ // CUANDO SUBIO LA SOLICITUD DEL BANCO
                    $mail = $this->enviarMailSolicitud($idcartafianza); // CORREO DE REGISTRO
                    $rpta['o_nres'] = $mail['o_nres'] ?? 0;
                    $rpta['o_msj'] = $mail['o_msj'] ?? 'Falló envío de correo.';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error de envío de correo.';
                    }
                }

                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }
                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Todo conforme.' : $o_msj;

                return response()->json($result);
            break;
            case 'cargarAdjuntoCTF_final':
                $idcartafianzafinal= is_null($request->input('idcartafianzafinal')) ? '' : trim($request->input('idcartafianzafinal'));
                $idestadocartafianzafinal= is_null($request->input('idestadocartafianzafinal')) ? '' : trim($request->input('idestadocartafianzafinal'));
                $codigo_cartafianza= is_null($request->input('codigo_cartafianza')) ? '' : trim($request->input('codigo_cartafianza'));
                $idtipoadjunto= is_null($request->input('idtipoadjunto')) ? '' : trim($request->input('idtipoadjunto'));

                $files_add = $request->file('archivos_add') ?? []; // Multiple
                $o_nres = 1;
                $o_msj = '';

                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    if($idtipoadjunto == 9){ // CUANDO SUBIO LA EVALUACIÓN BANCARIA DE LA RENOVACIÓN
                        $info[0] = 13;
                        $info[1] = $idcartafianzafinal;
                        $info[2] = $idestadocartafianzafinal;
                        $info[3] = $idtipoadjunto;
                    
                        $info[19] = $usuarioid;
                        $info[20] = $ip_maq;

                        $rpta = $model->mantenimientoData($info);unset($info);
                        $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                        $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [1 - CTF]';
                        if($o_nres == 0){
                            DB::rollBack();
                            $o_nres = 0;
                            $o_msj = ($o_msj != '') ? $o_msj : 'Error al cambiar estado de carta fianza.';
                        }  
                    }
                    if($o_nres == 1){
                        $rpta = $this->registrarAdjunto($files_add,$codigo_cartafianza,'',$idestadocartafianzafinal,$usuarioid,$ip_maq,$idtipoadjunto,$idcartafianzafinal);
                        $o_nres = $rpta['o_nres'] ?? 0;
                        $o_msj = $rpta['o_msj'] ?? 'Error respuesta en función de adjuntos';
                        if($o_nres == 0){
                            DB::rollBack();
                            $o_nres = 0;
                            $o_msj = ($o_msj != '') ? $o_msj : 'Error en función de adjuntos.';
                        }  
                    } 
                }
                
                if($idtipoadjunto == 9){ // CUANDO SUBIO LA EVALUACIÓN BANCARIA DE LA RENOVACIÓN
                    $mail = $this->enviarMailFinal($idcartafianzafinal); // CORREO DE REGISTRO
                    $rpta['o_nres'] = $mail['o_nres'] ?? 0;
                    $rpta['o_msj'] = $mail['o_msj'] ?? 'Falló envío de correo.';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error de envío de correo.';
                    }
                }


                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }
                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Todo conforme.' : $o_msj;

                return response()->json($result);
            break;
            case 'cargarAdjuntoCNT':
                $idcontrato= is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));
                $contrato= is_null($request->input('contrato')) ? '' : trim($request->input('contrato'));
                $idtipoadjunto= is_null($request->input('idtipoadjunto')) ? '' : trim($request->input('idtipoadjunto'));
                //dd($idtipoadjunto);
                $files_add = $request->file('archivos_add') ?? []; // Multiple
                $o_nres = 1;
                $o_msj = '';

                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    //registrarAdjunto($files_add=[],$codigo_ctf,$idcartafianza='',$idestadocartafianza,$usuarioid,$ip_maq,$idtipoadjunto,$idcartafianzafinal='',$flg_contrato=0)
                    $rpta = $this->registrarAdjunto($files_add,$contrato,$idcontrato,1,$usuarioid,$ip_maq,$idtipoadjunto,'',1); // 1: tipo adjunto "Regular"
                    $o_nres = $rpta['o_nres'] ?? 0;
                    $o_msj = $rpta['o_msj'] ?? 'Error respuesta en función de adjuntos';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error en función de adjuntos.';
                    }  
                } 
            
                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }
                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Todo conforme.' : $o_msj;

                return response()->json($result);
            break;
            case 'actualizarCTF':
                $idcartafianza= is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));
                $idestadocartafianza= is_null($request->input('idestadocartafianza')) ? '' : trim($request->input('idestadocartafianza'));
                $importe_ctf_final= is_null($request->input('importe_ctf_final')) ? '' : trim($request->input('importe_ctf_final'));
                $fecha_emision_final = is_null($request->input('fecha_emision_final')) ? '' : trim($request->input('fecha_emision_final'));
                $fecha_emision_final= $this->empresaFormato->formatDateIn($fecha_emision_final);
                $fecha_vcto_final = is_null($request->input('fecha_vcto_final')) ? '' : trim($request->input('fecha_vcto_final'));
                $fecha_vcto_final= $this->empresaFormato->formatDateIn($fecha_vcto_final);
                $nro_ctf= is_null($request->input('nro_ctf')) ? '' : trim($request->input('nro_ctf'));
                $codigo_cartafianza= is_null($request->input('codigo_cartafianza')) ? '' : trim($request->input('codigo_cartafianza'));
                $files_add = $request->file('archivos_add') ?? []; // Multiple
                $idtipoadjunto = 4;

                $o_nres = 1;
                $o_msj = '';

                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 12;
                    $info[1] = $idcartafianza;
                    $info[2] = $nro_ctf;
                    $info[3] = $importe_ctf_final;
                    $info[4] = $fecha_emision_final;
                    $info[5] = $fecha_vcto_final;
                
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [12 - CTF]';
                    $idcartafianzafinal = $rpta['registro'][0]->idcartafianzafinal ?? 0;
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al cambiar estado de carta fianza.';
                    }else{
                        $info[0] = 8;
                        $info[1] = $idcartafianza;
                        $info[2] = $idestadocartafianza;
                        $info[3] = 1;
                    
                        $info[19] = $usuarioid;
                        $info[20] = $ip_maq;

                        $rpta = $model->mantenimientoData($info);unset($info);
                        $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                        $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [8 - CTF]';
                        if($o_nres == 0){
                            DB::rollBack();
                            $o_nres = 0;
                            $o_msj = ($o_msj != '') ? $o_msj : 'Error al cambiar estado de carta fianza.';
                        }else{
                            $rpta = $this->registrarAdjunto($files_add,$codigo_cartafianza,$idcartafianza,$idestadocartafianza,$usuarioid,$ip_maq,$idtipoadjunto);
                            $o_nres = $rpta['o_nres'] ?? 0;
                            $o_msj = $rpta['o_msj'] ?? 'Error respuesta en función de adjuntos';
                            if($o_nres == 0){
                                DB::rollBack();
                                $o_nres = 0;
                                $o_msj = ($o_msj != '') ? $o_msj : 'Error en función de adjuntos.';
                            }  
                        }  
                    }  
                }
                
                if($o_nres == 1){
                    $mail = $this->enviarMailFinal($idcartafianzafinal); // CORREO DE REGISTRO
                    $rpta['o_nres'] = $mail['o_nres'] ?? 0;
                    $rpta['o_msj'] = $mail['o_msj'] ?? 'Falló envío de correo.';
                }

                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }else{
                    DB::rollBack();
                    $o_nres = 0;
                    $o_msj = ($o_msj != '') ? $o_msj : 'Error de envío de correo.';
                }

                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Todo conforme.' : $o_msj;

                return response()->json($result);
            break;
            case 'actualizarRespaldo':
                $idcartafianza= is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));
                $idestadocartafianza= is_null($request->input('idestadocartafianza')) ? '' : trim($request->input('idestadocartafianza'));
                $txt_respaldo= is_null($request->input('txt_respaldo')) ? '' : trim($request->input('txt_respaldo'));

                $o_nres = 1;
                $o_msj = '';

                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 14;
                    $info[1] = $idcartafianza;
                    $info[2] = $idestadocartafianza;
                    $info[3] = $txt_respaldo;
                 
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [14 - CTF]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al cambiar estado de carta fianza.';
                    }  
                }

                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }
                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Todo conforme.' : $o_msj;

                return response()->json($result);
            break;
            case 'editarSolicitud':
                // VARIABLES
                $idcartafianza= is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));
                $beneficiario= is_null($request->input('beneficiario')) ? '' : trim($request->input('beneficiario'));
                $garantizado= is_null($request->input('garantizado')) ? '' : trim($request->input('garantizado'));
                $importe_ctf= is_null(floatval($request->input('importe_ctf'))) ? '' : floatval($request->input('importe_ctf'));
                $respaldo= is_null($request->input('respaldo')) ? '' : trim($request->input('respaldo'));
                $fecha_vcto = is_null($request->input('fecha_vcto')) ? '' : trim($request->input('fecha_vcto'));
                $fecha_vcto = $this->empresaFormato->formatDateIn($fecha_vcto);
                $fecha_esperada = is_null($request->input('fecha_esperada')) ? '' : trim($request->input('fecha_esperada'));
                $fecha_esperada = $this->empresaFormato->formatDateIn($fecha_esperada);

                $o_nres = 1;
                $o_msj = '';
    
                // REGISTRO DE SOLICITUD
                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 15;
                    $info[1] = $idcartafianza;
                    $info[2] = $beneficiario;
                    $info[3] = $garantizado;
                    $info[4] = $respaldo;
                    $info[5] = $importe_ctf;
                    $info[6] = $fecha_vcto;
                    $info[7] = $fecha_esperada;
    
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [15 - CTF]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al editar la solicitud de carta fianza.';
                    }  
                }
                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }
                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Solicitud editada con éxito.' : $o_msj;

                return response()->json($result);
            break;
            case 'guardarObs':
                // VARIABLES
                $idcartafianza= is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));
                $idcartafianzafinal= is_null($request->input('idcartafianzafinal')) ? '' : trim($request->input('idcartafianzafinal'));
                $idtipoobs= is_null($request->input('idtipoobs')) ? '' : trim($request->input('idtipoobs'));
                $o_nres = 1;
                $o_msj = '';
                

                //dd("idcartafianza:".$idcartafianza."-idcartafianzafinal".$idcartafianzafinal);
                // REGISTRO DE SOLICITUD
                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 16;
                    $info[1] = $idcartafianzafinal;
                    $info[2] = $idtipoobs;
    
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [16 - CTF]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al editar la solicitud de carta fianza.';
                    }  
                }

                if($o_nres == 1){
                    $mail = $this->enviarMailSolicitud($idcartafianza); // CORREO DE REGISTRO
                    $rpta['o_nres'] = $mail['o_nres'] ?? 0;
                    $rpta['o_msj'] = $mail['o_msj'] ?? 'Falló envío de correo.';
                }

                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }else{
                    DB::rollBack();
                    $o_nres = 0;
                    $o_msj = ($o_msj != '') ? $o_msj : 'Error de envío de correo.';
                }

                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Todo conforme.' : $o_msj;

                return response()->json($result);
            break;
            case 'editarCF':
                // VARIABLES
                $idcartafianzafinal= is_null($request->input('idcartafianzafinal')) ? '' : trim($request->input('idcartafianzafinal'));
                $importe_ctf_final= is_null($request->input('importe_ctf_final')) ? '' : trim($request->input('importe_ctf_final'));
                $fecha_emision_final = is_null($request->input('fecha_emision_final')) ? '' : trim($request->input('fecha_emision_final'));
                $fecha_emision_final = $this->empresaFormato->formatDateIn($fecha_emision_final);
                $fecha_vcto_final = is_null($request->input('fecha_vcto_final')) ? '' : trim($request->input('fecha_vcto_final'));
                $fecha_vcto_final = $this->empresaFormato->formatDateIn($fecha_vcto_final);
                $nro_ctf= is_null($request->input('nro_ctf')) ? '' : trim($request->input('nro_ctf'));
                $o_nres = 1;
                $o_msj = '';
    
                // REGISTRO DE SOLICITUD
                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 17;
                    $info[1] = $idcartafianzafinal;
                    $info[2] = $importe_ctf_final;
                    $info[3] = $fecha_emision_final;
                    $info[4] = $fecha_vcto_final;
                    $info[5] = $nro_ctf;
    
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [18 - CTF]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al editar la solicitud de carta fianza.';
                    }  
                }
                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }
                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Carta Fianza editada con éxito.' : $o_msj;

                return response()->json($result);
            break;
            case 'generarReplica':
                // VARIABLES
                $idcartafianza= is_null($request->input('idcartafianza')) ? '' : trim($request->input('idcartafianza'));
                $idcartafianzafinal= is_null($request->input('idcartafianzafinal')) ? '' : trim($request->input('idcartafianzafinal'));
                $detalle= is_null($request->input('objetoDet')) ? [] : json_decode($request->input('objetoDet'));
                $detalle = json_decode(json_encode($detalle), true);
                $o_nres = 1;
                $o_msj = '';
                /* Logica para quedarme con Detalle único */
                $collection = collect($detalle);
                $uniqueCollection = $collection->keyBy('idprocesodet')->values();
                $detalle_unq = $uniqueCollection->toArray();
                
                // Genero Replica y Actualizo estados
                $valor_total = 0;
                foreach($detalle_unq as $value){
                    $info[0] = 2;
                    $info[12] = $value['idprocesodet'];
                    $data   = $model->consultarData($info);unset($info);
                    $data = json_decode(json_encode($data['registro'][0]),true);

                    if(count($data) > 0){
                        $valor_total += ($value['cantidad_seleccionada']*$data['valor_neto'])/$data['cantidad_prevista'];
                    }else{
                        $o_nres = 0;
                        $o_msj = 'No se encontró valores para las posiciones seleccionadas.';
                    }
                }

                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 18;
                    $info[1] = $idcartafianza;
                    $info[2] = $idcartafianzafinal;
                    $info[3] = round($valor_total,2);
                    $info[4] = round((0.1*$valor_total),2);
                    $info[5] = ceil(($valor_total*0.10) / 100) * 100;
    
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [18 - CTF]';
                    $idcartafianza = $rpta['registro'][0]->idcartafianza ?? 0;
                    $codigo_ctf = $rpta['registro'][0]->codigo_ctf ?? '';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al registrar la solicitud de carta fianza.';
                    }  
                }
                // INSERTO PROCESO x CTF
                if($o_nres == 1){
                    foreach($detalle_unq as $value){
                        $info[0] = 10;
                        $info[1] = $idcartafianza;
                        $info[2] = $value['idprocesodet'];
                        $info[3] = $value['cantidad_seleccionada'];
        
                        $info[19] = $usuarioid;
                        $info[20] = $ip_maq;
                        //dd($info);
    
                        $rpta = $model->mantenimientoData($info);unset($info);
                        $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                        $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [1 - CTF]';
                        if($o_nres == 0){
                            DB::rollBack();
                            $o_nres = 0;
                            $o_msj = ($o_msj != '') ? $o_msj : 'Error al registrar la solicitud de carta fianza con proceso.';
                            break;
                        }  
                    }
                }

                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }
                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Replica registrada con éxito ['.$codigo_ctf.'].' : $o_msj;

                return response()->json($result);
            break;
            case 'asociarCNTxCTF':
                // VARIABLES
                $idcontrato= is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));
                $ids_cartafianzafinal= is_null($request->input('ids_cartafianzafinal')) ? '' : trim($request->input('ids_cartafianzafinal'));
                $accion= is_null($request->input('accion')) ? '' : trim($request->input('accion'));
                $o_nres = 1;
                $o_msj = '';
                
                // REGISTRO DE SOLICITUD
                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 20;
                    $info[1] = $idcontrato;
                    $info[2] = $ids_cartafianzafinal;
                    $info[3] = $accion;
    
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [20 - CTF]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al asociar carta fianza al contrato.';
                    }  
                }
                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }
                $mensaje = ($o_nres == 1) ? 'Carta Fianza asociada.' : 'Carta Fianza desasociada.';
                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? $mensaje : $o_msj;

                return response()->json($result);
            break;
            case 'registrarCNT':
                // VARIABLES
                $detalle= is_null($request->input('objetoDet')) ? [] : json_decode($request->input('objetoDet'));
                $detalle = json_decode(json_encode($detalle), true);
                $idprocesocab= is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                $cnt= is_null($request->input('cnt')) ? '' : trim($request->input('cnt'));
                $cnt_sap= is_null($request->input('cnt_sap')) ? '' : trim($request->input('cnt_sap'));
                $idtipocnt= is_null($request->input('idtipocnt')) ? '' : trim($request->input('idtipocnt'));
                $importe_cnt= is_null(floatval($request->input('importe_cnt'))) ? '' : floatval($request->input('importe_cnt'));
                $fecha_emi_cnt = is_null($request->input('fecha_emi_cnt')) ? '' : trim($request->input('fecha_emi_cnt'));
                $fecha_emi_cnt = $this->empresaFormato->formatDateIn($fecha_emi_cnt);
                $fecha_vcto_cnt = is_null($request->input('fecha_vcto_cnt')) ? '' : trim($request->input('fecha_vcto_cnt'));
                $fecha_vcto_cnt = $this->empresaFormato->formatDateIn($fecha_vcto_cnt);
                $files_add = $request->file('archivos_add') ?? []; // Multiple
                /* Logica para quedarme con Detalle único */
                $collection = collect($detalle);
                $uniqueCollection = $collection->keyBy('identregadet')->values();
                $detalle_unq = $uniqueCollection->toArray();

                $o_nres = 1;
                $o_msj = '';
                // REGISTRO DE SOLICITUD
                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 21;
                    $info[1] = $cnt;
                    $info[2] = $cnt_sap;
                    $info[3] = $idtipocnt;
                    $info[4] = $importe_cnt;
                    $info[5] = $fecha_emi_cnt;
                    $info[6] = $fecha_vcto_cnt;
                    //$info[9] = $array_idprocesodet;
    
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [21 - CNT]';
                    $idcontrato = $rpta['registro'][0]->idcontrato ?? 0;
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al registrar el contrato.';
                    }  
                }
                // INSERTO PROCESO x CTF
                if($o_nres == 1){
                    foreach($detalle_unq as $value){
                        $info[0] = 22;
                        $info[1] = $idcontrato;
                        $info[2] = $value['identregadet'];
                        $info[3] = $value['cantidad_seleccionada'];
        
                        $info[19] = $usuarioid;
                        $info[20] = $ip_maq;
                        //dd($info);
    
                        $rpta = $model->mantenimientoData($info);unset($info);
                        $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                        $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [22 - CNT]';
                        if($o_nres == 0){
                            DB::rollBack();
                            $o_nres = 0;
                            $o_msj = ($o_msj != '') ? $o_msj : 'Error al registrar contrato con entregas.';
                            break;
                        }  
                    }
                }
                /* VALIDAR SI ES POSIBLE ADJUNTAR LOS ARCHIVOS */
                if($o_nres == 1){
                    //registrarAdjunto($files_add=[],$codigo_ctf,$idcartafianza='',$idestadocartafianza,$usuarioid,$ip_maq,$idtipoadjunto,$idcartafianzafinal='',$flg_contrato=0)
                    $rpta = $this->registrarAdjunto($files_add,$cnt,$idcontrato,1,$usuarioid,$ip_maq,6,'',1); // 6: tipo adjunto "Contrato"
                    $o_nres = $rpta['o_nres'] ?? 0;
                    $o_msj = $rpta['o_msj'] ?? 'Error respuesta en función de adjuntos';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error en función de adjuntos.';
                    }  
                }  

                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }
                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Contrato registrado con éxito ['.$cnt.'].' : $o_msj;

                return response()->json($result);
            break;
            case 'editarCNT':
                // VARIABLES
                $idcontrato = is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));
                $idprocesocab= is_null($request->input('idprocesocab')) ? '' : trim($request->input('idprocesocab'));
                $cnt= is_null($request->input('cnt')) ? '' : trim($request->input('cnt'));
                $cnt_sap= is_null($request->input('cnt_sap')) ? '' : trim($request->input('cnt_sap'));
                $idtipocnt= is_null($request->input('idtipocnt')) ? '' : trim($request->input('idtipocnt'));
                $importe_cnt= is_null(floatval($request->input('importe_cnt'))) ? '' : floatval($request->input('importe_cnt'));
                $fecha_emi_cnt = is_null($request->input('fecha_emi_cnt')) ? '' : trim($request->input('fecha_emi_cnt'));
                $fecha_emi_cnt = $this->empresaFormato->formatDateIn($fecha_emi_cnt);
                $fecha_vcto_cnt = is_null($request->input('fecha_vcto_cnt')) ? '' : trim($request->input('fecha_vcto_cnt'));
                $fecha_vcto_cnt = $this->empresaFormato->formatDateIn($fecha_vcto_cnt);
                $files_add = $request->file('archivos_add') ?? []; // Multiple

                $o_nres = 1;
                $o_msj = '';
                // REGISTRO DE SOLICITUD
                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 24;
                    $info[1] = $idcontrato;
                    $info[2] = $idtipocnt;
                    $info[3] = $cnt;
                    $info[4] = $importe_cnt;
                    $info[5] = $fecha_emi_cnt;
                    $info[6] = $fecha_vcto_cnt;
                    $info[7] = $cnt_sap;
    
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [21 - CNT]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al registrar el contrato.';
                    }  
                }

                /* VALIDAR SI ES POSIBLE ADJUNTAR LOS ARCHIVOS */
                if($o_nres == 1 && count($files_add) > 0){
                    //registrarAdjunto($files_add=[],$codigo_ctf,$idcartafianza='',$idestadocartafianza,$usuarioid,$ip_maq,$idtipoadjunto,$idcartafianzafinal='',$flg_contrato=0)
                    $rpta = $this->registrarAdjunto($files_add,$cnt,$idcontrato,1,$usuarioid,$ip_maq,6,'',1); // 6: tipo adjunto "Contrato"
                    $o_nres = $rpta['o_nres'] ?? 0;
                    $o_msj = $rpta['o_msj'] ?? 'Error respuesta en función de adjuntos';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error en función de adjuntos.';
                    }  
                }  

                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }
                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Contrato registrado con éxito ['.$cnt.'].' : $o_msj;

                return response()->json($result);
            break;
            case 'asociarENT_CNT':
                // VARIABLES
                $detalle= is_null($request->input('detalle')) ? [] : json_decode($request->input('detalle'));
                $detalle = json_decode(json_encode($detalle), true);
                $idcontrato= is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));
                $valor_total= is_null($request->input('valor_total')) ? '' : trim($request->input('valor_total'));
                /* Logica para quedarme con Detalle único */
                $collection = collect($detalle);
                $uniqueCollection = $collection->keyBy('identregadet')->values();
                $detalle_unq = $uniqueCollection->toArray();

                $o_nres = 1;
                $o_msj = '';

                // REGISTRO DE SOLICITUD
                DB::beginTransaction(); // INICIO TRX DB
                // INSERTO PROCESO x CTF
                if($o_nres == 1){
                    foreach($detalle_unq as $value){
                        $info[0] = 22;
                        $info[1] = $idcontrato;
                        $info[2] = $value['identregadet'];
                        $info[3] = $value['cantidad_seleccionada'];
        
                        $info[19] = $usuarioid;
                        $info[20] = $ip_maq;
                        //dd($info);
    
                        $rpta = $model->mantenimientoData($info);unset($info);
                        $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                        $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [22 - CNT]';
                        if($o_nres == 0){
                            DB::rollBack();
                            $o_nres = 0;
                            $o_msj = ($o_msj != '') ? $o_msj : 'Error al registrar contrato con entregas.';
                            break;
                        }  
                    }
                }
                if($o_nres == 1){
                    $info[0] = 25;
                    $info[1] = $idcontrato;
                    $info[2] = $valor_total;
    
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [25 - CNT]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al actualizar monto del contrato.';
                    }  
                }
                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }
                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Entregas asociadas con éxito.' : $o_msj;

                return response()->json($result);
            break;
            case 'quitarENTxCNT':
                $idcontrato= is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));
                $ids_entregas= is_null($request->input('ids_entregas')) ? '' : trim($request->input('ids_entregas'));

                $o_nres = 1;
                $o_msj = '';

                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 26;
                    $info[1] = $idcontrato;
                    $info[2] = $ids_entregas;
    
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [26 - CNT]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al quitar entregas del contrato.';
                    }  
                }

                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }

                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Entregas retiradas con éxito.' : $o_msj;

                return response()->json($result);
            break;
            case 'cambiarEstado_CNT':
                $idcontrato= is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));
                $idestadocontrato= is_null($request->input('idestadocontrato')) ? '' : trim($request->input('idestadocontrato'));
                $accion= is_null($request->input('accion')) ? '' : trim($request->input('accion'));
                $fecha_recep= is_null($request->input('fecha')) ? '' : trim($request->input('fecha'));
                
                $o_nres = 1;
                $o_msj = '';

                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 27;
                    $info[1] = $idcontrato;
                    $info[2] = $idestadocontrato;
                    $info[3] = $accion;
                    $info[4] = $fecha_recep;
    
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [27 - CNT]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error en el cambio de status del contrato.';
                    }  
                }

                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }

                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Contrato actualizado con éxito.' : $o_msj;

                return response()->json($result);
            break;
            case 'asociarSIT':
                $idcontrato= is_null($request->input('idcontrato')) ? '' : trim($request->input('idcontrato'));
                $idsituaciones= is_null($request->input('idsituaciones')) ? '' : trim($request->input('idsituaciones'));
                $idarearesponsable= is_null($request->input('idarearesponsable')) ? 0 : trim($request->input('idarearesponsable'));
                $idcartafianzafinal= is_null($request->input('idcartafianzafinal')) ? 0 : trim($request->input('idcartafianzafinal'));
                
                $o_nres = 1;
                $o_msj = '';

                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 28;
                    $info[1] = ($idcontrato) ? $idcontrato : 0;
                    $info[2] = $idsituaciones;
                    $info[3] = $idarearesponsable;
                    $info[4] = ($idcartafianzafinal) ? $idcartafianzafinal : 0;
    
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [28 - CNT]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al asociar situación al contrato.';
                    }  
                }

                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }

                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Estatus Legal actualizado con éxito.' : $o_msj;

                return response()->json($result);
            break;
            case 'renovarCTF':
                $idcartafianzafinal= is_null($request->input('idcartafianzafinal')) ? '' : trim($request->input('idcartafianzafinal'));
                $idmaebanco= is_null($request->input('idmaebanco')) ? '' : trim($request->input('idmaebanco'));
                $nro_ctf_renovacion= is_null($request->input('nro_ctf_renovacion')) ? '' : trim($request->input('nro_ctf_renovacion'));
                $fecha_emision_renovacion = is_null($request->input('fecha_emision_renovacion')) ? '' : trim($request->input('fecha_emision_renovacion'));
                $fecha_emision_renovacion = $this->empresaFormato->formatDateIn($fecha_emision_renovacion);
                $fecha_vcto_renovacion = is_null($request->input('fecha_vcto_renovacion')) ? '' : trim($request->input('fecha_vcto_renovacion'));
                $fecha_vcto_renovacion = $this->empresaFormato->formatDateIn($fecha_vcto_renovacion);
                $codigo_cartafianza= is_null($request->input('codigo_cartafianza')) ? '' : trim($request->input('codigo_cartafianza'));
                $idestadocartafianzafinal= is_null($request->input('idestadocartafianzafinal')) ? '' : trim($request->input('idestadocartafianzafinal'));
                $periodo_renovacion= is_null($request->input('periodo_renovacion')) ? '' : trim($request->input('periodo_renovacion'));
                $files_add = $request->file('archivos_add') ?? []; // Multiple
                
                $o_nres = 1;
                $o_msj = '';

                DB::beginTransaction(); // INICIO TRX DB
                if($o_nres == 1){
                    $info[0] = 29;
                    $info[1] = $idcartafianzafinal;
                    $info[2] = $nro_ctf_renovacion;
                    $info[3] = $fecha_emision_renovacion;
                    $info[4] = $fecha_vcto_renovacion;
                    $info[5] = $idmaebanco;
                    $info[6] = $periodo_renovacion;
    
                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [28 - CNT]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al asociar situación al contrato.';
                    }  
                }
                if($o_nres == 1){
                    // registrarAdjunto($files_add=[],$codigo_ctf,$idcartafianza='',$idestadocartafianza,$usuarioid,$ip_maq,$idtipoadjunto,$idcartafianzafinal='',$flg_contrato=0)
                    $rpta = $this->registrarAdjunto($files_add,$codigo_cartafianza,'',$idestadocartafianzafinal,$usuarioid,$ip_maq,8,$idcartafianzafinal);
                    $o_nres = $rpta['o_nres'] ?? 0;
                    $o_msj = $rpta['o_msj'] ?? 'Error respuesta en función de adjuntos';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error en función de adjuntos.';
                    }  
                }  

                if($o_nres == 1){
                    $mail = $this->enviarMailFinal($idcartafianzafinal); // CORREO DE REGISTRO
                    $rpta['o_nres'] = $mail['o_nres'] ?? 0;
                    $rpta['o_msj'] = $mail['o_msj'] ?? 'Falló envío de correo.';
                }
                
                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }else{
                    DB::rollBack();
                    $o_nres = 0;
                    $o_msj = ($o_msj != '') ? $o_msj : 'Error de envío de correo.';
                }

                $result['o_nres']   = $o_nres;
                $result['o_msj'] = ($o_nres == 1) ? 'Carta Fianza renovada con éxito.' : $o_msj;

                return response()->json($result);
            break;
            default:
                break;
        }
    }

    /*------------------------------------------------------------- PROCESOS ------------------------------------------------------------- */
    public function getPROCESOS_RFC($fechadesde='',$fechafin='',$array_doc=[]){
        // Variables Iniciales
        $sap = new SAP();
        $parametros =[]; 
        $o_nres = 1;
        $o_msj = 'Ok';
        $registros =[]; 

        // Variables requeridas
        $fecha_actual = Carbon::now()->format('Ymd');
        $fecha_min =  Carbon::now()->subYears(5)->format('Ymd');
        // Si el usuario no colocó una fecha desde o fin
        if($o_nres == 1 && $fechadesde == ''){
            $fechadesde = Carbon::now()->subYears(1)->format('Ymd'); // Carbon::now()->subYears(5)->format('Ymd');
        }else{
            $fechadesde = Carbon::createFromFormat('Y-m-d', $fechadesde)->format('Ymd');
        } 
        if($o_nres == 1 && $fechafin == ''){
            $fechafin = $fecha_actual;
        }else{
            $fechafin = Carbon::createFromFormat('Y-m-d', $fechafin)->format('Ymd');
        } 
        if($o_nres == 1){
            //if($txt_documento != '' && strlen($txt_documento) == 10){
            if(count($array_doc) > 0){
                foreach($array_doc as $value){
                    $parametros['PI_VBELN'][] = ['SIGN' => 'I',	'OPTION' => 'EQ', 'LOW' => $value, 'HIGH' => ''];
                }
            }else{
                $parametros['PI_AUDAT'] = [
                    ['SIGN' => 'I',	'OPTION' => 'BT', 'LOW' => $fechadesde, 'HIGH' => $fechafin]
                ];
                $parametros['PI_VBELN'] = [
                    ['SIGN' => 'I',	'OPTION' => 'BT', 'LOW' => '4100000000', 'HIGH' => '4199999999'],
                    ['SIGN' => 'I',	'OPTION' => 'BT', 'LOW' => '4600000000', 'HIGH' => '4699999999']
                ];
            }
            //dd($parametros);
            $get_rfc = $sap->ejecutarRFC('ZACSD_RFC_CARTAFIA_PEDIDOS',$parametros);
            //dd($get_rfc);
            $registros_cab = $get_rfc['PO_PEDIDOS_VBAK'];
            $registros_det = $get_rfc['PO_PEDIDOS_VBAP'];
        }   
                
        $result['o_nres'] = $o_nres;
        $result['o_msj'] = $o_msj;
        $result['rows_cab'] = $registros_cab;
        $result['rows_det'] = $registros_det;
        $result['fechadesde'] = $fechadesde;
        $result['fechafin'] = $fechafin;  
        return $result;
    } 

    public function procesarPROCESOS($data_cab,$data_det,$fechadesde,$fechafin){
        $model = new Instituciones();

        $result['o_nres'] = 1;
        $result['o_msj'] = 'OK';

        $result = $model->limpiarTemp($fechadesde, $fechafin, 'PRC');
        if($result['o_nres'] == 1 && count($data_cab) > 0){
            try {
                $chunkSize = 50;
                $doc = array();
                $data_cab = array_filter($data_cab, function($item) {
                    return strpos($item['VBELN'], '410') === 0 || strpos($item['VBELN'], '460') === 0; // Comprueba si VBELN empieza con '410' o '460'
                });

                foreach($data_cab as $row){
                    //dd($row);
                    $doc[] = array(
                        'prcchcddocumento' =>$row['VBELN'],
                        'prcdtfcfechadocumento' => ($row['AUDAT'] !== null && $row['AUDAT'] !== '00000000') ? date('Y-m-d', strtotime($row['AUDAT'])) : null,
                        'prcchcdclasedocumentoventas' => rtrim($row['AUART']) ?? null,
                        'prcchcdmotivopedido' => rtrim($row['AUGRU']) ?? null,
                        'prcchcdsolicitante' => rtrim($row['KUNNR']) ?? null,
                        'prcchdsdenominacion' => rtrim($row['KTEXT']) ?? null,
                        'prcdcnmimporteneto' => rtrim($row['NETWR']) ?? null,
                        'prcchcdmoneda' => rtrim($row['WAERK']) ?? null,
                        'prcchcdorganizacionventas' => rtrim($row['VKORG']) ?? null,
                        'prcchcdcanaldistribucion' => rtrim($row['VTWEG']) ?? null,
                        'prcdtfcfechaniciovalidez' => ($row['GUEBG'] !== null && $row['GUEBG'] !== '00000000') ? date('Y-m-d', strtotime($row['GUEBG'])) : null,
                        'prcdtfcfechafinvalidez' => ($row['GUEEN'] !== null && $row['GUEEN'] !== '00000000') ? date('Y-m-d', strtotime($row['GUEEN'])) : null,
                        'prcchcdnrocondiciondocumento' => rtrim($row['KNUMV']) ?? null,
                        'prcdtfcfechapreferenteentrega' => ($row['VDATU'] !== null && $row['VDATU'] !== '00000000') ? date('Y-m-d', strtotime($row['VDATU'])) : null,
                        'prcchcdesquemacalculo' => rtrim($row['KALSM']) ?? null,
                        'prcchcdnroentrega' => rtrim($row['BSTZD']) ?? null,
                        'prcdtfcfechapresentacionpropuesta' => ($row['ZZFECHA8'] !== null && $row['ZZFECHA8'] != '000000000000000') ? date('Y-m-d', strtotime(substr($row['ZZFECHA8'],0,8))) : null,
                        'prcdtfcfechainicioentregaoc' => ($row['ZZFECHA23'] !== null && $row['ZZFECHA23'] != '000000000000000') ? date('Y-m-d', strtotime(substr($row['ZZFECHA23'],0,8))) : null,
                        'prcdtfcfechafinentregaoc' => ($row['ZZFECHA24'] !== null && $row['ZZFECHA24'] != '000000000000000') ? date('Y-m-d', strtotime(substr($row['ZZFECHA24'],0,8))) : null,
                        'prcchcddestinatariomercancia' => rtrim($row['KUNNR_WE']) ?? null,
                        'prcchcdclientefinal' => rtrim($row['KUNNR_ZF']) ?? null,
                        'prcchcdgrupoclientes' =>rtrim($row['KDGRP']) ?? null, 
                        'prcdcnmimportebruto' =>rtrim($row['IMPORTE_FINAL']) ?? null, 
                        'prcchdsoperador' =>rtrim($row['OPERADOR']) ?? null, 
                        'prcdcnmimporteadjudicadoreal' =>rtrim($row['KWERT_ZPGO']) ?? null, 
                        'prcdcnmimportemargen' =>rtrim($row['KWERT_ZDSM']) ?? null, 
                    );
                    if(count($doc) > $chunkSize){
                        Procesos_I_CAB::insert($doc);
                        $doc = array();
                    }
                }
                if(count($doc) > 0){
                    Procesos_I_CAB::insert($doc);
                    $doc = array();
                }
            } catch (\Throwable $th) {
                $result['o_nres'] = 0;
                $result['o_msj'] = $th->getMessage();
                return $result;
            }
        }
        if($result['o_nres'] == 1){
            $rpta_det = $this->procesarPROCESOS_DET($data_det);
            if($rpta_det['o_nres'] == 1){
                $rpta = $model ->registrarFINAL($fechadesde,$fechafin,2);
            }else{
                $result['o_nres'] = 0;
                $result['o_msj'] = $rpta_det['o_msj'] ?? 'Error procesos DET';
            }
        }

        return $result;
    }

    public function procesarPROCESOS_WEB($data_cab,$data_det,$fechadesde,$fechafin){
        dd($fechadesde,$fechafin);
        $model = new Instituciones();

        $result['o_nres'] = 1;
        $result['o_msj'] = 'OK';

        $result = $model->limpiarTemp($fechadesde, $fechafin, 'PRC_WEB');
        if($result['o_nres'] == 1 && count($data_cab) > 0){
            try {
                $chunkSize = 50;
                $doc = array();
                $data_cab = array_filter($data_cab, function($item) {
                    return strpos($item['VBELN'], '410') === 0 || strpos($item['VBELN'], '460') === 0; // Comprueba si VBELN empieza con '410' o '460'
                });

                foreach($data_cab as $row){
                    
                    $doc[] = array(
                        'prcchcddocumento' =>$row['VBELN'],
                        'prcdtfcfechadocumento' => ($row['AUDAT'] !== null && $row['AUDAT'] !== '00000000') ? date('Y-m-d', strtotime($row['AUDAT'])) : null,
                        'prcchcdclasedocumentoventas' => rtrim($row['AUART']) ?? null,
                        'prcchcdmotivopedido' => rtrim($row['AUGRU']) ?? null,
                        'prcchcdsolicitante' => rtrim($row['KUNNR']) ?? null,
                        'prcchdsdenominacion' => rtrim($row['KTEXT']) ?? null,
                        'prcdcnmimporteneto' => rtrim($row['NETWR']) ?? null,
                        'prcchcdmoneda' => rtrim($row['WAERK']) ?? null,
                        'prcchcdorganizacionventas' => rtrim($row['VKORG']) ?? null,
                        'prcchcdcanaldistribucion' => rtrim($row['VTWEG']) ?? null,
                        'prcdtfcfechaniciovalidez' => ($row['GUEBG'] !== null && $row['GUEBG'] !== '00000000') ? date('Y-m-d', strtotime($row['GUEBG'])) : null,
                        'prcdtfcfechafinvalidez' => ($row['GUEEN'] !== null && $row['GUEEN'] !== '00000000') ? date('Y-m-d', strtotime($row['GUEEN'])) : null,
                        'prcchcdnrocondiciondocumento' => rtrim($row['KNUMV']) ?? null,
                        'prcdtfcfechapreferenteentrega' => ($row['VDATU'] !== null && $row['VDATU'] !== '00000000') ? date('Y-m-d', strtotime($row['VDATU'])) : null,
                        'prcchcdesquemacalculo' => rtrim($row['KALSM']) ?? null,
                        'prcchcdnroentrega' => rtrim($row['BSTZD']) ?? null,
                        'prcdtfcfechapresentacionpropuesta' => ($row['ZZFECHA8'] !== null && $row['ZZFECHA8'] != '000000000000000') ? date('Y-m-d', strtotime(substr($row['ZZFECHA8'],0,8))) : null,
                        'prcdtfcfechainicioentregaoc' => ($row['ZZFECHA23'] !== null && $row['ZZFECHA23'] != '000000000000000') ? date('Y-m-d', strtotime(substr($row['ZZFECHA23'],0,8))) : null,
                        'prcdtfcfechafinentregaoc' => ($row['ZZFECHA24'] !== null && $row['ZZFECHA24'] != '000000000000000') ? date('Y-m-d', strtotime(substr($row['ZZFECHA24'],0,8))) : null,
                        'prcchcddestinatariomercancia' => rtrim($row['KUNNR_WE']) ?? null,
                        'prcchcdclientefinal' => rtrim($row['KUNNR_ZF']) ?? null,
                        'prcchcdgrupoclientes' =>rtrim($row['KDGRP']) ?? null, 
                        'prcdcnmimportebruto' =>rtrim($row['IMPORTE_FINAL']) ?? null, 
                        'prcchdsoperador' =>rtrim($row['OPERADOR']) ?? null, 
                        'prcdcnmimporteadjudicadoreal' =>rtrim($row['KWERT_ZPGO']) ?? null, 
                        'prcdcnmimportemargen' =>rtrim($row['KWERT_ZDSM']) ?? null, 
                    );
                    if(count($doc) > $chunkSize){
                        Procesos_I_CAB_WEB::insert($doc);
                        $doc = array();
                    }
                }
                if(count($doc) > 0){
                    Procesos_I_CAB_WEB::insert($doc);
                    $doc = array();
                }
            } catch (\Throwable $th) {
                $result['o_nres'] = 0;
                $result['o_msj'] = $th->getMessage();
                return $result;
            }
        }
        if($result['o_nres'] == 1){
            $rpta_det = $this->procesarPROCESOS_DET_WEB($data_det);
            if($rpta_det['o_nres'] == 1){
                $rpta = $model ->registrarFINAL($fechadesde,$fechafin,38);
            }else{
                $result['o_nres'] = 0;
                $result['o_msj'] = $rpta_det['o_msj'] ?? 'Error procesos DET';
            }
        }

        return $result;
    }

    public function procesarPROCESOS_DET($data_det){
        $result['o_nres'] = 1;
        $result['o_msj'] = 'OK';

        if($result['o_nres'] == 1 && count($data_det) > 0){
            try {
                $chunkSize = 50;
                $doc = array();
                $data_det = array_filter($data_det, function($item) {
                    return strpos($item['VBELN'], '410') === 0 || strpos($item['VBELN'], '460') === 0; // Comprueba si VBELN empieza con '410' o '460'
                });

                foreach($data_det as $row){
                    $doc[] = array(
                        'prcchcddocumento' =>$row['VBELN'],
                        'prcchcdcodigomaterial' => rtrim($row['MATNR']) ?? null,
                        'prcchdsdescripcionmaterial' => rtrim($row['ARKTX']) ?? null,
                        'prcitenposicion' => rtrim($row['POSNR']) ?? null, 
                        'prcchcdgrupoarticulo' => rtrim($row['MATKL']) ?? null,
                        'prcchcdlote' => rtrim($row['CHARG']) ?? null,
                        'prcchcdtipoposicion' => rtrim($row['PSTYV']) ?? null,
                        'prcchcdmotivorechazo' => rtrim($row['ABGRU']) ?? null,
                        'prcchdsjerarquiproducto' => rtrim($row['PRODH']) ?? null,
                        'prcdcnmcantidadprevista' => rtrim($row['ZMENG']) ?? null,
                        'prcchcdunidadmedidadventa' => rtrim($row['ZIEME']) ?? null,
                        'prcdcnmcantidadbase' => rtrim($row['UMZIN']) ?? null,
                        'prcchcdunidadmedidabase' => rtrim($row['MEINS']) ?? null,
                        'prcchcdsector' => rtrim($row['SPART']) ?? null,
                        'prcdcnmvalorneto' => rtrim($row['NETWR']) ?? null,
                        'prcchcdmonedadocumento' => rtrim($row['WAERK']) ?? null,
                        'prcdcnmpesobruto' => rtrim($row['BRGEW']) ?? null,
                        'prcdcnmpesoneto' => rtrim($row['NTGEW']) ?? null,
                        'prcchcdunidadmedidapeso' => rtrim($row['GEWEI']) ?? null,
                        'prcinenprioridadentrega' => rtrim($row['LPRIO']) ?? null,
                        'prcchcdcentro' => rtrim($row['WERKS']) ?? null,
                        'prcchcdalmacen' => rtrim($row['LGORT']) ?? null,
                        'prcchcddptomercancia' => rtrim($row['VSTEL']) ?? null,
                        'prcchcdcentrobeneficiario' => rtrim($row['PRCTR']) ?? null,
                        'prccdcddocumentomodelo' => rtrim($row['VGBEL']) ?? null,
                        'prcitenposiciondocumentomodelo' => rtrim($row['VGPOS']) ?? null,
                        'prcchdspesquisa' => rtrim($row['KDKG4']) ?? null,
                        'prcchcditemposicionbase' => rtrim($row['POSEX']) ?? null,
                        'prcchcddocumentomodelo' =>rtrim($row['VGBEL']) ?? null, 
                        'prcitenposicionmodelo' =>rtrim($row['VGPOS']) ?? null,
                        'prcdcnmimportebruto' =>rtrim($row['IMPORTE_FINAL']) ?? null, 
                        'prcchcdcodigomolecula' =>rtrim($row['PRODH1']) ?? null, 
                        'prcchdsdesmolecula' =>rtrim($row['PRODH1_TEXT']) ?? null, 
                        'prcdcnmimporteadjudicadoreal' =>rtrim($row['KWERT_ZPGO']) ?? null, 
                        'prcdcnmimportemargen' =>rtrim($row['KWERT_ZDSM']) ?? null, 
                        'prcchcdindicadorproductosagitario' =>rtrim($row['PROD_PROPIO_DAT']) ?? null, 
                    );
                    if(count($doc) > $chunkSize){
                        Procesos_I_DET::insert($doc);
                        $doc = array();
                    }
                }
                if(count($doc) > 0){
                    Procesos_I_DET::insert($doc);
                    $doc = array();
                }
            } catch (\Throwable $th) {
                $result['o_nres'] = 0;
                $result['o_msj'] = $th->getMessage();
                return $result;
            }
        }

        return $result;
    }

    public function procesarPROCESOS_DET_WEB($data_det){
        $result['o_nres'] = 1;
        $result['o_msj'] = 'OK';

        if($result['o_nres'] == 1 && count($data_det) > 0){
            try {
                $chunkSize = 50;
                $doc = array();
                $data_det = array_filter($data_det, function($item) {
                    return strpos($item['VBELN'], '410') === 0 || strpos($item['VBELN'], '460') === 0; // Comprueba si VBELN empieza con '410' o '460'
                });

                foreach($data_det as $row){
                    $doc[] = array(
                        'prcchcddocumento' =>$row['VBELN'],
                        'prcchcdcodigomaterial' => rtrim($row['MATNR']) ?? null,
                        'prcchdsdescripcionmaterial' => rtrim($row['ARKTX']) ?? null,
                        'prcitenposicion' => rtrim($row['POSNR']) ?? null, 
                        'prcchcdgrupoarticulo' => rtrim($row['MATKL']) ?? null,
                        'prcchcdlote' => rtrim($row['CHARG']) ?? null,
                        'prcchcdtipoposicion' => rtrim($row['PSTYV']) ?? null,
                        'prcchcdmotivorechazo' => rtrim($row['ABGRU']) ?? null,
                        'prcchdsjerarquiproducto' => rtrim($row['PRODH']) ?? null,
                        'prcdcnmcantidadprevista' => rtrim($row['ZMENG']) ?? null,
                        'prcchcdunidadmedidadventa' => rtrim($row['ZIEME']) ?? null,
                        'prcdcnmcantidadbase' => rtrim($row['UMZIN']) ?? null,
                        'prcchcdunidadmedidabase' => rtrim($row['MEINS']) ?? null,
                        'prcchcdsector' => rtrim($row['SPART']) ?? null,
                        'prcdcnmvalorneto' => rtrim($row['NETWR']) ?? null,
                        'prcchcdmonedadocumento' => rtrim($row['WAERK']) ?? null,
                        'prcdcnmpesobruto' => rtrim($row['BRGEW']) ?? null,
                        'prcdcnmpesoneto' => rtrim($row['NTGEW']) ?? null,
                        'prcchcdunidadmedidapeso' => rtrim($row['GEWEI']) ?? null,
                        'prcinenprioridadentrega' => rtrim($row['LPRIO']) ?? null,
                        'prcchcdcentro' => rtrim($row['WERKS']) ?? null,
                        'prcchcdalmacen' => rtrim($row['LGORT']) ?? null,
                        'prcchcddptomercancia' => rtrim($row['VSTEL']) ?? null,
                        'prcchcdcentrobeneficiario' => rtrim($row['PRCTR']) ?? null,
                        'prccdcddocumentomodelo' => rtrim($row['VGBEL']) ?? null,
                        'prcitenposiciondocumentomodelo' => rtrim($row['VGPOS']) ?? null,
                        'prcchdspesquisa' => rtrim($row['KDKG4']) ?? null,
                        'prcchcditemposicionbase' => rtrim($row['POSEX']) ?? null,
                        'prcchcddocumentomodelo' =>rtrim($row['VGBEL']) ?? null, 
                        'prcitenposicionmodelo' =>rtrim($row['VGPOS']) ?? null,
                        'prcdcnmimportebruto' =>rtrim($row['IMPORTE_FINAL']) ?? null, 
                        'prcchcdcodigomolecula' =>rtrim($row['PRODH1']) ?? null, 
                        'prcchdsdesmolecula' =>rtrim($row['PRODH1_TEXT']) ?? null, 
                        'prcdcnmimporteadjudicadoreal' =>rtrim($row['KWERT_ZPGO']) ?? null, 
                        'prcdcnmimportemargen' =>rtrim($row['KWERT_ZDSM']) ?? null, 
                        'prcchcdindicadorproductosagitario' =>rtrim($row['PROD_PROPIO_DAT']) ?? null, 
                    );
                    if(count($doc) > $chunkSize){
                        Procesos_I_DET_WEB::insert($doc);
                        $doc = array();
                    }
                }
                if(count($doc) > 0){
                    Procesos_I_DET_WEB::insert($doc);
                    $doc = array();
                }
            } catch (\Throwable $th) {
                $result['o_nres'] = 0;
                $result['o_msj'] = $th->getMessage();
                return $result;
            }
        }

        return $result;
    }

    /*------------------------------------------------------------- ENTREGAS ------------------------------------------------------------- */
    public function getENTREGAS_RFC($fechadesde='',$fechafin='',$doc_modelo='',$posicion='',$doc_entregas=[]){
        // Variables Iniciales
        $sap = new SAP();
        $parametros =[]; 
        $o_nres = 1;
        $o_msj = 'Ok';
        $registros =[]; 

        if($doc_modelo == '' && $posicion == ''){
            // Variables requeridas
            $fecha_actual = Carbon::now()->format('Ymd');
            $fecha_min =  Carbon::now()->subYears(5)->format('Ymd');
            // Si el usuario no colocó una fecha desde o fin
            if($o_nres == 1 && $fechadesde == ''){
                $fechadesde = Carbon::now()->subYears(1)->format('Ymd'); // Carbon::now()->subYears(5)->format('Ymd');
            }else{
                $fechadesde = Carbon::createFromFormat('Y-m-d', $fechadesde)->format('Ymd');
            } 
            if($o_nres == 1 && $fechafin == ''){
                $fechafin = $fecha_actual;
            }else{
                $fechafin = Carbon::createFromFormat('Y-m-d', $fechafin)->format('Ymd');
            } 
        }
        if($o_nres == 1){
            if(count($doc_entregas) > 0){
                foreach($doc_entregas as $key => $value){
                    //dd($value);
                    $parametros['PI_VBELN'][] = ['SIGN' => 'I',	'OPTION' => 'EQ', 'LOW' => $value['documento'], 'HIGH' => ''];
                    //$parametros['PI_POSNR'][] = ['SIGN' => 'I',	'OPTION' => 'EQ', 'LOW' => $value['posicion'], 'HIGH' => ''];
                }
            }else{
                $parametros['PI_VBELN'] = [
                    ['SIGN' => 'I',	'OPTION' => 'BT', 'LOW' => '1100000000', 'HIGH' => '1199999999']
                ];
                if($doc_modelo == '' && $posicion == ''){
                    $parametros['PI_AUDAT'] = [
                        ['SIGN' => 'I',	'OPTION' => 'BT', 'LOW' => $fechadesde, 'HIGH' => $fechafin]
                    ];
                }
                if($doc_modelo != ''){
                    $parametros['PI_VGBEL'] = [
                        ['SIGN' => 'I',	'OPTION' => 'EQ', 'LOW' => $doc_modelo, 'HIGH' => '']
                    ];
                    if($posicion != ''){
                        $parametros['PI_VGPOS'] = [
                            ['SIGN' => 'I',	'OPTION' => 'EQ', 'LOW' => $posicion, 'HIGH' => '']
                        ];
                    }
                }    
            }
            //dd($parametros);
            $get_rfc = $sap->ejecutarRFC('ZACSD_RFC_CARTAFIA_PEDIDOS',$parametros);
            //dd($get_rfc);
            $registros_cab = $get_rfc['PO_PEDIDOS_VBAK'];
            $registros_det = $get_rfc['PO_PEDIDOS_VBAP'];
            if($doc_modelo != '' && $posicion != '' && count($registros_det) == 0){
                $registros_cab = [];
            }
        }   
                
        $result['o_nres'] = $o_nres;
        $result['o_msj'] = $o_msj;
        $result['rows_cab'] = $registros_cab;
        $result['rows_det'] = $registros_det;
        $result['fechadesde'] = $fechadesde;
        $result['fechafin'] = $fechafin;  
        return $result;
    } 

    public function procesarENTREGAS($data_cab,$data_det,$fechadesde='',$fechafin=''){
        $model = new Instituciones();

        $result['o_nres'] = 1;
        $result['o_msj'] = 'OK';

        $result = $model->limpiarTemp($fechadesde, $fechafin, 'ENT');
        if($result['o_nres'] == 1 && count($data_cab) > 0){
            try {
                $chunkSize = 50;
                $doc = array();
                $data_cab = array_filter($data_cab, function($item) {
                    return strpos($item['VBELN'], '110') === 0; // Comprueba si VBELN empieza con '110'
                });

                foreach($data_cab as $row){
                    //dd($row);
                    $doc[] = array(
                        'entchcddocumento' =>$row['VBELN'],
                        'entdtfcfechadocumento' => ($row['AUDAT'] !== null && $row['AUDAT'] !== '00000000') ? date('Y-m-d', strtotime($row['AUDAT'])) : null,
                        'entchcdclasedocumentoventas' => rtrim($row['AUART']) ?? null,
                        'entchcdmotivopedido' => rtrim($row['AUGRU']) ?? null,
                        'entchcdsolicitante' => rtrim($row['KUNNR']) ?? null,
                        'entchdsdenominacion' => rtrim($row['KTEXT']) ?? null,
                        'entdcnmimporteneto' => rtrim($row['NETWR']) ?? null,
                        'entchcdmoneda' => rtrim($row['WAERK']) ?? null,
                        'entchcdorganizacionventas' => rtrim($row['VKORG']) ?? null,
                        'entchcdcanaldistribucion' => rtrim($row['VTWEG']) ?? null,
                        'entdtfcfechaniciovalidez' => ($row['GUEBG'] !== null && $row['GUEBG'] !== '00000000') ? date('Y-m-d', strtotime($row['GUEBG'])) : null,
                        'entdtfcfechafinvalidez' => ($row['GUEEN'] !== null && $row['GUEEN'] !== '00000000') ? date('Y-m-d', strtotime($row['GUEEN'])) : null,
                        'entchcdnrocondiciondocumento' => rtrim($row['KNUMV']) ?? null,
                        'entdtfcfechapreferenteentrega' => ($row['VDATU'] !== null && $row['VDATU'] !== '00000000') ? date('Y-m-d', strtotime($row['VDATU'])) : null,
                        'entchcdesquemacalculo' => rtrim($row['KALSM']) ?? null,
                        'entchcdnroentrega' => rtrim($row['BSTZD']) ?? null,
                        'entdtfcfechapresentacionpropuesta' => ($row['ZZFECHA8'] !== null && $row['ZZFECHA8'] != '000000000000000') ? date('Y-m-d', strtotime(substr($row['ZZFECHA8'],0,8))) : null,
                        'entdtfcfechainicioentregaoc' => ($row['ZZFECHA23'] !== null && $row['ZZFECHA23'] != '000000000000000') ? date('Y-m-d', strtotime(substr($row['ZZFECHA23'],0,8))) : null,
                        'entdtfcfechafinentregaoc' => ($row['ZZFECHA24'] !== null && $row['ZZFECHA24'] != '000000000000000') ? date('Y-m-d', strtotime(substr($row['ZZFECHA24'],0,8))) : null,
                        'entchcddestinatariomercancia' => rtrim($row['KUNNR_WE']) ?? null,
                        'entchcdclientefinal' => rtrim($row['KUNNR_ZF']) ?? null,
                        'entchcdgrupoclientes' =>rtrim($row['KDGRP']) ?? null, 
                        'entdcnmimportebruto' =>rtrim($row['IMPORTE_FINAL']) ?? null, 
                        'entchdsoperador' =>rtrim($row['OPERADOR']) ?? null, 
                        'entdcnmimporteadjudicadoreal' =>rtrim($row['KWERT_ZPGO']) ?? null, 
                        'entdcnmimportemargen' =>rtrim($row['KWERT_ZDSM']) ?? null, 
                    );
                    if(count($doc) > $chunkSize){
                        Entregas_I_CAB::insert($doc);
                        $doc = array();
                    }
                }
                if(count($doc) > 0){
                    Entregas_I_CAB::insert($doc);
                    $doc = array();
                }
            } catch (\Throwable $th) {
                $result['o_nres'] = 0;
                $result['o_msj'] = $th->getMessage();
                return $result;
            }
        }
        if($result['o_nres'] == 1){
            $rpta_det = $this->procesarENTREGAS_DET($data_det);
            if($rpta_det['o_nres'] == 1){
                $rpta = $model ->registrarFINAL($fechadesde,$fechafin,3);
            }else{
                $result['o_nres'] = 0;
                $result['o_msj'] = $rpta_det['o_msj'] ?? 'Error entregas DET';
            }
        }

        return $result;
    }

    public function procesarENTREGAS_DET($data_det){
        $result['o_nres'] = 1;
        $result['o_msj'] = 'OK';

        if($result['o_nres'] == 1 && count($data_det) > 0){
            try {
                $chunkSize = 50;
                $doc = array();
                $data_det = array_filter($data_det, function($item) {
                    return strpos($item['VBELN'], '110') === 0; // Comprueba si VBELN empieza con '110'
                });
                foreach($data_det as $row){
                    $doc[] = array(
                        'entchcddocumento' =>$row['VBELN'],
                        'entchcdcodigomaterial' => rtrim($row['MATNR']) ?? null,
                        'entchdsdescripcionmaterial' => rtrim($row['ARKTX']) ?? null,
                        'entitenposicion' => rtrim($row['POSNR']) ?? null, 
                        'entchcdgrupoarticulo' => rtrim($row['MATKL']) ?? null,
                        'entchcdlote' => rtrim($row['CHARG']) ?? null,
                        'entchcdtipoposicion' => rtrim($row['PSTYV']) ?? null,
                        'entchcdmotivorechazo' => rtrim($row['ABGRU']) ?? null,
                        'entchdsjerarquiproducto' => rtrim($row['PRODH']) ?? null,
                        'entdcnmcantidadprevista' => rtrim($row['KWMENG']) ?? null,
                        'entchcdunidadmedidadventa' => rtrim($row['VRKME']) ?? null,
                        'entdcnmcantidadbase' => rtrim($row['UMZIN']) ?? null,
                        'entchcdunidadmedidabase' => rtrim($row['MEINS']) ?? null,
                        'entchcdsector' => rtrim($row['SPART']) ?? null,
                        'entdcnmvalorneto' => rtrim($row['NETWR']) ?? null,
                        'entchcdmonedadocumento' => rtrim($row['WAERK']) ?? null,
                        'entdcnmpesobruto' => rtrim($row['BRGEW']) ?? null,
                        'entdcnmpesoneto' => rtrim($row['NTGEW']) ?? null,
                        'entchcdunidadmedidapeso' => rtrim($row['GEWEI']) ?? null,
                        'entinenprioridadentrega' => rtrim($row['LPRIO']) ?? null,
                        'entchcdcentro' => rtrim($row['WERKS']) ?? null,
                        'entchcdalmacen' => rtrim($row['LGORT']) ?? null,
                        'entchcddptomercancia' => rtrim($row['VSTEL']) ?? null,
                        'entchcdcentrobeneficiario' => rtrim($row['PRCTR']) ?? null,
                        'entcdcddocumentomodelo' => rtrim($row['VGBEL']) ?? null,
                        'entitenposiciondocumentomodelo' => rtrim($row['VGPOS']) ?? null,
                        'entchdspesquisa' => rtrim($row['KDKG4']) ?? null,
                        'entdcnmimportebruto' =>rtrim($row['IMPORTE_FINAL']) ?? null, 
                        'entchcdcodigomolecula' =>rtrim($row['PRODH1']) ?? null, 
                        'entchdsdesmolecula' =>rtrim($row['PRODH1_TEXT']) ?? null, 
                        'entdcnmimporteadjudicadoreal' =>rtrim($row['KWERT_ZPGO']) ?? null, 
                        'entdcnmimportemargen' =>rtrim($row['KWERT_ZDSM']) ?? null, 
                        'entchcdindicadorproductosagitario' =>rtrim($row['PROD_PROPIO_DAT']) ?? null, 
                        'entchdscontrato' =>rtrim($row['IHREZ']) ?? null, 
                        'entdtfcfechainiciocontrato' => ($row['ZZFECHA30'] !== null && $row['ZZFECHA30'] != '000000000000000') ? date('Y-m-d', strtotime(substr($row['ZZFECHA30'],0,8))) : null,
                        'entdtfcfechafincontrato' => ($row['ZZFECHA31'] !== null && $row['ZZFECHA31'] != '000000000000000') ? date('Y-m-d', strtotime(substr($row['ZZFECHA31'],0,8))) : null,
                    );
                    if(count($doc) > $chunkSize){
                        Entregas_I_DET::insert($doc);
                        $doc = array();
                    }
                }
                if(count($doc) > 0){
                    Entregas_I_DET::insert($doc);
                    $doc = array();
                }
            } catch (\Throwable $th) {
                $result['o_nres'] = 0;
                $result['o_msj'] = $th->getMessage();
                return $result;
            }
        }
        return $result;
    }

    /*------------------------------------------------------------- PEDIDOS ------------------------------------------------------------- */
    public function getPEDIDOS_RFC($fechadesde='',$fechafin='',$doc_entregas=[],$posicion = ''){
        // Variables Iniciales
        $sap = new SAP();
        $parametros =[]; 
        $o_nres = 1;
        $o_msj = 'Ok';
        $registros =[]; 
        $result = [        
            'o_nres' => $o_nres,'o_msj'=> $o_msj,'rows_cab'=> [],'rows_det'=> [],'fechadesde'=>'','fechafin'=>''
        ];
        $parametros['PI_VGBEL'] = [];

        if(count($doc_entregas) == 0 && $posicion == ''){
            // Variables requeridas
            $fecha_actual = Carbon::now()->format('Ymd');
            $fecha_min =  Carbon::now()->subYears(5)->format('Ymd');
            // Si el usuario no colocó una fecha desde o fin
            if($o_nres == 1 && $fechadesde == ''){
                $fechadesde = Carbon::now()->subYears(1)->format('Ymd'); // Carbon::now()->subYears(5)->format('Ymd');
            }else{
                $fechadesde = Carbon::createFromFormat('Y-m-d', $fechadesde)->format('Ymd');
            } 
            if($o_nres == 1 && $fechafin == ''){
                $fechafin = $fecha_actual;
            }else{
                $fechafin = Carbon::createFromFormat('Y-m-d', $fechafin)->format('Ymd');
            } 
        }

        /* if($o_nres == 1){
            if(count($doc_entregas) == 0 && $posicion == ''){
                $parametros['PI_AUDAT'] = [
                    ['SIGN' => 'I',	'OPTION' => 'BT', 'LOW' => $fechadesde, 'HIGH' => $fechafin]
                ];
                $parametros['PI_VBELN'] = [
                    ['SIGN' => 'I',	'OPTION' => 'BT', 'LOW' => '1500000000', 'HIGH' => '1599999999']
                ];
            }else{
                //dd($doc_entregas);
                foreach($doc_entregas as $key => $value){
                    //dd($value);
                    $parametros['PI_VGBEL'][] = ['SIGN' => 'I',	'OPTION' => 'EQ', 'LOW' => $value['documento'], 'HIGH' => ''];
                    ($posicion != '') ?? $parametros['PI_VGPOS'][] = ['SIGN' => 'I',	'OPTION' => 'EQ', 'LOW' => $value['posicion'], 'HIGH' => ''];
                }
            }
            //dd($parametros);
            $get_rfc = $sap->ejecutarRFC('ZACSD_RFC_CARTAFIA_PEDIDOS',$parametros);
            //dd($get_rfc);
            $registros_cab = $get_rfc['PO_PEDIDOS_VBAK'];
            $registros_det = $get_rfc['PO_PEDIDOS_VBAP'];
        }  */  
        
        $batchSize = 100; // Tamaño del lote
        $doc_entregas_chunks = array_chunk($doc_entregas, $batchSize);

        foreach ($doc_entregas_chunks as $doc_entregas_batch) {
            $parametros = [];
            if (count($doc_entregas_batch) > 0) {
                foreach ($doc_entregas_batch as $key => $value) {
                    $parametros['PI_VGBEL'][] = ['SIGN' => 'I', 'OPTION' => 'EQ', 'LOW' => $value['documento'], 'HIGH' => ''];
                    if ($posicion != '') {
                        $parametros['PI_VGPOS'][] = ['SIGN' => 'I', 'OPTION' => 'EQ', 'LOW' => $value['posicion'], 'HIGH' => ''];
                    }
                }
            } else {
                $parametros['PI_AUDAT'] = [
                    ['SIGN' => 'I', 'OPTION' => 'BT', 'LOW' => $fechadesde, 'HIGH' => $fechafin]
                ];
                $parametros['PI_VBELN'] = [
                    ['SIGN' => 'I', 'OPTION' => 'BT', 'LOW' => '1500000000', 'HIGH' => '1599999999']
                ];
            }

            $get_rfc = $sap->ejecutarRFC('ZACSD_RFC_CARTAFIA_PEDIDOS', $parametros);
            if (count($get_rfc) > 0) {
                $result['rows_cab'] = array_merge($result['rows_cab'], $get_rfc['PO_PEDIDOS_VBAK']);
                $result['rows_det'] = array_merge($result['rows_det'], $get_rfc['PO_PEDIDOS_VBAP']);
            } /* else {
                $result['o_nres'] = 0;
                $result['o_msj'] = 'Error in batch processing';
                break;
            } */
        }

        /* $result['o_nres'] = $o_nres;
        $result['o_msj'] = $o_msj;
        $result['rows_cab'] = $registros_cab;
        $result['rows_det'] = $registros_det; */
        $result['fechadesde'] = $fechadesde;
        $result['fechafin'] = $fechafin;  
        //dd($result);
        return $result;
    } 

    public function procesarPEDIDOS($data_cab,$data_det,$fechadesde,$fechafin){
        $model = new Instituciones();

        $result['o_nres'] = 1;
        $result['o_msj'] = 'OK';
        $result = $model->limpiarTemp($fechadesde, $fechafin, 'PED');
        if($result['o_nres'] == 1 && count($data_cab) > 0){
            try {
                $chunkSize = 50;
                $doc = array();
                $data_cab = array_filter($data_cab, function($item) {
                    return strpos($item['VBELN'], '150') === 0; // Comprueba si VBELN empieza con '150'
                });

                foreach($data_cab as $row){
                    //dd($row);
                    $doc[] = array(
                        'pedchcddocumento' =>$row['VBELN'],
                        'peddtfcfechadocumento' => ($row['AUDAT'] !== null && $row['AUDAT'] !== '00000000') ? date('Y-m-d', strtotime($row['AUDAT'])) : null,
                        'pedchcdclasedocumentoventas' => rtrim($row['AUART']) ?? null,
                        'pedchcdmotivopedido' => rtrim($row['AUGRU']) ?? null,
                        'pedchcdsolicitante' => rtrim($row['KUNNR']) ?? null,
                        'pedchdsdenominacion' => rtrim($row['KTEXT']) ?? null,
                        'peddcnmimporteneto' => rtrim($row['NETWR']) ?? null,
                        'pedchcdmoneda' => rtrim($row['WAERK']) ?? null,
                        'pedchcdorganizacionventas' => rtrim($row['VKORG']) ?? null,
                        'pedchcdcanaldistribucion' => rtrim($row['VTWEG']) ?? null,
                        'peddtfcfechaniciovalidez' => ($row['GUEBG'] !== null && $row['GUEBG'] !== '00000000') ? date('Y-m-d', strtotime($row['GUEBG'])) : null,
                        'peddtfcfechafinvalidez' => ($row['GUEEN'] !== null && $row['GUEEN'] !== '00000000') ? date('Y-m-d', strtotime($row['GUEEN'])) : null,
                        'pedchcdnrocondiciondocumento' => rtrim($row['KNUMV']) ?? null,
                        'peddtfcfechapreferenteentrega' => ($row['VDATU'] !== null && $row['VDATU'] !== '00000000') ? date('Y-m-d', strtotime($row['VDATU'])) : null,
                        'pedchcdesquemacalculo' => rtrim($row['KALSM']) ?? null,
                        'pedchcdnroentrega' => rtrim($row['BSTZD']) ?? null,
                        'peddtfcfechapresentacionpropuesta' => ($row['ZZFECHA8'] !== null && $row['ZZFECHA8'] != '000000000000000') ? date('Y-m-d', strtotime(substr($row['ZZFECHA8'],0,8))) : null,
                        'peddtfcfechainicioentregaoc' => ($row['ZZFECHA23'] !== null && $row['ZZFECHA23'] != '000000000000000') ? date('Y-m-d', strtotime(substr($row['ZZFECHA23'],0,8))) : null,
                        'peddtfcfechafinentregaoc' => ($row['ZZFECHA24'] !== null && $row['ZZFECHA24'] != '000000000000000') ? date('Y-m-d', strtotime(substr($row['ZZFECHA24'],0,8))) : null,
                        'pedchcddestinatariomercancia' => rtrim($row['KUNNR_WE']) ?? null,
                        'pedchcdclientefinal' => rtrim($row['KUNNR_ZF']) ?? null,
                        'peddtfcfecharecepcionacfoc' => ($row['ZZFECHA10'] !== null && $row['ZZFECHA10'] != '000000000000000') ? date('Y-m-d', strtotime(substr($row['ZZFECHA10'],0,8))) : null, 
                        'pedchcdgrupoclientes' =>rtrim($row['KDGRP']) ?? null, 
                        'peddcnmimportebruto' =>rtrim($row['IMPORTE_FINAL']) ?? null, 
                        'pedchdsoperador' =>rtrim($row['OPERADOR']) ?? null, 
                        'peddcnmimporteadjudicadoreal' =>rtrim($row['KWERT_ZPGO']) ?? null, 
                        'peddcnmimportemargen' =>rtrim($row['KWERT_ZDSM']) ?? null, 
                        'pedchcdnumclienteoc' =>rtrim($row['BSTKD']) ?? null, 
                    );
                    if(count($doc) > $chunkSize){
                        Pedidos_I_CAB::insert($doc);
                        $doc = array();
                    }
                }
                if(count($doc) > 0){
                    Pedidos_I_CAB::insert($doc);
                    $doc = array();
                }
            } catch (\Throwable $th) {
                $result['o_nres'] = 0;
                $result['o_msj'] = $th->getMessage();
                return $result;
            }
        }
        if($result['o_nres'] == 1){
            $rpta_det = $this->procesarPEDIDOS_DET($data_det);
            if($rpta_det['o_nres'] == 1){
                $rpta = $model ->registrarFINAL($fechadesde,$fechafin,4);
            }else{
                $result['o_nres'] = 0;
                $result['o_msj'] = $rpta_det['o_msj'] ?? 'Error pedidos DET';
            }
        }

        return $result;
    }

    public function procesarPEDIDOS_DET($data_det){
        $result['o_nres'] = 1;
        $result['o_msj'] = 'OK';

        if($result['o_nres'] == 1 && count($data_det) > 0){
            try {
                $chunkSize = 50;
                $doc = array();
                $data_det = array_filter($data_det, function($item) {
                    return strpos($item['VBELN'], '150') === 0; // Comprueba si VBELN empieza con '410'
                });

                foreach($data_det as $row){
                    $doc[] = array(
                        'pedchcddocumento' =>$row['VBELN'],
                        'pedchcdcodigomaterial' => rtrim($row['MATNR']) ?? null,
                        'pedchdsdescripcionmaterial' => rtrim($row['ARKTX']) ?? null,
                        'peditenposicion' => rtrim($row['POSNR']) ?? null, 
                        'pedchcdgrupoarticulo' => rtrim($row['MATKL']) ?? null,
                        'pedchcdlote' => rtrim($row['CHARG']) ?? null,
                        'pedchcdtipoposicion' => rtrim($row['PSTYV']) ?? null,
                        'pedchcdmotivorechazo' => rtrim($row['ABGRU']) ?? null,
                        'pedchdsjerarquiproducto' => rtrim($row['PRODH']) ?? null,
                        'peddcnmcantidadprevista' => rtrim($row['KWMENG']) ?? null,
                        'pedchcdunidadmedidadventa' => rtrim($row['ZIEME']) ?? null,
                        'peddcnmcantidadbase' => rtrim($row['UMZIN']) ?? null,
                        'pedchcdunidadmedidabase' => rtrim($row['MEINS']) ?? null,
                        'pedchcdsector' => rtrim($row['SPART']) ?? null,
                        'peddcnmvalorneto' => rtrim($row['NETWR']) ?? null,
                        'pedchcdmonedadocumento' => rtrim($row['WAERK']) ?? null,
                        'peddcnmpesobruto' => rtrim($row['BRGEW']) ?? null,
                        'peddcnmpesoneto' => rtrim($row['NTGEW']) ?? null,
                        'pedchcdunidadmedidapeso' => rtrim($row['GEWEI']) ?? null,
                        'pedinenprioridadentrega' => rtrim($row['LPRIO']) ?? null,
                        'pedchcdcentro' => rtrim($row['WERKS']) ?? null,
                        'pedchcdalmacen' => rtrim($row['LGORT']) ?? null,
                        'pedchcddptomercancia' => rtrim($row['VSTEL']) ?? null,
                        'pedchcdcentrobeneficiario' => rtrim($row['PRCTR']) ?? null,
                        'pedcdcddocumentomodelo' => rtrim($row['VGBEL']) ?? null,
                        'peditenposiciondocumentomodelo' => rtrim($row['VGPOS']) ?? null,
                        'pedchdspesquisa' => rtrim($row['KDKG4']) ?? null,
                        'pedchcdfactura' => rtrim($row['VBELN_FAC']) ?? null,
                        'peddcnmimportebruto' =>rtrim($row['IMPORTE_FINAL']) ?? null, 
                        'pedchcdcodigomolecula' =>rtrim($row['PRODH1']) ?? null, 
                        'pedchdsdesmolecula' =>rtrim($row['PRODH1_TEXT']) ?? null, 
                        'peddcnmimporteadjudicadoreal' =>rtrim($row['KWERT_ZPGO']) ?? null, 
                        'peddcnmimportemargen' =>rtrim($row['KWERT_ZDSM']) ?? null, 
                        'pedchcdindicadorproductosagitario' =>rtrim($row['PROD_PROPIO_DAT']) ?? null, 
                    );
                    if(count($doc) > $chunkSize){
                        Pedidos_I_DET::insert($doc);
                        $doc = array();
                    }
                }
                if(count($doc) > 0){
                    Pedidos_I_DET::insert($doc);
                    $doc = array();
                }
            } catch (\Throwable $th) {
                $result['o_nres'] = 0;
                $result['o_msj'] = $th->getMessage();
                return $result;
            }
        }
        return $result;
    }

    /*------------------------------------------------------------- PICKING/FACTURA ------------------------------------------------------------- */
    public function getPICKINGREPARTOFACTURA_RFC($fechadesde='',$fechafin='',$doc_pedidos=[],$flg_only15=0/*,$posicion = ''*/){
        // Variables Iniciales
        $sap = new SAP();
        $parametros =[]; 
        $o_nres = 1;
        $o_msj = 'Ok';
        $registros =[]; 
        $result = [        
            'o_nres' => $o_nres,'o_msj'=> $o_msj,'rows_pick_cab'=> [],'rows_pick_det'=> [],'rows_fact_cab'=> [],
            'rows_fact_det'=> [],'rows_cargo_det'=> [],'fechadesde'=>'','fechafin'=>''
        ];
        /* $result['o_nres'] = $o_nres;
        $result['o_msj'] = $o_msj;
        $result['rows_pick_cab'] = [];
        $result['rows_pick_det'] = [];
        $result['rows_fact_cab'] = [];
        $result['rows_fact_det'] = [];
        $result['fechadesde'] = '';
        $result['fechafin'] = '';   */
        
        /* if($o_nres == 1){
            if($flg_only15 == 0){
                if(count($doc_pedidos) == 0){
                    // Variables requeridas
                    $fecha_actual = Carbon::now()->format('Ymd');
                    $fecha_min =  Carbon::now()->subYears(5)->format('Ymd');
                    // Si el usuario no colocó una fecha desde o fin
                    if($o_nres == 1 && $fechadesde == ''){
                        $fechadesde = Carbon::now()->subYears(1)->format('Ymd'); // Carbon::now()->subYears(5)->format('Ymd');
                    }else{
                        $fechadesde = Carbon::createFromFormat('Y-m-d', $fechadesde)->format('Ymd');
                    } 
                    if($o_nres == 1 && $fechafin == ''){
                        $fechafin = $fecha_actual;
                    }else{
                        $fechafin = Carbon::createFromFormat('Y-m-d', $fechafin)->format('Ymd');
                    } 
                    $parametros['PI_AUDAT'] = [
                        ['SIGN' => 'I',	'OPTION' => 'BT', 'LOW' => $fechadesde, 'HIGH' => $fechafin]
                    ];
                }else{
                    foreach($doc_pedidos as $key => $value){
                        //dd($value);
                        $parametros['PI_VBELN'][] = ['SIGN' => 'I',	'OPTION' => 'EQ', 'LOW' => $value['documento'], 'HIGH' => ''];
                        $parametros['PI_POSNR'][] = ['SIGN' => 'I',	'OPTION' => 'EQ', 'LOW' => $value['posicion'], 'HIGH' => ''];
                    }                    
                }
            }else{
                if(count($doc_pedidos) == 0){
                    return $result;
                }else{
                    foreach($doc_pedidos as $key => $value){
                        //dd($value);
                        $parametros['PI_VBELN'][] = ['SIGN' => 'I',	'OPTION' => 'EQ', 'LOW' => $value['documento'], 'HIGH' => ''];
                        //($flg_only15 == 0) ?? $parametros['PI_POSNR'][] = ['SIGN' => 'I',	'OPTION' => 'EQ', 'LOW' => $value['posicion'], 'HIGH' => ''];
                    }
                }
            }
            //dd($parametros);
            $get_rfc = $sap->ejecutarRFC('ZACSD_RFC_CARTAFIA_PED_FACT',$parametros);
            //dd($get_rfc);
            $registros_pick_cab = $get_rfc['PO_ENTREGAS_LIKP'];
            $registros_pick_det = $get_rfc['PO_ENTREGAS_LIPS'];
            $registros_fact_cab = $get_rfc['PO_PEDFACT_VBRK'];
            $registros_fac_det = $get_rfc['PO_PEDFACT_VBRP'];
            //$registros_cargo_cab = $get_rfc['PO_CAB_CARGO'];
            $registros_cargo_det = $get_rfc['PO_POS_CARGO'];

            // ACTUALIZA FECHAS EN EL 15 
            if(count($registros_cargo_det) > 0){
                $this->actualiza15Cargo($registros_cargo_det);
            }
        }*/

        // Lógica inicial de fechas y parámetros
        if ($o_nres == 1 && $flg_only15 == 0) {
            if (count($doc_pedidos) == 0) {
                $fecha_actual = Carbon::now()->format('Ymd');
                $fecha_min = Carbon::now()->subYears(5)->format('Ymd');
                if ($fechadesde == '') {
                    $fechadesde = Carbon::now()->subYears(1)->format('Ymd');
                } else {
                    $fechadesde = Carbon::createFromFormat('Y-m-d', $fechadesde)->format('Ymd');
                }
                if ($fechafin == '') {
                    $fechafin = $fecha_actual;
                } else {
                    $fechafin = Carbon::createFromFormat('Y-m-d', $fechafin)->format('Ymd');
                }
                $parametros['PI_AUDAT'] = [
                    ['SIGN' => 'I', 'OPTION' => 'BT', 'LOW' => $fechadesde, 'HIGH' => $fechafin]
                ];
            }
        }

        $batchSize = 100; // Tamaño del lote
        $doc_pedidos_chunks = array_chunk($doc_pedidos, $batchSize);

        foreach ($doc_pedidos_chunks as $doc_pedidos_batch) {
            $parametros = [];
            foreach ($doc_pedidos_batch as $key => $value) {
                $parametros['PI_VBELN'][] = ['SIGN' => 'I', 'OPTION' => 'EQ', 'LOW' => $value['documento'], 'HIGH' => ''];
                if (isset($value['posicion'])) {
                    $parametros['PI_POSNR'][] = ['SIGN' => 'I', 'OPTION' => 'EQ', 'LOW' => $value['posicion'], 'HIGH' => ''];
                }
            }
    
            $get_rfc = $sap->ejecutarRFC('ZACSD_RFC_CARTAFIA_PED_FACT', $parametros);
    
            // Combinar resultados de los lotes
            if (count($get_rfc) > 0) {
                $result['rows_pick_cab'] = array_merge($result['rows_pick_cab'], $get_rfc['PO_ENTREGAS_LIKP']);
                $result['rows_pick_det'] = array_merge($result['rows_pick_det'], $get_rfc['PO_ENTREGAS_LIPS']);
                $result['rows_fact_cab'] = array_merge($result['rows_fact_cab'], $get_rfc['PO_PEDFACT_VBRK']);
                $result['rows_fact_det'] = array_merge($result['rows_fact_det'], $get_rfc['PO_PEDFACT_VBRP']);
                $result['rows_cargo_det'] = array_merge($result['rows_cargo_det'], $get_rfc['PO_POS_CARGO']);
            } /* else {
                $result['o_nres'] = 0;
                $result['o_msj'] = 'Error in batch processing';
                break;
            } */
        }
        
        if (count($result['rows_cargo_det']) > 0) {
            $this->actualiza15Cargo($result['rows_cargo_det']);
        }
                
        /* $result['o_nres'] = $o_nres;
        $result['o_msj'] = $o_msj;
        $result['rows_pick_cab'] = $registros_pick_cab;
        $result['rows_pick_det'] = $registros_pick_det;
        $result['rows_fact_cab'] = $registros_fact_cab;
        $result['rows_fact_det'] = $registros_fac_det; */
        $result['fechadesde'] = $fechadesde;
        $result['fechafin'] = $fechafin;  
        return $result;
    } 

    public function procesarPICKINGFACTURA($data_pick_cab=[],$data_pick_det=[],$data_fact_cab=[],$data_fact_det=[],$fechadesde='',$fechafin=''){
        $model = new Instituciones();
        $result['o_nres'] = 1;
        $result['o_msj'] = 'OK';

        $result = $model->limpiarTemp($fechadesde, $fechafin, 'PF');
        //dd($data_fact_det);
        // PICKING
        if($result['o_nres'] == 1 && count($data_pick_cab) > 0){
            try {
                $chunkSize = 50;
                $doc = array();

                foreach($data_pick_cab as $row){
                    //dd($row);
                    $doc[] = array(
                        'pckchcddocumento' =>$row['VBELN'],
                        'pckchcddptomercancia' => rtrim($row['VSTEL']) ?? null,
                        'pckchcdorganizacionventas' => rtrim($row['VKORG']) ?? null,
                        'pckchcdclaseentrega' => rtrim($row['LFART']) ?? null,
                        'pckdtfcfechaprevistamvtomercancia' => ($row['WADAT'] !== null && $row['WADAT'] !== '00000000') ? date('Y-m-d', strtotime($row['WADAT'])) : null,
                        'pckdtfcfechacarga' => ($row['LDDAT'] !== null && $row['LDDAT'] !== '00000000') ? date('Y-m-d', strtotime($row['LDDAT'])) : null,
                        'pckdtfcfechaplanificaciontransporte' => ($row['TDDAT'] !== null && $row['TDDAT'] !== '00000000') ? date('Y-m-d', strtotime($row['TDDAT'])) : null,
                        'pckdtfcfechaentrega' => ($row['LFDAT'] !== null && $row['LFDAT'] !== '00000000') ? date('Y-m-d', strtotime($row['LFDAT'])) : null,
                        'pckdtfcfechapicking' => ($row['KODAT'] !== null && $row['KODAT'] !== '00000000') ? date('Y-m-d', strtotime($row['KODAT'])) : null,
                        'pckchcdtipodoccomercial' => rtrim($row['VBTYP']) ?? null,
                        'pckchcddestinatariomercancia' => rtrim($row['KUNNR']) ?? null,
                        'pckchcdsolicitante' => rtrim($row['KUNAG']) ?? null,
                        'pckchcdgrupoclientes' => rtrim($row['KDGRP']) ?? null,
                        'pckdcnmpesototal' => rtrim($row['BTGEW']) ?? null,
                        'pckdcnmpesoneto' => rtrim($row['NTGEW']) ?? null,
                        'pckchcdunidadmedidapeso' => rtrim($row['GEWEI']) ?? null,
                        'pckdcnmvolumen' => rtrim($row['VOLUM']) ?? null,
                        'pckdcnmcantidadtotalbultos' => rtrim($row['ANZPK']) ?? null,
                        'pckchcdgrupotransporte' => rtrim($row['TRAGR']) ?? null,
                        'pckcddsguiaremision' => rtrim($row['XBLNR']) ?? null,
                        'pckdtfcfecharecepcioncfoc' => ($row['FEC_RECLI'] !== null && $row['FEC_RECLI'] !== '00000000') ? date('Y-m-d', strtotime($row['FEC_RECLI'])) : null, 
                    );
                    if(count($doc) > $chunkSize){
                        Picking_I_CAB::insert($doc);
                        $doc = array();
                    }
                }
                if(count($doc) > 0){
                    Picking_I_CAB::insert($doc);
                    $doc = array();
                }
            } catch (\Throwable $th) {
                $result['o_nres'] = 0;
                $result['o_msj'] = $th->getMessage();
                return $result;
            }
        }
        if($result['o_nres'] == 1 && count($data_fact_cab) > 0){
            try {
                $chunkSize = 50;
                $doc = array();

                foreach($data_fact_cab as $row){
                    //dd($row);
                    $doc[] = array(
                        'facchcddocumento' =>$row['VBELN'],
                        'facchcdclasefactura' => rtrim($row['FKART']) ?? null,
                        'facchcdtipofactura' => rtrim($row['FKTYP']) ?? null,
                        'facchcdtipodocumentocomercial' => rtrim($row['VBTYP']) ?? null,
                        'facchcdmonedadocumentocomercial' => rtrim($row['WAERK']) ?? null,
                        'facchcdorganizacionventas' => rtrim($row['VKORG']) ?? null,
                        'facchcdcanaldistribucion' => rtrim($row['VTWEG']) ?? null,
                        'facdtfcfechafactura' => ($row['FKDAT'] !== null && $row['FKDAT'] !== '00000000') ? date('Y-m-d', strtotime($row['FKDAT'])) : null,
                        'facchcdgrupoclientes' => rtrim($row['KDGRP']) ?? null,
                        'facdcnmtipocambio' => rtrim($row['KURRF']) ?? null,
                        'facchcdcondicionpago' => rtrim($row['ZTERM']) ?? null,
                        'facchcdviapago' => rtrim($row['ZLSCH']) ?? null,
                        'facdcnmvalorneto' => rtrim($row['NETWR']) ?? null,
                        'facchcdpagador' => rtrim($row['KUNRG']) ?? null,
                        'faccdchfacturasunat' => rtrim($row['XBLNR']) ?? null,
                        /* 'faccdchseriefacturasunat' => rtrim($row['XBLNR']) ? : null,
                        'faccdchnrofacturasunat' => rtrim($row['XBLNR']) ? : null, */
                        'faccdchseriefacturasunat' => (rtrim($row['XBLNR']) !== null && count($parts = explode('-', rtrim($row['XBLNR']))) >= 3) ? $parts[1] : null,
                        'faccdchnrofacturasunat' => (rtrim($row['XBLNR']) !== null && count($parts = explode('-', rtrim($row['XBLNR']))) >= 3) ? implode('-', array_slice($parts, 2)) : null,
                        'facdcnmimportecf' => rtrim($row['KWERT_ZPGO']) ?? null,
                    );
                    if(count($doc) > $chunkSize){
                        Factura_I_CAB::insert($doc);
                        $doc = array();
                    }
                }
                if(count($doc) > 0){
                    Factura_I_CAB::insert($doc);
                    $doc = array();
                }
            } catch (\Throwable $th) {
                $result['o_nres'] = 0;
                $result['o_msj'] = $th->getMessage();
                return $result;
            }
        }
        if($result['o_nres'] == 1){
            $rpta_det = $this->procesarPICKINGFACTURA_DET($data_pick_det,$data_fact_det);
            if($rpta_det['o_nres'] == 1){
                $rpta = $model->registrarFINAL($fechadesde,$fechafin,5);
                if($rpta['o_nres'] == 1){
                    $rpta = $model->registrarFINAL($fechadesde,$fechafin,6);
                    $result['o_nres'] = $rpta['o_nres'];
                    $result['o_msj'] = $rpta['o_msj'];
                }
                else{
                    $result['o_nres'] = 0;
                    $result['o_msj'] = $rpta_det['o_msj'] ?? 'Error final PCK';
                }
            }else{
                $result['o_nres'] = 0;
                $result['o_msj'] = $rpta_det['o_msj'] ?? 'Error procesos DET';
            }
        }
        return $result;
    }

    public function procesarPICKINGFACTURA_DET($data_pck_det,$data_fact_det){
        $result['o_nres'] = 1;
        $result['o_msj'] = 'OK';

        if($result['o_nres'] == 1 && count($data_pck_det) > 0){
            try {
                $chunkSize = 50;
                $doc = array();

                foreach($data_pck_det as $row){
                    $doc[] = array(
                        'pckchcddocumento' =>$row['VBELN'],
                        'pckinenposicion' => rtrim($row['POSNR']) ?? null,
                        'pckchcdtipoposicion' => rtrim($row['PSTYV']) ?? null,
                        'pckchcdcodigomaterial' => rtrim($row['MATNR']) ?? null,
                        'pckchcdcodigomaterialintroducido' => rtrim($row['MATWA']) ?? null,
                        'pckchcdgrupoarticulos' => rtrim($row['MATKL']) ?? null,
                        'pckchcdcentro' => rtrim($row['WERKS']) ?? null,
                        'pckchcdalmacen' => rtrim($row['LGORT']) ?? null,
                        'pckchcdlote' => rtrim($row['CHARG']) ?? null,
                        'pckchcdloteproveedor' => rtrim($row['LICHN']) ?? null,
                        'pckchcdjerarquiaproducto' => rtrim($row['PRODH']) ?? null,
                        'pckchcdcantidadentregadaumv' => rtrim($row['LFIMG']) ?? null,
                        'pckchcdunidadmedidabase' => rtrim($row['MEINS']) ?? null,
                        'pckchcdunidadmedidaventa' => rtrim($row['VRKME']) ?? null,
                        'pckdcnmpesoneto' => rtrim($row['NTGEW']) ?? null,
                        'pckdcnmpesobruto' => rtrim($row['BRGEW']) ?? null,
                        'pckchcdunidadmedidapeso' => rtrim($row['GEWEI']) ?? null,
                        'pckdcnmvolumen' => rtrim($row['VOLUM']) ?? null,
                        'pckdtfcfechapuestadisposicionmaterial' => ($row['MBDAT'] !== null && $row['MBDAT'] !== '00000000') ? date('Y-m-d', strtotime($row['MBDAT'])) : null,
                        'pckdcnmcantidadentregadaumalmacen' => rtrim($row['LGMNG']) ?? null,
                        'pckchcdgrupocarga' => rtrim($row['LADGR']) ?? null,
                        'pckchcdgrupotransporte' => rtrim($row['TRAGR']) ?? null,
                        'pckchcddocumentomodelo' => rtrim($row['VGBEL']) ?? null,
                        'pckinenposicionmodelo' => rtrim($row['VGPOS']) ?? null,
                    );
                    if(count($doc) > $chunkSize){
                        Picking_I_DET::insert($doc);
                        $doc = array();
                    }
                }
                if(count($doc) > 0){
                    Picking_I_DET::insert($doc);
                    $doc = array();
                }
            } catch (\Throwable $th) {
                $result['o_nres'] = 0;
                $result['o_msj'] = $th->getMessage();
                return $result;
            }
        }
        if($result['o_nres'] == 1 && count($data_fact_det) > 0){
            try {
                $chunkSize = 50;
                $doc = array();

                foreach($data_fact_det as $row){
                    $doc[] = array(
                        'facchcddocumento' =>$row['VBELN'],
                        'facinenposicion' => rtrim($row['POSNR']) ?? null,
                        'facdcnmcantidadfacturada' => rtrim($row['FKIMG']) ?? null,
                        'facchcdunidadmedidaventa' => rtrim($row['VRKME']) ?? null,
                        'facchcdunidadmedidabase' => rtrim($row['MEINS']) ?? null,
                        'facdcnmcantidadfacturaumentrega' => rtrim($row['FKLMG']) ?? null,
                        'facdcnmtipocambio' => rtrim($row['KURSK']) ?? null,
                        'facdcnmvalorneto' => rtrim($row['NETWR']) ?? null,
                        'facchcdcodigomaterial' => rtrim($row['MATNR']) ?? null,
                        'facchcdlote' => rtrim($row['CHARG']) ?? null,
                        'facchcdcentro' => rtrim($row['WERKS']) ?? null,
                        'facchcdalmacen' => rtrim($row['LGORT']) ?? null,
                        'facchcddocumentomodelo' => rtrim($row['VGBEL']) ?? null,
                        'facinenposicionmodelo' => rtrim($row['VGPOS']) ?? null,
                        'facdcnmimportecf' => rtrim($row['KWERT_ZPGO']) ?? null,
                    );
                    if(count($doc) > $chunkSize){
                        Factura_I_DET::insert($doc);
                        $doc = array();
                    }
                }
                if(count($doc) > 0){
                    Factura_I_DET::insert($doc);
                    $doc = array();
                }
            } catch (\Throwable $th) {
                $result['o_nres'] = 0;
                $result['o_msj'] = $th->getMessage();
                return $result;
            }
        }
        return $result;
    }

    /*------------------------------------------------------------- REG. ADJ. ------------------------------------------------------------- */
    public function registrarAdjunto($files_add=[],$codigo_ctf,$idcartafianza='',$idestadocartafianza,$usuarioid,$ip_maq,$idtipoadjunto,$idcartafianzafinal='',$flg_contrato=0){
        $model   = new Instituciones();
        $conexionSFTP = ($flg_contrato == 1) ? 'sftp_contrato' : 'sftp_carta_fianza';
        $directorioCTF = '/' . $codigo_ctf;
        $o_nres = 1;
        $o_msj = 'ok';

        try{
            // Crear el directorio para la orden de compra (oc) si no existe
            if(!Storage::disk($conexionSFTP)->exists($directorioCTF)){
                Storage::disk($conexionSFTP)->makeDirectory($directorioCTF);
            }
        }catch (\Exception $e) {
            $o_nres = 0;
            $o_msj .= 'Error al crear directorio de archivos.';
            DB::rollBack();
        }
        if($o_nres == 1 && (isset($files_add) || !is_null($files_add))){
            $RUTA_FILE = $codigo_ctf.'/'; // carpeta de guardado
            foreach ($files_add as $file) {
                // ADD
                try{
                    $name = $file->getClientOriginalName();                          
                    $exte = strtolower($file->getClientOriginalExtension());            
                    $size = $file->getSize();                                          
                    $newName = explode('.',$name);
                    array_pop($newName);
                    $newName = implode('_',$newName);
                    $nameFile = date('YmdHi').'-'.Str::slug($newName).'.'.$exte;
                    $nameFileOriginal = $name;

                    if($flg_contrato == 1){
                        $info[0] = 23;
                    }else{
                        $info[0] = 11;
                        $info[9] = $idcartafianzafinal;
                    }

                    $info[1] = $idcartafianza;
                    $info[2] = $nameFile;                         
                    $info[3] = $RUTA_FILE;         
                    $info[4] = $exte;                               
                    $info[5] = $size;                               
                    $info[6] = $nameFileOriginal;
                    $info[7] = $idestadocartafianza; // Estado
                    $info[8] = $idtipoadjunto;

                    $info[19] = $usuarioid;
                    $info[20] = $ip_maq;
                    $data_rpta_add   = $model->mantenimientoData($info);unset($info);
                    $data_rpta_add = $data_rpta_add['registro'];
                    $o_nres = $data_rpta_add[0]->o_nres;
                    $o_msj = $data_rpta_add[0]->o_msj ?? 'Error al registrar adjunto.';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al registrar adjunto.';
                        break;
                    }else{
                        Storage::disk($conexionSFTP)->putFileAs($directorioCTF, $file, $nameFile); // GUARDA ADD
                    } 
                }catch(\Exception $e){
                    DB::rollBack();
                    $o_nres = 0;
                    $o_msj = 'Error en cargar los archivos: '.$e->getMessage();
                    break;
                }
            }
        } 

        $result['o_nres'] = $o_nres ?? 0;
        $result['o_msj'] = $o_msj ?? 'Error funcion adjunto.';

        return $result;
    }

    public function visualizacion_previa_archivo(string $disk,string $ruta_file,string $filename){
        
        if($disk == 'sftp_contrato' && !Storage::disk($disk)->exists($ruta_file)){
                $disk = 'sftp_contrato_sagitario';
        }        
        if(!Storage::disk($disk)->exists($ruta_file)){
            //return response()->json('El archivo ['.$ruta_file.'] no existe.');
            return response()->json('No se encontro el documento.');
        }

        $cacheKey = 'adjunto|||' . $ruta_file;
        Cache::forget($cacheKey);
        $response = Cache::get($cacheKey);

        if(!$response){
            $path = Storage::disk($disk)->get($ruta_file);
            $mimeType = Storage::disk($disk)->mimeType($ruta_file);

            $response = response($path, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'filename="' .$filename. '"')
            ->header('Cache-Control' , 'public, max-age=86400');

            Cache::put($cacheKey, $response, now()->addDay());
        }

        return $response;
    }

    /* MIGRACION */
    public function migracionMasiva(){
        $model   = new Instituciones();
        $o_nres = 1;
        $o_msj = '';
        // Vbles. Requeridas
        $detalle = [];
        $ids_cartafianza = [];
        $combined_det = [];

        /* CONSULTA DATA EXCEL - DETALLE */
        $info[0] = 16;
        $data   = $model->consultarData($info);unset($info);
        $data = $data['registro'];
        foreach($data as $value){
            $key_det = $value->idprocesocab.'_'.$value->nro_ctf_mae.'_'.$value->idprocesodet;
            if (!isset($detalle[$key_det])) {
                $detalle[$key_det] = [
                    'idprocesodet' => $value->idprocesodet,
                    'beneficiario' => $value->beneficiario,
                    'garantizado' => $value->garantizado,
                    'importe_adjudicado' => $value->mae_mont/0.1,
                    'importe_numero' => ceil(($value->mae_mont/0.10) / 100) * 100,
                    'importe_ctf' => $value->mae_mont,
                    'respaldo' => $value->respaldo,
                    'fecha_vcto' => $value->fecha_vcto_mae,
                    'fecha_esperada' => $value->fecha_emi_mae,
                    'cantidad' => $value->cantidad_final,
                    'usuario' => 1,
                    'ip_maq' => 'TI-MIGRA',
                ];
            }
        }
        /* CONSULTA DATA EXCEL - CABECERA */
        $info[0] = 17;
        $data   = $model->consultarData($info);unset($info);
        $data = $data['registro'];
        foreach($data as $value){
            $key_cab = $value->idprocesocab.'_'.$value->nro_ctf_mae;
            if (!isset($cabecera[$key_cab])) {
                $cabecera[$key_cab] = [
                    'beneficiario' => $value->beneficiario,
                    'garantizado' => $value->garantizado,
                    'importe_adjudicado' => $value->mae_mont/0.1,
                    'importe_numero' => ceil(($value->mae_mont/0.10) / 100) * 100,
                    'importe_ctf' => $value->mae_mont,
                    'nro_ctf' => $value->nro_ctf_mae,
                    'respaldo' => $value->respaldo,
                    'fecha_vcto' => $value->fecha_vcto_mae,
                    'fecha_esperada' => $value->fecha_emi_mae,
                    'usuario' => 1,
                    'ip_maq' => 'TI-MIGRA',
                ];
            }
        }


        DB::beginTransaction(); // INICIO TRX DB
        foreach($data as $value){
            $key_cab = $value->idprocesocab.'_'.$value->nro_ctf_mae;
            if($o_nres == 1){
                $info[0] = 7;
                $info[1] = $value->beneficiario;
                $info[2] = $value->garantizado;
                $info[3] = $value->mae_mont/0.1;
                $info[4] = ceil(($value->mae_mont/0.10) / 100) * 100;
                $info[5] = $value->mae_mont;
                $info[6] = $value->respaldo;
                $info[7] = $value->fecha_vcto_mae;
                $info[8] = $value->fecha_emi_mae;

                $info[19] = 1;
                $info[20] = 'TI-MIGRA';
                //dd($info);

                $rpta = $model->mantenimientoData($info);unset($info);
                $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [7 - CTF]';
                $idcartafianza = $rpta['registro'][0]->idcartafianza ?? 0;
                $ids_cartafianza[$key_cab] = $idcartafianza;
                if($o_nres == 0){
                    DB::rollBack();
                    $o_nres = 0;
                    $o_msj = ($o_msj != '') ? $o_msj : 'Error al registrar la solicitud de carta fianza.';
                    break;
                }  
            }
        }

        //dd($detalle,$ids_cartafianza);

        // Mezclar idctf vs Cabecerea
        foreach ($cabecera as $key => $cab) {
            $cabParts = explode('_', $key);
            $cabKeyBase = $cabParts[0] . '_' . $cabParts[1];
            if (isset($ids_cartafianza[$cabKeyBase])) {
                $cab['idcartafianza'] = $ids_cartafianza[$cabKeyBase];
                $combined_cab[$key] = $cab;
            }
        }
        // Mezclar idctf vs Detalle
        foreach ($detalle as $key => $details) {
            $detalleParts = explode('_', $key);
            $detalleKeyBase = $detalleParts[0] . '_' . $detalleParts[1];
            if (isset($ids_cartafianza[$detalleKeyBase])) {
                $details['idcartafianza'] = $ids_cartafianza[$detalleKeyBase];
                $combined_det[$key] = $details;
            }
        }
        //dd($combined_cab);
        foreach ($combined_det as $key => $details) {
            // INSERTO PROCESO x CTF
            $info[0] = 10;
            $info[1] = $details['idcartafianza'];
            $info[2] = $details['idprocesodet'];
            $info[3] = $details['cantidad'];

            $info[19] = 1;
            $info[20] = 'TI-MIGRA';

            $rpta = $model->mantenimientoData($info);unset($info);
            $o_nres = $rpta['registro'][0]->o_nres ?? 0;
            $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [10 - CTF]';
            if($o_nres == 0){
                DB::rollBack();
                $o_nres = 0;
                $o_msj = ($o_msj != '') ? $o_msj : 'Error al registrar la solicitud de carta fianza con proceso.';
                break;
            } 
        }
        foreach ($combined_cab as $key => $cab) {
            // INSERTO CARTA FIANZA FINAL
            if($o_nres == 1){
                $info[0] = 12;
                $info[1] = $cab['idcartafianza'];
                $info[2] = $cab['nro_ctf'];
                $info[3] = $cab['importe_ctf'];
                $info[4] = $cab['fecha_esperada'];
                $info[5] = $cab['fecha_vcto'];
            
                $info[19] = 1;
                $info[20] = 'TI-MIGRA';
                //dd($info);

                $rpta = $model->mantenimientoData($info);unset($info);
                $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [12 - CTF]';
                $idcartafianzafinal = $rpta['registro'][0]->idcartafianzafinal ?? 0;
                if($o_nres == 0){
                    DB::rollBack();
                    $o_nres = 0;
                    $o_msj = ($o_msj != '') ? $o_msj : 'Error al cambiar estado de carta fianza.';
                    break;
                }else{
                    $info[0] = 8;
                    $info[1] = $cab['idcartafianza'];
                    $info[2] = 7; // estado
                    $info[3] = 1;
                
                    $info[19] = 1;
                    $info[20] = 'TI-MIGRA';

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [8 - CTF]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al cambiar estado de carta fianza.';
                        break;
                    }else{
                        $info[0] = 13;
                        $info[1] = $idcartafianzafinal;
                        $info[2] = 3; // estado
                        $info[3] = 0;
                    
                        $info[19] = 1;
                        $info[20] = 'TI-MIGRA';
    
                        $rpta = $model->mantenimientoData($info);unset($info);
                        $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                        $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [8 - CTF]';
                        if($o_nres == 0){
                            DB::rollBack();
                            $o_nres = 0;
                            $o_msj = ($o_msj != '') ? $o_msj : 'Error al cambiar estado de carta fianza.';
                            break;
                        }
                    }
                }  
            }
        }
        
        if($o_nres == 1){
            DB::commit(); // FIN TRX DB - 1
        }
        $result['o_nres'] = $o_nres;
        $result['o_msj'] = $o_msj;    

        return $result;
    }

    /* ACTUALIZACION TOTAL(41,11,15,80,92) */
    public function getTOTAL($nro_proceso){
        $model = new Instituciones();
        $doc_entregas_11 = [];
        $doc_ocs_15 = [];
        $result['o_nres'] = 1;
        $result['o_msj'] = 'ok';
        $array_doc[] = $nro_proceso;

        // PROCESAR ENTREGAS DE TODO EL 41
        // PROCESAR 41-46
        $rpta_rfc = $this->getPROCESOS_RFC('', '',$array_doc);
        if($rpta_rfc['o_nres'] == 1 && count($rpta_rfc['rows_cab']) > 0 && count($rpta_rfc['rows_det']) > 0){
            $rpta_procesado = $this->procesarPROCESOS($rpta_rfc['rows_cab'], $rpta_rfc['rows_det'],'','');   
            // ENTREGAS   
            $rpta_rfc = $this->getENTREGAS_RFC('', '',$nro_proceso,'');  
            //dd($rpta_rfc);
            if($rpta_rfc['o_nres'] == 1 && count($rpta_rfc['rows_cab']) > 0 && count($rpta_rfc['rows_det']) > 0){
                $rpta_procesado = $this->procesarENTREGAS($rpta_rfc['rows_cab'], $rpta_rfc['rows_det'],'','');      
                $result['o_nres'] = $rpta_procesado['o_nres'];
                $result['o_msj'] = $rpta_procesado['o_msj'];
                if($result['o_nres'] == 1){
                    foreach($rpta_rfc['rows_cab'] as $value){
                        $doc_entregas_11[] = [
                            //'posicion' => $value->posicion,
                            'documento' => $value['VBELN']
                        ];
                    }
                    $rpta_rfc = $this->getPEDIDOS_RFC('', '',$doc_entregas_11);
                    if($rpta_rfc['o_nres'] == 1 && count($rpta_rfc['rows_cab']) > 0 && count($rpta_rfc['rows_det']) > 0){
                        $rpta_procesado = $this->procesarPEDIDOS($rpta_rfc['rows_cab'], $rpta_rfc['rows_det'],'','');      
                        $result['o_nres'] = $rpta_procesado['o_nres'];
                        $result['o_msj'] = $rpta_procesado['o_msj'];
                        if($result['o_nres'] == 1){
                            foreach($rpta_rfc['rows_cab'] as $value){
                                $doc_ocs_15[] = [
                                    'documento' => $value['VBELN']
                                ];
                            }
                            $rpta_rfc = $this->getPICKINGREPARTOFACTURA_RFC('','',$doc_ocs_15,1);
                            if($rpta_rfc['o_nres'] == 1 && count($rpta_rfc['rows_pick_cab']) > 0 && count($rpta_rfc['rows_pick_det']) > 0){
                                $rpta_procesado = $this->procesarPICKINGFACTURA($rpta_rfc['rows_pick_cab'], $rpta_rfc['rows_pick_det'],$rpta_rfc['rows_fact_cab'],$rpta_rfc['rows_fact_det'],'','');      
                                $result['o_nres'] = $rpta_procesado['o_nres'];
                                $result['o_msj'] = $rpta_procesado['o_msj'];
                            }
                        }
                    }
                }   
            }     
        } 

        // DEPURACION DE POSICIONES/DOCUMENTOS ELIMINADOS
        DB::beginTransaction(); 
        // PROCESAR Y ACTUALIZAR CONTRATOS
        if($result['o_nres'] == 1){
            $info[0] = 19;
            //$info[1] = $nro_proceso;

            $rpta   = $model->mantenimientoData($info);unset($info);
            $result['o_nres'] = $rpta['registro'][0]->o_nres ?? 0;
            $result['o_msj'] = $rpta['registro'][0]->o_msj ?? 'Error: SP [33 - HOMOLOGACION]';
            $result['contador_mae'] = $rpta['registro'][0]->contador_mae ?? 0;
            $result['contador_cxe'] = $rpta['registro'][0]->contador_cxe ?? 0;
        }
        
        if($result['o_nres'] == 1){
            $info[0] = 33;
            $info[1] = $nro_proceso;

            $rpta   = $model->mantenimientoData($info);unset($info);
            $result['o_nres'] = $rpta['registro'][0]->o_nres ?? 0;
            $result['o_msj'] = $rpta['registro'][0]->o_msj ?? 'Error: SP [33 - HOMOLOGACION]';
        }

        /* ACTUALIZA NC */
        if($result['o_nres'] == 1){
            $info[0] = 25;
            $info[2] = $nro_proceso;
            $data = $model->consultarData($info);unset($info);
            $cabecera = $data['registro'];
            if(count($cabecera) > 0){
                /* ACTUALIZO NIVEL DE CUMPLIMIENTO */
                $info[0] = 31;
                $info[1] = $cabecera[0]->idprocesocab;
                $info[2] = $cabecera[0]->nivel_cumplimiento;
                $info[3] = $cabecera[0]->nivel_cumplimiento_ejecucion;
                $rpta = $model->mantenimientoData($info);unset($info);
                $result['o_nres'] = $rpta['registro'][0]->o_nres ?? 0;
                $result['o_msj'] = $rpta['registro'][0]->o_msj ?? 'Error: SP [31 - ACT. NC]';
            }
        }

        if($result['o_nres'] == 0){
            DB::rollBack();
        }else{
            DB::commit();
        }

        return $result;
    }
    
    public function actualiza15Cargo($rows_cargo_det){
        $model = new Instituciones();
        // Filtra el array para obtener solo los elementos cuyo ZCODE empiece con 'DT'
        $filtered = array_filter($rows_cargo_det, function($item) {
            return strpos($item['ZCODE'], 'DT') === 0;
        });

        //dd($filtered);
        // Si no hay elementos después de filtrar, retorna null o lo que sea apropiado en tu caso
        if (empty($filtered)) {
            return null;
        }
        
        // Ordena los elementos filtrados por FEC_ENT en orden descendente
        /* usort($filtered, function($a, $b) {
            return strtotime($b['FEC_ENT']) - strtotime($a['FEC_ENT']);
        }); */
        // El primer elemento en el array ordenado es el que tiene la fecha más reciente
        //$ultimoRegistro = $filtered[0];
        
        //$info_x = [];
        foreach($filtered as $value){
            $info[0] = 34;
            $info[1] = $value['PEDIDO'];
            $info[2] = ($value['FECHA_ENT'] == '00000000') ? '' : $value['FECHA_ENT'];
            $info[3] = ($value['FEC_RECLI'] == '00000000') ? '' : $value['FEC_RECLI'];
            //$info_x[] = $info;
            
            $rpta = $model->mantenimientoData($info);unset($info);
        }

        //dd($info_x);
        return;

    }

    // CORREOS
    public function enviarMailSolicitud($idcartafianza){
        $model = new Instituciones();
        $o_nres = 1;
        $o_msj = 'Correo Enviado';
        $contactos_para = [];
        $contactos_cc = [];

        /* DATA */
        // Consulta de Comprobante
        $info[0] = 7;
        $info[2] = $idcartafianza;
        $data   = $model->consultarData($info);unset($info);
        $data = $data['registro'];
        $codigo_cartafianza=null;
        $proceso=null;
        // Datos de CAB
        $codigo_cartafianza = '';
        $proceso = '';
        foreach($data as $value){
            $proceso = $value->proceso;
            $codigo_cartafianza = $value->codigo_cartafianza;
        }

        $asunto = 'Solicitud de Carta Fianza '. $codigo_cartafianza .': ' . $proceso;

        /* CONTACTOS */ 
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_1'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_2'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_3'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_4'); // Copia

        $info[0] = 28;
        $info[1] = $idcartafianza;
        $contactos = $model->consultarData($info);unset($info);
        $contactos = $contactos['registro'];
        foreach($contactos as $contacto){
            switch($contacto->tipo){
                case 'to':
                    $contactos_para[] = $contacto->email;
                    break;
                case 'cc':
                    $contactos_cc[] = $contacto->email;
                    break;
            }
        }
        //dd($contactos_para);

        /* ADJUNTOS */ 
        $info[0] = 13;
        $info[1] = $idcartafianza;
        $adjuntos = $model->consultarData($info);unset($info);
        $adjuntos = $adjuntos['registro'];

        /* HISTORIAL */
        $info[0] = 9;
        $info[1] = $idcartafianza;
        $historial = $model->consultarData($info);unset($info);
        $historial = $historial['registro'];

        if(count($contactos_para) > 0){
            try{
                $data_correo['sctf'] = $data;
                $data_correo['contactos'] = $contactos;
                $data_correo['contactos_para'] = $contactos_para;
                $data_correo['contactos_cc'] = $contactos_cc;
                $data_correo['historial'] = $historial;
                $data_correo['fianza'] = $codigo_cartafianza;
                $data_correo['flg_final'] = 0;
                $obj = new EnviarMail(
                    "modulo.comercial.procesos.instituciones.alertas.mail_registro_sctf",
                    $data_correo
                );
                $obj->setDesde(env('MAIL_INTERNO'));
                $obj->setAlias("Tecnologías de la Información");
                $obj->setAsunto($asunto);
                if(count($adjuntos) > 0){
                    foreach($adjuntos as $adjunto){
                        //Esta condicion omite adjuntar archivos si no existen en la ruta por algun motivo
                        if(Storage::disk('sftp_carta_fianza')->exists($adjunto->ruta_descarga)){
                            $obj->setArchivo($adjunto->ruta_descarga,'sftp_carta_fianza');
                        }
                    }
                    /* $zip = new ZipArchive();
                    $zipFileName = 'Adjuntos_SCTF.zip';
    
                    if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
                        foreach ($adjuntos as $adjunto) {
                            $zip->addFile($adjunto->ruta_descarga, basename($adjunto->ruta_descarga));
                        }
                        $zip->close();
                        $obj->setArchivo($zipFileName, 'sftp_carta_fianza');
                    } else {
                        $o_nres = 0;
                        //$o_msj = 'No se pudo crear el archivo zip.';
                        throw new Exception('No se pudo crear el archivo zip.');
                    } */
                } 
                Mail::to($contactos_para)
                ->cc($contactos_cc)
                ->send($obj);
            }catch(\Exception $e){
                $o_nres = 0;
                //dd($e);
                $o_msj = 'Error al enviar correo: '.$e->getMessage() . ', '.$e->getLine() . ', '.$e->getFile();
                //$o_msj = 'Error al enviar correo: EXTERNO';
            }     
        }else{
            $o_nres = 0;
            $o_msj = 'No se encontraron usuarios para enviar correo.';
        }


        $result['o_nres']  = $o_nres;
        $result['o_msj']  = $o_msj;
        //dd($result);

        return $result;
    }

    public function enviarMailFinal($idcartafianzafinal){
        $model = new Instituciones();
        $o_nres = 1;
        $o_msj = 'Correo Enviado';
        $contactos_para = [];
        $contactos_cc = [];

        /* DATA */
        // Consulta de Comprobante
        $info[0] = 7;
        $info[4] = $idcartafianzafinal;
        $data   = $model->consultarData($info);unset($info);
        $data = $data['registro'];
        // Datos de CAB
        foreach($data as $value){
            $proceso = $value->proceso;
            $nro_ctf_f = $value->nro_ctf_f;
        }

        $asunto = 'Carta Fianza '. $nro_ctf_f .': ' . $proceso;

        /* CONTACTOS */ 
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_1'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_2'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_3'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_4'); // Copia

        $info[0] = 28;
        $info[2] = $idcartafianzafinal;
        $contactos = $model->consultarData($info);unset($info);
        $contactos = $contactos['registro'];
        foreach($contactos as $contacto){
            switch($contacto->tipo){
                case 'to':
                    $contactos_para[] = $contacto->email;
                    break;
                case 'cc':
                    $contactos_cc[] = $contacto->email;
                    break;
            }
        }
        //dd($contactos_para);

        /* ADJUNTOS */ 
        $info[0] = 15;
        $info[1] = $idcartafianzafinal;
        $adjuntos = $model->consultarData($info);unset($info);
        $adjuntos = $adjuntos['registro'];

        /* HISTORIAL */
        $info[0] = 14;
        $info[1] = $idcartafianzafinal;
        $historial = $model->consultarData($info);unset($info);
        $historial = $historial['registro'];

        if(count($contactos_para) > 0){
            try{
                $data_correo['sctf'] = $data;
                $data_correo['contactos'] = $contactos;
                $data_correo['contactos_para'] = $contactos_para;
                $data_correo['contactos_cc'] = $contactos_cc;
                $data_correo['historial'] = $historial;
                $data_correo['fianza'] = $nro_ctf_f;
                $data_correo['flg_final'] = 1;
                //dd($data_correo);
                $obj = new EnviarMail(
                    "modulo.comercial.procesos.instituciones.alertas.mail_registro_sctf",
                    $data_correo
                );
                $obj->setDesde(env('MAIL_INTERNO'));
                $obj->setAlias("Tecnologías de la Información");
                $obj->setAsunto($asunto);
                if(count($adjuntos) > 0){
                    foreach($adjuntos as $adjunto){
                        //Esta condicion omite adjuntar archivos si no existen en la ruta por algun motivo
                        if(Storage::disk('sftp_carta_fianza')->exists($adjunto->ruta_descarga)){
                            $obj->setArchivo($adjunto->ruta_descarga,'sftp_carta_fianza');
                        }
                    }
                    /* $zip = new ZipArchive();
                    $zipFileName = 'Adjuntos_SCTF.zip';
    
                    if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
                        foreach ($adjuntos as $adjunto) {
                            $zip->addFile($adjunto->ruta_descarga, basename($adjunto->ruta_descarga));
                        }
                        $zip->close();
                        $obj->setArchivo($zipFileName, 'sftp_carta_fianza');
                    } else {
                        $o_nres = 0;
                        //$o_msj = 'No se pudo crear el archivo zip.';
                        throw new Exception('No se pudo crear el archivo zip.');
                    } */
                } 
                Mail::to($contactos_para)
                ->cc($contactos_cc)
                ->send($obj);
            }catch(\Exception $e){
                $o_nres = 0;
                //dd($e);
                $o_msj = 'Error al enviar correo: '.$e->getMessage() . ', '.$e->getLine() . ', '.$e->getFile();
                //$o_msj = 'Error al enviar correo: EXTERNO';
            }     
        }else{
            $o_nres = 0;
            $o_msj = 'No se encontraron usuarios para enviar correo.';
        }


        $result['o_nres']  = $o_nres;
        $result['o_msj']  = $o_msj;
        //dd($result);

        return $result;
    }

    public function enviarMailCronogramaVcto(){
        $model = new Instituciones();
        $o_nres = 1;
        $o_msj = 'Correo Enviado';
        $contactos_para = [];
        $contactos_cc = [];
        
        $fecha = new DateTime();
        $fecha->modify('+2 months');
        $anio = $fecha->format('Y');
        $mes = $fecha->format('m');

        $meses = [
            '01' => 'Enero',
            '02' => 'Febrero',
            '03' => 'Marzo',
            '04' => 'Abril',
            '05' => 'Mayo',
            '06' => 'Junio',
            '07' => 'Julio',
            '08' => 'Agosto',
            '09' => 'Septiembre',
            '10' => 'Octubre',
            '11' => 'Noviembre',
            '12' => 'Diciembre'
        ];

        $mes_desc = $meses[$mes];

        $asunto = 'Programación de Renovación Cartas Fianzas '. $mes_desc . ' - '. $anio;
        /* DATA */
        $info[0] = 23;
        $info[1] = $mes;
        $info[2] = $anio;
        $data   = $model->consultarData($info);unset($info);
        $data = $data['registro'];

        /* CONTACTOS */ 
        $contactos_para[] = env('CARTA_FIANZA_CRONOGRAMA_VCTO_PARA');
        $contactos_cc[] = env('CARTA_FIANZA_CRONOGRAMA_VCTO_CC_1'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CRONOGRAMA_VCTO_CC_2');// Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_1'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_2'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_3'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_4'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_5'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_6'); // Copia

        if(count($contactos_para) > 0){
            try{
                $data_correo['data'] = $data;
                $data_correo['mes'] = $mes_desc;
                $data_correo['anio'] = $anio;
                //dd($data_correo);
                $obj = new EnviarMail(
                    "modulo.comercial.procesos.instituciones.alertas.mail_cronograma_vcto",
                    $data_correo
                );
                $obj->setDesde(env('MAIL_INTERNO'));
                $obj->setAlias("Tecnologías de la Información");
                $obj->setAsunto($asunto);
                Mail::to($contactos_para)
                ->cc($contactos_cc)
                ->send($obj);
            }catch(\Exception $e){
                $o_nres = 0;
                //dd($e);
                $o_msj = 'Error al enviar correo: '.$e->getMessage() . ', '.$e->getLine() . ', '.$e->getFile();
                //$o_msj = 'Error al enviar correo: EXTERNO';
            }     
        }else{
            $o_nres = 0;
            $o_msj = 'No se encontraron usuarios para enviar correo.';
        }

        $result['o_nres']  = $o_nres;
        $result['o_msj']  = $o_msj;
        //dd($result);

        return $result;
    }

    public function enviarMailComercial(){
        $model = new Instituciones();
        $o_nres = 1;
        $o_msj = 'Correo Enviado';
        $contactos_para = [];
        $contactos_cc = [];
        
        $fecha = new DateTime();
        $fecha->modify('+2 months');
        $anio = $fecha->format('Y');
        $mes = $fecha->format('m');

        $meses = [
            '01' => 'Enero',
            '02' => 'Febrero',
            '03' => 'Marzo',
            '04' => 'Abril',
            '05' => 'Mayo',
            '06' => 'Junio',
            '07' => 'Julio',
            '08' => 'Agosto',
            '09' => 'Septiembre',
            '10' => 'Octubre',
            '11' => 'Noviembre',
            '12' => 'Diciembre'
        ];

        $mes_desc = $meses[$mes];

        $asunto = 'Revisión de Cartas Fianzas a Vencer '. $mes_desc . ' - '. $anio;
        /* DATA */
        /* $info[0] = 26;
        $info[13] = $anio;
        $info[14] = $mes;
        $info[20] = 1;
        $info[30] = 1;
        $data   = $model->consultarData($info);unset($info);
        $data = $data['registro']; */
        /* CONTACTOS */ 
        $contactos_para[] = env('CARTA_FIANZA_COMERCIAL_VCTO_PARA');
        $contactos_cc[] = env('CARTA_FIANZA_COMERCIAL_VCTO_CC_1'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_COMERCIAL_VCTO_CC_2'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_COMERCIAL_VCTO_CC_3'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_COMERCIAL_VCTO_CC_4'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_1'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_2'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_3'); // Copia
        $contactos_cc[] = env('CARTA_FIANZA_CC_TI_4'); // Copia
        //dd($contactos_para,$contactos_cc);
        if(count($contactos_para) > 0){
            try{
                //$data_correo['data'] = $data;
                $data_correo['mes'] = $mes_desc;
                $data_correo['anio'] = $anio;
                //dd($data_correo);
                $obj = new EnviarMail(
                    "modulo.comercial.procesos.instituciones.alertas.mail_comercial_vcto",
                    $data_correo
                );
                $obj->setDesde(env('MAIL_INTERNO'));
                $obj->setAlias("Tecnologías de la Información");
                $obj->setAsunto($asunto);
                Mail::to($contactos_para)
                ->cc($contactos_cc)
                ->send($obj);
            }catch(\Exception $e){
                $o_nres = 0;
                //dd($e);
                $o_msj = 'Error al enviar correo: '.$e->getMessage() . ', '.$e->getLine() . ', '.$e->getFile();
                //$o_msj = 'Error al enviar correo: EXTERNO';
            }     
        }else{
            $o_nres = 0;
            $o_msj = 'No se encontraron usuarios para enviar correo.';
        }

        $result['o_nres']  = $o_nres;
        $result['o_msj']  = $o_msj;
        //dd($result);

        return $result;
    }

    public function evaluarRenovacionAuto(){
        $model = new Instituciones();
        $o_nres = 1;
        $o_msj = 'ok';
        
        $array_renovaciones = [];
        $ids_cartafianzafinal = [];
        //$fecha = '2024-07-15';
        $fecha = Carbon::now()->addDays(30)->toDateString();

        $fecha_actual = new DateTime();
        $anio_actual = $fecha_actual->format('Y');
        $mes = $fecha_actual->format('m');
        $meses = [
            '01' => 'ENERO',
            '02' => 'FEBRERO',
            '03' => 'MARZO',
            '04' => 'ABRIL',
            '05' => 'MAYO',
            '06' => 'JUNIO',
            '07' => 'JULIO',
            '08' => 'AGOSTO',
            '09' => 'SEPTIEMBRE',
            '10' => 'OCTUBRE',
            '11' => 'NOVIEMBRE',
            '12' => 'DICIEMBRE'
        ];
        $mes_desc = $meses[$mes];
        
        /* OBTIENE PROCESOS A EVALUAR */
        $info[0] = 26;
        $info[22] = $fecha;
        $data   = $model->consultarData($info);unset($info);
        $data = $data['registro'];
        //dd($data);

        if(count($data) > 0){
            foreach($data as $key => $value){
                $cumplimiento = ($value->nivel_cumplimiento == null) ? 0 : $value->nivel_cumplimiento_ejecucion;
                $deuda = (float)str_replace(',', '', $value->monto_deuda);
                // Inicializa un array temporal para cada iteración
                $temp = [
                    'idcartafianzafinal' => [],
                    'motivo' => [],
                    'fianza' => [],
                    'banco' => [],
                    'monto' => [],
                    'periodos' => []
                ];
                /* EVALUA % DE EJECUCION */
                if($cumplimiento < 75){ /* nivel_cumplimiento_ejecucion */
                    $temp['idcartafianzafinal'] = $value->idcartafianzafinal;
                    $temp['motivo'] = 'Porcentaje de Ejecución: '.$cumplimiento.' %';
                    $temp['fianza'] = $value->nro_ctf_f;
                    $temp['banco'] = $value->banco_corta_f;
                    $temp['monto'] = $value->importe_banco_f_moneda;
                    $temp['periodos'] = 2;
                }
                /* EVALUA DEUDA */
                else if($deuda > 0){
                    $temp['idcartafianzafinal'] = $value->idcartafianzafinal;
                    $temp['motivo'] = 'Deuda Pendiente: '.$value->monto_deuda.' PEN';
                    $temp['fianza'] = $value->nro_ctf_f;
                    $temp['banco'] = $value->banco_corta_f;
                    $temp['monto'] = $value->importe_banco_f_moneda;
                    $temp['periodos'] = 2;
                }
                /* EVALUA ESTATUS DE LEGAL */
                else if($value->situacion_legal != ''){
                    $temp['idcartafianzafinal'] = $value->idcartafianzafinal;
                    $temp['motivo'] = 'Situación Legal: '.$value->situacion_legal;
                    $temp['fianza'] = $value->nro_ctf_f;
                    $temp['banco'] = $value->banco_corta_f;
                    $temp['monto'] = $value->importe_banco_f_moneda;
                    $temp['periodos'] = 2;
                }
                else{
                    $temp['idcartafianzafinal'] = $value->idcartafianzafinal;
                    $temp['motivo'] = 'Sin proceso 41 asociado.';
                    $temp['fianza'] = $value->nro_ctf_f;
                    $temp['banco'] = $value->banco_corta_f;
                    $temp['monto'] = $value->importe_banco_f_moneda;
                    $temp['periodos'] = 2;
                }
                /* EVALUA PENALIDAD?? */
                
                // Añade el array temporal al array de renovaciones bajo una clave única
                $array_renovaciones[$key] = $temp;
            }
            //dd($array_renovaciones);
            if(count($array_renovaciones) > 0){
                // Iterar sobre el array y recoger los idcartafianzafinal
                DB::beginTransaction(); // INICIO TRX DB
                foreach($array_renovaciones as $renovacion) {
                    $ids_cartafianzafinal[] = $renovacion['idcartafianzafinal'];
                    // Mantenimiento de Renovación
                    $info[0] = 30;
                    $info[1] = $renovacion['idcartafianzafinal'];
                    $info[2] = 2; // 2 PERIODOS EN AUTOMATICO;
                
                    $info[19] = 1;
                    $info[20] = 'TI-AUTORENOVACION';
                    //dd($info);

                    $rpta = $model->mantenimientoData($info);unset($info);
                    $o_nres = $rpta['registro'][0]->o_nres ?? 0;
                    $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [30 - CTF]';
                    if($o_nres == 0){
                        DB::rollBack();
                        $o_nres = 0;
                        $o_msj = ($o_msj != '') ? $o_msj : 'Error al gestionar renovación.';
                        break;
                    }  
                }      
                
                if($o_nres == 1){
                    DB::commit(); // FIN TRX DB - 1
                }
        
            }else{
                $o_nres = 0;
                $o_msj = 'No existen parámetros de fianzas que requieran renovación automática.';
            }
        }else{
            $o_nres = 0;
            $o_msj = 'No existen fianzas a renovar en la fecha seleccionada.';
        }   

        // ENVIA CORREO DE ALERTA
        /* OBTIENE DATA DE FIANZAS A RENOVAR AUTOMATICO */
        if($o_nres == 1){
            // ENVIA CORREO EN AUTOMATICO
            if(count($data) > 0){
                /* CONTACTOS */ 
                $contactos_para[] = env('CARTA_FIANZA_COMERCIAL_VCTO_PARA'); 
                $contactos_para[] = env('CARTA_FIANZA_COMERCIAL_VCTO_CC_1'); 
                $contactos_para[] = env('CARTA_FIANZA_COMERCIAL_VCTO_CC_2'); 
                $contactos_para[] = env('CARTA_FIANZA_COMERCIAL_VCTO_CC_3'); 
                $contactos_para[] = env('CARTA_FIANZA_COMERCIAL_VCTO_CC_4'); 
                $contactos_para[] = env('CARTA_FIANZA_COMERCIAL_VCTO_CC_5'); 
                $contactos_para[] = env('CARTA_FIANZA_CRONOGRAMA_VCTO_CC_2'); 

                $contactos_cc[] = env('CARTA_FIANZA_CC_TI_1'); // Copia
                $contactos_cc[] = env('CARTA_FIANZA_CC_TI_2'); // Copia
                $contactos_cc[] = env('CARTA_FIANZA_CC_TI_3'); // Copia
                $contactos_cc[] = env('CARTA_FIANZA_CC_TI_4'); // Copia
            }

            try{
                $data_correo = [
                    'data' => $array_renovaciones,
                    'fecha' => $fecha,
                    'mes_desc' => $mes_desc,
                    '$anio_actual' => $anio_actual
                ];
                $contactos_para_INT = env('CARTA_FIANZA_RENUVA_PARA');
                $contactos_cc_INT = env('CARTA_FIANZA_RENUVA_CC');
                $rutaBlade = 'modulo.comercial.procesos.instituciones.alertas.mail_renovacion_automatica';
                $contenidoBlade = View::make($rutaBlade)->with('data', $data_correo)->render();
                $consulta = DB::statement('EXEC [DBO].[ENVIO_MAIL]  ?,?,?,?',[$contactos_para_INT,$contactos_cc_INT,'ALERTA DE RENOVACIÓN AUTOMÁTICA DE CARTAS FIANZAS ['.$mes_desc.' - '.$anio_actual.']',$contenidoBlade]);
            }catch(\Exception $e){
                $o_nres = 0;
                $o_msj = 'Error al enviar correo: '.$e->getMessage() . ', '.$e->getLine() . ', '.$e->getFile();
            }     
                   
        }

        $result['o_nres']  = $o_nres;
        $result['o_msj']  = $o_msj;
        //dd($result);

        return $result;
    }

    public function cuentas_x_cobrar(){
        $model = new Instituciones();
        $sap = new SAP();
        $o_nres = 1;
        $o_msj = 'ok';
        $ccod_cia = '';
        $servidor = '';

        // REGISTRA EN CUENTAS X COBRAR
        /* CXC AC FARMA */
        $fecha_actual = Carbon::now()->format('Ymd');
        $parametros['PI_BUDAT'] = $fecha_actual;

        $get_rfc = $sap->ejecutarRFC('ZACSD_RFC_CARTAFIA_CTAXCOB',$parametros);
        $registros = $get_rfc['PO_CTASXCOBRAR'];
        try {
            Cuentas_x_cobrar::where('cpcchcdcompania', 'ACF')->delete();
            $chunkSize = 50;
            $doc = array();

            foreach($registros as $row){
                $doc[] = array(
                    'cpcchcdcompania' => 'ACF',
                    'cpcchcdcodigocliente' =>$row['KUNNR'],
                    'cpcchdsrazonsocialcliente' => rtrim($row['RAZON_SOCIAL']) ?? null,
                    'cpcchdsfacturasunat' => rtrim($row['XBLNR']) ?? null,
                    'cpcchdsguiaremision' => rtrim($row['XBLNRLIKP']) ?? null,
                    'cpcchcdtipodocumento' => rtrim($row['XBLNR']) ? substr(explode('-',rtrim($row['XBLNR']))[0], 0, 2) : null,
                    'cpcchcdocentidad' => rtrim($row['BSTKD']) ?? null,
                    'cpcdcnmimportefactura' => rtrim($row['DMBTR']) ?? null,
                    'cpcdtfcfechaemisionfactura' => ($row['BLDAT'] !== null && $row['BLDAT'] !== '00000000') ? date('Y-m-d', strtotime($row['BLDAT'])) : null,
                    'cpcdtfcfechainternamientofactura' => ($row['MADAT'] !== null && $row['MADAT'] !== '00000000') ? date('Y-m-d', strtotime($row['MADAT'])) : null,
                    'cpcchdsdocumentorelacionadosunat' => rtrim($row['XBLNRFACREF']) ?? null,
                );
                if(count($doc) > $chunkSize){
                    Cuentas_x_cobrar::insert($doc);
                    $doc = array();
                }
            }
            if(count($doc) > 0){
                Cuentas_x_cobrar::insert($doc);
                $doc = array();
            }
        } catch (\Throwable $th) {
            $o_nres = 0;
            $o_msj = $th->getMessage();
            return $result;
        }

        /* CXC SGT/DIM/CON + Actualiza Monto Pendiente AC FARMA*/
        if($o_nres == 1){
            $info[0] = 35;     
            $rpta = $model->mantenimientoData($info);unset($info);
            $o_nres = $rpta['registro'][0]->o_nres ?? 0;
            $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [35 - CXCDATCORP]';
        }

        /* ACTUALIZA DEUDA -- Hasta el 15 */
        if($o_nres = 1){
            /* EJECUTA SAGITARIO */
            $info[0] = 36;     
            $info[2] = 'SGT';
            $info[3] = '[SERSAG]';
            $rpta = $model->mantenimientoData($info);unset($info);
            $o_nres = $rpta['registro'][0]->o_nres ?? 0;
            $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [36 - CXCDATCORP]';
        }

        if($o_nres = 1){
            /* EJECUTA DIMEXA/CONTINENTAL */
            $info[0] = 36;     
            $info[2] = 'DIM,CON';
            $info[3] = '[SERDIM]';
            $rpta = $model->mantenimientoData($info);unset($info);
            $o_nres = $rpta['registro'][0]->o_nres ?? 0;
            $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [36 - CXCDATCORP]';
        }

        /* ACTUALIZA DEUDA -- Hasta el 92 */
        if($o_nres = 1){
            /* EJECUTA SAGITARIO */
            $info[0] = 37;     
            $info[2] = 'SGT';
            $info[3] = '[SERSAG]';
            $rpta = $model->mantenimientoData($info);unset($info);
            $o_nres = $rpta['registro'][0]->o_nres ?? 0;
            $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [37 - CXCDATCORP]';
        }

        if($o_nres = 1){
            /* EJECUTA DIMEXA/CONTINENTAL */
            $info[0] = 37;     
            $info[2] = 'DIM,CON';
            $info[3] = '[SERDIM]';
            $rpta = $model->mantenimientoData($info);unset($info);
            $o_nres = $rpta['registro'][0]->o_nres ?? 0;
            $o_msj = $rpta['registro'][0]->o_msj ?? 'Error: SP [37 - CXCDATCORP]';
        }

        
        $result['o_nres']  = $o_nres;
        $result['o_msj']  = $o_msj;
        //dd($result);

        return $result;
    }

    public function enviarMensajeWSP($para = '', $contenido = ''){
        $telefono = '';
        $telefono = '+51'.$para;
        sleep(3);

        try{
            /* CONSUMO DE ULTRAMSG */
            $contenido = 'Esto es un mensaje de prueba del Sistema de Cartas Fianzas.';
            $token = 'z43f3hj2xnfcbksq';
            $payload = [
                'token' => $token,
                'to' => $telefono,
                "body" => $contenido
            ];

            $var_post = 'https://api.ultramsg.com/instance90828/messages/chat';

            $envio = Http::post($var_post,$payload)->json();
            //dd($envio);

            $result["res"] = 1;
            $result["msj"] = "Mensaje Enviado";
            return response()->json($result);
        }
        catch (Exception $e){
            $result["res"] = 0;
            $result["msj"] = "Error con API WSP";
            return response()->json($result);
        }
    } 

}