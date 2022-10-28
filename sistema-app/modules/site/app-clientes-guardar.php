<?php
if(is_post()) {
    if (isset($_POST['cliente']) && isset($_POST['telefono']) && isset($_POST['descripcion']) && isset($_POST['imagen']) && isset($_POST['id_user'])) {
        
        require config . '/database.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        $usuario = trim($_POST['username']);
        $password = trim($_POST['password']);

        try {   
            //Se abre nueva transacci贸n.
            $db->autocommit(false);
            $db->beginTransaction();
        
            require_once libraries . '/upload-class/class.upload.php';
            
            $usuario = $db->select('*, c.id_cliente_grupo')
                          ->from('sys_users a')
                          ->join('sys_empleados b','a.persona_id = b.id_empleado')
                          ->join('inv_clientes_grupos c','a.persona_id = c.vendedor_id')
                          ->where('a.id_user',$_POST['id_user'])
                          ->fetch_first();
                          
            //if($usuario['fecha'] != date('Y-m-d')){
            
            // if(true){
                $id_cliente = isset($_POST['id_cliente'])?$_POST['id_cliente']:0;
                
                $cliente = $_POST['cliente'];
                $nombre_factura = $_POST['nombre_factura'];
                $nit = $_POST['nit'];
                $telefono = $_POST['telefono'];
                $direccion = $_POST['direccion'];
                
                $descripcion = $_POST['descripcion'];
                $ubicacion = $_POST['latitud'].','.$_POST['longitud'];
                $imagen = isset($_POST['imagen'])?$_POST['imagen']:-1;
                $tipo = $_POST['tipo_cliente'];
                $ciudad = $_POST['ciudad'];
                
                $dia = $_POST['dia'];
                
                $categoria = 0;
                $id_empleado = $usuario['id_empleado'];
                
                if(isset($usuario['id_cliente_grupo'])){
                    $grupo = $usuario['id_cliente_grupo'];
                }else{
                    $grupo = 0;
                }
                
                
                
                $nombre_grupo = $db->select('*')->from('inv_clientes_grupos')->where('id_cliente_grupo',$grupo)->fetch_first();
                
                
                
                $departamento = $db->select('*')
                                   ->from('inv_ciudades c')
                                   ->join('inv_departamentos d','d.id_departamento = c.departamento_id')
                                   ->where('c.ciudad',$ciudad)
                                   ->fetch_first();
                
                $id_anterior = $db->select('*')->from('inv_clientes')->like('codigo',$departamento['abreviacion'])->order_by('id_cliente','desc')->limit(1)->fetch_first();
                
                $codigo = ($id_anterior['codigo'] != null) ? $id_anterior['codigo'] : 1;
                    
                if($codigo != 1){
                    $codigo3 = explode('-' , $codigo);
                    $codigo1 = $codigo3[1] + 1;
                }else{
                    $codigo1 = 1;
                }
                
                if($codigo1 > 9){
                    $codigo2 = $departamento['abreviacion'].'-000'.$codigo1;
                }elseif($codigo1 > 99){
                    $codigo2 = $departamento['abreviacion'].'-00'.$codigo1;
                }elseif($codigo1 > 999){
                    $codigo2 = $departamento['abreviacion'].'-0'.$codigo1;
                }elseif($codigo1 > 9999){
                    $codigo2 = $departamento['abreviacion'].'-'.$codigo1;
                }else{
                    $codigo2 = $departamento['abreviacion'].'-0000'.$codigo1;
                }
            
                //buscar ciudad
                
                //$ubicacion
    
                $imagen_final = md5(secret . random_string() . 'miimagen');
                $extension = 'jpg';
                //$ruta = url1 . '/tiendas/' . $imagen_final . '.' . $extension;
                
                $n_imagen = $imagen_final.'.'.$extension;
                $ruta = files . '/tiendas/' . $imagen_final . '.' . $extension;
    
                file_put_contents($ruta, base64_decode($imagen));
    
                $ver = $db->select('*')
                          ->from('inv_clientes')
                          ->where(array('cliente' => $cliente, 'nombre_factura' => $nombre_factura, 'nit' => $nit, 'direccion' => $direccion))
                          ->fetch_first();
                          
                if($id_cliente==0){
                    if(!$ver){
                        $datos = array(
                            'cliente' => $cliente,
                            'nombre_factura' => $nombre_factura,
                            //'nombre_factura' => $nombre_factura,
                            'nit' => $nit,
                            'estado' => 'si',
                            'telefono' => $telefono,
                            
                            'direccion' => $direccion,
                            'descripcion' => $descripcion,
                            'ubicacion' => $ubicacion,
                            'imagen' => $n_imagen,
                            'categoria' => $categoria,
                            
                            'tipo' => $tipo,
                            'cliente_grupo_id' => $grupo,
                            'ciudad_id' => $departamento['id_ciudad'],
                            'codigo' => $codigo2,
                            'fecha_creacion' => date('Y-m-d H:i:s'),
                            
                            'dia'=>$dia,                
                            'empleado_id' => $id_empleado
                        );
                        
                        $id = $db->insert('inv_clientes',$datos);
                    }
                }else{
                    if($imagen==-1){
                        $datos = array(
                            'cliente' => $cliente,
                            'nombre_factura' => $nombre_factura,
                            'nit' => $nit,
                            'telefono' => $telefono,
                            
                            'direccion' => $direccion,
                            'descripcion' => $descripcion,
                            'ubicacion' => $ubicacion,
                            'categoria' => $categoria,
                            'ciudad_id' => $departamento['id_ciudad'],
                            
                            'tipo' => $tipo,
                            'dia'=>$dia,
                        );
                    }else{
                        $datos = array(
                            'cliente' => $cliente,
                            'nombre_factura' => $nombre_factura,
                            'nit' => $nit,
                            'telefono' => $telefono,
                            'ciudad_id' => $departamento['id_ciudad'],
                            
                            'direccion' => $direccion,
                            'descripcion' => $descripcion,
                            'ubicacion' => $ubicacion,
                            'imagen' => $n_imagen,
                            'categoria' => $categoria,
                            
                            'tipo' => $tipo,
                            'dia'=>$dia,
                        );
                    }
                    $condicion = array('id_cliente' => $id_cliente);
            	    $db->where($condicion)->update('inv_clientes', $datos);
            	    $id=$id_cliente;
                }
        
                if($id){
                    $dis=$db->query('   SELECT  c.*, g.nombre_grupo, e.estadoe, ifnull(e.estadoe,0) as estadoe, 
                                            SUM( IFNULL(ppp.pagos_realizados,0) )as pagos_realizados, 
                                            SUM( IFNULL(edd.monto_sumado,0) ) as monto_sumado, cd.ciudad
                                    
                                    FROM inv_clientes c
                                    LEFT JOIN  inv_ciudades cd ON cd.id_ciudad = c.ciudad_id 	
                                    
                                    LEFT JOIN  inv_clientes_grupos g ON id_cliente_grupo = cliente_grupo_id 	
                                    LEFT JOIN inv_egresos e ON cliente_id=id_cliente AND fecha_egreso="'.date("Y-m-d").'" 
                                    LEFT JOIN inv_egresos i ON i.cliente_id=c.id_cliente AND (i.tipo = "Venta" OR i.tipo = "Preventa") AND i.preventa="habilitado"
                                        
                                    LEFT JOIN(
                                                SELECT ifnull(SUM(ed.precio*ed.cantidad),0)as monto_sumado, ed.egreso_id
                                                FROM inv_egresos_detalles ed 
                                                GROUP BY ed.egreso_id
                                            )edd ON i.id_egreso = edd.egreso_id
                                    
                                    LEFT JOIN(
                                                SELECT ifnull(SUM(IF(pd.estado="1", pd.monto, 0)),0)as pagos_realizados, movimiento_id
                                                FROM inv_pagos p 
                                                LEFT JOIN inv_pagos_detalles pd ON pd.pago_id = p.id_pago
                                                WHERE p.tipo="Egreso"
                                                GROUP BY pago_id
                                            )ppp ON ppp.movimiento_id = i.id_egreso 
                                    
                                    WHERE c.id_cliente = "'.$id.'" 
                                ')->fetch();
    
                    
                    unset($dis[0]['categoria']);
                    unset($dis[0]['ciudad_id']);
                    unset($dis[0]['codigo']);
                    unset($dis[0]['estado']);
                    unset($dis[0]['empleado_id']);
                    unset($dis[0]['fecha_creacion']);
                    
                    
                    unset($dis[0]['ubicacion']);
                    unset($dis[0]['nombre_grupo']);
                    unset($dis[0]['cliente_grupo_id']);
                    
                    $dis[0]['imagen']=url1."".tiendas.'/'.$dis[0]['imagen'];
                    
                    
                    
                    
                    
                    // //$respuesta[0] = array(
                    // $datos222[0] = array(
                    //     'estado' => 'v',
                    //     'cliente' => $cliente,
                    //     'nombre_factura' => $nombre_factura,
                    //     'nit' => $nit,
                    //     'telefono' => $telefono,
                    //     'direccion' => $direccion,
                    //     'descripcion' => $descripcion,
                    //     'latitud' => $_POST['latitud'],
                    //     'longitud' => $_POST['longitud'],
                        
                        
                        
                    //     'imagen' => url1 . tiendas . '/'.$n_imagen,
    
                        
                        
                    //     'id_cliente' => $id,
                    //     'tipo_cliente' => $tipo,
                    //     'nombre_grupo'=>$nombre_grupo['nombre_grupo'],
                    //     'dia'=>$dia,                
                    //     'grupo_id'=>$grupo
                    // );
                    $respuesta = array(
                         'estado' => 's',
                         'cliente' => $dis
                    );
                    echo json_encode($respuesta);
                }else{
                    echo json_encode(array('estado' => 'no guardo'));
                }
            // }else{
            //     echo json_encode(array('estado' => 'Inactivo'));
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
        echo json_encode(array('estado' => 'no llego uno de los datos'));
    }
}else{
    echo json_encode(array('estado' => 'no llego los datos'));
}
?>