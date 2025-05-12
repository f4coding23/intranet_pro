<script>
function colorear_dg(tipo=''){
    var panel = $("#dg_proceso_cab").datagrid("getPanel");
    var myheaderCol = "";

    /* myheaderCol = panel.find("div.datagrid-header td[field='T1']");
    myheaderCol.css("background-color","#D3FFA6"); */
    
    //No frosen - BASES
    if(tipo == ''){
        // PROCESO
        myheaderCol = panel.find("div.datagrid-header td[field='T1']");
        myheaderCol.css("background-color","#FFE092");
        myheaderCol = panel.find("div.datagrid-header td[field='T2']");
        myheaderCol.css("background-color","#D3FFA6");
        myheaderCol = panel.find("div.datagrid-header td[field='T3']");
        myheaderCol.css("background-color","#00DAF3");
    }else{
        var panel_entrega = $(tipo).datagrid("getPanel");
        // ENTREGA
        myheaderCol = panel_entrega.find("div.datagrid-header td[field='T1']");
        myheaderCol.css("background-color","#F37A00");
        myheaderCol = panel_entrega.find("div.datagrid-header td[field='T2']");
        myheaderCol.css("background-color","#00F3B4");
        myheaderCol = panel_entrega.find("div.datagrid-header td[field='T3']");
        myheaderCol.css("background-color","#00B21E");
        myheaderCol = panel_entrega.find("div.datagrid-header td[field='T4']");
        myheaderCol.css("background-color","#00DAF3");
    }
}

function colorear_dg_contratos(grilla){
    var panel = $(grilla).datagrid("getPanel");
    var myheaderCol = "";
   
    myheaderCol = panel.find("div.datagrid-header td[field='T1']");
    myheaderCol.css("background-color","#D3FFA6");
    myheaderCol = panel.find("div.datagrid-header td[field='T2']");
    myheaderCol.css("background-color","#FFE092"); 
    myheaderCol = panel.find("div.datagrid-header td[field='T3']");
    myheaderCol.css("background-color","#00B21E");
    myheaderCol = panel.find("div.datagrid-header td[field='T4']");
    myheaderCol.css("background-color","#00DAF3");
    
}

function listar_datagrid_bases_cab(){
    /* let txt_documento = $('#txt_documento').numberbox('getValue') ?? '';
    let txt_entrega = $('#txt_entrega').numberbox('getValue') ?? '';
    let txt_ctf = $('#txt_ctf').numberbox('getValue') ?? '';
    let txt_sctf = $('#txt_sctf').numberbox('getValue') ?? ''; */
    let fechaEmisionObj = new Date($('#dt_fecha_desde').datebox('getValue'));
    let fechaVencimientoObj = new Date($('#dt_fecha_fin').datebox('getValue'));
    let diferenciaMs = fechaVencimientoObj - fechaEmisionObj;
    let diferenciaDias = Math.floor(diferenciaMs / (1000 * 60 * 60 * 24));
    let filtros = getFiltroBusqueda();
    let allEmpty = true;
    for (let key in filtros) {
        if (key !== 'fechadesde' && key !== 'fechafin' && filtros[key] !== '') {
            allEmpty = false;
            break;
        }
    }
    if(allEmpty == true){
        if(diferenciaDias > 366){
            $.messager.alert('Error', 'El rango de búsqueda máximo por fecha de proceso es de 1 años(365 días), su búsqueda fue de <b>'+diferenciaDias+'</b> días. Por favor validar.', 'warning');
            return;
        }else{
            $('#dg_proceso_cab').datagrid('load', {
                _token	: '<?= csrf_token() ?>'
                ,_acc	: 'listarPrincipalProcesoCab'
                ,...getFiltroBusqueda()
            });
        }
    }else{
        //$('#dt_fecha_desde').datebox('clear');

        $('#dg_proceso_cab').datagrid('load', {
            _token	: '<?= csrf_token() ?>'
            ,_acc	: 'listarPrincipalProcesoCab'
            ,...getFiltroBusqueda()
        });
    }
}

function descargar(action,idprocesocab='',operador_alias=''){
    /* if(action == 'DownloadExcel'){
        $.messager.alert('Info ','En desarrollo.','info');
        return;
    }else{ */
        if(checkRolesAndPermissions(action)/*  === true */){
            if(action == 'DownloadExcel'){
                $('#dt_fecha_desde').datebox('setValue',trimestreAnterior);
            }
            $.ajax(url_consulta,{
                type: "post",
                async: true,
                dataType: "json",
                success: function(datos){
                    if (datos.res == 1){
                        // Pasar a descargar
                        window.open(url_consulta +
                            '?_token='+'<?= csrf_token() ?>'+
                            '&_acc=DescargarArchivo'+
                            '&_nombrefile='+datos.nombrefile,
                        "_blank");
                    }else{
                        $.messager.alert('Error',datos.msj,'error');
                    }
                },
                error:function(x,e){
                    $.messager.alert('Error '+x.status,'Ocurrió un error en el servidor.','error');
                },
                beforeSend: function(){
                    $.messager.progress({text:'Descargando...'});
                },
                complete: function(){
                    $.messager.progress('close');
                },
                data: {
                    _token          :'<?= csrf_token() ?>'
                    ,_acc           : action
                    ,...getFiltroBusqueda()
                    ,idprocesocab : idprocesocab
                    ,operador_alias : operador_alias
                },
            });
        }
    //}
}

function descargar_mae_cf(action){
    $.ajax(url_consulta,{
        type: "post",
        async: true,
        dataType: "json",
        success: function(datos){
            if (datos.res == 1){
                // Pasar a descargar
                window.open(url_consulta +
                    '?_token='+'<?= csrf_token() ?>'+
                    '&_acc=DescargarArchivo'+
                    '&_nombrefile='+datos.nombrefile,
                "_blank");
            }else{
                $.messager.alert('Error',datos.msj,'error');
            }
        },
        error:function(x,e){
            $.messager.alert('Error '+x.status,'Ocurrió un error en el servidor.','error');
        },
        beforeSend: function(){
            $.messager.progress({text:'Descargando...'});
        },
        complete: function(){
            $.messager.progress('close');
        },
        data: {
            _token          :'<?= csrf_token() ?>'
            ,_acc           : action
            ,proceso        : $('#txt_proceso_mae_cf').textbox('getValue')
            ,fianza         : $('#txt_cf_mae_cf').textbox('getValue')
            ,idestadocartafianzafinal         : $('#cmb_estado_mae_cf').combobox('getValues').join(',')
            ,idmaebanco     : $('#cmb_banco_mae_cf').combobox('getValues').join(',')
            ,anio           : $('#cmb_anio_mae_cf').combobox('getValues').join(',')
            ,mes            : $('#cmb_mes_mae_cf').combobox('getValues').join(',')
            ,contratista    : $('#cmb_contratista_mae_cf').combobox('getValues').join(',')
            ,linea          : $('#cmb_linea_mae_cf').combobox('getValues').join(',')
            //,flg_only_migrada : $('#ck_only_migradas').switchbutton('options').checked ? 1 : ''
            ,flg_only_faltante_41 : $('#cmb_faltante_41').combobox('getValue')
            ,dias_demora : $('#ck_dias_demora_mae_cf').switchbutton('options').checked ? 1 : ''
            ,flg_contrato : $('#cmb_con_contrato_mae_cf').combobox('getValue')
            ,flg_rechazada   : $('#ck_rechazadas_mae_cf').switchbutton('options').checked ? 1 : ''
            ,no_flg_rechazada: $('#ck_rechazadas_mae_cf').switchbutton('options').checked ? '' : 1
            ,fecha_desde_mae_cf: $('#dt_fecha_desde_mae_cf').datebox('getValue')
            ,fecha_fin_mae_cf  : $('#dt_fecha_fin_mae_cf').datebox('getValue') 
            ,indicador_1era          : $('#cmb_indicador_primera_mae_cf').combobox('getValue')
            ,flg_cumplimiento   : $('#cmb_cumplimiento_mae_cf').combobox('getValue')
            ,flg_ejecucion      : $('#cmb_ejecucion_mae_cf').combobox('getValue')
            ,flg_deuda          : $('#cmb_deuda_mae_cf').combobox('getValue')
            ,idsituaciones      : $('#cmb_legal_mae_cf').combobox('getValues').join(',')

        },
    });
}

function descargar_mae_scf(action){
    $.ajax(url_consulta,{
        type: "post",
        async: true,
        dataType: "json",
        success: function(datos){
            if (datos.res == 1){
                // Pasar a descargar
                window.open(url_consulta +
                    '?_token='+'<?= csrf_token() ?>'+
                    '&_acc=DescargarArchivo'+
                    '&_nombrefile='+datos.nombrefile,
                "_blank");
            }else{
                $.messager.alert('Error',datos.msj,'error');
            }
        },
        error:function(x,e){
            $.messager.alert('Error '+x.status,'Ocurrió un error en el servidor.','error');
        },
        beforeSend: function(){
            $.messager.progress({text:'Descargando...'});
        },
        complete: function(){
            $.messager.progress('close');
        },
        data: {
            _token          :'<?= csrf_token() ?>'
            ,_acc           : action
            ,proceso            : $('#txt_proceso_mae_sctf').textbox('getValue')
            ,solicitante        : $('#cmb_benificiario_mae_sctf').combobox('getValues').join(',')
            ,idestadocartafianza: $('#cmb_estado_mae_sctf').combobox('getValues').join(',')
            ,codigo_garantizado : $('#cmb_garantizado_mae_sctf').combobox('getValues').join(',')
            ,usuarioid          : $('#cmb_usuario_mae_sctf').combobox('getValues').join(',')
            ,flg_pdte_gestion          : $('#cmb_pdte_gestion_mae_sctf').combobox('getValue')
            /* ,flg_only_sctf      : $('#ck_only_solicitudes_mae_sctf').switchbutton('options').checked ? 1 : ''
            ,flg_only_migrada   : $('#ck_only_migradas_mae_sctf').switchbutton('options').checked ? 1 : ''
            ,no_flg_only_migrada: $('#ck_only_migradas_mae_sctf').switchbutton('options').checked ? '' : 1
            ,flg_rechazada   : $('#ck_rechazadas_mae_sctf').switchbutton('options').checked ? 1 : ''
            ,no_flg_rechazada: $('#ck_rechazadas_mae_sctf').switchbutton('options').checked ? '' : 1 */
        },
    });
}

function descargar_adjunto(idadjuntocartafianza){
    window.open(url_consulta +
        '?_token='+'<?= csrf_token() ?>'+
        '&_acc=DownloadAdjuntos'+
        '&_idadjuntocartafianza='+idadjuntocartafianza,
    "_blank");
}

function descargar_adjunto_cnt(idadjuntocontrato){
    window.open(url_consulta +
        '?_token='+'<?= csrf_token() ?>'+
        '&_acc=DownloadAdjuntosCNT'+
        '&_idadjuntocontrato='+idadjuntocontrato,
    "_blank");
}

function limpieza_filtros(){
    $('#dt_fecha_desde').datebox('enable');
    $('#dt_fecha_fin').datebox('enable');
    // PROCESO
    $('#txt_documento').numberbox('clear');     
    $('#cmb_org_ventas').combobox('clear');
    $('#dt_fecha_desde').datebox('clear');
    //$('#dt_fecha_desde').datebox('setValue',trimestreAnterior);
    $('#dt_fecha_fin').datebox('setValue',hoy);
    $('#cmb_cliente').combobox('clear');
    $('#cmb_motivo_pedido').combobox('clear');
    //$('#cmb_canal_dist').combobox('clear');
    $('#cmb_grupo_cliente').combobox('clear');
    $('#cmb_region').combobox('clear');
    $('#cmb_producto').combobox('clear');
    $('#cmb_tipo_venta').combobox('clear');
    $('#cmb_grupo_articulos').combobox('clear');
    $('#cmb_motivo_rechazo').combobox('clear');
    $('#txt_sctf').textbox('clear');
    $('#txt_ctf').textbox('clear');
    $('#txt_denominacion').textbox('clear');
    // ENTREGA
    $('#txt_entrega').textbox('clear');
    $('#txt_contrato').textbox('clear');
    $('#cmb_dst_mercancia').combobox('clear');
    // PEDIDO
    $('#txt_pedido').textbox('clear');
    $('#txt_gr').textbox('clear');
    $('#txt_factura').textbox('clear');
    // INDICADORES
    $('#cmb_presenta_ctf').combobox('setValue',3);
    $('#cmb_presenta_cnt').combobox('setValue',3);
    $('#cmb_presenta_pedido').combobox('clear');
    $('#cmb_presenta_facturacion').combobox('clear');
    $('#cmb_deuda_pendiente').combobox('clear');
    // FUNCIONES
    toggleFiltros(1);
    listar_datagrid_bases_cab();
} 

function getFiltroBusqueda(){  
    let data_filtro = {
        // PROCESO
        txt_documento      		: $('#txt_documento').numberbox('getValue')
        ,codigo_org_ventas      : $('#cmb_org_ventas').combobox('getValues').join(',')
        ,fechadesde   			: $('#dt_fecha_desde').datebox('getValue')
        ,fechafin       		: $('#dt_fecha_fin').datebox('getValue')
        ,codigo_cliente         : $('#cmb_cliente').combobox('getValues').join(',')
        ,codigo_mot_pedido      : $('#cmb_motivo_pedido').combobox('getValues').join(',')
        //,codigo_canal_dist      : $('#cmb_canal_dist').combobox('getValues').join(',')
        ,grupo_cliente          : $('#cmb_grupo_cliente').combobox('getValues').join(',')
        ,codigo_region          : $('#cmb_region').combobox('getValues').join(',')
        ,codigo_producto        : $('#cmb_producto').combobox('getValues').join(',')
        ,tipo_venta             : $('#cmb_tipo_venta').combobox('getValues').join(',')
        ,codigo_grupo_art       : $('#cmb_grupo_articulos').combobox('getValues').join(',')
        ,codigo_motivo_rechazo  : $('#cmb_motivo_rechazo').combobox('getValues').join(',')
        ,txt_sctf         		: $('#txt_sctf').textbox('getValue')
        ,txt_ctf         		: $('#txt_ctf').textbox('getValue')
        ,txt_denominacion       : $('#txt_denominacion').textbox('getValue')
        ,flg_8uit               : $('#ck_8uit').switchbutton('options').checked ? 1 : ''
        // ENTREGA
        ,txt_entrega      		: $('#txt_entrega').textbox('getValue')
        ,txt_contrato      		: $('#txt_contrato').textbox('getValue')
        ,codigo_dst_mercancia   : $('#cmb_dst_mercancia').combobox('getValues').join(',')
        // PEDIDO
        ,txt_pedido      		: $('#txt_pedido').textbox('getValue')
        ,txt_gr      		    : $('#txt_gr').textbox('getValue')
        ,txt_factura      		: $('#txt_factura').textbox('getValue')
        // INDICADORES
        ,flg_ctf                 : $('#cmb_presenta_ctf').combobox('getValue')
        ,flg_cnt                 : $('#cmb_presenta_cnt').combobox('getValue')
        ,con_15                 : $('#cmb_presenta_pedido').combobox('getValue')
        ,facturacion            : $('#cmb_presenta_facturacion').combobox('getValue')
        ,deuda                  : $('#cmb_deuda_pendiente').combobox('getValue')
        
        // NIVEL CUMPLIMIENTO
        /*,identregacab : $('#cmb_entrega_nc').combobox('getValues').join(',')
        ,nro_entrega  : $('#cmb_nro_entrega_nc').combobox('getValues').join(',')
        ,idmaeproducto: $('#cmb_producto_nc').combobox('getValues').join(',')
        ,codigo_dst_mercancia  : $('#cmb_destinatario_mercancia_nc').combobox('getValues').join(',')
        ,anio_nc      : $('#cmb_anio_entrega_nc').combobox('getValues').join(',')
        ,mes_nc       : $('#cmb_mes_entrega_nc').combobox('getValues').join(',')
        ,idcontrato   : $('#cmb_contrato_nc').combobox('getValues').join(',')
        ,fecha_desde_cnt_nc: $('#dt_fecha_desde_contrato_nc').datebox('getValue')
        ,fecha_fin_cnt_nc  : $('#dt_fecha_fin_contrato_nc').datebox('getValue')
        ,pendiente_atencion : $('#cmb_pendiente_atencion_nc').combobox('getValue')
        ,idpedidocab : $('#cmb_pedido_nc').combobox('getValues').join(',')
        ,idpickingcab : $('#cmb_picking_nc').combobox('getValues').join(',')
        ,guia_remision : $('#cmb_gr_nc').combobox('getValues').join(',')
        ,factura_sunat : $('#cmb_cmb_factura_ncgr_nc').combobox('getValues').join(',')
        ,flg_devolucion : $('#cmb_con_devolucion_nc').combobox('getValues').join(',')
        ,tipo_producto : $('#cmb_tipo_producto_nc').combobox('getValue')*/
    };
    return data_filtro;
}

function customSort(data) {
    return data.sort(function(a, b) {
        // Obtener los valores de doc_picking, asegurándose de que no sean undefined
        let docPickingA = a.doc_picking || '';
        let docPickingB = b.doc_picking || '';

        // Primero ordenar por doc_picking
        if (docPickingA < docPickingB) return -1;
        if (docPickingA > docPickingB) return 1;

        // Si doc_picking es igual, ordenar por el patrón 80 -> 84 -> 80
        let patternA = (docPickingA.startsWith('80') ? 1 : (docPickingA.startsWith('84') ? 2 : 3));
        let patternB = (docPickingB.startsWith('80') ? 1 : (docPickingB.startsWith('84') ? 2 : 3));

        return patternA - patternB;
    });
}


function new_formatter_date(date){ //Funciones para el formateo de las fechas de los DateBox
        let year = date.getFullYear();
        let month = date.getMonth()+1;
        month = (month < 10 ? ('0' + month) : month);
        let day = date.getDate();
        day = (day < 10 ? ('0' + day) : day);

        return  year + '-' + month + '-' + day;
        //return  (d < 10 ? ('0' + d) : d) + '/' + (m < 10 ? ('0' + m) : m) + '/' + y;
    }
function new_parser_date(date){ 	//Funciones para el formateo de las fechas de los DateBox
    if (!date) return new Date();
    date = date.split('-');
    let year = parseInt(date[0],10);
    let month = parseInt(date[1],10);
    let day = parseInt(date[2],10);

    if (!isNaN(year) && !isNaN(month) && !isNaN(day)){
        return new Date(year,month-1,day);
    } else {
        return new Date();
    }
}
function formatter_date_SAP(value){ //Funciones para el formateo de las fechas de los DateBox
    if (value && value.length === 8) {
        const year = value.substr(0, 4);
        const month = value.substr(4, 2);
        const day = value.substr(6, 2);
        //return `${year}.${month}.${day}`;
        return `${day}.${month}.${year}`;
    }
    return '';
}
// DESPLEGABLE 
function toggleFiltros(flg_limpieza=0,tipo='') {
    var filtro = document.getElementById(tipo);
    var arrowIcon = document.getElementById("arrow_icon");
    if(flg_limpieza == 0){
        if (filtro.style.display === "none") {
            filtro.style.display = "block";
        } else {
            filtro.style.display = "none";
        }
    }else {
        document.getElementById('filtro_proceso').style.display = "block";
        document.getElementById('filtro_entrega').style.display = "block";
        document.getElementById('filtro_pedido').style.display = "block";
        document.getElementById('filtro_indicadores').style.display = "block";
    }

    adjustDatagridSize();  // Ajusta el tamaño del datagrid
}

function toggleFiltrosEntrega(flg_limpieza=0) {
    var filtroEntrega = document.getElementById("filtro_entrega");
    var arrowIcon = document.getElementById("arrow_icon");
    if(flg_limpieza == 0){
        if (filtroEntrega.style.display === "none") {
            filtroEntrega.style.display = "block";
            //arrowIcon.innerHTML = "&#9650;"; // Cambia la flecha hacia arriba
        } else {
            filtroEntrega.style.display = "none";
            //arrowIcon.innerHTML = "&#9660;"; // Cambia la flecha hacia abajo
        }
    }else {
        filtroEntrega.style.display = "block";
        //arrowIcon.innerHTML = "&#9660;"; // Cambia la flecha hacia abajo
    }

    adjustDatagridSize();  // Ajusta el tamaño del datagrid
}

function toggleFiltrosPedido(flg_limpieza=0) {
    var filtroPedido = document.getElementById("filtro_pedido");
    var arrowIcon = document.getElementById("arrow_icon");
    if(flg_limpieza == 0){
        if (filtroPedido.style.display === "none") {
            filtroPedido.style.display = "block";
        } else {
            filtroPedido.style.display = "none";
        }
    }else {
        filtroPedido.style.display = "block";
    }

    adjustDatagridSize();  // Ajusta el tamaño del datagrid
}

function toggleFiltrosIndicadores(flg_limpieza=0) {
    var filtro_indicadores = document.getElementById("filtro_indicadores");
    var arrowIcon = document.getElementById("arrow_icon");
    if(flg_limpieza == 0){
        if (filtro_indicadores.style.display === "none") {
            filtro_indicadores.style.display = "block";
        } else {
            filtro_indicadores.style.display = "none";
        }
    }else {
        filtro_indicadores.style.display = "block";
    }

    adjustDatagridSize();  // Ajusta el tamaño del datagrid
}

function expandir_filtros(){
    document.getElementById('filtro_proceso').style.display = "block";
    document.getElementById('filtro_entrega').style.display = "block";
    document.getElementById('filtro_pedido').style.display = "block";
    document.getElementById('filtro_indicadores').style.display = "block";
    adjustDatagridSize();  // Ajusta el tamaño del datagrid
}

function contraer_filtros(){
    document.getElementById('filtro_proceso').style.display = "none";
    document.getElementById('filtro_entrega').style.display = "none";
    document.getElementById('filtro_pedido').style.display = "none";
    document.getElementById('filtro_indicadores').style.display = "none";
    adjustDatagridSize();  // Ajusta el tamaño del datagrid
}

function adjustDatagridSize(){
    //document.getElementById('filtro_proceso').style.display = "block";
    $('#dg_proceso_cab').datagrid('resize');
    /*document.getElementById('filtro_entrega').style.display = "none";
    document.getElementById('filtro_pedido').style.display = "none";*/
}

function adjustDatagridSizeOrigin(){
    //document.getElementById('filtro_proceso').style.display = "block";
    document.getElementById('filtro_entrega').style.display = "none";
    document.getElementById('filtro_pedido').style.display = "none";
    document.getElementById('filtro_indicadores').style.display = "none";
}

function formatNumber(number) {
    // Verificar si el número es menor que 1
    if (number < 1) {
        // Si es menor que 1, agregar "0." al inicio y devolverlo
        return "0" + number.toString();//.slice(2);
    } else {
        // Si es mayor o igual a 1, devolver el número sin cambios
        return number;
    }
}

function formatComas(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function parserComas(number){ 	//Funciones para el formateo de las fechas de los DateBox
    //return parseFloat(number.replace(',', ''));

    return parseFloat(number.toString().replace(/,/g, ''));
}

function cantidad_seleccionada(idprocesodet,newValue){
    let data_productos = JSON.parse(sessionStorage.getItem('productos_seleccionados_prev')) || [];
    data_productos.push({
        idprocesodet   : idprocesodet,
        cantidad_selec : newValue
    });
    sessionStorage.setItem('productos_seleccionados_prev',JSON.stringify(data_productos));
} 

function f_entregas_seleccionada(identregadet,newValue){
    let data_entregas = JSON.parse(sessionStorage.getItem('entregas_seleccionados_prev')) || [];
    data_entregas.push({
        identregadet   : identregadet,
        cantidad_selec : newValue
    });
    sessionStorage.setItem('entregas_seleccionados_prev',JSON.stringify(data_entregas));
} 

function calculoEvaluacion(importe_vsi,tasa,minimo_ctf,periodo,porte_ctf,idtipoeva=0,dia=0,codigo_mae_banco='',flg_renovacion=0, dias = null){
    let porte = parseFloat(porte_ctf);
    let minimo = parseFloat(minimo_ctf);
    let tipo_cobro = (idtipoeva == 2) ? parseFloat(dia) : 90; // A: Trimestral | B: Día
    let multiplica_periodo = (idtipoeva == 2) ? 1 : periodo;

    if (dias === null) {
        dias = $('#num_dias_eva').next('span').find('input.textbox-value').val();
    }


    var calculoPeriodo = (codigo_mae_banco == 'PCH') ? (importe_vsi * (tasa / 100) * (tipo_cobro / 360)) 
    : (codigo_mae_banco == 'STD') ?  ((importe_vsi * (tasa / 100))/ 360 * dias > minimo) ? (importe_vsi * (tasa / 100))/ 360 * dias : minimo
    : (codigo_mae_banco == 'SCT') ? (importe_vsi *(tasa/100)) / 360*90
    : (importe_vsi * (tasa / 100) * (tipo_cobro / 360));

    var cobroPeriodo = ((calculoPeriodo > minimo) ? calculoPeriodo : minimo) + porte;
    if(codigo_mae_banco == 'SCT') {
        cobroPeriodo = ((calculoPeriodo > minimo) ? calculoPeriodo : minimo) + porte;
    }
    if(codigo_mae_banco == 'STD') {
        cobroPeriodo = (calculoPeriodo > minimo) ? calculoPeriodo : minimo
    }   
    var importeCalculado_PCH = (calculoPeriodo > minimo) ? calculoPeriodo : minimo;
    let calculoImporte15 = f_calculo15(importe_vsi,tasa,minimo,periodo,porte_ctf,codigo_mae_banco);
    var importeCalculadoFinal = 0
    if(codigo_mae_banco == 'PCH'){
        importeCalculadoFinal = (flg_renovacion == 0) ? (importeCalculado_PCH*multiplica_periodo)+calculoImporte15.resultado+porte
        : (importeCalculado_PCH*multiplica_periodo)+calculoImporte15.resultado +porte;
    }else{
        if(codigo_mae_banco == 'STD' && flg_renovacion == 1){
            cobroPeriodo = cobroPeriodo / periodo;
            importeCalculadoFinal = (periodo*cobroPeriodo) + calculoImporte15.resultado + porte;
        }else if(codigo_mae_banco == 'STD' && flg_renovacion == 0){
            cobroPeriodo = cobroPeriodo / periodo;
            importeCalculadoFinal = (calculoPeriodo + calculoImporte15.resultado +porte ) ;
        }else{
            importeCalculadoFinal = (cobroPeriodo*multiplica_periodo)+calculoImporte15.resultado;
        }
        
    }

    if(codigo_mae_banco =='PCH' && importe_vsi ==  '78322.8000') {
        console.group(codigo_mae_banco);

        console.log('flg_renovacion', flg_renovacion);
        console.log('tipo_cobro',tipo_cobro);
        console.log('idtipoeva', idtipoeva);
        console.log('periodo', periodo);
        console.log('importe_vsi', importe_vsi);
        console.log('tasa', tasa);
        console.log('minimo', minimo);
        console.log('multiplica_periodo', multiplica_periodo);
        console.log('calculoPeriodo', calculoPeriodo);
        console.log('cobroPeriodo: ',cobroPeriodo);
        console.log('calculoImporte15:', calculoImporte15)
        console.warn((periodo* cobroPeriodo) + calculoImporte15.resultado);

        console.groupEnd();
    }

    if(codigo_mae_banco =='BCP' && importe_vsi == '48500.0000' && periodo == 6) {
        // console.group(codigo_mae_banco);

        // console.log('periodo', periodo);
        // console.log('calculoImporte15: ',calculoImporte15);
        // console.log('porte: ',porte);
        // console.log('calculoPeriodo', calculoPeriodo);
        // console.log('cobroPeriodo', cobroPeriodo);
        // console.log('minimo: ',minimo);

        // console.warn('importeCalculadoFinal', importeCalculadoFinal);

        // console.groupEnd();
    }

    if(codigo_mae_banco =='BBVA' && importe_vsi ==  '78322.8000') {
        // console.group(codigo_mae_banco);

        // console.log('(importe_vsi * (tasa / 100) * (tipo_cobro / 360)) + porte', (importe_vsi * (tasa / 100) * (tipo_cobro / 360)) + porte);
        // console.log('tipo_cobro', tipo_cobro);
        // console.log('importe_vsi', importe_vsi);
        // console.log('tasa', tasa);
        // console.log('minimo', minimo);
        // console.log('multiplica_periodo', multiplica_periodo);
        // console.log('calculoPeriodo', calculoPeriodo);
        // console.log('cobroPeriodo: ',cobroPeriodo);
        // console.log('calculoImporte15:', calculoImporte15)
        // console.warn((periodo* cobroPeriodo) + calculoImporte15.resultado);

        // console.groupEnd();
    }

    if(codigo_mae_banco =='STD') {
        // console.group(codigo_mae_banco);

        // console.log('flg_renovacion', flg_renovacion);
        // console.log('cobroPeriodo', cobroPeriodo);
        // console.log('calculoImporte15', calculoImporte15);
        // console.log('porte_ctf: ',porte_ctf);
        // console.log('calculoPeriodo: ',calculoPeriodo);
        // console.log('importe_vsi', importe_vsi);
        // console.log('tasa', tasa);
        // console.log('dias', dias);
        // console.log('minimo',minimo);
        // console.log('total', (periodo*cobroPeriodo) + calculoImporte15.resultado + porte);

        // console.groupEnd();
    }

    if(codigo_mae_banco =='SCT') {
        // console.group(codigo_mae_banco);

        // console.log('periodo', periodo);
        // console.log('calculoImporte15: ',calculoImporte15);
        // console.log('porte: ',porte);
        // console.log('calculoPeriodo', calculoPeriodo);
        // console.log('cobroPeriodo', cobroPeriodo);
        // console.log('minimo: ',minimo);

        // console.groupEnd();
    }

    //let importeCalculadoFinal = (codigo_mae_banco == 'PCH' && flg_renovacion == 0) ? (importeCalculado_PCH*multiplica_periodo)+calculoImporte15.resultado+porte : (cobroPeriodo*multiplica_periodo)+calculoImporte15.resultado;

    /* console.log('codigo_mae_banco: ',codigo_mae_banco);
    console.log('cobroPeriodo: ',cobroPeriodo); */
    //console.log('importeCalculado_PCH: ',importeCalculado_PCH);
    //console.log('importeCalculadoFinal: ',importeCalculadoFinal);
    return{
        x_periodo: (codigo_mae_banco == 'PCH') ? importeCalculado_PCH : cobroPeriodo,
        final: ((codigo_mae_banco == 'SCT' || codigo_mae_banco == 'PCH') && flg_renovacion == 0) ? importeCalculadoFinal + 100 : importeCalculadoFinal
    }
    //return ((codigo_mae_banco == 'SCT' || codigo_mae_banco == 'PCH') && flg_renovacion == 0) ? importeCalculadoFinal + 100 : importeCalculadoFinal;
}

function f_calculo15(importe_vsi,tasa,minimo_ctf,periodo,porte_ctf,codigo_mae_banco){
    let porte = parseFloat(porte_ctf);
    let minimo = parseFloat(minimo_ctf);
    //let calculo15 = (codigo_mae_banco == 'STD' || codigo_mae_banco == 'PCH') ? (importe_vsi*(tasa/100)*(15/360)) : (importe_vsi*(tasa/100)*(15/360))+porte; // okey
    let calculo15 = (codigo_mae_banco == 'SCT' || codigo_mae_banco == 'STD') ? (importe_vsi*(tasa/100)*(15/360))+porte : (importe_vsi*(tasa/100)*(15/360));
    let importeCalculado15 = (calculo15 > minimo) ? calculo15 : minimo/*+porte*/;
    let importeCalculado15_f = (codigo_mae_banco == 'SCT' || codigo_mae_banco == 'BCP' || codigo_mae_banco == 'BBVA') ? importeCalculado15+porte : importeCalculado15;

    let resultado = (codigo_mae_banco == 'STD' || codigo_mae_banco == 'PCH' || codigo_mae_banco == 'SCT') ? calculo15 : importeCalculado15_f;
    return {
        resultado: resultado
        //codigo_mae_banco: codigo_mae_banco
    };
    
}

function calculaDias(fecha_vcto,fecha_emision){ 	//Funciones para el formateo de las fechas de los DateBox
    let fechaFin = new Date(fecha_vcto);
    let fechaInicio = new Date(fecha_emision);
    let diferenciaMs = fechaFin - fechaInicio;
    let diferenciaDias = Math.abs(Math.floor(diferenciaMs / (1000 * 60 * 60 * 24)));

    return diferenciaDias;
}

function calculaPeriodos(fecha_vcto,fecha_emision){ 	//Funciones para el formateo de las fechas de los DateBox
    let fechaFin = new Date(fecha_vcto);
    let fechaInicio = new Date(fecha_emision);
    let diferenciaMs = fechaFin - fechaInicio;
    let diferenciaDias = Math.abs(Math.floor(diferenciaMs / (1000 * 60 * 60 * 24)));
    let periodos = Math.ceil(diferenciaDias / 90);

    return periodos;
}

function validarDevoluciónACF(documento_picking, importe){
    // Verificar si documento_picking empieza por 84
    if (/^84/.test(documento_picking)) {
        // Concatenar un "-" al inicio de la cadena importe
        importe = "- " + importe;
    }
    return importe;
}

function validarDevoluciónACF(documento_picking, importe){
    // Verificar si documento_picking empieza por 84
    if (/^07/.test(documento_picking) || /^NA/.test(documento_picking)) {
        // Concatenar un "-" al inicio de la cadena importe
        importe = "- " + importe;
    }
    return importe;
}


function ajaxAsociarSIT(idprocesocab,idcontrato,idsituaciones,tipo,contrato,combo='',idsituaciones_anterior='',idcartafianzafinal=''){
    let idarearesponsable = 0;
    if(combo != ''){
        if(combo == '#cmb_venta_ins'){
            idarearesponsable = 1;
        }else if(combo == '#cmb_cyc'){
            idarearesponsable = 2;
        }else if(combo == '#cmb_legal'){
            idarearesponsable = 3;
        }
    }
    //$.messager.confirm('Alerta', 'Se procederá a actualizar las situaciones del contrato <b>'+contrato+'</b> para el área de <b style=color:"red;">'+tipo+'</b>, ¿Desea continuar?',function(r){
        //if (r){
            $.ajax(url_mantenimiento,{
                type:'post',
                beforeSend: function(){
                    $.messager.progress({text:'Procesando...'});
                },
                complete: function(){
                    $.messager.progress('close');
                },
                success: function(datos){
                    if (datos.o_nres == 1){
                        if(idsituaciones == 0){
                            $(combo).combobox('clear');
                        }
                        let act_mae_cnt = actualizar_maestro_cnt();
                        let act_mae_cf = actualizar_maestro_cf();
                        $.messager.alert('Info',datos.o_msj,'info');
                        /* if(act_mae_cnt == false){          

                            $('#win_situaciones').window('close');
                            asociarSITUACIONES(idprocesocab,idcontrato,contrato); 
                            $('#dg_contrato').datagrid('load', {
                                _token          : '<?= csrf_token() ?>'
                                ,_acc			: 'listarContratos' 
                                ,idprocesocab	: idprocesocab
                                ,idcontrato     : idcontrato
                            });
                        } */
                    } 
                    else {
                        $.messager.alert('Error',datos.o_msj,'error');
                    }
                },
                error:function(x,e){
                    $.messager.alert('Error '+x.status,'Ocurrió un error en el servidor.','error');
                },
                data:{
                    _token               :'<?= csrf_token() ?>'
                    ,_acc                : 'asociarSIT'
                    ,idcontrato          : idcontrato
                    ,idsituaciones       : idsituaciones    
                    ,idarearesponsable   : idarearesponsable
                    ,idcartafianzafinal  : idcartafianzafinal
                },
                async: true,
                dataType: "json"
            });
    /*     }else{
            
            $(combo).combobox('clear');           
        }
    }); */
}

//Configuración de la orden de compra, opción: Comercial->Ord. De Compra A PARTIR DE HOY
function ordenes_de_compra() {
    let html = `
        <div id="win_ordencompra" title="Seguimiento Integral de Procesos" class="easyui-layout" style="width:99%;height:90%;">
            <table id="tb_ordenes_compra" style="width:100%">
                <tr>
                    <td style="width:100%">
                        <div id="toolbar_cnt" style="text-align: right;">
                            <a id="btn_consultar_oc">Consultar</a>
                            <a id="btn_limpiar_oc">Limpiar</a>
                            <a id="btn_ir_izquierda_oc">Izquierda</a>
                            <a id="btn_ir_derecha_oc">Derecha</a>
                        </div>
                        <div style="width:100%;padding:3px;">
                            <div id="filtro_oc_title" onclick="toggleFiltrosOC(0,'filtro_oc')" style="cursor: pointer;">
                                <b>FILTROS DE SEGUIMIENTO INTEGRAL</b>
                                <span id="arrow_icon_oc">&#9660;</span>
                            </div>
                        </div>
                        <div id="filtro_oc" style="width:100%;padding:3px; display: flex; align-items: center; flex-wrap: wrap;">
                            <input id="txt_proceso_oc" class="txt_enter_oc">
                            <input id="txt_entrega_oc" class="txt_enter_oc">
                            <input id="txt_pedido_oc" class="txt_enter_oc">
                            <input id="txt_numclienteoc_oc" class="txt_enter_oc">
                            <input id="txt_picking_oc" class="txt_enter_oc">
                            <input id="txt_factura_oc" class="txt_enter_oc">
                            <input id="txt_codigo_producto_oc" class="txt_enter_oc">
                            <input id="txt_desc_producto_oc" class="txt_enter_oc">
                            <input id="txt_contrato_oc" class="txt_enter_oc">
                            <input id="dt_fecha_desde_oc">
                            <input id="dt_fecha_fin_oc">
                        </div>
                    </td>
                </tr>
            </table>

            <table id="dg_orden_compra"></table>
        </div>
        <div id="mm_cnt" style="display:none;">
            <div data-options="iconCls:'icon-drive'" id="menu_adjuntos">Adjuntos</div>
        </div>

        <!-- Ventana para los adjuntos -->
        <div id="win_adjuntos" class="easyui-window" title="Adjuntos de Orden de Compra" style="width:800px;height:500px;padding:10px;" data-options="closed:true,modal:true">
            <div style="margin-bottom:10px;">
                <a id="btn_subir_adjunto" class="easyui-linkbutton" data-options="iconCls:'icon-add'">Subir</a>
            </div>
            <table id="dg_adjuntos" class="easyui-datagrid" style="width:100%;height:400px"
                data-options="singleSelect:true,fitColumns:true,rownumbers:true">
                <thead>
                    <tr>
                        <th data-options="field:'archivo',width:200">Archivo</th>
                        <th data-options="field:'valido',width:60,align:'center'">Válido</th>
                        <th data-options="field:'tipo',width:100">Tipo</th>
                        <th data-options="field:'estado',width:100">Estado Registro</th>
                        <th data-options="field:'fecha',width:100">Registro</th>
                        <th data-options="field:'descargar',width:80,align:'center'">Descargar</th>
                        <th data-options="field:'visualizar',width:80,align:'center'">Visualizar</th>
                    </tr>
                </thead>
            </table>
        </div>

        <!-- Modal para subir archivo -->
        <div id="dlg_subir_adjunto" class="easyui-dialog" title="Cargar Adjunto" style="width:750px;height:20%;">
            <div style="padding:7px;">
                <input id="cmb_tipo_adjunto">
                &nbsp;&nbsp;
            </div>
            <div style="padding:7px;">
                <input id="filebox_adjunto">
                &nbsp;&nbsp;
            </div>
            <div style="padding:3px; margin-top: 10px; text-align: right;">
                <a id="btn_guardar_adjunto">Grabar</a>
                <a id="btn_cancelar_adjunto">Salir</a>
            </div>
        </div>
    `;

    if ($('#win_ordencompra').length) {
        $('#win_ordencompra').empty();
        $('#win_ordencompra').remove();
    }

    $('body').append(html);

    // Definir funciones globales para formatters
    window.descargarArchivo = function(id) {
        window.location.href = url_consulta + '?_acc=DownloadAdjuntosOC&_idadjuntooc=' + id;
    };

    window.visualizarArchivo = function(id, ruta, nombre, tipo) {
        window.open(url_consulta + '?_acc=visualizacionPreviaOC&_path=' + ruta + '&_filename=' + nombre + '&_flg_tipo=' + tipo, '_blank');
    };

     // Modificar la función de guardar adjunto para usar FormData y AJAX
     window.guardarAdjunto = function() {
        if (!$('#fm_adjunto').form('validate')) {
            return;
        }

        var idtipoadjunto = $('#cmb_tipo_adjunto').combobox('getValue');
        var files = $('#file_adjunto').filebox('files');
        var formData = new FormData();

        // Verificar si hay archivos seleccionados
        if (files.length === 0) {
            $.messager.alert('Error', 'Debe adjuntar un archivo.', 'warning');
            return;
        }

        // Añadir archivos al FormData
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            formData.append('archivo', file, file.name);
        }

        // Añadir resto de datos
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('_acc', 'guardarAdjuntoOC');
        formData.append('_idpedido', window.selectedPedidoId);
        formData.append('tipo', idtipoadjunto);

        $.messager.confirm('Confirmación', '¿Está seguro de subir este archivo?', function(r) {
            if (r) {
                $.ajax({
                    url: url_consulta,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                        $.messager.progress({text: 'Procesando...'});
                    },
                    complete: function() {
                        $.messager.progress('close');
                    },
                    success: function(result) {
                        try {
                            if (result.success) {
                                $('#dlg_subir_adjunto').dialog('close');
                                $('#dg_adjuntos').datagrid('reload');
                                $.messager.show({
                                    title: 'Éxito',
                                    msg: 'Archivo adjuntado correctamente'
                                });
                            } else {
                                $.messager.alert('Error', result.mensaje || 'Error al guardar', 'error');
                            }
                        } catch (e) {
                            $.messager.alert('Error', 'Error en la respuesta del servidor', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        $.messager.alert('Error', 'Error al subir archivo: ' + error, 'error');
                    }
                });
            }
        });
    };

    // Estilos para el datagrid
    $('<style id="oc_styles">')
        .text(`
            .datagrid-body {
                overflow-x: auto !important;
            }
            .datagrid-view2 .datagrid-body {
                overflow-x: auto !important;
            }
            .datagrid-header {
                overflow-x: hidden !important;
            }
        `)
        .appendTo('head');

    // Iconos para limpieza de campos
    var iconolimpieza_oc = [{
        iconCls: 'icon-clear',
        handler: function(e) {
            var target = $(e.data.target);
            target.textbox('clear');
            target.textbox('textbox').focus();
            
            // Recargar la tabla al limpiar un campo usando el icono
            recargar_tabla_oc();
        }
    }];

    // Configuración de los filtros
    $('#txt_proceso_oc').textbox({
        icons: iconolimpieza_oc,
        width: '20%',
        height: 20,
        labelWidth: 100,
        labelAlign: 'left',
        label: 'Proceso',
        prompt: 'Número de Proceso',
        onChange: function(newValue, oldValue) {
            if (oldValue && !newValue) {
                recargar_tabla_oc();
            }
        }
    });

    $('#txt_entrega_oc').textbox({
        icons: iconolimpieza_oc,
        width: '20%',
        height: 20,
        labelWidth: 100,
        labelAlign: 'left',
        label: 'Entrega',
        prompt: 'Número de entrega',
        onChange: function(newValue, oldValue) {
            if (oldValue && !newValue) {
                recargar_tabla_oc();
            }
        }
    });

    $('#txt_pedido_oc').textbox({
        icons: iconolimpieza_oc,
        width: '20%',
        height: 20,
        labelWidth: 70,
        labelAlign: 'left',
        label: 'Pedido',
        prompt: 'Número de pedido',
        onChange: function(newValue, oldValue) {
            if (oldValue && !newValue) {
                recargar_tabla_oc();
            }
        }
    });

    $('#txt_numclienteoc_oc').textbox({
        icons: iconolimpieza_oc,
        width: '20%',
        height: 20,
        labelWidth: 110,
        labelAlign: 'left',
        label: 'N° Cliente OC',
        prompt: 'Número cliente OC',
        onChange: function(newValue, oldValue) {
            if (oldValue && !newValue) {
                recargar_tabla_oc();
            }
        }
    });

    // Nuevos filtros
    $('#txt_picking_oc').textbox({
        icons: iconolimpieza_oc,
        width: '20%',
        height: 20,
        labelWidth: 110,
        labelAlign: 'left',
        label: 'Picking',
        prompt: 'Número de picking',
        onChange: function(newValue, oldValue) {
            if (oldValue && !newValue) {
                recargar_tabla_oc();
            }
        }
    });

    $('#txt_factura_oc').textbox({
        icons: iconolimpieza_oc,
        width: '20%',
        height: 20,
        labelWidth: 110,
        labelAlign: 'left',
        label: 'Factura',
        prompt: 'Número de factura',
        onChange: function(newValue, oldValue) {
            if (oldValue && !newValue) {
                recargar_tabla_oc();
            }
        }
    });

    // Nuevos filtros
    $('#txt_codigo_producto_oc').textbox({
        icons: iconolimpieza_oc,
        width: '20%',
        height: 20,
        labelWidth: 120,
        labelAlign: 'left',
        label: 'Código Producto',
        prompt: 'Código del producto',
        onChange: function(newValue, oldValue) {
            if (oldValue && !newValue) {
                recargar_tabla_oc();
            }
        }
    });

    $('#txt_desc_producto_oc').textbox({
        icons: iconolimpieza_oc,
        width: '20%',
        height: 20,
        labelWidth: 120,
        labelAlign: 'left',
        label: 'Desc. Producto',
        prompt: 'Descripción del producto',
        onChange: function(newValue, oldValue) {
            if (oldValue && !newValue) {
                recargar_tabla_oc();
            }
        }
    });

    $('#txt_contrato_oc').textbox({
        icons: iconolimpieza_oc,
        width: '20%',
        height: 20,
        labelWidth: 120,
        labelAlign: 'left',
        label: 'Contrato',
        prompt: 'Número de contrato',
        onChange: function(newValue, oldValue) {
            if (oldValue && !newValue) {
                recargar_tabla_oc();
            }
        }
    });

    // Configuración de datebox para fechas
    $('#dt_fecha_desde_oc').datebox({
        width: '20%',
        height: 20,
        labelWidth: 120,
        labelAlign: 'left',
        label: 'Fecha Desde',
        prompt: 'Fecha inicial',
        parser: new_parser_date,
        formatter: new_formatter_date,
        editable: false,
        onShowPanel: function() {
            var opts = $(this).datebox('options');
            var fechaActual = new Date();
            fechaActual.setFullYear(fechaActual.getFullYear() - 5);
            var fechaMaxima = new Date();
            fechaMaxima.setFullYear(fechaMaxima.getFullYear());
            $(this).datebox('calendar').calendar({
                validator: function(date) {
                    if (date >= fechaActual && date <= fechaMaxima) {
                        return true;
                    } else {
                        return false;
                    }
                }
            });
        },
        onChange: function(newValue, oldValue) {
            if (oldValue && !newValue) {
                recargar_tabla_oc();
            }
        }
    });

    $('#dt_fecha_fin_oc').datebox({
        width: '20%',
        height: 20,
        labelWidth: 100,
        labelAlign: 'left',
        label: 'Fecha Hasta',
        prompt: 'Fecha final',
        parser: new_parser_date,
        formatter: new_formatter_date,
        editable: false,
        value: hoy,
        onShowPanel: function() {
            var opts = $(this).datebox('options');
            var fechaActual = new Date();
            fechaActual.setFullYear(fechaActual.getFullYear() - 5);
            var fechaMaxima = new Date();
            fechaMaxima.setFullYear(fechaMaxima.getFullYear());
            $(this).datebox('calendar').calendar({
                validator: function(date) {
                    if (date >= fechaActual && date <= fechaMaxima) {
                        return true;
                    } else {
                        return false;
                    }
                }
            });
        },
        onChange: function(newValue, oldValue) {
            if (oldValue && !newValue || (oldValue && newValue === hoy)) {
                recargar_tabla_oc();
            }
        }
    });

    // Configuración de los botones
    $('#btn_consultar_oc').linkbutton({
        'iconCls': 'icon-search',
        height: 20,
        onClick: function() {
            recargar_tabla_oc();
        }
    });

    // Configurar combobox y filebox según el código de referencia
    $('#cmb_tipo_adjunto').combobox({
        width       : '90%',
        height      : 20,
        labelWidth  : 80,
        labelAlign  : 'left',
        label       : 'Tipo',
        prompt      : '[Seleccionar]',
        url         : url_consulta,
        limitToList : true,
        hasDownArrow: true,
        multiple    : false,
        required    : true,
        editable    : true,
        queryParams : {
            _token : $('meta[name="csrf-token"]').attr('content'),
            _acc   : 'listarTiposAdjunto'
        },
        panelHeight : 'auto',
        panelMaxHeight: 200,
        valueField  : 'id',
        textField   : 'text'
    });

    $('#filebox_adjunto').filebox({
        label      : 'Adjunto(s)',
        labelWidth : 80,
        labelAlign : 'left',
        buttonText : 'Seleccione Archivos',
        buttonAlign: 'left',
        width      : '90%',
        height     : 20,
        prompt     : '[Seleccionar - Tamaño máximo 10MB]',
        multiple   : true,
        required   : false,
        onChange   : function (newValue, oldValue) {
            var fileName = $(this).filebox('getValue');
            var fileExtension = fileName.substr(fileName.lastIndexOf('.') + 1);
            let textoval = '[pdf,doc,docx,png,jpeg]'; 
            var allowedExtensions = ['pdf','doc','docx','png','jpeg']; 
            if (!allowedExtensions.includes(fileExtension.toLowerCase())) {
                $.messager.alert('Error', 'Formato de archivo no permitido. Por favor, adjuntar algunas de las extensiones habilitadas: '+textoval+'.', 'error');
                $(this).filebox('clear');
                return;
            }
        }
    });

    $('#btn_guardar_adjunto').linkbutton({
        iconCls: 'icon-save',
        onClick: function() {
            var idtipoadjunto = $('#cmb_tipo_adjunto').combobox('getValue');
            let files = $('#filebox_adjunto').filebox('files');
            var formData = new FormData();
            
            // Validaciones
            if(idtipoadjunto.length == 0) {
                $.messager.alert('Error','Tipo adjunto es obligatorio.','warning');
                return;
            }

            if(files.length == 0) {
                $.messager.alert('Error','Debe adjuntar por lo menos un archivo.','warning');
                return;
            }
            
            // Añadir archivos al FormData
            for(var i=0; i<files.length; i++) {
                var file = files[i];
                var ext = file.name.split('.').pop();
                var newFileName = window.selectedPedidoId + '_' + Date.now() + '.' + ext;
                formData.append('archivos[]', file, newFileName);
            }
            
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            formData.append('_acc', 'guardarAdjuntoOC');
            formData.append('_idpedido', window.selectedPedidoId);
            formData.append('tipo', idtipoadjunto);
            
            $.messager.confirm('Alerta', 'Se procederá a cargar los adjuntos brindados, ¿Desea continuar?', function(r) {
                if (r) {
                    $.ajax(url_consulta, {
                        type: 'post',
                        data: formData,
                        contentType: false,
                        processData: false,
                        beforeSend: function() {
                            $.messager.progress({text:'Procesando...'});
                        },
                        complete: function() {
                            $.messager.progress('close');
                        },
                        success: function(datos) {
                            if (datos.success) {
                                $('#dlg_subir_adjunto').dialog('close');
                                $.messager.alert('Info', 'Archivo adjuntado correctamente', 'info');
                                $('#dg_adjuntos').datagrid('reload');
                            } else {
                                $.messager.alert('Error', datos.mensaje || 'Error al guardar', 'error');
                            }
                        },
                        error: function(x,e) {
                            $.messager.alert('Error '+x.status, 'Ocurrió un error en el servidor.', 'error');
                        },
                        async: true
                    });
                }
            });
        }
    });

    $('#btn_cancelar_adjunto').linkbutton({
        iconCls: 'icon-cancel',
        onClick: function() {
            $('#dlg_subir_adjunto').dialog('close');
        }
    });

    $('#dlg_subir_adjunto').dialog({
        modal: true, 
        collapsible: false, 
        closable: true, 
        minimizable: false, 
        maximizable: false, 
        closed: true, 
        center: true, 
        resizable: false
    });

    $('#btn_limpiar_oc').linkbutton({
        'iconCls': 'icon-clear',
        height: 20,
        onClick: function() {
            $('#txt_proceso_oc').textbox('clear');
            $('#txt_entrega_oc').textbox('clear');
            $('#txt_pedido_oc').textbox('clear');
            $('#txt_numclienteoc_oc').textbox('clear');
            $('#txt_picking_oc').textbox('clear');
            $('#txt_factura_oc').textbox('clear');
            $('#txt_codigo_producto_oc').textbox('clear');
            $('#txt_desc_producto_oc').textbox('clear');
            $('#txt_contrato_oc').textbox('clear');
            $('#dt_fecha_desde_oc').datebox('clear');
            $('#dt_fecha_fin_oc').datebox('setValue', hoy);
            
            // Recargar la tabla después de limpiar los filtros
            recargar_tabla_oc();
        }
    });

    // Botones para navegar horizontalmente
    $('#btn_ir_izquierda_oc').linkbutton({
        'iconCls': 'icon-arrow-left',
        height: 20,
        onClick: function() {
            let panel = $('#dg_orden_compra').datagrid('getPanel');
            let body = panel.find('.datagrid-body');
            body.scrollLeft(0); // Scroll hasta el inicio
        }
    });

    $('#btn_ir_derecha_oc').linkbutton({
        'iconCls': 'icon-arrow-right',
        height: 20,
        onClick: function() {
            let panel = $('#dg_orden_compra').datagrid('getPanel');
            let body = panel.find('.datagrid-body');
            let view2 = panel.find('.datagrid-view2 .datagrid-body');
            
            // Scroll hasta el final
            let scrollWidth = view2[0].scrollWidth;
            body.scrollLeft(scrollWidth);
        }
    });

    // Añadir evento Enter para realizar búsqueda
    $('.txt_enter_oc').each(function(i, e) {
        $(e).textbox('textbox').bind('keydown', function(e) {
            if (e.keyCode == 13) {
                recargar_tabla_oc();
            }
        });
    });

    // Formatters para la tabla
    function formatDecimal(val, row) {
        if (val === null || val === undefined) return '';
        return parseFloat(val).toFixed(2);
    }

    function formatMoney(val, row) {
        if (val === null || val === undefined) return '';
        return parseFloat(val).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    // Inicializar el menú contextual
    $('#mm_cnt').menu({
        onClick: function(item) {
            if (item.text === 'Adjuntos') {
                mostrarAdjuntos(window.selectedPedidoId);
            }
        }
    });

    // Configuración de la ventana de adjuntos
    $('#win_adjuntos').window({
        closed: true,
        modal: true,
        minimizable: false,
        maximizable: true
    });

    // Configuración del botón para subir adjuntos
    $('#btn_subir_adjunto').linkbutton({
        onClick: function() {
            $('#fm_adjunto').form('clear');
            $('#pedido_id_adjunto').val(window.selectedPedidoId);
            $('#fm_adjunto').find('input[name="_token"]').val($('meta[name="csrf-token"]').attr('content'));
            $('#dlg_subir_adjunto').dialog('open').dialog('setTitle', 'Subir Adjunto');
        }
    });

    // Configuración del datagrid principal
    $('#dg_orden_compra').datagrid({
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
        // Configuración para ordenamiento
        sortName: 'FechaDocumentoPedido',
        sortOrder: 'desc',
        remoteSort: true, // Importante: esto hace que el ordenamiento se procese en el servidor
        queryParams: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            _acc: 'listarSeguimientoIntegral',
            proceso: $('#txt_proceso_oc').textbox('getValue'),
            entrega: $('#txt_entrega_oc').textbox('getValue'),
            pedido: $('#txt_pedido_oc').textbox('getValue'),
            numclienteoc: $('#txt_numclienteoc_oc').textbox('getValue'),
            picking: $('#txt_picking_oc').textbox('getValue'),
            factura: $('#txt_factura_oc').textbox('getValue'),
            fecha_desde: $('#dt_fecha_desde_oc').datebox('getValue'),
            fecha_hasta: $('#dt_fecha_fin_oc').datebox('getValue')
        },
        toolbar: '#tb_ordenes_compra',
        frozenColumns: [[
            {title: 'PEDIDO', colspan: 1, align: 'center'},
        ], [
            {field: 'NumeroDocumentoPedido', title: 'Pedido', align: 'center', halign: 'center', width: 120, sortable: true},
        ]],
        columns: [[
            {title: 'DATOS PEDIDO', colspan: 6, align: 'center'},
            {title: 'DATOS DEL CONTRATO', colspan: 4, align: 'center'},
            {title: 'DATOS PICKING', colspan: 4, align: 'center'},
            {title: 'DATOS FACTURA', colspan: 5, align: 'center'},
            {title: 'DATOS ENTREGA', colspan: 6, align: 'center'},
            {title: 'DATOS PROCESO', colspan: 8, align: 'center'},
            ],[

            // # DATOS PEDIDO
            {field: 'FechaDocumentoPedido', title: 'Fecha Pedido', align: 'center', halign: 'center', width: 120, sortable: true, 
                formatter: function(value, row) {
                if (!value) return '';
                
                // Para formato YYYYMMDD (8 caracteres)
                if (typeof value === 'string' && value.length === 8) {
                    return value.substr(0, 4) + '-' + value.substr(4, 2) + '-' + value.substr(6, 2);
                }
                
                // Para fechas con timestamp, extraer solo la parte de fecha
                if (value.includes(' ') && value.includes(':')) {
                    return value.split(' ')[0];
                }
                
                return value;
            }},
            {field: 'PosicionPedido', title: 'Pos. Ped.', align: 'center', halign: 'center', width: 80},
            {field: 'NumeroClienteOC', title: 'N° Cliente OC', align: 'center', halign: 'center', width: 120},
            {field: 'PuntoLlegada', title: 'Punto de Llegada', align: 'left', halign: 'center', width: 350},
            {field: 'CantidadPrevistaPedido', title: 'Cantidad', align: 'right', halign: 'center', width: 100, formatter: formatDecimal},
            {
                field: 'desc_producto',
                title: 'Producto',
                align: 'left',
                halign: 'center',
                width: 400,
                formatter: function(value, row) {
                    return '[' + row.codigo_producto + '] ' + value;
                }
            },


            // # DATOS DEL CONTRATO
            {field: 'NumeroContrato', title: 'Contrato', align: 'left', halign: 'center', width: 100},
            {field: 'FechaInicioContrato', title: 'Fecha Suscripción<br>Contrato', align: 'center', halign: 'center', width: 170, sortable: true, 
                formatter: function(value, row) {
                if (!value) return '';
                
                // Para formato YYYYMMDD (8 caracteres)
                if (typeof value === 'string' && value.length === 8) {
                    return value.substr(0, 4) + '-' + value.substr(4, 2) + '-' + value.substr(6, 2);
                }
                
                // Para fechas con timestamp, extraer solo la parte de fecha
                if (value.includes(' ') && value.includes(':')) {
                    return value.split(' ')[0];
                }
                
                return value;
            }},
            {field: 'FechaFinContrato', title: 'Fecha Finalización<br>Contrato', align: 'center', halign: 'center', width: 170, sortable: true, 
                formatter: function(value, row) {
                if (!value) return '';
                
                // Para formato YYYYMMDD (8 caracteres)
                if (typeof value === 'string' && value.length === 8) {
                    return value.substr(0, 4) + '-' + value.substr(4, 2) + '-' + value.substr(6, 2);
                }
                
                // Para fechas con timestamp, extraer solo la parte de fecha
                if (value.includes(' ') && value.includes(':')) {
                    return value.split(' ')[0];
                }
                
                return value;
            }},
            {
                field: 'false', // Un campo en tus datos que indique si tiene contrato (true/false)
                title: 'Contrato',
                align: 'center',
                halign: 'center',
                width: 120,
                formatter: function(value, row, index) {
                    if (value) {
                        return '<a href="javascript:void(0)" class="easyui-linkbutton l-btn l-btn-small" data-options="iconCls:\'icon-ok\'" style="width:70px">Sí</a>';
                    } else {
                        return '<a href="javascript:void(0)" class="easyui-linkbutton l-btn l-btn-small" data-options="iconCls:\'icon-cancel\'" style="width:70px">No</a>';
                    }
                }
            },
            
            // # DATOS PICKING
            {field: 'NumeroDocumentoPicking', title: 'Picking', align: 'center', halign: 'center', width: 120},
            {field: 'FechaDocumentoPicking', title: 'Fecha Picking', align: 'center', halign: 'center', width: 120, 
                formatter: function(value, row) {
                if (!value) return '';
                if (typeof value === 'string' && value.length === 8) {
                    return value.substr(0, 4) + '-' + value.substr(4, 2) + '-' + value.substr(6, 2);
                }
                if (value.includes(' ') && value.includes(':')) {
                    return value.split(' ')[0];
                }
                return value;
            }},
            {field: 'GuiaRemision', title: 'Guía Remisión', align: 'center', halign: 'center', width: 120},
            {field: 'PesoTotalPicking', title: 'Peso Total', align: 'right', halign: 'center', width: 100, formatter: formatDecimal},
            
            // # DATOS FACTURA
            {field: 'NumeroDocumentoFactura', title: 'Factura', align: 'center', halign: 'center', width: 120},
            {field: 'SerieFacturaSunat', title: 'Serie', align: 'center', halign: 'center', width: 80},
            {field: 'NumeroFacturaSunat', title: 'Núm. Factura', align: 'center', halign: 'center', width: 120},
            {field: 'FechaDocumentoFactura', title: 'Fecha Factura', align: 'center', halign: 'center', width: 120, 
                formatter: function(value, row) {
                if (!value) return '';
                if (typeof value === 'string' && value.length === 8) {
                    return value.substr(0, 4) + '-' + value.substr(4, 2) + '-' + value.substr(6, 2);
                }
                if (value.includes(' ') && value.includes(':')) {
                    return value.split(' ')[0];
                }
                return value;
            }},
            {field: 'ValorNetoFactura', title: 'Valor Factura', align: 'right', halign: 'center', width: 120, formatter: formatMoney},

            // # DATOS ENTREGA
            {field: 'NumeroDocumentoEntrega', title: 'Entrega', align: 'center', halign: 'center', width: 120},
            {field: 'FechaDocumentoEntrega', title: 'Fecha Entrega', align: 'center', halign: 'center', width: 120, 
                formatter: function(value, row) {
                if (!value) return '';
                
                // Para formato YYYYMMDD (8 caracteres)
                if (typeof value === 'string' && value.length === 8) {
                    return value.substr(0, 4) + '-' + value.substr(4, 2) + '-' + value.substr(6, 2);
                }
                
                // Para fechas con timestamp, extraer solo la parte de fecha
                if (value.includes(' ') && value.includes(':')) {
                    return value.split(' ')[0];
                }
                
                return value;
            }},
            {field: 'PosicionEntrega', title: 'Pos. Ent.', align: 'center', halign: 'center', width: 80},
            {field: 'CantidadPrevistaEntrega', title: 'Cantidad', align: 'right', halign: 'center', width: 100, formatter: formatDecimal},
            {field: 'UnidadMedidaEntrega', title: 'UM', align: 'center', halign: 'center', width: 50},
            {field: 'ValorNetoEntrega', title: 'Valor Neto', align: 'right', halign: 'center', width: 120, formatter: formatMoney},

            // # DATOS PROCESO
            {field: 'NumeroDocumentoProceso', title: 'Proceso', align: 'center', halign: 'center', width: 120},
            {field: 'FechaDocumentoProceso', title: 'Fecha Proceso', align: 'center', halign: 'center', width: 120, 
                formatter: function(value, row) {
                if (!value) return '';
                
                // Para formato YYYYMMDD (8 caracteres)
                if (typeof value === 'string' && value.length === 8) {
                    return value.substr(0, 4) + '-' + value.substr(4, 2) + '-' + value.substr(6, 2);
                }
                
                // Para fechas con timestamp, extraer solo la parte de fecha
                if (value.includes(' ') && value.includes(':')) {
                    return value.split(' ')[0];
                }
                
                return value;
            }},
            {field: 'DescripcionProceso', title: 'Descripción Proceso', align: 'left', halign: 'center', width: 300},
            {field: 'PosicionProceso', title: 'Pos. Proc.', align: 'center', halign: 'center', width: 80},
            {field: 'JerarquiaProductoProceso', title: 'Jerarquía Producto', align: 'left', halign: 'center', width: 150},
            {field: 'CantidadPrevistaProceso', title: 'Cantidad', align: 'right', halign: 'center', width: 100, formatter: formatDecimal},
            {field: 'UnidadMedidaProceso', title: 'UM', align: 'center', halign: 'center', width: 50},
            {field: 'ValorNetoProceso', title: 'Valor Neto', align: 'right', halign: 'center', width: 120, formatter: formatMoney},

        ]],
        onLoadSuccess: function(data) {
            forzarScrollHorizontal();
            setTimeout(function() {
                // Aplicar colores a los encabezados
                $('.datagrid-header-row:first-child td').each(function() {
                    let text = $(this).text();
                    if (text === 'PEDIDO') {
                        $(this).css('background-color', '#5B9BD5').css('color', 'white').css('font-weight', 'bold');
                    } else if (text === 'DATOS PEDIDO') {
                        $(this).css('background-color', '#5B9BD5').css('color', 'white').css('font-weight', 'bold');
                    } else if (text === 'DATOS DEL CONTRATO') {
                        $(this).css('background-color', '#eab676').css('color', 'white').css('font-weight', 'bold');
                    } else if (text === 'DATOS PROCESO') {
                        $(this).css('background-color', '#ED7D31').css('color', 'white').css('font-weight', 'bold');
                    } else if (text === 'DATOS ENTREGA') {
                        $(this).css('background-color', '#70AD47').css('color', 'white').css('font-weight', 'bold');
                    } else if (text === 'DATOS PICKING') {
                        $(this).css('background-color', '#4472C4').css('color', 'white').css('font-weight', 'bold');
                    } else if (text === 'DATOS FACTURA') {
                        $(this).css('background-color', '#A5A5A5').css('color', 'white').css('font-weight', 'bold');
                    }
                });
            }, 200);
        },
        onClickRow: function(index, row) {
            $(this).datagrid('uncheckRow', index);
        },
        onRowContextMenu: function(e, index, row) {
            e.preventDefault();
            $(this).datagrid('selectRow', index);
            // Almacenar el NumeroDocumentoPedido seleccionado
            window.selectedPedidoId = row.NumeroDocumentoPedido;
            $('#mm_cnt').menu('show', {
                left: e.pageX,
                top: e.pageY
            });
        },
        onBeforeLoad: function(params) {
            // Asegurar que todos los filtros se incluyan en cada solicitud
            params._token = $('meta[name="csrf-token"]').attr('content');
            params._acc = 'listarSeguimientoIntegral';
            params.proceso = $('#txt_proceso_oc').textbox('getValue');
            params.entrega = $('#txt_entrega_oc').textbox('getValue');
            params.pedido = $('#txt_pedido_oc').textbox('getValue');
            params.numclienteoc = $('#txt_numclienteoc_oc').textbox('getValue');
            params.picking = $('#txt_picking_oc').textbox('getValue');
            params.factura = $('#txt_factura_oc').textbox('getValue');
            params.codigo_producto = $('#txt_codigo_producto_oc').textbox('getValue');
            params.desc_producto = $('#txt_desc_producto_oc').textbox('getValue');
            params.contrato = $('#txt_contrato_oc').textbox('getValue');
            params.fecha_desde = $('#dt_fecha_desde_oc').datebox('getValue');
            params.fecha_hasta = $('#dt_fecha_fin_oc').datebox('getValue');
            return true;
        },
        onLoadError: function(xhr, status, error) {
            console.error('Error de carga:', xhr.responseText);
            $.messager.alert('Error', 'Error al cargar datos: ' + error, 'error');
        }
    }).datagrid('getPager').pagination({
        beforePageText: 'Pag. ',
        afterPageText: 'de {pages}',
        displayMsg: 'Del {from} al {to}, de {total} items.',
    });

    // Configuración del datagrid de adjuntos
    $('#dg_adjuntos').datagrid({
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
        _token: $('meta[name="csrf-token"]').attr('content'),
        _acc: 'listarAdjuntosOC',
        _idpedido: window.selectedPedidoId
    },
    columns: [[
        {field: 'archivo', title: 'Archivo', align: 'left', halign: 'center', width: '37.67%', formatter: function(val, row) {
            return `
                <div style="font-weight:bold;">Nombre de Archivo: ${row.archivo}</div>
                <div style="font-size:10px;color:blue;">Extensión: ${row.archivo.split('.').pop()}</div>
                <div style="font-size:10px;color:blue;">Tamaño: ${formatFileSize(row.tamanioadjunto || 0)}</div>
            `;
        }},
        {field: 'valido', title: 'Válido', align: 'center', halign: 'center', width: '4.79%', formatter: function(val) {
            if (val === 'Sí') {
                return `<span style="border-radius:5px;background-color:green;color:white;padding:3px;cursor:pointer;">SI</span>`;
            } else {
                return `<span style="border-radius:5px;background-color:red;color:white;padding:3px;cursor:pointer;">NO</span>`;
            }
        }},
        {field: 'tipo', title: 'Tipo', align: 'center', halign: 'center', width: '10.27%'},
        {field: 'estado', title: 'Estado<br>Registro', align: 'center', halign: 'center', width: '13.01%', formatter: function(val) {
            return `<span style="border-radius:5px;background-color:#4472C4;color:white;padding:3px;cursor:pointer;">${val}</span>`;
        }},
        {field: 'fecha', title: 'Registro', align: 'left', halign: 'center', width: '20.55%', formatter: function(val, row) {
            return `
                <div><span style="font-size:11px;color:blue;">Usuario Registro: </span><span style="font-size:11px;">${row.usuario || 'Usuario'}</span></div>
                <div><span style="font-size:11px;color:blue;">Fecha Registro: </span>${val}</div>
            `;
        }},
        {field: 'descargar', title: 'Descargar', align: 'center', halign: 'center', width: '6.85%', formatter: function(val, row) {
            return `<img src="${ambiente || ''}img/icons/flecha_verde_down.png" onclick="descargarArchivo(${row.id})" title="Descargar" style="cursor:pointer;width:16px;height:16px">`;
        }},
        {field: 'visualizar', title: 'Visualizar', align: 'center', halign: 'center', width: '6.85%', formatter: function(val, row) {
            const ext = row.archivo.split('.').pop().toLowerCase();
            if(['pdf', 'jpg', 'png', 'jpeg'].includes(ext)) {
                return `<img src="${ambiente || ''}img/icons/view.png" onclick="visualizarArchivo(${row.id}, '${row.ruta}', '${row.archivo}', ${row.idtipo})" title="Visualizar" style="cursor:pointer;width:16px;height:16px">`;
            } else {
                return ``;
            }
        }}
    ]],
    onLoadError: function() {
        $.messager.alert('Error', 'Error al mostrar los datos, vuelva a intentar', 'error');
    }
}).datagrid('getPager').pagination({
    beforePageText: 'Pag. ',
    afterPageText: 'de {pages}',
    displayMsg: 'Del {from} al {to}, de {total} items.'
});

// Función para formatear tamaño de archivo
function formatFileSize(bytes) {
    if (bytes === 0) return '0 KB';
    return (bytes / 1024).toFixed(2) + ' KB';
}

    // Inicializar el diálogo de subir adjunto
    $('#dlg_subir_adjunto').dialog({
        closed: true,
        modal: true,
        buttons: '#dlg-buttons-adjunto'
    });

    // Función para mostrar adjuntos
    function mostrarAdjuntos(pedidoId) {
        if (!pedidoId) {
            $.messager.alert('Error', 'No se ha seleccionado una orden de compra', 'error');
            return;
        }
        
        $('#win_adjuntos').window('open').window('setTitle', 'Adjuntos - Orden de Compra: ' + pedidoId);
        
        $('#dg_adjuntos').datagrid('loadData', {total: 0, rows: []});
        $('#dg_adjuntos').datagrid('options').url = url_consulta;
        $('#dg_adjuntos').datagrid('options').queryParams = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            _acc: 'listarAdjuntosOC',
            _idpedido: pedidoId
        };
        $('#dg_adjuntos').datagrid('reload');
    }

    // Colorear el datagrid (si existe la función)
    if (typeof colorear_dg_contratos === 'function') {
        colorear_dg_contratos('#dg_orden_compra');
    }

    // Función para forzar la aparición del scroll horizontal
    function forzarScrollHorizontal() {
        // Si no hay filas, agregar una fila dummy vacía
        if ($('#dg_orden_compra').datagrid('getRows').length === 0) {
            $('#dg_orden_compra').datagrid('appendRow', {});
        }
        
        // Establecer el ancho mínimo de la tabla para que siempre muestre scroll
        let totalWidth = 0;
        let columnas = $('#dg_orden_compra').datagrid('options').columns[0];
        for (let i = 0; i < columnas.length; i++) {
            totalWidth += parseInt(columnas[i].width);
        }
        
        // Asegurar que el contenedor tenga el ancho total de las columnas
        let panel = $('#dg_orden_compra').datagrid('getPanel');
        let view2 = panel.find('.datagrid-view2');
        let headerTable = view2.find('.datagrid-header-inner table');
        let bodyTable = view2.find('.datagrid-body table');
        
        headerTable.width(totalWidth);
        bodyTable.width(totalWidth);
        
        // Hacer visible el scroll horizontal
        panel.find('.datagrid-body').css('overflow-x', 'auto');
        view2.find('.datagrid-body').css('overflow-x', 'auto');
    }

    // Configuración de la ventana
    $('#win_ordencompra').window({
        modal: true,
        collapsible: false,
        closable: true,
        minimizable: false,
        maximizable: true,
        closed: false,
        center: true,
        resizable: false,
        onResize: function() {
            $('#dg_orden_compra').datagrid('resize');
            setTimeout(forzarScrollHorizontal, 100);
        },
        onClose: function() {
            $('#oc_styles').remove();
        }
    });

    // Forzar el scroll horizontal después de que todo se cargue
    setTimeout(forzarScrollHorizontal, 500);

    // Función para recargar la tabla
    function recargar_tabla_oc() {
        var pager = $('#dg_orden_compra').datagrid('getPager');
        var opts = pager.pagination('options');
        
        $('#dg_orden_compra').datagrid('load', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            _acc: 'listarSeguimientoIntegral',
            proceso: $('#txt_proceso_oc').textbox('getValue'),
            entrega: $('#txt_entrega_oc').textbox('getValue'),
            pedido: $('#txt_pedido_oc').textbox('getValue'),
            numclienteoc: $('#txt_numclienteoc_oc').textbox('getValue'),
            picking: $('#txt_picking_oc').textbox('getValue'),
            factura: $('#txt_factura_oc').textbox('getValue'),
            codigo_producto: $('#txt_codigo_producto_oc').textbox('getValue'),
            desc_producto: $('#txt_desc_producto_oc').textbox('getValue'),
            contrato: $('#txt_contrato_oc').textbox('getValue'),
            fecha_desde: $('#dt_fecha_desde_oc').datebox('getValue'),
            fecha_hasta: $('#dt_fecha_fin_oc').datebox('getValue'),
            page: opts.pageNumber,
            rows: opts.pageSize
        });
    }

    // Función para alternar la visualización de los filtros
    window.toggleFiltrosOC = function(flg_limpieza, tipo) {
        var filtro = document.getElementById(tipo);
        var arrowIcon = document.getElementById("arrow_icon_oc");
        if (flg_limpieza == 0) {
            if (filtro.style.display === "none") {
                filtro.style.display = "flex";
                arrowIcon.innerHTML = "&#9650;"; // Flecha hacia arriba
            } else {
                filtro.style.display = "none";
                arrowIcon.innerHTML = "&#9660;"; // Flecha hacia abajo
            }
        } else {
            filtro.style.display = "flex";
            arrowIcon.innerHTML = "&#9660;"; // Flecha hacia abajo
        }
    };
}

</script>