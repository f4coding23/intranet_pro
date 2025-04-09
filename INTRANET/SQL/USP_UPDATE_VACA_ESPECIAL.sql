USE [DBACINTRANET_TEST]
GO
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER PROCEDURE [dbo].[USP_UPDATE_VACA_ESPECIAL]
(
	@id_vaca_especial	INT,
	@usu_elim			INT,
	@resultado			BIT OUTPUT
)
AS
BEGIN
    SET @resultado = 0;

	IF NOT EXISTS (SELECT 1 FROM TBINT_VACACIONES WHERE id_vaca_especial = @id_vaca_especial)
	BEGIN
		UPDATE TBINT_VACA_ESPECIALES
		SET eliminado = 1,
		fecha_elim = GETDATE(),
		usu_elim = @usu_elim
		WHERE id_vaca_especial = @id_vaca_especial

		UPDATE TBINT_VACACIONES_TEMP
		SET eliminado = 1,
		fecha_elim = GETDATE(),
		usu_elim = @usu_elim
		WHERE id_vaca_especial = @id_vaca_especial

		SET @resultado = 1;
		PRINT @resultado;
	END
END
