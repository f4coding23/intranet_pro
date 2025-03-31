<?php
class VacacionController extends ControllerBase
{
    function __construct()
    {
        parent::__construct(__CLASS__);
    }

    public function index()
    {
        //Verificamos la sesión
        $this->sessionObj->checkInner();
        require $this->getDefaultModelName();
        require $this->getModelByName('Directorio', 'directorio');
        require $this->getModelByName('BoletaGeneradores', 'boletageneradores');
        require_once $this->getModelByName('VacacionConfiguracion', 'vacacionconfiguracion');


        $vacacionModelObj = new VacacionModel();
        $directorioModelObj = new DirectorioModel();
        $boletaGeneObj = new BoletaGeneradoresModel();
        $vacaConfigObj = new VacacionConfiguracionModel();

        //$userInfo = $this->sessionObj->getUserInfo();
        //$userInfoOfisis = $this->sessionObj->getInfoFromOfisis();
        //$gerencias_gene = $boletaGeneObj->getGenerador("", "", $userInfo[0]->ID_USUARIO,1);
        $minimoDiasEditReg = $vacaConfigObj->getConfigById(2);

        $data['cboEmpresa'] = $directorioModelObj->getEmpresaGroup(array('01'));
        $data['cboCondicion'] = $vacacionModelObj->listarCondicionCombo(array('01'));
        $data['maximoDiasReprogramar'] = $minimoDiasEditReg[0]->valor;
        /*$data['generador'] = array(
            'is_generador' => empty($gerencias_gene) ? false : true,
            'gerencias' => $gerencias_gene
        );*/
        //$data['ofisis'] = $userInfoOfisis;
        $data['rootFolder'] = $this->getRootFolder();
        $data['protocol'] = $this->getCurrentProtocol();
        $data['obj'] = $this;

        $this->sessionObj->RegisterAuditModule($this->getAppName());

        //Finalmente presentamos nuestra plantilla
        $this->view->show(array(
            'filename' => "main.tpl.php",
            'data' => $data
        ));
    }

    public function listar($master = false)
    {
        $this->sessionObj->checkJsonRequest();
        $pgStart = (isset($_REQUEST['jtStartIndex']) && $_REQUEST['jtStartIndex'] ? $_REQUEST['jtStartIndex'] : 0);
        $pgSize = (isset($_REQUEST['jtPageSize']) && $_REQUEST['jtPageSize'] ? $_REQUEST['jtPageSize'] : 0);
        $pgSort = (isset($_REQUEST['jtSorting']) && $_REQUEST['jtSorting'] ? $_REQUEST['jtSorting'] : '');

        $qryEmpresa = (isset($_REQUEST['qry_empresa']) && $_REQUEST['qry_empresa'] ? trim($_REQUEST['qry_empresa']) : 0);
        $qryGerencia = (isset($_REQUEST['qry_gerencia']) && $_REQUEST['qry_gerencia'] ? trim($_REQUEST['qry_gerencia']) : 0);
        $qryDepartamento = (isset($_REQUEST['qry_departamento']) && $_REQUEST['qry_departamento'] ? trim($_REQUEST['qry_departamento']) : 0);
        $qryArea = (isset($_REQUEST['qry_area']) && $_REQUEST['qry_area'] ? trim($_REQUEST['qry_area']) : 0);
        $qrySeccion = (isset($_REQUEST['qry_seccion']) && $_REQUEST['qry_seccion'] ? trim($_REQUEST['qry_seccion']) : 0);

        $qryColaborador = (isset($_REQUEST['qry_colaborador']) && $_REQUEST['qry_colaborador'] ? trim($_REQUEST['qry_colaborador']) : '');

        $qryIniRango = (isset($_REQUEST['qry_ini_rango']) && $_REQUEST['qry_ini_rango'] ? trim($_REQUEST['qry_ini_rango']) : '');
        $qryFinRango = (isset($_REQUEST['qry_fin_rango']) && $_REQUEST['qry_fin_rango'] ? trim($_REQUEST['qry_fin_rango']) : '');
        $response = array();

        require $this->getDefaultModelName();
        $vacacionModelObj = new VacacionModel();
        $dnis = !$master;

        if (!$master) {
            require $this->getModelByName('Reporte', 'reporte');
            $reporteModelObj = new ReporteModel();

            $userInfo = $this->sessionObj->getUserInfo();
            $dnis = $reporteModelObj->getDnisJerarquia2($userInfo[0]->DNI);
        }

        $vacacionesList = $vacacionModelObj->getVacacionesList($pgStart, $pgSize, $pgSort, $qryEmpresa, $qryGerencia, $qryDepartamento, $qryArea, $qrySeccion, $qryIniRango, $qryFinRango, $dnis, $qryColaborador);
        $numBoleta = $vacacionModelObj->getNumVacaciones($qryEmpresa, $qryGerencia, $qryDepartamento, $qryArea, $qrySeccion, $qryIniRango, $qryFinRango, $dnis, $qryColaborador);
        $numBoletaVal = $numBoleta[0]->num;

        if ($vacacionesList) {
            $this->_marcarDuenio($vacacionesList, (isset($userInfo[0]->ID_USUARIO)) ? $userInfo[0]->ID_USUARIO : '');
            $response["Result"] = 'OK';
            $response["Records"] = $vacacionesList;
            $response["TotalRecordCount"] = $numBoletaVal;
        } else {
            $response["Result"] = 'ERROR';
            $response["Message"] = 'No se encontraron Boletas de Vacaciones Generadas';
        }

        $this->view->showJSONPlane(array(
            'response' => $response
        ));
    }

    private function _marcarDuenio(&$reg, $own)
    {
        foreach ($reg as $key => $row) {
            $row->own = ($own == $row->id_solicitante || $own == $row->id_generador) ? true : false;
            $row->propio = ($own == $row->id_solicitante) ? true : false;
        }
    }

    public function listarAutorizaciones()
    {
        $this->sessionObj->checkJsonRequest();
        $qryIdVacacion = (isset($_REQUEST['id_vacacion']) && $_REQUEST['id_vacacion'] ? trim($_REQUEST['id_vacacion']) : 0);

        $response = array();

        require $this->getDefaultModelName();
        $vacacionModelObj = new VacacionModel();

        $autorizacionesReales = $vacacionModelObj->getAutorizacionesList($qryIdVacacion);
        $autorizadoresVirtuales = $vacacionModelObj->getVirtualAutorizador($qryIdVacacion);
        $autorizacionesList = array_merge($autorizacionesReales, $autorizadoresVirtuales);

        $response["Result"] = 'OK';
        $response["Records"] = ($autorizacionesList) ? $autorizacionesList : array();

        $this->view->showJSONPlane(array(
            'response' => $response
        ));
    }

    public function indexVacacionesMaster()
    {
        $this->sessionObj->checkInner();
        require $this->getDefaultModelName();
        require $this->getModelByName('Directorio', 'directorio');
        require_once $this->getModelByName('VacacionConfiguracion', 'vacacionconfiguracion');

        $vacacionModelObj = new VacacionModel();
        $directorioModelObj = new DirectorioModel();
        $vacaConfigObj = new VacacionConfiguracionModel();

        $minimoDiasEditReg = $vacaConfigObj->getConfigById(2);
        $data['cboEmpresa'] = $directorioModelObj->getEmpresaGroup(array('01'));
        $data['cboCondicion'] = $vacacionModelObj->listarCondicionCombo(array('01'));
        $data['maximoDiasReprogramar'] = $minimoDiasEditReg[0]->valor;

        $data['rootFolder'] = $this->getRootFolder();
        $data['protocol'] = $this->getCurrentProtocol();
        $data['obj'] = $this;

        $this->sessionObj->RegisterAuditModule($this->getAppName() . '/' . __FUNCTION__);

        //Finalmente presentamos nuestra plantilla
        $this->view->show(array(
            'filename' => "vacacionesMaster.tpl.php",
            'data' => $data
        ));
    }

    public function listarMaster()
    {
        $this->listar(true);
    }

    public function indexVacacionesMasterCP()
    {
        //Verificamos la sesión
        $this->sessionObj->checkInner();

        require $this->getDefaultModelName();
        require $this->getModelByName('Directorio', 'directorio');
        require $this->getModelByName('VacacionAsignacion', 'vacacionasignacion');
        $vacacionModelObj = new VacacionModel();
        $asignacionModelObj = new VacacionAsignacionModel();
        $directorioModelObj = new DirectorioModel();

        $userInfo = $this->sessionObj->getUserInfo();

        $data['rootFolder'] = $this->getRootFolder();
        $data['protocol'] = $this->getCurrentProtocol();
        $data['obj'] = $this;

        $data['cboEmpresa'] = $directorioModelObj->getEmpresaGroup(array('01'));
        $data['gerencias_ope'] = $asignacionModelObj->getVacacionAsignacionByUser($userInfo[0]->ID_USUARIO);

        $this->sessionObj->RegisterAuditModule($this->getAppName() . '/' . __FUNCTION__);
        $this->view->show(array(
            'filename' => "vacacionesMasterCP.tpl.php",
            'data' => $data
        ));
    }

    public function listarMasterCP()
    {
        $this->sessionObj->checkJsonRequest();
        $pgStart = (isset($_REQUEST['jtStartIndex']) && $_REQUEST['jtStartIndex'] ? $_REQUEST['jtStartIndex'] : 0);
        $pgSize = (isset($_REQUEST['jtPageSize']) && $_REQUEST['jtPageSize'] ? $_REQUEST['jtPageSize'] : 0);
        $pgSort = (isset($_REQUEST['jtSorting']) && $_REQUEST['jtSorting'] ? $_REQUEST['jtSorting'] : '');

        $qryEmpresa = (isset($_REQUEST['qry_empresa']) && $_REQUEST['qry_empresa'] ? trim($_REQUEST['qry_empresa']) : 0);
        $qryGerencia = (isset($_REQUEST['qry_gerencia']) && $_REQUEST['qry_gerencia'] ? trim($_REQUEST['qry_gerencia']) : 0);
        $qryDepartamento = (isset($_REQUEST['qry_departamento']) && $_REQUEST['qry_departamento'] ? trim($_REQUEST['qry_departamento']) : 0);
        $qryArea = (isset($_REQUEST['qry_area']) && $_REQUEST['qry_area'] ? trim($_REQUEST['qry_area']) : 0);
        $qrySeccion = (isset($_REQUEST['qry_seccion']) && $_REQUEST['qry_seccion'] ? trim($_REQUEST['qry_seccion']) : 0);

        $qryColaborador = (isset($_REQUEST['qry_colaborador']) && $_REQUEST['qry_colaborador'] ? trim($_REQUEST['qry_colaborador']) : '');

        $qryIniRango = (isset($_REQUEST['qry_ini_rango']) && $_REQUEST['qry_ini_rango'] ? trim($_REQUEST['qry_ini_rango']) : '');
        $qryFinRango = (isset($_REQUEST['qry_fin_rango']) && $_REQUEST['qry_fin_rango'] ? trim($_REQUEST['qry_fin_rango']) : '');
        $response = array();

        require $this->getDefaultModelName();
        require $this->getModelByName('VacacionAsignacion', 'vacacionasignacion');
        $asignacionModelObj = new VacacionAsignacionModel();
        $vacacionModelObj = new VacacionModel();
        $userInfo = $this->sessionObj->getUserInfo();
        $vacacionesList = array();
        $dnis = '';

        if (empty($qryGerencia)) {
            $gerencias = $asignacionModelObj->getVacacionAsignacionByUser($userInfo[0]->ID_USUARIO);
        }

        // Si filtro viene por gerencia, o no viene por gerencia pero tiene gerencias configuradas
        if ((empty($qryGerencia) && $gerencias) || $qryGerencia) {

            //Asignar el listado de gerencias configuradas
            if (empty($qryGerencia)) {
                $qryGerencia = array();
                foreach ($gerencias as $row) {
                    $qryGerencia[] = $row->id_gerencia;
                }
            }

            $vacacionesList = $vacacionModelObj->getVacacionesList($pgStart, $pgSize, $pgSort, $qryEmpresa, $qryGerencia, $qryDepartamento, $qryArea, $qrySeccion, $qryIniRango, $qryFinRango, $dnis, $qryColaborador);
            $numBoleta = $vacacionModelObj->getNumVacaciones($qryEmpresa, $qryGerencia, $qryDepartamento, $qryArea, $qrySeccion, $qryIniRango, $qryFinRango, $dnis, $qryColaborador);
            $numBoletaVal = $numBoleta[0]->num;
        } else {
            $boletaList = array();
            $numBoletaVal = 0;
        }


        if ($vacacionesList) {
            $this->_marcarDuenio($vacacionesList, (isset($userInfo[0]->ID_USUARIO)) ? $userInfo[0]->ID_USUARIO : '');
            $response["Result"] = 'OK';
            $response["Records"] = $vacacionesList;
            $response["TotalRecordCount"] = $numBoletaVal;
        } else {
            $response["Result"] = 'ERROR';
            $response["Message"] = 'No se encontraron Boletas de Vacaciones Generadas';
        }

        $this->view->showJSONPlane(array(
            'response' => $response
        ));
    }

    /************************************************* FUNCIONALIDADES PARA LA CREACION *********************************************/

    public function indexCrear()
    {
        $this->sessionObj->checkJsonRequest();
        $info = array();

        $qrySolicitante = (isset($_POST['idSolicitante']) && $_POST['idSolicitante'] ? trim($_POST['idSolicitante']) : '');

        require $this->getDefaultModelName();
        require $this->getModelByName('BoletaGeneradores', 'boletageneradores');
        require $this->getModelByName('Reporte', 'reporte');
        $vacacionModelObj = new VacacionModel();
        $boletaGeneObj = new BoletaGeneradoresModel();
        $userInfo = $this->sessionObj->getUserInfo();
        $gerencias_gene = $boletaGeneObj->getGenerador("", "", $userInfo[0]->ID_USUARIO, 3);

        $info['Result'] = 'OK';
        $info['generador'] = empty($gerencias_gene) ? false : true;

        if (!$info['generador']) {
            $userInfoOfisis = $this->sessionObj->getInfoFromOfisis();
            $regFechaIngreso = $vacacionModelObj->getFechaIngresoColaborador($userInfo[0]->USUARIO);

            $info['empresa'] = $userInfoOfisis[0]->EMPRESA;
            $info['gerencia'] = $userInfoOfisis[0]->GERENCIA;
            $info['area'] = $userInfoOfisis[0]->AREA;
            $info['id_solicitante'] = $userInfo[0]->ID_USUARIO;
            $info['fecha_ingreso'] = $regFechaIngreso[0]->FE_INGR_EMPR;
        }

        //Si envian el solicitante, devolver la fecha de ingreso del colaborador
        if ($qrySolicitante) {
            $userInfo = $this->sessionObj->getUserInfo($qrySolicitante);
            $regFechaIngreso = $vacacionModelObj->getFechaIngresoColaborador($userInfo[0]->USUARIO);
            $info['fecha_ingreso'] = $regFechaIngreso[0]->FE_INGR_EMPR;
        }
        $info['cboCondicion'] = $vacacionModelObj->listarCondicionComboEspecial($userInfo[0]->ID_USUARIO);

        $this->view->showJSONPlane(array(
            'response' => $info
        ));
    }

    public function listarConsolidado()
    {
        $this->sessionObj->checkJsonRequest();
        $idSolicitante = (isset($_REQUEST['idSolicitante']) && $_REQUEST['idSolicitante'] ? trim($_REQUEST['idSolicitante']) : 0);
        $idCondicion = (isset($_REQUEST['idCondicion']) && $_REQUEST['idCondicion'] ? trim($_REQUEST['idCondicion']) : 0);
        $idSolicitud = (isset($_REQUEST['idSolicitud']) && $_REQUEST['idSolicitud'] ? trim($_REQUEST['idSolicitud']) : 0);

        require $this->getModelByName('Reporte', 'reporte');
        require_once $this->getDefaultModelName();
        $vacacionModelObj = new VacacionModel();
        $reporteModelObj = new ReporteModel();

        $response = array();
        $userInfo = $this->sessionObj->getUserInfo($idSolicitante);
        $vacacion = $reporteModelObj->getReporteVacaciones(date('d/m/Y'), '01', '', '', '', '', '', "'" . $userInfo[0]->USUARIO . "'");
        $regProgramados = $vacacionModelObj->getNumProgramadas($idSolicitante, $idCondicion, $idSolicitud);
        //$regDisponibles = $vacacionModelObj->getVacacionesPendientes($idSolicitante,0,$idCondicion,$idSolicitud);
        $registro = $this->_formatVacionesOfisis($vacacion, $regProgramados, $idCondicion);
        $response['recordsTotal'] = 1;
        $response['recordsFiltered'] = 1;
        $response['data'] = array($registro);

        $this->view->showJSONPlane(array(
            'response' => $response
        ));
    }
    public function listarConsolidadoDetalle()
    {
        $this->sessionObj->checkJsonRequest();
        $idSolicitante = (isset($_REQUEST['idSolicitante']) && $_REQUEST['idSolicitante'] ? trim($_REQUEST['idSolicitante']) : 0);
        $idCondicion = (isset($_REQUEST['idCondicion']) && $_REQUEST['idCondicion'] ? trim($_REQUEST['idCondicion']) : 0);
        $idSolicitud = (isset($_REQUEST['idSolicitud']) && $_REQUEST['idSolicitud'] ? trim($_REQUEST['idSolicitud']) : 0);
        $empresa = (isset($_REQUEST['idemprealidad']) && $_REQUEST['idemprealidad'] ? trim($_REQUEST['idemprealidad']) : 0);


        require $this->getModelByName('Reporte', 'reporte');
        require_once $this->getDefaultModelName();
        $vacacionModelObj = new VacacionModel();
        $reporteModelObj = new ReporteModel();
        //getDetallePeriodos
        $userInfo = $this->sessionObj->getUserInfo($idSolicitante);
        $vacacion = $reporteModelObj->getReporteVacaciones(date('d/m/Y'), '01', '', '', '', '', '', "'" . $userInfo[0]->USUARIO . "'");
        $regProgramados = $vacacionModelObj->getNumProgramadas($idSolicitante, $idCondicion, $idSolicitud);
        $registro = $this->_formatVacionesOfisis($vacacion, $regProgramados, $idCondicion);
        $result = $reporteModelObj->getDetallePeriodos($userInfo[0]->USUARIO, $empresa, date('d/m/Y'), $registro["trunco"]);

        // echo $userInfo[0]->USUARIO.'|'.$empresa.'|'.date('d/m/Y').'|'.$registro["trunco"];
        $a_actual = date("Y");
        $a_anterior = $a_actual - 1;
        $a_trasante = $a_anterior - 1;
        $a_tras_ante = $a_trasante - 1;
        if (count($result) == 0) {
            $estados = [
                "trunco" => ["estado" => "no disponible", "periodo" => "$a_actual-$a_anterior"],
                "ganado" => ["estado" => "pendiente", "periodo" => "$a_anterior-$a_trasante"],
                "vencido" => ["estado" => "vencidas", "periodo" => "$a_trasante-$a_tras_ante"]
            ];

            foreach ($estados as $key => $data) {
                if ($registro[$key] > 0) {
                    $result[] = (object) [
                        "CO_EMPR" => "",
                        "NO_EMPR" => "",
                        "CO_TRAB" => "",
                        "NO_TRAB" => "",
                        "FECHA_INGRESO" => "",
                        "PE_VACA" => $data["periodo"],
                        "GANADAS" => $key === "ganado" ? $registro[$key] : "0",
                        "GOZADAS" => "0",
                        "TRUNCAS" => $key === "trunco" ? $registro[$key] : "0",
                        "SALDO" => $registro[$key],
                        "ESTADO" => $data["estado"]
                    ];
                }
            }
        }
        //eliminamos los estados cerrados y total para que no aparezcan en la tabla
        $filtrado = array_values(array_filter($result, function ($item) {
            return $item->ESTADO !== "Cerrado" && $item->ESTADO != "";
        }));
        $response['recordsTotal'] = 1;
        $response['recordsFiltered'] = 1;
        $response['data'] = $filtrado;

        $this->view->showJSONPlane(array(
            'response' => $response
        ));
    }
    private function _formatVacionesOfisis($vacacion, $regProgramados, $idCondicion, $disponibleTipo = false)
    {
        $periodos = array_reverse($vacacion); //Revertir, para empezar con los truncos, ganados y finalmente los vencidos

        $index = 1;
        $registro = array('trunco' => 0, 'ganado' => 0, 'vencido' => 0);
        foreach ($periodos as $periodo) {

            switch ($index) {
                case 1:
                    $registro['trunco'] = floatval($periodo->DIAS_PEND ? $periodo->DIAS_PEND : 0.00);
                    $index++;
                    break;
                case 2:
                    $registro['ganado'] = floatval($periodo->DIAS_PEND ? $periodo->DIAS_PEND : 0.00);
                    $index++;
                    break;
                case 3:
                    $registro['vencido'] = $registro['vencido'] + floatval($periodo->DIAS_PEND);
                    break;
            }
        }

        $registro['programado'] = 0;
        foreach ($regProgramados as $periodo) {
            $registro['programado'] += floatval($periodo->dias_total);
        }


        if ($idCondicion == 1 or $idCondicion == 3) {
            if (empty($disponibleTipo)) {
                $registro['por_programar'] = ($registro['ganado'] + $registro['vencido']) - $registro['programado'];
            } else {
                $registro['por_programar'] = $disponibleTipo;
            }
        } else {
            $registro['por_programar'] = $registro['trunco'] - $registro['programado'];
        }

        return $registro;
    }

    public function validarFechas()
    {
        $this->sessionObj->checkJsonRequest();
        $fechaInicio = (isset($_POST['fechaInicio']) && $_POST['fechaInicio'] ? trim($_POST['fechaInicio']) : 0);
        $fechaFin = (isset($_POST['fechaFin']) && $_POST['fechaFin'] ? trim($_POST['fechaFin']) : 0);
        $idCondicion = (isset($_POST['idCondicion']) && $_POST['idCondicion'] ? $_POST['idCondicion'] : 0);
        $idSolicitante = (isset($_POST['idSolicitante']) && $_POST['idSolicitante'] ? $_POST['idSolicitante'] : 0);
        $isMaster = (isset($_POST['master']) && $_POST['master'] ? $_POST['master'] : 0);

        $response = $this->_validarFechas($fechaInicio, $fechaFin, $idCondicion, $idSolicitante, $isMaster);

        $this->view->showJSONPlane(array(
            'response' => $response
        ));
    }

    private function _validarFechas($fechaInicio, $fechaFin, $idCondicion, $idsolicitante, $isMaster = 0)
    {
        $this->sessionObj->checkJsonRequest();

        $response = array('Result' => 'ERROR', 'Message' => '', 'Records' => array());
        require_once $this->getDefaultModelName();
        require_once $this->getModelByName('VacacionConfiguracion', 'vacacionconfiguracion');
        require_once $this->getModelByName('DiasNoLaborable', 'diasnolaborable');
        $vacaConfigObj = new VacacionConfiguracionModel();
        $diasNoLaborableObj = new DiasNoLaborableModel();
        $vacacionModelObj = new VacacionModel();

        $userInfo = $this->sessionObj->getUserInfo();
            
        $dateInicio = new DateTime($fechaInicio);
        $dateFin = new DateTime($fechaFin);
        $interval = $dateInicio->diff($dateFin);
        $numDias = (int) $interval->format('%r%a') + 1;

        $regDiasNoLaborable = $diasNoLaborableObj->getDiasSemanaNoLaborables();

        $minimoDiasReg = $vacaConfigObj->getConfigById(1);
        $maximoDiasReg = $vacaConfigObj->getConfigById(3);

        $periodoActual = $vacacionModelObj->_obtenerPeriodo($idsolicitante);
        $cantDias = $vacacionModelObj->_obtenerDiasRestantesDelPeriodo($idsolicitante, $periodoActual[0]->periodo);
        $diasRestantesDelPeriodo = $cantDias[0]->cantidad_dias;
        

        if($diasRestantesDelPeriodo < 30) {
            $diasTipoActual = $vacacionModelObj->_sumarDiasPorTipo($fechaInicio, $fechaFin);
          
            $diasTipoPrevias = $vacacionModelObj->_calcularDiasFaltantesPeriodoActual($userInfo[0]->DNI, '01', $periodoActual[0]->periodo);
            // print_r($periodoActual[0]->periodo);
           
            // die();
            if(($diasTipoActual['habil'] + $diasTipoPrevias[0]->HABIL) > 22) {
                $resultado['Message'] = 'No puede exceder los días habiles asigandos a su periodo: ' . $periodoActual[0]->periodo;
                return $resultado;
            }
        }


        if ($idCondicion != 3) {
            // $periodoActual = $vacacionModelObj->_obtenerPeriodo($idsolicitante);

            $numSolicitud = $vacacionModelObj->_obtenerNumeroSolicitud($idsolicitante, $periodoActual[0]->periodo);

            // Validar según el número de solicitud
            if ($numSolicitud == 1) {
                $numDiasPeriodo = $vacacionModelObj->_obtenerCantidadDiasPeriodo($idsolicitante);
                $totalDiasPeriodo = $numDiasPeriodo[0]->cantidad_dias;

                $validacion = $this->_validarPrimeraSolicitud($numDias, $totalDiasPeriodo);
                $response = $validacion;
            } else if ($numSolicitud == 2) {
                // Obtener los días consumidos en el subperiodo uno
                $subPeriodoUno = $vacacionModelObj->_obtenerDiasConsumidosPeriodo($idsolicitante, $periodoActual[0]->periodo, 1);
                $totalDiasConsumidosSubPeriodoUno = $subPeriodoUno[0]->dias_consumidos;
                // Obtener los días consumidos en el subperiodo dos
                $subPeriodoDos = $vacacionModelObj->_obtenerDiasConsumidosPeriodo($idsolicitante, $periodoActual[0]->periodo, 2);
                $totalDiasConsumidosSubPeriodoDos = $subPeriodoDos[0]->dias_consumidos;

                $totalAmbosSubPeriodos = $totalDiasConsumidosSubPeriodoUno + $totalDiasConsumidosSubPeriodoDos;
                // $cantDias = $vacacionModelObj->_obtenerDiasRestantesDelPeriodo($idsolicitante, $periodoActual[0]->periodo);
                // $diasRestantesDelPeriodo = $cantDias[0]->cantidad_dias;

                $validacion = $this->_validarSegundaSolicitud($numDias, $totalDiasConsumidosSubPeriodoUno, $totalAmbosSubPeriodos, $diasRestantesDelPeriodo);
                $response = $validacion;
            } else if ($numSolicitud >= 3) {

                // die();
                // Obtener días del subperiodo 1 y 2
                $diasSubperiodo1 = $vacacionModelObj->_obtenerDiasConsumidosPeriodo($idsolicitante, $periodoActual[0]->periodo, 1);
                $diasSubperiodo1Consumidos = $diasSubperiodo1[0]->dias_consumidos;
                $diasSubperiodo2 = $vacacionModelObj->_obtenerDiasConsumidosPeriodo($idsolicitante, $periodoActual[0]->periodo, 2);
                $diasSubperiodo2Consumidos = $diasSubperiodo2[0]->dias_consumidos;

                $diasNoHabil = $vacacionModelObj->_obtenerDiasConsumidosPeriodoNoHabil($idsolicitante, $periodoActual[0]->periodo);
                $diasNoHabilConsumidos = $diasNoHabil[0]->dias_consumidos_no_habil;

                $diasHabil = $vacacionModelObj->_obtenerDiasConsumidosPeriodoHabil($idsolicitante, $periodoActual[0]->periodo);
                $diasHabilConsumidos = $diasHabil[0]->dias_consumidos_habil;

                $validacion = $this->_validarTerceraSolicitud(
                    $diasSubperiodo1Consumidos,
                    $diasSubperiodo2Consumidos,
                    $diasHabilConsumidos,
                    $diasNoHabilConsumidos,
                    $fechaInicio,
                    $fechaFin
                );
                $response = $validacion;

                // if (!$validacion['status']) {
                //     $resultado['mensaje'] = $validacion['mensaje'];
                //     return $resultado;
                // }
            }

            // if($minimoDiasReg[0]->valor <= $numDias || $isMaster){
            //     if($maximoDiasReg[0]->valor >= $numDias || $isMaster){

            //                 $response['Result'] = 'OK';

            //     }else{
            //         $response['Message'] = 'La cantidad máxima de vacaciones es '.$maximoDiasReg[0]->valor;
            //     }
            // }else{
            //     $response['Message'] = 'La cantidad mínima de vacaciones es '.$minimoDiasReg[0]->valor;
            // }
        } else {
            $validacionFechaEspecial = $this->validarFechasEspeciales($fechaInicio, $fechaFin, $idsolicitante);
            $response = $validacionFechaEspecial;
        }
        return $response;
    }

    public function validarFechasEspeciales($fechaInicio, $fechaFin, $idsolicitante)
    {
        $this->sessionObj->checkJsonRequest();

        require_once $this->getDefaultModelName();
        $vacacionModelObj = new VacacionModel();

        $idFechaEspecial = $vacacionModelObj->getFechaEspecial($fechaInicio, $fechaFin, $idsolicitante);

        if (count($idFechaEspecial) > 0) {
            $response['idFechaEspecial'] = $idFechaEspecial[0]->id_vaca_especial;
            $response['Result'] = 'OK';
        } else {
            $response['Result'] = 'ERROR';
            $response['Message'] = 'No dispone de una fecha especial asignada.';
        }

        return $response;
    }

    /**
     * Valida las reglas para la primera solicitud de vacaciones
     * @param int $diasSolicitados Cantidad de días solicitados
     * @return array Resultado de la validación
     */
    private function _validarPrimeraSolicitud($diasSolicitados, $totalDiasPeriodo)
    {
        $resultado = array('Result' => "ERROR", 'Message' => '');

        if($totalDiasPeriodo < 7) {
            $resultado['Message'] = 'Comunicarse con el área de RRHH, para coordinar sobre su solicitud de vacaciones.';
            return $resultado;
        }

        // Validar mínimo 7 días y máximo 30
        if ($diasSolicitados < 7) {
            $resultado['Message'] = 'Su solicitud debe ser de mínimo 7 días.';
            return $resultado;
        }

        if ($diasSolicitados > 30) {
            $resultado['Message'] = 'Su solicitud debe ser de máximo 30 días.';
            return $resultado;
        }

        $resultado['Result'] = 'OK';
        return $resultado;
    }
    private function _validarSegundaSolicitud($diasSolicitados, $diasPrimeraSolicitud, $totalAmbosSubPeriodos, $cantidadDiasPeriodo)
    {
        $resultado = array('Result' => "ERROR", 'Message' => '');

        $diasPendientesRestantes = $cantidadDiasPeriodo - $totalAmbosSubPeriodos;

        // Determinar el mínimo de días según primera solicitud
        $minimoRequerido = ($diasPrimeraSolicitud == 7) ? 8 : 7;

        // Verificar si hay suficientes días en el periodo para cumplir con el mínimo requerido
        if ($diasPendientesRestantes < $minimoRequerido) {
            $resultado['Message'] = "Por favor, contacte a RRHH para solicitar vacaciones coordinadas.";
            return $resultado;
        }

        if ($diasSolicitados < $minimoRequerido) {
            $resultado['Message'] = "Su solicitud debe ser de mínimo $minimoRequerido días.";
            return $resultado;
        }

        $resultado['Result'] = 'OK';
        return $resultado;
    }

    /**
     * Valida las reglas para la tercera solicitud de vacaciones
     * @param int $diasSolicitados Cantidad de días solicitados
     * @param int $diasSubperiodo1 Días consumidos en subperiodo 1
     * @param int $diasNoHabilConsumo Días no hábiles ya consumidos en subperiodo 2
     * @return array Resultado de la validación
     */
    private function _validarTerceraSolicitud($diasSubperiodo1, $diasSubperiodo2, $diasHabilConsumo, $diasNoHabilConsumo, $fechaInicio, $fechaFin)
    {
        require_once $this->getDefaultModelName();
        require_once $this->getModelByName('VacacionConfiguracion', 'vacacionconfiguracion');

        $vacaConfigObj = new VacacionConfiguracionModel();
        $vacacionModelObj = new VacacionModel();

        $resultado = array('Result' => "ERROR", 'Message' => '');

        // Validar que se haya consumido todo el subperiodo 1
        if ($diasSubperiodo1 < 15) {
            $resultado['Message'] = "Debe consumir los 15 días del primer subperiodo antes de registrar esta solicitud.";
            return $resultado;
        }

        // Contar días hábiles y no hábiles en la solicitud actual (rango de fechas)
        $diasTipoActual = $vacacionModelObj->_sumarDiasPorTipo($fechaInicio, $fechaFin);
        $totalDiasSolicitados = $diasTipoActual['habil'] + $diasTipoActual['no_habil'];

        // Obtener configuración para días flexibles
        $diasFlexiblesConfig = $vacaConfigObj->getConfigById(6);
        $diasFlexibles = intval($diasFlexiblesConfig[0]->valor); // Por defecto 5 días flexibles

        // Ya ha consumido los días flexibles, ahora validar los días no hábiles para el segundo subperiodo
        $diasRestantesNoHabils = max(0, 8 - $diasNoHabilConsumo); // Solo se necesitan 4 días no hábiles en el segundo subperiodo

        // Verificar si la solicitud incluye suficientes días no hábiles (sábados o domingos)
        $diasFinDeSemanaIncluidos = $vacacionModelObj->_sumarDiasPorTipo($fechaInicio, $fechaFin);
        $diasNoHabilesxRangoDeFecha = $diasFinDeSemanaIncluidos['no_habil'];
        $diasHabilesxRangoDeFecha = $diasFinDeSemanaIncluidos['habil'];

        if ($diasSubperiodo2 < $diasFlexibles) {
            // Está dentro de los días flexibles, verificar que no sobrepase el límite
            if (($diasSubperiodo2 + $diasTipoActual['habil']) <= $diasFlexibles) {
                $resultado['Result'] = 'OK';
                return $resultado;
            } else {
                if (($diasHabilConsumo + $diasHabilesxRangoDeFecha) <= 22) {
                    $incluyeFinDeSemana = $this->_verificarInclucionFinDeSemana($fechaInicio, $fechaFin);
                    if (!$incluyeFinDeSemana) {
                        $resultado['Message'] = "Su solicitud debe incluir fin de semana.";
                        return $resultado;
                    }
                    $resultado['Result'] = 'OK';
                    return $resultado;
                } else {
                    $resultado['Message'] = "Su solicitud debe incluir fin de semana.";
                    return $resultado;
                }
                // Sobrepasa los días flexibles, debe incluir fin de semana obligatorio
                // Verificar si la solicitud incluye sábado y domingo
                $incluyeFinDeSemana = $this->_verificarInclucionFinDeSemana($fechaInicio, $fechaFin);
                if (!$incluyeFinDeSemana) {
                    $resultado['Message'] = "Al sobrepasar los {$diasFlexibles} días flexibles, debe incluir obligatoriamente un sábado y domingo en su solicitud.";
                    return $resultado;
                }
                $resultado['Result'] = 'OK';
                return $resultado;
            }
        } else {
            if ($diasNoHabilConsumo >= 8) {
                $resultado['Result'] = 'OK';
                return $resultado;
            }

            if (($diasHabilConsumo + $diasHabilesxRangoDeFecha) <= 22) {
                $incluyeFinDeSemana = $this->_verificarInclucionFinDeSemana($fechaInicio, $fechaFin);
                if (!$incluyeFinDeSemana) {
                    $resultado['Message'] = "Su solicitud debe incluir fin de semana.";
                    return $resultado;
                }
                $resultado['Result'] = 'OK';
                return $resultado;
            } else {
                $resultado['Message'] = "Su solicitud debe incluir fin de semana.";
                return $resultado;
            }

            // // Si los días no hábiles solicitados son menores a los 4 requeridos, devolver error
            // if ($diasNoHabilesxRangoDeFecha < $diasRestantesNoHabils) {
            //     $diasRestantes = $diasRestantesNoHabils - $diasNoHabilesxRangoDeFecha;
            //     // echo('Hola'. $diasRestantes .  $diasNoHabilesxRangoDeFecha);
            //     // die();
            //     $resultado['Message'] = "Debe seleccionar fechas que incluyan al menos {$diasRestantes} días adicionales de fin de semana (sábado o domingo).";
            //     return $resultado;
            // }

            // Si el total de días no hábiles es suficiente
            $resultado['Result'] = 'OK';
            return $resultado;
        }
    }

    public function crear()
    {
        $funcion = (isset($_POST['master']) && $_POST['master']) ? '/indexVacacionesMaster' : '';
        $this->sessionObj->checkJsonRequest($this->getAppName() . $funcion, __FUNCTION__);
        $config = Config::singleton();

        require_once $config->get('classesFolder') . 'gump.class.php';
        require_once $this->getDefaultModelName();
        $gump = new GUMP();
        $vacacionModelObj = new VacacionModel();

        $gump->validation_rules(array(
            'cboSolicitante' => 'required',
            'cboCondicion' => 'required',
            'txtFechaInicio' => 'required',
            'txtFechaFin' => 'required',
            'txtCantidadDias' => 'required'
        ));

        $status = $gump->run($_POST);
        $response = array('Result' => 'ERROR', 'Message' => '');

        if ($status) {
            require $config->get('classesFolder') . 'Input.php';
            $input = new CI_Input();

            $statusCantidadDias = $this->_validarCantidadDias($input->post(NULL));

            // Validar que no se seleccione vacaciones truncas si tiene vacaciones vencidas o ganadas
            // y que no se mezclen periodos en una misma solicitud
            $postData = $input->post(NULL);
            $validarCondicionTruncas = $this->_validarCondicionTruncas($postData['cboSolicitante'], $postData['cboCondicion'], $postData);
            if (!$validarCondicionTruncas['status']) {
                $response["Message"] = $validarCondicionTruncas['mensaje'];
                $this->view->showJSONPlane(array('response' => $response));
                return;
            }

            if ($statusCantidadDias['status']) {
                $statusRangoFecha = $this->_validarFechas($input->post('txtFechaInicio'), $input->post('txtFechaFin'), $input->post('cboCondicion'), $input->post('cboSolicitante'), $input->post('master'));
                if ($statusRangoFecha['Result'] === 'OK') {
                    $idVacacionEspecial = isset($statusRangoFecha['idFechaEspecial']) ? $statusRangoFecha['idFechaEspecial'] : null;
                    $vacaCruzadas = $vacacionModelObj->getVacacionesFromDate($input->post('cboSolicitante'), $input->post('txtFechaInicio'), $input->post('txtFechaFin'));
                    if (empty($vacaCruzadas)) {
                        $estadoRegistro = $vacacionModelObj->createVacacion($input->post(NULL), $idVacacionEspecial);
                        if ($estadoRegistro['status']) {
                            $this->sessionObj->RegisterAccion($this->getAppName() . $funcion, __FUNCTION__, $estadoRegistro['id']);
                            $response['Result'] = 'OK';
                            $response['id'] = $estadoRegistro['id'];
                        } else {
                            $response["Message"] = $estadoRegistro['mensaje'];
                        }
                    } else {
                        $response["Message"] = 'Existen vacaciones cruzadas en las fechas indicadas';
                    }
                } else {
                    $response["Message"] = $statusRangoFecha['Message'];
                }
            } else {
                $response["Message"] = $statusCantidadDias['mensaje'];
            }
        } else {
            $response["Message"] = $gump->get_readable_errors(true);
        }

        $this->view->showJSONPlane(array(
            'response' => $response
        ));
    }

    private function _validarCantidadDias($reg)
    {
        require_once $this->getModelByName('Reporte', 'reporte');
        require_once $this->getDefaultModelName();
        $vacacionModelObj = new VacacionModel();
        $reporteModelObj = new ReporteModel();

        $idSolicitud = isset($reg['idSolicitud']) ? $reg['idSolicitud'] : 0;

        $userInfo = $this->sessionObj->getUserInfo($reg['cboSolicitante']);

        $vacacion = $reporteModelObj->getReporteVacaciones(date('d/m/Y'), '01', '', '', '', '', '', "'" . $userInfo[0]->USUARIO . "'");

        $regProgramados = $vacacionModelObj->getNumProgramadas($reg['cboSolicitante'], 1, $idSolicitud);

        $regProgramadosAcuenta = $vacacionModelObj->getNumProgramadas($reg['cboSolicitante'], 2, $idSolicitud);

        $usoVaca = $this->_formatVacionesOfisis($vacacion, $regProgramados, 1);

        //$aCuentaVaca = $this->_formatVacionesOfisis($vacacion,$regProgramadosAcuenta,2);

        $response = array('status' => false, 'mensaje' => '');

        //Validar que acabe sus vacaciones ganadas
        if ($reg['cboCondicion'] == 2 && $usoVaca['por_programar'] > 0) {
            $response['mensaje'] = 'No puede seleccionar adelanto a cuenta de vacaciones truncas, si dispone de vacaciones vencidas o pendientes.';
            return $response;
        }

        //Se validara solo 22 dias habiles
        /*$porProgramar = ($reg['cboCondicion']=='1')?$usoVaca['por_programar']:$aCuentaVaca['por_programar'];
        $dateInicio = new DateTime($reg['txtFechaInicio']);
        $dateFin = new DateTime($reg['txtFechaFin']);
        $interval = $dateInicio->diff($dateFin);
        $numDias = (int)$interval->format('%d')+1;

        if($porProgramar >= $numDias){*/
        $response['status'] = true;
        /*}else{
            $response['mensaje'] = 'La cantidad de dias de vacaciones no debe ser mayor a '.$porProgramar;
        }*/

        return $response;
    }


    /**
     * Validar condición de vacaciones truncas y que no se mezclen periodos
     */
    private function _validarCondicionTruncas($idSolicitante, $condicion, $postData)
    {
        $result = array('status' => true, 'mensaje' => '');

        // Obtener el consolidado de vacaciones del JSON
        $consolidado = json_decode($postData['tblConsolidado'], true);
        $diasVencidos = isset($consolidado['vencido']) ? floatval($consolidado['vencido']) : 0;
        $diasGanados = isset($consolidado['ganado']) ? floatval($consolidado['ganado']) : 0;
        $diasTruncos = isset($consolidado['trunco']) ? floatval($consolidado['trunco']) : 0;
        $programado = isset($consolidado['programado']) ? floatval($consolidado['programado']) : 0;
        $programadosPrevios = isset($postData['programadosPrevios']) ? floatval($postData['programadosPrevios']) : 0;


        // Validar que no se seleccione vacaciones truncas (condición 2) si hay vencidas o ganadas
        if ($condicion == '2') {
            // if ($diasVencidos > 0 || $diasGanados > 0 || ($diasVencidos + $diasGanados) >  $programado) {
            if (max(
                0,
                (($diasVencidos ?: 0) +
                ($diasGanados?: 0)) -
                $programadosPrevios
            ) > 0) {
                $result['status'] = false;
                $result['mensaje'] = 'No puede seleccionar adelanto a cuenta de vacaciones truncas, si dispone de vacaciones vencidas o pendientes.';
                return $result;
            }
        }

        // Validar que la cantidad de días solicitada no exceda el periodo seleccionado
        $cantidadDias = intval($postData['txtCantidadDias']);
        $disponiblesPeriodo = 0;

        if ($condicion == '1') {
            // Para condición 1, usar primero vencidas, luego ganadas
            if ($diasVencidos > 0) {
                $disponiblesPeriodo = $diasVencidos - $programado;
                $tipoVacacion = 'vencidas';
            } else {
                $disponiblesPeriodo = $diasGanados - $programado;
                $tipoVacacion = 'pendientes';
            }
        } else if ($condicion == '2') {
            // Para condición 2, solo truncas
            $disponiblesPeriodo = $diasTruncos - $programado;
            $tipoVacacion = 'truncas';
        } else {
            return $result;
        }

        if ($cantidadDias > $disponiblesPeriodo) {
            $result['status'] = false;
            $result['mensaje'] = "Solo tienes disponible " . number_format($disponiblesPeriodo, 2) . " días en el periodo de vacaciones $tipoVacacion . Por favor, ajusta la cantidad de días solicitados.";
        }

        return $result;
    }


    public function buscarUsuario()
    {
        $this->sessionObj->checkJsonRequest();

        $qryName = (isset($_REQUEST['q']) && $_REQUEST['q'] ? trim($_REQUEST['q']) : '');

        require $this->getModelByName('Directorio', 'directorio');
        require $this->getModelByName('BoletaGeneradores', 'boletageneradores');
        $boletaGeneObj = new BoletaGeneradoresModel();
        $directorioObj = new DirectorioModel();

        $userInfo = $this->sessionObj->getUserInfo();
        $gerencias_gene = $boletaGeneObj->getGenerador("", "", $userInfo[0]->ID_USUARIO, 3);

        if (empty($gerencias_gene)) {
            $regDirectorio = $directorioObj->buscarSimplePersona($qryName);
        } else {
            $gerencias = '';
            $comma = '';
            $userInfo = $this->sessionObj->getUserInfo();

            foreach ($gerencias_gene as $key => $row) {
                $gerencias .= $comma . "'" . $row->idUnidad . "'";
            }

            $regDirectorio = $directorioObj->buscarSimplePersonaGerencia($qryName, $gerencias, $userInfo[0]->DNI);
        }
        $ajaxResponse = array();

        if ($regDirectorio) {
            $count = 0;
            foreach ($regDirectorio as $row) {
                //Retirar a los practicantes (Para generar solicitud de vacaciones)
                if ($row->CO_PLAN <> 'FLJ') {
                    $ajaxResponse[$count]['Value'] = $row->CO_TRAB;
                    $ajaxResponse[$count]['DisplayText'] = $row->nombres;
                    $ajaxResponse[$count]['DisplayText2'] = $row->area;
                    $ajaxResponse[$count]['fechaIngreso'] = $row->FE_INGR_EMPR;
                    $count++;
                }
            }
        }

        header('content-type: json');
        echo json_encode($ajaxResponse);
        exit();
    }

    public function buscarSolicitante()
    {
        $this->sessionObj->checkJsonRequest();

        $qryName = (isset($_REQUEST['q']) && $_REQUEST['q'] ? trim($_REQUEST['q']) : '');
        $isMaster = (isset($_REQUEST['master']) && $_REQUEST['master'] ? $_REQUEST['q'] : 0);

        require $this->getModelByName('Directorio', 'directorio');
        require $this->getModelByName('BoletaGeneradores', 'boletageneradores');
        $boletaGeneObj = new BoletaGeneradoresModel();
        $directorioObj = new DirectorioModel();

        $userInfo = $this->sessionObj->getUserInfo();
        $gerencias_gene = $boletaGeneObj->getGenerador("", "", $userInfo[0]->ID_USUARIO, 3);

        if (empty($gerencias_gene) || $isMaster) {
            $regDirectorio = $directorioObj->buscarSimplePersona($qryName);
        } else {
            $gerencias = '';
            $comma = '';
            $userInfo = $this->sessionObj->getUserInfo();

            foreach ($gerencias_gene as $key => $row) {
                $gerencias .= $comma . "'" . $row->idUnidad . "'";
                $comma = ',';
            }

            $jerarquias = $boletaGeneObj->getJerarquiaGeneracion("01", $userInfo[0]->ID_USUARIO, 3);
            $regDirectorio = $directorioObj->buscarSimplePersonaGerencia($qryName, $gerencias, $userInfo[0]->DNI, $jerarquias);
        }
        $ajaxResponse = array();

        if ($regDirectorio) {
            $count = 0;
            foreach ($regDirectorio as $row) {
                //Retirar a los practicantes (Para generar solicitud de vacaciones)
                if ($row->CO_PLAN <> 'FLJ') {
                    $userInfo = $this->sessionObj->getUserInfo($row->CO_TRAB, true); //Obtener el codigo de usuario (seguridad)
                    if (empty($userInfo)) {
                        continue;
                    }
                    $ajaxResponse[$count]['Value'] = $userInfo[0]->ID_USUARIO;
                    $ajaxResponse[$count]['DisplayText'] = $row->nombres;
                    $ajaxResponse[$count]['DisplayText2'] = $row->area;
                    $ajaxResponse[$count]['fechaIngreso'] = $row->FE_INGR_EMPR;
                    $count++;
                }
            }
        }

        header('content-type: json');
        echo json_encode($ajaxResponse);
        exit();
    }

    public function confirmarSolicitud()
    {
        $this->sessionObj->checkJsonRequest();

        $config = Config::singleton();
        require_once $config->get('classesFolder') . 'gump.class.php';
        $gump = new GUMP();

        $gump->validation_rules(array(
            'id_vacacion' => 'required'
        ));

        $status = $gump->run($_POST);

        if ($status) {
            require $this->getDefaultModelName();
            require $config->get('classesFolder') . 'Input.php';
            $vacacionModelObj = new VacacionModel();
            $input = new CI_Input();

            //Verificar si la solicitud aun se encuentra pendiente de aprobación
            $regValid = $vacacionModelObj->getInfoVacacion($input->post('id_vacacion', true), 0);

            if ($regValid && $regValid[0]->confirmado === 0) {
                $status = $vacacionModelObj->confirmSolicitudVacacion($input->post('id_vacacion', true));

                if ($status && $status['idSolicitudAutorizacion'] !== 0 && $status['NumError'] === 0) {
                    //Envio de correo para la confirmacion
                    require $this->getModelByName('VacacionAutorizacion', 'vacacionautorizacion');
                    $authModelObj = new VacacionAutorizacionModel();
                    $authModelObj->processMail($regValid, $status['idAuth']);

                    $response["Result"] = 'OK';
                } else {
                    $response["Result"] = 'ERROR';
                    if ($status['NumError'] === 50000) {
                        $response["Message"] = $status['MsgError'];
                    } else {
                        $response["Message"] = 'Ocurrió un error el confirmar la solicitud de vacación.';
                    }
                }
            } else {
                $response["Result"] = 'ERROR';
                $response["Message"] = 'La solicitud de vacación no se encuentra disponible para la confirmación.';
            }
        } else {
            $response["Result"] = 'ERROR';
            $response["Message"] = $gump->get_readable_errors(true);
        }

        //Finalmente presentamos el registro como JSON
        $this->view->showJSONPlane(array(
            'response' => $response
        ));
    }

    public function borrar()
    {
        $funcion = (isset($_POST['master']) && $_POST['master']) ? '/indexVacacionesMaster' : '';
        $this->sessionObj->checkJsonRequest($this->getAppName() . $funcion, __FUNCTION__);

        $config = Config::singleton();
        require_once $config->get('classesFolder') . 'gump.class.php';
        $gump = new GUMP();

        $gump->validation_rules(array(
            'id_vacacion' => 'required'
        ));

        $status = $gump->run($_POST);
        $response = array('Result' => 'ERROR');

        if ($status) {
            require $this->getDefaultModelName();
            require $config->get('classesFolder') . 'Input.php';
            $vacacionModelObj = new VacacionModel();
            $input = new CI_Input();
            $diaseliminar = $vacacionModelObj->getDiasEliminar();
            $userInfo = $this->sessionObj->getUserInfo();
            $regVacacion = $vacacionModelObj->getInfoVacacion($input->post('id_vacacion', true), 0);
            //obtener fecha actual para comprar
            $hoy = new DateTime(date('Y-m-d'));
            $interval = $hoy->diff($regVacacion[0]->fecha_crea);
            $dias = (int) $interval->format('%d');
            if ($dias <= $diaseliminar[0]->valor) {

                $validacion = $this->_validarParaEliminar((array) $regVacacion[0], $userInfo[0]->ID_USUARIO, $input->post('master'));
                if ($validacion['resultado']) {
                    //Eliminar logicamente y no eliminar archivos adjuntos
                    $vacacionModelObj->eliminarLogicamente($input->post('id_vacacion', true));
                    $this->sessionObj->RegisterAccion($this->getAppName() . $funcion, __FUNCTION__, $input->post('id_vacacion', true));
                    $response["Result"] = 'OK';
                } else {
                    $response["Message"] = $validacion['mensaje'];
                }
            } else {
                $response["Message"] = "Supera la fecha limite para eliminar el registro que es de 5 dias desde la fecha de creacion";
                $response["Result"] = 'ERROR';
            }
        } else {
            $response["Message"] = $gump->get_readable_errors(true);
        }

        //Finalmente presentamos el registro como JSON
        $this->view->showJSONPlane(array(
            'response' => $response
        ));
    }

    private function _validarParaEliminar($reg, $idUsuario, $master = 0)
    {
        $response = array('resultado' => false);

        //Si es master, no considerar las validaciones
        if (empty($master)) {
            if ($reg['id_vaca_estado'] == 4 || $reg['id_vaca_estado'] == 5 || $reg['id_vaca_estado'] == 6) {
                $response['mensaje'] = 'La solicitud ya se encuentra liberada completamente';
                return $response;
            }

            if ($reg['id_solicitante'] != $idUsuario && $reg['id_generador'] != $idUsuario) {
                $response['mensaje'] = 'La solicitud solo puede ser modificada por el solicitante o el generador de la solicitud';
                return $response;
            }
        }

        $response['resultado'] = true;
        return $response;
    }

    public function confirmarEjecucion()
    {
        $this->sessionObj->checkJsonRequest();

        $config = Config::singleton();
        require_once $config->get('classesFolder') . 'gump.class.php';
        $gump = new GUMP();

        $gump->validation_rules(array(
            'id_vacacion' => 'required'
        ));

        $status = $gump->run($_POST);

        if ($status) {
            require $this->getDefaultModelName();
            require $config->get('classesFolder') . 'Input.php';
            $vacacionModelObj = new VacacionModel();
            $input = new CI_Input();

            //Verificar si la solicitud aun se encuentra pendiente de aprobación
            $regValid = $vacacionModelObj->getInfoVacacion($input->post('id_vacacion', true), 0);

            if ($regValid && $regValid[0]->id_vaca_estado == '4') {
                $status = $vacacionModelObj->confirmEjecuciónVacacion($input->post('id_vacacion', true));
                if ($status) {
                    $response["Result"] = 'OK';
                } else {
                    $response["Result"] = 'ERROR';
                    $response["Message"] = 'Ocurrió un error el confirmar la solicitud de vacación.';
                }
            } else {
                $response["Result"] = 'ERROR';
                $response["Message"] = 'La solicitud de vacación no se encuentra disponible para la confirmación.';
            }
        } else {
            $response["Result"] = 'ERROR';
            $response["Message"] = $gump->get_readable_errors(true);
        }

        //Finalmente presentamos el registro como JSON
        $this->view->showJSONPlane(array(
            'response' => $response
        ));
    }

    public function exportarBoleta()
    {
        $this->sessionObj->checkJsonRequest();

        $qryIdVacacion = (isset($_POST['qryIdVacacion']) && $_POST['qryIdVacacion'] ? trim($_POST['qryIdVacacion']) : 0);

        //Verificar el correcto envio de parametros
        if ($qryIdVacacion !== 0) {
            require $this->getDefaultModelName();
            require $this->getModelByName('VacacionAutorizacion', 'vacacionautorizacion');

            $vacacionModelObj = new VacacionModel();
            $authModelObj = new VacacionAutorizacionModel();
            $regBoleta = $vacacionModelObj->getInfoVacacion($qryIdVacacion);
            $regBoletaAuth = $authModelObj->getAuthBySolicitud($qryIdVacacion);

            //verificar la existencia y estado ("APROBADO") de la boleta de permiso
            if ($regBoleta && ($regBoleta[0]->id_vaca_estado === 4 || $regBoleta[0]->id_vaca_estado === 5)) {

                // INCLUIR LA LIBRERIA PDF
                $data['solicitud'] = $regBoleta;
                $data['autorizacion'] = $regBoletaAuth;

                $config = Config::singleton();
                ob_start();

                $this->view->show(array(
                    'filename' => "impresion.tpl.php",
                    'data' => $data
                ));
                $content = ob_get_contents();

                ob_end_clean();

                require_once $config->get('libsFolder') . 'html2pdf/vendor/autoload.php';
                try {

                    $html2pdf = new Html2Pdf('P', 'A4', 'es', true, 'UTF-8', array(10, 5, 10, 10));
                    $html2pdf->setDefaultFont('Arial');
                    $html2pdf->pdf->SetTitle('Solicitud_' . $qryIdVacacion);
                    $html2pdf->writeHTML($content);
                    $html2pdf->output('Solicitud_' . $qryIdVacacion . '.pdf');
                } catch (Html2PdfException $e) {
                    $html2pdf->clean();
                    $formatter = new ExceptionFormatter($e);
                    echo $formatter->getHtmlMessage();
                }
            } else {
                echo "La boleta no existe o no se encuentra completamente aprobada";
                exit();
            }
        } else {
            echo "Faltan especificar parámetros";
            exit();
        }
    }

    /************************************************* FUNCIONALIDADES PARA LA EDICION *********************************************/

    public function editar()
    {
        $funcion = (isset($_POST['master']) && $_POST['master']) ? '/indexVacacionesMaster' : '';
        $this->sessionObj->checkJsonRequest($this->getAppName() . $funcion, __FUNCTION__);
        $config = Config::singleton();

        require_once $config->get('classesFolder') . 'gump.class.php';
        require_once $this->getDefaultModelName();
        $gump = new GUMP();
        $vacacionModelObj = new VacacionModel();

        $gump->validation_rules(array(
            'idSolicitud' => 'required',
            'cboSolicitante' => 'required',
            'cboCondicion' => 'required',
            'txtFechaInicio' => 'required',
            'txtFechaFin' => 'required',
            'txtCantidadDias' => 'required'
        ));

        $status = $gump->run($_POST);
        $response = array('Result' => 'ERROR', 'Message' => '');

        if ($status) {
            require $config->get('classesFolder') . 'Input.php';
            $input = new CI_Input();

            $statusCantidadDias = $this->_validarCantidadDias($input->post(NULL));
            if ($statusCantidadDias['status']) {
                $statusRangoFecha = $this->_validarFechas($input->post('txtFechaInicio'), $input->post('txtFechaFin'), $input->post('cboCondicion'), $input->post('cboSolicitante'), $input->post('master'));
                if ($statusRangoFecha['Result'] === 'OK') {
                    $vacaCruzadas = $vacacionModelObj->getVacacionesFromDate($input->post('cboSolicitante'), $input->post('txtFechaInicio'), $input->post('txtFechaFin'), $input->post('idSolicitud'));
                    if (empty($vacaCruzadas)) {
                        $estadoRegistro = $vacacionModelObj->editVacacion($input->post(NULL));
                        if ($estadoRegistro['status']) {
                            $this->sessionObj->RegisterAccion($this->getAppName() . $funcion, __FUNCTION__, $input->post('idSolicitud'));
                            $response['Result'] = 'OK';
                            $response['id'] = $estadoRegistro['id'];
                        } else {
                            $response["Message"] = $estadoRegistro['mensaje'];
                        }
                    } else {
                        $response["Message"] = 'Existen vacaciones cruzadas en las fechas indicadas';
                    }
                } else {
                    $response["Message"] = $statusRangoFecha['Message'];
                }
            } else {
                $response["Message"] = $statusCantidadDias['mensaje'];
            }
        } else {
            $response["Message"] = $gump->get_readable_errors(true);
        }

        $this->view->showJSONPlane(array(
            'response' => $response
        ));
    }

    public function exportar($master = false)
    {
        $app = $this->getAppName();

        if ($master) {
            $app .= '/indexVacacionesMaster';
        }
        $this->sessionObj->checkJsonRequest($app, "exportar");
        $this->sessionObj->RegisterAccion($app, "exportar", 0);

        $config = Config::singleton();
        require_once $this->getDefaultModelName();
        require_once $config->get('libsFolder') . 'PHPExcel/PHPExcel.php';
        $vacacionModelObj = new VacacionModel();

        $dnis = !$master;

        if (!$master) {
            // echo $this->getModelByName('Reporte','reporte');
            require $this->getModelByName('Reporte', 'reporte');
            $reporteModelObj = new ReporteModel();
            $userInfo = $this->sessionObj->getUserInfo();
            $dnis = $reporteModelObj->getDnisJerarquia2($userInfo[0]->DNI);
        }
        $arrVacaciones = $vacacionModelObj->listarVacacionesExport($_POST['qry_empresa'], $_POST['qry_gerencia'], $_POST['qry_departamento'], $_POST['qry_area'], $_POST['qry_seccion'], $_POST['qry_ini_rango'], $_POST['qry_fin_rango'], $dnis, $_POST['qry_colaborador']);

        if (empty($arrVacaciones)) {
            echo "No se encontraron registros";
            exit();
        }
        $this->_exportarDataBoleta($arrVacaciones);
    }

    public function exportarMaster()
    {
        $this->exportar(true);
    }

    public function exportarMasterCP()
    {
        $this->sessionObj->checkJsonRequest($this->getAppName() . '/indexVacacionesMasterCP', "exportar");
        $this->sessionObj->RegisterAccion($this->getAppName() . '/indexVacacionesMasterCP', "exportar", 0);
        $qryGerencia = (isset($_REQUEST['qry_gerencia']) && $_REQUEST['qry_gerencia'] ? trim($_REQUEST['qry_gerencia']) : 0);

        $config = Config::singleton();
        require_once $this->getDefaultModelName();
        require_once $config->get('libsFolder') . 'PHPExcel/PHPExcel.php';
        require $this->getModelByName('VacacionAsignacion', 'vacacionasignacion');
        $vacacionModelObj = new VacacionModel();
        $asignacionModelObj = new VacacionAsignacionModel();
        $userInfo = $this->sessionObj->getUserInfo();
        $arrVacaciones = array();
        $dnis = '';


        if (empty($qryGerencia)) {
            $gerencias = $asignacionModelObj->getVacacionAsignacionByUser($userInfo[0]->ID_USUARIO);
        }

        // Si filtro viene por gerencia, o no viene por gerencia pero tiene gerencias configuradas
        if ((empty($qryGerencia) && $gerencias) || $qryGerencia) {

            //Asignar el listado de gerencias configuradas
            if (empty($qryGerencia)) {
                $qryGerencia = array();
                foreach ($gerencias as $row) {
                    $qryGerencia[] = $row->id_gerencia;
                }
            }

            $arrVacaciones = $vacacionModelObj->listarVacacionesExport($_POST['qry_empresa'], $qryGerencia, $_POST['qry_departamento'], $_POST['qry_area'], $_POST['qry_seccion'], $_POST['qry_ini_rango'], $_POST['qry_fin_rango'], $dnis, $_POST['qry_colaborador']);
        }

        if (empty($arrVacaciones)) {
            echo "No se encontraron registros";
            exit();
        }
        $this->_exportarDataBoleta($arrVacaciones);
    }

    private function _exportarDataBoleta($arrVacaciones)
    {
        $columName = array('ID', 'GERENCIA', 'DEPARTAMENTO', 'AREA', 'SECCION', 'SOLICITANTE', 'DNI SOLICITANTE', 'GENERADOR', 'DNI GENERADOR', 'FECHA SOLICITUD', 'CONDICION', 'FECHA INICIO', 'FECHA_FIN', 'CANTIDAD DIAS', 'ESTADO', 'TIPO', 'AUTORIZADOR 1RA', 'DNI AUTORIZADOR 1RA', 'AUTORIZADOR 2DA', 'DNI AUTORIZADOR 2DA');
        $lastRow = count($arrVacaciones) + 1;
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Laboratorios ACFARMA - Sistemas")->setTitle("Listado de Boletas de vacaciones");
        $objPHPExcel->setActiveSheetIndex(0)->setTitle("Solicitudes Vacaciones");
        $objPHPExcel->getActiveSheet()->setShowGridlines(false);

        // Definimos el Color y Formato de Borde
        $objPHPExcel->getActiveSheet()->getStyle('A1:T1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objPHPExcel->getActiveSheet()->getStyle('A1:T1')->getFill()->getStartColor()->setRGB('b23535');
        // Definimos el color y fuente de la fi primera Fila
        $objPHPExcel->getActiveSheet()->getStyle('A1:T1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $objPHPExcel->getActiveSheet()->getStyle('A1:T1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle('A1:T1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //********************************************** CUERPO DE INFORME ***********************************************************
        // Recorremos el resultado de la consulta
        $objPHPExcel->getActiveSheet()->fromArray($columName, null, "A1");
        foreach ($arrVacaciones as $index => $row) {
            $objPHPExcel->getActiveSheet()->fromArray((array) $row, null, "A" . ($index + 2));
        }

        //Pintamos los bordes del cuerpo
        $objPHPExcel->getActiveSheet()->getStyle('A1:T' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        //************************************************* FINAL DE INFORME ****************************************************************

        //Formato a celdas
        $objPHPExcel->getActiveSheet()->getStyle('J2:J' . $lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $objPHPExcel->getActiveSheet()->getStyle('L2:M' . $lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy');

        //Set autoZise Columns
        foreach (range('A', 'T') as $columnID) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
        }
        //FILTER

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $filename = 'Solicitud_vacaciones_' . date('Y-m-d_H-i') . '.xlsx';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
    }

    /************************************************ NOTIFICACIONES DE CONFIRMACION ************************************************/

    public function notificarPendientesEjecucion()
    {
        $request = ManageRequest::singleton();
        $config = Config::singleton();

        $params = $request->getParams();
        $token = $config->get('keyExecJob');

        if (empty($params) || empty($params[0]) || $params[0] !== $token) {
            echo 'Clave incorrecta para inicio de Proceso';
            exit();
        }

        require $this->getDefaultModelName();
        $vacacionModelObj = new VacacionModel();
        $pendientes = $vacacionModelObj->getPendientesEjecucion();

        if (empty($pendientes)) {
            echo 'No existen pendientes a notificar';
            exit();
        }

        foreach ($pendientes as $vacacion) {
            $vacacionModelObj->proccessMailPendienteEjecucion($vacacion);
        }

        echo 'Se procesaron las notificaciones';
        exit();
    }

    public function notificarPendientesEjecucionGerencia()
    {
        $request = ManageRequest::singleton();
        $config = Config::singleton();

        $params = $request->getParams();
        $token = $config->get('keyExecJob');

        if (empty($params) || empty($params[0]) || $params[0] !== $token) {
            echo 'Clave incorrecta para inicio de Proceso';
            exit();
        }

        require_once $this->getModelByName('BoletaInstancias', 'boletainstancias');
        require $this->getDefaultModelName();
        $vacacionModelObj = new VacacionModel();
        $boletaInstObj = new BoletaInstanciasModel();

        //$estrategias = $authModelObj->getEstrategia();
        $jerarquias = $boletaInstObj->getAprobadoresJerarquia('2', 3);

        foreach ($jerarquias as $estrategia) {
            $solicitudes = $vacacionModelObj->getPendientesEjecucion($estrategia->idUnidad);
            if (!empty($solicitudes)) {
                $vacacionModelObj->processMailPendienteEjecucionGerencia($solicitudes);
            }
        }

        echo "Se procesaron los correos para las distintas Estrategias";
    }

    /********************************************* REPORTE DE CRONOGRAMA DE VACACIONES *****************************************/
    public function indexCronogramaVacaciones()
    {

        $this->sessionObj->checkInner();
        require $this->getDefaultModelName();
        require $this->getModelByName('Directorio', 'directorio');
        require_once $this->getModelByName('VacacionConfiguracion', 'vacacionconfiguracion');

        $vacacionModelObj = new VacacionModel();
        $directorioModelObj = new DirectorioModel();
        $vacaConfigObj = new VacacionConfiguracionModel();

        $data['estados'] = $vacaConfigObj->getEstados();
        $data['cboEmpresa'] = $directorioModelObj->getEmpresaGroup(array('01'));
        $data['cboCondicion'] = $vacacionModelObj->listarCondicionCombo(array('01'));
        // $data['maximoDiasReprogramar'] = $minimoDiasEditReg[0]->valor;

        $data['cboEmpresa'] = $directorioModelObj->getEmpresaGroup(array('01'));

        // $data['gerencias_ope'] = $asignacionModelObj->getVacacionAsignacionByUser($userInfo[0]->ID_USUARIO);

        $data['rootFolder'] = $this->getRootFolder();
        $data['protocol'] = $this->getCurrentProtocol();
        $data['obj'] = $this;

        $this->sessionObj->RegisterAuditModule($this->getAppName() . '/' . __FUNCTION__);
        $this->view->show(array(
            'filename' => "reporteCronogramaVacaciones.tpl.php",
            'data' => $data,
        ));
    }

    public function listarCronograma()
    {
        $this->sessionObj->checkJsonRequest();

        $qry_empresa = (isset($_REQUEST['qry_empresa']) && $_REQUEST['qry_empresa'] ? trim($_REQUEST['qry_empresa']) : '');
        $qry_gerencia = (isset($_REQUEST['qry_gerencia']) && $_REQUEST['qry_gerencia'] ? trim($_REQUEST['qry_gerencia']) : '');
        $qryDepartamento = (isset($_REQUEST['qry_departamento']) && $_REQUEST['qry_departamento'] ? trim($_REQUEST['qry_departamento']) : '');
        $qry_area = (isset($_REQUEST['qry_area']) && $_REQUEST['qry_area'] ? trim($_REQUEST['qry_area']) : '');
        $qry_seccion = (isset($_REQUEST['qry_seccion']) && $_REQUEST['qry_seccion'] ? trim($_REQUEST['qry_seccion']) : '');
        $qry_estado = (isset($_REQUEST['qry_estado']) && $_REQUEST['qry_estado'] ? trim($_REQUEST['qry_estado']) : '');
        $qry_colaborador = (isset($_REQUEST['qry_colaborador']) && $_REQUEST['qry_colaborador'] ? trim($_REQUEST['qry_colaborador']) : '');
        $qry_ini_rango = (isset($_REQUEST['qry_ini_rango']) && $_REQUEST['qry_ini_rango'] ? trim($_REQUEST['qry_ini_rango']) : 0);
        $qry_fin_rango = (isset($_REQUEST['qry_fin_rango']) && $_REQUEST['qry_fin_rango'] ? trim($_REQUEST['qry_fin_rango']) : 0);


        require $this->getModelByName('Reporte', 'reporte');
        require $this->getDefaultModelName();
        $vacacionModelObj = new VacacionModel();
        $reporteModelObj = new ReporteModel();
        $colaborador = $qry_colaborador;
        $estado = $qry_estado;

        $userInfo = $this->sessionObj->getUserInfo();
        $dnis = $reporteModelObj->getDnisJerarquia2($userInfo[0]->DNI);
        $dnis = ($dnis) ? $dnis : '';

        $regProgramados = $vacacionModelObj->listCronograma($qry_empresa, $qry_gerencia, $qryDepartamento, $qry_area, $qry_seccion, $estado, $colaborador, $qry_ini_rango, $qry_fin_rango, $dnis);

        $colaboradores = $this->_armandoArrayEstados($regProgramados);
        $regProgr['data'] = $colaboradores;

        $this->view->showJSONPlane(array(
            'response' => $regProgr
        ));
    }

    public function exportarCronograma2()
    {
        $this->sessionObj->checkJsonRequest();

        $qry_empresa = (isset($_REQUEST['qry_empresa']) && $_REQUEST['qry_empresa'] ? trim($_REQUEST['qry_empresa']) : '');
        $qry_gerencia = (isset($_REQUEST['qry_gerencia']) && $_REQUEST['qry_gerencia'] ? trim($_REQUEST['qry_gerencia']) : '');
        $qry_area = (isset($_REQUEST['qry_area']) && $_REQUEST['qry_area'] ? trim($_REQUEST['qry_area']) : '');
        $qry_seccion = (isset($_REQUEST['qry_seccion']) && $_REQUEST['qry_seccion'] ? trim($_REQUEST['qry_seccion']) : '');
        $qry_estado = (isset($_REQUEST['qry_estado']) && $_REQUEST['qry_estado'] ? trim($_REQUEST['qry_estado']) : '');
        $qry_colaborador = (isset($_REQUEST['qry_colaborador']) && $_REQUEST['qry_colaborador'] ? trim($_REQUEST['qry_colaborador']) : '');
        $qry_ini_rango = (isset($_REQUEST['qry_ini_rango']) && $_REQUEST['qry_ini_rango'] ? trim($_REQUEST['qry_ini_rango']) : 0);
        $qry_fin_rango = (isset($_REQUEST['qry_fin_rango']) && $_REQUEST['qry_fin_rango'] ? trim($_REQUEST['qry_fin_rango']) : 0);

        $config = Config::singleton();
        require $this->getModelByName('Reporte', 'reporte');
        require $this->getDefaultModelName();
        require_once $config->get('libsFolder') . 'PHPExcel/PHPExcel.php';

        $vacacionModelObj = new VacacionModel();
        $reporteModelObj = new ReporteModel();

        $colaborador = $qry_colaborador;
        $estado = $qry_estado;
        $regProgramados = $vacacionModelObj->listCronograma($qry_empresa, $qry_gerencia, $qry_area, $qry_seccion, $estado, $colaborador, $qry_ini_rango, $qry_fin_rango);

        $columnfilaA = array('EMPRESA', 'GERENCIA', 'AREA', 'SECCION', "DOCUMENTOS", 'COLABORADOR', 'ESTADO', $qry_ini_rango);
        $columName = array(
            'Ene.',
            'Feb.',
            'Mar.',
            'Abr.',
            'May.',
            'Jun.',
            'Jul.',
            'Ago.',
            'Set.',
            'Oct.',
            'Nov.',
            'Dic.',
            'Ene.',
            'Feb.',
            'Mar.',
            'Abr.',
            'May.',
            'Jun.',
            'Jul.',
            'Ago.',
            'Set.',
            'Oct.',
            'Nov.',
            'Dic.'
        );

        $lastRow = count($regProgramados) + 2;
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Laboratorios ACFARMA - Sistemas")->setTitle("Listado de Boletas de vacaciones");
        $objPHPExcel->setActiveSheetIndex(0)->setTitle("Solicitudes Vacaciones");
        $objPHPExcel->getActiveSheet()->setShowGridlines(false);

        // Definimos el Color y Formato de Borde
        $objPHPExcel->getActiveSheet()->getStyle('A1:AE2')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objPHPExcel->getActiveSheet()->getStyle('A1:AE2')->getFill()->getStartColor()->setRGB('b23535');
        // Definimos el color y fuente de la fi primera Fila
        $objPHPExcel->getActiveSheet()->getStyle('A1:AE2')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $objPHPExcel->getActiveSheet()->getStyle('A1:AE2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle('A1:AE2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //********************************************** CUERPO DE INFORME ***********************************************************
        // Recorremos el resultado de la consulta
        $objPHPExcel->getActiveSheet()->mergeCells('A1:A2');
        $objPHPExcel->getActiveSheet()->mergeCells('B1:B2');
        $objPHPExcel->getActiveSheet()->mergeCells('C1:C2');
        $objPHPExcel->getActiveSheet()->mergeCells('D1:D2');
        $objPHPExcel->getActiveSheet()->mergeCells('E1:E2');
        $objPHPExcel->getActiveSheet()->mergeCells('F1:F2');
        $objPHPExcel->getActiveSheet()->mergeCells('G1:G2');
        $objPHPExcel->getActiveSheet()->mergeCells('H1:S1');
        $objPHPExcel->getActiveSheet()->mergeCells('T1:AE1');
        $objPHPExcel->getActiveSheet()->getCell('T1')->setValue($qry_fin_rango);
        $objPHPExcel->getActiveSheet()->fromArray($columnfilaA, null, "A1");
        $objPHPExcel->getActiveSheet()->fromArray($columName, null, "H2");

        $noPrintCol = array('id_vaca_estado', 'color');
        foreach ($regProgramados as $index => $row) {

            foreach ($noPrintCol as $value) {
                unset($row->$value);
            }
            $objPHPExcel->getActiveSheet()->fromArray((array) $row, null, "A" . ($index + 3));
        }

        //Pintamos los bordes del cuerpo
        $objPHPExcel->getActiveSheet()->getStyle('A3:AE' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        //************************************************* FINAL DE INFORME ****************************************************************

        //Formato a celdas
        $objPHPExcel->getActiveSheet()->getStyle('F2:F' . $lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $objPHPExcel->getActiveSheet()->getStyle('H2:I' . $lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy');

        //Set autoZise Columns
        foreach (range('A', 'Z') as $columnID) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
        }
        //FILTER

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $filename = 'Solicitud_vacaciones_' . date('Y-m-d_H-i') . '.xlsx';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
    }

    public function exportarCronograma()
    {
        $this->sessionObj->checkJsonRequest();

        $qry_empresa = (isset($_REQUEST['qry_empresa']) && $_REQUEST['qry_empresa'] ? trim($_REQUEST['qry_empresa']) : '');
        $qry_gerencia = (isset($_REQUEST['qry_gerencia']) && $_REQUEST['qry_gerencia'] ? trim($_REQUEST['qry_gerencia']) : '');
        $qryDepartamento = (isset($_REQUEST['qry_departamento']) && $_REQUEST['qry_departamento'] ? trim($_REQUEST['qry_departamento']) : '');
        $qry_area = (isset($_REQUEST['qry_area']) && $_REQUEST['qry_area'] ? trim($_REQUEST['qry_area']) : '');
        $qry_seccion = (isset($_REQUEST['qry_seccion']) && $_REQUEST['qry_seccion'] ? trim($_REQUEST['qry_seccion']) : '');
        $qry_estado = (isset($_REQUEST['qry_estado']) && $_REQUEST['qry_estado'] ? trim($_REQUEST['qry_estado']) : '');
        $qry_colaborador = (isset($_REQUEST['qry_colaborador']) && $_REQUEST['qry_colaborador'] ? trim($_REQUEST['qry_colaborador']) : '');
        $qry_ini_rango = (isset($_REQUEST['qry_ini_rango']) && $_REQUEST['qry_ini_rango'] ? trim($_REQUEST['qry_ini_rango']) : 0);
        $qry_fin_rango = (isset($_REQUEST['qry_fin_rango']) && $_REQUEST['qry_fin_rango'] ? trim($_REQUEST['qry_fin_rango']) : 0);

        $config = Config::singleton();
        require $this->getModelByName('Reporte', 'reporte');
        require_once $this->getModelByName('VacacionConfiguracion', 'vacacionconfiguracion');
        require $this->getDefaultModelName();
        require_once $config->get('libsFolder') . 'PHPExcel/PHPExcel.php';

        $vacacionModelObj = new VacacionModel();
        $reporteModelObj = new ReporteModel();
        $vacaConfigObj = new VacacionConfiguracionModel();

        $userInfo = $this->sessionObj->getUserInfo();
        $dnis = $reporteModelObj->getDnisJerarquia2($userInfo[0]->DNI);
        $dnis = ($dnis) ? $dnis : '';

        $colaborador = $qry_colaborador;
        $estado = $qry_estado;
        $regProgramados = $vacacionModelObj->listCronograma($qry_empresa, $qry_gerencia, $qryDepartamento, $qry_area, $qry_seccion, $estado, $colaborador, $qry_ini_rango, $qry_fin_rango, $dnis);

        $programados = $this->_armandoArrayEstados($regProgramados);

        $columnfilaA = array('EMPRESA', 'GERENCIA', 'DEPARTAMENTO', 'AREA', 'SECCION', "DOCUMENTOS", 'COLABORADOR', $qry_ini_rango);
        $columName = array(
            'Ene.',
            'Feb.',
            'Mar.',
            'Abr.',
            'May.',
            'Jun.',
            'Jul.',
            'Ago.',
            'Set.',
            'Oct.',
            'Nov.',
            'Dic.',
            'Ene.',
            'Feb.',
            'Mar.',
            'Abr.',
            'May.',
            'Jun.',
            'Jul.',
            'Ago.',
            'Set.',
            'Oct.',
            'Nov.',
            'Dic.'
        );

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Laboratorios ACFARMA - Sistemas")->setTitle("Cronograma Vacaciones");
        $objPHPExcel->setActiveSheetIndex(0)->setTitle("Cronograma Vacaciones");
        $objPHPExcel->getActiveSheet()->setShowGridlines(false);

        $estadosVacas = $vacaConfigObj->getEstados();

        foreach ($estadosVacas as $k => $v) {
            $objPHPExcel->getActiveSheet()->getCell('G' . ($k + 2))->setValue($v->vaca_estado);
            $colorEstado = substr($v->color, 1);
            $objPHPExcel->getActiveSheet()->getStyle('H' . ($k + 2))->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $objPHPExcel->getActiveSheet()->getStyle('H' . ($k + 2))->getFill()->getStartColor()->setRGB($colorEstado);
        }

        $ini = 8;
        $lastRow = count($regProgramados) + $ini - 1;
        // Definimos el Color y Formato de Borde
        $objPHPExcel->getActiveSheet()->getStyle('A' . $ini . ':AE' . ($ini + 1))->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objPHPExcel->getActiveSheet()->getStyle('A' . $ini . ':AE' . ($ini + 1))->getFill()->getStartColor()->setRGB('b23535');
        // Definimos el color y fuente de la fi primera Fila
        $objPHPExcel->getActiveSheet()->getStyle('A' . $ini . ':AE' . ($ini + 1))->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $objPHPExcel->getActiveSheet()->getStyle('A' . $ini . ':AE' . ($ini + 1))->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle('A' . $ini . ':AE' . ($ini + 1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //********************************************** CUERPO DE INFORME ***********************************************************
        // Recorremos el resultado de la consulta
        $objPHPExcel->getActiveSheet()->mergeCells('A' . $ini . ':A' . ($ini + 1));
        $objPHPExcel->getActiveSheet()->mergeCells('B' . $ini . ':B' . ($ini + 1));
        $objPHPExcel->getActiveSheet()->mergeCells('C' . $ini . ':C' . ($ini + 1));
        $objPHPExcel->getActiveSheet()->mergeCells('D' . $ini . ':D' . ($ini + 1));
        $objPHPExcel->getActiveSheet()->mergeCells('E' . $ini . ':E' . ($ini + 1));
        $objPHPExcel->getActiveSheet()->mergeCells('F' . $ini . ':F' . ($ini + 1));
        $objPHPExcel->getActiveSheet()->mergeCells('G' . $ini . ':G' . ($ini + 1));
        // $objPHPExcel->getActiveSheet()->mergeCells('G1:G2');
        $objPHPExcel->getActiveSheet()->mergeCells('H' . $ini . ':S' . $ini);
        $objPHPExcel->getActiveSheet()->mergeCells('T' . $ini . ':AE' . $ini);
        $objPHPExcel->getActiveSheet()->getCell('T' . $ini)->setValue($qry_fin_rango);
        $objPHPExcel->getActiveSheet()->fromArray($columnfilaA, null, 'A' . $ini);
        $objPHPExcel->getActiveSheet()->fromArray($columName, null, 'H' . ($ini + 1));

        $noPrintCol = array(
            'id_vaca_estado',
            'color',
            'anio1_1',
            'anio1_2',
            'anio1_3',
            'anio1_4',
            'anio1_5',
            'anio1_6',
            'anio1_7',
            'anio1_8',
            'anio1_9',
            'anio1_10',
            'anio1_11',
            'anio1_12',
            'anio2_1',
            'anio2_2',
            'anio2_3',
            'anio2_4',
            'anio2_5',
            'anio2_6',
            'anio2_7',
            'anio2_8',
            'anio2_9',
            'anio2_10',
            'anio2_11',
            'anio2_12'
        );

        $noPrintFec = array('empresa', 'gerencia', 'area', 'seccion', 'dni', 'solicitante');

        $f = 7;
        foreach ($programados as $index => $row) {
            foreach ($noPrintCol as $value) {
                unset($row[$value]);
            }
            $numFila = $f + 3;
            $cantFilas = count($row['vaca_estado']);
            if ($cantFilas > 1) {
                $objPHPExcel->getActiveSheet()->mergeCells('A' . $numFila . ':A' . ($numFila + $cantFilas - 1));
                $objPHPExcel->getActiveSheet()->mergeCells('B' . $numFila . ':B' . ($numFila + $cantFilas - 1));
                $objPHPExcel->getActiveSheet()->mergeCells('C' . $numFila . ':C' . ($numFila + $cantFilas - 1));
                $objPHPExcel->getActiveSheet()->mergeCells('D' . $numFila . ':D' . ($numFila + $cantFilas - 1));
                $objPHPExcel->getActiveSheet()->mergeCells('E' . $numFila . ':E' . ($numFila + $cantFilas - 1));
                $objPHPExcel->getActiveSheet()->mergeCells('F' . $numFila . ':F' . ($numFila + $cantFilas - 1));
            }
            unset($row['vaca_estado']);
            $objPHPExcel->getActiveSheet()->fromArray((array) $row, null, "A" . $numFila);
            $f = $f + $cantFilas;
        }

        $f = 7;
        foreach ($programados as $key => $value) {
            foreach ($noPrintFec as $col) {
                unset($value[$col]);
            }

            foreach ($value['vaca_estado'] as $key => $ff) {
                $numFila = $f + 3;
                $fechas['anio1_1'] = $value['anio1_1'][$key]['fechas'];
                if ($fechas['anio1_1'] != '') {
                    $color['anio1_1'] = substr($value['anio1_1'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('H' . $numFila . ':H' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('H' . $numFila . ':H' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('H' . $numFila . ':H' . $numFila)->getFill()->getStartColor()->setRGB($color['anio1_1']);
                }
                $fechas['anio1_2'] = $value['anio1_2'][$key]['fechas'];
                if ($fechas['anio1_2'] != '') {
                    $color['anio1_2'] = substr($value['anio1_2'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('I' . $numFila . ':I' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('I' . $numFila . ':I' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('I' . $numFila . ':I' . $numFila)->getFill()->getStartColor()->setRGB($color['anio1_2']);
                }
                $fechas['anio1_3'] = $value['anio1_3'][$key]['fechas'];
                if ($fechas['anio1_3'] != '') {
                    $color['anio1_3'] = substr($value['anio1_3'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('J' . $numFila . ':J' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('J' . $numFila . ':J' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('J' . $numFila . ':J' . $numFila)->getFill()->getStartColor()->setRGB($color['anio1_3']);
                }
                $fechas['anio1_4'] = $value['anio1_4'][$key]['fechas'];
                if ($fechas['anio1_4'] != '') {
                    $color['anio1_4'] = substr($value['anio1_4'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('K' . $numFila . ':K' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('K' . $numFila . ':K' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('K' . $numFila . ':K' . $numFila)->getFill()->getStartColor()->setRGB($color['anio1_4']);
                }
                $fechas['anio1_5'] = $value['anio1_5'][$key]['fechas'];
                if ($fechas['anio1_5'] != '') {
                    $color['anio1_5'] = substr($value['anio1_5'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('L' . $numFila . ':L' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('L' . $numFila . ':L' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('L' . $numFila . ':L' . $numFila)->getFill()->getStartColor()->setRGB($color['anio1_5']);
                }
                $fechas['anio1_6'] = $value['anio1_6'][$key]['fechas'];
                if ($fechas['anio1_6'] != '') {
                    $color['anio1_6'] = substr($value['anio1_6'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('M' . $numFila . ':M' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('M' . $numFila . ':M' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('M' . $numFila . ':M' . $numFila)->getFill()->getStartColor()->setRGB($color['anio1_6']);
                }
                $fechas['anio1_7'] = $value['anio1_7'][$key]['fechas'];
                if ($fechas['anio1_7'] != '') {
                    $color['anio1_7'] = substr($value['anio1_7'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('N' . $numFila . ':N' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('N' . $numFila . ':N' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('N' . $numFila . ':N' . $numFila)->getFill()->getStartColor()->setRGB($color['anio1_7']);
                }
                $fechas['anio1_8'] = $value['anio1_8'][$key]['fechas'];
                if ($fechas['anio1_8'] != '') {
                    $color['anio1_8'] = substr($value['anio1_8'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('O' . $numFila . ':O' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('O' . $numFila . ':O' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('O' . $numFila . ':O' . $numFila)->getFill()->getStartColor()->setRGB($color['anio1_8']);
                }
                $fechas['anio1_9'] = $value['anio1_9'][$key]['fechas'];
                if ($fechas['anio1_9'] != '') {
                    $color['anio1_9'] = substr($value['anio1_9'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('P' . $numFila . ':P' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('P' . $numFila . ':P' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('P' . $numFila . ':P' . $numFila)->getFill()->getStartColor()->setRGB($color['anio1_9']);
                }
                $fechas['anio1_10'] = $value['anio1_10'][$key]['fechas'];
                if ($fechas['anio1_10'] != '') {
                    $color['anio1_10'] = substr($value['anio1_10'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('Q' . $numFila . ':Q' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('Q' . $numFila . ':Q' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('Q' . $numFila . ':Q' . $numFila)->getFill()->getStartColor()->setRGB($color['anio1_10']);
                }
                $fechas['anio1_11'] = $value['anio1_11'][$key]['fechas'];
                if ($fechas['anio1_11'] != '') {
                    $color['anio1_11'] = substr($value['anio1_11'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('R' . $numFila . ':R' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('R' . $numFila . ':R' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('R' . $numFila . ':R' . $numFila)->getFill()->getStartColor()->setRGB($color['anio1_11']);
                }
                $fechas['anio1_12'] = $value['anio1_12'][$key]['fechas'];
                if ($fechas['anio1_12'] != '') {
                    $color['anio1_12'] = substr($value['anio1_12'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('S' . $numFila . ':S' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('S' . $numFila . ':S' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('S' . $numFila . ':S' . $numFila)->getFill()->getStartColor()->setRGB($color['anio1_12']);
                }
                $fechas['anio2_1'] = $value['anio2_1'][$key]['fechas'];
                if ($fechas['anio2_1'] != '') {
                    $color['anio2_1'] = substr($value['anio2_1'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('T' . $numFila . ':T' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('T' . $numFila . ':T' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('T' . $numFila . ':T' . $numFila)->getFill()->getStartColor()->setRGB($color['anio2_1']);
                }
                $fechas['anio2_2'] = $value['anio2_2'][$key]['fechas'];
                if ($fechas['anio2_2'] != '') {
                    $color['anio2_2'] = substr($value['anio2_2'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('U' . $numFila . ':U' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('U' . $numFila . ':U' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('U' . $numFila . ':U' . $numFila)->getFill()->getStartColor()->setRGB($color['anio2_2']);
                }
                $fechas['anio2_3'] = $value['anio2_3'][$key]['fechas'];
                if ($fechas['anio2_3'] != '') {
                    $color['anio2_3'] = substr($value['anio2_3'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('V' . $numFila . ':V' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('V' . $numFila . ':V' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('V' . $numFila . ':V' . $numFila)->getFill()->getStartColor()->setRGB($color['anio2_3']);
                }
                $fechas['anio2_4'] = $value['anio2_4'][$key]['fechas'];
                if ($fechas['anio2_4'] != '') {
                    $color['anio2_4'] = substr($value['anio2_4'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('W' . $numFila . ':W' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('W' . $numFila . ':W' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('W' . $numFila . ':W' . $numFila)->getFill()->getStartColor()->setRGB($color['anio2_4']);
                }
                $fechas['anio2_5'] = $value['anio2_5'][$key]['fechas'];
                if ($fechas['anio2_5'] != '') {
                    $color['anio2_5'] = substr($value['anio2_5'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('X' . $numFila . ':X' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('X' . $numFila . ':X' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('X' . $numFila . ':X' . $numFila)->getFill()->getStartColor()->setRGB($color['anio2_5']);
                }
                $fechas['anio2_6'] = $value['anio2_6'][$key]['fechas'];
                if ($fechas['anio2_6'] != '') {
                    $color['anio2_6'] = substr($value['anio2_6'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('Y' . $numFila . ':Y' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('Y' . $numFila . ':Y' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('Y' . $numFila . ':Y' . $numFila)->getFill()->getStartColor()->setRGB($color['anio2_6']);
                }
                $fechas['anio2_7'] = $value['anio2_7'][$key]['fechas'];
                if ($fechas['anio2_7'] != '') {
                    $color['anio2_7'] = substr($value['anio2_7'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('Z' . $numFila . ':Z' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('Z' . $numFila . ':Z' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('Z' . $numFila . ':Z' . $numFila)->getFill()->getStartColor()->setRGB($color['anio2_7']);
                }
                $fechas['anio2_8'] = $value['anio2_8'][$key]['fechas'];
                if ($fechas['anio2_8'] != '') {
                    $color['anio2_8'] = substr($value['anio2_8'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('AA' . $numFila . ':AA' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('AA' . $numFila . ':AA' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('AA' . $numFila . ':AA' . $numFila)->getFill()->getStartColor()->setRGB($color['anio2_8']);
                }
                $fechas['anio2_9'] = $value['anio2_9'][$key]['fechas'];
                if ($fechas['anio2_9'] != '') {
                    $color['anio2_9'] = substr($value['anio2_9'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('AB' . $numFila . ':AB' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('AB' . $numFila . ':AB' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('AB' . $numFila . ':AB' . $numFila)->getFill()->getStartColor()->setRGB($color['anio2_9']);
                }
                $fechas['anio2_10'] = $value['anio2_10'][$key]['fechas'];
                if ($fechas['anio2_10'] != '') {
                    $color['anio2_10'] = substr($value['anio2_10'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('AC' . $numFila . ':AC' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('AC' . $numFila . ':AC' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('AC' . $numFila . ':AC' . $numFila)->getFill()->getStartColor()->setRGB($color['anio2_10']);
                }
                $fechas['anio2_11'] = $value['anio2_11'][$key]['fechas'];
                if ($fechas['anio2_11'] != '') {
                    $color['anio2_11'] = substr($value['anio2_11'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('AD' . $numFila . ':AD' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('AD' . $numFila . ':AD' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('AD' . $numFila . ':AD' . $numFila)->getFill()->getStartColor()->setRGB($color['anio2_11']);
                }
                $fechas['anio2_12'] = $value['anio2_12'][$key]['fechas'];
                if ($fechas['anio2_12'] != '') {
                    $color['anio2_12'] = substr($value['anio2_12'][$key]['color'], 1);
                    $objPHPExcel->getActiveSheet()->getStyle('AE' . $numFila . ':AE' . $numFila)->getFont()->getColor()->setRGB('FFFFFF');
                    $objPHPExcel->getActiveSheet()->getStyle('AE' . $numFila . ':AE' . $numFila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $objPHPExcel->getActiveSheet()->getStyle('AE' . $numFila . ':AE' . $numFila)->getFill()->getStartColor()->setRGB($color['anio2_12']);
                }
                $objPHPExcel->getActiveSheet()->fromArray((array) $fechas, null, "H" . $numFila);
                $f++;
            }
        }

        // exit;
        //Pintamos los bordes del cuerpo
        $objPHPExcel->getActiveSheet()->getStyle('A' . ($ini + 2) . ':AE' . ($lastRow + 2))->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        //************************************************* FINAL DE INFORME ****************************************************************

        // Set autoZise Columns
        foreach (range('A', 'Z') as $columnID) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
        }
        // FILTER

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $filename = 'Cronograma_vacaciones_' . date('Y-m-d_H-i') . '.xlsx';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
    }

    private function _cellColor($cells, $color)
    {
        global $objPHPExcel;
        $objPHPExcel->getStyle($cells)->applyFromArray(
            array(
                'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => $color)
                )
            )
        );
        // $objPHPExcel->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(
        //  'type' => PHPExcel_Style_Fill::FILL_SOLID,
        //  'startcolor' => array(
        //      'rgb' => $color
        //  )
        // ));
    }

    private function _armandoArrayEstados($regProgramados)
    {
        $colaboradores = array();
        $list = array();
        $i = -1;
        foreach ($regProgramados as $key => $value) {
            if (!in_array($value->dni, $list)) {
                $b = 0;
                $i++;
                $list['dni'] = $value->dni;
                $colaboradores[$i]['empresa'] = $value->empresa;
                $colaboradores[$i]['gerencia'] = $value->gerencia;
                $colaboradores[$i]['departamento'] = $value->departamento;
                $colaboradores[$i]['area'] = $value->area;
                $colaboradores[$i]['seccion'] = $value->seccion;
                $colaboradores[$i]['dni'] = $value->dni;
                $colaboradores[$i]['solicitante'] = $value->solicitante;

                $colaboradores[$i]['vaca_estado'][$b] = $value->vaca_estado;
                // $colaboradores[$i]['demo'][$b]   = $value;
                $colaboradores[$i]['anio1_1'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_1'][$b]['fechas'] = $value->anio1_1;
                $colaboradores[$i]['anio1_1'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_2'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_2'][$b]['fechas'] = $value->anio1_2;
                $colaboradores[$i]['anio1_2'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_3'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_3'][$b]['fechas'] = $value->anio1_3;
                $colaboradores[$i]['anio1_3'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_4'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_4'][$b]['fechas'] = $value->anio1_4;
                $colaboradores[$i]['anio1_4'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_5'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_5'][$b]['fechas'] = $value->anio1_5;
                $colaboradores[$i]['anio1_5'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_6'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_6'][$b]['fechas'] = $value->anio1_6;
                $colaboradores[$i]['anio1_6'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_7'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_7'][$b]['fechas'] = $value->anio1_7;
                $colaboradores[$i]['anio1_7'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_8'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_8'][$b]['fechas'] = $value->anio1_8;
                $colaboradores[$i]['anio1_8'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_9'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_9'][$b]['fechas'] = $value->anio1_9;
                $colaboradores[$i]['anio1_9'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_10'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_10'][$b]['fechas'] = $value->anio1_10;
                $colaboradores[$i]['anio1_10'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_11'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_11'][$b]['fechas'] = $value->anio1_11;
                $colaboradores[$i]['anio1_11'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_12'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_12'][$b]['fechas'] = $value->anio1_12;
                $colaboradores[$i]['anio1_12'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_1'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_1'][$b]['fechas'] = $value->anio2_1;
                $colaboradores[$i]['anio2_1'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_2'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_2'][$b]['fechas'] = $value->anio2_2;
                $colaboradores[$i]['anio2_2'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_3'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_3'][$b]['fechas'] = $value->anio2_3;
                $colaboradores[$i]['anio2_3'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_4'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_4'][$b]['fechas'] = $value->anio2_4;
                $colaboradores[$i]['anio2_4'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_5'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_5'][$b]['fechas'] = $value->anio2_5;
                $colaboradores[$i]['anio2_5'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_6'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_6'][$b]['fechas'] = $value->anio2_6;
                $colaboradores[$i]['anio2_6'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_7'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_7'][$b]['fechas'] = $value->anio2_7;
                $colaboradores[$i]['anio2_7'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_8'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_8'][$b]['fechas'] = $value->anio2_8;
                $colaboradores[$i]['anio2_8'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_9'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_9'][$b]['fechas'] = $value->anio2_9;
                $colaboradores[$i]['anio2_9'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_10'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_10'][$b]['fechas'] = $value->anio2_10;
                $colaboradores[$i]['anio2_10'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_11'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_11'][$b]['fechas'] = $value->anio2_11;
                $colaboradores[$i]['anio2_11'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_12'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_12'][$b]['fechas'] = $value->anio2_12;
                $colaboradores[$i]['anio2_12'][$b]['color'] = $value->color;
                $b++;
            } else {
                $colaboradores[$i]['vaca_estado'][$b] = $value->vaca_estado;
                // $colaboradores[$i]['demo'][$b]   = $value;
                $colaboradores[$i]['anio1_1'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_1'][$b]['fechas'] = $value->anio1_1;
                $colaboradores[$i]['anio1_1'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_2'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_2'][$b]['fechas'] = $value->anio1_2;
                $colaboradores[$i]['anio1_2'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_3'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_3'][$b]['fechas'] = $value->anio1_3;
                $colaboradores[$i]['anio1_3'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_4'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_4'][$b]['fechas'] = $value->anio1_4;
                $colaboradores[$i]['anio1_4'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_5'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_5'][$b]['fechas'] = $value->anio1_5;
                $colaboradores[$i]['anio1_5'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_6'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_6'][$b]['fechas'] = $value->anio1_6;
                $colaboradores[$i]['anio1_6'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_7'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_7'][$b]['fechas'] = $value->anio1_7;
                $colaboradores[$i]['anio1_7'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_8'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_8'][$b]['fechas'] = $value->anio1_8;
                $colaboradores[$i]['anio1_8'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_9'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_9'][$b]['fechas'] = $value->anio1_9;
                $colaboradores[$i]['anio1_9'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_10'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_10'][$b]['fechas'] = $value->anio1_10;
                $colaboradores[$i]['anio1_10'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_11'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_11'][$b]['fechas'] = $value->anio1_11;
                $colaboradores[$i]['anio1_11'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio1_12'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio1_12'][$b]['fechas'] = $value->anio1_12;
                $colaboradores[$i]['anio1_12'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_1'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_1'][$b]['fechas'] = $value->anio2_1;
                $colaboradores[$i]['anio2_1'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_2'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_2'][$b]['fechas'] = $value->anio2_2;
                $colaboradores[$i]['anio2_2'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_3'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_3'][$b]['fechas'] = $value->anio2_3;
                $colaboradores[$i]['anio2_3'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_4'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_4'][$b]['fechas'] = $value->anio2_4;
                $colaboradores[$i]['anio2_4'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_5'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_5'][$b]['fechas'] = $value->anio2_5;
                $colaboradores[$i]['anio2_5'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_6'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_6'][$b]['fechas'] = $value->anio2_6;
                $colaboradores[$i]['anio2_6'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_7'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_7'][$b]['fechas'] = $value->anio2_7;
                $colaboradores[$i]['anio2_7'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_8'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_8'][$b]['fechas'] = $value->anio2_8;
                $colaboradores[$i]['anio2_8'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_9'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_9'][$b]['fechas'] = $value->anio2_9;
                $colaboradores[$i]['anio2_9'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_10'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_10'][$b]['fechas'] = $value->anio2_10;
                $colaboradores[$i]['anio2_10'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_11'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_11'][$b]['fechas'] = $value->anio2_11;
                $colaboradores[$i]['anio2_11'][$b]['color'] = $value->color;
                $colaboradores[$i]['anio2_12'][$b]['estado'] = $value->vaca_estado;
                $colaboradores[$i]['anio2_12'][$b]['fechas'] = $value->anio2_12;
                $colaboradores[$i]['anio2_12'][$b]['color'] = $value->color;
                $b++;
            }
        }
        return $colaboradores;
    }

    public function sincronizarEstados()
    {
        require $this->getDefaultModelName();
        $vacacionModelObj = new VacacionModel();
        $vacacionModelObj->procesarSincronizacion();
    }

    /**
     * Verifica si entre las fechas de inicio y fin se incluye al menos un fin de semana completo (sábado y domingo)
     * 
     * @param string $fechaInicio Fecha de inicio en formato Y-m-d
     * @param string $fechaFin Fecha de fin en formato Y-m-d
     * @return boolean True si incluye al menos un sábado y un domingo, False en caso contrario
     */
    private function _verificarInclucionFinDeSemana($fechaInicio, $fechaFin)
    {
        $tieneSabado = false;
        $tieneDomingo = false;

        // Convertir las fechas a objetos DateTime
        $inicio = new DateTime($fechaInicio);
        $fin = new DateTime($fechaFin);
        $fin->modify('+1 day'); // Incluir el día final en el conteo

        // Intervalo de 1 día para iterar
        $intervalo = new DateInterval('P1D');
        $periodo = new DatePeriod($inicio, $intervalo, $fin);

        // Recorrer cada día en el periodo
        foreach ($periodo as $fecha) {
            $diaSemana = $fecha->format('w');

            if ($diaSemana == 6) { // Sábado
                $tieneSabado = true;
            } elseif ($diaSemana == 0) { // Domingo
                $tieneDomingo = true;
            }

            // Si ya encontramos ambos días, no necesitamos seguir verificando
            if ($tieneSabado && $tieneDomingo) {
                return true;
            }
        }

        // Verificar si encontramos tanto sábado como domingo
        return ($tieneSabado && $tieneDomingo);
    }
}
