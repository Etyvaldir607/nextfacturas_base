<?php

// Obtiene los clientes
$clientes = $db->select('nombre_cliente, nit_ci, count(nombre_cliente) as nro_visitas, sum(monto_total) as total_ventas')
			   ->from('inv_egresos')
			   ->group_by('nombre_cliente, nit_ci')
			   ->order_by('nombre_cliente asc, nit_ci asc')
			   ->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')
			 ->where('oficial', 'S')
			 ->fetch_first();

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

// Documento general -----------------------------------------------------

// Adiciona la pagina
$pdf->AddPage('P', array(PDF_PAGE_FORMAT_WIDTH, PDF_PAGE_FORMAT_HEIGHT));

// Establece la fuente del titulo
$pdf->SetFont(PDF_FONT_NAME_MAIN, 'BU', PDF_FONT_SIZE_MAIN);

// Titulo del documento
$pdf->Cell(0, 10, 'LISTA DE CLIENTES', 0, true, 'C', false, '', 0, false, 'T', 'M');

// Salto de linea
$pdf->Ln(5);

// Establece la fuente del contenido
$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);

// Estructura la tabla
$body = '';
foreach ($clientes as $nro => $cliente) {
	$body .= '<tr>';
	$body .= '<td>' . ($nro + 1) . '</td>';
	$body .= '<td>' . escape($cliente['nombre_cliente']) . '</td>';
	$body .= '<td>' . escape($cliente['nit_ci']) . '</td>';
	$body .= '<td>' . escape($cliente['nro_visitas']) . '</td>';
	$body .= '<td>' . escape($cliente['total_ventas']) . '</td>';
	$body .= '</tr>';
}

$body = ($body == '') ? '<tr><td colspan="5" align="center">No existen clientes registrados en la base de datos</td></tr>' : $body;

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
		<th width="10%">#</th>
		<th width="35%">Cliente</th>
		<th width="20%">NIT/CI</th>
		<th width="15%">Visitas</th>
		<th width="20%">Ventas $moneda</th>
	</tr>
	$body
</table>
EOD;

// Imprime la tabla
$pdf->writeHTML($tabla, true, false, false, false, '');

// Genera el nombre del archivo
$nombre = 'clientes_' . date('Y-m-d_H-i-s') . '.pdf';

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>
