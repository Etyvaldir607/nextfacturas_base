<?php

if(is_post()) {
    if (isset($_POST['id_asignacion']) && isset($_POST['id_motivo'])) {
        require config . '/database.php';

        $id_asignacion = trim($_POST['id_asignacion']);
        $id_motivo = trim($_POST['id_motivo']);

        if ($id_asignacion == 0 || $id_motivo == 0) {
            echo json_encode(array('estado' => 'Verifique los datos, uno de los datos est谩 llegando en 0.'));
            die();
        }
        // Obtenemos la asignacion
        $asignacion = $db->from('inv_asignaciones_clientes')->where('id_asignacion_cliente', $id_asignacion)->fetch_first();

        // Obtenemos el egresod e la asignacion
        $egreso = $db->from('inv_egresos')->where('id_egreso', $asignacion['egreso_id'])->fetch_first();

        // Verificamos qu el egreso no este registrado en el temporal
        $verifica = $db->select('*')->from('tmp_egresos')
                        ->where('distribuidor_estado','NO ENTREGA')
                        ->where('id_egreso',$asignacion['egreso_id'])
                        ->fetch_first();
        if(!$verifica){
            // //numero de movimiento
            // $movimiento = generarMovimiento($db, $_user['persona_id'], 'DV', $egreso['almacen_id']);

            // // creamos el ingreso
            // $ingreso = array(
            //     'fecha_ingreso' => date('Y-m-d'),
            //     'hora_ingreso' => date('H:i'),
            //     'tipo' => 'Devolucion',
            //     'descripcion' => 'Devolucion por preventa que ya NO QUIERE',
            //     'monto_total' => 0.00,
            //     'descuento' => 0.00,
            //     'monto_total_descuento' => 0.00,
            //     'nro_movimiento' => $movimiento,
            //     'tipo_pago' => 'EFECTIVO',
            //     'nombre_proveedor' => $egreso['nombre_cliente'],
            //     'nro_registros' => $egreso['nro_registros'],
            //     'almacen_id' => $egreso['almacen_id'],
            //     'empleado_id' => $egreso['empleado_id'],
            //     'plan_de_pagos' => 'no',
            //     'egreso_id' => $egreso['id_egreso'],
            //     'tipo_devol' => 'preventa'
            // );
            // $ingreso_id = $db->insert('inv_ingresos', $ingreso);
            // // Guarda Historial
            // $data = array(
            //     'fecha_proceso' => date("Y-m-d"),
            //     'hora_proceso' => date("H:i:s"),
            //     'proceso' => 'c',
            //     'nivel' => 'l',
            //     'direccion' => '?/asignacion/preventas_noquiere',
            //     'detalle' => 'Se creo ingreso con identificador numero ' . $ingreso_id,
            //     'usuario_id' => $_SESSION[user]['id_user']
            // );
            // $db->insert('sys_procesos', $data);

            // // pasamos los detalles del egreso a detalles de ingreso
            // $detalles = $db->from('inv_egresos_detalles')->where('egreso_id', $asignacion['egreso_id'])->fetch();
            // $subtotal = 0;
            // foreach ($detalles as $key => $detalle) {
            //     $deting = $db->from('inv_ingresos_detalles')
            //                  ->where('producto_id', $detalle['producto_id'])
            //                  ->where('lote', $detalle['lote'])
            //                  ->where('vencimiento', $detalle['vencimiento'])
            //                  ->fetch_first();
            //     $subtotal = $subtotal + ($deting['costo'] * $detalle['cantidad']);
            //     $detail = array(
            //         'cantidad' => $detalle['cantidad'],
            //         'costo' => $deting['costo'],
            //         'vencimiento' => $detalle['vencimiento'],
            //         'dui' => 0,
            //         'lote2' => $detalle['lote'],
            //         'factura' => $deting['factura'],
            //         'factura_v' => $deting['factura_v'],
            //         'contenedor' => 0,
            //         'producto_id' => $detalle['producto_id'],
            //         'ingreso_id' => $ingreso_id,
            //         'IVA' => $deting['IVA'],
            //         'lote' => $detalle['lote'],
            //         'lote_cantidad' => $detalle['cantidad'],
            //         'costo_sin_factura'=> 0.00,
            //     );
            //     // Guarda la informacion
            //     $id_detalle = $db->insert('inv_ingresos_detalles', $detail);
            //     // Guarda Historial
            //     $data = array(
            //         'fecha_proceso' => date("Y-m-d"),
            //         'hora_proceso' => date("H:i:s"),
            //         'proceso' => 'c',
            //         'nivel' => 'l',
            //         'direccion' => '?/asignacion/preventas_noquiere',
            //         'detalle' => 'Se creo ingreso detalle con identificador numero ' . $id_detalle,
            //         'usuario_id' => $_SESSION[user]['id_user']
            //     );
            //     $db->insert('sys_procesos', $data);
            // }
            // // modificamos el egreso
            // $eg = $asignacion['egreso_id'];
            // $db->query("UPDATE inv_egresos SET `tipo` = 'No venta', `estadoe` = 4, `motivo_id` = '$id_motivo', `preventa` = NULL, `ingreso_id` = $ingreso_id WHERE id_egreso = '$eg'")->execute();
            // // Guarda Historial
            // $data = array(
            //     'fecha_proceso' => date("Y-m-d"),
            //     'hora_proceso' => date("H:i:s"),
            //     'proceso' => 'c',
            //     'nivel' => 'l',
            //     'direccion' => '?/asignacion/preventas_noquiere',
            //     'detalle' => 'Se actualizo egreso con identificador numero ' . $egreso['egreso_id'],
            //     'usuario_id' => $_SESSION[user]['id_user']
            // );
            // $db->insert('sys_procesos', $data);

            // $db->where('id_ingreso', $ingreso_id)->update('inv_ingresos', array('monto_total' => $subtotal));
        }

        // echo $db->last_query();
        // $eg = $asignacion['egreso_id'];
        // $db->query("UPDATE inv_egresos SET `tipo` = 'No venta', `estadoe` = 4, `motivo_id` = '$id_motivo', `preventa` = NULL WHERE id_egreso = '$eg'")->execute();
        $db->where('id_asignacion_cliente', $id_asignacion)->update('inv_asignaciones_clientes', array('estado_pedido' => 'reasignado'));

        echo json_encode(array('estado' => 's'));

    } else {
        echo json_encode(array('estado' => 'No llego uno de los datos.'));
    }
} else {
    echo json_encode(array('estado' => 'Verifique el tipo de petici贸n.'));
}

?>