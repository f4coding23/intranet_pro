@extends('layouts.main')
@section('javascript')

<script type="text/javascript">	
	var myDate = new Date();
    var url_consulta = "{{ url('comercial/procesos/instituciones/consultar') }}";
    var url_mantenimiento = "{{ url('comercial/procesos/instituciones/mantenimiento') }}";
	var firstday_month = myDate.getFullYear().toString()+'-'+(myDate.getMonth() < 9 ? '0' + (myDate.getMonth() + 1).toString() : (myDate.getMonth() + 1).toString())+'-'+'01';
	var hoy = myDate.getFullYear().toString() + '-' + (myDate.getMonth() < 9 ? '0' + (myDate.getMonth() + 1).toString() : (myDate.getMonth() + 1).toString()) + '-' + myDate.getDate().toString();	//var firstday_month = '01'+'/'+(myDate.getMonth() < 9 ? '0' + (myDate.getMonth() + 1).toString() : (myDate.getMonth() + 1).toString())+'/'+myDate.getFullYear().toString();
	var esperada = myDate.setDate(myDate.getDate() + 2);
    // Año anterior
    var yearAgo = new Date(myDate.getFullYear(), myDate.getMonth() - 12, myDate.getDate());
	var yearAnterior = yearAgo.getFullYear().toString() + '-' + (yearAgo.getMonth() < 9 ? '0' + (yearAgo.getMonth() + 1).toString() : (yearAgo.getMonth() + 1).toString()) + '-' + (yearAgo.getDate() < 10 ? '0' + yearAgo.getDate().toString() : yearAgo.getDate().toString());

    // Trimestre anterior
	var threeMonthsAgo = new Date(myDate.getFullYear(), myDate.getMonth() - 3, myDate.getDate());
	var trimestreAnterior = threeMonthsAgo.getFullYear().toString() + '-' + (threeMonthsAgo.getMonth() < 9 ? '0' + (threeMonthsAgo.getMonth() + 1).toString() : (threeMonthsAgo.getMonth() + 1).toString()) + '-' + (threeMonthsAgo.getDate() < 10 ? '0' + threeMonthsAgo.getDate().toString() : threeMonthsAgo.getDate().toString());

    // Mes Anterior
    var oneAnterior = new Date(myDate.getFullYear(), myDate.getMonth() - 1, myDate.getDate());
	var mesAnterior = oneAnterior.getFullYear().toString() + '-' + (oneAnterior.getMonth() < 9 ? '0' + (oneAnterior.getMonth() + 1).toString() : (oneAnterior.getMonth() + 1).toString()) + '-' + (oneAnterior.getDate() < 10 ? '0' + oneAnterior.getDate().toString() : oneAnterior.getDate().toString());

    // Bimestre Anterior
	var twoAnterior = new Date(myDate.getFullYear(), myDate.getMonth() - 2, myDate.getDate());
	var bimestreAnterior = twoAnterior.getFullYear().toString() + '-' + (twoAnterior.getMonth() < 9 ? '0' + (twoAnterior.getMonth() + 1).toString() : (twoAnterior.getMonth() + 1).toString()) + '-' + (twoAnterior.getDate() < 10 ? '0' + twoAnterior.getDate().toString() : twoAnterior.getDate().toString());

    // Trimestre posterior
	var threeMonthsLater = new Date(myDate.getFullYear(), myDate.getMonth() + 3, myDate.getDate());
	var trimestrePosterior = threeMonthsLater.getFullYear().toString() + '-' + (threeMonthsLater.getMonth() < 9 ? '0' + (threeMonthsLater.getMonth() + 1).toString() : (threeMonthsLater.getMonth() + 1).toString()) + '-' + (threeMonthsLater.getDate() < 10 ? '0' + threeMonthsLater.getDate().toString() : threeMonthsLater.getDate().toString());
	
    var config = @json(config('banco'));
	var ambiente = @json($ambiente);
    var usuarioid_controller = @json($usuarioid);
    var nro_proceso_prueba = @json($nro_proceso_prueba);

    sessionStorage.removeItem('productos_seleccionados_prev');
    sessionStorage.removeItem('productos_seleccionados_cartafianza');
    sessionStorage.removeItem('entregas_seleccionados_prev');
    sessionStorage.removeItem('entregas_seleccionados_cartafianza');

	$(document).ready(function (){
		$('#lyaMod').layout();
        $('#lyaMod').layout('collapse', 'west');

		var iconolimpieza = [{
			iconCls:'icon-clear',
			handler: function(e){
				var tipodato = e.data.target.id.split('_');
				switch (tipodato[0]){
					case 'num':
						$(e.data.target).numberbox('clear');
						$(e.data.target).numberbox('textbox').focus();
						break
					case 'txt': 
						$(e.data.target).textbox('clear');
						$(e.data.target).textbox('textbox').focus();
						break
					case 'cmb':
						$(e.data.target).combobox('clear'); 
						$(e.data.target).combobox('textbox').focus();
						break
					case 'dt':
						$(e.data.target).datebox('clear'); 
						$(e.data.target).datebox('textbox').focus();
						break
					case 'cmg':
						$(e.data.target).combogrid('clear')
						$(e.data.target).combogrid('grid').datagrid('options').queryParams.p1='';
						$(e.data.target).combogrid('grid').datagrid('options').queryParams.p2='';
						$(e.data.target).combogrid('grid').datagrid('options').queryParams.p3='';
						$(e.data.target).combogrid('grid').datagrid('reload')
						$(e.data.target).combogrid('textbox').focus();
						break;
				}
				listar_datagrid_bases_cab();
			}
		}];
		// FILTROS ------------------------------------------------------------------------------------------------------------------
        $('#txt_documento').numberbox({
			icons 		: iconolimpieza,
            width       : '12%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'Proceso',
            prompt 		: 'N° 41', //
            value       : nro_proceso_prueba.toString() 
        });

        $('#txt_denominacion').textbox({
			icons 		: iconolimpieza,
            width       : '12%',
			height 		: 20,
            //labelWidth  : 120,
            //labelAlign  : 'right',
			//label 		: 'Proceso',
            prompt 		: 'Proceso',
		});

        $('#cmb_org_ventas').tagbox({
			icons 		: iconolimpieza,
            width       : '24%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'Org. Ventas',
            prompt 		: '[Seleccionar]',
            url 		: url_consulta,
			limitToList : true,
			hasDownArrow: true,
			multiple:true,
            onBeforeLoad: function(param) {
                param._token = '{{ csrf_token() }}';
                param._acc = 'Combo';
				param.opcion = 'CMB_ORG_VENTAS';
            },
            tagFormatter: function(tag, row) {
                return '<span class="tagbox-item" title="' + row.org_ventas + '">' + row.org_ventas + '</span>';
            },
            panelHeight : 'auto',
			panelMaxHeight:200,
            valueField 	: 'codigo',
            textField 	: 'org_ventas',
		});

        /* $('#cmb_canal_dist').tagbox({
			icons 		: iconolimpieza,
            width       : '24%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'Canal Dist.',
            prompt 		: '[Seleccionar]',
            url 		: url_consulta,
			limitToList : true,
			hasDownArrow: true,
			multiple:true,
            onBeforeLoad: function(param) {
                param._token = '{{ csrf_token() }}';
                param._acc = 'Combo';
				param.opcion = 'CMB_CANAL_DIST';
            },
            tagFormatter: function(tag, row) {
                return '<span class="tagbox-item" title="' + row.canal_dist + '">' + row.canal_dist + '</span>';
            },
            panelHeight : 'auto',
			panelMaxHeight:200,
            valueField 	: 'codigo',
            textField 	: 'canal_dist',
		}); */

        
        $('#cmb_motivo_pedido').tagbox({
			icons 		: iconolimpieza,
            width       : '19%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'Mot. Proceso',
            prompt 		: '[Seleccionar]',
            url 		: url_consulta,
			limitToList : true,
			hasDownArrow: true,
			multiple:true,
            onBeforeLoad: function(param) {
                param._token = '{{ csrf_token() }}';
                param._acc = 'Combo';
				param.opcion = 'CMB_MOTIVO_PEDIDO';
            },
            tagFormatter: function(tag, row) {
                return '<span class="tagbox-item" title="' + row.motivo_pedido + '">' + row.motivo_pedido + '</span>';
            },
            panelHeight : 'auto',
			panelMaxHeight:200,
            valueField 	: 'codigo',
            textField 	: 'motivo_pedido',
		});

        $('#ck_8uit').switchbutton({
            width: '3%',
            height: 20,
            labelWidth  : 50,
            labelAlign  : 'left',
            label 		: '<b>>8UIT</b>',
            onText: 'SI',
            offText: 'NO',
            checked : false,
            onChange(newValue,oldValue){
                listar_datagrid_bases_cab();
            }
        });

        $('#dt_fecha_desde').datebox({
			//icons 		: iconolimpieza,
			width		: '250px',
			height 		: 25,
			labelWidth	: 120,
			label		:'Fecha Proceso',
			labelAlign	:'right',
			parser:new_parser_date,
			formatter:new_formatter_date,
			height:20,
			prompt:'desde',
			editable: false,
            //value: trimestreAnterior,
            //value: mesAnterior,//trimestreAnterior,
            //value: '2023-08-07',
            /* onChange(newValue,oldValue){
                listar_datagrid_bases_cab();
            }, */
			onShowPanel:function(){
				var opts = $(this).datebox('options');
				var fechaActual = new Date();
				fechaActual.setFullYear(fechaActual.getFullYear() - 10);
				var fechaMaxima = new Date();
				fechaMaxima.setFullYear(fechaMaxima.getFullYear() + 2);
				$(this).datebox('calendar').calendar({
					validator: function(date){
						var max = opts.parser(opts.max);
						if (date >= fechaActual && date <= fechaMaxima) {
							return true;
						} else {
							return false;
						}
					}
				});
			}
		});

		$('#dt_fecha_fin').datebox({
			width		: '130px',
			height 		: 25,
			labelAlign	:'right',
			parser:new_parser_date,
			formatter:new_formatter_date,
			height:20,
			prompt:'hasta',	
			editable: false,	
            value: hoy,
			//value: '2023-08-07',
			onShowPanel:function(){
				var opts = $(this).datebox('options');
				var fechaActual = new Date();
				fechaActual.setFullYear(fechaActual.getFullYear() - 5);
				var fechaMaxima = new Date();
				fechaMaxima.setFullYear(fechaMaxima.getFullYear());
				$(this).datebox('calendar').calendar({
					validator: function(date){
						var max = opts.parser(opts.max);
						if (date >= fechaActual && date <= fechaMaxima) {
							return true;
						} else {
							return false;
						}
					}
				});
			}
		});

        $('#cmb_grupo_cliente').tagbox({
			icons 		: iconolimpieza,
            width       : '14%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'G. Cliente',
            prompt 		: '[Seleccionar]',
            url 		: url_consulta,
			limitToList : true,
			hasDownArrow: true,
			multiple:true,
            onBeforeLoad: function(param) {
                param._token = '{{ csrf_token() }}';
                param._acc = 'Combo';
				param.opcion = 'CMB_GRUPO_CLIENTE';
            },
            tagFormatter: function(tag, row) {
                return '<span class="tagbox-item" title="' + row.grupo_cliente + '">' + row.grupo_cliente + '</span>';
            },
            panelHeight : 'auto',
			panelMaxHeight:200,
            valueField 	: 'codigo',
            textField 	: 'grupo_cliente',
		});

        $('#cmb_region').tagbox({
			icons 		: iconolimpieza,
            width       : '10%',
			height 		: 20,
            labelWidth  : 60,
            labelAlign  : 'right',
			//label 		: 'Región',
            prompt 		: '[Región]',
            url 		: url_consulta,
			limitToList : true,
			hasDownArrow: true,
			multiple:true,
            onBeforeLoad: function(param) {
                param._token = '{{ csrf_token() }}';
                param._acc = 'Combo';
				param.opcion = 'CMB_REGION';
            },
            tagFormatter: function(tag, row) {
                return '<span class="tagbox-item" title="' + row.region + '">' + row.region + '</span>';
            },
            panelHeight : 'auto',
			panelMaxHeight:200,
            valueField 	: 'codigo',
            textField 	: 'region',
		});

                
        $('#cmb_grupo_articulos').tagbox({
			icons 		: iconolimpieza,
            width       : '24%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'Grupo Art.',
            prompt 		: '[Seleccionar]',
            url 		: url_consulta,
			limitToList : true,
			hasDownArrow: true,
			multiple:true,
            onBeforeLoad: function(param) {
                param._token = '{{ csrf_token() }}';
                param._acc = 'Combo';
				param.opcion = 'CMB_GRUPO_ART';
            },
            tagFormatter: function(tag, row) {
                return '<span class="tagbox-item" title="' + row.grupo_art + '">' + row.grupo_art + '</span>';
            },
            panelHeight : 'auto',
			panelMaxHeight:200,
            valueField 	: 'codigo',
            textField 	: 'grupo_art',
		});
        
        $('#cmb_motivo_rechazo').tagbox({
			icons 		: iconolimpieza,
            width       : '24%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'Mot. Rechazo',
            prompt 		: '[Seleccionar]',
            url 		: url_consulta,
			limitToList : true,
			hasDownArrow: true,
			multiple:true,
            onBeforeLoad: function(param) {
                param._token = '{{ csrf_token() }}';
                param._acc = 'Combo';
				param.opcion = 'CMB_MOTIVO_RECHAZO';
            },
            tagFormatter: function(tag, row) {
                return '<span class="tagbox-item" title="' + row.motivo_rechazo + '">' + row.motivo_rechazo + '</span>';
            },
            panelHeight : 'auto',
			panelMaxHeight:200,
            valueField 	: 'codigo',
            textField 	: 'motivo_rechazo',
		});

        $('#txt_sctf').textbox({
			icons 		: iconolimpieza,
            width       : '12%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'Carta Fianza',
            prompt 		: 'C. Solicitud',
			//value 		: '20152_EJM' // COMENTAR
		});

        $('#txt_ctf').textbox({
			icons 		: iconolimpieza,
            width       : '12%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			//label 		: 'Carta Fianza',
            prompt 		: 'N° Carta Fianza',
			//value 		: '20152_EJM' // COMENTAR
		});

        $('#cmb_cliente').tagbox({
			icons 		: iconolimpieza,
            width       : '24%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'Cliente',
            prompt 		: '[Seleccionar]',
            url 		: url_consulta,
			limitToList : true,
			hasDownArrow: true,
			multiple:true,
            onBeforeLoad: function(param) {
                param._token = '{{ csrf_token() }}';
                param._acc = 'Combo';
				param.opcion = 'CMB_CLIENTE';
            },
            tagFormatter: function(tag, row) {
                return '<span class="tagbox-item" title="' + row.cliente_v + '">' + row.cliente_v + '</span>';
            },
            panelHeight : 'auto',
			panelMaxHeight:200,
            valueField 	: 'codigo',
            textField 	: 'cliente',
            //value: '1200001708'
		});
        
        $('#cmb_producto').tagbox({
			icons 		: iconolimpieza,
            width       : '24%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'Producto',
            prompt 		: '[Seleccionar]',
            url 		: url_consulta,
			limitToList : true,
			hasDownArrow: true,
			multiple:true,
            onBeforeLoad: function(param) {
                param._token = '{{ csrf_token() }}';
                param._acc = 'Combo';
				param.opcion = 'CMB_PRODUCTO';
            },
            tagFormatter: function(tag, row) {
                return '<span class="tagbox-item" title="' + row.producto + '">' + row.producto + '</span>';
            },
            panelHeight : 'auto',
			panelMaxHeight:200,
            valueField 	: 'codigo',
            textField 	: 'producto',
		});

        $('#cmb_tipo_venta').combobox({
            width       : '24%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'Tipo Venta',
            prompt 		: '[Seleccionar]',
			limitToList : true,
			hasDownArrow: true,
            editable: false,
			multiple:false,
            data: [                
                { value: 1, text: 'Productos AC FARMA' },
                { value: 2, text: 'Productos Sagitario' },
                { value: 3, text: 'Productos Combinados' },
                { value: 4, text: 'TODOS'},
            ],
            onChange(newValue,oldValue){
                listar_datagrid_bases_cab();
            },
            panelHeight : 'auto',
            panelMaxHeight:200,
            value: 4,
        });

        /* ENTREGA */
        $('#txt_entrega').textbox({
			icons 		: iconolimpieza,
            width       : '24%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'Entrega(11)',
            prompt 		: 'Código/Descripción de la Entrega',
		});

        $('#txt_contrato').textbox({
			icons 		: iconolimpieza,
            width       : '24%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'Contrato',
            prompt 		: 'Contrato',
		});

        $('#cmb_dst_mercancia').tagbox({
			icons 		: iconolimpieza,
            width       : '24%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'Dest. Mercancía',
            prompt 		: '[Seleccionar]',
            url 		: url_consulta,
			limitToList : true,
			hasDownArrow: true,
			multiple:true,
            onBeforeLoad: function(param) {
                param._token = '{{ csrf_token() }}';
                param._acc = 'Combo';
				param.opcion = 'CMB_CLIENTE';
            },
            tagFormatter: function(tag, row) {
                return '<span class="tagbox-item" title="' + row.cliente_v + '">' + row.cliente_v + '</span>';
            },
            panelHeight : 'auto',
			panelMaxHeight:200,
            valueField 	: 'codigo',
            textField 	: 'cliente',
		});

        /* PEDIDO */
        $('#txt_pedido').textbox({
			icons 		: iconolimpieza,
            width       : '24%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'Pedido',
            prompt 		: 'Pedido(15)',
		});

        $('#txt_gr').textbox({
			icons 		: iconolimpieza,
            width       : '24%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'G. Remisión',
            prompt 		: 'Guía de Remisión',
		});

        $('#txt_factura').textbox({
			icons 		: iconolimpieza,
            width       : '24%',
			height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
			label 		: 'Factura',
            prompt 		: 'Factura',
		});

        /* INDICADORES */
        $('#cmb_presenta_ctf').combobox({
            width       : '24%',
            height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
            label 		: 'Fianza',
            prompt 		: '[Seleccionar]',
            limitToList : true,
            hasDownArrow: true,
            multiple:false,
            editable: false,
            data: [                
                { value: 1, text: 'SI' },
                { value: 2, text: 'NO' },
                { value: 3, text: 'TODOS' },
            ],
            onChange(newValue,oldValue){
                listar_datagrid_bases_cab();
            },
            panelHeight : 'auto',
            panelMaxHeight:200,
            value: 3,
        });
        
        $('#cmb_presenta_cnt').combobox({
            width       : '24%',
            height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
            label 		: 'Contrato',
            prompt 		: '[Seleccionar]',
            limitToList : true,
            hasDownArrow: true,
            multiple:false,
            editable: false,
            data: [                
                { value: 1, text: 'SI' },
                { value: 2, text: 'NO' },
                { value: 3, text: 'TODOS' },
            ],
            onChange(newValue,oldValue){
                listar_datagrid_bases_cab();
            },
            panelHeight : 'auto',
            panelMaxHeight:200,
            value: 3,
        });

        $('#cmb_presenta_pedido').combobox({
            icons 		: [{
                iconCls:'icon-clear',
                handler:function(e){
                    $(e.data.target).combobox('clear');
                }
            }],
            width       : '24%',
            height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
            label 		: 'Pedido',
            prompt 		: '[Seleccionar]',
            limitToList : true,
            hasDownArrow: true,
            multiple:false,
            editable: false,
            data: [                
                { value: 1, text: 'MENOR A 1 UIT' },
                { value: 2, text: 'ENTRE 1 A 4 UIT' },
                { value: 3, text: 'ENTRE 4 A 8 UIT' },
                { value: 4, text: 'MAYOR A 8 UIT' },
            ],
            onChange(newValue,oldValue){
                listar_datagrid_bases_cab();
            },
            panelHeight : 'auto',
            panelMaxHeight:200,
        });
        
        $('#cmb_presenta_facturacion').combobox({
            icons 		: [{
                iconCls:'icon-clear',
                handler:function(e){
                    $(e.data.target).combobox('clear');
                }
            }],
            width       : '24%',
            height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
            label 		: 'Facturación',
            prompt 		: '[Seleccionar]',
            limitToList : true,
            hasDownArrow: true,
            multiple:false,
            editable: false,
            data: [                
                { value: 1, text: 'MENOR A 1 UIT' },
                { value: 2, text: 'ENTRE 1 A 4 UIT' },
                { value: 3, text: 'ENTRE 4 A 8 UIT' },
                { value: 4, text: 'MAYOR A 8 UIT' },
            ],
            onChange(newValue,oldValue){
                listar_datagrid_bases_cab();
            },
            panelHeight : 'auto',
            panelMaxHeight:200,
        });

        $('#cmb_deuda_pendiente').combobox({
            icons 		: [{
                iconCls:'icon-clear',
                handler:function(e){
                    $(e.data.target).combobox('clear');
                }
            }],
            width       : '24%',
            height 		: 20,
            labelWidth  : 120,
            labelAlign  : 'right',
            label 		: 'Deuda',
            prompt 		: '[Seleccionar]',
            limitToList : true,
            hasDownArrow: true,
            multiple: false,
            editable: false,
            data: [                
                { value: 1, text: 'MENOR A 1 UIT' },
                { value: 2, text: 'ENTRE 1 A 4 UIT' },
                { value: 3, text: 'ENTRE 4 A 8 UIT' },
                { value: 4, text: 'MAYOR A 8 UIT' },
            ],
            onChange(newValue,oldValue){
                listar_datagrid_bases_cab();
            },
            panelHeight : 'auto',
            panelMaxHeight:200,
        });


		$('.txt_enter').each(function(i,e){
			$(e).textbox('textbox').bind('keydown',function(e){
				if(e.keyCode ==13){
					listar_datagrid_bases_cab();
				}
			});
		})

        $('#mb_finanzas').menubutton({
			//iconCls: 'icon-excel2',
			menu: '#mm_finanzas'
		});
        
        $('#mb_comercial').menubutton({
			//iconCls: 'icon-excel2',
			menu: '#mm_comercial'
		});
        
        $('#mb_descargar').menubutton({
			//iconCls: 'icon-excel2',
			menu: '#mm_descargar'
		});

        // GRILLA ----------------------------------------------------------------------------------------------------------------------------------
        $('#dg_proceso_cab').datagrid({ 
		    url:url_consulta,
		    fitColumns:false,
	        singleSelect:true,
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
				_token          : '{{ csrf_token() }}'
                ,_acc			: 'listarPrincipalProcesoCab'
                ,...getFiltroBusqueda()
			},
			toolbar: '#tb_dg_proceso_cab',
            columns:[[
                {field:'T1',colspan:7,title:'Datos del Proceso',align:'center',halign:'center'},
                {field:'T2',colspan:3,title:'Datos del Cliente',align:'center',halign:'center'},
                {field:'T3',colspan:4,title:'Opciones',align:'center',halign:'center'},
			],[
                // BASES
                {field:'org_ventas',title:'Org. Ventas',align:'center',halign:'center',width:'80px'},
                {field:'documento',title:'N° SAP',align:'center',halign:'center',width:'100px',formatter:function(val,row,index){
					if(row.flg_adenda == 1){
                        return `
                            <div title="Traer el Proceso Regular" style="cursor:pointer;text-decoration:underline;color:#DC00FF;text-weight:bold;" onclick="filtrar_adendas(${row.documento},${row.documento_modelo})">${row.documento}</div>
                        `
                    }else{
                        return `
                            <div>${row.documento}</div>
                        `
                    }					
				}},
                {field:'proceso',title:'Proceso',align:'left',halign:'center',width:'350px',formatter:function(val,row,index){
                    if(row.flg_adenda == 1){
                        return `
                            <div title="Traer el Proceso Regular" style="cursor:pointer;text-decoration:underline;color:#DC00FF;text-weight:bold;" onclick="filtrar_adendas(${row.documento},${row.documento_modelo})">${row.proceso}</div>
                        `
                    }else{
                        return `
                            <div>${row.proceso}</div>
                        `
                    }		
				}},
                {field:'motivo_pedido',title:'Motivo<br>Proceso',align:'left',halign:'center',width:'300px'},
                {field:'tipo_proceso',title:'Tipo Venta',align:'center',halign:'center',width:'175px',formatter:function(val,row,index){
					// Definimos la cadena original
                    let cadena = row.operador_alias;
                    let partes = cadena.split("_");
                    let parteDerecha = partes[1];

                    let tipo_cadena = '';
                    if(parteDerecha == 'VDS'){
                        tipo_cadena = 'Productos Sagitario';
                    }else if(parteDerecha == 'XXX' || parteDerecha == 'ACF'){
                        tipo_cadena = 'Productos AC FARMA';
                    }else if(parteDerecha == 'COMBINADO'){
                        tipo_cadena = 'Productos Combinados';
                    }
                    
                    return `
                        <div>${tipo_cadena}</div>
                    `
				}},
                {field:'fecha_documento',title:'Fecha<br>Documento',align:'center',halign:'center',width:'100px',formatter:function(val,row,index){
					let fechaDoc = formatter_date_SAP(row.fecha_documento);
					return `
						<div>${fechaDoc}</div>
					`
				}},
                {field:'importe_moneda',title:'Importe<br>Adjudicado',align:'center',halign:'center',width:'150px'},
                // CLIENTE
				{field:'cliente',title:'Cliente',align:'left',halign:'center',width:'350px'},
				{field:'grupo_cliente',title:'Grupo<br>Cliente',align:'center',halign:'center',width:'150px'},
                {field:'region',title:'Región',align:'center',halign:'center',width:'100px'},
                // OPCIONES
				{field:'entregas',title:'Control<br>Entregas',align:'center',halign:'center',width:110,formatter:function(value,row,index){
                    return `<img src="${ambiente}img/icons/analysis.png" title="Nivel de Cumplimiento" onclick="control_entregas_proceso(${row.idprocesocab},'${row.documento}','${row.proceso}','',0,'${row.operador_alias}')" title="Ver Control Entregas por Prozceso" style="cursor:pointer;width:16px;height:16px">`
				}},
                {field:'carta_fianza',title:'Solicitud Carta<br>Fianza',align:'center',halign:'center',width:100,formatter:function(value,row,index){
                    let bck_ctf = '';
                    if(row.flg_solicitud_ctf == 0){
                        bck_ctf = 'red';
                    }else{
                        bck_ctf = 'green';
                    }
                    return `<div style="display: flex; justify-content: center; align-items: center;">
                        <div style="background-color:${bck_ctf}; border-radius: 50%; padding: 6px 8px; display: flex; justify-content: center; align-items: center;">
                            <img src="${ambiente}img/icons/email_open.png" onclick="carta_fianza(${row.idprocesocab},'${row.documento}','${row.proceso}','${row.motivo_pedido}','${row.cliente_v}','${row.codigo_cliente}')" title="Seguimiento de Cartas Fianza" style="cursor:pointer;width:16px;height:16px;">
                        </div>
                    </div>`;
                    //return `<img src="${ambiente}img/icons/email_open.png"  onclick="wizard_carta_fianza(${row.idprocesocab},'${row.documento}','${row.proceso}','${row.motivo_pedido}','${row.cliente_v}','${row.codigo_cliente}','${row.grupo_cliente}')" title="Seguimiento de Cartas Fianza" style="cursor:pointer;width:16px;height:16px">`
				}},
                {field:'contratos',title:'Contratos',align:'center',halign:'center',width:100,formatter:function(value,row,index){
                    /* let bck_cnt = '';
                    if(row.flg_contrato == 0){
                        bck_cnt = 'red';
                    }else{
                        bck_cnt = 'green';
                    }
                    return `<div style="display: flex; justify-content: center; align-items: center;">
                        <div style="background-color:${bck_cnt}; border-radius: 50%; padding: 6px 8px; display: flex; justify-content: center; align-items: center;">
                            <img src="${ambiente}img/icons/archivos.png" onclick="contratos(${row.idprocesocab},'${row.documento}','${row.proceso}','${row.motivo_pedido}','${row.cliente_v}','${row.codigo_cliente}')" title="Seguimiento de Contratos" style="cursor:pointer;width:16px;height:16px;">
                        </div>
                    </div>`; */
                    return `<img src="${ambiente}img/icons/archivos.png"  onclick="contratos(${row.idprocesocab},'${row.documento}','${row.proceso}','${row.motivo_pedido}','${row.cliente_v}','${row.codigo_cliente}')" title="Seguimiento de Contratos" style="cursor:pointer;width:16px;height:16px">`
				}},
                {field:'facturacion',title:'Facturación',align:'center',halign:'center',width:100,formatter:function(value,row,index){
                    let bck_deuda = '';
                    if(row.importe_deuda > 0){
                        bck_deuda = 'red';
                    }else{
                        bck_deuda = 'green';
                    }
                    return `<img src="${ambiente}img/icons/money_dollar.png"  onclick="ver_facturacion(${row.idprocesocab})" title="Ver Facturación del Proceso" style="cursor:pointer;width:16px;height:16px;background-color:${bck_deuda}";>`
				}},
            ]],
            detailFormatter : function(index, row) {
                return '<div style="padding:2px"><table id="grid_proceso_det_' + index + '"></table></div>';
                //return '<div style="padding:2px;position:relative;overflow:auto;"><table id="grid_proceso_det_' + index + '" class="ddv"></table></div>';

            },
            onExpandRow : function(index, row) { // PROCESO DET
                var dg_proceso_cab = $("#dg_proceso_cab");
                $('#dg_proceso_cab').datagrid('selectRow',index);
                var ddv_proceso_det = $('#grid_proceso_det_' + index);
                var indexpadre = index;
                ddv_proceso_det.datagrid({
                    url:url_consulta,
                    fitColumns:true,
                    singleSelect:true,
                    width: 'auto',
                    height : 'auto',
                    //view: detailview, // Carga detalle procesos
                    loadMsg: 'Cargando por favor espere...',
                    queryParams: {
                        _token              : '{{ csrf_token() }}'
                        ,_acc			    : 'listarPrincipalProcesoDet'
                        // PROCESO
                        ,idprocesocab       : row.idprocesocab 
                        ,codigo_producto    : $('#cmb_producto').combobox('getValues').join(',')
                        // ENTREGA
                        ,txt_entrega        : $('#txt_entrega').textbox('getValue')
                        ,codigo_dst_mercancia : $('#cmb_dst_mercancia').combobox('getValues').join(',')
                        ,txt_contrato       : $('#txt_contrato').textbox('getValue')
                        // PEDIDO
                        ,txt_pedido      	: $('#txt_pedido').textbox('getValue')
                        ,txt_gr      		: $('#txt_gr').textbox('getValue')
                        ,txt_factura      	: $('#txt_factura').textbox('getValue')
                    },
                    columns:[[
                        {field:'entregas',title:'Seguimiento<br>Entregas',align:'center',halign:'center',width:110,formatter:function(value,row,index){
                            return `<img src="${ambiente}img/icons/cargo-truck.png"  onclick="control_entregas_proceso(${row.idprocesocab},'${row.documento}','${row.proceso}','',0,'','',${row.idmaeproducto})" title="Ver Control Entregas por Producto" style="cursor:pointer;width:16px;height:16px">`
                        }},
                        {field:'fila',title:'Item',align:'center',halign:'center',width:'80px'},
                        {field:'producto',title:'Producto',align:'left',halign:'center',width:'550px'},
                        {field:'grupo_articulo',title:'Grupo<br>Artículo',align:'left',halign:'center',width:'250px'},
                        {field:'molecula',title:'Molécula',align:'center',halign:'center',width:'200px'},
                        {field:'rechazo',title:'Rechazo',align:'center',halign:'center',width:'130px',formatter:function(val,row,index){
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
                        {field:'motivo_rechazo',title:'Motivo<br>Rechazo',align:'center',halign:'center',width:'300px'},
                        {field:'cantidad_um_venta',title:'Cantidad<br>Venta',align:'center',halign:'center',width:'150px'},
                        {field:'cantidad_um_base',title:'Cantidad<br>Presentación',align:'center',halign:'center',width:'150px'},
                        {field:'importe_moneda',title:'Importe<br>Adjudicado',align:'center',halign:'center',width:'170px'},
                    ]],
                    onClickRow: function (index, row) {
                        $(this).datagrid('uncheckRow', index);
                    },
                    onResize : function() {
                        $('#dg_proceso_cab').datagrid('fixDetailRowHeight',indexpadre);
                    },
                    onLoadSuccess : function() {
                        setTimeout( function() { $('#dg_proceso_cab').datagrid('fixDetailRowHeight',indexpadre); }, 0);
                    },
                    // PROCESO DET
                    detailFormatter : function(index, row) {
                        return '<div style="padding:2px"><table id="grid_entrega_det_' + index + '"></table></div>';
                    },
                });

                $('#dg_proceso_cab').datagrid('fixDetailRowHeight',index);

            },
            onClickRow: function (index, row) {
                $(this).datagrid('uncheckRow', index);
            },
			onLoadError: function(XMLHttpRequest, textStatus, errorThrown){
				$.messager.alert('Error','Error al mostrar los datos, vuelva a intentar','error');
			}		
		}).datagrid('getPager').pagination({
	        beforePageText: 'Pag. ',
	        afterPageText: 'de {pages}',
	        displayMsg: 'Del {from} al {to}, de {total} items.'
		});

        colorear_dg();
        adjustDatagridSize();

    });            
// ------------------------------------------------------------------------------------------------------------------------------ //
    
</script>

<?php include_once(resource_path('views/modulo/comercial/js_metodos_comercial.php')); ?> <!-- CLASES DEL MODULO --> 
<?php include_once(resource_path('views/modulo/comercial/procesos/instituciones/js_metodos.php')); ?> <!-- METODOS GENERALES -->
<?php include_once(resource_path('views/modulo/comercial/procesos/instituciones/js_metodos_solicitud.php')); ?> <!-- METODOS SCTF -->
<?php include_once(resource_path('views/modulo/comercial/procesos/instituciones/js_metodos_final.php')); ?> <!-- METODOS CTF -->
<?php include_once(resource_path('views/modulo/comercial/procesos/instituciones/js_metodos_contratos.php')); ?> <!-- METODOS CNT -->

@endsection

@section('content')

<table id="dg_proceso_cab"></table>

<div id="tb_dg_proceso_cab">	
    <!-- PROCESO -->
    <div style="width:100%;padding:3px;">
        <div id="filtro_proceso_title" onclick="toggleFiltros(0,'filtro_proceso')" style="cursor: pointer;">
            <b>FILTROS DEL PROCESO (41)</b>
            <span id="arrow_icon">&#9660;</span> <!-- Flecha hacia abajo por defecto -->
        </div>
    </div>
    <div id="filtro_proceso">
        <div style="width:100%;padding:3px;">
            <input id="txt_documento" class="txt_enter">     
            <input id="txt_denominacion" class="txt_enter">      
            <input id="cmb_org_ventas" type="text">    
            <!--<input id="cmb_canal_dist" type="text">-->
            <input id="cmb_motivo_rechazo" type="text">          
            <input id="cmb_motivo_pedido" type="text">      
            <input id="ck_8uit" type="text">               
        </div>
        <div style="width:100%;padding:3px;">
            <input id="cmb_grupo_cliente">           
            <input id="cmb_region" type="text">           
            <input id="cmb_grupo_articulos" type="text">
            <input id="txt_sctf" class="txt_enter">      
            <input id="txt_ctf" class="txt_enter">    
            <input id="dt_fecha_desde">
            <input id="dt_fecha_fin">  
        </div>
        <div style="width:100%;padding:3px;">
            <input id="cmb_cliente" type="text">      
            <input id="cmb_producto" type="text">    
            <input id="cmb_tipo_venta" type="text">    
        </div>
    </div>
    <!-- ENTREGA -->
    <div style="width:100%;padding:3px;">
        <div id="filtro_entrega_title" onclick="toggleFiltrosEntrega(0,'filtro_entrega')" style="cursor: pointer;">
            <b>FILTROS DE LA ENTREGA (11)</b>
            <span id="arrow_icon">&#9660;</span> <!-- Flecha hacia abajo por defecto -->
        </div>
    </div>
    <div id="filtro_entrega">
        <div style="width:100%;padding:3px;">
            <input id="txt_entrega" class="txt_enter">
            <input id="txt_contrato" class="txt_enter">
            <input id="cmb_dst_mercancia" class="txt_enter">           
        </div>
    </div>
    <!-- PEDIDO -->
    <div style="width:100%;padding:3px;">
        <div id="filtro_pedido_title" onclick="toggleFiltrosPedido(0,'filtro_pedido')" style="cursor: pointer;">
            <b>FILTROS DEL PEDIDO (15)</b>
            <span id="arrow_icon">&#9660;</span> <!-- Flecha hacia abajo por defecto -->
        </div>
    </div>
    <div id="filtro_pedido">
        <div style="width:100%;padding:3px;">
            <input id="txt_pedido" class="txt_enter">
            <input id="txt_gr" class="txt_enter">
            <input id="txt_factura" class="txt_enter">           
        </div>
    </div>
    <!-- INDICADORES --> 
    <div style="width:100%;padding:3px;">
        <div id="filtro_indicadores_title" onclick="toggleFiltrosIndicadores(0,'filtro_indicadores')" style="cursor: pointer;">
            <b>INDICADORES</b>
            <span id="arrow_icon">&#9660;</span> <!-- Flecha hacia abajo por defecto -->
        </div>
    </div>
    <div id="filtro_indicadores">
        <div style="width:100%;padding:3px;">
            <input id="cmb_presenta_ctf">
            <input id="cmb_presenta_cnt">
            <input id="cmb_presenta_pedido">
            <input id="cmb_deuda_pendiente">           
        </div>
        <div style="width:100%;padding:3px;">
            <input id="cmb_presenta_facturacion">
        </div>
    </div>
    <!-- BOTONERA -->
	<div style="display: flex; align-items: center; padding: 10px 0;">
        <div style="flex: 1;">
            <a href="javascript:void(0);" id="btn_expandir" class="easyui-linkbutton" data-options="iconCls:'icon-arrow_in'" onclick="expandir_filtros()">Expandir Filtros</a>
            <a href="javascript:void(0);" id="btn_contraer" class="easyui-linkbutton" data-options="iconCls:'icon-arrow_out'" onclick="contraer_filtros()">Contraer Filtros</a>
        </div>
        <div style="flex: 1; text-align: center;">
            <a href="javascript:void(0);" id="btn_actualizar" class="easyui-linkbutton" data-options="iconCls:'icon-search'" onclick="listar_datagrid_bases_cab()">Consultar</a>
            <!-- FINANZAS : 1 -->
            <a href="javascript:void(0);" id="mb_finanzas" class="menu-button" data-options="iconCls:'icon-coins'" onclick="$('#mm_finanzas').menu('show')">Finanzas</a>
            <div id="mm_finanzas" style="width:160px;height:165px;">
                <div data-options="iconCls:'icon-book-next'" onclick="maestro_solicitudes(1)">Sol. Cartas Fianzas</div>
                <div class="menu-sep"></div>
                <div data-options="iconCls:'icon-carta'" onclick="maestro_fianzas(1)">Cartas Fianzas</div>
                <div class="menu-sep"></div>
                <div data-options="iconCls:'icon-calendario_cf'" onclick="cronograma_vct_ctf()">Cronograma Vcto.</div>
                <div class="menu-sep"></div>
                <div data-options="iconCls:'icon-grafico'" onclick="ctf_general('','',1)">Evaluación Bancaria</div>
            </div>
            <!-- COMERCIAL: 2 --> 
            <a href="javascript:void(0);" id="mb_comercial" class="menu-button" data-options="iconCls:'icon-maleta'" onclick="$('#mm_comercial').menu('show')">Comercial</a>
            <div id="mm_comercial" style="width:160px;height:160px;">
                <div data-options="iconCls:'icon-book-next'" onclick="maestro_solicitudes(0)">Sol. Cartas Fianzas</div>
                <div class="menu-sep"></div>
                <div data-options="iconCls:'icon-carta'" onclick="maestro_fianzas(0)">Cartas Fianzas</div>
                <div class="menu-sep"></div>
                <div data-options="iconCls:'icon-archivos-cnt'" onclick="maestro_contratos()">Contratos</div>
                <div class="menu-sep"></div>
                <div data-options="iconCls:'icon-search'" onclick="ordenes_de_compra()">Orden de Compra</div>
            </div>
            <!-- DESCARGAR --> 
            <a href="javascript:void(0);" id="mb_descargar" class="menu-button" data-options="iconCls:'icon-drive_save'" onclick="$('#mm_descargar').menu('show')">Descargar</a>
            <div id="mm_descargar" style="width:160px;height:120px;"> <!-- height:120px;-->
                <div data-options="iconCls:'icon-excel2'" onclick="descargar('DownloadExcel')">Integral</div>
                <div class="menu-sep"></div>
                <div data-options="iconCls:'icon-excel2'" onclick="descargar('DownloadExcelMaestroFianzas')">Cartas Fianzas</div>
                <div class="menu-sep"></div>
                <div data-options="iconCls:'icon-excel2'" onclick="descargar('DownloadExcelMaestroContratos')">Contratos</div>
            </div>

            <!--<a href="javascript:void(0);" id="btn_descargar" class="easyui-linkbutton" data-options="iconCls:'icon-excel2'" onclick="descargar('DownloadExcel')">Descargar</a>-->
            <a href="javascript:void(0);" id="btn_limpiar" class="easyui-linkbutton" data-options="iconCls:'icon-borrador'" onclick="limpieza_filtros()">Limpiar Filtros</a>
        </div>
        <div style="flex: 1;"></div>
    </div>
</div>

@endsection

@section('javascript_body_footer')
<script>

    window.addEventListener("load", adjustDatagridSizeOrigin);
	
</script>
@endsection