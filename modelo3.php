private function _validarTerceraSolicitud($diasSolicitados, $diasSubperiodo1, $diasSubperiodo2, $diasNoHabilConsumo) {
    require_once $this->getDefaultModelName();
    require_once $this->getModelByName('VacacionConfiguracion', 'vacacionconfiguracion');

    $vacaConfigObj = new VacacionConfiguracionModel();

    $resultado = array('Result' => "ERROR", 'Message' => '');
    
    // 1. Validar que se haya consumido todo el subperiodo 1
    if ($diasSubperiodo1 < 15) {
        $resultado['Message'] = "Debe consumir los 15 días del primer subperiodo antes de registrar esta solicitud.";
        return $resultado;
    }
    
    // 2. Contar días hábiles y no hábiles en la solicitud actual
    $diasTipoActual = $this->_contarDiasHabilesNoHabiles($diasSolicitados);
    
    // 3. Obtener configuración para días flexibles
    $diasFlexiblesConfig = $vacaConfigObj->getConfigById(10); // Asumo que el ID 10 contiene la config de días flexibles
    $diasFlexibles = intval($diasFlexiblesConfig); // Por defecto 5 días flexibles
    
    // 4. Evaluar si está en el rango de días flexibles
    if ($diasSubperiodo2 < $diasFlexibles) {
        // Está dentro de los días flexibles, verificar que no sobrepase el límite
        if (($diasSubperiodo2 + count($diasSolicitados)) <= $diasFlexibles) {
            // Está dentro del límite de días flexibles, permitir solicitud sin restricciones
            $resultado['Result'] = 'OK';
            return $resultado;
        } else {
            // Sobrepasa los días flexibles, debe incluir fin de semana obligatorio
            // Verificar si la solicitud incluye sábado y domingo
            $incluyeFinDeSemana = $this->_verificarInclucionFinDeSemana($diasSolicitados);
            
            if (!$incluyeFinDeSemana) {
                $resultado['Message'] = "Al sobrepasar los {$diasFlexibles} días flexibles, debe incluir obligatoriamente un sábado y domingo en su solicitud.";
                return $resultado;
            }
        }
    }
    
    // 5. Verificar la obligación de fines de semana (8 días en total - sábados y domingos)
    $diasFinDeSemanaObligatorios = 8; // Total de sábados y domingos obligatorios
    
    if ($diasNoHabilConsumo < $diasFinDeSemanaObligatorios) {
        // Aún debe consumir días de fin de semana obligatorios
        $diasNoHabilRestantes = $diasFinDeSemanaObligatorios - $diasNoHabilConsumo;
        
        // Contar cuántos fines de semana incluye en la solicitud actual
        $finDeSemanaEnSolicitud = $diasTipoActual['no_habil'];
        
        if ($finDeSemanaEnSolicitud == 0) {
            $resultado['Message'] = "Debe incluir al menos un fin de semana en su solicitud. Aún le faltan {$diasNoHabilRestantes} días de fin de semana por consumir.";
            return $resultado;
        }
    }
    
    // Si llegó hasta aquí, la solicitud es válida
    $resultado['Result'] = 'OK';
    return $resultado;
}

/**
 * Verifica si el conjunto de días solicitados incluye al menos un fin de semana completo (sábado y domingo)
 * 
 * @param array $diasSolicitados Array de fechas solicitadas
 * @return boolean True si incluye al menos un sábado y un domingo, False en caso contrario
 */
private function _verificarInclucionFinDeSemana($diasSolicitados) {
    $tieneSabado = false;
    $tieneDomingo = false;
    
    foreach ($diasSolicitados as $fecha) {
        $diaSemana = date('w', strtotime($fecha));
        
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