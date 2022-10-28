<?php
// echo json_encode($_POST); die();

// Verifica si es POST
if (is_post()) {
	// Verifica la existencia de los datos enviados
    if (isset($_POST['id_egreso']) && isset($_POST['id_cliente']) && isset($_POST['nit_ci']) && isset($_POST['nombre_cliente']) && 
        isset($_POST['productos']) && isset($_POST['nombres']) && isset($_POST['lotes']) && isset($_POST['vencimiento']) && 
        isset($_POST['cantidades']) && isset($_POST['unidad'])&&  isset($_POST['tipo']) ) {
		
		// Importa la libreria para convertir el numero a letra
        require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

        $id_egreso = trim($_POST['id_egreso']);
        $id_cliente = trim($_POST['id_cliente']);
        $nit_ci = trim($_POST['nit_ci']);
        $nombre_cliente = trim($_POST['nombre_cliente']);
        $nro_factura = trim($_POST['nro_factura']);
        $motivo = trim($_POST['motivo']);
        $descripcion = trim($_POST['descripcion']);

        $productos = (isset($_POST['productos'])) ? $_POST['productos'] : array();
        $nombres = (isset($_POST['nombres'])) ? $_POST['nombres'] : array();
        $lotes = (isset($_POST['lotes'])) ? $_POST['lotes'] : array();
        $vencimiento = (isset($_POST['vencimiento'])) ? $_POST['vencimiento'] : array();
        $cantidades = (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
        $unidad = (isset($_POST['unidad'])) ? $_POST['unidad'] : array();
        $precios = (isset($_POST['precios'])) ? $_POST['precios'] : array();
        $descuentos = (isset($_POST['descuentos'])) ? $_POST['descuentos'] : array();

        $almacen_id = trim($_POST['almacen_id']);
        $nro_registros = trim($_POST['nro_registros']);
        $monto_total = trim($_POST['monto_total']);
        $tipo = trim($_POST['tipo']);
        
        // PARA LOS PAGOS
        $para_pagar = trim($_POST['para_pagar']);
        
        // VALIDAMOS EL PAGO
        $deuda_v = $db->select('SUM(i.monto) as saldo')
                      ->from('inv_pagos_detalles i')
                      ->where('i.estado', 0)
                      ->where('pago_id', $para_pagar)
                      ->fetch_first();
                      
        if ($deuda_v) {
            if ($deuda_v['saldo'] < $monto_total) {
                set_notification('danger','La deuda es menor al monto que desea devolver!','Pr favor seleccione otra deuda para pagar con la devolucion.');
                return redirect(back());
            }
        }
        // VALIDAMOS EL PAGO


        if ($tipo == 'Reposicion') {
            // Creamos el ingreso
            $movimiento = generarMovimiento($db, $_user['persona_id'], 'DV', $almacen_id);

            $ingreso = array(
                'fecha_ingreso'     => date('Y-m-d'),
                'hora_ingreso'      => date('H:i:s'),
                'tipo'              => 'Devolucion',
                'descripcion'       => 'Reposicion: ' . $descripcion,
                'monto_total'       => $monto_total,
                'descuento'         => 0,
                'monto_total_descuento' => 0,
                'nombre_proveedor'  => $nombre_cliente,
                'nro_registros'     => $nro_registros,
                'almacen_id'        => $almacen_id,
                'empleado_id'       => $_user['persona_id'],
                'egreso_id'         => $id_egreso,
                'tipo_devol'        => 'notas',
                'nro_movimiento'    => $movimiento, // + 1
            );
            // Guardamos el ingreso
            $id_ingreso = $db->insert('inv_ingresos', $ingreso);

            // Guarda Historial
            $data = array(
                'fecha_proceso' => date("Y-m-d"),
                'hora_proceso' => date("H:i:s"),
                'proceso' => 'c',
                'nivel' => 'l',
                'direccion' => '?/operaciones/notas_guardar',
                'detalle' => 'Se creo Ingreso con identificador numero ' . $id_ingreso ,
                'usuario_id' => $_SESSION[user]['id_user']
            );
            $db->insert('sys_procesos', $data) ;

            //Creamos y guardamos el detalle del ingreso
            foreach($productos as $nro => $producto){
                $unidad2 = $db->select('id_unidad')->from('inv_unidades')->where('unidad',$unidad[$nro])->fetch_first();
                $unidad3 = $unidad2['id_unidad'];
                $cantidad = cantidad_unidad($db, $productos[$nro], $unidad3)*$cantidades[$nro];
                $verif_f = $db->select('factura, factura_v, IVA')->from('inv_ingresos_detalles')->where('producto_id', $productos[$nro])->where('lote', $lotes[$nro])->where('vencimiento', $vencimiento[$nro])->fetch_first();
                $detalle = array(
                    'cantidad'      => $cantidad,
                    'costo'         => $precios[$nro],
                    'lote'          => $lotes[$nro],
                    'producto_id'   => $productos[$nro],
                    'ingreso_id'    => $id_ingreso,
                    'vencimiento'   => $vencimiento[$nro],
                    'dui'           => 0,
                    'contenedor'    => 0,
                    'factura'       => $verif_f['factura'],
                    'factura_v'     => $verif_f['factura_v'],
                    'almacen_id'    => $almacen_id,
                    'IVA'           => $verif_f['IVA'],
                );

                // Guarda la informacion
                $id_detalle = $db->insert('inv_ingresos_detalles', $detalle);
                // Guarda Historial
                $data = array(
                    'fecha_proceso' => date("Y-m-d"),
                    'hora_proceso' => date("H:i:s"),
                    'proceso' => 'c',
                    'nivel' => 'l',
                    'direccion' => '?/operaciones/notas_guardar',
                    'detalle' => 'Se creo el detalle de ingreso con identificador numero ' . $id_detalle ,
                    'usuario_id' => $_SESSION[user]['id_user']
                );
                $db->insert('sys_procesos', $data) ;
            }

            $_SESSION[temporary] = array(
                'alert' => 'success',
                'title' => 'Se realizo la devolucion!',
                'message' => 'El registro se realizó correctamente.'
            );
    
            redirect('?/ingresos/ver/'.$id_ingreso);
        }

        if ($tipo == 'Devolucion') {
            
            $egreso_modif =  $db->from('inv_egresos')
                                ->where('id_egreso', $id_egreso)
                                ->fetch_first();

            $egreso_no_venta =  $db->from('inv_egresos')
                                   ->where('nro_nota', $egreso_modif['nro_nota'])
                                   ->where('tipo', "No venta")
                                   ->fetch_first();

            if(!$egreso_no_venta){
                $data = array(
                    'fecha_egreso' =>  	$egreso_modif['fecha_egreso'],
                    'hora_egreso' =>  	$egreso_modif['hora_egreso'],
                    'fecha_habilitacion' =>  	$egreso_modif['fecha_habilitacion'],
                    'fecha_factura' =>  $egreso_modif['fecha_factura'],
                    'tipo' =>  	        "No venta",
                    'tipo_inicial' =>  	"No venta",
                    'distribuir' =>  	$egreso_modif['distribuir'],
                    'provisionado' =>  	$egreso_modif['provisionado'],
                    'descripcion' =>  	$egreso_modif['descripcion'],
                    'nro_nota' =>  	$egreso_modif['nro_nota'],
                    'nro_factura' =>  	$egreso_modif['nro_factura'],
                    'nro_movimiento' =>  	$egreso_modif['nro_movimiento'],
                    'nro_autorizacion' =>  	$egreso_modif['nro_autorizacion'],
                    'codigo_control' =>  	$egreso_modif['codigo_control'],
                    'fecha_limite' =>  	$egreso_modif['fecha_limite'],
                    'monto_total' =>  	0,
                    'descuento_porcentaje' =>  	0,
                    'descuento_bs' =>  	0,
                    'monto_total_descuento' =>  0,
                    'tipo_pago' =>  	$egreso_modif['tipo_pago'],
                    'nro_pago' =>  	$egreso_modif['nro_pago'],
                    'cliente_id' =>  	$egreso_modif['cliente_id'],
                    'nombre_cliente' =>  	$egreso_modif['nombre_cliente'],
                    'nit_ci' =>  	$egreso_modif['nit_ci'],
                    'nro_registros' =>  	0,
                    'estadoe' =>  	$egreso_modif['estadoe'],
                    'coordenadas' =>  	$egreso_modif['coordenadas'],
                    'observacion' =>  	$egreso_modif['observacion'],
                    'dosificacion_id' =>  	$egreso_modif['dosificacion_id'],
                    'almacen_id' =>  	$egreso_modif['almacen_id'],
                    'almacen_id_s' =>  	$egreso_modif['almacen_id_s'],
                    'empleado_id' =>  	$egreso_modif['empleado_id'],
                    'vendedor_id' =>  	$egreso_modif['vendedor_id'],
                    'codigo_vendedor' =>  	$egreso_modif['codigo_vendedor'],
                    'motivo_id' =>  	$egreso_modif['motivo_id'],
                    'duracion' =>  	$egreso_modif['duracion'],
                    'cobrar' =>  	$egreso_modif['cobrar'],
                    'grupo' =>  	$egreso_modif['grupo'],
                    'descripcion_venta' =>  	$egreso_modif['descripcion_venta'],
                    'ruta_id' =>  	$egreso_modif['ruta_id'],
                    'estado' =>  	$egreso_modif['estado'],
                    'plan_de_pagos' =>  	$egreso_modif['plan_de_pagos'],
                    'ingreso_id' =>  	$egreso_modif['ingreso_id'],
                    'preventa' =>  	$egreso_modif['preventa'],
                    'factura' =>  	$egreso_modif['factura']
                );
                $id_egreso_no_venta=$db->insert('inv_egresos', $data);
            }else{
                $id_egreso_no_venta=$egreso_no_venta['id_egreso'];
            }

            // PARA LAS CUOTAS 
            if ($egreso_modif['plan_de_pagos'] == 'si') {
                $pagos = $db->from('inv_pagos')->where('id_pago', $para_pagar)->fetch_first();

                $pagos_det = $db->from('inv_pagos_detalles')
                                ->where('pago_id', $pagos['id_pago'])
                                ->fetch();
                                
                $nro_cuotas = $db->select('COUNT(i.id_pago_detalle) as nro_cuota, MAX(nro_pago) as nro_pago')
                                 ->from('inv_pagos_detalles i')
                                 ->where('i.estado', 1)
                                 ->where('pago_id', $pagos['id_pago'])
                                 ->fetch_first();

                $total_salado = $db->select('SUM(i.monto) as saldo')
                                   ->from('inv_pagos_detalles i')
                                   ->where('i.estado', 0)
                                   ->where('pago_id', $pagos['id_pago'])
                                   ->fetch_first();
                                   
                if ($total_salado) {
                    echo $total_saldo = $total_salado['saldo'] - $monto_total;
                } else {
                    echo $total_saldo = 0;
                }

                // Elimina el ingreso
                $db->delete()->from('inv_pagos_detalles')->where('pago_id', $pagos['id_pago'])->where('estado', 0)->execute();

                // Guarda Historial
                $data = array(
                    'fecha_proceso' => date("Y-m-d"),
                    'hora_proceso' => date("H:i:s"),
                    'proceso' => 'u',
                    'nivel' => 'l',
                    'direccion' => '?/operaciones/notas_guardar',
                    'detalle' => 'Se elimino detalles del pago con identificador numero' . $pagos['id_pago'] ,
                    'usuario_id' => $_SESSION[user]['id_user']
                );
                $db->insert('sys_procesos', $data);

                // Creamos el nuevo pago
                // Guarda Historial
                if ($total_saldo > 0) {
                    // pagado
                    $pagado_d = array(
                        'pago_id'	        => $pagos['id_pago'],
                        'fecha'             => date('Y-m-d'),
                        'monto'             => $monto_total,
                        'estado'            => 1,
                        'fecha_pago'        => date('Y-m-d'),
                        'tipo_pago'         => 'DEVOLUCION',
                        'nro_cuota'         => ($nro_cuotas['nro_cuota'])?$nro_cuotas['nro_cuota']+1:1,
                        'nro_pago'          => ($nro_cuotas['nro_pago'])?$nro_cuotas['nro_pago']+1:1,
                        'empleado_id'       => $_user['persona_id']
                    );
                    $db->insert('inv_pagos_detalles', $pagado_d);
                    // saldo
                    $nuevo_pago = array(
                        'pago_id'	        => $pagos['id_pago'],
                        'fecha'             => date('Y-m-d'),
                        'monto'             => $total_saldo,
                        'estado'            => 0,
                        'fecha_pago'        => '0000-00-00',
                        'tipo_pago'         => '',
                        'nro_cuota'         => ($nro_cuotas['nro_cuota'])?$nro_cuotas['nro_cuota']+2:2,
                        'nro_pago'          => 0,
                        'empleado_id'       => $_user['persona_id']
                    );
                    $db->insert('inv_pagos_detalles', $nuevo_pago);
                }
                if ($total_saldo == 0) {
                    $nuevo_pago = array(
                        'pago_id'	        => $pagos['id_pago'],
                        'fecha'             => date('Y-m-d'),
                        'monto'             => $total_saldo,
                        'estado'            => 1,
                        'fecha_pago'        => date('Y-m-d'),
                        'tipo_pago'         => 'DEVOLUCION',
                        'nro_cuota'         => ($nro_cuotas['nro_cuota'])?$nro_cuotas['nro_cuota']+1:1,
                        'nro_pago'          => 0,
                        'empleado_id'       => $_user['persona_id']
                    );
                    $db->insert('inv_pagos_detalles', $nuevo_pago);
                    
                    $nuevo_pago = array(
                        'pago_id'	        => $pagos['id_pago'],
                        'fecha'             => date('Y-m-d'),
                        'monto'             => $total_saldo,
                        'estado'            => 1,
                        'fecha_pago'        => date('Y-m-d'),
                        'tipo_pago'         => 'DEVOLUCION',
                        'nro_cuota'         => ($nro_cuotas['nro_cuota'])?$nro_cuotas['nro_cuota']+1:1,
                        'nro_pago'          => 0,
                        'empleado_id'       => $_user['persona_id']
                    );
                }

            }

            $movimiento = generarMovimiento($db, $_user['persona_id'], 'DV', $almacen_id);

            $ingreso = array(
                'fecha_ingreso'     => date('Y-m-d'),
                'hora_ingreso'      => date('H:i:s'),
                'tipo'              => 'Devolucion',
                'descripcion'       => 'Devolucion: ' . $descripcion,
                'monto_total'       => $monto_total,
                'descuento'         => 0,
                'monto_total_descuento' => 0,
                'nombre_proveedor'  => $nombre_cliente,
                'nro_registros'     => $nro_registros,
                'almacen_id'        => $almacen_id,
                'empleado_id'       => $_user['persona_id'],
                'egreso_id'         => $id_egreso,
                'tipo_devol'        => 'notas',
                'nro_movimiento'    => $movimiento, // + 1
            );
            // Guardamos el ingreso
            $id_ingreso = $db->insert('inv_ingresos', $ingreso);

            // Guarda Historial
            $data = array(
                'fecha_proceso' => date("Y-m-d"),
                'hora_proceso' => date("H:i:s"),
                'proceso' => 'c',
                'nivel' => 'l',
                'direccion' => '?/operaciones/notas_guardar',
                'detalle' => 'Se creo Ingreso con identificador numero ' . $id_ingreso ,
                'usuario_id' => $_SESSION[user]['id_user']
            );
            $db->insert('sys_procesos', $data) ;

            //Creamos y guardamos el detalle del ingreso
            foreach($productos as $nro => $producto){
                
                
                echo "lista productos: ".$nro."<br>";
                
                
                
                $unidad2 = $db->select('id_unidad')
                              ->from('inv_unidades')
                              ->where('unidad',$unidad[$nro])
                              ->fetch_first();
                              
                $unidad3 = $unidad2['id_unidad'];
                
                $cantidad = cantidad_unidad($db, $productos[$nro], $unidad3)*$cantidades[$nro];
                
                echo $detalles_query = "SELECT * 
                                          from inv_egresos_detalles 
                                          where egreso_id='".$id_egreso."'
                                                AND producto_id='".$productos[$nro]."'
                                                AND lote='".$lotes[$nro]."'
                                                AND vencimiento='".$vencimiento[$nro]."'
                                        ";
            
                $detalles_modif = $db->query($detalles_query)->fetch();
                
                $cantidad_descontar=$cantidad;
                
                foreach ($detalles_modif as $key222 => $detal_modif) {
                
                
                    echo "lista egresos detalles: ".$cantidad_descontar."<br>";
                
                    
                    if($detal_modif['cantidad']<$cantidad_descontar){
                        $cantidad_egreso=$detal_modif['cantidad'];
                        $cantidad_modificar=0;
                        $cantidad_descontar=$cantidad_descontar-$detal_modif['cantidad'];
                    }else{
                        $cantidad_egreso=$cantidad_descontar;
                        $cantidad_modificar=$detal_modif['cantidad']-$cantidad_descontar;
                        $cantidad_descontar=0;
                    }
                    
                    if($cantidad_egreso>0){
                        $ing_detalle = $db->query("select *
                                                   from inv_ingresos_detalles
                                                   where id_detalle='".$detal_modif['ingresos_detalles_id']."'")
                                          ->fetch_first();
                                      
                        $detalle = array(
                            'cantidad'      => $cantidad_egreso,
                            'lote_cantidad' => $cantidad_egreso,
                            'costo'         => $ing_detalle['costo'],
                            'lote'          => $ing_detalle['lote'],
                            'producto_id'   => $ing_detalle['producto_id'],
                            'ingreso_id'    => $id_ingreso,
                            'vencimiento'   => $ing_detalle['vencimiento'],
                            'dui'           => 0,
                            'contenedor'    => 0,
                            'factura'       => 0,
                            'factura_v'     => 0,
                            'almacen_id'    => $almacen_id,
                            'IVA'           => 'no',
                        );
                    
                        // Guarda la informacion
                        $id_detalle = $db->insert('inv_ingresos_detalles', $detalle);
                        
                        /*************************/
                        
                        $detalle = array(
                            'precio'                => 	$detal_modif['precio'],
                            'unidad_id'             => 	$detal_modif['unidad_id'],
                            'cantidad'              => 	$cantidad_egreso,
                            'descuento'             => 	$detal_modif['descuento'],
                            'producto_id'           => 	$detal_modif['producto_id'],
                            'egreso_id'             => 	$id_egreso_no_venta,
                            'promocion_id'          => 	$detal_modif['promocion_id'],
                            'asignacion_id'         => 	$detal_modif['asignacion_id'],
                            'lote'                  => 	$detal_modif['lote'],
                            'vencimiento'           => 	$detal_modif['vencimiento'],
                            'detalle_ingreso_id'    => 	$detal_modif['detalle_ingreso_id'],
                            'ingresos_detalles_id'  => 	$detal_modif['ingresos_detalles_id'],
                        );
                    
                        // Guarda la informacion
                        $id_detalle = $db->insert('inv_egresos_detalles', $detalle);
                       
                        $data_detalle = array(
                			'cantidad'=>($detal_modif['cantidad']-$cantidad_egreso),
                		);
 
                        
                        $condicion = array('id_detalle' => $detal_modif['id_detalle']);
                        $db->where($condicion)->update('inv_egresos_detalles', $data_detalle);
        
                    }
                }
                
                // Guarda Historial
                $data = array(
                    'fecha_proceso' => date("Y-m-d"),
                    'hora_proceso' => date("H:i:s"),
                    'proceso' => 'c',
                    'nivel' => 'l',
                    'direccion' => '?/operaciones/notas_guardar',
                    'detalle' => 'Se creo el detalle de ingreso con identificador numero ' . $id_detalle ,
                    'usuario_id' => $_SESSION[user]['id_user']
                );
                $db->insert('sys_procesos', $data) ;
            }

            $_SESSION[temporary] = array(
                'alert' => 'success',
                'title' => 'Se realizo la devolucion!',
                'message' => 'El registro se realizó correctamente.'
            );

        }

        $_SESSION[temporary] = array(
            'alert' => 'success',
            'title' => 'Se realizo la devolucion!',
            'message' => 'El registro se realizó correctamente.'
        );
        //enviamos a imprimir el nuevo egreso
        if ($tipo == 'Reposicion') {
            $_SESSION['imprimir'] = $id_egreso;
        }
        if($tipo == 'Devolucion') {
            $_SESSION['imprimir'] = $egreso_modif['id_egreso'];
        }

        //redirect('?/operaciones/notas_listar');

		// Envia respuesta
        echo json_encode($id_egreso);

    } else {
		// Envia respuesta
		echo 'error';
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>