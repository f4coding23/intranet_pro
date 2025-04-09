CREATE VIEW [dbo].[VW_VACACIONES_ESPECIALES] AS
SELECT	V.id_vaca_especial, V.id_empresa, EMP.DE_NOMB AS empresa, V.id_unidad, UN.DE_UNID AS gerencia, V.id_departamento, D.DE_DEPA AS departamento, V.id_area, AR.DE_AREA AS area, V.id_seccion, SEC.DE_SECC AS seccion, V.id_solicitante, U.USUVCNOUSUARIO AS solicitante, V.id_generador, G.USUVCNOUSUARIO AS generador, V.fecha_crea, V.fecha_inicio, V.fecha_fin, V.num_dias
FROM	dbo.TBINT_VACA_ESPECIALES AS V INNER JOIN
	OFISIS.OFIPLAN_030420.dbo.TMEMPR AS EMP ON EMP.CO_EMPR COLLATE Modern_Spanish_CI_AS = V.id_empresa LEFT OUTER JOIN
	OFISIS.OFIPLAN_030420.dbo.TTDEPA AS D ON D.CO_DEPA COLLATE Modern_Spanish_CI_AS = V.id_departamento AND D.CO_EMPR COLLATE Modern_Spanish_CI_AS = V.id_empresa LEFT OUTER JOIN
	OFISIS.OFIPLAN_030420.dbo.TMUNID_EMPR AS UN ON UN.CO_UNID COLLATE Modern_Spanish_CI_AS = V.id_unidad AND UN.CO_EMPR COLLATE Modern_Spanish_CI_AS = V.id_empresa LEFT OUTER JOIN
	OFISIS.OFIPLAN_030420.dbo.TTAREA AS AR ON AR.CO_AREA COLLATE Modern_Spanish_CI_AS = V.id_area AND AR.CO_EMPR COLLATE Modern_Spanish_CI_AS = UN.CO_EMPR AND 
	AR.CO_DEPA = D.CO_DEPA LEFT OUTER JOIN
	OFISIS.OFIPLAN_030420.dbo.TTSECC AS SEC ON SEC.CO_SECC COLLATE Modern_Spanish_CI_AS = V.id_seccion AND SEC.CO_EMPR COLLATE Modern_Spanish_CI_AS = V.id_empresa AND 
	SEC.CO_AREA COLLATE Modern_Spanish_CI_AS = V.id_area AND SEC.CO_DEPA COLLATE Modern_Spanish_CI_AS = D.CO_DEPA LEFT OUTER JOIN
	dbo.VW_SEG_USUARIOS AS U ON U.USUINIDUSUARIO = V.id_solicitante LEFT OUTER JOIN
	dbo.VW_SEG_USUARIOS AS G ON G.USUINIDUSUARIO = V.id_generador
WHERE	(V.eliminado = 0) AND (U.USUBTFLACTIVO = 1) OR	(V.eliminado = 0) AND (V.id_solicitante IS NULL)