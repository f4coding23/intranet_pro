<?php
class VacacionModel extends ModelBase
{

    private $maxDiasHabiles;
    private $maxDiasNoHabiles;
    private $idVacacion;

    public function __construct()
    {
        parent::__construct(__CLASS__);
        $this->maxDiasHabiles = 22;
        $this->maxDiasNoHabiles = 8;
        $this->idVacacion = 0;
    }

    public function getVacacionesList($pgStart, $pgSize, $pgSort, $qryEmpresa, $qryGerencia, $qryDepartamento, $qryArea, $qrySeccion, $qryFechaInicio, $qryFechaFin, $dnis, $qryColaborador)
    {
        $infoGenerador = $this->sessionObj->getUserInfo();
        $idGenerador = $infoGenerador[0]->ID_USUARIO;

        $this->intra_db->usarUTF8();
        $this->intra_db->setCampos("id_vacacion, empresa, gerencia, departamento, area, id_solicitante, solicitante, id_generador, generador,  fecha_crea, id_vaca_condicion, vaca_condicion, fecha_inicio, fecha_fin, num_dias, id_vaca_estado, vaca_estado, idTipo, tipo");
        $this->intra_db->setTabla("VW_VACACIONES");

        if ($dnis) {
            $this->intra_db->setCondicionString(" ( dni IN (" . $dnis . ") OR (id_generador = {$idGenerador} AND idTipo <> 2) )");
        }

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

        $this->intra_db->setOrden($pgSort);
        $this->intra_db->setLimit($pgStart, $pgSize);
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }

    public function getNumVacaciones($qryEmpresa, $qryGerencia, $qryDepartamento, $qryArea, $qrySeccion, $qryFechaInicio, $qryFechaFin, $dnis, $qryColaborador)
    {
        $infoGenerador = $this->sessionObj->getUserInfo();
        $idGenerador = $infoGenerador[0]->ID_USUARIO;

        $this->intra_db->usarUTF8();
        $this->intra_db->setCampos("COUNT(*) AS num");
        $this->intra_db->setTabla("VW_VACACIONES");

        if ($dnis) {
            $this->intra_db->setCondicionString(" ( dni IN (" . $dnis . ") OR (id_generador = {$idGenerador} AND idTipo <> 2) )");
        }

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
            $this->intra_db->setCondicionString(" fecha_inicio BETWEEN '$qryFechaInicio' AND '$qryFechaFin'");
        } else {
            if ($qryFechaInicio && !$qryFechaFin) {
                $this->intra_db->setCondicionString(" fecha_inicio >= '$qryFechaInicio'");
            } elseif (!$qryFechaInicio && $qryFechaFin) {
                $this->intra_db->setCondicionString(" fecha_inicio <= '$qryFechaFin'");
            }
        }

        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }

    public function listarCondicionCombo()
    {
        $this->intra_db->usarUTF8();
        $this->intra_db->setCampos("id_vaca_condicion, vaca_condicion");
        $this->intra_db->setTabla("TBINT_VACA_CONDICION");
        $this->intra_db->setCondicion("=", "activo", 1);
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }

    public function getFechaIngresoColaborador($coTrab)
    {
        $this->intra_db->setCampos("FE_INGR_EMPR");
        $this->intra_db->setTabla("VW_OFI_PERFIL");
        $this->intra_db->setCondicion("=", "CO_TRAB", $coTrab);
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }

    public function getNumProgramadas($idSolicitante, $idCondicion, $idVacacion = 0)
    {
        $this->intra_db->setCampos("SUM(num_dias) AS dias_total");
        $this->intra_db->setTabla("TBINT_VACACIONES");
        $this->intra_db->setCondicion("=", "id_solicitante", $idSolicitante);

        if ($idCondicion == 1) {
            $this->intra_db->setCondicionString("id_vaca_condicion IN (1, 3)"); // Formato SQL directo
        } else {
            $this->intra_db->setCondicion("=", "id_vaca_condicion", $idCondicion);
        }

        $this->intra_db->setCondicion("=", "eliminado", 0);
        $this->intra_db->setCondicionString("id_vaca_estado IN (1,2,3,4)");

        if ($idVacacion) {
            $this->intra_db->setCondicion("<>", "id_vacacion", $idVacacion);
        }

        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }

    public function getNumProgramadasHabiles($idSolicitante, $idCondicion, $idVacacion = 0)
    {
        $this->intra_db->setCampos("SUM(VD.num_dias_habil) AS num, SUM(VD.num_dias_no_habil) AS dias_no_habil, SUM(VD.num_dias_total) AS dias_total");
        $this->intra_db->setTabla(array('V' => "TBINT_VACACIONES"));
        $this->intra_db->setJoin(array("VD" => "TBINT_VACA_DISTRIBUCION"), "VD.id_vacacion = V.id_vacacion", "INNER");
        $this->intra_db->setCondicion("=", "V.id_solicitante", $idSolicitante);
        $this->intra_db->setCondicion("=", "V.id_vaca_condicion", $idCondicion);
        $this->intra_db->setCondicion("=", "V.eliminado", 0);
        $this->intra_db->setCondicionString("V.id_vaca_estado IN (1,2,3,4)");

        if ($idVacacion) {
            $this->intra_db->setCondicion("<>", "V.id_vacacion", $idVacacion);
        }

        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }

    public function getNumProgramadasHabilesPeriodo($idSolicitante, $idCondicion, $idVacacion = 0)
    {
        $this->intra_db->setCampos("periodo, SUM(VD.num_dias_habil) AS num, SUM(VD.num_dias_no_habil) AS dias_no_habil, SUM(VD.num_dias_total) AS dias_total");
        $this->intra_db->setTabla(array('V' => "TBINT_VACACIONES"));
        $this->intra_db->setJoin(array("VD" => "TBINT_VACA_DISTRIBUCION"), "VD.id_vacacion = V.id_vacacion", "INNER");
        $this->intra_db->setCondicion("=", "V.id_solicitante", $idSolicitante);
        $this->intra_db->setCondicion("=", "V.id_vaca_condicion", $idCondicion);
        $this->intra_db->setCondicion("=", "V.eliminado", 0);
        $this->intra_db->setCondicionString("V.id_vaca_estado IN (1,2,3,4)");

        if ($idVacacion) {
            $this->intra_db->setCondicion("<>", "V.id_vacacion", $idVacacion);
        }
        $this->intra_db->setGrupo('periodo');
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }

    public function getVacacionesFromDate($idSolicitante, $fechaInicio, $fechaFin, $idSolicitud = 0)
    {
        $this->intra_db->setCampos("id_vacacion");
        $this->intra_db->setTabla("TBINT_VACACIONES");
        $this->intra_db->setCondicion("=", "id_solicitante", $idSolicitante);
        $this->intra_db->setCondicionString("((
            '" . $fechaInicio . "' BETWEEN fecha_inicio AND fecha_fin
            )OR( 
            '" . $fechaFin . "' BETWEEN fecha_inicio AND fecha_fin
            )OR(
            '" . $fechaInicio . "' < fecha_inicio AND '" . $fechaFin . "' > fecha_fin
            )OR(
            '" . $fechaInicio . "' > fecha_inicio AND '" . $fechaFin . "' < fecha_fin
        ))");
        $this->intra_db->setCondicionString("id_vaca_estado <> 6"); //Rechazados
        $this->intra_db->setCondicion('=', 'eliminado', 0); //No eliminados

        if ($idSolicitud) {
            $this->intra_db->setCondicionString("id_vacacion <> " . $idSolicitud); //Excluir a la propia solicitud
        }

        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }

    public function getInfoVacacion($idVacacion)
    {
        $this->intra_db->setCampos('*');
        $this->intra_db->setTabla("VW_VACACIONES");
        $this->intra_db->setCondicion("=", "id_vacacion", $idVacacion);
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

    public function getAutorizacionesList($idVacacion)
    {
        $this->intra_db->usarUTF8();
        $this->intra_db->setCampos("id_vaca_aut, id_vacacion, id_autorizador, autorizador, fecha_propuesta, fecha_autorizacion, estado_aprobacion, motivo_rechazo");
        $this->intra_db->setTabla("VW_VACA_AUTORIZACIONES");
        $this->intra_db->setCondicion("=", "id_vacacion", "$idVacacion");
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }

    public function getVirtualAutorizador($idVacacion)
    {
        $qryResult = [];
        $vacacion = $this->getInfoVacacion($idVacacion);

        if ($vacacion[0]->id_vaca_estado == 3) {
            $this->intra_db->usarUTF8();
            $this->intra_db->setCampos("null 'id_vaca_aut', " . $vacacion[0]->id_vacacion . " as id_vacacion, idAprobador as id_autorizador, S.USUVCNOUSUARIO as autorizador, null 'fecha_propuesta', null 'fecha_autorizacion', 'Pendiente 2da Aprobación' 'estado_aprobacion', null motivo_rechazo");
            $this->intra_db->setTabla(array("I" => "TBINT_PERMISO_INSTANCIAS"));
            $this->intra_db->setJoin(array('S' => 'VW_SEG_USUARIOS'), 'S.USUINIDUSUARIO = I.idAprobador', 'INNER');
            $this->intra_db->setCondicion("=", "idEmpresa", $vacacion[0]->id_empresa);
            //$this->intra_db->setCondicion("=","idSucursal",$vacacion[0]->id_sucursal);
            $this->intra_db->setCondicion("=", "instancia", 2);
            $this->intra_db->setCondicion("=", "ID_PROCESO_PERMISO", 3);
            $this->intra_db->setCondicionString("(
                (idUnidad = '" . $vacacion[0]->id_unidad . "' AND idDepartamento IS NULL AND idArea IS NULL AND idSeccion IS NULL) OR
                                    (idUnidad = '" . $vacacion[0]->id_unidad . "' AND idDepartamento = '" . $vacacion[0]->id_departamento . "' AND idArea IS NULL AND idSeccion IS NULL) OR
                                    (idUnidad = '" . $vacacion[0]->id_unidad . "' AND idDepartamento = '" . $vacacion[0]->id_departamento . "' AND idArea = '" . $vacacion[0]->id_area . "' AND idSeccion IS NULL) OR
                                    (idUnidad = '" . $vacacion[0]->id_unidad . "' AND idDepartamento = '" . $vacacion[0]->id_departamento . "' AND idArea = '" . $vacacion[0]->id_area . "' AND idSeccion = '" . $vacacion[0]->id_seccion . "')
            )");
            $qryResult = $this->intra_db->Listar();
        }

        return $qryResult;
    }

    public function listarVacacionesExport($qryEmpresa, $qryGerencia, $qryDepartamento, $qryArea, $qrySeccion, $qryFechaInicio, $qryFechaFin, $dnis, $qryColaborador)
    {
        $infoGenerador = $this->sessionObj->getUserInfo();
        $idGenerador = $infoGenerador[0]->ID_USUARIO;

        $this->intra_db->usarUTF8();
        $this->intra_db->setCampos("id_vacacion, gerencia, departamento, area, seccion, solicitante, dni,generador, dni_generador, fecha_crea, vaca_condicion, fecha_inicio, fecha_fin, num_dias, vaca_estado, tipo, dbo.FUNC_VACA_AUTORIZADOR(id_vacacion,1) autorizador_1ra, dbo.FUNC_VACA_AUTORIZADOR_DNI(id_vacacion,1) dni_1ra,dbo.FUNC_VACA_AUTORIZADOR(id_vacacion,2) autorizador_2da,dbo.FUNC_VACA_AUTORIZADOR_DNI(id_vacacion,2) dni_2da");
        $this->intra_db->setTabla("VW_VACACIONES");

        if ($dnis) {
            $this->intra_db->setCondicionString(" ( dni IN (" . $dnis . ") OR (id_generador = {$idGenerador} AND idTipo <> 2) )");
        }

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

    /******************************************* CREACION DE SOLICITUD DE VACACIONES *********************************************/
    public function createVacacion($reg)
    {
        $resultado = array('status' => false, 'mensaje' => '');
        $tblConsolidado = (array) json_decode($reg['tblConsolidado']);

        //Iniciamos la Conexión y la transacción
        $this->getInstaceTransac();
        $this->intra_trans->Conectar(true);
        $this->intra_trans->iniciarTransaccion();

        $resultadoInsercion = $this->_insertarSolicitudVacacion($reg, $reg['modalidad']);
        if (!$resultadoInsercion['status']) {
            $this->intra_trans->rollbackTransaccion();
            $this->intra_trans->Desconectar();

            $resultado['mensaje'] = $resultadoInsercion['mensaje'];
            return $resultado;
        }

        $this->idVacacion = $resultadoInsercion['id_vacacion'];

        $this->intra_trans->commitTransaccion();
        $this->intra_trans->Desconectar();

        //Si es una creacion desde el master, notificar a responsables
        if (isset($reg['master']) && $reg['master']) {
            $regVaca = $this->getInfoVacacion($this->idVacacion);
            $this->_proccessMailMaster($regVaca);
        }

        $resultado['status'] = true;
        $resultado['id'] = $this->idVacacion;
        $resultado['mensaje'] = $resultadoInsercion['mensaje']; // Incluir el mensaje de éxito
        return $resultado;
    }
    private function _insertarSolicitudVacacion($reg, $modalidad = 1)
    {
        $dateFormat = DateTime::createFromFormat('d/m/Y', $reg['txtFechaIngreso']);
        $fechaIngreso = $dateFormat->format('Y-m-d');
        $resultado = array('status' => false, 'mensaje' => '');

        switch ($modalidad) {
            case 1:
                //PROPIO
                $userInfo = $this->sessionObj->getUserInfo();
                $userInfoOfisis = $this->sessionObj->getInfoFromOfisis();
                $tipo = 1;
                $confirmado = 0;
                $estado = 1;
                $idGenerador = $userInfo[0]->ID_USUARIO;
                break;
            case 2:
                // SE HACE X EMERGENCIA (ESPECIAL)
                $userInfo = $this->sessionObj->getUserInfo($reg['cboSolicitante']);
                $userInfoOfisis = $this->_getUserFromOfisis($userInfo[0]->USUARIO);
                $tipo = 2;
                $confirmado = 1;
                $estado = 4;

                $infoGenerador = $this->sessionObj->getUserInfo();
                $idGenerador = $infoGenerador[0]->ID_USUARIO;
                break;
            case 3:
                // Lo HACE UN GENERADOR 
                $userInfo = $this->sessionObj->getUserInfo($reg['cboSolicitante']);
                $userInfoOfisis = $this->_getUserFromOfisis($userInfo[0]->USUARIO);
                $tipo = 1;
                $confirmado = 0;
                $estado = 1;

                $infoGenerador = $this->sessionObj->getUserInfo();
                $idGenerador = $infoGenerador[0]->ID_USUARIO;
                break;
        }

        $esFechaEpecial = $this->getFechaEspecial($reg['txtFechaInicio'], $reg['txtFechaFin'], $userInfo[0]->ID_USUARIO);
        // var_dump($esFechaEpecial);
        // die();
        $infoSucursal = $this->sessionObj->getInfoFromSucursalByEmpresa($userInfoOfisis[0]->CO_EMPR, 1);
        //aquí obtenemos el periodo del cual dispones días de vacaciones
        $periodoActual = $this->_obtenerPeriodoActual($userInfo[0]->DNI);
        // var_dump($periodoActual);
        // die();

        $validacionIntegral = $this->determinarYValidarSolicitud(
            $userInfo[0]->ID_USUARIO,
            $periodoActual['periodo'],
            $reg['txtFechaInicio'],
            $reg['txtFechaFin'],
            $reg['txtCantidadDias'],
            $userInfoOfisis[0]->CO_EMPR,
            $reg['cboCondicion']
        );
        // var_dump($validacionIntegral);
        // die();

        if (!$validacionIntegral['es_valido']) {
            $resultado['mensaje'] = $validacionIntegral['mensaje'];
            return $resultado;
        }

        // Obtenemos el subperíodo asignado
        $subperiodoDestino = $validacionIntegral['subperiodo'];
        $idVacacionEspecial = isset($validacionIntegral['id_vacacion_especial']) ? $validacionIntegral['id_vacacion_especial'] : null;
        // reemplazar por lo de validacionintegral
        //$idVacacionEspecial = isset($esFechaEpecial[0]->id_vaca_especial) ? $esFechaEpecial[0]->id_vaca_especial : null;

        $idVacacion = 0;

        $params = array(
            array($userInfoOfisis[0]->CO_EMPR, SQLSRV_PARAM_IN),
            array($infoSucursal[0]->SUCINIDSUCURSAL, SQLSRV_PARAM_IN),
            array($userInfoOfisis[0]->CO_UNID, SQLSRV_PARAM_IN),
            array($userInfoOfisis[0]->CO_DEPA, SQLSRV_PARAM_IN),
            array($userInfoOfisis[0]->CO_AREA, SQLSRV_PARAM_IN),
            array($userInfoOfisis[0]->CO_SEC, SQLSRV_PARAM_IN),
            array($userInfo[0]->ID_USUARIO, SQLSRV_PARAM_IN),
            array($fechaIngreso, SQLSRV_PARAM_IN),
            array($idGenerador, SQLSRV_PARAM_IN),
            array($tipo, SQLSRV_PARAM_IN),
            array($reg['cboCondicion'], SQLSRV_PARAM_IN),
            array($estado, SQLSRV_PARAM_IN),
            array($reg['txtFechaInicio'], SQLSRV_PARAM_IN),
            array($reg['txtFechaFin'], SQLSRV_PARAM_IN),
            array($reg['txtCantidadDias'], SQLSRV_PARAM_IN),
            array($confirmado, SQLSRV_PARAM_IN),
            array($idGenerador, SQLSRV_PARAM_IN),
            array($idVacacionEspecial, SQLSRV_PARAM_IN),
            array($periodoActual['periodo'], SQLSRV_PARAM_IN),
            array($subperiodoDestino, SQLSRV_PARAM_IN),
            array(&$idVacacion, SQLSRV_PARAM_OUT) // Parámetro de salida para el ID
        );

        $sqlQuery = "{CALL SPU_CREATE_VACACION_PERIODO(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}";
        // print_r($params);
        // die();
        $result = $this->intra_db->CallSP4($sqlQuery, $params);
        if ($result) {
            $resultado['status'] = true;
            $resultado['id_vacacion'] = $idVacacion;
            $resultado['mensaje'] = "Solicitud de vacaciones registrada correctamente.";
        } else {
            $resultado['mensaje'] = "Error al registrar la solicitud de vacaciones.";
        }
        return $resultado;

    }
    private function _insertarDistribucion($idVacacion, $distribuciones)
    {

        foreach ($distribuciones as $distribucion) {

            foreach ($distribucion['detalle'] as $row) {
                $cantFechas = count($row['fechas']);
                $diasHabiles = isset($row['pend_habil']) ? $row['pend_habil'] : 0;
                $diasNoHabiles = isset($row['pend_no_habil']) ? $row['pend_no_habil'] : 0;
                $totalDias = $diasHabiles + $diasNoHabiles;

                $sqlQuery = " INSERT INTO dbo.TBINT_VACA_DISTRIBUCION(id_vacacion, periodo, fecha_inicio, fecha_fin, num_dias_total, num_dias_habil, num_dias_no_habil) 
                VALUES (
                {$idVacacion},
                " . substr($distribucion['periodo'], 0, 4) . ",
                '" . $row['fechas'][0] . "',
                '" . $row['fechas'][$cantFechas - 1] . "',
                " . $totalDias . ",
                " . $diasHabiles . ",
                " . $diasNoHabiles . ");";

                $this->intra_trans->DoInsert($sqlQuery);
            }
        }
    }
    private function _armarDistribucion($idSolicitante, $trunco, $condicion, $fechaInicio, $fechaFin, $idSolicitud = 0)
    {
        $resultado = array('status' => false, 'mensaje' => '');

        $userInfo = $this->sessionObj->getUserInfo($idSolicitante);
        $usuario = $userInfo[0]->USUARIO;
        $indexFormatToDelete = array();

        $periodos = $this->_getDetallePeriodos($usuario, '01', date('Y-m-d'), $trunco);

        if (empty($periodos)) {
            $regIngreso = $this->getFechaIngresoColaborador($usuario);
            $hoy = new DateTime(date('Y-m-d'));
            $interval = $hoy->diff($regIngreso[0]->FE_INGR_EMPR);

            $anios = array();
            $anioIngreso = $regIngreso[0]->FE_INGR_EMPR->format('Y');

            for ($i = $anioIngreso; $i < date('Y'); $i++) {
                $anios[] = (int) $i;
            }

            foreach ($anios as $key => $anio) {
                $periodos[] = (object) array(
                    'CO_EMPR' => '01',
                    'CO_TRAB' => $usuario,
                    'PE_VACA' => $anio . '-' . ($anio + 1),
                    'GANADAS' => 30,
                    'GOZADAS' => 0,
                    'TRUNCAS' => 0,
                    'SALDO' => 30,
                    'ESTADO' => 'Pendiente'
                );
            }

            //Si tiene pendiente
            //if($interval->format('%Y') > 0){
            $periodos[] = (object) array(
                'CO_EMPR' => '01',
                'CO_TRAB' => $usuario,
                'PE_VACA' => $regIngreso[0]->FE_INGR_EMPR->format('Y') . '-' . ($regIngreso[0]->FE_INGR_EMPR->format('Y') + 1),
                'GANADAS' => 0,
                'GOZADAS' => 0,
                'TRUNCAS' => $trunco,
                'SALDO' => $trunco,
                'ESTADO' => 'No Disponible'
            );
            //}
        }

        $formatPeriodos = $this->_formatPeriodosByCondicion($periodos, $condicion);
        $arrSoliTipoDias = $this->_sumarDiasPorTipo($fechaInicio, $fechaFin);
        $totalDisponible = array('habil' => 0, 'no_habil' => 0);

        foreach ($formatPeriodos as $key => $periodo) {
            $detallePeriodo = $this->_getDetalleVacaciones($usuario, '01', $periodo['PE_VACA'], date('d/m/Y'));

            if (empty($detallePeriodo)) {
                $detallePeriodo[] = array('NRO_DIAS' => 0, 'HABIL' => 0, 'NO_HABIL' => 0);
            }

            $consumoPeriodo = $this->_getDistribucionActiva(substr($periodo['PE_VACA'], 0, 4), $idSolicitante, $idSolicitud);
            if (empty($consumoPeriodo)) {
                $consumoPeriodo[] = (object) array('habil' => 0, 'no_habil' => 0);
            }

            //calcular lo pendiente (habil / no habil)
            $formatPeriodos[$key]['detalle'] = (array) $detallePeriodo[0];
            $diasHabilesTomados = $formatPeriodos[$key]['detalle']['HABIL'] + $consumoPeriodo[0]->habil;
            $diasNoHabilesTomados = $formatPeriodos[$key]['detalle']['NO_HABIL'] + $consumoPeriodo[0]->no_habil;
            $formatPeriodos[$key]['detalle']['pend_habil'] = $this->maxDiasHabiles - $diasHabilesTomados;
            $formatPeriodos[$key]['detalle']['pend_no_habil'] = $this->maxDiasNoHabiles - $diasNoHabilesTomados;

            //Almacenar saldos totales
            $totalDisponible['habil'] = $totalDisponible['habil'] + $diasHabilesTomados;
            $totalDisponible['no_habil'] = $totalDisponible['no_habil'] + $diasNoHabilesTomados;

            //De no existir periodos almancenar para eliminar
            if ($formatPeriodos[$key]['detalle']['pend_habil'] == 0 && $formatPeriodos[$key]['detalle']['pend_no_habil'] == 0) {
                $indexFormatToDelete[] = $key;
            }
        }

        if ($condicion == 1) {
            //Dias Habiles / No habiles disponibles (sobre el maximo por periodo)
            $totalDisponible['habil'] = ($this->maxDiasHabiles * count($formatPeriodos)) - $totalDisponible['habil'];
            $totalDisponible['no_habil'] = ($this->maxDiasNoHabiles * count($formatPeriodos)) - $totalDisponible['no_habil'];
        } else {
            //Dias Habiles / No habiles disponibles (sobre los truncos)
            $totalDisponible['habil'] = $formatPeriodos[0]['TRUNCAS'] - $totalDisponible['habil'];
            $totalDisponible['no_habil'] = $formatPeriodos[0]['TRUNCAS'] - $totalDisponible['no_habil'];
        }

        // Eliminar Periodos que ya no cuenten con disponibles
        foreach ($indexFormatToDelete as $index) {
            unset($formatPeriodos[$index]);
        }

        if ($arrSoliTipoDias['habil'] <= $totalDisponible['habil']) {
            /*if($arrSoliTipoDias['no_habil'] <= $totalDisponible['no_habil']){*/

            //DISTRIBUCION POR PERIODO
            $arrDistribucion = array();
            $arrDias = $this->_getRangeDates($fechaInicio, $fechaFin);

            foreach ($formatPeriodos as $key => $periodo) {
                if (!empty($arrDias)) {
                    $arrDistribucion[$key]['periodo'] = $periodo['PE_VACA'];
                    $indexes = array();
                    $grupo = 0;
                    $diaOrden = '';

                    foreach ($arrDias as $i => $dia) {
                        if ($i === array_key_first($arrDias)) {
                            $diaOrden = $dia;
                        }

                        if ($diaOrden->format('Y-m-d') != $dia->format('Y-m-d')) {
                            $diaOrden = $dia;
                            $grupo++;
                        }

                        $id = ($dia->format('w') == 0 || $dia->format('w') == 6) ? 'pend_no_habil' : 'pend_habil';

                        //Dias no habiles (fines de semana)
                        if ($periodo['detalle'][$id] > 0) {

                            if (!isset($arrDistribucion[$key]['detalle'][$grupo][$id])) {
                                $arrDistribucion[$key]['detalle'][$grupo][$id] = 0;
                            }

                            $arrDistribucion[$key]['detalle'][$grupo]['fechas'][] = $dia->format('Y-m-d');
                            $arrDistribucion[$key]['detalle'][$grupo][$id]++;
                            $periodo['detalle'][$id]--;
                            $indexes[] = $i;
                        } else {
                            //Si aun hay algun dia disponible para el siguien grupo continuar
                            if ($periodo['detalle']['pend_no_habil'] > 0 || $periodo['detalle']['pend_habil'] > 0) {
                                $grupo++; //Continuar con el siguiente grupo
                            } else {
                                break;
                            }
                        }

                        $diaOrden->modify('+1 day');
                    }

                    //Retirar la fecha ya usadas
                    foreach ($indexes as $index) {
                        unset($arrDias[$index]);
                    }
                }
            }

            $resultado = array('status' => true, 'resultado' => $arrDistribucion);
            /*}else{
                $resultado['mensaje'] = 'No se puede procesar, debido a que la cantidad de dias no habiles disponibles es: '.$totalDisponible['no_habil'];
            }*/
        } else {
            $resultado['mensaje'] = 'No se puede procesar, debido a que la cantidad de días hábiles disponibles es : ' . $totalDisponible['habil'];
        }

        return $resultado;
    }
    public function getVacacionesPendientes($idSolicitante, $trunco, $condicion, $idSolicitud = 0)
    {
        $userInfo = $this->sessionObj->getUserInfo($idSolicitante);
        $usuario = $userInfo[0]->USUARIO;

        $periodos = $this->_getDetallePeriodos($usuario, '01', date('Y-m-d'), $trunco);
        if (empty($periodos)) {
            $regIngreso = $this->getFechaIngresoColaborador($usuario);
            $hoy = new DateTime(date('Y-m-d'));
            $interval = $hoy->diff($regIngreso[0]->FE_INGR_EMPR);

            $anios = array();
            $anioIngreso = $regIngreso[0]->FE_INGR_EMPR->format('Y');

            for ($i = $anioIngreso; $i < date('Y'); $i++) {
                $anios[] = (int) $i;
            }

            foreach ($anios as $key => $anio) {
                $periodos[] = (object) array(
                    'CO_EMPR' => '01',
                    'CO_TRAB' => $usuario,
                    'PE_VACA' => $anio . '-' . ($anio + 1),
                    'GANADAS' => 30,
                    'GOZADAS' => 0,
                    'TRUNCAS' => 0,
                    'SALDO' => 30,
                    'ESTADO' => 'Pendiente'
                );
            }

            //Si tiene pendiente
            //if($interval->format('%Y') > 0){
            $periodos[] = (object) array(
                'CO_EMPR' => '01',
                'CO_TRAB' => $usuario,
                'PE_VACA' => $regIngreso[0]->FE_INGR_EMPR->format('Y') . '-' . ($regIngreso[0]->FE_INGR_EMPR->format('Y') + 1),
                'GANADAS' => 0,
                'GOZADAS' => 0,
                'TRUNCAS' => $trunco,
                'SALDO' => $trunco,
                'ESTADO' => 'No Disponible'
            );
            //}
        }

        $formatPeriodos = $this->_formatPeriodosByCondicion($periodos, $condicion);
        $totalDisponible = array('habil' => 0, 'no_habil' => 0);

        foreach ($formatPeriodos as $key => $periodo) {
            $detallePeriodo = $this->_getDetalleVacaciones($usuario, '01', $periodo['PE_VACA'], date('d/m/Y'));

            if (empty($detallePeriodo)) {
                $detallePeriodo[] = array('NRO_DIAS' => 0, 'HABIL' => 0, 'NO_HABIL' => 0);
            }

            $consumoPeriodo = $this->_getDistribucionActiva(substr($periodo['PE_VACA'], 0, 4), $idSolicitante, $idSolicitud);
            if (empty($consumoPeriodo)) {
                $consumoPeriodo[] = (object) array('habil' => 0, 'no_habil' => 0);
            }

            //calcular lo pendiente (habil / no habil)
            $formatPeriodos[$key]['detalle'] = (array) $detallePeriodo[0];
            $diasHabilesTomados = $formatPeriodos[$key]['detalle']['HABIL'] + $consumoPeriodo[0]->habil;
            $diasNoHabilesTomados = $formatPeriodos[$key]['detalle']['NO_HABIL'] + $consumoPeriodo[0]->no_habil;
            $formatPeriodos[$key]['detalle']['pend_habil'] = $this->maxDiasHabiles - $diasHabilesTomados;
            $formatPeriodos[$key]['detalle']['pend_no_habil'] = $this->maxDiasNoHabiles - $diasNoHabilesTomados;

            //Almacenar saldos totales
            $totalDisponible['habil'] = $totalDisponible['habil'] + $diasHabilesTomados;
            $totalDisponible['no_habil'] = $totalDisponible['no_habil'] + $diasNoHabilesTomados;
        }

        //Dias Habiles / No habiles disponibles (sobre el maximo por periodo)
        $totalDisponible['habil'] = ($this->maxDiasHabiles * count($formatPeriodos)) - $totalDisponible['habil'];
        $totalDisponible['no_habil'] = ($this->maxDiasNoHabiles * count($formatPeriodos)) - $totalDisponible['no_habil'];

        return $totalDisponible;
    }
    private function _getRangeDates($fechaInicio, $fechaFin)
    {
        $begin = new DateTime($fechaInicio);
        $end = new DateTime($fechaFin);
        $end = $end->modify('+1 day');

        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($begin, $interval, $end);

        $range = [];
        foreach ($dateRange as $date) {
            $range[] = $date;
        }
        return $range;
    }
    public function _sumarDiasPorTipo($fechaInicio, $fechaFin)
    {
        $response = array('habil' => 0, 'no_habil' => 0);

        $begin = new DateTime($fechaInicio);
        $end = new DateTime($fechaFin);
        $end = $end->modify('+1 day');

        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($begin, $interval, $end);
        $diasSemanaNoLaborable = $this->_getFormatDiasSemanaNoLaborable();

        foreach ($dateRange as $date) {
            if (in_array($date->format('N'), $diasSemanaNoLaborable)) {
                $response['no_habil']++;
            } else {
                $response['habil']++;
            }
        }

        return $response;
    }
    private function _getFormatDiasSemanaNoLaborable()
    {
        $arrIndicesNoLaborables = array();
        $diasNoLaborables = $this->_getDiasSemanaNoLaborables();

        foreach ($diasNoLaborables as $row) {
            array_push($arrIndicesNoLaborables, $row->indice);
        }

        return $arrIndicesNoLaborables;
    }
    private function _getDiasSemanaNoLaborables()
    {
        $this->intra_db->usarUTF8();
        $this->intra_db->setCampos('indice, dia_nombre');
        $this->intra_db->setTabla('TBINT_DIAS_SEMANA');
        $this->intra_db->setCondicion("=", "laborable", 0);
        $this->intra_db->setOrden('indice');
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }
    private function _getDetallePeriodos($cod_trab, $cod_empr, $fecha_corte, $truncas)
    {
        $params = array(
            array($cod_trab, SQLSRV_PARAM_IN),
            array($cod_empr, SQLSRV_PARAM_IN),
            array($fecha_corte, SQLSRV_PARAM_IN),
            array($truncas, SQLSRV_PARAM_IN)
        );

        $sqlQuery = "{CALL USP_VACA_PERIODO(?,?,?,?)}";
        $qryResult = $this->intra_db->CallSPWithResult($sqlQuery, $params);
        return $qryResult;
    }
    private function _formatPeriodosByCondicion($periodos, $condicion)
    {
        $arrPeriodos = array();
        $estado = ($condicion == '1') ? array('Pendiente', 'Vencido') : array('No Disponible');

        foreach ($periodos as $key => $periodo) {
            if (in_array($periodo->ESTADO, $estado)) {
                $arrPeriodos[] = (array) $periodo;
            }
        }

        //Reorganizar
        usort($arrPeriodos, function ($a, $b) {
            return $a['PE_VACA'] <=> $b['PE_VACA'];
        });

        return $arrPeriodos;
    }
    private function _getDetalleVacaciones($cod_trab, $cod_empr, $periodo, $fecha_corte)
    {
        $this->intra_db->usarUTF8();
        $this->intra_db->setCampos("SUM(NRO_DIAS) 'NRO_DIAS', SUM(NRO_DIAS - dbo.FUNC_NUM_DAYS_NO_HABIL(CO_EMPR,CO_SEDE,FECHA_INICIAL,FECHA_FINAL)) AS HABIL, SUM(dbo.FUNC_NUM_DAYS_NO_HABIL(CO_EMPR,CO_SEDE,FECHA_INICIAL,FECHA_FINAL)) AS NO_HABIL");
        $this->intra_db->setTabla("VW_OFI_VACACIONES");
        $this->intra_db->setCondicion("=", "CO_TRAB", $cod_trab);
        $this->intra_db->setCondicion("=", "CO_EMPR", $cod_empr);
        $this->intra_db->setCondicion("=", "PERIODO_VACACIONAL", $periodo);
        $this->intra_db->setCondicion("<=", "FECHA_INICIAL", $fecha_corte);
        $this->intra_db->setGrupo("PERIODO_VACACIONAL");
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
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
    private function _getDistribucionActiva($periodo, $solicitante, $idVacacion = 0)
    {
        $this->intra_db->setCampos("periodo, id_vaca_condicion, SUM(num_dias_total) 'total', SUM(num_dias_habil) 'habil', SUM(num_dias_no_habil) 'no_habil'");
        $this->intra_db->setTabla(array("V" => "TBINT_VACACIONES"));
        $this->intra_db->setJoin(array("VD" => "TBINT_VACA_DISTRIBUCION"), "VD.id_vacacion = V.id_vacacion", "INNER");
        $this->intra_db->setCondicion("=", "periodo", $periodo);
        $this->intra_db->setCondicion("=", "id_solicitante", $solicitante);

        if ($idVacacion) {
            $this->intra_db->setCondicion("<>", "V.id_vacacion", $idVacacion);
        }

        $this->intra_db->setCondicionString(' id_vaca_estado IN (1,2,3,4)');
        $this->intra_db->setCondicion("=", "eliminado", 0);
        $this->intra_db->setGrupo('periodo, id_vaca_condicion');
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }
    private function _proccessMailMaster($regVacacion)
    {
        $config = Config::singleton();
        $mensaje = '';
        $asunto = 'Sistema Intranet - Generación Vacación Master';

        $destinatarios = $this->_getNotificados(2, 1, $regVacacion[0]->id_unidad);
        $copias = $this->_getNotificados(2, 2, $regVacacion[0]->id_unidad);
        $para = $this->_convertCadena($destinatarios);
        $copia = $this->_convertCadena($copias);

        if ($config->get('env') !== 'prod') {
            $asunto .= ' - ' . strtoupper($config->get('env'));
            $mensaje .= '<div style="font-style: italic; background-color: #d9edf7; color:#31708f; border-color: #bce8f1; border: 1px solid; padding: 4px;" >Este correo es generado debido a las pruebas que se encuentran realizando en este sistema, por favor ignorar su contenido; de encontrarse en PRODUCCION este correo se enviaría a: ' . $para . ', con copia a: ' . $copia . '</div><br/>';
            $para = $config->get('mailDev');
            $copia = '';
        }

        $tabla = '<table border="1" cellpadding="5" cellspacing="5" style="border-collapse: collapse;">
        <tr>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">DNI</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">SOLICITANTE</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">CONDICION</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">FECHA INICIO</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">FECHA FIN</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">NUMERO DIAS</span></b></th>
        </tr>
        <tr>
        <td style="font-size: 12px">' . $regVacacion[0]->dni . '</td>
        <td style="font-size: 12px">' . $regVacacion[0]->solicitante . '</td>
        <td style="font-size: 12px">' . $regVacacion[0]->vaca_condicion . '</td>
        <td style="font-size: 12px">' . $regVacacion[0]->fecha_inicio->format('Y-m-d') . '</td>
        <td style="font-size: 12px">' . $regVacacion[0]->fecha_fin->format('Y-m-d') . '</td>
        <td style="font-size: 12px">' . $regVacacion[0]->num_dias . '</td>
        </tr>
        </table>';

        $mensaje .= 'Estimados(as). :
        <br/><br/>
        Se informa que se registro la siguiente solicitud de vacaciones completamente liberada. ' . $tabla . '
        <br/>
        Atte.
        <br/>
        Administrador de Software.
        <br/>
        Sistema Intranet.';

        $this->_sendEmail($para, $copia, $asunto, $mensaje);
    }
    private function _getNotificados($accion, $envio, $idGerencia)
    {
        $this->intra_db->setCampos('C.USUINIDUSUARIO, S.USUVCNOUSUARIO, S.USUVCTXMAIL, C.tipoEnvio, CO_UNID');
        $this->intra_db->setTabla(array('C' => 'TBINT_NOT_CONFIG'));
        $this->intra_db->setJoin(array('S' => 'VW_SEG_USUARIOS'), 'S.USUINIDUSUARIO = C.USUINIDUSUARIO', 'INNER');
        $this->intra_db->setJoin(array('U' => 'VW_OFI_PERFIL'), 'U.CO_TRAB collate Modern_Spanish_CI_AS = S.USUCHCDUSUARIO');
        $this->intra_db->setCondicion('=', 'idAccion', $accion);
        //$this->intra_db->setCondicion('=','id_solicitante',$solicitante);
        $this->intra_db->setCondicion('=', 'tipoEnvio', $envio);
        $this->intra_db->setCondicionString(" (CO_UNID = '" . $idGerencia . "' OR C.todas_gerencias = 1)");
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }
    private function _convertCadena($reg)
    {
        $cadena = '';
        $separador = '';

        if (!empty($reg)) {
            foreach ($reg as $row) {
                $cadena .= $separador . $row->USUVCTXMAIL;
                $separador = ';';
            }
        }

        return $cadena;
    }
    private function _sendEmail($para, $copia, $asunto, $mensaje)
    {
        $params = array(
            array($para, SQLSRV_PARAM_IN),
            array($copia, SQLSRV_PARAM_IN),
            array($asunto, SQLSRV_PARAM_IN),
            array($mensaje, SQLSRV_PARAM_IN)
        );

        $sqlQuery = "{CALL TES_ENV_CORREO_DESARROLLO3(?,?,?,?)}";
        $this->envc_db->CallSP($sqlQuery, $params);
    }

    /********************************************* MODIFICACION DE LA SOLICITUD DE VACACIONES ***********************************/

    public function editVacacion($reg)
    {
        $resultado = array('status' => false, 'mensaje' => '');
        $tblConsolidado = (array) json_decode($reg['tblConsolidado']);

        // 22/06/2020 no se concidera la distribucion (todo se realizara en dias sin conciderara habiles y no habiles)
        /*$distribucion = $this->_armarDistribucion($reg['cboSolicitante'],$tblConsolidado['trunco'],$reg['cboCondicion'],$reg['txtFechaInicio'],$reg['txtFechaFin'],$reg['idSolicitud']);

        if(!$distribucion['status']){
            $resultado['mensaje'] = $distribucion['mensaje'];
            return $resultado;
        }*/

        //Iniciamos la Conexión y la transacción
        $this->getInstaceTransac();
        $this->intra_trans->Conectar(true);
        $this->intra_trans->iniciarTransaccion();

        $this->idVacacion = $reg['idSolicitud'];
        $this->_modificarSolicitudVacacion($reg, $reg['modalidad']);
        //$this->_eliminarDistribucionActual($this->idVacacion);
        //$this->_insertarDistribucion($this->idVacacion,$distribucion['resultado']);
        $this->_eliminarAutorizaciones($this->idVacacion);

        $this->intra_trans->commitTransaccion();
        $this->intra_trans->Desconectar();

        $resultado['status'] = true;
        $resultado['id'] = $this->idVacacion;
        return $resultado;
    }
    private function _modificarSolicitudVacacion($reg, $modalidad = 1)
    {
        $regSolicitud = $this->getInfoVacacion($reg['idSolicitud']);
        $arrEstadosToReset = array(3, 4);
        $estado = $regSolicitud[0]->id_vaca_estado;
        $tipo = 0;

        //Volver a primera aprobación, si editan una solicitud ya aprobada.  (excepto al ser master)
        if (in_array($regSolicitud[0]->id_vaca_estado, $arrEstadosToReset) && empty($reg['master'])) {
            $estado = 2;
        }

        switch ($modalidad) {
            case '1':
                //PROPIO
                $userInfo = $this->sessionObj->getUserInfo();
                $userInfoOfisis = $this->sessionObj->getInfoFromOfisis();
                $tipo = 1;
                //$confirmado = 0;
                //$estado = 0;
                //$idGenerador = $userInfo[0]->ID_USUARIO;
                break;
            case '2':
                // SE HACE X EMERGENCIA (ESPECIAL)
                $userInfo = $this->sessionObj->getUserInfo($reg['cboSolicitante']);
                $userInfoOfisis = $this->_getUserFromOfisis($userInfo[0]->USUARIO);
                $tipo = 2;
                //$confirmado = 1;
                //$estado = 4;

                //$infoGenerador = $this->sessionObj->getUserInfo();
                //$idGenerador = $infoGenerador[0]->ID_USUARIO;
                break;
            case '3':
                // Lo HACE UN GENERADOR 
                $userInfo = $this->sessionObj->getUserInfo($reg['cboSolicitante']);
                $userInfoOfisis = $this->_getUserFromOfisis($userInfo[0]->USUARIO);
                $tipo = 1;
                //$confirmado = 0;
                //$estado = 0;

                //$infoGenerador = $this->sessionObj->getUserInfo();
                //$idGenerador = $infoGenerador[0]->ID_USUARIO;
                break;
        }

        $infoSucursal = $this->sessionObj->getInfoFromSucursalByEmpresa($userInfoOfisis[0]->CO_EMPR, 1);

        $sqlQuery = "UPDATE TBINT_VACACIONES SET
        [id_vaca_condicion] = " . $reg['cboCondicion'] . ",
        [fecha_inicio] = '" . $reg['txtFechaInicio'] . "',
        [fecha_fin] = '" . $reg['txtFechaFin'] . "',
        [num_dias] = " . $reg['txtCantidadDias'] . ",
        [idTipo] = $tipo,
        [id_vaca_estado] = $estado,
        [usu_modi] = " . $userInfo[0]->ID_USUARIO . ",
        [fecha_modi] = GETDATE()
        WHERE  id_vacacion = " . $reg['idSolicitud'] . ";";

        return $this->intra_trans->DoUpdate($sqlQuery);
    }
    private function _eliminarDistribucionActual($idSolicitud)
    {
        $sqlQuery = "DELETE FROM TBINT_VACA_DISTRIBUCION WHERE id_vacacion = $idSolicitud;";
        $this->intra_trans->Ejecutar($sqlQuery);
    }
    private function _eliminarAutorizaciones($idSolicitud)
    {
        $sqlQuery = "DELETE FROM TBINT_VACA_AUT WHERE id_vacacion = $idSolicitud;";
        $this->intra_trans->Ejecutar($sqlQuery);
    }
    /****************************************** CONFIRMACION Y AUTORIZACIONES  **************************************************/
    public function confirmSolicitudVacacion($idVacacion)
    {
        $rsp_id = 0;
        $rsp_id_autorizador = 0;
        $rsp_estado = 0;
        $rsp_mensaje = '';

        $params = array(
            array($idVacacion, SQLSRV_PARAM_IN),
            array(&$rsp_id, SQLSRV_PARAM_OUT),
            array(&$rsp_id_autorizador, SQLSRV_PARAM_OUT),
            array(&$rsp_estado, SQLSRV_PARAM_OUT),
            array(&$rsp_mensaje, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(200))
        );

        $sqlQuery = "{CALL USP_VACA_SOLICITUD_CONFIRMAR(?,?,?,?,?)}";
        $this->intra_db->CallSP($sqlQuery, $params);

        $response['idSolicitudAutorizacion'] = $rsp_id;
        $response['NumError'] = $rsp_estado;
        $response['MsgError'] = $rsp_mensaje;
        $response['idAuth'] = $rsp_id_autorizador;
        return $response;
    }
    public function getDiasEliminar()
    {

        $this->intra_db->setCampos('valor');
        $this->intra_db->setTabla("TBINT_VACA_CONFIG");
        $this->intra_db->setCondicion("like", "configuracion", "%eliminar%");
        $qryResult = $this->intra_db->Listar();

        return $qryResult;
    }
    // public function eliminarLogicamente($idVacacion){
    //     $userInfo = $this->sessionObj->getUserInfo();
    //     $sqlQuery = "UPDATE TBINT_VACACIONES SET
    //     [eliminado] = 1,
    //     [usu_elim] = ".$userInfo[0]->ID_USUARIO.",
    //     [fecha_elim] = GETDATE()
    //     WHERE  id_vacacion = {$idVacacion};";
    //     return $this->intra_db->DoUpdate($sqlQuery);
    // }

    public function eliminarLogicamente($idVacacion)
    {
        $userInfo = $this->sessionObj->getUserInfo();
        $usuario = $userInfo[0]->ID_USUARIO;
        $params = array(
            array($idVacacion, SQLSRV_PARAM_IN),
            array($usuario, SQLSRV_PARAM_IN)
        );

        $sqlQuery = "{CALL SPU_ELIMINA_VACACION_PERIODO(?,?)}";
        $this->intra_db->CallSP($sqlQuery, $params);
    }
    public function eliminarLogicoRange()
    {
        $userInfo = $this->sessionObj->getUserInfo();
        $sqlQuery = "UPDATE TBINT_VACACIONES SET
        [eliminado] = 1,
        [usu_elim] = " . $userInfo[0]->ID_USUARIO . ",
        [fecha_elim] = GETDATE()
        WHERE  [fecha_inicio] >= '" . date('Y-m-d') . "';";
        return $this->intra_db->DoUpdate($sqlQuery);
    }
    public function confirmEjecuciónVacacion($idVacacion)
    {
        $userInfo = $this->sessionObj->getUserInfo();
        $sqlQuery = "UPDATE TBINT_VACACIONES SET
        [id_vaca_estado] = 5,
        [usu_modi] = " . $userInfo[0]->ID_USUARIO . ",
        [fecha_modi] = GETDATE()
        WHERE  id_vacacion = {$idVacacion};";
        return $this->intra_db->DoUpdate($sqlQuery);
    }
    public function getPendientesEjecucion($idGerencia = 0)
    {
        $this->intra_db->setCampos('id_unidad,gerencia,area,seccion,dni,id_solicitante,solicitante,vaca_condicion,fecha_inicio,fecha_fin,num_dias');
        $this->intra_db->setTabla("VW_VACACIONES");
        $this->intra_db->setCondicion("=", "id_vaca_estado", 4);
        $this->intra_db->setCondicionString('fecha_fin < CAST(GETDATE() AS DATE)');
        if ($idGerencia) {
            $this->intra_db->setCondicion("=", "id_unidad", $idGerencia);
        }
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }
    public function proccessMailPendienteEjecucion($regVacacion)
    {
        $config = Config::singleton();
        $mensaje = '';
        $asunto = 'Sistema Intranet - Vacaciones pendientes de confirmación';

        $userInfo = $this->sessionObj->getUserInfo($regVacacion->id_solicitante);
        $para = $userInfo[0]->CORREO;

        if ($config->get('env') !== 'prod') {
            $asunto .= ' - ' . strtoupper($config->get('env'));
            $mensaje .= '<div style="font-style: italic; background-color: #d9edf7; color:#31708f; border-color: #bce8f1; border: 1px solid; padding: 4px;" >Este correo es generado debido a las pruebas que se encuentran realizando en este sistema, por favor ignorar su contenido; de encontrarse en PRODUCCION este correo se enviaría a: ' . $para . '</div><br/>';
            $para = $config->get('mailDev');
        }

        $tabla = '<table border="1" cellpadding="5" cellspacing="5" style="border-collapse: collapse;">
        <tr>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">DNI</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">SOLICITANTE</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">CONDICION</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">FECHA INICIO</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">FECHA FIN</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">NUMERO DIAS</span></b></th>
        </tr>
        <tr>
        <td style="font-size: 12px">' . $regVacacion->dni . '</td>
        <td style="font-size: 12px">' . $regVacacion->solicitante . '</td>
        <td style="font-size: 12px">' . $regVacacion->vaca_condicion . '</td>
        <td style="font-size: 12px">' . $regVacacion->fecha_inicio->format('Y-m-d') . '</td>
        <td style="font-size: 12px">' . $regVacacion->fecha_fin->format('Y-m-d') . '</td>
        <td style="font-size: 12px">' . $regVacacion->num_dias . '</td>
        </tr>
        </table>';

        $mensaje .= 'Estimados(as). : <br/><br/>
        Se informa que las siguientes solicitudes de vacaciones aun no las confirma como ejecutadas, por favor dirigirse al modulo de vacaciones y confirme su ejecución: ' . $tabla . ' <br/>
        Atte. <br/>
        Administrador de Software. <br/>
        Sistema Intranet.';

        $this->_sendEmail($para, '', $asunto, $mensaje);
    }
    public function processMailPendienteEjecucionGerencia($solicitudes)
    {
        $config = Config::singleton();
        $mensaje = '';
        $asunto = 'Sistema Intranet - Vacaciones pendientes de confirmación';

        $destinatarios = $this->_getNotificados(2, 1, $solicitudes[0]->id_unidad);
        $copias = $this->_getNotificados(2, 2, $solicitudes[0]->id_unidad);
        $para = $this->_convertCadena($destinatarios);
        $copia = $this->_convertCadena($copias);

        if ($config->get('env') !== 'prod') {
            $asunto .= ' - ' . strtoupper($config->get('env'));
            $mensaje .= '<div style="font-style: italic; background-color: #d9edf7; color:#31708f; border-color: #bce8f1; border: 1px solid; padding: 4px;" >Este correo es generado debido a las pruebas que se encuentran realizando en este sistema, por favor ignorar su contenido; de encontrarse en PRODUCCION este correo se enviaría a: ' . $para . ', con copia a: ' . $copia . '</div><br/>';
            $para = $config->get('mailDev');
            $copia = '';
        }

        $tabla = '<table border="1" cellpadding="5" cellspacing="5" style="border-collapse: collapse;">
        <tr>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">GERENCIA</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">AREA</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">SECCION</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">DNI</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">SOLICITANTE</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">CONDICION</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">FECHA INICIO</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">FECHA FIN</span></b></th>
        <th style="background-color: #b23535"><b><span style="color:#ffffff;">NUMERO DIAS</span></b></th>
        </tr>';

        foreach ($solicitudes as $row) {
            $tabla .= '<tr>
            <td style="font-size: 12px">' . $row->gerencia . '</td>
            <td style="font-size: 12px">' . $row->area . '</td>
            <td style="font-size: 12px">' . $row->seccion . '</td>
            <td style="font-size: 12px">' . $row->dni . '</td>
            <td style="font-size: 12px">' . $row->solicitante . '</td>
            <td style="font-size: 12px">' . $row->vaca_condicion . '</td>
            <td style="font-size: 12px">' . $row->fecha_inicio->format('Y-m-d') . '</td>
            <td style="font-size: 12px">' . $row->fecha_fin->format('Y-m-d') . '</td>
            <td style="font-size: 12px">' . $row->num_dias . '</td>
            </tr>';
        }
        $tabla .= '</table>';

        $mensaje .= 'Estimados(as). :
        <br/><br/>
        Se informa que las siguientes solicitudes de vacaciones aun no han sido confirmadas su ejecución. ' . $tabla . '
        <br/>
        Atte.
        <br/>
        Administrador de Software.
        <br/>
        Sistema Intranet.';

        $this->_sendEmail($para, $copia, $asunto, $mensaje);
    }
    public function listCronograma($qry_empresa, $qry_gerencia, $qryDepartamento, $qry_area, $qry_seccion, $qry_estado, $qry_colaborador, $qry_ini_rango, $qry_fin_rango, $dnis = '')
    {
        $colaborador = '';
        $estado = '';

        if (strlen($qry_colaborador) > 0) {
            $colaborador .= ' AND ' . $this->getProccessGroup($qry_colaborador, 'solicitante', 'like', 'or');
        }

        if (strlen($qry_estado) > 0) {
            //$estado .= " AND v.id_vaca_estado in (".$qry_estado.")";
            $estado .= $qry_estado;
        }

        $params = array(
            array($qry_empresa, SQLSRV_PARAM_IN),
            array($qry_gerencia, SQLSRV_PARAM_IN),
            array($qryDepartamento, SQLSRV_PARAM_IN),
            array($qry_area, SQLSRV_PARAM_IN),
            array($qry_seccion, SQLSRV_PARAM_IN),
            array($estado, SQLSRV_PARAM_IN),
            array($colaborador, SQLSRV_PARAM_IN),
            array($dnis, SQLSRV_PARAM_IN),
            array($qry_ini_rango, SQLSRV_PARAM_IN),
            array($qry_fin_rango, SQLSRV_PARAM_IN),
        );

        $sqlQuery = "{CALL USP_VACA_CRONOGRAMA(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}";
        $demo = $this->intra_db->CallSPWithResult($sqlQuery, $params);
        return $demo;
    }
    public function procesarSincronizacion()
    {
        $incidencias = array();

        // ACTIVOS
        $vacaOfisis = $this->_obtenerVacacionesActuales();

        foreach ($vacaOfisis as $vacacion) {
            $solicitud = $this->getInfoVacacion(trim($vacacion->DE_OBSE));
            if (!empty($solicitud) && $solicitud[0]->id_vaca_estado != 5) {
                if ($solicitud[0]->dni == $vacacion->CO_TRAB) {

                    if ($vacacion->FE_INIC_VACA->format('d/m/Y') != $solicitud[0]->fecha_inicio->format('d/m/Y') || $vacacion->FE_FINA_VACA->format('d/m/Y') != $solicitud[0]->fecha_fin->format('d/m/Y')) {
                        $this->_actualizarInicioFinSolicitud($vacacion->DE_OBSE, $vacacion->FE_INIC_VACA, $vacacion->FE_FINA_VACA);
                    }

                    $this->_actualizarEstadoFinal($vacacion->DE_OBSE);
                } else {
                    $incidencias[] = array(
                        'codigo' => $vacacion->DE_OBSE,
                        'colaborador' => $vacacion->CO_TRAB,
                        'fecha_inicio' => $vacacion->FE_INIC_VACA->format('d/m/Y'),
                        'fecha_fin' => $vacacion->FE_FINA_VACA->format('d/m/Y'),
                        'mensaje' => 'La vacación no corresponde al código del trabajador'
                    );
                }
            } else {
                $incidencias[] = array(
                    'codigo' => $vacacion->DE_OBSE,
                    'colaborador' => $vacacion->CO_TRAB,
                    'fecha_inicio' => $vacacion->FE_INIC_VACA->format('d/m/Y'),
                    'fecha_fin' => $vacacion->FE_FINA_VACA->format('d/m/Y'),
                    'mensaje' => 'No se encuentra el código de solicitud en Intranet'
                );
            }
        }

        //VACACIONES SIN CODIGO SOLICITUD
        $vacaSolas = $this->_obtenerVacacionesSinCodigo();
        foreach ($vacaSolas as $vaca) {
            $incidencias[] = array(
                'codigo' => $vaca->DE_OBSE,
                'colaborador' => $vaca->CO_TRAB,
                'fecha_inicio' => $vacacion->FE_INIC_VACA->format('d/m/Y'),
                'fecha_fin' => $vacacion->FE_FINA_VACA->format('d/m/Y'),
                'mensaje' => 'La vacación no posee un código de solicitud'
            );
        }

        //Notificar Incidencias
        if (!empty($incidencias)) {
            $this->_notificarIncidencias($incidencias);
        }
    }
    private function _obtenerVacacionesActuales()
    {
        $this->ofisis_db->usarUTF8();
        $this->ofisis_db->setCampos('CO_TRAB, DE_OBSE, MIN(FE_INIC_VACA) FE_INIC_VACA, MAX(FE_FINA_VACA) FE_FINA_VACA, COUNT(*) CANTIDAD');
        $this->ofisis_db->setTabla('TDVACA');
        $this->ofisis_db->setCondicionString("(CAST(FE_USUA_CREA AS DATE) = CAST(GETDATE() AS DATE) OR CAST(FE_USUA_MODI AS DATE) = CAST(GETDATE() AS DATE)) AND ISNUMERIC(REPLACE(REPLACE(DE_OBSE, CHAR(13), ''), CHAR(10), '')) = 1");
        $this->ofisis_db->setGrupo("CO_TRAB, DE_OBSE");
        $this->ofisis_db->setOrden("CO_TRAB");
        $qryResult = $this->ofisis_db->Listar();
        return $qryResult;
    }
    private function _obtenerVacacionesSinCodigo()
    {
        $this->ofisis_db->usarUTF8();
        $this->ofisis_db->setCampos('CO_TRAB, DE_OBSE, MIN(FE_INIC_VACA) FE_INIC_VACA, MAX(FE_FINA_VACA) FE_FINA_VACA');
        $this->ofisis_db->setTabla('TDVACA');
        $this->ofisis_db->setCondicionString("(CAST(FE_USUA_CREA AS DATE) = CAST(GETDATE() AS DATE) OR CAST(FE_USUA_MODI AS DATE) = CAST(GETDATE() AS DATE)) AND ISNUMERIC(REPLACE(REPLACE(DE_OBSE, CHAR(13), ''), CHAR(10), '')) = 0");
        $this->ofisis_db->setGrupo("CO_TRAB, DE_OBSE");
        $this->ofisis_db->setOrden("CO_TRAB");
        $qryResult = $this->ofisis_db->Listar();
        return $qryResult;
    }
    private function _actualizarInicioFinSolicitud($idSolicitud, $inicio, $fin)
    {
        $sqlQuery = "UPDATE TBINT_VACACIONES SET
        [fecha_inicio] = '" . $inicio->format('Y-m-d') . "',
        [fecha_fin] = '" . $fin->format('Y-m-d') . "',
        [usu_modi] = 1,
        [fecha_modi] = GETDATE()
        WHERE  id_vacacion = " . $idSolicitud . ";";
        return $this->intra_db->DoUpdate($sqlQuery);
    }
    private function _actualizarEstadoFinal($idSolicitud)
    {
        $sqlQuery = "UPDATE TBINT_VACACIONES SET
        [id_vaca_estado] = 5,
        [usu_modi] = 1,
        [fecha_modi] = GETDATE()
        WHERE  id_vacacion = " . $idSolicitud . ";";
        return $this->intra_db->DoUpdate($sqlQuery);
    }
    private function _notificarIncidencias($incidencias)
    {
        $config = Config::singleton();
        $asunto = 'Sistema Intranet - Sincronización Vacaciones';
        $destinatarios = $this->_getNotificacion(_notificacion_vacacion_, 1);
        $copias = $this->_getNotificacion(_notificacion_vacacion_, 2);

        $para = $this->_convertCadenaNotificadores($destinatarios);
        $copia = $this->_convertCadenaNotificadores($copias);
        $mensaje = '';

        if ($config->get('env') !== 'prod') {
            $asunto .= ' (' . strtoupper($config->get('env')) . ')';
            $mensaje .= '<div style="font-style: italic; background-color: #d9edf7; color:#31708f; border-color: #bce8f1; border: 1px solid; padding: 4px;" >Este correo es generado debido a las pruebas que se encuentran realizando en este sistema, por favor ignorar su contenido; de encontrarse en PRODUCCION este correo se enviaría a: ' . $para . ' con copia a: ' . $copia . '</div><br/>';
            $para = $config->get('mailDev');
            $copia = '';
        }

        $tabla = '<table border="1" cellpadding="5" cellspacing="5" style="border-collapse: collapse;">
                    <tr>
                        <th style="background-color: #b23535"><b><span style="color:#ffffff;">SOLICITUD</span></b></th>
                        <th style="background-color: #b23535"><b><span style="color:#ffffff;">COD TRAB</span></b></th>
                        <th style="background-color: #b23535"><b><span style="color:#ffffff;">FECHA INICIO</span></b></th>
                        <th style="background-color: #b23535"><b><span style="color:#ffffff;">FECHA FIN</span></b></th>
                        <th style="background-color: #b23535"><b><span style="color:#ffffff;">MENSAJE</span></b></th>
                    </tr>';
        foreach ($incidencias as $row) {
            $tabla .= '<tr>
                        <td style="font-size: 12px">' . $row['codigo'] . '</td>
                        <td style="font-size: 12px">' . $row['colaborador'] . '</td>
                        <td style="font-size: 12px">' . $row['fecha_inicio'] . '</td>
                        <td style="font-size: 12px">' . $row['fecha_fin'] . '</td>
                        <td style="font-size: 12px">' . $row['mensaje'] . '</td>
                    </tr>';
        }
        $tabla .= '</table>';

        $mensaje .= 'Estimad@s: <br/>
                Se presentaron las siguiente incidencias en el proceso de <b>sincronización estado vacaciones</b> el día de hoy: ' . $tabla . '
                <br/>
                Atte.
                <br/>
                Administrador de Software.<br/>
                Sistema Intranet.<br/>';

        $this->_sendEmail($para, $copia, $asunto, $mensaje);
    }
    private function _getNotificacion($accion, $envio)
    {
        $this->intra_db->usarUTF8();
        $this->intra_db->setCampos("idNotificacion, accion, tipoEnvio, NO_DIRE_MAI2");
        $this->intra_db->setTabla("VW_NOT_NOTIFICACION");
        $this->intra_db->setCondicionExpr("=", "estado", 1);
        $this->intra_db->setCondicionExpr("=", "accion", "'$accion'");
        $this->intra_db->setCondicionExpr("=", "tipoEnvio", $envio);
        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }
    private function _convertCadenaNotificadores($reg)
    {
        $cadena = '';
        $separador = '';

        if (!empty($reg)) {
            foreach ($reg as $row) {
                $cadena .= $separador . $row->NO_DIRE_MAI2;
                $separador = ';';
            }
        }

        return $cadena;
    }
    public function listarCondicionComboEspecial($id_solicitante)
    {
        $this->intra_db->usarUTF8();

        $query = "SELECT CASE WHEN EXISTS (
                    SELECT 1 FROM TBINT_VACACIONES_TEMP 
                    WHERE id_solicitante = '$id_solicitante' AND eliminado <> 1
                  ) THEN 1 ELSE 0 END AS tiene_registros";

        $resultado = $this->intra_db->Consulta($query);
        $tiene_registros = (is_object($resultado[0])) ?
            $resultado[0]->tiene_registros :
            $resultado[0]['tiene_registros'];

        if ($tiene_registros) {
            $sql = "SELECT id_vaca_condicion, vaca_condicion 
                    FROM TBINT_VACA_CONDICION 
                    WHERE activo = 1";
        } else {
            $sql = "SELECT TOP 2 id_vaca_condicion, vaca_condicion 
                    FROM TBINT_VACA_CONDICION 
                    WHERE activo = 1 
                    ORDER BY id_vaca_condicion ASC";
        }

        return $this->intra_db->Consulta($sql);
    }
    public function getFechaEspecial($fechaInicio, $fechaFin, $idSolicitante)
    {
        $this->intra_db->usarUTF8();
        $this->intra_db->setCampos("id_vaca_especial");
        $this->intra_db->setTabla("TBINT_VACACIONES_TEMP");
        $this->intra_db->setCondicionExpr("=", "id_solicitante", $idSolicitante);
        $this->intra_db->setCondicionExpr("=", "fecha_inicio", "'$fechaInicio'");
        $this->intra_db->setCondicionExpr("=", "fecha_fin", "'$fechaFin'");

        $qryResult = $this->intra_db->Listar();
        return $qryResult;
    }
    // public function _obtenerPeriodoActual($dni)
    // {
    //     // Preparar variables
    //     $fechaCorte = date('Y-m-d');
    //     $filtroTrabajador = "AND TE.CO_TRAB IN ('" . $dni . "')";

    //     // Parámetros para el stored procedure
    //     $params = array(
    //         array('REM', SQLSRV_PARAM_IN),              // @ISTI_VACA
    //         array('OFISIS', SQLSRV_PARAM_IN),           // @ISCO_GRUP
    //         array($fechaCorte, SQLSRV_PARAM_IN),        // @IDFE_CORT
    //         array('AND TE.CO_EMPR = 01', SQLSRV_PARAM_IN), // @ISCO_WHE1
    //         array('', SQLSRV_PARAM_IN),                 // @ISCO_WHE2
    //         array($filtroTrabajador, SQLSRV_PARAM_IN),  // @ISCO_WHE3
    //         array('', SQLSRV_PARAM_IN),                 // @ISCO_WHE4
    //         array('', SQLSRV_PARAM_IN),                 // @ISCO_WHE5
    //         array('N', SQLSRV_PARAM_IN)                 // @ISST_VALO_VACA
    //     );

    //     // Ejecutar el stored procedure
    //     $sqlQuery = "{CALL dbo.USP_VACA_INTRANET_MEJORADO(?,?,?,?,?,?,?,?,?)}";
    //     $resultados = $this->intra_db->CallSPWithResult($sqlQuery, $params);

    //     // Variables para almacenar el resultado
    //     $periodoMenor = null;
    //     $infoCompleta = null;

    //     if (!empty($resultados)) {
    //         // Filtrar solo periodos con días pendientes mayores a cero
    //         $periodosConDias = array_filter($resultados, function ($item) {
    //             return floatval($item->DIAS_PEND) > 0;
    //         });

    //         if (!empty($periodosConDias)) {
    //             // Ordenar los periodos por fecha (del más antiguo al más reciente)
    //             usort($periodosConDias, function ($a, $b) {
    //                 return strcmp($a->PERIODO, $b->PERIODO);
    //             });

    //             // El primer periodo después de ordenar será el más antiguo con días disponibles
    //             $periodoMenor = $periodosConDias[0]->PERIODO;
    //             $infoCompleta = $periodosConDias[0];
    //         }
    //     }

    //     // Puedes retornar solo el periodo o un objeto con toda la información
    //     return [
    //         'periodo' => $periodoMenor,
    //         'info' => $infoCompleta
    //     ];
    // }

    public function _obtenerPeriodoActual($dni)
{
    // Preparar variables
    $fechaCorte = date('Y-m-d');
    $filtroTrabajador = "AND TE.CO_TRAB IN ('" . $dni . "')";

    // Parámetros para el stored procedure
    $params = array(
        array('REM', SQLSRV_PARAM_IN),              // @ISTI_VACA
        array('OFISIS', SQLSRV_PARAM_IN),           // @ISCO_GRUP
        array($fechaCorte, SQLSRV_PARAM_IN),        // @IDFE_CORT
        array('AND TE.CO_EMPR = 01', SQLSRV_PARAM_IN), // @ISCO_WHE1
        array('', SQLSRV_PARAM_IN),                 // @ISCO_WHE2
        array($filtroTrabajador, SQLSRV_PARAM_IN),  // @ISCO_WHE3
        array('', SQLSRV_PARAM_IN),                 // @ISCO_WHE4
        array('', SQLSRV_PARAM_IN),                 // @ISCO_WHE5
        array('N', SQLSRV_PARAM_IN)                 // @ISST_VALO_VACA
    );

    // Ejecutar el stored procedure
    $sqlQuery = "{CALL dbo.USP_VACA_INTRANET_MEJORADO(?,?,?,?,?,?,?,?,?)}";
    $resultados = $this->intra_db->CallSPWithResult($sqlQuery, $params);

    // Variables para almacenar el resultado
    $periodoMenor = null;
    $infoCompleta = null;

    if (!empty($resultados)) {
        // Filtrar solo periodos con días pendientes mayores a cero
        $periodosConDias = array_filter($resultados, function ($item) {
            return floatval($item->DIAS_PEND) > 0;
        });

        if (!empty($periodosConDias)) {
            // Ordenar los periodos por fecha (del más antiguo al más reciente)
            usort($periodosConDias, function ($a, $b) {
                return strcmp($a->PERIODO, $b->PERIODO);
            });

            // Evaluar cada periodo para determinar si sus días programados completan los días pendientes
            foreach ($periodosConDias as $periodo) {
                // Consultar días programados para este período
                $diasProgramados = $this->_consultarDiasProgramados($dni, $periodo->PERIODO);

                // Si los días programados son menores a los días pendientes, este es el período actual
                if ($diasProgramados['HABIL'] + $diasProgramados['NO_HABIL'] < $periodo->DIAS_PEND) {
                    $periodoMenor = $periodo->PERIODO;
                    $infoCompleta = $periodo;
                    break;
                }
            }

            // Si no se encontró un período con días incompletos, tomar el primer período
            if ($periodoMenor === null) {
                $periodoMenor = $periodosConDias[0]->PERIODO;
                $infoCompleta = $periodosConDias[0];
            }
        }
    }

    // Retornar el período y su información
    return [
        'periodo' => $periodoMenor,
        'info' => $infoCompleta
    ];
}

// Método auxiliar para consultar días programados
private function _consultarDiasProgramados($dni, $periodo)
{
    $this->intra_db->setCampos("sum(num_dias - dbo.FUNC_NUM_DAYS_NO_HABIL(id_empresa,id_sucursal,fecha_inicio,fecha_fin)) AS HABIL, sum(dbo.FUNC_NUM_DAYS_NO_HABIL(id_empresa,id_sucursal,fecha_inicio,fecha_fin)) AS NO_HABIL");
    $this->intra_db->setTabla("TBINT_VACACIONES");
    $this->intra_db->setCondicionString("id_solicitante = (SELECT USUINIDUSUARIO FROM [DBACINHOUSE_TEST].[dbo].[TBSEGMAEUSUARIO] WHERE USUVCDNI = '$dni') AND periodo = '$periodo' AND id_empresa = '01' AND id_sucursal = '01' AND eliminado = 0 AND subperiodo in (1,2)");
    
    $resultado = $this->intra_db->Listar();

    // Si no hay resultado, devolver un array con ceros
    if (empty($resultado)) {
        return [
            'HABIL' => 0, 
            'NO_HABIL' => 0
        ];
    }

    // Devolver el primer resultado
    return [
        'HABIL' => $resultado[0]->HABIL ?? 0, 
        'NO_HABIL' => $resultado[0]->NO_HABIL ?? 0
    ];
}


    public function _obtenerCantidadDiasPeriodo($idSolicitante)
    {
        $this->intra_db->setCampos("TOP 1 cantidad_dias");
        $this->intra_db->setTabla("TBINT_VACA_DISTRIBUCION_PERIODO");
        $this->intra_db->setCondicion("=", "id_solicitante", $idSolicitante);
        $this->intra_db->setCondicionString('(subperiodo_uno + subperiodo_dos) < cantidad_dias');
        $this->intra_db->setOrden("periodo ASC");
        $qryResult = $this->intra_db->Listar();

        return $qryResult;
    }
    public function _obtenerDiasConsumidosPeriodo($idSolicitante, $periodo, $subPeriodo)
    {
        $this->intra_db->setCampos("isnull(sum(num_dias - dbo.FUNC_NUM_DAYS_NO_HABIL(id_empresa,id_sucursal,fecha_inicio,fecha_fin)),0) AS HABIL,isnull(sum(dbo.FUNC_NUM_DAYS_NO_HABIL(id_empresa,id_sucursal,fecha_inicio,fecha_fin)),0) AS NO_HABIL");
        $this->intra_db->setTabla("TBINT_VACACIONES");
        $this->intra_db->setCondicion("=", "id_solicitante ", $idSolicitante);
        $this->intra_db->setCondicion("=", "periodo ", $periodo);
        $this->intra_db->setCondicion("=", "subperiodo", $subPeriodo);
        $this->intra_db->setCondicion("=", "eliminado", 0);
        // agrega un group by
        $this->intra_db->setGrupo("periodo, subperiodo");
        $this->intra_db->setCondicionString('id_vaca_estado in (1,2,3,4,5)');
        return $result = $this->intra_db->Listar();
    }
    public function getDiasFlexibles()
    {
        $this->intra_db->setCampos('valor');
        $this->intra_db->setTabla("TBINT_VACA_CONFIG");
        $this->intra_db->setCondicion("like", "configuracion", "%flexibles%");
        $qryResult = $this->intra_db->Listar();

        return $qryResult;
    }
    public function obtenerVacacionesOfisis($idUsuario, $empresa, $periodo)
    {
        $params = array(
            array($idUsuario, SQLSRV_PARAM_IN),
            array($empresa, SQLSRV_PARAM_IN),
            array($periodo, SQLSRV_PARAM_IN)
        );

        $sqlQuery = "{CALL SP_SUMARIO_VACACIONES_EMPLEADO(?,?,?)}";
        $resultado = $this->intra_db->CallSPWithResult($sqlQuery, $params);
        return $resultado;
    }
    public function obtenerVacacionesProgramadas($idSoliciante, $periodo)
    {
        $this->intra_db->setCampos('sum(num_dias - dbo.FUNC_NUM_DAYS_NO_HABIL(id_empresa,id_sucursal,fecha_inicio,fecha_fin)) AS HABIL, sum(dbo.FUNC_NUM_DAYS_NO_HABIL(id_empresa,id_sucursal,fecha_inicio,fecha_fin)) AS NO_HABIL');
        $this->intra_db->setTabla("TBINT_VACACIONES");
        $this->intra_db->setCondicion("=", "id_solicitante", $idSoliciante);
        $this->intra_db->setCondicion("=", "periodo", $periodo);
        $this->intra_db->setCondicion("=", "id_empresa", '01');
        $this->intra_db->setCondicion("=", "id_sucursal", 1);
        $this->intra_db->setCondicion("=", "eliminado", 0);
        $this->intra_db->setCondicionString('subperiodo in (1,2)');
        $qryResult = $this->intra_db->Listar();

        return $qryResult;
    }
    public function determinarYValidarSolicitud($idUsuario, $periodo, $fechaInicio, $fechaFin, $diasSolicitados, $empresa, $condicion = null)
    {
        if ($condicion == 3) {
            $esFechaEpecial = $this->getFechaEspecial($fechaInicio, $fechaFin, $idUsuario);
            if (is_array($esFechaEpecial) && count($esFechaEpecial) > 0) {
                $idVacacionEspecial = $esFechaEpecial[0]->id_vaca_especial ? $esFechaEpecial[0]->id_vaca_especial : null;

                $validacionEspecial = $this->validarVacacionEspecial($idUsuario, $periodo, $fechaInicio, $fechaFin, $empresa);
                // Copiar los valores de la validación especial al resultado
                $resultado['subperiodo'] = $validacionEspecial['subperiodo'];
                $resultado['es_valido'] = $validacionEspecial['es_valido'];
                $resultado['mensaje'] = $validacionEspecial['mensaje'];
                // Añadimos el ID de la vacación especial al resultado
                $resultado['id_vacacion_especial'] = $idVacacionEspecial;

                return $resultado;
            } else {
                $resultado['subperiodo'] = 2;
                $resultado['es_valido'] = false;
                $resultado['mensaje'] = 'No existe la vacación coordinada en este rango de fechas.';
                return $resultado;
            }
        }
        // Validar el subperíodo 1
        $resultado = [
            'subperiodo' => 1, // Valor predeterminado
            'es_valido' => true,
            'mensaje' => '',
        ];
        $validacionSub1 = $this->validarSubperiodo1($idUsuario, $periodo, $empresa, $fechaInicio, $fechaFin);
        $resultado['validacion_sub1'] = $validacionSub1;

        if (isset($validacionSub1['es_valido']) && $validacionSub1['es_valido'] === false) {
            $resultado['es_valido'] = false;
            $resultado['mensaje'] = $validacionSub1['mensaje'];
            return $resultado;
        }

        // Validar si el subperiodo 1 esta completo
        $esSubPeriodoUnoCompleto = isset($validacionSub1['subperiodo_completo']) ? $validacionSub1['subperiodo_completo'] : ($validacionSub1['dias_usados'] >= 15);

        if ($esSubPeriodoUnoCompleto) {
            $resultado['subperiodo'] = 2;
            // aqui debemos de validar el subperido2
            $validacionSubp2 = $this->validarSubperiodo2($idUsuario, $empresa, $periodo, $fechaInicio, $fechaFin);

            // Agregar la validación al resultado
            $resultado['validacion_subp2'] = $validacionSubp2;
            if (!$validacionSubp2['es_valido']) {
                $resultado['es_valido'] = false;
                $resultado['mensaje'] = $validacionSubp2['mensaje'];
            } else {
                $resultado['es_valido'] = true;
                $resultado['mensaje'] = "Solicitud asignada al subperíodo 2.";
            }
        } else {
            // La solicitud va al subperíodo 1
            $resultado['subperiodo'] = 1;

            // Verificar si cumple con el mínimo de días para subperíodo 1
            if ($diasSolicitados < $validacionSub1['siguiente_solicitud_minimo']) {
                $resultado['es_valido'] = false;
                $resultado['mensaje'] = "La solicitud debe ser por un mínimo de {$validacionSub1['siguiente_solicitud_minimo']} días.";
                return $resultado;
            }
            $resultado['mensaje'] = "Solicitud asignada al subperíodo 1.";
        }
        return $resultado;
    }
    public function validarSubperiodo1($idUsuario, $periodo, $empresa, $fechaInicio, $fechaFin)
    {
        // Obtener solicitudes existentes en el subperíodo 1
        $this->intra_db->setCampos("id_vacacion, NUM_DIAS as dias");
        $this->intra_db->setTabla("TBINT_VACACIONES");
        $this->intra_db->setCondicion("=", "periodo", $periodo);
        $this->intra_db->setCondicion("=", "subperiodo", 1);
        $this->intra_db->setCondicion("=", "id_solicitante", $idUsuario);
        $this->intra_db->setCondicion("=", "eliminado", 0);
        $this->intra_db->setCondicionString('id_vaca_estado in (1,2,3,4,5)');
        $this->intra_db->setOrden("id_vacacion ASC");

        $resultadoBD = $this->intra_db->Listar();

        // Inicializar variables para solicitudes en el sistema
        $totalDias = 0;
        $solicitudes = [];

        if (is_array($resultadoBD)) {
            foreach ($resultadoBD as $item) {
                if (is_object($item)) {
                    // Convertir objeto a array para consistencia
                    $solicitud = [
                        'id_vacacion' => $item->id_vacacion,
                        'dias' => $item->dias
                    ];
                    $solicitudes[] = $solicitud;
                    $totalDias += intval($item->dias);
                } elseif (is_array($item)) {
                    $solicitudes[] = $item;
                    $totalDias += intval($item['dias']);
                }
            }
        }

        // Límite teórico del subperíodo 1
        $LIMITE_SUBPERIODO1 = 15;

        // Verificar si el subperíodo 1 está completo (15+ días)
        $subperiodoCompleto = ($totalDias >= $LIMITE_SUBPERIODO1);

        // Obtener días programados internos
        $vacacionesProgramadas = $this->obtenerVacacionesProgramadas($idUsuario, $periodo);
        $diasHabilesProgramados = 0;
        $diasNoHabilesProgramados = 0;
        if (!empty($vacacionesProgramadas) && isset($vacacionesProgramadas[0])) {
            $diasHabilesProgramados = $vacacionesProgramadas[0]->HABIL ?? 0;
            $diasNoHabilesProgramados = $vacacionesProgramadas[0]->NO_HABIL ?? 0;
        }

        // Obtener y validar vacaciones Ofisis
        $vacacionesOfisis = $this->obtenerVacacionesOfisis($idUsuario, $empresa, $periodo);
        $diasHabilesOfisis = 0;
        $diasNoHabilesOfisis = 0;

        if (!empty($vacacionesOfisis) && isset($vacacionesOfisis[0])) {
            $diasHabilesOfisis = $vacacionesOfisis[0]->DIAS_HABILES_OFISIS ?? 0;
            $diasNoHabilesOfisis = $vacacionesOfisis[0]->DIAS_NO_HABILES_OFISIS ?? 0;
        }

        // Validar cuantas días hábiles y no hábiles se tienen en total
        $totalDiasHabiles = $diasHabilesProgramados + $diasHabilesOfisis;
        $totalDiasNoHabiles = $diasNoHabilesProgramados + $diasNoHabilesOfisis;
        $totalDiasOfisis = $diasHabilesOfisis + $diasNoHabilesOfisis;
        $totalDiasOfisisYProgramadas = $totalDiasOfisis + $diasHabilesProgramados + $diasNoHabilesProgramados;

        // Constantes para los límites
        $TOTAL_DIAS_NO_HABILES_REQUERIDOS = 8;
        $TOTAL_DIAS_HABILES_REQUERIDOS = 22; // 30 - 8
        $TOTAL_DIAS_VACACIONES = 30;

        // Calcular días de la solicitud actual 
        $diasSolicitudActual = $this->_sumarDiasPorTipo($fechaInicio, $fechaFin);
        $diasHabilesSolicitud = $diasSolicitudActual['habil'];
        $diasNoHabilesSolicitud = $diasSolicitudActual['no_habil'];

        // Verificar si con esta solicitud se excedería el límite de días totales
        $totalDiasHabilesConSolicitud = $totalDiasHabiles + $diasHabilesSolicitud;
        $totalDiasNoHabilesConSolicitud = $totalDiasNoHabiles + $diasNoHabilesSolicitud;
        $totalDiasConSolicitud = $totalDiasOfisisYProgramadas + $diasHabilesSolicitud + $diasNoHabilesSolicitud;

        // Días restantes antes de la solicitud
        $diasRestantesParaElUsuario = $TOTAL_DIAS_VACACIONES - $totalDiasOfisisYProgramadas;

        // Días hábiles y no hábiles restantes
        $diasHabilesRestantes = $TOTAL_DIAS_HABILES_REQUERIDOS - $totalDiasHabiles;
        $diasNoHabilesRestantes = $TOTAL_DIAS_NO_HABILES_REQUERIDOS - $totalDiasNoHabiles;

        // Inicializar resultado con validez
        $resultado = [
            'dias_usados' => $totalDias,
            'dias_disponibles' => $LIMITE_SUBPERIODO1 - $totalDias - $totalDiasOfisis > 0 ? $LIMITE_SUBPERIODO1 - $totalDias - $totalDiasOfisis : 0,
            'solicitudes' => $solicitudes,
            'cumple_primera_solicitud' => false,
            'siguiente_solicitud_minimo' => 0,
            'exceso' => max(0, $totalDias - $LIMITE_SUBPERIODO1),
            'mensaje' => '',
            'subperiodo_completo' => $subperiodoCompleto,
            'es_valido' => true
        ];

        // VALIDACIÓN 1: Verificar si la solicitud excede el total de días disponibles
        if ($totalDiasConSolicitud > $TOTAL_DIAS_VACACIONES) {
            $resultado['es_valido'] = false;
            $resultado['mensaje'] = "La solicitud excede el límite permitido. Días disponibles: {$diasRestantesParaElUsuario}.";
            return $resultado;
        }

        // VALIDACIÓN 2: Verificar si la solicitud excede los días hábiles disponibles
        if ($totalDiasHabilesConSolicitud > $TOTAL_DIAS_HABILES_REQUERIDOS) {
            $resultado['es_valido'] = false;
            $resultado['mensaje'] = "La solicitud excede los días hábiles permitidos. Días hábiles disponibles: {$diasHabilesRestantes}.";
            return $resultado;
        }

        // VALIDACIÓN 3: Verificar balance entre días hábiles y no hábiles
        // Si quedan pocos días y no se cumplirían los días no hábiles requeridos
        if ($diasRestantesParaElUsuario <= 10) {
            // Verificar si después de esta solicitud, los días no hábiles restantes serían suficientes
            $diasNoHabilesRestantesDespuesSolicitud = $diasNoHabilesRestantes - $diasNoHabilesSolicitud;
            $diasHabilesRestantesDespuesSolicitud = $diasHabilesRestantes - $diasHabilesSolicitud;

            // Si quedarían días hábiles pero no suficientes días no hábiles restantes
            if ($diasHabilesRestantesDespuesSolicitud > 0 && $diasNoHabilesRestantesDespuesSolicitud > 0) {
                // Verificar si la proporción permitiría cumplir con los 8 días no hábiles
                if ($diasHabilesRestantesDespuesSolicitud < $diasNoHabilesRestantesDespuesSolicitud) {
                    $resultado['es_valido'] = false;
                    $resultado['mensaje'] = "Después de esta solicitud quedarían {$diasHabilesRestantesDespuesSolicitud} días hábiles y {$diasNoHabilesRestantesDespuesSolicitud} días no hábiles. " .
                        "Debe incluir más días no hábiles en esta solicitud para mantener el balance requerido.";
                    return $resultado;
                }
            }

            // Si no quedarían días no hábiles pero sí días hábiles
            if ($diasNoHabilesRestantesDespuesSolicitud <= 0 && $diasHabilesRestantesDespuesSolicitud > 0) {
                $resultado['es_valido'] = false;
                $resultado['mensaje'] = "Esta solicitud agotaría los días no hábiles pero aún quedarían {$diasHabilesRestantesDespuesSolicitud} días hábiles. " .
                    "Debe incluir más días no hábiles en su solicitud.";
                return $resultado;
            }
        }

        // Si el subperíodo está completo, modificamos el mensaje
        if ($subperiodoCompleto) {
            $resultado['mensaje'] = "El subperíodo 1 ya tiene {$totalDias} días asignados (límite: {$LIMITE_SUBPERIODO1}). ";
            $resultado['mensaje'] .= "Nuevas solicitudes deben ir al subperíodo 2.";
            return $resultado;
        }

        // Lógica para subperíodo no completo
        if (count($solicitudes) == 0) {
            // No hay solicitudes en subperíodo 1
            $resultado['siguiente_solicitud_minimo'] = 7;
            $resultado['mensaje'] = "La primera solicitud debe ser de mínimo 7 días.";
        } else if (count($solicitudes) == 1 && $totalDias == 7) {
            // Solo hay una solicitud por exactamente 7 días
            $resultado['cumple_primera_solicitud'] = true;
            $resultado['siguiente_solicitud_minimo'] = 8;
            $resultado['mensaje'] = "Existe una solicitud por 7 días. La próxima debe ser de mínimo 8 días.";
        } else {
            // Otros casos (múltiples solicitudes o primera > 7 días)
            $resultado['cumple_primera_solicitud'] = true;
            $resultado['siguiente_solicitud_minimo'] = 7;
            $resultado['mensaje'] = "Su solicitud debe ser de mínimo 7 días.";
        }

        // Añadir nota sobre exceso (si no está completo pero hay exceso)
        if (!$subperiodoCompleto && $resultado['exceso'] > 0) {
            $resultado['mensaje'] .= " Nota: El subperíodo 1 tiene {$totalDias} días asignados, superando el límite teórico de {$LIMITE_SUBPERIODO1} días por {$resultado['exceso']} días, lo cual es válido según las reglas establecidas.";
        }

        // // Añadir información detallada sobre días
        // $resultado['detalles'] = [
        //     'dias_habiles_ofisis' => $diasHabilesOfisis,
        //     'dias_no_habiles_ofisis' => $diasNoHabilesOfisis,
        //     'dias_habiles_programados' => $diasHabilesProgramados,
        //     'dias_no_habiles_programados' => $diasNoHabilesProgramados,
        //     'total_dias_habiles' => $totalDiasHabiles,
        //     'total_dias_no_habiles' => $totalDiasNoHabiles,
        //     'total_dias_consumidos' => $totalDiasOfisisYProgramadas,
        //     'dias_habiles_solicitud' => $diasHabilesSolicitud,
        //     'dias_no_habiles_solicitud' => $diasNoHabilesSolicitud,
        //     'dias_habiles_restantes' => $diasHabilesRestantes,
        //     'dias_no_habiles_restantes' => $diasNoHabilesRestantes,
        //     'total_dias_restantes' => $diasRestantesParaElUsuario
        // ];

        return $resultado;
    }
    public function validarSubperiodo2($idUsuario, $empresa, $periodo, $fechaInicio, $fechaFin)
    {
        // Primero, verificamos el exceso del subperíodo 1
        $validacionSub1 = $this->validarSubperiodo1($idUsuario, $periodo, $empresa, $fechaInicio, $fechaFin);
        $excesoSubperiodo1 = $validacionSub1['exceso']; // Días que exceden el límite de 15

        // Obtener solicitudes existentes en el subperíodo 2
        $this->intra_db->setCampos("id_vacacion, NUM_DIAS as dias");
        $this->intra_db->setTabla("TBINT_VACACIONES");
        $this->intra_db->setCondicion("=", "periodo", $periodo);
        $this->intra_db->setCondicion("=", "subperiodo", 2);
        $this->intra_db->setCondicion("=", "id_solicitante", $idUsuario);
        $this->intra_db->setCondicion("=", "eliminado", 0);
        $this->intra_db->setCondicionString('id_vaca_estado in (1,2,3,4,5)');
        $this->intra_db->setOrden("id_vacacion ASC");

        $resultadoBD = $this->intra_db->Listar();

        // Inicializar variables
        $totalDias = 0;
        $solicitudes = [];

        // Verificar si el resultado es null o vacío
        if (empty($resultadoBD)) {
            $resultadoBD = [];
        }

        if (is_array($resultadoBD)) {
            foreach ($resultadoBD as $item) {
                if (is_object($item)) {
                    // Convertir objeto a array para consistencia
                    $solicitud = [
                        'id_vacacion' => $item->id_vacacion,
                        'dias' => $item->dias
                    ];
                    $solicitudes[] = $solicitud;
                    $totalDias += intval($item->dias);
                } elseif (is_array($item)) {
                    $solicitudes[] = $item;
                    $totalDias += intval($item['dias']);
                }
            }
        }

        // Límite teórico del subperíodo 2
        $LIMITE_SUBPERIODO2 = 15;

        // Disponibilidad real ajustada por exceso en subperíodo 1
        $disponibilidadReal = $LIMITE_SUBPERIODO2 - $excesoSubperiodo1;

        // Si la disponibilidad real es negativa, establecemos a 0
        if ($disponibilidadReal < 0) {
            $disponibilidadReal = 0;
        }

        $vacacionesProgramadas = $this->obtenerVacacionesProgramadas($idUsuario, $periodo);
        $diasHabilesProgramados = 0;
        $diasNoHabilesProgramados = 0;
        if (!empty($vacacionesProgramadas) && isset($vacacionesProgramadas[0])) {
            $diasHabilesProgramados = $vacacionesProgramadas[0]->HABIL ?? 0;
            $diasNoHabilesProgramados = $vacacionesProgramadas[0]->NO_HABIL ?? 0;
        }

        // Obtener y validar vacaciones Ofisis
        $vacacionesOfisis = $this->obtenerVacacionesOfisis($idUsuario, $empresa, $periodo);
        $diasHabilesOfisis = 0;
        $diasNoHabilesOfisis = 0;

        if (!empty($vacacionesOfisis) && isset($vacacionesOfisis[0])) {
            $diasHabilesOfisis = $vacacionesOfisis[0]->DIAS_HABILES_OFISIS ?? 0;
            $diasNoHabilesOfisis = $vacacionesOfisis[0]->DIAS_NO_HABILES_OFISIS ?? 0;
        }

        // Validar cuantas días hábiles y no hábiles se tienen en total
        $totalDiasHabiles = $diasHabilesProgramados + $diasHabilesOfisis;
        $totalDiasNoHabiles = $diasNoHabilesProgramados + $diasNoHabilesOfisis;
        $totalDiasOfisis = $diasHabilesOfisis + $diasNoHabilesOfisis;
        
        // Resultado de la validación
        $resultado = [
            'dias_usados' => $totalDias,
            'dias_disponibles' => $disponibilidadReal - $totalDias - $totalDiasOfisis > 0 ? $disponibilidadReal - $totalDias - $totalDiasOfisis : 0,
            'solicitudes' => $solicitudes,
            'es_valido' => true,
            'mensaje' => empty($solicitudes) ? 'No se han registrado solicitudes en el subperiodo 2' : '',
            'vacio' => empty($solicitudes), // Indicador explícito si no hay solicitudes
            'exceso_subperiodo1' => $excesoSubperiodo1, // Guardar el exceso del subperíodo 1
            'limite_original' => $LIMITE_SUBPERIODO2, // Límite original
            'limite_ajustado' => $disponibilidadReal - $totalDiasOfisis // Límite ajustado después del exceso
        ];

        $TOTAL_DIAS_NO_HABILES_REQUERIDOS = 8;
        $TOTAL_DIAS_HABILES_REQUERIDOS = 22; // 30 - 8

        // Calcular días de la solicitud actual
        $diasSolicitudActual = $this->_sumarDiasPorTipo($fechaInicio, $fechaFin);
        $diasHabilesSolicitud = $diasSolicitudActual['habil'];
        $diasNoHabilesSolicitud = $diasSolicitudActual['no_habil'];

        // Verificar si con esta solicitud se excedería el límite de días hábiles permitidos
        $totalDiasHabilesConSolicitud = $totalDiasHabiles + $diasHabilesSolicitud;
        $totalDiasNoHabilesConSolicitud = $totalDiasNoHabiles + $diasNoHabilesSolicitud;
        $diasNoHabilesFaltantesDespuesSolicitud = $TOTAL_DIAS_NO_HABILES_REQUERIDOS - $totalDiasNoHabilesConSolicitud;
        // var_dump($totalDiasHabilesConSolicitud);
        // die();
        // Si se excedería el límite de días hábiles y aún faltan días no hábiles por cumplir
        if ($totalDiasHabilesConSolicitud > $TOTAL_DIAS_HABILES_REQUERIDOS || $diasNoHabilesFaltantesDespuesSolicitud > 0) {
            $resultado['es_valido'] = false;
            $resultado['mensaje'] = "Por favor seleccione otro rango de fecha que incluya más fines de semana. " .
                "Falta incluir {$diasNoHabilesFaltantesDespuesSolicitud} días no hábiles para cumplir con el mínimo requerido.";
            // Sugerencia adicional
            if ($diasNoHabilesFaltantesDespuesSolicitud == 1) {
                $resultado['mensaje'] .= " Considere incluir al menos un día más de fin de semana en su solicitud.";
            } else {
                $resultado['mensaje'] .= " Considere incluir al menos {$diasNoHabilesFaltantesDespuesSolicitud} días más de fin de semana en su solicitud.";
            }
            // var_dump($resultado);
            return $resultado;
        }

        return $resultado;
    }
    private function validarVacacionEspecial($idUsuario, $periodo, $fechaInicio, $fechaFin, $empresa)
    {
        // Resultado por defecto
        $resultado = [
            'subperiodo' => 2,  // Por defecto asignamos al subperíodo 2
            'es_valido' => true,
            'mensaje' => ''
        ];

        // Obtener vacaciones programadas
        $vacacionesProgramadas = $this->obtenerVacacionesProgramadas($idUsuario, $periodo);
        $diasHabilesProgramados = 0;
        $diasNoHabilesProgramados = 0;
        if (!empty($vacacionesProgramadas) && isset($vacacionesProgramadas[0])) {
            $diasHabilesProgramados = $vacacionesProgramadas[0]->HABIL ?? 0;
            $diasNoHabilesProgramados = $vacacionesProgramadas[0]->NO_HABIL ?? 0;
        }

        // Obtener y validar vacaciones Ofisis
        $vacacionesOfisis = $this->obtenerVacacionesOfisis($idUsuario, $empresa, $periodo);
        $diasHabilesOfisis = 0;
        $diasNoHabilesOfisis = 0;

        if (!empty($vacacionesOfisis) && isset($vacacionesOfisis[0])) {
            $diasHabilesOfisis = $vacacionesOfisis[0]->DIAS_HABILES_OFISIS ?? 0;
            $diasNoHabilesOfisis = $vacacionesOfisis[0]->DIAS_NO_HABILES_OFISIS ?? 0;
        }

        // Validar cuantas días hábiles y no hábiles se tienen en total
        $totalDiasHabiles = $diasHabilesProgramados + $diasHabilesOfisis;
        $totalDiasNoHabiles = $diasNoHabilesProgramados + $diasNoHabilesOfisis;
        // Total consumido en ofisis (solicitudes antiguas antes del cambio)
        $totalDiasOfisis = $diasHabilesOfisis + $diasNoHabilesOfisis;

        // Obtener información de ambos subperíodos
        $infoSubperiodo1 = $this->_obtenerDiasConsumidosPeriodo($idUsuario, $periodo, 1);
        $diasHabilesUsadosSubp1 = 0;
        $diasNoHabilesUsadosSubp1 = 0;
        if (!empty($infoSubperiodo1) && isset($infoSubperiodo1[0])) {
            $diasHabilesUsadosSubp1 = $infoSubperiodo1[0]->HABIL ?? 0;
            $diasNoHabilesUsadosSubp1 = $infoSubperiodo1[0]->NO_HABIL ?? 0;
        }


        $infoSubperiodo2 = $this->_obtenerDiasConsumidosPeriodo($idUsuario, $periodo, 2);
        $diasHabilesUsadosSubp2 = 0;
        $diasNoHabilesUsadosSubp2 = 0;
        if (!empty($infoSubperiodo2) && isset($infoSubperiodo2[0])) {
            $diasHabilesUsadosSubp2 = $infoSubperiodo2[0]->HABIL ?? 0;
            $diasNoHabilesUsadosSubp2 = $infoSubperiodo2[0]->NO_HABIL ?? 0;
        }

        // Calcular días hábiles y no hábiles de la solicitud actual
        $diasSolicitudActual = $this->_sumarDiasPorTipo($fechaInicio, $fechaFin);


        $diasHabilesSolicitud = $diasSolicitudActual['habil'];
        $diasNoHabilesSolicitud = $diasSolicitudActual['no_habil'];

        // Total de días hábiles y no hábiles usados
        $totalDiasHabilesUsados = $diasHabilesUsadosSubp1 + $diasHabilesUsadosSubp2 + $diasHabilesOfisis;
        $totalDiasNoHabilesUsados = $diasNoHabilesUsadosSubp1 + $diasNoHabilesUsadosSubp2 + $diasNoHabilesOfisis;

        // Límites
        $LIMITE_DIAS_HABILES_TOTAL = 22;
        $LIMITE_DIAS_NO_HABILES_TOTAL = 8;
        $LIMITE_DIAS_SUBPERIODO = 11;
        $MINIMO_DIAS_NO_HABILES_POR_SUBPERIODO = 4;
        // Verificar si después de esta solicitud se excedería el límite total de días hábiles
        if ($totalDiasHabilesUsados + $diasHabilesSolicitud > $LIMITE_DIAS_HABILES_TOTAL) {
            $diasHabilesDisponibles = $LIMITE_DIAS_HABILES_TOTAL - $totalDiasHabilesUsados;

            $resultado['es_valido'] = false;
            $resultado['mensaje'] = "No puede solicitar {$diasHabilesSolicitud} días hábiles. Solo tiene {$diasHabilesDisponibles} días hábiles disponibles.";
            return $resultado;
        }

        // Verificar si ya se han completado los días hábiles pero faltan días no hábiles
        if ($totalDiasHabilesUsados == $LIMITE_DIAS_HABILES_TOTAL && $totalDiasNoHabilesUsados < $LIMITE_DIAS_NO_HABILES_TOTAL) {
            // Si la solicitud no incluye días no hábiles, rechazarla
            if ($diasNoHabilesSolicitud == 0) {
                $diasNoHabilesFaltantes = $LIMITE_DIAS_NO_HABILES_TOTAL - $totalDiasNoHabilesUsados;

                $resultado['es_valido'] = false;
                $resultado['mensaje'] = "Ya ha completado los días hábiles permitidos. Solo puede solicitar días que incluyan fines de semana. " .
                    "Aún debe completar {$diasNoHabilesFaltantes} días no hábiles.";

                return $resultado;
            }
        }

        // Determinar a qué subperíodo asignar la solicitud
        // Regla: Primero llenar el subperíodo 2 hasta 11 días, luego el subperíodo 1
        if ($diasHabilesUsadosSubp2 < $LIMITE_DIAS_SUBPERIODO) {
            // Verificar si la solicitud excedería el límite del subperíodo 2
            if ($diasHabilesUsadosSubp2 + $diasHabilesSolicitud > $LIMITE_DIAS_SUBPERIODO) {
                // La solicitud excede el límite del subperíodo 2, verificar si el subperíodo 1 está disponible
                if ($diasHabilesUsadosSubp1 < $LIMITE_DIAS_SUBPERIODO) {
                    $resultado['subperiodo'] = 1;
                    $resultado['mensaje'] = "Vacación especial asignada al subperíodo 1 (subperíodo 2 se completaría).";
                } else {
                    // Ambos subperíodos están llenos o se completarían
                    $diasDisponiblesSubp2 = $LIMITE_DIAS_SUBPERIODO - $diasHabilesUsadosSubp2;
                    $diasDisponiblesSubp1 = $LIMITE_DIAS_SUBPERIODO - $diasHabilesUsadosSubp1;

                    $resultado['es_valido'] = false;
                    $resultado['mensaje'] = "La solicitud excede los límites de ambos subperíodos. " .
                        "Disponible en subperíodo 2: {$diasDisponiblesSubp2} días. " .
                        "Disponible en subperíodo 1: {$diasDisponiblesSubp1} días.";

                    return $resultado;
                }
            } else {
                // La solicitud cabe en el subperíodo 2
                $resultado['subperiodo'] = 2;
                $resultado['mensaje'] = "Vacación especial asignada al subperíodo 2.";
            }
        } else {
            // El subperíodo 2 está lleno, verificar si el subperíodo 1 está disponible
            if ($diasHabilesUsadosSubp1 < $LIMITE_DIAS_SUBPERIODO) {
                // Verificar si la solicitud excedería el límite del subperíodo 1
                if ($diasHabilesUsadosSubp1 + $diasHabilesSolicitud > $LIMITE_DIAS_SUBPERIODO) {
                    $diasDisponiblesSubp1 = $LIMITE_DIAS_SUBPERIODO - $diasHabilesUsadosSubp1;

                    $resultado['es_valido'] = false;
                    $resultado['mensaje'] = "La solicitud excede el límite del subperíodo 1. " .
                        "Disponible en subperíodo 1: {$diasDisponiblesSubp1} días.";

                    return $resultado;
                } else {
                    // La solicitud cabe en el subperíodo 1
                    $resultado['subperiodo'] = 1;
                    $resultado['mensaje'] = "Vacación especial asignada al subperíodo 1 (subperíodo 2 completo).";
                }
            } else {
                // Ambos subperíodos están llenos
                $resultado['es_valido'] = false;
                $resultado['mensaje'] = "No se pueden asignar más días hábiles. Ambos subperíodos han alcanzado su límite de 11 días.";

                // Verificar si aún faltan días no hábiles por cumplir
                if ($totalDiasNoHabilesUsados < $LIMITE_DIAS_NO_HABILES_TOTAL) {
                    $diasNoHabilesFaltantes = $LIMITE_DIAS_NO_HABILES_TOTAL - $totalDiasNoHabilesUsados;
                    $resultado['mensaje'] .= " Aún debe completar {$diasNoHabilesFaltantes} días no hábiles.";
                }

                return $resultado;
            }
        }
        return $resultado;
    }
}