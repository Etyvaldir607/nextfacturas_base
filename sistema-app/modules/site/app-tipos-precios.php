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
    if (isset($_POST['producto_id']) && isset($_POST['tipo_pago'])) {
        
        require config . '/database.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        try {   
            //Se abre nueva transacci贸n.
            $db->autocommit(false);
            $db->beginTransaction();
    
            $id_producto = $_POST['producto_id'];
    
            if($_POST['tipo_pago'] == 'Contado'){
                $tipo_precio1 = $db->select('b.unidad, unidad_id, precio_contado as precio, precio_mayor, cantidad_mayor')
                                   ->from('inv_productos')
                                   ->join('inv_unidades b','unidad_id = b.id_unidad')
                                   ->where('id_producto',$id_producto)
                                   ->fetch_first();
                                   
                // Obtiene los usuarios que cumplen la condicion
                $tipo_precio2 = $db->select('a.unidad_id, a.cantidad_unidad, a.precio_contado as precio, a.precio_mayor, a.cantidad_mayor, b.unidad')
                                   ->from('inv_asignaciones a')
                                   ->join('inv_unidades b','a.unidad_id = b.id_unidad')
                                   ->where('producto_id',$id_producto)->fetch();
            }else{
                $tipo_precio1 = $db->select('precio_actual as precio, b.unidad, unidad_id, precio_contado as precio_mayor, cantidad_mayor')->from('inv_productos')->join('inv_unidades b','unidad_id = b.id_unidad')->where('id_producto',$id_producto)->fetch_first();
                // Obtiene los usuarios que cumplen la condicion
                $tipo_precio2 = $db->select('a.unidad_id, a.cantidad_unidad, a.otro_precio as precio, a.precio_contado as precio_mayor, a.cantidad_mayor, b.unidad')->from('inv_asignaciones a')->join('inv_unidades b','a.unidad_id = b.id_unidad')->where('producto_id',$id_producto)->fetch();
            }
            
    
            $tipos_precios = array();
    
            $tipos_precios[0] = array(
                'unidad' => $tipo_precio1['unidad'],
                'id_unidad' => (int)$tipo_precio1['unidad_id'],
                'cantidad' => '1',
                'precio' => $tipo_precio1['precio'],
                'precio mayor' => $tipo_precio1['precio_mayor'],
                'cantidad_mayor' => $tipo_precio1['cantidad_mayor'],
            );
            $nro = 0;
            foreach($tipo_precio2 as $tipo_precio){
                $nro = $nro + 1;
                $tipos_precios[$nro] = array(
                    'unidad' => $tipo_precio['unidad'],
                    'id_unidad' => (int)$tipo_precio['unidad_id'],
                    'cantidad' => $tipo_precio['cantidad_unidad'],
                    'precio' => $tipo_precio['precio'],
                    'precio mayor' => $tipo_precio['precio_mayor'],
                    'cantidad_mayor' => $tipo_precio['cantidad_mayor']
                );
            }
            $db->commit();
            $respuesta = array(
                'estado' => 's',
                'tipos' => $tipos_precios
            );
            echo json_encode($respuesta);
            // Verifica si el user existe
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
        echo json_encode(array('estado' => 'n', 'msg'=> 'no llega el id producto'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'n', 'msg'=> 'no llega ningun dato'));
}

?>