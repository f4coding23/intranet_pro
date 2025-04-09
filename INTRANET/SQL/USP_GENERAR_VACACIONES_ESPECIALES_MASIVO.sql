USE [DBACINTRANET_TEST]
GO
/****** Object:  StoredProcedure [dbo].[USP_GENERAR_VACACIONES_ESPECIALES_MASIVO]    Script Date: 8/04/2025 15:57:27 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER PROCEDURE [dbo].[USP_GENERAR_VACACIONES_ESPECIALES_MASIVO]
    @idEmpresa          VARCHAR(50),
    @idSucursal         INT,
    @idUnidad           VARCHAR(50) = NULL,
    @idDepartamento     VARCHAR(50) = NULL,
    @idArea             VARCHAR(50) = NULL,
    @idSeccion          VARCHAR(50) = NULL,
    @idVacaEspecial     INT,
    @modalidad          INT,
    @idGenerador        INT,
    @fechaInicio        DATE,
    @fechaFin           DATE,
    @cantidadDias       INT
AS
BEGIN
    -- Validar la modalidad
    IF @modalidad NOT IN (2, 3, 4, 5, 6)
    BEGIN
        RAISERROR('Modalidad no válida.', 16, 1);
        RETURN
    END
    
    -- Declarar tablas temporales
    DECLARE @usuarios TABLE (
        id_solicitante      INT,
        cod_empresa         VARCHAR(50),
        cod_gerencia        VARCHAR(50),
        cod_departamento    VARCHAR(50),
        cod_area           VARCHAR(50),
        cod_seccion        VARCHAR(50)
    )
    
    DECLARE @usuariosConConflicto TABLE (
        id_solicitante      INT,
        cod_empresa         VARCHAR(50),
        nombre_completo     VARCHAR(200)
    )
    
    -- Obtener los usuarios según la modalidad
    IF @modalidad = 2 -- Sección
    BEGIN
        INSERT INTO @usuarios
        SELECT * FROM dbo.FUNC_GET_USUARIOS_POR_ORGANIZACION(@idEmpresa, @idUnidad, @idDepartamento, @idArea, @idSeccion);
    END
    ELSE IF @modalidad = 3 -- Área
    BEGIN
        INSERT INTO @usuarios
        SELECT * FROM dbo.FUNC_GET_USUARIOS_POR_ORGANIZACION(@idEmpresa, @idUnidad, @idDepartamento, @idArea, NULL);
    END
    ELSE IF @modalidad = 4 -- Departamento
    BEGIN
        INSERT INTO @usuarios
        SELECT * FROM dbo.FUNC_GET_USUARIOS_POR_ORGANIZACION(@idEmpresa, @idUnidad, @idDepartamento, NULL, NULL);
    END
    ELSE IF @modalidad = 5 -- Gerencia / Unidad
    BEGIN
        INSERT INTO @usuarios
        SELECT * FROM dbo.FUNC_GET_USUARIOS_POR_ORGANIZACION(@idEmpresa, @idUnidad, NULL, NULL, NULL);
    END
    ELSE IF @modalidad = 6 -- Empresa
    BEGIN
        INSERT INTO @usuarios
        SELECT * FROM dbo.FUNC_GET_USUARIOS_POR_ORGANIZACION(@idEmpresa, NULL, NULL, NULL, NULL);
    END
    
    -- Identificar usuarios con vacaciones especiales que se solapan
    INSERT INTO @usuariosConConflicto
    SELECT 
        u.id_solicitante,
        u.cod_empresa,
        ISNULL(usr.nom_solicitante, 'Usuario ID: ' + CAST(u.id_solicitante AS VARCHAR(10)))
    FROM @usuarios u
    LEFT JOIN VW_OFI_PERFIL_FULL usr ON u.id_solicitante = usr.id_solicitante
    WHERE EXISTS (
        SELECT 1 
        FROM TBINT_VACACIONES_TEMP v 
        WHERE v.id_solicitante = u.id_solicitante
        AND v.eliminado = 0
        AND v.id_vaca_especial IS NOT NULL
        AND (
            (@fechaInicio BETWEEN v.fecha_inicio AND v.fecha_fin) OR
            (@fechaFin BETWEEN v.fecha_inicio AND v.fecha_fin) OR
            (v.fecha_inicio BETWEEN @fechaInicio AND @fechaFin) OR
            (v.fecha_fin BETWEEN @fechaInicio AND @fechaFin)
        )
    )
    
    -- Eliminar usuarios con conflictos de la tabla @usuarios
    DELETE FROM @usuarios
    WHERE id_solicitante IN (SELECT id_solicitante FROM @usuariosConConflicto)
    
    -- Insertar los usuarios sin conflictos en la tabla intermedia
    INSERT INTO dbo.TBINT_VACACIONES_TEMP (
        id_empresa,
        id_sucursal,
        id_unidad,
        id_area,
        id_seccion,
        id_solicitante,
        id_generador,
        fecha_inicio,
        fecha_fin,
        num_dias,
        eliminado,
        fecha_crea,
        usu_crea,
        id_departamento,
        id_vaca_especial,
		subperiodo
    )
    SELECT
        cod_empresa,
        @idSucursal,
        cod_gerencia,
        cod_area,
        cod_seccion,
        id_solicitante,
        @idGenerador,
        @fechaInicio,
        @fechaFin,
        @cantidadDias,
        0,
        GETDATE(),
        @idGenerador,
        cod_departamento,
        @idVacaEspecial,
		2
    FROM @usuarios;
    
    -- Mostrar resultados
    DECLARE @totalUsuarios INT = (SELECT COUNT(*) FROM @usuarios);
    DECLARE @totalConflictos INT = (SELECT COUNT(*) FROM @usuariosConConflicto);
    
    PRINT 'Proceso completado:';
    PRINT ' - Usuarios con vacaciones asignadas correctamente: ' + CAST(@totalUsuarios AS VARCHAR(10));
    PRINT ' - Usuarios con vacaciones cruzadas (no asignados): ' + CAST(@totalConflictos AS VARCHAR(10));
    
    -- Mostrar detalles de usuarios con conflictos si los hay
    IF @totalConflictos > 0
    BEGIN
        PRINT ' ';
        PRINT 'Usuarios con vacaciones existentes en el rango especificado:';
        
        DECLARE @mensaje VARCHAR(MAX) = '';
        SELECT @mensaje = @mensaje + nombre_completo + CHAR(13) + CHAR(10)
        FROM @usuariosConConflicto;
        
        PRINT @mensaje;
    END
END
