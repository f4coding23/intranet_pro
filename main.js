var $ajaxEmpresa;
var $ajaxGerencia;
var $ajaxDepartamento;
var $ajaxArea;
var tblConsolidado;
var generador = false;

/*var localeDate = {
    "separator": " - ",
    "applyLabel": "Aplicar",
    "cancelLabel": "Cancelar",
    "fromLabel": "DE",
    "toLabel": "HASTA",
    "customRangeLabel": "Personalizado",
    "daysOfWeek": [
    "Dom",
    "Lun",
    "Mar",
    "Mie",
    "Jue",
    "Vie",
    "Sáb"
    ],
    "monthNames": [
    "Enero",
    "Febrero",
    "Marzo",
    "Abril",
    "Mayo",
    "Junio",
    "Julio",
    "Agosto",
    "Septiembre",
    "Octubre",
    "Noviembre",
    "Diciembre"
    ],
    "firstDay": 1
}*/
var dateOptions = {
    locale: 'es',
    format: 'DD/MM/YYYY',
    allowInputToggle: false,
    ignoreReadonly: true,
    showTodayButton: true,
    showClose: true,
    useCurrent: false
}

$(function () {
    /*var start = '';
    var end = '';*/

	$('#qryFechaInicio').datetimepicker(dateOptions);
	$('#qryFechaFin').datetimepicker(dateOptions);

	$("#qryFechaInicio").on("dp.change", function (e) {
		$('#qryFechaFin').data("DateTimePicker").minDate(e.date);
	});
	$("#qryFechaFin").on("dp.change", function (e) {
		$('#qryFechaInicio').data("DateTimePicker").maxDate(e.date);
	});
    
	$("#qry_empresa").change(function() {
		var $gerencia   = $("#qry_gerencia");
		var $valor = $(this).val();
		//Cargar información del combo de áreas
		$gerencia.html('<option value=""> Seleccione </option>');
		$("#qry_area").html('<option value=""> Seleccione </option>');
		//$("#qry_seccion").html('<option value=""> Seleccione </option>');

		if($ajaxEmpresa && $ajaxEmpresa.readyState != 4){
			$ajaxEmpresa.abort();
		}

		if($valor != ''){
			$ajaxEmpresa = $.post('directorio/getGerencias/', {qry_empresa: $valor}, function(data) {
				if (data.Result === 'OK') {
					$.each(data.Options, function(index, el) {
						$gerencia.append('<option value="' + el.Value + '">' + el.DisplayText + '</option>');
					});
				} else {
					showConfirmWarning(data.Message);
				}
			}, 'json'); 
		}        
	});

	$("#qry_gerencia").change(function() {
		var $departamento   = $("#qry_departamento");
		var $valor = $(this).val();
		$valorEmpresa = $("#qry_empresa").val();
		//Cargar información del combo de áreas
		$departamento.html('<option value=""> Seleccione </option>');
        $('#qry_area').html('<option value=""> Seleccione </option>');
		//$("#qry_seccion").html('<option value=""> Seleccione </option>');

		if($ajaxGerencia && $ajaxGerencia.readyState != 4){
			$ajaxGerencia.abort();
		}

		if($valor != ''){
			$ajaxGerencia = $.post('directorio/getDepartamentos/', {qry_empresa: $valorEmpresa, qry_gerencia: $valor}, function(data) {
				if (data.Result === 'OK') {
					$.each(data.Options, function(index, el) {
						$departamento.append('<option value="' + el.Value + '">' + el.DisplayText + '</option>');
					});
				} else {
					showConfirmWarning(data.Message);
				}
			}, 'json'); 
		}
	});

    $("#qry_departamento").change(function() {
        var $area   = $("#qry_area");
        var $valor = $(this).val();
        $valorEmpresa = $("#qry_empresa").val();
        $valorGerencia = $("#qry_gerencia").val();
        //Cargar información del combo de áreas
        $area.html('<option value=""> Seleccione </option>');
        $("#qrySeccion").html('<option value=""> Seleccione </option>');

        if($ajaxDepartamento && $ajaxDepartamento.readyState != 4){
            $ajaxDepartamento.abort();
        }

        if($valor != ''){
            $ajaxDepartamento = $.post('directorio/getAreas/', {qry_empresa: $valorEmpresa, qry_departamento:  $valor, qry_gerencia: $valorGerencia}, function(data) {
                if (data.Result === 'OK') {
                    $.each(data.Options, function(index, el) {
                        $area.append('<option value="' + el.Value + '">' + el.DisplayText + '</option>');
                    });
                } else {
                    showConfirmWarning(data.Message);
                }
            }, 'json'); 
        }
    });

	$("#qry_area").change(function() {
		var $seccion   = $("#qry_seccion");
		var $valor = $(this).val();
		$valorEmpresa = $("#qry_empresa").val();

		$valorDepartamento = $("#qry_departamento").val();
		//Cargar información del combo de áreas
		$seccion.html('<option value=""> Seleccione </option>');
		$("#qry_seccion").html('<option value=""> Seleccione </option>');

		if($ajaxArea && $ajaxArea.readyState != 4){
			$ajaxArea.abort();
		}

		if($valor != 0){
			$ajaxArea = $.post('directorio/getSecciones/', {
				qry_empresa: $valorEmpresa,
				qry_departamento: $valorDepartamento,
				qry_area: $valor
			}, function(data) {
				if (data.Result === 'OK') {
					$.each(data.Options, function(index, el) {
						$seccion.append('<option value="' + el.Value + '">' + el.DisplayText + '</option>');
					});
				} else {
					showConfirmWarning(data.Message);
				}
			}, 'json'); 
		}
	});

    /*$('#reportrange').daterangepicker({
        locale: localeDate,
        autoUpdateInput: false,
        //autoApply: true,
        format: "DD/MM/YYYY",
        alwaysShowCalendars: true,
        linkedCalendars: false,
        showDropdowns: true,
        ranges: {
            'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
            'Este mes': [moment().startOf('month'), moment().endOf('month')],
            'El mes pasado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Año actual': [moment().startOf('year'), moment().endOf('year')],
            'El año pasado': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
        }
    });

    $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
        $('#reportrange span').html('<b>Desde:</b> '+picker.startDate.format('L')+' <b>Hasta:</b> '+picker.endDate.format('L'));
        start = picker.startDate;
        end = picker.endDate;
	});

    $('#reportrange').on('cancel.daterangepicker', function(ev, picker) {
        $('#reportrange span').html('');
        start = '';
        end = '';
    });*/

    $("#vacacionesContainer").jtable({
        title: 'Listado de Solicitudes de Vacaciones',
        paging: true,
        pageSize: 20,
        sorting: true,
        defaultSorting: 'fecha_inicio DESC',
        saveUserPreferences: false,
        toolbar: {
            hoverAnimation: true,
            hoverAnimationDuration: 60,
            hoverAnimationEasing: undefined,
            items: [{
                icon: 'assets/browser-components/jquery-jtable/themes/lightcolor/add.png',                  
                text: 'Crear solicitud de vacaciones',
                click: function () {
                    loading(true,'Obteniendo información');

                    $.post($getAppName+'/indexCrear/', {}, function(data) {
                        loading(false);
                        if (data.Result === 'OK') {
                            generador = data.generador;
                            //Armar el Template
                            var template = $('#tplFrmModal').html();
                            Mustache.parse(template);   // optional, speeds up future uses

                            var optionsRender = {
                                action: $getAppName+'/crear',
                                generador: data.generador,
                                val_button: 'Generar Solicitud de vacaciones',
                                edit  : false,
                                listCondicion : data.cboCondicion,
                                //sel_motivo : function() { return (this.id_vaca_condicion == data.record.id_vaca_condicion) ? "selected":"";},
                            }

                            if(!data.generador){
                                optionsRender.val_empresa = data.empresa;
                                optionsRender.val_gerencia = data.gerencia;
                                optionsRender.val_area = data.area;
                                optionsRender.val_id_solicitante = data.id_solicitante;
                                optionsRender.val_fecha_ingreso = moment(data.fecha_ingreso.date).format('L');
                            }

                            var rendered = Mustache.render(template,optionsRender);
                            $('#modalForm .modal-dialog .modal-content').html(rendered);
                            $('#modalForm .modal-dialog').draggable({handle: ".modal-header"});
                            setFunctionFormulario();
                            $('#modalForm').bootstrapModal({
                                show: true,
                                backdrop: false
                            });
                        } else {
                            showConfirmWarning(data.Message);
                        }
                    }, 'json')
                    .fail(function() {
                        loading(false);
                        showConfirmError('Ocurrió un Error interno');
                    });
                }
            }]
        },
        actions: {
            listAction: $getAppName+'/listar/'
        },
        fields: {
            ver:{
                title: '',
                width: '2%',
                sorting: false,
                edit: false,
                create: false,
                display: function (data) {
                    var $button =$('<button class="btn btn-ac btn-xs" title="Aprobaciones"><i class="glyphicon glyphicon-user"></i></button>');
                    $button.click(function () {
                        $('#vacacionesContainer').jtable('openChildTable',
                            $button.closest('tr'),
                            {
                                title: ' Autorizaciones',
                                actions: {
                                    listAction: $getAppName+'/listarAutorizaciones/?id_vacacion=' + data.record.id_vacacion,
                                },
                                fields: {
                                    id_vacacion: {
                                        type: 'hidden',
                                        defaultValue: data.record.id_vacacion
                                    },
                                    id_vaca_aut: {
                                        title: 'idAutorizacion',
                                        key: true,
                                        list: false
                                    },
                                    autorizador: {
                                        title: 'Autorizador',
                                        width: '30%'
                                    },
                                    estado_aprobacion: {
                                        title: 'Estado',
                                        width: '11%'
                                    },
                                    fecha_autorizacion: {
                                        title: 'Fecha Autorización',
                                        display:  function(data){
                                            if(data.record.fecha_autorizacion){
                                                return moment(data.record.fecha_autorizacion.date).format('DD/MM/YYYY HH:mm');
                                            }else{
                                                return '';
                                            }
                                        },
                                        width: '17%'
                                    },
                                    motivo_rechazo: {
                                        title: 'Comentario',
                                        width: '25%'
                                    }
                                }
                            }, function (data) { //opened handler
                                data.childTable.jtable('load');
                            });
                    });
                    return $button;
                }
            },
            id_vacacion:{
                title: 'Id',
                width: '3%',
                list: true,
                key: true
            },
            tipo: {
                title: 'Tipo',
                width: '5%'
            },
            vaca_condicion: {
                title: 'Condición',
                width: '13%'
            },
            solicitante: {
                title: 'solicitante',
                width: '25%'
            },
            fecha_crea: {
                title: 'Fch. Solicitud',
                display:  function(data){
                    return moment(data.record.fecha_crea.date).format('DD/MM/YYYY')
                },
                width: '7%'
            },
            fecha_inicio: {
                title: 'Fch. Inicio',
                display:  function(data){
                    return moment(data.record.fecha_inicio.date).format('DD/MM/YYYY')
                },
                width: '7%'
            },
            fecha_fin: {
                title: 'Fch. Fin',
                display:  function(data){
                    return moment(data.record.fecha_fin.date).format('DD/MM/YYYY')
                },
                width: '7%'
            },
            num_dias: {
                title: '# Días',
                width: '5%'
            },
            vaca_estado: {
                title: 'Estado',
                width: '10%'
            },
            acciones:{
                title: 'Acciones',
                width: '8%',
                sorting: false,
                edit: false,
                create: false,
                display: function (data) {
                    var btnGroup = $('<div class="btn-group" role="group"></div>');
                    var estadosEdit = [1,2,3,4];
                    var estadosEliminar = [1,2,3];
                    var estadosEliminarPropio = [1,2,3,4];
                    var dateInicio = moment(data.record.fecha_inicio.date);
                    var diferenciaDias = dateInicio.diff(moment().startOf('day'), 'days');

                    //editar
                    if(estadosEdit.includes(data.record.id_vaca_estado) && diferenciaDias > maxDayToEdit && data.record.own){
                        btnEditar = $('<button class="btn btn-ac btn-xs" title="Editar"><i class="fa fa-pencil"></i></button>');
                        btnEditar.click(function () {
                            loading(true,'Obteniendo información');

                            $.post($getAppName+'/indexCrear/', {idSolicitante: data.record.id_solicitante}, function(result) {
                                loading(false);

                                var template = $('#tplFrmModal').html();
                                Mustache.parse(template);   // optional, speeds up future uses
                                var optionsRender = {
                                    action: $getAppName+'/editar',
                                    val_button: 'modificar solicitud de vacaciones',
                                    generador: result.generador,
                                    edit  : true,
                                    val_id_vacacion: data.record.id_vacacion,
                                    listCondicion : result.cboCondicion,
                                    sel_condicion : function() { return (this.id_vaca_condicion == data.record.id_vaca_condicion) ? "selected":"";},
                                    val_empresa : data.record.empresa,
                                    val_gerencia : data.record.gerencia,
                                    val_area : data.record.area,
                                    val_id_solicitante : data.record.id_solicitante,
                                    val_nombre_solicitante : data.record.solicitante,
                                    val_fecha_ingreso : moment(result.fecha_ingreso.date,'').format('L'),
                                    val_fecha_inicio : moment(data.record.fecha_inicio.date).format('L'),
                                    val_fecha_fin : moment(data.record.fecha_fin.date).format('L'),
                                    val_dias : data.record.num_dias
                                }

                                var rendered = Mustache.render(template,optionsRender);
                                $('#modalForm .modal-dialog .modal-content').html(rendered);
                                $('#modalForm .modal-dialog').draggable({handle: ".modal-header"});
                                setFunctionFormulario(true, data.record.fecha_inicio, data.record.fecha_fin);
                                $('#modalForm').bootstrapModal({
                                    show: true,
                                    backdrop: false
                                });
                            }, 'json')
                            .fail(function() {
                                loading(false);
                                showConfirmError('Ocurrió un Error interno');
                            });

                        });
                        btnGroup.append(btnEditar);
                    }

                    //Confirmar Solicitud
                    if(data.record.id_vaca_estado == '1' /*&& data.record.propio*/){
                        btnConfirmar = $('<button class="btn btn-ac btn-xs"><i class="glyphicon glyphicon-ok" title="Confirmar Solicitud"></i></button>');
                        btnConfirmar.click(function () {
                            $.post($getAppName+'/confirmarSolicitud/', {id_vacacion :data.record.id_vacacion}, function(data) {
                                if (data.Result === 'OK') {
                                    showConfirmSuccess('Se confirmó correctamente la solicitud de vacaciones');
                                    $('#vacacionesContainer').jtable('reload');
                                } else {
                                    showConfirmWarning(data.Message);
                                }
                            }, 'json')
                            .fail(function() {
                                showConfirmError('Ocurrió un Error al intentar comunicarse con el servidor');
                            });
                        });
                        btnGroup.append(btnConfirmar);
                    }

                    //eliminar
                    if((estadosEliminar.includes(data.record.id_vaca_estado) && data.record.own) || (estadosEliminarPropio.includes(data.record.id_vaca_estado) && data.record.propio)){
                        btnEliminar = $('<button data-style="slide-up" class="btn btn-ac btn-xs ladda-button" title="Eliminar Solicitud"><span class="ladda-label"><i class="glyphicon glyphicon-trash"></i></span></button>');
                        var btnLdEliminar = Ladda.create(btnEliminar[0]);
                        btnEliminar.click(function () {
                            btnLdEliminar.start();
                            $.confirm({
                                theme:'warning',
                                icon: 'fa fa-exclamation-triangle',
                                title: '¡Eliminar!',
                                content: '¿Esta seguro de eliminar el registro?, esta acción no podrá ser revertida.',
                                confirm: function () {
                                    $.post($getAppName+'/borrar/', {id_vacacion:data.record.id_vacacion}, function(data) {
                                        btnLdEliminar.stop();
                                        if (data.Result === 'OK') {
                                            $('#vacacionesContainer').jtable('reload');
                                        } else {
                                            showConfirmWarning(data.Message);
                                        }
                                    }, 'json')
                                    .fail(function() {
                                        btnLdEliminar.stop();
                                        showConfirmError('Ocurrió un Error al intentar comunicarse con el servidor');
                                    });
                                },
                                cancel: function(){
                                    btnLdEliminar.stop();
                                }
                            });

                        });
                        btnGroup.append(btnEliminar);
                    }

                    //Confirmar Vacacion tomada
                    if(data.record.id_vaca_estado == '4' && data.record.own){
                        btnConfirmVaca = $('<button data-style="slide-up" class="btn btn-ac btn-xs ladda-button" title="Confirmar Vacaciones"><span class="ladda-label"><i class="fa fa-calendar-check-o"></i></span></button>');
                        var btnLdConfirmar = Ladda.create(btnConfirmVaca[0]);
                        btnConfirmVaca.click(function () {
                            btnLdConfirmar.start();
                            $.confirm({
                                theme:'warning',
                                icon: 'fa fa-exclamation-triangle',
                                title: '¡Confirmar!',
                                content: '¿Esta seguro de confirmar la ejecución de las vacaciones?',
                                confirm: function () {
                                    $.post($getAppName+'/confirmarEjecucion/', {id_vacacion:data.record.id_vacacion}, function(data) {
                                        btnLdConfirmar.stop();
                                        if (data.Result === 'OK') {
                                            $('#vacacionesContainer').jtable('reload');
                                        } else {
                                            showConfirmWarning(data.Message);
                                        }
                                    }, 'json')
                                    .fail(function() {
                                        btnLdConfirmar.stop();
                                        showConfirmError('Ocurrió un Error al intentar comunicarse con el servidor');
                                    });
                                },
                                cancel: function(){
                                    btnLdConfirmar.stop();
                                }
                            });
                        });
                        btnGroup.append(btnConfirmVaca);
                    }

                    //Formato impreso
                    if(data.record.id_vaca_estado == '4' || data.record.id_vaca_estado == '5'){
                        btnBoleta = $('<button class="btn btn-ac btn-xs" title="Boleta"><i class="fa fa-file-text"></i></button>');
                        btnBoleta.click(function () {
                            open('POST',$getAppName+"/exportarBoleta/",{
                                qryIdVacacion:   data.record.id_vacacion
                            },'_blank');
                        });
                        btnGroup.append(btnBoleta);
                    }

                    return btnGroup;
                }
            }
        }
    });

    $('#LoadRecordsButton').click(function (e) {
        e.preventDefault();
        /*var qryFechaInicio = start?start.format('YYYY-MM-DD'):'';
        var qryFechaFin = end?end.format('YYYY-MM-DD'):'';*/

        $('#vacacionesContainer').jtable('load', {
            qry_empresa     : $('#qry_empresa').val(),
            qry_gerencia    : $("#qry_gerencia").val(),
            qry_departamento: $("#qry_departamento").val(),
            qry_area        : $("#qry_area").val(),
            qry_seccion     : $("#qry_seccion").val(),
            qry_colaborador : $("#qry_colaborador").val(),
            qry_ini_rango   : $("#qryFechaInicio").val(),
            qry_fin_rango   : $("#qryFechaFin").val()
        });
    });

    $('#btnExportar').click(function(e){
        /*var qryFechaInicio = start?start.format('YYYY-MM-DD'):'';
        var qryFechaFin = end?end.format('YYYY-MM-DD'):'';*/
        
        open('POST',$getAppName+"/exportar/",{
            qry_empresa     : $('#qry_empresa').val(),
            qry_gerencia    : $("#qry_gerencia").val(),
            qry_departamento: $("#qry_departamento").val(),
            qry_area        : $("#qry_area").val(),
            qry_seccion     : $("#qry_seccion").val(),
            qry_colaborador : $('#qry_colaborador').val(),
            qry_ini_rango   : $("#qryFechaInicio").val(),
            qry_fin_rango   : $("#qryFechaFin").val()
        },'_blank');
    });

	$('#LoadRecordsButton').click();
    $("#qry_empresa").change();
});

// Modificación de la función setFunctionFormulario en main.js
function setFunctionFormulario(editar, fechaInicio, fechaFin) {
    editar = editar || false;
    var uso_vacaciones = 0;

    $('#divFechaInicio').datetimepicker(dateOptions);
    $('#divFechaFin').datetimepicker(dateOptions);

    // Inicializar la tabla de detalle si existe
    if ($('#tblDetallePeriodos').length > 0) {
        // Configurar estilos de la tabla
        $('#tblDetallePeriodos thead th').css('background-color', '#b23535').css('color', 'white');
        
        // Cargar datos iniciales si ya se conoce el solicitante
        if ($('#cboSolicitante').val()) {
            cargarTablaPeriodos($('#cboSolicitante').val(), $('#qry_empresa').val(), $('#idSolicitud').val());
        }
    }
    
    if(editar) {
        $('#divFechaInicio').data("DateTimePicker").minDate(moment().format('L'));
        $('#divFechaInicio').data("DateTimePicker").maxDate(moment(fechaFin.date).format('L'));
        $('#divFechaFin').data("DateTimePicker").minDate(moment(fechaInicio.date).format('L'));
    } else {
        var optMinDate = moment().format('L');
        $('#divFechaInicio').data("DateTimePicker").minDate(optMinDate);
        $('#divFechaFin').data("DateTimePicker").minDate(optMinDate);
    }
    
    var maxFecha = moment().endOf('year').add(2,'years');
    $('#divFechaFin').data("DateTimePicker").maxDate(maxFecha);

    $('#divFechaInicio').on("dp.change", function (e) {
        $('#divFechaFin').data("DateTimePicker").minDate(e.date);
    });

    $('#divFechaFin').on("dp.change", function (e) {
        $('#divFechaInicio').data("DateTimePicker").maxDate(e.date);
    });

    $('#divFechaInicio').on("dp.show", function (e) {
        if($('#cboCondicion').val() == '0'){
            e.preventDefault();
            showConfirmWarning('Seleccione una condición');
            return false;
        }
    });

    $('#divFechaFin').on("dp.show", function (e) {
        if($('#cboCondicion').val() == '0'){
            e.preventDefault();
            showConfirmWarning('Seleccione una condición');
            return false;
        }
    });

    $('#divFechaInicio').on("dp.change", function (e) {
        cambiarFechas(uso_vacaciones);
    });

    $('#divFechaFin').on("dp.change", function (e) {
        cambiarFechas(uso_vacaciones);
    });

    $('#cboCondicion').change(function(){
        if($('#cboSolicitante').val()){
            tblConsolidado.ajax.reload();
            // Cargar también la tabla de detalles por periodo
            cargarTablaPeriodos($('#cboSolicitante').val(), $('#qry_empresa').val(), $('#idSolicitud').val());
        }
    });

    // Tabla principal de consolidado
    tblConsolidado = $('#tblConsolidado').DataTable({
        searching: false,
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: true,
        lengthChange: false,
        deferLoading: 0, // Deshabilitar el ajax automático
        sorting: false,
        info: false,
        paging: false,
        ordering: false,
        ajax: {
            url: $getAppName+'/listarConsolidado/',
            type: 'POST',
            data: function(){
                var data = { 
                    idSolicitante: $('#cboSolicitante').val(),
                    idCondicion: $('#cboCondicion').val(),
                    modificacion: editar,
                    idSolicitud: $('#idSolicitud').val()
                };
                return data;
            }
        },
        columns: [
            { data: "trunco", title : "Truncas"},
            { data: "ganado", title : "Pendientes"},
            { data: "vencido", title : "Vencidas"},
            { data: 'programado', title: 'Programadas'},
            { data: "por_programar", title : "Por Programar",
                render: function (data, type, row, meta){
                    if(typeof row.por_programar === 'object'){
                        return 'Habiles: <b>'+row.por_programar.habil+'</b> <br> No Hábiles: <b>'+row.por_programar.no_habil+'</b>';
                    }else{
                        return row.por_programar;
                    }
                }
            }
        ]
    });

    // Nueva tabla para mostrar detalle por periodo
    // Este código añade la nueva tabla que aparece en la segunda imagen
    // Función para cargar la tabla de detalle de periodos
function cargarTablaPeriodos(idSolicitante, idCondicion, idSolicitud) {
    if($('#tblDetallePeriodos').length > 0) {
        $.post($getAppName+'/detallePeriodo/', {
            qry_cod: idSolicitante,
            qry_emp: '01', // Empresa predeterminada
            qry_fecha_corte: moment().format('DD/MM/YYYY')
        }, function(response) {
            if(response.Result === 'OK') {
                // Limpiar tabla
                var tbodyHtml = '';
                var ganadas = 0;
                var gozadas = 0;
                var truncas = 0;
                var saldo = 0;

                // Ordenar los periodos de más reciente a más antiguo
                var periodos = response.Records.sort(function(a, b) {
                    return b.PE_VACA.localeCompare(a.PE_VACA);
                });

                // Generar las filas de la tabla
                $.each(periodos, function(index, record) {
                    tbodyHtml += '<tr>';
                    tbodyHtml += '<td>' + record.PE_VACA + '</td>';
                    tbodyHtml += '<td>' + (parseFloat(record.GANADAS) || 0) + '</td>';
                    tbodyHtml += '<td>' + (parseFloat(record.GOZADAS) || 0) + '</td>';
                    tbodyHtml += '<td>' + (parseFloat(record.TRUNCAS) || 0).toFixed(2) + '</td>';
                    tbodyHtml += '<td>' + (parseFloat(record.SALDO) || 0).toFixed(2) + '</td>';
                    
                    // Determinar el estilo del estado según su valor
                    var estadoClass = '';
                    if(record.ESTADO === 'Pendiente') estadoClass = 'text-success';
                    else if(record.ESTADO === 'Vencido') estadoClass = 'text-danger';
                    else if(record.ESTADO === 'No Disponible') estadoClass = 'text-warning';
                    else if(record.ESTADO === 'Cerrado') estadoClass = 'text-muted';
                    
                    tbodyHtml += '<td class="' + estadoClass + '">' + record.ESTADO + '</td>';
                    tbodyHtml += '</tr>';

                    // Sumar los valores para los totales
                    ganadas += parseFloat(record.GANADAS || 0);
                    gozadas += parseFloat(record.GOZADAS || 0);
                    truncas += parseFloat(record.TRUNCAS || 0);
                    saldo += parseFloat(record.SALDO || 0);
                });

                // Actualizar el contenido de la tabla
                $('#tblDetallePeriodos tbody').html(tbodyHtml);
                
                // Actualizar la fila de totales
                $('#tblDetallePeriodos tfoot tr td:eq(1)').text(ganadas);
                $('#tblDetallePeriodos tfoot tr td:eq(2)').text(gozadas);
                $('#tblDetallePeriodos tfoot tr td:eq(3)').text(truncas.toFixed(2));
                $('#tblDetallePeriodos tfoot tr td:eq(4)').text(saldo.toFixed(2));
            } else {
                // Si no hay datos, mostrar mensaje o tabla vacía
                $('#tblDetallePeriodos tbody').html('<tr><td colspan="6" class="text-center">No hay periodos disponibles</td></tr>');
                // Resetear totales
                $('#tblDetallePeriodos tfoot tr td:eq(1)').text('0');
                $('#tblDetallePeriodos tfoot tr td:eq(2)').text('0');
                $('#tblDetallePeriodos tfoot tr td:eq(3)').text('0');
                $('#tblDetallePeriodos tfoot tr td:eq(4)').text('0');
            }
        }, 'json')
        .fail(function() {
            showConfirmError('Ocurrió un error al obtener el detalle de periodos');
        });
    }
}

    if($('#cboSolicitante').attr('type') == 'hidden'){
        tblConsolidado.ajax.reload();
        // Cargar también la tabla de detalles por periodo
        if($('#tblDetallePeriodos').length > 0) {
            cargarTablaPeriodos($('#cboSolicitante').val(), $('#qry_empresa').val(), $('#idSolicitud').val());
        }

        // Obtener la cantidad de dias disponibles por "Uso de vacaciones"
        $.post($getAppName+'/listarConsolidado/', {idSolicitante: $('#cboSolicitante').val(), idCondicion: 1, idSolicitud: $('#idSolicitud').val()}, function(data) {
            uso_vacaciones = data.data[0].por_programar;                
        }, 'json')
        .fail(function() {
            showConfirmError('Ocurrió un Error al consultar la disponibilidad de "Uso de vacaciones"');
        });
    } else {
        var opt = {
            ajax : {
                url     : $getAppName+'/buscarSolicitante',
                type    : 'POST',
                dataType: 'json',
                data    : {
                    q: '{{{q}}}'
                }
            },
            log : 0,
            minLength: 3,
            preserveSelected: false,
            locale: {
                emptyTitle: "Seleccione y comience a escribir",
                currentlySelected: "Seleccionado",
                searchPlaceholder: "Buscar...",
                statusSearching: "Buscando...",
                statusNoResults: "Sin Resultados",
                statusInitialized: "Empieza a escribir una consulta de búsqueda",
                statusSearching: "Buscando...",
                statusTooShort: 'Introduzca más caracteres',
                errorText: "No se puede recuperar resultados",
            },
            preprocessData: function (data) {
                var i, l = data.length, array = [];
                if (l) {
                    for (i = 0; i < l; i++) {
                        array.push($.extend(true, data[i], {
                            text : data[i].DisplayText,
                            value: data[i].Value,
                            data : {
                                subtext: data[i].DisplayText2,
                                id_solicitante: data[i].Value,
                                fecha_ingreso: moment(data[i].fechaIngreso.date).format('L') 
                            }
                        }));
                    }
                }
                // You must always return a valid array when processing data. The
                // data argument passed is a clone and cannot be modified directly.
                return array;
            }
        };

        $("#cboSolicitante").selectpicker().filter('.with-ajax').ajaxSelectPicker(opt);

        $('#cboSolicitante').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
            var option = $('#cboSolicitante option')[clickedIndex];
            var data = $(option).data();
            var fechaIngreso = data.fecha_ingreso;
            $('#txtFechaIngreso').val(fechaIngreso);
            tblConsolidado.ajax.reload();

            // Cargar también la tabla de detalles por periodo
            if($('#tblDetallePeriodos').length > 0) {
                cargarTablaPeriodos(data.id_solicitante, $('#qry_empresa').val(), $('#idSolicitud').val());
            }

            // Obtener la cantidad de dias disponibles por "Uso de vacaciones"
            $.post($getAppName+'/listarConsolidado/', {idSolicitante: data.id_solicitante, idCondicion: 1, idSolicitud: $('#idSolicitud').val()}, function(data) {
                uso_vacaciones = data.data[0].por_programar;                
            }, 'json')
            .fail(function() {
                showConfirmError('Ocurrió un Error al consultar la disponibilidad de "Uso de vacaciones"');
            });
        });
    }

    // Función para cargar la tabla de periodos
/**
 * Función para cargar la tabla de detalle de periodos
 * Esta versión simplificada se enfoca en calcular correctamente los totales
 */
/**
 * Función para cargar la tabla de detalle de periodos sin mostrar totales
 */
function cargarTablaPeriodos(idSolicitante, idEmpresa, idSolicitud) {
    if($('#tblDetallePeriodos').length > 0) {
        // Eliminar completamente el pie de tabla para evitar totales
        $('#tblDetallePeriodos tfoot').remove();
        
        $.post($getAppName+'/detallePeriodo/', {
            qry_cod: idSolicitante,
            qry_emp: idEmpresa,
            qry_fecha_corte: moment().format('DD/MM/YYYY'),
            qry_trunco: 0
        }, function(response) {
            if(response.Result === 'OK' && response.Records && response.Records.length > 0) {
                // Filtrar para excluir los estados "Cerrado"
                var periodosFiltrados = [];
                for(var i = 0; i < response.Records.length; i++) {
                    if(response.Records[i].ESTADO !== 'Cerrado') {
                        periodosFiltrados.push(response.Records[i]);
                    }
                }
                
                // Ordenar los periodos de más reciente a más antiguo
                periodosFiltrados.sort(function(a, b) {
                    return b.PE_VACA.localeCompare(a.PE_VACA);
                });
                
                // Generar el HTML de las filas
                var tbodyHtml = '';
                
                // Recorrer los periodos filtrados
                for(var j = 0; j < periodosFiltrados.length; j++) {
                    var periodo = periodosFiltrados[j];
                    
                    // Convertir a números para formateo
                    var ganadas = Number(periodo.GANADAS) || 0;
                    var gozadas = Number(periodo.GOZADAS) || 0;
                    var truncas = Number(periodo.TRUNCAS) || 0;
                    var saldo = Number(periodo.SALDO) || 0;
                    
                    // Generar la fila
                    tbodyHtml += '<tr>';
                    tbodyHtml += '<td>' + periodo.PE_VACA + '</td>';
                    tbodyHtml += '<td>' + ganadas + '</td>';
                    tbodyHtml += '<td>' + gozadas + '</td>';
                    tbodyHtml += '<td>' + truncas.toFixed(2) + '</td>';
                    tbodyHtml += '<td>' + saldo.toFixed(2) + '</td>';
                    
                    var estadoClass = '';
                    if(periodo.ESTADO === 'Pendiente') estadoClass = 'text-success';
                    else if(periodo.ESTADO === 'Vencido') estadoClass = 'text-danger';
                    else if(periodo.ESTADO === 'No Disponible') estadoClass = 'text-warning';
                    
                    tbodyHtml += '<td class="' + estadoClass + '">' + periodo.ESTADO + '</td>';
                    tbodyHtml += '</tr>';
                }
                
                // Actualizar el cuerpo de la tabla
                $('#tblDetallePeriodos tbody').html(tbodyHtml);
            } else {
                // Si no hay datos, mostrar mensaje
                $('#tblDetallePeriodos tbody').html('<tr><td colspan="6" class="text-center">No hay periodos disponibles</td></tr>');
            }
        }, 'json')
        .fail(function() {
            showConfirmError('Ocurrió un error al obtener el detalle de periodos');
        });
    }
}

    var btnLaddaSubmit = Ladda.create($('#btnSubmit')[0]);
    $('#frmModal').validate({
        ignore: '',
        rules: {
            cboSolicitante:  {required: generador},
            txtFechaIngreso: {required: true},
            cboCondicion:    {required: true},
            txtCantidadDias: {required: true},
            txtFechaInicio: {required: true},
            txtFechaFin: {required: true}
        },
        messages: {
            cboSolicitante :  { required : 'Seleccione un solicitante.'},
            txtFechaIngreso : { required : 'Fecha de Ingreso invalido.'},
            cboCondicion:     { required : 'Seleccione una condición.'},
            txtCantidadDias:  { required : 'Seleccione un rango de días valido.'},
            txtFechaInicio:  { required : 'Ingrese la fecha de inicio.'},
            txtFechaFin:  { required : 'Ingrese la fecha de fin.'}
        },
        invalidHandler: function(e, validator) {
            var mensaje = validator.errorList[0].message;
        },
        submitHandler: function(e) {
            btnLaddaSubmit.start();
            var options = {
                beforeSubmit: function(arr, $form, options) {
                    var inicio = moment($("#txtFechaInicio").val(),'DD/MM/YYYY');
                    var fin = moment($("#txtFechaFin").val(),'DD/MM/YYYY');
                    arr.forEach(element => {
                        if(element.name == "txtFechaInicio"){
                            element.value = inicio.format('YYYY-MM-DD')
                        }

                        if(element.name == "txtFechaFin"){
                            element.value = fin.format('YYYY-MM-DD')
                        }
                    });

                    var regConsolidado = tblConsolidado.row(0).data();
                    var modalidad = ($('#cboSolicitante').attr('type') == 'hidden')?1:3;
                    arr.push({name: 'tblConsolidado', value : JSON.stringify(regConsolidado)});
                    arr.push({name: 'modalidad', value : modalidad});
                    return arr;                 
                },
                beforeSend: function(e) {
                    //VALIDACION PREVIA
                    status = validarUsoDeVacaciones(uso_vacaciones);
                    if(status == 'false'){
                        e.abort();
                        btnLaddaSubmit.stop();
                        return false;
                    }

                    var inicio = moment($("#txtFechaInicio").val(),'DD/MM/YYYY');
                    var fin = moment($("#txtFechaFin").val(),'DD/MM/YYYY');
                    status = validarTiempo(inicio,fin);
                    if(status == 'false'){
                        e.abort();
                        btnLaddaSubmit.stop();
                        return false;
                    }
                },
                success: function(data) {
                    btnLaddaSubmit.stop();
                    if (data.Result == 'OK') {
                        showConfirmSuccess('Se proceso correctamente la solicitud de vacaciones');
                        $('#modalForm').bootstrapModal('hide');
                        $('#vacacionesContainer').jtable('reload');
                    }else{
                        showConfirmError(data.Message);
                    }
                },
                error: function() {
                    showConfirmError('Ocurrió un error interno');
                    btnLaddaSubmit.stop();
                },
                complete: function() {
                    btnLaddaSubmit.stop();
                },
                dataType: 'json'
            };
            $('#frmModal').ajaxSubmit(options);
        }
    });
}

function cambiarFechas(uso_vacaciones){
    if($("#txtFechaInicio").val()=="" || $("#txtFechaFin").val()==""){
        return false;
    }

    start = moment($("#txtFechaInicio").val(),'DD/MM/YYYY');
    end = moment($("#txtFechaFin").val(),'DD/MM/YYYY');

    var numDias = end.diff(start,'days') + 1;
    $('#txtCantidadDias').val(numDias);

    status = validarUsoDeVacaciones(uso_vacaciones);
    if(status == 'false'){
        return false;
    }

    loading(true,'Validando fechas');
    $.post($getAppName+'/validarFechas/', { fechaInicio: start.format('YYYY-MM-DD'), fechaFin: end.format('YYYY-MM-DD') }, function(data) {
        loading(false);
        if (data.Result === 'OK') {
            validarTiempo(start,end);
        } else {
            showConfirmWarning(data.Message);
        }
    }, 'json')
    .fail(function() {
        loading(false);
        showConfirmError('Ocurrió un Error interno');
        //ev.preventDefault();
        return false;
    });
}

function loading(mostrar,mensaje){
    if(mostrar){
        $('body').loadingModal({
            position: 'auto',
            text: mensaje,
            color: '#fff',
            opacity: '0.7',
            backgroundColor: 'rgb(0,0,0)',
            animation: 'wave'
        });
    }else{
        $('body').loadingModal('hide');
        setTimeout(function(){ 
            $('body').loadingModal('destroy');
        }, 2000);
    }
}

function validarTiempo(fechaInicio,fechaFin){
    var condicion = $('#cboCondicion').val();
    var numDias = fechaFin.diff(fechaInicio,'days') + 1;
    var regConsolidado = tblConsolidado.row(0).data();
    var disponible = 0;

    $('#txtCantidadDias').val(numDias);

    if(regConsolidado){
        if(condicion == '1'){
            disponible = (regConsolidado.ganado + regConsolidado.vencido) - regConsolidado.programado; //uso vacaciones
        }else{
            disponible = regConsolidado.trunco - regConsolidado.programado; //uso a cuenta de vacaciones
        }
    }

    if(numDias > disponible){
        showConfirmWarning('No puede exceder los días que tiene ganado: '+disponible+' días');
        return false;
    }
    return true;
}

function validarUsoDeVacaciones(usoDeVacaciones){
    var condicion = $('#cboCondicion').val();
    if(condicion == '2' && usoDeVacaciones > 0){
        showConfirmWarning('Primero debe consumir sus vacaciones ganadas, antes de usar las vacaciones truncas');
        return false;
    }

    return true;
}