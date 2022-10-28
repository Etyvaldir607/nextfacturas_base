<?php

$id_egreso = (isset($params[0])) ? $params[0] : 0;
//$id_producto = (isset($params[1])) ? $params[1] : 0;
$det = $db->from('inv_egresos')->where('id_egreso',$id_egreso)->fetch_first();
$nombrer = $det['nombre_cliente'];
$nit = $det['nit_ci'];

// Obtiene los movimientos
//$movimientos = $db->query("select m.*, ifnull(concat(e.nombres, ' ', e.paterno, ' ', e.materno), '') as empleado from (select i.id_ingreso as id_movimiento, d.id_detalle, i.fecha_ingreso as fecha_movimiento, i.hora_ingreso as hora_movimiento, i.descripcion, d.cantidad, d.costo as monto, 'i' as tipo, i.empleado_id, i.almacen_id from inv_ingresos_detalles d left join inv_ingresos i on d.ingreso_id = i.id_ingreso where d.producto_id = $id_producto union select e.id_egreso as id_movimiento, d.id_detalle, e.fecha_egreso as fecha_movimiento, e.hora_egreso as hora_movimiento, e.descripcion, d.cantidad, d.precio as monto, 'e' as tipo, e.empleado_id, e.almacen_id from inv_egresos_detalles d left join inv_egresos e on d.egreso_id = e.id_egreso where d.producto_id = $id_producto) m left join sys_empleados e on m.empleado_id = e.id_empleado where m.almacen_id = $id_almacen order by m.fecha_movimiento asc, m.hora_movimiento asc")->fetch();
// Obtener las transacciones
$clientet = $db->select('nombre_cliente, nit_ci, count(nombre_cliente) as nro_visitas, sum(monto_total) as total_ventas')
               ->from('inv_egresos')
               ->where(array('nombre_cliente' => $nombrer,'nit_ci' => $nit))
               ->fetch_first();
               
$ventas = number_format($clientet['total_ventas'],2,',','.');
$visitas = $clientet['nro_visitas'];
$movimientos = $db->select('c.codigo, c.nombre, c.descripcion, d.categoria, SUM(b.cantidad) AS cant, COUNT(a.id_egreso) AS reg, SUM(b.precio*b.cantidad) AS prec')
                  ->from('inv_egresos a')
                  ->join('inv_egresos_detalles b','a.id_egreso = b.egreso_id')
                  ->join('inv_productos c','b.producto_id = c.id_producto')
                  ->join('inv_categorias d', 'c.categoria_id = d.id_categoria')
                  ->group_by('id_producto')
                  ->where(array('a.nombre_cliente' => $nombrer, 'nit_ci' => $nit))
                  ->fetch();
//var_dump($movimientos);exit();

if (!$movimientos) {
	// Error 404
	//require_once not_found();
	//exit;
}

// Obtiene el almacen
$almacen = $db->from('inv_almacenes')->where('id_almacen', $id_almacen)->fetch_first();

// Obtiene el producto
$producto = $db->from('inv_productos')->where('id_producto', $id_producto)->fetch_first();

$codigo=$producto['codigo'];
$nombre=$producto['nombre'];
$precio=$producto['precio_actual'];
$almacenName=escape($almacen['almacen']); 
$direccion=escape($almacen['direccion']); 
$principal=($almacen['principal'] == 'S') ? 'Si' : 'No'; 

//$direccion=$almacen['direccion']; 
//$principal=$almacen['principal']; 
//$principal=$almacen['principal']; 

/*
// Obtiene el id_egreso
$id_egreso = (isset($params[0])) ? $params[0] : 0;

*/
// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Importa la libreria para el generado del pdf
require_once libraries . '/tcpdf/tcpdf.php';

// Importa la libreria para convertir el numero a letra
require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';
						   

// Define variables globales
/*define('DIRECCION', escape($_institution['pie_pagina']));
define('IMAGEN', escape($_institution['imagen_encabezado']));
define('ATENCION', 'Lun. a Vie. de 08:30 a 18:30 y Sáb. de 08:30 a 13:00');
define('PIE', escape($_institution['pie_pagina']));
define('TELEFONO', escape(str_replace(',', ', ', $_institution['telefono'])));
*/
// Instancia el documento PDF
$pdf = new TCPDF('L', 'pt', 'LETTER', true, 'UTF-8', false);


// ------------------------------------------------------------

// Define tamanos y fuentes
$font_name_main = 'roboto';
$font_name_data = 'roboto';
$font_size_main = 8;
$font_size_data = 7;

// Obtiene el ancho de la pagina
$width_page = $pdf->GetPageWidth();

// Define los margenes
$margin_left = $margin_right = 30;
$margin_top = 30;
//$margin_left2 = $margin_right = 60;
$margin_bottom = 20;
// Define las cabeceras
$margin_header = 30;
$margin_header = 60;
$margin_footer2 = 30;
$margin_footer = 1;

// Define el ancho de la pagina sin margenes
$width_page = $width_page - $margin_left - $margin_right;
// Define el ancho de la pagina sin margenes
$width_page2 = $margin_left + $margin_right ;

// Asigna margenes
$pdf->SetMargins($margin_left, $margin_top, $margin_right);
$pdf->SetAutoPageBreak(true, $margin_bottom);

// Elimina las cabeceras
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);


// Asigna la orientacion de la pagina
$pdf->SetPageOrientation('L');

	// Adiciona la pagina
	$pdf->AddPage();
	
// Define las variables
$width_image = 145;
$height_image = 50;
$rows = 9;
$padding = 5;
$height_cell = ($height_image - $padding) / $rows;
$width_table = ($width_page * (1 - ($width_image / $width_page))) - $padding; //19CM //230
//$width_table = ($width_page * (1 - ($width_image / $width_page))) - $padding;
//$width_table2= ($width_image + $padding + 900 + 900 + 900 );
//$width_table3= ($padding);
$pru = (30);
$pdf->SetAlpha(1);
// Define el margen interior de las celdas
$pdf->setCellPaddings($padding, $padding, $padding, $padding);

// Primera sección
$pdf->SetTextColor(48, 48, 48);
$pdf->SetFont($font_name_data, '', $font_size_data);
$pdf->SetXY($margin_left , $margin_top);
$pdf->Ln($padding+2);
// Cuarta sección
$pdf->Ln($padding-45);

// Segunda sección
$pdf->SetTextColor(52, 73, 94);
$pdf->SetFont($font_name_data, '', 12);

$pdf->Cell($width_page * 1.1, $height_cell * 12.5, '  REPORTE DE CLIENTES ' ,  0, 0, 'C', 0, '', 1, true, 'T', 'M');

$pdf->SetFont($font_name_data, 'B', 8);
$pdf->Ln($padding+13);

$pdf->Ln($padding+29);


// Tercera sección
$pdf->SetTextColor(48, 48, 48);


$pdf->SetFont($font_name_data, 'B', $font_size_data);

$pdf->Ln($padding);
$pdf->SetFont($font_name_data, 'B', $font_size_data);
$pdf->Cell($width_page* 0.30, $height_cell * 0.1, 'NIT / CI: ', 0, 0, 'R', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, 'B', $font_size_data);
$pdf->Cell($width_page* 0.25, $height_cell * 0.1, '', 0, 0, 'C', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, 'B', $font_size_data);
$pdf->Cell($width_page * 0.10, $height_cell * 0.1, 'VISITAS:', 0, 0, 'R', 0, '', 1, true, 'T', 'M');

$pdf->Ln($padding);
$pdf->SetFont($font_name_data, 'B', $font_size_data);
$pdf->Cell($width_page * 0.30, $height_cell * 1.2 , 'NOMBRE:', 0, 0, 'R', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, 'B', $font_size_data);
$pdf->Cell($width_page* 0.25, $height_cell * 0.1, '', 0, 0, 'C', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, 'B', $font_size_data);
$pdf->Cell($width_page * 0.10, $height_cell * 1.2, 'TOTAL:', 0, 0, 'R', 0, '', 1, true, 'T', 'M');

$pdf->Ln($padding);
$pdf->SetFont($font_name_data, 'B', $font_size_data);
$pdf->Cell($width_page * 0.30, $height_cell * 2.3 , '', 0, 0, 'R', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, 'B', $font_size_data);
$pdf->Cell($width_page* 0.25, $height_cell * 0.1, '', 0, 0, 'C', 0, '', 1, true, 'T', 'M');
$pdf->SetFont($font_name_data, 'B', $font_size_data);
$pdf->Cell($width_page * 0.10, $height_cell * 2.3, '', 0, 0, 'R', 0, '', 1, true, 'T', 'M');

$pdf->Ln($padding-5);
$pdf->SetFont($font_name_data, '', $font_size_data);
//$pdf->Cell($width_page *0.80, $height_cell * 1.30,  $fecha , 0, 0, 'C', 0, '', 1, true, 'T', 'M');
//$pdf->Ln($padding-5);
$pdf->Cell($width_page*0.30, $height_cell * -3.5,  '', 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->Cell($width_page*0.50, $height_cell * -3.5,  $nit, 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->Ln($padding-5);
$pdf->Cell($width_page*0.65, $height_cell * -3.5,  '', 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->Cell($width_page * 1.35, $height_cell * -3.5, $visitas, 0, 0, 'L', 0, '', 1, true, 'T', 'M');

$pdf->Ln($padding-5);
$pdf->Cell($width_page*0.30, $height_cell * -0.7,  '', 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->Cell($width_page*0.50, $height_cell * -0.7, $nombrer, 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->Ln($padding-5);
$pdf->Cell($width_page*0.65, $height_cell * -0.7,  '', 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->Cell($width_page * 1.35, $height_cell * -0.7, $ventas, 0, 0, 'L', 0, '', 1, true, 'T', 'M');

$pdf->Ln($padding-5);
$pdf->Cell($width_page*0.30, $height_cell * 2.3,  '', 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->Cell($width_page * 0.50, $height_cell * 2.3, '', 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->Ln($padding-5);
$pdf->Cell($width_page*0.65, $height_cell * 2.3,  '', 0, 0, 'L', 0, '', 1, true, 'T', 'M');
$pdf->Cell($width_page * 1.35, $height_cell * 2.3, '', 0, 0, 'L', 0, '', 1, true, 'T', 'M');






$imagen = (IMAGEN != '') ? institucion . '/' . escape($_institution['imagen_encabezado']) : IMGS . '/empty.jpg' ;
$pdf->Image($imagen, $margin_left+20 , '35', '65', '50', 'jpg', '', 'T', false, false, '', false, false, 0, false, false, false);

// Define el estilo de los bordes
$border = array(
	'width' => 1,

	'cap' => 'butt',
	'join' => 'miter',
	'dash' => 0,
	'color' => array(52, 73, 94)
);

// Define el color de las lineas
$pdf->SetTextColor(48, 48, 48);

// Titulo del documento
$pdf->SetXY($margin_left, $margin_top + ($height_cell * 9) + ($padding * 3) + ($height_cell * 1.5));

// Estructura la tabla

$saldo_cantidad = 0; 
$saldo_costo = 0; 
$ingresos = array(); 

$body = '';
$body .= '<tr>';
$body .= '<th width="7%" align="center" style="border:1ps solid #aaa;" rowspan="2">#</th>';
$body .= '<th width="12%" align="center" style="border:1ps solid #aaa;" rowspan="2">CODIGO</th>';
$body .= '<th width="27%" align="center" style="border:1ps solid #aaa;" rowspan="2">NOMBRE</th>';
$body .= '<th width="27%" align="center" style="border:1ps solid #aaa;" colspan="3">DETALLE</th>';
$body .= '<th width="27%" align="center" style="border:1ps solid #aaa;" colspan="3">SALDOS</th>';
$body .= '</tr>';

$body .= '<tr>';
$body .= '<th width="9%" align="center" colspan="3" style="border:1ps solid #aaa;">COLOR</th>';
$body .= '<th width="9%" align="center" colspan="3" style="border:1ps solid #aaa;">TIPO</th>';
$body .= '<th width="9%" align="center" colspan="3" style="border:1ps solid #aaa;">DESCRIPCION</th>';
$body .= '<th width="9%" align="center" colspan="3" style="border:1ps solid #aaa;">REGISTROS</th>';
$body .= '<th width="9%" align="center" colspan="3" style="border:1ps solid #aaa;">CANTIDAD</th>';
$body .= '<th width="9%" align="center" colspan="3" style="border:1ps solid #aaa;">TOTAL</th>';
$body .= '</tr>';

foreach ($movimientos as $nro => $movimiento) {
	if (TRUE) {
		array_push($ingresos, array('cantidad' => $movimiento['cantidad'], 'costo' => $movimiento['monto']));
		$saldo_cantidad = $saldo_cantidad + $movimiento['cantidad'];
		$saldo_costo = $saldo_costo + ($movimiento['cantidad'] * $movimiento['monto']);

		$descripcion=(escape($movimiento['descripcion']) == '') ? 'Ingreso de productos a almacén': escape($movimiento['descripcion']);

		$body .= '<tr>';
		$body .= '<td width="7%" align="center">' . escape($nro + 1) . '</td>';
            $body .= '<td width="12%" align="center">' . escape($movimiento['codigo']) . '</td>';
		$body .= '<td width="27%" align="justify">' . escape($movimiento['nombre']) . '</td>';

		$body .= '<td width="9%" align="rigth" style="background-color:#c4e3f3">' . escape($movimiento['color']) . '</td>';
		$body .= '<td width="9%" align="rigth" style="background-color:#c4e3f3">' . escape($movimiento['categoria']) . '</td>';
		$body .= '<td width="9%" align="rigth" style="background-color:#c4e3f3">' . escape($movimiento['descripcion']) . '</td>';

		$body .= '<td width="9%" align="rigth" style="background-color:#d0e9c6;">' .escape($movimiento['reg']) . '</td>';
		$body .= '<td width="9%" align="rigth" style="background-color:#d0e9c6;">' .number_format($movimiento['cant'],0,',','.'). '</td>';
		$body .= '<td width="9%" align="rigth" style="background-color:#d0e9c6;">' .number_format($movimiento['prec'],2,',','.'). '</td>';

		$body .= '</tr>';
	}else{
		$ciclo = true;
		do {
			$ingreso = array_shift($ingresos);
			if ($ingreso['cantidad'] >= $movimiento['cantidad']) {
				$ingreso['cantidad'] = $ingreso['cantidad'] - $movimiento['cantidad'];
				if ($ingreso['cantidad'] > 0) {
					array_unshift($ingresos, $ingreso);
				}
				$ciclo = false;
				$saldo_cantidad = $saldo_cantidad - $movimiento['cantidad'];
				$saldo_costo = $saldo_costo - ($movimiento['cantidad'] * $ingreso['costo']);

				$descripcion=(escape($movimiento['descripcion']) == '') ? 'Ingreso de productos a almacén': escape($movimiento['descripcion']);

				$body .= '<tr>';
				$body .= '<td width="4%" align="center">' . escape($nro + 1) . '</td>';
				$body .= '<td width="8%" align="center">' . escape($movimiento['fecha_movimiento']) .' '.escape($movimiento['hora_movimiento']).'</td>';
				$body .= '<td width="15%" align="justify">' . $descripcion . '</td>';

				$body .= '<td width="7%" align="rigth"></td>';
				$body .= '<td width="7%" align="rigth"></td>';
				$body .= '<td width="7%" align="rigth"></td>';

				$body .= '<td width="7%" align="rigth" style="background-color:#ebcccc">' . number_format($movimiento['cantidad'], 2, ',', '.') . '</td>';
				$body .= '<td width="7%" align="rigth" style="background-color:#ebcccc">' . number_format($ingreso['costo'], 2, ',', '.') . '</td>';
				$body .= '<td width="7%" align="rigth" style="background-color:#ebcccc">' . number_format(($movimiento['cantidad'] * $ingreso['costo']), 2, ',', '.') . '</td>';

				$body .= '<td width="7%" align="rigth" style="background-color:#c4e3f3">' . number_format($saldo_cantidad, 2, ',', '.') . '</td>';
				$body .= '<td width="7%" align="rigth" style="background-color:#c4e3f3"></td>';
				$body .= '<td width="7%" align="rigth" style="background-color:#c4e3f3">' . number_format($saldo_costo, 2, ',', '.') . '</td>';
				$body .= '<td width="10%" align="rigth">' . escape($movimiento['empleado']) . '</td>';
				$body .= '</tr>';
			} else {
				$saldo_cantidad = $saldo_cantidad - $ingreso['cantidad'];
				$saldo_costo = $saldo_costo - ($ingreso['cantidad'] * $ingreso['costo']);
				$descripcion=(escape($movimiento['descripcion']) == '') ? 'Ingreso de productos a almacén': escape($movimiento['descripcion']);

				$body .= '<tr>';
				$body .= '<td width="4%" align="center">' . escape($nro + 1) . '</td>';
				$body .= '<td width="8%" align="center">' . escape($movimiento['fecha_movimiento']) .' '.escape($movimiento['hora_movimiento']).'</td>';
				$body .= '<td width="15%" align="justify">' . $descripcion . '</td>';

				$body .= '<td width="7%" align="rigth"></td>';
				$body .= '<td width="7%" align="rigth"></td>';
				$body .= '<td width="7%" align="rigth"></td>';

				$body .= '<td width="7%" align="rigth" style="background-color:#ebcccc">' . number_format(($ingreso['cantidad']), 0, ',', '.') . '</td>';
				$body .= '<td width="7%" align="rigth" style="background-color:#ebcccc">' . number_format(($ingreso['costo']), 2, ',', '.') . '</td>';
				$body .= '<td width="7%" align="rigth" style="background-color:#ebcccc">' . number_format(($ingreso['cantidad'] * $ingreso['costo']), 2, ',', '.') . '</td>';

				$body .= '<td width="7%" align="rigth" style="background-color:#c4e3f3">' . $saldo_cantidad . '</td>';
				$body .= '<td width="7%" align="rigth" style="background-color:#c4e3f3"></td>';
				$body .= '<td width="7%" align="rigth" style="background-color:#c4e3f3">' . number_format($saldo_costo, 2, ',', '.') . '</td>';
				$body .= '<td width="10%" align="rigth">' . escape($movimiento['empleado']) . '</td>';
				$body .= '</tr>';
				$movimiento['cantidad'] = $movimiento['cantidad'] - $ingreso['cantidad'];
			}
		} while ($ciclo);
	}
}
$total = number_format($total, 2, '.', '');

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
$pdf->SetXY($margin_left , $final + $padding);

// Cuarta sección
$pdf->SetTextColor(52, 73, 94);
$pdf->SetFont($font_name_data, 'B', $font_size_main);

// Asigna la fuente y color
$pdf->SetFont($font_name_data, '', $font_size_main);
$pdf->SetTextColor(48, 48, 48);

// Salto de linea
$pdf->Ln($padding);

// Genera el nombre del archivo
$nombre = 'kardex_valorado_' . $id_egreso . '_' . date('Y-m-d_H-i-s') . '.pdf';

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>
