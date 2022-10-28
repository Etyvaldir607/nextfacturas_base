<?php
require config . '/database.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

try {   
    //Se abre nueva transacci贸n.
    $db->autocommit(false);
    $db->beginTransaction();
    
    $id_venta = (isset($params[0])) ? $params[0] : 0;
    $preventa =  $db->from('inv_egresos')
                    ->where('id_egreso', $id_venta)
                    ->fetch_first();

    if ($preventa) {

        if ($preventa['preventa'] == 'habilitado') {
            // Instancia la variable de notificacion
            set_notification('warning', 'Acción insatisfactoria!', 'No se puede realizar esta acción, la preventa ya fue habilitada.');
            // Redirecciona a la pagina principal
            return redirect(back());
        } else {
            // Verificamos que se tenga el suficiente stock
            $detallesV = $db->from('inv_egresos_detalles')
                            ->where('egreso_id', $preventa['id_egreso'])
                            ->fetch();
                            
            // echo json_encode($detallesV); die();
            foreach ($detallesV as $key => $det) {
                $cantidad = $det['cantidad'];
                
                $ingresoV = $db->query("SELECT SUM(lote_cantidad)as lote_cantidad
                                        FROM inv_ingresos_detalles
                                        LEFT JOIN inv_ingresos i ON id_ingreso=ingreso_id
                                        WHERE i.almacen_id='".$preventa['almacen_id']."'
                                              AND producto_id='".$det['producto_id']."'
                                              AND lote='".$det['lote']."'
                                              AND vencimiento='".$det['vencimiento']."'
                                      ")->fetch_first();
    
                if ($cantidad > $ingresoV['lote_cantidad']) {

                    $producto =  $db->from('inv_productos')
                                    ->where('id_producto', $det['producto_id'])
                                    ->fetch_first();

                    set_notification('danger', '¡No existe Stock suficiente!', 'El producto: '.$producto['nombre'].' solo tiene: '.$ingresoV['lote_cantidad'].' y se require: <b>'.$cantidad.'</b>');

                    // Redirecciona a la pagina principal
                    
                    return redirect(back());
                    die();
                }
            }

            $movimiento = generarMovimiento($db, trim($preventa['empleado_id']), 'PV', trim($preventa['almacen_id']));

            // Obtiene el numero de nota
    		$nro_factura = $db->query(" select MAX(nro_nota) + 1 as nro_factura 
                                        from inv_egresos 
                                     ")->fetch_first();
                                     //where tipo = 'Venta' and provisionado = 'S'
                                    
    		$nro_factura = $nro_factura['nro_factura'];

            // Actualizar el egreso
            $modificado = array(
                'tipo' => 'Venta',
                'preventa' => 'habilitado',
                'nro_movimiento' => $movimiento,
                'nro_nota' => $nro_factura,
                'fecha_habilitacion' => date('Y-m-d H:i:s')
            );
            $db->where('id_egreso', $id_venta)->update('inv_egresos', $modificado);

            // Actualizamos el lote cantidad de los ingresos
            $detalles =  $db->select('*')
                            ->from('inv_egresos_detalles')
                            ->where('egreso_id', $preventa['id_egreso'])
                            ->fetch();
                            
            // echo json_encode($detalles); die();
            foreach ($detalles as $key => $detalle) {
                $cantidad = $detalle['cantidad'];
                
                // echo json_encode($cantidad); die();
                
                $LOTE=0;
                $VENC=0;
                $PROD=0;
                $ALMA=0;
                $stockNegativo=0;

                $ingresos_query = ' SELECT *
                                    FROM inv_ingresos_detalles
                                    LEFT JOIN inv_ingresos i ON id_ingreso=ingreso_id
                                    WHERE i.almacen_id="'.$preventa['almacen_id'].'"
                                          AND producto_id="'.$detalle['producto_id'].'"
                                          AND lote="'.$detalle['lote'].'"
                                          AND vencimiento="'.$detalle['vencimiento'].'"
                                    ORDER BY id_ingreso ';
            
                $ingresosX = $db->query($ingresos_query)->fetch();

                foreach ($ingresosX as $nroX => $ingress) { 
                    if($ingress['lote']==$LOTE && $ingress['producto_id']==$PROD && $ingress['almacen_id']==$ALMA){
                        $egresoSuma=$stockNegativo;
                    }else{
                        $egresoSuma=$cantidad;
                    }
                    
                    if($ingress['lote_cantidad']<$egresoSuma){
                        $stock=0;
                        $stockNegativo=$egresoSuma-$ingress['lote_cantidad'];
                        $cantidad_egreso=$ingress['lote_cantidad'];
                    }
                    else{
                        $stock=$ingress['lote_cantidad']-$egresoSuma;
                        $stockNegativo=0;
                        $cantidad_egreso=$egresoSuma;
                    }
                    $db->query('UPDATE inv_ingresos_detalles SET lote_cantidad="'.($stock).'" WHERE id_detalle="'.$ingress['id_detalle'].'"')->execute();
                    
                    $LOTE=$ingress['lote'];
                    $PROD=$ingress['producto_id'];
                    $ALMA=$ingress['almacen_id'];
                    
                    if($cantidad_egreso>0){
                        $detalle123 = array(
            				'cantidad' => $cantidad_egreso,
            				'precio' => $detalle['precio'],
                            'unidad_id' => $detalle['unidad_id'],
        					'descuento' => 0,
            				'producto_id' => $detalle['producto_id'],
            				'egreso_id' => $detalle['egreso_id'],
            				'lote' => $detalle['lote'],
            				'vencimiento' => $detalle['vencimiento'],
            				'ingresos_detalles_id'=>$ingress['id_detalle'],
            			);
                        $id_detalle = $db->insert('inv_egresos_detalles', $detalle123);
                    }
                }
            
                $db->delete()->from('inv_egresos_detalles')->where('id_detalle', $detalle['id_detalle'])->limit(1)->execute();
            }

            set_notification('success', 'Acción satisfactoria!', 'La preventa fue habilitada satisfactoriamente.');

             // Guarda en el historial
            $data = array(
                'fecha_proceso' => date("Y-m-d"),
                'hora_proceso' => date("H:i:s"),
                'proceso' => 'c',
                'nivel' => 'l',
                'direccion' => '?/asignacion/habilitar',
                'detalle' => 'Se modifico el egreso (preventa) con identificador numero ' . $id_venta,
                'usuario_id' => $_SESSION[user]['id_user']
            );
            $db->insert('sys_procesos', $data);



            if($preventa['distribuir']=='N'){
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
            }



            // Redirecciona a la pagina principal
            $db->commit();
            redirect('?/asignacion/preventas_listar');
        }
    } else {
        // Instancia la variable de notificacion
        $db->commit();
        set_notification('danger', 'Acción insatisfactoria!', 'No se puede realizar esta accion, verifique los datos.');
		// Redirecciona a la pagina principal
		redirect(back());
    }
} catch (Exception $e) {
    $status = false;
    $error = $e->getMessage();

    //Se devuelve el error en mensaje json
    echo json_encode(array('estado' => 'n', 'msg'=>$error));

    set_notification('danger', 'Acción insatisfactoria!', 'No se pudo habilitar, verifique los datos.');
	redirect(back());

    //se cierra transaccion
    $db->rollback();
}
?>
