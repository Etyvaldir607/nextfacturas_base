<?php

/**
 * FunctionPHP - Framework Functional PHP
 *
 * @package  FunctionPHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Define las cabeceras
header('Content-Type: application/json');

// Verifica la peticion post
if (is_post()) {
    // Verifica la existencia de datos
    if (isset($_POST['id_user'])) {
        // Importa la configuracion para el manejo de la base de datos
        require config . '/database.php';
        $egresos = (isset($_POST['id_egreso'])) ? $_POST['id_egreso'] : array();
        $id_user = $_POST['id_user'];
        $user = $db->select('*')->from('sys_users')->where('id_user',$id_user)->fetch_first();

        if($egresos){
            $egresos = str_replace('[','',$egresos);
            $egresos = str_replace(']','',$egresos);
            $egresos = str_replace('"','',$egresos);
            $egreso = explode(',',$egresos);
            $egresos = array_unique($egreso);
            foreach ($egresos as $nro => $egreso) {
                $id_egreso = $egresos[$nro];
                $datos_egreso = $db->select('b.*, b.fecha_egreso as distribuidor_fecha , b.hora_egreso as distribuidor_hora, b.almacen_id as distribuidor_estado, b.almacen_id as distribuidor_id, b.estadoe as estado')->from('inv_egresos b')->where('b.id_egreso',$id_egreso)->fetch_first();
                if($datos_egreso){
                    $db->where('id_egreso',$id_egreso)->update('inv_egresos',array('estadoe' => 3));
                    $datos_egreso['distribuidor_fecha'] = date('Y-m-d');
                    $datos_egreso['distribuidor_hora'] = date('H:i:s');
                    $datos_egreso['distribuidor_estado'] = 'ENTREGA';
                    $datos_egreso['distribuidor_id'] = $user['persona_id'];
                    $datos_egreso['estado'] = 3;
                    $id = $db->insert('tmp_egresos', $datos_egreso);
                    $egresos_detalles = $db->select('*, egreso_id as tmp_egreso_id')->from('inv_egresos_detalles')->where('egreso_id',$id_egreso)->fetch();
                    foreach ($egresos_detalles as $nr => $detalle) {
                        $detalle['tmp_egreso_id'] = $id;
                        $db->insert('tmp_egresos_detalles', $detalle);
                    }
                }
            }
            $respuesta = array(
                'estado' => 's'
            );
            echo json_encode($respuesta);
        }else{

            // Instancia el objeto
            $respuesta = array(
                'estado' => 'no exite ventas'
            );

            // Devuelve los resultados
            echo json_encode($respuesta);
        }
    } else {
        // Devuelve los resultados
        echo json_encode(array('estado' => 'no llega el id usuario'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'no llega ningun dato'));
}

?>