<?php
$id_producto = (sizeof($params) > 0) ? $params[0] : 0;

$producto = $db->from('inv_productos')
               ->where('id_producto', $id_producto)
               ->fetch_first();

$movimientos = $db->query("(SELECT A.* 
                            FROM inv_productos p 
            				LEFT JOIN (SELECT id.id_detalle ,id.cantidad, id.producto_id, 'i'AS movimiento FROM inv_productos p 
            				LEFT JOIN inv_ingresos_detalles id ON id.producto_id = p.id_producto) A ON p.id_producto = A.producto_id WHERE A.producto_id={$id_producto})
            				
            				UNION
            				
            				(SELECT B.* 
            				FROM inv_productos p 
            				LEFT JOIN (SELECT ed.id_detalle ,ed.cantidad, ed.producto_id, 'e' AS movimiento FROM inv_productos p 
            				LEFT JOIN inv_egresos_detalles ed ON ed.producto_id = p.id_producto) B ON p.id_producto = B.producto_id WHERE B.producto_id={$id_producto})
            			   ")->fetch_first();

//verifica que no exitam movimientos
if (!$movimientos || true) {
    
    // Verifica si el producto existe
    if ($producto) {
    	// Elimina el producto
    // 	$db->delete()->from('inv_productos')->where('id_producto', $id_producto)->limit(1)->execute();
        
        // var_dump($producto['visible']);die(); 
        if($producto['visible'] == 's'){
            
            //echo "NNNNNNNNNNNNNNNNNNNNNNNN";
            
            $actualizacion = array(
    			'visible' => 'n'
    		);
    		$condicion = array('id_producto' => $id_producto);
    		$db->where($condicion)->update('inv_productos', $actualizacion);
        }else{
            //echo "SSSSSSSSSSSSSSSSSSSSSSSSSSSS";
            $actualizacion = array(
    			'visible' => 's'
    		);
    		$condicion = array('id_producto' => $id_producto);
    		$db->where($condicion)->update('inv_productos', $actualizacion);
        }
        
        
    
    	//Guarda en el historial
    	$data = array(
    		'fecha_proceso' => date("Y-m-d"),
    		'hora_proceso' => date("H:i:s"), 
    		'proceso' => 'd',
    		'nivel' => 'l',
    		'direccion' => '?/productos/eliminar',
    		'detalle' => 'Se elimino producto con identificador numero ' . $id_producto ,
    		'usuario_id' => $_SESSION[user]['id_user']			
    	);			
    	$db->insert('sys_procesos', $data) ;
    
    	// Verifica si fue el producto eliminado
    	if ($db->affected_rows) {
    		// Instancia variable de notificacion
    		$_SESSION[temporary] = array(
    			'alert' => 'success',
    			'title' => 'Eliminación satisfactoria!',
    			'message' => 'El registro fue eliminado correctamente.'
    		);
    	}
    
    	// Redirecciona a la pagina principal
    	redirect('?/productos/listar');
    } else {
    	// Error 404
    	require_once not_found();
    	exit;
    }
}else{
	// Verifica si fue el ingreso eliminado
	if ($db->affected_rows) {
		// Instancia variable de notificacion
		$_SESSION[temporary] = array(
			'alert' => 'danger',
			'title' => 'Eliminacion no realizada!',
			'message' => 'El producto contiene movimientos en la empresa. La eliminacion no puede ser realizada.'
		);
	}

	// Redirecciona a la pagina principal
	redirect('?/productos/listar');	
}
?>