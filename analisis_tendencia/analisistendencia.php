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
        try {
            $consulta = DB::select('EXEC [CCA].[usp_inspeccion_material_consultar_detalles] ?,?,?,?,?,?', $xCriterio);
            
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
        $pagina = $info[5] ?? 1;
        $registros_por_pagina = $info[6] ?? 10;

        $offset = ($pagina - 1) * $registros_por_pagina;

        // Construir la base de la consulta para reutilizarla
        $baseQuery = DB::table('CCA.mae_inspecciones AS mi')
            ->join('CCA.mae_productos AS mp', 'mi.cod_producto', '=', 'mp.cod_producto')
            ->join('CCA.mae_tipo_inspecciones AS mti', 'mi.cod_insp', '=', 'mti.cod_insp')
            ->where('mp.cod_producto_mae', $cod_producto_mae)
            ->where('mti.cod_insp_mae', $cod_insp_mae)
            ->where('mi.valoracion', 'A')
            ->when($fecha_inicio, function ($query, $fecha_inicio) {
                return $query->where('mi.fec_ini_insp', '>=', $fecha_inicio);
            })
            ->when($fecha_fin, function ($query, $fecha_fin) {
                return $query->where('mi.fec_ini_insp', '<=', $fecha_fin);
            });
        
        // Primero obtenemos los lotes únicos para el conteo correcto
        $lotes_unicos = (clone $baseQuery)
            ->select('mi.num_lote')
            ->distinct()
            ->get()
            ->pluck('num_lote')
            ->toArray();
        
        $total = count($lotes_unicos);
        
        // Ahora construimos la consulta principal con todos los campos requeridos
        $registros = (clone $baseQuery)
            ->selectRaw("
                mti.cod_insp_mae,
                mti.nom_insp_mae,
                mi.cod_producto,
                mi.num_lote,
                CONVERT(VARCHAR(10), mi.fec_ini_insp, 120) AS fec_ini_insp,
                CONVERT(VARCHAR(10), mi.fec_fin_insp, 120) AS fec_fin_insp,
                CONVERT(VARCHAR(10), mi.fec_ven_lote, 120) AS fec_ven_lote,
                mi.valoracion,
                mi.resultado,
                mi.media,
                mi.texto_breve
            ")
            ->orderBy('mi.num_lote')
            ->orderBy('mi.fec_ini_insp')
            ->get()
            ->groupBy('num_lote') // Agrupar por número de lote
            ->map(function ($grupo) {
                // Tomar solo el primer registro de cada grupo (lote)
                return $grupo->first();
            })
            ->values() // Convertir a array indexado
            ->slice($offset, $registros_por_pagina) // Aplicar paginación manualmente
            ->toArray();
        
        // Añadir total_registros a cada registro
        foreach ($registros as $registro) {
            $registro->total_registros = $total;
        }

        Log::channel('daily')->info('Parámetros de Lotes Aprobados', [
            'cod_producto_mae' => $cod_producto_mae,
            'cod_insp_mae' => $cod_insp_mae,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'pagina' => $pagina,
            'registros_por_pagina' => $registros_por_pagina,
            'offset' => $offset,
            'lotes_unicos' => $total
        ]);
        
        Log::channel('daily')->info('Registros filtrados', [
            'total_registros' => $total,
            'registros_paginados' => count($registros)
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
            'trace' => $e->getTraceAsString()
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
public function consultarLotesDesaprobados($info) {
    try {
        // Inicializar variables de retorno
        $resultado = [
            'valor' => 0,
            'mensaje' => '',
            'registro' => []
        ];

        // Extraer parámetros
        $cod_producto_mae = $info[1] ?? '';
        $cod_insp_mae = $info[2] ?? '';
        $fecha_inicio = $info[3] ?? null;
        $fecha_fin = $info[4] ?? null;
        $pagina = $info[5] ?? 1;
        $registros_por_pagina = $info[6] ?? 10;

        // Calcular offset para paginación
        $offset = ($pagina - 1) * $registros_por_pagina;

        // Consulta SQL base
        $query = "
        WITH ProductosFiltrados AS (
            SELECT mp.cod_producto
            FROM CCA.mae_productos mp
            WHERE mp.cod_producto_mae = :cod_producto_mae
        ),
        TipoInspeccionesFiltradas AS (
            SELECT
                mti.cod_insp,
                mti.cod_insp_mae,
                mti.nom_insp_mae
            FROM CCA.mae_tipo_inspecciones mti
            WHERE mti.cod_insp_mae = :cod_insp_mae
        ),
        InspeccionesFiltradas AS (
            SELECT
                mi.cod_producto,
                mi.cod_insp,
                mi.num_lote,
                mi.fec_ini_insp,
                mi.fec_fin_insp,
                mi.fec_ven_lote,
                mi.valoracion,
                mi.resultado,
                mi.media,
                mi.texto_breve,
                ROW_NUMBER() OVER (PARTITION BY mi.num_lote ORDER BY mi.fec_ini_insp ASC) AS rn,
                COUNT(*) OVER () AS total_registros
            FROM CCA.mae_inspecciones mi
            WHERE mi.cod_producto IN (SELECT cod_producto FROM ProductosFiltrados)
              AND mi.valoracion = 'R'
              AND (:fecha_inicio IS NULL OR mi.fec_ini_insp >= :fecha_inicio)
              AND (:fecha_fin IS NULL OR mi.fec_ini_insp <= :fecha_fin)
        )
        SELECT 
            tif.cod_insp_mae,
            tif.nom_insp_mae,
            inf.cod_producto,
            inf.num_lote,
            inf.fec_ini_insp,
            inf.fec_fin_insp,
            inf.fec_ven_lote,
            inf.valoracion,
            inf.resultado,
            inf.media,
            inf.texto_breve,
            inf.total_registros
        FROM InspeccionesFiltradas inf
        INNER JOIN TipoInspeccionesFiltradas tif ON inf.cod_insp = tif.cod_insp
        WHERE inf.rn = 1
        ORDER BY inf.fec_ini_insp DESC, inf.num_lote
        OFFSET :offset ROWS 
        FETCH NEXT :registros_por_pagina ROWS ONLY
        ";

        // Ejecutar consulta
        $registros = DB::select($query, [
            'cod_producto_mae' => $cod_producto_mae,
            'cod_insp_mae' => $cod_insp_mae,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'offset' => $offset,
            'registros_por_pagina' => $registros_por_pagina
        ]);

        // Verificar si hay registros
        if (!empty($registros)) {
            $resultado['valor'] = 1;
            $resultado['mensaje'] = 'Consulta exitosa';
            $resultado['registro'] = $registros;
        } else {
            $resultado['valor'] = 0;
            $resultado['mensaje'] = 'No se encontraron registros';
        }

        return $resultado;

    } catch (\Exception $e) {
        // Manejar cualquier error
        Log::error('Error en consultarLotesDesaprobados: ' . $e->getMessage());
        return [
            'valor' => 0,
            'mensaje' => 'Error en la consulta: ' . $e->getMessage(),
            'registro' => []
        ];
    }
}
}