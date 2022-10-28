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
		// Importa la libreria para el codigo de control
		require_once libraries . '/controlcode-class/ControlCode.php';
		// Importa la libreria para convertir al numero
		require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

		// Obtiene el id_venta
		$id_venta = trim($_POST['id_venta']);

		// Obtiene la venta
		$venta = $db->select('i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno')->from('inv_egresos i')->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')->where('i.id_egreso', $id_venta)->fetch_first();

		if ($venta) {
			if ($venta['nro_autorizacion'] == 0 && $venta['codigo_control'] == NULL ) {
				# code...
				// Obtiene la fecha de hoy
				$hoy = date('Y-m-d');
				// Obtiene la dosificacion del periodo actual
				$dosificacion = $db->from('inv_dosificaciones')->where('fecha_registro <=', $hoy)->where('fecha_limite >=', $hoy)->where('activo', 'S')->fetch_first();

				// Verifica si la dosificación existe
				if ($dosificacion) {
					// Obtiene los datos para el codigo de control
					$nro_autorizacion = $dosificacion['nro_autorizacion'];
					$nro_factura = intval($dosificacion['nro_facturas']) + 1;
					$nit_ci = $venta['nit_ci'];
					$fecha = date('Ymd');
					$total = round($venta['monto_total'], 0);
					$llave_dosificacion = base64_decode($dosificacion['llave_dosificacion']);
					// Genera el codigo de control
					$codigo_control = new ControlCode();
					$codigo_control = $codigo_control->generate($nro_autorizacion, $nro_factura, $nit_ci, $fecha, $total, $llave_dosificacion);
					// Define la variable de subtotales
					$subtotales = array();
					// Obtiene la moneda
					$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
					$moneda = ($moneda) ? $moneda['moneda'] : '';
					// Obtiene los datos del monto total
					$conversor = new NumberToLetterConverter();
					$monto_textual = explode('.', $venta['monto_total']);
					$monto_numeral = $monto_textual[0];
					$monto_decimal = $monto_textual[1];
					$monto_literal = ucfirst(strtolower(trim($conversor->to_word($monto_numeral))));

					// Instancia la venta
					$actualizado = array(
						// 'fecha_egreso' => date('Y-m-d'),
						// 'hora_egreso' => date('H:i:s'),
						'provisionado' => 'N', // S
						'descripcion' => 'Venta de productos con factura electrónica',
						'nro_factura' => $nro_factura,
						'nro_autorizacion' => $nro_autorizacion,
						'codigo_control' => $codigo_control,
						'fecha_limite' => $dosificacion['fecha_limite'],
						'dosificacion_id' => $dosificacion['id_dosificacion'],
						'empleado_id' => $_user['persona_id'],
						'estadoe' => 0
					);
					// Actualizamos la venta
					$db->where('id_egreso', $venta['id_egreso'])->update('inv_egresos', $actualizado);

					// Guarda Historial
					$data = array(
						'fecha_proceso' => date("Y-m-d"),
						'hora_proceso' => date("H:i:s"),
						'proceso' => 'c',
						'nivel' => 'l',
						'direccion' => '?/operaciones/preventas_obtener',
						'detalle' => 'Se creo actualizo el egreso con identificador numero ' . $venta['id_egreso'] ,
						'usuario_id' => $_SESSION[user]['id_user']
					);
					$db->insert('sys_procesos', $data);

					// Actualiza la informacion
					$db->where('id_dosificacion', $dosificacion['id_dosificacion'])->update('inv_dosificaciones', array('nro_facturas' => $nro_factura));
					// Guarda Historial
					$data = array(
						'fecha_proceso' => date("Y-m-d"),
						'hora_proceso' => date("H:i:s"),
						'proceso' => 'u',
						'nivel' => 'l',
						'direccion' => '?/operaciones/preventas_obtener',
						'detalle' => 'Se actualizo almacen con dosificacion con numero ' . $dosificacion['id_dosificacion'] ,
						'usuario_id' => $_SESSION[user]['id_user']
					);
					$db->insert('sys_procesos', $data) ;

					// Enviamos el egreso_id
					echo json_encode($venta['id_egreso']);

				} else {
					echo 'error';
				}
			} else {
				echo 'facturado';
			}
		} else {
			echo 'error';
		}




		// Verifica si existe la venta
		// if ($proforma) {
		// 	// Obtiene la moneda
		// 	$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
		// 	$moneda = ($moneda) ? $moneda['moneda'] : '';

		// 	// Obtiene los datos del monto total
		// 	$conversor = new NumberToLetterConverter();
		// 	$monto_textual = explode('.', $proforma['monto_total']);
		// 	$monto_numeral = $monto_textual[0];
		// 	$monto_decimal = $monto_textual[1];
		// 	$monto_literal = ucfirst(strtolower(trim($conversor->to_word($monto_numeral))));

		// 	// Obtiene los detalles
		// 	$detalles = $db->select('d.*, p.codigo, p.nombre, p.nombre_factura')->from('inv_egresos_detalles d')->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')->where('d.egreso_id', $proforma['id_egreso'])->order_by('id_detalle asc')->fetch();

		// 	// Instancia los detalles
		// 	$nombres = array();
		// 	$cantidades = array();
		// 	$precios = array();
		// 	$subtotales = array();

		// 	// Recorre los detalles
		// 	foreach ($detalles as $nro => $detalle) {
		// 		// Almacena los detalles
		// 		array_push($nombres, str_replace("*", "'", $detalle['nombre_factura']));
		// 		array_push($cantidades, $detalle['cantidad']);
		// 		array_push($precios, $detalle['precio']);
		// 		array_push($subtotales, number_format($detalle['precio'] * $detalle['cantidad'], 2, '.', ''));
		// 	}

		// 	// Instancia la respuesta
		// 	$respuesta = array(
		// 		'empresa_nombre' => $_institution['nombre'],
		// 		'empresa_sucursal' => 'SUCURSAL Nº 1',
		// 		'empresa_direccion' => $_institution['direccion'],
		// 		'empresa_telefono' => 'TELÉFONO ' . $_institution['telefono'],
		// 		'empresa_ciudad' => 'EL ALTO - LA PAZ - BOLIVIA',
		// 		'empresa_actividad' => $_institution['razon_social'],
		// 		'empresa_nit' => $_institution['nit'],
        //         'empresa_agradecimiento' => 'Gracias por su compra',
        //         'empresa_empleado' => $proforma['nombre'].' '.$proforma['paterno'].' '.$proforma['materno'],
		// 		'factura_titulo' => 'P  R  E V E N T A',
		// 		'factura_numero' => $proforma['nro_proforma'],
		// 		'factura_fecha' => date_decode($proforma['fecha_proforma'], 'd/m/Y'),
		// 		'factura_hora' => substr($proforma['hora_proforma'], 0, 5),
		// 		'cliente_nit' => $proforma['nit_ci'],
		// 		'cliente_nombre' => $proforma['nombre_cliente'],
		// 		'venta_titulos' => array('CANTIDAD', 'DETALLE', 'P. UNIT.', 'SUBTOTAL', 'TOTAL'),
		// 		'venta_cantidades' => $cantidades,
		// 		'venta_detalles' => $nombres,
		// 		'venta_precios' => $precios,
		// 		'venta_subtotales' => $subtotales,
		// 		'venta_total_numeral' => $proforma['monto_total'],
		// 		'venta_total_literal' => $monto_literal,
		// 		'venta_total_decimal' => $monto_decimal . '/100',
		// 		'venta_moneda' => $moneda,
		// 		'impresora' => $_terminal['impresora'],
		// 		'modulo' => name_project
		// 	);

		// 	// Envia respuesta
		// 	echo json_encode($respuesta);
		// } else {
		// 	// Envia respuesta
		// 	echo 'error';
		// }
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