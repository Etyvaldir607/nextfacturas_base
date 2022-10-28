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
    if (isset($_POST['id_user']) && isset($_POST['id_egreso'])) {
        // Importa la configuracion para el manejo de la base de datos
        require config . '/database.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        try {   
            //Se abre nueva transacci贸n.
            $db->autocommit(false);
            $db->beginTransaction();
         
            $egreso_id = $_POST['id_egreso'];
    
            // Obtiene los usuarios que cumplen la condicion
            $usuario = $db->from('sys_users')
                          ->join('sys_empleados','persona_id = id_empleado')
                          ->where('id_user',$_POST['id_user'])
                          ->fetch_first();
                          
            //$emp = $usuario['id_empleado'];
    
            //buscar si es vendedor o distribuidor
            $deudas = $db->query("  select fecha,monto_programado
                                    from inv_pagos a
                                    INNER join inv_pagos_detalles ON id_pago=pago_id
                                    where fecha!='0000-00-00' and monto>0 and movimiento_id='$egreso_id' AND tipo='Egreso' 
                                    ")
                        ->fetch();
            
            foreach($deudas as $nro => $deuda){
                $vec=explode("-", $deuda['fecha']);
                $deudas[$nro]['fecha']=$vec[2]."/".$vec[1]."/".$vec[0];
            }
            
            $db->commit();
            
            if(count($deudas)>0){
                $respuesta = array(
                    'estado' => 's',
                    'deudas' => $deudas
                );
            }else{
                $respuesta = array(
                    'estado' => 'n',
                    'msg' => 'no existen plan de pagos programado'
                );
            }
            echo json_encode($respuesta);
        
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
        echo json_encode(array('estado' => 'n','msg' => 'no llega el id usuario'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'n','msg'  => 'no llega ningun dato'));
}

?>