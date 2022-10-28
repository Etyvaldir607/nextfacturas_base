<?php 
// echo 'hola'; die();
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
	$egreso = $db->query('select p.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno
						from inv_egresos p
						LEFT join inv_almacenes a ON p.almacen_id = a.id_almacen
						LEFT join sys_empleados e ON p.empleado_id = e.id_empleado
						where p.id_egreso = '.$id_egreso.'
						LIMIT 1')->fetch_first();
	
	// Verifica si existe el egreso
	// if (!$egreso || $egreso['empleado_id'] != $_user['persona_id']) {
	// 	// Error 404
	// 	require_once not_found();
	// 	exit;
	// } elseif (!$permiso_ver) {
	// 	// Error 401
	// 	require_once bad_request();
	// 	exit;
	// }

	// Obtiene los detalles
	$detalles = $db->select('d.*,d.unidad_id as unidad_otra, p.codigo, p.nombre, p.nombre_factura')
				   ->from('inv_egresos_detalles d')
				   ->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')
				   ->where('d.egreso_id', $id_egreso)
				   ->order_by('id_detalle asc')
				   ->fetch();
}
// echo json_encode($egreso); die();
// Obtiene las deudas

$deuda_real = $db->select('monto_total ')
			->from('inv_egresos')
			->where('id_egreso = ', $id_egreso)
			->fetch_first();
			
$deuda = $db->select('sum(monto) as monto_parcial ')
			->from('inv_pagos p')
			->join('inv_pagos_detalles pd', 'pd.pago_id=p.id_pago', 'left')
			->where('p.movimiento_id = ', $id_egreso)
			->where('p.tipo', 'Egreso')
			->where('estado != ', '1')
			->fetch_first();
			
$deuda1 = $db->select('pd.*')
			->from('inv_pagos p')
			->join('inv_pagos_detalles pd', 'pd.pago_id=p.id_pago', 'left')
			->where('p.movimiento_id = ', $id_egreso)
			->where('p.tipo', 'Egreso')
			->where('estado = ', '1')
			->order_by('pd.id_pago_detalle', 'DESC')
			->fetch_first();

$deuda2 = $db->select('pd.*')
			->from('inv_pagos p')
			->join('inv_pagos_detalles pd', 'pd.pago_id=p.id_pago', 'left')
			->where('p.movimiento_id = ', $id_egreso)
			->where('p.tipo', 'Egreso')
			->where('estado = ', '1')
			->where('pd.nro_cuota', ($deuda1['nro_cuota']-1))
			->where('codigo != ', '0')
			->order_by('pd.id_pago_detalle', 'DESC')
			->fetch_first();

$deudaT = $db->select('sum(monto) as monto_pagado')
			->from('inv_pagos p')
			->join('inv_pagos_detalles pd', 'pd.pago_id=p.id_pago', 'left')
			->where('p.movimiento_id = ', $id_egreso)
			->where('p.tipo', 'Egreso')
			->where('estado = ', '1')
			->order_by('pd.id_pago_detalle', 'DESC')
			->fetch_first();
// echo json_encode($deuda2); die();
$deuda_pendiente = number_format($deuda[0]['monto_parcial'], 2, '.', '');

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Importa la libreria para el generado del pdf
require_once libraries . '/tcpdf/tcpdf.php';
require_once libraries . '/tcpdf/tcpdf_barcodes_2d.php';
require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

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
	// $pdf->Cell(0, 10, 'NOTA DE VENTA', 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Salto de linea
	// $pdf->Ln(5);
	
	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', 9);
	
	// Define las variables
	$valor_fecha_factura = explode(" ",$egreso['fecha_factura']);
	$valor_fecha_factura2 = escape(date_decode($valor_fecha_factura[0], $_institution['formato']) . ' ' . $valor_fecha_factura[1]);
	$valor_fecha = escape(date_decode($egreso['fecha_egreso'], $_institution['formato']) . ' ' . $egreso['hora_egreso']);
	$valor_nombre_cliente = escape($egreso['nombre_cliente']);
	$valor_nit_ci = escape($egreso['nit_ci']);
	$valor_nro_egreso = escape($egreso['id_egreso']);
	$valor_monto_total = escape($egreso['monto_total']);
	$valor_nro_registros = escape($egreso['nro_factura']);
	$valor_almacen = escape($egreso['almacen']);
	$valor_empleado = escape($egreso['nombres'] . ' ' . $egreso['paterno'] . ' ' . $egreso['materno']);
	$valor_descuento_global = escape($egreso['descuento_bs']);
	$valor_moneda = $moneda;
	$total = 0;

	// Datos de la empresa
	$valor_logo = (IMAGEN != '') ? institucion . '/' . IMAGEN : imgs . '/empty.jpg' ;
	$valor_empresa = $_institution['nombre'];
	$valor_direccion = $_institution['direccion'];
	$valor_telefono = $_institution['telefono'];
	$valor_pie = $_institution['pie_pagina'];
	$valor_razon = $_institution['razon_social'];
	$valor_nit_empresa = $_institution['nit'];
	$descripcion_venta=escape($egreso['descripcion_venta']);

	// datos factura
	$hoy = date('Y-m-d');
	// Obtiene la dosificacion del periodo actual
	$dosificacion = $db->from('inv_dosificaciones')->where('fecha_registro <=', $hoy)->where('fecha_limite >=', $hoy)->where('activo', 'S')->fetch_first();
	$valor_numero = $egreso['nro_factura'];
	$valor_nro_nota = $egreso['nro_nota'];
	$valor_autorizacion = $egreso['nro_autorizacion'];
    $valor_codigo = $egreso['codigo_control'];
    $valor_limite = date_decode($egreso['fecha_limite'], $_institution['formato']);
    $valor_leyenda = $dosificacion['leyenda'];
	$valor_solo_fecha = date_decode($egreso['fecha_egreso'], 'd/m/Y');
	// Gereramos el codigo de seguridad QR
	$factura_qr = $valor_nit_empresa . '|' . $valor_numero . '|' . $valor_autorizacion . '|' . $valor_solo_fecha . '|' . $valor_total . '|' . $valor_total . '|' . $valor_codigo . '|' . $valor_nit_ci . '|0.00|0.00|0.00|0.00';
    // Instancia el objeto QR
	$objeto = new TCPDF2DBarcode($factura_qr, 'QRCODE,L');
	// Obtiene la imagen QR en modo cadena
	$imagen = $objeto->getBarcodePngData(4, 4, array(30, 30, 30));
	// Crea la imagen a partir de la cadena
	$imagen = imagecreatefromstring($imagen);
	imagejpeg($imagen, storage . '/qr.jpg', 100);
	$qr_imagen = storage . '/qr.jpg';
	

	if($egreso['descuento_porcentaje']!=0){
	$valor_descuento_porcentaje  = utf8_decode($egreso['descuento_porcentaje'].'.00 %');
	}else{
		$valor_descuento_porcentaje  ='';
	}

	// PARA RECIBO DINERO
	$liente = $db->from('inv_clientes')->where('id_cliente', $egreso['cliente_id'])->fetch_first();
	$nom_cl = $liente['cliente'];
	$dir_cl = $liente['direccion'];
	$tel_cl = $liente['telefono'];

	$dia = date('d');
	$mes = date('m');
	$gestion = date('Y');

	$codigo = str_pad($deuda1['codigo'], 5, "0", STR_PAD_LEFT);
	if ($deuda1['tipo_pago'] == 'Efectivo' || $deuda1['tipo_pago'] == 'EFECTIVO' || $deuda1['tipo_pago'] == 'efectivo') {
		$efect_v = 'X';
		$chequ_v = '';
	} else {
		$efect_v = '';
		$chequ_v = $deuda1['nro_pago'];
	}
	
	$nro_n_v = $egreso['nro_factura'];
	$sgrec_v = ($deuda2)?$deuda2['codigo']:''; // numero anterior
	$monto_v = number_format(($deudaT['monto_pagado'] - $deuda2['monto']) ,2 ,',', '.'); // lo que debe; la deuda
	$pagor_v = number_format($deuda1['monto'] ,2 ,',', '.');
	$saldo_v = number_format(($egreso['monto_total'] - $deudaT['monto_pagado']) ,2 ,',', '.'); // monto - pago realizado

	
	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', 8);

	// Estructura la tabla
	$body = '';
	foreach ($detalles as $nro => $detalle) {
		$cantidad = escape($detalle['cantidad']/cantidad_unidad($db,$detalle['producto_id'],$detalle['unidad_otra']));
		$precio = escape($detalle['precio']);
		$descuento = escape($detalle['descuento']);
		$importe = $cantidad * $precio - $descuento;
		$total = $total + $importe;

		$body .= '<tr>';
		$body .= '<td class="left-right">' . ($nro + 1) . '</td>';
		$body .= '<td class="left-right">' . escape($detalle['codigo']) . '</td>';
		$body .= '<td class="left-right">' . escape($detalle['nombre_factura']) .' (LOTE: '. escape($detalle['lote']) .')' . '</td>';
		$body .= '<td class="left-right" align="right">' . $cantidad . ' '.nombre_unidad($db,$detalle['unidad_otra']). '</td>';
		$body .= '<td class="left-right" align="right">' . $precio . '</td>';
		$body .= '<td class="left-right" align="right">' . $descuento . '</td>';
		$body .= '<td class="left-right" align="right">' . number_format($importe, 2, '.', '') . '</td>';
		$body .= '</tr>';
	}
	
	//$valor_total = number_format($total, 2, '.', '');

	$valor_total = number_format($total, 2, '.', '');
	$total_con_descuento=$valor_total-$valor_descuento_global;
	$valor_total_con_descuento = number_format($total_con_descuento, 2, '.', '');
	$body = ($body == '') ? '<tr><td colspan="7" align="center" class="all">Este egreso no tiene detalle, es muy importante que todos las egresos cuenten con un detalle de venta.</td></tr>' : $body;
	
	// Estructura la tabla factura
	$bodyf = '';
	$total = 0;
	foreach ($detalles as $nro => $detalle) {
// 		$cantidad = escape($detalle['cantidad']);
// 		$precio = escape($detalle['precio']);
// 		$descuento = escape($detalle['descuento']);
// 		$importe = $cantidad * $precio;
// 		$total = $total + $importe;

        $cantidad = escape($detalle['cantidad']/cantidad_unidad($db,$detalle['producto_id'],$detalle['unidad_otra']));
		$precio = escape($detalle['precio']);
		$descuento = escape($detalle['descuento']);
		$importe = $cantidad * $precio - $descuento;
		$total = $total + $importe;

		$bodyf .= '<tr>';
		$bodyf .= '<td class="left-right">' . ($nro + 1) . '</td>';
		$bodyf .= '<td class="left-right">' . escape($detalle['codigo']) . '</td>';
		$bodyf .= '<td class="left-right">' . escape($detalle['nombre_factura']).' <br>(LOTE: '. escape($detalle['lote']) .' VENC: '. escape($detalle['vencimiento']) .')' . '</td>';
		$bodyf .= '<td class="left-right" align="right">' . $cantidad . '</td>';
		$bodyf .= '<td class="left-right" align="right">' . $precio . '</td>';
		$bodyf .= '<td class="left-right" align="right">' . number_format($importe, 2, '.', '') . '</td>';
		$bodyf .= '</tr>';
	}
	
	$valor_total = number_format($total, 2, '.', '');
	$bodyf = ($bodyf == '') ? '<tr><td colspan="6" align="center" class="all">Este egreso no tiene detalle, es muy importante que todos las egresos cuenten con un detalle de venta.</td></tr>' : $bodyf;
	///termina estructura de tabla de factura
    // Estructura la tabla
	$bodyv = '';
	foreach ($detalles as $nro => $detalle) {
// 		$cantidad = escape($detalle['cantidad']/cantidad_unidad($db,$detalle['producto_id'],$detalle['unidad_otra']));
// 		$precio = escape($detalle['precio']);
// 		$descuento = escape($detalle['descuento']);
// 		$importe = $cantidad * $precio - $descuento;
// 		$total = $total + $importe;
        $cantidad = escape($detalle['cantidad']/cantidad_unidad($db,$detalle['producto_id'],$detalle['unidad_otra']));
		$precio = escape($detalle['precio']);
		$descuento = escape($detalle['descuento']);
		$importe = $cantidad * $precio - $descuento;
		$total = $total + $importe;

		$bodyv .= '<tr>';
		$bodyv .= '<td class="left-right">' . ($nro + 1) . '</td>';
		$bodyv .= '<td class="left-right">' . escape($detalle['codigo']) . '</td>';
		$bodyv .= '<td class="left-right">' . escape($detalle['nombre_factura']) .' <br>(LOTE: '. escape($detalle['lote']) .' VENC: '. escape(date_decode($detalle['vencimiento'], $_institution['formato'])) .')' . '</td>';
		$bodyv .= '<td class="left-right" align="right">' . $cantidad . ' '.nombre_unidad($db,$detalle['unidad_otra']). '</td>';
		$bodyv .= '<td class="left-right" align="right">' . number_format($precio, 2, ',', '.') . '</td>';
		$bodyv .= '<td class="left-right" align="right">' . number_format($descuento, 2, ',', '.') . '</td>';
		$bodyv .= '<td class="left-right" align="right">' . number_format($importe, 2, ',', '.') . '</td>';
		$bodyv .= '</tr>';
	}
	
	//$valor_total = number_format($total, 2, '.', '');

	$valor_total = number_format($total, 2, '.', '');
	$total_con_descuento=$valor_total-$valor_descuento_global;
	$valor_total_con_descuento = number_format($total_con_descuento, 2, '.', '');
	$bodyv = ($bodyv == '') ? '<tr><td colspan="7" align="center" class="all">Este egreso no tiene detalle, es muy importante que todos las egresos cuenten con un detalle de venta.</td></tr>' : $bodyv;
	//termina estructura de tabla nota de venta
	
	// Obtiene los datos del monto total
	$conversor = new NumberToLetterConverter();
	$monto_textual = explode('.', $deuda1['monto']);
	$monto_numeral = $monto_textual[0];
	$monto_decimal = $monto_textual[1];
	$monto_literal = ucfirst(strtolower(trim($conversor->to_word($monto_numeral))));

	$monto_escrito = $monto_literal . ' ' . $monto_decimal . '/100';


	// Formateamos la tabla
	$tabla = <<<EOD
	<style>
	th {
		background-color: #fff;
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
    .date {
        height: 12px;
        border: 1px solid #f29f5e;
        background-color: #fff;
	}
	.pie {
		border-top: 1px solid #f29f5e;
		font-size: 9px;
	}
	.texto_pie{
		font-size: 6.49px;
		text-align: justify;
  		text-justify: inter-word;
	}
	#cssTable td { vertical-align: middle; }

	</style>
	<table>
		<tr>
            <td width="50%">
                <table>
                    <tr>
                        <td width="30%" class="none" align="left" rowspan="4"><img src="$valor_logo" width="70"></td>
                        <td width="70%" class="none" align="center"><h3>RECIBO DE DINERO</h3><b>$valor_empresa</b><br><br><b>$valor_direccion</b><br><br><b>Teléfono: $valor_telefono</b></td>
                    </tr>
                </table>
            </td>
            <td width="50%" align="center">
                <span style="font-size: 14px; color: red; "><b>Nº  $codigo</b></span><br>
                <table style="padding-top: 3px;">
                    <tr>
                        <th width="30%" class="date"><br>DIA</th>
                        <th width="35%" class="date"><br>MES</th>
                        <th width="35%" class="date"><br>AÑO</th>
                    </tr>
                    <tr>
                        <th class="date">$dia</th>
                        <th class="date">$mes</th>
                        <th class="date">$gestion</th>
                    </tr>
                </table>
				<h3>COD. VENDEDOR Nº $valor_empleado</h3>
            </td>
		</tr>
	</table>
	<br><br>
	<table cellpadding="1">
		<tr>
			<td width="8%" class="none"><b>NOMBRE:</b></td>
			<td width="30%" class="none">$nom_cl</td>
			<td width="5%" class="none"><b>DIR:</b></td>
			<td width="37%" class="none">$dir_cl</td>
			<td width="8%" class="none"><b>TEL:</b></td>
			<td width="22%" class="none">$tel_cl</td>
		</tr>
	</table>
	
	<br><br>
	<table cellpadding="5">
		<tr>
			<th width="10%" class="date" align="center" rowspan="2">Efect.</th>
			<th width="15%" class="date" align="center" rowspan="2">Cheque/Bco/Nº</th>
			<th width="15%" class="date" align="center" rowspan="2">Numero de nota</th>
			<th width="30%" class="date" align="center" colspan="2">Monto Adeudado</th>
			<th width="15%" class="date" align="center" rowspan="2">Pago realizado</th>
			<th width="15%" class="date" align="center" rowspan="2">Saldo</th>
		</tr>
		<tr>
			<th class="date" align="center">Sg. recibo #</th>
			<th class="date" align="center">Monto</th>
		</tr>
		<tr>
			<th class="date" align="center">$efect_v</th>
			<th class="date">$chequ_v</th>
			<th class="date">$nro_n_v</th>
			<th class="date">$sgrec_v</th>
			<th class="date" align="right">$monto_v</th>
			<th class="date" align="right">$pagor_v</th>
			<th class="date" align="right">$saldo_v</th>
		</tr>
	</table>
	<h3>SON: $monto_escrito Bolivianos</h3>
	<table>
		<tr>
			<th width="10%" align="center"></th>
			<th width="30%" align="center">
				<br>
				<span><h3 class="pie" >Recibí conforme <br> Nombre, firma y sello</h3></span>
			</th>
			<th width="10%" align="center"></th>
			<th width="10%" align="center"></th>
			<th width="30%" align="center">
				<br>
				<span><h3 class="pie" >Entregué conforme <br> Nombre, firma y sello</h3></span>
			</th>
			<th width="10%" align="center"></th>
		</tr>
	</table>
	<table>
		<tr>
			<td width="33%" class="texto_pie" align="left">ORIGINAL: CLIENTE</td>
			<td width="33%" class="texto_pie" align="center">1ª COPIA: FINANZAS</td>
			<td width="34%" class="texto_pie" align="right">2ª COPIA: CONTABILIDAD</td>
		</tr>
	</table>
	<span class="texto_pie" align="justify">NEXTCORP S.R.L. NO SE HACE RESPONSABLE DE NINGUN DINERO ENTREGADO AL VENDEDOR/COBRADOR SIN EXIGIR EL RECIBO DEBIDAMENTE FIRMADO Y SELLADO</span>
	<div style="border-width: 1px; border-style: dashed; border-color: black; "></div>
	<table>
		<tr>
            <td width="50%">
                <table>
                    <tr>
                        <td width="30%" class="none" align="left" rowspan="4"><img src="$valor_logo" width="70"></td>
                        <td width="70%" class="none" align="center"><h3>RECIBO DE DINERO</h3><b>$valor_empresa</b><br><br><b>$valor_direccion</b><br><br><b>Teléfono: $valor_telefono</b></td>
                    </tr>
                </table>
            </td>
            <td width="50%" align="center">
                <span style="font-size: 14px; color: red; "><b>Nº  $codigo</b></span><br>
                <table style="padding-top: 3px;">
                    <tr>
                        <th width="30%" class="date"><br>DIA</th>
                        <th width="35%" class="date"><br>MES</th>
                        <th width="35%" class="date"><br>AÑO</th>
                    </tr>
                    <tr>
                        <th class="date">$dia</th>
                        <th class="date">$mes</th>
                        <th class="date">$gestion</th>
                    </tr>
                </table>
				<h3>COD. VENDEDOR Nº $valor_empleado</h3>
            </td>
		</tr>
	</table>
	<br><br>
	<table cellpadding="1">
		<tr>
			<td width="8%" class="none"><b>NOMBRE:</b></td>
			<td width="30%" class="none">$nom_cl</td>
			<td width="5%" class="none"><b>DIR:</b></td>
			<td width="37%" class="none">$dir_cl</td>
			<td width="8%" class="none"><b>TEL:</b></td>
			<td width="22%" class="none">$tel_cl</td>
		</tr>
	</table>
	
	<br><br>
	<table cellpadding="5">
		<tr>
			<th width="10%" class="date" align="center" rowspan="2">Efect.</th>
			<th width="15%" class="date" align="center" rowspan="2">Cheque/Bco/Nº</th>
			<th width="15%" class="date" align="center" rowspan="2">Numero de nota</th>
			<th width="30%" class="date" align="center" colspan="2">Monto Adeudado</th>
			<th width="15%" class="date" align="center" rowspan="2">Pago realizado</th>
			<th width="15%" class="date" align="center" rowspan="2">Saldo</th>
		</tr>
		<tr>
			<th class="date" align="center">Sg. recibo #</th>
			<th class="date" align="center">Monto</th>
		</tr>
		<tr>
			<th class="date" align="center">$efect_v</th>
			<th class="date">$chequ_v</th>
			<th class="date">$nro_n_v</th>
			<th class="date">$sgrec_v</th>
			<th class="date" align="right">$monto_v</th>
			<th class="date" align="right">$pagor_v</th>
			<th class="date" align="right">$saldo_v</th>
		</tr>
	</table>
	<h3>SON: $monto_escrito Bolivianos</h3>
	<table>
		<tr>
			<th width="10%" align="center"></th>
			<th width="30%" align="center">
				<br>
				<span><h3 class="pie" >Recibí conforme <br> Nombre, firma y sello</h3></span>
			</th>
			<th width="10%" align="center"></th>
			<th width="10%" align="center"></th>
			<th width="30%" align="center">
				<br>
				<span><h3 class="pie" >Entregué conforme <br> Nombre, firma y sello</h3></span>
			</th>
			<th width="10%" align="center"></th>
		</tr>
	</table>
	<table>
		<tr>
			<td width="33%" class="texto_pie" align="left">ORIGINAL: CLIENTE</td>
			<td width="33%" class="texto_pie" align="center">1ª COPIA: FINANZAS</td>
			<td width="34%" class="texto_pie" align="right">2ª COPIA: CONTABILIDAD</td>
		</tr>
	</table>
	<span class="texto_pie" align="justify">NEXTCORP S.R.L. NO SE HACE RESPONSABLE DE NINGUN DINERO ENTREGADO AL VENDEDOR/COBRADOR SIN EXIGIR EL RECIBO DEBIDAMENTE FIRMADO Y SELLADO</span>
	<div style="border-width: 1px; border-style: dashed; border-color: black; "></div>
	<table>
		<tr>
            <td width="50%">
                <table>
                    <tr>
                        <td width="30%" class="none" align="left" rowspan="4"><img src="$valor_logo" width="70"></td>
                        <td width="70%" class="none" align="center"><h3>RECIBO DE DINERO</h3><b>$valor_empresa</b><br><br><b>$valor_direccion</b><br><br><b>Teléfono: $valor_telefono</b></td>
                    </tr>
                </table>
            </td>
            <td width="50%" align="center">
                <span style="font-size: 14px; color: red; "><b>Nº  $codigo</b></span><br>
                <table style="padding-top: 3px;">
                    <tr>
                        <th width="30%" class="date"><br>DIA</th>
                        <th width="35%" class="date"><br>MES</th>
                        <th width="35%" class="date"><br>AÑO</th>
                    </tr>
                    <tr>
                        <th class="date">$dia</th>
                        <th class="date">$mes</th>
                        <th class="date">$gestion</th>
                    </tr>
                </table>
				<h3>COD. VENDEDOR Nº $valor_empleado</h3>
            </td>
		</tr>
	</table>
	<br><br>
	<table cellpadding="1">
		<tr>
			<td width="8%" class="none"><b>NOMBRE:</b></td>
			<td width="30%" class="none">$nom_cl</td>
			<td width="5%" class="none"><b>DIR:</b></td>
			<td width="37%" class="none">$dir_cl</td>
			<td width="8%" class="none"><b>TEL:</b></td>
			<td width="22%" class="none">$tel_cl</td>
		</tr>
	</table>
	
	<br><br>
	<table cellpadding="5">
		<tr>
			<th width="10%" class="date" align="center" rowspan="2">Efect.</th>
			<th width="15%" class="date" align="center" rowspan="2">Cheque/Bco/Nº</th>
			<th width="15%" class="date" align="center" rowspan="2">Numero de nota</th>
			<th width="30%" class="date" align="center" colspan="2">Monto Adeudado</th>
			<th width="15%" class="date" align="center" rowspan="2">Pago realizado</th>
			<th width="15%" class="date" align="center" rowspan="2">Saldo</th>
		</tr>
		<tr>
			<th class="date" align="center">Sg. recibo #</th>
			<th class="date" align="center">Monto</th>
		</tr>
		<tr>
			<th class="date" align="center">$efect_v</th>
			<th class="date">$chequ_v</th>
			<th class="date">$nro_n_v</th>
			<th class="date">$sgrec_v</th>
			<th class="date" align="right">$monto_v</th>
			<th class="date" align="right">$pagor_v</th>
			<th class="date" align="right">$saldo_v</th>
		</tr>
	</table>
	<h3>SON: $monto_escrito Bolivianos</h3>
	<table>
		<tr>
			<th width="10%" align="center"></th>
			<th width="30%" align="center">
				<br>
				<span><h3 class="pie" >Recibí conforme <br> Nombre, firma y sello</h3></span>
			</th>
			<th width="10%" align="center"></th>
			<th width="10%" align="center"></th>
			<th width="30%" align="center">
				<br>
				<span><h3 class="pie" >Entregué conforme <br> Nombre, firma y sello</h3></span>
			</th>
			<th width="10%" align="center"></th>
		</tr>
	</table>
	<table>
		<tr>
			<td width="33%" class="texto_pie" align="left">ORIGINAL: CLIENTE</td>
			<td width="33%" class="texto_pie" align="center">1ª COPIA: FINANZAS</td>
			<td width="34%" class="texto_pie" align="right">2ª COPIA: CONTABILIDAD</td>
		</tr>
	</table>
	<span class="texto_pie" align="justify">NEXTCORP S.R.L. NO SE HACE RESPONSABLE DE NINGUN DINERO ENTREGADO AL VENDEDOR/COBRADOR SIN EXIGIR EL RECIBO DEBIDAMENTE FIRMADO Y SELLADO</span>
	<div style="border-width: 1px; border-style: dashed; border-color: black; "></div>
EOD;

	
/////////////termina recibo////////////////
/////////////factura//////////////////////
   
	$valor_total = number_format($pagor_v, 2, '.', '');// monto total del recibo
    $monto_textual = explode('.', $valor_total);
	$monto_numeral = $monto_textual[0];
	$monto_decimal = $monto_textual[1];
	$monto_literal = ucfirst(strtolower(trim($conversor->to_word($monto_numeral))));
	$monto_escrito = $monto_literal . ' ' . $monto_decimal . '/100';
	
$tabla_factura = <<<EOD
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
		height: 15px;
	}
	.all {
		border: 1px solid #f29f5e;
		background-color: #f5af76;
	}
	.orange {color: #fb8b30;}
	</style>
	<table cellpadding="1">
		<tr>
			<td width="20%" class="none" align="left" rowspan="4">
				<img src="$valor_logo" width="100">
			</td>
			<td width="30%" class="none" align="left">$valor_empresa</td>
			<td width="35%" class="none" align="right"><b>NIT:</b></td>
			<td width="15%" class="none" align="right">$valor_nit_empresa</td>
		</tr>
		<tr>
			<td class="none" align="left">$valor_direccion</td>
			<td class="none" align="right"><b>NRO. FACTURA:</b></td>
			<td class="none" align="right">$valor_numero</td>
		</tr>
		<tr>
			<td class="none" align="left">Teléfono: $valor_telefono</td>
			<td class="none" align="right"><b>NRO. AUTORIZACIÓN:</b></td>
			<td class="none" align="right">$valor_autorizacion</td>
		</tr>
		<tr>
			<td class="none" align="left">$valor_pie</td>
			<td class="none" align="right" colspan="2">$valor_razon</td>
		</tr>
	</table>
	<h1 align="center">FACTURA</h1>
	<table cellpadding="1">
		<tr>
			<td width="20%" class="none"><b>FECHA Y HORA:</b></td>
			<td width="80%" class="none">$valor_fecha_factura2</td>
		</tr>
		<tr>
			<td class="none"><b>SEÑOR(ES):</b></td>
			<td class="none">$valor_nombre_cliente</td>
		</tr>
		<tr>
			<td class="none"><b>NIT / CI:</b></td>
			<td class="none">$valor_nit_ci</td>
		</tr>
	</table>
	<br><br>
	<table cellpadding="5">
		<tr>
			<th width="5%" class="all" align="center">#</th>
			<th width="12%" class="all" align="center">CÓDIGO</th>
			<th width="47%" class="all" align="center">NOMBRE</th>
			<th width="12%" class="all" align="center">CANTIDAD</th>
			<th width="12%" class="all" align="center">PRECIO</th>
			<th width="12%" class="all" align="center">IMPORTE</th>
		</tr>
		$bodyf
		<tr>
			<th class="all" align="left" colspan="5">SON: $monto_escrito</th>
			<th class="all" align="right">$valor_total</th>
		</tr>
	</table>
	<br><br>
	<table cellpadding="1">
		<tr>
			<td width="20%" class="none"><b>Código de control:</b></td>
			<td width="30%" class="none">$valor_codigo</td>
			<td width="50%" class="none" rowspan="2" align="right">
				<img src="$qr_imagen" width="80">
			</td>
		</tr>
		<tr>
			<td class="none"><b>Fecha límite de emisión:</b></td>
			<td class="none">$valor_limite</td>
		</tr>
	</table>
	<h4 align="center" class="orange">"ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS. EL USO ILÍCITO DE ÉSTA SERÁ SANCIONADO DE ACUERDO A LEY"</h4>
	<div align="center" class="orange"><b>Ley Nº 453:</b> "$valor_leyenda".</div>
EOD;
/////////////termina factura//////////////////
/////////////nota venta//////////////////////

    $valor_total = number_format($total, 2, '.', '');
	$total_con_descuento=$valor_total-$valor_descuento_global;
	$valor_total_con_descuento = number_format($total_con_descuento, 2, '.', '');
	$bodyv = ($bodyv == '') ? '<tr><td colspan="7" align="center" class="all">Este egreso no tiene detalle, es muy importante que todos las egresos cuenten con un detalle de venta.</td></tr>' : $bodyv;
	
	$valor_totalx=number_format($valor_total, 2, ',', '.');
	$valor_descuento_porcentajex=number_format($valor_descuento_porcentaje, 2, ',', '.');
	$valor_descuento_globalx=number_format($valor_descuento_global, 2, ',', '.');
    $valor_total_con_descuento = $pagor_v;// monto total del recibo
	$valor_total_con_descuentox=number_format($valor_total_con_descuento, 2, ',', '.');
	
$tabla_nota_venta = <<<EOD
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
			<td width="26%" class="none" align="left">$valor_empresa</td>
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
			<td class="none" align="right">$valor_nro_nota</td>
		</tr>
		<tr>
			<td class="none" align="left"></td>
			<td class="none" align="right" colspan="2"></td>
		</tr>
	</table>
	<h1 align="center">NOTA DE VENTA</h1>
	<br>

	<table cellpadding="1">
		<tr>
			<td width="22%" class="none"><b>LUGAR, FECHA Y HORA:</b></td>
			<td width="28%" class="none">LA PAZ, $valor_fecha</td>
		</tr>
		<tr>
			<td class="none"><b>SEÑOR(ES):</b></td>
			<td class="none">$valor_nombre_cliente</td>
		</tr>
		<tr>
			<td class="none"><b>NIT / CI:</b></td>
			<td class="none">$valor_nit_ci</td>
		</tr>
	</table>
	
	<br><br>
	<table cellpadding="5">
		<tr>
			<th width="5%" class="all" align="center">#</th>
			<th width="12%" class="all" align="center">CÓDIGO</th>
			<th width="35%" class="all" align="center">NOMBRE</th>
			<th width="12%" class="all" align="center">CANTIDAD</th>
			<th width="12%" class="all" align="center">PRECIO $valor_moneda</th>
			<th width="12%" class="all" align="center">DESCUENTO $valor_moneda</th>
			<th width="12%" class="all" align="center">IMPORTE $valor_moneda</th>
		</tr>
		$bodyv
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
	
    <br><br>

	<table cellpadding="1">
		<tr>
			<td width="22%" class="none"><b>OBSERVACION:</b></td>
			<td width="28%" class="none">$descripcion_venta</td>
		</tr>
	</table>
EOD;
/////////////termina nota venta///////////////

	// Imprime la tabla
	$pdf->writeHTML($tabla, true, false, false, false, '');
	$pdf->AddPage();
	$pdf->writeHTML($tabla_factura, true, false, false, false, '');
	$pdf->AddPage();
	$pdf->writeHTML($tabla_nota_venta, true, false, false, false, '');
	
	// Genera el nombre del archivo
	$nombre = 'documentos' . $id_egreso . '_' . date('Y-m-d_H-i-s') . '.pdf';
}

// ------------------------------------------------------------

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>
