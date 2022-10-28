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
            $usuario = $db->query(" select id_user, id_empleado, e.fecha, e.hora 
                                    from sys_users 
                                    LEFT JOIN sys_empleados e ON persona_id = id_empleado 
                                    where id_user = '$id_usuario' and active = '1' 
                                    limit 1
                                ")->fetch_first();

            // Verifica la existencia del usuario
            if ($usuario) {
                
                $fecha_actual = strtotime(date("d-m-Y H:i:s",time()));
                $fecha_entrada = strtotime($usuario['fecha']." ".$usuario['hora']);
                	
                if($fecha_actual < $fecha_entrada+7200){
                    
                    
                	$user = array(
    					'fecha' => date("Y-m-d"),
    					'hora' => date("H:i:s"),
    				);
    			
        			$condicion = array('id_empleado' => $usuario['id_empleado'] );
                    $db->where($condicion)->update('sys_empleados', $user);
        			
                
                    //Instancia el objeto
                    $db->commit();
                    
                    $respuesta = array(
                        'estado' => 's', //.$fecha_actual." - ".$fecha_entrada,
                    );
                }else{
                    //Instancia el objeto
                    $db->commit();
                    
                    $respuesta = array(
                        'estado' => 'n',
                        'msg' => 'sin actividad, hace 2 horas'
                    );
                }
                
                // Devuelve los resultados
                echo json_encode($respuesta);
            } else {
                // Devuelve los resultados
                $db->commit();
                echo json_encode(array('estado' => 'n','msg' => 'no existe el usuario'));
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
        echo json_encode(array('estado' => 'n','msg' => 'no se envio el id usuario'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'n','msg' => 'no se envio ningun dato'));
}

?>