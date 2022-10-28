<?php

if(is_post()) {
    if (isset($_POST['id_asignacion']) && isset($_POST['id_motivo'])) {
        require config . '/database.php';

        $id_asignacion = trim($_POST['id_asignacion']);
        $id_motivo = $_POST['id_motivo'];


        //echo $id_asignacion ." - ". $id_motivo;


        if ($id_asignacion == 0 || $id_motivo == '') {
            echo json_encode(array('estado' => 'Verifique los datos, uno de los datos esta llegando en 0.'));
            die();
        }
        // Obtenemos la asignacion
        $asignacion = $db->from('inv_asignaciones_clientes')->where('id_asignacion_cliente', $id_asignacion)->fetch_first();

        // Obtenemos el egreso de la asignacion
        $egreso = $db->from('inv_egresos')->where('id_egreso', $asignacion['egreso_id'])->fetch_first();

        // Verificamos qu el egreso no este registrado en el temporal
        $verifica = $db->select('*')->from('tmp_egresos')
                        ->where('distribuidor_estado','NO ENTREGA')
                        ->where('id_egreso',$asignacion['egreso_id'])
                        ->fetch_first();
        if(!$verifica){
            // modificamos el egreso
            $eg = $asignacion['egreso_id'];
            $db->query("UPDATE inv_egresos SET  `tipo` = 'No venta', 
                                                `estadoe` = 4, 
                                                `motivo_id` = '$id_motivo', 
                                                `preventa` = 'habilitado', 
                                                `ingreso_id` = 0 
                        WHERE id_egreso = '$eg'")->execute();
        }

        $db->where('id_asignacion_cliente', $id_asignacion)->update('inv_asignaciones_clientes', array('estado_pedido' => 'reasignado'));

        echo json_encode(array('estado' => 's'));

    } else {
        echo json_encode(array('estado' => 'No llego uno de los datos.'));
    }
} else {
    echo json_encode(array('estado' => 'Verifique el tipo de petición.'));
}

?>