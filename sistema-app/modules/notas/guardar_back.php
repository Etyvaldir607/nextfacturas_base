<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */
// echo json_encode($_POST); die();

if (is_ajax() && is_post()) {
	// Verifica la existencia de los datos enviados 
	if (isset($_POST['nit_ci']) && isset($_POST['nombre_cliente']) && isset($_POST['productos']) && isset($_POST['nombres']) && isset($_POST['cantidades']) && isset($_POST['precios']) && isset($_POST['descuentos']) && isset($_POST['nro_registros']) && isset($_POST['monto_total']) && isset($_POST['almacen_id'])) {
		// Importa la libreria para convertir el numero a letra
		require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';



		// {
		// 	"cliente":"",
		// 	// "id_cliente":"",
		// 	// "nit_ci":"12345687",
		// 	"direccion":"mi casa",
		// 	"telefono":"77545812",
		// 	"ubicacion":"no se",
		// 	"nombre_cliente":"alvin",
		// 	"productos":["10","18","9","17"],
		// 	"nombres":["SECUFEM PLUS LEVONORGESTREL 1.5 MG CAJA X 1 COMP.","SECUFEM PLUS LEVONORGESTREL 1.5 MG CAJA X 1 COMP.","FACTOR DERMICO","FACTOR DERMICO"],
		// 	"lote":["Lote: lt9","Lote: lt12","Lote: lt1","Lote: lt6"],
		// 	"vencimiento":["Venc: 2023-02-09","Venc: 2020-11-11","Venc: 2022-03-11","Venc: 2020-11-11"],
		// 	"cantidades":["500","500","500","500"],
		//  "unidad":["CAJA","CAJA","UNIDAD","UNIDAD"],
		// 	"precios":["30.00  ","30.00  ","27.00","27.00"],
		// 	"asignaciones":["","","",""],
		// 	"descuentos":["0","0","0","0"],
		// 	"almacen_id":"1",
		// 	"nro_registros":"4",
		// 	"monto_total":"57000.00",
		// 	"tipo":"0",
		// 	"descuento_porc":"0",
		// 	"descuento_bs":"0",
		// 	"total_importe_descuento":"57000.00",
		// 	"forma_pago":"1",
		// 	"nro_cuentas":"1",
		// 	"fecha":[""],
		// 	"cuota":["0","0","0","0","0","0","0","0"],
		// 	"des_reserva":""
		// }


		// Obtiene los datos de la nota
		$nit_ci 		= trim($_POST['nit_ci']);
		$nombre_cliente	= trim($_POST['nombre_cliente']);
		$id_cliente 	= trim($_POST['id_cliente']);
		$productos 		= (isset($_POST['productos'])) ? $_POST['productos'] : array();
		$nombres 		= (isset($_POST['nombres'])) ? $_POST['nombres'] : array();
		$cantidades		= (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
		$unidad 		= (isset($_POST['unidad'])) ? $_POST['unidad'] : array();
		$precios 		= (isset($_POST['precios'])) ? $_POST['precios'] : array();
		$descuentos 	= (isset($_POST['descuentos'])) ? $_POST['descuentos'] : array();
		$nro_registros 	= trim($_POST['nro_registros']);
		$monto_total 	= trim($_POST['monto_total']);
		$des_reserva 	= trim($_POST['des_reserva']);
		$reserva 		= isset($_POST['reserva']);
		$almacen_id 	= trim($_POST['almacen_id']);
		$lote			= (isset($_POST['lote'])) ? $_POST['lote'] : array();
		$vencimiento	= (isset($_POST['vencimiento'])) ? $_POST['vencimiento'] : array();

		//Cuentas
		$tipo_pago = (isset($_POST['tipo_pago'])) ? trim($_POST['tipo_pago']) : 'Efectivo';
		// $nro_factura = trim($_POST['nro_factura']);
		// $nro_autorizacion = trim($_POST['nro_autorizacion']);


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
		$nro_factura = $db->query("select count(id_egreso) + 1 as nro_factura from inv_egresos where tipo = 'Venta' and provisionado = 'S'")->fetch_first();
		$nro_factura = $nro_factura['nro_factura'];

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

		// obtiene a el cliente
        $cliente = $db->select('*')->from('inv_clientes')->where(array('cliente' => $nombre_cliente, 'nit' => $nit_ci))->fetch_first();
        if(!$cliente){
            $cl = array(
                'cliente' => $nombre_cliente,
				'nit' => $nit_ci,
				'direccion' =>  $_POST['direccion'],
				'telefono' =>  $_POST['telefono'],
				'ubicacion' =>  $_POST['ubicacion'],
            );
            $db->insert('inv_clientes',$cl);
        }




		// Instancia la nota
		$nota = array(
			'fecha_egreso' => date('Y-m-d'),
			'hora_egreso' => date('H:i:s'),
			'tipo' => 'Venta',
			'provisionado' => 'S',
			'descripcion' => 'Venta de productos con nota de remisión',
			'nro_factura' => $nro_factura,
			'nro_autorizacion' => '',
			'codigo_control' => '',
			'fecha_limite' => '0000-00-00',
			'monto_total' => $monto_total,
			'nit_ci' => $nit_ci,
			'nombre_cliente' => strtoupper($nombre_cliente),
			'nro_registros' => $nro_registros,
			'dosificacion_id' => 0,
			'almacen_id' => $almacen_id,
			'cobrar' => $reserva,
			'estado' => 1,
			'observacion' => $des_reserva,
			'empleado_id' => $_user['persona_id'],
			'plan_de_pagos' => $plan,
			'descuento_porcentaje' => $descuento_porc,
			'descuento_bs' => $descuento_bs,
			'monto_total_descuento' => $total_importe_descuento,
		);

		// Guarda la informacion
		$egreso_id = $db->insert('inv_egresos', $nota);
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
			$aux = $db->select('*')->from('inv_productos')->where('id_producto', $productos[$nro])->fetch_first();

			if ($aux['promocion'] == 'si') {
				// Forma el detalle
				$prod = $productos[$nro];
				$promos = $db->select('producto_id, precio, unidad_id, cantidad, descuento , id_promocion as egreso_id, cantidad as promocion_id')->from('inv_promociones')->where('id_promocion', $prod)->fetch();

				/////////////////////////////////////////////////////////////////////////////////////////
				$Lote = '';
				$CantidadAux = $cantidades[$nro];
				$Detalles = $db->query("SELECT id_detalle,cantidad,lote,lote_cantidad FROM inv_ingresos_detalles WHERE producto_id='$productos[$nro]' AND lote_cantidad>0 ORDER BY id_detalle ASC LIMIT 3")->fetch();
				foreach ($Detalles as $Fila => $Detalle) :
					if ($CantidadAux >= $Detalle['lote_cantidad']) :
						$Datos = [
							'lote_cantidad' => 0,
						];
						$Cant = $Detalle['lote_cantidad'];
					elseif ($CantidadAux > 0) :
						$Datos = [
							'lote_cantidad' => $Detalle['lote_cantidad'] - $CantidadAux,
						];
						$Cant = $CantidadAux;
					else :
						break;
					endif;
					$Condicion = [
						'id_detalle' => $Detalle['id_detalle'],
					];
					$db->where($Condicion)->update('inv_ingresos_detalles', $Datos);
					$CantidadAux = $CantidadAux - $Detalle['lote_cantidad'];
					$Lote .= $Detalle['lote'] . '-' . $Cant . ',';
				endforeach;
				$Lote = trim($Lote, ',');
				/////////////////////////////////////////////////////////////////////////////////////////
				$detalle = array(
					'cantidad' => $cantidades[$nro],
					'precio' => $precios[$nro],
					'descuento' => 0,
					'unidad_id' => 11,
					'producto_id' => $productos[$nro],
					'egreso_id' => $proforma_id,
					'promocion_id' => 1,
					'lote' => explode(': ',$lote[$nro])[1], // josema::modeificado
					'vencimiento' => explode(': ',$vencimiento[$nro])[1], // josema::modeificado
				);
				// Guarda la informacion
				$id = $db->insert('inv_egresos_detalles', $detalle);

				// Guarda en el historial
				$data = array(
					'fecha_proceso' => date("Y-m-d"),
					'hora_proceso' => date("H:i:s"),
					'proceso' => 'c',
					'nivel' => 'l',
					'direccion' => '?/notas/guardar',
					'detalle' => 'Se inserto el inventario egreso detalle con identificador numero ' . $id,
					'usuario_id' => $_SESSION[user]['id_user']
				);
				$db->insert('sys_procesos', $data);

				foreach ($promos as $key => $promo) {
					/////////////////////////////////////////////////////////////////////////////////////////
					$Lote = '';
					$CantidadAux = $promo['cantidad'] * $cantidades[$nro];
					$Detalles = $db->query("SELECT id_detalle,cantidad,lote,lote_cantidad FROM inv_ingresos_detalles WHERE producto_id='$productos[$nro]' AND lote_cantidad>0 ORDER BY id_detalle ASC LIMIT 3")->fetch();
					foreach ($Detalles as $Fila => $Detalle) :
						if ($CantidadAux >= $Detalle['lote_cantidad']) :
							$Datos = [
								'lote_cantidad' => 0,
							];
							$Cant = $Detalle['lote_cantidad'];
						elseif ($CantidadAux > 0) :
							$Datos = [
								'lote_cantidad' => $Detalle['lote_cantidad'] - $CantidadAux,
							];
							$Cant = $CantidadAux;
						else :
							break;
						endif;
						$Condicion = [
							'id_detalle' => $Detalle['id_detalle'],
						];
						$db->where($Condicion)->update('inv_ingresos_detalles', $Datos);
						$CantidadAux = $CantidadAux - $Detalle['lote_cantidad'];
						$Lote .= $Detalle['lote'] . '-' . $Cant . ',';
					endforeach;
					$Lote = trim($Lote, ',');
					/////////////////////////////////////////////////////////////////////////////////////////
					// $promo['lote'] = $lote[$nro]; // josema::modificado
					$promo['lote'] = explode(': ',$lote[$nro])[1]; // josema::modeificado
					$promo['vencimiento'] = explode(': ',$vencimiento[$nro])[1]; // josema::modificado'vencimiento' => explode(': ',$vencimiento[$nro])[1], // josema::modeificado
					$promo['egreso_id'] = $proforma_id;
					$promo['promocion_id'] = $productos[$nro];
					$promos[$key]['cantidad'] = $promo['cantidad'] * $cantidades[$nro];
					// Guarda la informacion
					$db->insert('inv_egresos_detalles', $promo);
					// Guarda en el historial
					$data = array(
						'fecha_proceso' => date("Y-m-d"),
						'hora_proceso' => date("H:i:s"),
						'proceso' => 'c',
						'nivel' => 'l',
						'direccion' => '?/notas/guardar',
						'detalle' => 'Se inserto el inventario egreso detalle con identificador numero ' . $proforma_id,
						'usuario_id' => $_SESSION[user]['id_user']
					);
					$db->insert('sys_procesos', $data);
				}
			} else {
				$id_unidad = $db->select('id_unidad')->from('inv_unidades')->where('unidad', $unidad[$nro])->fetch_first();
				$cantidad = $cantidades[$nro] * ((cantidad_unidad($db, $productos[$nro], $id_unidad['id_unidad']) == 0) ? 1 : cantidad_unidad($db, $productos[$nro], $id_unidad['id_unidad']));
				/////////////////////////////////////////////////////////////////////////////////////////
				// $Lote = '';
				// $CantidadAux = $cantidad;
				// $Detalles = $db->query("SELECT id_detalle,cantidad,lote,lote_cantidad FROM inv_ingresos_detalles WHERE producto_id='$productos[$nro]' AND lote_cantidad>0 ORDER BY id_detalle ASC LIMIT 3")->fetch();
				// foreach ($Detalles as $Fila => $Detalle) :
				// 	if ($CantidadAux >= $Detalle['lote_cantidad']) :
				// 		$Datos = [
				// 			'lote_cantidad' => 0,
				// 		];
				// 		$Cant = $Detalle['lote_cantidad'];
				// 	elseif ($CantidadAux > 0) :
				// 		$Datos = [
				// 			'lote_cantidad' => $Detalle['lote_cantidad'] - $CantidadAux,
				// 		];
				// 		$Cant = $CantidadAux;
				// 	else :
				// 		break;
				// 	endif;
				// 	$Condicion = [
				// 		'id_detalle' => $Detalle['id_detalle'],
				// 	];
				// 	$db->where($Condicion)->update('inv_ingresos_detalles', $Datos);
				// 	$CantidadAux = $CantidadAux - $Detalle['lote_cantidad'];
				// 	$Lote .= $Detalle['lote'] . '-' . $Cant . ',';
				// endforeach;
				// $Lote = trim($Lote, ',');
				/////////////////////////////////////////////////////////////////////////////////////////

				// echo $cantidad ;

				$detalle = array(
					'cantidad' => $cantidad,
					'precio' => $precios[$nro],
					'unidad_id' => $id_unidad['id_unidad'],
					'descuento' => $descuentos[$nro],
					'producto_id' => $productos[$nro],
					'egreso_id' => $egreso_id,
					// 'lote' => $lote[$nro],
					'lote' => explode(': ',$lote[$nro])[1], // josema::modeificado
					// 'vencimiento' => $vencimiento[$nro],
					'vencimiento' => explode(': ',$vencimiento[$nro])[1], // josema::modeificado
				);
			}

			// Genera los subtotales
			// echo $precios[$nro] * $cantidades[$nro];
			// $subtotales[$nro] = number_format($precios[$nro] * $cantidades[$nro], 2, '.', '');

			// Guarda la informacion
			$id = $db->insert('inv_egresos_detalles', $detalle);
			// Guarda en el historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"),
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/notas/guardar',
				'detalle' => 'Se inserto el inventario egreso detalle con identificador numero ' . $id,
				'usuario_id' => $_SESSION[user]['id_user']
			);
			$db->insert('sys_procesos', $data);
		}

		// Instancia la respuesta
		/*$respuesta = array(
			'papel_ancho' => 10,
			'papel_alto' => 30,
			'papel_limite' => 576,
			'empresa_nombre' => $_institution['nombre'],
			'empresa_sucursal' => 'SUCURSAL Nº 1',
			'empresa_direccion' => $_institution['direccion'],
			'empresa_telefono' => 'TELÉFONO ' . $_institution['telefono'],
			'empresa_ciudad' => 'LA PAZ - BOLIVIA',
			'empresa_actividad' => $_institution['razon_social'],
			'empresa_nit' => $_institution['nit'],
			'nota_titulo' => 'N O T A   D E   R E M I S I Ó N',
			'nota_numero' => $nota['nro_factura'],
			'nota_fecha' => date_decode($nota['fecha_egreso'], 'd/m/Y'),
			'nota_hora' => substr($nota['hora_egreso'], 0, 5),
			'cliente_nit' => $nota['nit_ci'],
			'cliente_nombre' => $nota['nombre_cliente'],
			'venta_titulos' => array('CANTIDAD', 'DETALLE', 'P. UNIT.', 'SUBTOTAL', 'TOTAL'),
			'venta_cantidades' => $cantidades,
			'venta_detalles' => $nombres,
			'venta_precios' => $precios,
			'venta_subtotales' => $subtotales,
			'venta_total_numeral' => $nota['monto_total'],
			'venta_total_literal' => $monto_literal,
			'venta_total_decimal' => $monto_decimal . '/100',
			'venta_moneda' => $moneda,
			'impresora' => $_terminal['impresora']
		);*/


		//Cuentas
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
					$fecha_format = "00-00-0000";
				}

				$vfecha = explode("-", $fecha_format);
				//var_dump($vfecha);

				if (count($vfecha) == 3) {
					$fecha_format = $vfecha[2] . "-" . $vfecha[1] . "-" . $vfecha[0];
				} else {
					$fecha_format = "0000-00-00";
				}

				$nro_cuota++;

				if ($nro2 == 0) {
					$detallePlan = array(
						'nro_cuota' => $nro_cuota,
						'pago_id' => $ingreso_id_plan,
						'fecha' => $fecha_format,
						'fecha_pago' => $fecha_format,
						'monto' => (isset($cuotas[$nro2])) ? $cuotas[$nro2] : 0,
						'tipo_pago' => $tipo_pago,
						'empleado_id' => $_user['persona_id'],
						'estado'  => '1'
					);
				} else {
					$detallePlan = array(
						'nro_cuota' => $nro_cuota,
						'pago_id' => $ingreso_id_plan,
						'fecha' => $fecha_format,
						'fecha_pago' => $fecha_format,
						'monto' => (isset($cuotas[$nro2])) ? $cuotas[$nro2] : 0,
						'tipo_pago' => '',
						'empleado_id' => $_user['persona_id'],
						'estado'  => '0'
					);
				}
				// Guarda la informacion
				$db->insert('inv_pagos_detalles', $detallePlan);
			}
		}

		// Envia respuesta
		echo json_encode($egreso_id);
	} else {
		// Envia respuesta
		echo 'error';
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}
