https://www.blackbox.ai/

CONSIDERACIONES:
	- NO SE PODRÁ TOMAR 2 PERIODOS EN UNA MISMA SOLICITUD.
	- SI DISPONEN VACACIONES VENCIDAS O GANADAS, NO PODRÁ SELECCIONAR LA OPCIÓN DE VACACIONES TRUNCAS.
	- RESPETAR LOS NÚMEROS DE DÍAS COMO MÁXIMO PARA BORRAR O CAMBIAR UNA SOLICITUD.
REGLAS DE NEGOCIO:
	- EL SISTEMA DEBERÁ CONSIDERAR EN INTERNO 2 SUBPERIODOS DE VACACIONES DE 15 DÍAS CADA UNO.
	- LA LÓGICA ES CULMINAR LOS 15 DÍAS DEL SUBPERIODO 1 PARA LUEGO PROCEDER CON LOS OTROS 15 DÍAS DEL SUBPERIODO 2.

1RA SOLICITUD: 
	- SE SOLICITARÁ COMO UN MINIMO DE 7 DÍAS Y UN MÁXIMO DE 30 DÍAS.
	- VALIDAR DISPONIBILIDAD DE DÍAS DE VACACIONES DURANTE SU PERIODO (30 DÍAS).
	- LOS DÍAS DE ESTA SOLICITUD IRAN AL BLOQUE DEL SUBPERIODO 1(LOS PRIMERO 15 DÍAS).
	
2DA SOLICITUD:
	- SE SOLICITARÁ CON UN :
	- MÍNIMO DE 8 DÍAS, SI EN LA 1RA SOLICITUD ES IGUAL 7 DÍAS.
	- MÍNIMO DE 7 DÍAS, SI EN LA 1RA SOLICITUD ES MAYOR A 7 DÍAS.
	- VALIDAR DISPONIBILIDAD DE DÍAS DE VACACIONES DURANTE SU PERIODO (30 DÍAS).
	- LOS DÍAS DE ESTA SOLICITUD IRAN AL BLOQUE DEL SUBPERIODO 1(LOS PRIMERO 15 DÍAS).
	
3RA SOLICITUD:
	- VALIDAR QUE SE HAYA CONSUMIDO LOS 15 DÍAS DEL SUBPERIODO 1, CASO CONTRARIO NO PERMITIRÁ EL REGISTRO.
	- VALIDAR DISPONIBILIDAD DE DÍAS DE VACACIONES DURANTE SU PERIODO (30 DÍAS).
	- LA REGLA PRINCIPAL ES CUBRIR LOS 4 DÍAS POR FIN DE SEMANA DE LOS 15 DÍAS DEL SUBPERIODO 2.
	- EL SISTEMA LE PERMITIRÁ COMO MÁXIMO TOMAR 5 DÍAS(PUEDE VARIAR SEGUN LA TABLA DE CONFIGURACIÓN) SIN CONSIDERAR SÁBADOS Y DOMINGOS, LUEGO DE ELLO EL SISTEMA OBLUIGARÁ A TOMAR EL SABADO Y DOMINGO EN CADA SOLICITUD HASTA COMPLETAR LOS 4 OBLIGATORIOS.

	- CUANDO EL TRABAJADOR AUN NO HA COMPLETA EL SUBPERIODO 1, PERO DESEA TOMAR VACACIONES POR DIAS INFERIORES A 7 DÍAS, SI LO PODRIA REALIZAR SIEMPRE Y CUANDO SE ENCUENTREN EN LA TABLA TBINT_VACACIONES_TEMP,
	Y ESOS DIAS SE IRAN CONSUMIENDO AL SUBPERIODO 2
	


	
    /*
	=====================================================================
					CREAR
	=====================================================================
*/

	print_r($_POST);	|	$input->post(NULL);
	RESULT:
	Array ( 
		[cboSolicitante] => 2794 
		[txtFechaIngreso] => 17/04/2017 
		[cboCondicion] => 1 
		[txtCantidadDias] => 7 
		[txtFechaInicio] => 2025-03-20 
		[txtFechaFin] => 2025-03-26 
		[idSolicitud] => 
		[tblConsolidado] => {"trunco":27.83,"ganado":30,"vencido":27,"programado":0,"por_programar":57} 
		[modalidad] => 1 
		)
		
		
	$statusCantidadDias = $this->_validarCantidadDias($input->post(NULL));
	RESULT:
	Array ( 
	[status] => 1 
	[mensaje] => 
	)
	
	
	private function _validarCantidadDias($reg)
		$userInfo = $this->sessionObj->getUserInfo($reg['cboSolicitante']);
		RESULT:
		Array ( [0] => stdClass Object ( 
			[ID_USUARIO] => 2794 
			[USUARIO] => 40648343 
			[NOM_USUARIO] => FIGUEROA MONTOYA, JONATHAN CARLOS 
			[ADMIN] => 0 
			[CORREO] => informatica4@acfarma.com 
			[DNI] => 40648343 ) 
			)
			
			
		$vacacion = $reporteModelObj->getReporteVacaciones(date('d/m/Y'),'01','','','','','',"'".$userInfo[0]->USUARIO."'");
		RESULT:
		Array ( [0] => stdClass Object ( 
					[CO_EMPR] => 01 
					[NO_EMPR] => ACFARMA 
					[DE_UNID] => GERENCIA GENERAL 
					[DE_DEPA] => GERENCIA GENERAL 
					[DE_AREA] => TECNOLOGIAS DE LA INFORMACION 
					[DE_SECC] => SOLUCIONES DE NEGOCIO 
					[CO_TRAB] => 40648343 
					[NO_TRAB] => FIGUEROA MONTOYA JONATHAN CARLOS 
					[FECHA_INGRESO] => 17/04/2017 
					[TI_SITU] => ACTIVO 
					[PERIODO] => 2022-2023 
					[DIAS_PEND] => 27.0000 
					[DE_OBSE] => 
					[IM_VALO_DIAR] => ) 
				[1] => stdClass Object ( 
					[CO_EMPR] => 01 
					[NO_EMPR] => ACFARMA 
					[DE_UNID] => GERENCIA GENERAL 
					[DE_DEPA] => GERENCIA GENERAL 
					[DE_AREA] => TECNOLOGIAS DE LA INFORMACION 
					[DE_SECC] => SOLUCIONES DE NEGOCIO 
					[CO_TRAB] => 40648343 
					[NO_TRAB] => FIGUEROA MONTOYA JONATHAN CARLOS 
					[FECHA_INGRESO] => 17/04/2017 
					[TI_SITU] => ACTIVO 
					[PERIODO] => 2023-2024 
					[DIAS_PEND] => 30.0000 
					[DE_OBSE] => 
					[IM_VALO_DIAR] => ) 
				[2] => stdClass Object ( 
					[CO_EMPR] => 01 
					[NO_EMPR] => ACFARMA 
					[DE_UNID] => GERENCIA GENERAL 
					[DE_DEPA] => GERENCIA GENERAL 
					[DE_AREA] => TECNOLOGIAS DE LA INFORMACION 
					[DE_SECC] => SOLUCIONES DE NEGOCIO 
					[CO_TRAB] => 40648343 
					[NO_TRAB] => FIGUEROA MONTOYA JONATHAN CARLOS 
					[FECHA_INGRESO] => 17/04/2017 
					[TI_SITU] => ACTIVO 
					[PERIODO] => 2024-2025 
					[DIAS_PEND] => 27.8300 
					[DE_OBSE] => PERIODO TRUNCO 
					[IM_VALO_DIAR] => ) 
			  )
		  
		  
		$regProgramados = $vacacionModelObj->getNumProgramadas($reg['cboSolicitante'],1,$idSolicitud);
		RESULT:
		Array ( [0] => stdClass Object ( 
					[dias_total] => ) 
			  )
			  
		
		$usoVaca = $this->_formatVacionesOfisis($vacacion,$regProgramados,1);
		RESULT:
		Array ( 
			[trunco] => 27.83 
			[ganado] => 30 
			[vencido] => 27 
			[programado] => 0 
			[por_programar] => 57 
			)

