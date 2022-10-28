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
    if (isset($_POST['id_user'])) {
        // Importa la configuracion para el manejo de la base de datos
        require config . '/database.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        try {   
            //Se abre nueva transacci贸n.
            $db->autocommit(false);
            $db->beginTransaction();
                // Obtiene los datos
            $id_usuario = trim($_POST['id_user']);

            // Obtiene los usuarios que cumplen la condicion
            $usuario = $db->query("select id_user, id_empleado from sys_users LEFT JOIN sys_empleados ON persona_id = id_empleado where  id_user = '$id_usuario' and active = '1' limit 1")->fetch_first();

            // Verifica la existencia del usuario
            if ($usuario) {
                
                $total = $db->select('ifnull(SUM(monto_total),0) as total, COUNT(cliente_id) as cont')
                            ->from('inv_egresos')
                            ->where('empleado_id',$usuario['id_empleado'])
                            ->where('estadoe',2)
                            ->where('fecha_egreso',date('Y-m-d'))
                            ->fetch_first();
                            
                $auxf = date('Y-m-d');
                $egresos =   $db->select('*, descripcion_venta as observacion')
                                ->from('inv_egresos')
                                ->where('empleado_id',$usuario['id_empleado'])
                                ->where('estadoe',2)
                                ->where('fecha_egreso',$auxf)
                                ->order_by('id_egreso')
                                ->fetch();
                                
                foreach($egresos as $nro5 => $egreso){
                    $detalles =  $db->query("select b.nombre_factura, a.precio, a.cantidad, a.producto_id
                                            from inv_egresos_detalles a
                                            left join inv_productos b on a.producto_id = b.id_producto
                                            where a.egreso_id='".$egreso['id_egreso']."'")
                                    ->fetch();
                    foreach($detalles as $nro6 => $detalle){
                        //$detalles[$nro6]['cantidad'] = (int)($detalle['cantidad']/cantidad_unidad($db, $detalle['producto_id'], $detalle['unidad_id']));
                        $detalles[$nro6]['cantidad'] = (int)($detalle['cantidad']);
                        //$detalles[$nro6]['unidad'] = nombre_unidad($db,$detalle['unidad_id']);
                        $detalles[$nro6]['unidad'] = $detalle['precio'];
                    }
                    $egresos[$nro5]['descripcion_venta'] = $detalles;
                }
                
                $db->commit();

                if(count($egresos)>0){
                    //Instancia el objeto
                    $respuesta = array(
                        'estado' => 's',
                        'nro_clientes' => $total['cont'],
                        'total' => $total['total'],
                        'cliente' => $egresos
                    );
                }else{
                    //Instancia el objeto
                    $respuesta = array(
                        'estado' => 'n',
                        'nro_clientes' => 0,
                        'total' => 0,
                    );
                }
                
                // Devuelve los resultados
                echo json_encode($respuesta);
            } else {
                // Devuelve los resultados
                $db->commit();
                echo json_encode(array('estado' => 'n','msg'=>'2'));
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
        echo json_encode(array('estado' => 'n','msg'=>'3'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'n','msg'=>'4'));
}

?>