<?php
// echo json_encode($_POST); die();

if (is_ajax() && is_post()) {
	// echo json_encode($_POST); die();
	// Verifica la existencia de los datos enviados 
	if (isset($_POST['nit_ci']) && isset($_POST['nombre_cliente']) && isset($_POST['productos']) && isset($_POST['nombres']) && 
	    isset($_POST['cantidades']) && isset($_POST['precios']) && isset($_POST['nro_registros']) && 
	    isset($_POST['monto_total']) && isset($_POST['almacen_id'])) {
	        
		// Importa la libreria para convertir el numero a letra
		require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

		// Obtiene los datos de la nota
		$nit_ci 		= trim($_POST['nit_ci']);
		$nombre_cliente	= trim($_POST['nombre_cliente']);
		$id_cliente 	= trim($_POST['id_cliente']);
		$productos 		= (isset($_POST['productos'])) ? $_POST['productos'] : array();
		$nombres 		= (isset($_POST['nombres'])) ? $_POST['nombres'] : array();
		$cantidades		= (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
		$unidad 		= (isset($_POST['unidad'])) ? $_POST['unidad'] : array();
		$precios 		= (isset($_POST['precios'])) ? $_POST['precios'] : array();
		$nro_registros 	= trim($_POST['nro_registros']);
		
		//$monto_total 	= trim($_POST['monto_total']);
		
		//$des_reserva 	= trim($_POST['des_reserva']);
		$reserva 		= isset($_POST['reserva']);
		$almacen_id 	= trim($_POST['almacen_id']);
		$lote			= (isset($_POST['lote'])) ? $_POST['lote'] : array();
		$vencimiento	= (isset($_POST['vencimiento'])) ? $_POST['vencimiento'] : array();
		
		// para tipo de pago
		$nro_pago = trim($_POST['nro_pago']);
		$distribuir = trim($_POST['distribuir']);
        $observacion = trim($_POST['observacion']);
		
		//Cuentas
		$tipo_pago = (isset($_POST['tipo_pago'])) ? trim($_POST['tipo_pago']) : 'EFECTIVO';

        $empleadox = $db->query(" 	SELECT id_cliente_grupo 
                                    from inv_clientes_grupos
                                    WHERE vendedor_id='".$_POST['empleado']."'
                                 ")->fetch_first();
                        //where tipo = 'Venta' and provisionado = 'S'

        $monto_total=0;
        foreach ($productos as $nro => $elemento) {
			$cantidad = $cantidades[$nro];
			$precio = $precios[$nro];
            $monto_total=$monto_total+($cantidad*$precio);
        }

		$nro_cuentas = trim($_POST['nro_cuentas']);
		$plan = (isset($_POST['forma_pago'])) ? trim($_POST['forma_pago']) : '';
		$plan = ($plan == "2") ? "si" : "no";
		if ($plan == "si") {

			$fechas = (isset($_POST['fecha'])) ? $_POST['fecha'] : array();
			$cuotas = (isset($_POST['cuota'])) ? $_POST['cuota'] : array();
		}

		if (isset($_POST['reserva'])) {
			$reserva = 'si';
		} else {
			$reserva = 'no';
		}

		//descuento
		$descuento_porc = isset($_POST['descuento_porc']) ? trim($_POST['descuento_porc']) : 0;
		$descuento_bs = (isset($_POST['descuento_bs'])) ? clear($_POST['descuento_bs']) : 0;
		$total_importe_descuento = trim($_POST['total_importe_descuento']);

		// Obtiene el numero de nota
		$nro_facturax = $db->query("SELECT IFNULL(MAX(nro_nota),0) + 1 as nro_factura 
                                    from inv_egresos 
                                 ")->fetch_first();
                        //where tipo = 'Venta' and provisionado = 'S'
		if($nro_facturax){
		    $nro_factura = $nro_facturax['nro_factura'];
        }else{
            $nro_factura = 1;
        }
        
		// Define la variable de subtotales
		$subtotales = array();

		// Obtiene la moneda
		$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
		$moneda = ($moneda) ? $moneda['moneda'] : '';

		// Obtiene los datos del monto total
		$conversor = new NumberToLetterConverter();
		$monto_textual = explode('.', $monto_total);
		$monto_numeral = $monto_textual[0];
		if(isset($monto_textual[1])){
			$monto_decimal = $monto_textual[1];
		}else{
			$monto_decimal = '';
		}
		$monto_literal = ucfirst(strtolower(trim($conversor->to_word($monto_numeral))));

		// obtiene a el cliente
        /*
        $cliente = $db->select('*')->from('inv_clientes')->where(array('cliente' => $nombre_cliente, 'nit' => $nit_ci))->fetch_first();
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
        */
		$movimiento = generarMovimiento($db, $_user['persona_id'], 'NR', $almacen_id);

		// Instancia la nota
		$nota = array(
			'fecha_egreso' => date('Y-m-d'),
			'hora_egreso' => date('H:i:s'),
			'fecha_habilitacion' => date('Y-m-d H:i:s'),
			'tipo' => 'Venta',
			'tipo_inicial' => 'Venta',
			'distribuir'=>$distribuir,
			'provisionado' => 'S',
			'descripcion' => 'Venta de productos con nota de venta',
			'nro_nota' => $nro_factura,
			//'nro_factura' => 0,
			// 'nro_autorizacion' => '',
			// 'codigo_control' => '',
			// 'fecha_limite' => '0000-00-00',
			'monto_total' => $monto_total,
			// 'nit_ci' => $nit_ci,
			// 'nombre_cliente' => strtoupper($nombre_cliente),
			'cliente_id' => $id_cliente,
			'nro_registros' => $nro_registros,
			//'dosificacion_id' => 0,
			'almacen_id' => $almacen_id,
			'estado' => 1,
			'observacion' => "",
			'empleado_id' => $_user['persona_id'],
			'vendedor_id' => $_POST['empleado'],
			'codigo_vendedor'=>$empleadox['id_cliente_grupo'],
			'plan_de_pagos' => ($plan == 'no') ? 'no' : 'si',
			'descuento_porcentaje' => $descuento_porc,
			'descuento_bs' => $descuento_bs,
			'monto_total_descuento' => $monto_total,                            //$total_importe_descuento,
			'nro_movimiento' => $movimiento, // + 1
			'tipo_pago' => $tipo_pago,
			'nro_pago' => $nro_pago,
			'descripcion_venta' => $observacion,
			'preventa'=>"habilitado",
    		'estadoe'=>2,

    		//##siat fields
			// 'monto_giftcard'			=> 0,
			// 'codigo_sucursal'			=> 0,
			// 'punto_venta'				=> 0,
			// 'codigo_documento_sector'	=> 1, //compra venta
			// 'tipo_documento_identidad'	=> 1, //CI,
			// //'tipo_documento_identidad'	=> (int)$_POST['tipo_documento_identidad'], //CI,
			// 'codigo_metodo_pago'		=> (int)$_POST['codigo_metodo_pago'],
			// 'codigo_moneda'				=> 1,
			// //'complemento'				=> $_POST['complemento'],
			// 'complemento'				=> "",
			// 'numero_tarjeta'			=> $_POST['numero_tarjeta'],
			// 'tipo_cambio'				=> 1,
			
		);

    //     if($distribuir=="S"){
    // 		$nota['preventa']='habilitado';
    // 		$nota['estadoe']='2';
    //     }

		// Guarda la informacion
		$egreso_id = $db->insert('inv_egresos', $nota);
		

		// Instancia la nota
		$nota = array(
			'nro_factura' => 0,
			'nro_autorizacion' => '',
			'codigo_control' => '',
			'fecha_limite' => '0000-00-00',
			'nit_ci' => $nit_ci,
			'nombre_cliente' => strtoupper($nombre_cliente),
			'dosificacion_id' => 0,
			'monto_giftcard'			=> 0,
			'codigo_sucursal'			=> 0,
			'punto_venta'				=> 0,
			'codigo_documento_sector'	=> 1, //compra venta
			//'tipo_documento_identidad'	=> (int)$_POST['tipo_documento_identidad'], //CI,
			'tipo_documento_identidad'	=> 1, //CI,
			'codigo_metodo_pago'		=> (int)$_POST['codigo_metodo_pago'],
			'codigo_moneda'				=> 1,
			'complemento'				=> "",
			//'complemento'				=> $_POST['complemento'],
			'numero_tarjeta'			=> $_POST['numero_tarjeta'],
			'tipo_cambio'				=> 1,			
			'egreso_id' => $egreso_id,			
		);
		// Guarda la informacion
		$factura_id = $db->insert('inv_egresos_facturas', $nota);


		// Guarda en el historial
		$data = array(
			'fecha_proceso' => date("Y-m-d"),
			'hora_proceso' => date("H:i:s"),
			'proceso' => 'c',
			'nivel' => 'l',
			'direccion' => '?/notas/guardar',
			'detalle' => 'Se inserto el inventario egreso con identificador numero ' . $egreso_id,
			'usuario_id' => $_SESSION[user]['id_user']
		);
		$db->insert('sys_procesos', $data);

		// Recorre los productos
		foreach ($productos as $nro => $elemento) {
			// Forma el detalle
			$id_unidad = $db->query('SELECT id_unidad FROM inv_unidades WHERE unidad = "'.trim($unidad[$nro]).'" LIMIT 1 ')->fetch_first();
			
			$cantidad = $cantidades[$nro] * ((cantidad_unidad($db, $productos[$nro], $id_unidad['id_unidad']) == 0) ? 1 : cantidad_unidad($db, $productos[$nro], $id_unidad['id_unidad']));
			
            $vencimientoxx=explode(': ',$vencimiento[$nro])[1];
            $venc_v=explode('/',$vencimientoxx);
            $vencimientoxx=$venc_v[2]."-".$venc_v[1]."-".$venc_v[0];

			/*****************************************/
            //ACTUALIZAR LOTE CANTIDAD
            /*****************************************/
            // Forma el detalle
			$loteX=explode(': ',$lote[$nro])[1];
			$loteX = trim($loteX);
			$vencX=$vencimientoxx;
			
			$ingresos = $db->query('SELECT *
                                    FROM inv_ingresos_detalles
                                    LEFT JOIN inv_ingresos i ON id_ingreso=ingreso_id
                                    WHERE lote="'.$loteX.'" AND producto_id="'.$productos[$nro].'" AND i.almacen_id="'.$almacen_id.'"  
                                ')->fetch();

            $cantidad_descontar=$cantidades[$nro];
            
            foreach ($ingresos as $nro222 => $ingreso) {
                
                if($ingreso['lote_cantidad']<$cantidad_descontar){
                    $cantidad_egreso=$ingreso['lote_cantidad'];
                    $cantidad_modificar=0;
                    $cantidad_descontar=$cantidad_descontar-$ingreso['lote_cantidad'];
                }else{
                    $cantidad_egreso=$cantidad_descontar;
                    $cantidad_modificar=$ingreso['lote_cantidad']-$cantidad_descontar;
                    $cantidad_descontar=0;
                }
                
                $datos = array(
        			'lote_cantidad' => $cantidad_modificar
        		);
        		$condicion = array('id_detalle' => $ingreso['id_detalle']);
        		$db->where($condicion)->update('inv_ingresos_detalles', $datos);
        		
        		/******************************************************/
                if($cantidad_egreso>0){
                    $detalle = array(
        				'cantidad' => $cantidad_egreso,
        				'precio' => $precios[$nro],
                        'unidad_id' => $id_unidad['id_unidad'],
    					'descuento' => 0,
        				'producto_id' => $productos[$nro],
        				'egreso_id' => $egreso_id,
        				'lote' => $loteX,
        				'vencimiento' => $vencX,
        				'ingresos_detalles_id'=>$ingreso['id_detalle'],
        			);
                    $id_detalle = $db->insert('inv_egresos_detalles', $detalle);
                }
            }
		    /************************************************/
            
			// Guarda en el historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"),
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/notas/guardar',
				'detalle' => 'Se inserto el inventario egreso detalle con identificador numero ' . $egreso_id,
				'usuario_id' => $_SESSION[user]['id_user']
			);
			$db->insert('sys_procesos', $data);
		}

        $nro_recibo=0;

		if ($plan == 'no') {
			// Instancia el ingreso
			$planPago = array(
				'movimiento_id' => $egreso_id,
				'interes_pago' => 0,
				'tipo' => 'Egreso'
			);
			// Guarda la informacion del ingreso general
			$id_plan_egreso = $db->insert('inv_pagos', $planPago);
			// Genera el plan de pagos
			
// 			$code = $db->select('MAX(inv_pagos_detalles.codigo) as code')
// 			           ->from('inv_pagos_detalles')
// 			           ->join('inv_pagos', 'inv_pagos.id_pago = inv_pagos_detalles.pago_id')
// 			           ->where('inv_pagos.tipo', 'Egreso')
// 			           ->fetch_first();
			           
			$detallePlan = array(
				'nro_cuota' => 1,
				'pago_id' => $id_plan_egreso,
				'fecha' => date('Y-m-d'),
				'fecha_pago' => date('Y-m-d'),
				'hora_pago' => date('H:i:s'),
				'monto' => $monto_total,
				'monto_programado' => $monto_total,
				'tipo_pago' => $tipo_pago,
				'nro_pago' => $nro_pago,
				'empleado_id' => $_user['persona_id'],
				'estado'  => '0',
				'codigo' => 0   //$code['code']+1
			);
			// Guarda la informacion
			$nro_recibo=$db->insert('inv_pagos_detalles', $detallePlan);
			$recibo = 'no';
		}

		// Cuentas
		if ($plan == "si") {
			// Instancia el ingreso
			$ingresoPlan = array(
				'movimiento_id' => $egreso_id,
				'interes_pago' => 0,
				'tipo' => 'Egreso'
			);
			// Guarda la informacion del ingreso general
			$ingreso_id_plan = $db->insert('inv_pagos', $ingresoPlan);

			$nro_cuota = 0;

			for ($nro2 = 0; $nro2 < $nro_cuentas; $nro2++) {
				if (isset($fechas[$nro2])) {
					$fecha_format = $fechas[$nro2];
				} else {
					$fecha_format = date("Y-m-d");
				}
				
				$vfecha = explode("-", $fecha_format);
				
				if (count($vfecha) == 3) {
					$fecha_format = $vfecha[2] . "-" . $vfecha[1] . "-" . $vfecha[0];
				} else {
					$fecha_format = date("Y-m-d");
				}

				$nro_cuota++;
				
				$recibo = 'no';    
    			
				$detallePlan = array(
					'nro_cuota' => $nro_cuota,
					'pago_id' => $ingreso_id_plan,
					'fecha' => $fecha_format,
					'fecha_pago' => $fecha_format,
					'hora_pago' => date('H:i:s'),
					'monto' => (isset($cuotas[$nro2])) ? $cuotas[$nro2] : 0,
					'monto_programado' => (isset($cuotas[$nro2])) ? $cuotas[$nro2] : 0,
					'tipo_pago' => '',
					'nro_pago' => '0',
					'empleado_id' => $_user['persona_id'],
					'estado'  => '0',
					'codigo' => 0
				);
			
				// Guarda la informacion
				$db->insert('inv_pagos_detalles', $detallePlan);
			}
		}
		
		
		
		
		if($distribuir=='N'){
            $venta = $db->query('select MAX(nro_salida)as nro_salida
                                     from inv_asignaciones_clientes')
                        ->fetch_first();

            $asignacion = array(
                'egreso_id'         => $egreso_id,
                'distribuidor_id'   => $_user['persona_id'],
                'fecha_entrega'     => date('Y-m-d H:i:s'),
                'estado_pedido'     => 'salida',
                'empleado_id'       => $_user['persona_id'],
                'estado'            => 'A',
                'fecha_hora_salida' => date('Y-m-d H:i:s'), 
                //'nro_salida'        => ($venta['nro_salida']+1)
                'nro_salida'        => -1
            );
            // Guardamos el asignacion
            $id_asignacion = $db->insert('inv_asignaciones_clientes', $asignacion);
        }
		
		
		
		
		// Instancia el objeto
        $respuesta = array(
            'egreso_id' => $egreso_id,
            'recibo' => $recibo,
            'nro_recibo'=>$nro_recibo
        );
        // Devuelve los resultados
        echo json_encode($respuesta);

		// Envia respuesta
// 		echo json_encode($egreso_id);
	} else {
		// Envia respuesta
		echo 'error';
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}
