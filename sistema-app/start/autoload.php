<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Configuracion reporte de errores
if (environment == 'production') {
	error_reporting(0);
} else {
	error_reporting(E_ALL);
}

// Inicia el buffer
ob_start();

// Obtiene la uri
$_url = $_SERVER['REQUEST_URI'];

// Desglosa la url para calcular el nro de partes
$_url = explode('?', $_url);

// Verifica si la url tiene 2 partes
if (sizeof($_url) != 2) {
	// Si la url no tiene 2 partes, redirecciona al modulo index
	redirect(index_public);
} else {
	// Si la url tiene 2 partes, elimina la primera
	array_shift($_url);

	// Obtiene la nueva url
	$_url = $_url[0];

	// Verifica si la url cumple con el formato de la expresion regular
	if (preg_match('/^(\/[a-z0-9-_]+){2,}$/', $_url)) {
		// Desglosa la url
		$_url = explode('/', $_url);

		// Elimina las partes vacias
		array_shift($_url);

		// Obtiene el modulo y el archivo
		$_module = array_shift($_url);
		$_file = array_shift($_url);

		// Define como variables globales
		define('module', $_module);
		define('file', $_file);

		// Almacena la direcion actual
		define('location', '?/' . $_module . '/' . $_file);

		// Almacena los parametros $params = palabra reservada
		$params = $_url;

		// Genera las direciones del modulo y el fichero
		$_url_module = modules . '/' . $_module;
		$_url_file = modules . '/' . $_module . '/' . $_file . '.php';
		
		// Verifica si existe el modulo y si existe el fichero
		if (file_exists($_url_module) && is_readable($_url_file)){
			// Genera la vista
			if ($_module != site){
				// Carga las configuraciones
				require_once start . '/session.php';
				require_once config . '/database.php';
				require_once start . '/check.php';
			} else {
				if ($_file == 'login') {
					// Importa el archivo de recuerdame
					require_once start . '/remember.php';
				}
			}
			// Carga el fichero
			require_once $_url_file;
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
}

// Limpia el buffer imprimiendo la salida
ob_flush();