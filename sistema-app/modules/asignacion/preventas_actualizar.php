<?php
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
        // $validez = trim($_POST['validez']);
        $observacion = trim($_POST['observacion']);
        $prioridad = trim($_POST['prioridad']);
        $direccion = trim($_POST['direccion']);
        // $atencion = trim($_POST['atencion']);
		$productos = (isset($_POST['productos'])) ? $_POST['productos'] : array();
		$nombres = (isset($_POST['nombres'])) ? $_POST['nombres'] : array();
		$cantidades = (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
        $unidad = (isset($_POST['unidad'])) ? $_POST['unidad']: array();
        $precios = (isset($_POST['precios'])) ? $_POST['precios'] : array();
        $descuentos = (isset($_POST['descuentos'])) ? $_POST['descuentos'] : array();
        $nro_registros = trim($_POST['nro_registros']);
		$almacen_id = trim($_POST['almacen_id']);
        $adelanto = trim($_POST['adelanto']);
        $id_cliente= trim($_POST['cliente_id']);

        $lote			= (isset($_POST['lote'])) ? $_POST['lote'] : array();
        $vencimiento	= (isset($_POST['vencimiento'])) ? $_POST['vencimiento'] : array();

        $nro_cuentas = trim($_POST['nro_cuentas']);
        $plan = (isset($_POST['forma_pago'])) ? trim($_POST['forma_pago']) : '';
        $plan = ($plan == "2") ? "si" : "no";
        if ($plan == "si") {
            $fechas = (isset($_POST['fecha'])) ? $_POST['fecha'] : array();
            $cuotas = (isset($_POST['cuota'])) ? $_POST['cuota'] : array();
        } 
        
        
        //$monto_total = trim($_POST['monto_total']);
		$monto_total=0;
        foreach ($productos as $nro => $elemento) {
			$cantidad = $cantidades[$nro];
			$precio = $precios[$nro];
            $monto_total=$monto_total+($cantidad*$precio);
        }

        
        //var_dump($fechas);

        
        
        
        // PARA HACER O NO LA ENTREGA
        $entrega = trim($_POST['entrega']);

        $egreso = $db->from('inv_egresos')->where('id_egreso',$_POST['id_egreso'])->fetch_first();
        // Actualizamos el egreso
        
        if($_POST['id_egreso'] > 0){
            $proforma = array(
                'monto_total' => $monto_total,
                'cliente_id' => $id_cliente,
                'nit_ci' => $nit_ci,
                'nombre_cliente' => mb_strtoupper($nombre_cliente, 'UTF-8'),
                'nro_registros' => $nro_registros,
                'observacion' => $prioridad,
                'descripcion_venta' => $observacion,
                'plan_de_pagos' => ($plan == 'no') ? 'no' : 'si',
            );

            // Guarda la informacion
            $db->where('id_egreso',$_POST['id_egreso'])->update('inv_egresos', $proforma);
            
            $db->delete()->from('inv_egresos_detalles')->where('egreso_id', $_POST['id_egreso'])->execute();
            
            //  recorremos los nuevos productos
            foreach($productos as $nro => $producto){
                $unidad2 = $db->select('id_unidad')->from('inv_unidades')->where('unidad',$unidad[$nro])->fetch_first();
                $unidad3 = $unidad2['id_unidad'];
                $cantidad = $cantidades[$nro];

                $detalle1 = array(
                    'cantidad' => $cantidad,
                    'unidad_id' => $unidad3,
                    'precio' => $precios[$nro],
                    'descuento' => '0',
                    'producto_id' => $productos[$nro],
                    'egreso_id' => $_POST['id_egreso'],
                    'lote' => explode(': ',$lote[$nro])[1],
                    'vencimiento' => explode(': ',$vencimiento[$nro])[1]
                );
                // Guarda la informacion
                $id1 = $db->insert('inv_egresos_detalles', $detalle1);
            }

            // Inicio para pagos
            //$plan = trim($egreso['plan_de_pagos']);
            $pagos1 = $db->from('inv_pagos')->where('movimiento_id', $_POST['id_egreso'])->where( 'tipo', 'Egreso')->fetch_first();
            $db->delete()->from('inv_pagos')->where('movimiento_id', $_POST['id_egreso'])->where( 'tipo', 'Egreso')->execute();
            $db->delete()->from('inv_pagos_detalles')->where('pago_id', $pagos1['id_pago'])->execute();


            if ($plan == 'no') {
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
                    'fecha_pago' => '0000-00-00',
                    'monto' => $monto_total,
                    'tipo_pago' => '', //$tipo_pago
                    'nro_pago' => '0',
                    'empleado_id' => 0,
                    'estado'  => '0',
                    'codigo'=>0
                );
                // Guarda la informacion
                $db->insert('inv_pagos_detalles', $detallePlan);
            }

            //Cuentas
            if ($plan == "si") {
                // Instancia el ingreso
                $ingresoPlan = array(
                    'movimiento_id' => $_POST['id_egreso'],
                    'interes_pago' => 0,
                    'tipo' => 'Egreso'
                );
                // Guarda la informacion del ingreso general
                $egreso_id_plan = $db->insert('inv_pagos', $ingresoPlan);

                $nro_cuota = 0;
                for ($nro2 = 0; $nro2 < $nro_cuentas; $nro2++) {
                    if (isset($fechas[$nro2])) {
                        $fecha_format = date_create($fechas[$nro2]); //date_format(, 'Y-m-d');
                    } else {
                        $fecha_format = date_create("00000-00-00");
                    }

                    $nro_cuota++;
                    $detallePlan = array(
                        'nro_cuota' => $nro_cuota,
                        'pago_id' => $egreso_id_plan,
                        'fecha' => $fecha_format->format('Y-m-d'),
                        'fecha_pago' => '0000-00-00',
                        'monto' => (isset($cuotas[$nro2])) ? $cuotas[$nro2] : 0,
                        'tipo_pago' => '',
                        'nro_pago' => '',
                        'empleado_id' => $_user['persona_id'],
                        'estado'  => '0'
                    );
                    // Guarda la informacion
                    $db->insert('inv_pagos_detalles', $detallePlan);
                }
            }
        }
        
        set_notification('success', 'Accion satisfactoria.', 'El registro se modificó correctamente.');
        redirect($_POST['atras']);
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