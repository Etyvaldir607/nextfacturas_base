<?php

// Obtiene el distribuidor
$distribuidor = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el user
$user = $db->from('sys_empleados')
           ->where('id_empleado', $distribuidor)
           ->fetch_first();
           
$id_user = $user['id_empleado'];

// Verifica si el user existe
if ($user) {
    
    $inv_asignaciones =  $db->select('*')
                            ->from('inv_asignaciones_clientes')
                            ->where('distribuidor_id',$distribuidor)
                            ->where('estado','A')
                            ->where('estado_pedido =', "salida" ) // , $fecha_inicial
                            ->fetch();

    foreach ($inv_asignaciones as $inv_asignacion){    
                                // Obtenemos el egreso de la asignacion
        $egreso = $db->from('inv_egresos')
                     ->where('id_egreso', $inv_asignacion['egreso_id'])
                     ->fetch_first();

                                // Verificamos qu el egreso no este registrado en el temporal
                                // $verifica = $db->select('*')
                                //                 ->from('tmp_egresos')
                                //                 ->where('distribuidor_estado','NO ENTREGA')
                                //                 ->where('id_egreso',$asignacion['egreso_id'])
                                //                 ->fetch_first();
                                
                                // if(!$verifica){
                                    // modificamos el egreso
                                    
        $eg = $inv_asignacion['egreso_id'];
        $db->query("UPDATE inv_egresos 
                    SET `tipo` = 'No venta', `estadoe` = 4, `motivo_id` = -1, `preventa` = 'habilitado', `ingreso_id` = 0 
                    WHERE id_egreso = '$eg'
                ")->execute();
                            //}

        $db->where('id_asignacion_cliente', $inv_asignacion['id_asignacion_cliente'])
           ->update('inv_asignaciones_clientes', array('estado_pedido' => 'reasignado'));
    }
    
    
    $venta = $db->query('select MAX(nro_liquidacion)as nro_liquidacion
                         from inv_asignaciones_clientes
                         ')
                ->fetch_first();
    
    $db->where(array('fecha_hora_liquidacion' => '0000-00-00 00:00:00', 'distribuidor_id' => $id_user))
       ->update('inv_asignaciones_clientes',array('fecha_hora_liquidacion' => date('Y-m-d H:i:s'), 'nro_liquidacion'=>($venta['nro_liquidacion']+1) ) )
       ->execute();
       
    $db->where(array('estado' => 2, 'distribuidor_id' => $id_user))
       ->update('tmp_egresos',array('estado' => 1))
       ->execute();
       
    // Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"),
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/asignacion/asignacion_activar2',
		'detalle' => 'Se actualizo estado 2 ditribuidor con identificador número ' . $id_user ,
		'usuario_id' => $_SESSION[user]['id_user']
	);
	$db->insert('sys_procesos', $data) ;

    $db->where(array('estado' => 3, 'distribuidor_id' => $id_user))
       ->update('tmp_egresos',array('estado' => 2))
       ->execute();
    
     // Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"),
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/asignacion/asignacion_activar2',
		'detalle' => 'Se actualizo estado 3 ditribuidor con identificador número ' . $id_user ,
		'usuario_id' => $_SESSION[user]['id_user']
	);
	$db->insert('sys_procesos', $data) ;

	// Obtiene el nuevo estado
    $fecha_actual = date("Y-m-d");
    $nuevo = date("Y-m-d",strtotime($fecha_actual."- 1 days"));
	$estado = ($user['fecha_validar'] == date('Y-m-d')) ? $nuevo : date('Y-m-d');

	// Instancia el user
	$user = array(
		'fecha_validar' => $estado,
        'hora' => date('H:i:s')
	);
	// Genera la condicion
	$condicion = array('id_empleado' => $id_user);

	// Actualiza la informacion
	$db->where($condicion)->update('sys_empleados', $user);

	// Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"),
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/asignacion/asignacion_activar2',
		'detalle' => 'Se actualizo empleado con identificador número ' . $id_user ,
		'usuario_id' => $_SESSION[user]['id_user']
	);
	$db->insert('sys_procesos', $data) ;

	// Redirecciona a la pagina principal
	redirect('?/asignacion/asignaciones');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>