<?php

namespace App\Models\Modulo\Controlcalidad\Procesos;

use Illuminate\Database\Eloquent\Model;
use DB;

class Analisistendencia extends Model {

    public function consultarTablaInicial($xCriterio) {
        try {
            $consulta = DB::select('EXEC [CCA].[usp_inspeccion_material_consultar_inicial] ?,?,?,?,?,?', $xCriterio);
            $rpta['valor'] = 1;
            $rpta['registro'] = $consulta;
        } catch (\Exception $e) {
            $rpta['valor'] = 0;
            $rpta['e'] = $e;
        }
        return $rpta;
    }
    
    public function consultarDetallesInspeccion($xCriterio) {
        try {
            $consulta = DB::select('EXEC [CCA].[usp_inspeccion_material_consultar_detalles] ?,?,?,?', $xCriterio);
            $rpta['valor'] = 1;
            $rpta['registro'] = $consulta;
        } catch (\Exception $e) {
            $rpta['valor'] = 0;
            $rpta['e'] = $e;
        }
        return $rpta;
    }
}