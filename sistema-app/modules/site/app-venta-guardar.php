<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: application/json; charset=utf-8');
header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
date_default_timezone_set('America/La_Paz');

if(is_post()) {
    if (isset($_POST['id_cliente']) && isset($_POST['id_user'])) {
        require config . '/database.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
        
        try {   
            //Se abre nueva transacci贸n.
            $db->autocommit(false);
            $db->beginTransaction();
        
            $id_empleado = $db->query(" select persona_id 
                                        from sys_users
                                        WHERE id_user='".$_POST['id_user']."'
                                    ")->fetch_first();

            // $almacenes = $db->query("   select almacen_id 
            //                             from inv_users_almacenes
            //                             WHERE user_id='".$_POST['id_user']."'
            //                          ")->fetch_first();

            $estado_precio =   (isset($_POST['estado_precio'])) ? trim($_POST['estado_precio']) : 'Contado';  
            if($estado_precio == 'Credito'){
                $tipo_pago = 'si';
                $estadoe = 2;
                $plan = "si";
            }else{
                $tipo_pago = 'no';
                $estadoe = 0;
                $plan = "no";
            }
            
            // $nro_registros = count($productos);
            // $monto_total = $_POST['monto_total'];
            // $des_venta = $_POST['descripcion_venta'];
            // $id_user = $_POST['id_user'];
            // $ubicacion = $cliente['ubicacion'];
            // $observacion = $_POST['prioridad'];
            // $hora_ini = $_POST['hora_inicial'];
            // $hora_fin = $_POST['hora_final'];

            //                 if($tipo_pago == 'si'){
            //                     $pago_inicial       = $_POST['monto_pago_inicial'];
            //                     $cuota_dos          = $_POST['monto_cuota_dos'];
            //                     $cuota_tres         = $_POST['monto_cuota_tres'];
            //                     $id_egreso          = $id;
            //                     $empleado_id        = $id_empleado;
                                
            //                     $fecha_pago_inicial = trim($_POST['fecha_pago_inicial']);
            //                     $fecha_pago_inicial = date("Y-m-d", strtotime(str_replace('/','-',$fecha_pago_inicial)));
            //                     $fecha_cuota_dos    = trim($_POST['fecha_cuota_dos']);
            //                     $fecha_cuota_dos = date("Y-m-d", strtotime(str_replace('/','-',$fecha_cuota_dos)));
            //                     $fecha_cuota_tres   = trim($_POST['fecha_cuota_tres']);
            //                     $fecha_cuota_tres = date("Y-m-d", strtotime(str_replace('/','-',$fecha_cuota_tres)));
                                
            //                     $detalle            = $_POST['motivo'];
            //                     $nro_cuota          = $_POST['nro_cuotas'];
                                
                    
            // Obtiene los datos de la proforma
            $id_cliente =   (isset($_POST['id_cliente'])) ? trim($_POST['id_cliente']) : '';  
            
            $nit_ci =       (isset($_POST['nit_ci'])) ? trim($_POST['nit_ci']) : '';          
            $nombre_cliente = (isset($_POST['nombre_cliente'])) ? trim($_POST['nombre_cliente']) : '';          
            $almacen_idx =   (isset($_POST['almacen_id'])) ? trim($_POST['almacen_id']) : '0'; 
            
            if($nit_ci == "" || $nombre_cliente == ""){
                $clientes = $db->query(" select * 
                                            from inv_clientes
                                            WHERE id_cliente='".$id_cliente."'
                                        ")->fetch_first();
        
                $nit_ci =       $clientes['nit'];      
                $nombre_cliente = $clientes['nombre_factura'];          
            }
            
            $telefono =     (isset($_POST['telefono_cliente'])) ? trim($_POST['telefono_cliente']) : '';          
            $tipo_cli =     (isset($_POST['tipo_cli'])) ? trim($_POST['tipo_cli']) : '';         
            $ciudad_id =    (isset($_POST['ciudad'])) ? trim($_POST['ciudad']) : '';          
            $observacion =  (isset($_POST['observacion'])) ? trim($_POST['observacion']) : '';          
            $direccion =    (isset($_POST['direccion'])) ? trim($_POST['direccion']) : '';         
            //$atencion =     (isset($_POST['atencion'])) ? trim($_POST['atencion']) : '';          
            
            $latitud =      (isset($_POST['latitud'])) ? trim($_POST['latitud']) : '';          
            $longitud =     (isset($_POST['longitud'])) ? trim($_POST['longitud']) : '';         
            
            $productos =    (isset($_POST['productos'])) ? $_POST['productos'] : array();
            $nombres =      (isset($_POST['nombres'])) ? $_POST['nombres'] : array();
            $cantidades =   (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
            $unidad =       (isset($_POST['unidad'])) ? $_POST['unidad'] : array();
            $precios =      (isset($_POST['precios'])) ? $_POST['precios'] : array();
            $descuentos =   (isset($_POST['descuentos'])) ? $_POST['descuentos'] : array();
            $nro_registros = (isset($_POST['nro_registros'])) ? trim($_POST['nro_registros']) : '0';
            
            //$almacen_id =   $almacenes['almacen_id']; 
            
            $adelanto =     (isset($_POST['adelanto'])) ? trim($_POST['adelanto']) : '0';     
            $lote		=   (isset($_POST['lotes'])) ? $_POST['lotes'] : array();
            $vencimiento=   (isset($_POST['vencimientos'])) ? $_POST['vencimientos'] : array();
            
            //Cuentas
            //$tipo_pago =    (isset($_POST['tipo_pago'])) ? trim($_POST['tipo_pago']) : 'Efectivo';
            $hora_ini = $_POST['hora_inicial'];
            $hora_fin = $_POST['hora_final'];

            $horaInicio = new DateTime($hora_fin);
            $horaTermino = new DateTime($hora_ini);

            $duracion = $horaInicio->diff($horaTermino);
            $duracion = $duracion->format('%H:%I:%s');

            
            $prioridad =    (isset($_POST['prioridad'])) ? '' : '';         
            $distribuir = (isset($_POST['prioridad'])) ? trim($_POST['prioridad']) : '';
            
            if($distribuir == 'Distribucion' || $distribuir == 'distribucion'){
                $distribuir2 ="S";
            } 
            else{
                $distribuir2 ="N";
            } 
            
            $monto_total=0;
            foreach ($productos as $nro => $elemento) {
                $cantidad = $cantidades[$nro];
                $precio = $precios[$nro];
                $monto_total=$monto_total+($cantidad*$precio);
            }

            $descuento_porc = isset($_POST['descuento_porc'])?trim($_POST['descuento_porc']):0;
            $descuento_bs = isset($_POST['descuento_bs'])?trim($_POST['descuento_bs']):0;  
            $total_importe_descuento = isset($_POST['total_importe_descuento'])?trim($_POST['total_importe_descuento']):0;     

            $nro_cuentas = (isset($_POST['nro_cuentas'])) ? trim($_POST['nro_cuentas']) : '0';
            
            
            
            $monto_cuota[0] = $_POST['monto_pago_inicial'];
            $monto_cuota[1] = $_POST['monto_cuota_dos'];
            $monto_cuota[2] = $_POST['monto_cuota_tres'];

            $fecha_cuota[0] = trim($_POST['fecha_pago_inicial']);
            $explode = explode('/',$fecha_cuota[0]);
            if(count($explode)>=2){
                $fecha_cuota[0] = $explode[2]."-".$explode[1]."-".$explode[0];
            }else{
                $fecha_cuota[0] =date("Y-m-d");
            }
            
            $fecha_cuota[1] = trim($_POST['fecha_cuota_dos']);
            $explode = explode('/',$fecha_cuota[1]);
            if(count($explode)>=2){
                $fecha_cuota[1] = $explode[2]."-".$explode[1]."-".$explode[0];
            }else{
                $fecha_cuota[1] =date("Y-m-d");
            }
            
            $fecha_cuota[2] = trim($_POST['fecha_cuota_tres']);
            $explode = explode('/',$fecha_cuota[2]);
            if(count($explode)>=2){
                $fecha_cuota[2] = $explode[2]."-".$explode[1]."-".$explode[0];
            }else{
                $fecha_cuota[2] =date("Y-m-d");
            }
                        
            
            
            //$plan = (isset($_POST['forma_pago'])) ? trim($_POST['forma_pago']) : '';
            //$plan = ($plan == "2") ? "si" : "no";
            
            if ($plan == "si") {
                $fechas = (isset($_POST['fecha'])) ? $_POST['fecha'] : array();
                $cuotas = (isset($_POST['cuota'])) ? $_POST['cuota'] : array();
            } else {
                
            }
            
            // Obtiene el empleado
            $empleadox = $db->query(" select id_cliente_grupo 
                                from inv_clientes_grupos
                                WHERE vendedor_id='".$id_empleado['persona_id']."'
                            ")->fetch_first();
            
            // obtiene a el cliente
            $cliente = $db->select('*')->from('inv_clientes')->where('id_cliente', $id_cliente)->fetch_first();
            if(!$cliente){
                $cl = array(
                    'cliente' => $nombre_cliente,
                    'nombre_factura' => $nombre_cliente,
                    'nit' => $nit_ci,
                    'direccion' =>  $_POST['direccion'],
                    'descripcion' => '',
                    'telefono' =>  $_POST['telefono'],
                    'ubicacion' =>  $_POST['ubicacion'],
                    'imagen' =>  '',
                    'tipo' =>  '',
                    'fecha_creacion'=>date("Y-m-d  H:i:s"),
                    'empleado_id' => $id_empleado['persona_id'],
                    'cliente_grupo_id' =>  0
                );
                // $db->insert('inv_clientes',$cl);
                $idcli = $db->insert('inv_clientes',$cl);
                $cliente = $db->select('*')->from('inv_clientes')->where('id_cliente', $idcli)->fetch_first();
            }
            
            $nro_factura = 0;
            $nro_registros = count($productos);
            
            if ( $nro_registros> 0) {
                // Instancia la proforma
                $proforma = array(
                    'fecha_egreso' => date('Y-m-d'),
                    'hora_egreso' => date('H:i:s'),
                    'fecha_habilitacion' => date('Y-m-d H:i:s'),
                    'tipo' => 'Preventa',  // Venta
                    'tipo_inicial' => 'Preventa',  // Venta
                    'provisionado' => 'S',
                    'descripcion' => 'Venta de productos con preventa',
                    'nro_nota' => $nro_factura,
                    'nro_factura' => 0,
                    'nro_autorizacion' => '',
                    'codigo_control' => '',
                    'fecha_limite' => '0000-00-00',
                    'monto_total' => $monto_total,
                    'cliente_id' => $cliente['id_cliente'],
                    'nit_ci' => $nit_ci,
                    'nombre_cliente' => $nombre_cliente,
                    'nro_registros' => $nro_registros,
                    'dosificacion_id' => 0,
                    'almacen_id' => $almacen_idx,
                    'empleado_id' => $id_empleado['persona_id'],
                    'vendedor_id' => $id_empleado['persona_id'],
                    'codigo_vendedor'=>$empleadox['id_cliente_grupo'],
                    'coordenadas' => $latitud.",".$longitud,
                    'observacion' => '',//$prioridad." ".$distribuir." ".$almacen_idx,
                    'estadoe' => 2,
                    'descripcion_venta' => $observacion,
                    'ruta_id' => 0,
                    'plan_de_pagos' => ($plan == 'no') ? 'no' : 'si',
                    'estado' => 1,
                    'descuento_porcentaje' => $descuento_porc,
                    'descuento_bs' => $descuento_bs,
                    'monto_total_descuento' => $total_importe_descuento,
                    'nro_movimiento' => 0, //$movimiento, // + 1
                    'duracion' => $duracion,
                    'distribuir'=>$distribuir2,
                    'ubicacion'=>$direccion,
                    'motivo_id'=>'',
                );
            
                // Guarda la informacion
                $proforma_id = $db->insert('inv_egresos', $proforma);

                // Recorre los productos
                foreach ($productos as $nro => $elemento) {
                    $id_unidad = $db->query('SELECT id_unidad FROM inv_unidades WHERE unidad = "'.trim($unidad[$nro]).'" LIMIT 1 ')->fetch_first();
                    $cantidad = $cantidades[$nro];
        
                    $aux = $db->select('*')
                            ->from('inv_ingresos_detalles')
                            ->where('id_detalle', $productos[$nro])
                            ->fetch_first();
                    
                    $venc=explode("/",$vencimiento[$nro]);
                    $vencimiento[$nro]=$venc[2]."-".$venc[1]."-".$venc[0];
                        
                    $detalle = array(
                        'cantidad' => $cantidad,
                        'unidad_id' => $id_unidad['id_unidad'],
                        'precio' => $precios[$nro] - $descuentos[$nro],
                        'descuento' => 0, // $descuentos[$nro]
                        'producto_id' => $aux['producto_id'],
                        'egreso_id' => $proforma_id,
                        'lote' => $lote[$nro],
                        'vencimiento' => $vencimiento[$nro],
                    );
        
                    $id_detalle = $db->insert('inv_egresos_detalles', $detalle);
                }
                
                if ($plan == 'no' && $proforma_id) {
                    // Instancia el ingreso
                    $planPago = array(
                        'movimiento_id' => $proforma_id,
                        'interes_pago' => 0,
                        'tipo' => 'Egreso'
                    );
                    // Guarda la informacion del ingreso general
                    $id_plan_egreso = $db->insert('inv_pagos', $planPago);
                    // Genera el plan de pagos
                    $detallePlan = array(
                        'nro_cuota' => 1,
                        'pago_id' => $id_plan_egreso,
                        'fecha' => date('Y-m-d'),
                        'fecha_pago' => date('Y-m-d'),
                        'monto' => $monto_total,
                        'tipo_pago' => $tipo_pago,
                        'nro_pago' => '0',
                        'empleado_id' => $id_empleado['persona_id'],
                        'estado'  => '0'
                    );
                    // Guarda la informacion
                    $db->insert('inv_pagos_detalles', $detallePlan);
        
                }
        
                //Cuentas
                if ($plan == "si" && $proforma_id) {
                    // Instancia el ingreso
                    $ingresoPlan = array(
                        'movimiento_id' => $proforma_id,
                        'interes_pago' => 0,
                        'tipo' => 'Egreso'
                    );
                    // Guarda la informacion del ingreso general
                    $ingreso_id_plan = $db->insert('inv_pagos', $ingresoPlan);
        
                    $nro_cuota = 0;
                    for ($nro2 = 0; $nro2 < 3; $nro2++) {
                        $nro_cuota++;
                        
                        if ($monto_cuota[$nro2]>0) {
                            $fecha_format = $fecha_cuota[$nro2];
                            
                            $detallePlan = array(
                                'nro_cuota' => $nro_cuota,
                                'pago_id' => $ingreso_id_plan,
                                'fecha' => $fecha_format,
                                'fecha_pago' => $fecha_format,
                                'monto' => (isset($monto_cuota[$nro2])) ? $monto_cuota[$nro2] : 0,
                                'tipo_pago' => '',
                                'nro_pago' => '',
                                'empleado_id' => $id_empleado['persona_id'],
                                'estado'  => '0'
                            );
                        
                            $db->insert('inv_pagos_detalles', $detallePlan);
                        }
                    }
                }
            }

            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            if($proforma_id){
                if ($plan == "si") {
                    $respuesta = array(
                        'estado' => 's',
                        'estadoe' => 2
                    );
                }else{
                    $respuesta = array(
                        'estado' => 's',
                        'estadoe' => $estadoe
                    );
                }    
                echo json_encode($respuesta);
            }else{
                echo json_encode(array('estado'=>'n', 'msg'=> 'no guardo'));
            }
            //     }else{
            //         echo json_encode(array('estado' => 'no tiene stock', 'sinstok' => $sin_stock));
            //     }
            // }else{
            //     echo json_encode(array('estado' => 'no llego uno de los datos'));
            // }
        } catch (Exception $e) {
            $status = false;
            $error = $e->getMessage();

            //Se devuelve el error en mensaje json
            echo json_encode(array("estado" => 'n', 'msg'=>$error));

            //se cierra transaccion
            $db->rollback();
        }
    } else {
        echo json_encode(array('estado'=>'n', 'msg'=> 'llego uno de los datos1'));
    }
}else{
    echo json_encode(array('estado'=>'n', 'msg'=> 'no llego los datos'));
}
?>