<?php 
// Obtiene el id_ingreso
$id_ingreso = (isset($params[0])) ? $params[0] : 0;

if ($id_ingreso == 0) {
	 					
} else {
	// Obtiene los permisos
	$permisos = explode(',', permits);
	
	// Almacena los permisos en variables
	$permiso_ver = in_array('ver', $permisos);
	
	// Obtiene la egreso
	$egreso = $db->query("select a.almacen, a.principal, cl.direccion, cl.telefono, nombre_grupo, cl.cliente, i.nro_nota_credito, i.descripcion, fecha_ingreso, hora_ingreso, 
	                            e.nro_nota, e.nro_factura
        				  from inv_ingresos i
        				  
        				  left join inv_pagos_detalles pd on i.id_ingreso = pd.ingreso_id
                          left join inv_pagos p on (p.id_pago = pd.pago_id AND p.tipo='Egreso')
                          
                          left join inv_egresos e on e.id_egreso = p.movimiento_id
                          
        				  LEFT join inv_almacenes a ON i.almacen_id = a.id_almacen
        				  LEFT join inv_clientes_grupos ON id_cliente_grupo = e.codigo_vendedor
        				  LEFT join inv_clientes cl ON e.cliente_id = cl.id_cliente
        				  where 
        				        i.id_ingreso='$id_ingreso' 
        				  ")
				  ->fetch_first();

    // Obtiene los detalles
	$detalles = $db->query('select d.*, SUM(d.cantidad) as cantidad, p.codigo, p.nombre, p.nombre_factura
                            from inv_ingresos_detalles d
                            left join inv_productos p ON d.producto_id = p.id_producto
                            where d.ingreso_id="'.$id_ingreso.'" 
                            group by precio, producto_id, lote, vencimiento
                            ')
				   ->fetch();
				   
	$qwr_notas_pagadas = $db->query(" select e.nro_nota, SUM(pd.monto)as monto
                				  from inv_pagos_detalles pd 
                                  left join inv_pagos p on p.id_pago = pd.pago_id AND tipo='Egreso'
                				  left join inv_egresos e on e.id_egreso = p.movimiento_id
                				  where pd.ingreso_id='$id_ingreso' 
                				  group by e.nro_nota
                				  ")
        				  ->fetch();
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

if ($id_ingreso == 0) {
	
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
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', 8);
	
	// Define las variables
	$valor_fecha = escape(date_decode($egreso['fecha_ingreso'], $_institution['formato']) . ' ' . $egreso['hora_ingreso']);
	
	if($egreso['nro_nota']!=0){
	    $valor_nro_nota = "Devolucion de productos del nota Nro: ".$egreso['nro_nota'];
	    if($egreso['nro_factura']!=0){
	        $valor_nro_nota .= ", nro de Factura: ".$egreso['nro_factura'];
	    }
	    $valor_nro_nota .= " <br>";
    }else{
        $valor_nro_nota = "";
    }
    
	$valor_nombre_cliente = escape($egreso['cliente']);
	$valor_codigo_vendedor = escape($egreso['nombre_grupo']);
	$valor_nit_ci = escape($egreso['nit_ci']);
	$valor_direccion222 = escape($egreso['direccion']);
	$valor_telefono = escape($egreso['telefono']);
	$valor_nro_egreso = escape($egreso['nro_egreso']);
	$valor_monto_total = escape($egreso['monto_total']);
	$valor_nro_registros = escape($egreso['nro_nota']);
	$valor_almacen = escape($egreso['almacen']);
	$valor_empleado = escape($egreso['nombres'] . ' ' . $egreso['paterno'] . ' ' . $egreso['materno']);
	$valor_vendedor = escape($egreso['nombresv'] . ' ' . $egreso['paternov'] . ' ' . $egreso['maternov']);
	$valor_descuento_global = escape($egreso['descuento_bs']);
	$valor_moneda = $moneda;
	
	$descripcion_venta=escape($egreso['descripcion']);
	$total = 0;

	// Datos de la empresa
	$valor_logo = (IMAGEN != '') ? institucion . '/' . IMAGEN : imgs . '/empty.jpg' ;
	$valor_empresa = $_institution['nombre'];
	$valor_direccion = $_institution['direccion'];
	$valor_telefono = $_institution['telefono'];
	$valor_pie = $_institution['pie_pagina'];
	$valor_razon = $_institution['razon_social'];

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
		$cantidad = $detalle['cantidad'];
		$precio = escape($detalle['precio']);
		$descuento = escape($detalle['descuento']);
		$importe = $cantidad * $precio - $descuento;
		$total = $total + $importe;

		$body .= '<tr>';
		//$body .= '<td class="left-right">' . ($nro + 1) . '</td>';
		$body .= '<td class="left-right">' . escape($detalle['codigo']) . '</td>';
		$body .= '<td class="left-right">' . escape($detalle['nombre_factura']) .' <br>(LOTE: '. escape($detalle['lote']) .' VENC: '. escape(date_decode($detalle['vencimiento'], $_institution['formato'])) .')' . '</td>';
		$body .= '<td class="left-right" align="right">' . $cantidad . '</td>';
		//$body .= '<td class="left-right" align="right">' . $cantidad . ' '.nombre_unidad($db,$detalle['unidad_otra']). '</td>';
		$body .= '<td class="left-right" align="right">' . number_format($precio, 2, ',', '.') . '</td>';
		$body .= '<td class="left-right" align="right">' . number_format($importe, 2, ',', '.') . '</td>';
		$body .= '</tr>';
	}
	
	$notas_pagadas='';
	foreach ($qwr_notas_pagadas as $nro => $notax) {
		$notas_pagadas .= '<tr>';
		$notas_pagadas .= '<td class="left-right"> Nro. de Nota ' . $notax['nro_nota'] . '</td>';
		$notas_pagadas .= '<td class="left-right" align="right">' . number_format($notax['monto'],2,',','.') .'</td>';
		$notas_pagadas .= '</tr>';
	}
	
	//$valor_total = number_format($total, 2, '.', '');

	$valor_total = number_format($total, 2, '.', '');
	$total_con_descuento=$valor_total-$valor_descuento_global;
	$valor_total_con_descuento = number_format($total_con_descuento, 2, '.', '');
	$body = ($body == '') ? '<tr><td colspan="7" align="center" class="all">Este egreso no tiene detalle, es muy importante que todos las egresos cuenten con un detalle de venta.</td></tr>' : $body;
	
	$valor_totalx=number_format($valor_total, 2, ',', '.');
	$valor_descuento_porcentajex=number_format($valor_descuento_porcentaje, 2, ',', '.');
	$valor_descuento_globalx=number_format($valor_descuento_global, 2, ',', '.');
	$valor_total_con_descuentox=number_format($valor_total_con_descuento, 2, ',', '.');
	
	$nro_nota_credito=$egreso['nro_nota_credito']; 
	
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
			<td width="30%" class="none" align="right"><b>OPERADOR:</b></td>
			<td width="20%" class="none" align="right">$valor_empleado</td>
		</tr>
		<tr>
			<td class="none" align="left">$valor_direccion</td>
			<td class="none" align="right"><b>ALMACÉN:</b></td>
			<td class="none" align="right">$valor_almacen</td>
		</tr>
		<tr>
			<td class="none" align="left">Teléfono: $valor_telefono</td>
			<td class="none" align="right"><b></b></td>
			<td class="none" align="right"></td>
		</tr>
		<tr>
			<td class="none" align="left">$valor_pie</td>
			<td class="none" align="right" colspan="2"></td>
		</tr>
	</table>
	<h1 align="center">NOTA DE CREDITO #$nro_nota_credito</h1>
	<br>

	<table cellpadding="1">
		<tr>
			<td width="22%" class="none"><b>FECHA Y HORA:</b></td>
			<td width="78%" class="none">$valor_fecha</td>
		</tr>
		<tr>
			<td class="none"><b>SEÑOR(ES):</b></td>
			<td class="none">$valor_nombre_cliente</td>
		</tr>
		<tr>
			<td class="none"><b>NIT / CI:</b></td>
			<td class="none">$valor_nit_ci</td>
		</tr>
		<tr>
			<td class="none"><b>DIRECCION:</b></td>
			<td class="none">$valor_direccion222</td>
		</tr>
	</table>
	
	<br><br>
	<table cellpadding="5">
		<tr>
			<th width="17%" class="all" align="center">CÓDIGO</th>
			<th width="47%" class="all" align="center">NOMBRE</th>
			<th width="12%" class="all" align="center">CANTIDAD</th>
			<th width="12%" class="all" align="center">PRECIO $valor_moneda</th>
			<th width="12%" class="all" align="center">IMPORTE $valor_moneda</th>
		</tr>
		$body
		<tr>
			<th class="all" align="left" colspan="4">IMPORTE TOTAL $valor_moneda</th>
			<th class="all" align="right">$valor_total_con_descuento</th>
		</tr>
	</table>

    <br><br>

	<table cellpadding="1">
		<tr>
			<td width="22%" class="none"><b>OBSERVACION:</b></td>
    		<td>
    		    $valor_nro_nota.$descripcion_venta
			</td>
		</tr>
	</table>

    
	<h1 align="center">NOTAS PAGADAS</h1>
	<br>
    <table cellpadding="5">
		<tr>
			<th width="50%" class="all" align="center">NRO DE NOTA</th>
			<th width="50%" class="all" align="center">MONTO</th>
		</tr>
		$notas_pagadas
		<tr>
			<th class="all" align="left">IMPORTE TOTAL $valor_moneda</th>
			<th class="all" align="right">$valor_total_con_descuento</th>
		</tr>
	</table>

EOD;
	
	// Imprime la tabla
	$pdf->writeHTML($tabla, true, false, false, false, '');
	
	// Genera el nombre del archivo
	$nombre = 'nota_venta_' . $id_ingreso . '_' . date('Y-m-d_H-i-s') . '.pdf';
}

// ------------------------------------------------------------

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>
