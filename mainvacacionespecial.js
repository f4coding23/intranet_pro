// Variable global para controlar el proceso de autocompletado
let autoCompletando = false;
let ajaxRequests = {};
let generador = false;

// Función para mostrar/ocultar indicador de carga
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

// Función para realizar peticiones AJAX de manera más eficiente
function fetchData(url, params, targetElement, callback) {
  // Cancelar petición existente si hay alguna en progreso
  const requestKey = url + JSON.stringify(params);
  if (ajaxRequests[requestKey] && ajaxRequests[requestKey].readyState !== 4) {
    ajaxRequests[requestKey].abort();
  }

  // Si no hay parámetros válidos, no hacer la petición
  if (params && Object.values(params).some(val => val !== "")) {
    ajaxRequests[requestKey] = $.post(
      url,
      params,
      function (data) {
        if (data.Result === "OK") {
          let options = '<option value=""> Todas </option>';
          if (data.Options && Array.isArray(data.Options)) {
            data.Options.forEach((el) => {
              options += `<option value="${el.Value}">${el.DisplayText}</option>`;
            });
          }
          
          if (targetElement && targetElement.length > 0) {
            targetElement.html(options);
          }

          if (callback && typeof callback === 'function') callback(data);
        } else {
          showConfirmWarning(data.Message || 'No se pudo obtener la información');
        }
      },
      "json"
    ).fail(function (xhr, status, error) {
      console.error("Error en fetchData:", status, error);
      showConfirmError("Ocurrió un Error interno");
    });
  }
}

$(function () {
    // Inicializar selectores de fecha
    $("#qryFechaInicio, #qryFechaFin").datetimepicker({
        locale: "es",
        format: "DD/MM/YYYY",
        allowInputToggle: false,
        ignoreReadonly: true,
        showTodayButton: true,
        showClose: true,
        useCurrent: false
    });

    // Configurar dependencias entre fechas (inicio/fin)
    $("#qryFechaInicio").on("dp.change", function (e) {
        if ($("#qryFechaFin").data("DateTimePicker")) {
            $("#qryFechaFin").data("DateTimePicker").minDate(e.date);
        }
    });

    $("#qryFechaFin").on("dp.change", function (e) {
        if ($("#qryFechaInicio").data("DateTimePicker")) {
            $("#qryFechaInicio").data("DateTimePicker").maxDate(e.date);
        }
    });

    /* ========================================
                CARGAR CBX GERENCIAS 
    ======================================== */
    $("#qry_empresa").change(function () {
        const $gerencia = $("#qry_gerencia");
        const empresaId = $(this).val();

        fetchData(
            "directorio/getGerencias/",
            { qry_empresa: empresaId },
            $gerencia
        );
    });

    /* ========================================
                CARGAR CBX DEPARTAMENTOS 
    ======================================== */
    $("#qry_gerencia").change(function () {
        const $departamento = $("#qry_departamento");
        const gerenciaId = $(this).val();
        const empresaId = $("#qry_empresa").val();

        $("#qry_departamento").html('<option value=""> Todas </option>');
        $("#qry_area").html('<option value=""> Todas </option>');
        $("#qry_seccion").html('<option value=""> Todas </option>');
        
        if (!autoCompletando && $("#qry_colaborador").length > 0) {
            try {
                $("#qry_colaborador").selectpicker('val', '');
                $("#qry_colaborador").selectpicker('refresh');
            } catch (e) {
                console.warn("Error al reiniciar colaborador:", e);
            }
        }

        fetchData(
            "directorio/getDepartamentos/",
            { qry_empresa: empresaId, qry_gerencia: gerenciaId },
            $departamento
        );
    });

    /* ========================================
                CARGAR CBX AREAS 
    ======================================== */
    $("#qry_departamento").change(function () {
        const $area = $("#qry_area");
        const departamentoId = $(this).val();
        const gerenciaId = $("#qry_gerencia").val();
        const empresaId = $("#qry_empresa").val();

        $("#qry_area").html('<option value=""> Todas </option>');
        $("#qry_seccion").html('<option value=""> Todas </option>');
        
        if (!autoCompletando && $("#qry_colaborador").length > 0) {
            try {
                $("#qry_colaborador").selectpicker('val', '');
                $("#qry_colaborador").selectpicker('refresh');
            } catch (e) {
                console.warn("Error al reiniciar colaborador:", e);
            }
        }

        fetchData(
            "directorio/getAreas/",
            {
                qry_empresa: empresaId,
                qry_gerencia: gerenciaId,
                qry_departamento: departamentoId
            },
            $area
        );
    });

    /* ========================================
                CARGAR CBX SECCIONES 
    ======================================== */
    $("#qry_area").change(function () {
        const $seccion = $("#qry_seccion");
        const areaId = $(this).val();
        const departamentoId = $("#qry_departamento").val();
        const gerenciaId = $("#qry_gerencia").val();
        const empresaId = $("#qry_empresa").val();

        $("#qry_seccion").html('<option value=""> Todas </option>');
        
        if (!autoCompletando && $("#qry_colaborador").length > 0) {
            try {
                $("#qry_colaborador").selectpicker('val', '');
                $("#qry_colaborador").selectpicker('refresh');
            } catch (e) {
                console.warn("Error al reiniciar colaborador:", e);
            }
        }

        fetchData(  
            "directorio/getSecciones/",
            {
                qry_empresa: empresaId,
                qry_gerencia: gerenciaId,
                qry_departamento: departamentoId,
                qry_area: areaId
            },
            $seccion
        );
    });

    

    /* ========================================
        TABLA DE REGISTRO DE FECHAS ESPECIALES
    ======================================== */
    $("#vacacionesespecialesContainer").jtable({
        title: 'Listado de Registros de Fechas Especiales',
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
                text: 'Crear registro de fecha especial',
                click: function () {
                    loading(true,'Obteniendo información');

                    $.post($getAppName+'/indexCrear/', {}, function(data) {
                        loading(false);
                        if (data.Result === 'OK') {
                            generador = data.generador;
                            //Armar el Template
                            var template = $('#tplFrmModal').html();
                            Mustache.parse(template);

                            var optionsRender = {
                                action: $getAppName+'/crear',
                                generador: data.generador,
                                val_button: 'Generar Solicitud de vacaciones',
                                edit: false,
                                listCondicion: data.cboCondicion
                            };

                            if(!data.generador){
                                optionsRender.val_empresa = data.empresa;
                                optionsRender.val_gerencia = data.gerencia;
                                optionsRender.val_area = data.area;
                                optionsRender.val_id_solicitante = data.id_solicitante;
                                optionsRender.val_fecha_ingreso = moment(data.fecha_ingreso.date).format('L');
                            }

                            var rendered = Mustache.render(template, optionsRender);
                            $('#modalForm .modal-dialog .modal-content').html(rendered);
                            $('#modalForm .modal-dialog').draggable({handle: ".modal-header"});
                            setFunctionFormulario();
                            $('#modalForm').bootstrapModal({
                                show: true,
                                backdrop: false
                            });
                        } else {
                            showConfirmWarning(data.Message || 'No se pudo crear el registro');
                        }
                    }, 'json')
                    .fail(function(xhr, status, error) {
                        console.error("Error en indexCrear:", status, error);
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
            id_vaca_especial: {
                title: 'ID',
                width: '1%',
                list: true,
                key: true
            },
            fecha_inicio: {
                title: 'Fec. Inicio',
                display: function(data){
                    try {
                        return moment(data.record.fecha_inicio.date).format('DD/MM/YYYY');
                    } catch(e) {
                        console.warn("Error formateando fecha:", e);
                        return '';
                    }
                },
                width: '5%'
            },
            fecha_fin: {
                title: 'Fec. Fin',
                display: function(data){
                    try {
                        return moment(data.record.fecha_fin.date).format('DD/MM/YYYY');
                    } catch(e) {
                        console.warn("Error formateando fecha:", e);
                        return '';
                    }
                },
                width: '5%'
            },
            empresa: {
                title: 'Empresa',
                width: '5%'
            },
            gerencia: {
                title: 'Gerencia',
                width: '12%'
            },
            departamento: {
                title: 'Departamento',
                width: '12%'
            },
            area: {
                title: 'Área',
                width: '12%'
            },
            seccion: {
                title: 'Sección',
                width: '12%'
            },
            id_solicitante: {
                title: 'id_solicitante',
                width: '5%',
                list: false
            },
            solicitante: {
                title: 'Solicitante',
                width: '15%'
            },
            id_generador: {
                title: 'id_Solicitante',
                width: '5%',
                list: false
            },
            generador: {
                title: 'Registrador',
                width: '15%'
            },
            fecha_crea: {
                title: 'Fec. Solicitud',
                display: function(data){
                    try {
                        return moment(data.record.fecha_crea.date).format('DD/MM/YYYY HH:mm:ss');
                    } catch(e) {
                        console.warn("Error formateando fecha:", e);
                        return '';
                    }
                },
                width: '5%'
            },
            acciones: {
                title: 'Acciones',
                width: '1%',
                sorting: false,
                edit: false,
                create: false,
                display: function (data) {
                    var btnGroup = $('<div class="btn-group" role="group"></div>');
                    
                    try {
                        // Botón eliminar
                        var btnEliminar = $('<button data-style="slide-up" class="btn btn-ac btn-xs ladda-button" title="Eliminar Solicitud"><span class="ladda-label"><i class="glyphicon glyphicon-trash"></i></span></button>');
                        var btnLdEliminar = Ladda.create(btnEliminar[0]);
                        
                        btnEliminar.click(function () {
                            btnLdEliminar.start();
                            $.confirm({
                                theme: 'warning',
                                icon: 'fa fa-exclamation-triangle',
                                title: '¡Eliminar!',
                                content: '¿Esta seguro de eliminar el registro?, esta acción no podrá ser revertida.',
                                confirm: function () {
                                    $.post($getAppName+'/borrar/', {id_vacacion: data.record.id_vaca_especial}, function(response) {
                                        btnLdEliminar.stop();
                                        if (response.Result === 'OK') {
                                            $('#vacacionesespecialesContainer').jtable('reload');
                                        } else {
                                            showConfirmWarning(response.Message || 'No se pudo eliminar el registro');
                                        }
                                    }, 'json')
                                    .fail(function(xhr, status, error) {
                                        console.error("Error en borrar:", status, error);
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
                    } catch(e) {
                        console.warn("Error en display de acciones:", e);
                    }

                    return btnGroup;
                }
            }
        }
    });

    // Aplicar estilos a las cabeceras después de inicializar la tabla
    setTimeout(function() {
        $("#vacacionesespecialesContainer .jtable-column-header").css({
            "height": "50px",
            "vertical-align": "middle"
        });
        
        $("#vacacionesespecialesContainer .jtable-column-header-text").css({
            "position": "relative",
            "top": "50%",
            "transform": "translateY(-50%)"
        });
    }, 100);
    
    // Aplicar estilos después de cada recarga de datos
    $("#vacacionesespecialesContainer").bind("jtable.rowsRefreshed", function() {
        $("#vacacionesespecialesContainer .jtable-column-header").css({
            "height": "50px",
            "vertical-align": "middle"
        });
        
        $("#vacacionesespecialesContainer .jtable-column-header-text").css({
            "position": "relative",
            "top": "50%",
            "transform": "translateY(-50%)"
        });
    });

    // Configuración para el selectpicker con Ajax
    var optColaborador = {
        ajax: {
            url: $getAppName + '/buscarUsuario',
            type: 'POST',
            dataType: 'json',
            data: {
                q: '{{{q}}}'
            }
        },
        log: 0,
        minLength: 3,
        preserveSelected: false,
        locale: {
            emptyTitle: "Seleccione y comience a escribir",
            currentlySelected: "Seleccionado",
            searchPlaceholder: "Buscar...",
            statusSearching: "Buscando...",
            statusNoResults: "Sin Resultados",
            statusInitialized: "Empieza a escribir una consulta de búsqueda",
            statusTooShort: 'Introduzca más caracteres',
            errorText: "No se puede recuperar resultados",
        },
        preprocessData: function(data) {
            var i, l = data.length, array = [];
            if (l) {
                for (i = 0; i < l; i++) {
                    array.push($.extend(true, data[i], {
                        text: data[i].DisplayText,
                        value: data[i].id_solicitante,
                        data: {
                            subtext: data[i].DisplayText2
                        }
                    }));
                }
            }
            return array;
        }
    };
    
    // Inicializar el selectpicker con ajaxSelectPicker
    if ($("#qry_colaborador").length > 0) {
        try {
            $("#qry_colaborador").selectpicker().ajaxSelectPicker(optColaborador);
        } catch (e) {
            console.error("Error al inicializar selectpicker:", e);
        }
    }

    // Evento para cuando se selecciona un solicitante
    $("#qry_colaborador").on('changed.bs.select', function (e) {
        const selectedValue = $(this).val();
        
        if (selectedValue && selectedValue !== '') {
            // Guardar la selección actual
            const selectedId = selectedValue;
            const selectedText = $(this).find("option:selected").text();
            
            // Mostrar indicador de carga
            loading(true, 'Cargando información del solicitante');
            
            // Marcar que estamos en proceso de autocompletado
            autoCompletando = true;
            
            // Obtener los datos del solicitante
            $.post(
                $getAppName + '/getInfoSolicitante',
                { id_solicitante: selectedValue },
                function(response) {
                    if (response.Result === 'OK') {
                        const data = response.Data;
                        
                        // Actualizar el combo de empresa y encadenar las actualizaciones
                        if ($("#qry_empresa").length > 0) {
                            $("#qry_empresa").val(data.cod_empresa).trigger('change');
                            
                            // Encadenar actualizaciones con retrasos para permitir que se carguen los datos
                            setTimeout(function() {
                                if ($("#qry_gerencia").length > 0) {
                                    $("#qry_gerencia").val(data.cod_gerencia).trigger('change');
                                    
                                    setTimeout(function() {
                                        if ($("#qry_departamento").length > 0) {
                                            $("#qry_departamento").val(data.cod_departamento).trigger('change');
                                            
                                            setTimeout(function() {
                                                if ($("#qry_area").length > 0) {
                                                    $("#qry_area").val(data.cod_area).trigger('change');
                                                    
                                                    setTimeout(function() {
                                                        if ($("#qry_seccion").length > 0) {
                                                            $("#qry_seccion").val(data.cod_seccion);
                                                        }
                                                        
                                                        restaurarColaborador();
                                                    }, 300);
                                                } else {
                                                    restaurarColaborador();
                                                }
                                            }, 300);
                                        } else {
                                            restaurarColaborador();
                                        }
                                    }, 300);
                                } else {
                                    restaurarColaborador();
                                }
                            }, 300);
                        } else {
                            restaurarColaborador();
                        }
                    } else {
                        restaurarColaborador();
                        showConfirmWarning(response.Message || 'No se pudo obtener la información del solicitante');
                    }
                },
                'json'
            )
            .fail(function(xhr, status, error) {
                console.error("Error en getInfoSolicitante:", status, error);
                restaurarColaborador();
                showConfirmError('Ocurrió un Error al intentar obtener la información del solicitante');
            });
            
            // Función auxiliar para restaurar el colaborador seleccionado
            function restaurarColaborador() {
                if ($("#qry_colaborador").length > 0) {
                    try {
                        if (!$("#qry_colaborador option[value='" + selectedId + "']").length) {
                            $("#qry_colaborador").append(new Option(selectedText, selectedId, true, true));
                        }
                        $("#qry_colaborador").val(selectedId);
                        $("#qry_colaborador").selectpicker('refresh');
                    } catch (e) {
                        console.warn("Error al restaurar solicitante:", e);
                    }
                }
                
                autoCompletando = false;
                loading(false);
            }
        }
    });

    // // Manejador del botón de búsqueda
    $('#LoadRecordsButton').click(function (e) {
        e.preventDefault();
        
        // Convertir fechas al formato esperado por el servidor (si es necesario)
        let fechaInicio = $("#qryFechaInicio").val();
        let fechaFin = $("#qryFechaFin").val();
        
        // Opcional: convertir formato de fecha si es necesario
        // if (fechaInicio) fechaInicio = moment(fechaInicio, 'DD/MM/YYYY').format('YYYY-MM-DD');
        // if (fechaFin) fechaFin = moment(fechaFin, 'DD/MM/YYYY').format('YYYY-MM-DD');

        $('#vacacionesespecialesContainer').jtable('load', {
            qry_empresa: $('#qry_empresa').val(),
            qry_gerencia: $("#qry_gerencia").val(),
            qry_departamento: $("#qry_departamento").val(),
            qry_area: $("#qry_area").val(),
            qry_seccion: $("#qry_seccion").val(),
            qry_colaborador: $("#qry_colaborador").val(),
            qry_ini_rango: fechaInicio,
            qry_fin_rango: fechaFin
        });
    });

    // Botón para exportar (implementación básica)
    $('#btnExportar').click(function(e) {
        e.preventDefault();
        
        // Obtener los mismos parámetros que se usan para la búsqueda
        const params = {
            qry_empresa: $('#qry_empresa').val(),
            qry_gerencia: $("#qry_gerencia").val(),
            qry_departamento: $("#qry_departamento").val(),
            qry_area: $("#qry_area").val(),
            qry_seccion: $("#qry_seccion").val(),
            qry_colaborador: $("#qry_colaborador").val(),
            qry_ini_rango: $("#qryFechaInicio").val(),
            qry_fin_rango: $("#qryFechaFin").val(),
            export: 'excel' // Indicar que queremos exportar
        };
        
        // Construir URL con parámetros para la exportación
        const queryString = Object.keys(params)
            .filter(key => params[key]) // Filtrar parámetros vacíos
            .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
            .join('&');
            
        // Redirigir a la URL de exportación
        window.location.href = $getAppName + '/exportar/?' + queryString;
    });

    // Cargar datos iniciales
    $('#LoadRecordsButton').click();
    $("#qry_empresa").change();
});