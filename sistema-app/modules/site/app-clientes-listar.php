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
                
            $dis=$db->query('   SELECT  c.*, g.nombre_grupo
                                
                                FROM( 
                                    SELECT *, 1 as orden_cliente
                                    FROM inv_clientes c1
                                    WHERE dia="'.$dia_hoy.'" and estado="si"
                                    
                                    UNION 

                                    SELECT *, 2 as orden_cliente
                                    FROM inv_clientes c2 
                                    WHERE dia!="'.$dia_hoy.'" and estado="si"
                                )c
                                
                                LEFT JOIN  inv_clientes_grupos g ON id_cliente_grupo = cliente_grupo_id 	
                                
                                WHERE g.vendedor_id = "'.$id_empleado.'" 
                            ')->fetch();

            
            // LEFT JOIN  inv_ciudades cd ON cd.id_ciudad = c.ciudad_id 	
                                
                                
            foreach ($dis as $nro => $di) {
                
                // $dis_xxx=$db->query('   SELECT  e.estadoe, ifnull(e.estadoe,0) as estadoe, 
                //                                 SUM( IFNULL(ppp.pagos_realizados,0) )as pagos_realizados, 
                //                                 SUM( IFNULL(edd.monto_sumado,0) ) as monto_sumado
                                
                //                     FROM inv_egresos e 
                                    
                //                     LEFT JOIN inv_egresos i ON i.cliente_id="'.$di['id_cliente'].'" AND (i.tipo = "Venta" OR i.tipo = "Preventa") AND i.preventa="habilitado"
                                        
                //                     LEFT JOIN(
                //                                 SELECT ifnull(SUM(ed.precio*ed.cantidad),0)as monto_sumado, ed.egreso_id
                //                                 FROM inv_egresos_detalles ed 
                //                                 GROUP BY ed.egreso_id
                //                             )edd ON i.id_egreso = edd.egreso_id
                                    
                //                     LEFT JOIN(
                //                                 SELECT ifnull(SUM(IF(pd.estado="1", pd.monto, 0)),0)as pagos_realizados, movimiento_id
                //                                 FROM inv_pagos p 
                //                                 LEFT JOIN inv_pagos_detalles pd ON pd.pago_id = p.id_pago
                //                                 WHERE p.tipo="Egreso"
                //                                 GROUP BY pago_id
                //                             )ppp ON ppp.movimiento_id = i.id_egreso 
                                    
                //                     WHERE e.cliente_id="'.$di['id_cliente'].'" AND e.fecha_egreso="'.date("Y-m-d").'"
                //                 ')->fetch_first();

                // if( number_format($dis_xxx['monto_sumado'],2)>number_format($dis_xxx['pagos_realizados'],2) ){ 
                
                // $dis['pagos_realizados']=$dis_xxx['pagos_realizados']; 
                // $dis['monto_sumado']=$dis_xxx['monto_sumado'];
                // $dis['estadoe']=$dis_xxx['estadoe'];
                
                // $dis['pagos_realizados']=0; 
                // $dis['monto_sumado']=0;
                $dis[$nro]['ciudad']='';
                $dis[$nro]['estadoe']=0;
                
                $dis[$nro]['estado_cobro'] = "si";

                $dis[$nro]['imagen'] = ($di['imagen'] == '') ? url1 . imgs2 . '/image.jpg' : url1 . tiendas . '/' . $di['imagen'];
                $a = explode(',',$di['ubicacion']);
                $dis[$nro]['latitud'] = (float)$a[0];
                $dis[$nro]['longitud'] = (float)$a[1];
                
                // $dis[$nro]['cliente'] = $dis[$nro]['cliente']." - ".$dis[$nro]['id_cliente'];
                
                unset($dis[$nro]['categoria']);
                unset($dis[$nro]['ciudad_id']);
                unset($dis[$nro]['codigo']);
                unset($dis[$nro]['estado']);
                unset($dis[$nro]['empleado_id']);
                unset($dis[$nro]['fecha_creacion']);
                
                
                unset($dis[$nro]['ubicacion']);
                unset($dis[$nro]['nombre_grupo']);
                unset($dis[$nro]['cliente_grupo_id']);
                
                
                if($di['dia']==date('w')){
                    $dis[$nro]['visitar'] = "si";
                }
                else{
                    $dis[$nro]['visitar'] = "no";
                }
                
                unset($dis[$nro]['orden_cliente']);
                unset($dis[$nro]['pagos_realizados']);
                unset($dis[$nro]['monto_sumado']);
                
                $venx=explode("-",$dis[$nro]['vencimiento']);
                $dis[$nro]['vencimiento']=$venx[2]."/".$venx[1]."/".$venx[0];
                
            }
            $aux = array();
            foreach ($dis as $nro => $di) {
                if(false){

                }else{
                    array_push($aux,$di);
                }
            }
            
            $db->commit();
            
            if(count($aux)>0){
                $respuesta = array(
                    'estado' => 's',
                    'cliente' => $aux
                );
            }else{
                $respuesta = array(
                    'estado' => 'n',
                    'msg' => 'array vacio'
                );
            }
            echo json_encode($respuesta);
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
        echo json_encode(array('estado' => 'n', 'msg'=>'no llega el id usuario'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'n', 'msg'=>'no llega ningun dato'));
}
?>






<?php
// header("Access-Control-Allow-Origin: *");
// header('Access-Control-Allow-Credentials: true');
// header('Access-Control-Allow-Methods: POST');
// header("Access-Control-Allow-Headers: X-Requested-With");
// header('Content-Type: application/json; charset=utf-8');
// header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
// date_default_timezone_set('America/La_Paz');

// header('Content-Type: application/json');

// // Verifica la peticion post
// if (is_post()) {
//     // Verifica la existencia de datos
//     if (isset($_POST['id_user'])) {
//         require config . '/database.php';
//         mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

//         try {   
//             //Se abre nueva transacci贸n.
//             $db->autocommit(false);
//             $db->beginTransaction();
         
        
//             $id_user = $_POST['id_user'];
            
//             //$dia = date('w');

//             $area =  $db->select('*')
//                         ->from('sys_users a')
//                         ->join('sys_empleados b', 'a.persona_id = b.id_empleado')
//                         ->where('a.id_user',$id_user)
//                         ->fetch_first();

//             $id_empleado = $area['id_empleado'];
            
            
//             $dia_hoy=date('w');
                
            
//             //buscar si es vendedor o distribuidor
//             //if($usuario['rol_id'] == 4){
//             // $dis=$db->query('   SELECT  c.*
//             //                     FROM( 
//             //                         SELECT c1.*, 1 as orden_cliente
//             //                         FROM vista_cliente c1
//             //                         WHERE c1.dia="'.$dia_hoy.'"
            
//             //                         UNION 
            
//             //                         SELECT c2.*, 2 as orden_cliente
//             //                         FROM vista_cliente c2
//             //                         WHERE c2.dia!="'.$dia_hoy.'"
//             //                     )c            
                                
//             //                     WHERE vendedor_id = "'.$id_empleado.'" 
//             //                     ORDER BY orden_cliente
//             //                 ')->fetch();        
            
            
//             $dis=$db->query('   SELECT  c.*, g.nombre_grupo, e.estadoe, ifnull(e.estadoe,0) as estadoe, 
//                                         SUM( IFNULL(ppp.pagos_realizados,0) )as pagos_realizados, 
//                                         SUM( IFNULL(edd.monto_sumado,0) ) as monto_sumado, cd.ciudad
//                                 FROM( 
//                                     SELECT *, 1 as orden_cliente
//                                     FROM inv_clientes c1
//                                     WHERE dia="'.$dia_hoy.'" and estado="si"
                                    
//                                     UNION 

//                                     SELECT *, 2 as orden_cliente
//                                     FROM inv_clientes c2 
//                                     WHERE dia!="'.$dia_hoy.'" and estado="si"
//                                 )c
                                
//                                 LEFT JOIN  inv_ciudades cd ON cd.id_ciudad = c.ciudad_id 	
                                
//                                 LEFT JOIN  inv_clientes_grupos g ON id_cliente_grupo = cliente_grupo_id 	
//                                 LEFT JOIN inv_egresos e ON cliente_id=id_cliente AND fecha_egreso="'.date("Y-m-d").'" 
                                
                                
//                                 LEFT JOIN inv_egresos i ON i.cliente_id=c.id_cliente AND (i.tipo = "Venta" OR i.tipo = "Preventa") AND i.preventa="habilitado"
                                    
//                                 LEFT JOIN(
//                                             SELECT ifnull(SUM(ed.precio*ed.cantidad),0)as monto_sumado, ed.egreso_id
//                                             FROM inv_egresos_detalles ed 
//                                             GROUP BY ed.egreso_id
//                                         )edd ON i.id_egreso = edd.egreso_id
                                
//                                 LEFT JOIN(
//                                             SELECT ifnull(SUM(IF(pd.estado="1", pd.monto, 0)),0)as pagos_realizados, movimiento_id
//                                             FROM inv_pagos p 
//                                             LEFT JOIN inv_pagos_detalles pd ON pd.pago_id = p.id_pago
//                                             WHERE p.tipo="Egreso"
//                                             GROUP BY pago_id
//                                         )ppp ON ppp.movimiento_id = i.id_egreso 
                                
//                                 WHERE g.vendedor_id = "'.$id_empleado.'" 
                                
//                                 GROUP BY c.id_cliente
                                
//                                 ORDER BY orden_cliente
//                             ')->fetch();

//             foreach ($dis as $nro => $di) {
                
//                 //if( number_format($di['monto_sumado'],2)>number_format($di['pagos_realizados'],2) ){ 
//                 if( $di['monto_sumado'] > $di['pagos_realizados'] ){ 
//                         $dis[$nro]['estado_cobro'] = "si";
//                 }else{
//                     $dis[$nro]['estado_cobro'] = "no";
//                 }

//                 $dis[$nro]['imagen'] = ($di['imagen'] == '') ? url1 . imgs2 . '/image.jpg' : url1 . tiendas . '/' . $di['imagen'];
//                 $a = explode(',',$di['ubicacion']);
//                 $dis[$nro]['latitud'] = (float)$a[0];
//                 $dis[$nro]['longitud'] = (float)$a[1];
                
//                 // $dis[$nro]['cliente'] = $dis[$nro]['cliente']." - ".$dis[$nro]['id_cliente'];
                
//                 unset($dis[$nro]['categoria']);
//                 unset($dis[$nro]['ciudad_id']);
//                 unset($dis[$nro]['codigo']);
//                 unset($dis[$nro]['estado']);
//                 unset($dis[$nro]['empleado_id']);
//                 unset($dis[$nro]['fecha_creacion']);
                
                
//                 unset($dis[$nro]['ubicacion']);
//                 unset($dis[$nro]['nombre_grupo']);
//                 unset($dis[$nro]['cliente_grupo_id']);
                
//                 if($di['dia']==date('w')){
//                     $dis[$nro]['visitar'] = "si";
//                 }
//                 else{
//                     $dis[$nro]['visitar'] = "no";
//                 }
                
//                 unset($dis[$nro]['orden_cliente']);
//                 unset($dis[$nro]['pagos_realizados']);
//                 unset($dis[$nro]['monto_sumado']);
                
//                 $venx=explode("-",$dis[$nro]['vencimiento']);
//                 $dis[$nro]['vencimiento']=$venx[2]."/".$venx[1]."/".$venx[0];
                
//             }
//             $aux = array();
//             foreach ($dis as $nro => $di) {
//                 if(false){

//                 }else{
//                     array_push($aux,$di);
//                 }
//             }
            
//             $db->commit();
            
//             if(count($aux)>0){
//                 $respuesta = array(
//                     'estado' => 's',
//                     'cliente' => $aux
//                 );
//             }else{
//                 $respuesta = array(
//                     'estado' => 'n',
//                     'msg' => 'array vacio'
//                 );
//             }
//             echo json_encode($respuesta);
//         } catch (Exception $e) {
//             $status = false;
//             $error = $e->getMessage();

//             //Se devuelve el error en mensaje json
//             echo json_encode(array('estado' => 'n', 'msg'=>$error));

//             //se cierra transaccion
//             $db->rollback();
//         }
//     } else {
//         // Devuelve los resultados
//         echo json_encode(array('estado' => 'n', 'msg'=>'no llega el id usuario'));
//     }
// } else {
//     // Devuelve los resultados
//     echo json_encode(array('estado' => 'n', 'msg'=>'no llega ningun dato'));
// }
?>