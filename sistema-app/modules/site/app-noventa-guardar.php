<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: application/json; charset=utf-8');
header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
date_default_timezone_set('America/La_Paz');


if(is_post()) {
    if (isset($_POST['id_cliente']) && isset($_POST['id_user']) && isset($_POST['latitud']) && isset($_POST['longitud'])) {

        require config . '/database.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        $usuario = trim($_POST['username']);
        $password = trim($_POST['password']);

        $imei = trim($_POST['imei']);
        $model = trim($_POST['model']);

        $rol = $db->select('a.rol_id, a.persona_id, b.fecha')
                  ->from('sys_users a')
                  ->join('sys_empleados b','a.persona_id = b.id_empleado')
                  ->where('a.id_user',$_POST['id_user'])
                  ->fetch_first();
                  
        $id_user = $rol['persona_id'];
        
        if($rol['fecha'] == date('Y-m-d')){
            if( $rol['rol_id'] != 4 ){
         
                try {   
                    $nit = $_POST['nit'];
                    $nombre_cliente = $_POST['cliente'];
                    $id_cliente = $_POST['id_cliente'];
                    $id_user = $_POST['id_user'];
                    $ubicacion = $_POST['ubicacion'];
                    $observacion = $_POST['prioridad'];
                    $hora_ini = $_POST['hora_inicial'];
                    $hora_fin = $_POST['hora_final'];
                    $motivo = $_POST['motivo_id'];
    
    
                    $direccion =    (isset($_POST['direccion'])) ? trim($_POST['direccion']) : '';         
                    $latitud =      (isset($_POST['latitud'])) ? trim($_POST['latitud']) : '';          
                    $longitud =     (isset($_POST['longitud'])) ? trim($_POST['longitud']) : '';         
                
    
                    $horaInicio = new DateTime($hora_fin);
                    $horaTermino = new DateTime($hora_ini);
    
                    $duracion = $horaInicio->diff($horaTermino);
                    $duracion = $duracion->format('%H:%I:%s');
    
                    $empleado = $db->select('persona_id')->from('sys_users')->where('id_user',$id_user)->fetch_first();
                    $id_empleado = $empleado['persona_id'];
    
                    //buscamos la ruta que tiene
                    //$ruta = $db->select('id_ruta')->from('gps_rutas')->where('empleado_id',$id_empleado)->where('dia',date('w'))->fetch_first();
    
    
    
    
    
                    $cliente = $db->select('*')->from('inv_clientes')->where('id_cliente',$id_cliente)->fetch_first();
                    $ubicacion = $cliente['ubicacion'];
                    $ubicaciones = explode(',',$ubicacion);
    
                    $distancia=distanceCalculation($ubicaciones[0], $ubicaciones[1], $latitud, $longitud, 'km', 2);
    
                    if($distancia<100){
                        // Obtiene el empleado
                        $empleadox = $db->query("   select id_cliente_grupo 
                                                    from inv_clientes_grupos
                                                    WHERE vendedor_id='".$id_empleado."'
                                                 ")->fetch_first();
                        
                        $egreso = array(
                            'fecha_egreso' => date('Y-m-d'),
                            'hora_egreso' => date('H:i:s'),
                            'fecha_habilitacion' => date('Y-m-d'),
                            'fecha_factura' => '0000-00-00',
                            'tipo' => 'NO VENTA',
                            'tipo_inicial' => 'NO VENTA',
                            'distribuir' => 'N',
                            'provisionado' => 'N',
                            'descripcion' => 'No se realizo ninguna venta',
                            'nro_nota' => 0,
                            'nro_factura' => 0,
                            'nro_movimiento' => 0,
                            'nro_autorizacion' => 0,
                            'codigo_control' => 0,
                            'fecha_limite' => '0000-00-00',
                            'monto_total' => 0,
                            'monto_total_descuento' => 0,
                            
                            'tipo_pago' => '',
                            'nro_pago' => '',
                            
                            'cliente_id' => $id_cliente,
                            'nit_ci' => $nit,
                            'nombre_cliente' => strtoupper($nombre_cliente),
                            
                            'nro_registros' => 0,
                            'estadoe' => 1,
                            'coordenadas' => $latitud.",".$longitud,
                            //'coordenadas' => $ubicacion,
                            'observacion' => '',
                            'dosificacion_id' => 0,
                            'almacen_id' => 0,
                            'almacen_id_s' => 0,
                            
                            'empleado_id' => $id_empleado,
                            'vendedor_id' => $id_empleado,
                            'codigo_vendedor' => $empleadox['id_cliente_grupo'],
                            'motivo_id' => '',
                            'visita' => $motivo,
                            'duracion' => $duracion,
                            'ruta_id' => 0,
                            
                         	'estado' => 0,
                         	'plan_de_pagos' => 'no',
                         	'ingreso_id' => 0,
                         	'preventa' => '',
                         	'fecha_devolucion' => '0000-00-00 00:00:00',
                         	'factura' => 'no',
                            'ubicacion'=>$direccion,
                        );
        
                        $id = $db->insert('inv_egresos',$egreso);
        
                        if($id){
                            $db->commit();
                            $respuesta = array(
                                'estado' => 's', 
                                'estadoe' => 1
                            );
                            echo json_encode($respuesta);
                            
                        }else{
                            $db->commit();
                            echo json_encode(array('estado' => 'no guardo'));
                        }
                    }else{
                        $db->commit();
                        $respuesta = array(
                            'estado' => 'n', 
                            'msg' => 'cliente no esta cerca '.$distancia.' metros'
                        );
                        
                        /******************************************************/
                        $egreso = array(
                            'categoria' => $distancia,
                            'descripcion' => date("H:i:s")." --- ".$ubicaciones[0].", ".$ubicaciones[1]." - ".$latitud.", ".$longitud,
                            
                        );
                        $id = $db->insert('inv_categorias',$egreso);
                        /******************************************************/
                        
                        echo json_encode($respuesta);
                    }
            
                } catch (Exception $e) {
                    $status = false;
                    $error = $e->getMessage();
        
                    //Se devuelve el error en mensaje json
                    echo json_encode(array("estado" => 'n', 'msg'=>$error));
        
                    /******************************************************/
                    $egreso = array(
                        'categoria' => date("H:i:s"),
                        'descripcion' => $error,
                    );
                    $id = $db->insert('inv_categorias',$egreso);
                    /******************************************************/
                        
                    //se cierra transaccion
                    $db->rollback();
                }
            }else{
                try {   
                    
                    if (isset($_POST['id_egreso']) && isset($_POST['motivo_id'])) {
                        
                        // latitud
                        // longitud
                        // id_cliente
                        // id_user
                        // nit
                        // cliente
                        // ubicacion // del cliente
                        // hora_inicial 
                        // hora_final
                        
                        $asigx = $db->from('inv_asignaciones_clientes')
                                    ->where('egreso_id', $_POST['id_egreso'])
                                    ->where('estado', "A")
                                    ->fetch_first();
            
                        $id_asignacion = $asigx['id_asignacion_cliente'];
                        $id_motivo = $_POST['motivo_id'];

                        $asignacion = $db->from('inv_asignaciones_clientes')->where('id_asignacion_cliente', $id_asignacion)->fetch_first();
                
                        // Obtenemos el egreso de la asignacion
                        $egreso = $db->from('inv_egresos')->where('id_egreso', $asignacion['egreso_id'])->fetch_first();
                
                        // Verificamos qu el egreso no este registrado en el temporal
                        $verifica = $db->select('*')
                                        ->from('tmp_egresos')
                                        ->where('distribuidor_estado','NO ENTREGA')
                                        ->where('id_egreso',$asignacion['egreso_id'])
                                        ->fetch_first();
                        if(!$verifica){
                            // modificamos el egreso
                            $eg = $asignacion['egreso_id'];
                            $db->query("UPDATE inv_egresos SET  `tipo` = 'No venta', 
                                                                `estadoe` = 4, 
                                                                `motivo_id` = '$id_motivo', 
                                                                `preventa` = 'habilitado', 
                                                                `ingreso_id` = 0 
                                        WHERE id_egreso = '$eg'")->execute();
                        }
                
                        $db->where('id_asignacion_cliente', $id_asignacion)
                           ->update('inv_asignaciones_clientes', array('estado_pedido' => 'reasignado'));
                
                        
                        $db->commit();
                        $respuesta = array(
                            'estado' => 's', 
                            'estadoe' => 4
                        );
                        echo json_encode($respuesta);                
                    } else {
                        $db->commit();
                        echo json_encode(array('estado' => 'n', 'msg'=>'nota no entregada'));
                    }
                    
                    
                    
                    /*$verifica = $db->select('*')
                                   ->from('tmp_egresos')
                                   ->where('distribuidor_estado','NO ENTREGA')
                                   ->where('cliente_id',$_POST['id_cliente'])
                                   ->fetch_first();
                    if(!$verifica){
                        $motivo = $_POST['motivo_id'];
                        $id_cliente = $_POST['id_cliente'];
    
                        $fecha = $db->select('a.fecha')
                                    ->from('sys_empleados a')
                                    ->join('inv_egresos b','a.id_empleado = b.empleado_id')
                                    ->where('b.cliente_id',$id_cliente)
                                    ->where('b.estadoe',2)
                                    ->fetch_first();
    
                        if($fecha['fecha'] == date('Y-m-d')){
                            $egresos = $db->select('b.*, b.fecha_egreso as distribuidor_fecha , b.hora_egreso as distribuidor_hora, b.almacen_id as distribuidor_estado, b.almacen_id as distribuidor_id, b.estadoe as estado')
                                          ->from('inv_egresos b')
                                          ->where('b.fecha_egreso <=',date('Y-m-d'))
                                          ->where('b.cliente_id',$_POST['id_cliente'])
                                          ->where('b.estadoe',2)
                                          ->fetch();
                        }
                        else{
                            $egresos = $db->select('b.*, b.fecha_egreso as distribuidor_fecha , b.hora_egreso as distribuidor_hora, b.almacen_id as distribuidor_estado, b.almacen_id as distribuidor_id, b.estadoe as estado')
                                          ->from('inv_egresos b')
                                          ->where('b.fecha_egreso <',date('Y-m-d'))
                                          ->where('b.cliente_id',$_POST['id_cliente'])
                                          ->where('b.estadoe',2)
                                          ->fetch();
                        }
    
                        foreach($egresos as $nro2 => $egreso){
                            $egreso['distribuidor_fecha'] = date('Y-m-d');
                            $egreso['distribuidor_hora'] = date('H:i:s');
                            $egreso['distribuidor_estado'] = 'NO ENTREGA';
                            $egreso['motivo_id'] = $_POST['motivo_id'];
                            $egreso['distribuidor_id'] = $id_user;
                            $egreso['estado'] = 3;
                            $egreso['estadoe'] = 4;
                            $id_egreso = $egreso['id_egreso'];
    
                            $id = $db->insert('tmp_egresos', $egreso);
                            $db->delete()
                               ->from('inv_egresos')
                               ->where('id_egreso',$id_egreso)
                               ->limit(1)
                               ->execute();
    
                            $detalles = $db->select('a.*, a.id_detalle as tmp_egreso_id')
                                           ->from('inv_egresos_detalles a')
                                           ->where('a.egreso_id',$id_egreso)
                                           ->fetch();
    
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
                            
                            foreach($detalles as $nro => $detalle){
                                $detalle['tmp_egreso_id'] = $id;
                                $id_detalle = $detalle['id_detalle'];
                                $db->insert('tmp_egresos_detalles', $detalle);
                                $db->delete()->from('inv_egresos_detalles')->where('id_detalle',$id_detalle)->limit(1)->execute();
                            }
                        }
                        $db->commit();
                        $respuesta = array(
                            'estado' => 's', 
                            'estadoe' => 4
                        );
                        echo json_encode($respuesta);
                    }else{
                        $db->commit();
                        echo json_encode(array('estado' => 'n', 'msg'=>'nota no entregada'));
                    }*/
                } catch (Exception $e) {
                    $status = false;
                    $error = $e->getMessage();
        
                    //Se devuelve el error en mensaje json
                    echo json_encode(array("estado" => 'n', 'msg'=>$error));
        
                    //se cierra transaccion
                    $db->rollback();
                }
            }
        }else{
            echo json_encode(array('estado' => 'Inactivo'));
        }
//
    } else {
        /******************************************************/
        $egreso = array(
            'categoria' => date("H:i:s"),
            'descripcion' => "no llego uno de los datos",
        );
        $id = $db->insert('inv_categorias',$egreso);
        /******************************************************/
        
        echo json_encode(array('estado' => 'no llego uno de los datos'));
    }
}else{
    /******************************************************/
    $egreso = array(
        'categoria' => date("H:i:s"),
        'descripcion' => "no llego uno de los datos",
    );
    $id = $db->insert('inv_categorias',$egreso);
    /******************************************************/

    echo json_encode(array('estado' => 'no llego uno de los datos'));
}






function distanceCalculation($point1_lat, $point1_long, $point2_lat, $point2_long, $unit = 'km', $decimals = 2) {
	// Cálculo de la distancia en grados
	$degrees = rad2deg(acos((sin(deg2rad($point1_lat))*sin(deg2rad($point2_lat))) + (cos(deg2rad($point1_lat))*cos(deg2rad($point2_lat))*cos(deg2rad($point1_long-$point2_long)))));

	// Conversión de la distancia en grados a la unidad escogida (kilómetros, millas o millas naúticas)
	switch($unit) {
		case 'km':
			$distance = $degrees * 111.13384*1000; // 1 grado = 111.13384 km, basándose en el diametro promedio de la Tierra (12.735 km)
			break;
		case 'mi':
			$distance = $degrees * 69.05482; // 1 grado = 69.05482 millas, basándose en el diametro promedio de la Tierra (7.913,1 millas)
			break;
		case 'nmi':
			$distance =  $degrees * 59.97662; // 1 grado = 59.97662 millas naúticas, basándose en el diametro promedio de la Tierra (6,876.3 millas naúticas)
	}
	return round($distance, $decimals);
}

?>