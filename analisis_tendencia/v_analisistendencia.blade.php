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
            panelMaxHeight: 200
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
            rownumbers: true,  // Desactivamos la numeración automática
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
                // Eliminamos la columna personalizada con el botón expandir
                { field: 'lote_inspeccion', title: 'Lote Inspección', align: 'center', halign: 'center', width: 200 },
                { field: 'cod_material', title: 'Código Material', align: 'center', halign: 'center', width: 200 },
                { field: 'nom_material', title: 'Nombre Material', align: 'left', halign: 'center', width: 450 },
                { field: 'num_lote', title: 'Número Lote', align: 'center', halign: 'center', width: 200 }
            ]],
            view: detailview,
            detailFormatter: function(index, row) {
                return '<div style="padding:10px;"><table id="detalle-grid-' + row.lote_inspeccion + '"></table></div>';
            },
            onExpandRow: function(index, row) {
                var lote = row.lote_inspeccion;
                inicializarTablaDetalles(lote);
                $('#dg_001').datagrid('fixDetailRowHeight', index);
            },
            onLoadSuccess: function(data) {
                console.log("Datos cargados en tabla principal:", data);
                if (data.rows.length > 0) {
                    console.log("Primera fila:", data.rows[0]);
                    console.log("Valor de lote_inspeccion:", data.rows[0].lote_inspeccion);
                }
            },
            onLoadError: function (XMLHttpRequest, textStatus, errorThrown) {
                $.messager.alert('Error', 'Error al mostrar los datos, vuelva a intentar', 'error');
            }
        }).datagrid('getPager').pagination({
            beforePageText: 'Pag. ',
            afterPageText: 'de {pages}',
            displayMsg: 'Del {from} al {to}, de {total} items.'
        });


    });

    // Función para expandir o contraer una fila
    function expandirFila(index, lote_inspeccion) {
        var $dg = $('#dg_001');
        var $btn = $('.btn-expandir').eq(index);
        var isExpanded = $btn.data('expanded');
        
        if (isExpanded) {
            // Contraer fila
            $dg.datagrid('collapseRow', index);
            $btn.html('<i class="bi bi-plus-circle"></i>');
            $btn.data('expanded', false);
        } else {
            // Expandir fila
            $dg.datagrid('expandRow', index);
            $btn.html('<i class="bi bi-dash-circle"></i>');
            $btn.data('expanded', true);
        }
    }

    // Función para inicializar la tabla de detalles
    function inicializarTablaDetalles(lote_inspeccion) {
    var $detalleGrid = $('#detalle-grid-' + lote_inspeccion);
    
    console.log("Inicializando tabla de detalles para lote: " + lote_inspeccion);
    
    if ($detalleGrid.data('initialized')) {
        console.log("Tabla ya inicializada, retornando");
        return;
    }
    
    $detalleGrid.datagrid({
        url: url_consulta,
        fitColumns: false,
        singleSelect: true,
        rownumbers: false,
        pagination: true,
        pageSize: 10,
        striped: true,
        fit: true,
        nowrap: false,
        border: false,
        loadMsg: 'Cargando detalles...',
        queryParams: {
            _token: '{{ csrf_token() }}',
            _acc: 'listarDetalles',
            lote_inspeccion: lote_inspeccion
        },
        onLoadSuccess: function(data) {
            console.log("Datos cargados exitosamente:", data);
        },
        onLoadError: function(xhr, status, error) {
            console.error("Error cargando datos:", status, error);
            $.messager.alert('Error', 'Error al cargar los detalles: ' + status, 'error');
        },
        columns: [[
            { 
                field: 'grafico', 
                title: '', 
                align: 'center', 
                width: 40, 
                formatter: function(val, row, index) {
                    return '<a href="javascript:void(0)" onclick="mostrarGraficoEstadistico(\'' + row.cod_insp + '\')" class="btn-grafico"><i class="bi bi-bar-chart"></i></a>';
                }
            },
            { field: 'cod_insp', title: 'Código Insp.', width: 100, align: 'center' },
            { field: 'nom_insp', title: 'Nombre Inspección', width: 250, align: 'left' },
            { field: 'resultado', title: 'Resultado', width: 120, align: 'center' },
            { field: 'media', title: 'Media', width: 80, align: 'center' },
            { field: 'txt_breve', title: 'Texto Breve', width: 150, align: 'left' },
            { field: 'fec_vec_lote', title: 'Fecha Venc. Lote', width: 120, align: 'center', formatter: function(val) { 
                return val ? formatter_date_SAP(val) : ''; 
            }},
            { field: 'fec_ini_insp', title: 'Fecha Inicio Insp.', width: 120, align: 'center', formatter: function(val) { 
                return val ? formatter_date_SAP(val) : ''; 
            }},
            { field: 'fec_fin_insp', title: 'Fecha Fin Insp.', width: 120, align: 'center', formatter: function(val) { 
                return val ? formatter_date_SAP(val) : ''; 
            }}
        ]]
    }).datagrid('getPager').pagination({
        beforePageText: 'Pag. ',
        afterPageText: 'de {pages}',
        displayMsg: 'Del {from} al {to}, de {total} items.'
    });
    
    $detalleGrid.data('initialized', true);
}

    // Función para mostrar el gráfico estadístico
    function mostrarGraficoEstadistico(cod_insp) {
        let html = `
        <div id="win_grafico" title="Gráfico Estadístico - Inspección ${cod_insp}" class="easyui-layout" style="width:600px;height:400px;">
            <div style="padding:20px;text-align:center;">
                <h3>Datos Estadísticos</h3>
                <p>Código de Inspección: ${cod_insp}</p>
                <div id="grafico-contenedor" style="width:100%;height:250px;border:1px solid #ddd;"></div>
            </div>
        </div>`;

        if ($('#win_grafico')) {
            $('#win_grafico').window('close');
            $('#win_grafico').empty();
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
        
        // Simulación de gráfico (implementar según necesidades)
        $('#grafico-contenedor').html('<div style="margin-top:100px;">Gráfico estadístico para la inspección ' + cod_insp + '</div>');
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
        $('#dt_fecha_fin').datebox('clear');
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