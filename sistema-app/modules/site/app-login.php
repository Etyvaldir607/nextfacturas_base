<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: application/json; charset=utf-8');
header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
date_default_timezone_set('America/La_Paz');

if(is_post()) {
    if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['model']) && isset($_POST['imei'])) {
        require config . '/database.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        $usuario = trim($_POST['username']);
        $password = trim($_POST['password']);

        $imei = trim($_POST['imei']);
        $model = trim($_POST['model']);

        try {   
            //Se abre nueva transacci贸n.
            $db->autocommit(false);
            $db->beginTransaction();
        
            $contrasenia = sha1(prefix . md5($password));

            $usuario = $db->query(" select  a.id_user,a.persona_id, a.avatar, b.nombres, b.paterno, b.materno, b.genero, b.telefono, b.fecha_ingreso as fecha, a.rol_id, g.nombre_grupo, iua.almacen_id, 
                                            ifnull(tk.model,'')as model, ifnull(tk.imei,'')as imei, ifnull(tk.token,'')as token 
                                    from sys_users a 
                                    LEFT JOIN sys_empleados b ON a.persona_id = b.id_empleado 
                                    LEFT JOIN sys_token tk ON tk.user_id = a.id_user 
                                    LEFT JOIN  inv_clientes_grupos g ON vendedor_id = id_empleado 	
                                    LEFT JOIN inv_users_almacenes iua ON iua.user_id=a.id_user
                                    where (md5(a.username) = md5('$usuario') or md5(a.email) = md5('$usuario')) and a.password = '$contrasenia'  and a.active = '1' limit 1")->fetch_first();

            if ($usuario) {
                
                if($usuario['model']=="" && $usuario['imei']==""){
                    $token = sha1(md5($imei.$model));

                    $datos = array(
                        'imei' => $imei,
                        'token' => $token,
                        'model' => $model,
                        'user_id' => $usuario['id_user']
                    );
                    $id = $db->insert('sys_token',$datos);
                    
                    $usuario['model']=$model;
                    $usuario['imei']=$imei;
                    $usuario['token']=$token;
                }
                
                if($usuario['model']==$model && $usuario['imei']==$imei){
                    $usuario['avatar'] = ($usuario['avatar'] == '') ? imgs2 . '/avatar.jpg' : url1 . profiles2 . '/' . $usuario['avatar'];
                    // $usuario['avatar'] = ($usuario['avatar'] == '') ? imgs . '/avatar.jpg' : url1 . profiles . '/' . $usuario['avatar'];
        
                    $usuario['id_user'] = (int)$usuario['id_user'];
                    $emp = $usuario['persona_id'];
        
                    if($usuario['rol_id'] == 4){
        
                        // $dis=$db->query('SELECT b.fecha_egreso, b.monto_total, b.observacion as estadod, c.*, c.ubicacion AS latitud, c.ubicacion AS longitud, b.fecha_egreso, b.estadoe, b.empleado_id FROM gps_asigna_distribucion a
                        // LEFT JOIN gps_rutas d ON a.ruta_id = d.id_ruta
                        // LEFT JOIN sys_empleados e ON d.empleado_id = e.id_empleado
                        // LEFT JOIN inv_egresos b ON d.id_ruta = b.ruta_id
                        // LEFT JOIN inv_clientes c ON b.cliente_id = c.id_cliente
                        // WHERE a.distribuidor_id = '.$emp.' AND a.estado = 1 AND b.grupo="" AND b.estadoe > 1 AND (b.fecha_egreso < CURDATE() OR b.fecha_egreso <= e.fecha) GROUP BY b.cliente_id')->fetch();
                        
                        
                        
                        
                        // $dis=$db->query('SELECT  b.monto_total, e.nombres, e.paterno, b.observacion as estadod, c.id_cliente, c.cliente, c.nombre_factura, c.nit, c.telefono, c.direccion, c.descripcion, c.imagen, c.tipo, c.ubicacion AS latitud, iua.almacen_id
                        //                         c.ubicacion AS longitud,  b.estadoe, b.plan_de_pagos, g.nombre_grupo 
                        //                 FROM inv_asignaciones_clientes a
                        //                 LEFT JOIN inv_egresos b ON a.egreso_id = b.id_egreso
                        //                 LEFT JOIN sys_empleados e ON b.empleado_id = e.id_empleado
                                        
                        //                 LEFT JOIN inv_clientes c ON b.cliente_id = c.id_cliente
                                        
                        //                 LEFT JOIN  inv_clientes_grupos g ON vendedor_id = id_empleado 	
                                        
                        //                 LEFT JOIN inv_users_almacenes iua ON user_id="'.$usuario['id_user'].'"
                                        
                        //                 WHERE a.distribuidor_id = '.$emp.' AND a.estado = "A" AND b.grupo="" AND b.estadoe > 1 AND b.estadoe < 3 AND (a.fecha_entrega<= CURDATE()) GROUP BY b.cliente_id')->fetch();
        
                        
                        
                        
                        // $dis1 = $db->query('SELECT b.fecha_egreso, b.monto_total, b.observacion AS estadod, c.*, c.ubicacion AS latitud, c.ubicacion AS longitud, b.fecha_egreso, b.estadoe, b.empleado_id FROM gps_asigna_distribucion a
                        // LEFT JOIN gps_rutas d ON a.ruta_id = d.id_ruta
                        // LEFT JOIN tmp_egresos b ON d.id_ruta = b.ruta_id
                        // LEFT JOIN inv_clientes c ON b.cliente_id = c.id_cliente
                        // WHERE a.distribuidor_id = '. $emp .' AND a.estado = 1 AND b.grupo="" AND b.estadoe > 1 AND b.distribuidor_estado = "NO ENTREGA" AND b.estado = 3 GROUP BY b.cliente_id ORDER BY b.estadoe DESC')->fetch();
        
                        // $dis = array_merge ($dis, $dis1);
                        
                        
                        
                        
                        
                        
                        // $aux = array();
                        // foreach ($dis as $nro => $di) {
                        //     if($usuario['fecha'] >= $di['fecha_egreso'] && $di['estadoe'] == 3){
        
                        //     }else{
                        //         array_push($aux,$dis[$nro]);
                        //     }
                        // }
                        // $db->commit();
                        // if(!empty($aux)){
                        //     $usuario['ruta'] = '';
                        //     $usuario['estado_precio'] = 0;
                        //     $respuesta = array(
                        //         'estado' => 's',
                        //         'vendedor' => $usuario
                        //     );
                        // }else{
                        //     $respuesta = array('estado' => 'no tiene clientes que repartir');
                        // }
                    
                    
                    
                    
                    
                        $cliente = array(
                            'fecha' => date('Y-m-d'),
                            'hora' => date('H:i:s')
                        );
                        $db->where('id_empleado',$usuario['persona_id'])->update('sys_empleados', $cliente);
                        
                        
                        if($usuario['rol_id'] > 2){
                            $usuario['estado_precio'] = 0;
                        }else{
                            $usuario['estado_precio'] = 1;
                        }
                        
                        
                        $respuesta = array(
                            'estado' => 's',
                            'vendedor' => $usuario
                        );

                    
                    
                    
                    }else{
                        /*$dia = date('w');
                        $area = $db->select('*')
                                ->from('sys_users a')
                                ->join('sys_empleados b', 'a.persona_id = b.id_empleado')
                                ->join('gps_rutas c','b.id_empleado = c.empleado_id')
                                ->where('a.id_user',$usuario['id_user'])
                                ->where('c.dia',$dia)
                                ->fetch_first();
                        if($area){
                        */
                        //$usuario['ruta'] = '';
                        
                        $cliente = array(
                            'fecha' => date('Y-m-d'),
                            'hora' => date('H:i:s')
                        );
                        $db->where('id_empleado',$usuario['persona_id'])->update('sys_empleados', $cliente);
                        
                        
                        if($usuario['rol_id'] > 2){
                            $usuario['estado_precio'] = 0;
                        }else{
                            $usuario['estado_precio'] = 1;
                        }
                        
                        
                        $respuesta = array(
                            'estado' => 's',
                            'vendedor' => $usuario
                        );
                    
                        /*}else{
                            $usuario['ruta'] = '';
                            $respuesta = array(
                                'estado' => 'sr',
                                'vendedor' => $usuario
                            );
                        }*/
                    }
                    $db->commit();
                    echo json_encode($respuesta);
                // }else{
                //     echo json_encode(array('estado' => 'sv'));
                }else{
                    $db->commit();
                    echo json_encode(array('estado' => 'token incorrecto'));
                }
            }else{
                $db->commit();
                echo json_encode(array('estado' => 'password incorrecto'));
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
        echo json_encode(array('estado' => 'uno de los datos fallo'));
    }
}else{
    echo json_encode(array('estado' => 'no llego los datos'));
}
?>