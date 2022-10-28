<?php

// echo json_encode($_POST);
if (is_post()) {
    
    // echo "111";
    //var_dump($_POST);
    
	// Verifica la existencia de los datos enviados
	if (    (isset($_POST['id_venta']) || isset($_POST['nro_egresos']) )
	        && 
	        isset($_POST['id_distribuidor']) && isset($_POST['fecha_entrega']) ) {

        // echo "222";
        
        if( isset($_POST['nro_egresos']) ){ 
            $nro_egresos= $_POST['nro_egresos'];
        }else{
            $nro_egresos = array();
            $nro_egresos[0] = trim($_POST['id_venta']);
        }
        
        // echo "333";
        //var_dump($nro_egresos);
        
        foreach($nro_egresos as $nro => $id_det){
        	
        	$id_venta=$id_det;
        	
            $id_distribuidor = trim($_POST['id_distribuidor']);
            $fecha_entrega = trim($_POST['fecha_entrega']);

            $existe = $db->from('inv_asignaciones_clientes')->where('egreso_id', $id_venta)->where('estado_pedido', 'reasignado')->fetch();
            
            if (count($existe) > 0) {
                // echo json_encode($existe); die();
                foreach ($existe as $key => $item) {
                    $db->where('id_asignacion_cliente', $item['id_asignacion_cliente'])->update('inv_asignaciones_clientes', array('estado' => 'I'));
                }
                // Despues de cambiar de estado l asignacion, modificamos el egreso
                $modificado = array(
                    'tipo' => 'Preventa',
                    'estadoe' => 2,
                    'motivo_id' => 0
                );
                $db->where('id_egreso', $id_venta)->update('inv_egresos', $modificado);
    
                // echo 'Verificar si se modifico'; die();
    
                // Creamos el asignacion
                $asignacion = array(
                    'egreso_id'         => $id_venta,
                    'distribuidor_id'   => $id_distribuidor,
                    'fecha_entrega'     => $fecha_entrega,
                    'estado_pedido'     => 'sin_aprobacion',
                    'empleado_id'       => $_user['persona_id'],
                    'estado'            => 'A',
                    
                    'fecha_hora_salida'=>'0000-00-00 00:00:00',
                    'fecha_hora_liquidacion'=>'0000-00-00 00:00:00',
                	'nro_salida'=>0,
	                'nro_liquidacion'=>0,
                    'coordenadas_entrega'=>'', 
                );
                // Guardamos el asignacion
                $id_asignacion = $db->insert('inv_asignaciones_clientes', $asignacion);
    
                // Guarda Historial
                $data = array(
                    'fecha_proceso' => date("Y-m-d"),
                    'hora_proceso' => date("H:i:s"),
                    'proceso' => 'c',
                    'nivel' => 'l',
                    'direccion' => '?/asignacion/preventas_guardar',
                    'detalle' => 'Se creo reasignacion cliente con identificador numero ' . $id_asignacion,
                    'usuario_id' => $_SESSION[user]['id_user']
                );
                $db->insert('sys_procesos', $data);
    
            } else {
                // Creamos el asignacion
                $asignacion = array(
                    'egreso_id'         => $id_venta,
                    'distribuidor_id'   => $id_distribuidor,
                    'fecha_entrega'     => $fecha_entrega,
                    'estado_pedido'     => 'sin_aprobacion',
                    'empleado_id'       => $_user['persona_id'],
                    'estado'            => 'A',
                    
                    'fecha_hora_salida'=>'0000-00-00 00:00:00',
                    'fecha_hora_liquidacion'=>'0000-00-00 00:00:00',
                	'nro_salida'=>0,
	                'nro_liquidacion'=>0,
                    'coordenadas_entrega'=>'', 
                );
                // Guardamos el asignacion
                $id_asignacion = $db->insert('inv_asignaciones_clientes', $asignacion);
    
                // Guarda Historial
                $data = array(
                    'fecha_proceso' => date("Y-m-d"),
                    'hora_proceso' => date("H:i:s"),
                    'proceso' => 'c',
                    'nivel' => 'l',
                    'direccion' => '?/asignacion/preventas_guardar',
                    'detalle' => 'Se creo asignacion cliente con identificador numero ' . $id_asignacion,
                    'usuario_id' => $_SESSION[user]['id_user']
                );
                $db->insert('sys_procesos', $data);
            }
        }

        set_notification('success', 'Accion satisfactoria.', 'La reasignacion se realizó de forma correcta.');
        return redirect('?/asignacion/preventas_listar');

    } else {
        set_notification('error', 'Accion insatisfactoria.', 'Por favor seleccione el distribuidor y la fecha de entrega...');
        return redirect(back());
    }
} else {
    set_notification('error', 'Accion insatisfactoria.', 'La accion no es válida.');
    return redirect(back());
}

?>