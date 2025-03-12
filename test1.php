<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>INTRANET - Gestión de Solicitudes de Vacaciones</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <!-- Bootstrap y estilos principales -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.0/css/jquery-ui.min.css" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">

    <!-- Estilos personalizados -->
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 15px;
        }

        .main-container {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 0;
            overflow: hidden;
        }

        .header {
            background-color: #882132;
            color: white;
            padding: 10px 15px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }

        .form-horizontal .control-label {
            text-align: left;
        }

        .form-container {
            padding: 20px;
            background-color: rgba(246, 236, 236, 0.53); border-bottom: 1px solid #ddd;
        }

        .control-label {
            font-weight: bold;
            color: #882132;
            text-align: left;
            padding-bottom: 5px;
            display: block;
        }

        .form-control {
            height: 34px;
            padding: 6px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .btn-search {
            background-color: #5a5a5a;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 7px 12px;
        }

        .btn-export {
            background-color: #5a5a5a;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 7px 12px;
            margin-left: 5px;
        }

        .table-header {
            background-color: #882132;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header-title {
            margin: 0;
        }

        .btn-add {
            background-color: #882132;
            color: white;
            border: none;
            border-radius: 3px;
        }

        .grid-table {
            width: 100%;
            border-collapse: collapse;
        }

        .grid-table th {
            background-color: #f2f2f2;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            position: relative;
            white-space: nowrap;
        }

        .grid-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .sort-icon:after {
            content: "▼";
            font-size: 10px;
            margin-left: 5px;
        }

        .pagination-container {
            padding: 10px;
            background-color: #f9f9f9;
            border-top: 1px solid #ddd;
        }

        .no-data {
            text-align: center;
            padding: 15px;
        }

        /* Ajustes específicos para que coincida con la imagen */
        .form-group {
            margin-bottom: 15px;
        }

        .row {
            margin-bottom: 5px;
        }

        .input-group-addon {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
        }

        select.form-control {
            padding-right: 25px;
            appearance: menulist;
        }

        .btn-search,
        .btn-export {
            box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }

        .btn-add {
            box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>
    <div class="container main-container">
        <!-- Encabezado principal -->
        <div class="header">
            GESTIÓN DE FECHA ESPECIAL
        </div>

        <!-- Formulario de búsqueda -->
        <div class="form-container">
            <form class="form-horizontal">
                <div class="row">
                    <div class="col-md-6">
                        <label class="control-label">Empresa:</label>
                        <select class="form-control">
                            <option>LABORATORIOS AC FARMA</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="control-label">Gerencia:</label>
                        <select class="form-control">
                            <option>Todos</option>
                            <option>GERENCIA GENERAL</option>
                            <option>GERENCIA DE OPERACIONES</option>
                            <option>GERENCIA COMERCIAL INSTITUCIONAL</option>
                            <option>GERENCIA DE DIRECCION TECNICA</option>
                            <option>GERENCIA COMERCIAL PRIVADA</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="control-label">Departamento:</label>
                        <select class="form-control">
                            <option>Seleccione</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="control-label">Área:</label>
                        <select class="form-control">
                            <option>Seleccione</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="control-label">Sección:</label>
                        <select class="form-control">
                            <option>Seleccione</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="control-label">Solicitante:</label>
                        <input type="text" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="control-label">Fecha Inicio de la fecha especial:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="fechaInicio">
                            <span class="input-group-addon">Hasta</span>
                            <input type="text" class="form-control" id="fechaFin">
                        </div>
                    </div>
                    <div class="col-md-6 text-right" style="margin-top: 25px;">
                        <button type="button" class="btn btn-search">
                            <i class="glyphicon glyphicon-search"></i> Buscar
                        </button>
                        <button type="button" class="btn btn-export">
                            <i class="glyphicon glyphicon-export"></i> Exportar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla de datos -->
        <div class="table-container">
            <div class="table-header">
                <span class="table-header-title">Listado de Fechas Especiales</span>
                <button class="btn btn-add">
                    <i class="glyphicon glyphicon-plus"></i> Crear registro de fecha especial
                </button>
            </div>

            <table class="grid-table">
                <thead>
                    <tr>
                        <th>Id <span class="sort-icon"></span></th>
                        <th>F. Inicio <span class="sort-icon"></span></th>
                        <th>f. Fin <span class="sort-icon"></span></th>
                        <th>Empresa <span class="sort-icon"></span></th>
                        <th>Gerencia<span class="sort-icon"></span></th>
                        <th>Departamento<span class="sort-icon"></span></th>
                        <th>Área<span class="sort-icon"></span></th>
                        <th>Sección<span class="sort-icon"></span></th>
                        <th>Solicitante<span class="sort-icon"></span></th>
                        <th>Registrador<span class="sort-icon"></span></th>
                        <th>F. Registro<span class="sort-icon"></span></th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>2025-03-14</td>
                        <td>2025-03-14</td>
                        <td>AC FARMA</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>WILLIAM VILLENA</td>
                        <td>2025-03-10 13:53:24</td>
                        <td>
                            <button type="button" class="btn btn-search">
                                <i class="glyphicon glyphicon-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="pagination-container">
                <div class="row">
                    <div class="col-md-12">
                        Ir a página:
                        <select style="width: 60px;">
                            <option>1</option>
                        </select>
                        Registros por página:
                        <select style="width: 60px;">
                            <option>20</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.0/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializar datepickers
            $('#fechaInicio, #fechaFin').datepicker({
                dateFormat: 'dd/mm/yy',
                dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
                dayNamesMin: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                monthNamesShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                firstDay: 1
            });
        });
    </script>
    <!-- Asegúrate de que estos enlaces estén correctamente incluidos en el head -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" rel="stylesheet" type="text/css" />

    <!-- Script para inicializar correctamente el datepicker -->
    <script>
        $(document).ready(function() {
            // Reinicializar los datepickers con configuraciones explícitas
            $('#fechaInicio, #fechaFin').datepicker({
                dateFormat: 'dd/mm/yy',
                dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
                dayNamesMin: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                monthNamesShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                firstDay: 1,
                changeMonth: true,
                changeYear: true,
                showOtherMonths: true,
                selectOtherMonths: true,
                showAnim: "fadeIn",
                zIndex: 9999 // Asegura que esté por encima de otros elementos
            });

            // CSS personalizado para corregir problemas de visualización
            $("<style>")
                .prop("type", "text/css")
                .html(`
            .ui-datepicker {
                background-color: white !important;
                border: 1px solid #ddd !important;
                color: #333 !important;
                box-shadow: 0 0 10px rgba(0,0,0,0.2);
                font-size: 13px;
            }
            .ui-datepicker-header {
                background-color: #882132 !important;
                color: white !important;
                border: none !important;
            }
            .ui-datepicker th {
                background-color: #f5f5f5 !important;
                color: #555 !important;
            }
            .ui-datepicker .ui-state-default {
                background: white !important;
                border: 1px solid #ddd !important;
                color: #555 !important;
            }
            .ui-datepicker .ui-state-hover {
                background: #f0f0f0 !important;
                border: 1px solid #ccc !important;
                color: #212121 !important;
            }
            .ui-datepicker .ui-state-active {
                background: #882132 !important;
                color: white !important;
            }
        `)
                .appendTo("head");
        });
    </script>

    <!-- Modal para Crear Fecha Especial -->
    <div class="modal fade" id="modalFechaEspecial" tabindex="-1" role="dialog" aria-labelledby="modalFechaEspecialLabel">
        <div class="modal-dialog modal-lg" role="document" style="width: 1200px;">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #882132; color: white;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modalFechaEspecialLabel">Crear Registro de Fecha Especial</h4>
                </div>
                <div class="modal-body">
                    <form id="formFechaEspecial">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Empresa:</label>
                                    <select class="form-control" id="empresaModal" required>
                                        <option value="LABORATORIOS AC FARMA">LABORATORIOS AC FARMA</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Gerencia:</label>
                                    <select class="form-control" id="gerenciaModal">
                                        <option value="">Seleccione</option>
                                        <option>GERENCIA GENERAL</option>
                                        <option>GERENCIA DE OPERACIONES</option>
                                        <option>GERENCIA COMERCIAL INSTITUCIONAL</option>
                                        <option>GERENCIA DE DIRECCION TECNICA</option>
                                        <option>GERENCIA COMERCIAL PRIVADA</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Departamento:</label>
                                    <select class="form-control" id="departamentoModal">
                                        <option value="">Seleccione</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Área:</label>
                                    <select class="form-control" id="areaModal">
                                        <option value="">Seleccione</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Sección:</label>
                                    <select class="form-control" id="seccionModal">
                                        <option value="">Seleccione</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Solicitante:</label>
                                    <input type="text" class="form-control" id="solicitanteModal">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Fecha Inicio:</label>
                                    <input type="text" class="form-control fechaModal" id="fechaInicioModal" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Fecha Fin:</label>
                                    <input type="text" class="form-control fechaModal" id="fechaFinModal" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-search" id="btnAgregarFila">
                                    <i class="glyphicon glyphicon-plus"></i> Agregar
                                </button>
                            </div>
                        </div>

                        <!-- Tabla temporal de fechas agregadas -->
                        <div class="row" style="margin-top: 15px;">
                            <div class="col-md-12">
                                <table class="table table-bordered" id="tablaFechasTemporales">
                                    <thead style="background-color: #f2f2f2;">
                                        <tr>
                                            <th>ID</th>
                                            <th>F. Inicio</th>
                                            <th>F. Fin</th>
                                            <th>Empresa</th>
                                            <th>Gerencia</th>
                                            <th>Departamento</th>
                                            <th>Área</th>
                                            <th>Sección</th>
                                            <th>Solicitante</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>2</td>
                                            <td>2025-03-18</td>
                                            <td>2025-03-18</td>
                                            <td>AC FARMA</td>
                                            <td>GRENCIA GENERAL</td>
                                            <td>GERENCIA GENERAL</td>
                                            <td>TECNOLOGIAS DE LA INFORMACION</td>
                                            <td></td>
                                            <td></td>
                                            <td>
                                                <button type="button" class="btn btn-search">
                                                    <i class="glyphicon glyphicon-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <!-- <div id="sinDatos" class="text-center" style="padding: 20px; display: block;">
                                    No hay fechas especiales agregadas
                                </div> -->
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnGuardarTodo" style="background-color: #882132; border-color: #882132;">Crear Registro</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script para el funcionamiento del modal -->
    <script>
        $(document).ready(function() {
            // Variable para almacenar las fechas temporales
            let fechasTemporales = [];

            // Inicializar datepickers en el modal
            $('.fechaModal').datepicker({
                dateFormat: 'dd/mm/yy',
                dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
                dayNamesMin: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                monthNamesShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                firstDay: 1,
                changeMonth: true,
                changeYear: true,
                minDate: 0, // Impedir fechas anteriores a la actual
                onSelect: function(dateText) {
                    validarFechas();
                }
            });

            // Abrir modal al hacer clic en el botón de crear
            $('.btn-add').click(function() {
                resetearModal();
                $('#modalFechaEspecial').modal('show');
            });

            // Validar fechas
            // function validarFechas() {
            //     let fechaInicio = $('#fechaInicioModal').datepicker('getDate');
            //     let fechaFin = $('#fechaFinModal').datepicker('getDate');

            //     if (fechaInicio && fechaFin) {
            //         if (fechaInicio > fechaFin) {
            //             alert('La fecha de inicio no puede ser posterior a la fecha fin');
            //             $('#fechaFinModal').val('');
            //             return false;
            //         }
            //     }
            //     return true;
            // }

            // Agregar fila a la tabla temporal
            // $('#btnAgregarFila').click(function() {
            //     if (!validarFormulario()) return;

            //     let empresa = $('#empresaModal').val();
            //     let gerencia = $('#gerenciaModal').val() || '(No especificado)';
            //     let departamento = $('#departamentoModal').val() || '(No especificado)';
            //     let area = $('#areaModal').val() || '(No especificado)';
            //     let seccion = $('#seccionModal').val() || '(No especificado)';
            //     let solicitante = $('#solicitanteModal').val() || '(No especificado)';
            //     let fechaInicio = $('#fechaInicioModal').val();
            //     let fechaFin = $('#fechaFinModal').val();

            //     // Crear objeto para almacenar
            //     let nuevaFecha = {
            //         id: Date.now(), // ID temporal
            //         empresa: empresa,
            //         gerencia: gerencia,
            //         departamento: departamento,
            //         area: area,
            //         seccion: seccion,
            //         solicitante: solicitante,
            //         fechaInicio: fechaInicio,
            //         fechaFin: fechaFin
            //     };

            //     // Añadir a la matriz
            //     fechasTemporales.push(nuevaFecha);

            //     // Actualizar tabla
            //     actualizarTablaTemporal();

            //     // Limpiar campos excepto empresa
            //     limpiarCamposModal();
            // });

            // Función para validar el formulario
            // function validarFormulario() {
            //     if (!$('#empresaModal').val()) {
            //         alert('Debe seleccionar una empresa');
            //         return false;
            //     }

            //     if (!$('#fechaInicioModal').val()) {
            //         alert('Debe ingresar una fecha de inicio');
            //         return false;
            //     }

            //     if (!$('#fechaFinModal').val()) {
            //         alert('Debe ingresar una fecha de fin');
            //         return false;
            //     }

            //     return validarFechas();
            // }

            // Función para actualizar la tabla temporal
            // function actualizarTablaTemporal() {
            //     let tbody = $('#tablaFechasTemporales tbody');
            //     tbody.empty();

            //     if (fechasTemporales.length === 0) {
            //         $('#sinDatos').show();
            //         $('#btnGuardarTodo').prop('disabled', true);
            //     } else {
            //         $('#sinDatos').hide();
            //         $('#btnGuardarTodo').prop('disabled', false);

            //         fechasTemporales.forEach(function(fecha) {
            //             let fila = `
            //     <tr data-id="${fecha.id}">
            //         <td>${fecha.empresa}</td>
            //         <td>${fecha.gerencia}</td>
            //         <td>${fecha.fechaInicio}</td>
            //         <td>${fecha.fechaFin}</td>
            //         <td>
            //             <button type="button" class="btn btn-danger btn-eliminar-fila" data-id="${fecha.id}">
            //                 <i class="glyphicon glyphicon-trash"></i>
            //             </button>
            //         </td>
            //     </tr>`;
            //             tbody.append(fila);
            //         });
            //     }

            //     // Reinicializar eventos para los botones de eliminar
            //     $('.btn-eliminar-fila').off('click').on('click', function() {
            //         let id = $(this).data('id');
            //         eliminarFila(id);
            //     });
            // }

            // Función para eliminar una fila
            // function eliminarFila(id) {
            //     fechasTemporales = fechasTemporales.filter(fecha => fecha.id !== id);
            //     actualizarTablaTemporal();
            // }

            // Función para limpiar campos del modal
            function limpiarCamposModal() {
                $('#gerenciaModal').val('');
                $('#departamentoModal').val('');
                $('#areaModal').val('');
                $('#seccionModal').val('');
                $('#solicitanteModal').val('');
                $('#fechaInicioModal').val('');
                $('#fechaFinModal').val('');
            }

            // Función para resetear completamente el modal
            function resetearModal() {
                limpiarCamposModal();
                fechasTemporales = [];
                // actualizarTablaTemporal();
            }

            // Guardar todos los registros
            // $('#btnGuardarTodo').click(function() {
            //     if (fechasTemporales.length === 0) {
            //         alert('No hay fechas especiales para guardar');
            //         return;
            //     }

            //     // Aquí iría el código para enviar los datos al servidor
            //     console.log('Guardando fechas especiales:', fechasTemporales);

            //     // Simular éxito
            //     alert('Fechas especiales guardadas correctamente');
            //     $('#modalFechaEspecial').modal('hide');

            //     // Recargar la tabla principal (simulado)
            //     // location.reload();
            // });
        });
    </script>
</body>

</html>

<!-- https://excalidraw.com/#json=m4NukUJ7WjQOyu7VevcS_,f_7aDKutaifNsZG1otAR8w -->