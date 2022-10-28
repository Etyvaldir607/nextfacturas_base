<?php

    $id_asignacion = (isset($params[0])) ? $params[0] : 0;

    $asignacion = $db->from('inv_asignaciones_clientes')->where('id_asignacion_cliente', $id_asignacion)->fetch_first();

    if ($asignacion) {

        if ($asignacion['estado_pedido'] == 'entregado') {
            // Instancia la variable de notificacion
            set_notification('danger', 'Acción insatisfactoria!', 'No se puede realizar esta acción, la preventa ya fue entregada.');
            // Redirecciona a la pagina principal
            return redirect(back());
        } else {
            // Elimina el user
            $db->delete()->from('inv_asignaciones_clientes')->where('id_asignacion_cliente', $id_asignacion)->limit(1)->execute();

            $modificado = array(
                'tipo' => 'Preventa',
                'estadoe' => 2,
                'motivo_id' => 0
            );
            $db->where('id_egreso', $asignacion['egreso_id'])->update('inv_egresos', $modificado);

            // Verifica si fue el user eliminado
            if ($db->affected_rows) {
                // Define la variable de error
                set_notification('success', 'Eliminación satisfactoria!', 'La asignación fue eliminada satisfactoriamente.');
            }

             // Guarda en el historial
            $data = array(
                'fecha_proceso' => date("Y-m-d"),
                'hora_proceso' => date("H:i:s"),
                'proceso' => 'c',
                'nivel' => 'l',
                'direccion' => '?/asignacion/preventas_eliminar',
                'detalle' => 'Se eliminó la asignacion cliente con identificador numero ' . $id_asignacion,
                'usuario_id' => $_SESSION[user]['id_user']
            );
            $db->insert('sys_procesos', $data);


            // Redirecciona a la pagina principal
            redirect(back());
        }

    } else {
        // Instancia la variable de notificacion
        set_notification('danger', 'Acción insatisfactoria!', 'No se puede realizar esta accion, verifique los datos.');
		// Redirecciona a la pagina principal
		redirect(back());
    }

?>


?>