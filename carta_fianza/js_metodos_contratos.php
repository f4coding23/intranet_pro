<script>

function contratos(idprocesocab,doc_proceso,proceso,tipo_proceso,cliente, solicitante, idcontrato=''){
    if ($('#win_contrato').length > 0) {
        $('#win_contrato').window('destroy');
    }
    if ($('#win_wizard_ctf').length > 0) {
        $('#win_wizard_ctf').window('destroy');
    }
    if(idcontrato > 0){ // VENGO DESDE LA CARTA FIANZA
        $('#win_ver_carta_fianza_final').window('close');
    }

    let html = `
        <div id="win_contrato" title="Seguimiento de Contratos [${doc_proceso}] ${proceso}" class="easyui-layout" style="width:99%;height:95%;">
            <table id="tb_contrato" style="width:100%">
                <tr>
                    <td style="width:100%">
                        <div id="toolbar_contratos" style="text-align: right;">
                            <a id="btn_actualizar_cnt">Actualizar</a>
                            <a id="btn_nueva_cnt">Contrato</a>
                            <a id="btn_ir_izquierda_cnt">Ir Izquierda</a>
                            <a id="btn_ir_derecha_cnt">Ir Opciones</a>
                        </div>
                        <div style="width:100%;padding:3px; display: flex; align-items: center;">
                            <input id="txt_cliente_cnt">
                            <!--<input id="txt_proceso_cnt">-->
                            <input id="txt_tipo_proceso_ctn">
                            <input id="ck_cnt_onlyvigentes">
                        </div>
                    </td>
                </tr>
            </table>

            <table id="dg_contrato"></table>

            <!-- HTML para el menú emergente -->
            <div id="mm_cnt" style="display:none;">
                <div data-options="iconCls:'icon-carta'">Carta Fianza</div>
                <!--<div data-options="iconCls:'icon-user'">Situaciones</div>
                <div data-options="iconCls:'icon-drive'">Adjuntos</div>-->
                <div data-options="iconCls:'icon-cancel'">Anular</div>
            </div>

        </div>
    `;

    if ($('#win_contrato')){
        $('#win_contrato').empty();
        $('#win_contrato').remove();
    }

    $('body').append(html);

    let proceso_detallado = doc_proceso+' - '+proceso;

    $('#txt_cliente_cnt').textbox({
        width       : '28%',
        height 		: 20,
        labelWidth  : 70,
        labelAlign  : 'left',
        label 		: 'Cliente',
        required    : true,
        disabled    : true,
        prompt 		: 'Cliente',
        value       : cliente ?? ''
    });

    /* $('#txt_proceso_cnt').textbox({
        width       : '25%',
        height 		: 20,
        labelWidth  : 70,
        labelAlign  : 'left',
        label 		: 'Proceso',
        required    : true,
        disabled    : true,
        prompt 		: 'Proceso',
        value       : proceso_detallado ?? ''
    }); */

    $('#txt_tipo_proceso_ctn').textbox({
        width       : '25%',
        height 		: 20,
        labelWidth  : 100,
        labelAlign  : 'left',
        label 		: 'Tipo Proceso',
        required    : true,
        disabled    : true,
        prompt 		: 'Tipo Proceso',
        value       : tipo_proceso ?? ''
    });

    $('#ck_cnt_onlyvigentes').switchbutton({
        width: 90,
        height: 20,
        labelWidth  : 50,
        labelAlign  : 'left',
        onText: 'VIGENTES',
        offText: 'TODOS',
        checked : false,
        onChange(newValue,oldValue){
            $('#dg_contrato').datagrid('load', {
                _token          : '<?= csrf_token() ?>'
                ,_acc			: 'listarContratos' 
                ,idprocesocab	: idprocesocab
                ,flg_aprobados : (newValue == true) ? 1 : ''
            });
        }
    });

    // Nueva funcionalidad para el botón "Ir a la derecha"
    $('#btn_ir_izquierda_cnt').linkbutton({
        'iconCls': 'icon-arrow-left',
        height: 20,
        onClick: function () {
            let panel = $('#dg_contrato').datagrid('getPanel');
            let body = panel.find('.datagrid-body');
            body.scrollLeft(0);
        }
    });

    $('#btn_ir_derecha_cnt').linkbutton({
        'iconCls': 'icon-arrow-right',
        height: 20,
        onClick: function () {
            let panel = $('#dg_contrato').datagrid('getPanel');
            let body = panel.find('.datagrid-body');
            let view2 = panel.find('.datagrid-view2 .datagrid-body');

            // Calcular el ancho total del contenido desplazable
            let totalWidth = view2[0].scrollWidth;

            // Desplazar el cuerpo principal hasta el ancho total del contenido
            body.scrollLeft(totalWidth);
        }
    });

    $('#dg_contrato').datagrid({
        url:url_consulta,
        fitColumns:false,
        singleSelect:true,
        rownumbers:true,
        pagination:true,
        pageSize:50,
        striped:true,
        fit:true,
        nowrap:false,
        border:false,
        loadMsg: 'Cargando por favor espere...',
        queryParams: {
            _token          : '<?= csrf_token() ?>'
            ,_acc			: 'listarContratos'
            ,idprocesocab   : idprocesocab
            ,idcontrato     : idcontrato
        },
        toolbar: '#tb_contrato',
        frozenColumns:[[
                {field:'T1',colspan:5,title:'Contrato',align:'center',halign:'center'},
            ],[      
            {field:'contrato_completo',title:'Contrato / Contrato-SAP',align:'center',halign:'center',width:'200px'},
            {field:'desc_tipo_cnt',title:'Tipo<br>Contrato',align:'center',halign:'center',width:'170px'},
            {field:'monto_contrato_moneda',title:'Monto',align:'center',halign:'center',width:'150px'},
            {field:'estado',title:'Estado',align:'center',halign:'center',width:190,formatter:function(val,row,index){
                return `<span style="border-radius:5px 5px 5px 5px;
                    background-color:${row.color_estado};
                    color:white;padding:3px;cursor:pointer;">
                ${row.desc_estado}</span>` 

            }},
            {field:'nivel_cumplimiento',title:'Nivel<br>Cumplimiento',align:'left',halign:'center',width:'225px',formatter:function(value,row,index){
                if(row.idprocesocab != 0){
                    let nivel_cumplimiento = (row.nivel_cumplimiento == null) ? 'ND' : row.nivel_cumplimiento;
                    let nivel_cumplimiento_ejecucion = (row.nivel_cumplimiento_ejecucion == null) ? 'ND' : row.nivel_cumplimiento_ejecucion;
                    var color_cumplimiento = '';
                    var color_cumplimiento_ejecucion = '';
                    if(nivel_cumplimiento >= 0 && nivel_cumplimiento < 75){
                        color_cumplimiento = "red";
                    }else if(nivel_cumplimiento >= 75 && nivel_cumplimiento < 100){
                        color_cumplimiento = "orange";
                    }else if(nivel_cumplimiento >= 100){
                        color_cumplimiento = "green";
                    }else if(nivel_cumplimiento == 'ND'){
                        color_cumplimiento = "gray";
                    }
                    if(nivel_cumplimiento_ejecucion >= 0 && nivel_cumplimiento_ejecucion < 75){
                        color_cumplimiento_ejecucion = "red";
                    }else if(nivel_cumplimiento_ejecucion >= 75 && nivel_cumplimiento_ejecucion < 100){
                        color_cumplimiento_ejecucion = "orange";
                    }else if(nivel_cumplimiento_ejecucion >= 100){
                        color_cumplimiento_ejecucion = "green";
                    }else if(nivel_cumplimiento_ejecucion == 'ND'){
                        color_cumplimiento_ejecucion = "gray";
                    }
                    
                    return `
                        <div class="progress-bar-container" title="Nivel de Cumplimiento" onclick="control_entregas_proceso(${row.idprocesocab},'${row.proceso}','${row.deno_proceso}','',2,'${row.operador_alias}','${row.idcontrato}')">
                            <div class="progress-bar" style="background-color: ${color_cumplimiento}; width: ${nivel_cumplimiento > 100 ? 100 : nivel_cumplimiento}%;"></div>
                            <div class="progress-bar-text">
                                <span style="font-size: 11px; color: black; flex-shrink: 0; font-weight: bold;">% Cumplimiento:</span>
                                <span style="text-align: right; flex-shrink: 0; font-weight: bold;">
                                    ${nivel_cumplimiento} %
                                </span>
                            </div>
                        </div>
                        <div class="progress-bar-container" title="Nivel de Cumplimiento" onclick="control_entregas_proceso(${row.idprocesocab},'${row.proceso}','${row.deno_proceso}','',2,'${row.operador_alias}','${row.idcontrato}')">
                            <div class="progress-bar" style="background-color: ${color_cumplimiento_ejecucion}; width: ${nivel_cumplimiento_ejecucion > 100 ? 100 : nivel_cumplimiento_ejecucion}%;"></div>
                            <div class="progress-bar-text">
                                <span style="font-size: 11px; color: black; flex-shrink: 0; font-weight: bold;">% Ejecución:</span>
                                <span style="text-align: right; flex-shrink: 0; font-weight: bold;">
                                    ${nivel_cumplimiento_ejecucion} %
                                </span>
                            </div>
                        </div>
                    `
                }else{
                    return ``
                }
            }},
        ]],
        columns:[[
                {field:'T1',colspan:6,title:'Datos del Contrato',align:'center',halign:'center'},
                {field:'T2',colspan:4,title:'Datos de la Carta Fianza',align:'center',halign:'center'},
                //{field:'T3',colspan:3,title:'Situaciones del Contrato',align:'center',halign:'center'},
                {field:'T4',colspan:1,title:'Opciones',align:'center',halign:'center'},
            ],[      
            // CONTRATO
            {field:'situacion_legal',title:'Situación<br>Legal',align:'left',halign:'center',width:'150px'},
            {field:'fecha_ini_contrato',title:'Fecha Sucripción<br>Contrato',align:'center',halign:'center',width:'150px',formatter:function(val,row,index){
                let fechaIniCNT = formatter_date_SAP(row.fecha_ini_contrato);
                return `
                    <div>${fechaIniCNT}</div>
                `
            }},
            {field:'fecha_fin_contrato',title:'Fecha Finalización<br>Contrato',align:'center',halign:'center',width:'150px',formatter:function(val,row,index){
                let fechaFinCNT = formatter_date_SAP(row.fecha_fin_contrato);
                return `
                    <div>${fechaFinCNT}</div>
                `
            }},
            {field:'fecha_recep_contrato',title:'Fecha Recepción<br>Contrato',align:'center',halign:'center',width:'150px',formatter:function(val,row,index){
                let fechaRecepCNT = formatter_date_SAP(row.fecha_recep_contrato);
                return `
                    <div>${fechaRecepCNT}</div>
                `
            }},
            {field:'vb',title:'Visto<br>Bueno',align:'center',halign:'center',width:80,formatter:function(value,row,index){
                if(row.flg_estado_aprobacion == 1){ // Pendiente de Recepción del Contrato
                    return `<img src="${ambiente}img/icons/accept.png" 
                        onclick="if (checkRolesAndPermissions('visto_bueno_cnt')){ ajaxCambioEstadoCNT(${row.idcontrato},${row.idestadocontrato},'poner en vigencia',1,${row.idprocesocab})}"
                        title="Aprobar" style="cursor:pointer;width:16px;height:16px">`
                }
                else{
                    return ``
                }
            }},
            {field:'dias_demora',title:'Días Para<br>Vencimiento',align:'center',halign:'center',width:'10%',formatter:function(val,row,index){
                if(row.dias_demora > 0){
                    return `<span style="border-radius:5px 5px 5px 5px;
                        background-color:red;
                        color:white;padding:3px;">
                    ${row.dias_demora} días vencido
                    </span>`;
                } else {
                    return `<span style="border-radius:5px 5px 5px 5px;
                        background-color:green;
                        color:white;padding:3px;">
                    ${Math.abs(row.dias_demora)} días
                    </span>`;
                }
            }},
            /* {field:'ver',title:'Ver<br>Contrato',align:'center',halign:'center',width:70,formatter:function(value,row,index){
                if(row.idadjuntocontrato > 0){
                    return `<img src="${ambiente}img/icons/application_view_gallery.png"  onclick="ver_pdf('${row.contrato}','${row.ruta_cnt}','${row.adjunto_cnt}',6)" title="Ver Contrato" style="cursor:pointer;width:16px;height:16px">`              
                }else{
                    return ``
                }
                
            }}, */
            // CTF
            {field:'fianzas',title:'N° Carta<br>Fianza',align:'left',halign:'center',width:250,formatter:function(value,row,index){
                if (row.fianzas != '') {
                    let idFianzasArray = row.ids_cartafianzafinal.split('|');
                    let fianzaArray = row.fianzas.split('|');
                    let html_fianza = fianzaArray.map((fianza, index) => {
                        return `<div style="font-size:11px;text-decoration: underline;cursor: pointer;" onclick="carta_fianza_final(${idprocesocab},'${doc_proceso}','${proceso}','${tipo_proceso}','${cliente}','${solicitante}','${idFianzasArray[index]}')" title="Ir a la Carta Fianza">Fianza: <b>${fianza}</b></div>`;
                    }).join('');
                    return html_fianza;
                } else {
                    return ``
                }         
            }},
            {field:'fianzas_fi',title:'Fecha Emisión<br>Carta Fianza',align:'left',halign:'center',width:200,formatter:function(value,row,index){
                if (row.fianzas_fi != '') {
                    let fianzaArray = row.fianzas_fi.split('|');
                    return fianzaArray.map(fianza_fi => `<div style="font-size:11px;">Fecha Emisión: <b>${fianza_fi}</b></div>`).join('');
                } else {
                    return ``
                }         
            }},
            {field:'fianzas_ff',title:'Fecha Vcto.<br>Carta Fianza',align:'left',halign:'center',width:200,formatter:function(value,row,index){
                if (row.fianzas_ff != '') {
                    let fianzaArray = row.fianzas_ff.split('|');
                    return fianzaArray.map(fianza_ff => `<div style="font-size:11px;">Fecha Vcto.: <b>${fianza_ff}</b></div>`).join('');
                } else {
                    return ``
                }         
            }},
            {field:'fianzas_imp',title:'Importe',align:'left',halign:'center',width:200,formatter:function(value,row,index){
                if (row.fianzas_imp != '') {
                    let fianzaArray = row.fianzas_imp.split('|');
                    return fianzaArray.map(fianza_imp => `<div style="font-size:11px;">Importe: <b>${fianza_imp}</b></div>`).join('');
                } else {
                    return ``
                }         
            }},
            // SITUACIONES
            /* {field:'situaciones_vsi',title:'Venta Institucional',align:'left',halign:'center',width:'250px'},
            {field:'situaciones_cyc',title:'Créditos y Cobranza',align:'left',halign:'center',width:'250px'},
            {field:'situaciones_legal',title:'Legal',align:'left',halign:'center',width:'250px'}, */
            // OPCIONES
            /* {field:'recepcionar',title:'Recepcionar<br>Contrato',align:'center',halign:'center',width:80,formatter:function(value,row,index){
                if(row.idestadocontrato == 1){ // Registro
                    return `<img src="${ambiente}img/icons/drive_disk.png"  onclick="ajaxCambioEstadoCNT(${row.idcontrato},${row.idestadocontrato},'recepcionar',1,${row.idprocesocab})" title="Recepcionar" style="cursor:pointer;width:16px;height:16px">`
                }
                else{
                    return ``
                }
            }}, 
            {field:'historial',title:'Historial',align:'center',halign:'center',width:80,formatter:function(value,row,index){
                return `<img src="${ambiente}img/icons/book_next.png"  onclick="ver_historial_cnt(${row.idcontrato},'${row.contrato}')" title="Historial" style="cursor:pointer;width:16px;height:16px">`
            }}, */
            {field:'menu',title:'Menú<br>Opciones',align:'center',halign:'center',width:'130px',formatter:function(val,row,index){
                return '<a class="act-btn" rowindex='+index+' href="javascript:void(0);">Menú</a>';
            }},
        ]],
        onRowContextMenu: function(e, index, row) {
            e.preventDefault();
            $(this).datagrid('selectRow', index);
            let rowsgrilla = $(this).datagrid('getRows');
            if(row != null /*&& index != rowsgrilla.length - 1*/){
                abrirAtck_cnt(e,index,row);
            }
        },
        onLoadSuccess: function(data){
            $(this).datagrid('getPanel').find('.act-btn').menubutton({
                iconCls: 'icon-editarctf',
                menu: '#mm_cnt',
                showEvent: 'click',
                onClick: function(){
                    var index = $(this).attr('rowindex');
                    $('#dg_contrato').datagrid('selectRow', index);
                }
            });

        },
        onClickRow: function (index, row) {
            $(this).datagrid('uncheckRow', index);
        },
        onLoadError: function(XMLHttpRequest, textStatus, errorThrown){
            $.messager.alert('Error','Error al mostrar los datos, vuelva a intentar','error');
        },
    }).datagrid('getPager').pagination({
        beforePageText: 'Pag. ',
        afterPageText: 'de {pages}',
        displayMsg: 'Del {from} al {to}, de {total} items.'
    });

    colorear_dg_contratos('#dg_contrato');
    $('#dg_contrato').datagrid('enableFilter');
    evaluarMenuCNT(idprocesocab);

    $('#btn_actualizar_cnt').linkbutton({
        'iconCls':'icon-refrescar',
        height:20,
        onClick:function(){
            $('#dg_contrato').datagrid('load', {
                _token          : '<?= csrf_token() ?>'
                ,_acc			: 'listarContratos' 
                ,idprocesocab	: idprocesocab
            });
        }
    });

    $('#btn_nueva_cnt').linkbutton({
        'iconCls':'icon-add',
        height:20,
        onClick:function(){
            generar_contrato_wizard(idprocesocab,proceso_detallado,solicitante,doc_proceso);
        }
    });

    $('#win_contrato').window({
        modal:true, collapsible:false, closable:true, minimizable:false, maximizable:true, closed:false, center:true, resizable:false
    });
}

function abrirAtck_cnt(e,index,row,doc_proceso,proceso,tipo_proceso,cliente,solicitante){
    let disabled_1 = '';
    let disabled_2 = '';
    let disabled_3 = '';
    let disabled_4 = '';

    if (row && row.idestadocontrato == 4) {
        disabled_1 = ' class="menu-disabled"';
    }
    if (row && row.idestadocontrato == 4) {
        disabled_2 = ' class="menu-disabled"';
    }
    if (row && row.idadjuntocontrato == 0) {
        disabled_3 = ' class="menu-disabled"';
    }
    if (row && row.idestadocontrato == 4) {
        disabled_4 = ' class="menu-disabled"';
    }

    let html = `
        <div id="mm_contrato_atck" style="width:170px;">
            <!--<div ${disabled_1} onclick="atck_entregas_cnt()" data-options="iconCls:'icon-cargo_truck'">Entregas</div>-->
            <div ${disabled_2} onclick="atck_editar_cnt()" data-options="iconCls:'icon-editarctf'">Editar</div>
            <div ${disabled_4} onclick="atck_situaciones_cnt()" data-options="iconCls:'icon-user'">Situaciones</div>
            <div onclick="atck_historial_cnt()" data-options="iconCls:'icon-book-next'">Historial</div>
            <div onclick="atck_adjuntos_cnt()" data-options="iconCls:'icon-drive'">Adjuntos</div>
            <div ${disabled_3} onclick="atck_ver_cnt()" data-options="iconCls:'icon-view_img'">Ver Contrato</div>
        </div>
    `;

    if ($('#mm_contrato_atck')){
        $('#mm_contrato_atck').empty();
        $('#mm_contrato_atck').remove();
    }

    $('body').append(html);

    $('#mm_contrato_atck').menu();
    $('#mm_contrato_atck').menu('show', {
        left: event.pageX,
        top: event.pageY
    });

}

// Funciones de las opciones del menú de contexto
function atck_historial_cnt() {
    var row = $('#dg_contrato').datagrid('getSelected');
    if (row) {
        ver_historial_cnt(row.idcontrato,row.contrato);
    }
}

function atck_entregas_cnt() {
    var row = $('#dg_contrato').datagrid('getSelected');
    if (row) {
        if(row.idestadocontrato  != 4){
            //entregas_x_contrato(row.idprocesocab,row.idcontrato,row.contrato,row.ids_procesodet,row.proceso,row.proceso_detallado,row.idestadocontrato,row.operador_alias);
            control_entregas_proceso(row.idprocesocab,row.proceso,row.deno_proceso,'',0,row.operador_alias,row.idcontrato);
        }else{
            $.messager.alert('Error','El contrato seleccionado ha sido anulado.','warning');
            return;    
        }
    }
}

function atck_editar_cnt() {
    var row = $('#dg_contrato').datagrid('getSelected');
    if (row) {
        if(row.idestadocontrato != 4){
            generar_contrato(row.idprocesocab,row.proceso_detallado,0,'',row.idcontrato);
        }else{
            $.messager.alert('Error','El contrato seleccionado ha sido anulado.','warning');
            return;    
        }
    }
}

function atck_situaciones_cnt() {
    var row = $('#dg_contrato').datagrid('getSelected');
    if (row) {
        if(row.idestadocontrato  != 4){
            asociarSITUACIONES(row.idprocesocab,row.idcontrato,row.contrato);
        }else{
            $.messager.alert('Error','El contrato seleccionado ha sido anulado.','warning');
            return;    
        }
    }
}

function atck_adjuntos_cnt(){
    var row = $('#dg_contrato').datagrid('getSelected');
    if (row) {
        adjuntosCNT(row.idcontrato);
    }
}

function atck_ver_cnt() {
    var row = $('#dg_contrato').datagrid('getSelected');
    if (row) {
        if(row.idadjuntocontrato > 0){
            ver_pdf(row.contrato,row.ruta_cnt,row.adjunto_cnt,6)
        }else{
            $.messager.alert('Error','El contrato seleccionado no cuenta con adjuntos.','warning');
            return;    
        }
    }
}

function evaluarMenuCNT(idprocesocab){
    // OPCION DE ASOCIAR CTF
    $('#mm_cnt div[data-options*="iconCls:\'icon-carta\'"]').click(function() {
        // VARIABLES
        var idcontrato = 0;
        var idestadocontrato = 0;
        var contrato = '';
        var index = $('#dg_contrato').datagrid('getRowIndex', $('#dg_contrato').datagrid('getSelected'));
        var rowData = $('#dg_contrato').datagrid('getData').rows[index];
        idcontrato = rowData.idcontrato;
        idestadocontrato = rowData.idestadocontrato;
        contrato = rowData.contrato;
        if(idcontrato != 0 && idestadocontrato != 0){
            if(idestadocontrato == 4){ // ANULADA
                $.messager.alert('Error','El contrato seleccionado se encuentra anulado, no es posible continuar.','warning');
                return;
            }else{
                asociarCTF(idprocesocab,idcontrato,contrato);
                return;
            }
        }else{
            $.messager.alert('Error','No se ha encontrado "id" de contrato y/o estado. Comunicarse con TI.','warning');
            return;
        }
    });
    /* // OPCION DE RESPONSABLE
    $('#mm_cnt div[data-options*="iconCls:\'icon-user\'"]').click(function() {
        // VARIABLES
        var idcontrato = 0;
        var idestadocontrato = 0;
        var contrato = '';
        var index = $('#dg_contrato').datagrid('getRowIndex', $('#dg_contrato').datagrid('getSelected'));
        var rowData = $('#dg_contrato').datagrid('getData').rows[index];
        idcontrato = rowData.idcontrato;
        idestadocontrato = rowData.idestadocontrato;
        contrato = rowData.contrato;
        if(idcontrato != 0 && idestadocontrato != 0){
            if(idestadocontrato == 4){ // ANULADA
                $.messager.alert('Error','El contrato seleccionado se encuentra anulado, no es posible continuar.','warning');
                return;
            }else{
                asociarSITUACIONES(idprocesocab,idcontrato,contrato);
                return;
            }
        }else{
            $.messager.alert('Error','No se ha encontrado "id" de contrato y/o estado. Comunicarse con TI.','warning');
            return;
        }
    });
    // OPCION DE ADJUNTOS
    $('#mm_cnt div[data-options*="iconCls:\'icon-drive\'"]').click(function() {
        // VARIABLES
        var idcontrato = 0;
        var idestadocontrato = 0;
        var contrato = '';
        var index = $('#dg_contrato').datagrid('getRowIndex', $('#dg_contrato').datagrid('getSelected'));
        var rowData = $('#dg_contrato').datagrid('getData').rows[index];
        idcontrato = rowData.idcontrato;
        idestadocontrato = rowData.idestadocontrato;
        contrato = rowData.contrato;
        if(idcontrato != 0 && idestadocontrato != 0){
            adjuntosCNT(idcontrato);
        }else{
            $.messager.alert('Error','No se ha encontrado "id" de contrato y/o estado. Comunicarse con TI.','warning');
            return;
        }
    }); */
    // OPCION DE ANULAR
    $('#mm_cnt div[data-options*="iconCls:\'icon-cancel\'"]').click(function() {
        // VARIABLES
        var idcontrato = 0;
        var idestadocontrato = 0;
        var index = $('#dg_contrato').datagrid('getRowIndex', $('#dg_contrato').datagrid('getSelected'));
        var rowData = $('#dg_contrato').datagrid('getData').rows[index];
        idcontrato = rowData.idcontrato;
        idestadocontrato = rowData.idestadocontrato;
        if(idcontrato != 0 && idestadocontrato != 0){
            if(idestadocontrato == 4){ // ANULADO
                $.messager.alert('Error','El contrato seleccionado ya se encuentra <b style="color:red;">ANULADO</b>.','warning');
                return;
            }else{
                ajaxCambioEstadoCNT(idcontrato,idestadocontrato,'anular',3,idprocesocab);
                return;
            }
        }else{
            $.messager.alert('Error','No se ha encontrado "id" de contrato y/o estado. Comunicarse con TI.','warning');
            return;
        }
    });
}

function ajaxCambioEstadoCNT(idcontrato,idestadocontrato,tipo,accion,idprocesocab){
    var dialogContent = '';
    if(idestadocontrato == 1){ // Estado de Registro
        tipo = 'recepcionar';
        dialogContent = `
        <div style="margin:20px 0;"></div>
            <div>
                <input id="dt_fecha_recepcion_ajax" class="easyui-datebox" editable="false" style="width:100%">
            </div>
        `;
        // Inicializar el datebox después de que el diálogo esté en el DOM
        setTimeout(function() {
            $('#dt_fecha_recepcion_ajax').datebox({
                parser: new_parser_date,
                formatter: new_formatter_date,
                value: hoy
            });
        }, 100);  // Ajustar el retardo según sea necesario
        $.messager.alert({
            title: 'Seleccionar Fecha de Recepción',
            msg: dialogContent,
            width: 300,
            onClose: function() {
                var fecha = $('#dt_fecha_recepcion_ajax').datebox('getValue');
                // Confirmar la acción una vez que la fecha ha sido ingresada
                $.messager.confirm('Alerta', 'Se procederá a <b>' + tipo + '</b> el contrato, ¿Desea continuar?', function(r) {
                    if (r) {
                        $.ajax(url_mantenimiento, {
                            type: 'post',
                            beforeSend: function() {
                                $.messager.progress({ text: 'Procesando...' });
                            },
                            complete: function() {
                                $.messager.progress('close');
                            },
                            success: function(datos) {
                                if (datos.o_nres == 1) {
                                    let act_mae_cnt = actualizar_maestro_cnt();
                                    $.messager.alert('Info', datos.o_msj, 'info');
                                    if(act_mae_cnt == false){          
                                        $('#dg_contrato').datagrid('load', {
                                            _token: '<?= csrf_token() ?>',
                                            _acc: 'listarContratos',
                                            idprocesocab: idprocesocab
                                        });
                                    }
                                } else {
                                    $.messager.alert('Error', datos.o_msj, 'error');
                                }
                            },
                            error: function(x, e) {
                                $.messager.alert('Error ' + x.status, 'Ocurrió un error en el servidor.', 'error');
                            },
                            data: {
                                _token: '<?= csrf_token() ?>'
                                ,_acc: 'cambiarEstado_CNT'
                                ,idcontrato: idcontrato
                                ,idestadocontrato: idestadocontrato
                                ,accion: accion
                                ,fecha: fecha
                            },
                            async: true,
                            dataType: "json"
                        });
                    }
                });
            }
        });
    }else{
        $.messager.confirm('Alerta', 'Se procederá a <b>' + tipo + '</b> el contrato, ¿Desea continuar?', function(r) {
            if (r) {
                $.ajax(url_mantenimiento, {
                    type: 'post',
                    beforeSend: function() {
                        $.messager.progress({ text: 'Procesando...' });
                    },
                    complete: function() {
                        $.messager.progress('close');
                    },
                    success: function(datos) {
                        if (datos.o_nres == 1) {
                            $.messager.alert('Info', datos.o_msj, 'info');
                            $('#dg_contrato').datagrid('load', {
                                _token: '<?= csrf_token() ?>',
                                _acc: 'listarContratos',
                                idprocesocab: idprocesocab
                            });
                        } else {
                            $.messager.alert('Error', datos.o_msj, 'error');
                        }
                    },
                    error: function(x, e) {
                        $.messager.alert('Error ' + x.status, 'Ocurrió un error en el servidor.', 'error');
                    },
                    data: {
                        _token: '<?= csrf_token() ?>'
                        ,_acc: 'cambiarEstado_CNT'
                        ,idcontrato: idcontrato
                        ,idestadocontrato: idestadocontrato
                        ,accion: accion
                    },
                    async: true,
                    dataType: "json"
                });
            }
        });
    }
}

function ver_historial_cnt(idcontrato,contrato=''){
    let html = `
        <div id="win_ver_historial_contrato" title="Historial de Estados Contrato: ${contrato}" class="easyui-layout" style="width:1350px;height:620px;">
            <table id="tb_historial_contrato">
            </table>

            <table id="dg_historial_contrato"></table>
        </div>
    `;

    if ($('#win_ver_historial_contrato')){
        $('#win_ver_historial_contrato').empty();
        $('#win_ver_historial_contrato').remove();
    }

    $('body').append(html);

    $('#dg_historial_contrato').datagrid({
        url:url_consulta,
        fitColumns:false,
        singleSelect:true,
        rownumbers:true,
        pagination:true,
        pageSize:50,
        striped:true,
        fit:true,
        nowrap:false,
        border:false,
        loadMsg: 'Cargando por favor espere...',
        queryParams: {
            _token          : '<?= csrf_token() ?>'
            ,_acc			: 'listarHistorialCNT'
            ,idcontrato 	: idcontrato
        },
        toolbar: '#tb_historial_contrato',
        columns:[[
            {field:'usuario',title:'Usuario',align:'left',halign:'center',width:'350px'},
            {field:'estado',title:'Estado',align:'left',halign:'center',width:'600px',formatter:function(val,row,index){
                if(row.estado_anterior == ''){
                    return `<span style="border-radius:5px 5px 5px 5px;
                        background-color:${row.color_estado_actual};
                        color:white;padding:3px;cursor:pointer;"
                    >
                    ${row.estado_actual}</span>`	
                }else{
                    return `<span style="border-radius:5px 5px 5px 5px;
                        background-color:${row.color_estado_anterior};
                        color:white;padding:3px;cursor:pointer;"
                    >
                    ${row.estado_anterior}</span>     a     <span style="border-radius:5px 5px 5px 5px;
                        background-color:${row.color_estado_actual};
                        color:white;padding:3px;cursor:pointer;"
                    >
                    ${row.estado_actual}</span>`	
                } 
            }},
            //{field:'texto_historial',title:'Texto<br>Historial',align:'left',halign:'center',width:'350px'},
            {field:'indicador_estado',title:'Actual',align:'center',halign:'center',width:'80px',formatter:function(val,row,index){
                if(row.indicador_estado == 1){
                    return ` SI `
                }else{
                    return ` NO `
                } 
                
            }}, 
            {field:'avanza_retorna',title:'Movimiento',align:'center',halign:'center',width:'80px',formatter:function(val,row,index){
                if(row.codigo_estado_actual != 'X'){
                    if(row.orden_anterior > row.orden_actual){
                        return `<img src="${ambiente}img/icons/arrow_left.png"  onclick="#" title="Retornó" style="cursor:pointer;width:16px;height:16px">`
                    }else if(row.orden_anterior == row.orden_actual){
                        return `<img src="${ambiente}img/icons/arrow_right.png"  onclick="#" title="Avanzo" style="cursor:pointer;width:16px;height:16px">`
                    }
                    else{ 
                        return `<img src="${ambiente}img/icons/arrow_right.png"  onclick="#" title="Avanzo" style="cursor:pointer;width:16px;height:16px">`
                    }
                }else{
                    return `<img src="${ambiente}img/icons/arrow_right.png"  onclick="#" title="Avanzo" style="cursor:pointer;width:16px;height:16px">`
                }
            }}, 
            {field:'auditoria_reg',title:'Fecha Registro',align:'center',halign:'center',width:'150px',formatter:function(val,row,index){
                let fechaProceso = formatter_date_SAP(row.fechaproceso_vista);
                return `
                    <div>${fechaProceso} ${row.horaproceso}</div>
                `
            }},
        ]],
        onLoadError: function(XMLHttpRequest, textStatus, errorThrown){
            $.messager.alert('Error','Error al mostrar los datos, vuelva a intentar','error');
        },
    }).datagrid('getPager').pagination({
        beforePageText: 'Pag. ',
        afterPageText: 'de {pages}',
        displayMsg: 'Del {from} al {to}, de {total} items.'
    });

    $('#win_ver_historial_contrato').window({
        modal:true, collapsible:false, closable:true, minimizable:false, maximizable:true, closed:false, center:true, resizable:false
    });

}

function entregas_x_contrato(idprocesocab,idcontrato,contrato,ids_procesodet,doc_proceso,proceso_detallado,idestadocontrato,operador_alias){    
    let html = `
        <div id="win_entregas_x_contrato" title="Seguimiento de Entregas del Contrato: ${contrato}" class="easyui-layout" style="width:99%;height:95%;">
            <table id="tb_entrega_cnt" style="width:100%">
                <!--<tr>
                    <td style="width:100%">
                        <div style="width:100%;padding:3px;text-align:right;">
                            <a id="btn_asociar_entregas_cnt">Añadir</a>
                            <a id="btn_quitar_entregas_cnt">Quitar</a>
                        </div>
                    </td>
                </tr>-->
            </table>

            <table id="dg_entrega_cnt"></table>
        </div>
    `;

    if ($('#win_entregas_x_contrato')){
        $('#win_entregas_x_contrato').empty();
        $('#win_entregas_x_contrato').remove();
    }

    $('body').append(html);

    $('#dg_entrega_cnt').datagrid({
        url:url_consulta,
        fitColumns:false,
        singleSelect:false,
        //rownumbers:true,
        pagination:true,
        pageSize:50,
        striped:true,
        fit:true,
        nowrap:false,
        border:false,
        view: detailview, // Carga detalle bases
        loadMsg: 'Cargando por favor espere...',
        queryParams: {
            _token          : '<?= csrf_token() ?>'
            ,_acc			: 'listarPrincipalEntregaxContrato' 
            ,idcontrato 	: idcontrato
            ,ids_procesodet : ids_procesodet
            ,doc_proceso    : doc_proceso
        },
        toolbar: '#tb_entrega_cnt',
        columns:[[
            {field:'T1',colspan:12,title:'Datos de la Entrega',align:'center',halign:'center'},
            {field:'T2',colspan:3,title:'Datos del Contrato',align:'center',halign:'center'},
            //{field:'T3',colspan:3,title:'Situaciones del Contrato',align:'center',halign:'center'},
        ],[       
            {field:'documento',title:'Documento',align:'center',halign:'center',width:'100px'},
            {field:'nro_entrega',title:'Nro. Entrega',align:'center',halign:'center',width:'80px'},
            {field:'fecha_ent',title:'Fecha Entrega',align:'left',halign:'center',width:'200px',formatter:function(val,row,index){
                let fechaIniEnt = formatter_date_SAP(row.fecha_ini_oc);
                let fechaFinEnt = formatter_date_SAP(row.fecha_fin_oc);
                return `
                    <div><span style="font-size: 11px; color: blue;">Inicio Entrega: </span>${fechaIniEnt}
                    <div><span style="font-size: 11px; color: blue;">Fin Entrega: </span>${fechaFinEnt}
                `
            }},
            {field:'anio_mes_ent',title:'Año/Mes Entrega',align:'center',halign:'center',width:'150px',formatter:function(val,row,index){
                return `
                    <div>${row.desc_mes_entrega}-${row.anio_entrega}</div>
                `
            }},
            /* CANTIDADES */
            {field:'producto',title:'Producto',align:'left',halign:'center',width:'350px'},
            {field:'molecula',title:'Molécula',align:'center',halign:'center',width:'200px'},
            {field:'cantidad_programada_um',title:'Cantidad<br>Programada',align:'center',halign:'center',width:'150px'},
            {field:'cantidad_atendida_cf',title:'Cantidad<br>Atendida C/F',align:'center',halign:'center',width:'150px'}, // con factura        
            {field:'cantidad_x_atender',title:'Cantidad por<br>Atender',align:'center',halign:'center',width:'150px',formatter:function(val,row,index){
                if(row.cantidad_x_atender <= 0){
                    return `<span style="border-radius:5px 5px 5px 5px;
                        background-color:green;
                        color:white;padding:3px;"
                    >
                    ${row.cantidad_x_atender_um}
                    </span>`
                }else if(row.cantidad_x_atender > 0 && row.cantidad_x_atender <= row.cantidad_prevista*0.5){
                    return `<span style="border-radius:5px 5px 5px 5px;
                        background-color:orange;
                        color:white;padding:3px;"
                    >
                    ${row.cantidad_x_atender_um}
                    </span>`
                }else{
                    return `<span style="border-radius:5px 5px 5px 5px;
                        background-color:red;
                        color:white;padding:3px;"
                    >
                    ${row.cantidad_x_atender_um}
                    </span>`
                }                            
            }},
            {field:'cantidad_pedida_sf',title:'Cantidad Ped.<br>Cargado S/F',align:'center',halign:'center',width:'150px'},
            {field:'importe_neto_moneda',title:'Importe Neto<br>Entrega',align:'center',halign:'center',width:'150px'},
            /* ---------------------------- */   
            {field:'cliente_dm',title:'Destinatario<br>Mercancía',align:'left',halign:'center',width:'350px'},
            //{field:'pesquisa',title:'Pesquisa',align:'center',halign:'center',width:'150px'},
            /* {field:'rechazo',title:'Rechazo',align:'center',halign:'center',width:'80px',formatter:function(val,row,index){
                if(row.motivo_rechazo == '-'){
                    return `<span style="border-radius:5px 5px 5px 5px;
                        background-color:green;
                        color:white;padding:3px;"
                    >
                    NO
                    </span>`
                }else{
                    return `<span style="border-radius:5px 5px 5px 5px;
                        background-color:red;
                        color:white;padding:3px;"
                    >
                    SI
                    </span>`
                }                            
            }},
            {field:'motivo_rechazo',title:'Motivo<br>Rechazo',align:'center',halign:'center',width:'200px'}, */
            // CONTRATO
            {field:'contrato',title:'Contrato',align:'center',halign:'center',width:'150px'},
            {field:'fecha_ini_contrato',title:'Fecha Suscripción<br>Contrato',align:'center',halign:'center',width:'150px',formatter:function(val,row,index){
                let fechaIniCnt = formatter_date_SAP(row.fecha_ini_contrato);
                return `
                    <div>${fechaIniCnt}</div>
                `
            }},
            {field:'fecha_fin_contrato',title:'Fecha Finalización<br>Contrato',align:'center',halign:'center',width:'150px',formatter:function(val,row,index){
                let fechaFinCnt = formatter_date_SAP(row.fecha_fin_contrato);
                return `
                    <div>${fechaFinCnt}</div>
                `
            }},
            // SITUACIONES
            /* {field:'situaciones_vsi',title:'Venta Institucional',align:'left',halign:'center',width:'250px'},
            {field:'situaciones_cyc',title:'Créditos y Cobranza',align:'left',halign:'center',width:'250px'},
            {field:'situaciones_legal',title:'Legal',align:'left',halign:'center',width:'250px'}, */
        ]],
        onClickRow: function (index, row) {
            // Desmarcar la fila si se hace clic en cualquier parte excepto el checkbox
            var checkbox = $(this).datagrid('getPanel').find('.datagrid-cell-check input[type="checkbox"]')[index];
            if (checkbox !== event.target) {
                $(this).datagrid('uncheckRow', index);
            }
        },
        detailFormatter : function(index, row) {
            return '<div style="padding:2px"><table id="grid_pedido_det_cnt_' + index + '"></table></div>';
        },
        onExpandRow : function(index, row) { // PEDIDO DET
            var dg_entrega_cnt = $("#dg_entrega_cnt");
            $('#dg_entrega_cnt').datagrid('selectRow',index);
            var ddv_pedido_det_cnt = $('#grid_pedido_det_cnt_' + index);
            var indexpadre = index;
            ddv_pedido_det_cnt.datagrid({
                url:url_consulta,
                fitColumns:true,
                singleSelect:true,
                width: 'auto',
                height : 'auto',
                loadMsg: 'Cargando por favor espere...',
                queryParams: {
                    _token          : '<?= csrf_token() ?>'
                    ,_acc			: 'listarPrincipalPedidoNivelCumplimiento'
                    ,identregadet   : row.identregadet 
                    ,operador_alias : operador_alias
                },
                columns:[[
                    {field:'documento',title:'Documento',align:'center',halign:'center',width:'100px'},
                    {field:'cliente_oc',title:'Cliente<br>O/C',align:'left',halign:'center',width:'300px'},
                    {field:'fecha_recepcion_oc',title:'Fecha<br>Recepción ACF O/C',align:'center',halign:'center',width:'150px',formatter:function(val,row,index){
                        let fechaRecep = formatter_date_SAP(row.fecha_recepcion_oc);
                        return `
                            <div>${fechaRecep}</div>
                        `
                    }},      
                    {field:'fecha_inicio_oc',title:'Fecha Inicio<br>Entrega O/C',align:'center',halign:'center',width:'150px',formatter:function(val,row,index){
                        let fechaEntrega = formatter_date_SAP(row.fecha_inicio_entrega_oc);
                        return `
                            <div>${fechaEntrega}</div>
                        `
                    }},      
                    {field:'fecha_vcto_oc',title:'Fecha<br>Vencimiento O/C (A)',align:'center',halign:'center',width:'150px',formatter:function(val,row,index){
                        let fechaVctoOc = formatter_date_SAP(row.fecha_fin_entrega_oc);
                        return `
                            <div>${fechaVctoOc}</div>
                        `
                    }},        
                    {field:'fecha_despacho',title:'Fecha Entrega<br>Transportista',align:'center',halign:'center',width:'150px',formatter:function(val,row,index){
                        let fechaEntregaTransportista = formatter_date_SAP(row.fecha_entrega_transportista);
                        return `
                            <div>${fechaEntregaTransportista}</div>
                        `
                    }},
                    {field:'fecha_recepcion_cliente',title:'Fecha<br>Recepción Cliente (B)',align:'center',halign:'center',width:'150px',formatter:function(val,row,index){
                        let fechaRecepcionCliente = formatter_date_SAP(row.fecha_recepcion_cliente);
                        return `
                            <div>${fechaRecepcionCliente}</div>
                        `
                    }},
                    {field:'dias_demora',title:'Días<br>Demora (B-A)',align:'center',halign:'center',width:'170px',formatter:function(val,row,index){
                        if(row.dias_demora > 0){
                            return `<span style="border-radius:5px 5px 5px 5px;
                                background-color:red;
                                color:white;padding:3px;"
                            >
                            ${row.dias_demora} días vencido
                            </span>`
                        }else{
                            return `<span style="border-radius:5px 5px 5px 5px;
                                background-color:green;
                                color:white;padding:3px;"
                            >
                            Dentro del Plazo
                            </span>`
                        }
                    }},
                    {field:'cantidad_um_venta',title:'Cantidad<br>Venta',align:'center',halign:'center',width:'150px'},
                    //{field:'importe_neto_moneda',title:'Importe<br>Neto Venta',align:'center',halign:'center',width:'170px'},
                    {field:'importe_venta',title:'Importe<br>Neto Venta',align:'center',halign:'center',width:'170px',formatter:function(val,row,index){
                        if(operador_alias == 'SGT' || operador_alias == 'CON' || operador_alias == 'DIM'){
                            return `
                                <div>${row.importe_neto_moneda_cf}</div>
                            `
                        }else{
                            return `
                                <div>${row.importe_neto_moneda}</div>
                            `
                        }
                        
                    }},
                    /* CLIENTE FINAL */
                    {field:'cantidad_alm_cf',title:'[CF] Cantidad<br>Entrega Alm.',align:'center',halign:'center',width:'130px'},
                    {field:'almacen_lote_cf',title:'[CF] Almacen/Lote',align:'center',halign:'center',width:'150px'},
                    {field:'guia_remision_cf',title:'[CF] Guía<br>Remisión',align:'center',halign:'center',width:'150px'},
                    {field:'cliente_oc_cf',title:'[CF] Cliente<br>Factura',align:'left',halign:'center',width:'300px'},
                    {field:'factura_sunat_cf',title:'[CF] Factura SUNAT',align:'center',halign:'center',width:'150px'},
                    {field:'fecha_factura_cf',title:'[CF] Fecha<br>Factura',align:'center',halign:'center',width:'100px',formatter:function(val,row,index){
                        let fechaFacturaCF = formatter_date_SAP(row.fecha_factura_cf);
                        return `
                        <div>${fechaFacturaCF}</div>
                        `
                    }},  
                    {field:'importe_sigv_cf',title:'[CF] Importe s/IGV',align:'center',halign:'center',width:'150px'},
                    {field:'importe_cigv_cf',title:'[CF] Importe c/IGV',align:'center',halign:'center',width:'150px'},
                    /* AC FARMA */ 
                    {field:'doc_picking',title:'[ACF] Documento<br>Picking',align:'center',halign:'center',width:'100px'},
                    {field:'fecha_picking',title:'[ACF] Fecha<br>Picking',align:'center',halign:'center',width:'100px',formatter:function(val,row,index){
                        let fechaPck = formatter_date_SAP(row.fecha_picking);
                        return `
                            <div>${fechaPck}</div>
                        `
                    }},
                    {field:'cantidad_entrega_um_venta',title:'[ACF] Cantidad<br>Entrega Alm.',align:'center',halign:'center',width:'130px'},
                    {field:'centro_almacen_lote',title:'[ACF] Centro/Almacen/Lote',align:'center',halign:'center',width:'200px'},
                    {field:'guia_remision',title:'[ACF] Guía<br>Remisión',align:'center',halign:'center',width:'150px'},
                    {field:'cliente_factura',title:'[ACF] Cliente<br>Factura',align:'left',halign:'center',width:'300px'},
                    {field:'factura_sunat',title:'[ACF] Factura SUNAT',align:'center',halign:'center',width:'150px'},
                    {field:'fecha_factura',title:'[ACF] Fecha<br>Factura',align:'center',halign:'center',width:'100px',formatter:function(val,row,index){
                        let fechaFactura = formatter_date_SAP(row.fecha_factura);
                        return `
                            <div>${fechaFactura}</div>
                        `
                    }},   
                    {field:'importe_sigv',title:'[ACF] Importe s/IGV',align:'center',halign:'center',width:'150px'},
                    {field:'importe_cigv',title:'[ACF] Importe c/IGV',align:'center',halign:'center',width:'150px'},
                ]],
                onClickRow: function (index, row) {
                    $(this).datagrid('uncheckRow', index);
                },
                onResize : function() {
                    $('#dg_entrega_cnt').datagrid('fixDetailRowHeight',indexpadre);
                },
                onLoadSuccess : function() {
                    setTimeout( function() { $('#dg_entrega_cnt').datagrid('fixDetailRowHeight',indexpadre); }, 0);
                },
            });

            $('#dg_entrega_cnt').datagrid('fixDetailRowHeight',index);
            $(ddv_pedido_det_cnt).datagrid('enableFilter');

        },
        onClickRow: function (index, row) {
            $(this).datagrid('uncheckRow', index);
        },
        onLoadError: function(XMLHttpRequest, textStatus, errorThrown){
            $.messager.alert('Error','Error al mostrar los datos, vuelva a intentar','error');
        },
    }).datagrid('getPager').pagination({
        beforePageText: 'Pag. ',
        afterPageText: 'de {pages}',
        displayMsg: 'Del {from} al {to}, de {total} items.'
    });

    colorear_dg('#dg_entrega_cnt');
    $('#dg_entrega_cnt').datagrid('enableFilter');

    /* $('#btn_asociar_entregas_cnt').linkbutton({
        'iconCls':'icon-add',
        height:20,
        disabled: (idestadocontrato == 4) ? true : false,
        onClick:function(){
            let rows = $('#dg_entrega_cnt').datagrid('getChecked');
            if(rows && rows.length > 0){
                for (var i = 0; i < rows.length; i++) {
                    $('#dg_entrega_cnt').datagrid('uncheckRow', i);
                }
            }
            generar_contrato_wizard(idprocesocab,proceso_detallado,'',doc_proceso,idcontrato,contrato);
        }
    });

    $('#btn_quitar_entregas_cnt').linkbutton({
        'iconCls':'icon-remove',
        height:20,
        disabled: (idestadocontrato == 4) ? true : false,
        onClick:function(){
            let rows = $('#dg_entrega_cnt').datagrid('getChecked');
            var ids_entregas = [];
            if(rows && rows.length > 0){
                for (var i = 0; i < rows.length; i++) {
                    ids_entregas.push(rows[i].identregadet);
                }
                var ids_entregas_str = ids_entregas.join(',');
                f_quitar_entregas_cnt(idcontrato,contrato,ids_entregas_str,idprocesocab);             
            }else{
                $.messager.alert('Error','Debe seleccionar al menos una entrega.','warning');
                return;
            }
        }
    }); */

    $('#win_entregas_x_contrato').window({
        modal:true, collapsible:false, closable:true, minimizable:false, maximizable:true, closed:false, center:true, resizable:false
    });
}

function asociarCTF(idprocesocab,idcontrato,contrato){    
    let html = `
        <div id="win_contrato_ctf" title="Asociar Carta Fianza al Contrato: ${contrato}" class="easyui-layout" style="width:1400px;height:700px;">
            <table id="tb_contrato_ctf" style="width:100%">
                <tr>
                    <td style="width:100%">
                        <div style="width:100%;padding:3px;text-align:right;">
                            <a id="btn_asociar_cnt_ctf">Asociar</a>
                            <a id="btn_quitar_cnt_ctf">Quitar</a>
                            <a id="btn_salir_cnt_ctf">Salir</a>
                        </div>
                    </td>
                </tr>
            </table>

            <table id="dg_contrato_ctf"></table>
        </div>
    `;

    if ($('#win_contrato_ctf')){
        $('#win_contrato_ctf').empty();
        $('#win_contrato_ctf').remove();
    }

    $('body').append(html);

    $('#dg_contrato_ctf').datagrid({
        url:url_consulta,
        fitColumns:false,
        singleSelect:false,
        //rownumbers:true,
        pagination:true,
        pageSize:50,
        striped:true,
        fit:true,
        nowrap:false,
        border:false,
        loadMsg: 'Cargando por favor espere...',
        queryParams: {
            _token          : '<?= csrf_token() ?>'
            ,_acc			: 'listarPrincipalCartaFianza' 
            ,idprocesocab 	: idprocesocab
            ,flg_ctf_aprobadas : 1
        },
        toolbar: '#tb_contrato_ctf',
        columns:[[
            {field:'T1',colspan:12,title:'Datos de la Carta Fianza',align:'center',halign:'center'},
        ],[       
            {field:'chec',checkbox:true},
            {field:'items',title:'Items<br>Asociados',align:'center',halign:'center',width:80,formatter:function(value,row,index){
                return `<img src="${ambiente}img/icons/application_view_detail.png"  onclick="ver_items(${row.idcartafianza},'${row.proceso}','${row.codigo_cartafianza}',1)" title="Ver Items" style="cursor:pointer;width:16px;height:16px">`
            }},
            {field:'txt_respaldo',title:'Texto<br>Respaldo',align:'center',halign:'center',width:80,formatter:function(value,row,index){
                return `<img src="${ambiente}img/icons/blog.png"  onclick="ver_respaldo('${row.codigo_cartafianza}',${row.idprocesocab},${row.idcartafianza},1)" title="Ver Texto Respaldo" style="cursor:pointer;width:16px;height:16px">`
            }},
            {field:'nro_ctf_f',title:'N° Carta<br>Fianza',align:'center',halign:'center',width:'150px'},
            {field:'contrato',title:'Contrato',align:'center',halign:'center',width:'170px'},
            {field:'estado',title:'Estado',align:'center',halign:'center',width:190,formatter:function(val,row,index){
                return `<span style="border-radius:5px 5px 5px 5px;
                    background-color:${row.color_estado_f};
                    color:white;padding:3px;cursor:pointer;">
                ${row.desc_estado_f}</span>` 
            }},
            {field:'banco_corta_f',title:'Banco',align:'center',halign:'center',width:'100px'},
            {field:'fecha_emision',title:'Fecha<br>Emisión',align:'center',halign:'center',width:'100px',formatter:function(val,row,index){
                let fechaEmisionCTF = formatter_date_SAP(row.fecha_emision_f);
                if(row.idcartafianzafinal != 0){
                    return `
                        <div>${fechaEmisionCTF}</div>
                    `
                }else{
                    return ``
                }
            }},
            {field:'fecha_vcto_cf',title:'Fecha Vcto.<br>Carta Fianza',align:'center',halign:'center',width:'100px',formatter:function(val,row,index){
                let fechaVctoCTF = formatter_date_SAP(row.fecha_vencimiento_f);
                if(row.idcartafianzafinal != 0){
                    return `
                        <div>${fechaVctoCTF}</div>
                    `
                }else{
                    return ``
                }
            }},
            {field:'importe_banco_f_moneda',title:'Importe',align:'center',halign:'center',width:'150px'},
            {field:'garantizado',title:'Contratista',align:'center',halign:'center',width:'120px'},
            {field:'linea_eva',title:'Línea',align:'center',halign:'center',width:'120px'},
        ]],
        onClickRow: function (index, row) {
            // Desmarcar la fila si se hace clic en cualquier parte excepto el checkbox
            var checkbox = $(this).datagrid('getPanel').find('.datagrid-cell-check input[type="checkbox"]')[index];
            if (checkbox !== event.target) {
                $(this).datagrid('uncheckRow', index);
            }
        },
        rowStyler: function (index, row) {
            // FILAS BLOQUEADAS
            if (row.idcontrato > 0){
                if(idcontrato != row.idcontrato){
                    return 'background-color: #CD5C5C; cursor: not-allowed;';
                }else{
                    return 'background-color: #1BA000';
                }
            }
        },
        onBeforeCheck: function(index, row) {
            // Evitar que las filas se marque
            if (row.idcontrato > 0 && idcontrato != row.idcontrato) {
                return false;
            }
            //return row.idcontrato > 0;
        },
        onLoadError: function(XMLHttpRequest, textStatus, errorThrown){
            $.messager.alert('Error','Error al mostrar los datos, vuelva a intentar','error');
        },
    }).datagrid('getPager').pagination({
        beforePageText: 'Pag. ',
        afterPageText: 'de {pages}',
        displayMsg: 'Del {from} al {to}, de {total} items.'
    });

    colorear_dg('#dg_contrato_ctf');
    $('#dg_contrato_ctf').datagrid('enableFilter');

    $('#btn_asociar_cnt_ctf').linkbutton({
        'iconCls':'icon-add',
        onClick:function(){
            let rows = $('#dg_contrato_ctf').datagrid('getChecked');
            let ids_cartafianzafinal = [];
            if(rows && rows.length > 0){
                for (var i = 0; i < rows.length; i++) {
                    if(rows[i].idcontrato > 0){
                        if(rows[i].idcontrato == idcontrato){
                            $.messager.alert('Info','El contrato <b>'+contrato+'</b> ya tiene asociada la Carta Fianza seleccionada.','info');
                            $('#dg_contrato_ctf').datagrid('uncheckRow', i);
                            ids_cartafianzafinal = [];
                            return;
                        }else{
                            $.messager.alert('Error','La carta fianza <b>'+rows[i].nro_ctf_f+'</b> ya se encuentra asociado a otro contrato, no es posible continuar.','warning');
                            $('#dg_contrato_ctf').datagrid('uncheckRow', i);
                            ids_cartafianzafinal = [];
                            return;
                        }
                    }else{
                        ids_cartafianzafinal.push(rows[i].idcartafianzafinal);
                    }
                }  
                ajaxAsociar(idcontrato,ids_cartafianzafinal,1,contrato,idprocesocab);        
            }else{
                $.messager.alert('Error','Debe seleccionar al menos una carta fianza.','warning');
                return;
            }
        } 
    });

    $('#btn_quitar_cnt_ctf').linkbutton({
        'iconCls':'icon-remove',
        onClick:function(){
            let rows = $('#dg_contrato_ctf').datagrid('getChecked');
            let ids_cartafianzafinal = [];
            if(rows && rows.length > 0){
                for (var i = 0; i < rows.length; i++) {
                    if(rows[i].idcontrato == 0){
                            $.messager.alert('Error','La carta fianza <b>'+rows[i].nro_ctf_f+'</b> no se encuentra asociado a ningún contrato, no es posible continuar.','warning');
                            $('#dg_contrato_ctf').datagrid('uncheckRow', i);
                            ids_cartafianzafinal = [];
                            return;
                    }
                    else{
                        if(rows[i].idcontrato != idcontrato){
                            $.messager.alert('Error','La carta fianza <b>'+rows[i].nro_ctf_f+'</b> ya se encuentra asociado a otro contrato, no es posible continuar.','warning');
                            $('#dg_contrato_ctf').datagrid('uncheckRow', i);
                            ids_cartafianzafinal = [];
                            return;
                        }else{
                            ids_cartafianzafinal.push(rows[i].idcartafianzafinal);
                        }
                    }
                }  
                ajaxAsociar(idcontrato,ids_cartafianzafinal,0,contrato,idprocesocab);
            }else{
                $.messager.alert('Error','Debe seleccionar al menos una carta fianza.','warning');
                return;
            }
        }
    });

    $('#btn_salir_cnt_ctf').linkbutton({
        iconCls:'icon-cancel',
        onClick:function(){
            $('#win_contrato_ctf').window('close');
        }
    });

    $('#win_contrato_ctf').window({
        modal:true, collapsible:false, closable:true, minimizable:false, maximizable:true, closed:false, center:true, resizable:false
    });
}

function f_quitar_entregas_cnt(idcontrato,contrato,ids_entregas_str,idprocesocab){
    $.messager.confirm('Alerta', 'Se procederá a quitar las entregas seleccionadas del contrato <b>'+contrato+'</b>, ¿Desea continuar?',function(r){
        if (r){
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
                        $.messager.alert('Info',datos.o_msj,'info');
                        $('#dg_entrega_cnt').datagrid('load', {
                            _token          : '<?= csrf_token() ?>'
                            ,_acc			: 'listarEntregasDB' 
                            ,idcontrato     : idcontrato
                        });
                        $('#dg_contrato').datagrid('load', {
                            _token          : '<?= csrf_token() ?>'
                            ,_acc			: 'listarContratos' 
                            ,idprocesocab   : idprocesocab
                        });
                    }else {
                        $.messager.alert('Error',datos.o_msj,'error');
                    }
                },
                error:function(x,e){
                    $.messager.alert('Error '+x.status,'Ocurrió un error en el servidor.','error');
                },
                data:{
                    _token               :'<?= csrf_token() ?>'
                    ,_acc                : 'quitarENTxCNT'
                    ,idcontrato          : idcontrato
                    ,ids_entregas        : ids_entregas_str
                },
                async: true,
                dataType: "json"
            });
        }
    }); 
}

function ajaxAsociar(idcontrato,ids_cartafianzafinal,accion,contrato,idprocesocab){
    let titulo_aso = (accion == 0) ? 'desasociar' : 'asociar';
    $.messager.confirm('Alerta', 'Se procederá a <b>'+titulo_aso+'</b> la carta fianza seleccionada </b> al contrato <b>'+contrato+'</b>, ¿Desea continuar?',function(r){
        if (r){
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
                        let act_mae_cnt = actualizar_maestro_cnt();
                        $.messager.alert('Info',datos.o_msj,'info');
                        if(act_mae_cnt == false){    
                            $('#dg_contrato_ctf').datagrid('load', {
                                _token          : '<?= csrf_token() ?>'
                                ,_acc			: 'listarPrincipalCartaFianza' 
                                ,idprocesocab 	: idprocesocab
                                ,flg_ctf_aprobadas : 1
                            });
                            $('#dg_contrato').datagrid('load', {
                                _token          : '<?= csrf_token() ?>'
                                ,_acc			: 'listarContratos'
                                ,idprocesocab   : idprocesocab
                                ,idcontrato     : idcontrato
                            });
                        }
                    }else {
                        $.messager.alert('Error',datos.o_msj,'error');
                    } 
                },
                error:function(x,e){
                    $.messager.alert('Error '+x.status,'Ocurrió un error en el servidor.','error');
                },
                data:{
                    _token               :'<?= csrf_token() ?>'
                    ,_acc                : 'asociarCNTxCTF'
                    ,idcontrato          : idcontrato
                    ,ids_cartafianzafinal: ids_cartafianzafinal.join(',')
                    ,accion              : accion
                },
                async: true,
                dataType: "json"
            });
        }
    }); 
}

function generar_contrato_wizard(idprocesocab,proceso,solicitante,doc_proceso,idcontrato=0,contrato=''){    
    sessionStorage.removeItem('entregas_seleccionados_prev');
    sessionStorage.removeItem('entregas_seleccionados_cartafianza');
    let html = `
        <div id="win_entregas_cnt_wizard" title="Seleccione las Entregas del proceso: ${proceso}" class="easyui-layout" style="width:95%;height:850px;">
            <div id="tb_entregas_cnt">
                <div style="padding:3px; margin-top: 10px;<!--text-align: right;-->">
                    <input id="cmb_entrega_cnt">
                    <input id="cmb_nro_entrega_cnt">    
                    <input id="cmb_producto_cnt">
                    <input id="ck_libres_cnt">
                    <div style="padding:3px; margin-top: 5px;text-align: right;">
                        <a id="btn_consultar_entregas_cnt">Consultar</a>
                        <a id="btn_asociar_entregas_cnt_wizard">Guardar</a>
                        <a id="btn_next_entregas_cnt">Siguiente</a>
                        <a id="btn_salir_entregas_cnt">Salir</a>
                    </div>
                </div>
            </div>

            <table id="dg_entregas_cnt"></table>
        </div>
    `;

    if ($('#win_entregas_cnt_wizard')){
        $('#win_entregas_cnt_wizard').empty();
        $('#win_entregas_cnt_wizard').remove();
    }

    $('body').append(html);

    if(idcontrato != 0){
        $('#btn_asociar_entregas_cnt_wizard').show();
        $('#btn_consultar_entregas_cnt').show();
        $('#btn_next_entregas_cnt').hide();
        $('#btn_salir_entregas_cnt').show();
    }else{
        $('#btn_asociar_entregas_cnt_wizard').hide();
        $('#btn_consultar_entregas_cnt').show();
        $('#btn_next_entregas_cnt').show();
        $('#btn_salir_entregas_cnt').show();
    }

    $('#cmb_entrega_cnt').tagbox({
        icons   : [{
            iconCls:'icon-clear',
            handler:function(e){
                $(e.data.target).combobox('clear');
            }
        }],
        width       : '20%',
        height 		: 20,
        labelWidth  : 60,
        labelAlign  : 'left',
        label 		: 'Entrega',
        prompt 		: '[Seleccionar]',
        url 		: url_consulta,
        limitToList : true,
        hasDownArrow: true,
        multiple:true,
        queryParams : {
            _token : '<?= csrf_token() ?>',
            _acc : 'Combo',
            opcion : 'CMB_ENT_TOTAL_PRC',
            input2 : idprocesocab,
        },
        tagFormatter: function(tag, row) {
            return '<span class="tagbox-item" title="' + row.entrega + '">' + row.entrega + '</span>';
        },
        panelHeight : 'auto',
        panelMaxHeight:200,
        valueField 	: 'entrega',
        textField 	: 'entrega',
    });

    $('#cmb_nro_entrega_cnt').tagbox({
        icons   : [{
            iconCls:'icon-clear',
            handler:function(e){
                $(e.data.target).combobox('clear');
            }
        }],
        width       : '20%',
        height 		: 20,
        labelWidth  : 90,
        labelAlign  : 'left',
        label 		: 'Nro. Entrega',
        prompt 		: '[Seleccionar]',
        url 		: url_consulta,
        limitToList : true,
        hasDownArrow: true,
        multiple:true,
        queryParams : {
            _token : '<?= csrf_token() ?>',
            _acc : 'Combo',
            opcion : 'CMB_NRO_ENT_TOTAL_PRC',
            input2 : idprocesocab,
        },
        tagFormatter: function(tag, row) {
            return '<span class="tagbox-item" title="' + row.nro_entrega + '">' + row.nro_entrega + '</span>';
        },
        panelHeight : 'auto',
        panelMaxHeight:200,
        valueField 	: 'nro_entrega',
        textField 	: 'nro_entrega',
    });

    $('#cmb_producto_cnt').tagbox({
        icons   : [{
            iconCls:'icon-clear',
            handler:function(e){
                $(e.data.target).combobox('clear');
            }
        }],
        width       : '40%',
        height 		: 20,
        labelWidth  : 70,
        labelAlign  : 'left',
        label 		: 'Producto',
        prompt 		: '[Seleccionar]',
        url 		: url_consulta,
        limitToList : true,
        hasDownArrow: true,
        multiple:true,
        queryParams : {
            _token : '<?= csrf_token() ?>',
            _acc : 'Combo',
            opcion : 'CMB_PRC_X_PRODUCTO',
            input2 : idprocesocab,
        },
        tagFormatter: function(tag, row) {
            return '<span class="tagbox-item" title="' + row.producto + '">' + row.producto + '</span>';
        },
        panelHeight : 'auto',
        panelMaxHeight:200,
        valueField 	: 'codigo',
        textField 	: 'producto',
    });

    $('#ck_libres_cnt').switchbutton({
        width: 90,
        height: 20,
        labelWidth  : 80,
        labelAlign  : 'left',
        label 		: 'Solo Libres',
        onText: 'SI',
        offText: 'NO',
        checked : false,
    });

    $('#btn_next_entregas_cnt').linkbutton({
        'iconCls':'icon-arrow-right',
        onClick:function(){
            var valor_total = 0;
            let rows = $('#dg_entregas_cnt').datagrid('getChecked');
            var ultimoSel = {};
            var res2 = [];
            var entregas_seleccionada = JSON.parse(sessionStorage.getItem('entregas_seleccionados_prev')) || [];
            var data_entregas = JSON.parse(sessionStorage.getItem('entregas_seleccionados_cartafianza')) || [];
            // VALIDA QUE NO SE HAYA FORZADO A SELECCIONAR POSICIONES BLOQUEADAS
            for (var i = 0; i < rows.length; i++) {
                if (rows[i].flg_cnt == 1) {
                    $.messager.alert('Error','Ha seleccionado items que ya cuentan con una carta fianza registrada, vuelva a intentarlo.','warning');
                    return;
                }
            }
            if(rows && rows.length > 0){
                /* LOGICA PARA MAPEO DE LO SELECCIONADO */
                entregas_seleccionada.forEach(item => {
                    ultimoSel[item.identregadet] = item.cantidad_selec;
                });
                let res = Object.keys(ultimoSel).map(identregadet => ({
                    identregadet,
                    cantidad_selec: ultimoSel[identregadet]
                }));
                let newRows = rows.map(function(row){
                    return {
                        identregadet         : row.identregadet,
                        // PARA CALCULO DE VALIDACION
                        cantidad_total       : row.cantidad_prevista,
                        importe_total        : row.valor_neto,
                    };
                });
                res.forEach(resItem => {
                    let matchingNewRow = newRows.find(newRow => newRow.identregadet === resItem.identregadet);
                    if (matchingNewRow) {
                        res2.push({
                            identregadet: resItem.identregadet,
                            cantidad_selec: resItem.cantidad_selec,
                            // PARA CALCULO DE VALIDACION
                            cantidad_total: matchingNewRow.cantidad_total,
                            importe_total: matchingNewRow.importe_total,
                        });
                    }
                });
                res2.forEach((item,index) => {
                    var cantidad_selec = parseFloat(item.cantidad_selec);
                    var importe_total = parseFloat(item.importe_total);
                    var cantidad_total = parseFloat(item.cantidad_total);
                    data_entregas.push({ 
                        identregadet: item.identregadet ?? 0,
                        cantidad_seleccionada: item.cantidad_selec,
                    });
                    valor_total += (cantidad_selec*importe_total)/cantidad_total;
                });
                /* --------- */
                sessionStorage.setItem('entregas_seleccionados_cartafianza',JSON.stringify(data_entregas));
                $('#win_entregas_cnt_wizard').window('close');
                generar_contrato(idprocesocab,proceso,valor_total,doc_proceso);
                
            }else{
                $.messager.alert('Error','Debe seleccionar al menos una entrega.','warning');
                return;
            }

        }
    });

    $('#dg_entregas_cnt').datagrid({
        url:url_consulta,
        fitColumns:false,
        singleSelect:false,
        //rownumbers:true,
        pagination:true,
        pageSize:50,
        pageList: [10,50,100,200,500,1000],
        striped:true,
        fit:true,
        nowrap:false,
        border:false,
        loadMsg: 'Cargando por favor espere...',
        queryParams: {
            _token          : '<?= csrf_token() ?>'
            ,_acc			: 'listarPrincipalEntregaxContrato' 
            ,idprocesocab   : idprocesocab
            ,doc_proceso    : doc_proceso
            ,entrega                :  $('#cmb_entrega_cnt').combobox('getValue')
            ,nro_entrega            :  $('#cmb_nro_entrega_cnt').combobox('getValue')
            ,codigo_producto_ent    :  $('#cmb_producto_cnt').combobox('getValue')
            ,flg_libres             :  $('#ck_libres_cnt').switchbutton('options').checked ? 1 : ''
        },
        toolbar: '#tb_entregas_cnt',
        columns:[[
            {field:'chec',checkbox:true},
            {field:'documento',title:'Documento',align:'center',halign:'center',width:'100px'},
            {field:'producto',title:'Producto',align:'left',halign:'center',width:'550px'},
            {field:'molecula',title:'Molécula',align:'center',halign:'center',width:'200px'},
            {field:'nro_entrega',title:'Nro. Entrega',align:'center',halign:'center',width:'80px'},
            {field:'cliente_dm',title:'Destinatario<br>Mercancía',align:'left',halign:'center',width:'350px'},
            {field:'valor_neto_moneda',title:'Importe<br>Total',align:'center',halign:'center',width:'170px'},
            {field:'importe_moneda_cnt',title:'Importe<br>Pendiente',align:'center',halign:'center',width:'170px'},
            {field:'um_venta',title:'UM Venta',align:'center',halign:'center',width:'80px'},
            {field:'cantidad_prevista',title:'Cantidad<br>Total',align:'center',halign:'center',width:'150px'},
            {field:'cantidad_pendiente',title:'Cantidad<br>Pendiente',align:'center',halign:'center',width:'150px',formatter:function(value,row,index){
                return `
                    <div style="width:100%;height:100%;">
                        <input class="numberbox_cantidad_entrega" id="num_can_ent_${index}" data-identregadet="${row.identregadet}" data-cantidadtotal="${row.cantidad_prevista}" data-cantidadpendiente="${row.cantidad_pendiente_cnt}">
                    </div>
                `
            }},
        ]],
        onLoadSuccess: function (data) {
            sessionStorage.removeItem('entregas_seleccionados_prev');
            $('.numberbox_cantidad_entrega').each(function(i,element){    
                let cantidad_total = parseFloat($(element).data('cantidadtotal'));
                let identregadet = $(element).data('identregadet');
                let cantidadpendiente = parseFloat($(element).data('cantidadpendiente'));
                $(element).numberspinner({ //numberspinner
                    min:0,
                    max:cantidadpendiente,
                    precision:3,
                    width:'90%',
                    height:25,
                    value:cantidadpendiente,
                    disabled:  true,
                    //formatter: formatComas,
                    //parser:parserComas,
                    onChange:function(newValue,oldValue){
                        f_entregas_seleccionada(identregadet,newValue);
                    } 
                });
            });

        }, 
        onClickRow: function (index, row) {
            // Desmarcar la fila si se hace clic en cualquier parte excepto el checkbox
            var checkbox = $(this).datagrid('getPanel').find('.datagrid-cell-check input[type="checkbox"]')[index];
            if (checkbox !== event.target) {
                $(this).datagrid('uncheckRow', index);
            }
        },
        rowStyler: function (index, row) {
            if (row.flg_cnt == 1) {
                // Aplica un estilo CSS personalizado para filas con flg_semanal igual a 1 (rojo y deshabilitado)
                return 'background-color: #CD5C5C; cursor: not-allowed;';
            }
        },
        onBeforeCheck: function(index, row) {
            // Evitar que las filas con row.flg_semanal igual a 1 se marquen
            return row.flg_cnt != 1;
        },
        onLoadError: function(XMLHttpRequest, textStatus, errorThrown){
            $.messager.alert('Error','Error al mostrar los datos, vuelva a intentar','error');
        }
    }).datagrid('getPager').pagination({
        beforePageText: 'Pag. ',
        afterPageText: 'de {pages}',
        displayMsg: 'Del {from} al {to}, de {total} items.'
    });

    $('#btn_consultar_entregas_cnt').linkbutton({
        iconCls:'icon-search',
        onClick:function(){
            $('#dg_entregas_cnt').datagrid('load', {
                _token	: '<?= csrf_token() ?>'
                ,_acc	: 'listarEntregasDB'
                ,idprocesocab : idprocesocab
                ,entrega                :  $('#cmb_entrega_cnt').combobox('getValue')
                ,nro_entrega            :  $('#cmb_nro_entrega_cnt').combobox('getValue')
                ,codigo_producto_ent    :  $('#cmb_producto_cnt').combobox('getValue')
                ,flg_libres             :  $('#ck_libres_cnt').switchbutton('options').checked ? 1 : ''
            });
           }
    });

    $('#btn_asociar_entregas_cnt_wizard').linkbutton({
        iconCls:'icon-save',
        onClick:function(){
            var valor_total = 0;
            let rows = $('#dg_entregas_cnt').datagrid('getChecked');
            var ultimoSel = {};
            var res2 = [];
            var entregas_seleccionada = JSON.parse(sessionStorage.getItem('entregas_seleccionados_prev')) || [];
            var data_entregas = JSON.parse(sessionStorage.getItem('entregas_seleccionados_cartafianza')) || [];
            // VALIDA QUE NO SE HAYA FORZADO A SELECCIONAR POSICIONES BLOQUEADAS
            for (var i = 0; i < rows.length; i++) {
                if (rows[i].flg_cnt == 1) {
                    $.messager.alert('Error','Ha seleccionado items que ya cuentan con una carta fianza registrada, vuelva a intentarlo.','warning');
                    return;
                }
            }
            if(rows && rows.length > 0){
                /* LOGICA PARA MAPEO DE LO SELECCIONADO */
                entregas_seleccionada.forEach(item => {
                    ultimoSel[item.identregadet] = item.cantidad_selec;
                });
                let res = Object.keys(ultimoSel).map(identregadet => ({
                    identregadet,
                    cantidad_selec: ultimoSel[identregadet]
                }));
                let newRows = rows.map(function(row){
                    return {
                        identregadet         : row.identregadet,
                        // PARA CALCULO DE VALIDACION
                        cantidad_total       : row.cantidad_prevista,
                        importe_total        : row.valor_neto,
                    };
                });
                res.forEach(resItem => {
                    let matchingNewRow = newRows.find(newRow => newRow.identregadet === resItem.identregadet);
                    if (matchingNewRow) {
                        res2.push({
                            identregadet: resItem.identregadet,
                            cantidad_selec: resItem.cantidad_selec,
                            // PARA CALCULO DE VALIDACION
                            cantidad_total: matchingNewRow.cantidad_total,
                            importe_total: matchingNewRow.importe_total,
                        });
                    }
                });
                res2.forEach((item,index) => {
                    var cantidad_selec = parseFloat(item.cantidad_selec);
                    var importe_total = parseFloat(item.importe_total);
                    var cantidad_total = parseFloat(item.cantidad_total);
                    data_entregas.push({ 
                        identregadet: item.identregadet ?? 0,
                        cantidad_seleccionada: item.cantidad_selec,
                    });
                    valor_total += (cantidad_selec*importe_total)/cantidad_total;
                });
                /* --------- */
                sessionStorage.setItem('entregas_seleccionados_cartafianza',JSON.stringify(data_entregas));
                f_asociar_entregas_CTN(idprocesocab,idcontrato,contrato,valor_total);
                
            }else{
                $.messager.alert('Error','Debe seleccionar al menos una entrega.','warning');
                return;
            }
        }
    });

    $('#btn_salir_entregas_cnt').linkbutton({
        iconCls:'icon-cancel',
        onClick:function(){
            $('#win_entregas_cnt_wizard').window('close');
        }
    });


    $('#win_entregas_cnt_wizard').window({
        modal:true, collapsible:false, closable:true, minimizable:false, maximizable:true, closed:false, center:true, resizable:false
    });
}   

function f_asociar_entregas_CTN(idprocesocab,idcontrato,contrato,valor_total){
    let data_entregas = JSON.parse(sessionStorage.getItem('entregas_seleccionados_cartafianza')) || [];
    const objetoDet = JSON.stringify(data_entregas);
    $.messager.confirm('Alerta', 'Se procederá a asociar las entregas seleccionadas al contrato <b>'+contrato+'</b>, ¿Desea continuar?',function(r){
        if (r){
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
                        $.messager.alert('Info',datos.o_msj,'info');
                        $('#win_entregas_cnt_wizard').window('close');
                        $('#dg_entrega_cnt').datagrid('load', {
                            _token          : '<?= csrf_token() ?>'
                            ,_acc			: 'listarEntregasDB' 
                            ,idcontrato     : idcontrato
                        });
                        $('#dg_contrato').datagrid('load', {
                            _token          : '<?= csrf_token() ?>'
                            ,_acc			: 'listarContratos' 
                            ,idprocesocab   : idprocesocab
                        });
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
                    ,_acc                : 'asociarENT_CNT'
                    ,idcontrato          : idcontrato
                    ,detalle             : objetoDet
                    ,valor_total         : valor_total
                },
                async: true,
                dataType: "json"
            });
        }
    });
}

function generar_contrato(idprocesocab,proceso_detallado,valor_total,doc_proceso,idcontrato=0){
    $.ajax(url_consulta,{
        data: {
            _token			   :'<?= csrf_token() ?>'
            ,_acc       	   : 'listarContratos'
            ,idcontrato        : idcontrato    
        },
        type: "post",
        async: true,
        dataType: "json",
        success: function(datos){
            cab = datos.cabecera ?? [];
            let titulo_cnt = ''
            if(idcontrato > 0){
                titulo_cnt = 'Editar Contrato: '+cab.contrato;
            }else{
                titulo_cnt = 'Registrar Nuevo contrato para: '+proceso_detallado;
            }
            
            let html = `
                <div id="win_generar_contrato" title="${titulo_cnt}" class="easyui-layout" style="width:750px;height:230px;">
                    <div style="padding:3px;">
                        <input id="txt_cnt">
                        <input id="txt_cnt_sap">
                    </div>
                    <div style="padding:3px;">
                        <input id="cmb_tipo_cnt">
                        <input id="num_importe_cnt">
                        <!--<a id="btn_editar_monto_cnt"></a>-->
                    </div>
                    <div style="padding:3px;">
                        <input id="dt_fecha_emision_cnt">
                        <input id="dt_fecha_vcto_cnt">
                    </div>
                    <div style="padding:7px;">
                        <input id="filebox_new_adicional_cnt">
                        &nbsp;&nbsp;
                    </div>
                    <div style="padding:3px; margin-top: 10px; text-align: right;">
                        <a id="btn_visualizar_selec_cnt">Entregas</a>   
                        <a id="btn_back_form_cnt">Retornar</a>
                        <a id="btn_guardar_cnt">Grabar</a>
                        <a id="btn_salir_cnt">Salir</a>
                    </div>
                </div>
            `;

            if ($('#win_generar_contrato')){
                $('#win_generar_contrato').empty();
                $('#win_generar_contrato').remove();
            }

            $('body').append(html);

            if(idcontrato > 0){
                $('#btn_visualizar_selec_cnt').hide();
                $('#btn_back_form_cnt').hide();
            }

            $('#txt_cnt').textbox({
                width       : '47%',
                height 		: 20,
                labelWidth  : 100,
                label		:'Contrato',
                prompt 		: 'N° Contrato',
                required    : (idcontrato > 0) ? false : true,
                labelAlign  : 'left',
                value       : cab.contrato,
            });

            $('#txt_cnt_sap').textbox({
                width       : '47%',
                height 		: 20,
                labelWidth  : 100,
                label		:'Contrato SAP',
                prompt 		: 'N° Contrato - SAP',
                required    : (idcontrato > 0) ? false  : true,
                labelAlign  : 'left',
                onChange(newValue,oldValue){
                    if($(this).textbox('getValue').length > 12){
                        $.messager.alert('Error', 'Descripción de SAP es de máximo 12 caractéres.', 'warning');
                        $(this).textbox('setValue','');
                        return;
                    }
                },
                value       : cab.contrato_sap,
            });

            $('#cmb_tipo_cnt').combobox({
                width       : '47%',
                height 		: 20,
                labelWidth  : 100,
                labelAlign  : 'left',
                label 		: 'Tipo Contrato',
                prompt 		: '[Seleccionar]',
                url 		: url_consulta,
                limitToList : true,
                hasDownArrow: true,
                multiple:false,
                required :(idcontrato > 0) ? false : true,
                editable : false,
                queryParams : {
                    _token : '<?= csrf_token() ?>',
                    _acc : 'Combo',
                    opcion : 'CMB_TIPO_CNT',
                },
                panelHeight : 'auto',
                panelMaxHeight:200,
                valueField 	: 'idtipocontrato',
                textField 	: 'tipo_contrato',
                value       : (idcontrato > 0) ? cab.idtipocontrato : 1, // CONTRATO MARCO x DEFAULT
            });


            let valor = (idcontrato > 0) ? parseFloat(cab.monto_contrato) : valor_total;
            let maximo = parseFloat(valor*1.1);
            let minimo = parseFloat(valor-(0.1*valor));
            $('#num_importe_cnt').numberbox({
                width       : '47%',
                height 		: 20,
                labelWidth  : 100,
                label		:'Importe',
                labelAlign  : 'left',
                precision   : 2,
                disabled    : true,
                value       : (idcontrato > 0) ? cab.monto_contrato : valor_total,
                onChange: function (newValue, oldValue) {
                    if(newValue > maximo){
                        $.messager.alert('Error', 'Monto ingresado excede en 10% a las entregas seleccionadas, no es posible continuar.', 'warning');
                        $('#num_importe_cnt').numberbox('setValue',valor);
                        return;
                    }
                    if(newValue < minimo){
                        $.messager.alert('Error', 'Monto ingresado se encuentra por debajo del 10% de las entregas seleccionadas, no es posible continuar.', 'warning');
                        $('#num_importe_cnt').numberbox('setValue',valor);
                        return;
                    }
                }

            });

            /* $('#btn_editar_monto_cnt').linkbutton({
                height 		: 20,
                iconCls:'icon-editarctf',
                onClick:function(){
                    var isDisabled = $('#num_importe_cnt').numberbox('options').disabled;
                    if (isDisabled) {
                        $('#num_importe_cnt').numberbox({
                            disabled: false,
                        });
                    } else {
                        $('#num_importe_cnt').numberbox({
                            disabled: true,
                        });
                    }

                }
            }); */

            $('#dt_fecha_emision_cnt').datebox({
                width		: '250px',
                height 		: 25,
                labelWidth	: 100,
                label		:'F. Suscripción',
                labelAlign	:'left',
                parser:new_parser_date,
                formatter:new_formatter_date,
                height:20,
                editable: false,
                required  : (idcontrato > 0) ? false  : true,
                value: (idcontrato > 0) ? cab.fecha_ini_contrato_order : hoy,
            });

            $('#dt_fecha_vcto_cnt').datebox({
                width		: '250px',
                height 		: 25,
                labelWidth	: 100,
                label		:'Fecha Vcto.',
                labelAlign	:'left',
                parser:new_parser_date,
                formatter:new_formatter_date,
                required : (idcontrato > 0) ? false  : true,
                height:20,
                editable: false,
                value: (idcontrato > 0) ? cab.fecha_fin_contrato_order : trimestrePosterior,
            });

            $('#filebox_new_adicional_cnt').filebox({
                label:'Adjunto',
                labelWidth:100,
                labelAlign:'left',
                buttonText: 'Seleccione Archivos',
                buttonAlign: 'left',
                width:'95%',
                height:20,
                prompt: '[Seleccionar - Tamaño máximo 2MB]',
                multiple:false,
                required: (idcontrato > 0) ? false : true,
                disabled: false,
                onChange: function (newValue, oldValue) {
                    // Obtener la extensión del archivo
                    var fileName = $(this).filebox('getValue');
                    var fileExtension = fileName.substr(fileName.lastIndexOf('.') + 1);
                    // Lista de extensiones permitidas 
                    var allowedExtensions = ['jpg','png','pdf']; 
                    // Validar la extensión del archivo
                    if (!allowedExtensions.includes(fileExtension.toLowerCase())) {
                        // Mostrar un mensaje de error
                        $.messager.alert('Error', 'Formato de archivo no permitido. Por favor, adjuntar algunas de las extensiones habilitadas: [jpg,png,pdf].', 'error');
                        $(this).filebox('clear');
                        return;
                    }
                }
            });

            $('#btn_visualizar_selec_cnt').linkbutton({
                iconCls:'icon-cargo_truck',
                onClick:function(){
                    visualizar_entregas_selec();
                }
            });

            $('#btn_back_form_cnt').linkbutton({
                'iconCls':'icon-arrow-left',
                onClick:function(){
                    $.messager.confirm('Alerta', 'Se volverá a la selección de Entregas y se pederán los valores registrados, ¿Desea continuar?',function(r){
                        if (r){
                            $('#win_generar_contrato').window('close');
                            generar_contrato_wizard(idprocesocab,proceso_detallado,'',doc_proceso);
                        }
                    });
                }
            });

            $('#btn_guardar_cnt').linkbutton({
                iconCls:'icon-save',
                onClick:function(){
                    f_registrarCNT(idprocesocab,idcontrato);
                }
            });

            $('#btn_salir_cnt').linkbutton({
                iconCls:'icon-cancel',
                onClick:function(){
                    $('#win_generar_contrato').window('close');
                }
            });

            $('#win_generar_contrato').window({
                modal:true, collapsible:false, closable:true, minimizable:false, maximizable:false, closed:false, center:true, resizable:false
            });
        },
        error:function(x,e){
            $.messager.alert('Error '+x.status,'Ocurrió un error en el servidor.','error');
        },
        beforeSend: function(){
            $.messager.progress({text:'Cargando contrato, por favor espere...'});
        },
        complete: function(){
            $.messager.progress('close');
        },

    });
}

function visualizar_entregas_selec(){
    let html = `
        <div id="win_revision_entregas_cnt" title="Revisión de las Entregas seleccionadas" class="easyui-layout" style="width:95%;height:650px;">
            <table id="tb_revision_entregas_cnt"></table>    
            <table id="dg_revision_entregas_cnt"></table>
        </div>
    `;

    if ($('#win_revision_entregas_cnt')){
        $('#win_revision_entregas_cnt').empty();
        $('#win_revision_entregas_cnt').remove();
    }

    $('body').append(html);

    $('#dg_revision_entregas_cnt').datagrid({
        fitColumns:false,
        singleSelect:true,
        rownumbers:true,
        pagination:true,
        pageSize:50,
        striped:true,
        fit:true,
        nowrap:false,
        border:false,
        showFooter:true,
        loadMsg: 'Cargando por favor espere...',
        toolbar: '#tb_revision_entregas_cnt',
        columns:[[
            {field:'documento',title:'Documento',align:'center',halign:'center',width:'100px'},
            {field:'producto',title:'Producto',align:'left',halign:'center',width:'550px'},
            {field:'molecula',title:'Molécula',align:'center',halign:'center',width:'200px'},
            {field:'nro_entrega',title:'Nro. Entrega',align:'center',halign:'center',width:'80px'},
            {field:'cliente_dm',title:'Destinatario<br>Mercancía',align:'left',halign:'center',width:'350px'},
            {field:'importe_total',title:'Importe<br>Total',align:'center',halign:'center',width:'170px'},
            {field:'importe_selec',title:'Importe<br>Seleccionado',align:'center',halign:'center',width:'150px',formatter:function(value,row,index){
                var importe_selec_dg = row.importe_selec;
                var importeSelecFormat = importe_selec_dg.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                return `
                    <div>${importeSelecFormat} PEN</div>
                `
            }},
            {field:'um_venta',title:'UM Venta',align:'center',halign:'center',width:'80px'},
            {field:'cantidad_total',title:'Cantidad<br>Total',align:'center',halign:'center',width:'150px'},
            {field:'cantidad_selec',title:'Cantidad<br>Seleccionada',align:'center',halign:'center',width:'150px',formatter:function(value,row,index){
                var cantidad_seleccionada_dg = row.cantidad_selec;
                //var cantidadSelecFormat = cantidad_seleccionada_dg.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                var cantidadSelecFormat = cantidad_seleccionada_dg.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                //var cantidadSelecFormat = cantidad_seleccionada.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                return `
                    <div>${cantidadSelecFormat}</div>
                `
            }},
        ]],
        onLoadSuccess: function (data) {
            // NETEO TOTAL
            var totalSum = 0;
            for (var i = 0; i < data.rows.length; i++) {
                var row = data.rows[i];
                var importe = parseFloat(row.importe_total.replace(',', ''));
                if (!isNaN(importe)) {
                    totalSum += importe;
                }
            }
            var formattedTotal = totalSum.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
            data.rows[0].footer = true;
            // NETEO SELECCIONADO
            var totalSum_selec = 0;
            for (var i = 0; i < data.rows.length; i++) {
                var row = data.rows[i];
                var impote_selec = row.importe_selec;
                if (!isNaN(impote_selec)) {
                    totalSum_selec += impote_selec;
                }
            }
            var formattedTotal_selec = totalSum_selec.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
            data.rows[0].footer = true;
            // Actualiza el contenido del footer
            $('#dg_revision_entregas_cnt').datagrid('reloadFooter', [{
                fila: 'Total: ',
                importe_total: formattedTotal+' PEN',
                cantidad_selec: '', 
                importe_selec: formattedTotal_selec,
            }]); 
        }, 
        onLoadError: function(XMLHttpRequest, textStatus, errorThrown){
            $.messager.alert('Error','Error al mostrar los datos, vuelva a intentar','error');
        }
    }).datagrid('getPager').pagination({
        beforePageText: 'Pag. ',
        afterPageText: 'de {pages}',
        displayMsg: 'Del {from} al {to}, de {total} items.'
    });

    var valor_total = 0;
    let rows = $('#dg_entregas_cnt').datagrid('getChecked');
    var valor_total = 0;
    var ultimoSel = {};
    var res2 = [];
    var cantidad_seleccionada = JSON.parse(sessionStorage.getItem('entregas_seleccionados_prev')) || [];
    var data_entregas = JSON.parse(sessionStorage.getItem('entregas_seleccionados_cartafianza')) || [];
    if(rows && rows.length > 0){
        /* LOGICA PARA MAPEO DE LO SELECCIONADO */
        cantidad_seleccionada.forEach(item => {
            ultimoSel[item.identregadet] = item.cantidad_selec;
        });
        let res = Object.keys(ultimoSel).map(identregadet => ({
            identregadet,
            cantidad_selec: ultimoSel[identregadet]
        }));
        let newRows = rows.map(function(row){
            return {
                identregadet         : row.identregadet,
                // PARA CALCULO DE VALIDACION
                cantidad_total       : row.cantidad_prevista,
                importe_total        : row.importe_neto_moneda,
                producto             : row.producto,
                documento            : row.documento,
                nro_entrega          : row.nro_entrega,
                um_venta             : row.um_venta,
                cliente_dm           : row.cliente_dm,
                cantidad_prevista    : row.cantidad_prevista, //can_total
                valor_neto           : row.valor_neto, // precio total
            };
        });
        res.forEach(resItem => {
            let matchingNewRow = newRows.find(newRow => newRow.identregadet === resItem.identregadet);
            if (matchingNewRow) {
                res2.push({
                    identregadet: resItem.identregadet,
                    cantidad_selec: resItem.cantidad_selec,
                    // PARA PINTADO
                    cantidad_total: matchingNewRow.cantidad_total,
                    importe_total: matchingNewRow.importe_total,
                    producto: matchingNewRow.producto,
                    documento: matchingNewRow.documento,
                    nro_entrega: matchingNewRow.nro_entrega,
                    um_venta: matchingNewRow.um_venta,
                    cliente_dm : matchingNewRow.cliente_dm,
                    importe_selec: (resItem.cantidad_selec*matchingNewRow.valor_neto)/matchingNewRow.cantidad_prevista,
                });
            }
        });

        /* ORDENO PARA IMPRESION */
        res2.sort(function(a, b) {
            return a.nro_entrega - b.nro_entrega;
        });

        $('#dg_revision_entregas_cnt').datagrid('loadData', res2);

        $('#win_revision_entregas_cnt').window({
            modal:true, collapsible:false, closable:true, minimizable:false, maximizable:true, closed:false, center:true, resizable:false
        });

    }
}

function f_registrarCNT(idprocesocab,idcontrato=0){
    let data_entregas = JSON.parse(sessionStorage.getItem('entregas_seleccionados_cartafianza')) || [];
    const objetoDet = JSON.stringify(data_entregas);
    let cnt = $('#txt_cnt').textbox('getValue');
    let cnt_sap = $('#txt_cnt_sap').textbox('getValue');
    let idtipocnt = $('#cmb_tipo_cnt').combobox('getValue');
    let importe_cnt = $('#num_importe_cnt').numberbox('getValue');
    let fecha_emi_cnt = $('#dt_fecha_emision_cnt').datebox('getValue');
    let fecha_vcto_cnt = $('#dt_fecha_vcto_cnt').datebox('getValue');
    let files_add = $('#filebox_new_adicional_cnt').filebox('files');

    var formData = new FormData();
    for(var i=0; i<files_add.length; i++){
        var file_add = files_add[i];
        formData.append('archivos_add[]',file_add,file_add.name);
    }

    // Validaciones
    if(data_entregas.length == 0 && idcontrato == 0){
        $.messager.alert('Error','Debe seleccionar las entregas.','warning');
        return;
    }
    if(cnt.length == 0){
        $.messager.alert('Error','Debe registrar el número de contrato.','warning');
        return;
    }
    if(cnt_sap.length == 0){
        $.messager.alert('Error','Debe registar el número de contrato SAP.','warning');
        return;
    }
    if(cnt_sap.length > 12){
        $.messager.alert('Error', 'Descripción de SAP es de máximo 12 caractéres.', 'warning');
        return;
    }
    if(idtipocnt.length == 0){
        $.messager.alert('Error','Falta tipo de contrato.','warning');
        return;
    }
    if(importe_cnt.length == 0){
        $.messager.alert('Error','Falta importe de contrato.','warning');
        return;
    } 
    if(fecha_emi_cnt.length == 0){
        $.messager.alert('Error','Fecha de Emisión del Contrato es obligatorio.','warning');
        return;
    } 
    if(fecha_vcto_cnt.length == 0){
        $.messager.alert('Error','Fecha de Vencimiento del Contrato es obligatorio.','warning');
        return;
    } 
    if(files_add.length == 0 && idcontrato == 0){
        $.messager.alert('Error','Archivo adjuntos son obligatorios.','warning');
        return;
    }
    // fin validaciones
    if(idcontrato == 0){
        formData.append('_acc','registrarCNT');
        formData.append('objetoDet',objetoDet);
    }else{
        formData.append('_acc','editarCNT');
        formData.append('idcontrato',idcontrato);
    }
    formData.append('_token','<?= csrf_token() ?>');
    formData.append('idprocesocab',idprocesocab);
    formData.append('cnt',cnt);
    formData.append('cnt_sap',cnt_sap);
    formData.append('idtipocnt',idtipocnt);
    formData.append('importe_cnt',importe_cnt);
    formData.append('fecha_emi_cnt',fecha_emi_cnt);
    formData.append('fecha_vcto_cnt',fecha_vcto_cnt);

    let adv = (idcontrato > 0) ? 'editar' : 'registrar';
    $.messager.confirm('Alerta', 'Se procederá a '+adv+' el contrato con los datos brindados, ¿Desea continuar?',function(r){
        if (r){
            $.ajax(url_mantenimiento,{
                type:'post',
                data:formData,
                contentType:false,
                processData:false,
                beforeSend: function(){
                    $.messager.progress({text:'Procesando...'});
                },
                complete: function(){
                    $.messager.progress('close');
                },
                success: function(datos){
                    if (datos.o_nres == 1){
                        $('#win_generar_contrato').window('close');
                        $.messager.alert('Info',datos.o_msj,'info');
                        $('#dg_contrato').datagrid('load', {
                            _token          : '<?= csrf_token() ?>'
                            ,_acc			: 'listarContratos' 
                            ,idprocesocab	: idprocesocab
                        });
                    } 
                    else {
                        $.messager.alert('Error',datos.o_msj,'error');
                    }
                },
                error:function(x,e){
                    $.messager.alert('Error '+x.status,'Ocurrió un error en el servidor.','error');
                },
                async: true
            });
        }
    });
}

function adjuntosCNT(idcontrato){    
    $.ajax(url_consulta,{
        data: {
            _token			   :'<?= csrf_token() ?>'
            ,_acc       	   : 'listarContratos'
            ,idcontrato        : idcontrato
        },
        type: "post",
        async: true,
        dataType: "json",
        success: function(datos){
            cab = datos.cabecera ?? [];
            let html = `
                <div id="win_adjuntos_cnt" title="Adjuntos para Contrato: ${cab.contrato}" class="easyui-layout" style="width:75%;height:700px;">
                    <table id="tb_adjuntos_cnt" style="width:100% !important;">
                        <tr>
                            <td style="width:90%;text-align: right;">
                                <div style="width:100%; padding:3px; margin:3px;">
                                    <a id="btn_subir_archivo_cnt">Subir</a>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <table id="dg_adjuntos_cnt"></table>
                </div>
            `;

            if ($('#win_adjuntos_cnt')){
                $('#win_adjuntos_cnt').empty();
                $('#win_adjuntos_cnt').remove();
            }

            $('body').append(html);

            $('#dg_adjuntos_cnt').datagrid({
                url:url_consulta,
                fitColumns:false,
                singleSelect:true,
                rownumbers:true,
                pagination:true,
                pageSize:50,
                striped:true,
                fit:true,
                nowrap:false,
                border:false,
                loadMsg: 'Cargando por favor espere...',
                queryParams: {
                    _token          : '<?= csrf_token() ?>'
                    ,_acc			: 'listarAdjuntosCNT'
                    ,idcontrato     : idcontrato
                },
                toolbar: '#tb_adjuntos_cnt',
                columns:[[          
                    {field:'id',title:'Archivo',align:'left',halign:'center',width:'37.67%',formatter:function(val,row,index){
                        return `
                            <div style="font-weight:bold;">Nombre de Archivo: ${row.nombre_archivo}</div>
                            <div style="font-size:10px;color:blue;">Extensión: ${row.extension}</div>
                            <div style="font-size:10px;color:blue;">Tamaño: ${formatNumber2Decimal.new2(parseFloat(row.size) / 1024)} KB</div>
                        `
                    }},
                    {field:'validez',title:'Válido',align:'center',halign:'center',width:'4.79%',formatter:function(val,row,index){
                        if(row.idestadodato == 1){
                            return `<span style="border-radius:5px 5px 5px 5px;
                                background-color:green;
                                color:white;padding:3px;cursor:pointer;">
                                SI</span>` 
                        }else{
                            return `<span style="border-radius:5px 5px 5px 5px;
                                background-color:red;
                                color:white;padding:3px;cursor:pointer;">
                                NO</span>` 
                        }
                    }}, 
                    {field:'tipo_adj',title:'Tipo',align:'center',halign:'center',width:'10.27%'},
                    {field:'estado',title:'Estado<br>Registro',align:'center',halign:'center',width:'13.01%',formatter:function(val,row,index){
                        return `<span style="border-radius:5px 5px 5px 5px;
                            background-color:${row.color_estado};
                            color:white;padding:3px;cursor:pointer;">
                        ${row.desc_estado}</span>` 
                    }}, 
                    {field:'auditoria_reg',title:'Registro',align:'left',halign:'center',width:'20.55%',formatter:function(val,row,index){
                        let fechaRegistro = formatter_date_SAP(row.fechareg);
                        return `
                            <div><span style="font-size: 11px; color: blue;">Usuario Registro: </span><span style="font-size:11px;">${row.usuario_reg}</span>
                            <div><span style="font-size: 11px; color: blue;">Fecha Registro: </span>${fechaRegistro} ${row.horareg}
                        `
                    }},
                    {field:'adjuntos',title:'Descargar',align:'center',halign:'center',width:'6.85%',formatter:function(value,row,index){
                        return `<img src="${ambiente}img/icons/flecha_verde_down.png"  onclick="descargar_adjunto_cnt(${row.idadjuntocontrato})" title="Descargar" style="cursor:pointer;width:16px;height:16px">`
                    }},
                    {field:'ver',title:'Visualizar',align:'center',halign:'center',width:'6.85%',formatter:function(value,row,index){
                        if(['pdf', 'jpg', 'png', 'jpeg'].includes(row.extension)){
                            return `<img src="${ambiente}img/icons/view.png" onclick="ver_pdf('${row.contrato}','${row.ruta}','${row.nombre_archivo}',7,'${row.operador_alias}')" title="Visualizar Adjunto" style="cursor:pointer;width:16px;height:16px">`
                        }else{
                            return ``
                        }
                    }}
                ]],
                onLoadError: function(XMLHttpRequest, textStatus, errorThrown){
                    $.messager.alert('Error','Error al mostrar los datos, vuelva a intentar','error');
                },
            }).datagrid('getPager').pagination({
                beforePageText: 'Pag. ',
                afterPageText: 'de {pages}',
                displayMsg: 'Del {from} al {to}, de {total} items.'
            });

            $('#dg_adjuntos_cnt').datagrid('enableFilter');

            $('#btn_subir_archivo_cnt').linkbutton({
                iconCls:'icon-subir',
                disabled: (cab.idestadocontrato == 4) ? true : false, // RECHAZADO 
                onClick:function(){
                    let idtipoadjunto = 1;//(cab.idestadocartafianzafinal == 2) ? 5 : 1;  
                    cargarAdjuntoCNT(cab.idcontrato,cab.contrato,idtipoadjunto); // adjunto regular
                }
            });

            $('#win_adjuntos_cnt').window({
                modal:true, collapsible:false, closable:true, minimizable:false, maximizable:true, closed:false, center:true, resizable:false
            });
        },
        error:function(x,e){
            $.messager.alert('Error '+x.status,'Ocurrió un error en el servidor.','error');
        },
        beforeSend: function(){
            $.messager.progress({text:'Cargando evaluación bancaria, por favor espere...'});
        },
        complete: function(){
            $.messager.progress('close');
        },

    });
}

function cargarAdjuntoCNT(idcontrato,contrato,idtipoadjunto){
    let html = `
        <div id="win_cargar_adjunto_cnt" title="Cargar Adjunto para: ${contrato}" class="easyui-layout" style="width:750px;height:20%;">
            <div style="padding:7px;">
                <input id="cmb_tipo_adjunto_contrato">
                &nbsp;&nbsp;
            </div>
            <div style="padding:7px;">
                <input id="filebox_new_cargar_adjunto_cnt">
                &nbsp;&nbsp;
            </div>
            <div style="padding:3px; margin-top: 10px; text-align: right;">
                <a id="btn_guardar_cargar_adjunto_cnt">Grabar</a>
                <a id="btn_salir_cargar_adjunto_cnt">Salir</a>
            </div>
        </div>
    `;
    if ($('#win_cargar_adjunto_cnt')){
        $('#win_cargar_adjunto_cnt').empty();
        $('#win_cargar_adjunto_cnt').remove();
    }

    $('body').append(html);

    $('#cmb_tipo_adjunto_contrato').combobox({
        width       : '90%',
        height 		: 20,
        labelWidth  : 80,
        labelAlign  : 'left',
        label 		: 'Tipo',
        prompt 		: '[Seleccionar]',
        url 		: url_consulta,
        limitToList : true,
        hasDownArrow: true,
        multiple:false,
        required :true,
        editable: true,
        queryParams : {
            _token : '<?= csrf_token() ?>',
            _acc : 'Combo',
            opcion : 'CMB_TIPO_ADJ',
            input4 : 1
        },
        panelHeight : 'auto',
        panelMaxHeight:200,
        valueField 	: 'idtipoadjunto',
        textField 	: 'tipo_adjunto',
        //value: idtipoadjunto
    });

    $('#filebox_new_cargar_adjunto_cnt').filebox({
        label:'Adjunto(s)',
        labelWidth:80,
        labelAlign:'left',
        buttonText: 'Seleccione Archivos',
        buttonAlign: 'left',
        width:'90%',
        height:20,
        prompt: '[Seleccionar - Tamaño máximo 2MB]',
        multiple: true,
        required: false,
        disabled: false,
        onChange: function (newValue, oldValue) {
            // Obtener la extensión del archivo
            var fileName = $(this).filebox('getValue');
            var fileExtension = fileName.substr(fileName.lastIndexOf('.') + 1);
            // Lista de extensiones permitidas 
            let textoval = '[pdf,png,jpeg]'; 
            var allowedExtensions = ['pdf','png','jpeg']; 
            // Validar la extensión del archivo
            if (!allowedExtensions.includes(fileExtension.toLowerCase())) {
                // Mostrar un mensaje de error
                $.messager.alert('Error', 'Formato de archivo no permitido. Por favor, adjuntar algunas de las extensiones habilitadas: '+textoval+'.', 'error');
                $(this).filebox('clear');
                return;
            }
        }
    });

    $('#btn_guardar_cargar_adjunto_cnt').linkbutton({
        iconCls:'icon-save',
        onClick:function(){
            var idtipoadjunto_combo = $('#cmb_tipo_adjunto_contrato').combobox('getValue');
            let files_add = $('#filebox_new_cargar_adjunto_cnt').filebox('files');
            var formData = new FormData();
            for(var i=0; i<files_add.length; i++){
                var file_add = files_add[i];
                formData.append('archivos_add[]',file_add,file_add.name);
            }

            // Validaciones
            if(idtipoadjunto_combo.length == 0){
                $.messager.alert('Error','Tipo adjunto es obligatorio.','warning');
                return;
            }

            if(files_add.length == 0){
                $.messager.alert('Error','Debe adjuntar por lo menos un archivo.','warning');
                return;
            }
            
            formData.append('_token','<?= csrf_token() ?>');
            formData.append('_acc','cargarAdjuntoCNT');
            formData.append('idcontrato',idcontrato);
            formData.append('contrato',contrato);
            formData.append('idtipoadjunto',idtipoadjunto_combo);

            $.messager.confirm('Alerta', 'Se procederá a cargar los adjuntos brindados, ¿Desea continuar?',function(r){
                if (r){
                    $.ajax(url_mantenimiento,{
                        type:'post',
                        data:formData,
                        contentType:false,
                        processData:false,
                        beforeSend: function(){
                            $.messager.progress({text:'Procesando...'});
                        },
                        complete: function(){
                            $.messager.progress('close');
                        },
                        success: function(datos){
                            if (datos.o_nres == 1){
                                $('#win_cargar_adjunto_cnt').window('close');
                                $.messager.alert('Info',datos.o_msj,'info');
                                $('#dg_adjuntos_cnt').datagrid('load', {
                                    _token          : '<?= csrf_token() ?>'
                                    ,_acc			: 'listarAdjuntosCNT' 
                                    ,idcontrato     : idcontrato        
                                });
                            } 
                            else {
                                $.messager.alert('Error',datos.o_msj,'error');
                            }
                        },
                        error:function(x,e){
                            $.messager.alert('Error '+x.status,'Ocurrió un error en el servidor.','error');
                        },
                        async: true
                    });
                }
            });
        }
    });

    $('#btn_salir_cargar_adjunto_cnt').linkbutton({
        iconCls:'icon-cancel',
        onClick:function(){
            $('#win_cargar_adjunto_cnt').window('close');
        }
    });

    $('#win_cargar_adjunto_cnt').window({
        modal:true, collapsible:false, closable:true, minimizable:false, maximizable:false, closed:false, center:true, resizable:false
    });
}

/* SITUACIONES */
function asociarSITUACIONES(idprocesocab,idcontrato,contrato,idcartafianzafinal=''){
    $.ajax(url_consulta,{
        data: {
            _token			   :'<?= csrf_token() ?>'
            ,_acc       	   : 'listarContratosxSituaciones'
            ,idcontrato        : idcontrato       
            ,idcartafianzafinal  : idcartafianzafinal
        },
        type: "post",
        async: true,
        dataType: "json",
        success: function(datos){
            cab = datos.cabecera ?? [];
            let html = `
                <div id="win_situaciones" title="Estatus Legal del Contrato: ${contrato}" class="easyui-layout" style="width:50%;height:50%;">
                    ${cab.estatus_fi ? `
                        <div style="width:100%;padding:3px;">
                            <input id="txt_situaciones_estatus_fi"> 
                        </div>
                    ` : ``}
                    <div id="flowContainer">
                        <!--<div class="stage" id="ventaInstitucionalStage">
                            <h3>Venta Institucional</h3>
                            <input id="cmb_venta_ins" style="width:150px;">
                        </div>-->
                        <!--<div class="stage" id="creditosCobranzasStage">
                            <h3>Créditos y Cobranzas</h3>
                            <input id="cmb_cyc" style="width:150px;">
                        </div>-->
                        <div class="stage" id="areaLegalStage">
                            <h3>Área Legal</h3>
                            <input id="cmb_legal" style="width:150px;">
                        </div>
                    </div>
                </div>
            `;

            if ($('#win_situaciones')){
                $('#win_situaciones').empty();
                $('#win_situaciones').remove();
                $('#win_situaciones').window('destroy');
            }

            $('body').append(html);

            if(cab.estatus_fi){
                $('#txt_situaciones_estatus_fi').textbox({
                    width       : '99%',
                    height 		: 50,
                    disabled    : true,
                    multiline   : true,
                    value       : 'Area Responsable: '+cab.area_fi+'\n\t'+cab.estatus_fi,
                }).textbox('textbox').addClass('textbox-disabled');
            }

            /* $('#cmb_venta_ins').combobox({
                icons   : [{
                    iconCls:'icon-clear',
                    handler:function(e){
                        if($(e.data.target).combobox('getValue') == ''){
                            $.messager.alert('Error','No se encontraron registros en el combo seleccionado.','error');
                            return;
                        }else{
                            ajaxAsociarSIT(idprocesocab,idcontrato,0,'Venta Institucional',contrato,'#cmb_venta_ins',cab.idsituaciones_vsi);
                        }
                        //$(e.data.target).combobox('clear');
                    } 
                }],
                width       : '99%',
                height 		: 20,
                labelWidth  : 60,
                labelAlign  : 'left',
                prompt 		: 'No Definido',
                url 		: url_consulta,
                limitToList : true,
                hasDownArrow: true,
                multiple    :false,
                editable    : false,
                disabled    : false,
                queryParams : {
                    _token : '<?= csrf_token() ?>',
                    _acc : 'Combo',
                    opcion : 'CMB_SITUACIONES',
                    input2 : 1, // venta inst.
                },
                onChange(newValue,oldValue){
                    if(newValue != ''){
                        ajaxAsociarSIT(idprocesocab,idcontrato,newValue,'Venta Institucional',contrato,'#cmb_venta_ins',cab.idsituaciones_vsi);
                    }                    
                },
                panelHeight : 'auto',
                panelMaxHeight:200,
                valueField 	: 'idsituaciones',
                textField 	: 'situacion',
                value       : cab.idsituaciones_vsi
            }); 

            $('#cmb_cyc').combobox({
                icons   : [{
                    iconCls:'icon-clear',
                    handler:function(e){
                        if($(e.data.target).combobox('getValue') == ''){
                            $.messager.alert('Error','No se encontraron registros en el combo seleccionado.','error');
                            return;
                        }else{
                            ajaxAsociarSIT(idprocesocab,idcontrato,0,'Créditos y Cobranza',contrato,'#cmb_cyc',cab.idsituaciones_cyc);
                        }
                        //$(e.data.target).combobox('clear');
                    }
                }],
                width       : '99%',
                height 		: 20,
                labelWidth  : 60,
                labelAlign  : 'left',
                prompt 		: 'No Definido',
                url 		: url_consulta,
                limitToList : true,
                hasDownArrow: true,
                multiple    :false,
                editable    : false,
                disabled    : false,
                queryParams : {
                    _token : '<?= csrf_token() ?>',
                    _acc : 'Combo',
                    opcion : 'CMB_SITUACIONES',
                    input2 : 2, // venta inst.
                },
                onChange(newValue,oldValue){
                    if(newValue != ''){
                        ajaxAsociarSIT(idprocesocab,idcontrato,newValue,'Créditos y Cobranza',contrato,'#cmb_cyc',cab.idsituaciones_cyc);
                    }
                },
                panelHeight : 'auto',
                panelMaxHeight:200,
                valueField 	: 'idsituaciones',
                textField 	: 'situacion',
                value       : cab.idsituaciones_cyc
            }); */

            $('#cmb_legal').combobox({
                icons   : [{
                    iconCls:'icon-clear',
                    handler:function(e){
                        if($(e.data.target).combobox('getValue') == ''){
                            $.messager.alert('Error','No se encontraron registros en el combo seleccionado.','error');
                            return;
                        }else{
                            ajaxAsociarSIT(idprocesocab,idcontrato,0,'Legal',contrato,'#cmb_legal',cab.idsituaciones_legal);
                        }
                        //$(e.data.target).combobox('clear');
                    }
                }],
                width       : '99%',
                height 		: 20,
                labelWidth  : 60,
                labelAlign  : 'left',
                prompt 		: 'No Definido',
                url 		: url_consulta,
                limitToList : true,
                hasDownArrow: true,
                multiple    :false,
                editable    : false,
                disabled    : false,
                queryParams : {
                    _token : '<?= csrf_token() ?>',
                    _acc : 'Combo',
                    opcion : 'CMB_SITUACIONES',
                    input2 : 3, // venta inst.
                },
                onChange(newValue,oldValue){
                    if(newValue != ''){
                        ajaxAsociarSIT(idprocesocab,idcontrato,newValue,'Legal',contrato,'#cmb_legal',cab.idsituaciones_legal);
                    }
                },
                panelHeight : 'auto',
                panelMaxHeight:200,
                valueField 	: 'idsituaciones',
                textField 	: 'situacion',
                value       : cab.idsituaciones_legal
            });

            /* let connections = [];

            jsPlumb.ready(function() {
                jsPlumb.setContainer($('#flowContainer'));

                var common = {
                    anchor: "AutoDefault",
                    connector: ["Flowchart", { cornerRadius: 5 }],
                    endpoint: "Blank",
                    paintStyle: { strokeWidth: 3, stroke: "#000000" },
                    overlays: [["Arrow", { width: 10, length: 10, location: 1 }]]
                };

                function connectStages() {
                    if (connections.length === 0) {
                        connections.push(jsPlumb.connect({
                            source: "ventaInstitucionalStage",
                            target: "creditosCobranzasStage",
                            anchors: ["BottomCenter", "TopCenter"] // Anclajes ajustados para evitar invasión
                        }, common));

                        connections.push(jsPlumb.connect({
                            source: "creditosCobranzasStage",
                            target: "areaLegalStage",
                            anchors: ["BottomCenter", "TopCenter"] // Anclajes ajustados para evitar invasión
                        }, common));
                    }
                }

                connectStages();

                $('#win_situaciones').window({
                    modal: true,collapsible: false,closable: true,minimizable: false,maximizable: false,closed: false,center: true,resizable: false,
                    onOpen: function() {
                        connectStages();
                    },
                    onClose: function() {
                        jsPlumb.reset(); // Reset jsPlumb state
                        connections = []; // Reset connections array
                    }
                });
            }); */

            $('#win_situaciones').window({
                modal: true,collapsible: false,closable: true,minimizable: false,maximizable: false,closed: false,center: true,resizable: false,
            });
        },
        error:function(x,e){
            $.messager.alert('Error '+x.status,'Ocurrió un error en el servidor.','error');
        },
        beforeSend: function(){
            $.messager.progress({text:'Consultando...'});
        },
        complete: function(){
            $.messager.progress('close');
        },

    });
}
</script>