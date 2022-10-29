/*****************************************************************************************************************/
/***************************************** ADICIÓN DE COLUMNAS ***************************************************/
/*****************************************************************************************************************/

ALTER TABLE `inv_egresos_facturas`
	ADD COLUMN `status` ENUM('issued','void','error') NULL DEFAULT 'issued' AFTER `egreso_id`;


ALTER TABLE `mb_siat_puntos_venta`
	ADD COLUMN `status` ENUM('open','unregistered','closed') NULL DEFAULT 'open' COLLATE 'utf8_general_ci' AFTER `creation_date`;

/*****************************************************************************************************************/
/****************************************** MODIFICACIÓN MENÚS ***************************************************/
/*****************************************************************************************************************/

TRUNCATE TABLE `sys_menus`;


INSERT INTO `sys_menus` (`id_menu`, `menu`, `icono`, `ruta`, `modulo`, `orden`, `antecesor_id`) VALUES
	(1, 'Administración', 'dashboard', '', '', 1, 0),
	(2, 'Configuración general', 'cog', '', '', 1, 1),
	(3, 'Apariencia del sistema', 'tint', '?/configuraciones/apariencia', 'configuraciones', 4, 2),
	(4, 'Información de la empresa', 'home', '?/configuraciones/institucion', 'configuraciones', 1, 2),
	(5, 'Ajustes sobre la fecha', 'cog', '?/configuraciones/preferencias', 'configuraciones', 3, 2),
	(175, 'Registro de roles', 'stats', '?/roles/listar', 'roles', 0, 1),
	(8, 'Asignación de permisos', 'lock', '?/permisos/listar', 'permisos', 3, 1),
	(9, 'Registro de usuarios', 'user', '?/usuarios/listar', 'usuarios', 5, 1),
	(10, 'Registro de empleados', 'eye-open', '?/empleados/listar', 'empleados', 4, 1),
	(19, 'Inventario', 'inbox', '', '', 3, 0),
	(20, 'Catálogo de productos', 'scale', '?/productos/listar', 'productos', 0, 19),
	(27, 'Facturación', 'qrcode', '', '', 5, 0),
	(28, 'Registro de terminales', 'phone', '?/terminales/listar', 'terminales', 3, 27),
	(29, 'Registro de dosificaciones', 'lock', '?/dosificaciones/listar', 'dosificaciones', 2, 27),
	(30, 'Ventas', 'shopping-cart', '', '', 4, 0),
	(31, 'Compras', 'log-in', '?/ingresos/listar', 'ingresos', 0, 19),
	(33, 'Proformas', 'list-alt', '?/proformas/seleccionar_almacen', 'proformas', 3, 30),
	(34, 'Ventas computarizadas', 'shopping-cart', '?/electronicas/mostrar', 'electronicas', 1, 30),
	(41, 'Operaciones', 'list', '', '', 5, 30),
	(42, 'Listado de facturas', 'qrcode', '?/operaciones/facturas_listar', 'operaciones', 1, 41),
	(44, 'Cierre de caja', 'stats', '?/movimientos/cerrar', 'movimientos', 0, 24),
	(45, 'Ingreso de dinero a caja', 'plus-sign', '?/movimientos/ingresos_listar', 'movimientos', 0, 24),
	(46, 'Egreso de dinero de caja', 'minus-sign', '?/movimientos/egresos_listar', 'movimientos', 0, 24),
	(48, 'Notas de venta', 'edit', '?/notas/seleccionar_almacen', 'notas', 2, 30),
	(50, 'Registro directo', 'plus', '?/registros/crear ', 'registros', 7, 0),
	(51, 'Registro de gastos', 'remove-sign', '?/movimientos/gastos_listar', 'movimientos', 0, 24),
	(54, 'Listado de notas de venta', 'edit', '?/operaciones/notas_listar', 'operaciones', 2, 41),
	(57, 'Certificación del sistema', 'ok', '?/evaluacion/verificar', 'evaluacion', 1, 27),
	(60, 'Preventas', 'list-alt', '?/preventas/seleccionar_almacen', 'preventas', 0, 30),
	(76, 'Repartidor', 'inbox', '', '', 0, 0),
	(77, 'Preventista', 'road', '', '', 0, 76),
	(79, 'Clientes', 'user', '', '', 0, 77),
	(83, 'Vendedores', 'user', '?/vendedor/listar', 'vendedor', 0, 77),
	(141, 'Comparativo', 'stats', '?/balances/comparativo', 'balances', 0, 139),
	(142, 'General', 'stats', '?/balances/general', 'balances', 0, 139),
	(143, 'Resultados', 'stats', '?/balances/resultados', 'balances', 0, 139),
	(144, 'Sumas y Saldos', 'stats', '?/balances/sumas', 'balances', 0, 139),
	(145, 'Configurar asientos', 'cog', '?/asientos/listar', 'asientos', 0, 138),
	(146, 'Estados financieros', 'list-alt', '', '', 0, 138),
	(147, 'Cambio de patrimonio', 'file', '?/estados_financieros/patrimonio', 'estados_financieros', 0, 146),
	(148, 'Estado de flujo', 'file', '?/estados_financieros/flujo', 'estados_financieros', 0, 146),
	(149, 'Estado de resultados', 'file', '?/estados_financieros/resultados', 'estados_financieros', 0, 146),
	(150, 'Hoja de trabajo', 'list-alt', '', '', 0, 138),
	(151, '10 columnas', 'file', '?/hoja_trabajo/hoja10', 'hoja_trabajo', 0, 150),
	(152, '6 columnas', 'file', '?/hoja_trabajo/hoja8', 'hoja_trabajo', 0, 150),
	(153, 'Hoja de ocho columnas', 'file', '?/hoja_trabajo/hoja8', 'hoja_trabajo', 0, 150),
	(154, 'Sumas y saldos', 'file', '?/hoja_trabajo/sumas', 'hoja_trabajo', 0, 150),
	(156, 'Libro mayor', 'book', '?/libro_mayor/libro_mayor', 'libro_mayor', 0, 138),
	(157, 'Plan de cuentas', 'list-alt', '?/cuentas/mostrar', 'cuentas', 0, 138),
	(173, 'Productos por cliente', 'copy', '?/reportes_ventas/clientes_listar', 'reportes_ventas', 0, 168),
	(174, 'Productos por vendedor', 'copy', '?/reportes_ventas/productos_vendedor', 'reportes_ventas', 0, 168),
	(196, 'Siat', '', '', '', 3, 27),
	(198, 'Sincronizacion', '', '?/siat/sync', 'siat', 0, 196),
	(199, 'Puntos de Venta', 'plus', '?/siat/listar_puntos_venta', 'siat', 0, 196),
	(200, 'eventos', 'plus', '?/siat/listar_eventos', 'siat', 0, 196),
	(201, 'CUFDs', 'plus', '?/siat/listas_cufds', 'siat', 0, 196),
	(202, 'Asignar ventas', 'map-marker', '?/asignacion/preventas_listar', 'asignacion', 0, 77),
	(203, 'Facturas', 'plus', '?/siat/listar_facturas', 'siat', 0, 196);
