<?php
// Obtiene los parametros
$id_producto = (isset($params[0])) ? $params[0] : 0;

if ($id_producto != 0) {

    $producto = $db->from('inv_productos')->where('id_producto', $id_producto)->fetch_first();
    if ($producto['regalo'] == 1) {
        // Actualiza la informacion
	    $db->where('id_producto', $id_producto)->update('inv_productos', array('regalo' => 0));
    } else {
        // Actualiza la informacion
	    $db->where('id_producto', $id_producto)->update('inv_productos', array('regalo' => 1));
    }
	// Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/productos/regalo',
		'detalle' => 'Se actualizo producto con identificador numero ' . $id_producto ,
		'usuario_id' => $_SESSION[user]['id_user']
	);
	$db->insert('sys_procesos', $data) ;

    set_notification('success', 'Acción exitosa!', 'El producto fue agregado al listado de productos validos para regalos en promociones.');
	// Redirecciona a la pagina principal
    redirect('?/productos/listar');
} else {
    // if (count($clients) == 0 && count($grupos) == 0 ) {
    // Crea la notificacion
    set_notification('danger', 'No ha seleccionado un producto válido!', 'Asegurese de enviar los parametros correctos.');
    // Redirecciona la pagina
    redirect(back());
    // }
}



?>