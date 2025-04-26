<?php

namespace App\Models\Modulo\Controlcalidad\Procesos;

use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Support\Facades\Log; 

class Analisistendencia extends Model {

    public function consultarTablaInicial($xCriterio) {
        try {
            $consulta = DB::select('EXEC [CCA].[usp_inspeccion_material_consultar_inicial] ?,?,?,?,?,?', $xCriterio);
            
            $rpta['valor'] = 1;
            $rpta['registro'] = $consulta;
        } catch (\Exception $e) {
            $rpta['valor'] = 0;
            $rpta['mensaje'] = $e->getMessage();
            $rpta['linea'] = $e->getLine();
            $rpta['archivo'] = $e->getFile();
        }
        return $rpta;
    }
    
    public function consultarDetallesInspeccion($xCriterio) {
        dd($xCriterio);
        try {
            $consulta = DB::select('EXEC [CCA].[usp_inspeccion_material_consultar_detalles] ?,?,?,?,?,?', $xCriterio);
            dd($consulta);
            $rpta['valor'] = 1;
            $rpta['registro'] = $consulta;
        } catch (\Exception $e) {
            $rpta['valor'] = 0;
            $rpta['mensaje'] = $e->getMessage();
            $rpta['linea'] = $e->getLine();
            $rpta['archivo'] = $e->getFile();
        }
        return $rpta;
    }
    
    // Método para obtener productos para el combo
    public function consultarProductos() {
        try {
            $consulta = DB::select('SELECT DISTINCT cod_producto_mae AS value, nom_producto_mae AS text FROM CCA.mae_productos ORDER BY nom_producto_mae');
            
            $rpta['valor'] = 1;
            $rpta['registro'] = $consulta;
        } catch (\Exception $e) {
            $rpta['valor'] = 0;
            $rpta['mensaje'] = $e->getMessage();
        }
        return $rpta;
    }

    // Actualización del método consultarLotesAprobados en Analisistendencia.php
    public function consultarLotesAprobados($info)
    {
        try {
            if (empty($info[1])) throw new \Exception('Código de producto no proporcionado');
            if (empty($info[2])) throw new \Exception('Código de inspección no proporcionado');
    
            $cod_producto_mae = $info[1] ?? '';
            $cod_insp_mae = $info[2] ?? '';
            $fecha_inicio = $info[3] ?? null;
            $fecha_fin = $info[4] ?? null;
            $pagina = intval($info[5] ?? 1);
            $registros_por_pagina = intval($info[6] ?? 10);
    
            // Asegurarse de que la página y registros_por_pagina sean números enteros positivos
            if ($pagina < 1) $pagina = 1;
            if ($registros_por_pagina < 1) $registros_por_pagina = 10;
    
            // PASO 1: Encontrar todos los lotes únicos que cumplen los criterios
            $subConsulta = DB::table('CCA.mae_inspecciones AS mi')
                ->join('CCA.mae_productos AS mp', 'mi.cod_producto', '=', 'mp.cod_producto')
                ->join('CCA.mae_tipo_inspecciones AS mti', 'mi.cod_insp', '=', 'mti.cod_insp')
                ->select('mi.num_lote')
                ->distinct()
                ->where('mp.cod_producto_mae', $cod_producto_mae)
                ->where('mti.cod_insp_mae', $cod_insp_mae)
                ->where('mi.resultado', '>', '0.00')
                ->where('mi.valoracion', 'A')
                ->when($fecha_inicio, function ($query, $fecha_inicio) {
                    return $query->where('mi.fec_ini_insp', '>=', $fecha_inicio);
                })
                ->when($fecha_fin, function ($query, $fecha_fin) {
                    return $query->where('mi.fec_ini_insp', '<=', $fecha_fin);
                });
    
            // Obtener los lotes únicos como array para contarlos correctamente
            $todosLotesUnicos = $subConsulta->get()->pluck('num_lote')->toArray();
            $total = count($todosLotesUnicos);
    
            // PASO 2: Obtener los lotes únicos para la página actual
            $lotes_paginados = array_slice(
                $todosLotesUnicos, 
                ($pagina - 1) * $registros_por_pagina, 
                $registros_por_pagina
            );
    
            // PASO 3: Recuperar información completa solo para los lotes de esta página
            $registros = [];
            if (!empty($lotes_paginados)) {
                $registros = DB::table('CCA.mae_inspecciones AS mi')
                    ->join('CCA.mae_productos AS mp', 'mi.cod_producto', '=', 'mp.cod_producto')
                    ->join('CCA.mae_tipo_inspecciones AS mti', 'mi.cod_insp', '=', 'mti.cod_insp')
                    ->selectRaw("
                        mti.cod_insp_mae,
                        mti.nom_insp_mae,
                        mi.cod_producto,
                        mi.num_lote,
                        mi.lote_inspeccion,
                        CONVERT(VARCHAR(10), mi.fec_ini_insp, 120) AS fec_ini_insp,
                        CONVERT(VARCHAR(10), mi.fec_fin_insp, 120) AS fec_fin_insp,
                        CONVERT(VARCHAR(10), mi.fec_ven_lote, 120) AS fec_ven_lote,
                        mi.valoracion,
                        mi.resultado,
                        mi.media,
                        mi.texto_breve
                    ")
                    ->where('mp.cod_producto_mae', $cod_producto_mae)
                    ->where('mti.cod_insp_mae', $cod_insp_mae)
                    ->where('mi.resultado', '>', '0.00')
                    ->where('mi.valoracion', 'A')
                    ->whereIn('mi.num_lote', $lotes_paginados)
                    ->orderBy('mi.num_lote')
                    ->orderBy('mi.fec_ini_insp')
                    ->get()
                    ->toArray();
                
                // Agrupamos por lote y tomamos el primero de cada grupo
                $registros_agrupados = collect($registros)
                    ->groupBy('num_lote')
                    ->map(function ($grupo) use ($total) {
                        $registro = $grupo->first();
                        $registro->total_registros = $total;
                        return $registro;
                    })
                    ->values()
                    ->toArray();
                
                $registros = $registros_agrupados;
            }
    
            Log::channel('daily')->info('Parámetros de Lotes Aprobados', [
                'cod_producto_mae' => $cod_producto_mae,
                'cod_insp_mae' => $cod_insp_mae,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'pagina' => $pagina,
                'registros_por_pagina' => $registros_por_pagina,
                'total_lotes_unicos' => $total,
                'lotes_paginados' => $lotes_paginados,
                'registros_obtenidos' => count($registros)
            ]);
    
            return [
                'valor' => count($registros) ? 1 : 0,
                'mensaje' => count($registros) ? 'Consulta exitosa' : 'No se encontraron registros',
                'registro' => $registros,
                'total' => $total
            ];
        } catch (\Exception $e) {
            Log::channel('daily')->error('Error en consultarLotesAprobados (Query Builder)', [
                'mensaje' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'info' => $info
            ]);
    
            return [
                'valor' => 0,
                'mensaje' => 'Error en la consulta: ' . $e->getMessage(),
                'registro' => [],
                'total' => 0
            ];
        }
    }

    public function obtenerInformacionDetalle($cod_producto_mae, $cod_insp_mae) {
        try {
            // Consulta para obtener nombre del producto y nombre de la inspección
            $consulta = DB::select("
                SELECT 
                    p.nom_producto_mae, 
                    ti.nom_insp_mae
                FROM 
                    CCA.mae_productos p,
                    CCA.mae_tipo_inspecciones ti
                WHERE 
                    p.cod_producto_mae = :cod_producto_mae AND
                    ti.cod_insp_mae = :cod_insp_mae
            ", [
                'cod_producto_mae' => $cod_producto_mae,
                'cod_insp_mae' => $cod_insp_mae
            ]);
            
            $rpta['valor'] = 1;
            $rpta['registro'] = $consulta;
        } catch (\Exception $e) {
            $rpta['valor'] = 0;
            $rpta['mensaje'] = $e->getMessage();
            $rpta['linea'] = $e->getLine();
            $rpta['archivo'] = $e->getFile();
        }
        return $rpta;
    }

    // Actualización del método consultarLotesDesaprobados
    public function consultarLotesDesaprobados($info)
    {
        try {
            if (empty($info[1])) throw new \Exception('Código de producto no proporcionado');
            if (empty($info[2])) throw new \Exception('Código de inspección no proporcionado');

            $cod_producto_mae = $info[1] ?? '';
            $cod_insp_mae = $info[2] ?? '';
            $fecha_inicio = $info[3] ?? null;
            $fecha_fin = $info[4] ?? null;
            $pagina = intval($info[5] ?? 1);
            $registros_por_pagina = intval($info[6] ?? 10);

            // Asegurarse de que la página y registros_por_pagina sean números enteros positivos
            if ($pagina < 1) $pagina = 1;
            if ($registros_por_pagina < 1) $registros_por_pagina = 10;

            // PASO 1: Encontrar todos los lotes únicos que cumplen los criterios
            $subConsulta = DB::table('CCA.mae_inspecciones AS mi')
                ->join('CCA.mae_productos AS mp', 'mi.cod_producto', '=', 'mp.cod_producto')
                ->join('CCA.mae_tipo_inspecciones AS mti', 'mi.cod_insp', '=', 'mti.cod_insp')
                ->select('mi.num_lote')
                ->distinct()
                ->where('mp.cod_producto_mae', $cod_producto_mae)
                ->where('mti.cod_insp_mae', $cod_insp_mae)
                ->where('mi.resultado', '>', '0.00')
                ->where('mi.valoracion', 'R')  // CAMBIO DE 'A' A 'R' para desaprobados
                ->when($fecha_inicio, function ($query, $fecha_inicio) {
                    return $query->where('mi.fec_ini_insp', '>=', $fecha_inicio);
                })
                ->when($fecha_fin, function ($query, $fecha_fin) {
                    return $query->where('mi.fec_ini_insp', '<=', $fecha_fin);
                });

            // Obtener los lotes únicos como array para contarlos correctamente
            $todosLotesUnicos = $subConsulta->get()->pluck('num_lote')->toArray();
            $total = count($todosLotesUnicos);

            // PASO 2: Obtener los lotes únicos para la página actual
            $lotes_paginados = array_slice(
                $todosLotesUnicos, 
                ($pagina - 1) * $registros_por_pagina, 
                $registros_por_pagina
            );

            // PASO 3: Recuperar información completa solo para los lotes de esta página
            $registros = [];
            if (!empty($lotes_paginados)) {
                $registros = DB::table('CCA.mae_inspecciones AS mi')
                    ->join('CCA.mae_productos AS mp', 'mi.cod_producto', '=', 'mp.cod_producto')
                    ->join('CCA.mae_tipo_inspecciones AS mti', 'mi.cod_insp', '=', 'mti.cod_insp')
                    ->selectRaw("
                        mti.cod_insp_mae,
                        mti.nom_insp_mae,
                        mi.cod_producto,
                        mi.num_lote,
                        mi.lote_inspeccion,
                        CONVERT(VARCHAR(10), mi.fec_ini_insp, 120) AS fec_ini_insp,
                        CONVERT(VARCHAR(10), mi.fec_fin_insp, 120) AS fec_fin_insp,
                        CONVERT(VARCHAR(10), mi.fec_ven_lote, 120) AS fec_ven_lote,
                        mi.valoracion,
                        mi.resultado,
                        mi.media,
                        mi.texto_breve
                    ")
                    ->where('mp.cod_producto_mae', $cod_producto_mae)
                    ->where('mti.cod_insp_mae', $cod_insp_mae)
                    ->where('mi.resultado', '>', '0.00')
                    ->where('mi.valoracion', 'R')  // CAMBIO DE 'A' A 'R' para desaprobados
                    ->whereIn('mi.num_lote', $lotes_paginados)
                    ->orderBy('mi.num_lote')
                    ->orderBy('mi.fec_ini_insp')
                    ->get()
                    ->toArray();
                
                // Agrupamos por lote y tomamos el primero de cada grupo
                $registros_agrupados = collect($registros)
                    ->groupBy('num_lote')
                    ->map(function ($grupo) use ($total) {
                        $registro = $grupo->first();
                        $registro->total_registros = $total;
                        return $registro;
                    })
                    ->values()
                    ->toArray();
                
                $registros = $registros_agrupados;
            }

            Log::channel('daily')->info('Parámetros de Lotes Desaprobados', [
                'cod_producto_mae' => $cod_producto_mae,
                'cod_insp_mae' => $cod_insp_mae,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'pagina' => $pagina,
                'registros_por_pagina' => $registros_por_pagina,
                'total_lotes_unicos' => $total,
                'lotes_paginados' => $lotes_paginados,
                'registros_obtenidos' => count($registros)
            ]);

            return [
                'valor' => count($registros) ? 1 : 0,
                'mensaje' => count($registros) ? 'Consulta exitosa' : 'No se encontraron registros',
                'registro' => $registros,
                'total' => $total
            ];
        } catch (\Exception $e) {
            Log::channel('daily')->error('Error en consultarLotesDesaprobados (Query Builder)', [
                'mensaje' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'info' => $info
            ]);

            return [
                'valor' => 0,
                'mensaje' => 'Error en la consulta: ' . $e->getMessage(),
                'registro' => [],
                'total' => 0
            ];
        }
    }

    public function obtenerDatosGrafico($cod_producto_mae, $cod_insp, $fecha_inicio = null, $fecha_fin = null)
    {
        return DB::select("
            DECLARE @fecha_inicio DATE = :fecha_inicio;
            DECLARE @fecha_fin DATE = :fecha_fin;

            WITH ProductosFiltrados AS (
                SELECT mp.cod_producto
                FROM CCA.mae_productos mp
                WHERE mp.cod_producto_mae = :cod_producto_mae
            ),
            TipoInspeccionesFiltradas AS (
                SELECT mti.cod_insp
                FROM CCA.mae_tipo_inspecciones mti
                WHERE mti.cod_insp_mae = :cod_insp
            ),
            InspeccionesFiltradas AS (
                SELECT 
                    mi.*,
                    ROW_NUMBER() OVER (PARTITION BY mi.num_lote ORDER BY mi.fec_ini_insp ASC) AS rn
                FROM CCA.mae_inspecciones mi
                WHERE mi.cod_producto IN (SELECT cod_producto FROM ProductosFiltrados)
                  AND mi.cod_insp IN (SELECT cod_insp FROM TipoInspeccionesFiltradas)
                  AND mi.resultado > 0.00
                  AND (
                      @fecha_inicio IS NULL OR mi.fec_ini_insp >= @fecha_inicio
                  )
                  AND (
                      @fecha_fin IS NULL OR mi.fec_ini_insp <= @fecha_fin
                  )
            )
            SELECT 
                num_lote,
                cod_producto,
                cod_insp,
                fec_ini_insp,
                valoracion,
                resultado
            FROM InspeccionesFiltradas
            WHERE rn = 1
            ORDER BY fec_ini_insp ASC;
        ", [
            'cod_producto_mae' => $cod_producto_mae,
            'cod_insp' => $cod_insp,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin
        ]);
    }
}