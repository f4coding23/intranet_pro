<?php
namespace App\Models\Modulo\Comercial\Procesos;

use Illuminate\Database\Eloquent\Model;
use DB;
//use Empresa;

class Instituciones extends Model
{
    public function consultarData($xCriterio){		
        try {
            $consulta = DB::select('EXEC [CTF].[usp_instituciones_consultar]  ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?',$this->depurarData($xCriterio,42,true));
            $rpta['valor'] = 1;
            $rpta['registro'] = $consulta;
        } catch (Exception $e) {
            $rpta['valor'] = 0;
            $rpta['e'] = $e;
        }
        return $rpta;
    }

    public function combo_ctf($xCriterio){        
        try {
            $consulta = DB::select('EXEC [CTF].[listado_combo_consultar] ?,?,?,?,?,?,?,?,?,?,?,?',$this->depurarData($xCriterio,12,true));
            $rpta['valor'] = 1;
            $rpta['registro'] = $consulta;
        } catch (Exception $e) {
            $rpta['valor'] = 0;
            $rpta['e'] = $e;
        }
        return $rpta;
    }  

    public function mantenimientoData($xCriterio){		
        try {
            $consulta = DB::select('EXEC [CTF].[usp_instituciones_mantenimiento]  ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?',$this->depurarData($xCriterio,22,true));
            $rpta['valor'] = 1;
            $rpta['registro'] = $consulta;
        } catch (Exception $e) {
            $rpta['valor'] = 0;
            $rpta['e'] = $e;
        }
        return $rpta;
    }

    private function depurarData($info,$nro, $conidempresa = false){
        $newData = array();
        for ($i=0; $i <= $nro; $i++) { 
            if ($conidempresa){
                if ($i == 0){
                    $newData[0] = isset($info[$i]) ? $info[$i] : '0';   // Opcion
                    //$newData[1] = Empresa::idempresa();                 // idempresa
                    $newData[1] = 1;                 
                }else{
                    $newData[$i + 1] = isset($info[$i]) ? $info[$i] : '';
                }
            }else{
                $newData[$i] = isset($info[$i]) ? $info[$i] : '';
            }
        }
        return $newData;
    }

    public function limpiarTemp($fechadesde, $fechafin, $tabla){
        $result = [];

        $info[0] = 1;
        $info[1] = $fechadesde;
        $info[2] = $fechafin;

        if($tabla == 'PRC'){
            $info[3] = 1;
        }
        if($tabla == 'ENT'){
            $info[3] = 2;
        }
        if($tabla == 'PED'){
            $info[3] = 3;
        }
        if($tabla == 'PF'){
            $info[3] = 4;
        }
        if($tabla == 'PRC_WEB'){
            $info[3] = 5;
        }

        $rpta = $this->mantenimientoData($info);unset($info);
        $result['o_nres'] = $rpta['registro'][0]->o_nres ?? 0;
        $result['o_msj'] = $rpta['registro'][0]->o_msj ?? 'Error: SP [LIMPIEZA]';
        //dd($result);
        
        return $result;
    }  

    public function registrarFINAL($fechadesde, $fechafin,$opcion){
        $info[0] = $opcion;
        $info[1] = $fechadesde;
        $info[2] = $fechafin;
        
        $rpta   = $this->mantenimientoData($info);unset($info);
        $result['o_nres'] = $rpta['registro'][0]->o_nres ?? 0;
        $result['o_msj'] = $rpta['registro'][0]->o_msj ?? 'Error: SP [REGISTRO-PROCESO]';

        return $result;
    }

    public function consultarSeguimientoIntegral($parametros){
        try {
            // Ahora incluimos los nuevos parámetros (15 parámetros en total)
            $consulta = DB::select('EXEC [CTF].[usp_seguimiento_integral_consultar] ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?', $parametros);
            return [
                'valor' => 1,
                'registro' => $consulta
            ];
        } catch (Exception $e) {
            \Log::error('Error en SP: ' . $e->getMessage());
            return [
                'valor' => 0,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    public function consultarAdjuntosOC($parametros) {
        try {
            // Intentar ejecutar el procedimiento almacenado
            $consulta = DB::select('EXEC [CTF].[usp_adjuntos_oc_gestionar] ?,?,?,?,?,?,?,?,?,?,?,?', [
                $parametros[0], // accion
                isset($parametros[1]) ? $parametros[1] : null, // idadjuntooc
                isset($parametros[2]) ? $parametros[2] : null, // idcontrato
                isset($parametros[3]) ? $parametros[3] : null, // idpedido
                isset($parametros[4]) ? $parametros[4] : null, // idestadocontrato
                isset($parametros[5]) ? $parametros[5] : null, // idtipoadjunto
                isset($parametros[6]) ? $parametros[6] : null, // nombreadjunto
                isset($parametros[7]) ? $parametros[7] : null, // ruta
                isset($parametros[8]) ? $parametros[8] : null, // extension
                isset($parametros[9]) ? $parametros[9] : null, // tamanioadjunto
                isset($parametros[10]) ? $parametros[10] : null, // idusuario
                isset($parametros[11]) ? $parametros[11] : null, // ipmaquina
            ]);
            
            // Si es una operación de guardar (acción 2) y no hay resultados, 
            // considera que fue exitoso de todos modos
            if ($parametros[0] == 2 && empty($consulta)) {
                // Crear un resultado ficticio para mantener la estructura esperada
                return [
                    'valor' => 1,
                    'registro' => [['id' => 0]]
                ];
            }
            
            return [
                'valor' => 1,
                'registro' => $consulta
            ];
        } catch (Exception $e) {
            \Log::error('Error en SP adjuntos OC: ' . $e->getMessage());
            return [
                'valor' => 0,
                'mensaje' => $e->getMessage()
            ];
        }
    }

}