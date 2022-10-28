<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica si es una peticion ajax y post
if (is_ajax() && is_post()) {
	// Verifica la existencia de los datos enviados
	if (isset($_POST['id_proforma'])) {
		// Importa la libreria para convertir al numero
		require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

		// Obtiene el id_proforma
		$id_proforma = trim($_POST['id_proforma']);

		// Obtiene la proforma
		$proforma = $db->select('i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno')->from('inv_proformas i')->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')->where('id_proforma', $id_proforma)->fetch_first();

		// Verifica si existe la proforma
		if ($proforma) {
			// Obtiene la moneda
			$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
			$moneda = ($moneda) ? $moneda['moneda'] : '';

			// Obtiene los datos del monto total
			$conversor = new NumberToLetterConverter();
			$monto_textual = explode('.', $proforma['monto_total']);
			$monto_numeral = $monto_textual[0];
			$monto_decimal = $monto_textual[1];
			$monto_literal = ucfirst(strtolower(trim($conversor->to_word($monto_numeral))));

			// Obtiene los detalles
			$detalles = $db->select('d.*, p.codigo, p.nombre, p.nombre_factura')->from('inv_proformas_detalles d')->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')->where('d.proforma_id', $id_proforma)->order_by('id_detalle asc')->fetch();

			// Instancia los detalles
			$nombres = array();
			$cantidades = array();
			$precios = array();
			$subtotales = array();

			// Recorre los detalles
			foreach ($detalles as $nro => $detalle) {
				// Almacena los detalles
				array_push($nombres, str_replace("*", "'", $detalle['nombre_factura']));
				array_push($cantidades, $detalle['cantidad']);
				array_push($precios, $detalle['precio']);
				array_push($subtotales, number_format($detalle['precio'] * $detalle['cantidad'], 2, '.', ''));
			}

			// Instancia la respuesta
			$respuesta = array(
				'empresa_nombre' => $_institution['nombre'],
				'empresa_sucursal' => 'SUCURSAL Nº 1',
				'empresa_direccion' => $_institution['direccion'],
				'empresa_telefono' => 'TELÉFONO ' . $_institution['telefono'],
				'empresa_ciudad' => 'EL ALTO - LA PAZ - BOLIVIA',
				'empresa_actividad' => $_institution['razon_social'],
				'empresa_nit' => $_institution['nit'],
                'empresa_agradecimiento' => 'Gracias por su compra',
                'empresa_empleado' => $proforma['nombre'].' '.$proforma['paterno'].' '.$proforma['materno'],
				'proforma_titulo' => 'P  R  O  F  O  R  M  A',
				'proforma_numero' => $proforma['nro_proforma'],
				'proforma_fecha' => date_decode($proforma['fecha_proforma'], 'd/m/Y'),
				'proforma_hora' => substr($proforma['hora_proforma'], 0, 5),
				'cliente_nit' => $proforma['nit_ci'],
				'cliente_nombre' => $proforma['nombre_cliente'],
				'venta_titulos' => array('CANTIDAD', 'DETALLE', 'P. UNIT.', 'SUBTOTAL', 'TOTAL'),
				'venta_cantidades' => $cantidades,
				'venta_detalles' => $nombres,
				'venta_precios' => $precios,
				'venta_subtotales' => $subtotales,
				'venta_total_numeral' => $proforma['monto_total'],
				'venta_total_literal' => $monto_literal,
				'venta_total_decimal' => $monto_decimal . '/100',
				'venta_moneda' => $moneda,
				'impresora' => $_terminal['impresora'],
				'modulo' => name_project
			);

			// Envia respuesta
			echo json_encode($respuesta);
		} else {
			// Envia respuesta
			echo 'error';
		}		
	} else {
		// Envia respuesta
		echo 'error';
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>