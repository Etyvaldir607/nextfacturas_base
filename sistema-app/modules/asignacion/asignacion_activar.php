<?php

// Obtiene el id_user
$distribuidor = (sizeof($params) > 0) ? $params[0] : 0;


// Obtiene el user
$user = $db->from('sys_empleados')->where('id_empleado', $distribuidor)->fetch_first();
$id_user = $user['id_empleado'];

// Verifica si el user existe
if ($user) {
	$egresos = $db->query('SELECT  b.*, b.fecha_egreso as distribuidor_fecha , b.hora_egreso as distribuidor_hora, b.almacen_id as distribuidor_estado, b.almacen_id as distribuidor_id, estadoe as estado
    FROM inv_asignaciones_clientes a
    LEFT JOIN inv_egresos b ON a.egreso_id = b.id_egreso
    WHERE a.distribuidor_id = '.$id_user.' AND b.grupo = "" AND a.estado_pedido = "salida" AND b.estadoe = 2 AND b.fecha_egreso < CURDATE()')->fetch();

    $db->where(array('estado' => 2, 'distribuidor_id' => $id_user))->update('tmp_egresos',array('estado' => 1))->execute();
    // Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"),
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/asignacion/asignacion_activar',
		'detalle' => 'Se actualizo estado 2 ditribuidor con identificador número ' . $id_user ,
		'usuario_id' => $_SESSION[user]['id_user']
	);
	$db->insert('sys_procesos', $data) ;

    $db->where(array('estado' => 3, 'distribuidor_id' => $id_user))->update('tmp_egresos',array('estado' => 2))->execute();
     // Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"),
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/asignacion/asignacion_activar',
		'detalle' => 'Se actualizo estado 3  ditribuidor con identificador número ' . $id_user ,
		'usuario_id' => $_SESSION[user]['id_user']
	);
	$db->insert('sys_procesos', $data) ;

    foreach ($egresos as $nro => $egreso) {
    	$egreso['distribuidor_fecha'] = date('Y-m-d');
    	$egreso['distribuidor_hora'] = date('H:i:s');
    	$egreso['distribuidor_estado'] = 'ALMACEN';
    	$egreso['distribuidor_id'] = $id_user;
        $egreso['estado'] = 2;
    	// var_dump($egreso);

    	$id = $db->insert('tmp_egresos', $egreso);
    	// Guarda Historial
		$data = array(
			'fecha_proceso' => date("Y-m-d"),
			'hora_proceso' => date("H:i:s"), 
			'proceso' => 'c',
			'nivel' => 'm',
			'direccion' => '?/asignacion/asignacion_activar',
			'detalle' => 'Se creó egreso con identificador número ' . $id ,
			'usuario_id' => $_SESSION[user]['id_user']
		);
		$db->insert('sys_procesos', $data) ;

    	$id_egreso = $egreso['id_egreso'];
    	$egresos_detalles = $db->select('*, egreso_id as tmp_egreso_id')->from('inv_egresos_detalles')->where('egreso_id',$id_egreso)->fetch();
    	foreach ($egresos_detalles as $nr => $detalle) {
    		$detalle['tmp_egreso_id'] = $id;
    		$id_detalle = $db->insert('tmp_egresos_detalles', $detalle);
    		// Guarda Historial
    		$data = array(
    			'fecha_proceso' => date("Y-m-d"),
    			'hora_proceso' => date("H:i:s"),
    			'proceso' => 'c',
    			'nivel' => 'm',
    			'direccion' => '?/asignacion/asignacion_activar',
    			'detalle' => 'Se creó egreso detalle con identificador número ' . $id_detalle ,
    			'usuario_id' => $_SESSION[user]['id_user']
    		);
    		$db->insert('sys_procesos', $data) ;
    	}

    	if($id){
    		$db->delete()->from('inv_egresos')->where('id_egreso', $id_egreso)->limit(1)->execute();
    		// Guarda Historial
        	$data = array(
        		'fecha_proceso' => date("Y-m-d"),
        		'hora_proceso' => date("H:i:s"),
        		'proceso' => 'u',
        		'nivel' => 'l',
        		'direccion' => '?/asignacion/asignacion_activar',
        		'detalle' => 'Se elimino egreso con identificador número' . $id_egreso ,
        		'usuario_id' => $_SESSION[user]['id_user']
        	);
        	$db->insert('sys_procesos', $data) ;

			// Elimina los detalles
			$db->delete()->from('inv_egresos_detalles')->where('egreso_id', $id_egreso)->execute();
				// Guarda Historial
        	$data = array(
        		'fecha_proceso' => date("Y-m-d"),
        		'hora_proceso' => date("H:i:s"),
        		'proceso' => 'u',
        		'nivel' => 'l',
        		'direccion' => '?/asignacion/asignacion_activar',
        		'detalle' => 'Se elimino egreso detalle con identificador número' . $id_egreso ,
        		'usuario_id' => $_SESSION[user]['id_user']
        	);
        	$db->insert('sys_procesos', $data) ;

    	}
    }
    $egresos2 = $db->query('SELECT  b.*, b.fecha_egreso as distribuidor_fecha , b.hora_egreso as distribuidor_hora, b.almacen_id as distribuidor_estado, b.almacen_id as distribuidor_id, b.estadoe as estado 
                            FROM inv_asignaciones_clientes a
                            LEFT JOIN inv_egresos b ON a.egreso_id = b.id_egreso
                            WHERE a.distribuidor_id = '.$id_user.' AND b.grupo != "" AND a.estado_pedido = "salida" AND b.estadoe = 2 AND b.fecha_egreso < CURDATE()')->fetch();

    foreach ($egresos2 as $nro2 => $egreso2) {
    	$egreso2['distribuidor_fecha'] = date('Y-m-d');
    	$egreso2['distribuidor_hora'] = date('H:i:s');
    	$egreso2['distribuidor_estado'] = 'ALMACEN';
    	$egreso2['distribuidor_id'] = $id_user;
        $egreso2['estado'] = 2;
    	$id2 = $db->insert('tmp_egresos', $egreso2);

    	$id_egreso = $egreso2['id_egreso'];
    	$egresos_detalles2 = $db->from('inv_egresos_detalles')->where('egreso_id',$egreso2['id_egreso'])->fetch();
    	foreach ($egresos_detalles2 as $nr => $detalle2) {

    	$id = $db->insert('tmp_egresos_detalles', $detalle2);
		// Guarda Historial
		$data = array(
			'fecha_proceso' => date("Y-m-d"),
			'hora_proceso' => date("H:i:s"), 
			'proceso' => 'c',
			'nivel' => 'l',
			'direccion' => '?/asignacion/asignacion_activar',
			'detalle' => 'Se creó egreso detalle con identificador número ' . $id ,
			'usuario_id' => $_SESSION[user]['id_user']
		);
		$db->insert('sys_procesos', $data) ;

    	}
    	if($id2){
    		$db->delete()->from('inv_egresos')->where('id_egreso', $id_egreso)->limit(1)->execute();
    		// Guarda Historial
        	$data = array(
        		'fecha_proceso' => date("Y-m-d"),
        		'hora_proceso' => date("H:i:s"), 
        		'proceso' => 'd',
        		'nivel' => 'l',
        		'direccion' => '?/asignacion/asignacion_activar',
        		'detalle' => 'Se elimino egreso con identificador número' . $id_egreso ,
        		'usuario_id' => $_SESSION[user]['id_user']
        	);
        	$db->insert('sys_procesos', $data) ;

			/////////////////////////////////////////////////////////////////////
            $Lotes=$db->query("SELECT producto_id,lote,unidad_id
                                FROM inv_egresos_detalles AS ed
                                LEFT JOIN inv_unidades AS u ON ed.unidad_id=u.id_unidad
                                WHERE egreso_id='{$id_egreso}'")->fetch();
            foreach($Lotes as $Fila=>$Lote):
                $IdProducto=$Lote['producto_id'];
                $UnidadId=$Lote['unidad_id'];
                $LoteGeneral=explode(',',$Lote['lote']);
                for($i=0;$i<count($LoteGeneral);++$i):
                    $SubLote=explode('-',$LoteGeneral[$i]);
                    $Lot=$SubLote[0];
                    $Cantidad=$SubLote[1];
                    $DetalleIngreso=$db->query("SELECT id_detalle,lote_cantidad
                                                FROM inv_ingresos_detalles
                                                WHERE producto_id='{$IdProducto}' AND lote='{$Lot}'
                                                LIMIT 1")->fetch_first();
                    $Condicion=[
                            'id_detalle'=>$DetalleIngreso['id_detalle'],
                            'lote'=>$Lot,
                        ];
                    $CantidadAux=$Cantidad;
                    $Datos=[
                            'lote_cantidad'=>(strval($DetalleIngreso['lote_cantidad'])+strval($CantidadAux)),
                        ];
                    $db->where($Condicion)->update('inv_ingresos_detalles',$Datos);
                endfor;
            endforeach;
            /////////////////////////////////////////////////////////////////////

			// Elimina los detalles
			$db->delete()->from('inv_egresos_detalles')->where('egreso_id', $id_egreso)->execute();
			// Guarda Historial
        	$data = array(
        		'fecha_proceso' => date("Y-m-d"),
        		'hora_proceso' => date("H:i:s"),
        		'proceso' => 'u',
        		'nivel' => 'l',
        		'direccion' => '?/asignacion/asignacion_activar',
        		'detalle' => 'Se elimino egreso_detalle con identificador número' . $id_egreso ,
        		'usuario_id' => $_SESSION[user]['id_user']
        	);
        	$db->insert('sys_procesos', $data) ;

    	}
    }

	// Obtiene el nuevo estado
    $fecha_actual = date("Y-m-d");
    $nuevo = date("Y-m-d",strtotime($fecha_actual."- 1 days"));
	$estado = ($user['fecha'] == date('Y-m-d')) ? $nuevo : date('Y-m-d');

	// Instancia el user
	$user = array(
		'fecha' => $estado,
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
		'direccion' => '?/asignacion/asignacion_activar',
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