var $ajaxEmpresa;
var $ajaxGerencia;
var $ajaxDepartamento;
var $ajaxArea;
var tblConsolidado;
var tbldetalleconsolidado;
var generador = false;
var tipocondicion = 0;

var pendientesPrevios = 0;
var programadosPrevios = 0;

var datosOriginales = null;

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
  locale: "es",
  format: "DD/MM/YYYY",
  allowInputToggle: false,
  ignoreReadonly: true,
  showTodayButton: true,
  showClose: true,
  useCurrent: false,
};

$(function () {
  /*var start = '';
    var end = '';*/

  $("#qryFechaInicio").datetimepicker(dateOptions);
  $("#qryFechaFin").datetimepicker(dateOptions);

  $("#qryFechaInicio").on("dp.change", function (e) {
    $("#qryFechaFin").data("DateTimePicker").minDate(e.date);
  });
  $("#qryFechaFin").on("dp.change", function (e) {
    $("#qryFechaInicio").data("DateTimePicker").maxDate(e.date);
  });

  $("#qry_empresa").change(function () {
    var $gerencia = $("#qry_gerencia");
    var $valor = $(this).val();
    //Cargar información del combo de áreas
    $gerencia.html('<option value=""> Seleccione </option>');
    $("#qry_area").html('<option value=""> Seleccione </option>');
    //$("#qry_seccion").html('<option value=""> Seleccione </option>');

    if ($ajaxEmpresa && $ajaxEmpresa.readyState != 4) {
      $ajaxEmpresa.abort();
    }

    if ($valor != "") {
      $ajaxEmpresa = $.post(
        "directorio/getGerencias/",
        { qry_empresa: $valor },
        function (data) {
          if (data.Result === "OK") {
            $.each(data.Options, function (index, el) {
              $gerencia.append(
                '<option value="' +
                  el.Value +
                  '">' +
                  el.DisplayText +
                  "</option>"
              );
            });
          } else {
            showConfirmWarning(data.Message);
          }
        },
        "json"
      );
    }
  });

  $("#qry_gerencia").change(function () {
    var $departamento = $("#qry_departamento");
    var $valor = $(this).val();
    $valorEmpresa = $("#qry_empresa").val();
    //Cargar información del combo de áreas
    $departamento.html('<option value=""> Seleccione </option>');
    $("#qry_area").html('<option value=""> Seleccione </option>');
    //$("#qry_seccion").html('<option value=""> Seleccione </option>');

    if ($ajaxGerencia && $ajaxGerencia.readyState != 4) {
      $ajaxGerencia.abort();
    }

    if ($valor != "") {
      $ajaxGerencia = $.post(
        "directorio/getDepartamentos/",
        { qry_empresa: $valorEmpresa, qry_gerencia: $valor },
        function (data) {
          if (data.Result === "OK") {
            $.each(data.Options, function (index, el) {
              $departamento.append(
                '<option value="' +
                  el.Value +
                  '">' +
                  el.DisplayText +
                  "</option>"
              );
            });
          } else {
            showConfirmWarning(data.Message);
          }
        },
        "json"
      );
    }
  });

  $("#qry_departamento").change(function () {
    var $area = $("#qry_area");
    var $valor = $(this).val();
    $valorEmpresa = $("#qry_empresa").val();
    $valorGerencia = $("#qry_gerencia").val();
    //Cargar información del combo de áreas
    $area.html('<option value=""> Seleccione </option>');
    $("#qrySeccion").html('<option value=""> Seleccione </option>');

    if ($ajaxDepartamento && $ajaxDepartamento.readyState != 4) {
      $ajaxDepartamento.abort();
    }

    if ($valor != "") {
      $ajaxDepartamento = $.post(
        "directorio/getAreas/",
        {
          qry_empresa: $valorEmpresa,
          qry_departamento: $valor,
          qry_gerencia: $valorGerencia,
        },
        function (data) {
          if (data.Result === "OK") {
            $.each(data.Options, function (index, el) {
              $area.append(
                '<option value="' +
                  el.Value +
                  '">' +
                  el.DisplayText +
                  "</option>"
              );
            });
          } else {
            showConfirmWarning(data.Message);
          }
        },
        "json"
      );
    }
  });

  $("#qry_area").change(function () {
    var $seccion = $("#qry_seccion");
    var $valor = $(this).val();
    $valorEmpresa = $("#qry_empresa").val();

    $valorDepartamento = $("#qry_departamento").val();
    //Cargar información del combo de áreas
    $seccion.html('<option value=""> Seleccione </option>');
    $("#qry_seccion").html('<option value=""> Seleccione </option>');

    if ($ajaxArea && $ajaxArea.readyState != 4) {
      $ajaxArea.abort();
    }

    if ($valor != 0) {
      $ajaxArea = $.post(
        "directorio/getSecciones/",
        {
          qry_empresa: $valorEmpresa,
          qry_departamento: $valorDepartamento,
          qry_area: $valor,
        },
        function (data) {
          if (data.Result === "OK") {
            $.each(data.Options, function (index, el) {
              $seccion.append(
                '<option value="' +
                  el.Value +
                  '">' +
                  el.DisplayText +
                  "</option>"
              );
            });
          } else {
            showConfirmWarning(data.Message);
          }
        },
        "json"
      );
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
    title: "Listado de Solicitudes de Vacaciones",
    paging: true,
    pageSize: 20,
    sorting: true,
    defaultSorting: "fecha_inicio DESC",
    saveUserPreferences: false,
    toolbar: {
      hoverAnimation: true,
      hoverAnimationDuration: 60,
      hoverAnimationEasing: undefined,
      items: [
        {
          icon: "assets/browser-components/jquery-jtable/themes/lightcolor/add.png",
          text: "Crear solicitud de vacaciones",
          click: function () {
            loading(true, "Obteniendo información");

            $.post(
              $getAppName + "/indexCrear/",
              {},
              function (data) {
                loading(false);
                if (data.Result === "OK") {
                  generador = data.generador;
                  //Armar el Template
                  var template = $("#tplFrmModal").html();
                  Mustache.parse(template); // optional, speeds up future uses

                  var optionsRender = {
                    action: $getAppName + "/crear",
                    generador: data.generador,
                    val_button: "Generar Solicitud de vacaciones",
                    edit: false,
                    listCondicion: data.cboCondicion,
                    //sel_motivo : function() { return (this.id_vaca_condicion == data.record.id_vaca_condicion) ? "selected":"";},
                  };

                  if (!data.generador) {
                    optionsRender.val_empresa = data.empresa;
                    optionsRender.val_gerencia = data.gerencia;
                    optionsRender.val_area = data.area;
                    optionsRender.val_id_solicitante = data.id_solicitante;
                    optionsRender.val_fecha_ingreso = moment(
                      data.fecha_ingreso.date
                    ).format("L");
                  }

                  var rendered = Mustache.render(template, optionsRender);
                  $("#modalForm .modal-dialog .modal-content").html(rendered);
                  $("#modalForm .modal-dialog").draggable({
                    handle: ".modal-header",
                  });
                  setFunctionFormulario();
                  $("#modalForm").bootstrapModal({
                    show: true,
                    backdrop: false,
                  });
                } else {
                  showConfirmWarning(data.Message);
                }
              },
              "json"
            ).fail(function () {
              loading(false);
              showConfirmError("Ocurrió un Error interno");
            });
          },
        },
      ],
    },
    actions: {
      listAction: $getAppName + "/listar/",
    },
    fields: {
      ver: {
        title: "",
        width: "2%",
        sorting: false,
        edit: false,
        create: false,
        display: function (data) {
          var $button = $(
            '<button class="btn btn-ac btn-xs" title="Aprobaciones"><i class="glyphicon glyphicon-user"></i></button>'
          );
          $button.click(function () {
            $("#vacacionesContainer").jtable(
              "openChildTable",
              $button.closest("tr"),
              {
                title: " Autorizaciones",
                actions: {
                  listAction:
                    $getAppName +
                    "/listarAutorizaciones/?id_vacacion=" +
                    data.record.id_vacacion,
                },
                fields: {
                  id_vacacion: {
                    type: "hidden",
                    defaultValue: data.record.id_vacacion,
                  },
                  id_vaca_aut: {
                    title: "idAutorizacion",
                    key: true,
                    list: false,
                  },
                  autorizador: {
                    title: "Autorizador",
                    width: "30%",
                  },
                  estado_aprobacion: {
                    title: "Estado",
                    width: "11%",
                  },
                  fecha_autorizacion: {
                    title: "Fecha Autorización",
                    display: function (data) {
                      if (data.record.fecha_autorizacion) {
                        return moment(
                          data.record.fecha_autorizacion.date
                        ).format("DD/MM/YYYY HH:mm");
                      } else {
                        return "";
                      }
                    },
                    width: "17%",
                  },
                  motivo_rechazo: {
                    title: "Comentario",
                    width: "25%",
                  },
                },
              },
              function (data) {
                //opened handler
                data.childTable.jtable("load");
              }
            );
          });
          return $button;
        },
      },
      id_vacacion: {
        title: "Id",
        width: "3%",
        list: true,
        key: true,
      },
      tipo: {
        title: "Tipo",
        width: "5%",
      },
      vaca_condicion: {
        title: "Condición",
        width: "13%",
      },
      solicitante: {
        title: "solicitante",
        width: "25%",
      },
      fecha_crea: {
        title: "Fch. Solicitud",
        display: function (data) {
          return moment(data.record.fecha_crea.date).format("DD/MM/YYYY");
        },
        width: "7%",
      },
      fecha_inicio: {
        title: "Fch. Inicio",
        display: function (data) {
          return moment(data.record.fecha_inicio.date).format("DD/MM/YYYY");
        },
        width: "7%",
      },
      fecha_fin: {
        title: "Fch. Fin",
        display: function (data) {
          return moment(data.record.fecha_fin.date).format("DD/MM/YYYY");
        },
        width: "7%",
      },
      num_dias: {
        title: "# Días",
        width: "5%",
      },
      vaca_estado: {
        title: "Estado",
        width: "10%",
      },
      acciones: {
        title: "Acciones",
        width: "8%",
        sorting: false,
        edit: false,
        create: false,
        display: function (data) {
          var btnGroup = $('<div class="btn-group" role="group"></div>');
          var estadosEdit = [1, 2, 3, 4];
          var estadosEliminar = [1, 2, 3];
          var estadosEliminarPropio = [1, 2, 3, 4];
          var dateInicio = moment(data.record.fecha_inicio.date);
          var diferenciaDias = dateInicio.diff(moment().startOf("day"), "days");
          //editar
          if (
            estadosEdit.includes(data.record.id_vaca_estado) &&
            diferenciaDias > maxDayToEdit &&
            data.record.own &&
            data.record.id_vaca_condicion != 3 // No se pueden editar las vacaciones coordinadas
          ) {
            btnEditar = $(
              '<button class="btn btn-ac btn-xs" title="Editar"><i class="fa fa-pencil"></i></button>'
            );
            btnEditar.click(function () {
              if (data.record.id_vaca_condicion == 3) {
                showConfirmWarning(
                  "No se puede editar solicitudes con esta condición"
                );
                return;
              }
              loading(true, "Obteniendo información");

              $.post(
                $getAppName + "/indexCrear/",
                { idSolicitante: data.record.id_solicitante },
                function (result) {
                  loading(false);
                  console.log(result);

                  var template = $("#tplFrmModal").html();
                  Mustache.parse(template); // optional, speeds up future uses
                  var optionsRender = {
                    action: $getAppName + "/editar",
                    val_button: "modificar solicitud de vacaciones",
                    generador: result.generador,
                    edit: true,
                    val_id_vacacion: data.record.id_vacacion,
                    // readonly_condicion: "disabled",
                    listCondicion: result.cboCondicion,
                    sel_condicion: function () {
                      return this.id_vaca_condicion ==
                        data.record.id_vaca_condicion
                        ? "selected"
                        : "";
                    },
                    val_empresa: data.record.empresa,
                    val_gerencia: data.record.gerencia,
                    val_area: data.record.area,
                    val_id_solicitante: data.record.id_solicitante,
                    val_nombre_solicitante: data.record.solicitante,
                    val_fecha_ingreso: moment(
                      result.fecha_ingreso.date,
                      ""
                    ).format("L"),
                    val_fecha_inicio: moment(
                      data.record.fecha_inicio.date
                    ).format("L"),
                    val_fecha_fin: moment(data.record.fecha_fin.date).format(
                      "L"
                    ),
                    val_dias: data.record.num_dias,
                  };

                  var rendered = Mustache.render(template, optionsRender);
                  $("#modalForm .modal-dialog .modal-content").html(rendered);
                  $("#modalForm .modal-dialog").draggable({
                    handle: ".modal-header",
                  });
                  setFunctionFormulario(
                    true,
                    data.record.fecha_inicio,
                    data.record.fecha_fin
                  );
                  $("#modalForm").bootstrapModal({
                    show: true,
                    backdrop: false,
                  });
                },
                "json"
              ).fail(function () {
                loading(false);
                showConfirmError("Ocurrió un Error interno");
              });
            });
            btnGroup.append(btnEditar);
          }

          //Confirmar Solicitud
          if (data.record.id_vaca_estado == "1" /*&& data.record.propio*/) {
            btnConfirmar = $(
              '<button class="btn btn-ac btn-xs"><i class="glyphicon glyphicon-ok" title="Confirmar Solicitud"></i></button>'
            );
            btnConfirmar.click(function () {
              $.post(
                $getAppName + "/confirmarSolicitud/",
                { id_vacacion: data.record.id_vacacion },
                function (data) {
                  if (data.Result === "OK") {
                    showConfirmSuccess(
                      "Se confirmó correctamente la solicitud de vacaciones"
                    );
                    $("#vacacionesContainer").jtable("reload");
                  } else {
                    showConfirmWarning(data.Message);
                  }
                },
                "json"
              ).fail(function () {
                showConfirmError(
                  "Ocurrió un Error al intentar comunicarse con el servidor"
                );
              });
            });
            btnGroup.append(btnConfirmar);
          }

          //eliminar
          if (
            (estadosEliminar.includes(data.record.id_vaca_estado) &&
              data.record.own) ||
            (estadosEliminarPropio.includes(data.record.id_vaca_estado) &&
              data.record.propio)
          ) {
            btnEliminar = $(
              '<button data-style="slide-up" class="btn btn-ac btn-xs ladda-button" title="Eliminar Solicitud"><span class="ladda-label"><i class="glyphicon glyphicon-trash"></i></span></button>'
            );
            var btnLdEliminar = Ladda.create(btnEliminar[0]);
            btnEliminar.click(function () {
              btnLdEliminar.start();
              $.confirm({
                theme: "warning",
                icon: "fa fa-exclamation-triangle",
                title: "¡Eliminar!",
                content:
                  "¿Esta seguro de eliminar el registro?, esta acción no podrá ser revertida.",
                confirm: function () {
                  $.post(
                    $getAppName + "/borrar/",
                    { id_vacacion: data.record.id_vacacion },
                    function (data) {
                      btnLdEliminar.stop();
                      if (data.Result === "OK") {
                        $("#vacacionesContainer").jtable("reload");
                      } else {
                        showConfirmWarning(data.Message);
                      }
                    },
                    "json"
                  ).fail(function () {
                    btnLdEliminar.stop();
                    showConfirmError(
                      "Ocurrió un Error al intentar comunicarse con el servidor"
                    );
                  });
                },
                cancel: function () {
                  btnLdEliminar.stop();
                },
              });
            });
            btnGroup.append(btnEliminar);
          }

          //Confirmar Vacacion tomada
          if (data.record.id_vaca_estado == "4" && data.record.own) {
            btnConfirmVaca = $(
              '<button data-style="slide-up" class="btn btn-ac btn-xs ladda-button" title="Confirmar Vacaciones"><span class="ladda-label"><i class="fa fa-calendar-check-o"></i></span></button>'
            );
            var btnLdConfirmar = Ladda.create(btnConfirmVaca[0]);
            btnConfirmVaca.click(function () {
              btnLdConfirmar.start();
              $.confirm({
                theme: "warning",
                icon: "fa fa-exclamation-triangle",
                title: "¡Confirmar!",
                content:
                  "¿Esta seguro de confirmar la ejecución de las vacaciones?",
                confirm: function () {
                  $.post(
                    $getAppName + "/confirmarEjecucion/",
                    { id_vacacion: data.record.id_vacacion },
                    function (data) {
                      btnLdConfirmar.stop();
                      if (data.Result === "OK") {
                        $("#vacacionesContainer").jtable("reload");
                      } else {
                        showConfirmWarning(data.Message);
                      }
                    },
                    "json"
                  ).fail(function () {
                    btnLdConfirmar.stop();
                    showConfirmError(
                      "Ocurrió un Error al intentar comunicarse con el servidor"
                    );
                  });
                },
                cancel: function () {
                  btnLdConfirmar.stop();
                },
              });
            });
            btnGroup.append(btnConfirmVaca);
          }

          //Formato impreso
          if (
            data.record.id_vaca_estado == "4" ||
            data.record.id_vaca_estado == "5"
          ) {
            btnBoleta = $(
              '<button class="btn btn-ac btn-xs" title="Boleta"><i class="fa fa-file-text"></i></button>'
            );
            btnBoleta.click(function () {
              open(
                "POST",
                $getAppName + "/exportarBoleta/",
                {
                  qryIdVacacion: data.record.id_vacacion,
                },
                "_blank"
              );
            });
            btnGroup.append(btnBoleta);
          }

          return btnGroup;
        },
      },
    },
  });

  $("#LoadRecordsButton").click(function (e) {
    e.preventDefault();
    /*var qryFechaInicio = start?start.format('YYYY-MM-DD'):'';
        var qryFechaFin = end?end.format('YYYY-MM-DD'):'';*/

    $("#vacacionesContainer").jtable("load", {
      qry_empresa: $("#qry_empresa").val(),
      qry_gerencia: $("#qry_gerencia").val(),
      qry_departamento: $("#qry_departamento").val(),
      qry_area: $("#qry_area").val(),
      qry_seccion: $("#qry_seccion").val(),
      qry_colaborador: $("#qry_colaborador").val(),
      qry_ini_rango: $("#qryFechaInicio").val(),
      qry_fin_rango: $("#qryFechaFin").val(),
    });
  });

  $("#btnExportar").click(function (e) {
    /*var qryFechaInicio = start?start.format('YYYY-MM-DD'):'';
        var qryFechaFin = end?end.format('YYYY-MM-DD'):'';*/

    open(
      "POST",
      $getAppName + "/exportar/",
      {
        qry_empresa: $("#qry_empresa").val(),
        qry_gerencia: $("#qry_gerencia").val(),
        qry_departamento: $("#qry_departamento").val(),
        qry_area: $("#qry_area").val(),
        qry_seccion: $("#qry_seccion").val(),
        qry_colaborador: $("#qry_colaborador").val(),
        qry_ini_rango: $("#qryFechaInicio").val(),
        qry_fin_rango: $("#qryFechaFin").val(),
      },
      "_blank"
    );
  });

  $("#LoadRecordsButton").click();
  $("#qry_empresa").change();
});

function setFunctionFormulario(editar, fechaInicio, fechaFin) {
  editar = editar || false;
  var uso_vacaciones = 0;

  $("#divFechaInicio").datetimepicker(dateOptions);
  $("#divFechaFin").datetimepicker(dateOptions);

  if (editar) {
    $("#divFechaInicio").data("DateTimePicker").minDate(moment().format("L"));
    $("#divFechaInicio")
      .data("DateTimePicker")
      .maxDate(moment(fechaFin.date).format("L"));
    $("#divFechaFin")
      .data("DateTimePicker")
      .minDate(moment(fechaInicio.date).format("L"));
  } else {
    var optMinDate = moment().format("L");
    $("#divFechaInicio").data("DateTimePicker").minDate(optMinDate);
    $("#divFechaFin").data("DateTimePicker").minDate(optMinDate);
  }

  var maxFecha = moment().endOf("year").add(2, "years");
  $("#divFechaFin").data("DateTimePicker").maxDate(maxFecha);

  $("#divFechaInicio").on("dp.change", function (e) {
    $("#divFechaFin").data("DateTimePicker").minDate(e.date);
  });

  $("#divFechaFin").on("dp.change", function (e) {
    $("#divFechaInicio").data("DateTimePicker").maxDate(e.date);
  });

  $("#cboCondicion").change(function () {
    if (
      $("#cboSolicitante").val() &&
      $("#txtFechaInicio").val() &&
      $("#txtFechaFin").val()
    ) {
      var inicio = moment($("#txtFechaInicio").val(), "DD/MM/YYYY");
      var fin = moment($("#txtFechaFin").val(), "DD/MM/YYYY");
      validarTiempo(inicio, fin);
    }
  });

  /*if(editar){
        var start = moment(fechaInicio.date);
        var end = moment(fechaFin.date);
    }else{
        var start = '';
        var end = '';
    }*/

  /*********************************************** FUNCIONES PARA LAS FECHAS **********************************************/
  /*var optionDateRange = {
        locale: localeDate,
        autoUpdateInput: false,
        format: "DD/MM/YYYY",
        alwaysShowCalendars: true,
        showDropdowns: true,
        minDate: moment(),
        maxYear: parseInt(moment().endOf('year').format('YYYY'))+2
    }

    if(editar){
        optionDateRange.startDate = start;
        optionDateRange.endDate = end;
    }

    $('#txtRango').daterangepicker(optionDateRange);

    if(editar){
        $('#txtRango span').html('<b>Desde:</b> '+start.format('L')+' <b>Hasta:</b> '+end.format('L'));
    }

    $('#txtRango').on('show.daterangepicker', function(ev, picker) {
        if($('#cboCondicion').val() == '0'){
            ev.preventDefault();
            showConfirmWarning('Seleccione una condición');
            return false;
        }
    });

    $('#txtRango').on('apply.daterangepicker', function(ev, picker) {
        $('#txtRango span').html('<b>Desde:</b> '+picker.startDate.format('L')+' <b>Hasta:</b> '+picker.endDate.format('L'));
        start = picker.startDate;
        end = picker.endDate;

        var numDias = end.diff(start,'days') + 1;
        $('#txtCantidadDias').val(numDias);

        status = validarUsoDeVacaciones(uso_vacaciones);
        if(status == 'false'){
            return false;
        }

        loading(true,'Validando fechas');
        $.post($getAppName+'/validarFechas/', { fechaInicio: picker.startDate.format('YYYY-MM-DD'), fechaFin: picker.endDate.format('YYYY-MM-DD') }, function(data) {
            loading(false);
            if (data.Result === 'OK') {
                validarTiempo(picker.startDate,picker.endDate);
            } else {
                showConfirmWarning(data.Message);
            }
        }, 'json')
        .fail(function() {
            loading(false);
            showConfirmError('Ocurrió un Error interno');
            ev.preventDefault();
            return false;
        });
    });*/

  $("#divFechaInicio").on("dp.show", function (e) {
    if ($("#cboCondicion").val() == "0") {
      e.preventDefault();
      showConfirmWarning("Seleccione una condición");
      return false;
    }
  });

  $("#divFechaFin").on("dp.show", function (e) {
    if ($("#cboCondicion").val() == "0") {
      e.preventDefault();
      showConfirmWarning("Seleccione una condición");
      return false;
    }
  });

  $("#divFechaInicio").on("dp.change", function (e) {
    cambiarFechas(uso_vacaciones);
  });

  $("#divFechaFin").on("dp.change", function (e) {
    cambiarFechas(uso_vacaciones);
  });

  $("#cboCondicion").change(function () {
    if ($("#cboSolicitante").val()) {
      tblConsolidado.ajax.reload();
      tbldetalleconsolidado.ajax.reload();
    }
  });

  /*********************************************** TABLA DE VACACIONES CONSOLIDADA *********************************************/
  tblConsolidado = $("#tblConsolidado").DataTable({
    searching: false,
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: true,
    lengthChange: false,
    deferLoading: 0, //Deshabilitar el ajax automatico
    sorting: false,
    info: false,
    paging: false,
    ordering: false,
    ajax: {
      url: $getAppName + "/listarConsolidado/",
      type: "POST",
      data: function () {
        var data = {
          idSolicitante: $("#cboSolicitante").val(),
          idCondicion: $("#cboCondicion").val(),
          modificacion: editar,
          idSolicitud: $("#idSolicitud").val(),
        };
        return data;
      },
    },
    columns: [
      { data: "trunco", title: "Truncas" },
      { data: "ganado", title: "Pendientes" },
      { data: "vencido", title: "Vencidas" },
      { data: "programado", title: "Programadas" },
      {
        data: "por_programar",
        title: "Por Programar",
        render: function (data, type, row, meta) {
          if (typeof row.por_programar === "object") {
            return (
              "Habiles: <b>" +
              row.por_programar.habil +
              "</b> <br> No Hábiles: <b>" +
              row.por_programar.no_habil +
              "</b>"
            );
          } else {
            return row.por_programar;
          }
        },
      },
    ],
  });

  tblConsolidado.on("xhr", function (e, settings, json) {
    if (json.data.length > 0) {
      var datosIniciales = json.data[0]; // Captura la primera fila

      // Guardar los datos originales
      if (datosOriginales === null) {
        datosOriginales = {
          trunco: parseFloat(datosIniciales.trunco) || 0,
          ganado: parseFloat(datosIniciales.ganado) || 0,
          vencido: parseFloat(datosIniciales.vencido) || 0,
          programado: parseFloat(datosIniciales.programado) || 0,
          por_programar: parseFloat(datosIniciales.por_programar) || 0
        };
        console.log("Datos originales guardados:", datosOriginales);
      }

      var truncas = parseFloat(datosIniciales.trunco) || 0;
      var pendientes = parseFloat(datosIniciales.ganado) || 0;
      var vencidas = parseFloat(datosIniciales.vencido) || 0;
      var programadas = parseFloat(datosIniciales.programado) || 0;

      if (programadosPrevios == null || programadosPrevios == 0) {
        programadosPrevios = programadas;
        $("#programadosPrevios").val(programadosPrevios);
      } else {
        console.debug("programadosPrevios ya tiene un valor:", programadosPrevios);
      }
    }
  });

  tbldetalleconsolidado = $("#tbldetalleconsolidado").DataTable({
    searching: false,
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: true,
    lengthChange: false,
    deferLoading: 0, //Deshabilitar el ajax automatico
    sorting: false,
    info: false,
    paging: false,
    ordering: false,
    ajax: {
      url: $getAppName + "/listarConsolidadoDetalle/",
      type: "POST",
      data: function () {
        var data = {
          idSolicitante: $("#cboSolicitante").val(),
          idemprealidad: $("#qry_empresa").val(),
          idCondicion: $("#cboCondicion").val(),
          modificacion: editar,
          idSolicitud: $("#idSolicitud").val(),
        };
        return data;
      },
    },
    columns: [
      { data: "PE_VACA", title: "PERIODO" },
      { data: "GANADAS", title: "PENDIENTES" },
      { data: "GOZADAS", title: "GOZADAS" },
      { data: "TRUNCAS", title: "TRUNCAS" },
      { data: "SALDO", title: "SALDO" },
      { data: "ESTADO", title: "ESTADO" },
    ],
  });

  /*********************************************** FUNCIONES PARA LISTADO DE GENERADORES **********************************************/
  if ($("#cboSolicitante").attr("type") == "hidden") {
    tblConsolidado.ajax.reload();
    tbldetalleconsolidado.ajax.reload();
    //Obtener la cantidad de dias disponibles por "Uso de vacaciones"
    $.post(
      $getAppName + "/listarConsolidado/",
      {
        idSolicitante: $("#cboSolicitante").val(),
        idCondicion: 1,
        idSolicitud: $("#idSolicitud").val(),
      },
      function (data) {
        uso_vacaciones = data.data[0].por_programar;
      },
      "json"
    ).fail(function () {
      showConfirmError(
        'Ocurrió un Error al consultar la disponibilidad de "Uso de vacaciones"'
      );
    });
  } else {
    var opt = {
      ajax: {
        url: $getAppName + "/buscarSolicitante",
        type: "POST",
        dataType: "json",
        data: {
          q: "{{{q}}}",
        },
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
        statusSearching: "Buscando...",
        statusTooShort: "Introduzca más caracteres",
        errorText: "No se puede recuperar resultados",
      },
      preprocessData: function (data) {
        var i,
          l = data.length,
          array = [];
        if (l) {
          for (i = 0; i < l; i++) {
            array.push(
              $.extend(true, data[i], {
                text: data[i].DisplayText,
                value: data[i].Value,
                data: {
                  subtext: data[i].DisplayText2,
                  id_solicitante: data[i].Value,
                  fecha_ingreso: moment(data[i].fechaIngreso.date).format("L"),
                },
              })
            );
          }
        }
        // You must always return a valid array when processing data. The
        // data argument passed is a clone and cannot be modified directly.
        return array;
      },
    };

    $("#cboSolicitante")
      .selectpicker()
      .filter(".with-ajax")
      .ajaxSelectPicker(opt);

    $("#cboSolicitante").on(
      "changed.bs.select",
      function (e, clickedIndex, isSelected, previousValue) {
        var option = $("#cboSolicitante option")[clickedIndex];
        var data = $(option).data();
        var fechaIngreso = data.fecha_ingreso;
        $("#txtFechaIngreso").val(fechaIngreso);
        tblConsolidado.ajax.reload();
        tbldetalleconsolidado.ajax.reload();
        //Obtener la cantidad de dias disponibles por "Uso de vacaciones"
        $.post(
          $getAppName + "/listarConsolidado/",
          {
            idSolicitante: data.id_solicitante,
            idCondicion: 1,
            idSolicitud: $("#idSolicitud").val(),
          },
          function (data) {
            uso_vacaciones = data.data[0].por_programar;
          },
          "json"
        ).fail(function () {
          showConfirmError(
            'Ocurrió un Error al consultar la disponibilidad de "Uso de vacaciones"'
          );
        });
      }
    );
  }

  var btnLaddaSubmit = Ladda.create($("#btnSubmit")[0]);
  $("#frmModal").validate({
    ignore: "",
    rules: {
      cboSolicitante: { required: generador },
      txtFechaIngreso: { required: true },
      cboCondicion: { required: true },
      txtCantidadDias: { required: true },
      txtFechaInicio: { required: true },
      txtFechaFin: { required: true },
    },
    messages: {
      cboSolicitante: { required: "Seleccione un solicitante." },
      txtFechaIngreso: { required: "Fecha de Ingreso invalido." },
      cboCondicion: { required: "Seleccione una condición." },
      txtCantidadDias: { required: "Seleccione un rango de días valido." },
      txtFechaInicio: { required: "Ingrese la fecha de inicio." },
      txtFechaFin: { required: "Ingrese la fecha de fin." },
    },
    invalidHandler: function (e, validator) {
      var mensaje = validator.errorList[0].message;
    },
    submitHandler: function (e) {
      btnLaddaSubmit.start();
      var options = {
        beforeSubmit: function (arr, $form, options) {
          var inicio = moment($("#txtFechaInicio").val(), "DD/MM/YYYY");
          var fin = moment($("#txtFechaFin").val(), "DD/MM/YYYY");
          arr.forEach((element) => {
            if (element.name == "txtFechaInicio") {
              element.value = inicio.format("YYYY-MM-DD");
            }

            if (element.name == "txtFechaFin") {
              element.value = fin.format("YYYY-MM-DD");
            }
          });

          var regConsolidado = tblConsolidado.row(0).data();
          var modalidad = $("#cboSolicitante").attr("type") == "hidden" ? 1 : 3;
          //arr.push({name: 'txtFechaInicio', value : inicio.format('YYYY-MM-DD')});
          //arr.push({name: 'txtFechaFin', value : fin.format('YYYY-MM-DD')});
          arr.push({
            name: "tblConsolidado",
            value: JSON.stringify(regConsolidado),
          });
          arr.push({ name: "modalidad", value: modalidad });
          return arr;
        },
        beforeSend: function (e) {
          //VALIDACION PREVIA
          status = validarUsoDeVacaciones(uso_vacaciones);
          if (status == "false") {
            e.abort();
            btnLaddaSubmit.stop();
            return false;
          }

          var inicio = moment($("#txtFechaInicio").val(), "DD/MM/YYYY");
          var fin = moment($("#txtFechaFin").val(), "DD/MM/YYYY");
          status = validarTiempo(inicio, fin);
          if (status == "false") {
            e.abort();
            btnLaddaSubmit.stop();
            return false;
          }
        },
        success: function (data) {
          btnLaddaSubmit.stop();
          if (data.Result == "OK") {
            showConfirmSuccess(
              "Se proceso correctamente la solicitud de vacaciones"
            );
            $("#modalForm").bootstrapModal("hide");
            $("#vacacionesContainer").jtable("reload");
          } else {
            showConfirmError(data.Message);
          }
        },
        error: function () {
          showConfirmError("Ocurrió un error interno");
          btnLaddaSubmit.stop();
        },
        complete: function () {
          btnLaddaSubmit.stop();
        },
        dataType: "json",
      };
      $("#frmModal").ajaxSubmit(options);
    },
  });

  //$('#divFechaInicio').trigger('dp.change');
  //$('#divFechaFin').trigger('dp.change');
}

function cambiarFechas(uso_vacaciones) {
  if ($("#txtFechaInicio").val() == "" || $("#txtFechaFin").val() == "") {
    return false;
  }

  start = moment($("#txtFechaInicio").val(), "DD/MM/YYYY");
  end = moment($("#txtFechaFin").val(), "DD/MM/YYYY");

  var numDias = end.diff(start, "days") + 1;
  $("#txtCantidadDias").val(numDias);

  status = validarUsoDeVacaciones(uso_vacaciones);
  if (status == "false") {
    return false;
  }
  // data = { fechaInicio: start.format('YYYY-MM-DD'), fechaFin: end.format('YYYY-MM-DD'), idCondicion:$('#cboCondicion').val() , idSolicitante: $('#cboSolicitante').val() };
  // loading(true,'Validando fechas');
  // $.post($getAppName+'/validarFechas/', data, function(data) {
  //     loading(false);
  //     if (data.Result === 'OK') {
  //         validarTiempo(start,end);
  //         $('idvacacionespecial').val(data.idFechaEspecial);
  //     } else {
  //         showConfirmWarning(data.Message);
  //     }
  // }, 'json')
  // .fail(function() {
  //     loading(false);
  //     showConfirmError('Ocurrió un Error interno');
  //     //ev.preventDefault();
  //     return false;
  // });
}

function loading(mostrar, mensaje) {
  if (mostrar) {
    $("body").loadingModal({
      position: "auto",
      text: mensaje,
      color: "#fff",
      opacity: "0.7",
      backgroundColor: "rgb(0,0,0)",
      animation: "wave",
    });
  } else {
    $("body").loadingModal("hide");
    setTimeout(function () {
      $("body").loadingModal("destroy");
    }, 2000);
  }
}

// validar omar 322

/**
 * Valida el tiempo de vacaciones solicitado según los días disponibles
 * @param {object} fechaInicio - Fecha de inicio de vacaciones (objeto moment.js)
 * @param {object} fechaFin - Fecha de fin de vacaciones (objeto moment.js)
 * @returns {boolean} - Verdadero si la validación es exitosa, falso en caso contrario
 */
function validarTiempo(fechaInicio, fechaFin) {
  var condicion = $("#cboCondicion").val();
  var numDias = fechaFin.diff(fechaInicio, "days") + 1;
  
  // Verificar si existe la tabla de consolidado
  if (typeof tblConsolidado === 'undefined' || tblConsolidado === null) {
    console.error("Error: No se encontró la tabla de consolidado de vacaciones");
    showConfirmWarning("No se pueden validar las vacaciones porque no se encontraron los datos consolidados.");
    return false;
  }
  
  // Obtener datos de la tabla
  var regConsolidado;
  try {
    regConsolidado = tblConsolidado.row(0).data();
    if (!regConsolidado) {
      console.error("No se encontraron datos en la tabla de consolidado");
      return false;
    }
  } catch (error) {
    console.error("Error al obtener datos de la tabla:", error);
    return false;
  }
  
  // Actualizar campo de cantidad de días
  $("#txtCantidadDias").val(numDias);
  
  // Inicializar variables
  var disponible = 0;
  var diasDisponiblesVencido = 0;
  var diasDisponiblesGanado = 0;
  var tipoVacacion = "";
  
  // Convertir valores a números para evitar problemas
  var vencido = parseFloat(regConsolidado.vencido) || 0;
  var ganado = parseFloat(regConsolidado.ganado) || 0;
  var trunco = parseFloat(regConsolidado.trunco) || 0;
  var programado = parseFloat(regConsolidado.programado) || 0;
  var porProgramar = parseFloat(regConsolidado.por_programar) || 0;
  
  // Calcular pendientes previos si es necesario
  if (typeof pendientesPrevios === 'undefined' || pendientesPrevios == 0) {
    pendientesPrevios = Math.max(0, vencido + ganado - programado);
  }
  
  console.log('Datos actuales de vacaciones:', regConsolidado);
  console.log('Días solicitados:', numDias);
  console.log('Pendientes previos:', pendientesPrevios);

  // Validar que no seleccione truncas si tiene vencidas o ganadas
  if (condicion == "2") {
    // IMPORTANTE: Para esta validación, usar los datos originales si están disponibles
    if (typeof datosOriginales !== 'undefined' && datosOriginales !== null) {
      var vencidoOriginal = datosOriginales.vencido;
      var ganadoOriginal = datosOriginales.ganado;
      var programadoOriginal = datosOriginales.programado;
      
      // Calcular disponibles usando los valores originales
      var disponiblesVencido = Math.max(0, vencidoOriginal - programadoOriginal);
      var disponiblesGanado = 0;
      
      if (programadoOriginal > vencidoOriginal) {
        // Si programado > vencido, el restante va a ganado
        var programadoRestante = programadoOriginal - vencidoOriginal;
        disponiblesGanado = Math.max(0, ganadoOriginal - programadoRestante);
      } else {
        disponiblesGanado = ganadoOriginal;
      }
      
      var disponibleOriginal = disponiblesVencido + disponiblesGanado;
      
      console.log("Validando con datos originales:", {
        vencido: vencidoOriginal,
        ganado: ganadoOriginal,
        programado: programadoOriginal,
        disponible: disponibleOriginal
      });
      
      if (disponibleOriginal > 0) {
        showConfirmWarning(
          "No puede seleccionar adelanto a cuenta de vacaciones truncas, si dispone de vacaciones vencidas o pendientes."
        );
        return false;
      }
    } else {
      // Si no tenemos datos originales, validar con los actuales
      if (pendientesPrevios > 0 || porProgramar > 0) {
        showConfirmWarning(
          "No puede seleccionar adelanto a cuenta de vacaciones truncas, si dispone de vacaciones vencidas o pendientes."
        );
        return false;
      }
    }
  }
  
  // Calcular disponibilidad según condición
  if (condicion == "1") {
    // Para condición 1, usar disponibilidad total correctamente
    
    // IMPORTANTE: Usar directamente por_programar si está disponible
    // Este valor ya debería tener calculado correctamente lo disponible
    if (porProgramar > 0) {
      disponible = porProgramar;
      tipoVacacion = "pendientes por programar";
      
      console.log('Usando valor por_programar:', porProgramar);
    } else {
      // Calcular disponible como se hacía antes si por_programar no está disponible
      
      // Primero restamos programado de vencido
      if (programado <= vencido) {
        diasDisponiblesVencido = vencido - programado;
        diasDisponiblesGanado = ganado;
      } else {
        // Si programado > vencido, el restante va a ganado
        diasDisponiblesVencido = 0;
        var programadoRestante = programado - vencido;
        diasDisponiblesGanado = Math.max(0, ganado - programadoRestante);
      }
      
      // Total disponible es la suma de ambos
      disponible = diasDisponiblesVencido + diasDisponiblesGanado;
      
      console.log('Disponible calculado:', {
        vencido: diasDisponiblesVencido,
        ganado: diasDisponiblesGanado,
        total: disponible
      });
      
      // Verificar si los días solicitados superan lo disponible en vencidas
      if (numDias <= diasDisponiblesVencido) {
        tipoVacacion = "vencidas";
      } else if (numDias <= disponible) {
        // Si no alcanza solo con vencidas pero sí con ganadas
        var diasDeVencidas = Math.min(numDias, diasDisponiblesVencido);
        var diasDeGanadas = numDias - diasDeVencidas;
        
        tipoVacacion = "vencidas y pendientes";
        
        // Mostrar mensaje informativo sobre distribución de días
        if (diasDeVencidas > 0 && diasDeGanadas > 0) {
          showConfirmInfo(
            "Se tomarán " + diasDeVencidas.toFixed(2) + " días de vacaciones vencidas y " +
            diasDeGanadas.toFixed(2) + " días de vacaciones pendientes."
          );
        }
      } else {
        // Si no alcanza ni con ambas
        tipoVacacion = "vencidas y pendientes";
      }
    }
    
  } else if (condicion == "2") {
    // Para condición 2, solo truncas
    disponible = trunco;
    tipoVacacion = "truncas";
  } else if (condicion == "3") {
    // Para condición 3, no validamos disponibilidad
    return true;
  }

  // Validar disponibilidad para condiciones 1 y 2
  if (condicion != "3") {
    if (numDias > disponible) {
      var mensaje = "Solo tienes disponible " +
          disponible.toFixed(2) +
          " días en el periodo de vacaciones " +
          tipoVacacion;
          
      // Si hay vacaciones truncas disponibles, sugerir usarlas
      if (trunco > 0 && condicion == "1") {
        mensaje += ". Te recomendamos considerar tomar vacaciones truncas si necesitas más días.";
      } else {
        mensaje += ". Por favor, ajusta la cantidad de días solicitados.";
      }
      
      showConfirmWarning(mensaje);
      return false;
    }
  }

  return true;
}

function validarUsoDeVacaciones(usoDeVacaciones) {
  var condicion = $("#cboCondicion").val();
  if (condicion == "2" && usoDeVacaciones > 0) {
    showConfirmWarning(
      "No puede seleccionar adelanto a cuenta de vacaciones truncas, si dispone de vacaciones vencidas o pendientes."
    );
    return false;
  }

  return true;
}
