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
	if (isset($_POST['id_menu']) && isset($_POST['menu']) && isset($_POST['icono']) && isset($_POST['ruta']) && isset($_POST['antecesor_id'])) {
		// Obtiene los datos del menu
		$id_menu = trim($_POST['id_menu']);
		$menu = trim($_POST['menu']);
		$icono = trim($_POST['icono']);
		$ruta = trim($_POST['ruta']);
		$antecesor_id = trim($_POST['antecesor_id']);

		// Genera el modulo
		$modulo = '';
		if ($ruta != '') {
			$modulo = explode('/', $ruta);
			$modulo = $modulo[1];
		}

		// Instancia el menu
		$menu = array(
			'menu' => $menu,
			'icono' => $icono,
			'ruta' => $ruta,
			'modulo' => $modulo,
			'antecesor_id' => $antecesor_id
		);

		// Verifica si es creacion o modificacion
		if ($id_menu > 0) {
			// Genera la condicion
			$condicion = array('id_menu' => $id_menu);

			// Actualiza la informacion
			$db->where($condicion)->update('sys_menus', $menu);
		} else {
			// Guarda la informacion
			$menu_id = $db->insert('sys_menus', $menu);

			// Instancia el permiso
			$permiso = array(
				'rol_id' => '1',
				'menu_id' => $menu_id,
				'archivos' => ''
			);
			
			// Otorga el permiso al usuario principal
			$db->insert('sys_permisos', $permiso);
		}

		// Redirecciona a la pagina principal
		redirect('?/' . tools . '/menus_listar');
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