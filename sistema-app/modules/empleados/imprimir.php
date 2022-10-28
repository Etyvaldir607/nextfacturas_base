<?php

// Obtiene el id_empleado
$id_empleado = (sizeof($params) > 0) ? $params[0] : 0;

if ($id_empleado == 0) {
	// Obtiene los empleados
	$empleados = $db->select('z.*')
	->from('sys_empleados z')
	->order_by('z.id_empleado')
	->fetch();
} else {
	// Obtiene los permisos
	$permisos = explode(',', permits);
	
	// Almacena los permisos en variables
	$permiso_ver = in_array('ver', $permisos);
	
	// Obtiene el empleado
	$empleado = $db->select('z.*')
	->from('sys_empleados z')
	->where('z.id_empleado', $id_empleado)
	->fetch_first();
	
	// Verifica si existe el empleado
	if (!$empleado) {
		// Error 404
		require_once not_found();
		exit;
	} elseif (!$permiso_ver) {
		// Error 401
		require_once bad_request();
		exit;
	}
}

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

if ($id_empleado == 0) {
	// Documento general -----------------------------------------------------

	// Adiciona la pagina
	$pdf->AddPage('L', array(PDF_PAGE_FORMAT_WIDTH, PDF_PAGE_FORMAT_HEIGHT));
	
	// Establece la fuente del titulo
	$pdf->SetFont(PDF_FONT_NAME_MAIN, 'BU', PDF_FONT_SIZE_MAIN);
	
	// Titulo del documento
	$pdf->Cell(0, 10, 'LISTA DE EMPLEADOS', 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Salto de linea
	$pdf->Ln(5);
	
	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);
	
	// Estructura la tabla
	$body = '';
	foreach ($empleados as $nro => $empleado) {
		$body .= '<tr>';
		$body .= '<td>' . ($nro + 1) . '</td>';
		$body .= '<td>' . escape($empleado['nombres']) . '</td>';
		$body .= '<td>' . escape($empleado['paterno']) . '</td>';
		$body .= '<td>' . escape($empleado['materno']) . '</td>';
		$body .= '<td>' . escape($empleado['genero']) . '</td>';
		$body .= '<td>' . date_decode(escape($empleado['fecha_nacimiento']), $_institution['formato']) . '</td>';
		$body .= '<td>' . str_replace(',', ' / ', escape($empleado['telefono'])) . '</td>';
		$body .= '<td>' . escape($empleado['cargo']) . '</td>';
		$body .= '</tr>';
	}
	
	$body = ($body == '') ? '<tr><td colspan="8" align="center">No existen empleados registrados en la base de datos</td></tr>' : $body;
	
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
			<th width="15%">Nombres</th>
			<th width="15%">Apellido paterno</th>
			<th width="15%">Apellido materno</th>
			<th width="8%">Género</th>
			<th width="8%">Fecha de nacimiento</th>
			<th width="16.5%">Teléfono</th>
			<th width="16.5%">Cargo</th>
		</tr>
		$body
	</table>
EOD;
	
	// Imprime la tabla
	$pdf->writeHTML($tabla, true, false, false, false, '');
	
	// Genera el nombre del archivo
	$nombre = 'empleados_' . date('Y-m-d_H-i-s') . '.pdf';
} else {
	// Documento individual --------------------------------------------------
	
	// Asigna la orientacion de la pagina
	$pdf->SetPageOrientation('P');
	
	// Adiciona la pagina
	$pdf->AddPage();
	
	// Establece la fuente del titulo
	$pdf->SetFont(PDF_FONT_NAME_MAIN, 'BU', PDF_FONT_SIZE_MAIN);
	
	// Titulo del documento
	$pdf->Cell(0, 10, 'DETALLE DE EMPLEADO', 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Establece la fuente del titulo
	$pdf->SetFont('helvetica', 'I', 10);
	
	// Subtitulo del documento
	$pdf->Cell(0, 5, 'ID: ' . $id_empleado, 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Salto de linea
	$pdf->Ln(5);
	
	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);
	
	// Define las variables
	$valor_nombres = escape($empleado['nombres']);
	$valor_paterno = escape($empleado['paterno']);
	$valor_materno = escape($empleado['materno']);
	$valor_genero = escape($empleado['genero']);
	$valor_fecha_nacimiento = date_decode(escape($empleado['fecha_nacimiento']), $_institution['formato']);
	$valor_telefono = escape($empleado['telefono']);
	$valor_cargo = escape($empleado['cargo']);
	
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
						<th width="40%">Nombres:</th>
						<td width="60%">$valor_nombres</td>
					</tr>
					<tr>
						<th width="40%">Apellido paterno:</th>
						<td width="60%">$valor_paterno</td>
					</tr>
					<tr>
						<th width="40%">Apellido materno:</th>
						<td width="60%">$valor_materno</td>
					</tr>
					<tr>
						<th width="40%">Género:</th>
						<td width="60%">$valor_genero</td>
					</tr>
					<tr>
						<th width="40%">Fecha de nacimiento:</th>
						<td width="60%">$valor_fecha_nacimiento</td>
					</tr>
					<tr>
						<th width="40%">Teléfono:</th>
						<td width="60%">$valor_telefono</td>
					</tr>
					<tr>
						<th width="40%">Cargo:</th>
						<td width="60%">$valor_cargo</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
EOD;
	
	// Imprime la tabla
	$pdf->writeHTML($tabla, true, false, false, false, '');
	
	// Genera el nombre del archivo
	$nombre = 'empleado_' . $id_empleado . '_' . date('Y-m-d_H-i-s') . '.pdf';
}

// ------------------------------------------------------------

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>
