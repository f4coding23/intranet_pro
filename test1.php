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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
            background-color:rgba(246, 236, 236, 0.53)
            border-bottom: 1px solid #ddd;
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
        
        .btn-search, .btn-export {
            box-shadow: 1px 1px 3px rgba(0,0,0,0.2);
        }
        
        .btn-add {
            box-shadow: 1px 1px 3px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <!-- Encabezado principal -->
        <div class="header">
            REGISTRO DE FECHAS ESPECIALES
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
                            <option>Seleccione</option>
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
                        <label class="control-label">Fecha Inicio de Vacaciones:</label>
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
                <span class="table-header-title">Listado de Registro de Fechas Especiales</span>
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
</body>
</html>