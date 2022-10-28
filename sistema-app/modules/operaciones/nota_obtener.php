<?php

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
						'fecha_factura' => date('Y-m-d H:i:s'),
						'provisionado' => 'N', // S
						'descripcion' => 'Venta de productos con factura electronica',
						'nro_factura' => $nro_factura,
						'nro_autorizacion' => $nro_autorizacion,
						'codigo_control' => $codigo_control,
						'fecha_limite' => $dosificacion['fecha_limite'],
						'dosificacion_id' => $dosificacion['id_dosificacion'],
						'empleado_id' => $_user['persona_id']
					);
					// Actualizamos la venta
					$db->where('id_egreso', $venta['id_egreso'])->update('inv_egresos', $actualizado);

					// Guarda Historial
					$data = array(
						'fecha_proceso' => date("Y-m-d"),
						'hora_proceso' => date("H:i:s"),
						'proceso' => 'c',
						'nivel' => 'l',
						'direccion' => '?/operaciones/nota_obtener',
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
						'direccion' => '?/operaciones/nota_obtener',
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