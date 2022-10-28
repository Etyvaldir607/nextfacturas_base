<?php
    $id_venta = (isset($params[0])) ? $params[0] : 0;

    $preventa = $db->from('inv_egresos')->where('id_egreso', $id_venta)->fetch_first();

    //json_encode($preventa); die();

    if ($preventa) {
        if ($preventa['preventa'] == 'anulado') {
            // Instancia la variable de notificacion
            set_notification('warning', 'Accion insatisfactoria!', 'No se puede realizar esta acci贸n, la venta ya fue anulada.');
            // Redirecciona a la pagina principal
            return redirect(back());
        } else {
            $pagos = $db->query("   SELECT * 
                                    FROM inv_pagos 
                                    inner join inv_pagos_detalles on id_pago=pago_id 
                                    WHERE movimiento_id='$id_venta' AND tipo='Egreso' AND estado=1")->fetch();
            if($pagos){     
                set_notification('warning', 'Accion insatisfactoria!', 'No se puede realizar esta accion, la venta tiene cuotas cobradas.');
            }else{
                if ($preventa['nro_nota'] != 0) {
                    
                    $movimiento = generarMovimiento($db, $_user['persona_id'], '', $preventa['almacen_id'] );
                                
                    $ingreso = array(
                                    'fecha_ingreso' => date('Y-m-d'),
                                    'hora_ingreso' => date('H:i:s'),
                                    'tipo' => 'Anulado',
                                    'nro_movimiento' => $movimiento, // + 1
                                    'descripcion' => '',
                                    'monto_total' => 0,
                                    'nombre_proveedor' => mb_strtoupper($preventa['nombre_cliente'], 'UTF-8'),
                                    'nro_registros' => 1,
                                    'transitorio' => 0,
                                    'des_transitorio' => 0,
                                    'plan_de_pagos' => 'no',
                                    'empleado_id' => $_user['persona_id'],
                                    'almacen_id' => $preventa['almacen_id'],
                                    'tipo_pago' => '',
                                    'nro_pago' =>'',
                                    'egreso_id' =>$id_venta,
                                    // 	'nro_movimiento' => 0
                                );
                                
                    // Guarda la informacion
                    $ingreso_id = $db->insert('inv_ingresos', $ingreso);
    
                    $consulta = $db->from('inv_egresos_detalles')->where('egreso_id', $id_venta)->fetch();
                
                    foreach($consulta as $key=>$Dato){
                    
                        $datos_ingreso =  $db->select('d.*')
                                        ->from('inv_ingresos_detalles d')
                                        ->join('inv_ingresos i', 'd.ingreso_id = i.id_ingreso', 'left')
                                        ->where('i.almacen_id', $preventa['almacen_id'])
                                        ->where('d.producto_id', $Dato['producto_id'])
                                        ->where('d.lote', $Dato['lote'])
                                        ->where('d.vencimiento', $Dato['vencimiento'])
                                        ->fetch_first();
                        
                        $detalleI = array(
                                    'cantidad' => $Dato['cantidad'],
                                    'costo' => $datos_ingreso['costo'],
                                    'precio' => 0,
                                    'vencimiento' => $datos_ingreso['vencimiento'],
                                    'dui' => $datos_ingreso['dui'],
                                    'lote2' => $datos_ingreso['lote2'],
                                    'factura' => $datos_ingreso['factura'],
                                    'factura_v' => $datos_ingreso['factura_v'],
                                    'contenedor' => $datos_ingreso['contenedor'],
                                    'producto_id' => $datos_ingreso['producto_id'],
                                    'ingreso_id' => $ingreso_id,
                                    // 'lote' => 'lt' . ($Cantidad + 1),
                                    'IVA' => $datos_ingreso['IVA'],
                                    'lote' => $datos_ingreso['lote'],
                                    'lote_cantidad' => $Dato['cantidad'],
                                    'costo_sin_factura'=>0,
                                );
                                // Guarda la informacion
    
                        $id_detalleI = $db->insert('inv_ingresos_detalles', $detalleI);
                    }
                }
    
                $modificado = array(
                    'preventa' => 'anulado'
                );
    
                // Actualiza la informacion
                $db->where('id_egreso', $id_venta)->update('inv_egresos', $modificado);
    
                set_notification('success', 'Accion satisfactoria!', 'La venta fue anulada satisfactoriamente.');
    
                 // Guarda en el historial
                $data = array(
                    'fecha_proceso' => date("Y-m-d"),
                    'hora_proceso' => date("H:i:s"),
                    'proceso' => 'c',
                    'nivel' => 'l',
                    'direccion' => '?/asignacion/habilitar',
                    'detalle' => 'Se modifico el egreso (venta) con identificador numero ' . $id_venta,
                    'usuario_id' => $_SESSION[user]['id_user']
                );
                $db->insert('sys_procesos', $data);
            }
            
            // Redirecciona a la pagina principal
            redirect('?/notas/mostrar');
        }

    } else {
        // Instancia la variable de notificacion
        set_notification('danger', 'Acci贸n insatisfactoria!', 'No se puede realizar esta accion, verifique los datos.');
		// Redirecciona a la pagina principal
		redirect(back());
    }
?>
