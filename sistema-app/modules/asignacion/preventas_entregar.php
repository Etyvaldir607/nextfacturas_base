<?php
$id_asignacion = (isset($params[0])) ? $params[0] : 0;
$id_egreso = (isset($params[1])) ? $params[1] : 0;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

try {   
    //Se abre nueva transacciÃ³n.
    $db->autocommit(false);
    $db->beginTransaction();


    /****************************************************************/

    if($id_asignacion==-1){
        $asignacion_datos = array(
            'egreso_id'=>$id_egreso,	
            'distribuidor_id'=> $_user['persona_id'],	
            'fecha_asignacion'=>date('Y-m-d'),	
            'fecha_entrega'=>'0000-00-00',	
            'estado_pedido'=>'salida',	
            'empleado_id'=> $_user['persona_id'],	
            'estado'=>'A',	
            'fecha_hora_salida'=>'0000-00-00',	
            'fecha_hora_liquidacion'=>'00:00:00',	
            'nro_salida'=>-1,	
            'nro_liquidacion'=>0
    	);
    	$id_asignacion=$db->insert('inv_asignaciones_clientes', $asignacion_datos);
    }

    $asignacion = $db->query("select *
                              from inv_asignaciones_clientes ac
                              inner join inv_egresos ON id_egreso=egreso_id
                              where id_asignacion_cliente='".$id_asignacion."'
                                    AND ac.estado='A'")
                    ->fetch_first();

    echo $id_asignacion." - ".json_encode($asignacion); 
    //die();

    if ($asignacion) {

        echo "ingresa a asignacion";
    
        if ($asignacion['estado_pedido'] == 'entregado' && $asignacion['estadoe']==3) {
            // Instancia la variable de notificacion
            set_notification('warning', 'Acci¨®n insatisfactoria!', 'No se puede realizar esta acci¨®n, la preventa ya fue entregada.');
            // Redirecciona a la pagina principal
            return redirect(back());
        
            
        } else {
            $datos_egreso = $db->select('   b.*, b.fecha_egreso as distribuidor_fecha , b.hora_egreso as distribuidor_hora, 
                                            b.almacen_id as distribuidor_estado, b.almacen_id as distribuidor_id, b.estadoe as estado')
                                ->from('inv_egresos b')
                                ->where('b.id_egreso',$asignacion['egreso_id'])
                                ->fetch_first();
            
            if($datos_egreso){
                $db->where('id_egreso',$asignacion['egreso_id'])
                    ->update('inv_egresos',array('estadoe' => 3));
    
    
                echo 'modificar egresios';
                
                
                
                $datos_egreso =  $db->select('  b.*, b.fecha_egreso as distribuidor_fecha , b.hora_egreso as distribuidor_hora, b.almacen_id as distribuidor_estado, 
                                                b.almacen_id as distribuidor_id, b.estadoe as estado')
                                    ->from('inv_egresos b')
                                    ->where('b.id_egreso',$asignacion['egreso_id'])
                                    ->fetch_first();
                
                $datos_egreso['distribuidor_fecha'] = date('Y-m-d');
                $datos_egreso['distribuidor_hora'] = date('H:i:s');
                $datos_egreso['distribuidor_estado'] = 'ENTREGA';
                $datos_egreso['distribuidor_id'] = $asignacion['distribuidor_id'];
                $datos_egreso['estado'] = 3;
                
                $id = $db->insert('tmp_egresos', $datos_egreso);
                
                $egresos_detalles = $db->select('*, egreso_id as tmp_egreso_id')
                                       ->from('inv_egresos_detalles')
                                       ->where('egreso_id',$asignacion['egreso_id'])
                                       ->fetch();
                
                foreach ($egresos_detalles as $nr => $detalle) {
                    $detalle['tmp_egreso_id'] = $id;
                    $db->insert('tmp_egresos_detalles', $detalle);
                }
            
                echo 'insertar tmp egresios';
                
            
                $modificado = array(
                    'estado_pedido' => 'entregado',
                    'fecha_entrega' => date('Y-m-d')
                );
    
                // Actualiza la informacion
                $db->where('id_asignacion_cliente', $id_asignacion)
                   ->update('inv_asignaciones_clientes', $modificado);
                
                $nro_recibo=0;
        
                // si es contado lo quitamos la deuda
                if($datos_egreso['plan_de_pagos']=='no'){
                    $code = $db->select('MAX(inv_pagos_detalles.codigo) as code')
                               ->from('inv_pagos_detalles')
                               ->join('inv_pagos', 'inv_pagos.id_pago = inv_pagos_detalles.pago_id')
                               ->where('inv_pagos.tipo', 'Egreso')
                               ->fetch_first();
                    
                    if(!$code){
                        $code['code']=0;
                    }
                               
                    $pago_d = $db->query("  SELECT * 
                                            FROM inv_pagos as p 
                                            WHERE p.tipo = 'Egreso'
                                            AND p.movimiento_id = " . $asignacion['egreso_id'])->fetch_first();
                    
                    if($pago_d){
                        //echo "existe el pago";
                        $pago_d2 = $db->query("SELECT * 
                                                FROM inv_pagos_detalles as d
                                                LEFT JOIN inv_pagos as p ON d.pago_id = p.id_pago
                                                WHERE p.tipo = 'Egreso'
                                                AND p.movimiento_id = " . $asignacion['egreso_id'])->fetch_first();
                        
                        if($pago_d2){
                            $db->where('id_pago_detalle',$pago_d2['id_pago_detalle'])
                               ->where('pago_id',$pago_d2['id_pago'])
                               ->update('inv_pagos_detalles',array( 'estado' => 1, 
                                                                    'codigo' => $code['code']+1, 
                                                                    'empleado_id'=>$_user['persona_id'],
                                                                    'fecha_pago'=>date('Y-m-d'),
                                                                    'hora_pago'=>date('H:i:s') 
                                                                ));
                            $nro_recibo=$pago_d2['id_pago_detalle'];
                        }else{
                            //echo "crear cuota";
                            $detallePlan = array(
                				'pago_id'=>$pago_d['id_pago'],
                				'fecha' => date('Y-m-d'),
                				'monto' => $datos_egreso['monto_total'],
                				'estado' => 1,
                				'fecha_pago' => date('Y-m-d'),
                				'hora_pago' => date('H:i:s'),
                			    'tipo_pago' => 'Efectivo',
                				'nro_pago' => '',
                				'nro_cuota'=>0,
                				'empleado_id' => $_user['persona_id'],
                		    	'deposito'=> "inactivo",
                                'fecha_deposito'=> 0000-00-00,
                                'codigo'=>$code['code']+1,
                		    	'observacion_anulado'=> "",
                                'fecha_anulado'=> "0000-00-00"
                			);
                			$nro_recibo=$db->insert('inv_pagos_detalles', $detallePlan);
                        }
                    }
                    else{
                        $detallePlan = array(
            				'movimiento_id' =>$asignacion['egreso_id'], 
            				'interes_pago' =>0, 
            				'tipo'=>'Egreso',
            			);
            			$nro_pago=$db->insert('inv_pagos', $detallePlan);
                    
                        //echo "crear cuota";
                        $detallePlan = array(
            				'pago_id'=>$nro_pago,
            				'fecha' => date('Y-m-d'),
            				'monto' => $datos_egreso['monto_total'],
            				'estado' => 1,
            				'fecha_pago' => date('Y-m-d'),
            				'hora_pago' => date('H:i:s'),
            			    'tipo_pago' => 'Efectivo',
            				'nro_pago' => '',
            				'nro_cuota'=>0,
            				'empleado_id' => $_user['persona_id'],
            		    	'deposito'=> "inactivo",
                            'fecha_deposito'=> 0000-00-00,
                            'codigo'=>$code['code']+1,
            		    	'observacion_anulado'=> "",
                            'fecha_anulado'=> "0000-00-00"
            			);
            			$nro_recibo=$db->insert('inv_pagos_detalles', $detallePlan);
                    }
                    set_notification('success', 'Entrega satisfactoria!', 'La asignaci¨®n fue entregada satisfactoriamente.');
                }
            
                // Guarda en el historial
                $data = array(
                    'fecha_proceso' => date("Y-m-d"),
                    'hora_proceso' => date("H:i:s"),
                    'proceso' => 'c',
                    'nivel' => 'l',
                    'direccion' => '?/asignacion/preventas_entregar',
                    'detalle' => 'Se modifico la asignacion cliente con identificador numero ' . $id_asignacion,
                    'usuario_id' => $_SESSION[user]['id_user']
                );
                $db->insert('sys_procesos', $data);
            }
            
            echo "ya acabo";
            
            // Redirecciona a la pagina principal
            //echo back()." / ".$_POST['atras'];
            
            $db->commit();
                    
            $partes=explode("?/asignacion/asignacion_ver/",back() );
            if (count($partes)>1) {
                if ($plan == 'no') {
                    redirect("?/asignacion/asignacion_ver/".$asignacion['distribuidor_id']."/".$datos_egreso['id_egreso']."/".$nro_recibo);
                }else{
                    redirect("?/asignacion/asignacion_ver/".$asignacion['distribuidor_id']."/".$datos_egreso['id_egreso']."/".$nro_recibo);
                }
            }

            $partes=explode("?/asignacion/preventas_listar",back() );
            if (count($partes)>1) {
                if ($plan == 'no') {
                    redirect("?/asignacion/preventas_listar/".$datos_egreso['id_egreso']."/".$nro_recibo);
                }else{
                    redirect("?/asignacion/preventas_listar/".$datos_egreso['id_egreso']."/".$nro_recibo);
                }
            }
            
        }
    } else {
        // Instancia la variable de notificacion
        set_notification('danger', 'AcciÃ³n insatisfactoria!', 'No se puede realizar esta accion, verifique los datos.');
		// Redirecciona a la pagina principal
		redirect(back());
    }
} catch (Exception $e) {
    $status = false;
    $error = $e->getMessage();

    // Instancia la variable de notificacion
    set_notification('danger', 'AcciÃ³n insatisfactoria!', 'No se puede realizar esta accion, verifique los datos.');
	// Redirecciona a la pagina principal
	redirect(back());
}    
?>