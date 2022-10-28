<?php

// Obtiene el id_proforma
$id_proforma = (isset($params[0])) ? $params[0] : 0;

// Obtiene la proforma
$proforma = $db->select('p.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno, e.telefono')->from('inv_proformas p')->join('inv_almacenes a', 'p.almacen_id = a.id_almacen', 'left')->join('sys_empleados e', 'p.empleado_id = e.id_empleado', 'left')->where('id_proforma', $id_proforma)->fetch_first();

// Verifica si existe el proforma
//if (!$proforma || $proforma['empleado_id'] != $_user['persona_id']) {
if (!$proforma) {
	// Error 404
	require_once not_found();
	exit;
}

// Obtiene los detalles
$detalles = $db->select('d.*, p.codigo, p.nombre, p.descripcion')->from('inv_proformas_detalles d')->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')->where('d.proforma_id', $id_proforma)->order_by('id_detalle asc')->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene datos generales
$telefono = str_replace(',', ' / ', escape($_institution['telefono']));
$correo = escape($_institution['correo']);

// Define datos de la proforma
$nro_proforma = escape($proforma['nro_proforma']);
$fecha = upper('La Paz, ' . get_date_literal($proforma['fecha_proforma']));
$nombre_cliente = escape($proforma['nombre_cliente']);
$nit_ci = escape($proforma['nit_ci']);
$atencion = escape($proforma['descripcion']);
$validez = date_decode(add_day($proforma['fecha_proforma'], intval($proforma['validez'])), $_institution['formato']);
$observacion = trim(escape($proforma['observacion']));
$monto_total = escape($proforma['monto_total']);
$total = 0;

// Datos del vendedor
$nombre_empleado = trim($proforma['nombres'] . ' ' . $proforma['paterno'] . ' ' . $proforma['materno']);
$nombre_empleado = ($nombre_empleado != '') ? $nombre_empleado : upper('ninguno');
$telefono_empleado = trim(str_replace(',', ' / ', escape($proforma['telefono'])));
$telefono_empleado = ($telefono_empleado != '') ? $telefono_empleado : upper('ninguno');

// Importa la libreria para generar el reporte
require_once libraries . '/tcpdf/tcpdf.php';

// Importa la libreria para convertir el numero a letra
require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

// Instancia el documento pdf
$pdf = new TCPDF('P', 'pt', 'LETTER', true, 'UTF-8', false);

// Asigna la informacion al documento
$pdf->SetCreator($_institution['propietario']);
$pdf->SetAuthor($_institution['propietario']);
$pdf->SetTitle($_institution['nombre']);
$pdf->SetSubject($_institution['propietario']);
$pdf->SetKeywords($_institution['sigla']);

// Define tamanos y fuentes
$font_name_main = 'roboto';
$font_name_data = 'roboto';
$font_size_main = 10;
$font_size_data = 8;

// Obtiene el ancho de la pagina
$width_page = $pdf->GetPageWidth();

// Define los margenes
$margin_left = $margin_right = 30;
$margin_top = $margin_bottom = 30;

// Define las cabeceras
$margin_header = 30;
$margin_footer = 30;

// Define el ancho de la pagina sin margenes
$width_page = $width_page - $margin_left - $margin_right;

// Asigna margenes
$pdf->SetMargins($margin_left, $margin_top, $margin_right);
$pdf->SetAutoPageBreak(true, $margin_bottom);

// Elimina las cabeceras
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Asigna la orientacion de la pagina
$pdf->SetPageOrientation('P');

// Adiciona la pagina
$pdf->AddPage();

// Define las variables
$width_image = 145;
$height_image = 145;
$rows = 9;
$padding = 5;
$height_cell = ($height_image - $padding) / $rows;
$width_table = ($width_page * (1 - ($width_image / $width_page))) - $padding;

// Define el margen interior de las celdas
$pdf->setCellPaddings($padding, $padding, $padding, $padding);

// Primera sección
$pdf->SetTextColor(48, 48, 48);
$pdf->SetFont($font_name_data, '', $font_size_data);
$pdf->SetXY($margin_left, $margin_top);
$pdf->Cell($width_table * 0.5, $height_cell, 'Innovación agrotécnica', 0, 1, 'L', 0, '', 1, true, 'T', 'M');
$pdf->Cell($width_table * 0.5, $height_cell, 'Venta de maquinaria industrial', 0, 1, 'L', 0, '', 1, true, 'T', 'M');
$pdf->Cell($width_table * 0.5, $height_cell, 'Servicio técnico y mano de obra', 0, 1, 'L', 0, '', 1, true, 'T', 'M');
$pdf->SetTextColor(231, 76, 60);
$pdf->Cell($width_table * 0.5, $height_cell, 'Telefonos: ' . $telefono, 0, 1, 'L', 0, '', 1, true, 'T', 'M');
$pdf->Cell($width_table * 0.5, $height_cell, 'Correo electrónico: ' . $correo, 0, 1, 'L', 0, '', 1, true, 'T', 'M');

// Segunda sección
$pdf->SetTextColor(48, 48, 48);
$pdf->SetXY($margin_left + ($width_table * 0.5), $margin_top);
$pdf->Cell($width_table * 0.5, $height_cell * 1, '', 'L', 1, 'C', 0, '', 1, true, 'T', 'M');
$pdf->SetTextColor(52, 73, 94);
$pdf->SetFont($font_name_data, '', 28);
$pdf->SetXY($margin_left + ($width_table * 0.5), $margin_top + $height_cell);
$pdf->Cell($width_table * 0.5, $height_cell * 2, 'PROFORMA', 'L', 1, 'C', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, 'B', $font_size_main);
$pdf->SetXY($margin_left + ($width_table * 0.5), $margin_top + ($height_cell * 3));
$pdf->Cell($width_table * 0.5, $height_cell, 'Nro. ' . $nro_proforma, 'L', 1, 'C', 0, '', 1, true, 'T', 'M');
$pdf->SetXY($margin_left + ($width_table * 0.5), $margin_top + ($height_cell * 4));
$pdf->Cell($width_table * 0.5, $height_cell, '', 'L', 1, 'C', 0, '', 1, true, 'T', 'M');
$pdf->Ln($padding);

// Tercera sección
$pdf->SetTextColor(48, 48, 48);
$pdf->SetFont($font_name_data, '', $font_size_data);
$pdf->Cell($width_table, $height_cell, $fecha, 0, 1, 'L', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, 'B', $font_size_data);
$pdf->Cell($width_table * 0.12, $height_cell, 'SEÑOR(ES):', 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, '', $font_size_data);
$pdf->Cell($width_table * 0.38, $height_cell, $nombre_cliente, 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, 'B', $font_size_data);
$pdf->Cell($width_table * 0.12, $height_cell, 'NIT / CI:', 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, '', $font_size_data);
$pdf->Cell($width_table * 0.38, $height_cell, $nit_ci, 0, 1, 'L', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, 'B', $font_size_data);
$pdf->Cell($width_table * 0.12, $height_cell, 'ATENCIÓN:', 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, '', $font_size_data);
$pdf->Cell($width_table * 0.38, $height_cell, $atencion, 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, 'B', $font_size_data);
$pdf->Cell($width_table * 0.12, $height_cell, 'VALIDEZ:', 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, '', $font_size_data);
$pdf->Cell($width_table * 0.38, $height_cell, $validez, 0, 1, 'L', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, 'B', $font_size_data);
$pdf->Cell($width_table * 0.12, $height_cell, 'EMPLEADO:', 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, '', $font_size_data);
$pdf->Cell($width_table * 0.38, $height_cell, $nombre_empleado, 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, 'B', $font_size_data);
$pdf->Cell($width_table * 0.12, $height_cell, 'TELÉFONO:', 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, '', $font_size_data);
$pdf->Cell($width_table * 0.38, $height_cell, $telefono_empleado, 0, 1, 'L', 0, '', 1, true, 'T', 'M');
$pdf->Ln($padding);

// Cuarta sección
$pdf->SetTextColor(52, 73, 94);
$pdf->SetFont($font_name_data, 'B', $font_size_main);
$pdf->Cell($width_page * 0.12, $height_cell * 1.5, 'CÓDIGO', 0, 0, 'C', 0, '', 1, true, 'T', 'M');
$pdf->Cell($width_page * 0.52, $height_cell * 1.5, 'DESCRIPCIÓN', 0, 0, 'C', 0, '', 1, true, 'T', 'M');
$pdf->Cell($width_page * 0.12, $height_cell * 1.5, 'CANTIDAD', 0, 0, 'C', 0, '', 1, true, 'T', 'M');
$pdf->Cell($width_page * 0.12, $height_cell * 1.5, 'P. UNITARIO', 0, 0, 'C', 0, '', 1, true, 'T', 'M');
$pdf->Cell($width_page * 0.12, $height_cell * 1.5, 'SUBTOTAL', 0, 1, 'C', 0, '', 1, true, 'T', 'M');

// Imprime la imagen
$imagen = (IMAGEN != '') ? institucion . '/' . escape($_institution['imagen_encabezado']) : imgs . '/empty.jpg' ;
$pdf->Image($imagen, $margin_left + $width_table + $padding, $margin_top, $width_image, $height_image, 'jpg', '', 'T', false, false, '', false, false, 0, false, false, false);

// Define el estilo de los bordes
$border = array(
	'width' => 1,
	'cap' => 'butt',
	'join' => 'miter',
	'dash' => 0,
	'color' => array(52, 73, 94)
);

// Imprime los bordes
$pdf->SetLineStyle($border);
$pdf->RoundedRect($margin_left, $margin_top, $width_table, $height_cell * 5, $padding, '1111');
$pdf->RoundedRect($margin_left, $margin_top + ($height_cell * 5) + $padding, $width_table, $height_cell * 4, $padding, '1111');
$pdf->RoundedRect($margin_left, $margin_top + ($height_cell * 9) + ($padding * 2), $width_page, $height_cell * 1.5, $padding, '1111');

// Define el color de las lineas
$pdf->SetTextColor(48, 48, 48);

// Titulo del documento
$pdf->SetXY($margin_left, $margin_top + ($height_cell * 9) + ($padding * 3) + ($height_cell * 1.5));

// Estructura la tabla
$body = '';
foreach ($detalles as $nro => $detalle) {
	$cantidad = escape($detalle['cantidad']);
	$precio = escape($detalle['precio']);
	$descuento = escape($detalle['descuento']);
	$importe = $cantidad * $precio;
	$total = $total + $importe;
	$body .= '<tr>';
	$body .= '<td width="12%" align="left">' . escape($detalle['codigo']) . '</td>';
	$body .= '<td width="52%" align="justify"><b>' . escape($detalle['nombre']) . '</b><br><em>' . escape($detalle['descripcion']) . '</em></td>';
	$body .= '<td width="12%" align="right">' . $cantidad . '</td>';
	$body .= '<td width="12%" align="right">' . number_format($precio, 2, '.', ',') . '</td>';
	$body .= '<td width="12%" align="right"><b>' . number_format($importe, 2, '.', ',') . '</b></td>';
	$body .= '</tr>';
}
$total = number_format($total, 2, '.', '');

// Obtiene los datos del monto total
$conversor = new NumberToLetterConverter();
$monto_textual = explode('.', $total);
$monto_numeral = $monto_textual[0];
$monto_decimal = $monto_textual[1];
$monto_literal = upper($conversor->to_word($monto_numeral));

// Formatea la tabla en caso de tabla vacia
$body = ($body == '') ? '<tr><td colspan="5" align="center">Este egreso no tiene detalle, es muy importante que todos las egresos cuenten con un detalle de venta.</td></tr>' : $body;

// Formateamos la tabla
$tabla = '<style>
table { margin: 0px; }
th { background-color: #eee; font-weight: bold; }
td { border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; }
</style>
<table cellpadding="' . $padding . '">' . $body . '</table>';

// Asigna la fuente
$pdf->SetFont($font_name_data, '', $font_size_data);

// Imprime la tabla
$pdf->writeHTML($tabla, true, false, false, false, '');

// Obtiene la posicion vertical final
$final = $pdf->getY() - 18;

// Asigna la posicion final
$pdf->SetXY($margin_left, $final + $padding);

// Cuarta sección
$pdf->SetTextColor(52, 73, 94);
$pdf->SetFont($font_name_data, 'B', $font_size_main);
$pdf->Cell($width_page * 0.50, $height_cell * 1.5, 'IMPORTE TOTAL ' . $moneda, 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->Cell($width_page * 0.50, $height_cell * 1.5, number_format($total, 2, '.', ','), 0, 1, 'R', 0, '', 1, true, 'T', 'M');
$pdf->RoundedRect($margin_left, $final + $padding, $width_page, $height_cell * 1.5, $padding, '1111');

// Asigna la fuente y color
$pdf->SetFont($font_name_data, '', $font_size_main);
$pdf->SetTextColor(48, 48, 48);

// Salto de linea
$pdf->Ln($padding);

// Imprime el monto literal
$pdf->writeHTML('<b>SON:</b> ' . $monto_literal . ' ' . $monto_decimal . '/100', true, false, false, false, '');

// Verifica si existe una observacion
if ($observacion != '') {
	// Imprime la tabla
	$pdf->writeHTML('<table><tr><td><br><br><u><b>OBSERVACIÓN:</b></u><br></td></tr><tr><td align="justify">' . $observacion . '</td></tr></table>', true, false, false, false, '');
}

// Imprime el footer
$pdf->writeHTML('<img src="' . imgs . '/footer.jpg" width="' . $width_page . '">', true, false, false, false, '');

// Genera el nombre del archivo
$nombre = 'proforma_' . $id_proforma . '_' . date('Y-m-d_H-i-s') . '.pdf';

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>
