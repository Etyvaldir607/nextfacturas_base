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
    if( isset($_POST['id_user']) && isset($_POST['id_cliente']) ) {

        // Importa la configuracion para el manejo de la base de datos
        require config . '/database.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        try {   
            //Se abre nueva transacci贸n.
            $db->autocommit(false);
            $db->beginTransaction();
    
            $cliente_id = $_POST['id_cliente'];
            $id_user = $_POST['id_user'];
            
            $area =  $db->select('*')
                        ->from('sys_users a')
                        ->join('sys_empleados b', 'a.persona_id = b.id_empleado')
                        ->where('a.id_user',$id_user)
                        ->fetch_first();

            $id_empleado = $area['id_empleado'];
            
    
    
            $ultimo_despacho =   $db->select('MAX(fecha_hora_salida)as fecha_hora_salida')
                                ->from('inv_asignaciones_clientes')
                                ->where('distribuidor_id',$id_empleado)
                                ->where('estado','A')
                                ->fetch_first();
        
            $usuarios_query = " select e.id_egreso, e.cliente_id, e.nro_nota, descripcion_venta, monto_total, plan_de_pagos, estadoe
                                from inv_asignaciones_clientes ac
                                inner join inv_egresos e ON id_egreso=egreso_id
                                where   distribuidor_id='$id_empleado' 
                                        AND ac.estado='A' 
                                        AND ac.fecha_hora_salida ='".$ultimo_despacho['fecha_hora_salida']."'
                                        AND cliente_id='".$cliente_id."'
                                ";

            $usuarios = $db->query($usuarios_query)->fetch();


            // $proformas1query = "select *
            //                     from inv_egresos a
            //                     left join sys_empleados e ON a.vendedor_id = e.id_empleado
            //                     left join inv_clientes c ON a.cliente_id = c.id_cliente
                                
            //                     where   a.id_egreso ='".$usuario['egreso_id']."'
            //                             and a.estadoe >=2
            //                             and a.estadoe <=4";
                                
                                //->join('gps_noventa_motivos g', 'a.motivo_id = g.id_motivo', 'left')
                                //->where('a.preventa', 'habilitado')
                                //->where('a.fecha_egreso >=',$fecha_inicial)
                                //->where('a.fecha_egreso <=',$fecha_final)
    
            $proformas1 = $db->query($proformas1query)->fetch();
            
    
            $db->commit();
                        
            if($usuarios){
                $respuesta = array(
                    'estado' => 's',
                    'ventas' => $usuarios
                );
                echo json_encode($respuesta);
            }else{
                // Instancia el objeto
                $respuesta = array(
                    'estado' => 'n', 'msg'=>'no existen entregas'//.$usuarios_query
                );
                // Devuelve los resultados
                echo json_encode($respuesta);
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
        echo json_encode(array('estado' => 'n', 'msg'=>'no llega el id usuario'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'n', 'msg' => 'no llega ningun dato'));
}

?>