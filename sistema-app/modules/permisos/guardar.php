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
	if (isset($_POST['id_rol'])) {
		// Obtiene los datos del rol
		$id_rol = $_POST['id_rol'];
		$estados = (isset($_POST['estados']) ? $_POST['estados'] : array());
		$archivos = (isset($_POST['archivos']) ? $_POST['archivos'] : array());

		// Elimina todos los permisos del rol
		$db->delete()->from('sys_permisos')->where('rol_id', $id_rol)->execute();

		// Recorre todos los estados marcados
		foreach ($estados as $indice => $estado) {
			// Instancia el permiso
			$permiso = array(
				'rol_id' => trim($id_rol),
				'menu_id' => trim($estados[$indice]),
				'archivos' => trim($archivos[$indice])
			);
			
			// Guarda el permiso
			$db->insert('sys_permisos', $permiso);
			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/permisos/guardar',
				'detalle' => 'Se creo permiso del rol con identificador ' . $id_rol ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);
			$db->insert('sys_procesos', $data) ; 
			
		}

		// Redirecciona a la pagina principal
		redirect('?/permisos/listar');
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