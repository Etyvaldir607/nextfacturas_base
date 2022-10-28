<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene el id_user
$id_producto = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el user
$prod = $db->from('inv_productos')->where('id_producto', $id_producto)->fetch_first();

// Verifica si el user existe
if ($prod) {
	// Obtiene el nuevo estado

    if(isset($_POST['grupo'])){
        $user = array(
            'grupo' => $_POST['grupo']
        );
    }else{
        $user = array(
            'grupo' => ''
        );
    }

	// Genera la condicion
	$condicion = array('id_producto' => $id_producto);

	// Actualiza la informacion
	$db->where($condicion)->update('inv_productos', $user);
	
	// Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/productos/activar',
		'detalle' => 'Se actualizo producto con identificador número ' . $id_producto ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;

	// Redirecciona a la pagina principal
	redirect('?/productos/listar');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>