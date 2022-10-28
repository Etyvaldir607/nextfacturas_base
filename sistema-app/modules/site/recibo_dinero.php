<?php
$id_detalle_pago = (isset($params[0])) ? $params[0] : 0;
$id_empleado = (isset($params[1])) ? $params[1] : 0;

require config . '/database.php';

if ($id_detalle_pago == 0) {
	require_once not_found();
 	exit;
}else{

    $deuda1 = $db->select('pd.*,movimiento_id, em.*, pd.codigo as codigoo')
        			->from('inv_pagos p')
        			->join('inv_pagos_detalles pd', 'pd.pago_id=p.id_pago', 'inner')
        			->join('sys_empleados em', 'pd.empleado_id=em.id_empleado', 'inner')
        			->where('pd.id_pago_detalle = ', $id_detalle_pago)
        			->where('p.tipo', 'Egreso')
        			->fetch_first();
        
    $id_egreso = $deuda1['movimiento_id'];
    
	// Obtiene el egreso
	$egreso = $db->query('select p.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno,cg.nombre_grupo
						from inv_egresos p
						LEFT join inv_almacenes a ON p.almacen_id = a.id_almacen
						LEFT join sys_empleados e ON p.empleado_id = p.empleado_id
					    left join inv_clientes_grupos cg ON cg.vendedor_id = p.vendedor_id
						where p.id_egreso = '.$id_egreso.'
						LIMIT 1
						')->fetch_first();
}

$deuda = $db->select('sum(monto) as monto_parcial ')
			->from('inv_pagos p')
			->join('inv_pagos_detalles pd', 'pd.pago_id=p.id_pago', 'left')
			->where('p.movimiento_id = ', $id_egreso)
			->where('p.tipo', 'Egreso')
			->where('estado != ', '1')
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

$deudaT = $db->query("select sum(monto) as monto_pagado_hasta_antes
        			from inv_pagos p
        			inner join inv_pagos_detalles pd ON pd.pago_id=p.id_pago
        			where p.movimiento_id = '".$id_egreso."'
                	    AND p.tipo='Egreso'
        			    AND estado = 1
        			    AND codigo<(SELECT codigo
        			                FROM inv_pagos_detalles
        			                WHERE id_pago_detalle = '".$id_detalle_pago."'
        			               )
        			")
			->fetch_first();


$Nombre_distribuidor=$deuda1['paterno']." ".$deuda1['materno']." ".$deuda1['nombres'];

$deuda_pendiente = number_format($deuda[0]['monto_parcial'], 2, '.', '');

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Importa la libreria para el generado del pdf
require_once libraries . '/tcpdf/tcpdf.php';
// Importa la libreria para convertir el numero a letra
require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';


// Operaciones con la imagen del header
list($ancho_header, $alto_header) = getimagesize(imgs . '/anulado.jpg');
$relacion = $alto_header / $ancho_header;
$ancho_header = 612;
$alto_header = round($ancho_header * $relacion);
define('alto_header', $alto_header);
define('ancho_header', $ancho_header);

// Define variables globales
define('DIRECCION', escape($_institution['pie_pagina']));
define('IMAGEN', escape($_institution['imagen_encabezado']));
define('ATENCION', 'Lun. a Vie. de 08:30 a 18:30 y Sáb. de 08:30 a 13:00');
define('PIE', escape($_institution['pie_pagina']));
define('TELEFONO', escape(str_replace(',', ', ', $_institution['telefono'])));

// Extiende la clase TCPDF para crear Header y Footer
class MYPDF extends TCPDF {
	public function Header() {
		$this->Image(imgs . '/anulado.jpg', 0, 50, 710, 710);
		$this->Ln(50);
		$this->SetFont(PDF_FONT_NAME_HEAD, 'I', PDF_FONT_SIZE_HEAD);
	}
	public function Footer() {
		$this->Image(imgs . '/footer.jpg', 0, 698, ancho_footer, alto_footer);
	}
}

// Instancia el documento PDF
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
if($deuda1['estado']==1){
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
}

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
	$valor_fecha = escape(date_decode($egreso['fecha_egreso'], $_institution['formato']) . ' ' . $egreso['hora_egreso']);
	$valor_nombre_cliente = escape($egreso['nombre_cliente']);
	$valor_nit_ci = escape($egreso['nit_ci']);
// 	$valor_nro_egreso = escape($egreso['id_egreso']);
// 	$valor_nro_nota = escape($egreso['nro_nota']);
	$valor_monto_total = escape($egreso['monto_total']);
	$valor_nro_registros = escape($egreso['nro_factura']);
	$valor_almacen = escape($egreso['almacen']);
	$valor_empleado = escape($egreso['nombres'] . ' ' . $egreso['paterno'] . ' ' . $egreso['materno']);
	$valor_empleado = escape($egreso['nombre_grupo']);
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

    $deuda111=explode("-",$deuda1['fecha_pago']);
	$dia = $deuda111[2];
	$mes = $deuda111[1];
	$gestion = $deuda111[0];

	$codigo = str_pad($deuda1['codigoo'], 5, "0", STR_PAD_LEFT);
	if ($deuda1['tipo_pago'] == 'Efectivo' || $deuda1['tipo_pago'] == 'EFECTIVO' || $deuda1['tipo_pago'] == 'efectivo') {
		$efect_v = 'X';
		$chequ_v = '';
	} else {
		$efect_v = '';
		$chequ_v = $deuda1['nro_pago'];
	}
	
	$nro_n_v = $egreso['nro_nota'];
	$sgrec_v = ($deuda2)?$deuda2['codigo']:''; // numero anterior
	$monto_v = number_format(($egreso['monto_total'] - $deudaT['monto_pagado_hasta_antes']) ,2 ,',', '.'); // lo que debe; la deuda
	$pagor_v = number_format($deuda1['monto'] ,2 ,',', '.');
	$saldo_v = number_format(($egreso['monto_total'] - $deudaT['monto_pagado_hasta_antes'] - $deuda1['monto']) ,2 ,',', '.'); // monto - pago realizado

	
	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', 8);

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
			<th width="7%" align="center"></th>
			<th width="36%" align="center">
				<br>
				<span><h3 class="pie" >Recibí conforme <br>$Nombre_distribuidor</h3></span>
			</th>
			<th width="7%" align="center"></th>
			<th width="7%" align="center"></th>
			<th width="36%" align="center">
				<br>
				<span><h3 class="pie" >Entregué conforme <br>Nombre, firma y sello</h3></span>
			</th>
			<th width="7%" align="center"></th>
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
	
	// Imprime la tabla
	$pdf->writeHTML($tabla, true, false, false, false, '');
	
	// Genera el nombre del archivo
	$nombre = 'nota_venta_' . $id_egreso . '_' . date('Y-m-d_H-i-s') . '.pdf';
}

// ------------------------------------------------------------

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>
