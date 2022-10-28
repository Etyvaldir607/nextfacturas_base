<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica si es una peticion ajax
if (is_ajax()) {
	// Verifica la existencia de los datos enviados
	if (isset($_POST['codigo'])) {
		// Obtiene los datos del producto
		$codigo = trim($_POST['codigo']);
        
        if($codigo != '' && $codigo != NULL) {
    		// Obtiene los productos con el valor buscado
    		$producto = $db->select('id_producto, codigo')->from('inv_productos')->where('codigo', $codigo)->fetch_first();
    
    		// Verifica si existe coincidencias
    		if ($producto) {
    			$response = array('valid' => false, 'message' => 'El código "' . $producto['codigo'] . '" ya fue registrado');
    		} else {
    			$response = array('valid' => true);
    		}
        } else {
            $response = array('valid' => false, 'message' => 'El código no puede ser vacio.');
        }

		// Devuelve los resultados
		echo json_encode($response);
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