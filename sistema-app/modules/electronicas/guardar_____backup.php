<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// echo json_encode($_POST); die();
// Verifica si es una peticion ajax y post
if (is_ajax() && is_post()) {
	// Verifica la existencia de los datos enviados
	if (isset($_POST['nit_ci']) && isset($_POST['nombre_cliente']) && isset($_POST['productos']) && isset($_POST['nombres']) && isset($_POST['cantidades']) && isset($_POST['precios']) && isset($_POST['nro_registros']) && isset($_POST['monto_total']) && isset($_POST['almacen_id'])) {
		// Importa la libreria para el codigo de control
		require_once libraries . '/controlcode-class/ControlCode.php';
		require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

		// Obtiene los datos de la venta
		$nit_ci = trim($_POST['nit_ci']);
		$nombre_cliente = trim($_POST['nombre_cliente']);
		$productos = (isset($_POST['productos'])) ? $_POST['productos'] : array();
		$unidad = (isset($_POST['unidad'])) ? $_POST['unidad']: array();
		$nombres = (isset($_POST['nombres'])) ? $_POST['nombres'] : array();
		$cantidades = (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
		$precios = (isset($_POST['precios'])) ? $_POST['precios'] : array();
		$descuentos = (isset($_POST['descuentos'])) ? $_POST['descuentos'] : array();
		$almacen_id = trim($_POST['almacen_id']);
		$nro_registros = trim($_POST['nro_registros']);
		$monto_total = trim($_POST['monto_total']);
		$observacion = trim($_POST['observacion']);
                
		$lote			= (isset($_POST['lote'])) ? $_POST['lote'] : array();
		$vencimiento	= (isset($_POST['vencimiento'])) ? $_POST['vencimiento'] : array();
		$distribuir = trim($_POST['distribuir']);

        //Cuentas
		// $tipo_pago = (isset($_POST['tipo_pago'])) ?trim($_POST['tipo_pago']):'Efectivo';
		// $nro_factura = trim($_POST['nro_factura']);
		// $nro_autorizacion = trim($_POST['nro_autorizacion']);

		// para tipo de pago
		$tipo_pago = trim($_POST['tipo_pago']);
		$nro_pago = trim($_POST['nro_pago']);

		$nro_cuentas = trim($_POST['nro_cuentas']);
		$plan = (isset($_POST['forma_pago']))?trim($_POST['forma_pago']):'';
		$plan = ($plan=="2") ? "si" : "no";	
		if($plan=="si"){

			$fechas = (isset($_POST['fecha'])) ? $_POST['fecha']: array();
			$cuotas = (isset($_POST['cuota'])) ? $_POST['cuota']: array();
		}

        // if($_POST['reserva']){
        //     $reserva = 'si';
        // }else{
        //     $reserva = 'no';
        // }

        $empleadox = $db->query(" select nombre_grupo 
                            from inv_clientes_grupos
                            WHERE vendedor_id='".$_POST['empleado']."'
                         ")->fetch_first();

        //obtiene al cliente
        $cliente = $db->select('*')->from('inv_clientes')->where(array('cliente' => $nombre_cliente, 'nit' => $nit_ci))->fetch_first();
        if(!$cliente){
            $cl = array(
                'cliente' => $nombre_cliente,
                'nit' => $nit_ci
            );
            $id = $db->insert('inv_clientes',$cl);
            // Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/electronicas/guardar',
				'detalle' => 'Se creo electronica con identificador numero ' . $id ,
				'usuario_id' => $_SESSION[user]['id_user']
			);

			$db->insert('sys_procesos', $data) ;
        }

		// Obtiene la fecha de hoy
		$hoy = date('Y-m-d');

		// Obtiene la dosificacion del periodo actual
		$dosificacion = $db->from('inv_dosificaciones')->where('fecha_registro <=', $hoy)->where('fecha_limite >=', $hoy)->where('activo', 'S')->fetch_first();

		// Verifica si la dosificación existe
		if ($dosificacion) {
			// Obtiene los datos para el codigo de control
			$nro_autorizacion = $dosificacion['nro_autorizacion'];
			$nro_factura = intval($dosificacion['nro_facturas']) + 1;
			$nit_ci = $nit_ci;
			$fecha = date('Ymd');
			$total = round($monto_total, 0);
			$llave_dosificacion = base64_decode($dosificacion['llave_dosificacion']);

			// Genera el codigo de control
			$codigo_control = new ControlCode();
			$codigo_control = $codigo_control->generate($nro_autorizacion, $nro_factura, $nit_ci, $fecha, $total, $llave_dosificacion);

			// Define la variable de subtotales
			$subtotales = array();

			// Obtiene la moneda
			$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
			$moneda = ($moneda) ? $moneda['moneda'] : '';

			// Obtiene los datos del monto total
			$conversor = new NumberToLetterConverter();
			$monto_textual = explode('.', $monto_total);
			$monto_numeral = $monto_textual[0];
			$monto_decimal = $monto_textual[1];
			$monto_literal = ucfirst(strtolower(trim($conversor->to_word($monto_numeral))));

    		$nro_nota = $db->query(" select MAX(nro_nota) + 1 as nro_nota 
                                        from inv_egresos 
                                     ")->fetch_first();
                            //where tipo = 'Venta' and provisionado = 'S'
    		$nro_nota = $nro_nota['nro_nota'];

			$movimiento = generarMovimiento($db, $_user['persona_id'], 'VE', $almacen_id);

			if ($tipo_pago == 'EFECTIVO') {
				$nro_pago = $nro_factura;
			}
			
			// Instancia la venta
			$venta = array(
				'fecha_egreso' => date('Y-m-d'),
				'hora_egreso' => date('H:i:s'),
    			'fecha_factura' => date('Y-m-d H:i:s'),
    			'fecha_habilitacion' => date('Y-m-d H:i:s'),
				'tipo' => 'Venta',
                'tipo_inicial' => 'Venta',
			    'distribuir'=>$distribuir,
				'descripcion' => 'Venta de productos con factura electrónica',
				'nro_factura' => $nro_factura,
				'nro_nota' => $nro_nota,
				'nro_autorizacion' => $nro_autorizacion,
				'codigo_control' => $codigo_control,
				'fecha_limite' => $dosificacion['fecha_limite'],
				'monto_total' => $monto_total,
				'nit_ci' => $nit_ci,
				'nombre_cliente' => mb_strtoupper($nombre_cliente, 'UTF-8'),
				'cliente_id' => $cliente['id_cliente'],
				'nro_registros' => $nro_registros,
				'dosificacion_id' => $dosificacion['id_dosificacion'],
				'almacen_id' => $almacen_id,
				'empleado_id' => $_user['persona_id'],
    			'vendedor_id' => $_POST['empleado'],
    			'codigo_vendedor'=>$empleadox['nombre_grupo'],
    			'plan_de_pagos' => ($plan == 'no') ? 'si' : 'si',
				'nro_movimiento' => $movimiento, // + 1
				'descripcion_venta' => $observacion,
                'tipo_pago' => $tipo_pago,
				'nro_pago' => $nro_pago,
			);

            if($distribuir=="S"){
        		$venta['preventa']='habilitado';
        		$venta['estadoe']='2';
            }

			// Guarda la informacion
			$egreso_id = $db->insert('inv_egresos', $venta);

			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/electronicas/guardar',
				'detalle' => 'Se creo inventario egreso con identificador numero ' . $egreso_id ,
				'usuario_id' => $_SESSION[user]['id_user']
			);

			$db->insert('sys_procesos', $data) ;

			// Recorre los productos
			$unidad = (isset($_POST['unidad'])) ? $_POST['unidad']: array();
			foreach ($productos as $nro => $elemento) {
				$id_unidad = $db->query('SELECT id_unidad FROM inv_unidades WHERE unidad = "'.trim($unidad[$nro]).'" LIMIT 1 ')->fetch_first();
				$cantidad = $cantidades[$nro] * ((cantidad_unidad($db, $productos[$nro], $id_unidad['id_unidad']) == 0) ? 1 : cantidad_unidad($db, $productos[$nro], $id_unidad['id_unidad']));

                $vencimientoxx=explode(': ',$vencimiento[$nro])[1];
                $venc_v=explode('/',$vencimientoxx);
                $vencimientoxx=$venc_v[2]."-".$venc_v[1]."-".$venc_v[0];

				/////////////////////////////////////////////////////////////////////////////////////////
				// Forma el detalle
				$detalle = array(
					'cantidad' => $cantidad,
					'precio' => $precios[$nro],
                    'unidad_id' => $id_unidad['id_unidad'],
					'descuento' => 0,
					'producto_id' => $productos[$nro],
					'egreso_id' => $egreso_id,
					'lote' => explode(': ',$lote[$nro])[1],
					'vencimiento' => explode(': ',$vencimiento[$nro])[1],
				);

				// Genera los subtotales
				$subtotales[$nro] = number_format($precios[$nro] * $cantidades[$nro], 2, '.', '');

				// Guarda la informacion
				$id = $db->insert('inv_egresos_detalles', $detalle);


				/*****************************************/
                //ACTUALIZAR LOTE CANTIDAD
                /*****************************************/
                // Forma el detalle
    			$loteX=explode(': ',$lote[$nro])[1];
    			$vencX=$vencimientoxx;
    			
    			$ingresos = $db->query('SELECT *
                                        FROM inv_ingresos_detalles
                                        LEFT JOIN inv_ingresos i ON id_ingreso=ingreso_id
                                        WHERE lote="'.$loteX.'" AND producto_id="'.$productos[$nro].'" AND i.almacen_id="'.$almacen_id.'" 
                                    ')->fetch();
    
                $cantidad_descontar=$cantidades[$nro];
                //echo $cantidad_descontar;
                foreach ($ingresos as $nro => $ingreso) {
                    if($ingreso['lote_cantidad']<$cantidad_descontar){
                        $cantidad_modificar=0;
                        $cantidad_descontar=$cantidad_descontar-$ingreso['lote_cantidad'];
                    }else{
                        $cantidad_modificar=$ingreso['lote_cantidad']-$cantidad_descontar;
                        $cantidad_descontar=0;
                    }
                    
                    $datos = array(
            			'lote_cantidad' => $cantidad_modificar
            		);
            		$condicion = array('id_detalle' => $ingreso['id_detalle']);
            		$db->where($condicion)->update('inv_ingresos_detalles', $datos);
            		//echo $cantidad_modificar;
                }
                /************************************************/
                

				// Guarda en el historial
				$data = array(
					'fecha_proceso' => date("Y-m-d"),
					'hora_proceso' => date("H:i:s"),
					'proceso' => 'c',
					'nivel' => 'l',
					'direccion' => '?/electronicas/guardar',
					'detalle' => 'Se inserto el inventario egreso detalle con identificador numero ' . $id,
					'usuario_id' => $_SESSION[user]['id_user']
				);
				$db->insert('sys_procesos', $data);
			}

			// Actualiza la informacion
			$db->where('id_dosificacion', $dosificacion['id_dosificacion'])->update('inv_dosificaciones', array('nro_facturas' => $nro_factura));

			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'u',
				'nivel' => 'l',
				'direccion' => '?/electronicas/guardar',
				'detalle' => 'Se actualizo almacen con dosificacion con numero ' . $dosificacion['id_dosificacion'] ,
				'usuario_id' => $_SESSION[user]['id_user']
			);
			$db->insert('sys_procesos', $data) ; 
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
				'empresa_empleado' => ($_user['persona_id'] == 0) ? upper($_user['username']) : upper(trim($_user['nombres'] . ' ' . $_user['paterno'] . ' ' . $_user['materno'])),
				'empresa_agradecimiento' => '¡Gracias por tu compra!',
				'factura_titulo' => 'F  A  C  T  U  R  A',
				'factura_numero' => $venta['nro_factura'],
				'factura_autorizacion' => $venta['nro_autorizacion'],
				'factura_fecha' => date_decode($venta['fecha_egreso'], 'd/m/Y'),
				'factura_hora' => substr($venta['hora_egreso'], 0, 5),
				'factura_codigo' => $venta['codigo_control'],
				'factura_limite' => date_decode($venta['fecha_limite'], 'd/m/Y'),
				'factura_autenticidad' => '"ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS. EL USO ILÍCITO DE ÉSTA SERÁ SANCIONADO DE ACUERDO A LEY"',
				'factura_leyenda' => 'Ley Nº 453: "' . $dosificacion['leyenda'] . '".',
				'cliente_nit' => $venta['nit_ci'],
				'cliente_nombre' => $venta['nombre_cliente'],
				'venta_titulos' => array('CANT.', 'DETALLE', 'P. UNIT.', 'SUBTOTAL', 'TOTAL'),
				'venta_cantidades' => $cantidades,
				'venta_detalles' => $nombres,
				'venta_precios' => $precios,
				'venta_subtotales' => $subtotales,
				'venta_total_numeral' => $venta['monto_total'],
				'venta_total_literal' => $monto_literal,
				'venta_total_decimal' => $monto_decimal . '/100',
				'venta_moneda' => $moneda,
				'importe_base' => '0',
				'importe_ice' => '0',
				'importe_venta' => '0',
				'importe_credito' => '0',
				'importe_descuento' => '0',
				'impresora' => $_terminal['impresora']
			);

			// Envia respuesta
			// echo json_encode($respuesta);
			// echo json_encode($egreso_id);

			///////

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
			    $code = $db->select('MAX(inv_pagos_detalles.codigo) as code')->from('inv_pagos_detalles')->join('inv_pagos', 'inv_pagos.id_pago = inv_pagos_detalles.pago_id')->where('inv_pagos.tipo', 'Egreso')->fetch_first();
				// Genera el plan de pagos
				$detallePlan = array(
					'nro_cuota' => 1,
					'pago_id' => $id_plan_egreso,
					'fecha' => date('Y-m-d'),
					'fecha_pago' => date('Y-m-d'),
					'monto' => $monto_total,
					'tipo_pago' => $tipo_pago,
					'nro_pago' => $nro_pago,
					'empleado_id' => $_user['persona_id'],
					'estado'  => '1',
				    'codigo' => $code['code']+1
				);
				// Guarda la informacion
				$db->insert('inv_pagos_detalles', $detallePlan);
				$recibo = 'si';
			}

			//Cuentas
			if($plan=="si"){
				// Instancia el ingreso
				$ingresoPlan = array(
					'movimiento_id' => $egreso_id,
					'interes_pago' => 0,
					'tipo' => 'Egreso'
				);
				// Guarda la informacion del ingreso general
				$ingreso_id_plan = $db->insert('inv_pagos', $ingresoPlan);
				$nro_cuota=0;
				for($nro2=0; $nro2<$nro_cuentas; $nro2++) {
					if(isset($fechas[$nro2])){
						$fecha_format=$fechas[$nro2];
					}else{
						$fecha_format="00-00-0000";
					}

					$vfecha=explode("-",$fecha_format);

					if(count($vfecha)==3){
						$fecha_format=$vfecha[2]."-".$vfecha[1]."-".$vfecha[0];
					}
					else{
						$fecha_format="0000-00-00";
					}

					$nro_cuota++;
					
					$dt=strtotime($fechas[0]);
                    $fecha_comp = date("Y-m-d", $dt);
                    if($fecha_comp == date('Y-m-d')){
        				$recibo = 'si';
        			} else {
        			    $recibo = 'no';    
        			}
    			
    			
					if($nro2 == 0 && $fecha_comp == date('Y-m-d')){
					    $code = $db->select('MAX(inv_pagos_detalles.codigo) as code')->from('inv_pagos_detalles')->join('inv_pagos', 'inv_pagos.id_pago = inv_pagos_detalles.pago_id')->where('inv_pagos.tipo', 'Egreso')->fetch_first();
						$detallePlan = array(
							'nro_cuota' => $nro_cuota,
							'pago_id' => $ingreso_id_plan,
							'fecha' => $fecha_format,
							'fecha_pago' => $fecha_format,
							'monto' => (isset($cuotas[$nro2])) ? $cuotas[$nro2]: 0,
							'tipo_pago' => $tipo_pago,
							'nro_pago' => $nro_pago,
							'empleado_id' => $_user['persona_id'],
							'estado'  => '1',
						    'codigo' => $code['code']+1
						);
					}else{
						$detallePlan = array(
							'nro_cuota' => $nro_cuota,
							'pago_id' => $ingreso_id_plan,
							'fecha' => $fecha_format,
							'fecha_pago' => $fecha_format,
							'monto' => (isset($cuotas[$nro2])) ? $cuotas[$nro2]: 0,
							'tipo_pago' => '',
							'nro_pago' => 0,
							'empleado_id' => $_user['persona_id'],
							'estado'  => '0',
						    'codigo' => 0
						);
					}
					// Guarda la informacion
					$db->insert('inv_pagos_detalles', $detallePlan);
				}
			}
            
            // Instancia el objeto
            $respuesta = array(
                'egreso_id' => $egreso_id,
                'recibo' => $recibo,
            );
            // Devuelve los resultados
            echo json_encode($respuesta);
		//////////////////////
		} else {
			// Envia respuesta
			echo 'error';
		}
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