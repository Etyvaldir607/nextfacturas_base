<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica si es una peticion post
if (is_post()) {	
	// Verifica la existencia de los datos enviados
	if (isset($_POST['id_proforma']) && isset($_POST['nit_ci']) && isset($_POST['nombre_cliente'])) {
		// Obtiene los datos del producto
		$id_proforma = trim($_POST['id_proforma']);
		$nit_ci = trim($_POST['nit_ci']);
		$nombre_cliente = trim($_POST['nombre_cliente']);

		// Instancia la proforma
		$proforma = array(
			'nit_ci' => $nit_ci,
			'nombre_cliente' => strtoupper($nombre_cliente)
		);
		
		// Actualiza la informacion
		$db->where('id_egreso', $id_proforma)->update('inv_egresos', $proforma);

		// Instancia la variable de notificacion
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Actualización satisfactoria!',
			'message' => 'El registro se actualizó correctamente.'
		);

		// Redirecciona a la pagina principal
		redirect('?/preventas/ver/' . $id_proforma);
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