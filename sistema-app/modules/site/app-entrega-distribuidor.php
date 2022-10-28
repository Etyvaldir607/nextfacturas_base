<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: application/json; charset=utf-8');
header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
date_default_timezone_set('America/La_Paz');

// Define las cabeceras
header('Content-Type: application/json');

// Verifica la peticion post
if (is_post()) {
    // Verifica la existencia de datos
    if (isset($_POST['id_user'])  &&  isset($_POST['id_egreso']) ) {
        
        require config . '/database.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        try {   
            //Se abre nueva transacci贸n.
            $db->autocommit(false);
            $db->beginTransaction();
    
            $egresos = (isset($_POST['id_egreso'])) ? $_POST['id_egreso'] : array();
            $latitud = (isset($_POST['latitud'])) ? $_POST['latitud'] : '';
            $longitud = (isset($_POST['longitud'])) ? $_POST['longitud'] : '';
            
            $id_user = $_POST['id_user'];
            $user = $db->select('*')->from('sys_users')->where('id_user',$id_user)->fetch_first();
    
            if($egresos){
                $egresos = str_replace('[','',$egresos);
                $egresos = str_replace(']','',$egresos);
                $egresos = str_replace('"','',$egresos);
                $egreso = explode(',',$egresos);
                $egresos = array_unique($egreso);
                foreach ($egresos as $nro => $egreso) {
                    $id_egreso = $egresos[$nro];
                    
                    $datos_egreso = $db->select('b.*, b.fecha_egreso as distribuidor_fecha , b.hora_egreso as distribuidor_hora, b.almacen_id as distribuidor_estado, 
                                                 b.almacen_id as distribuidor_id, b.estadoe as estado')
                                        ->from('inv_egresos b')
                                        ->where('b.id_egreso',$id_egreso)->fetch_first();
                    
                    $datos_temporal = $db->select('b.*')
                                        ->from('tmp_egresos b')
                                        ->where('b.id_egreso',$id_egreso)->fetch_first();
                    
                    if($datos_egreso && !$datos_temporal){
                        $db->where('id_egreso',$id_egreso)
                           ->update('inv_egresos',array('estadoe' => 3));
    
                        $pagos1 = $db->from('inv_pagos')->where('movimiento_id', $id_egreso)->where( 'tipo', 'Egreso')->fetch_first();
    
                        // $db->delete()->from('inv_pagos')->where('movimiento_id', $id_egreso)->where( 'tipo', 'Egreso')->execute();
                        
                        if($pagos1){
                            $id_plan_egreso = $pagos1['id_pago'];
                            $db->delete()->from('inv_pagos_detalles')->where('pago_id', $pagos1['id_pago'])->where('estado', 0)->execute();
                        }else{
                            // Instancia el ingreso
                            $planPago = array(
                                'movimiento_id' => $id_egreso,
                                'interes_pago' => 0,
                                'tipo' => 'Egreso'
                            );
                            $id_plan_egreso = $db->insert('inv_pagos', $planPago);
                        }
                        
                        
                        $code = $db->select('MAX(inv_pagos_detalles.codigo) as code')
                                   ->from('inv_pagos_detalles')
                                   ->join('inv_pagos', 'inv_pagos.id_pago = inv_pagos_detalles.pago_id')
                                   ->where('inv_pagos.tipo', 'Egreso')
                                   ->fetch_first();
            
                        // Genera el plan de pagos
                        $detallePlan = array(
                            'nro_cuota' => 1,
                            'pago_id' => $id_plan_egreso,
                            'fecha' => date('Y-m-d'),
                            'fecha_pago' => date('Y-m-d'),
                            'hora_pago' => date('H:i:s'),
                            
                            'monto' => $datos_egreso['monto_total'],
                            'monto_programado' => $datos_egreso['monto_total'],
                            'tipo_pago' => '', //$tipo_pago
                            'nro_pago' => 0,
                            'codigo'=>($code['code']+1),
                            'empleado_id' => $user['persona_id'],
                            
                            'estado'  => 1,
                            'deposito'  => 'inactivo',
                            'fecha_deposito'  => '0000-00-00', 	
                            'observacion_anulado'  => '',
                            'fecha_anulado'  => '0000-00-00', 
                            
                            'coordenadas'=>$latitud.",".$longitud,
            
                        );
                        // Guarda la informacion
                        $db->insert('inv_pagos_detalles', $detallePlan);
    
    
                        $estado_e = $db->from('inv_egresos')->where('id_egreso', $id_egreso)->fetch_first();
    
                        $datos_egreso['distribuidor_fecha'] = date('Y-m-d');
                        $datos_egreso['distribuidor_hora'] = date('H:i:s');
                        $datos_egreso['distribuidor_estado'] = 'ENTREGA';
                        $datos_egreso['distribuidor_id'] = $user['persona_id'];
                        $datos_egreso['estado'] = 3;
                        $datos_egreso['estadoe'] = 3;
                        $id = $db->insert('tmp_egresos', $datos_egreso);
                        
                        $egresos_detalles = $db->select('*, egreso_id as tmp_egreso_id')
                                               ->from('inv_egresos_detalles')
                                               ->where('egreso_id',$id_egreso)
                                               ->fetch();
                        
                        foreach ($egresos_detalles as $nr => $detalle) {
                            $detalle['tmp_egreso_id'] = $id;
                            $db->insert('tmp_egresos_detalles', $detalle);
                        }
                    }
    
                    $asignacion = $db->from('inv_asignaciones_clientes')
                                     ->where('egreso_id', $id_egreso)
                                     ->where('distribuidor_id', $user['persona_id'])
                                     ->where('estado', 'A')
                                     ->fetch_first();
                    if ($asignacion) {
                        $modificado = array(
                            'estado_pedido' => 'entregado',
                            'fecha_entrega' => date('Y-m-d'),
                            'coordenadas_entrega' => $latitud.",".$longitud,
                        );
                        // Actualiza la informacion
                        $db->where('id_asignacion_cliente', $asignacion['id_asignacion_cliente'])
                           ->update('inv_asignaciones_clientes', $modificado);
                    }
                }
                
                $db->commit();
                $respuesta = array(
                    'estado' => 's',
                    'estadoe' => $estado_e['estadoe'],
                    'total' => $estado_e['monto_total']
                );
                echo json_encode($respuesta);
            }else{
    
                $db->commit();
                // Instancia el objeto
                $respuesta = array(
                    'estado' => 'n', 'msg'=>'no existe ventas'
                );
    
                // Devuelve los resultados
                echo json_encode($respuesta);
            }
        } catch (Exception $e) {
            $status = false;
            $error = $e->getMessage();

            //Se devuelve el error en mensaje json
            echo json_encode(array("estado" => 'n', 'msg'=>$error));

            //se cierra transaccion
            $db->rollback();
        }    
    } else {
        // Devuelve los resultados
        echo json_encode(array('estado' => 'n', 'msg'=>'no llega el id usuario'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'n', 'msg'=>'no llega ningun dato'));
}

?>