<?php

// Define las cabeceras
header('Content-Type: application/json');

// Verifica la peticion post
if (is_post()) {
    
    // Verifica la existencia de datos
    if (isset($_POST['id_user']) && isset($_POST['id_detalle']) && isset($_POST['cantidad']) && isset($_POST['unidad_id']) ) {

        if ( $_POST['cantidad'] > 0 ) {
        
            require config . '/database.php';
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
    
    
    // 		$data = array(
    // 			'categoria' => $_POST['id_detalle']." / ".$_POST['cantidad']." / ".$_POST['unidad_id'],
    // 			'descripcion' => $_POST['id_user'],
    // 		);
    
    // 		$db->insert('inv_categorias', $data);

    
            try {   
                //Se abre nueva transacci贸n.
                $db->autocommit(false);
                $db->beginTransaction();
             
                $empleado = $db->select('persona_id')
                               ->from('sys_users')
                               ->where('id_user',$_POST['id_user'])
                               ->fetch_first();
                               
                $id_user = $empleado['persona_id'];
                
                $id_detalle = $_POST['id_detalle'];
                $cantidad = $_POST['cantidad'];
                $id_unidad = $_POST['unidad_id'];
        
                //buscamos el detalle y sus datos
                $detalle = $db->select('*')
                              ->from('inv_egresos_detalles')
                              ->where('id_detalle',$id_detalle)
                              ->fetch_first();

                $precio = $detalle['precio'];  // recibiendo precio de la app
                              
                $id_egreso = $detalle['egreso_id'];
        
                $egreso = $db->select(' b.*, b.fecha_egreso as distribuidor_fecha , b.hora_egreso as distribuidor_hora, 
                                        b.almacen_id as distribuidor_estado, b.almacen_id as distribuidor_id, b.estadoe as estado')
                             ->from('inv_egresos b')
                             ->where('b.id_egreso',$id_egreso)
                             ->fetch_first();
        
                $cliente =$db->select('cl.*')
                         ->from('inv_clientes cl')
                         ->where('id_cliente',$egreso['cliente_id']) 
                         ->fetch_first();
    
                $detalle_similares = $db->select('*')
                                        ->from('inv_egresos_detalles')
                                        ->where('producto_id',$detalle['producto_id'])
                                        ->where('precio',$detalle['precio'])
                                        ->where('lote',$detalle['lote'])
                                        ->where('vencimiento',$detalle['vencimiento'])
                                        ->where('egreso_id',$detalle['egreso_id'])
                                        ->fetch();
                
                $detalle_similares_2 = $db->query(" select SUM(cantidad)as cantidad_acumulada
                                                    from inv_egresos_detalles
                                                    where producto_id='".$detalle['producto_id']."'
                                                        and precio='".$detalle['precio']."'
                                                        and lote='".$detalle['lote']."'
                                                        and vencimiento='".$detalle['vencimiento']."'
                                                        and egreso_id='".$detalle['egreso_id']."' 
                                                ")
                                            ->fetch_first();



                $cantidad_acumulada = $detalle_similares_2['cantidad_acumulada'];
                
                $id_ingreso=0;
                $id_egreso_nuevo=0;
                
                
                
                
                
                $ingreso_bd = $db->select('id_ingreso')
                                 ->from('inv_ingresos b')
                                 ->where('b.egreso_id',$id_egreso)
                                 ->where('tipo',"Devolucion")
                                 ->fetch_first();
        
                if($ingreso_bd){
                    $id_ingreso=$ingreso_bd['id_ingreso'];
                }
                
                
                
                
                
                
                if ( $cantidad <= $cantidad_acumulada ) {
                    
                    $cantidad = $cantidad_acumulada-$cantidad;

                    foreach ($detalle_similares as $nro => $det_similar) {
                        if($cantidad>0){
                            if($cantidad>$det_similar['cantidad']){
                                $cantidad_devuelta=$det_similar['cantidad'];
                                $cantidad=$cantidad-$det_similar['cantidad'];
                                $det_similar['cantidad']=0;
                            }else{
                                
                                $cantidad_devuelta=$cantidad;
                                $det_similar['cantidad']=$det_similar['cantidad']-$cantidad;
                                $cantidad=0;
                            }
                            
                            $db->where('id_detalle',$det_similar['id_detalle'])
                               ->update('inv_egresos_detalles',array('cantidad' => $det_similar['cantidad']) 
                                                                    //  'precio' => $precio)
                                                                    );
                            
                            
                            
                            
                    //         $data = array(
                    // 			'categoria' => "modificcar canrtidad",
                    // 			'descripcion' => $det_similar['cantidad'],
                    // 		);
                    
                    // 		$db->insert('inv_categorias', $data);

                            
                            
                            
                            /************************************************************************************/
                            
                            $un_ingreso = $db->select('*')
                                             ->from('inv_ingresos_detalles')
                                             ->join('inv_ingresos', 'id_ingreso=ingreso_id', 'inner')
                                             ->where('id_detalle',$det_similar['ingresos_detalles_id'])
                                             ->fetch_first();
                            
                            if($id_ingreso==0){   //PRIMER INGRESO REALIZADO
                                $nro_nota_credito = $db->query("select MAX(nro_nota_credito)as nro_nota_credito
                                                                from inv_ingresos i
                                                                ")
                                                        ->fetch_first();
                                            
                                $ingreso = array(
                                            'fecha_ingreso' => date('Y-m-d'),
                                            'hora_ingreso' => date('H:i:s'),
                                            'tipo' => 'Devolucion',
                                            'nro_movimiento' => 0, // + 1
                                            'descripcion' => "",
                                            'monto_total' => ($det_similar['cantidad']*$un_ingreso['costo']),
                                            'nombre_proveedor' => $cliente['cliente'], 
                                            'nro_registros' => 1,
                                            'transitorio' => 0,
                                            'des_transitorio' => 0,
                                            'plan_de_pagos' => 'no',
                                            'empleado_id' => $id_user, //$_user['persona_id'],
                                            'almacen_id' => $un_ingreso['almacen_id'],
                                            'tipo_pago' => '',
                                            'nro_pago' =>'',
                                            'egreso_id' =>$id_egreso,
                                            'nro_nota_credito'=>($nro_nota_credito['nro_nota_credito']+1)
                                );
                                $id_ingreso = $db->insert('inv_ingresos', $ingreso);
                            }
                            
                            if($id_ingreso!=0){
                                $detalleI = array(
                                            'cantidad' => $cantidad_devuelta,
                                            'costo' => $un_ingreso['costo'],
                                            'vencimiento' => $un_ingreso['vencimiento'],
                                            'dui' => $un_ingreso['dui'],
                                            'lote2' => $un_ingreso['lote2'],
                                            'factura' => $un_ingreso['factura'],
                                            'factura_v' => $un_ingreso['factura_v'],
                                            'contenedor' => $un_ingreso['contenedor'],
                                            'producto_id' => $un_ingreso['producto_id'],
                                            'ingreso_id' => $id_ingreso,
                                            'IVA' => $un_ingreso['IVA'],
                                            'lote' => $un_ingreso['lote'],
                                            'lote_cantidad' => $cantidad_devuelta,
                                            'costo_sin_factura'=>0,
                                            'precio'=>$det_similar['precio']
                                );
                                // Guarda la informacion
                                $id_detalleI = $db->insert('inv_ingresos_detalles', $detalleI);
                            }
                            
                            /************************************************************************************/
                            
                            if($id_egreso_nuevo==0){   //PRIMER INGRESO REALIZADO
                    
                                $egreso = $db->select('b.*')
                                         ->from('inv_egresos b')
                                         ->where('b.id_egreso',$id_egreso)
                                         ->fetch_first();
                    
                                //EGRESOS
                                $egreso['tipo']='no venta';
                                $egreso['estadoe']='4';
                                $egreso['preventa']='devolucion';
                                unset($egreso['id_egreso']);
                                
                                $id_egreso_nuevo = $db->insert('inv_egresos', $egreso);
                            }
                            
                            if($id_egreso_nuevo!=0){
                                $det_similar['cantidad']=$cantidad_devuelta;
                                $det_similar['egreso_id']=$id_egreso_nuevo;
                                unset($det_similar['id_detalle']);
                                
                                // Guarda la informacion
                                $db->insert('inv_egresos_detalles', $det_similar);
                            }
                            
                            /**************************************************************/
                        }
                    }
                
                    /////////////////////////////////////////////////////////////////////
        
                    if(isset($id_egreso)){
                        $egresos_detss = $db->query("  select IFNULL(SUM(cantidad*precio),0) as monto_total
                                                       from inv_egresos_detalles
                                                       where egreso_id='".$id_egreso."'
                                                    ")
                                               ->fetch_first();
    
                        $m_total=$egresos_detss['monto_total'];   
                        
                        $db->where('id_egreso',$id_egreso)
                           ->update('inv_egresos',array('monto_total'=>$m_total));
                    }
                    
                    /////////////////////////////////////////////////////////////////////
        
                    if(isset($id_egreso_nuevo)){
                        $egresos_detss = $db->query("  select IFNULL(SUM(cantidad*precio),0) as monto_total
                                                       from inv_egresos_detalles
                                                       where egreso_id='".$id_egreso_nuevo."'
                                                    ")
                                               ->fetch_first();
    
                        $m_total=$egresos_detss['monto_total'];   
                        
                        $db->where('id_egreso',$id_egreso_nuevo)
                           ->update('inv_egresos',array('monto_total'=>$m_total));
                    
                        /*******************************************************************************************/
                    
                        $pagos1 = $db->from('inv_pagos')->where('movimiento_id', $id_egreso)->where( 'tipo', 'Egreso')->fetch_first();
                        
                        if($id_ingreso!=0){
                            if($pagos1){
                                $nro_pago=$pagos1['id_pago'];
                            }else{
                                $detallePlan = array(
                    				'movimiento_id' =>$id_egreso, 
                    				'interes_pago' =>0, 
                    				'tipo'=>'Egreso',
                    			);
                    			$nro_pago=$db->insert('inv_pagos', $detallePlan);
                            }
                            
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
                        }
                    }
                    
                    /////////////////////////////////////////////////////////////////////////////
                
                    $db->commit();
                    
                    $respuesta = array(
                        'estado' => 's',
                        'cuentas_por_cobrar'=>$egreso['plan_de_pagos'],
                        'monto_total'=>$egreso['monto_total'],
                        'estadoe'=>2
                    );
                    echo json_encode($respuesta);
                } else {
                    // Devuelve los resultados
                    echo json_encode(array('estado' => 'n','msg' => 'la cantidad no puede ser mayor al stock'));
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
            echo json_encode(array('estado' => 'n','msg' => 'cantidad debe ser mayor a cero'));
        }
    } else {
        // Devuelve los resultados
        echo json_encode(array('estado' => 'n','msg' => 'no llego algunos campos'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'n','msg' => 'no llega ningun dato'));
}

?>