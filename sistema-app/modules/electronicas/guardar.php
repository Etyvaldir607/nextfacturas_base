<?php

//echo json_encode($_POST); die();
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (is_ajax() && is_post()) {
	// echo json_encode($_POST); die();
	// Verifica la existencia de los datos enviados 
	if (
		isset($_POST['nit_ci']) &&
		isset($_POST['nombre_cliente']) &&
		isset($_POST['productos']) &&
		isset($_POST['nombres']) &&
		isset($_POST['cantidades']) &&
		isset($_POST['precios']) &&
		isset($_POST['nro_registros']) &&
		isset($_POST['monto_total']) &&
		isset($_POST['almacen_id'])
	) {

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

		$des_reserva 	= isset($_POST['des_reserva']) ? trim($_POST['des_reserva']) : '';
		$reserva 		= isset($_POST['reserva']);
		$almacen_id 	= trim($_POST['almacen_id']);
		$lote			= (isset($_POST['lote'])) ? $_POST['lote'] : array();
		$vencimiento	= (isset($_POST['vencimiento'])) ? $_POST['vencimiento'] : array();

		// para tipo de pago
		$nro_pago 		= trim($_POST['nro_pago']);
		$distribuir 	= trim($_POST['distribuir']);
		$observacion 	= trim($_POST['observacion']);

		$punto_venta 	= intval($_POST['puntoventa_id']);

		//Cuentas
		$tipo_pago = (isset($_POST['tipo_pago'])) ? trim($_POST['tipo_pago']) : 'EFECTIVO';

		$empleadox = $db->query(" select id_cliente_grupo 
                                    from inv_clientes_grupos
                                    WHERE vendedor_id='" . $_POST['empleado'] . "'
                                 ")->fetch_first();
		//where tipo = 'Venta' and provisionado = 'S'

		$monto_total = 0;
		foreach ($productos as $nro => $elemento) {
			$cantidad = $cantidades[$nro];
			$precio = $precios[$nro];
			$monto_total = $monto_total + ($cantidad * $precio);
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
		if ($nro_facturax) {
			$nro_factura = $nro_facturax['nro_factura'];
		} else {
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
		$monto_decimal = isset($monto_textual[1]) ? $monto_textual[1] : '';
		$monto_literal = ucfirst(strtolower(trim($conversor->to_word($monto_numeral))));


		// Instancia la nota
		$nota = array(
			'fecha_egreso' 			=> date('Y-m-d'),
			'hora_egreso' 			=> date('H:i:s'),
			'fecha_habilitacion' 	=> date('Y-m-d H:i:s'),
			'tipo' 					=> 'Venta',
			'tipo_inicial' 			=> 'Venta',
			'distribuir' 			=> $distribuir,
			'provisionado' 			=> 'S',
			'descripcion' 			=> 'Venta de productos con nota de venta',
			'nro_nota' 				=> $nro_factura,
			'monto_total' 			=> $monto_total,
			'cliente_id' 			=> $id_cliente,
			'nro_registros' 		=> $nro_registros,
			'almacen_id' 			=> $almacen_id,
			'estado' 				=> 1,
			'observacion' 			=> $des_reserva,
			'empleado_id' 			=> $_user['persona_id'],
			'vendedor_id' 			=> $_POST['empleado'],
			'codigo_vendedor' 		=> $empleadox['id_cliente_grupo'],
			'plan_de_pagos' 		=> ($plan == 'no') ? 'no' : 'si',
			'descuento_porcentaje' 	=> $descuento_porc,
			'descuento_bs' 			=> $descuento_bs,
			'monto_total_descuento' => $monto_total,                            //$total_importe_descuento,
			'nro_movimiento'		=> 1, // + 1
			'tipo_pago' 			=> $tipo_pago,
			'nro_pago' 				=> $nro_pago,
			'descripcion_venta' 	=> $observacion,
			'preventa' 				=> "habilitado",
			'estadoe' 				=> 2,
		);
		// Guarda la informacion
		$egreso_id = $db->insert('inv_egresos', $nota);

		// Instancia la nota
		$nota = array(
			'nro_factura'	 			=> 0,
			'nro_autorizacion' 			=> '',
			'codigo_control' 			=> '',
			'fecha_limite' 				=> '0000-00-00',
			'nit_ci' 					=> $nit_ci,
			'nombre_cliente' 			=> strtoupper($nombre_cliente),
			'dosificacion_id' 			=> 0,
			'monto_giftcard'			=> 0,
			'codigo_sucursal'			=> 0,
			'punto_venta'				=> $punto_venta,
			'codigo_documento_sector'	=> 1, //compra venta
			'tipo_documento_identidad'	=> (int)$_POST['tipo_documento_identidad'], //CI,
			'codigo_metodo_pago'		=> (int)$_POST['codigo_metodo_pago'],
			'codigo_moneda'				=> 1,
			'complemento'				=> $_POST['complemento'],
			'numero_tarjeta'			=> $_POST['numero_tarjeta'],
			'tipo_cambio'				=> 1,
			'egreso_id' 				=> $egreso_id
		);


		// Guarda la informacion
		$factura_id = $db->insert('inv_egresos_facturas', $nota);


		// Recorre los productos
		foreach ($productos as $nro => $elemento) {
			// Forma el detalle
			$id_unidad = $db->query('SELECT id_unidad FROM inv_unidades WHERE unidad = "' . trim($unidad[$nro]) . '" LIMIT 1 ')->fetch_first();

			$cantidad = $cantidades[$nro] * ((cantidad_unidad($db, $productos[$nro], $id_unidad['id_unidad']) == 0) ? 1 : cantidad_unidad($db, $productos[$nro], $id_unidad['id_unidad']));

			$vencimientoxx = explode(': ', $vencimiento[$nro])[1];
			$venc_v = explode('/', $vencimientoxx);
			$vencimientoxx = $venc_v[2] . "-" . $venc_v[1] . "-" . $venc_v[0];

			/*****************************************/
			//ACTUALIZAR LOTE CANTIDAD
			/*****************************************/
			// Forma el detalle
			$loteX = explode(': ', $lote[$nro])[1];
			$loteX = trim($loteX);
			$vencX = $vencimientoxx;

			$ingresos = $db->query('SELECT *
                                    FROM inv_ingresos_detalles
                                    LEFT JOIN inv_ingresos i ON id_ingreso=ingreso_id
                                    WHERE lote="' . $loteX . '" AND producto_id="' . $productos[$nro] . '" AND i.almacen_id="' . $almacen_id . '"  
                                ')->fetch();

			$cantidad_descontar = $cantidades[$nro];

			foreach ($ingresos as $nro222 => $ingreso) {

				if ($ingreso['lote_cantidad'] < $cantidad_descontar) {
					$cantidad_egreso = $ingreso['lote_cantidad'];
					$cantidad_modificar = 0;
					$cantidad_descontar = $cantidad_descontar - $ingreso['lote_cantidad'];
				} else {
					$cantidad_egreso = $cantidad_descontar;
					$cantidad_modificar = $ingreso['lote_cantidad'] - $cantidad_descontar;
					$cantidad_descontar = 0;
				}

				$datos = array(
					'lote_cantidad' => $cantidad_modificar
				);
				$condicion = array('id_detalle' => $ingreso['id_detalle']);
				$db->where($condicion)->update('inv_ingresos_detalles', $datos);

				/******************************************************/
				if ($cantidad_egreso > 0) {
					$detalle = array(
						'cantidad' => $cantidad_egreso,
						'precio' => $precios[$nro],
						'unidad_id' => $id_unidad['id_unidad'],
						'descuento' => 0,
						'producto_id' => $productos[$nro],
						'egreso_id' => $egreso_id,
						'lote' => $loteX,
						'vencimiento' => $vencX,
						'ingresos_detalles_id' => $ingreso['id_detalle'],
					);
					$id_detalle = $db->insert('inv_egresos_detalles', $detalle);
				}
			}
			/************************************************/
		}

		$nro_recibo = 0;

		//echo 'hasta aqui';
		//exit;

		require_once dirname(__DIR__) . '/siat/siat.php';
		try {
			//## generar factura siat
			$egreso = siat_recepcion_factura($egreso_id, $nro_facturax);
			//echo json_encode($egreso);
			//exit;
		} catch (ExceptionInvalidInvoiceData $e) {
			if ($e->egreso) {
				//TODO: borrar/revertir egreso
				die($e->getMessage());
			}
		} catch (Exception $e) {
			die($e->getMessage());
		}

		// Instancia el objeto
		$respuesta = array(
			'egreso_id' 	=> $egreso_id,
			'nro_recibo'	=> $nro_recibo,
			'siat_url'		=> siat_factura_url((object)$egreso),
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
