<!DOCTYPE html>

<head>
    <meta charset="utf-8">
    <base href="<?php echo $protocol . $_SERVER['HTTP_HOST'] . '/' . $rootFolder; ?>" />
    <title>INTRANET</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="keywords" content="Intranet" />
    <meta name="Description" content="Intranet " />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <!--**************** style *****************-->
    <link href="assets/browser-components/jquery-ui/css/jquery-ui-ac.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/browser-components/jquery-jtable/themes/lightcolor/ac/jtable.css" rel="stylesheet"
        type="text/css" />
    <link href="assets/browser-components/jquery_confirm/jquery-confirm.min.css" rel="stylesheet">
    <link href="assets/browser-components/ladda/css/ladda-themeless.min.css" rel="stylesheet" type="text/css" />
    <!--**************** style *****************-->

    <!--*************** script *****************-->
    <script type="text/javascript">
        //Prevent Duplicate jquery and ui
        if (parent.document.getElementById('panel-body-main') == null) {
            console.log('Se añade el script en la cabecera');
            document.write('<link href="assets/css/style.min.css" rel="stylesheet" type="text/css" />');
            document.write('<link href="assets/browser-components/bootstrap/css/bootstrap.css" rel="stylesheet">');
            document.write('<script type="text/javascript" src="assets/js/jquery-1.11.3.min.js"></' + 'script>');
            document.write('<script type="text/javascript" src="assets/browser-components/jquery-ui/js/jquery-ui-1.10.0.min.js"></' + 'script>');
            document.write('<script type="text/javascript" src="assets/browser-components/bootstrap/js/bootstrap.js"></' + 'script>');
            document.write('<script src="assets/browser-components/jquery_confirm/jquery-confirm.min.js"></' + 'script>');
            document.write('<script type="text/javascript" src="assets/js/main.js"></' + 'script>');
        }
    </script>
    <script src="assets/browser-components/jquery_confirm/jquery-confirm.min.js"></script>

    <script type="text/javascript" src="assets/browser-components/ladda/js/spin.min.js"></script>
    <script type="text/javascript" src="assets/browser-components/ladda/js/ladda.min.js"></script>
    <script type="text/javascript" src="assets/browser-components/mustache/mustache.min.js"></script>
    <!-- bootstrap select -->
    <link href="assets/browser-components/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet"
        type="text/css" />
    <script type="text/javascript" src="assets/browser-components/bootstrap-select/js/bootstrap-select.min.js"></script>
    <!-- date range -->
    <link href="assets/browser-components/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="assets/browser-components/daterangepicker/daterangepicker.js"></script>
    <!-- Loading -->
    <script type="text/javascript" src="assets/browser-components/loadingModal/js/jquery.loadingModal.js"></script>
    <link rel="stylesheet" type="text/css" href="assets/browser-components/loadingModal/css/jquery.loadingModal.css">
    <!-- ajax-select -->
    <link href="assets/browser-components/ajax-select/css/ajax-bootstrap-select.min.css" rel="stylesheet"
        type="text/css" />
    <script type="text/javascript" src="assets/browser-components/ajax-select/js/ajax-bootstrap-select.min.js"></script>
    <script type="text/javascript"
        src="assets/browser-components/ajax-select/js/locale/ajax-bootstrap-select.es-ES.min.js"></script>

    <!-- Data Table -->
    <link href="assets/browser-components/dataTables/DataTables-1.10.15/css/dataTables.bootstrap.min.css"
        rel="stylesheet" type="text/css" />
    <script src="assets/browser-components/dataTables/DataTables-1.10.15/js/jquery.dataTables.min.js"
        type="text/javascript"></script>

    <!-- Validate -->
    <script type="text/javascript" src="assets/browser-components/validate/jquery.form.min.js"></script>
    <script type="text/javascript" src="assets/browser-components/validate/jquery.validate.min.js"></script>
    <!-- jquery.jtable -->
    <script type="text/javascript" src="assets/browser-components/jquery-jtable/jquery.jtable.min.js"></script>
    <script type="text/javascript"
        src="assets/browser-components/jquery-jtable/localization/jquery.jtable.es.js"></script>
    <script type="text/javascript" src="assets/js/config.js"></script>
    <!--*************** script *****************-->
    <!-- <script type="text/javascript" src="assets/browser-components/bootstrap/js/bootstrap.js"></script> -->
    <script type="text/javascript">
        var $getAppName = '<?php echo $obj->getAppName(); ?>';
        var maxDayToEdit = <?= $maximoDiasReprogramar; ?>;
    </script>
    <script type="text/javascript" src="assets/js/form-main.js"></script>
    <script type="text/javascript" src="assets/js/vacacion/main.js"></script>
    <style type="text/css">
        .daterangepicker td.active,
        .daterangepicker td.active:hover {
            background-color: #882132;
        }

        .daterangepicker td.active.in-range {
            background-color: #882132;
        }

        .daterangepicker td.in-range {
            background-color: #f8ebeb;
        }

        .weekend {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="panel panel-ac" style="margin-bottom:0px;">
                <div class="panel-heading" id="panel_body_title">
                    <h3 class="panel-title text-center">Gestión de Solicitudes de vacaciones</h3>
                </div>
            </div>
            <div class="filtering">
                <form class="">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="control-label">Empresa:</label>
                            <select name="qry_empresa" id="qry_empresa" class="form-control select-default-ac">
                                <?php foreach ($cboEmpresa as $row): ?>
                                    <option value="<?= $row->CO_EMPR; ?>"><?= $row->DE_NOMB; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="control-label">Gerencia:</label>
                            <select name="qry_gerencia" id="qry_gerencia" class="form-control">
                                <option value="0"> Seleccione </option>
                            </select>
                        </div>
                        <div class="col-sm-6 form-group">
                            <label class="control-label">Departamento:</label>
                            <select name="qry_departamento" id="qry_departamento" class="form-control">
                                <option value="0"> Seleccione </option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class=" control-label">Área:</label>
                            <select name="qry_area" id="qry_area" class="form-control">
                                <option value="0"> Seleccione </option>
                            </select>
                        </div>
                        <div class="col-sm-6 form-group">
                            <label class="control-label">Sección:</label>
                            <select name="qry_seccion" id="qry_seccion" class="form-control buscar">
                                <option value="0"> Seleccione </option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="control-label">Solicitante:</label>
                            <input align="center" type="text" id="qry_colaborador" class="form-control">
                        </div>
                        <div class="col-sm-6 form-group">
                            <label class="control-label">Fecha Inicio de Vacaciones:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="qryFechaInicio" autocomplete="off">
                                <span class="input-group-addon">Hasta</span>
                                <input type="text" class="form-control" id="qryFechaFin" autocomplete="off">
                            </div>
                        </div>
                        <!-- <div class="col-md-6 form-group">
                            <label class="control-label">Rango Inicio Vacaciones:</label>
                            <div id="reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%; margin-bottom: 5px;">
                                <i class="fa fa-calendar"></i>&nbsp;
                                <span></span> <i class="fa fa-caret-down pull-right"></i>
                            </div>
                        </div> -->
                        <div class="col-md-6 groupTogether">
                            <button type="submit" id="LoadRecordsButton" class="btn btn-ac"><i
                                    class="glyphicon glyphicon-search"></i> Buscar</button>
                            <button type="button" id="btnExportar" class="btn btn-ac"><i
                                    class="glyphicon glyphicon-cloud-download"></i> Exportar</button>
                        </div>
                    </div>
                </form>
            </div>
            <div id="vacacionesContainer"></div>
        </div>
    </div>

    <!-- Modal Gestion de Formulario -->
    <div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-labelledby="modalFrameLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

    <script id="tplFrmModal" type="x-tmpl-mustache">
        <form class="form-horizontal" method="post" action="{{action}}" id="frmModal" role="form">
            <div class="modal-header text-center">
                <button type="button" class="close" id="closePanel" data-dismiss="modal" aria-hidden="true">&times;       </button>
                <h4 class="modal-title" id="modalFrameLabel">Administración de Vacaciones</h4>
            </div>
            <div class="modal-body" id="modalBoletaBody">
                <div class="row">
                    {{#generador}}
                        {{#edit}}
                            <div class="col-md-12">
                                <div class="alert alert-success" role="alert">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <label class="col-md-5">Empresa:</label>
                                            <label class="col-md-7">{{val_empresa}}</label>
                                        </div>
                                        <div class="col-md-7">
                                            <label class="col-md-5">Gerencia Central:</label>
                                            <label class="col-md-7">{{val_gerencia}}</label>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="col-md-2">Área:</label>
                                            <label class="col-md-10">{{val_area}}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        {{/edit}}
                    {{/generador}}
                    {{^generador}}
                        <div class="col-md-12">
                            <div class="alert alert-success" role="alert">
                                <div class="row">
                                    <div class="col-md-5">
                                        <label class="col-md-5">Empresa:</label>
                                        <label class="col-md-7">{{val_empresa}}</label>
                                    </div>
                                    <div class="col-md-7">
                                        <label class="col-md-5">Gerencia Central:</label>
                                        <label class="col-md-7">{{val_gerencia}}</label>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="col-md-2">Area:</label>
                                        <label class="col-md-10">{{val_area}}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    {{/generador}}

                    {{#generador}}
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="col-md-2 control-label">Solicitante:</label>
                                <div class="col-md-10">
                                    {{#edit}}
                                        <label style="padding-top: 7px;">{{val_nombre_solicitante}}</label>
                                        <input type="hidden" name="cboSolicitante" id="cboSolicitante" value="{{val_id_solicitante}}" />
                                    {{/edit}}
                                    {{^edit}}
                                        <select name="cboSolicitante" id="cboSolicitante" class="form-control selectpicker with-ajax" data-live-search="true">
                                        </select>
                                    {{/edit}}
                                </div>
                            </div>
                        </div>
                    {{/generador}}
                    {{^generador}}
                        <input type="hidden" name="cboSolicitante" id="cboSolicitante" value="{{val_id_solicitante}}" />
                    {{/generador}}

                    <div class="col-md-4">
                        <div class="form-group groupTogether">
                            <label class="col-md-6 control-label">Fec. Ingreso:</label>
                            <div class="col-md-6">
                                <input class="form-control" type="text" name="txtFechaIngreso" id="txtFechaIngreso" value="{{val_fecha_ingreso}}" readonly="readonly" />
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="col-md-2 control-label">Condición:</label>
                            <div class="col-md-10">
                                <select class="form-control" name="cboCondicion" id="cboCondicion">
                                    {{#listCondicion}}
                                        <option value='{{id_vaca_condicion}}' {{sel_condicion}} >{{vaca_condicion}}</option>
                                    {{/listCondicion}}
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-8 control-label">Cantidad Dias:</label>
                            <div class="col-md-4">
                                <input class="form-control" type="text" name="txtCantidadDias" id="txtCantidadDias" value="{{val_dias}}" readonly="readonly"/>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Fch. Inicio:</label>
                            <div class="col-md-8">
                                <div class='input-group' id="divFechaInicio">
                                    <input class="form-control" type="text" name="txtFechaInicio" id="txtFechaInicio" value="{{val_fecha_inicio}}" readonly="readonly"/>
                                    <span class="input-group-addon">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Fch. Fin:</label>
                            <div class="col-md-8">
                                <div class='input-group' id="divFechaFin">
                                    <input class="form-control" type="text" name="txtFechaFin" id="txtFechaFin" value="{{val_fecha_fin}}" readonly="readonly"/>
                                    <span class="input-group-addon">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="alert alert-info ac-alert-info" role="alert">
                            <i class="glyphicon glyphicon-question-sign"></i> Fecha de Inicio y fin de Vacación (no incluir fecha de reingreso)
                        </div>
                    </div>

                    <!-- <div class="col-md-8">
                        <div class="form-group">
                            <label class="col-md-5 control-label">Rango de fecha de vacaciones:</label>
                            <div class="col-md-7">
                                <div id="txtRango" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%; margin-bottom: 5px;">
                                    <i class="fa fa-calendar"></i>&nbsp;
                                    <span></span> <i class="fa fa-caret-down pull-right"></i>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="alert alert-info ac-alert-info" role="alert">
                                    <i class="glyphicon glyphicon-question-sign"></i> Fecha de Inicio y fin de Vacación (no incluir fecha de reingreso)
                                </div>
                            </div>
                        </div>
                    </div> -->
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div id="consolidadoContainer">
                            <table id="tblConsolidado" class="table datatable table-striped table-bordered table-hover toggle-arrow-tiny display nowrap" cellspacing="0" width="100%"></table>
                            <div class="alert alert-info ac-alert-info" role="alert">
                                <i class="glyphicon glyphicon-question-sign"></i> <b>"Por programar"</b> se calcula:
                                <ul>
                                    <li>Uso de Vacaciones Ganadas: (Pendientes + Vencidos) - Programados </li>
                                    <li>Adelanto a cuenta de vacaciones Truncas: Truncas - Programados </li>
                                </ul>
                            </div>
                            <table id="tbldetalleconsolidado" class="table datatable table-striped table-bordered table-hover toggle-arrow-tiny display nowrap" cellspacing="0" width="100%">
                                <caption style="caption-side: top; font-size: 1.2em; font-weight: bold;">Detalle de vacaciones por periodo</caption>
                            </table>
                            <input type="hidden" name="programadosPrevios" id="programadosPrevios"/>
                            {{#edit}}
                                <div class="alert alert-info ac-alert-info" role="alert">
                                    <i class="glyphicon glyphicon-question-sign"></i> La cantidad de dias a programar, incluye los días de la solicitud
                                </div>
                            {{/edit}}
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background-color: #f3f3f4;">
                <input type="hidden" name="idSolicitud" id="idSolicitud" value="{{val_id_vacacion}}" />
                <button type="button" class="btn btn-danger" id="btnReturn" data-dismiss="modal">
                    <span class="ladda-label"><i class="glyphicon glyphicon-remove-circle" aria-hidden="true"></i>  Cerrar</span>
                </button>
                <button type="submit" data-style="zoom-in" class="btn btn-ac ladda-button" id="btnSubmit"> 
                    <span class="ladda-label"><i class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></i> {{val_button}}</span>
                </button>
            </div>
        </form>
    </script>
</body>

</html>