// Variable global para controlar el proceso de autocompletado
let autoCompletando = false;
let ajaxRequests = {};
let generador = false;
var $ajaxEmpresa;
var $ajaxGerencia;
var $ajaxArea;
var dateOptions = {
  locale: "es",
  format: "DD/MM/YYYY",
  allowInputToggle: false,
  ignoreReadonly: true,
  showTodayButton: true,
  showClose: true,
  useCurrent: false,
};
var VEmasivo = [];
var maxDiasVacacionesEspeciales = 6;
// Función para mostrar/ocultar indicador de carga
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

// Función para realizar peticiones AJAX de manera más eficiente
function fetchData(url, params, targetElement, callback) {
  // Cancelar petición existente si hay alguna en progreso
  const requestKey = url + JSON.stringify(params);
  if (ajaxRequests[requestKey] && ajaxRequests[requestKey].readyState !== 4) {
    ajaxRequests[requestKey].abort();
  }

  // Si no hay parámetros válidos, no hacer la petición
  if (params && Object.values(params).some((val) => val !== "")) {
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

          if (callback && typeof callback === "function") callback(data);
        } else {
          showConfirmWarning(
            data.Message || "No se pudo obtener la información"
          );
        }
      },
      "json"
    ).fail(function (xhr, status, error) {
      console.error("Error en fetchData:", status, error);
      showConfirmError("Ocurrió un Error interno");
    });
  }
}

function obtenerLimiteDias() {
  $.ajax({
    url: $getAppName + "/obtenerLimiteDiasVacacionesEspeciales",
    type: "GET",
    dataType: "json",
    success: function (response) {
      console.log(response);
      if (response && response.limite) {
        maxDiasVacacionesEspeciales = response.limite[0]['valor'];
        // console.log(
        //   "Límite de días actualizado: " + maxDiasVacacionesEspeciales
        // );
      } else {
        console.warn(
          "No se pudo obtener el límite de días, usando valor por defecto: " +
            maxDiasVacacionesEspeciales
        );
      }
    },
    error: function (xhr, status, error) {
      console.error("Error al obtener límite de días:", status, error);
    },
  });
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
    useCurrent: false,
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
        $("#qry_colaborador").selectpicker("val", "");
        $("#qry_colaborador").selectpicker("refresh");
      } catch (e) {
        console.warn("Error al reiniciar colaborador:", e);
      }
    }

    fetchData(
      "directorio/getAreas/",
      {
        qry_empresa: empresaId,
        qry_gerencia: gerenciaId,
        qry_departamento: departamentoId,
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
        $("#qry_colaborador").selectpicker("val", "");
        $("#qry_colaborador").selectpicker("refresh");
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
        qry_area: areaId,
      },
      $seccion
    );
  });

  /* ========================================
        TABLA DE REGISTRO DE FECHAS ESPECIALES
    ======================================== */
    $("#vacacionesespecialesContainer").jtable({
      title: "Listado de Registros de Fechas Especiales",
      paging: true,
      pageSize: 20,
      sorting: true,
      defaultSorting: "id_vaca_especial DESC",
      saveUserPreferences: false,
      toolbar: {
          hoverAnimation: true,
          hoverAnimationDuration: 60,
          hoverAnimationEasing: undefined,
          items: [
              {
                  icon: "assets/browser-components/jquery-jtable/themes/lightcolor/add.png",
                  text: "Crear registro de fecha especial",
                  click: function () {
                      abrirModalFormulario();
                  },
              },
          ],
      },
      actions: {
          listAction: $getAppName + "/listar/",
      },
      fields: {
          id_vaca_especial: {
              title: "ID",
              width: "1%",
              list: true,
              key: true,
          },
          fecha_inicio: {
              title: "Fec. Inicio",
              display: function (data) {
                  try {
                      return moment(data.record.fecha_inicio.date).format("DD/MM/YYYY");
                  } catch (e) {
                      console.warn("Error formateando fecha:", e);
                      return "";
                  }
              },
              width: "5%",
          },
          fecha_fin: {
              title: "Fec. Fin",
              display: function (data) {
                  try {
                      return moment(data.record.fecha_fin.date).format("DD/MM/YYYY");
                  } catch (e) {
                      console.warn("Error formateando fecha:", e);
                      return "";
                  }
              },
              width: "5%",
          },
          empresa: {
              title: "Empresa",
              width: "5%",
          },
          gerencia: {
              title: "Gerencia",
              width: "12%",
          },
          departamento: {
              title: "Departamento",
              width: "12%",
          },
          area: {
              title: "Área",
              width: "12%",
          },
          seccion: {
              title: "Sección",
              width: "12%",
          },
          id_solicitante: {
              title: "id_solicitante",
              width: "5%",
              list: false,
          },
          solicitante: {
              title: "Solicitante",
              width: "15%",
          },
          id_generador: {
              title: "id_Solicitante",
              width: "5%",
              list: false,
          },
          generador: {
              title: "Registrador",
              width: "15%",
          },
          fecha_crea: {
              title: "Fec. Solicitud",
              display: function (data) {
                  try {
                      return moment(data.record.fecha_crea.date).format(
                          "DD/MM/YYYY HH:mm:ss"
                      );
                  } catch (e) {
                      console.warn("Error formateando fecha:", e);
                      return "";
                  }
              },
              width: "5%",
          },
          acciones: {
              title: "Acciones",
              width: "5%",
              sorting: false,
              edit: false,
              create: false,
              display: function (data) {
                  var btnGroup = $('<div class="btn-group" role="group"></div>');
  
                  try {
                      // Botón Editar
                      var btnEditar = $(
                          '<button data-style="slide-up" class="btn btn-primary btn-xs ladda-button" title="Editar Solicitud"><span class="ladda-label"><i class="glyphicon glyphicon-pencil"></i></span></button>'
                      );
                      var btnLdEditar = Ladda.create(btnEditar[0]);
                      btnEditar.click(function () {
                          btnLdEditar.start();
                          abrirModalEditar(data.record);
                          btnLdEditar.stop();
                      });
                      btnGroup.append(btnEditar);
  
                      // Botón eliminar
                      var btnEliminar = $(
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
                                      { id_vaca_especial: data.record.id_vaca_especial },
                                      function (response) {
                                          if (response.Result === "OK") {
                                              btnLdEliminar.stop();
                                              $("#vacacionesespecialesContainer").jtable("reload");
                                          } else {
                                              showConfirmWarning(
                                                  response.Message || "No se pudo eliminar el registro"
                                              );
                                          }
                                      },
                                      "json"
                                  ).fail(function (xhr, status, error) {
                                      console.error("Error en borrar:", status, error);
                                      console.error("Respuesta del servidor:", xhr.responseText);
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
                  } catch (e) {
                      console.warn("Error en display de acciones:", e);
                  }
  
                  return btnGroup;
              },
          },
      },
  });
  
  // Función reutilizable para abrir el modal para la creación
  function abrirModalFormulario() {
      loading(true, "Obteniendo información para crear");
      $.post(
          $getAppName + "/indexCrear/",
          {},
          function (data) {
              loading(false);
              if (data.Result === "OK") {
                  var template = $("#tplFrmModal").html();
                  Mustache.parse(template);
  
                  var optionsRender = {
                      action: $getAppName + "/crear",
                      edit: false,
                      empresa: data.empresa,
                      submitText: "Generar Solicitud",
                      formData: {},
                  };
  
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
                  showConfirmWarning(
                      data.Message || "No se pudo obtener la información para crear el registro"
                  );
              }
          },
          "json"
      ).fail(function (xhr, status, error) {
          console.error("Error en indexCrear:", status, error);
          loading(false);
          showConfirmError("Ocurrió un Error interno al obtener la información de creación");
      });
  }
  
  // Nueva función para abrir el modal para la edición
  function abrirModalEditar(rowData) {
    loading(true, "Obteniendo información para editar");
    $.post(
        $getAppName + "/indexEditar/",
        { id_vaca_especial: rowData.id_vaca_especial },
        function (data) {
            loading(false);
            if (data.Result === "OK") {
                var template = $("#tplFrmModal").html();
                Mustache.parse(template);

                var optionsRender = {
                    action: $getAppName + "/editar",
                    edit: true,
                    empresa: data.empresa,
                    submitText: "Guardar Cambios",
                    formData: rowData,
                };

                var rendered = Mustache.render(template, optionsRender);
                $("#modalForm .modal-dialog .modal-content").html(rendered);
                $("#modalForm .modal-dialog").draggable({
                    handle: ".modal-header",
                });
                setFunctionFormulario(true, rowData.fecha_inicio, rowData.fecha_fin);

                // Pre-llenado de los campos del formulario
                for (var key in rowData) {
                    var input = $("#modalForm").find("[name='" + key + "']");
                    if (input.length) {
                        input.val(rowData[key]);
                    }
                    if (key.startsWith("fecha_") && rowData[key] && rowData[key].date) {
                        $("#modalForm").find("[name='" + key + "']").val(moment(rowData[key].date).format("YYYY-MM-DD"));
                    }
                }

                // Llenar y deshabilitar los combos selectores
                llenarYDeshabilitarCombos(rowData);

                $("#modalForm").bootstrapModal({
                    show: true,
                    backdrop: false,
                });

                // Cargar la tabla relacionada
                cargarTablaVacacionesTemp(rowData.id_vaca_especial);
            } else {
                showConfirmWarning(
                    data.Message || "No se pudo obtener la información para editar el registro"
                );
            }
        },
        "json"
    ).fail(function (xhr, status, error) {
        console.error("Error en indexEditar:", status, error);
        loading(false);
        showConfirmError("Ocurrió un Error interno al obtener la información de edición");
    });
}

// Función para llenar y deshabilitar los combos
function llenarYDeshabilitarCombos(rowData) {
    // Llenar los combos con la información de la tabla
    if (rowData.empresa) {
        // Llenar combo de Empresa
        $("#qryEmpresa").val(rowData.id_empresa || "");
        $("#qryEmpresa").prop("disabled", true);
        
        // Agregar opciones al combo de Gerencia si está vacío
        if ($("#qryGerencia option").length <= 1 && rowData.gerencia) {
            $("#qryGerencia").append(new Option(rowData.gerencia, rowData.id_unidad || "", true, true));
        }
        $("#qryGerencia").val(rowData.id_unidad || "");
        $("#qryGerencia").prop("disabled", true);
        
        // Agregar opciones al combo de Departamento si está vacío
        if ($("#qryDepartamento option").length <= 1 && rowData.departamento) {
            $("#qryDepartamento").append(new Option(rowData.departamento, rowData.id_departamento || "", true, true));
        }
        $("#qryDepartamento").val(rowData.id_departamento || "");
        $("#qryDepartamento").prop("disabled", true);
        
        // Agregar opciones al combo de Área si está vacío
        if ($("#qryArea option").length <= 1 && rowData.area) {
            $("#qryArea").append(new Option(rowData.area, rowData.id_area || "", true, true));
        }
        $("#qryArea").val(rowData.id_area || "");
        $("#qryArea").prop("disabled", true);
        
        // Agregar opciones al combo de Sección si está vacío
        if ($("#qrySeccion option").length <= 1 && rowData.seccion) {
            $("#qrySeccion").append(new Option(rowData.seccion, rowData.id_seccion || "", true, true));
        }
        $("#qrySeccion").val(rowData.id_seccion || "");
        $("#qrySeccion").prop("disabled", true);

        $("#qryColaborador").prop("disabled", true);
    }
}

// Función para cargar la tabla de Vacaciones Temporales relacionada
function cargarTablaVacacionesTemp(idVacaEspecial) {
  loading(true, "Cargando Vacaciones Temporales...");

  $.post(
      $getAppName + "/listarVacacionesTemp",
      { id_vaca_especial: idVacaEspecial },
      function (data) {
          loading(false);

          if (data.Result === "OK") {
              console.log("Datos completos desde el backend:", data);

              // Destruir la tabla anterior si ya estaba inicializada
              $("#tableListaVE").jtable("destroy");

              $("#tableListaVE").jtable({
                  title: "Listado de Vacaciones Especiales",
                  paging: true,
                  pageSize: 10,
                  sorting: false,
                  actions: {
                      listAction: function () {
                          return $.Deferred(function ($dfd) {
                              $dfd.resolve({
                                  Result: "OK",
                                  Records: data.Records,
                                  TotalRecordCount: data.TotalRecordCount
                              });
                          });
                      }
                  },
                  fields: {
                    id_vacacion_temp: {
                      title: "id_vacacion_temp",
                      width: "12%",
                      display: function (recordData) {
                          return recordData.record.id_vacacion_temp;
                      },
                      list: false,
                    },
                      fecha_inicio: {
                          title: "F. Inicio",
                          width: "8%",
                          display: function (recordData) {
                            const fecha = recordData.record.fecha_inicio;
                            const fechaFormateada = fecha && fecha.date ? fecha.date.split(" ")[0] : ""; // Solo fecha sin hora
                            return fechaFormateada;
                        },
                      },
                      fecha_fin: {
                          title: "F. Fin",
                          width: "8%",
                          display: function (recordData) {
                            const fecha = recordData.record.fecha_fin;
                            const fechaFormateada = fecha && fecha.date ? fecha.date.split(" ")[0] : "";
                            return fechaFormateada;
                        },
                      },
                      empresa: {
                          title: "Empresa",
                          width: "12%",
                          display: function (recordData) {
                              return recordData.record.empresa;
                          },
                      },
                      gerencia: {
                          title: "Gerencia",
                          width: "12%",
                          display: function (recordData) {
                              return recordData.record.gerencia;
                          },
                      },
                      departamento: {
                          title: "Departamento",
                          width: "12%",
                          display: function (recordData) {
                              return recordData.record.departamento;
                          },
                      },
                      area: {
                          title: "Área",
                          width: "12%",
                          display: function (recordData) {
                              return recordData.record.area;
                          },
                      },
                      seccion: {
                          title: "Sección",
                          width: "12%",
                          display: function (recordData) {
                              return recordData.record.seccion;
                          },
                      },
                      id_solicitante: {
                          title: "Solicitante",
                          width: "15%",
                          display: function (recordData) {
                              return recordData.record.solicitante;
                          },
                      },
                      accion: {
                          title: "Acción",
                          width: "5%",
                          display: function (recordData) {
                              var btnGroup = $('<div class="btn-group" role="group"></div>');
                              
                              // Botón editar
                              var btnEditar = $('<button class="btn btn-primary btn-xs ladda-button" title="Editar Vacación"><span class="ladda-label"><i class="glyphicon glyphicon-pencil"></i></span></button>');
                              var btnLdEditar = Ladda.create(btnEditar[0]);
                              btnEditar.click(function () {
                                  btnLdEditar.start();
                                  editarVacacionTemp(recordData.record);
                                  btnLdEditar.stop();
                              });
                              btnGroup.append(btnEditar);
                              
                              // Botón eliminar
                              // var btnEliminar = $('<button class="btn btn-ac btn-xs ladda-button" title="Eliminar Vacación"><span class="ladda-label"><i class="glyphicon glyphicon-trash"></i></span></button>');
                              // var btnLdEliminar = Ladda.create(btnEliminar[0]);
                              // btnEliminar.click(function () {
                              //     btnLdEliminar.start();
                              //     eliminarVacacionTemp(recordData.record.id_vacacion_temp);
                              //     btnLdEliminar.stop();
                              // });
                              // btnGroup.append(btnEliminar);
                              
                              return btnGroup;
                          },
                      },
                  },
              });

              // Esto carga los datos en la tabla desde la memoria
              $("#tableListaVE").jtable("load");
              
              // Agregar un campo oculto para guardar el ID de la vacación temporal en edición
              if ($("#id_vacacion_temp").length === 0) {
                  $("<input>").attr({
                      type: "hidden",
                      id: "id_vacacion_temp",
                      name: "id_vacacion_temp"
                  }).appendTo("#frmModal");
              }
              
              // Modificar el botón de agregar para que considere la edición
              $("#addColaborador").off("click").on("click", function() {
                  var btnLaddaAddColaborador = Ladda.create($("#addColaborador")[0]);
                  btnLaddaAddColaborador.start();
                  
                  if ($("#frmModal").valid()) {
                      var idVacacionTemp = $("#id_vacacion_temp").val();
                      
                      if (idVacacionTemp) {
                          // Si hay ID, estamos actualizando una vacación temporal existente
                          actualizarVacacionTemp(idVacacionTemp, btnLaddaAddColaborador);
                      } else {
                          // Si no hay ID, agregar nueva vacación como estaba antes
                          // Tu código existente para agregar vacación
                          // ...
                      }
                  } else {
                      btnLaddaAddColaborador.stop();
                  }
              });

          } else {
              showConfirmWarning(data.Message || "No se pudieron cargar las Vacaciones Temporales.");
          }
      },
      "json"
  ).fail(function (xhr, status, error) {
      console.error("Error al cargar Vacaciones Temporales:", status, error);
      console.error("Respuesta del servidor:", xhr.responseText);
      loading(false);
      showConfirmError("Ocurrió un error al cargar las Vacaciones Temporales.");
  });
}

// Función para actualizar la vacación temporal
function actualizarVacacionTemp(idVacacionTemp, btnLadda) {
    // Obtener los datos del formulario
    var fechaInicio = $("#txtFechaInicio").val();
    var fechaFin = $("#txtFechaFin").val();
    var colaboradorId = $("#qryColaborador").val();
    
    // Validar datos
    if (!fechaInicio || !fechaFin) {
        showConfirmWarning("Los campos fecha inicio y fecha fin son obligatorios.");
        btnLadda.stop();
        return;
    }
    
    // Enviar solicitud para actualizar
    $.post(
        $getAppName + "/actualizarVacacionTemp/",
        {
            id_vacacion_temp: idVacacionTemp,
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin,
            id_solicitante: colaboradorId
        },
        function(response) {
            btnLadda.stop();
            if (response.Result === "OK") {
                showConfirmSuccess("Vacación temporal actualizada correctamente");
                
                // Resetear el formulario
                $("#id_vacacion_temp").val("");
                $("#txtFechaInicio").val("");
                $("#txtFechaFin").val("");
                $("#qryColaborador").val("");
                $("#qryColaborador").selectpicker("refresh");
                
                // Cambiar el texto del botón de nuevo a Agregar
                $("#addColaborador").text("Agregar Colaborador");
                
                // Recargar la tabla
                cargarTablaVacacionesTemp($("#id_vaca_especial").val());
            } else {
                showConfirmWarning(response.Message || "No se pudo actualizar la vacación temporal");
            }
        },
        "json"
    ).fail(function(xhr, status, error) {
        btnLadda.stop();
        console.error("Error al actualizar vacación temporal:", status, error);
        showConfirmError("Ocurrió un error al actualizar la vacación temporal");
    });
}


  // Asegúrate de que setFunctionFormulario() esté definida en tu código
  // y que pueda manejar tanto la creación como la edición.
  // Probablemente necesites verificar el valor de 'optionsRender.edit' dentro de ella
  // para determinar a qué endpoint enviar los datos del formulario.

  // Función para actualizar la vacación temporal
function actualizarVacacionTemp(idVacacionTemp, btnLadda) {
  // Obtener los datos del formulario
  var fechaInicio = $("#txtFechaInicio").val();
  var fechaFin = $("#txtFechaFin").val();
  var colaboradorId = $("#qryColaborador").val();
  
  // Validar datos
  if (!fechaInicio || !fechaFin) {
      showConfirmWarning("Los campos fecha inicio y fecha fin son obligatorios.");
      btnLadda.stop();
      return;
  }
  
  // Enviar solicitud para actualizar
  $.post(
      $getAppName + "/actualizarVacacionTemp/",
      {
          id_vacacion_temp: idVacacionTemp,
          fecha_inicio: fechaInicio,
          fecha_fin: fechaFin,
          id_solicitante: colaboradorId
      },
      function(response) {
          btnLadda.stop();
          if (response.Result === "OK") {
              showConfirmSuccess("Vacación temporal actualizada correctamente");
              
              // Resetear el formulario
              $("#id_vacacion_temp").val("");
              $("#txtFechaInicio").val("");
              $("#txtFechaFin").val("");
              $("#qryColaborador").val("");
              $("#qryColaborador").selectpicker("refresh");
              
              // Cambiar el texto del botón de nuevo a Agregar
              $("#addColaborador").text("Agregar Colaborador");
              
              // Recargar la tabla
              cargarTablaVacacionesTemp($("#id_vaca_especial").val());
          } else {
              showConfirmWarning(response.Message || "No se pudo actualizar la vacación temporal");
          }
      },
      "json"
  ).fail(function(xhr, status, error) {
      btnLadda.stop();
      console.error("Error al actualizar vacación temporal:", status, error);
      showConfirmError("Ocurrió un error al actualizar la vacación temporal");
  });
}
  
  
  // Asegúrate de que setFunctionFormulario() esté definida en tu código para manejar el submit del formulario

  // Aplicar estilos a las cabeceras después de inicializar la tabla
  setTimeout(function () {
    $("#vacacionesespecialesContainer .jtable-column-header").css({
      height: "50px",
      "vertical-align": "middle",
    });

    $("#vacacionesespecialesContainer .jtable-column-header-text").css({
      position: "relative",
      top: "50%",
      transform: "translateY(-50%)",
    });
  }, 100);

  // Aplicar estilos después de cada recarga de datos
  $("#vacacionesespecialesContainer").bind("jtable.rowsRefreshed", function () {
    $("#vacacionesespecialesContainer .jtable-column-header").css({
      height: "50px",
      "vertical-align": "middle",
    });

    $("#vacacionesespecialesContainer .jtable-column-header-text").css({
      position: "relative",
      top: "50%",
      transform: "translateY(-50%)",
    });
  });

  // Configuración para el selectpicker con Ajax
  var optColaborador = {
    ajax: {
      url: $getAppName + "/buscarUsuario",
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
              value: data[i].id_solicitante,
              data: {
                subtext: data[i].DisplayText2,
              },
            })
          );
        }
      }
      return array;
    },
  };

  // Manejador del botón de búsqueda
  $("#LoadRecordsButton").click(function (e) {
    e.preventDefault();
    // Convertir fechas al formato esperado por el servidor (si es necesario)
    let fechaInicio = $("#qryFechaInicio").val();
    let fechaFin = $("#qryFechaFin").val();

    $("#vacacionesespecialesContainer").jtable("load", {
      qry_empresa: $("#qry_empresa").val(),
      qry_gerencia: $("#qry_gerencia").val(),
      qry_departamento: $("#qry_departamento").val(),
      qry_area: $("#qry_area").val(),
      qry_seccion: $("#qry_seccion").val(),
      qry_colaborador: $("#qry_colaborador").val(),
      qry_ini_rango: fechaInicio,
      qry_fin_rango: fechaFin,
    });
  });
  // Botón para exportar (implementación básica)
  $("#btnExportar").click(function (e) {
    e.preventDefault();

    // Obtener los mismos parámetros que se usan para la búsqueda
    const params = {
      qry_empresa: $("#qry_empresa").val(),
      qry_gerencia: $("#qry_gerencia").val(),
      qry_departamento: $("#qry_departamento").val(),
      qry_area: $("#qry_area").val(),
      qry_seccion: $("#qry_seccion").val(),
      qry_colaborador: $("#qry_colaborador").val(),
      qry_ini_rango: $("#qryFechaInicio").val(),
      qry_fin_rango: $("#qryFechaFin").val(),
      export: "excel", // Indicar que queremos exportar
    };

    // Construir URL con parámetros para la exportación
    const queryString = Object.keys(params)
      .filter((key) => params[key]) // Filtrar parámetros vacíos
      .map(
        (key) => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`
      )
      .join("&");

    // Redirigir a la URL de exportación
    window.location.href = $getAppName + "/exportar/?" + queryString;
  });
  // Función para mostrar datos de los combo en el modal
  function setFunctionFormulario(editar, fechaInicio, fechaFin) {
    obtenerLimiteDias();
    editar = editar || false;
    // Inicia el selectpicker con ajaxSelectPicker
    if ($("#qryColaborador").length > 0) {
      try {
        $("#qryColaborador").selectpicker().ajaxSelectPicker(optColaborador);
      } catch (e) {
        console.error("Error al inicializar selectpicker:", e);
      }
    }

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



        $("#qryEmpresa").off("change");
        $("#qryGerencia").off("change");
        $("#qryDepartamento").off("change");
        $("#qryArea").off("change");
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
      validarRangoDias();
    });

    function validarRangoDias() {
      var fechaInicioVal = $("#txtFechaInicio").val();
      var fechaFinVal = $("#txtFechaFin").val();
      
      // Solo validar si ambas fechas están seleccionadas
      if (fechaInicioVal && fechaFinVal) {
        // Obtener objetos moment desde el DateTimePicker
        var momentInicio = $("#divFechaInicio").data("DateTimePicker").date();
        var momentFin = $("#divFechaFin").data("DateTimePicker").date();
        
        if (momentInicio && momentFin) {
          // Calcular diferencia en días
          var diferenciaDias = momentFin.diff(momentInicio, "days") + 1; // +1 para incluir ambos días
          // Validar que no exceda el límite de días permitido
          if (diferenciaDias > maxDiasVacacionesEspeciales) {
            showConfirmWarning(
              "El rango de fechas seleccionado excede el límite de " + maxDiasVacacionesEspeciales + " días permitido."
            );
            // Resetear la fecha fin
            $("#divFechaFin").data("DateTimePicker").clear();
          }
        }
      }
    }
    
    $("#qryEmpresa").change(function () {
      var $gerencia = $("#qryGerencia");
      $gerencia.html('<option value=""> Seleccione </option>');
      var $valor = $(this).val();

      if ($ajaxEmpresa && $ajaxEmpresa.readyState != 4) {
        $ajaxEmpresa.abort();
      }

      if ($valor != 0) {
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

    $("#qryGerencia").change(function () {
      const $departamento = $("#qryDepartamento");
      const gerenciaId = $(this).val();
      const empresaId = $("#qryEmpresa").val();

      $("#qryDepartamento").html('<option value=""> Todas </option>');
      $("#qryArea").html('<option value=""> Todas </option>');
      $("#qrySeccion").html('<option value=""> Todas </option>');

      fetchData(
        "directorio/getDepartamentos/",
        { qry_empresa: empresaId, qry_gerencia: gerenciaId },
        $departamento
      );
    });

    $("#qryDepartamento").change(function () {
      const $area = $("#qryArea");
      const departamentoId = $(this).val();
      const gerenciaId = $("#qryGerencia").val();
      const empresaId = $("#qryEmpresa").val();

      $("#qryArea").html('<option value=""> Todas </option>');
      $("#qrySeccion").html('<option value=""> Todas </option>');

      if (!autoCompletando && $("#qryColaborador").length > 0) {
        try {
          $("#qryColaborador").selectpicker("val", "");
          $("#qryColaborador").selectpicker("refresh");
        } catch (e) {
          console.warn("Error al reiniciar colaborador:", e);
        }
      }

      fetchData(
        "directorio/getAreas/",
        {
          qry_empresa: empresaId,
          qry_gerencia: gerenciaId,
          qry_departamento: departamentoId,
        },
        $area
      );
    });

    $("#qryArea").change(function () {
      const $seccion = $("#qrySeccion");
      const areaId = $(this).val();
      const departamentoId = $("#qryDepartamento").val();
      const gerenciaId = $("#qryGerencia").val();
      const empresaId = $("#qryEmpresa").val();

      $("#qrySeccion").html('<option value=""> Todas </option>');

      if (!autoCompletando && $("#qryColaborador").length > 0) {
        try {
          $("#qryColaborador").selectpicker("val", "");
          $("#qryColaborador").selectpicker("refresh");
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
          qry_area: areaId,
        },
        $seccion
      );
    });

    // Evento para cuando se selecciona un solicitante desde el modal
    $("#qryColaborador").on("changed.bs.select", function (e) {
      const selectedValue = $(this).val();

      if (selectedValue && selectedValue !== "") {
        // Guardar la selección actual
        const selectedId = selectedValue;
        const selectedText = $(this).find("option:selected").text();

        // Mostrar indicador de carga
        loading(true, "Cargando información del solicitante");

        // Marcar que estamos en proceso de autocompletado
        autoCompletando = true;

        // Obtener los datos del solicitante
        $.post(
          $getAppName + "/getInfoSolicitante",
          { id_solicitante: selectedValue },
          function (response) {
            if (response.Result === "OK") {
              const data = response.Data;

              // Actualizar el combo de empresa y encadenar las actualizaciones
              if ($("#qryEmpresa").length > 0) {
                $("#qryEmpresa").val(data.cod_empresa).trigger("change");
                $("#qryEmpresa").prop("disabled", true);

                // Encadenar actualizaciones con retrasos para permitir que se carguen los datos
                setTimeout(function () {
                  if ($("#qryGerencia").length > 0) {
                    $("#qryGerencia").val(data.cod_gerencia).trigger("change");
                    $("#qryGerencia").prop("disabled", true);

                    setTimeout(function () {
                      if ($("#qryDepartamento").length > 0) {
                        $("#qryDepartamento")
                          .val(data.cod_departamento)
                          .trigger("change");
                          $("#qryDepartamento").prop("disabled", true);

                        setTimeout(function () {
                          if ($("#qryArea").length > 0) {
                            $("#qryArea").val(data.cod_area).trigger("change");
                            $("#qryArea").prop("disabled", true);

                            setTimeout(function () {
                              if ($("#qrySeccion").length > 0) {
                                $("#qrySeccion").val(data.cod_seccion);
                                $("#qrySeccion").prop("disabled", true);
                              }

                              restaurarColaborador();
                            }, 400);
                          } else {
                            restaurarColaborador();
                          }
                        }, 400);
                      } else {
                        restaurarColaborador();
                      }
                    }, 400);
                  } else {
                    restaurarColaborador();
                  }
                }, 400);
              } else {
                restaurarColaborador();
              }
            } else {
              restaurarColaborador();
              showConfirmWarning(
                response.Message ||
                  "No se pudo obtener la información del solicitante"
              );
            }
          },
          "json"
        ).fail(function (xhr, status, error) {
          console.error("Error en getInfoSolicitante:", status, error);
          restaurarColaborador();
          showConfirmError(
            "Ocurrió un Error al intentar obtener la información del solicitante"
          );
        });

        // Función auxiliar para restaurar el colaborador seleccionado
        function restaurarColaborador() {
          if ($("#qryColaborador").length > 0) {
            try {
              if (
                !$("#qryColaborador option[value='" + selectedId + "']").length
              ) {
                $("#qryColaborador").append(
                  new Option(selectedText, selectedId, true, true)
                );
              }
              $("#qryColaborador").val(selectedId);
              $("#qryColaborador").selectpicker("refresh");
            } catch (e) {
              console.warn("Error al restaurar solicitante:", e);
            }
          }

          autoCompletando = false;
          loading(false);
        }
      }else{
        habilitarSelectores();
      }
    });

    function habilitarSelectores() {
      $("#qryEmpresa").prop("disabled", false);
      $("#qryGerencia").prop("disabled", false);
      $("#qryDepartamento").prop("disabled", false);
      $("#qryArea").prop("disabled", false);
      $("#qrySeccion").prop("disabled", false);
    }
    // Botón para agregar la VE a la tabla
    var btnLaddaAddColaborador = Ladda.create($("#addColaborador")[0]);
    $("#addColaborador").click(function () {
      btnLaddaAddColaborador.start();

      if ($("#frmModal").valid()) {
        var empresaId = $("#qryEmpresa").val();
        var empresaNombre = $("#qryEmpresa").find("option:selected").text();
        var fechaInicio = $("#txtFechaInicio").val();
        var fechaFin = $("#txtFechaFin").val();

        // Validar campos obligatorios
        if (!fechaInicio || !fechaFin) {
          showConfirmWarning(
            "Los campos fecha inicio y fecha fin son obligatorios."
          );
          btnLaddaAddColaborador.stop();
          return;
        }

        // Función para validar si el valor es "Seleccione" o "Todas"
        function validarValor(valor, nombre) {
          if (
            valor === "" ||
            nombre.toLowerCase() === "seleccione" ||
            nombre.toLowerCase() === "todas"
          ) {
            return { id: null, nombre: "" };
          }
          return { id: valor, nombre: nombre };
        }
        // Capturar valores opcionales
        var gerencia = validarValor(
          $("#qryGerencia").val(),
          $("#qryGerencia").find("option:selected").text()
        );
        var departamento = validarValor(
          $("#qryDepartamento").val(),
          $("#qryDepartamento").find("option:selected").text()
        );
        var area = validarValor(
          $("#qryArea").val(),
          $("#qryArea").find("option:selected").text()
        );
        var seccion = validarValor(
          $("#qrySeccion").val(),
          $("#qrySeccion").find("option:selected").text()
        );
        var colaborador = validarValor(
          $("#qryColaborador").val(),
          $("#qryColaborador").find("option:selected").text()
        );
        // console.log(colaborador.id);
        if (colaborador.id) {
          // Llamar al controlador de validación solo cuando hay un colaborador.id
          $.ajax({
            url: $getAppName + "/validacionFechaEspecialSolicitante",
            type: "POST",
            dataType: "json",
            data: {
              colaboradorId: colaborador.id,
              fechaInicio: fechaInicio,
              fechaFin: fechaFin,
            },
            success: function (response) {
              if (!response.valido) {
                showConfirmWarning(response.mensaje);
                btnLaddaAddColaborador.stop();
                // return;
              } else {
                agregarRegistro(); // Función separada para agregar el registro
              }
            },
            error: function (xhr, status, error) {
              console.error("Error en la validación:", status, error);
              showConfirmWarning(
                "Error al validar las fechas del colaborador."
              );
              btnLaddaAddColaborador.stop();
            },
          });
        } else {
          // Validación cuando no hay colaborador específico
          $.ajax({
            url: $getAppName + "/validacionFechaEspecial",
            type: "POST",
            dataType: "json",
            data: {
              idEmpresa: empresaId,
              idUnidad: gerencia.id,
              idDepartamento: departamento.id,
              idArea: area.id,
              idSeccion: seccion.id,
              fechaInicio: fechaInicio,
              fechaFin: fechaFin,
            },
            success: function (response) {
              if (!response.valido) {
                showConfirmWarning(response.mensaje);
                btnLaddaAddColaborador.stop();
              } else {
                agregarRegistro(); // Si pasa la validación, agregar el registro
              }
            },
            error: function (xhr, status, error) {
              console.error("Error en la validación:", status, error);
              showConfirmWarning(
                "Error al validar las fechas para el grupo seleccionado."
              );
              btnLaddaAddColaborador.stop();
            },
          });
        }

        function agregarRegistro() {
          // Verificar si el registro ya existe en VEmasivo
          var registroExiste = VEmasivo.some(function (registro) {
            return (
              registro.empresaId === empresaId &&
              registro.gerenciaId === gerencia.id &&
              registro.departamentoId === departamento.id &&
              registro.areaId === area.id &&
              registro.seccionId === seccion.id &&
              registro.id_solicitante === colaborador.id &&
              registro.fecha_inicio === fechaInicio &&
              registro.fecha_fin === fechaFin
            );
          });

          if (registroExiste) {
            showConfirmWarning(
              "El registro ya existe en el listado de la solicitud."
            );
            btnLaddaAddColaborador.stop();
            return;
          }

          var nuevaInicioDate = new Date(
            fechaInicio.split("/").reverse().join("-")
          );
          var nuevaFinDate = new Date(fechaFin.split("/").reverse().join("-"));

          // Establecer hora a final del día para la fecha fin para incluir todo el día
          nuevaFinDate.setHours(23, 59, 59, 999);

          // Verificar si hay cruce de fechas para el mismo filtro (colaborador, sección, etc.)
          var fechaCruzada = VEmasivo.some(function (registro) {
            // Verificar si el nuevo registro está dentro de la jerarquía del registro existente o viceversa
            var mismoGrupo = false;
            
            // Caso 1: Si el nuevo registro es un colaborador específico
            if (colaborador.id) {
                // Verificar si pertenece a la misma sección/área/departamento/gerencia/empresa
                mismoGrupo = (
                    (registro.seccionId && registro.seccionId === seccion.id) ||
                    (registro.areaId && registro.areaId === area.id) ||
                    (registro.departamentoId && registro.departamentoId === departamento.id) ||
                    (registro.gerenciaId && registro.gerenciaId === gerencia.id) ||
                    (registro.empresaId && registro.empresaId === empresaId)
                );
            } 
            // Caso 2: Si el nuevo registro es para una sección
            else if (seccion.id) {
                // Verificar si el registro existente es para la misma sección o un colaborador de esta
                mismoGrupo = (
                    (registro.seccionId === seccion.id) ||
                    (registro.id_solicitante && (
                        (registro.seccionId === seccion.id) ||
                        (registro.areaId === area.id) ||
                        (registro.departamentoId === departamento.id) ||
                        (registro.gerenciaId === gerencia.id) ||
                        (registro.empresaId === empresaId)
                    ))
                );
            }
            // Continuar con la misma lógica para area, departamento, gerencia y empresa
            // ... (implementar casos similares para los demás niveles)
            
            if (!mismoGrupo) {
                return false; // No es el mismo grupo, no hay cruce
            }
        
            // Verificar cruce de fechas
            var regInicioStr = registro.fecha_inicio;
            var regFinStr = registro.fecha_fin;
        
            var regInicioDate = new Date(regInicioStr.split("/").reverse().join("-"));
            var regFinDate = new Date(regFinStr.split("/").reverse().join("-"));
        
            regFinDate.setHours(23, 59, 59, 999);
            return (nuevaFinDate >= regInicioDate && nuevaInicioDate <= regFinDate);
          });

          if (fechaCruzada) {
            showConfirmWarning(
              "Ya existe un registro con fechas que se cruzan para el mismo filtro seleccionado."
            );
            btnLaddaAddColaborador.stop();
            return;
          }

          // Determinar modalidad
          var modalidad;
          if (colaborador.id) {
            modalidad = 1;
          } else if (seccion.id) {
            modalidad = 2;
          } else if (area.id) {
            modalidad = 3;
          } else if (departamento.id) {
            modalidad = 4;
          } else if (gerencia.id) {
            modalidad = 5;
          } else if (empresaId) {
            modalidad = 6;
          } else {
            showConfirmWarning(
              "Debe seleccionar al menos una empresa para registrar vacaciones especiales."
            );
            btnLaddaAddColaborador.stop();
            return;
          }

          // Crear el objeto del registro
          var nuevoRegistro = {
            id: Date.now(),
            empresaId: empresaId,
            empresaNombre: empresaNombre,
            gerenciaId: gerencia.id,
            gerenciaNombre: gerencia.nombre,
            departamentoId: departamento.id,
            departamentoNombre: departamento.nombre,
            areaId: area.id,
            areaNombre: area.nombre,
            seccionId: seccion.id,
            seccionNombre: seccion.nombre,
            id_solicitante: colaborador.id,
            solicitanteNombre: colaborador.nombre,
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin,
            modalidad: modalidad,
            modalidadNombre: [
              "",
              "Usuario específico",
              "Sección",
              "Área",
              "Departamento",
              "Gerencia",
              "Toda la empresa",
            ][modalidad],
          };

          // Agregar el registro a VEmasivo
          VEmasivo.push(nuevoRegistro);

          // Recargar la tabla
          $("#tableListaVE").jtable("reload");

          // Mostrar mensaje de éxito
          showConfirmSuccess("Registro agregado correctamente");
          btnLaddaAddColaborador.stop();
        }
      } else {
        btnLaddaAddColaborador.stop();
      }
    });
    // Tabla visualización previo a registrar VE
    $("#tableListaVE").jtable({
      title: "Listado de Vacaciones Especiales",
      paging: true, // Habilitar paginación
      pageSize: 10, // Mostrar 5 registros por página
      sorting: false, // Deshabilitar ordenamiento
      actions: {
        listAction: function (postData, jtParams) {
          // Devolver los registros de VEmasivo
          return {
            Result: "OK",
            Records: VEmasivo,
            TotalRecordCount: VEmasivo.length,
          };
        },
      },
      fields: {
        fecha_inicio: {
          title: "F. Inicio",
          width: "5%",
        },
        fecha_fin: {
          title: "F. Fin",
          width: "5%",
        },
        empresa: {
          title: "Empresa",
          width: "12%",
          display: function (data) {
            return data.record.empresaNombre; // Mostrar el nombre de la empresa
          },
        },
        gerencia: {
          title: "Gerencia",
          width: "12%",
          display: function (data) {
            return data.record.gerenciaNombre; // Mostrar el nombre de la gerencia
          },
        },
        departamento: {
          title: "Departamento",
          width: "12%",
          display: function (data) {
            return data.record.departamentoNombre; // Mostrar el nombre del departamento
          },
        },
        area: {
          title: "Área",
          width: "12%",
          display: function (data) {
            return data.record.areaNombre; // Mostrar el nombre del área
          },
        },
        seccion: {
          title: "Sección",
          width: "12%",
          display: function (data) {
            return data.record.seccionNombre; // Mostrar el nombre de la sección
          },
        },
        id_solicitante: {
          title: "Solicitante",
          width: "15%",
          display: function (data) {
            return data.record.solicitanteNombre; // Mostrar el nombre del solicitante
          },
        },
        accion: {
          title: "Acción",
          width: "10%",
          display: function (data) {
            // Botón para eliminar el registro
            var btntmpVE = $(
              '<button class="btn btn-ac btn-xs" title="Eliminar" type="button"><i class="fa fa-trash"></i></button>'
            );
            btntmpVE.click(function () {
              // Eliminar el registro de VEmasivo usando el identificador único
              VEmasivo = VEmasivo.filter(function (registro) {
                return registro.id !== data.record.id; // Usar el identificador único
              });

              // Recargar la tabla
              $("#tableListaVE").jtable("reload");

              // Deshabilitar el botón de enviar si no hay registros
              if (VEmasivo.length === 0) {
                $("#btnSubmit").prop("disabled", true);
              }
            });
            return btntmpVE;
          },
        },

      },
    });

    // Evento para limpiar el arreglo y la tabla al cerrar el modal
    $("#modalForm").on("hidden.bs.modal", function () {
      VEmasivo = []; // Limpiar el arreglo
    $("#tableListaVE").jtable("reload"); // Recargar la tabla para reflejar los cambios
    
    // Habilitar los combos nuevamente
    $("#qryEmpresa").prop("disabled", false);
    $("#qryGerencia").prop("disabled", false);
    $("#qryDepartamento").prop("disabled", false);
    $("#qryArea").prop("disabled", false);
    $("#qrySeccion").prop("disabled", false);
      // VEmasivo = []; // Limpiar el arreglo
      // $("#tableListaVE").jtable("reload"); // Recargar la tabla para reflejar los cambios
    });

    $("#limpiarCampos").on("click", function () {
      // Limpiar selects
      $(
        "#qryGerencia, #qryDepartamento, #qryArea, #qrySeccion, #qryColaborador"
      ).val("");

      habilitarSelectores();
      // Si usas un selectpicker con búsqueda en vivo, recárgalo
      $(".selectpicker").selectpicker("refresh");
    });

    // Inicializar la tabla
    $("#tableListaVE").jtable("load");

    // Envio de datos al controller
    $("#frmModal").validate({
      ignore: "",
      rules: {
        qryEmpresa: { required: true },
        txtFechaInicio: { required: true },
        txtFechaFin: { required: true },
      },
      messages: {
        qryEmpresa: { required: "No ha seleccionado la empresa." },
        txtFechaInicio: { required: "Ingrese la fecha de inicio." },
        txtFechaFin: { required: "Ingrese la fecha de fin." },
      },
      invalidHandler: function (e, validator) {
        var mensaje = validator.errorList[0].message;
        showConfirmWarning(mensaje);
      },
      submitHandler: function (form) {
        var btnLaddaSubmit = Ladda.create(document.querySelector("#btnSubmit"));
        btnLaddaSubmit.start();

        // Verificar que haya registros para enviar
        if (VEmasivo.length === 0) {
          showConfirmWarning("No hay registros para procesar.");
          btnLaddaSubmit.stop();
          return;
        }
        // Crear un objeto con los registros
        var data = {
          registros: VEmasivo,
        };

        // Enviar los datos al servidor
        $.ajax({
          url: $(form).attr("action"),
          type: "POST",
          data: JSON.stringify(data), // Convertir a JSON
          contentType: "application/json", // Especificar el tipo de contenido
          success: function (data) {
            btnLaddaSubmit.stop();
            if (data.Result === "OK") {
              showConfirmSuccess(
                "Se procesó correctamente la solicitud de vacación especial."
              );
              $("#modalForm").bootstrapModal("hide");

              // Limpiar la tabla temporal
              VEmasivo = [];
              $("#tableListaVE").empty();

              // Recargar la tabla principal si existe
              if ($("#vacacionesespecialesContainer").length) {
                $("#vacacionesespecialesContainer").jtable("reload");
              }
            } else {
              // Manejar errores
              var mensaje = data.Message || "Error al procesar la solicitud";
              showConfirmError(mensaje);
            }
          },
          error: function (xhr, status, error) {
            btnLaddaSubmit.stop();
            showConfirmError("Ocurrió un error interno: " + error);
          },
        });
      },
    });

    // Cargar datos iniciales
    $("#qryEmpresa").change();
  }
  // Cargar datos iniciales
  $("#LoadRecordsButton").click();
  $("#qry_empresa").change();







  // Función para eliminar una vacación temporal
// function eliminarVacacionTemp(idVacacion) {
//   $.confirm({
//       theme: "warning",
//       icon: "fa fa-exclamation-triangle",
//       title: "¡Eliminar!",
//       content: "¿Está seguro de eliminar esta vacación especial?, esta acción no podrá ser revertida.",
//       confirm: function () {
//           loading(true, "Eliminando vacación temporal...");
//           $.post(
//               $getAppName + "/eliminarVacacionTemp/",
//               { id_vacacion_temp: idVacacion },
//               function (response) {
//                   loading(false);
//                   if (response.Result === "OK") {
//                       showConfirmSuccess("Vacación temporal eliminada correctamente");
//                       // Recargar la tabla de vacaciones temporales
//                       cargarTablaVacacionesTemp($("#id_vaca_especial").val());
//                   } else {
//                       showConfirmWarning(
//                           response.Message || "No se pudo eliminar la vacación temporal"
//                       );
//                   }
//               },
//               "json"
//           ).fail(function (xhr, status, error) {
//               console.error("Error al eliminar vacación temporal:", status, error);
//               loading(false);
//               showConfirmError("Ocurrió un error al eliminar la vacación temporal");
//           });
//       }
//   });
// }



$(document).ready(function() {
  $('.selectpicker').selectpicker(); // Inicializa todos los elementos con la clase 'selectpicker'
});

// Función para editar una vacación temporal
$(document).ready(function() {
  $('.selectpicker').selectpicker(); // Inicializa todos los selectpicker
});

// Función para editar una vacación temporal
function editarVacacionTemp(recordData) {
  console.log("editarVacacionTemp ejecutada con:", recordData);

  // Establecer fechas
  if (recordData.fecha_inicio?.date) {
      const fechaInicio = recordData.fecha_inicio.date.split(" ")[0].split("-").reverse().join("/");
      $("#txtFechaInicio").val(fechaInicio);
      $("#divFechaInicio").data("DateTimePicker").date(moment(fechaInicio, "DD/MM/YYYY"));
  }

  if (recordData.fecha_fin?.date) {
      const fechaFin = recordData.fecha_fin.date.split(" ")[0].split("-").reverse().join("/");
      $("#txtFechaFin").val(fechaFin);
      $("#divFechaFin").data("DateTimePicker").date(moment(fechaFin, "DD/MM/YYYY"));
  }

  // Establecer colaborador (mostrar select de edición, ocultar select de creación)
  $("#qryColaboradorCrear").hide();
  $("#qryColaboradorEditar").show();

  // Limpia las opciones existentes en el select de edición
  $("#qryColaboradorEditar").empty();

  // Agrega la opción del solicitante actual al select de edición y la selecciona
  if (recordData.id_solicitante && recordData.solicitante) {
      $("#qryColaboradorEditar").append(`<option value="${recordData.id_solicitante}" selected>${recordData.solicitante.trim()}</option>`);
  }

  // Refresca el selectpicker de edición
  $("#qryColaboradorEditar").selectpicker('refresh');

  // Guardar el ID
  $("#id_vacacion_temp").val(recordData.id_vacacion_temp);
  $("#addColaborador").text("Actualizar Vacación");

  // Scroll al formulario
  $('html, body').animate({
      scrollTop: $("#divFechaInicio").offset().top - 100
  }, 500);
}

$('#miModal').on('show.bs.modal', function (event) {
  const relatedTarget = $(event.relatedTarget);
  const isEditing = relatedTarget.data('id');

  if (isEditing) {
      // Modo Edición: Ocultar select de creación, mostrar select de edición
      $("#qryColaboradorCrear").hide();
      $("#qryColaboradorEditar").show();
      $("#qryColaboradorEditar").selectpicker('refresh'); // Refresca al mostrar
  } else {
      // Modo Creación: Mostrar select de creación, ocultar select de edición
      $("#qryColaboradorCrear").show();
      $("#qryColaboradorEditar").hide();
      $("#qryColaboradorCrear").selectpicker('refresh'); // Refresca al mostrar
      // Aquí podrías cargar las opciones para la creación si aún no lo has hecho
  }
});

$('#miModal').on('hidden.bs.modal', function () {
  $("#qryColaboradorCrear").hide();
  $("#qryColaboradorEditar").hide();
  // Opcional: Limpiar las opciones del select de edición al cerrar
  // $("#qryColaboradorEditar").empty().selectpicker('refresh');
});

// Función para obtener el valor del colaborador al guardar
function obtenerValorColaborador() {
  return $("#qryColaboradorEditar").is(":visible") ? $("#qryColaboradorEditar").val() : $("#qryColaboradorCrear").val();
}

// Evento para inicializar/refrescar el selectpicker cuando el modal se muestra
// $('#tuModal').on('shown.bs.modal', function (e) {
//   $("#qryColaborador").selectpicker('refresh');
// });


});
