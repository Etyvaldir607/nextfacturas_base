<?php
// Obtiene el distribuidor
if(sizeof($params) > 0){
    $distribuidor = $params[0];

    $venta = $db->query('select MAX(nro_salida)as nro_salida
                         from inv_asignaciones_clientes')
            ->fetch_first();
    
    $db->where(array('estado_pedido' => "sin_aprobacion", 'distribuidor_id' => $distribuidor))
       ->update('inv_asignaciones_clientes',array('estado_pedido' => "salida", 'fecha_hora_salida'=>date('Y-m-d H:i:s'), 'nro_salida'=>($venta['nro_salida']+1) ) )
       ->execute();
    
	// Redirecciona a la pagina principal
	redirect('?/asignacion/asignaciones/'.$distribuidor);
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>