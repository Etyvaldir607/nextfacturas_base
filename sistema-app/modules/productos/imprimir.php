<?php

// Obtiene el id_producto
$id_producto = (isset($params[0])) ? $params[0] : 0;

if ($id_producto == 0) {
	// Obtiene los productos
	$productos = $db->select('p.*, u.unidad as unidad, c.categoria as categoria')
					->from('inv_productos p')
					->join('inv_unidades u', 'p.unidad_id = u.id_unidad', 'left')
					->join('inv_categorias c', 'p.categoria_id = c.id_categoria', 'left')
					->order_by('p.id_producto')
					->fetch();
} else {
	// Obtiene los permisos
	$permisos = explode(',', permits);
	
	// Almacena los permisos en variables
	$permiso_ver = in_array('ver', $permisos);
	
	// Obtiene el producto
	$producto = $db->select('p.*, u.unidad as unidad, u.sigla as sigla, c.categoria as categoria')
				   ->from('inv_productos p')
				   ->join('inv_unidades u', 'p.unidad_id = u.id_unidad', 'left')
				   ->join('inv_categorias c', 'p.categoria_id = c.id_categoria', 'left')
				   ->where('p.id_producto', $id_producto)
				   ->fetch_first();
	
	// Verifica si existe el producto
	if (!$producto) {
		// Error 404
		require_once not_found();
		exit;
	} elseif (!$permiso_ver) {
		// Error 401
		require_once bad_request();
		exit;
	}
}

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')
			 ->where('oficial', 'S')
			 ->fetch_first();

$moneda = ($moneda) ? escape($moneda['sigla']) : '';

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

if ($id_producto == 0) {
	// Documento general -----------------------------------------------------

	// Adiciona la pagina
	$pdf->AddPage('L', array(PDF_PAGE_FORMAT_WIDTH, PDF_PAGE_FORMAT_HEIGHT));
	
	// Establece la fuente del titulo
	$pdf->SetFont(PDF_FONT_NAME_MAIN, 'BU', PDF_FONT_SIZE_MAIN);
	
	// Titulo del documento
	$pdf->Cell(0, 10, 'LISTA DE productos', 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Salto de linea
	$pdf->Ln(5);
	
	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);

	// Define variables
	$valor_moneda = ($moneda != '') ? ' (' . $moneda . ')' : '';
	
	// Estructura la tabla
	$body = '';
	foreach ($productos as $nro => $producto) {
		$body .= '<tr>';
		$body .= '<td>' . ($nro + 1) . '</td>';
		$body .= '<td>' . escape($producto['codigo']) . '</td>';
		$body .= '<td>' . str_replace("*", "'", escape($producto['nombre'])) . '</td>';
		$body .= '<td>' . escape($producto['categoria']) . '</td>';
		$body .= '<td>' . escape($producto['precio_actual']) . '</td>';
		$body .= '<td>' . escape($producto['cantidad_minima']) . '</td>';
		$body .= '<td>' . escape($producto['unidad']) . '</td>';
		$body .= '</tr>';
	}
	
	$body = ($body == '') ? '<tr><td colspan="4" align="center">No existen productos registrados en la base de datos</td></tr>' : $body;
	
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
			<th width="14%">Código</th>
			<th width="42%">Nombre</th>
			<th width="12%">Categoría</th>
			<th width="8%">Precio actual $valor_moneda</th>
			<th width="8%">Cantidad mínima</th>
			<th width="10%">Unidad</th>
		</tr>
		$body
	</table>
EOD;
	
	// Imprime la tabla
	$pdf->writeHTML($tabla, true, false, false, false, '');
	
	// Genera el nombre del archivo
	$nombre = 'productos_' . date('Y-m-d_H-i-s') . '.pdf';
} else {
	// Documento individual --------------------------------------------------
	
	// Asigna la orientacion de la pagina
	$pdf->SetPageOrientation('P');
	
	// Adiciona la pagina
	$pdf->AddPage();
	
	// Establece la fuente del titulo
	$pdf->SetFont(PDF_FONT_NAME_MAIN, 'BU', PDF_FONT_SIZE_MAIN);
	
	// Titulo del documento
	$pdf->Cell(0, 10, 'DETALLE DE PRODUCTO', 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Establece la fuente del titulo
	$pdf->SetFont('helvetica', 'I', 10);
	
	// Subtitulo del documento
	$pdf->Cell(0, 5, 'ID: ' . $id_producto, 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Salto de linea
	$pdf->Ln(5);
	
	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);
	
	// Define las variables
	$valor_codigo = escape($producto['codigo']);
	$valor_nombre = str_replace("*", "'", escape($producto['nombre']));
	$valor_nombre_factura = escape($producto['nombre_factura']);
	$valor_categoria_id = escape($producto['categoria']);
	$valor_precio_actual = escape($producto['precio_actual'] . ' ' . $moneda);
	$valor_cantidad_minima = escape($producto['cantidad_minima'] . ' ' . $producto['sigla']);
	$valor_unidad_id = escape($producto['unidad']);
	
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
						<th width="40%">Código:</th>
						<td width="60%">$valor_codigo</td>
					</tr>
					<tr>
						<th width="40%">Nombre:</th>
						<td width="60%">$valor_nombre</td>
					</tr>
					<tr>
						<th width="40%">Nombre en la factura:</th>
						<td width="60%">$valor_nombre_factura</td>
					</tr>
					<tr>
						<th width="40%">Categoría:</th>
						<td width="60%">$valor_categoria_id</td>
					</tr>
					<tr>
						<th width="40%">Precio actual:</th>
						<td width="60%">$valor_precio_actual</td>
					</tr>
					<tr>
						<th width="40%">Cantidad mínima:</th>
						<td width="60%">$valor_cantidad_minima</td>
					</tr>
					<tr>
						<th width="40%">Unidad:</th>
						<td width="60%">$valor_unidad_id</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
EOD;
	
	// Imprime la tabla
	$pdf->writeHTML($tabla, true, false, false, false, '');
	
	// Genera el nombre del archivo
	$nombre = 'producto_' . $id_producto . '_' . date('Y-m-d_H-i-s') . '.pdf';
}

// ------------------------------------------------------------

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>
