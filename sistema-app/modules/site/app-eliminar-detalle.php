<?php

// Define las cabeceras
header('Content-Type: application/json');

// Verifica la peticion post
if (is_post()) {
    // Verifica la existencia de datos
    if (isset($_POST['id_detalle']) && isset($_POST['id_user'])) {
        require config . '/database.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        try {   


    // 		$data = array(
    // 			'categoria' => $_POST['id_detalle'],
    // 			'descripcion' => $_POST['id_user'],
    // 		);
    
    // 		$db->insert('inv_categorias', $data);



            //Se abre nueva transacci贸n.
            $db->autocommit(false);
            $db->beginTransaction();

            //buscamos al empleado
            $empleado = $db->select('persona_id')
                           ->from('sys_users')
                           ->where('id_user',$_POST['id_user'])
                           ->fetch_first();
                           
            $id_user = $empleado['persona_id'];
            $id_detalle = $_POST['id_detalle'];
            $id_ingreso=0;
    
    
    
            $sw_ingreso=false;
    
            //buscamos el detalle
            $detalle = $db->select('a.*')
                          ->from('inv_egresos_detalles a')
                          ->where('id_detalle',$id_detalle)
                          ->fetch_first();
                          
            $egreso = $db->select('b.*')
                         ->from('inv_egresos b')
                         ->where('id_egreso',$detalle['egreso_id']) 
                         ->fetch_first();
    
            $cliente =$db->select('cl.*')
                         ->from('inv_clientes cl')
                         ->where('id_cliente',$egreso['cliente_id']) 
                         ->fetch_first();
    
            $ingreso_bd = $db->select('id_ingreso')
                             ->from('inv_ingresos b')
                             ->where('b.egreso_id',$detalle['egreso_id'])
                             ->where('tipo',"Devolucion")
                             ->fetch_first();
    
            if($ingreso_bd){
                $id_ingreso=$ingreso_bd['id_ingreso'];
            }    
    
            if($detalle && $egreso){
                
                //EGRESOS
                $egreso['tipo']='no venta';
                $egreso['estadoe']='4';
                $egreso['preventa']='devolucion';
                unset($egreso['id_egreso']);
                
                $id_egreso_nuevo = $db->insert('inv_egresos', $egreso);
                
                $unidad = $detalle['unidad_id'];
                $id_producto = $detalle['producto_id'];
                $cantidad_unidad = $detalle['cantidad'];
                $precio = $detalle['precio'];
                $monto_total =  $egreso['monto_total'];
                $registros =    $egreso['nro_registros'];
    
                /////////////////////////////////////////////////////////////////////
                $Lotes=$db->query(" SELECT cantidad, ed.ingresos_detalles_id, precio, id_detalle
                                    FROM inv_egresos_detalles AS ed
                                    WHERE   lote='".$detalle['lote']."'
                                        AND vencimiento='".$detalle['vencimiento']."'
                                        AND producto_id='".$detalle['producto_id']."'
                                        AND precio='".$detalle['precio']."'
                                        AND egreso_id='".$detalle['egreso_id']."'
                                ")->fetch();
            
                foreach($Lotes as $Fila=>$Lote):
                    
                    $ingresos_detalles_id=$Lote['ingresos_detalles_id'];

                    $DetalleIngreso=$db->query("SELECT *
                                                FROM inv_ingresos_detalles
                                                WHERE id_detalle='".$ingresos_detalles_id."' 
                                              ")->fetch_first();
                    
                    if($id_ingreso==0){   //PRIMER INGRESO REALIZADO
                        $nro_nota_credito = $db->query("select MAX(nro_nota_credito)as nro_nota_credito
                                                                from inv_ingresos i
                                                                ")
                                                        ->fetch_first();
                                
                        $Ingreso_info=$db->query("  SELECT *
                                                    FROM inv_ingresos
                                                    WHERE id_ingreso=".$DetalleIngreso['ingreso_id']." 
                                                    ")->fetch_first();
                        
                        
                        
                        /***********************************************************/
                		$data = array(
                			'categoria' => " WHERE id_ingreso=".$DetalleIngreso['ingreso_id']."",
                			'descripcion' => "antes de crear ingreso",
                		);
                        $db->insert('inv_categorias', $data);
                	    /***********************************************************/
                        
                        
                        
                        if($Ingreso_info){
                            $Ingreso_info['fecha_ingreso']= date('Y-m-d');
                            $Ingreso_info['hora_ingreso']= date('H:i:s');
                            $Ingreso_info['tipo']='Devolucion';
                            $Ingreso_info['descripcion']= '';
                            $Ingreso_info['monto_total']=0;
                            $Ingreso_info['monto_total_descuento']= 0;
                            $Ingreso_info['nro_movimiento']= 0;
                            $Ingreso_info['nro_registros']= 0;
                            $Ingreso_info['empleado_id']=$id_user;
                            $Ingreso_info['plan_de_pagos']= 'no';
                            $Ingreso_info['egreso_id']= $detalle['egreso_id'];
                            $Ingreso_info['tipo_devol']= 'notas';
                            $Ingreso_info['nombre_proveedor']= $cliente['cliente'];
                            $Ingreso_info['importacion_id']= 0;
                            unset($Ingreso_info['id_ingreso']);
                            $Ingreso_info['nro_nota_credito']=($nro_nota_credito['nro_nota_credito']+1);
                                
                            $id_ingreso = $db->insert('inv_ingresos', $Ingreso_info);
                        }
                        
                        /***********************************************************/
                		$data = array(
                			'categoria' => "Si se creo el ingreso",
                			'descripcion' => "se creo ingreso",
                		);
                        $db->insert('inv_categorias', $data);
                	    /***********************************************************/
                        
                    }
                    
                    if($id_ingreso!=0){
                        $DetalleIngreso['cantidad']= $Lote['cantidad'];
                        $DetalleIngreso['lote_cantidad']= $Lote['cantidad'];
                        $DetalleIngreso['precio']= $Lote['precio'];
                        $DetalleIngreso['ingreso_id']= $id_ingreso;
                        $DetalleIngreso['almacen_id']= 0;
                        $DetalleIngreso['asignacion_id']= 0;
    
                        unset($DetalleIngreso['id_detalle']);
            
                        $id = $db->insert('inv_ingresos_detalles', $DetalleIngreso);
    
                            // $Condicion=[
                            //         'id_detalle'=>$DetalleIngreso['id_detalle'],
                            //         'lote'=>$Lot,
                            //     ];
                            // $CantidadAux=$Cantidad;
                            // $Datos=[
                            //         'lote_cantidad'=>(strval($DetalleIngreso['lote_cantidad'])+strval($CantidadAux)),
                            //     ];
                            // $db->where($Condicion)->update('inv_ingresos_detalles',$Datos);
                    
                        $sw_ingreso=true;
                    }
                
                    
                    
                    
                    $db->where('id_detalle',$Lote['id_detalle'])
                       ->update('inv_egresos_detalles',array('egreso_id' => $id_egreso_nuevo) );
                endforeach;
                
                /////////////////////////////////////////////////////////////////////
    
                $egresos_detss = $db->query("  select IFNULL(SUM(cantidad*precio), 0) as monto_total
                                                   from inv_egresos_detalles
                                                   where egreso_id='".$detalle['egreso_id']."'
                                                ")
                                           ->fetch_first();

                if($egresos_detss){
                    $m_total=$egresos_detss['monto_total'];   
                }else{
                    $m_total=0;   
                }
                
                $db->where('id_egreso',$detalle['egreso_id'])
                   ->update('inv_egresos',array('monto_total'=>$m_total));
                   
                /////////////////////////////////////////////////////////////////////
    
                $egresos_detss = $db->query("  select IFNULL(SUM(cantidad*precio), 0) as monto_total
                                                   from inv_egresos_detalles
                                                   where egreso_id='".$id_egreso_nuevo."'
                                                ")
                                           ->fetch_first();

                if($egresos_detss){
                    $m_total=$egresos_detss['monto_total'];   
                }else{
                    $m_total=0;   
                }
                
                $db->where('id_egreso',$id_egreso_nuevo)
                   ->update('inv_egresos',array('monto_total'=>$m_total));
                   
    
    
                $pagos1 = $db->from('inv_pagos')->where('movimiento_id', $detalle['egreso_id'])->where( 'tipo', 'Egreso')->fetch_first();
                





                /******************************************************************/
        		$data = array(
        			'categoria' => "ingreso existe ".$id_ingreso,
        			'descripcion' => "egreso existe ". $detalle['egreso_id'],
        		);
        		$db->insert('inv_categorias', $data);
                /******************************************************************/







                if($id_ingreso!=0){
                    if($pagos1){
                        $nro_pago=$pagos1['id_pago'];
                    }else{
                        $detallePlan = array(
            				'movimiento_id' =>$detalle['egreso_id'], 
            				'interes_pago' =>0, 
            				'tipo'=>'Egreso',
            			);
            			$nro_pago=$db->insert('inv_pagos', $detallePlan);
                    }
                    
            	
            	    /***********************************************************/
            		$data = array(
            			'categoria' => $nro_pago,
            			'descripcion' => "inv_pagos",
            		);
                    $db->insert('inv_categorias', $data);
            	    /***********************************************************/



                    //echo "crear cuota";
                    
                    $detallePlan = array(
        				'pago_id'=>$nro_pago,
        				'fecha' => date('Y-m-d'),
        				'monto' => $m_total,
        				'estado' => 1,
        				'fecha_pago' => date('Y-m-d'),
        				'hora_pago' => date('H:i:s'),
        			    'tipo_pago' => 'DEVOLUCION',
        				'nro_pago' => '0',
        				'nro_cuota'=>0,
        				'empleado_id' => $id_user,
        		    	'deposito'=> "inactivo",
                        'fecha_deposito'=> 0000-00-00,
                        'codigo'=>0,
        		    	'observacion_anulado'=> "",
                        'fecha_anulado'=> "0000-00-00",
        			    'ingreso_id'=> $id_ingreso
                    );
        			$nro_recibo=$db->insert('inv_pagos_detalles', $detallePlan);
        			
        			
        			$data = array(
            			'categoria' => $nro_recibo,
            			'descripcion' => "inv_pagos_detalles",
            		);
                    $db->insert('inv_categorias', $data);


                }

    
    
    
    
                //datos del producto
                // $db->delete()->from('inv_egresos_detalles')->where('id_detalle',$id_detalle)->limit(1)->execute();
    
                // if($registros > 1){
                //     $c = cantidad_unidad($db, $id_producto, $unidad);
                //     $monto_total2 = ($precio * ($cantidad_unidad/$c));
                //     $monto_total = $monto_total - ($precio * ($cantidad_unidad/$c));
                //     // echo json_encode($c);exit();
                //     $db->where('id_egreso',$egreso['id_egreso'])
                //       ->update('inv_egresos',array('monto_total' => $monto_total,'nro_registros' => ($registros-1)));
                // }else{
                //     $db->delete()->from('inv_egresos')
                //       ->where('id_egreso',$detalle['egreso_id'])
                //       ->limit(1)
                //       ->execute();
                       
                //     $monto_total2 = $monto_total;
                // }
                // $egreso['monto_total']= $monto_total2;
                // $egreso['nro_registros']=1;
                // $egreso['distribuidor_fecha'] = date('Y-m-d');
                // $egreso['distribuidor_hora'] = date('H:i:s');
                // $egreso['distribuidor_estado'] = 'DEVUELTO';
                // $egreso['distribuidor_id'] = $id_user;
                // $egreso['estado'] = 3;
    
                // $id = $db->insert('tmp_egresos', $egreso);
    
                // $detalle['tmp_egreso_id'] = $id;
                // $id = $db->insert('tmp_egresos_detalles', $detalle);
    
                if($sw_ingreso){
                    $respuesta = array(
                        'estado' => 's'
                    );
                }else{
                    $respuesta = array(
                        'estado' => 'n'
                        // 'msg' => "  SELECT * FROM inv_ingresos WHERE id_ingreso='".$DetalleIngreso['ingreso_id']."' ",
                        // 'msg2'=> " SELECT cantidad, ed.ingresos_detalles_id, precio
                        //             FROM inv_egresos_detalles AS ed
                        //             WHERE   lote='".$detalle['lote']."'
                        //                 AND vencimiento='".$detalle['vencimiento']."'
                        //                 AND producto_id='".$detalle['producto_id']."'
                        //                 AND precio='".$detalle['precio']."'
                        //                 AND egreso_id='".$detalle['egreso_id']."'
                        //         "
                    );
                }
                echo json_encode($respuesta);
            }else{
                echo json_encode(array('estado' => 'n','msg' => 'otra unidad'));
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
        echo json_encode(array('estado' => 'no llega el id usuario'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'no llega ningun dato'));
}

?>