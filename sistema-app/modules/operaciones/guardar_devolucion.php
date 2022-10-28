<?php
/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */
//var_dump($_POST);exit();

// echo json_encode($_POST); die();
// Verifica si es una peticion ajax y post
// var_dump($_POST);
if (is_post()) {
	// Verifica la existencia de los datos enviados
	if (isset($_POST['nit_ci']) && isset($_POST['nombre_cliente']) && isset($_POST['productos']) && isset($_POST['nombres']) && isset($_POST['cantidades']) && isset($_POST['precios']) && isset($_POST['descuentos']) && isset($_POST['nro_registros']) && isset($_POST['monto_total']) && isset($_POST['almacen_id'])) {
		// Importa la libreria para convertir el numero a letra
		require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

		// // Obtiene los datos de la proforma
        // $nit_ci = trim($_POST['nit_ci']);
        // $id_cliente = trim($_POST['id_cliente']);
        // $nombre_cliente = trim($_POST['nombre_cliente']);
        // $nro_factura = trim($_POST['nro_factura']);
        // $motivo = trim($_POST['motivo']);
        // $descripcion = trim($_POST['descripcion']);
        // // $tipo = 'Baja';

        // $monto_total = trim($_POST['monto_total']);
        // $almacen_id = trim($_POST['almacen_id']);
        // $nro_registros = trim($_POST['nro_registros']);

        // $telefono = trim($_POST['telefono_cliente']);
        // $validez = trim($_POST['validez']);
        // $observacion = trim($_POST['observacion']);
        // $direccion = trim($_POST['direccion']);
        // $atencion = trim($_POST['atencion']);
		// $productos = (isset($_POST['productos'])) ? $_POST['productos'] : array();
		// $nombres = (isset($_POST['nombres'])) ? $_POST['nombres'] : array();
		// $cantidades = (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
        // $unidad = (isset($_POST['unidad'])) ? $_POST['unidad']: array();
        // $precios = (isset($_POST['precios'])) ? $_POST['precios'] : array();
        // $descuentos = (isset($_POST['descuentos'])) ? $_POST['descuentos'] : array();

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


//        var_dump($_POST);exit();
        //obtiene al cliente


        // TRABAJAR CON ESTADOE = 3 RECIEN DEVOLUCIONES

        $movimiento = generarMovimiento($db, $_user['persona_id'], 'DV', $almacen_id);

        if ($tipo == 'Reposicion') {
            // Creamos el ingreso
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
                'tipo_devol'        => 'preventa',
                'nro_movimiento' => $movimiento, // + 1
            );
            // Guardamos el ingreso
            $id_ingreso = $db->insert('inv_ingresos', $ingreso);

            // Guarda Historial
            $data = array(
                'fecha_proceso' => date("Y-m-d"),
                'hora_proceso' => date("H:i:s"),
                'proceso' => 'c',
                'nivel' => 'l',
                'direccion' => '?/operaciones/guardar_devolucion',
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
    
            redirect('?/ingresos/ver/' . $id_ingreso);

        }
        
        if ($tipo == 'Devolucion') {
            $egreso_modif = $db->from('inv_egresos')->where('id_egreso', $id_egreso)->fetch_first();

            foreach ($productos as $key => $producto) {
                $detalles_modif = $db->from('inv_egresos_detalles')
                                        ->where('egreso_id', $id_egreso)
                                        ->where('producto_id', $productos[$key])
                                        ->where('lote', $lotes[$key])
                                        ->where('vencimiento', $vencimiento[$key])
                                        ->fetch_first();

                // Instancia el ingreso
                $egreso_new = array(
                    'monto_total' => $egreso_modif['monto_total'] - ($precios[$key] * $cantidades[$key]),
                );
                // Guarda la informacion
                $db->where('id_egreso', $id_egreso)->update('inv_egresos', $egreso_new);

                // Instancia el ingreso
                $det_egreso_new = array(
                    'cantidad' => $detalles_modif['cantidad'] - $cantidades[$key],
                );
                // Guarda la informacion
                $db->where('id_detalle', $detalles_modif['id_detalle'])->update('inv_egresos_detalles', $det_egreso_new);
                // }

                if ($egreso_modif['plan_de_pagos'] == 'si') {
                    $pagos = $db->from('inv_pagos')->where('movimiento_id', $id_egreso)->where('tipo', 'Egreso')->fetch_first();
                    $pagos_det = $db->from('inv_pagos_detalles')->where('pago_id', $pagos['id_pago'])->fetch();
                    $nro_cuotas = $db->select('COUNT(i.id_pago_detalle) as nro_cuota')
                                     ->from('inv_pagos_detalles i')
                                     ->where('i.estado', 1)
                                     ->where('pago_id', $pagos['id_pago'])
                                     ->fetch_first();

                    $total_salado = $db->select('SUM(i.monto) as saldo')->from('inv_pagos_detalles i')->where('i.estado', 0)->where('pago_id', $pagos['id_pago'])->fetch_firs();

                    // Elimina el ingreso
                    $db->delete()->from('inv_pagos_detalles')->where('pago_id', $pagos['id_pago'])->where('estado', 0)->execute();

                    // Guarda Historial
                    $data = array(
                        'fecha_proceso' => date("Y-m-d"),
                        'hora_proceso' => date("H:i:s"),
                        'proceso' => 'u',
                        'nivel' => 'l',
                        'direccion' => '?/operaciones/guardar_devolucion',
                        'detalle' => 'Se elimino detalles del pago con identificador numero' . $pagos['id_pago'] ,
                        'usuario_id' => $_SESSION[user]['id_user']
                    );
                    $db->insert('sys_procesos', $data);

                    // Creamos el nuevo pago
                    // Guarda Historial
                    $nuevo_pago = array(
                    'pago_id'	        => $pagos['id_pago'],
                    'fecha'             => date('Y-m-d'),
                    'monto'             => $total_salado - $precios[$key],
                    'estado'            => 0,
                    'fecha_pago'        => '0000-00-00',
                    'tipo_pago'         => '',
                    'nro_cuota'         => $nro_cuotas['nro_cuota'] +1,
                    'empleado_id'       => $_user['persona_id']
                    );
                    $db->insert('inv_pagos_detalles', $nuevo_pago);

                }
            }

            //Creamos el egreso
            $movimientoE = generarMovimiento($db, $_user['persona_id'], 'RP', $almacen_id);
            $egreso = array(
                'fecha_egreso'          => date('Y-m-d'),
                'hora_egreso'           => date('H:i:s'),
                'tipo'                  => 'Devolucion',
                'provisionado'          => 'S',
                'descripcion'           => 'Devolucion: ' . $descripcion,
                'nro_factura'           => $nro_factura,
                'nro_autorizacion'      => '',
                'codigo_control'        => '',
                'fecha_limite'          => '0000-00-00',
                'monto_total'           => $monto_total,
                'descuento_porcentaje'  => 0,
                'descuento_bs'          => 0,
                'monto_total_descuento' => 0,
                'cliente_id'            => $id_cliente,
                'nombre_cliente'        => $nombre_cliente,
                'nit_ci'                => $nit_ci,
                'nro_registros'         => $nro_registros,
                'estadoe'               => 0,
                'coordenadas'           => '',
                'observacion'           => '',
                'dosificacion_id'       => 0,
                'almacen_id'            => $almacen_id,
                'almacen_id_s'          => 0,
                'empleado_id'           => $_user['persona_id'],
                'motivo_id'             => 0,
                'duracion'              => '00:00:00',
                'cobrar'                => 'no',
                'grupo'                 => '',
                'estado'                => 1,
                'descripcion_venta'     => 'DEVOLUCION',
                'nro_movimiento' => $movimientoE, // + 1
            );
            // Guarda el egreso
            $id_egreso = $db->insert('inv_egresos', $egreso);
            // Guarda Historial
            $data = array(
                'fecha_proceso' => date("Y-m-d"),
                'hora_proceso' => date("H:i:s"),
                'proceso' => 'c',
                'nivel' => 'l',
                'direccion' => '?/operaciones/guardar_devolucion',
                'detalle' => 'Se creo el Egreso con identificador numero ' . $id ,
                'usuario_id' => $_SESSION[user]['id_user']
            );
            $db->insert('sys_procesos', $data);

            // Guardamos los detalles del egreso
            foreach($productos as $nro => $producto){
                $unidad2 = $db->select('id_unidad')->from('inv_unidades')->where('unidad',$unidad[$nro])->fetch_first();
                $unidad3 = $unidad2['id_unidad'];
                $cantidad = cantidad_unidad($db, $productos[$nro], $unidad3)*$cantidades[$nro];
                $detalle = array(
                    'precio'        => 0,
                    'unidad_id'     => $unidad3,
                    'cantidad'      => $cantidad,
                    'descuento'     => $descuentos[$nro],
                    'producto_id'   => $productos[$nro],
                    'egreso_id'     => $id_egreso,
                    'lote'          => $lotes[$nro],
                    'vencimiento'   => $vencimiento[$nro]
                );
                // Guarda la informacion
                $id_detalle = $db->insert('inv_egresos_detalles', $detalle);

                // Guarda Historial
                $data = array(
                    'fecha_proceso' => date("Y-m-d"),
                    'hora_proceso' => date("H:i:s"),
                    'proceso' => 'c',
                    'nivel' => 'l',
                    'direccion' => '?/operaciones/guardar_devolucion',
                    'detalle' => 'Se creo Detalle de egreso con identificador numero ' . $id_detalle ,
                    'usuario_id' => $_SESSION[user]['id_user']
                );
                $db->insert('sys_procesos', $data) ;
            }

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

        redirect('?/operaciones/preventas_listar');

		// Envia respuesta
        echo json_encode($id_egreso);







        // if($_POST['id_egreso'] > 0){
        //     $egreso = array(
        //         'fecha_egreso' => date('Y-m-d'),
        //         'hora_egreso' => date('H:i:s'),
        //         'tipo' => 'Devolucion',
        //         'provisionado' => 'N',
        //         'descripcion' => $descripcion,
        //         'nro_factura' => $nro_factura,
        //         'nro_autorizacion' => '',
        //         'codigo_control' => '',
        //         'fecha_limite' => '0000-00-00',
        //         'monto_total' => $monto_total,
        //         'descuento_porcentaje' => 0,
        //         'descuento_bs' => 0,
        //         'monto_total_descuento' => 0,
        //         'cliente_id' => $id_cliente,
        //         'nit_ci' => $nit_ci,
        //         'nombre_cliente' => strtoupper($nombre_cliente),
        //         'nro_registros' => $nro_registros,
        //         'estadoe' => 0,
        //         'coordenadas' => '',
        //         'observacion' => '',
        //         'empleado_id' => $_user['persona_id'],
        //         'dosificacion_id' => 0,
        //         'almacen_id' => $almacen_id,
        //         'motivo_id' => 0,
        //         'duracion' => '00:00:00',
        //         'cobrar' => '',
        //         'grupo' => '',
        //         'descripcion_venta' => 'DEVOLUCION'
        //     );

        //     // Guarda la informacion
        //     $id = $db->insert('inv_egresos', $egreso);
        //      // Guarda Historial
		// 	$data = array(
		// 		'fecha_proceso' => date("Y-m-d"),
		// 		'hora_proceso' => date("H:i:s"), 
		// 		'proceso' => 'c',
		// 		'nivel' => 'l',
		// 		'direccion' => '?/operaciones/guardar_devolucion',
		// 		'detalle' => 'Se creo inventario egreso con identificador numero ' . $id ,
		// 		'usuario_id' => $_SESSION[user]['id_user']			
		// 	);
			
		// 	$db->insert('sys_procesos', $data) ; 

        //     foreach($productos as $nro => $producto){
        //         $unidad2 = $db->select('id_unidad')->from('inv_unidades')->where('unidad',$unidad[$nro])->fetch_first();
        //         $unidad3 = $unidad2['id_unidad'];
        //         $cantidad = cantidad_unidad($db, $productos[$nro], $unidad3)*$cantidades[$nro];
        //         $detalle = array(
        //             'cantidad' => $cantidad,
        //             'unidad_id' => $unidad3,
        //             'precio' => $precios[$nro],
        //             'descuento' => $descuentos[$nro],
        //             'producto_id' => $productos[$nro],
        //             'egreso_id' => $id
        //         );

        //         // Guarda la informacion
        //         $id_detalle = $db->insert('inv_egresos_detalles', $detalle);
        //          // Guarda Historial
    	// 		$data = array(
    	// 			'fecha_proceso' => date("Y-m-d"),
    	// 			'hora_proceso' => date("H:i:s"), 
    	// 			'proceso' => 'c',
    	// 			'nivel' => 'l',
    	// 			'direccion' => '?/operaciones/guardar_devolucion',
    	// 			'detalle' => 'Se creo inventario egreso  detalle con identificador numero ' . $id_detalle ,
    	// 			'usuario_id' => $_SESSION[user]['id_user']
    	// 		);
    	// 		$db->insert('sys_procesos', $data) ; 
        //     }
        // }
        // $_SESSION[temporary] = array(
        //     'alert' => 'success',
        //     'title' => 'Se realizo la devolucion!',
        //     'message' => 'El registro se realizó correctamente.'
        // );
        // redirect('?/operaciones/preventas_listar');

		// // Envia respuesta
		// echo json_encode($respuesta);
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