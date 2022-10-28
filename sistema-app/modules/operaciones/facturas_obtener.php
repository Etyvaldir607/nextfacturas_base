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
		if ($venta && $venta['empleado_id'] == $_user['persona_id']) {
			// Obtiene la moneda
			$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
			$moneda = ($moneda) ? $moneda['moneda'] : '';

			// Obtiene la dosificacion
			$dosificacion = $db->from('inv_dosificaciones')->where('id_dosificacion', $venta['dosificacion_id'])->fetch_first();

			// Obtiene los datos del monto total
			$conversor = new NumberToLetterConverter();
			$monto_textual = explode('.', $venta['monto_total']);
			$monto_numeral = $monto_textual[0];
			$monto_decimal = $monto_textual[1];
			$monto_literal = ucfirst(strtolower(trim($conversor->to_word($monto_numeral))));

			// Verifica si la dosificación existe
			if ($dosificacion) {
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
                    'empresa_agradecimiento' => 'Gracias por su compra',
                    'empresa_empleado' => $venta['nombre'].' '.$venta['paterno'].' '.$venta['materno'],
					'empresa_nit' => $_institution['nit'],
					'factura_titulo' => 'F  A  C  T  U  R  A',
					'factura_numero' => $venta['nro_factura'],
					'factura_autorizacion' => $venta['nro_autorizacion'],
					'factura_fecha' => date_decode($venta['fecha_egreso'], 'd/m/Y'),
					'factura_hora' => substr($venta['hora_egreso'], 0, 5),
					'factura_codigo' => $venta['codigo_control'],
					'factura_limite' => date_decode($venta['fecha_limite'], 'd/m/Y'),
					'factura_autenticidad' => '"ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS. EL USO ILÍCITO DE ÉSTA SERÁ SANCIONADO DE ACUERDO A LEY"',
					'factura_leyenda' => 'Ley Nº 453: "' . $dosificacion['leyenda'] . '".',
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
					'venta_moneda' => $moneda,
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
		// Envia respuesta
		echo 'error';
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>