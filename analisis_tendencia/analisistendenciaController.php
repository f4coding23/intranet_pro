<?php

namespace App\Http\Controllers\Modulo\Controlcalidad\Procesos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Home;
use App\Models\Modulo\Controlcalidad\Procesos\Analisistendencia;
use App\FormatoEmpresa;
use Route;
use Illuminate\Support\Facades\Session;

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
            
            $data['operaciones'] = $operaciones_new;
            $data['rol'] = $operaciones['rol'];
            $data['usuarioid'] = $usuarioid;
            $data['estadosCodigo'] = $estadosCodigo;
            $data['estadosFiltros'] = $estadosFiltros;
            $data['opcEsp'] = $opcEsp;
            $data['ordenIns'] = $ordenIns;
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
                // Consulta de la tabla principal agrupada
                $info[0] = 1;
                $info[1] = is_null($request->input('producto')) ? '' : trim($request->input('producto'));
                $info[2] = is_null($request->input('fechadesde')) ? '' : trim($request->input('fechadesde'));
                $info[3] = is_null($request->input('fechafin')) ? '' : trim($request->input('fechafin'));
                $info[4] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                $info[5] = is_null($request->input('rows')) ? '50' : trim($request->input('rows'));

                $data = $model->consultarTablaInicial($info);
                
                $result["total"] = isset($data['registro'][0]->total) ? $data['registro'][0]->total : 0;
                $result["rows"] = $data['registro'];

                return response()->json($result);
                break;

                case 'listarDetalles':
                    // Consulta de los detalles para un lote específico
                    $info[0] = 2;
                    $info[1] = is_null($request->input('lote_inspeccion')) ? '' : trim($request->input('lote_inspeccion'));
                    $info[2] = is_null($request->input('page')) ? '1' : trim($request->input('page'));
                    $info[3] = is_null($request->input('rows')) ? '50' : trim($request->input('rows'));
                
                    $data = $model->consultarDetallesInspeccion($info);
                    
                    // Debug para encontrar problemas
                    \Log::info('Detalles para lote: ' . $info[1], ['resultados' => count($data['registro'] ?? [])]);
                    
                    $result["total"] = isset($data['registro'][0]->total) ? $data['registro'][0]->total : 0;
                    $result["rows"] = $data['registro'] ?? [];
                
                    return response()->json($result);
                    break;
                
            default:
                break;
        }
    }
}