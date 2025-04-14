<script>
    function revisores(idprocesocab) {
        console.log('idprocesocab', idprocesocab);
        let html = `
        <div id="win_ver_revisores" title="Flujo de Revisores" class="easyui-layout" style="width:75%;height:600px;">
        
            <table id="tb_historial_revisores">
            </table>

            <table id="dg_historial_revisores"></table>
        </div>
    `;

        if ($('#win_ver_revisores')) {
            $('#win_ver_revisores').empty();
            $('#win_ver_revisores').remove();
        }

        $('body').append(html);

        $('#dg_historial_revisores').datagrid({
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
                _token: '<?= csrf_token() ?>'
                , _acc: 'listarHistRevisores'
                , idprocesocab: idprocesocab
            },
            toolbar: '#tb_historial_revisores',
            columns: [[
                    {field: 'usuario', title: 'Usuario', align: 'left', halign: 'center', width: '350px'},
                    {field: 'desc_estado', title: 'Estado', align: 'center', halign: 'center', width: '120px', formatter: function (val, row, index) {
                            if (row.codigo_estado !== '') {
                                var estado_new = row.desc_estado;
                                if (row.codigo_estado == 'B') {
                                    estado_new = estado_new + ' ' + row.orden;
                                }
                                return `<span style="border-radius:5px 5px 5px 5px;background-color:${row.color_estado};color:white;padding:3px;cursor:pointer;">
                    ${estado_new}</span>`
                            }
                        }},

                    {field: 'indicador_activox', title: 'Revisar', align: 'center', halign: 'center', width: '80px', formatter: function (val, row, index) {
                            if (row.codigo_estado !== '') {
                                var estado_new = row.desc_estado;
                                var disabled = '';
                                var checked = '';

                                if (row.codigo_estado == 'A' || row.codigo_estado == 'C') { //PENDIENTE(A) , REVISADO(C)
                                    disabled = ' disabled ';
                                    if (row.codigo_estado == 'C') {
                                        checked = ' checked ';
                                    }
                                } else if (row.codigo_estado == 'B') {

                                }
                            }
                            return ` <div class="form-check form-switch form-switch-lg">
                                    <input class="form-check-input check_elemento" data-codigo="` + row.idrevisor_protocolo + `" data-prot_id="` + row.idprotocolo_cab + `" type="checkbox" role="switch" ` + checked + ` ` + disabled + ` />
                            </div>`
                        }},
                    {field: 'txt_obs', title: 'Observación', align: 'left', halign: 'center', width: '350px'},

                    {field: 'auditoria_reg', title: 'Registro', align: 'center', halign: 'center', width: '150px', formatter: function (val, row, index) {
                            let fechareg = formatter_date_SAP(row.fechareg);
//                            console.log('row', row);
                            return `<div>${fechareg} ${row.horareg}</div>`
                        }},
                    {field: 'auditoria_mod', title: 'Modificación', align: 'center', halign: 'center', width: '150px', formatter: function (val, row, index) {
                            let fechamod = formatter_date_SAP(row.fechamod);
                            console.log('row', row);
                            return `<div>${fechamod} ${row.horamod}</div>`
                        }},
                ]],
            onLoadError: function (XMLHttpRequest, textStatus, errorThrown) {
                $.messager.alert('Error', 'Error al mostrar los datos, vuelva a intentar', 'error');
            },
        }).datagrid('getPager').pagination({
            beforePageText: 'Pag. ',
            afterPageText: 'de {pages}',
            displayMsg: 'Del {from} al {to}, de {total} items.'
        });

        $('#win_ver_revisores').window({
            modal: true, collapsible: false, closable: true, minimizable: false, maximizable: false, closed: false, center: true, resizable: false
        });

        $('[id*="win_ver_revisores"]').on('change', '.check_elemento', function (e) {
            e.preventDefault();
            var thisx = $(this);
            var isChecked = $(this).is(':checked');
            var codigo = $(this).data('codigo');
            var prot_id = $(this).data('prot_id');

//            console.log('isChecked', isChecked, 'codigo', codigo);
            if (isChecked) {
                confirmar_revisor(codigo, prot_id, thisx);

            }

//            $(this).prop("checked", true);
        });
    }

    function vista_protocolo(idprotocolo, codigo_producto, lote = '', lote_insp = '', versionsap = '') {

        if (codigo_producto == '') {
            $.messager.alert('Info', 'No se puede visualizar el protocolo, verificar el código de producto ', 'error');
            return;
        }

        if (lote == '') {
            $.messager.alert('Info', 'No se puede visualizar el protocolo, verificar el número de lote logístico', 'error');
            return;
        }

        if (lote_insp == '') {
            $.messager.alert('Info', 'No se puede visualizar el protocolo, verificar el número de lote de inspección', 'error');
            return;
        }

        if (versionsap == '') {
            $.messager.alert('Info', 'No se puede visualizar el protocolo, verificar el número de versión', 'error');
            return;
        }

        let url_imagen = "controlcalidad/procesos/analisistendencia/generardocumentoprotocolo";
        url_imagen += `?idprotocolo=${encodeURIComponent(idprotocolo)}&producto=${encodeURIComponent(codigo_producto)}&lote=${encodeURIComponent(lote)}&lote_insp=${encodeURIComponent(lote_insp)}&versionsap=${encodeURIComponent(versionsap)}`;


        let html = `
    <div id="win_vista_impresion" title="Vista de Impresión - Lote Insp. ${lote_insp}" class="easyui-layout" style="width:75%;height:80%;">
        <iframe src="${url_imagen}" height="99%" width="100%" id="iframe_imagen">Loading..</iframe>
    </div>`

        if ($(`#win_vista_impresion`)) {
            $(`#win_vista_impresion`).window('close');
            $(`#win_vista_impresion`).empty();
            $(`#win_vista_impresion`).remove();
        }
        $('body').append(html);

        $(`#win_vista_impresion`).window({
            modal: false, collapsible: true, closable: true,
            minimizable: false, maximizable: true, closed: false,
            center: true, resizable: true
        });

    }

    function vista_historial(idprocesocab, lote_insp = '') {
        console.log('idprocesocab', idprocesocab);
        let html = `
        <div id="win_historial_protocolo" title="Historial de Protocolo - Lote Insp. ${lote_insp}" class="easyui-layout" style="width:56%;height:600px;">
        
            <table id="tb_historial_protocolo">
            </table>

            <table id="dg_historial_protocolo"></table>
        </div>
    `;

        if ($('#win_historial_protocolo')) {
            $('#win_historial_protocolo').empty();
            $('#win_historial_protocolo').remove();
        }

        $('body').append(html);

        $('#dg_historial_protocolo').datagrid({
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
                _token: '<?= csrf_token() ?>'
                , _acc: 'listarHistorialProtocolo'
                , idprocesocab: idprocesocab
            },
            toolbar: '#tb_historial_protocolo',
            columns: [[
                    {field: 'usuario', title: 'Usuario', align: 'left', halign: 'center', width: '350px'},
                    {field: 'estado', title: 'Estado', align: 'center', halign: 'center', width: '300px', formatter: function (val, row, index) {
                            if (row.estado_anterior == '') {
                                return `<span style="border-radius:5px 5px 5px 5px;
                        background-color:${row.color_estado_actual};
                        color:white;padding:3px;cursor:pointer;"
                    >
                    ${row.estado_actual}</span>`
                            } else {
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
                    {field: 'texto_historial', title: 'Texto <br/>Historial', align: 'left', halign: 'center', width: '150px'},    
                    {field: 'orden_inst', title: 'Nivel', align: 'center', halign: 'center', width: '150px'},
                    {field: 'indicador_estado', title: 'Actual', align: 'center', halign: 'center', width: '80px', formatter: function (val, row, index) {
                            if (row.indicador_estado == 1) {
                                return ` SI `
                            } else {
                                return ` NO `
                            }

                        }},
                    {field: 'auditoria_reg', title: 'Fecha Registro', align: 'center', halign: 'center', width: '150px', formatter: function (val, row, index) {
                            let fechaProceso = formatter_date_SAP(row.fechaproceso_vista);
                            return `
                    <div>${fechaProceso} ${row.horaproceso}</div>
                `
                        }},
                ]],
            onLoadError: function (XMLHttpRequest, textStatus, errorThrown) {
                $.messager.alert('Error', 'Error al mostrar los datos, vuelva a intentar', 'error');
            },
        }).datagrid('getPager').pagination({
            beforePageText: 'Pag. ',
            afterPageText: 'de {pages}',
            displayMsg: 'Del {from} al {to}, de {total} items.'
        });


        $('#win_historial_protocolo').window({
            modal: true, collapsible: false, closable: true, minimizable: false, maximizable: false, closed: false, center: true, resizable: false
        });
    }


    function consultarProtocolo(url_consulta, idprotocolo, lote_insp) {
        return new Promise(function(resolve, reject) {
            $.ajax(url_consulta, {
                type: 'post',
                beforeSend: function () {
                    $.messager.progress({ text: 'Procesando...' });
                },
                complete: function () {
                    $.messager.progress('close');
                },
                success: function (datos) {
                    if (datos.total == 1) {
                        // Si pasa la validación, resolvemos la promesa
                        if (parseInt(datos.rows[0].instancia_actual) >= parseInt(datos.OrdenInsDeEmp) && datos.rows[0].decision_empleo_vista == '') {
                            $.messager.alert('Error', 'El protocolo de lote de insp: <strong>' + lote_insp + '</strong> no tiene decisión de empleo', 'error');
                            reject('No tiene decisión de empleo');  // Rechazamos la promesa con un mensaje de error
                        } else {
                            // Todo está correcto, resolvemos la promesa
                            resolve(datos);
                        }
                    } else {
                        // Si no se encuentran resultados, rechazamos la promesa
                        reject('No se encontró el protocolo');
                    }
                },
                error: function (x, e) {
                    $.messager.alert('Error ' + x.status, 'Ocurrió un error en el servidor.', 'error');
                    reject('Error en la consulta AJAX');
                },
                data: {
                    _token: '<?= csrf_token() ?>',
                    _acc: 'listarPrincipal',
                    idprotocolo: idprotocolo,
                    lote_insp: lote_insp
                },
                async: true,
                dataType: "json"
            });
        });
    }


    function confirmar_revision(idprotocolo, lote_insp = '', idesp = '') {        
        $('#checkbox_' + idprotocolo).prop("checked", false);
        // Llamada a consultarProtocolo con promesas
        consultarProtocolo(url_consulta, idprotocolo, lote_insp)
            .then(function(datos) {                
                // Si la promesa se resuelve exitosamente, ejecutamos el siguiente código
                // Aquí podemos tomar decisiones basadas en los datos obtenidos
                $.messager.confirm('Confirmar', 'Se procederá a confirmar la revisión del lote de insp: <strong>' + lote_insp + '</strong>, ¿Desea continuar?', function (r) {
                    if (r) {
                        // Realizar la segunda solicitud AJAX para confirmar la revisión
                        $.ajax(url_mantenimiento, {
                            type: 'post',
                            beforeSend: function () {
                                $.messager.progress({ text: 'Procesando...' });
                            },
                            complete: function () {
                                $.messager.progress('close');
                            },
                            success: function (datos) {
                                if (datos.o_nres == 1) {
                                    $.messager.alert('Info', 'Se realizó correctamente la revisión del lote de insp: <strong>' + lote_insp + '</strong>', 'info');
                                    listar_datagrid(); // Actualiza la lista
                                } else {
                                    $.messager.alert('Error', datos.o_msj, 'error');
                                }
                            },
                            error: function (x, e) {
                                $.messager.alert('Error ' + x.status, 'Ocurrió un error en el servidor.', 'error');
                            },
                            data: {
                                _token: '<?= csrf_token() ?>',
                                _acc: 'confirmarRevisor',
                                codigo: JSON.stringify(idprotocolo),
                                lote_insp: lote_insp
                            },
                            async: true,
                            dataType: "json"
                        });
                    }
                });
            })
            .catch(function(error) {
                // Si la promesa se rechaza (por ejemplo, error o no pasa las validaciones)
                
                //$.messager.alert('Error', error, 'error');
            });

        
    }



    function confirmar_revision_bk(idprotocolo, lote_insp = '',idesp='') {
        $('#checkbox_' + idprotocolo).prop("checked", false);
        /*if(idesp==0){

        }*/

        $.ajax(url_consulta, {
            type: 'post',
            beforeSend: function () {
                $.messager.progress({text: 'Procesando...'});
            },
            complete: function () {
                $.messager.progress('close');
            },
            success: function (datos) {                                
                if (datos.total == 1) {               
                    if(parseInt(datos.rows[0].instancia_actual)>=parseInt(datos.OrdenInsDeEmp) && datos.rows[0].decision_empleo_vista==''){
                        $.messager.alert('Error', 'El protocolo de lote de insp: <strong>' + lote_insp + '</strong> no tiene decisión de empleo', 'info');
                        return;
                    }
                                        
                    $.messager.confirm('Confirmar', 'Se procederá a confirmar la revisión del lote de insp: <strong>' + lote_insp + '</strong> , ¿Desea continuar?', function (r) {
                        if (r) {
                            $.ajax(url_mantenimiento, {
                                type: 'post',
                                beforeSend: function () {
                                    $.messager.progress({text: 'Procesando...'});
                                },
                                complete: function () {
                                    $.messager.progress('close');
                                },
                                success: function (datos) {
                                    console.log('datax', datos);
                                    if (datos.o_nres == 1) {
                                        $.messager.alert('Info', 'Se realizó correctamente la revisión del lote de insp: <strong>' + lote_insp + '</strong>', 'info');
                                        listar_datagrid();
                                    } else {
                                        $.messager.alert('Error', datos.o_msj, 'error');
                                    }
                                },
                                error: function (x, e) {
                                    //$.messager.alert('Error '+x.status,'Ocurrió un error en el servidor.','error');
                                    $.messager.alert('Error ' + x.status, 'Ocurrió un error en el servidor.', 'error');
                                },
                                data: {
                                    _token: '<?= csrf_token() ?>'
                                    , _acc: 'confirmarRevisor'
                                    , codigo: JSON.stringify(idprotocolo)
                                    , lote_insp:lote_insp
                                },
                                async: true,
                                dataType: "json"
                            });
                        }
                    });
                }
            },
            error: function (x, e) {
                $.messager.alert('Error ' + x.status, 'Ocurrió un error en el servidor.', 'error');
            },
            data: {
                _token: '<?= csrf_token() ?>'
                , _acc: 'listarPrincipal'
                , idprotocolo: idprotocolo
                , lote_insp:lote_insp
            },
            async: true,
            dataType: "json"
        });            
    }

    function especificacion_tecnica(idprotocolo, codigo_producto, lote_insp = '',idesp='',idversion='') {
        console.log('idprotocolo', idprotocolo,'idesp',idesp,'idversion',idversion);

        let url_imagen = "controlcalidad/procesos/analisistendencia/generardocumentoespecificacion";
        url_imagen += `?protocolo=${encodeURIComponent(idprotocolo)}&producto=${encodeURIComponent(codigo_producto)}&lote_insp=${encodeURIComponent(lote_insp)}&idesp=${encodeURIComponent(idesp)}&idversion=${encodeURIComponent(idversion)}`;


        let html = `
    <div id="win_vista_especificacion_tecnica" title="Vista de Especificación Técnica" class="easyui-layout" style="width:75%;height:80%;">
        <iframe src="${url_imagen}" height="99%" width="100%" id="iframe_imagen">Loading..</iframe>
    </div>`

        if ($(`#win_vista_especificacion_tecnica`)) {
            $(`#win_vista_especificacion_tecnica`).window('close');
            $(`#win_vista_especificacion_tecnica`).empty();
            $(`#win_vista_especificacion_tecnica`).remove();
        }
        $('body').append(html);

        $(`#win_vista_especificacion_tecnica`).window({
            modal: false, collapsible: true, closable: true,
            minimizable: false, maximizable: true, closed: false,
            center: true, resizable: true
        });

    }

    function comparar_documento(idprotocolo, codigo_producto, lote = '', lote_insp = '', versionsap = '',idesp='',versionesp='') {
        if (codigo_producto == '') {
            $.messager.alert('Info', 'No se puede visualizar el protocolo, verificar el código de producto ', 'error');
            return;
        }

        if (lote == '') {
            $.messager.alert('Info', 'No se puede visualizar el protocolo, verificar el número de lote logístico', 'error');
            return;
        }

        if (lote_insp == '') {
            $.messager.alert('Info', 'No se puede visualizar el protocolo, verificar el número de lote de inspección', 'error');
            return;
        }

        if (versionsap == '') {
            $.messager.alert('Info', 'No se puede visualizar el protocolo, verificar el número de versión', 'error');
            return;
        }


        $.ajax(url_consulta, {
            type: 'post',
            beforeSend: function () {
                $.messager.progress({text: 'Procesando...'});
            },
            complete: function () {
                $.messager.progress('close');
            },
            success: function (datos) {                                
                if (datos.total == 1) {
                    console.log('idesp',idesp,'idprotocolo',idprotocolo);
                    let url_archivo_pro = "controlcalidad/procesos/analisistendencia/generardocumentoprotocolo";
                    url_archivo_pro += `?idprotocolo=${encodeURIComponent(idprotocolo)}&idesp=${encodeURIComponent(idesp)}&idversion=${encodeURIComponent(versionesp)}&producto=${encodeURIComponent(codigo_producto)}&lote=${encodeURIComponent(lote)}&lote_insp=${encodeURIComponent(lote_insp)}&versionsap=${encodeURIComponent(versionsap)}`;

                    let url_archivo_esp = "controlcalidad/procesos/analisistendencia/generardocumentoespecificacion";
                    url_archivo_esp += `?comparar=1&idprotocolo=${encodeURIComponent(idprotocolo)}&idesp=${encodeURIComponent(idesp)}&idversion=${encodeURIComponent(versionesp)}&producto=${encodeURIComponent(codigo_producto)}&lote_insp=${encodeURIComponent(lote_insp)}`;

                    let html = `
                        <div id="win_vista_comparar" title="Vista de Comparación - Lote Insp. ${lote_insp}" class="easyui-layout" style="width:75%;height:80%;">
                            <table style="width:100%;height:100%;" >
                                <tr>
                                    <td style="width:50%;">
                                        <iframe src="${url_archivo_esp}" height="99%" width="100%" id="iframe_especificacion">Loading..</iframe>
                                    </td>
                                    <td style="width:50%;">
                                        <iframe src="${url_archivo_pro}" height="99%" width="100%" id="iframe_protocolo">Loading..</iframe>                        
                                    </td>
                                </tr> 
                            </table>        
                        </div>`

                    if ($(`#win_vista_comparar`)) {
                        $(`#win_vista_comparar`).window('close');
                        $(`#win_vista_comparar`).empty();
                        $(`#win_vista_comparar`).remove();
                    }
                    $('body').append(html);

                    $(`#win_vista_comparar`).window({
                        modal: false, collapsible: true, closable: true,
                        minimizable: false, maximizable: true, closed: false,
                        center: true, resizable: true
                    });
                                            
                    listar_datagrid();                                                         
                } else {
                    $.messager.alert('Error', datos.o_msj, 'error');
                }
            },
            error: function (x, e) {
                $.messager.alert('Error ' + x.status, 'Ocurrió un error en el servidor.', 'error');
            },
            data: {
                _token: '<?= csrf_token() ?>'
                , _acc: 'listarPrincipal'
                , idprotocolo: idprotocolo
            },
            async: true,
            dataType: "json"
        });       
    }

    function observar_protocolo(idprotocolo, lote_insp = '') {
        let html = `
            <div id="win_obs_protocolo" title="Registrar Observación - Lote Insp. ${lote_insp}" class="easyui-layout" style="width:600px;height:220px;">
                <div style="padding:3px;">
                    <input class="txt_historial">
                </div>            
                <div style="padding:3px; margin-top: 10px; text-align: right;">
                    <a class="btn_grabar">Grabar</a>
                    <a class="btn_salir">Salir</a>
                </div>
            </div>
        `;

        if ($('#win_obs_protocolo')) {
            $('#win_obs_protocolo').empty();
            $('#win_obs_protocolo').remove();
        }

        $('body').append(html);

        $('#win_obs_protocolo .txt_historial').textbox({
            width: '95%',
            height: 100,
            labelWidth: 100,
            labelAlign: 'left',
            label: 'Observación',
            multiline: true,
        });

        $('#win_obs_protocolo .btn_grabar').linkbutton({
            iconCls: 'icon-save',
            onClick: function () {
                $.messager.confirm('Alerta', 'Se procederá a cambiar el estado del protocolo seleccionado, ¿Desea continuar?', function (r) {
                    if (r) {
                        $.ajax(url_mantenimiento, {
                            type: 'post',
                            beforeSend: function () {
                                $.messager.progress({text: 'Procesando...'});
                            },
                            complete: function () {
                                $.messager.progress('close');
                            },
                            success: function (datos) {
                                if (datos.o_nres == 1) {
                                    $('#win_obs_protocolo').window('close');
                                    $.messager.alert('Info', datos.o_msj, 'info');
                                    listar_datagrid();
                                } else {
                                    $.messager.alert('Error', datos.o_msj, 'error');
                                }
                            },
                            error: function (x, e) {
                                $.messager.alert('Error ' + x.status, 'Ocurrió un error en el servidor.', 'error');
                            },
                            data: {
                                _token: '<?= csrf_token() ?>'
                                , _acc: 'cambiarEstado'
                                , idprotocolo: idprotocolo
                                , lote_insp:lote_insp
                                , txt_historial: $('#win_obs_protocolo .txt_historial').textbox('getValue')
                                , codigo_estado:'D'
                            },
                            async: true,
                            dataType: "json"
                        });
                    }
                });
            }
        });

        $('#win_obs_protocolo .btn_salir').linkbutton({
            iconCls: 'icon-cancel',
            onClick: function () {
                $('#win_obs_protocolo').window('close');
            }
        });

        $('#win_obs_protocolo').window({
            modal: true, collapsible: false, closable: true, minimizable: false, maximizable: false, closed: false, center: true, resizable: false
        });

    }

    function rechazar_protocolo(idprotocolo,lote_insp){
        // Llamada a consultarProtocolo con promesas
        consultarProtocolo(url_consulta, idprotocolo, lote_insp)
            .then(function(datos) { 
                let html = `
                    <div id="win_rechazar_protocolo" title="Registrar Rechazar - Lote Insp. ${lote_insp}" class="easyui-layout" style="width:600px;height:220px;">
                        <div style="padding:3px;">
                            <input class="txt_historial">
                        </div>            
                        <div style="padding:3px; margin-top: 10px; text-align: right;">
                            <a class="btn_grabar">Grabar</a>
                            <a class="btn_salir">Salir</a>
                        </div>
                    </div>
                `;

                if ($('#win_rechazar_protocolo')) {
                    $('#win_rechazar_protocolo').empty();
                    $('#win_rechazar_protocolo').remove();
                }

                $('body').append(html);

                $('#win_rechazar_protocolo .txt_historial').textbox({
                    width: '95%',
                    height: 100,
                    labelWidth: 100,
                    labelAlign: 'left',
                    label: 'Historial:',
                    multiline: true,
                });

                $('#win_rechazar_protocolo .btn_grabar').linkbutton({
                    iconCls: 'icon-save',
                    onClick: function () {
                        $.messager.confirm('Alerta', 'Se procederá a cambiar el estado del protocolo seleccionado, ¿Desea continuar?', function (r) {
                            if (r) {
                                $.ajax(url_mantenimiento, {
                                    type: 'post',
                                    beforeSend: function () {
                                        $.messager.progress({text: 'Procesando...'});
                                    },
                                    complete: function () {
                                        $.messager.progress('close');
                                    },
                                    success: function (datos) {
                                        if (datos.o_nres == 1) {
                                            $('#win_rechazar_protocolo').window('close');
                                            $.messager.alert('Info', datos.o_msj, 'info');
                                            listar_datagrid();
                                        } else {
                                            $.messager.alert('Error', datos.o_msj, 'error');
                                        }
                                    },
                                    error: function (x, e) {
                                        $.messager.alert('Error ' + x.status, 'Ocurrió un error en el servidor.', 'error');
                                    },
                                    data: {
                                        _token: '<?= csrf_token() ?>'
                                        , _acc: 'cambiarEstado'
                                        , idprotocolo: idprotocolo
                                        , lote_insp:lote_insp
                                        , txt_historial: $('#win_rechazar_protocolo .txt_historial').textbox('getValue')
                                        ,codigo_estado:'G'
                                    },
                                    async: true,
                                    dataType: "json"
                                });
                            }
                        });
                    }
                });

                $('#win_rechazar_protocolo .btn_salir').linkbutton({
                    iconCls: 'icon-cancel',
                    onClick: function () {
                        $('#win_rechazar_protocolo').window('close');
                    }
                });

                $('#win_rechazar_protocolo').window({
                    modal: true, collapsible: false, closable: true, minimizable: false, maximizable: false, closed: false, center: true, resizable: false
                });  
            })
            .catch(function(error) {
                // Si la promesa se rechaza (por ejemplo, error o no pasa las validaciones)
      
                //$.messager.alert('Error', error, 'error');
            });          
    }

    function eliminar_protocolo(idprotocolo,lote_insp){
        var Numinst=1;

        $.ajax(url_consulta, {
            type: 'post',
            beforeSend: function () {
                $.messager.progress({text: 'Procesando...'});
            },
            complete: function () {
                $.messager.progress('close');
            },
            success: function (datos) {     
                console.log('datos',datos);                           
                if (datos.total == 1) {       
                    /*if(parseInt(datos.rows[0].instancia_actual)==Numinst && datos.rows[0].decision_empleo_vista!=''){
                        $.messager.alert('Error', 'El protocolo de lote de insp: <strong>' + lote_insp + '</strong> ya tiene decisión de empleo, no se puede eliminar', 'info');
                        return;
                    }*/                    

                    
                    //if(parseInt(datos.rows[0].instancia_actual)==Numinst && datos.rows[0].decision_empleo_vista==''){                                                                     
                        $.messager.confirm('Confirmar', 'Se procederá a eliminar el protocolo con lote de insp: <strong>' + lote_insp + '</strong> , ¿Desea continuar?', function (r) {
                            if (r) {
                                $.ajax(url_mantenimiento, {
                                    type: 'post',
                                    beforeSend: function () {
                                        $.messager.progress({text: 'Procesando...'});
                                    },
                                    complete: function () {
                                        $.messager.progress('close');
                                    },
                                    success: function (datos) {
                                        if (datos.o_nres == 1) {
                                            $.messager.alert('Info', 'Se realizó correctamente la eliminación del lote de insp: <strong>' + lote_insp + '</strong>', 'info');
                                            listar_datagrid();
                                        } else {
                                            $.messager.alert('Error', datos.o_msj, 'error');
                                        }
                                    },
                                    error: function (x, e) {
                                        //$.messager.alert('Error '+x.status,'Ocurrió un error en el servidor.','error');
                                        $.messager.alert('Error ' + x.status, 'Ocurrió un error en el servidor.', 'error');
                                    },
                                    data: {
                                        _token: '<?= csrf_token() ?>'
                                        , _acc: 'eliminarProtocolo'
                                        , codigo: JSON.stringify(idprotocolo)
                                        , lote_insp:lote_insp
                                    },
                                    async: true,
                                    dataType: "json"
                                });
                            }
                        });                      
                    //}
                }
            },
            error: function (x, e) {
                $.messager.alert('Error ' + x.status, 'Ocurrió un error en el servidor.', 'error');
            },
            data: {
                _token: '<?= csrf_token() ?>'
                , _acc: 'listarPrincipal'
                , idprotocolo: idprotocolo
                , lote_insp:lote_insp
            },
            async: true,
            dataType: "json"
        });           
    }
</script>