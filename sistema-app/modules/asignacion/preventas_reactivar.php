<?php

    $id_venta = (isset($params[0])) ? $params[0] : 0;

    $preventa = $db->from('inv_egresos')->where('id_egreso', $id_venta)->fetch_first();

    // echo json_encode($preventa); die();

    if ($preventa) {

        if ($preventa['preventa'] == 'anulado') {
            // Actualiza la informacion
            $A = 'NULL';
            $db->where('id_egreso', $id_venta)->update('inv_egresos', array('preventa' => 'habilitado' ));

            set_notification('success', 'Acción satisfactoria!', 'La preventa fue anulada satisfactoriamente.');

            // Guarda en el historial
            $data = array(
                'fecha_proceso' => date("Y-m-d"),
                'hora_proceso' => date("H:i:s"),
                'proceso' => 'c',
                'nivel' => 'l',
                'direccion' => '?/asignacion/preventas_reactivar',
                'detalle' => 'Se modifico el egreso (preventa) con identificador numero ' . $id_venta,
                'usuario_id' => $_SESSION[user]['id_user']
            );
            $db->insert('sys_procesos', $data);

            // Redirecciona a la pagina principal
            redirect('?/asignacion/preventas_listar');

        } else {
            // Instancia la variable de notificacion
            set_notification('warning', 'Acción insatisfactoria!', 'No se puede realizar esta acción, la preventa no esta anulada.');
            // Redirecciona a la pagina principal
            return redirect(back());
        }

    } else {
        // Instancia la variable de notificacion
        set_notification('danger', 'Acción insatisfactoria!', 'No se puede realizar esta accion, verifique los datos.');
		// Redirecciona a la pagina principal
		redirect(back());
    }

?>