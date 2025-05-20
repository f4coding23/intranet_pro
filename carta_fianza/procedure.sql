USE [DBACPORTALINT_TEST]
GO
/****** Object:  StoredProcedure [CTF].[usp_moc_orden_compra_original]    Script Date: 20/05/2025 18:02:32 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER PROCEDURE [CTF].[usp_moc_orden_compra]
    @NumeroDocumentoProceso VARCHAR(10) = NULL,
    @DescripcionProceso VARCHAR(255) = NULL,
    @NumeroDocumentoEntrega VARCHAR(10) = NULL,
    @NumeroDocumentoPedido VARCHAR(10) = NULL, 
    @NumeroClienteOC VARCHAR(50) = NULL,
    @FechaDesde VARCHAR(10) = NULL,
    @FechaHasta VARCHAR(10) = NULL,
    @NumeroDocumentoPicking VARCHAR(20) = NULL,
    @NumeroDocumentoFactura VARCHAR(20) = NULL,
    @CodigoProducto VARCHAR(20) = NULL,
    @DescripcionProducto VARCHAR(255) = NULL,
    @NumeroContrato VARCHAR(50) = NULL,
    @Page INT = 1,
    @Rows INT = 50,
    @sort VARCHAR(50) = NULL,
    @order VARCHAR(4) = 'desc'
AS
BEGIN
    SET NOCOUNT ON;
    
    -- Convertir cadenas vacías a NULL
    SET @NumeroDocumentoProceso = NULLIF(@NumeroDocumentoProceso, '');
    SET @DescripcionProceso = NULLIF(@DescripcionProceso, '');
    SET @NumeroDocumentoEntrega = NULLIF(@NumeroDocumentoEntrega, '');
    SET @NumeroDocumentoPedido = NULLIF(@NumeroDocumentoPedido, '');
    SET @NumeroClienteOC = NULLIF(@NumeroClienteOC, '');
    SET @FechaDesde = NULLIF(@FechaDesde, '');
    SET @FechaHasta = NULLIF(@FechaHasta, '');
    SET @NumeroDocumentoPicking = NULLIF(@NumeroDocumentoPicking, '');
    SET @NumeroDocumentoFactura = NULLIF(@NumeroDocumentoFactura, '');
    SET @CodigoProducto = NULLIF(@CodigoProducto, '');
    SET @DescripcionProducto = NULLIF(@DescripcionProducto, '');
    SET @NumeroContrato = NULLIF(@NumeroContrato, '');
    
    SET @sort = ISNULL(@sort, 'FechaDocumentoPedido');
    
    DECLARE @FechaDesdeDateTime DATETIME = NULL;
    DECLARE @FechaHastaDateTime DATETIME = NULL;
    DECLARE @FechaActual DATETIME = CAST(CONVERT(VARCHAR(10), GETDATE(), 103) AS DATETIME);
    
    -- Conversión de fechas (código existente)...
    BEGIN TRY
        IF @FechaHasta IS NOT NULL 
            SET @FechaHastaDateTime = CONVERT(DATETIME, @FechaHasta, 120);
    END TRY
    BEGIN CATCH
        BEGIN TRY
            IF @FechaHasta IS NOT NULL 
                SET @FechaHastaDateTime = CONVERT(DATETIME, @FechaHasta, 103);
        END TRY
        BEGIN CATCH
            BEGIN TRY
                IF @FechaHasta IS NOT NULL 
                    SET @FechaHastaDateTime = CONVERT(DATETIME, @FechaHasta, 101);
            END TRY
            BEGIN CATCH
                SET @FechaHastaDateTime = NULL;
            END CATCH
        END CATCH
    END CATCH
    
    BEGIN TRY
        IF @FechaDesde IS NOT NULL 
            SET @FechaDesdeDateTime = CONVERT(DATETIME, @FechaDesde, 120);
    END TRY
    BEGIN CATCH
        BEGIN TRY
            IF @FechaDesde IS NOT NULL 
                SET @FechaDesdeDateTime = CONVERT(DATETIME, @FechaDesde, 103);
        END TRY
        BEGIN CATCH
            BEGIN TRY
                IF @FechaDesde IS NOT NULL 
                    SET @FechaDesdeDateTime = CONVERT(DATETIME, @FechaDesde, 101);
            END TRY
            BEGIN CATCH
                SET @FechaDesdeDateTime = NULL;
            END CATCH
        END CATCH
    END CATCH
    
    SET @Page = ISNULL(@Page, 1);
    SET @Rows = ISNULL(@Rows, 50);
    DECLARE @Inicio INT = (@Page - 1) * @Rows;
    
    CREATE TABLE #TempResultados (
        idprocesocab INT,
        NumeroDocumentoProceso VARCHAR(20),
        FechaDocumentoProceso DATETIME,
        DescripcionProceso VARCHAR(255),
        
        idprocesodet INT,
        PosicionProceso VARCHAR(10),
        JerarquiaProductoProceso VARCHAR(100),
        CantidadPrevistaProceso DECIMAL(18,4),
        UnidadMedidaProceso VARCHAR(10),
        ValorNetoProceso DECIMAL(18,4),
        
        identregacab INT,
        NumeroDocumentoEntrega VARCHAR(20),
        FechaDocumentoEntrega DATETIME,
        DescripcionEntrega VARCHAR(255),
        NumeroEntrega VARCHAR(20), -- Agregamos la nueva columna
        
        identregadet INT,
        PosicionEntrega VARCHAR(10),
        JerarquiaProductoEntrega VARCHAR(100),
        CantidadPrevistaEntrega DECIMAL(18,4),
        UnidadMedidaEntrega VARCHAR(10),
        ValorNetoEntrega DECIMAL(18,4),
        
        idpedidocab INT,
        NumeroDocumentoPedido VARCHAR(20),
        FechaDocumentoPedido DATETIME,
        DescripcionPedido VARCHAR(255),
        NumeroClienteOC VARCHAR(50),
        PuntoLlegada VARCHAR(255),
        cliente_oc VARCHAR(400),
        fecha_recepcion_oc VARCHAR(8),
		fecha_recepcion_cliente_final VARCHAR(8),
        
        idpedidodet INT,
        PosicionPedido VARCHAR(10),
        JerarquiaProductoPedido VARCHAR(100),
        CantidadPrevistaPedido DECIMAL(18,4),
        UnidadMedidaPedido VARCHAR(10),
        
        idmaeproducto INT,
        codigo_producto VARCHAR(20),
        desc_producto VARCHAR(255),
        
        idpickingcab INT,
        NumeroDocumentoPicking VARCHAR(20),
        FechaDocumentoPicking DATETIME,
        GuiaRemision VARCHAR(100),
        PesoTotalPicking DECIMAL(18,4),
        CantidadBultosPicking DECIMAL(18,4),
        
        idfacturacab INT,
        NumeroDocumentoFactura VARCHAR(20),
        NumeroFacturaSunat VARCHAR(50),
        SerieFacturaSunat VARCHAR(10),
        FechaDocumentoFactura DATETIME,
        ValorNetoFactura DECIMAL(18,4),
        ImporteCFFactura DECIMAL(18,4),
        fecha_internamiento_fac VARCHAR(8),
        
        idcontrato INT,
        NumeroContrato VARCHAR(50),
        FechaInicioContrato DATETIME,
        FechaFinContrato DATETIME,
        
        IdAdjuntoOC INT,
        IdAdjuntoPDF INT
    );
    
    INSERT INTO #TempResultados
    SELECT 
        PCAB.idprocesocab,
        PCAB.prcchcddocumento AS NumeroDocumentoProceso,
        PCAB.prcdtfcfechadocumento AS FechaDocumentoProceso,
        PCAB.prcchdsdenominacion AS DescripcionProceso,
        
        PDET.idprocesodet,
        PDET.prcitenposicion AS PosicionProceso,
        PDET.prcchdsjerarquiproducto AS JerarquiaProductoProceso,
        PDET.prcdcnmcantidadprevista AS CantidadPrevistaProceso,
        PDET.prcchcdunidadmedidadventa AS UnidadMedidaProceso,
        PDET.prcdcnmvalorneto AS ValorNetoProceso,
        
        ECAB.identregacab,
        ECAB.entchcddocumento AS NumeroDocumentoEntrega,
        ECAB.entdtfcfechadocumento AS FechaDocumentoEntrega,
        ECAB.entchdsdenominacion AS DescripcionEntrega,
        ECAB.entchcdnroentrega AS NumeroEntrega, -- Agregamos el campo pedchcdnroentrega
        
        EDET.identregadet,
        EDET.entitenposicion AS PosicionEntrega,
        EDET.entchdsjerarquiproducto AS JerarquiaProductoEntrega,
        EDET.entdcnmcantidadprevista AS CantidadPrevistaEntrega,
        EDET.entchcdunidadmedidadventa AS UnidadMedidaEntrega,
        EDET.entdcnmvalorneto AS ValorNetoEntrega,
        
        PEDCAB.idpedidocab,
        PEDCAB.pedchcddocumento AS NumeroDocumentoPedido,
        PEDCAB.peddtfcfechadocumento AS FechaDocumentoPedido,
        PEDCAB.pedchdsdenominacion AS DescripcionPedido,
        PEDCAB.pedchcdnumclienteoc AS NumeroClienteOC,
        CASE
            WHEN MAE_CF.KUNNR IS NOT NULL AND NULLIF(TRIM(ISNULL(MAE_CF.STRAS, '')), '') IS NOT NULL
            THEN CONCAT('[', MAE_CF.MCOD3, '] ', MAE_CF.STRAS)
    
            WHEN MAE_CF.KUNNR IS NULL AND MAE_DST.KUNNR IS NOT NULL AND NULLIF(TRIM(ISNULL(MAE_DST.STRAS, '')), '') IS NOT NULL
            THEN CONCAT('[', MAE_DST.MCOD3, '] ', MAE_DST.STRAS)
    
            WHEN NULLIF(TRIM(ISNULL(MAE_DST.STRAS, '')), '') IS NOT NULL
            THEN CONCAT('[', MAE_DST.MCOD3, '] ', MAE_DST.STRAS)
    
            ELSE NULL
        END AS PuntoLlegada,
        CASE
            WHEN MAE_CF.KUNNR IS NOT NULL AND NULLIF(CONCAT(TRIM(ISNULL(MAE_CF.NAME1, '')), TRIM(ISNULL(MAE_CF.NAME2, '')), TRIM(ISNULL(MAE_CF.NAME3, '')), TRIM(ISNULL(MAE_CF.NAME4, ''))), '') IS NOT NULL 
            THEN RTRIM(CONCAT(ISNULL(MAE_CF.NAME1, ''), ' ', ISNULL(MAE_CF.NAME2, ''), ' ', ISNULL(MAE_CF.NAME3, ''), ' ', ISNULL(MAE_CF.NAME4, '')))
    
            WHEN MAE_CF.KUNNR IS NULL AND MAE_DST.KUNNR IS NOT NULL AND NULLIF(CONCAT(TRIM(ISNULL(MAE_DST.NAME1, '')), TRIM(ISNULL(MAE_DST.NAME2, '')), TRIM(ISNULL(MAE_DST.NAME3, '')), TRIM(ISNULL(MAE_DST.NAME4, ''))), '') IS NOT NULL
            THEN RTRIM(CONCAT(ISNULL(MAE_DST.NAME1, ''), ' ', ISNULL(MAE_DST.NAME2, ''), ' ', ISNULL(MAE_DST.NAME3, ''), ' ', ISNULL(MAE_DST.NAME4, '')))
    
            WHEN NULLIF(CONCAT(TRIM(ISNULL(MAE_DST.NAME1, '')), TRIM(ISNULL(MAE_DST.NAME2, '')), TRIM(ISNULL(MAE_DST.NAME3, '')), TRIM(ISNULL(MAE_DST.NAME4, ''))), '') IS NOT NULL
            THEN RTRIM(CONCAT(ISNULL(MAE_DST.NAME1, ''), ' ', ISNULL(MAE_DST.NAME2, ''), ' ', ISNULL(MAE_DST.NAME3, ''), ' ', ISNULL(MAE_DST.NAME4, '')))
    
            ELSE NULL
        END AS cliente_oc,
        CONVERT(VARCHAR(8), PEDCAB.peddtfcfecharecepcionacfoc, 112) AS fecha_recepcion_oc,
		CONVERT(VARCHAR(8), PEDCAB.peddtfcfecharecepcionclientefinal, 112) AS fecha_recepcion_cliente_final,
        
        PEDDET.idpedidodet,
        PEDDET.peditenposicion AS PosicionPedido,
        PEDDET.pedchdsjerarquiproducto AS JerarquiaProductoPedido,
        PEDDET.peddcnmcantidadprevista AS CantidadPrevistaPedido,
        PEDDET.pedchcdunidadmedidadventa AS UnidadMedidaPedido,
        
        EDET.idmaeproducto,
        PROD.codigo_producto,
        PROD.desc_producto,
        
        PICKCAB.idpickingcab,
        PICKCAB.pckchcddocumento AS NumeroDocumentoPicking,
        PICKCAB.pckdtfcfechapicking AS FechaDocumentoPicking,
        PICKCAB.pckcddsguiaremision AS GuiaRemision,
        PICKCAB.pckdcnmpesototal AS PesoTotalPicking,
        PICKCAB.pckdcnmcantidadtotalbultos AS CantidadBultosPicking,
        
        FACCAB.idfacturacab,
        FACCAB.facchcddocumento AS NumeroDocumentoFactura,
        FACCAB.faccdchnrofacturasunat AS NumeroFacturaSunat,
        FACCAB.faccdchseriefacturasunat AS SerieFacturaSunat,
        FACCAB.facdtfcfechafactura AS FechaDocumentoFactura,
        FACCAB.facdcnmvalorneto AS ValorNetoFactura,
        FACCAB.facdcnmimportecf AS ImporteCFFactura,
        CONVERT(VARCHAR(8), DEU.cpcdtfcfechainternamientofactura, 112) AS fecha_internamiento_fac,
        
        CONT.idcontrato,
        CONT.cntchdscontrato AS NumeroContrato,
        CONT.cntdtfcfechainicontrato AS FechaInicioContrato,
        CONT.cntdtfcfechafincontrato AS FechaFinContrato,
        
        CASE 
            WHEN EXISTS (SELECT 1 FROM CTF.TBCTFADJUNTO_OC ADJ WITH (NOLOCK) 
                        WHERE ADJ.idpedido = PEDCAB.pedchcddocumento 
                        AND ADJ.adocitenidestadodato = 1 AND ADJ.idtipoadjunto = (SELECT idtipoadjunto FROM [CTF].[TBCTFTIPO_ADJUNTO] WHERE tdjchdstipoadjunto = 'Contrato')) 
            THEN (SELECT TOP 1 idadjuntooc FROM CTF.TBCTFADJUNTO_OC ADJ WITH (NOLOCK) 
                  WHERE ADJ.idpedido = PEDCAB.pedchcddocumento 
                  AND ADJ.adocitenidestadodato = 1 AND ADJ.idtipoadjunto = (SELECT idtipoadjunto FROM [CTF].[TBCTFTIPO_ADJUNTO] WHERE tdjchdstipoadjunto = 'Contrato')
                  ORDER BY adocdtfcfechareg DESC)
            ELSE 0 
        END AS IdAdjuntoOC,
        
        CASE 
            WHEN EXISTS (SELECT 1 FROM CTF.TBCTFADJUNTO_OC ADJ WITH (NOLOCK) 
                        WHERE ADJ.idpedido = PEDCAB.pedchcddocumento 
                        AND ADJ.adocitenidestadodato = 1 AND ADJ.idtipoadjunto = (SELECT idtipoadjunto FROM [CTF].[TBCTFTIPO_ADJUNTO] WHERE tdjchdstipoadjunto = 'Guía Sellada')) 
            THEN (SELECT TOP 1 idadjuntooc FROM CTF.TBCTFADJUNTO_OC ADJ WITH (NOLOCK) 
                  WHERE ADJ.idpedido = PEDCAB.pedchcddocumento 
                  AND ADJ.adocitenidestadodato = 1 AND ADJ.idtipoadjunto = (SELECT idtipoadjunto FROM [CTF].[TBCTFTIPO_ADJUNTO] WHERE tdjchdstipoadjunto = 'Guía Sellada')
                  ORDER BY adocdtfcfechareg DESC)
            ELSE 0 
        END AS IdAdjuntoPDF
    FROM CTF.TBCTFPEDIDO_CAB PEDCAB WITH (NOLOCK)
    LEFT JOIN 
        CTF.TBCTFPEDIDO_DET PEDDET WITH (NOLOCK) ON PEDDET.idpedidocab = PEDCAB.idpedidocab
    LEFT JOIN 
        CTF.TBCTFENTREGA_DET EDET WITH (NOLOCK) ON EDET.identregadet = PEDDET.identregadet
    LEFT JOIN 
        CTF.TBCTFENTREGA_CAB ECAB WITH (NOLOCK) ON ECAB.identregacab = EDET.identregacab
    LEFT JOIN 
        CTF.TBCTFPROCESO_DET PDET WITH (NOLOCK) ON PDET.idprocesodet = EDET.idprocesodet
    LEFT JOIN 
        CTF.TBCTFPROCESO_CAB PCAB WITH (NOLOCK) ON PCAB.idprocesocab = PDET.idprocesocab
    LEFT JOIN 
        DBPRUEBA_CONEXSAP.DBO.ZACTB_CF_MAESTRO_CLIENTES MAE_DST WITH (NOLOCK) ON MAE_DST.KUNNR = PEDCAB.pedchcddestinatariomercancia
    LEFT JOIN 
        DBPRUEBA_CONEXSAP.DBO.ZACTB_CF_MAESTRO_CLIENTES MAE_CF WITH (NOLOCK) ON MAE_CF.KUNNR = PEDCAB.pedchcdclientefinal    
    LEFT JOIN 
        GFD.mae_producto PROD WITH (NOLOCK) ON PEDDET.idmaeproducto = PROD.idmaeproducto
    LEFT JOIN 
        CTF.TBCTFCONTRATO_x_ENTREGA CXXE WITH (NOLOCK) ON CXXE.identregadet = EDET.identregadet
    LEFT JOIN 
        CTF.TBCTFCONTRATO CONT WITH (NOLOCK) ON CONT.idcontrato = CXXE.idcontrato
    LEFT JOIN 
        CTF.TBCTFPICKING_DET PICKDET WITH (NOLOCK) ON PEDDET.idpedidodet = PICKDET.idpedidodet
    LEFT JOIN 
        CTF.TBCTFPICKING_CAB PICKCAB WITH (NOLOCK) ON PICKDET.idpickingcab = PICKCAB.idpickingcab
    LEFT JOIN 
        CTF.TBCTFFACTURA_DET FACDET WITH (NOLOCK) ON PICKDET.idpickingdet = FACDET.idpickingdet
    LEFT JOIN 
        CTF.TBCTFFACTURA_CAB FACCAB WITH (NOLOCK) ON FACDET.idfacturacab = FACCAB.idfacturacab
    LEFT JOIN 
        CTF.TBCTFCUENTAS_X_COBRAR DEU WITH (NOLOCK) ON DEU.cpcchdsfacturasunat = FACCAB.faccdchfacturasunat
            AND (ABS(CAST(FACCAB.facdcnmvalorneto*1.18 AS DECIMAL(16,2)))-ABS(CAST(DEU.cpcdcnmimportefactura AS DECIMAL(16,2)))) < 0.1
    WHERE
        (@NumeroDocumentoProceso IS NULL OR PCAB.prcchcddocumento LIKE '%' + @NumeroDocumentoProceso + '%')
        AND (@DescripcionProceso IS NULL OR PCAB.prcchdsdenominacion LIKE '%' + @DescripcionProceso + '%')
        AND (@NumeroDocumentoEntrega IS NULL OR ECAB.entchcddocumento LIKE '%' + @NumeroDocumentoEntrega + '%')
        AND (@NumeroDocumentoPedido IS NULL OR PEDCAB.pedchcddocumento LIKE '%' + @NumeroDocumentoPedido + '%')
        AND (@NumeroClienteOC IS NULL OR PEDCAB.pedchcdnumclienteoc LIKE '%' + @NumeroClienteOC + '%')
        AND (@NumeroDocumentoPicking IS NULL OR PICKCAB.pckchcddocumento LIKE '%' + @NumeroDocumentoPicking + '%')
        AND (@NumeroDocumentoFactura IS NULL OR FACCAB.facchcddocumento LIKE '%' + @NumeroDocumentoFactura + '%')
        AND (@FechaDesdeDateTime IS NULL OR PEDCAB.peddtfcfechadocumento >= @FechaDesdeDateTime)
        AND (@FechaHastaDateTime IS NULL OR PEDCAB.peddtfcfechadocumento <= @FechaHastaDateTime)
        AND (@CodigoProducto IS NULL OR PROD.codigo_producto LIKE '%' + @CodigoProducto + '%')
        AND (@DescripcionProducto IS NULL OR PROD.desc_producto LIKE '%' + @DescripcionProducto + '%')
        AND (@NumeroContrato IS NULL OR CONT.cntchdscontrato LIKE '%' + @NumeroContrato + '%')
        AND (PICKDET.pckchcdtipoposicion IS NULL OR PICKDET.pckchcdtipoposicion <> 'ZPLM');
    
    DECLARE @TotalRows INT = (SELECT COUNT(*) FROM #TempResultados);
    
    IF EXISTS (SELECT 1 FROM #TempResultados)
    BEGIN
        DECLARE @sql NVARCHAR(4000);
        
        SET @sql = N'
        SELECT *, ' + CAST(@TotalRows AS VARCHAR) + ' AS Total FROM #TempResultados
        ORDER BY ' + QUOTENAME(@sort) + ' ' + @order + ', 
        NumeroDocumentoPedido, PosicionPedido
        OFFSET ' + CAST(@Inicio AS VARCHAR) + ' ROWS
        FETCH NEXT ' + CAST(@Rows AS VARCHAR) + ' ROWS ONLY;';
        
        EXEC sp_executesql @sql;
    END
    ELSE
    BEGIN
        SELECT 
            NULL AS idprocesocab, '' AS NumeroDocumentoProceso, NULL AS FechaDocumentoProceso, '' AS DescripcionProceso,
            NULL AS idprocesodet, '' AS PosicionProceso, '' AS JerarquiaProductoProceso, NULL AS CantidadPrevistaProceso,
            '' AS UnidadMedidaProceso, NULL AS ValorNetoProceso, NULL AS identregacab, '' AS NumeroDocumentoEntrega,
            NULL AS FechaDocumentoEntrega, '' AS DescripcionEntrega, '' AS NumeroEntrega, NULL AS identregadet, '' AS PosicionEntrega,
            '' AS JerarquiaProductoEntrega, NULL AS CantidadPrevistaEntrega, '' AS UnidadMedidaEntrega, NULL AS ValorNetoEntrega,
            NULL AS idpedidocab, '' AS NumeroDocumentoPedido, NULL AS FechaDocumentoPedido, '' AS DescripcionPedido,
            '' AS NumeroClienteOC, '' AS PuntoLlegada, '' AS cliente_oc, '' AS fecha_recepcion_oc, NULL AS idpedidodet, 
            '' AS PosicionPedido, '' AS JerarquiaProductoPedido, NULL AS CantidadPrevistaPedido, NULL AS idmaeproducto, '' AS UnidadMedidaPedido,
            '' AS codigo_producto, '' AS desc_producto, NULL AS idpickingcab, '' AS NumeroDocumentoPicking, 
            NULL AS FechaDocumentoPicking, '' AS GuiaRemision, NULL AS PesoTotalPicking, NULL AS CantidadBultosPicking,
            NULL AS idfacturacab, '' AS NumeroDocumentoFactura, '' AS NumeroFacturaSunat, '' AS SerieFacturaSunat, 
            NULL AS FechaDocumentoFactura, NULL AS ValorNetoFactura, NULL AS ImporteCFFactura, '' AS fecha_internamiento_fac,
            NULL AS idcontrato, '' AS NumeroContrato, NULL AS FechaInicioContrato, NULL AS FechaFinContrato,
            0 AS IdAdjuntoOC, 0 AS IdAdjuntoPDF,
            0 AS Total
        WHERE 1 = 0;
    END
    
    DROP TABLE #TempResultados;
END