@extends('layouts.main')
<link href="{{ asset('public/css/app.css') }}" rel="stylesheet">

<style>
    /* Estilos para el botón expandir */
    .btn-expandir {
        display: inline-block;
        width: 24px;
        height: 24px;
        line-height: 24px;
        text-align: center;
        cursor: pointer;
    }

    .btn-expandir i {
        color: #21A6A4;
    }

    .btn-expandir:hover i {
        color: #1a8a88;
    }

    /* Estilos para el botón de gráfico */
    .btn-grafico i {
        color: #9B59B6;
    }

    .btn-grafico:hover i {
        color: #8e44ad;
    }

    /* Estilos para la fila de detalle */
    .datagrid-row-detail {
        background-color: #f9f9f9;
    }

    .datagrid-row-detail-content {
        padding: 10px;
    }
    
    /* Estilos para modal */
    .modal-header {
        background-color: #f5f5f5;
        padding: 10px;
        border-bottom: 1px solid #ddd;
    }
    
    .modal-body {
        padding: 15px;
    }
    
    .modal-footer {
        background-color: #f5f5f5;
        padding: 10px;
        border-top: 1px solid #ddd;
    }
    
    /* Tabla detalle datos */
    .table-detalle {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table-detalle th, .table-detalle td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    .table-detalle th {
        background-color: #f5f5f5;
    }
</style>

@section('javascript')
<script type="text/javascript">
    var myDate = new Date();
    var url_consulta = "{{ url('controlcalidad/procesos/analisistendencia/consultar') }}";
    var url_mantenimiento = "{{ url('controlcalidad/procesos/analisistendencia/mantenimiento') }}";    
    var usuarID = "{{ $usuarioid }}";
    var ambiente = @json($ambiente);
    
    var firstday_month = myDate.getFullYear().toString() + '-' + (myDate.getMonth() < 9 ? '0' + (myDate.getMonth() + 1).toString() : (myDate.getMonth() + 1).toString()) + '-' + '01';
    var hoy = myDate.getFullYear().toString() + '-' + (myDate.getMonth() < 9 ? '0' + (myDate.getMonth() + 1).toString() : (myDate.getMonth() + 1).toString()) + '-' + myDate.getDate().toString();
    
    $(document).ready(function () {
        var iconolimpieza = [{
            iconCls: 'icon-clear',
            handler: function (e) {
                var tipodato = e.data.target.id.split('_');
                switch (tipodato[0]) {
                    case 'cmb':
                        $(e.data.target).combobox('clear');
                        $(e.data.target).combobox('textbox').focus();
                        break;
                    case 'dt':
                        $(e.data.target).datebox('clear');
                        $(e.data.target).datebox('textbox').focus();
                        break;
                }
                listar_datagrid();
            }
        }];
        
        // FILTROS
        $('#cmb_producto').combobox({ 
            icons: iconolimpieza,
            width: '30%',
            height: 20,
            labelWidth: 120,
            labelAlign: 'right',
            label: 'Producto',
            prompt: 'Buscar producto',
            panelHeight: 'auto',
            panelMaxHeight: 200,
            data: @json($productos),
            valueField: 'value',
            textField: 'text',
            filter: function(q, row) {
                return row.text.toLowerCase().indexOf(q.toLowerCase()) >= 0 || 
                       row.value.toLowerCase().indexOf(q.toLowerCase()) >= 0;
            }
        });

        $('#dt_fecha_desde').datebox({
            icons: iconolimpieza,
            width: '255px',
            height: 25,
            labelWidth: 120,
            label: 'Periodo análisis',
            labelAlign: 'right',
            parser: new_parser_date,
            formatter: new_formatter_date,
            height: 20,
            prompt: 'desde'
        });

        $('#dt_fecha_fin').datebox({
            icons: iconolimpieza,
            width: '135px',
            height: 25,
            labelAlign: 'right',
            parser: new_parser_date,
            formatter: new_formatter_date,
            height: 20,
            prompt: 'hasta',        
            value: hoy
        });
        
        // DATAGRID PRINCIPAL
        $('#dg_001').datagrid({
            url: url_consulta,
            fitColumns: false,
            singleSelect: true,
            rownumbers: true,
            pagination: true,
            pageSize: 50,
            striped: true,
            fit: true,
            nowrap: false,
            border: false,
            loadMsg: 'Cargando por favor espere...',
            queryParams: {
                _token: '{{ csrf_token() }}',
                _acc: 'listarPrincipal'
            },
            toolbar: '#tb_dg_001',
            columns: [[
                { field: 'cod_producto_mae', title: 'Código Producto', align: 'center', halign: 'center', width: 150 },
                { field: 'nom_producto_mae', title: 'Nombre Producto', align: 'left', halign: 'center', width: 400 },
                { field: 'total_lotes', title: 'Cantidad Lotes', align: 'center', halign: 'center', width: 120 },
                { field: 'lotes_aprobados', title: 'Lotes Aprobados', align: 'center', halign: 'center', width: 120 },
                { field: 'lotes_desaprobados', title: 'Lotes Desaprobados', align: 'center', halign: 'center', width: 120 }
            ]],
            view: detailview,
            detailFormatter: function(index, row) {
                return '<div style="padding:10px;"><table id="detalle-grid-' + row.cod_producto_mae + '"></table></div>';
            },
            onExpandRow: function(index, row) {
                inicializarTablaDetalles(row.cod_producto_mae, index);
            },
            onLoadSuccess: function(data) {
                console.log("Datos cargados en tabla principal:", data);
            },
            onLoadError: function (XMLHttpRequest, textStatus, errorThrown) {
                $.messager.alert('Error', 'Error al mostrar los datos: ' + errorThrown, 'error');
            }
        }).datagrid('getPager').pagination({
            beforePageText: 'Pag. ',
            afterPageText: 'de {pages}',
            displayMsg: 'Del {from} al {to}, de {total} items.'
        });
    });

    // Función para inicializar la tabla de detalles
    
    // Función para mostrar modal de detalles de lotes
    // Función para mostrar modal de detalles de lotes
    // Función para mostrar modal de detalles de lotes
    function mostrarDetallesLotes(tipoLote, codProductoMae, codInspeccion) {
    // Primero, eliminar cualquier modal existente
    if ($('#modal-detalles-lotes').length) {
        $('#modal-detalles-lotes').remove();
    }
    
    // Obtener el nombre del producto y el nombre de la inspección mediante AJAX
    $.ajax({
        url: url_consulta,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            _acc: 'obtenerInfoDetalle',
            cod_producto_mae: codProductoMae,
            cod_insp_mae: codInspeccion
        },
        success: function(response) {
            // Crear estructura del modal con la información adicional
            let modalHtml = `
            <div id="modal-detalles-lotes" style="width:900px;height:500px;padding:10px;">
                <div class="easyui-layout" data-options="fit:true">
                    <div data-options="region:'center'" style="padding:10px;">
                        <div class="info-header" style="background-color:#f5f5f5;padding:10px;margin-bottom:15px;border:1px solid #ddd;border-radius:4px;">
                            <h3 style="margin-top:0;color:#333;">${tipoLote}</h3>
                            <table class="table-detalle" style="width:100%;margin-bottom:15px;">
                                <tr>
                                    <th style="width:20%;">Código Producto:</th>
                                    <td style="width:30%;">${codProductoMae}</td>
                                    <th style="width:20%;">Nombre Producto:</th>
                                    <td style="width:30%;">${response.nombreProducto || '-'}</td>
                                </tr>
                                <tr>
                                    <th>Código Inspección:</th>
                                    <td>${codInspeccion}</td>
                                    <th>Nombre Inspección:</th>
                                    <td>${response.nombreInspeccion || '-'}</td>
                                </tr>
                                <tr>
                                    <th>Rango de Fechas:</th>
                                    <td colspan="3">Desde ${$('#dt_fecha_desde').datebox('getValue') || '-'} hasta ${$('#dt_fecha_fin').datebox('getValue') || '-'}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <table id="grid-detalles-lotes"></table>
                    </div>
                    <div data-options="region:'south',border:false" style="height:40px;text-align:center;padding:5px;">
                        <a href="javascript:void(0)" class="easyui-linkbutton" onclick="cerrarModalLotes()">Cerrar</a>
                    </div>
                </div>
            </div>`;
            
            // Agregar modal al cuerpo
            $('body').append(modalHtml);

            // Inicializar ventana
            $('#modal-detalles-lotes').window({
                title: `Detalles de ${tipoLote}`,
                modal: true,
                collapsible: false,
                minimizable: false,
                maximizable: true,
                closable: true,
                onClose: function() {
                    $(this).window('destroy');
                }
            });

            // Inicializar grid de detalles (el resto del código permanece igual)
            $('#grid-detalles-lotes').datagrid({
                columns:[[
                    {field:'num_lote', title:'Número Lote', width:80, align:'center'},
                    {field:'fec_ven_lote', title:'Vencimiento Lote', width:100, align:'center'},
                    {field:'valoracion', title:'Valoración', width:70, align:'center', 
                        formatter: function(value){
                            if (value === 'A') return 'Aprobado';
                            if (value === 'R') return 'Rechazado';
                            return value;
                        }
                    },
                    {field:'resultado', title:'Resultado', width:80, align:'center'},
                    {field:'media', title:'Media', width:80, align:'center'},
                    {field:'texto_breve', title:'Texto Breve', width:150, align:'left'},
                    {field:'fec_ini_insp', title:'Inicio Insp.', width:100, align:'center'},
                    {field:'fec_fin_insp', title:'Fin Insp.', width:100, align:'center'}
                ]],
                fitColumns: true,
                singleSelect: true,
                pagination: true,
                pageSize: 10,
                rownumbers: true,
                loadMsg: 'Cargando datos...',
                emptyMsg: 'No hay datos disponibles',
                url: url_consulta,
                method: 'post',
                queryParams: {
                    _token: '{{ csrf_token() }}',
                    _acc: tipoLote === 'Lotes Aprobados' ? 'listarLotesAprobados' : 'listarLotesDesaprobados',
                    cod_producto_mae: codProductoMae,
                    cod_insp_mae: codInspeccion,
                    fechadesde: $('#dt_fecha_desde').datebox('getValue'),
                    fechafin: $('#dt_fecha_fin').datebox('getValue')
                },
                onLoadSuccess: function(data) {
                    console.log("Datos cargados:", data);
                    // Si no hay datos, mostrar mensaje
                    if (data.rows.length === 0) {
                        $(this).datagrid('appendRow', {
                            num_lote: '<div style="text-align:center;color:#999;">No hay información disponible</div>'
                        });
                        $(this).datagrid('mergeCells', {
                            index: 0,
                            field: 'num_lote',
                            colspan: 8
                        });
                    }
                },
                onLoadError: function(xhr, status, error) {
                    console.error("Error al cargar datos:", xhr, status, error);
                    
                    // Intentar parsear el error del servidor
                    try {
                        var responseJson = xhr.responseJSON || JSON.parse(xhr.responseText);
                        var errorMessage = responseJson.message || 'Error al cargar los datos';
                        
                        $(this).datagrid('loadData', {total: 0, rows: []});
                        $(this).datagrid('appendRow', {
                            num_lote: '<div style="text-align:center;color:red;">' + errorMessage + '</div>'
                        });
                        $(this).datagrid('mergeCells', {
                            index: 0,
                            field: 'num_lote',
                            colspan: 8
                        });
                    } catch (e) {
                        console.error("Error al procesar respuesta de error:", e);
                        $(this).datagrid('loadData', {total: 0, rows: []});
                        $(this).datagrid('appendRow', {
                            num_lote: '<div style="text-align:center;color:red;">Error desconocido al cargar los datos</div>'
                        });
                        $(this).datagrid('mergeCells', {
                            index: 0,
                            field: 'num_lote',
                            colspan: 8
                        });
                    }
                }
            });
        },
        error: function(xhr, status, error) {
            console.error("Error al obtener información detallada:", error);
            // Proceder con el modal pero sin la información adicional
            mostrarModalSinInfoAdicional(tipoLote, codProductoMae, codInspeccion);
        }
    });
}

// Función para cerrar modal
function cerrarModalLotes() {
    $('#modal-detalles-lotes').window('close');
}

// Modificar la función de inicialización de tabla de detalles
// Modificar la función de inicialización de tabla de detalles
// Modificar la función de inicialización de tabla de detalles
function inicializarTablaDetalles(cod_producto_mae, index) {
    var $detalleGrid = $('#detalle-grid-' + cod_producto_mae);
    
    console.log("Inicializando tabla de detalles para producto: " + cod_producto_mae);
    
    // Petición AJAX para obtener los detalles
    $.ajax({
        url: url_consulta,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            _acc: 'listarDetalles',
            cod_producto_mae: cod_producto_mae,
            fechadesde: $('#dt_fecha_desde').datebox('getValue'),
            fechafin: $('#dt_fecha_fin').datebox('getValue'),
            page: 1,
            rows: 10
        },
        success: function(response) {
            console.log("Respuesta AJAX completa:", JSON.stringify(response));
            
            // Verificar si hay datos
            if (!response || !response.rows || response.rows.length === 0) {
                $detalleGrid.html('<div style="padding:10px;text-align:center;">No hay datos disponibles</div>');
                return;
            }
            
            // Crear HTML de la tabla con estilos de EasyUI
            var html = '<div class="datagrid-wrap panel-body panel-body-noheader">';
            html += '<div class="datagrid-view">';
            html += '<div class="datagrid-view1" style="width: 25px;">';
            html += '<div class="datagrid-header" style="height: 26px; width: 25px;">';
            html += '<div class="datagrid-header-inner">';
            html += '<table class="datagrid-htable" border="0" cellspacing="0" cellpadding="0" style="width: 25px;">';
            html += '<tbody><tr><td></td></tr></tbody>';
            html += '</table>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '<div class="datagrid-view2">';
            html += '<div class="datagrid-header" style="height: 26px;">';
            html += '<div class="datagrid-header-inner">';
            html += '<table class="datagrid-htable" border="0" cellspacing="0" cellpadding="0">';
            html += '<thead><tr>';
            html += '<th><div class="datagrid-cell" style="width: 50px; text-align: center;">Gráfico</div></th>';
            html += '<th><div class="datagrid-cell" style="width: 150px; text-align: center;">Código Inspección</div></th>';
            html += '<th><div class="datagrid-cell" style="width: 300px; text-align: left;">Nombre Inspección</div></th>';
            html += '<th><div class="datagrid-cell" style="width: 120px; text-align: center;">Cantidad Lotes</div></th>';
            html += '<th><div class="datagrid-cell" style="width: 120px; text-align: center;">Lotes Aprobados</div></th>';
            html += '<th><div class="datagrid-cell" style="width: 120px; text-align: center;">Lotes Desaprobados</div></th>';
            html += '</tr></thead>';
            html += '</table>';
            html += '</div>';
            html += '</div>';
            html += '<div class="datagrid-body">';
            html += '<table class="datagrid-btable" border="0" cellspacing="0" cellpadding="0">';
            
            // Agregar filas
            response.rows.forEach(function(row, index) {
                html += '<tr datagrid-row-index="' + index + '" class="datagrid-row ' + (index % 2 === 0 ? 'datagrid-row-alt' : '') + '">';
                html += '<td class="datagrid-td-rownumber" style="width: 50px; text-align: center;">';
                html += '<a href="javascript:void(0)" onclick="mostrarGraficoEstadistico(\'' + row.cod_insp_mae + '\', \'' + cod_producto_mae + '\')" class="btn-grafico">';
                html += '<i class="bi bi-bar-chart" style="color: #9B59B6;"></i></a>';
                html += '</td>';
                html += '<td class="datagrid-cell" style="width: 150px; text-align: center;">' + (row.cod_insp_mae || '') + '</td>';
                html += '<td class="datagrid-cell" style="width: 300px; text-align: left;">' + (row.nom_insp_mae || '') + '</td>';
                html += '<td class="datagrid-cell" style="width: 120px; text-align: center;">' + (row.total_lotes || 0) + '</td>';
                
                // Lotes Aprobados
                html += '<td class="datagrid-cell" style="width: 120px; text-align: center;">';
                html += '<span style="cursor: pointer;" onclick="mostrarDetallesLotes(\'Lotes Aprobados\', \'' + cod_producto_mae + '\', \'' + row.cod_insp_mae + '\')">' + (row.lotes_aprobados || 0) + '</span>';
                html += '<a href="javascript:void(0)" onclick="mostrarDetallesLotes(\'Lotes Aprobados\', \'' + cod_producto_mae + '\', \'' + row.cod_insp_mae + '\')" style="margin-left: 10px;">';
                html += '<i class="bi bi-file-earmark-text" style="color: #21A6A4;"></i></a>';
                html += '</td>';
                
                // Lotes Desaprobados
                html += '<td class="datagrid-cell" style="width: 120px; text-align: center;">';
                html += '<span style="cursor: pointer;" onclick="mostrarDetallesLotes(\'Lotes Desaprobados\', \'' + cod_producto_mae + '\', \'' + row.cod_insp_mae + '\')">' + (row.lotes_desaprobados || 0) + '</span>';
                html += '<a href="javascript:void(0)" onclick="mostrarDetallesLotes(\'Lotes Desaprobados\', \'' + cod_producto_mae + '\', \'' + row.cod_insp_mae + '\')" style="margin-left: 10px;">';
                html += '<i class="bi bi-file-earmark-text" style="color: #21A6A4;"></i></a>';
                html += '</td>';
                
                html += '</tr>';
            });
            
            html += '</table>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            
            // Insertar HTML
            $detalleGrid.html(html);
            
            // Intentar ajustar altura de la fila de detalles
            setTimeout(function() {
                try {
                    $('#dg_001').datagrid('fixDetailRowHeight', index);
                } catch(e) {
                    console.warn('No se pudo ajustar la altura de la fila de detalles', e);
                }
            }, 100);
        },
        error: function(xhr, status, error) {
            console.error("Error en petición AJAX:", status, error);
            $detalleGrid.html('<div style="padding:10px;text-align:center;color:red;">Error al cargar los datos: ' + status + '</div>');
        }
    });
}

    // Función para mostrar el gráfico estadístico
    // Función para mostrar el gráfico estadístico
    // Función para mostrar el gráfico estadístico
function mostrarGraficoEstadistico(cod_insp, cod_producto_mae) {
    // Capturar los valores de las fechas antes de la llamada AJAX
    var fechaDesde = $('#dt_fecha_desde').datebox('getValue') || '-';
    var fechaHasta = $('#dt_fecha_fin').datebox('getValue') || '-';
    
    // Obtener información adicional mediante AJAX
    $.ajax({
        url: url_consulta,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            _acc: 'obtenerInfoDetalle',
            cod_producto_mae: cod_producto_mae,
            cod_insp_mae: cod_insp
        },
        success: function(response) {
            // Crear estructura del modal con información básica
            let html = `
            <div id="win_grafico" class="easyui-window" title="Gráfico Estadístico" style="width:600px;height:400px;">
                <div class="easyui-layout" data-options="fit:true">
                    <div data-options="region:'center'" style="padding:15px;">
                        <div style="margin-bottom:15px;">
                            <table class="table-detalle" style="width:100%;">
                                <tr>
                                    <th style="width:30%;">Producto:</th>
                                    <td style="width:70%;">${cod_producto_mae} - ${response.nombreProducto || ''}</td>
                                </tr>
                                <tr>
                                    <th>Inspección:</th>
                                    <td>${cod_insp} - ${response.nombreInspeccion || ''}</td>
                                </tr>
                                <tr>
                                    <th>Período:</th>
                                    <td>${fechaDesde} al ${fechaHasta}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div id="grafico-contenedor" style="width:100%;height:250px;border:1px solid #ddd;"></div>
                    </div>
                    <div data-options="region:'south',border:false" style="text-align:center;padding:5px;">
                        <a href="javascript:void(0)" class="easyui-linkbutton" onclick="cerrarGrafico()">Cerrar</a>
                    </div>
                </div>
            </div>`;

            // Si ya existe una ventana, la cerramos
            if ($('#win_grafico').length) {
                $('#win_grafico').window('close');
                $('#win_grafico').remove();
            }
            
            $('body').append(html);
            
            $('#win_grafico').window({
                modal: true,
                collapsible: false,
                closable: true,
                minimizable: false,
                maximizable: false,
                closed: false,
                center: true,
                resizable: false
            });
            
            // Aquí iría la lógica para cargar el gráfico
            $('#grafico-contenedor').html('<div style="margin-top:100px;text-align:center;">Gráfico estadístico para la inspección</div>');
        },
        error: function() {
            // Versión simplificada sin información adicional
            let html = `
            <div id="win_grafico" class="easyui-window" title="Gráfico Estadístico - Inspección ${cod_insp}" style="width:600px;height:400px;">
                <div class="easyui-layout" data-options="fit:true">
                    <div data-options="region:'center'" style="padding:20px;text-align:center;">
                        <h3>Datos Estadísticos</h3>
                        <p>Código de Inspección: ${cod_insp}</p>
                        <p>Código Producto: ${cod_producto_mae}</p>
                        <div id="grafico-contenedor" style="width:100%;height:250px;border:1px solid #ddd;"></div>
                    </div>
                    <div data-options="region:'south',border:false" style="text-align:center;padding:5px;">
                        <a href="javascript:void(0)" class="easyui-linkbutton" onclick="cerrarGrafico()">Cerrar</a>
                    </div>
                </div>
            </div>`;
            
            if ($('#win_grafico').length) {
                $('#win_grafico').window('close');
                $('#win_grafico').remove();
            }
            
            $('body').append(html);
            
            $('#win_grafico').window({
                modal: true,
                collapsible: false,
                closable: true,
                minimizable: false,
                maximizable: false,
                closed: false,
                center: true,
                resizable: false
            });
            
            $('#grafico-contenedor').html('<div style="margin-top:100px;">Gráfico estadístico para la inspección ' + cod_insp + ' del producto ' + cod_producto_mae + '</div>');
        }
    });
}
    
    function cerrarGrafico() {
        $('#win_grafico').window('close');
    }

    function listar_datagrid() {
        $('#dg_001').datagrid('load', {
            _token: '{{ csrf_token() }}',
            _acc: 'listarPrincipal',
            producto: $('#cmb_producto').combobox('getValue'),
            fechadesde: $('#dt_fecha_desde').datebox('getValue'),
            fechafin: $('#dt_fecha_fin').datebox('getValue')
        });
    }

    function limpieza_filtros() {
        $('#cmb_producto').combobox('clear');
        $('#dt_fecha_desde').datebox('clear');
        $('#dt_fecha_fin').datebox('setValue', hoy);
        listar_datagrid();
    }

    function new_formatter_date(date) {
        let year = date.getFullYear();
        let month = date.getMonth() + 1;
        month = (month < 10 ? ('0' + month) : month);
        let day = date.getDate();
        day = (day < 10 ? ('0' + day) : day);
        return year + '-' + month + '-' + day;
    }
    
    function new_parser_date(date) {
        if (!date) return new Date();
        date = date.split('-');
        let year = parseInt(date[0], 10);
        let month = parseInt(date[1], 10);
        let day = parseInt(date[2], 10);
        if (!isNaN(year) && !isNaN(month) && !isNaN(day)) {
            return new Date(year, month - 1, day);
        } else {
            return new Date();
        }
    }
    
    function formatter_date_SAP(value) {
        if (value && value.length === 8) {
            const year = value.substr(0, 4);
            const month = value.substr(4, 2);
            const day = value.substr(6, 2);
            return `${year}-${month}-${day}`;
        }
        return value;
    }
</script>
@endsection

@section('content')
<div id="tb_dg_001">    
    <div style="width:100%;padding:3px;">
        <input id="cmb_producto" type="text">
        <input id="dt_fecha_desde">
        <input id="dt_fecha_fin">
    </div>

    <div style="padding:15px 0 20px 0;text-align:center">
        <button class="consultar-button" id="btn_actualizar" onclick="listar_datagrid()">
            <i class="bi bi-search"></i> Consultar
        </button>
        <button class="consultar-button" id="btn_limpiar" onclick="limpieza_filtros()">
            <i class="bi bi-funnel-fill"></i> &nbsp;Limpiar Filtro
        </button>
    </div>
</div>
<table id="dg_001"></table>
@endsection