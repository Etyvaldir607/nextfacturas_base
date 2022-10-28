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

// Verifica si es una peticion post
if (is_post()) 
{
	if (isset($_POST['id_egreso']) && isset($_POST['monto_total']) && isset($_POST['fecha_pago_inicial']) && isset($_POST['monto_pago_inicial']) && isset($_POST['id_user']) )
    { 
        require config . '/database.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        try {   
            //Se abre nueva transacci贸n.
            $db->autocommit(false);
            $db->beginTransaction();
    
            $m_total = 0;

            $monto_total        = $_POST['monto_total'];
            $nombre_cliente     = $_POST['nombre_cliente'];
            $pago_inicial       = $_POST['monto_pago_inicial'];
            $cuota_dos          = $_POST['monto_cuota_dos'];
            $cuota_tres         = $_POST['monto_cuota_tres'];
    
            $fecha_pago_inicial = trim($_POST['fecha_pago_inicial']);
            $fecha_pago_inicial = date("Y-m-d", strtotime(str_replace('/','-',$fecha_pago_inicial)));
            
            $fecha_cuota_dos    = trim($_POST['fecha_cuota_dos']);
            $fecha_cuota_dos = date("Y-m-d", strtotime(str_replace('/','-',$fecha_cuota_dos)));
            
            $fecha_cuota_tres   = trim($_POST['fecha_cuota_tres']);
            $fecha_cuota_tres = date("Y-m-d", strtotime(str_replace('/','-',$fecha_cuota_tres)));
    
            $latitud = (isset($_POST['latitud'])) ? $_POST['latitud'] : '';
            $longitud = (isset($_POST['longitud'])) ? $_POST['longitud'] : '';
            
                                                                                    //$tipo_pago   = $_POST['tipo_pago'];
                                                                                    //$nro_pago    = $_POST['nro_pago'];
                                                                            
                                                                                                        //        $egreso_id          = $_POST['id_egreso'];
                                                                                                        //$descuentos   = array('2','2');
                                                                                                        //        $empleado_id        = $_POST['id_user'];
                                                                            
            $detalle            = $_POST['motivo'];
            $nro_cuota          = $_POST['nro_cuotas'];
    
            $egresos = (isset($_POST['id_egreso'])) ? $_POST['id_egreso'] : array();
            $id_user = $_POST['id_user'];
            $user = $db->select('*')
                       ->from('sys_users')
                       ->where('id_user',$id_user)
                       ->fetch_first();
            
            $empleado_id = $user['persona_id'];
    
            if($egresos){
                $egresos = str_replace('[','',$egresos);
                $egresos = str_replace(']','',$egresos);
                $egresos = str_replace('"','',$egresos);
                $egreso = explode(',',$egresos);
                $egresos = array_unique($egreso);
                
                foreach ($egresos as $nro => $egreso) {
                    $id_egreso = $egresos[$nro];
                    $datos_egreso = $db->select('b.*, b.fecha_egreso as distribuidor_fecha , 
                                                 b.hora_egreso as distribuidor_hora, b.almacen_id as distribuidor_estado, 
                                                 b.almacen_id as distribuidor_id, b.estadoe as estado')
                                       ->from('inv_egresos b')
                                       ->where('b.id_egreso',$id_egreso)
                                       ->fetch_first();
                                       
                    $datos_temporal = $db->select('b.*')
                                        ->from('tmp_egresos b')
                                        ->where('b.id_egreso',$id_egreso)->fetch_first();
                    
                    if($datos_egreso && !$datos_temporal){
                        
                        if($datos_egreso['plan_de_pagos']=="no"){
                            $egresos_detss = $db->query("  select *
                                                           from inv_egresos_detalles
                                                           inner join inv_productos ON id_producto=producto_id
                                                           where egreso_id='".$id_egreso."'
                                                        ")
                                                   ->fetch();
                                                   
                            foreach ($egresos_detss as $nrs => $detss) {
                                if($detss['precio']!=0){
                                    $db->where('id_detalle',$detss['id_detalle'])
                                       ->update('inv_egresos_detalles',array('precio' => $detss['precio_actual']));
                                    
                                    $m_total=$m_total+($detss['precio_actual']*$detss['cantidad']);   
                                }
                            }
                        }else{
                            $egresos_detss = $db->query("  select SUM(cantidad*precio)as monto_total
                                                           from inv_egresos_detalles
                                                           where egreso_id='".$id_egreso."'
                                                        ")
                                                   ->fetch_first();

                            $m_total=$egresos_detss['monto_total'];   
                        }
                        
                        $db->where('id_egreso',$id_egreso)
                           ->update('inv_egresos',array('estadoe' => 3, 'plan_de_pagos' => 'si', 'monto_total'=>$m_total));
                           
                        $estado_e = $db->from('inv_egresos')
                                       ->where('id_egreso', $id_egreso)
                                       ->fetch_first();
    
                        $datos_egreso['distribuidor_fecha'] = date('Y-m-d');
                        $datos_egreso['distribuidor_hora'] = date('H:i:s');
                        $datos_egreso['distribuidor_estado'] = 'ENTREGA';
                        $datos_egreso['plan_de_pagos'] = 'si';
                        $datos_egreso['distribuidor_id'] = $user['persona_id'];
                        $datos_egreso['estado'] = 3;
                        $datos_egreso['estadoe'] = 3;
                        $datos_egreso['monto_total'] = $m_total;
                        
                        $id = $db->insert('tmp_egresos', $datos_egreso);
                        
                        $egresos_detalles = $db->select('*, egreso_id as tmp_egreso_id')
                                               ->from('inv_egresos_detalles')
                                               ->where('egreso_id',$id_egreso)
                                               ->fetch();
                                               
                        foreach ($egresos_detalles as $nr => $detalle) {
                            $detalle['tmp_egreso_id'] = $id;
                            $db->insert('tmp_egresos_detalles', $detalle);
                        }
                        
                        $asignacion = $db->from('inv_asignaciones_clientes')
                                         ->where('egreso_id', $id_egreso)
                                         ->where('distribuidor_id', $empleado_id)
                                         ->where('estado', 'A')
                                         ->fetch_first();
                        
                        if ($asignacion) {
                            $modificado = array(
                                'estado_pedido' => 'entregado',
                                'fecha_entrega' => date('Y-m-d'),
                                'coordenadas_entrega' => $latitud.",".$longitud,
                            );
                            // Actualiza la informacion
                            $db->where('id_asignacion_cliente', $asignacion['id_asignacion_cliente'])->update('inv_asignaciones_clientes', $modificado);
                        }
                    }
                    $fechas = array($fecha_pago_inicial, $fecha_cuota_dos, $fecha_cuota_tres);
                    $cuotas = array($pago_inicial,$cuota_dos,$cuota_tres);
                    $monto_total_cuotas = $pago_inicial+$cuota_dos+$cuota_tres;
                    
                    //if($ monto_total<=$monto_total_cuotas){

                        $pagos1 = $db->from('inv_pagos')->where('movimiento_id', $estado_e['id_egreso'])->where( 'tipo', 'Egreso')->fetch_first();
    
                        // $db->delete()->from('inv_pagos')->where('movimiento_id', $estado_e['id_egreso'])->where( 'tipo', 'Egreso')->execute();
                        $db->delete()->from('inv_pagos_detalles')->where('pago_id', $pagos1['id_pago'])->where('estado', 0)->execute();
    
    
                        $fecha_format=(isset($fechas[0])) ? $fechas[0]: "00-00-0000";
                        $vfecha=explode("-",$fecha_format);
                        $fecha_format=$vfecha[0]."-".$vfecha[1]."-".$vfecha[2];
    
    
                        if($pagos1){
                            $ingreso_id_plan = $pagos1['id_pago'];
                            $db->delete()->from('inv_pagos_detalles')->where('pago_id', $pagos1['id_pago'])->where('estado', 0)->execute();
                        }else{
                            // Instancia el ingreso
                            $ingresoPlan = array (
                                'movimiento_id' => $id_egreso,
                                'tipo' => 'Egreso'
                            );
                            // Guarda la informacion del ingreso general
                            $ingreso_id_plan = $db->insert('inv_pagos', $ingresoPlan);
                        }
                        
    
                                                                                        // //inserta en cronograma
                                                                                        // $datos_c = array(
                                                                                        //     'fecha' => $fecha_format,
                                                                                        //     'periodo' =>'trimestral',
                                                                                        //     'detalle' => $detalle,
                                                                                        //     'monto'=> $ monto_total
                                                                                        // );
                                                                    
                                                                                        // $id_cronograma = $db->insert('cronograma', $datos_c);
                                                                    
                        if($cuotas[0]!=''){
                            $fecha_format = (isset($fechas[0]) && $fechas[0]!='') ? $fechas[0]: "00-00-0000";
                            $vfecha       = explode("-",$fecha_format);
                            $fecha_format = $vfecha[0]."-".$vfecha[1]."-".$vfecha[2];
                            $detallePlan = array(
                                'pago_id'   => $ingreso_id_plan,
                                'fecha'     => $fecha_format,
                                'monto'     => (isset($cuotas[0])) ? $cuotas[0]: 0,
                                'monto_programado'     => (isset($cuotas[0])) ? $cuotas[0]: 0,
                                'estado'    => 0,
                                
                                'fecha_pago'=> '0000-00-00',
                                'hora_pago'=> '00:00:00',
                                'tipo_pago' => 'efectivo',
                                'nro_cuota' => 1,
                                'nro_pago' => 0,
                                
                                'empleado_id' => 0,
                                'deposito'  => 'inactivo',
                                'fecha_deposito'  => '0000-00-00', 	
                                'codigo' => 0,
                                'observacion_anulado'  => '',
                                
                                'fecha_anulado'  => '0000-00-00', 
                                'coordenadas'  => '', 
                                'ingreso_id'  => '0', 
                            );
                            $db->insert('inv_pagos_detalles', $detallePlan);
                        }
                        
                        
                        if($cuotas[1]!='' && $cuotas[1]!=0){
                            $fecha_format = (isset($fechas[1]) && $fechas[1]!='') ? $fechas[1]: "00-00-0000";
                            $vfecha       = explode("-",$fecha_format);
                            $fecha_format = $vfecha[0]."-".$vfecha[1]."-".$vfecha[2];
                            $detallePlan = array(
                                'pago_id'   => $ingreso_id_plan,
                                'fecha'     => $fecha_format,
                                'monto'     => (isset($cuotas[0])) ? $cuotas[0]: 0,
                                'monto_programado'     => (isset($cuotas[0])) ? $cuotas[0]: 0,
                                'estado'    => 0,

                                'fecha_pago'=> '0000-00-00',
                                'hora_pago'=> '00:00:00',
                                'tipo_pago' => 'efectivo',
                                'nro_cuota' => 1,
                                'nro_pago' => 0,
                                
                                'empleado_id' => 0,
                                'deposito'  => 'inactivo',
                                'fecha_deposito'  => '0000-00-00', 	
                                'codigo' => 0,
                                'observacion_anulado'  => '',
                                
                                'fecha_anulado'  => '0000-00-00', 
                                'coordenadas'  => '', 
                                'ingreso_id'  => '0', 
                            );
                            $db->insert('inv_pagos_detalles', $detallePlan);
                        }
                        
                        
                        if($cuotas[2]!='' && $cuotas[2]!=0){
                            $fecha_format = (isset($fechas[2]) && $fechas[2]!='') ? $fechas[2]: "00-00-0000";
                            $vfecha       = explode("-",$fecha_format);
                            $fecha_format = $vfecha[0]."-".$vfecha[1]."-".$vfecha[2];
                            $detallePlan = array(
                                'pago_id'   => $ingreso_id_plan,
                                'fecha'     => $fecha_format,
                                'monto'     => (isset($cuotas[0])) ? $cuotas[0]: 0,
                                'monto_programado'     => (isset($cuotas[0])) ? $cuotas[0]: 0,
                                'estado'    => 0,
                                
                                'fecha_pago'=> '0000-00-00',
                                'hora_pago'=> '00:00:00',
                                'tipo_pago' => 'efectivo',
                                'nro_cuota' => 1,
                                'nro_pago' => 0,
                                
                                'empleado_id' => 0,
                                'deposito'  => 'inactivo',
                                'fecha_deposito'  => '0000-00-00', 	
                                'codigo' => 0,
                                'observacion_anulado'  => '',
                                
                                'fecha_anulado'  => '0000-00-00', 
                                'coordenadas'  => '', 
                                'ingreso_id'  => '0', 
                            );
                            $db->insert('inv_pagos_detalles', $detallePlan);
                        }
                    //}
                }
                // $respuesta = array(
                //     'estado' => 's',
                //     'estadoe' => $estado_e['estadoe']
                // );
                if ($estado_e['estadoe'] == '3') {
                    $estadoe = 3;
                } else {
                    $estadoe = $estado_e['estadoe'];
                }
    
                $db->commit();
                
                $respuesta = array(
                    'estado' => 's',
                    'monto_total' => $m_total,
                    'estadoe' => $estadoe
                );
                echo json_encode($respuesta);
            }else{
    
                $db->rollback();
                
                // Instancia el objeto
                $respuesta = array(
                    'estado' => 'n', 'msg'=>'no exite ventas'
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
        // Instancia el objeto
        $respuesta = array(
            'estado' => 'n', 'msg'=>'no llego algun dato'
        );

        // Devuelve los resultados
        echo json_encode($respuesta);
	}
} else {
    echo json_encode(array('estado' => 'n', 'msg'=>'no llega ningun dato'));
}
?>