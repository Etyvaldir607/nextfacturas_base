<?php

$id_venta = (isset($params[0])) ? $params[0] : 0;

$venta = $db->query('select MAX(nro_salida)as nro_salida
                         from inv_asignaciones_clientes')
            ->fetch_first();

$asignacion = array(
    'egreso_id'         => $id_venta,
    'distribuidor_id'   => $_user['persona_id'],
    'fecha_entrega'     => date('Y-m-d H:i:s'),
    'estado_pedido'     => 'salida',
    'empleado_id'       => $_user['persona_id'],
    'estado'            => 'A',
    'fecha_hora_salida' => date('Y-m-d H:i:s'), 
    'nro_salida'        => ($venta['nro_salida']+1)
);
// Guardamos el asignacion
$id_asignacion = $db->insert('inv_asignaciones_clientes', $asignacion);

return redirect('?/asignacion/preventas_listar/'.$id_venta);

?>