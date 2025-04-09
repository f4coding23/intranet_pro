CREATE VIEW [dbo].[VW_OFI_PERFIL_FULL] AS
SELECT	B.USUINIDUSUARIO AS id_solicitante, A.NOMBRES AS nom_solicitante, A.CO_EMPR AS cod_empresa, A.DE_NOMB_CORT AS nombre_empresa, A.CO_UNID AS cod_gerencia, A.DE_UNID AS gerencia, A.CO_DEPA AS cod_departamento, A.DE_DEPA AS departamento, A.CO_AREA AS cod_area, A.DE_AREA AS area, A.CO_SECC AS cod_seccion, A.DE_SECC_U AS seccion, A.FE_INGR_EMPR AS fecha_ingreso
FROM	dbo.TBINT_OFI_PERFILES AS A INNER JOIN
	DBACINHOUSE_TEST.dbo.TBSEGMAEUSUARIO AS B ON B.USUVCDNI = A.CO_TRAB
WHERE	(A.CO_EMPR = 1) AND (B.USUBTFLACTIVO = 1)