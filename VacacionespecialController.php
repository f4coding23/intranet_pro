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
            'filename'  => "main.tpl.php",
            'data'      => $data
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

        if ($vacacionesList) {
            $response["Result"] = 'OK';
            $response["Records"] = $vacacionesList;
        } else {
            $this->sendErrorResponse('No se encontraron Boletas de Vacaciones Especiales Generadas');
            // $response["Result"] = 'ERROR';
            // $response["Message"] = 'No se encontraron Boletas de Vacaciones Especiales Generadas';
        }

        $this->view->showJSONPlane(array(
            'response' => $response
        ));
    }
    public function crear()
    {
        $response = ['Result' => 'ERROR', 'Message' => ''];
        try {
            // Cargar el modelo
            require_once $this->getDefaultModelName();
            $vacacionEspecialModelObj = new VacacionespecialModel();

            // Datos hardcodeados para pruebas, quitar en prod
            $reg = [
                // 'id_solicitante' => 8922,
                // 'id_seccion' => '010S',
                // 'id_area' => '004A',
                'id_departamento' => '009D',
                'id_unidad' => '002',
                'fecha_inicio' => '17/03/2024', // Formato d/m/Y
                'fecha_fin' => '24/03/2024', // Formato d/m/Y
            ];
            $mod = 4;
            // Llamar a la función del modelo
            $resultado = $vacacionEspecialModelObj->crearVacacionEspecial($reg, $mod);

            // Devolver respuesta exitosa
            $response = ['Result' => 'OK', 'id' => $resultado['id']];
        } catch (Exception $e) {
            // Manejar errores
            $this->sendErrorResponse('Error al crear la vacación especial' . $e->getMessage());
            // $response['Message'] = 'Error al crear la vacación especial: ' . $e->getMessage();
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

    /**
     * Método para obtener parámetros de request con valor por defecto
     */
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

    public function prueba()
    {
        
    }
    public function borrar()
    {
        // Simular datos de entrada para pruebas (eliminar en producción)
        $_POST['id_vaca_especial'] = 48;

        // Validar la entrada
        $validationResult = $this->validateInput();
        if (!$validationResult['status']) {
            $this->sendErrorResponse($validationResult['message']);
            return;
        }

        // Procesar la eliminación
        $idVacacionEspecial = $this->getInput('id_vaca_especial');
        $eliminacionExitosa = $this->eliminarVacacionEspecial($idVacacionEspecial);

        if ($eliminacionExitosa) {
            // $this->registerAction($idVacacionEspecial); Descomentar en prod
            $this->sendSuccessResponse();
        } else {
            $this->sendErrorResponse('No se pudo eliminar la vacación especial.');
        }
    }
    /**
     * Valida los datos de entrada.
     *
     * @return array
     */
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
    /**
     * Obtiene un valor de entrada.
     *
     * @param string $key
     * @return mixed
     */
    private function getInput($key)
    {
        require_once Config::singleton()->get('classesFolder') . 'Input.php';
        $input = new CI_Input();
        return $input->post($key, true);
    }
    /**
     * Elimina una vacación especial lógicamente.
     *
     * @param int $idVacacionEspecial
     * @return bool
     */
    private function eliminarVacacionEspecial($idVacacionEspecial)
    {
        require $this->getDefaultModelName();
        $vacacionEspecialModelObj = new VacacionespecialModel();
        return $vacacionEspecialModelObj->eliminarLogicamente($idVacacionEspecial);
    }
    /**
     * Registra la acción en el sistema.
     *
     * @param int $idVacacionEspecial
     */
    private function registerAction($idVacacionEspecial)
    {
        $funcion = (isset($_POST['master']) && $_POST['master']) ? '/indexVacacionesMaster' : '';
        $this->sessionObj->RegisterAccion($this->getAppName() . $funcion, __FUNCTION__, $idVacacionEspecial);
    }
    /**
     * Envía una respuesta de éxito.
     */
    private function sendSuccessResponse()
    {
        $this->view->showJSONPlane([
            'response' => ['Result' => 'OK']
        ]);
    }
    /**
     * Envía una respuesta de error.
     *
     * @param string $message
     */
    private function sendErrorResponse($message)
    {
        $this->view->showJSONPlane([
            'response' => [
                'Result' => 'ERROR',
                'Message' => $message
            ]
        ]);
    }

}