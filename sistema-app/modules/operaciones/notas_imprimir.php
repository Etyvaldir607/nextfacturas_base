<?php 

// Obtiene el id_egreso
$id_egreso = (isset($params[0])) ? $params[0] : 0;


if ($id_egreso == 0) {
	// Obtiene las egresos
	$egresos =   $db->select('p.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno, v.nombres nombresv, v.paterno paternov, v.materno maternov,nombre_grupo')
					->from('inv_egresos p')
					->join('inv_almacenes a', 'p.almacen_id = a.id_almacen', 'left')
					->join('sys_empleados e', 'p.empleado_id = e.id_empleado', 'left')
					->join('sys_empleados v', 'p.vendedor_id = v.id_empleado', 'left')
					->join('inv_clientes_grupos', 'id_cliente_grupo = codigo_vendedor', 'left')
					->where('p.empleado_id', $_user['persona_id'])
					->order_by('p.fecha_egreso desc, p.hora_egreso desc')
					->fetch();
} else {
	// Obtiene los permisos
	$permisos = explode(',', permits);
	
	// Almacena los permisos en variables
	$permiso_ver = in_array('ver', $permisos);
	
	// Obtiene la egreso
	$egreso =  $db->select('p.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno, c.direccion, c.telefono, v.nombres nombresv, v.paterno paternov, v.materno maternov,nombre_grupo')
				  ->from('inv_egresos p')
				  ->join('inv_almacenes a', 'p.almacen_id = a.id_almacen', 'left')
				  ->join('sys_empleados e', 'p.empleado_id = e.id_empleado', 'left')
				  ->join('sys_empleados v', 'p.vendedor_id = v.id_empleado', 'left')
				  ->join('inv_clientes c', 'p.cliente_id = c.id_cliente', 'left')
				  ->join('inv_clientes_grupos', 'id_cliente_grupo = codigo_vendedor', 'left')
					->where('p.id_egreso', $id_egreso)
				  ->fetch_first();
	
	// Verifica si existe el egreso
	//if (!$egreso || $egreso['empleado_id'] != $_user['persona_id']) {
	/*if (!$egreso) {
		// Error 404
		require_once not_found();
		exit;
	} elseif (false) {
		// Error 401
		require_once bad_request();
		exit;
	}*/

    $a_cuenta = $db->select('monto')
                    ->from('inv_pagos p')
                    ->join('inv_pagos_detalles pd', 'pd.pago_id=p.id_pago', 'left')
        			->where('p.movimiento_id = ', $id_egreso)
        			->where('p.tipo = ', "Egreso")
        			->where('estado = ', '1')
        			->where('fecha_pago', $egreso['fecha_egreso'])
        			->fetch_first();
    $valor_a_cuenta = number_format($a_cuenta['monto'], 2, ',', '.');
    
	// Obtiene los detalles
	$detalles = $db->select('d.*,d.unidad_id as unidad_otra, p.codigo, p.nombre, p.nombre_factura')
				   ->from('inv_egresos_detalles d')
				   ->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')
				   ->where('d.egreso_id', $id_egreso)
				   ->order_by('id_detalle asc')
				   ->fetch();
}

// Obtiene las deudas

$id_cli= $db->select('cliente_id')->from('inv_egresos')->where('id_egreso=',$id_egreso)->fetch_first();
$id_cliente = $id_cli['cliente_id'];

$deudas = $db->select('sum(monto) as monto_parcial,e.id_egreso')
			->from('inv_pagos p')
			->join('inv_pagos_detalles pd', 'pd.pago_id=p.id_pago', 'left')
			->join('inv_egresos e', 'e.id_egreso=p.movimiento_id', 'left')
			//->where('p.movimiento_id = ', $id_egreso)
			->where('e.preventa != ', 'habilitado')
			->where('pd.estado != ', '1')
			->where('e.cliente_id=',$id_cliente)
			->group_by('e.id_egreso')
			->fetch();
$deuda_pendiente='';
$cont=1;

foreach ($deudas as $deuda) {
	$deuda_pendiente .= 'D'.$cont.': '.number_format($deuda['monto_parcial'],2,',','.').' <br> ';
	$cont++;
}

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
class MYPDF extends TCPDF {}

// Instancia el documento PDF
$pdf = new MYPDF('p', 'pt', PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Asigna la informacion al documento
$pdf->SetCreator(name_autor);
$pdf->SetAuthor(name_autor);
$pdf->SetTitle($_institution['nombre']);
$pdf->SetSubject($_institution['propietario']);
$pdf->SetKeywords($_institution['sigla']);

// Asignamos margenes
$pdf->SetMargins(30, 30, 30);

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

	// Salto de linea
	$pdf->Ln(5);

	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', 8);

	// Define las variables
	$valor_fecha = escape(date_decode($egreso['fecha_egreso'], $_institution['formato']) . ' ' . $egreso['hora_egreso']);
	$valor_nombre_cliente = escape($egreso['nombre_cliente']);
	$valor_codigo_vendedor = escape($egreso['codigo_vendedor']);
	$valor_codigo_vendedor = escape($egreso['nombre_grupo']);
	$valor_nit_ci = escape($egreso['nit_ci']);
	$valor_direccion = escape($egreso['direccion']);
	$valor_telefono = escape($egreso['telefono']);
	$valor_nro_egreso = escape($egreso['nro_egreso']);
	$valor_monto_total = escape($egreso['monto_total']);
	$valor_nro_registros = escape($egreso['nro_nota']);
	$valor_almacen = escape($egreso['almacen']);
	$valor_empleado = escape($egreso['nombres'] . ' ' . $egreso['paterno'] . ' ' . $egreso['materno']);
	$valor_vendedor = escape($egreso['nombresv'] . ' ' . $egreso['paternov'] . ' ' . $egreso['maternov']);
	$valor_descuento_global = escape($egreso['descuento_bs']);
	$valor_moneda = $moneda;
	$descripcion_venta=escape($egreso['descripcion_venta']);
	
	$total = 0;

	$valor_logo = (imagen != '') ? institucion . '/' . IMAGEN : imgs . '/empty.jpg';
	$valor_empresa = $_institution['nombre'];
	$valor_direccion = $_institution['direccion'];
	$valor_telefono = $_institution['telefono'];
	$valor_pie = $_institution['pie_pagina'];
	$valor_razon = $_institution['razon_social'];

	$valor_nit_empresa = $_institution['nit'];
	$valor_autorizacion = $egreso['nro_autorizacion'];
	$valor_codigo = $egreso['codigo_control'];
	$valor_numero = $egreso['nro_factura'];
	$valor_limite = date_decode($egreso['fecha_limite'], $_institution['formato']);


    if($egreso['descuento_porcentaje']!=0){
	$valor_descuento_porcentaje  = utf8_decode($egreso['descuento_porcentaje'].'.00 %');
	}else{
		$valor_descuento_porcentaje  ='';
	}

	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', 7);

	// Estructura la tabla
	$body = '';
	foreach ($detalles as $nro => $detalle) {
		$cantidad = escape($detalle['cantidad']/cantidad_unidad($db,$detalle['producto_id'],$detalle['unidad_otra']));
		$precio = escape($detalle['precio']);
		$descuento = escape($detalle['descuento']);
		$importe = $cantidad * $precio - $descuento;
		$total = $total + $importe;

		$body .= '<tr>';
		$body .= '<td width="16%" class="left-right">' . escape($detalle['codigo']) . '</td>';
		$body .= '<td width="59%" class="left-right">' . escape($detalle['nombre_factura']) .' (LOTE: '. escape($detalle['lote']) .' VENC: '. escape(date_decode($detalle['vencimiento'], $_institution['formato'])) .')' . '</td>';
		$body .= '<td width="5%" class="left-right" align="right">' . $cantidad . '</td>';
		$body .= '<td width="10%" class="left-right" align="right">' . number_format($precio, 2, ',', '.') . '</td>';
		//$body .= '<td width="10%" class="left-right" align="right">' . number_format($descuento, 2, ',', '.') . '</td>';
		$body .= '<td width="10%" class="left-right" align="right">' . number_format($importe, 2, ',', '.') . '</td>';
		$body .= '</tr>';
	}
	for($i=$nro;$i<=15;$i++){
		$body .= '<tr>';
		$body .= '<td>-</td>';
		$body .= '</tr>';
	}
	//$valor_total = number_format($total, 2, ',', '.');

	$valor_total = number_format($total, 2, '.', '');
	$total_con_descuento=$valor_total-$valor_descuento_global;
	$valor_total_con_descuento = number_format($total_con_descuento, 2, ',', '.');
	$body = ($body == '') ? '<tr><td colspan="7" align="center" class="all">Este egreso no tiene detalle, es muy importante que todos las egresos cuenten con un detalle de venta.</td></tr>' : $body;
	
	
	// Obtiene los datos del monto total
	$conversor = new NumberToLetterConverter();
	$monto_textual = explode('.', $valor_total);
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
		border-left: 0px solid #ffffff;
		border-right: 0px solid #ffffff;
	}
	.none {
		border: 0px solid #fff;
	}
	#cssTable td { vertical-align: middle; }
	</style>
	
	<table cellpadding="12">
		<tr><td></td></tr>
	</table>
	
	<table cellpadding="0" id="cssTable">
		<tr>
			<td width="34%" class="none" align="left"></td>
			<td width="25%" class="none" align="right">$valor_nro_registros</td>
			<td width="25%" class="none" align="right">$valor_fecha</td>
		</tr>
		<tr>
			<td class="none" align="left"></td>
			<td width="25%" class="none" align="right"><b></b></td>
			<td width="25%" class="none" align="right"></td>
		</tr>
		<tr>
			<td class="none" align="left"></td>
			<td class="none" align="right"><b></b></td>
			<td class="none" align="right"></td>
		</tr>
		
	</table>
	<table cellpadding="2">
		<tr>
			<td width="16%" class="none"><b></b></td>
			<td width="64%" class="none">$valor_nombre_cliente</td>
			<td width="20%" class="none">$valor_telefono</td>
		</tr>
		<tr>
			<td width="16%" class="none">.</td>
			<td width="64%" class="none"></td>
			<td width="20%" class="none"></td>
		</tr>
		<tr>
			<td width="16%" class="none">.</td>
			<td width="64%" class="none"></td>
			<td width="20%" class="none"></td>
		</tr>
		<tr>
			<td class="none"><b></b></td>
			<td class="none">$valor_direccion</td>
			<td width="20%" class="none">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $valor_codigo_vendedor</td>
		</tr>
	</table>

	<table cellpadding="15">
		<tr><td></td></tr>
	</table>

    <table cellpadding="1">
		$body
	</table>
	
	<table cellpadding="4">
		<tr>
			<td width="12%" class="none"><b></b></td>
			<td width="78%" class="none"></td>
			<td width="10%" align="right">$valor_total</td>
		</tr>
		<tr>
			<td width="12%" class="none"><b></b></td>
			<td width="78%" class="none">$monto_escrito</td>
			<td width="10%" align="right">$valor_descuento_global</td>
		</tr>
		<tr>
			<td width="12%" class="none"><b></b></td>
			<td width="78%" class="none">$descripcion_venta</td>
			<td width="10%" align="right">$valor_total_con_descuento</td>
		</tr>
	</table>

	<br><br>


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
