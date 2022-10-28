<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Inicializa las sessiones
session_start();

// Verifica si la variable de session existe
if (isset($_SESSION[user])){
	// Importa la configuracion para el manejo de la base de datos
	require_once config . '/database.php';

	// Almacena el id del usuario
	$id_user = $_SESSION[user]['id_user'];

	// Actualiza la ultima salida del usuario
	$where = array('id_user' => $id_user);
	$user = array('logout_at' => date('Y-m-d H:i:s'));
	$db->where($where)->update('sys_users', $user);

	// Desactiva la variable de session
	unset($_SESSION[user]);
	unset($_SESSION[locale]);
	setcookie(remember, '', time());

	// Destruye la variable de session
	session_destroy();
}

// Redirecciona a la pagina de inicio
redirect(index_public);

?>