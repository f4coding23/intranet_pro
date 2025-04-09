USE [DBACINTRANET_TEST]
GO
/****** Object:  UserDefinedFunction [dbo].[FUNC_GET_USUARIOS_POR_ORGANIZACION]    Script Date: 8/04/2025 16:53:52 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER FUNCTION [dbo].[FUNC_GET_USUARIOS_POR_ORGANIZACION]
(
    @idEmpresa			VARCHAR(50) = NULL,
    @idUnidad			VARCHAR(50) = NULL,
    @idDepartamento		VARCHAR(50) = NULL,
    @idArea				VARCHAR(50) = NULL,
    @idSeccion			VARCHAR(50) = NULL
)
RETURNS TABLE
AS
RETURN
(
    SELECT 
        id_solicitante,
        cod_empresa,
        cod_gerencia,
        cod_departamento,
        cod_area,
        cod_seccion
    FROM 
        dbo.VW_OFI_PERFIL_FULL
    WHERE 
			(@idEmpresa			IS NULL OR cod_empresa		= @idEmpresa)
        AND (@idUnidad			IS NULL OR cod_gerencia		= @idUnidad)
        AND (@idDepartamento	IS NULL OR cod_departamento = @idDepartamento)
        AND (@idArea			IS NULL OR cod_area			= @idArea)
        AND (@idSeccion			IS NULL OR cod_seccion		= @idSeccion)
)
