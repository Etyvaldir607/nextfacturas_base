<?php

// Obtiene el id_dosificacion
$id_dosificacion = (sizeof($params) > 0) ? $params[0] : 0;

if ($id_dosificacion == 0) {
	// Obtiene las dosificaciones
	$dosificaciones = $db->from('inv_dosificaciones')->order_by('id_dosificacion')->fetch();
} else {
	// Obtiene los permisos
	$permisos = explode(',', permits);
	
	// Almacena los permisos en variables
	$permiso_ver = in_array('ver', $permisos);
	
	// Obtiene la dosificación
	$dosificacion = $db->from('inv_dosificaciones')->where('id_dosificacion', $id_dosificacion)->fetch_first();
	
	// Verifica si existe la dosificación
	if (!$dosificacion) {
		// Error 404
		require_once not_found();
		exit;
	} elseif (!$permiso_ver) {
		// Error 401
		require_once bad_request();
		exit;
	}
}

// Define la fecha de hoy
$hoy = date('Y-m-d');

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

if ($id_dosificacion == 0) {
	// Documento general -----------------------------------------------------

	// Adiciona la pagina
	$pdf->AddPage('P', array(PDF_PAGE_FORMAT_WIDTH, PDF_PAGE_FORMAT_HEIGHT));
	
	// Establece la fuente del titulo
	$pdf->SetFont(PDF_FONT_NAME_MAIN, 'BU', PDF_FONT_SIZE_MAIN);
	
	// Titulo del documento
	$pdf->Cell(0, 10, 'LISTA DE LLAVES DE DOSIFICACIONES', 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Salto de linea
	$pdf->Ln(5);
	
	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', 8);
	
	// Estructura la tabla
	$body = '';
	foreach ($dosificaciones as $nro => $dosificacion) {
		$estado = ($hoy < $dosificacion['fecha_registro']) ? 'En espera' : (($hoy > $dosificacion['fecha_limite']) ? 'Sin vigencia' : 'En uso');
		$body .= '<tr>';
		$body .= '<td>' . ($nro + 1) . '</td>';
		$body .= '<td>' . date_decode(escape($dosificacion['fecha_registro']), $_institution['formato']) . ' ' . escape($dosificacion['hora_registro']) . '</td>';
		$body .= '<td>' . escape($dosificacion['nro_tramite']) . '</td>';
		$body .= '<td>' . escape($dosificacion['nro_autorizacion']) . '</td>';
		$body .= '<td>' . date_decode(escape($dosificacion['fecha_limite']), $_institution['formato']) . '</td>';
		$body .= '<td>' . escape($estado) . '</td>';
		$body .= '<td>' . escape($dosificacion['nro_facturas']) . '</td>';
		$body .= '<td>' . 'Ley Nº 453: "' . escape($dosificacion['leyenda']) . '"' . '</td>';
		$body .= '</tr>';
	}
	
	$body = ($body == '') ? '<tr><td colspan="8" align="center">No existen llaves de dosificación registrados en la base de datos</td></tr>' : $body;
	
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
			<th width="5%">#</th>
			<th width="10%">Fecha de registro</th>
			<th width="15%">Número de tramite</th>
			<th width="15%">Número de autorización</th>
			<th width="10%">Fecha límite</th>
			<th width="10%">Estado</th>
			<th width="10%">Número de facturas</th>
			<th width="25%">Leyenda de la factura</th>
		</tr>
		$body
	</table>
EOD;
	
	// Imprime la tabla
	$pdf->writeHTML($tabla, true, false, false, false, '');
	
	// Genera el nombre del archivo
	$nombre = 'dosificaciones_' . date('Y-m-d_H-i-s') . '.pdf';
} else {
	// Documento individual --------------------------------------------------
	
	// Asigna la orientacion de la pagina
	$pdf->SetPageOrientation('P');
	
	// Adiciona la pagina
	$pdf->AddPage();
	
	// Establece la fuente del titulo
	$pdf->SetFont(PDF_FONT_NAME_MAIN, 'BU', PDF_FONT_SIZE_MAIN);
	
	// Titulo del documento
	$pdf->Cell(0, 10, 'DETALLE DE DOSIFICACIóN', 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Establece la fuente del titulo
	$pdf->SetFont('helvetica', 'I', 10);
	
	// Subtitulo del documento
	$pdf->Cell(0, 5, 'ID: ' . $id_dosificacion, 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Salto de linea
	$pdf->Ln(5);
	
	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);
	
	// Define las variables
	$valor_fecha_registro = date_decode(escape($dosificacion['fecha_registro']), $_institution['formato']);
	$valor_hora_registro =  escape($dosificacion['hora_registro']);
	$valor_nro_tramite = escape($dosificacion['nro_tramite']);
	$valor_nro_autorizacion = escape($dosificacion['nro_autorizacion']);
	$valor_fecha_limite = date_decode(escape($dosificacion['fecha_limite']), $_institution['formato']);
	$valor_nro_facturas = escape($dosificacion['nro_facturas']);
	$valor_leyenda = 'Ley Nº 453: "' . escape($dosificacion['leyenda']) . '"';
	$valor_observacion =  escape($dosificacion['observacion']);

	$valor_vigencia = date_diff(date_create($dosificacion['fecha_registro']), date_create($dosificacion['fecha_limite']));
	$valor_vigencia = intval($valor_vigencia->format('%a')) + 1;
	$valor_estado = ($hoy < $dosificacion['fecha_registro']) ? 'En espera' : (($hoy > $dosificacion['fecha_limite']) ? 'Sin vigencia' : 'En uso');

	// Formateamos la tabla
	$tabla = <<<EOD
	<style>
	th {
		background-color: #eee;
		font-weight: bold;
		text-align: right;
		border-right: 1px solid #444;
	}
	</style>
	<table cellpadding="1">
		<tr>
			<td width="10%"></td>
			<td width="80%" style="border: 1px solid #444;">
				<table cellpadding="5">
					<tr>
						<th width="40%">Fecha de registro:</th>
						<td width="60%">$valor_fecha_registro</td>
					</tr>
					<tr>
						<th width="40%">Hora de registro:</th>
						<td width="60%">$valor_hora_registro</td>
					</tr>
					<tr>
						<th width="40%">Número de trámite:</th>
						<td width="60%">$valor_nro_tramite</td>
					</tr>
					<tr>
						<th width="40%">Número de autorización:</th>
						<td width="60%">$valor_nro_autorizacion</td>
					</tr>
					<tr>
						<th width="40%">Fecha límite de emisión:</th>
						<td width="60%">$valor_fecha_limite</td>
					</tr>
					<tr>
						<th width="40%">Vigencia en días:</th>
						<td width="60%">$valor_vigencia</td>
					</tr>
					<tr>
						<th width="40%">Número de facturas emitidas:</th>
						<td width="60%">$valor_nro_facturas</td>
					</tr>
					<tr>
						<th width="40%">Estado:</th>
						<td width="60%">$valor_estado</td>
					</tr>
					<tr>
						<th width="40%">Leyenda de la factura:</th>
						<td width="60%">$valor_leyenda</td>
					</tr>
					<tr>
						<th width="40%">Observación:</th>
						<td width="60%">$valor_observacion</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
EOD;
	
	// Imprime la tabla
	$pdf->writeHTML($tabla, true, false, false, false, '');
	
	// Genera el nombre del archivo
	$nombre = 'dosificacion_' . $id_dosificacion . '_' . date('Y-m-d_H-i-s') . '.pdf';
}

// ------------------------------------------------------------

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>
