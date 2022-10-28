<?php
/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

//  echo json_encode($_POST); die();
//var_dump($_POST);exit();
// Verifica si es una peticion ajax y post
if (is_post()) {
	// Verifica la existencia de los datos enviados
	if (isset($_POST['nit_ci']) && isset($_POST['nombre_cliente']) && isset($_POST['productos']) && isset($_POST['nombres']) && isset($_POST['cantidades']) && isset($_POST['precios'])  && isset($_POST['nro_registros']) && isset($_POST['monto_total']) && isset($_POST['almacen_id'])) {
		// Importa la libreria para convertir el numero a letra
		require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

		// Obtiene los datos de la proforma
		$nit_ci = trim($_POST['nit_ci']);
        $nombre_cliente = trim($_POST['nombre_cliente']);
        $telefono = trim($_POST['telefono_cliente']);
        $validez = trim($_POST['validez']);
        $observacion = trim($_POST['observacion']);
        $direccion = trim($_POST['direccion']);
        $atencion = trim($_POST['atencion']);
		$productos = (isset($_POST['productos'])) ? $_POST['productos'] : array();
		$nombres = (isset($_POST['nombres'])) ? $_POST['nombres'] : array();
		$cantidades = (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
        $unidad = (isset($_POST['unidad'])) ? $_POST['unidad']: array();
        $precios = (isset($_POST['precios'])) ? $_POST['precios'] : array();
        $descuentos = (isset($_POST['descuentos'])) ? $_POST['descuentos'] : array();
        $nro_registros = trim($_POST['nro_registros']);
		$monto_total = trim($_POST['monto_total']);
		$almacen_id = trim($_POST['almacen_id']);
        $adelanto = trim($_POST['adelanto']);

        $lote			= (isset($_POST['lote'])) ? $_POST['lote'] : array();
        $vencimiento	= (isset($_POST['vencimiento'])) ? $_POST['vencimiento'] : array();
//        var_dump($_POST);exit();
        //obtiene al cliente
        $cliente = $db->select('*')->from('inv_clientes')->where(array('cliente' => $nombre_cliente, 'nit' => $nit_ci))->fetch_first();

        if(!$cliente){
            $cl = array(
                'cliente' => $nombre_cliente,
                'nit' => $nit_ci,
                'telefono' => $telefono,
                'ubicacion' => $atencion,
                'direccion' => $direccion
            );
            $id_cliente = $db->insert('inv_clientes',$cl);
           // Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/operaciones/guardar',
				'detalle' => 'Se creo cliente con identificador numero ' . $id_cliente ,
				'usuario_id' => $_SESSION[user]['id_user']
			);
			$db->insert('sys_procesos', $data) ;

        }else{
            $id_cliente = $cliente['id_cliente'];
        }

        $egreso = $db->from('inv_egresos')->where('id_egreso',$_POST['id_egreso'])->fetch_first();
        // Actualizamos el egreso
        if($_POST['id_egreso'] > 0){
            $proforma = array(
                'monto_total' => $monto_total,
                'cliente_id' => $id_cliente,
                'nit_ci' => $nit_ci,
                'nombre_cliente' => mb_strtoupper($nombre_cliente, 'UTF-8'),
                'nro_registros' => $nro_registros,
                // 'empleado_id' => $_user['persona_id'],
                'coordenadas' => $atencion,
                'observacion' => $observacion
            );

            // Guarda la informacion
            $db->where('id_egreso',$_POST['id_egreso'])->update('inv_egresos', $proforma);
            // Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'u',
				'nivel' => 'l',
				'direccion' => '?/operaciones/guardar',
				'detalle' => 'Se actualizo egreso con identificador numero ' . $_POST['id_egreso'] ,
				'usuario_id' => $_SESSION[user]['id_user']
			);
            $db->insert('sys_procesos', $data) ;

            /////////////////////////////////////////////////////////////////////
            $Lotes=$db->query("SELECT *
                                FROM inv_egresos_detalles AS ed
                                LEFT JOIN inv_unidades AS u ON ed.unidad_id=u.id_unidad
                                WHERE egreso_id='{$_POST['id_egreso']}'")->fetch();
            foreach($Lotes as $Fila=>$Lote):
                $IdProducto=$Lote['producto_id'];
                $UnidadId=$Lote['unidad_id'];
                $LoteGeneral=$Lote['lote'];
                $VencGeneral=$Lote['vencimiento'];
                $CantGeneral=$Lote['cantidad'];
                $DetalleIngreso=$db->query("SELECT id_detalle,lote_cantidad
                                                FROM inv_ingresos_detalles
                                                WHERE producto_id='{$IdProducto}' AND lote='{$LoteGeneral}' AND vencimiento='{$VencGeneral}'
                                                LIMIT 1")->fetch_first();
                $Condicion=[
                        'id_detalle'=>$DetalleIngreso['id_detalle'],
                        'lote'=>$LoteGeneral,
                        'vencimiento'=>$VencGeneral,
                    ];
                $CantidadAux=$CantGeneral;
                $Datos=[
                        'lote_cantidad'=>(strval($DetalleIngreso['lote_cantidad'])+strval($CantidadAux)),
                    ];
                $db->where($Condicion)->update('inv_ingresos_detalles',$Datos);
            endforeach;
            /////////////////////////////////////////////////////////////////////
            $db->delete()->from('inv_egresos_detalles')->where('egreso_id', $_POST['id_egreso'])->execute();
            // Guarda Historial
        	$data = array(
        		'fecha_proceso' => date("Y-m-d"),
        		'hora_proceso' => date("H:i:s"),
        		'proceso' => 'u',
        		'nivel' => 'l',
        		'direccion' => '?/operaciones/guardar',
        		'detalle' => 'Se elimino inventario egreso detalle con identificador numero' . $_POST['id_egreso'] ,
        		'usuario_id' => $_SESSION[user]['id_user']
        	);
        	$db->insert('sys_procesos', $data) ;

            foreach($productos as $nro => $producto){
                $unidad2 = $db->select('id_unidad')->from('inv_unidades')->where('unidad',$unidad[$nro])->fetch_first();
                $unidad3 = $unidad2['id_unidad'];
                $cantidad = cantidad_unidad($db, $productos[$nro], $unidad3)*$cantidades[$nro];
                /////////////////////////////////////////////////////////////////////////////////////////
                $detalle = array(
                    'cantidad' => $cantidad,
                    'unidad_id' => $unidad3,
                    'precio' => $precios[$nro],
                    'descuento' => '0',
                    'producto_id' => $productos[$nro],
                    'egreso_id' => $_POST['id_egreso'],
                    'lote' => explode(': ',$lote[$nro])[1],
                    'vencimiento' => explode(': ',$vencimiento[$nro])[1],
                );

                // Guarda la informacion
                $id = $db->insert('inv_egresos_detalles', $detalle);

                // Guarda Historial
    			$data = array(
    				'fecha_proceso' => date("Y-m-d"),
    				'hora_proceso' => date("H:i:s"),
    				'proceso' => 'c',
    				'nivel' => 'l',
    				'direccion' => '?/operaciones/guardar',
    				'detalle' => 'Se creo inventario egreso detalle con identificador numero ' . $id ,
    				'usuario_id' => $_SESSION[user]['id_user']
    			);

    			$db->insert('sys_procesos', $data) ;
            }



            // Inicio para pagos
            $plan = trim($egreso['plan_de_pagos']);
            $pagos1 = $db->from('inv_pagos')->where('movimiento_id', $_POST['id_egreso'])->where( 'tipo', 'Egreso')->fetch_first();

            $db->delete()->from('inv_pagos')->where('movimiento_id', $_POST['id_egreso'])->where( 'tipo', 'Egreso')->execute();
            $db->delete()->from('inv_pagos_detalles')->where('pago_id', $pagos1['id_pago'])->execute();


            if ($plan == 'no' || $plan == 'si') {
                // Instancia el ingreso
                $planPago = array(
                    'movimiento_id' => $_POST['id_egreso'],
                    'interes_pago' => 0,
                    'tipo' => 'Egreso'
                );
                // Guarda la informacion del ingreso general
                $id_plan_egreso = $db->insert('inv_pagos', $planPago);
                // Genera el plan de pagos
                $detallePlan = array(
                    'nro_cuota' => 1,
                    'pago_id' => $id_plan_egreso,
                    'fecha' => date('Y-m-d'),
                    'fecha_pago' => date('Y-m-d'),
                    'monto' => $monto_total,
                    'tipo_pago' => '', //$tipo_pago
                    'nro_pago' => '0',
                    'empleado_id' => $_user['persona_id'],
                    'estado'  => '0'
                );
                // Guarda la informacion
                $db->insert('inv_pagos_detalles', $detallePlan);
            }
            //Cuentas
            // if ($plan == "si") {
            //     // Instancia el ingreso
            //     $ingresoPlan = array(
            //         'movimiento_id' => $_POST['id_egreso'],
            //         'interes_pago' => 0,
            //         'tipo' => 'Egreso'
            //     );
            //     // Guarda la informacion del ingreso general
            //     $ingreso_id_plan = $db->insert('inv_pagos', $ingresoPlan);
            //     $nro_cuota = 0;
            //     for ($nro2 = 0; $nro2 < $nro_cuentas; $nro2++) {
            //         if (isset($fechas[$nro2])) {
            //             $fecha_format = date_create($fechas[$nro2]);
            //         } else {
            //             $fecha_format = date_create("0000-00-00");
            //         }
            //         $nro_cuota++;

            //         $detallePlan = array(
            //             'nro_cuota' => $nro_cuota,
            //             'pago_id' => $ingreso_id_plan,
            //             'fecha' => $fecha_format->format('Y-m-d'),
            //             'fecha_pago' => $fecha_format->format('Y-m-d'),
            //             'monto' => (isset($cuotas[$nro2])) ? $cuotas[$nro2] : 0,
            //             'tipo_pago' => '',
            //             'nro_pago' => '',
            //             'empleado_id' => $_user['persona_id'],
            //             'estado'  => '0'
            //         );
            //         // Guarda la informacion
            //         $db->insert('inv_pagos_detalles', $detallePlan);
            //     }
            // }
            // Fin para pagos

        }
        $_SESSION[temporary] = array(
            'alert' => 'success',
            'title' => 'Se creo el nuevo cliente!',
            'message' => 'El registro se realizó correctamente.'
        );
        if ($_POST['modo_egreso'] == 'preventa') {
            redirect('?/operaciones/preventas_listar');
        }
        elseif ($_POST['modo_egreso'] == 'manuales') {
            redirect('?/operaciones/listar_manuales');
        }
        else {
            redirect(back());
        }
        

		// Envia respuesta
		echo json_encode($respuesta);
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