<?php
class VacacionespecialModel extends ModelBase
{
    private $idVacacionEspecial;
    public function __construct()
    {
        parent::__construct(__CLASS__);
    }

    public function getVacacionesEspeciales($pgStart, $pgSize, $pgSort, $qryEmpresa, $qryGerencia, $qryDepartamento, $qryArea, $qrySeccion, $qryFechaInicio, $qryFechaFin, $qryColaborador)
    {
        $infoGenerador = $this->sessionObj->getUserInfo();
        // $idGenerador = $infoGenerador[0]->ID_USUARIO;
        $this->intra_db->usarUTF8();
        $this->intra_db->setCampos("
        A.id_vaca_especial,
        A.id_empresa,
        A.empresa,
        A.id_unidad,
        A.gerencia,
        A.id_departamento,
        A.departamento,
        A.id_area,
        A.area,
        A.id_seccion,
        A.seccion,
        A.id_solicitante,
        A.solicitante,
        A.id_generador,
        A.generador,
        A.fecha_crea,
        A.fecha_inicio,
        A.fecha_fin,
        A.num_dias	
        ");
        $this->intra_db->setTabla('VW_VACACIONES_ESPECIALES AS A');

        if ($qryEmpresa) {
            $this->intra_db->setCondicion("=", "A.id_empresa", "$qryEmpresa");
        }

        if ($qryGerencia) {
            if (is_array($qryGerencia)) {
                $gerencias = implode("','", $qryGerencia);
                $this->intra_db->setCondicionString(" A.id_unidad IN ('" . $gerencias . "')");
            } else {
                $this->intra_db->setCondicion("=", "A.id_unidad", "$qryGerencia");
            }
        }

        if ($qryDepartamento) {
            $this->intra_db->setCondicion("=", "A.id_departamento", "$qryDepartamento");
        }

        if ($qryArea) {
            $this->intra_db->setCondicion("=", "A.id_area", "$qryArea");
        }

        if ($qrySeccion) {
            $this->intra_db->setCondicion("=", "A.id_seccion", "$qrySeccion");
        }

        if (!empty($qryColaborador)) {
            $this->intra_db->setCondicionString("A.solicitante LIKE '%{$qryColaborador}%'");
        }

        if ($qryFechaInicio && $qryFechaFin) {
            $this->intra_db->setCondicionString(" fecha_inicio BETWEEN CONVERT(DATE,'$qryFechaInicio',103) AND CONVERT(DATE,'$qryFechaFin',103)");
        } else {
            if ($qryFechaInicio && !$qryFechaFin) {
                $this->intra_db->setCondicionString(" fecha_inicio >= CONVERT(DATE,'$qryFechaInicio',103)");
            } elseif (!$qryFechaInicio && $qryFechaFin) {
                $this->intra_db->setCondicionString(" fecha_inicio <= CONVERT(DATE,'$qryFechaFin',103)");
            }
        }

        $this->intra_db->setOrden($pgSort);
        $this->intra_db->setLimit($pgStart, $pgSize);
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }
    public function getInfoVacacionEspecial($idVacacionEspecial)
    {
        $this->intra_db->setCampos('*');
        $this->intra_db->setTabla("VW_VACACIONES_ESPECIALES");
        $this->intra_db->setCondicion("=", "id_vaca_especial", $idVacacionEspecial);
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }
    public function crearVacacionEspecial($reg, $mod)
    {
        $resultado = ['status' => false, 'mensaje' => ''];

        try {
            // Iniciar la conexión y la transacción
            $this->getInstaceTransac();
            $this->intra_trans->Conectar(true);
            $this->intra_trans->iniciarTransaccion();

            $id_vaca = 0; // Corregido: agregado el punto y coma
            $id_vaca = $this->_insertarSolicitudVacacionEspecial($reg,$mod);
            
            $this->intra_trans->commitTransaccion();
            $this->intra_trans->Desconectar();

            // Si es una creación desde el master, notificar a responsables
            if (isset($reg['master']) && $reg['master']) {
                // $regVaca = $this->getInfoVacacionEspecial($id_vaca);
                // $this->_proccessMailMaster($regVaca);
            }

            $resultado['status'] = true;
            $resultado['id'] = $id_vaca;
        } catch (Exception $e) {
            echo "Error en crearVacacionEspecial: " . $e->getMessage() . "<br>"; // Depuración
            // $this->intra_trans->rollbackTransaccion();
            $resultado['mensaje'] = 'Error: ' . $e->getMessage();
        }

        return $resultado;
    }

    private function _insertarSolicitudVacacionEspecial($reg, $modalidad = 1)
    {
        try {
            // Validar los campos obligatorios para todas las modalidades
            if (empty($reg['fecha_inicio']) || empty($reg['fecha_fin'])) {
                throw new Exception('Las fechas de inicio y fin son obligatorias para todos los tipos de solicitud.');
            }

            // Obtener información del usuario generador (usuario actual de la sesión)
            $userInfo = $this->sessionObj->getUserInfo();
            if (empty($userInfo)) {
                throw new Exception('No se encontró información del usuario generador.');
            }
            $idGenerador = $userInfo[0]->ID_USUARIO;
            $nombreGenerador = $userInfo[0]->NOM_USUARIO;

            // Obtener información del generador desde Ofisis
            $userInfoOfisis = $this->sessionObj->getInfoFromOfisis();
            if (empty($userInfoOfisis)) {
                throw new Exception('No se pudo obtener información desde Ofisis.');
            }

            // Obtener información de la sucursal del usuario actual
            $infoSucursal = $this->sessionObj->getInfoFromSucursalByEmpresa($userInfoOfisis[0]->CO_EMPR, 1);
            if (empty($infoSucursal)) {
                throw new Exception('No se encontró información de la sucursal del usuario actual.');
            }

            // ID de sucursal siempre del usuario actual
            $idSucursal = "'" . $infoSucursal[0]->SUCINIDSUCURSAL . "'";

            // Validar y formatear las fechas
            $fechaInicio = DateTime::createFromFormat('d/m/Y', $reg['fecha_inicio']);
            $fechaFin = DateTime::createFromFormat('d/m/Y', $reg['fecha_fin']);
            if (!$fechaInicio || !$fechaFin) {
                throw new Exception('Formato de fecha inválido. Use el formato d/m/Y.');
            }

            // Calcular la diferencia en días
            $diferencia = $fechaInicio->diff($fechaFin);
            $cantidadDias = $diferencia->days + 1; // Sumamos 1 para incluir el día final

            // Definir valores predeterminados para los campos
            $idSolicitante = "NULL";
            $nombreSolicitante = "NULL";
            $idEmpresa = "'" . $userInfoOfisis[0]->CO_EMPR . "'"; // Por defecto, empresa del usuario actual
            $idUnidad = "NULL";
            $idDepartamento = "NULL";
            $idArea = "NULL";
            $idSeccion = "NULL";

            // Switch para manejar las diferentes modalidades
            switch ($modalidad) {
                case 1: // Usuario específico
                    if (empty($reg['id_solicitante'])) {
                        throw new Exception('El ID del solicitante es obligatorio para vacaciones individuales.');
                    }

                    // Obtener información del solicitante seleccionado
                    $solicitanteInfo = $this->sessionObj->getUserInfo($reg['id_solicitante']);
                    if (empty($solicitanteInfo)) {
                        throw new Exception('No se encontró información del usuario solicitante.');
                    }

                    // Obtener información del solicitante desde Ofisis usando el USUARIO del solicitanteInfo
                    $solicitanteInfoOfisis = $this->sessionObj->getInfoFromOfisis($solicitanteInfo[0]->USUARIO);
                    if (empty($solicitanteInfoOfisis)) {
                        throw new Exception('No se pudo obtener información desde Ofisis para el solicitante.');
                    }

                    // Asignar valores del solicitante seleccionado
                    $idSolicitante = $reg['id_solicitante'];
                    $nombreSolicitante = "'" . $solicitanteInfo[0]->NOM_USUARIO . "'";
                    $idEmpresa = "'" . $solicitanteInfoOfisis[0]->CO_EMPR . "'"; // Empresa del solicitante
                    $idUnidad = "'" . $solicitanteInfoOfisis[0]->CO_UNID . "'";
                    $idDepartamento = "'" . $solicitanteInfoOfisis[0]->CO_DEPA . "'";
                    $idArea = "'" . $solicitanteInfoOfisis[0]->CO_AREA . "'";
                    $idSeccion = "'" . $solicitanteInfoOfisis[0]->CO_SEC . "'";
                    break;

                case 2: // Sección
                    if (empty($reg['id_seccion'])) {
                        throw new Exception('El ID de la sección es obligatorio para esta modalidad.');
                    }

                    // Usar los valores proporcionados en $reg para los campos específicos
                    $idEmpresa = !empty($reg['id_empresa']) ? "'" . $reg['id_empresa'] . "'" : $idEmpresa;
                    $idUnidad = !empty($reg['id_unidad']) ? "'" . $reg['id_unidad'] . "'" : "NULL";
                    $idDepartamento = !empty($reg['id_departamento']) ? "'" . $reg['id_departamento'] . "'" : "NULL";
                    $idArea = !empty($reg['id_area']) ? "'" . $reg['id_area'] . "'" : "NULL";
                    $idSeccion = "'" . $reg['id_seccion'] . "'";
                    break;

                case 3: // Área
                    if (empty($reg['id_area'])) {
                        throw new Exception('El ID del área es obligatorio para esta modalidad.');
                    }

                    // Usar los valores proporcionados en $reg
                    $idEmpresa = !empty($reg['id_empresa']) ? "'" . $reg['id_empresa'] . "'" : $idEmpresa;
                    $idUnidad = !empty($reg['id_unidad']) ? "'" . $reg['id_unidad'] . "'" : "NULL";
                    $idDepartamento = !empty($reg['id_departamento']) ? "'" . $reg['id_departamento'] . "'" : "NULL";
                    $idArea = "'" . $reg['id_area'] . "'";
                    $idSeccion = "NULL";
                    break;

                case 4: // Departamento
                    if (empty($reg['id_departamento'])) {
                        throw new Exception('El ID del departamento es obligatorio para esta modalidad.');
                    }

                    // Usar los valores proporcionados en $reg
                    $idEmpresa = !empty($reg['id_empresa']) ? "'" . $reg['id_empresa'] . "'" : $idEmpresa;
                    $idUnidad = !empty($reg['id_unidad']) ? "'" . $reg['id_unidad'] . "'" : "NULL";
                    $idDepartamento = "'" . $reg['id_departamento'] . "'";
                    $idArea = "NULL";
                    $idSeccion = "NULL";
                    break;

                case 5: // Departamento/Unidad (Gerencia)
                    if (empty($reg['id_unidad'])) {
                        throw new Exception('El ID de la unidad es obligatorio para esta modalidad.');
                    }

                    // Usar los valores proporcionados en $reg
                    $idEmpresa = !empty($reg['id_empresa']) ? "'" . $reg['id_empresa'] . "'" : $idEmpresa;
                    $idUnidad = "'" . $reg['id_unidad'] . "'";
                    $idDepartamento = "NULL";
                    $idArea = "NULL";
                    $idSeccion = "NULL";
                    break;

                case 6: // Toda la empresa
                    $idEmpresa = !empty($reg['id_empresa']) ? "'" . $reg['id_empresa'] . "'" : $idEmpresa;
                    $idUnidad = "NULL";
                    $idDepartamento = "NULL";
                    $idArea = "NULL";
                    $idSeccion = "NULL";
                    break;

                default:
                    throw new Exception('Modalidad no válida.');
            }

            // Construir la consulta SQL
            $sqlQuery = "INSERT INTO dbo.TBINT_VACA_ESPECIALES (
            id_empresa, 
            id_sucursal, 
            id_unidad, 
            id_departamento, 
            id_area, 
            id_seccion, 
            id_solicitante, 
            solicitante,  
            id_generador, 
            generador, 
            fecha_inicio,
            fecha_fin,
            num_dias,
            eliminado,
            fecha_crea,
            usu_crea
        ) VALUES (
            " . $idEmpresa . ",
            " . $idSucursal . ",
            " . $idUnidad . ",
            " . $idDepartamento . ",
            " . $idArea . ",
            " . $idSeccion . ",
            " . $idSolicitante . ",
            " . $nombreSolicitante . ",
            " . $idGenerador . ",
            '" . $nombreGenerador . "',
            '" . $fechaInicio->format('Y-m-d') . "',
            '" . $fechaFin->format('Y-m-d') . "',
            " . $cantidadDias . ",
            0, 
            GETDATE(),
            " . $idGenerador . "
        )";

            // Ejecutar la consulta
            try {
                $id = $this->intra_trans->DoInsert($sqlQuery);
                return $id;
            } catch (Exception $e) {
                throw new Exception("Error al insertar la solicitud: " . $e->getMessage());
            }
        } catch (Exception $e) {
            // Para entornos de producción, considera usar un sistema de logging en lugar de echo
            error_log("Error en _insertarSolicitudVacacionEspecial: " . $e->getMessage());
            throw $e; // Relanza la excepción para que sea capturada en el método principal
        }
    }
    public function eliminarLogicamente($idVacacionEspecial)
    {
        echo 'entra a eliminar';
        echo $idVacacionEspecial;
        $userInfo = $this->sessionObj->getUserInfo();
        $sqlQuery = "UPDATE TBINT_VACA_ESPECIALES SET
        [eliminado] = 1,
        [usu_elim] = " . $userInfo[0]->ID_USUARIO . ",
        [fecha_elim] = GETDATE()
        WHERE  id_vaca_especial = {$idVacacionEspecial};";
        return $this->intra_db->DoUpdate($sqlQuery);
    }
}