<?php

// Obtiene el id_proforma
$id_proforma = (isset($params[0])) ? $params[0] : 0;

if ($id_proforma == 0) {
	// Obtiene las proformas
	$proformas = $db->select('p.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno')
					->from('inv_proformas p')
					->join('inv_almacenes a', 'p.almacen_id = a.id_almacen', 'left')
					->join('sys_empleados e', 'p.empleado_id = e.id_empleado', 'left')
					->where('p.empleado_id', $_user['persona_id'])
					->order_by('p.fecha_proforma desc, p.hora_proforma desc')
					->fetch();
} else {
	// Obtiene los permisos
	$permisos = explode(',', permits);
	
	// Almacena los permisos en variables
	$permiso_ver = in_array('ver', $permisos);
	
	// Obtiene la proforma
	$proforma = $db->select('p.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno')
				  ->from('inv_proformas p')
				  ->join('inv_almacenes a', 'p.almacen_id = a.id_almacen', 'left')
				  ->join('sys_empleados e', 'p.empleado_id = e.id_empleado', 'left')
				  ->where('id_proforma', $id_proforma)
				  ->fetch_first();
	
	// Verifica si existe el proforma
	if (!$proforma || $proforma['empleado_id'] != $_user['persona_id']) {
		// Error 404
		require_once not_found();
		exit;
	} elseif (!$permiso_ver) {
		// Error 401
		require_once bad_request();
		exit;
	}

	// Obtiene los detalles
	$detalles = $db->select('d.*, p.codigo, p.nombre, p.nombre_factura')
				   ->from('inv_proformas_detalles d')
				   ->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')
				   ->where('d.proforma_id', $id_proforma)
				   ->order_by('id_detalle asc')
				   ->fetch();
}

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Importa la libreria para el generado del pdf
require_once libraries . '/tcpdf/tcpdf.php';

// Define variables globales
define('NOMBRE', escape($_institution['nombre']));
define('IMAGEN', escape($_institution['imagen_encabezado']));
define('PROPIETARIO', escape($_institution['propietario']));
define('PIE', escape($_institution['pie_pagina']));
define('FECHA', date(escape($_institution['formato'])) . ' ' . date('H:i:s'));

// Extiende la clase TCPDF para crear Header y Footer
class MYPDF extends TCPDF {
	public function Header() {
		$this->Ln(5);
		$this->SetFont(PDF_FONT_NAME_HEAD, 'I', PDF_FONT_SIZE_HEAD);
		$this->Cell(0, 5, NOMBRE, 0, true, 'R', false, '', 0, false, 'T', 'M');
		$this->Cell(0, 5, PROPIETARIO, 0, true, 'R', false, '', 0, false, 'T', 'M');
		$this->Cell(0, 5, FECHA, 'B', true, 'R', false, '', 0, false, 'T', 'M');
		$imagen = (IMAGEN != '') ? institucion . '/' . IMAGEN : imgs . '/empty.jpg' ;
		$this->Image($imagen, PDF_MARGIN_LEFT, 5, '', 14, 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
	}
	
	public function Footer() {
		$this->SetY(-10);
		$this->SetFont(PDF_FONT_NAME_HEAD, 'I', PDF_FONT_SIZE_HEAD);
		$length = ($this->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT) / 2;
		$number = $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();
		$this->Cell($length, 5, $number, 'T', false, 'L', false, '', 0, false, 'T', 'M');
		$this->Cell($length, 5, PIE, 'T', true, 'R', false, '', 0, false, 'T', 'M');
	}
}

// Instancia el documento PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Asigna la informacion al documento
$pdf->SetCreator(name_autor);
$pdf->SetAuthor(name_autor);
$pdf->SetTitle($_institution['nombre']);
$pdf->SetSubject($_institution['propietario']);
$pdf->SetKeywords($_institution['sigla']);

// Asignamos margenes
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// ------------------------------------------------------------

if ($id_proforma == 0) {
	// Documento general -----------------------------------------------------

	// Adiciona la pagina
	$pdf->AddPage('L', array(PDF_PAGE_FORMAT_WIDTH, PDF_PAGE_FORMAT_HEIGHT));
	
	// Establece la fuente del titulo
	$pdf->SetFont(PDF_FONT_NAME_MAIN, 'BU', PDF_FONT_SIZE_MAIN);
	
	// Titulo del documento
	$pdf->Cell(0, 10, 'LISTA DE PROFORMAS PERSONALES', 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Salto de linea
	$pdf->Ln(5);
	
	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);

	// Define variables
	$valor_moneda = $moneda;

	// Estructura la tabla
	$body = '';
	foreach ($proformas as $nro => $proforma) {
		$body .= '<tr>';
		$body .= '<td>' . ($nro + 1) . '</td>';
		$body .= '<td>' . escape(date_decode($proforma['fecha_proforma'], $_institution['formato']) . ' ' . $proforma['hora_proforma']) . '</td>';
		$body .= '<td>' . escape($proforma['nombre_cliente']) . '</td>';
		$body .= '<td>' . escape($proforma['nit_ci']) . '</td>';
		$body .= '<td align="right">' . escape($proforma['nro_proforma']) . '</td>';
		$body .= '<td align="right">' . escape($proforma['monto_total']) . '</td>';
		$body .= '<td align="right">' . escape($proforma['nro_registros']) . '</td>';
		$body .= '<td>' . escape($proforma['almacen']) . '</td>';
		$body .= '<td>' . escape($proforma['nombres'] . ' ' . $proforma['paterno'] . ' ' . $proforma['materno']) . '</td>';
		$body .= '</tr>';
	}
	
	$body = ($body == '') ? '<tr><td colspan="9" align="center">No existen proformas registrados en la base de datos</td></tr>' : $body;
	
	// Formateamos la tabla
	$tabla = <<<EOD
	<style>
	th {
		background-color: #eee;
		border: 1px solid #444;
		font-weight: bold;
	}
	td {
		border-left: 1px solid #444;
		border-right: 1px solid #444;
	}
	table {
		border-bottom: 1px solid #444;
	}
	</style>
	<table cellpadding="5">
		<tr>
			<th width="6%">#</th>
			<th width="8%">Fecha</th>
			<th width="16%">Cliente</th>
			<th width="10%">NIT/CI</th>
			<th width="10%">Proforma</th>
			<th width="10%">Monto $valor_moneda</th>
			<th width="8%">Registros</th>
			<th width="12%">Almacén</th>
			<th width="20%">Empleado</th>
		</tr>
		$body
	</table>
EOD;
	
	// Imprime la tabla
	$pdf->writeHTML($tabla, true, false, false, false, '');
	
	// Genera el nombre del archivo
	$nombre = 'proformas_' . date('Y-m-d_H-i-s') . '.pdf';
} else {
	// Documento individual --------------------------------------------------
	
	// Asigna la orientacion de la pagina
	$pdf->SetPageOrientation('P');
	
	// Adiciona la pagina
	$pdf->AddPage();
	
	// Establece la fuente del titulo
	$pdf->SetFont(PDF_FONT_NAME_MAIN, 'BU', PDF_FONT_SIZE_MAIN);
	
	// Titulo del documento
	$pdf->Cell(0, 10, 'PROFORMA # ' . escape($proforma['nro_proforma']), 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Salto de linea
	$pdf->Ln(5);
	
	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', 8);
	
	// Define las variables
	$valor_fecha = escape(date_decode($proforma['fecha_proforma'], $_institution['formato']) . ' ' . $proforma['hora_proforma']);
	$valor_nombre_cliente = escape($proforma['nombre_cliente']);
	$valor_nit_ci = escape($proforma['nit_ci']);
	$valor_nro_proforma = escape($proforma['nro_proforma']);
	$valor_monto_total = escape($proforma['monto_total']);
	$valor_nro_registros = escape($proforma['nro_registros']);
	$valor_almacen = escape($proforma['almacen']);
	$valor_empleado = escape($proforma['nombres'] . ' ' . $proforma['paterno'] . ' ' . $proforma['materno']);
	$valor_moneda = $moneda;
	$total = 0;

	// Estructura la tabla
	$body = '';
	foreach ($detalles as $nro => $detalle) {
		$cantidad = escape($detalle['cantidad']);
		$precio = escape($detalle['precio']);
		$descuento = escape($detalle['descuento']);
		$importe = $cantidad * $precio;
		$total = $total + $importe;

		$body .= '<tr>';
		$body .= '<td class="left-right">' . ($nro + 1) . '</td>';
		$body .= '<td class="left-right">' . escape($detalle['codigo']) . '</td>';
		$body .= '<td class="left-right">' . escape($detalle['nombre_factura']) . '</td>';
		$body .= '<td class="left-right" align="right">' . $cantidad . '</td>';
		$body .= '<td class="left-right" align="right">' . $precio . '</td>';
		$body .= '<td class="left-right" align="right">' . $descuento . '</td>';
		$body .= '<td class="left-right" align="right">' . number_format($importe, 2, '.', '') . '</td>';
		$body .= '</tr>';
	}
	
	$valor_total = number_format($total, 2, '.', '');
	$body = ($body == '') ? '<tr><td colspan="7" align="center" class="all">Este proforma no tiene detalle, es muy importante que todos las proformas cuenten con un detalle de venta.</td></tr>' : $body;
	
	// Formateamos la tabla
	$tabla = <<<EOD
	<style>
	th {
		background-color: #eee;
		font-weight: bold;
	}
	.left-right {
		border-left: 1px solid #444;
		border-right: 1px solid #444;
	}
	.none {
		border: 1px solid #fff;
	}
	.all {
		border: 1px solid #444;
	}
	</style>
	<table cellpadding="1">
		<tr>
			<td width="15%" class="none"><b>Fecha y hora:</b></td>
			<td width="35%" class="none">La Paz, $valor_fecha</td>
			<td width="15%" class="none"><b>Operador:</b></td>
			<td width="35%" class="none">$valor_empleado</td>
		</tr>
		<tr>
			<td class="none"><b>Señor(es):</b></td>
			<td class="none">$valor_nombre_cliente</td>
			<td class="none"><b>Almacén:</b></td>
			<td class="none">$valor_almacen</td>
		</tr>
		<tr>
			<td class="none"><b>NIT / CI:</b></td>
			<td class="none">$valor_nit_ci</td>
			<td class="none"><b>Nro. de registros:</b></td>
			<td class="none">$valor_nro_registros</td>
		</tr>
	</table>
	<br><br>
	<table cellpadding="5">
		<tr>
			<th width="5%" class="all">#</th>
			<th width="13%" class="all">Código</th>
			<th width="38%" class="all">Nombre</th>
			<th width="10%" class="all">Cantidad</th>
			<th width="10%" class="all">Precio $valor_moneda</th>
			<th width="13%" class="all">Descuento (%)</th>
			<th width="11%" class="all">Importe $valor_moneda</th>
		</tr>
		$body
		<tr>
			<th class="all" align="right" colspan="6">Importe total $valor_moneda</th>
			<th class="all" align="right">$valor_total</th>
		</tr>
	</table>
EOD;
	
	// Imprime la tabla
	$pdf->writeHTML($tabla, true, false, false, false, '');
	
	// Genera el nombre del archivo
	$nombre = 'proforma_' . $id_proforma . '_' . date('Y-m-d_H-i-s') . '.pdf';
}

// ------------------------------------------------------------

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>
