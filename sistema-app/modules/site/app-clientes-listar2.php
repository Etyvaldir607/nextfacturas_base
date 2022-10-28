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
    if (isset($_POST['id_user'])) {
        require config . '/database.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        try {   
            //Se abre nueva transacci贸n.
            $db->autocommit(false);
            $db->beginTransaction();
        
            $id_user = $_POST['id_user'];
            
            //$dia = date('w');

            $area =  $db->select('*')
                        ->from('sys_users a')
                        ->join('sys_empleados b', 'a.persona_id = b.id_empleado')
                        ->where('a.id_user',$id_user)
                        ->fetch_first();

            $id_empleado = $area['id_empleado'];
            $dia_hoy=date('w');
            
            //buscar si es vendedor o distribuidor
            if($area['rol_id'] != 4){

                //ifnull(if(e.estadoe=2,0,e.estadoe),0) as estadoe, c.ciudad, 

                $dis=$db->query('   SELECT c.id_cliente, c.cliente, c.nombre_factura, c.nit, c.telefono, c.direccion, c.descripcion, c.ubicacion, c.imagen, c.tipo, c.dia, c.visitar,
                                            ifnull(e.estadoe,0) as estadoe, "" as ciudad, 
                
                                            xxx.pagos_realizados, xxx.monto_sumado
                                    FROM( 
                                        SELECT *, 1 as orden_cliente, "si" as visitar
                                        FROM vista_clientes
                                        WHERE dia="'.$dia_hoy.'" AND vendedor_id = "'.$id_empleado.'"
                                        
                                        UNION 
    
                                        SELECT *, 2 as orden_cliente, "no" as visitar
                                        FROM vista_clientes
                                        WHERE dia!="'.$dia_hoy.'" AND vendedor_id = "'.$id_empleado.'"
                                    )c
                                    
                                    LEFT JOIN inv_egresos e ON cliente_id=id_cliente AND fecha_egreso="'.date("Y-m-d").'" 
                                    
                                    LEFT JOIN (
                                        SELECT  estadoe, ifnull(pagos_realizados,0) as pagos_realizados, ifnull(monto_sumado,0)as monto_sumado, cliente_id
                                        FROM vista_deudas_cliente
                                        GROUP BY CLIENTE_ID
                                    )xxx ON xxx.cliente_id=id_cliente
                                    
                                    GROUP BY id_cliente
                                ')->fetch();
    
                foreach ($dis as $nro => $di) {
                    
                    $dis[$nro]['estado_cobro'] = "si";
                    
                    // if( number_format($di['monto_sumado'],2,'.','')>number_format($di['pagos_realizados'],2,'.','') ){ 
                    //     $dis[$nro]['estado_cobro'] = "si";
                    // }else{
                    //     $dis[$nro]['estado_cobro'] = "no";
                    //     //$dis[$nro]['estado_cobro'] = "no".number_format($di['monto_sumado'],2).">".number_format($di['pagos_realizados'],2);
                    // }
                    
                    $dis[$nro]['imagen'] = ($di['imagen'] == '') ? url1 . imgs2 . '/image.jpg' : url1 . tiendas . '/' . $di['imagen'];
                    $a = explode(',',$di['ubicacion']);
                    $dis[$nro]['latitud'] = (float)$a[0];
                    $dis[$nro]['longitud'] = (float)$a[1];
                    unset($dis[$nro]['categoria']);
                    unset($dis[$nro]['ciudad_id']);
                    unset($dis[$nro]['codigo']);
                    unset($dis[$nro]['estado']);
                    unset($dis[$nro]['empleado_id']);
                    unset($dis[$nro]['fecha_creacion']);
                    unset($dis[$nro]['pagos_realizados']);
                    unset($dis[$nro]['monto_sumado']);

                    unset($dis[$nro]['ubicacion']);
                }
                $aux = array();
                foreach ($dis as $nro => $di) {
                    if(false){
    
                    }else{
                        array_push($aux,$di);
                    }
                }
                
                if(count($aux)>0){
                    $db->commit();
                    $respuesta = array(
                        'estado' => 'v',
                        'cliente' => $aux
                    );
                    echo json_encode($respuesta);
                }else{
                    $db->commit();
                    $respuesta = array(
                        'estado' => 'n'
                    );
                    echo json_encode($respuesta);
                }
            }
            else{
                $dis_query='    SELECT c.id_cliente, c.cliente, c.nombre_factura, c.nit, c.telefono, c.direccion, c.descripcion, c.ubicacion, c.imagen, c.tipo, 
                                            e.monto_total, e.estadoe, e.id_egreso, e.plan_de_pagos, CONCAT(b.nombres," ",b.paterno)as nombres, e.nro_nota, e.descripcion_venta
                
                                FROM inv_clientes c 
                                
                                INNER JOIN inv_egresos e ON cliente_id=id_cliente
                                
                                INNER JOIN inv_asignaciones_clientes ac on ac.egreso_id=e.id_egreso

                                left join sys_empleados b on e.vendedor_id = b.id_empleado
                    
                                WHERE ac.distribuidor_id= "'.$id_empleado.'" and ac.estado="A" AND estado_pedido="salida"
                                
                                ORDER BY c.cliente
                            ';
    
                $dis=$db->query($dis_query)->fetch();
    
                foreach ($dis as $nro => $di) {
    
                    $dis_query222='  SELECT  estadoe, ifnull(SUM(pagos_realizados),0)as pagos_realizados, ifnull(SUM(monto_sumado),0)as monto_sumado, cliente_id, descripcion_venta
                                     FROM    inv_egresos i 
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
                                        
                                     where  (i.tipo = "Venta" OR i.tipo = "Preventa") AND i.preventa="habilitado" AND i.cliente_id="'.$di['id_cliente'].'"
                                
                                     GROUP BY CLIENTE_ID
                                    ';
        
                    $dis222=$db->query($dis_query222)->fetch_first();
                
                    if( number_format($dis222['monto_sumado'],2,'.','')>number_format($dis222['pagos_realizados'],2,'.','') ){ 
                        $dis[$nro]['estado_cobro'] = "si";
                    }else{
                        $dis[$nro]['estado_cobro'] = "no";
                    }
    
                    $dis[$nro]['imagen'] = ($di['imagen'] == '') ? url1 . imgs2 . '/image.jpg' : url1 . tiendas . '/' . $di['imagen'];
                    $a = explode(',',$di['ubicacion']);
                    $dis[$nro]['latitud'] = (float)$a[0];
                    $dis[$nro]['longitud'] = (float)$a[1];
            
                    $dis[$nro]['id_unico'] = $dis[$nro]['id_egreso'];
            
                    unset($dis[$nro]['categoria']);
                    unset($dis[$nro]['ciudad_id']);
                    unset($dis[$nro]['codigo']);
                    unset($dis[$nro]['estado']);
                    unset($dis[$nro]['empleado_id']);
                    unset($dis[$nro]['fecha_creacion']);
                    unset($dis[$nro]['pagos_realizados']);
                    unset($dis[$nro]['monto_sumado']);

                    $dis[$nro]['descripcion']=number_format($dis222['monto_sumado'],2,'.','')." > ".number_format($dis222['pagos_realizados'],2,'.','');
                    $dis[$nro]['descripcion']=$dis222['descripcion_venta'];
                }
                $aux = array();
                foreach ($dis as $nro => $di) {
                    if(false){
    
                    }else{
                        array_push($aux,$di);
                    }
                }
                
                if(count($aux)>0){
                    $db->commit();
                    $respuesta = array(
                        'estado' => 'd',
                        'cliente' => $aux
                    );
                    echo json_encode($respuesta);
                }else{
                    $db->commit();
                    $respuesta = array(
                        'estado' => 'n'
                    );
                    echo json_encode($respuesta);
                }
            }
        } catch (Exception $e) {
            $status = false;
            $error = $e->getMessage();

            //Se devuelve el error en mensaje json
            echo json_encode(array('estado' => 'n', 'msg'=>$error));

            //se cierra transaccion
            $db->rollback();
        }
    } else {
        // Devuelve los resultados
        echo json_encode(array('estado'=>'n', 'msg'=> 'no llega el id usuario'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado'=>'n', 'msg' => 'no llega ningun dato'));
}
?>