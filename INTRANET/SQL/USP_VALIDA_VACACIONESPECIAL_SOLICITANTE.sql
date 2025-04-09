USE [DBACINTRANET_TEST]
GO
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER PROCEDURE [dbo].[USP_VALIDA_VACACIONESPECIAL_SOLICITANTE]
(
    @id_solicitante INT,
    @fecha_inicio DATE,
    @fecha_fin DATE,
    @existeRegistro BIT OUTPUT,
    @mensaje VARCHAR(200) OUTPUT
)
AS
BEGIN
    SET @existeRegistro = 0;
    SET @mensaje = '';
    
    IF EXISTS (
        SELECT 1 FROM TBINT_VACACIONES_TEMP 
        WHERE id_solicitante = @id_solicitante AND eliminado = 0
        AND (
            (@fecha_inicio BETWEEN fecha_inicio AND fecha_fin) OR
            (@fecha_fin BETWEEN fecha_inicio AND fecha_fin) OR
            (fecha_inicio BETWEEN @fecha_inicio AND @fecha_fin) OR
            (fecha_fin BETWEEN @fecha_inicio AND @fecha_fin)
        )
    )
    BEGIN
        SET @existeRegistro = 1;
        SET @mensaje = 'El colaborador ya tiene vacaciones especiales registradas que coinciden con el rango de fechas seleccionado.';
    END
END