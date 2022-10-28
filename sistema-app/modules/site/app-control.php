<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: application/json; charset=utf-8');
header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
date_default_timezone_set('America/La_Paz');

if(is_post()) {
    if (isset($_POST['id_user']) && isset($_POST['latitud']) && isset($_POST['longitud']) && isset($_POST['fecha_hora']) ) {
        require config . '/database.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        try {   
            //Se abre nueva transacci贸n.
            $db->autocommit(false);
            $db->beginTransaction();
         
            $id_user = $_POST['id_user'];
            $latitud = $_POST['latitud'];
            $logitud = $_POST['longitud'];
            
            $fecha_hora = $_POST['fecha_hora'];
            $fecha_ext=explode(" ",$fecha_hora);
            
            $sesion = isset($_POST['sesion'])? $_POST['sesion'] : '0';
            
            $fecha = date('Y-m-d');
            $hora = '*'.$fecha_ext[1];
            $sesion = '*'.$sesion;
            //$ubicacion
            $coordenada = '*'.$latitud.','.$logitud;
            
            if($fecha_ext[0]==$fecha){
                $ubicacion = $db->select('*')->from('gps_seguimientos')->where('user_id',$id_user)->where('fecha_seguimiento',date('Y-m-d'))->fetch_first();
                
                if($ubicacion){
                    
                    $horas=explode("*",$ubicacion['hora_seguimiento']);
                    
                    $ultima_hora=$horas[count($horas)-1];
                    
                    $hora_aux=explode(":",$ultima_hora);
                    $sum_hora_aux=($hora_aux[0]*60*60)+($hora_aux[1]*60)+($hora_aux[2]);
                    
                    $hora_ext=explode(":",$fecha_ext[1]);
                    $sum_hora_ext=($hora_ext[0]*60*60)+($hora_ext[1]*60)+($hora_ext[2]);
                    
                    if($sum_hora_ext>$sum_hora_aux){
                        $datos = array(
                            'sesion' => $ubicacion['sesion'].$sesion,
                            'coordenadas' => $ubicacion['coordenadas'].$coordenada,
                            'hora_seguimiento' => $ubicacion['hora_seguimiento'].$hora
                        );
                        $db->where(array('user_id' => $id_user, 'fecha_seguimiento' => $fecha))->update('gps_seguimientos',$datos);
                    }
                    
                }else{
                    $datos = array(
                        'sesion' => $sesion,
                        'coordenadas' => $coordenada,
                        'fecha_seguimiento' => $fecha,
                        'hora_seguimiento' => $hora,
                        'user_id' => $id_user
                    );
                    $id = $db->insert('gps_seguimientos',$datos);
                }
    
                $db->commit();
                
                $respuesta = array(
                    'estado' => 's',
                    'msg'=>$ultima_hora." ".$sum_hora_aux
                );
                echo json_encode($respuesta);
            }
            else{
                $respuesta = array(
                    'estado' => 'n',
                    'msg' => 'fecha_incorrecta'
                );
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
        echo json_encode(array('estado' => 'n', 'msg'=> 'no llego uno de los datos'));
    }
}else{
    echo json_encode(array('estado' => 'n', 'msg'=> 'no llego los datos'));
}
?>