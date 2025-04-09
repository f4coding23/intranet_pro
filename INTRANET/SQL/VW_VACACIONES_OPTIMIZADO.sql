CREATE VIEW [dbo].[VW_VACACIONES_OPTIMIZADO] AS
SELECT V.id_vacacion, V.id_empresa, EMP.DE_NOMB AS empresa, V.id_unidad, UN.DE_UNID AS gerencia, V.id_departamento, D.DE_DEPA AS departamento, V.id_area, AR.DE_AREA AS area, V.id_seccion, SEC.DE_SECC AS seccion, 
    V.id_solicitante, U.USUVCDNI AS dni, U.USUVCNOUSUARIO AS solicitante, U.USUBTFLACTIVO AS solicitante_activo, V.fecha_ingreso, V.id_generador, G.USUVCNOUSUARIO AS generador, G.USUVCDNI AS dni_generador, 
    V.fecha_crea, V.fecha_inicio, V.fecha_fin, V.num_dias, V.id_vaca_condicion, C.vaca_condicion, V.confirmado, V.id_vaca_estado, E.vaca_estado, T.idTipo, 
    CASE T .idTipo WHEN 2 THEN 'Extraordinario' ELSE T .tipo END AS tipo
FROM dbo.TBINT_VACACIONES AS V INNER JOIN
	dbo.TBINT_VACA_ESTADOS AS E ON E.id_vaca_estado = V.id_vaca_estado INNER JOIN
	dbo.TBINT_VACA_CONDICION AS C ON C.id_vaca_condicion = V.id_vaca_condicion INNER JOIN
	dbo.TBINT_PERMISO_TIPO AS T ON T.idTipo = V.idTipo INNER JOIN
	OFISIS.OFIPLAN.dbo.TMEMPR AS EMP ON EMP.CO_EMPR COLLATE Modern_Spanish_CI_AS = V.id_empresa INNER JOIN
	OFISIS.OFIPLAN.dbo.TTDEPA AS D ON D.CO_DEPA COLLATE Modern_Spanish_CI_AS = V.id_departamento AND D.CO_EMPR COLLATE Modern_Spanish_CI_AS = V.id_empresa INNER JOIN
	OFISIS.OFIPLAN.dbo.TMUNID_EMPR AS UN ON UN.CO_UNID COLLATE Modern_Spanish_CI_AS = V.id_unidad AND UN.CO_EMPR COLLATE Modern_Spanish_CI_AS = V.id_empresa INNER JOIN
	OFISIS.OFIPLAN.dbo.TTAREA AS AR ON AR.CO_AREA COLLATE Modern_Spanish_CI_AS = V.id_area AND AR.CO_EMPR COLLATE Modern_Spanish_CI_AS = V.id_empresa AND AR.CO_DEPA = D.CO_DEPA INNER JOIN
	OFISIS.OFIPLAN.dbo.TTSECC AS SEC ON SEC.CO_SECC COLLATE Modern_Spanish_CI_AS = V.id_seccion AND SEC.CO_EMPR COLLATE Modern_Spanish_CI_AS = V.id_empresa AND 
	SEC.CO_AREA COLLATE Modern_Spanish_CI_AS = V.id_area AND SEC.CO_DEPA COLLATE Modern_Spanish_CI_AS = D.CO_DEPA INNER JOIN
	dbo.VW_SEG_USUARIOS AS U ON U.USUINIDUSUARIO = V.id_solicitante LEFT OUTER JOIN
	dbo.VW_SEG_USUARIOS AS G ON G.USUINIDUSUARIO = V.id_generador
WHERE (V.eliminado = 0) AND (U.USUBTFLACTIVO = 1)