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
	// Obtiene el egreso
	$egreso = $db->query('select p.*, a.almacen, a.direccion, a.principal, e.nombres, e.paterno, e.materno
                            from inv_egresos p
                            LEFT join inv_almacenes a ON p.almacen_id = a.id_almacen 
                            LEFT join sys_empleados e ON p.empleado_id = e.id_empleado
                            where p.id_egreso = '.$id_egreso.'
						LIMIT 1')->fetch_first();
	
	// Obtiene los detalles
	$detalles = $db->select('d.*,d.unidad_id as unidad_otra, p.codigo, p.nombre, p.nombre_factura')
				   ->from('inv_egresos_detalles d')
				   ->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')
				   ->where('d.egreso_id', $id_egreso)
				   ->order_by('id_detalle asc')
				   ->fetch();
	
	if (!$egreso ) {
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
class MYPDF extends TCPDF {
	public function Header() {
		$this->Ln(5);
		$this->SetFont(PDF_FONT_NAME_HEAD, 'I', PDF_FONT_SIZE_HEAD);
		$this->Cell(0, 5, DIRECCION, 0, true, 'R', false, '', 0, false, 'T', 'M');
		$this->Cell(0, 5, ATENCION, 0, true, 'R', false, '', 0, false, 'T', 'M');
		$this->Cell(0, 5, TELEFONO, 0, true, 'R', false, '', 0, false, 'T', 'M');
		$imagen = (IMAGEN != '') ? institucion . '/' . IMAGEN : imgs . '/empty.jpg' ;
		$this->Image($imagen, PDF_MARGIN_LEFT, 5, '', 14, 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
	}

	public function Footer() {
		$this->SetY(-30);
		$this->SetFont(PDF_FONT_NAME_HEAD, 'I', PDF_FONT_SIZE_HEAD);
		$length = ($this->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT) / 2.15;
		$number = $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();
		$this->Cell($length, 5, $number, 'T', false, 'L', false, '', 0, false, 'T', 'M');
		$this->Cell($length, 5, PIE, 'T', true, 'R', false, '', 0, false, 'T', 'M');
	}
}

// Instancia el documento PDF
// $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf = new MYPDF('P', 'pt', PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Asigna la informacion al documento
$pdf->SetCreator(name_autor);
$pdf->SetAuthor(name_autor);
$pdf->SetTitle($_institution['nombre']);
$pdf->SetSubject($_institution['propietario']);
$pdf->SetKeywords($_institution['sigla']);

// Asignamos margenes
// $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
// Asignamos margenes
$pdf->SetMargins(30, 30, 30);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Elimina las cabeceras
$pdf->setPrintHeader(false);

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
	// $pdf->Cell(0, 10, 'NOTA DE VENTA', 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Salto de linea
	// $pdf->Ln(5);
	
	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', 9);
	
	// Define las variables
	$valor_fecha = escape(date_decode($egreso['fecha_egreso'], $_institution['formato']) . ' - ' . $egreso['hora_egreso']);
	$valor_nombre_cliente = escape($egreso['nombre_cliente']);
	$valor_nit_ci = escape($egreso['nit_ci']);
	$valor_direccion_e = escape($egreso['direccion']);
	$valor_nro_egreso = escape($egreso['id_egreso']);
	$valor_monto_total = escape($egreso['monto_total']);
// 	$valor_nro_registros = escape($egreso['nro_factura']);
	$valor_nro_registros = escape($egreso['nro_nota']);
	$valor_almacen = escape($egreso['almacen']);
	$valor_empleado = escape($egreso['nombres'] . ' ' . $egreso['paterno'] . ' ' . $egreso['materno']);
	
	$valor_moneda = $moneda;
	$descripcion_venta=escape($egreso['descripcion_venta']);
	$total = 0;

	// Datos de la empresa
	$valor_logo = (IMAGEN != '') ? institucion . '/' . IMAGEN : imgs . '/empty.jpg' ;
	$valor_empresa = $_institution['nombre'];
	$valor_direccion = $_institution['direccion'];
	$valor_telefono = $_institution['telefono'];
	$valor_pie = $_institution['pie_pagina'];
	$valor_razon = $_institution['razon_social'];

	
	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', 8);

	// Estructura la tabla
	$body = '';
	foreach ($detalles as $nro => $detalle) {
		$cantidad = escape($detalle['cantidad']/cantidad_unidad($db,$detalle['producto_id'],$detalle['unidad_otra']));
		$precio = escape($detalle['precio']);
		$descuento = 0;
		$importe = $cantidad * $precio;
		$total = $total + $importe;

		$body .= '<tr>';
		$body .= '<td class="left-right">' . ($nro + 1) . '</td>';
		$body .= '<td class="left-right">' . escape($detalle['codigo']) . '</td>';
		$body .= '<td class="left-right">' . escape($detalle['nombre_factura']) .' <br>(LOTE: '. escape($detalle['lote']) .' VENC: '. escape(date_decode($detalle['vencimiento'], $_institution['formato'])) .')' . '</td>';
		$body .= '<td class="left-right" align="right">' . $cantidad . ' '.nombre_unidad($db,$detalle['unidad_otra']). '</td>';
		$body .= '<td class="left-right" align="right">' . number_format($precio, 2, ',', '.') . '</td>';
		$body .= '<td class="left-right" align="right">' . number_format($importe, 2, ',', '.') . '</td>';
		$body .= '</tr>';
	}
	
	$valor_total = number_format($total, 2, '.', '');
	$body = ($body == '') ? '<tr><td colspan="7" align="center" class="all">Este egreso no tiene detalle, es muy importante que todos las egresos cuenten con un detalle de venta.</td></tr>' : $body;
	
	$valor_totalx=number_format($valor_total, 2, ',', '.');
	
	$des = '';
	if($descripcion_venta != ''){
	    $des = "
    	<br><br>
        <table cellpadding='1'>
    		<tr>
    			<td width='15%' class='none'><b>OBSERVACION:</b></td>
    			<td width='35%' class='none' align='left'>$descripcion_venta</td>
    		</tr>
    	</table>";    
	}
	
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
	#cssTable td { vertical-align: middle; }

	</style>
	<table cellpadding="1" id="cssTable">
		<tr>
			<td width="20%" class="none" align="left" rowspan="4">
				<img src="$valor_logo" width="70">
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
			<!-- <td class="none" align="right"><b>NÚMERO DE NOTA:</b></td> -->
			<!-- <td class="none" align="right">$valor_nro_registros</td> -->
		</tr>
		<tr>
			<td class="none" align="left">$valor_pie</td>
			<td class="none" align="right" colspan="2"></td>
		</tr>
	</table>
	<h1 align="center">BAJA Nº $valor_nro_registros</h1>
	<br>

	<table cellpadding="1">
		<tr>
			<td width="22%" class="none"><b>FECHA Y HORA:</b></td>
			<td width="28%" class="none">$valor_fecha</td>
		</tr>
		<tr>
			<td class="none"><b>VISITADOR:</b></td>
			<td class="none">$valor_nombre_cliente</td>
		</tr>
		<tr>
			<td class="none"><b>DIRECCION ALMACEN:</b></td>
			<td class="none">$valor_direccion_e</td>
		</tr>
	</table>
	
	<br><br>
	<table cellpadding="5">
		<tr>
			<th width="5%" class="all" align="center">#</th>
			<th width="24%" class="all" align="center">CÓDIGO</th>
			<th width="35%" class="all" align="center">NOMBRE</th>
			<th width="12%" class="all" align="center">CANTIDAD</th>
			<th width="12%" class="all" align="center">PRECIO $valor_moneda</th>
			<th width="12%" class="all" align="center">TOTAL $valor_moneda</th>
		</tr>
		$body
		<tr>
			<th class="all" align="left" colspan="5">PRECIO TOTAL $valor_moneda</th>
			<th class="all" align="right">$valor_totalx</th>
		</tr>
	</table>
    $des
EOD;
    
	
	// Imprime la tabla
	$pdf->writeHTML($tabla, true, false, false, false, '');
	
	// Genera el nombre del archivo
	$nombre = 'Baja_especial_' . $id_egreso . '_' . date('Y-m-d_H-i-s') . '.pdf';
}

// ------------------------------------------------------------

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>
