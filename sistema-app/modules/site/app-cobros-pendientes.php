<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: application/json; charset=utf-8');
header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
date_default_timezone_set('America/La_Paz');

header('Content-Type: application/json');

// Verifica la peticion post
if (is_post()) {
    // Verifica la existencia de datos
    if (isset($_POST['id_user']) && isset($_POST['id_cliente'])) {
        // Importa la configuracion para el manejo de la base de datos
        require config . '/database.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        try {   
            //Se abre nueva transacci贸n.
            $db->autocommit(false);
            $db->beginTransaction();
         
            $cliente_id = $_POST['id_cliente'];
    
            // Obtiene los usuarios que cumplen la condicion
            $usuario = $db->from('sys_users')
                          ->join('sys_empleados','persona_id = id_empleado')
                          ->where('id_user',$_POST['id_user'])
                          ->fetch_first();
                          
            $emp = $usuario['id_empleado'];
    
            //buscar si es vendedor o distribuidor
            $deudas = $db->query("  select  a.id_egreso, a.fecha_habilitacion as fecha_egreso, 
                                            ifnull(P.id_pago,-1)as id_pago, 
                                            ifnull(P.pendiente,-1)as pendiente, 
                                            D.monto_total, a.tipo, a.nro_nota
                                    from inv_egresos a
                                    
                                    left join (
                                                SELECT ifnull( SUM(d.cantidad*d.precio),0) as monto_total, d.egreso_id
                                                from inv_egresos_detalles d
                                                group by d.egreso_id
                                               )D on D.egreso_id = a.id_egreso
                                    
                                    left join (
                                                SELECT ifnull( SUM(c.monto),0) as pendiente, b.movimiento_id, b.id_pago
                                                FROM inv_pagos b 
                                                left join inv_pagos_detalles c ON (c.pago_id = b.id_pago AND c.estado=1)
                                                group by b.movimiento_id
                                                )P ON P.movimiento_id = a.id_egreso
                                    
                                    where   a.cliente_id='".$cliente_id."' 
                                            AND (tipo='Venta' OR tipo='Preventa')
                                            AND (preventa='habilitado')
                                    
                                    ORDER BY fecha_egreso DESC
                                    ")
                        ->fetch();
                        
            //        AND (pendiente is null OR (pendiente!=D.monto_total && (pendiente+0.001)<D.monto_total))
                                    
                                    
            foreach($deudas as $nro => $deuda){
                $egreso=explode(" ",$deuda['fecha_egreso']);
                $subegreso=explode("-",$egreso[0]);
                $deudas[$nro]['fecha_egreso'] = $subegreso[2]."/".$subegreso[1]."/".$subegreso[0];
    
                if($deuda['id_pago']==-1){
                    $detallePlan = array(
                                        'movimiento_id'	=> $deuda['id_egreso'],
                                        'interes_pago'  => 0,	
                                        'tipo'          =>'Egreso'
                        			);
                    $deudas[$nro]['id_pago']=$db->insert('inv_pagos', $detallePlan);
                }
                


                if($deuda['pendiente']==-1){
                    $deudas[$nro]['pendiente']=$deuda['monto_total'];
                }
                else{
                    $deudas[$nro]['pendiente']=$deuda['monto_total']-$deuda['pendiente'];
                }
                
                //$deudas[$nro]['pendiente']=number_format($deudas[$nro]['pendiente'],2,'.','');

                $deudas[$nro]['nro_cuotas']=1;
                
                $detalles = $db->query("select *
                                        from inv_pagos_detalles
                                        where pago_id='".$deudas[$nro]['id_pago']."' and estado=1
                                        ORDER BY fecha_pago ASC
                                        ")
                                    ->fetch();
                                
                foreach($detalles as $nro2 => $detalle){
                    $detalles[$nro2]['id_cliente'] = $cliente_id;

                    $vec=explode("-", $detalle['fecha_pago']);
                    $detalles[$nro2]['fecha_pago']=$vec[2]."/".$vec[1]."/".$vec[0];
                }
                
                $detalles2 = $db->query("select *
                                        from inv_pagos_detalles
                                        where pago_id='".$deudas[$nro]['id_pago']."' and estado=0
                                        ORDER BY fecha ASC
                                        ")
                                    ->fetch_first();
                                
                if($detalles2){
                    if($deudas[$nro]['pendiente']>0.001){
                        if(!$detalles){
                            $detalles = array();
                            $detalles[0] = $detalles2;
                            $detalles[0]['monto'] = number_format($deudas[$nro]['pendiente'],2,'.','');
                            $detalles[0]['fecha_pago'] = "--";
                            $detalles[0]['id_cliente'] = $cliente_id;
                        }else{
                            $detalles[$nro2+1] = $detalles2;
                            $detalles[$nro2+1]['monto'] = number_format($deudas[$nro]['pendiente'],2,'.','');
                            $detalles[$nro2+1]['fecha_pago'] = "--";
                            $detalles[$nro2+1]['id_cliente'] = $cliente_id;
                        }
                    }
                }else{
                    if($deudas[$nro]['pendiente']>0.001){
                        
                        if(!$detalles){
                            $detalles = array();
                            $nro2=-1;
                        }
                        
                        $det = array(
            				'pago_id'=>$deudas[$nro]['id_pago'],
            				'fecha' => '0000-00-00',
            				'monto' => number_format($deudas[$nro]['pendiente'],2,'.',''),
            				'estado' => 0,
            				'fecha_pago' => '0000-00-00',
            				'hora_pago' => '00:00:00',
            			    'tipo_pago' => '',
            				'nro_pago' => '',
            				'nro_cuota'=>0,
            				'empleado_id' => 0,
            		    	'deposito'=> "inactivo",
                            'fecha_deposito'=> 0000-00-00,
                            'codigo'=>0,
            		    	'observacion_anulado'=> "",
                            'fecha_anulado'=> "0000-00-00"
            			);
            			$idxxx=$db->insert('inv_pagos_detalles', $det);
        
        
                        
        
                        $detalles[$nro2+1]=$det;
                        $detalles[$nro2+1]['id_pago_detalle'] = $idxxx;
            			$detalles[$nro2+1]['id_cliente'] = $cliente_id;
                        $detalles[$nro2+1]['fecha_pago'] = "--";
                    
            			//$detalles[$nro2+1] = $detallePlan;
                    }
                }
                $deudas[$nro]['detalle'] = $detalles;
            }
    
            $db->commit();
            
            if(count($deudas)>0){
                $respuesta = array(
                    'estado' => 's',
                    'deudas' => $deudas
                );
            }else{
                $respuesta = array(
                    'estado' => 'n',
                    'msg' => 'no existen deudas'
                );
            }
            echo json_encode($respuesta);
        
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
        echo json_encode(array('estado' => 'n','msg' => 'no llega el id usuario'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'n','msg'  => 'no llega ningun dato'));
}

?>