<?php 

// Obtiene el id_egreso
$id_egreso = (isset($params[0])) ? $params[0] : 0;


if ($id_egreso == 0) {
	// Obtiene las egresos
	$egresos = $db->select('p.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno')
					->from('inv_egresos p')
					->join('inv_almacenes a', 'p.almacen_id = a.id_almacen', 'left')
					->join('sys_empleados e', 'p.empleado_id = e.id_empleado', 'left')
					->where('p.empleado_id', $_user['persona_id'])
					->order_by('p.fecha_egreso desc, p.hora_egreso desc')
					->fetch();
} else {
	// Obtiene los permisos
	$permisos = explode(',', permits);
	
	// Almacena los permisos en variables
	$permiso_ver = in_array('ver', $permisos);
	
	// Obtiene la egreso
	$egreso = $db->select('p.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno')
				  ->from('inv_egresos p')
				  ->join('inv_almacenes a', 'p.almacen_id = a.id_almacen', 'left')
				  ->join('sys_empleados e', 'p.empleado_id = e.id_empleado', 'left')
				  ->where('id_egreso', $id_egreso)
				  ->fetch_first();
	
	// Verifica si existe el egreso
	if (!$egreso ) { // || $egreso['empleado_id'] != $_user['persona_id']
		// Error 404
		require_once not_found();
		exit;
	} elseif (!$permiso_ver) {
		// Error 401
		require_once bad_request();
		exit;
	}

	// Obtiene los detalles
	$detalles = $db->select('d.*,d.unidad_id as unidad_otra, p.codigo, p.nombre, p.nombre_factura')
				   ->from('inv_egresos_detalles d')
				   ->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')
				   ->where('d.egreso_id', $id_egreso)
				   ->where('p.promocion !=', 'si')
				   ->order_by('id_detalle asc')
				   ->fetch();
}

// Obtiene las deudas

$id_cli= $db->select('cliente_id')->from('inv_egresos')->where('id_egreso=',$id_egreso)->fetch_first();
$id_cliente = $id_cli['cliente_id'];

$deudas = $db->select('sum(monto) as monto_parcial,e.id_egreso')
			->from('inv_pagos p')
			->join('inv_pagos_detalles pd', 'pd.pago_id=p.id_pago', 'left')
			->join('inv_egresos e', 'e.id_egreso=p.movimiento_id', 'left')
			->where('p.movimiento_id != ', $id_egreso)
			->where('p.tipo', "Egreso")
			->where('pd.estado != ', '1')
			->where('e.cliente_id=',$id_cliente)
			->where('((e.preventa IS NULL AND e.tipo!="preventa") OR e.preventa="habilitado")')
			->group_by('e.id_egreso')
			->fetch();
$deuda_pendiente='';
$cont=1;

foreach ($deudas as $deuda) {
	$deuda_pendiente .= 'D'.$cont.': '.number_format($deuda['monto_parcial'],2,',','.').' <br> ';
	$cont++;
}

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Importa la libreria para el generado del pdf
require_once libraries . '/tcpdf/tcpdf.php';

// Define variables globales
define('DIRECCION', escape($_institution['pie_pagina']));
define('IMAGEN', escape($_institution['imagen_encabezado']));
define('ATENCION', 'Lun. a Vie. de 08:30 a 18:30 y Sáb. de 08:30 a 13:00');
define('PIE', escape($_institution['pie_pagina']));
define('TELEFONO', escape(str_replace(',', ', ', $_institution['telefono'])));
//define('TELEFONO', date(escape($_institution['formato'])) . ' ' . date('H:i:s'));

// Extiende la clase TCPDF para crear Header y Footer
class MYPDF extends TCPDF {}

// Instancia el documento PDF
$pdf = new MYPDF('p', 'pt', PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Asigna la informacion al documento
$pdf->SetCreator(name_autor);
$pdf->SetAuthor(name_autor);
$pdf->SetTitle($_institution['nombre']);
$pdf->SetSubject($_institution['propietario']);
$pdf->SetKeywords($_institution['sigla']);

// Asignamos margenes
$pdf->SetMargins(30, 30, 30);

// Elimina las cabeceras
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// ------------------------------------------------------------

if ($id_egreso == 0) {

} else {
	// Documento individual --------------------------------------------------

	// Asigna la orientacion de la pagina
	$pdf->SetPageOrientation('P');

	// Adiciona la pagina
	$pdf->AddPage();

	// Establece la fuente del titulo
	$pdf->SetFont(PDF_FONT_NAME_MAIN, 'B', 16);

	// Titulo del documento
	// $pdf->Cell(0, 10, 'NOTA DE PREVENTA', 0, true, 'C', false, '', 0, false, 'T', 'M');

	// Salto de linea
	$pdf->Ln(5);

	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', 9);

	// Define las variables
	$valor_fecha = escape(date_decode($egreso['fecha_egreso'], $_institution['formato']) . ' ' . $egreso['hora_egreso']);
	$valor_nombre_cliente = escape($egreso['nombre_cliente']);
	$valor_nit_ci = escape($egreso['nit_ci']);
	$valor_nro_egreso = escape($egreso['nro_egreso']);
	$valor_monto_total = escape($egreso['monto_total']);
	$valor_nro_registros = escape($egreso['nro_factura']);
	$valor_almacen = escape($egreso['almacen']);
	$valor_empleado = escape($egreso['nombres'] . ' ' . $egreso['paterno'] . ' ' . $egreso['materno']);
	$valor_descuento_global = escape($egreso['descuento_bs']);
	$valor_moneda = $moneda;
	$total = 0;

	$valor_logo = (imagen != '') ? institucion . '/' . IMAGEN : imgs . '/empty.jpg';
	$valor_empresa = $_institution['nombre'];
	$valor_direccion = $_institution['direccion'];
	$valor_telefono = $_institution['telefono'];
	$valor_pie = $_institution['pie_pagina'];
	$valor_razon = $_institution['razon_social'];

	$valor_nit_empresa = $_institution['nit'];
	$valor_autorizacion = $egreso['nro_autorizacion'];
	$valor_codigo = $egreso['codigo_control'];
	$valor_numero = $egreso['nro_factura'];
	$valor_limite = date_decode($egreso['fecha_limite'], $_institution['formato']);



	if($egreso['descuento_porcentaje']!=0){
	$valor_descuento_porcentaje  = utf8_decode($egreso['descuento_porcentaje'].'.00 %');
	}else{
		$valor_descuento_porcentaje  ='';
	}

	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', 8);

	// Estructura la tabla
	$body = '';
	foreach ($detalles as $nro => $detalle) {
		$cantidad = escape($detalle['cantidad']/cantidad_unidad($db,$detalle['producto_id'],$detalle['unidad_otra']));
		$precio = escape($detalle['precio']);
		$descuento = escape($detalle['descuento']);
		$importe = $cantidad * $precio;
		$total = $total + $importe;

		$body .= '<tr>';
		$body .= '<td class="left-right">' . ($nro + 1) . '</td>';
		$body .= '<td class="left-right">' . escape($detalle['codigo']) . '</td>';
		$body .= '<td class="left-right">' . escape($detalle['nombre_factura']) .' <br>(LOTE: '. escape($detalle['lote']) .' VENC: '. escape(date_decode($detalle['vencimiento'], $_institution['formato'])) .')' . '</td>';
		$body .= '<td class="left-right" align="right">' . $cantidad . ' '.nombre_unidad($db,$detalle['unidad_otra']). '</td>';
		$body .= '<td class="left-right" align="right">' . number_format($precio, 2, ',', '.') . '</td>';
		$body .= '<td class="left-right" align="right">' . number_format($descuento, 2, ',', '.') . '</td>';
		$body .= '<td class="left-right" align="right">' . number_format($importe, 2, ',', '.') . '</td>';
		$body .= '</tr>';
	}
	
	//$valor_total = number_format($total, 2, '.', '');

	$valor_total = number_format($total, 2, '.', '');
	$total_con_descuento=$valor_total-$valor_descuento_global;
	$valor_total_con_descuento = $total_con_descuento;
	$body = ($body == '') ? '<tr><td colspan="7" align="center" class="all">Este egreso no tiene detalle, es muy importante que todos las egresos cuenten con un detalle de venta.</td></tr>' : $body;
	
	
	
	$valor_totalx=number_format($valor_total, 2, ',', '.');
	$valor_descuento_porcentajex=number_format($valor_descuento_porcentaje, 2, ',', '.');
	$valor_descuento_globalx=number_format($valor_descuento_global, 2, ',', '.');
	$valor_total_con_descuentox=number_format($valor_total_con_descuento, 2, ',', '.');
	
	
	
	// Formateamos la tabla
	$tabla = <<<EOD
	<style>
	th {
		background-color: #eee;
		font-weight: bold;
	}
	.left-right {
		border-left: 1px solid #f29f5e;
		border-right: 1px solid #f29f5e;
	}
	.none {
		border: 1px solid #fff;
	}
	.all {
		border: 1px solid #f29f5e;
		background-color: #f5af76;
	}
	</style>
	<table cellpadding="1">
		<tr>
			<td width="20%" class="none" align="left" rowspan="4">
				<img src="$valor_logo" width="100">
			</td>
			<td width="30%" class="none" align="left">$valor_empresa</td>
			<td width="35%" class="none" align="right"><b>OPERADOR:</b></td>
			<td width="15%" class="none" align="right">$valor_empleado</td>
		</tr>
		<tr>
			<td class="none" align="left">$valor_direccion</td>
			<td class="none" align="right"><b>ALMACÉN:</b></td>
			<td class="none" align="right">$valor_almacen</td>
		</tr>
		<tr>
			<td class="none" align="left">Teléfono: $valor_telefono</td>
			<td class="none" align="right"><b>NÚMERO DE NOTA:</b></td>
			<td class="none" align="right">$valor_nro_registros</td>
		</tr>
		<tr>
			<td class="none" align="left">$valor_pie</td>
			<td class="none" align="right" colspan="2"></td>
		</tr>
	</table>
	<h1 align="center">NOTA DE PREVENTA</h1>
	<br><br>
	<table cellpadding="5">
		<tr>
			<th width="5%" class="all" align="center">#</th>
			<th width="12%" class="all" align="center">CÓDIGO</th>
			<th width="35%" class="all" align="center">NOMBRE</th>
			<th width="12%" class="all" align="center">CANTIDAD</th>
			<th width="12%" class="all" align="center">PRECIO $valor_moneda</th>
			<th width="12%" class="all" align="center">DESCUENTO (%)</th>
			<th width="12%" class="all" align="center">IMPORTE $valor_moneda</th>
		</tr>
		$body
		<!--<tr>
			<th class="all" align="left" colspan="6">SUBTOTAL: </th>
			<th class="all" align="right">$valor_totalx</th>
		</tr>
		<tr>
			<th class="all" align="left" colspan="6">DESCUENTO: </th>
			<th class="all" align="right">$valor_descuento_porcentajex            $valor_descuento_globalx</th>
		</tr>-->
		<tr>
			<th class="all" align="left" colspan="6">IMPORTE TOTAL $valor_moneda</th>
			<th class="all" align="right">$valor_total_con_descuentox</th>
		</tr>
		<!--<tr>
			<th class="all" align="left" colspan="6">Deuda Pendiente(s) $valor_moneda</th>
			<th class="all" align="right">$deuda_pendiente</th>
		</tr>-->
	</table>
EOD;
	
	// Imprime la tabla
	$pdf->writeHTML($tabla, true, false, false, false, '');
	
	// Genera el nombre del archivo
	$nombre = 'nota_venta_' . $id_egreso . '_' . date('Y-m-d_H-i-s') . '.pdf';
}

// ------------------------------------------------------------

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>
