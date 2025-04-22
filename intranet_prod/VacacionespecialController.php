<?php
class VacacionespecialController extends ControllerBase
{
    function __construct()
    {
        parent::__construct(__CLASS__);
    }

    public function index()
    {
        $this->sessionObj->checkInner();
        require $this->getDefaultModelName();
        require $this->getModelByName('Directorio', 'directorio');

        $directorioModelObj = new DirectorioModel();

        $data['cboEmpresa'] = $directorioModelObj->getEmpresaGroup(array('01'));
        $data['rootFolder'] = $this->getRootFolder();
        $data['protocol'] = $this->getCurrentProtocol();
        $data['obj'] = $this;

        $this->sessionObj->RegisterAuditModule($this->getAppName());

        $this->view->show(array(
            'filename' => "main.tpl.php",
            'data' => $data
        ));
    }
    public function listar()
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
        $vacacionEspecialModelObj = new VacacionespecialModel();

        $vacacionesList = $vacacionEspecialModelObj->getVacacionesEspeciales($pgStart, $pgSize, $pgSort, $qryEmpresa, $qryGerencia, $qryDepartamento, $qryArea, $qrySeccion, $qryIniRango, $qryFinRango, $qryColaborador);
        $totalVacaciones = $vacacionEspecialModelObj->getInfoVacacionEspecial($qryEmpresa, $qryGerencia, $qryDepartamento, $qryArea, $qrySeccion, $qryIniRango, $qryFinRango, $qryColaborador);

        if ($vacacionesList) {
            $response["Result"] = 'OK';
            $response["Records"] = $vacacionesList;
            $response["TotalRecordCount"] = $totalVacaciones[0]->num;
        } else {
            // $this->sendErrorResponse('No se encontraron Boletas de Vacaciones Especiales Generadas');
            $response["Result"] = 'ERROR';
            $response["Message"] = 'No se encontraron Boletas de Vacaciones Especiales Generadas';
        }

        $this->view->showJSONPlane(array(
            'response' => $response
        ));
    }
    public function crear()
    {
        $this->sessionObj->checkJsonRequest($this->getAppName(), "crear");
        $app = $this->getAppName();
        $response = ['Result' => 'ERROR', 'Message' => ''];

        try {
            // Obtener el contenido JSON de la solicitud
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            // Verificar que se recibieron registros
            if (!isset($data['registros'])) {
                throw new Exception('No se recibieron registros para procesar.');
            }

            // Decodificar los registros JSON
            $registros = $data['registros'];

            if (!is_array($registros) || empty($registros)) {
                throw new Exception('No hay registros válidos para procesar.');
            }

            // Cargar el modelo
            require_once $this->getDefaultModelName();
            $vacacionEspecialModelObj = new VacacionespecialModel();

            // Procesar cada registro para asegurar que tiene la modalidad
            $registrosProcesados = [];
            foreach ($registros as $registro) {
                // Asegurar que cada registro tiene la modalidad
                if (!isset($registro['modalidad'])) {
                    throw new Exception('Uno o más registros no tienen modalidad especificada.');
                }

                // Adaptar el formato del registro si es necesario
                $registroProcesado = [
                    'id_empresa' => $registro['empresaId'],
                    'id_unidad' => $registro['gerenciaId'],
                    'id_departamento' => $registro['departamentoId'],
                    'id_area' => $registro['areaId'],
                    'id_seccion' => $registro['seccionId'],
                    'id_solicitante' => $registro['id_solicitante'],
                    'fecha_inicio' => $registro['fecha_inicio'],
                    'fecha_fin' => $registro['fecha_fin'],
                    'modalidad' => $registro['modalidad']
                ];

                $registrosProcesados[] = $registroProcesado;
            }

            $resultado = $vacacionEspecialModelObj->crearVacacionEspecial($registrosProcesados);
            $response = ['Result' => 'OK', 'ids' => $resultado['ids']];

            if (!empty($resultado['ids'])) {
                // Iterar sobre cada ID en el array
                foreach ($resultado['ids'] as $id) {
                    $idEntero = (int) $id;
                    $this->sessionObj->RegisterAccion($app, 'crear', $idEntero);
                }
            }
        } catch (Exception $e) {
            $response['Message'] = 'Error al crear la vacación especial: ' . $e->getMessage();
        }

        // Devolver respuesta JSON
        $this->view->showJSONPlane(['response' => $response]);
    }
    public function buscarUsuario()
    {
        $this->sessionObj->checkJsonRequest();

        $qryName = $this->getRequestParam('q', '');

        require $this->getModelByName('Directorio', 'directorio');
        $directorioObj = new DirectorioModel();

        $regDirectorio = $directorioObj->buscarFullPersona($qryName);
        $data = [];

        foreach ($regDirectorio as $persona) {
            $data[] = [
                'id_solicitante' => $persona->id_solicitante,
                'DisplayText' => $persona->nom_solicitante,
                'DisplayText2' => $persona->seccion
            ];
        }

        echo json_encode($data);
        exit();
    }
    private function getRequestParam($paramName, $defaultValue = null)
    {
        return (isset($_REQUEST[$paramName]) && $_REQUEST[$paramName] !== '') ? trim($_REQUEST[$paramName]) : $defaultValue;
    }
    public function getInfoSolicitante()
    {
        $this->sessionObj->checkJsonRequest();

        $id_solicitante = $this->getRequestParam('id_solicitante', '');

        if (empty($id_solicitante)) {
            echo json_encode([
                'Result' => 'ERROR',
                'Message' => 'ID de solicitante no proporcionado'
            ]);
            exit();
        }

        require $this->getModelByName('Directorio', 'directorio');
        $directorioObj = new DirectorioModel();

        // Obtener la información del solicitante por ID
        $solicitante = $directorioObj->getSolicitanteById($id_solicitante);

        if ($solicitante) {
            echo json_encode([
                'Result' => 'OK',
                'Data' => $solicitante
            ]);
        } else {
            echo json_encode([
                'Result' => 'ERROR',
                'Message' => 'No se encontró información para el solicitante'
            ]);
        }
        exit();
    }
    public function borrar()
    {
        $this->sessionObj->checkJsonRequest($this->getAppName(), "borrar");
        $app = $this->getAppName();
        // Validar la entrada
        $validationResult = $this->validateInput();
        if (!$validationResult['status']) {
            $this->sendErrorResponse($validationResult['message']);
            return;
        }
        $response = array('Result' => 'ERROR');

        // Incluir el modelo
        require $this->getDefaultModelName();
        $vacacionEspecialModelObj = new VacacionespecialModel();

        // Obtener el ID de la vacación especial a eliminar
        $idVacacionEspecial = $this->getInput('id_vaca_especial');

        // Procesar la eliminación
        $eliminacionExitosa = $vacacionEspecialModelObj->eliminarLogicamente($idVacacionEspecial);

        if ($eliminacionExitosa === 1) {
            $response["Result"] = 'OK';
            $this->sessionObj->RegisterAccion($app, 'borrar', $idVacacionEspecial);
        } else {
            $response["Result"] = 'ERROR';
            $response["Message"] = 'Operación denegada. La vacación especial está siendo utilizada en el sistema.';
        }
        $this->view->showJSONPlane(array(
            'response' => $response
        ));
    }
    private function validateInput()
    {
        $config = Config::singleton();
        require_once $config->get('classesFolder') . 'gump.class.php';
        $gump = new GUMP();

        $gump->validation_rules(array(
            'id_vaca_especial' => 'required|integer'
        ));

        $status = $gump->run($_POST);

        return [
            'status' => $status,
            'message' => $status ? '' : $gump->get_readable_errors(true)
        ];
    }
    private function getInput($key)
    {
        require_once Config::singleton()->get('classesFolder') . 'Input.php';
        $input = new CI_Input();
        return $input->post($key, true);
    }
    private function sendErrorResponse($message)
    {
        $this->view->showJSONPlane([
            'response' => [
                'Result' => 'ERROR',
                'Message' => $message
            ]
        ]);
    }

    public function indexCrear()
    {
        $this->sessionObj->checkJsonRequest();
        $info = array();

        $qrySolicitante = (isset($_POST['idSolicitante']) && $_POST['idSolicitante'] ? trim($_POST['idSolicitante']) : '');

        require $this->getDefaultModelName();
        $vacacionEspecialModelObj = new VacacionespecialModel();

        $userInfo = $this->sessionObj->getUserInfo();

        $info['Result'] = 'OK';
        $info['generador'] = true;

        $this->view->showJSONPlane(array(
            'response' => $info
        ));
    }

    public function indexEditar()
    {
        // var_dump('entre');
        // die();
        $this->sessionObj->checkJsonRequest();
        $info = array();

        // Obtener el ID de la solicitud de vacaciones especiales desde la petición POST
        $idVacacionEspecial = (isset($_POST['id_vaca_especial']) && $_POST['id_vaca_especial'] ? trim($_POST['id_vaca_especial']) : null);

        if ($idVacacionEspecial) {
            require $this->getDefaultModelName();
            $vacacionEspecialModelObj = new VacacionespecialModel();

            // Aquí deberías llamar a una función de tu modelo para obtener la información
            // de la solicitud de vacaciones especiales basada en el ID.
            // $datosVacacion = $vacacionEspecialModelObj->obtenerVacacionEspecialPorId($idVacacionEspecial);

            // if ($datosVacacion) {
                $info['Result'] = 'OK';
                $info['generador'] = true;
                // $info['Data'] = $datosVacacion; // Enviar los datos obtenidos
            // } else {
            //     $info['Result'] = 'ERROR';
            //     $info['Message'] = 'No se encontró la solicitud de vacaciones especiales con el ID proporcionado.';
            // }
        } else {
            $info['Result'] = 'ERROR';
            $info['Message'] = 'No se recibió el ID de la solicitud de vacaciones especiales.';
        }

        $this->view->showJSONPlane(array(
            'response' => $info
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
        $vacacionEspecialModelObj = new VacacionespecialModel();

        $dnis = !$master;

        if (!$master) {
            require $this->getModelByName('Reporte', 'reporte');
            $reporteModelObj = new ReporteModel();
            $userInfo = $this->sessionObj->getUserInfo();
        }

        // Hacer que los parámetros sean opcionales
        $qry_empresa = isset($_GET['qry_empresa']) ? $_GET['qry_empresa'] : null;
        $qry_gerencia = isset($_GET['qry_gerencia']) ? $_GET['qry_gerencia'] : null;
        $qry_departamento = isset($_GET['qry_departamento']) ? $_GET['qry_departamento'] : null;
        $qry_area = isset($_GET['qry_area']) ? $_GET['qry_area'] : null;
        $qry_seccion = isset($_GET['qry_seccion']) ? $_GET['qry_seccion'] : null;
        $qry_ini_rango = isset($_GET['qry_ini_rango']) ? $_GET['qry_ini_rango'] : null;
        $qry_fin_rango = isset($_GET['qry_fin_rango']) ? $_GET['qry_fin_rango'] : null;
        $qry_colaborador = isset($_GET['qry_colaborador']) ? $_GET['qry_colaborador'] : null;

        $arrVacaciones = $vacacionEspecialModelObj->exportarVacacionesEspeciales(
            $qry_empresa,
            $qry_gerencia,
            $qry_departamento,
            $qry_area,
            $qry_seccion,
            $qry_ini_rango,
            $qry_fin_rango,
            $qry_colaborador
        );

        if (empty($arrVacaciones)) {
            echo "No se encontraron registros";
            exit();
        }
        $this->_exportarDataBoleta($arrVacaciones);
    }
    private function _exportarDataBoleta($arrVacaciones)
    {
        $columName = array('ID', 'GERENCIA', 'DEPARTAMENTO', 'AREA', 'SECCION', 'SOLICITANTE', 'GENERADOR', 'FECHA SOLICITUD', 'FECHA INICIO', 'FECHA_FIN', 'CANTIDAD DIAS');
        $lastRow = count($arrVacaciones) + 1;
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Laboratorios ACFARMA - Sistemas")->setTitle("Listado de vacaciones especiales");
        $objPHPExcel->setActiveSheetIndex(0)->setTitle("Vacaciones Especiales");
        $objPHPExcel->getActiveSheet()->setShowGridlines(false);

        // Definimos el Color y Formato de Borde
        $objPHPExcel->getActiveSheet()->getStyle('A1:K1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objPHPExcel->getActiveSheet()->getStyle('A1:K1')->getFill()->getStartColor()->setRGB('b23535');
        // Definimos el color y fuente de la fi primera Fila
        $objPHPExcel->getActiveSheet()->getStyle('A1:K1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $objPHPExcel->getActiveSheet()->getStyle('A1:K1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle('A1:K1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //********************************************** CUERPO DE INFORME ***********************************************************
        // Recorremos el resultado de la consulta
        $objPHPExcel->getActiveSheet()->fromArray($columName, null, "A1");
        foreach ($arrVacaciones as $index => $row) {
            $objPHPExcel->getActiveSheet()->fromArray((array) $row, null, "A" . ($index + 2));
        }

        //Pintamos los bordes del cuerpo
        $objPHPExcel->getActiveSheet()->getStyle('A1:K' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        //************************************************* FINAL DE INFORME ****************************************************************

        //Formato a celdas
        $objPHPExcel->getActiveSheet()->getStyle('J2:J' . $lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $objPHPExcel->getActiveSheet()->getStyle('L2:M' . $lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy');

        //Set autoZise Columns
        foreach (range('A', 'K') as $columnID) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
        }
        //FILTER

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007'); // Descomentar para prod
        $filename = 'Solicitud_vacaciones_especiales_' . date('Y-m-d_H-i') . '.xlsx';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
    }
    public function validacionFechaEspecialSolicitante()
    {
        $idSolicitante = $this->getInput('colaboradorId');
        $fechaInicio = $this->getInput('fechaInicio');
        $fechaFin = $this->getInput('fechaFin');

        require_once $this->getDefaultModelName();
        $vacacionEspecialModelObj = new VacacionespecialModel();

        // if (empty($idSolicitante) || empty($fechaInicio) || empty($fechaFin)) {
        //     echo json_encode([
        //         'valido' => false,
        //         'mensaje' => 'Faltan parámetros requeridos'
        //     ]);
        //     return;
        // }

        $resultado = $vacacionEspecialModelObj->_validarFechaEspecialSolicitante($idSolicitante, $fechaInicio, $fechaFin);

        echo json_encode([
            'valido' => ($resultado['existeRegistro'] == 0),
            'mensaje' => $resultado['mensaje']
        ]);
    }

    public function validacionFechaEspecial()
    {
        $idEmpresa = $this->getInput('idEmpresa');
        $idUnidad = $this->getInput('idUnidad');
        $idDepartamento = $this->getInput('idDepartamento');
        $idArea = $this->getInput('idArea');
        $idSeccion = $this->getInput('idSeccion');
        $fechaInicio = $this->getInput('fechaInicio');
        $fechaFin = $this->getInput('fechaFin');

        require_once $this->getDefaultModelName();
        $vacacionEspecialModelObj = new VacacionespecialModel();

        $resultado = $vacacionEspecialModelObj->_validarFechaEspecial($idEmpresa, $idUnidad, $idDepartamento, $idArea, $idSeccion, $fechaInicio, $fechaFin);

        echo json_encode([
            'valido' => ($resultado['existeRegistro'] == 0),
            'mensaje' => $resultado['mensaje']
        ]);
    }
    public function obtenerLimiteDiasVacacionesEspeciales()
    {
        require_once $this->getDefaultModelName();
        $vacacionEspecialModelObj = new VacacionespecialModel();
        $limite = $vacacionEspecialModelObj->obtenerLimiteDiasVacacionesEspeciales();
        // var_dump($limite);
        echo json_encode([
            'limite' => $limite
        ]);
    }

    public function listarVacacionesTemp() // No necesitas el parámetro aquí, lo recibirás por POST
    {
        require_once $this->getDefaultModelName();
        $vacacionEspecialModelObj = new VacacionespecialModel();

        // Obtén el id_vaca_especial del POST
        $idvacaespecial = $_POST['id_vaca_especial'];

        // Llama a la función del modelo con el ID recibido
        $vacaciones = $vacacionEspecialModelObj->listarVacacionesTemp($idvacaespecial);

        // Formatea la respuesta para jTable
        $respuesta = [
            'Result' => 'OK', // jTable espera 'Result'
            'Records' => $vacaciones, // jTable espera 'Records' con el array de datos
            'TotalRecordCount' => count($vacaciones) // jTable necesita el total de registros para la paginación
        ];

        // Envía la respuesta como JSON
        header('Content-Type: application/json'); // Asegúrate de enviar la cabecera correcta
        echo json_encode($respuesta);
    }

}