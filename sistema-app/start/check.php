<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene los permisos
$_menus = $db->select('m.modulo, p.archivos')->from('sys_permisos p')->join('sys_menus m', 'p.menu_id = m.id_menu')->where(array('p.rol_id' => $_SESSION[user]['rol_id']))->where_not_in('m.id_menu', array('0'))->where_not_in('p.archivos', array(''))->fetch();

// Define el estado de autorizacion de un modulo
$_is_module = false;

// Define el grupo de archivos de un modulo
$_views = '';

// Recorre y verifica si tiene acceso al modulo
foreach ($_menus as $_menu) {
	if ($_menu['modulo'] == module) {
		$_is_module = true;
		$_views = $_menu['archivos'];
		break;
	}
}

// Define como variable global
define('permits', $_views);

// Verifica si tiene acceso al modulo
if (!$_is_module && module != home && module != tools) {
	// Error 401
	require_once bad_request();
	exit;
} else {
	// Obtiene las vistas
	$_views = explode(',', $_views);

	// Verifica si tiene acceso a la vista
	if (!in_array(file, $_views) && module != home && module != tools) {
		// Error 401
		require_once bad_request();
		exit;
	}
}

// Obtiene datos de la empresa $_institution = palabra reservada
$_institution = $db->from('sys_instituciones')->fetch_first();

// Obtiene los datos de la terminal
$_terminal = $db->from('inv_terminales')->where('identificador', $_SESSION[locale])->fetch_first();

// Obtiene datos del usuario $_user = palabra reservada
$_user = $db->select('u.*, r.rol, p.id_empleado, p.paterno, p.materno, p.nombres')->from('sys_users u')->join('sys_roles r', 'u.rol_id = r.id_rol', 'left')->join('sys_empleados p', 'u.persona_id = p.id_empleado', 'left')->where('u.id_user', $_SESSION[user]['id_user'])->fetch_first();