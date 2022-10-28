<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica si es una peticion ajax y post
if (is_ajax() && is_post()) {
	// Verifica la existencia de los datos enviados 
	if (isset($_POST['nit_ci']) && isset($_POST['nombre_cliente']) && isset($_POST['productos']) && isset($_POST['nombres']) && isset($_POST['cantidades']) && isset($_POST['precios']) && isset($_POST['descuentos']) && isset($_POST['nro_registros']) && isset($_POST['monto_total']) && isset($_POST['almacen_id'])) {
		// Importa la libreria para convertir el numero a letra
		require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

		// Obtiene los datos de la nota
		$nit_ci = trim($_POST['nit_ci']);
        $nombre_cliente = trim($_POST['nombre_cliente']);
        $id_cliente = trim($_POST['id_cliente']);
		$productos = (isset($_POST['productos'])) ? $_POST['productos'] : array();
		$nombres = (isset($_POST['nombres'])) ? $_POST['nombres'] : array();
		$cantidades = (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
        $unidad = (isset($_POST['unidad'])) ? $_POST['unidad']: array();
		$precios = (isset($_POST['precios'])) ? $_POST['precios'] : array();
		$descuentos = (isset($_POST['descuentos'])) ? $_POST['descuentos'] : array();
		$nro_registros = trim($_POST['nro_registros']);
        $monto_total = trim($_POST['monto_total']);
        $des_reserva = trim($_POST['des_reserva']);
        $reserva = trim($_POST['reserva']);
		$almacen_id = trim($_POST['almacen_id']);

        if($_POST['reserva']){
            $reserva = 'si';
        }else{
            $reserva = 'no';
        }

		//descuento
		$descuento_porcentaje = trim($_POST['descuento_porcentaje']);
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
			'descuento_porcentaje' => $descuento_porcentaje,
			'descuento_bs' => $descuento_bs,
			'monto_total_descuento' => $total_importe_descuento,
			'nit_ci' => $nit_ci,
			'nombre_cliente' => strtoupper($nombre_cliente),
			'nro_registros' => $nro_registros,
			'dosificacion_id' => 0,
			'almacen_id' => $almacen_id,
            'cobrar' => $reserva,
            'observacion' => $des_reserva,
			'empleado_id' => $_user['persona_id']
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
			'detalle' => 'Se inserto el inventario egreso con identificador numero ' . $egreso_id ,
			'usuario_id' => $_SESSION[user]['id_user']			
		);			
		$db->insert('sys_procesos', $data) ;
		

		// Recorre los productos
		foreach ($productos as $nro => $elemento) {
			// Forma el detalle
            $aux = $db->select('*')->from('inv_productos')->where('id_producto',$productos[$nro])->fetch_first();

                if($aux['promocion'] == 'si'){
                    // Forma el detalle
                    $prod = $productos[$nro];
                    $promos = $db->select('producto_id, precio, unidad_id, cantidad, descuento , id_promocion as egreso_id, cantidad as promocion_id')->from('inv_promociones')->where('id_promocion', $prod)->fetch();
                    $detalle = array(
                        'cantidad' => $cantidades[$nro],
                        'precio' => $precios[$nro],
                        'descuento' => 0,
                        'unidad_id' => 11,
                        'producto_id' => $productos[$nro],
                        'egreso_id' => $proforma_id,
                        'promocion_id' => 1
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
            			'detalle' => 'Se inserto el inventario egreso detalle con identificador numero ' . $id ,
            			'usuario_id' => $_SESSION[user]['id_user']			
            		);			
            		$db->insert('sys_procesos', $data) ;
                    
                    foreach ($promos as $key => $promo) {
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
                			'detalle' => 'Se inserto el inventario egreso detalle con identificador numero ' . $proforma_id ,
                			'usuario_id' => $_SESSION[user]['id_user']			
                		);			
                		$db->insert('sys_procesos', $data) ;
                    }
                }else{
                    $id_unidad = $db->select('id_unidad')->from('inv_unidades')->where('unidad',$unidad[$nro])->fetch_first();
                    $cantidad = $cantidades[$nro] * cantidad_unidad($db, $productos[$nro], $id_unidad['id_unidad']);
                    $detalle = array(
                        'cantidad' => $cantidad,
                        'precio' => $precios[$nro],
                        'unidad_id' => $id_unidad['id_unidad'],
                        'descuento' => $descuentos[$nro],
                        'producto_id' => $productos[$nro],
                        'egreso_id' => $egreso_id
                    );
                }

			// Genera los subtotales
			$subtotales[$nro] = number_format($precios[$nro] * $cantidades[$nro], 2, '.', '');

			// Guarda la informacion
			$id = $db->insert('inv_egresos_detalles', $detalle);
    		// Guarda en el historial
    		$data = array(
    			'fecha_proceso' => date("Y-m-d"),
    			'hora_proceso' => date("H:i:s"), 
    			'proceso' => 'c',
    			'nivel' => 'l',
    			'direccion' => '?/notas/guardar',
    			'detalle' => 'Se inserto el inventario egreso detalle con identificador numero ' . $id ,
    			'usuario_id' => $_SESSION[user]['id_user']			
    		);			
    		$db->insert('sys_procesos', $data) ;
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

?>