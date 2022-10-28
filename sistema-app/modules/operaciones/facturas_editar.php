<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica si es una peticion ajax
if (is_post()) {	
	// Verifica la existencia de los datos enviados
	if (isset($_POST['id_egreso']) && isset($_POST['nit_ci']) && isset($_POST['nombre_cliente'])) {
		// Importa la libreria para el codigo de control
		require_once libraries . '/controlcode-class/ControlCode.php';

		// Obtiene los datos del producto
		$id_egreso = trim($_POST['id_egreso']);
		$nit_ci = trim($_POST['nit_ci']);
		$nombre_cliente = trim($_POST['nombre_cliente']);

		// Obtiene la venta modificada
		$venta = $db->from('inv_egresos')->where('id_egreso', $id_egreso)->fetch_first();

		// Verifica si existe la venta
		if ($venta) {
			// Obtiene la dosificacion
			$dosificacion = $db->from('inv_dosificaciones')->where('id_dosificacion', $venta['dosificacion_id'])->fetch_first();

			// Verifica si l dosificacion existe
			if ($dosificacion) {
				// Obtiene los datos para el codigo de control
				$nro_autorizacion = $venta['nro_autorizacion'];
				$nro_factura = $venta['nro_factura'];
				$nit_ci = $nit_ci;
				$fecha = str_replace('-', '', $venta['fecha_egreso']);
				$total = round($venta['monto_total'], 0);
				$llave_dosificacion = base64_decode($dosificacion['llave_dosificacion']);
				
				// Genera el codigo de control
				$codigo_control = new ControlCode();
				$codigo_control = $codigo_control->generate($nro_autorizacion, $nro_factura, $nit_ci, $fecha, $total, $llave_dosificacion);

				// Instancia la venta
				$venta = array(
					'nit_ci' => $nit_ci,
					'nombre_cliente' => strtoupper($nombre_cliente),
					'codigo_control' => $codigo_control
				);
				
				// Actualiza la informacion
				$db->where('id_egreso', $id_egreso)->update('inv_egresos', $venta);

				// Instancia la variable de notificacion
				$_SESSION[temporary] = array(
					'alert' => 'success',
					'title' => 'Actualización satisfactoria!',
					'message' => 'El registro se actualizó correctamente.'
				);

				// Redirecciona a la pagina principal
				redirect('?/operaciones/facturas_ver/' . $id_egreso);
			} else {
				// Error 404
				require_once not_found();
				exit;
			}
		} else {
			// Error 404
			require_once not_found();
			exit;
		}
	} else {
		// Error 401
		require_once bad_request();
		exit;
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>