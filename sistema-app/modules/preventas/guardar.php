<?php
// echo json_encode($_POST); die();
if (is_ajax() && is_post()) {
    // Verifica la existencia de los datos enviados
    if (isset($_POST['nit_ci']) && isset($_POST['nombre_cliente']) && isset($_POST['productos']) && isset($_POST['nombres']) && isset($_POST['cantidades']) && isset($_POST['precios']) && isset($_POST['descuentos']) && isset($_POST['nro_registros']) && isset($_POST['monto_total']) && isset($_POST['almacen_id'])) {
        // Importa la libreria para convertir el numero a letra
        require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

        // Obtiene los datos de la proforma
        $id_cliente =   (isset($_POST['id_cliente'])) ? trim($_POST['id_cliente']) : '';  
        $nit_ci =       (isset($_POST['nit_ci'])) ? trim($_POST['nit_ci']) : '';          
        $nombre_cliente = (isset($_POST['nombre_cliente'])) ? trim($_POST['nombre_cliente']) : '';          
        $telefono =     (isset($_POST['telefono_cliente'])) ? trim($_POST['telefono_cliente']) : '';          
        $tipo_cli =     (isset($_POST['tipo_cli'])) ? trim($_POST['tipo_cli']) : '';         
        $ciudad_id =    (isset($_POST['ciudad'])) ? trim($_POST['ciudad']) : '';          
        $observacion =  (isset($_POST['observacion'])) ? trim($_POST['observacion']) : '';          
        $direccion =    (isset($_POST['direccion'])) ? trim($_POST['direccion']) : '';         
        $atencion =     (isset($_POST['atencion'])) ? trim($_POST['atencion']) : '';          
        $productos =    (isset($_POST['productos'])) ? $_POST['productos'] : array();
        $nombres =      (isset($_POST['nombres'])) ? $_POST['nombres'] : array();
        $cantidades =   (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
        $unidad =       (isset($_POST['unidad'])) ? $_POST['unidad'] : array();
        $precios =      (isset($_POST['precios'])) ? $_POST['precios'] : array();
        $descuentos =   (isset($_POST['descuentos'])) ? $_POST['descuentos'] : array();
        $nro_registros = (isset($_POST['nro_registros'])) ? trim($_POST['nro_registros']) : '0';
        
        $almacen_id =   (isset($_POST['almacen_id'])) ? trim($_POST['almacen_id']) : '0';             
        $adelanto =     (isset($_POST['adelanto'])) ? trim($_POST['adelanto']) : '0';     
        $lote		=   (isset($_POST['lote'])) ? $_POST['lote'] : array();
		$vencimiento=   (isset($_POST['vencimiento'])) ? $_POST['vencimiento'] : array();
 		
 		//Cuentas
        $tipo_pago =    (isset($_POST['tipo_pago'])) ? trim($_POST['tipo_pago']) : 'Efectivo';
        
        $prioridad =    (isset($_POST['prioridad'])) ? $_POST['prioridad'] : '';         
        $distribuir = (isset($_POST['distribuir'])) ? trim($_POST['distribuir']) : '';

        $monto_total=0;
        foreach ($productos as $nro => $elemento) {
			$cantidad = $cantidades[$nro];
			$precio = $precios[$nro];
            $monto_total=$monto_total+($cantidad*$precio);
        }

        
        $descuento_porc = isset($_POST['descuento_porc'])?trim($_POST['descuento_porc']):0;
        $descuento_bs = trim($_POST['descuento_bs']);
        $total_importe_descuento = trim($_POST['total_importe_descuento']);

        $nro_cuentas = trim($_POST['nro_cuentas']);
        $plan = (isset($_POST['forma_pago'])) ? trim($_POST['forma_pago']) : '';
        $plan = ($plan == "2") ? "si" : "no";
        if ($plan == "si") {
            $fechas = (isset($_POST['fecha'])) ? $_POST['fecha'] : array();
            $cuotas = (isset($_POST['cuota'])) ? $_POST['cuota'] : array();
        } else {
            
        }
        
        // if ($_POST['reserva']) {
        //     $reserva = 'si';
        // } else {
        //     $reserva = 'no';
        // }

        // Obtiene el empleado
        $empleado = isset($_POST['empleado'])?trim($_POST['empleado']):$_user['persona_id'];

        $empleadox = $db->query(" select id_cliente_grupo 
                            from inv_clientes_grupos
                            WHERE vendedor_id='".$_POST['empleado']."'
                         ")->fetch_first();
        
        // obtiene a el cliente
        $cliente = $db->select('*')->from('inv_clientes')->where('id_cliente', $id_cliente)->fetch_first();
        if(!$cliente){
            $cl = array(
                'cliente' => $nombre_cliente,
                'nombre_factura' => $nombre_cliente,
				'nit' => $nit_ci,
				'direccion' =>  $_POST['direccion'],
				'descripcion' => '',
				'telefono' =>  $_POST['telefono'],
				'ubicacion' =>  $_POST['ubicacion'],
				'imagen' =>  '',
				'tipo' =>  '',
				'fecha_creacion'=>date("Y-m-d  H:i:s"),
			    'empleado_id' => $_user['persona_id'],
				'cliente_grupo_id' =>  0
            );
			// $db->insert('inv_clientes',$cl);
			$idcli = $db->insert('inv_clientes',$cl);
			$cliente = $db->select('*')->from('inv_clientes')->where('id_cliente', $idcli)->fetch_first();
        }


        

        // Obtiene el numero de nota
        $nro_factura = $db->query("select count(id_egreso) + 1 as nro_factura from inv_egresos where tipo = 'Venta' and provisionado = 'S'")->fetch_first();
        $nro_factura = $nro_factura['nro_factura'];

        // Define la variable de subtotales
        $subtotales = array();

        // Obtiene la moneda
        $moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
        $moneda = ($moneda) ? $moneda['moneda'] : '';

        $a = 0;
        $b = 0;
        foreach ($productos as $nro2 => $elemento2) {
            $aux = $db->select('*')->from('inv_productos')->where('id_producto', $elemento2)->fetch_first();
            if ($aux['grupo'] != '') {
                $a = $a + $precios[$nro2] * $cantidades[$nro2];
                $b = $b + 1;
            }
        }
        $monto_total = $monto_total - $a;

        $nro_factura = 0;
    
        if (($nro_registros - $b) != 0) {
            // Instancia la proforma
            $proforma = array(
                'fecha_egreso' => date('Y-m-d'),
                'hora_egreso' => date('H:i:s'),
                'fecha_habilitacion' => date('Y-m-d H:i:s'),
				'tipo' => 'Preventa',  // Venta
                'tipo_inicial' => 'Preventa',  // Venta
                'provisionado' => 'S',
                'descripcion' => 'Venta de productos con preventa',
                'nro_nota' => $nro_factura,
                'nro_factura' => 0,
                'nro_autorizacion' => '',
                'codigo_control' => '',
                'fecha_limite' => '0000-00-00',
                'monto_total' => $monto_total,
                'cliente_id' => $cliente['id_cliente'],
                'nit_ci' => $nit_ci,
                'nombre_cliente' => $nombre_cliente,
                'nro_registros' => $nro_registros - $b,
                'dosificacion_id' => 0,
                'almacen_id' => $almacen_id,
                'empleado_id' => $_user['persona_id'],
    			'vendedor_id' => $_POST['empleado'],
    			'codigo_vendedor'=>$empleadox['id_cliente_grupo'],
    			'coordenadas' => $atencion,
                'observacion' => $prioridad,
                'estadoe' => 2,
                'descripcion_venta' => $observacion,
                'ruta_id' => 0,
                'plan_de_pagos' => ($plan == 'no') ? 'no' : 'si',
                'estado' => 1,
                'descuento_porcentaje' => $descuento_porc,
                'descuento_bs' => $descuento_bs,
                'monto_total_descuento' => $total_importe_descuento,
                'nro_movimiento' => 0, //$movimiento, // + 1
                'distribuir'=>$distribuir,
            );
            
            // Guarda la informacion
            $proforma_id = $db->insert('inv_egresos', $proforma);

            // Guarda Historial
            $data = array(
                'fecha_proceso' => date("Y-m-d"),
                'hora_proceso' => date("H:i:s"),
                'proceso' => 'c',
                'nivel' => 'l',
                'direccion' => '?/preventas/guardar',
                'detalle' => 'Se creo inventario egreso con identificador numero ' . $proforma_id,
                'usuario_id' => $_SESSION[user]['id_user']
            );

            $db->insert('sys_procesos', $data);
        }

        // Recorre los productos
        foreach ($productos as $nro => $elemento) {
            $id_unidad = $db->query('SELECT id_unidad FROM inv_unidades WHERE unidad = "'.trim($unidad[$nro]).'" LIMIT 1 ')->fetch_first();
            $cantidad = $cantidades[$nro];

            $aux = $db->select('*')
                      ->from('inv_productos')
                      ->where('id_producto', $productos[$nro])
                      ->fetch_first();
            
            $loteX = explode(': ',$lote[$nro])[1];
            $loteX = trim($loteX);
			
            $detalle = array(
                'cantidad' => $cantidad,
                'unidad_id' => $id_unidad['id_unidad'],
                'precio' => $precios[$nro] - $descuentos[$nro],
                'descuento' => 0, // $descuentos[$nro]
                'producto_id' => $productos[$nro],
                'egreso_id' => $proforma_id,
                'lote' => $loteX,
                'vencimiento' => explode(': ',$vencimiento[$nro])[1],
            );

            $id_detalle = $db->insert('inv_egresos_detalles', $detalle);
        }
    
        // Instancia la respuesta
        $respuesta = array(
            'papel_ancho' => 10,
            'papel_alto' => 25,
            'papel_limite' => 576,
            'empresa_nombre' => $_institution['nombre'],
            'empresa_sucursal' => 'SUCURSAL Nº 1',
            'empresa_direccion' => $_institution['direccion'],
            'empresa_telefono' => 'TELÉFONO ' . $_institution['telefono'],
            'empresa_ciudad' => 'EL ALTO - BOLIVIA',
            'empresa_actividad' => $_institution['razon_social'],
            'empresa_nit' => $_institution['nit'],
            'id_egreso' => $proforma_id
        );

        if ($plan == 'no') {
			// Instancia el ingreso
			$planPago = array(
				'movimiento_id' => $proforma_id,
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
                'tipo_pago' => $tipo_pago,
                'nro_pago' => '0',
				'empleado_id' => $_user['persona_id'],
				'estado'  => '0'
			);
			// Guarda la informacion
			$db->insert('inv_pagos_detalles', $detallePlan);

        }

        //Cuentas
        if ($plan == "si") {
            // Instancia el ingreso
            $ingresoPlan = array(
                'movimiento_id' => $proforma_id,
                'interes_pago' => 0,
                'tipo' => 'Egreso'
            );
            // Guarda la informacion del ingreso general
            $ingreso_id_plan = $db->insert('inv_pagos', $ingresoPlan);

            $nro_cuota = 0;
            for ($nro2 = 0; $nro2 < $nro_cuentas; $nro2++) {
                if (isset($fechas[$nro2])) {
                    $fecha_format = date_create($fechas[$nro2]); //date_format(, 'Y-m-d');
                } else {
                    $fecha_format = date("Y-m-d");
                }

                // $vfecha = explode("-", $fecha_format);

                // if (count($vfecha) > 3) {
                //     $fecha_format = $vfecha[2] . "-" . $vfecha[1] . "-" . $vfecha[0];
                // } else {
                //     $fecha_format = "0000-00-00";
                // }

                $nro_cuota++;
                // if ($nro2 == 0) {
                //     $detallePlan = array(
                //         'nro_cuota' => $nro_cuota,
                //         'pago_id' => $ingreso_id_plan,
                //         'fecha' => $fecha_format->format('Y-m-d'),
                //         'fecha_pago' => $fecha_format->format('Y-m-d'),
                //         'monto' => (isset($cuotas[$nro2])) ? $cuotas[$nro2] : 0,
                //         'tipo_pago' => $tipo_pago,
                //         'empleado_id' => $_user['persona_id'],
                //         'estado'  => '1'
                //     );
                // } else {
                    $detallePlan = array(
                        'nro_cuota' => $nro_cuota,
                        'pago_id' => $ingreso_id_plan,
                        'fecha' => $fecha_format->format('Y-m-d'),
                        'fecha_pago' => "0000-00-00",
                        'monto' => (isset($cuotas[$nro2])) ? $cuotas[$nro2] : 0,
                        'monto_programado' => (isset($cuotas[$nro2])) ? $cuotas[$nro2] : 0,
                        'tipo_pago' => '',
                        'nro_pago' => '',
                        'empleado_id' => $_user['persona_id'],
                        'estado'  => '0'
                    );
                // }
                // Guarda la informacion
                $db->insert('inv_pagos_detalles', $detallePlan);
            }
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
