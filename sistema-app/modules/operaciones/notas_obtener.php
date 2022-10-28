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
	if (isset($_POST['id_venta'])) {
		// Importa la libreria para convertir al numero
		require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

		// Obtiene el id_venta
		$id_venta = trim($_POST['id_venta']);

		// Obtiene la venta
		$venta = $db->select('i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno')->from('inv_egresos i')->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')->where('id_egreso', $id_venta)->fetch_first();

		// Verifica si existe la venta
		if ($venta) {
			// Obtiene la moneda
			$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
			$moneda = ($moneda) ? $moneda['moneda'] : '';

			// Obtiene los datos del monto total
			$conversor = new NumberToLetterConverter();
			$monto_textual = explode('.', $venta['monto_total']);
			$monto_numeral = $monto_textual[0];
			$monto_decimal = $monto_textual[1];
			$monto_literal = ucfirst(strtolower(trim($conversor->to_word($monto_numeral))));

			// Obtiene los detalles
			$detalles = $db->select('d.*, p.codigo, p.nombre, p.nombre_factura')->from('inv_egresos_detalles d')->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')->where('d.egreso_id', $id_venta)->order_by('id_detalle asc')->fetch();

			// Instancia los detalles
			$nombres = array();
			$cantidades = array();
			$precios = array();
			$subtotales = array();

			// Recorre los detalles
			foreach ($detalles as $nro => $detalle) {
				// Almacena los detalles
				array_push($nombres, $detalle['nombre_factura']);
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
				'empresa_ciudad' => 'LA PAZ - BOLIVIA',
				'empresa_actividad' => $_institution['razon_social'],
				'empresa_nit' => $_institution['nit'],
                'empresa_agradecimiento' => 'Gracias por su compra',
                'empresa_empleado' => $venta['nombres'].' '.$venta['paterno'].' '.$venta['materno'],
				'nota_titulo' => 'N O T A   D E   R E M I S I Ó N',
				'nota_numero' => $venta['nro_factura'],
				'nota_autorizacion' => '',
				'nota_fecha' => date_decode($venta['fecha_egreso'], 'd/m/Y'),
				'nota_hora' => substr($venta['hora_egreso'], 0, 5),
				'nota_codigo' => '',
				'nota_limite' => '',
				'nota_autenticidad' => '',
				'nota_leyenda' => '',
				'cliente_nit' => $venta['nit_ci'],
				'cliente_nombre' => $venta['nombre_cliente'],
				'venta_titulos' => array('CANTIDAD', 'DETALLE', 'P. UNIT.', 'SUBTOTAL', 'TOTAL'),
				'venta_cantidades' => $cantidades,
				'venta_detalles' => $nombres,
				'venta_precios' => $precios,
				'venta_subtotales' => $subtotales,
				'venta_total_numeral' => $venta['monto_total'],
				'venta_total_literal' => $monto_literal,
				'venta_total_decimal' => $monto_decimal . '/100',
				'venta_moneda' => '',
				'importe_base' => '0',
				'importe_ice' => '0',
				'importe_venta' => '0',
				'importe_credito' => '0',
				'importe_descuento' => '0',
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