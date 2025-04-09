USE [DBACINTRANET_TEST]
GO
/****** Object:  StoredProcedure [dbo].[SP_SUMARIO_VACACIONES_EMPLEADO]    Script Date: 9/04/2025 08:16:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER PROCEDURE [dbo].[SP_SUMARIO_VACACIONES_EMPLEADO]
    @ID_USUARIO INT,
    @EMPRESA VARCHAR(10) = NULL,
    @PERIODO VARCHAR(20) = NULL
AS
BEGIN
SET NOCOUNT ON
	DECLARE @DNI VARCHAR(20) = NULL

	SELECT @DNI = B.USUVCDNI FROM TBINT_OFI_PERFILES A
	INNER JOIN DBACINHOUSE_TEST..TBSEGMAEUSUARIO B ON	B.USUVCDNI collate Modern_Spanish_CI_AS = A.CO_TRAB
	WHERE A.CO_EMPR = 1 AND B.USUBTFLACTIVO = 1 
	AND B.USUINIDUSUARIO = @ID_USUARIO

    -- Crear una tabla temporal para almacenar los resultados combinados
    CREATE TABLE #ResultadosCombinados (
        DNI NVARCHAR(20),
        EMPRESA VARCHAR(10),
        PERIODO VARCHAR(20),
        DIAS_HABILES_OFISIS DECIMAL(10,2),
        DIAS_NO_HABILES_OFISIS DECIMAL(10,2),
        DIAS_HABILES_INTRANET DECIMAL(10,2),
        DIAS_NO_HABILES_INTRANET DECIMAL(10,2)
    );

    -- Insertar datos de Ofisis
    INSERT INTO #ResultadosCombinados (
        DNI, EMPRESA, PERIODO, 
        DIAS_HABILES_OFISIS, DIAS_NO_HABILES_OFISIS,
        DIAS_HABILES_INTRANET, DIAS_NO_HABILES_INTRANET
    )
    SELECT 
        CO_TRAB,
        CO_EMPR,
        PERIODO_VACACIONAL,
        SUM(NRO_DIAS - dbo.FUNC_NUM_DAYS_NO_HABIL(CO_EMPR, CO_SEDE, FECHA_INICIAL, FECHA_FINAL)),
        SUM(dbo.FUNC_NUM_DAYS_NO_HABIL(CO_EMPR, CO_SEDE, FECHA_INICIAL, FECHA_FINAL)),
        0,
        0
    FROM 
        VW_OFI_VACACIONES_OPTIMIZADO
    WHERE 
        (@DNI IS NULL OR CO_TRAB = @DNI) AND
        (@EMPRESA IS NULL OR CO_EMPR = @EMPRESA) AND
        (@PERIODO IS NULL OR PERIODO_VACACIONAL = @PERIODO)
    GROUP BY 
        CO_TRAB, CO_EMPR, PERIODO_VACACIONAL;

    -- Actualizar o insertar datos de Intranet
    MERGE INTO #ResultadosCombinados AS target
    USING (
        SELECT
            u.USUVCDNI AS dni,
            v.id_empresa AS empresa,
            v.periodo,
            SUM((DATEDIFF(DAY, fecha_inicio, fecha_fin) + 1) - dbo.FUNC_NUM_DAYS_NO_HABIL(id_empresa, id_sucursal, fecha_inicio, fecha_fin)) AS HABIL,
            SUM(dbo.FUNC_NUM_DAYS_NO_HABIL(id_empresa, id_sucursal, fecha_inicio, fecha_fin)) AS NO_HABIL
        FROM 
            [dbo].[TBINT_VACACIONES] v
        INNER JOIN
            [DBACINHOUSE_TEST].[dbo].[TBSEGMAEUSUARIO] u ON v.id_solicitante = u.USUINIDUSUARIO
        WHERE
            v.eliminado = 0 AND
            (@DNI IS NULL OR u.USUVCDNI = @DNI) AND
            (@EMPRESA IS NULL OR v.id_empresa = @EMPRESA) AND
            (@PERIODO IS NULL OR v.periodo = @PERIODO)
        GROUP BY
            u.USUVCDNI, v.id_empresa, v.periodo
    ) AS source
    ON (target.DNI = source.dni AND target.EMPRESA = source.empresa AND target.PERIODO = source.periodo)
    WHEN MATCHED THEN
        UPDATE SET
            target.DIAS_HABILES_INTRANET = source.HABIL,
            target.DIAS_NO_HABILES_INTRANET = source.NO_HABIL
    WHEN NOT MATCHED THEN
        INSERT (DNI, EMPRESA, PERIODO, DIAS_HABILES_OFISIS, DIAS_NO_HABILES_OFISIS, DIAS_HABILES_INTRANET, DIAS_NO_HABILES_INTRANET)
        VALUES (source.dni, source.empresa, source.periodo, 0, 0, source.HABIL, source.NO_HABIL);

    -- Devolver resultados con totales calculados
    SELECT 
        DNI,
        EMPRESA,
        PERIODO,
        DIAS_HABILES_OFISIS = CAST(DIAS_HABILES_OFISIS AS INT),
        DIAS_NO_HABILES_OFISIS = CAST(DIAS_NO_HABILES_OFISIS AS INT)
        --DIAS_HABILES_INTRANET,
        --DIAS_NO_HABILES_INTRANET,
        --DIAS_HABILES_OFISIS + DIAS_HABILES_INTRANET AS TOTAL_DIAS_HABILES,
        --DIAS_NO_HABILES_OFISIS + DIAS_NO_HABILES_INTRANET AS TOTAL_DIAS_NO_HABILES,
        --DIAS_HABILES_OFISIS + DIAS_HABILES_INTRANET + DIAS_NO_HABILES_OFISIS + DIAS_NO_HABILES_INTRANET AS TOTAL_DIAS
    FROM 
        #ResultadosCombinados;

    -- Eliminar tabla temporal
    DROP TABLE #ResultadosCombinados;
	SET NOCOUNT OFF
END;