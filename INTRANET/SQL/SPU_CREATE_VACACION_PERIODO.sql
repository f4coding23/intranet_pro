USE [DBACINTRANET_TEST]
GO
/****** Object:  StoredProcedure [dbo].[SPU_CREATE_VACACION_PERIODO]    Script Date: 9/04/2025 08:32:35 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER PROCEDURE [dbo].[SPU_CREATE_VACACION_PERIODO]
(
@id_empresa			VARCHAR(10), 
@id_sucursal		INT,
@id_unidad			VARCHAR(10),
@id_departamento	VARCHAR(4),
@id_area			VARCHAR(10),
@id_seccion			VARCHAR(10),
@id_solicitante		INT,
@fecha_ingreso		DATE,
@id_generador		INT,
@id_tipo			INT,
@id_vaca_condicion	INT,
@id_vaca_estado		INT,
@fecha_inicio		DATE,
@fecha_fin			DATE,
@num_dias			INT,
@confirmado			BIT,
@usu_crea			INT,
@id_vaca_especial	INT,
@periodo			NVARCHAR(100),
@subperiodo			INT,
@ID_VACACION		INT OUTPUT
)
AS
BEGIN
SET NOCOUNT ON

BEGIN TRY
    -- Iniciar transacción
    BEGIN TRANSACTION;


	INSERT INTO TBINT_VACACIONES
	(id_empresa,
	id_sucursal,
	id_unidad,
	id_departamento,
	id_area,
	id_seccion,
	id_solicitante,
	fecha_ingreso,
	id_generador,
	idTipo,
	id_vaca_condicion,
	id_vaca_estado,
	fecha_inicio,
	fecha_fin,
	num_dias,
	confirmado,
	eliminado,
	fecha_crea,
	usu_crea,
	id_vaca_especial,
	periodo,
	subperiodo)
	VALUES 
	(
	@id_empresa			
	,@id_sucursal		
	,@id_unidad			
	,@id_departamento	
	,@id_area			
	,@id_seccion			
	,@id_solicitante		
	,@fecha_ingreso		
	,@id_generador		
	,@id_tipo			
	,@id_vaca_condicion	
	,@id_vaca_estado		
	,@fecha_inicio		
	,@fecha_fin			
	,@num_dias			
	,@confirmado			
	,0
	,GETDATE()	
	,@usu_crea
	,@id_vaca_especial
	,@periodo
	,@subperiodo)
	
	SET @ID_VACACION = SCOPE_IDENTITY();

	IF(@id_vaca_especial <> NULL OR @id_vaca_especial <> '')
	BEGIN
		UPDATE TBINT_VACACIONES_TEMP
		SET eliminado = 1
		WHERE id_solicitante = @id_solicitante and eliminado = 0 and fecha_inicio = @fecha_inicio and fecha_fin = @fecha_fin
	END

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        -- Revertir transacción en caso de error
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        -- Devolver mensaje de error
        SELECT 
            ERROR_NUMBER() AS ErrorNumber,
            ERROR_MESSAGE() AS ErrorMessage;
    END CATCH;

	SET NOCOUNT OFF
END