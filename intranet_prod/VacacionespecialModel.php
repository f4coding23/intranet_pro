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
    public function getInfoVacacionEspecial($qryEmpresa, $qryGerencia, $qryDepartamento, $qryArea, $qrySeccion, $qryFechaInicio, $qryFechaFin, $qryColaborador)
    {
        $infoGenerador = $this->sessionObj->getUserInfo();
        $idGenerador = $infoGenerador[0]->ID_USUARIO;

        $this->intra_db->usarUTF8();
        $this->intra_db->setCampos("COUNT(*) AS num");
        $this->intra_db->setTabla("VW_VACACIONES_ESPECIALES");

        if ($qryEmpresa) {
            $this->intra_db->setCondicion("=", "id_empresa", "$qryEmpresa");
        }

        if ($qryGerencia) {
            if (is_array($qryGerencia)) {
                $gerencias = implode("','", $qryGerencia);
                $this->intra_db->setCondicionString(" id_unidad IN ('" . $gerencias . "')");
            } else {
                $this->intra_db->setCondicion("=", "id_unidad", "$qryGerencia");
            }
        }

        if ($qryDepartamento) {
            $this->intra_db->setCondicion("=", "id_departamento", "$qryDepartamento");
        }

        if ($qryArea) {
            $this->intra_db->setCondicion("=", "id_area", "$qryArea");
        }

        if ($qrySeccion) {
            $this->intra_db->setCondicion("=", "id_seccion", "$qrySeccion");
        }

        if (!empty($qryColaborador)) {
            $this->intra_db->setCondicionString(" solicitante LIKE '%{$qryColaborador}%'");
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

        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }
    public function crearVacacionEspecial($registros)
    {
        $resultado = ['status' => false, 'mensaje' => '', 'ids' => []];

        try {
            // Iniciar la conexión y la transacción
            $this->getInstaceTransac();
            $this->intra_trans->Conectar(true);
            $this->intra_trans->iniciarTransaccion();

            // Array para almacenar los IDs de las vacaciones creadas
            $ids_vacaciones = [];

            // Procesar cada registro
            foreach ($registros as $reg) {
                // Validar que el registro tenga los campos necesarios
                if (empty($reg['fecha_inicio']) || empty($reg['fecha_fin'])) {
                    throw new Exception('Las fechas de inicio y fin son obligatorias para todos los registros.');
                }

                // Obtener la modalidad del registro
                $modalidad = isset($reg['modalidad']) ? $reg['modalidad'] : null;

                if ($modalidad === null) {
                    throw new Exception('La modalidad es obligatoria para todos los registros.');
                }

                // Insertar la solicitud de vacación especial
                $id_vaca = $this->_insertarSolicitudVacacionEspecial($reg, $modalidad);

                // Guardar el ID de la vacación creada
                $ids_vacaciones[] = $id_vaca;
            }

            // Confirmar la transacción
            $this->intra_trans->commitTransaccion();
            $this->intra_trans->Desconectar();

            // Devolver el resultado exitoso
            $resultado['status'] = true;
            $resultado['ids'] = $ids_vacaciones; // Devolver los IDs de las vacaciones creadas
        } catch (Exception $e) {
            // Revertir la transacción en caso de error
            $this->intra_trans->rollbackTransaccion();
            $this->intra_trans->Desconectar();

            // Registrar el error y devolver el mensaje
            error_log("Error en crearVacacionEspecial: " . $e->getMessage());
            $resultado['mensaje'] = 'Error: ' . $e->getMessage();
        }

        return $resultado;
    }
    private function _getUserFromOfisis($co_trab)
    {
        $this->intra_db->usarUTF8();
        $this->intra_db->setCampos("CO_EMPR, CO_UNID, CO_DEPA, CO_AREA, CO_SECC 'CO_SEC'"); //Se coloca un alias, ya que en la vista original esta asi
        $this->intra_db->setTabla("VW_OFI_PERFIL");
        $this->intra_db->setCondicion("=", "CO_TRAB", $co_trab);
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }
    private function _insertarSolicitudVacacionEspecial($reg, $modalidad)
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
                    // var_dump($solicitanteInfo);

                    // Obtener información del solicitante desde Ofisis usando el USUARIO del solicitanteInfo
                    $solicitanteInfoOfisis = $this->_getUserFromOfisis($solicitanteInfo[0]->USUARIO);
                    // var_dump($solicitanteInfoOfisis);

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

            try {
                $id = $this->intra_trans->DoInsert($sqlQuery);
                if ($modalidad == 1) {
                    $this->insertarUsuarioEnTablaIntermedia($idEmpresa, $idSucursal, $idUnidad, $idDepartamento, $idArea, $idSeccion, $idSolicitante, $idGenerador, $fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d'), $cantidadDias, $id);
                } else {
                    $this->insertarUsuariosMasivosEnTablaIntermedia($idEmpresa, $idSucursal, $idUnidad, $idDepartamento, $idArea, $idSeccion, $id, $modalidad, $idGenerador, $fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d'), $cantidadDias);
                }
                return $id;
            } catch (Exception $e) {
                throw new Exception("Error al insertar la solicitud: " . $e->getMessage());
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function eliminarLogicamente($idVacacionEspecial)
    {
        $userInfo = $this->sessionObj->getUserInfo();
        $usuario = $userInfo[0]->ID_USUARIO;
        $resultado = 0;

        $params = array(
            array($idVacacionEspecial, SQLSRV_PARAM_IN),
            array($usuario, SQLSRV_PARAM_IN),
            array(&$resultado, SQLSRV_PARAM_OUT)
        );

        $sqlQuery = "{CALL USP_UPDATE_VACA_ESPECIAL(?,?,?)}";
        $this->intra_db->CallSP2($sqlQuery, $params);

        return $resultado;
    }
    private function insertarUsuarioEnTablaIntermedia($idEmpresa, $idSucursal, $idUnidad, $idDepartamento, $idArea, $idSeccion, $idSolicitante, $idGenerador, $fechaInicio, $fechaFin, $cantidadDias, $idVacaEspecial)
    {
        // Insertar en la tabla intermedia
        $sqlQuery = "INSERT INTO dbo.TBINT_VACACIONES_TEMP (
            id_empresa,
            id_sucursal,
            id_unidad,
            id_area,
            id_seccion,
            id_solicitante,
            id_generador,
            fecha_inicio,
            fecha_fin,
            num_dias,
            eliminado,
            fecha_crea,
            usu_crea,
            id_departamento,
            id_vaca_especial,
            subperiodo
        ) VALUES (
            " . $idEmpresa . ",
            " . $idSucursal . ",
            " . $idUnidad . ",
            " . $idArea . ",
            " . $idSeccion . ",
            " . $idSolicitante . ",
            " . $idGenerador . ",
            '" . $fechaInicio . "',
            '" . $fechaFin . "',
            '" . $cantidadDias . "',
            0,
            GETDATE(),
            " . $idGenerador . ",
            " . $idDepartamento . ",
            " . $idVacaEspecial . ",
            2
        )";
        // echo $sqlQuery;
        $this->intra_db->DoInsert($sqlQuery);
    }
    public function insertarUsuariosMasivosEnTablaIntermedia($idEmpresa, $idSucursal, $idUnidad, $idDepartamento, $idArea, $idSeccion, $idVacaEspecial, $modalidad, $idGenerador, $fechaInicio, $fechaFin, $cantidadDias)
    {
        // Eliminar comillas simples adicionales de los valores
        $idEmpresa = trim($idEmpresa, "'");
        $idSucursal = intval(str_replace(["'", '"', " "], "", $idSucursal));
        $idUnidad = trim($idUnidad, "'");
        $idDepartamento = trim($idDepartamento, "'");
        $idArea = trim($idArea, "'");
        $idSeccion = trim($idSeccion, "'");
        $idVacaEspecial = intval(str_replace(["'", '"', " "], "", $idVacaEspecial));

        // Convertir 'NULL' a null
        $idUnidad = ($idUnidad === 'NULL' || $idUnidad === "''") ? null : $idUnidad;
        $idDepartamento = ($idDepartamento === 'NULL' || $idDepartamento === "''") ? null : $idDepartamento;
        $idArea = ($idArea === 'NULL' || $idArea === "''") ? null : $idArea;
        $idSeccion = ($idSeccion === 'NULL' || $idSeccion === "''") ? null : $idSeccion;

        // Parámetros para el procedimiento almacenado
        $params = array(
            array($idEmpresa, SQLSRV_PARAM_IN),
            array($idSucursal, SQLSRV_PARAM_IN),
            array($idUnidad, SQLSRV_PARAM_IN),
            array($idDepartamento, SQLSRV_PARAM_IN),
            array($idArea, SQLSRV_PARAM_IN),
            array($idSeccion, SQLSRV_PARAM_IN),
            array($idVacaEspecial, SQLSRV_PARAM_IN),
            array($modalidad, SQLSRV_PARAM_IN),
            array($idGenerador, SQLSRV_PARAM_IN),
            array($fechaInicio, SQLSRV_PARAM_IN),
            array($fechaFin, SQLSRV_PARAM_IN),
            array($cantidadDias, SQLSRV_PARAM_IN),
        );

        // Consulta SQL para llamar al procedimiento almacenado
        $sqlQuery = "{CALL USP_GENERAR_VACACIONES_ESPECIALES_MASIVO(?,?,?,?,?,?,?,?,?,?,?,?)}";
        $this->intra_db->CallSP($sqlQuery, $params);
    }
    public function exportarVacacionesEspeciales($qryEmpresa, $qryGerencia, $qryDepartamento, $qryArea, $qrySeccion, $qryFechaInicio, $qryFechaFin, $qryColaborador)
    {
        $infoGenerador = $this->sessionObj->getUserInfo();
        // $idGenerador = $infoGenerador[0]->ID_USUARIO;
        $this->intra_db->usarUTF8();
        $this->intra_db->setCampos("
        A.id_vaca_especial,
        A.gerencia,
        A.departamento,
        A.area,
        A.seccion,
        A.solicitante,
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

        $this->intra_db->setOrden("A.id_vaca_especial DESC");
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }
    public function _validarFechaEspecialSolicitante($idSolicitante, $fechaInicio, $fechaFin)
    {
        $rsp_existe = 0;
        $rsp_mensaje = '';

        $params = array(
            array($idSolicitante, SQLSRV_PARAM_IN),
            array($fechaInicio, SQLSRV_PARAM_IN),
            array($fechaFin, SQLSRV_PARAM_IN),
            array(&$rsp_existe, SQLSRV_PARAM_OUT),
            array(&$rsp_mensaje, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(200))
        );

        $sqlQuery = "{CALL USP_VALIDA_VACACIONESPECIAL_SOLICITANTE(?,?,?,?,?)}";
        $this->intra_db->CallSP($sqlQuery, $params);

        $response['existeRegistro'] = $rsp_existe;
        $response['mensaje'] = $rsp_mensaje;

        return $response;
    }
    public function _validarFechaEspecial($idEmpresa, $idUnidad, $idDepartamento, $idArea, $idSeccion, $fechaInicio, $fechaFin)
    {
        $rsp_existe = 0;
        $rsp_mensaje = '';

        $params = array(
            array($idEmpresa, SQLSRV_PARAM_IN),
            array($idUnidad, SQLSRV_PARAM_IN),
            array($idDepartamento, SQLSRV_PARAM_IN),
            array($idArea, SQLSRV_PARAM_IN),
            array($idSeccion, SQLSRV_PARAM_IN),
            array($fechaInicio, SQLSRV_PARAM_IN),
            array($fechaFin, SQLSRV_PARAM_IN),
            array(&$rsp_existe, SQLSRV_PARAM_OUT),
            array(&$rsp_mensaje, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(200))
        );

        $sqlQuery = "{CALL SPU_VALIDAR_VACACIONESPECIAL(?,?,?,?,?,?,?,?,?)}";
        $this->intra_db->CallSP($sqlQuery, $params);
        $response['existeRegistro'] = $rsp_existe;
        $response['mensaje'] = $rsp_mensaje;

        return $response;
    }
    public function obtenerLimiteDiasVacacionesEspeciales()
    {
        $this->intra_db->usarUTF8();
        $this->intra_db->setCampos("valor");
        $this->intra_db->setTabla("TBINT_VACA_CONFIG");
        $this->intra_db->setCondicion("=", "configuracion", "limite_dias_vacaciones_especiales");
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }

    public function listarVacacionesTemp($idvacaespecial)
    {
        $this->intra_db->usarUTF8();
        $this->intra_db->setCampos("*");
        $this->intra_db->setTabla("[dbo].[VW_VACACIONES_ESPECIALES_TEMP]");
        $this->intra_db->setCondicion("=", "id_vaca_especial", $idvacaespecial); // Usa la variable recibida
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }
}