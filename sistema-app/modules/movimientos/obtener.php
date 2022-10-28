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
	if (isset($_POST['fecha'])) {
		// Importa la libreria para el codigo de control
		require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

		// Obtiene la fecha
		$fecha = trim($_POST['fecha']);

		// Obtiene los ingresos
		$ingresos = $db->select("m.*, concat(e.nombres, ' ', e.paterno, ' ', e.materno) as empleado")->from('caj_movimientos m')->join('sys_empleados e', 'm.empleado_id = e.id_empleado', 'left')->where('m.tipo', 'i')->where('m.empleado_id', $_user['persona_id'])->where('m.fecha_movimiento', $fecha)->order_by('m.fecha_movimiento desc, m.hora_movimiento desc')->fetch();

		// Obtiene los egresos
		$egresos = $db->select("m.*, concat(e.nombres, ' ', e.paterno, ' ', e.materno) as empleado")->from('caj_movimientos m')->join('sys_empleados e', 'm.empleado_id = e.id_empleado', 'left')->where('m.tipo', 'e')->where('m.empleado_id', $_user['persona_id'])->where('m.fecha_movimiento', $fecha)->order_by('m.fecha_movimiento desc, m.hora_movimiento desc')->fetch();

		// Obtiene los gastos
		$gastos = $db->select("m.*, concat(e.nombres, ' ', e.paterno, ' ', e.materno) as empleado")->from('caj_movimientos m')->join('sys_empleados e', 'm.empleado_id = e.id_empleado', 'left')->where('m.tipo', 'g')->where('m.empleado_id', $_user['persona_id'])->where('m.fecha_movimiento', $fecha)->order_by('m.fecha_movimiento desc, m.hora_movimiento desc')->fetch();

		// Obtiene las ventas
		$ventas = $db->query("select v.*, sum(v.cantidad) as numero, sum(v.importe) as subtotal from (select v.id_detalle, v.precio, v.cantidad, v.producto_id, p.nombre, p.codigo, (v.cantidad * v.precio) as importe from (select d.* from inv_egresos_detalles d left join inv_egresos e on d.egreso_id = e.id_egreso where e.tipo = 'venta' and e.fecha_egreso = '$fecha' and e.empleado_id = '" . $_user['persona_id'] . "') v left join inv_productos p on v.producto_id = p.id_producto) v group by v.producto_id order by v.codigo")->fetch();

		// Obtiene la suma de los ingresos
		$total_ingreso = 0;
		foreach ($ingresos as $nro => $ingreso) {
			$total_ingreso = $total_ingreso + $ingreso['monto'];
		}

		// Obtiene la suma de los egresos
		$total_egreso = 0;
		foreach ($egresos as $nro => $egreso) {
			$total_egreso = $total_egreso + $egreso['monto'];
		}

		// Obtiene la suma de los gastos
		$total_gasto = 0;
		foreach ($gastos as $nro => $gasto) {
			$total_gasto = $total_gasto + $gasto['monto'];
		}

		// Obtiene la suma de las ventas
		$total_venta = 0;
		foreach ($ventas as $nro => $venta) {
			$total_venta = $total_venta + $venta['subtotal'];
		}

		// Obtiene el total
		$total = $total_ingreso - $total_egreso - $total_gasto + $total_venta;

		// Obtiene los datos del monto total
		$conversor = new NumberToLetterConverter();
		$monto_textual = explode('.', $total);
		$monto_numeral = $monto_textual[0];
		$monto_decimal = ($monto_textual[1] < 10) ? $monto_textual[1] . '0' : $monto_textual[1];
		$monto_literal = ucfirst(strtolower(trim($conversor->to_word($monto_numeral))));

		// Instancia la respuesta
		$respuesta = array(
			'empresa_nombre' => 'GIMNASIO AQUÍ ME QUEDO',
			'empresa_sucursal' => 'SUCURSAL Nº 1',
			'diario_fecha' => date_decode(date('Y-m-d'), 'd/m/Y'),
			'diario_hora' => substr(date('H:i:s'), 0, 5),
			'diario_titulo' => 'C I E R R E   D E   C A J A',
			'impresora' => 'EPSON TM-T88V Receipt',
			'venta_cantidades' => ['(+)', '(-)', '(-)', '(+)'],
			'venta_detalles' => ['TOTAL INGRESOS', 'TOTAL EGRESOS', 'TOTAL GASTOS', 'TOTAL VENTAS'],
			'venta_moneda' => 'Bolivianos',
			'venta_precios' => ['', '', '', ''],
			'venta_subtotales' => [number_format($total_ingreso, 2, '.', ''), number_format($total_egreso, 2, '.', ''), number_format($total_gasto, 2, '.', ''), number_format($total_venta, 2, '.', '')],
			'venta_titulos' => ['OPERACI.', 'DETALLE', '', 'SUBTOTAL', 'TOTAL'],
			'venta_total_decimal' => $monto_decimal . '/100',
			'venta_total_literal' => $monto_literal,
			'venta_total_numeral' => number_format($total, 2, '.', '')
		);

		echo json_encode($respuesta);
	} else {
		// Error 404
		require_once not_found();
		exit;
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>