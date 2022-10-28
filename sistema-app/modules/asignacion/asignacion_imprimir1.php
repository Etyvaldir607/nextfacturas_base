<?php

// Obtiene el orden de compra
$distribuidor = (isset($params[0])) ? $params[0] : 0;
// $id_emp = $id_orden;
// Obtiene fecha inicial
$fecha_inicial = (isset($params[1])) ? $params[1] : date('Y-m-d');
$fecha_inicial = date_encode($fecha_inicial);
// Obtiene fecha final
$fecha_final = (isset($params[2])) ? $params[2] : date('Y-m-d');
$fecha_final = date_encode($fecha_final);

// Obtiene los empleados
$empleados = $db->query('select e.*, CONCAT(em.nombres," ", em.paterno," ", em.materno) as empleado, cl.cliente, cl.direccion, al.almacen
                        from inv_asignaciones_clientes ac
                        left join inv_egresos e ON ac.egreso_id = e.id_egreso
                        left join inv_clientes cl on e.cliente_id = cl.id_cliente
                        left join sys_empleados em on e.empleado_id = em.id_empleado
                        left join inv_almacenes al on e.almacen_id = al.id_almacen
                        where e.estadoe="2"
                            AND ac.distribuidor_id="'.$distribuidor.'"
                            AND ac.estado_pedido="salida"
                            AND fecha_hora_salida IN (  SELECT  MAX(fecha_hora_salida)as fecha_hora_salida
                                                                FROM    inv_asignaciones_clientes ac  
                                                                WHERE   distribuidor_id="'.$distribuidor.'" 
                                                            )
    
                            
                            ')
                        //->where('ac.fecha_asignacion >=', $fecha_inicial)
                        //->where('ac.fecha_asignacion <=', $fecha_final)
                        // ->group_by('e.empleado_id')
                ->fetch();

// echo json_encode($empleados); die();


// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Importa la libreria para el generado del pdf
require_once libraries . '/tcpdf/tcpdf.php';

// Importa la libreria para convertir el numero a letra
require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

define('IMAGEN', escape($_institution['imagen_encabezado']));

// Operaciones con la imagen del header
list($ancho_header, $alto_header) = getimagesize(imgs . '/header.jpg');
$relacion = $alto_header / $ancho_header;
$ancho_header = 612;
$alto_header = round(312 * $relacion);
define('ancho_header', $ancho_header);
define('alto_header', $alto_header);

// Operaciones con la imagen del footer
list($ancho_footer, $alto_footer) = getimagesize(imgs . '/header.jpg');
$relacion = $alto_footer / $ancho_footer;
$ancho_footer = 612;
$alto_footer = round(312 * $relacion);
define('ancho_footer', $ancho_footer);
define('alto_footer', $alto_footer);


// Extiende la clase TCPDF para crear Header y Footer
class MYPDF extends TCPDF
{
    public function Header()
    {

    }

    public function Footer()
    {

    }
}
$imagen = (IMAGEN != '') ? institucion . '/' . IMAGEN : imgs . '/empty.jpg' ;

$valor_empresa = $_institution['nombre'];
$valor_direccion_e = $_institution['direccion'];
$valor_telefono = $_institution['telefono'];
$valor_pie = $_institution['pie_pagina'];
$valor_razon = $_institution['razon_social'];

$nro_nota = 0;
// Instancia el documento PDF
$pdf = new MYPDF('P', 'pt', array(612,935), true, 'UTF-8', false);

// Asigna la informacion al documento
$pdf->SetCreator(name_autor);
$pdf->SetAuthor(name_autor);
$pdf->SetTitle($_institution['nombre']);
$pdf->SetSubject($_institution['propietario']);
$pdf->SetKeywords($_institution['sigla']);

// Asignamos margenes
$pdf->SetMargins(30, 20, -1, false);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);
$pdf->SetAutoPageBreak(true, 50);


// Adiciona la pagina
// $pdf->AddPage();

$aux1 = 0;
$aux2 = 0;
if($empleados) {


    foreach ($empleados as $key => $egreso) {
        // $detalles = $db->select('d.*, p.codigo, p.nombre, p.nombre_factura, p.precio_sugerido')
        //                 ->from('inv_egresos_detalles d')
        //                 ->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')
        //                 ->where('promocion !=', 'si')
        //                 ->where('d.egreso_id', $egreso['id_egreso'])
        //                 ->order_by('id_detalle asc')
        //                 ->fetch();
        $a_cuenta = $db->select('monto')
                        ->from('inv_pagos p')
                        ->join('inv_pagos_detalles pd', 'pd.pago_id=p.id_pago', 'left')
            			->where('p.movimiento_id = ', $egreso['id_egreso'])
            			->where('p.tipo = ', "Egreso")
            			->where('estado = ', '1')
            			->where('fecha_pago', $egreso['fecha_egreso'])
            			->fetch_first();
        $valor_a_cuenta = number_format($a_cuenta['monto'], 2, '.', '');
        $deuda = $db->select('sum(monto) as monto_parcial ')
        			->from('inv_pagos p')
        			->join('inv_pagos_detalles pd', 'pd.pago_id=p.id_pago', 'left')
        			->where('p.movimiento_id = ', $egreso['id_egreso'])
        			->where('p.tipo = ', "Egreso")
        			->where('estado != ', '1')
        			->fetch();
        $deuda_pendiente = number_format($deuda[0]['monto_parcial'], 2, '.', '');
    
        $detalles = $db->query('select d.*, SUM(d.cantidad) as cantidad, d.unidad_id as unidad_otra, p.codigo, p.nombre, p.nombre_factura
                                from inv_egresos_detalles d
                                left join inv_productos p ON d.producto_id = p.id_producto
                                where d.egreso_id="'.$egreso['id_egreso'].'"
                                group by precio, producto_id, lote, vencimiento
                                order by id_detalle asc')
        			   ->fetch();

        $aux2 = $aux2 + count($detalles);
        if ($aux1 == 2) {
            if ($aux2 > 4) {
                // $pdf->AddPage();
                $aux1 = 1;
                $aux2 = count($detalles);
            }
        }
        if ($aux1 == 3) {
            // $pdf->AddPage();
            $aux1 = 1;
            $aux2 = count($detalles);
        }
        $aux1 = $aux1+1;
        // Asigna la orientacion de la pagina
        $pdf->SetPageOrientation('P');
        // Establece la fuente del titulo
        $pdf->SetFont(PDF_FONT_NAME_MAIN, 'B', 16);
        // Titulo del documento
        $pdf->Cell(0, 10, '', 0, true, 'C', false, '', 0, false, 'T', 'M');
        // $valor_empresa = ($egreso['cargo'] == 1) ? $_institution['empresa1'] : $_institution['empresa2'];
        $valor_empresa = ($_institution['empresa1']) ? $_institution['empresa1'] : $_institution['empresa2'];
        $valor_empleado = escape($egreso['empleado']);
        // ADICIONES JOSEMA
        
        $fecha_habilitacion=explode(" ",$egreso['fecha_habilitacion']);
        
        $valor_fecha = escape(date_decode($fecha_habilitacion[0], $_institution['formato']) . ' ' . $fecha_habilitacion[1]);
        $valor_nombre_cliente = escape($egreso['cliente']);
        $valor_nit_ci = escape($egreso['nit_ci']);
        $valor_direccion = escape($egreso['direccion']);
        $valor_nro_egreso = escape($egreso['id_egreso']);
        $valor_nro_registros = escape($egreso['nro_nota']);
	    $valor_almacen = escape($egreso['almacen']);
        
        if($egreso['plan_de_pagos']=="si"){
            $forma_pago="A CREDITO";
        }else{
            $forma_pago="AL CONTADO";
        }
        
        $body1 = '<table cellpadding="1" >
            		<tr>
            			<td width="20%" class="none" align="left" rowspan="4">
            				<img src="'.$imagen.'" width="70">
            			</td>
            			<td width="30%" class="none" align="left">'.$valor_empresa.'</td>
            			<td width="35%" class="none" align="right"><b>OPERADOR:</b></td>
            			<td width="15%" class="none" align="right">'.$valor_empleado.'</td>
            		</tr>
            		<tr>
            			<td class="none" align="left">'.$valor_direccion_e.'</td>
            			<td class="none" align="right"><b>ALMACÉN:</b></td>
            			<td class="none" align="right">'.$valor_almacen.'</td>
            		</tr>
            		<tr>
            			<td class="none" align="left">Teléfono: '.$valor_telefono.'</td>
            			<td class="none" align="right"><b>NÚMERO DE NOTA:</b></td>
            			<td class="none" align="right">'.$valor_nro_registros.'</td>
            		</tr>
            		<tr>
            			<td class="none" align="left">'.$valor_pie.'</td>
            			<td class="none" align="right"><b>FORMA DE PAGO:</b></td>
            			<td class="none" align="right">'.$forma_pago.'</td>
            		</tr>
            	</table>
            	<h1 align="center">NOTA DE VENTA</h1>
            	<br>
            
            	<table cellpadding="1">
            		<tr>
            			<td width="22%" class="none"><b>LUGAR, FECHA Y HORA:</b></td>
            			<td width="78%" class="none">LA PAZ, '.$valor_fecha.'</td>
            		</tr>
            		<tr>
            			<td class="none"><b>SEÑOR(ES):</b></td>
            			<td class="none">'.$valor_nombre_cliente.'</td>
            		</tr>
            		<tr>
            			<td class="none"><b>NIT / CI:</b></td>
            			<td class="none">'.$valor_nit_ci.'</td>
            		</tr>
            		<tr>
            			<td class="none"><b>DIRECCION:</b></td>
            			<td class="none">'.$valor_direccion.'</td>
            		</tr>
            	</table>
            	';
        // $body1 = '<tr height="2%">
        //         <td align="left" width="30%"><img src="'.$imagen.'" width="55"/></td>
        //         <td align="center" width="40%"> <h2><font color="#7030A0">DISTRIBUIDORA DE PRODUCTOS DE<br />CONSUMO MASIVOS "'.$valor_empresa.'"</font></h2></td>
        //         <td  align="right" width="30%"><img src="'.$imagen.'" width="55"/></td>
        //         </tr><tr>
        //         <td align="right" colspan="3" width="60%" bgcolor="#7030A0"><h1><em><font color="#fff" >NOTA DE VENTA </font></em></h1></td>
        //         <td align="right" colspan="3" width="40%" bgcolor="#7030A0"><h1><font color="#fff">' . $egreso['nro_nota'] . '</font></h1></td>
        //         </tr>';
        // Salto de linea

        // Establece la fuente del contenido
        $pdf->SetFont(PDF_FONT_NAME_DATA, '', 9);
        // Define las variables
        $nro_nota = $key+1;
        $valor_fecha = escape(date_decode($egreso['fecha_egreso'], $_institution['formato']) ) . ' ' . $egreso['hora_egreso'];
        $valor_nombre_cliente = escape($egreso['cliente']);
        $valor_nit_ci = escape($egreso['nit_ci']);
        $valor_direccion = escape($egreso['direccion']);
        $valor_descripcion = escape($egreso['descripcion']);
        $valor_telefono = escape($egreso['telefono']);
        $valor_descuento = escape($egreso['descuento']);
        $valor_observacion = escape($egreso['observacion']);
        $valor_id_cliente = escape($egreso['id_cliente']);
        $detalle_venta = escape($egreso['descripcion_venta']);
        $valor_moneda = $moneda;
        $total = 0;
        $fecha_actual = date_decode(date('Y-m-d'), $_institution['formato']);
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
    		$body .= '<td class="left-right">' . escape($detalle['nombre_factura']) .' <br>(LOTE: '. escape($detalle['lote']) .' VENC: '. escape(date_decode($detalle['vencimiento'], $_institution['formato'])) .')' . '</td>';
    		$body .= '<td class="left-right" align="right">' . $cantidad . ' '.nombre_unidad($db,$detalle['unidad_otra']). '</td>';
    		$body .= '<td class="left-right" align="right">' . $precio . '</td>';
    		$body .= '<td class="left-right" align="right">' . $descuento . '</td>';
    		$body .= '<td class="left-right" align="right">' . number_format($importe, 2, '.', '') . '</td>';
    		$body .= '</tr>';
    	}
        // foreach ($detalles as $nro => $detalle) {
        //     //var_dump($detalle);exit();
        //     $cantidad = escape($detalle['cantidad']);
        //     if($detalle['precio'])
        //         $precio = escape($detalle['precio']);
        //         else
        //         $precio = 0;

        //     $pr = $db->select('*')->from('inv_productos a')->join('inv_unidades b', 'a.unidad_id = b.id_unidad')->where('a.id_producto', $detalle['producto_id'])->fetch_first();
        //     if ($pr['unidad_id'] == $detalle['unidad_id']) {
        //         $unidad = $pr['unidad'];
        //     } else {
        //         $pr = $db->select('*')->from('inv_asignaciones a')->join('inv_unidades b', 'a.unidad_id = b.id_unidad')->where(array('a.producto_id' => $detalle['producto_id'], 'a.unidad_id' => $detalle['unidad_id']))->fetch_first();
        //         if($pr['cantidad_unidad'])
        //         {
        //             $unidad = $pr['unidad'];
        //             $cantidad = $cantidad / $pr['cantidad_unidad'];
        //         }
        //     }
        //     $uni_detalle = cantidad_unidad($db, $detalle['producto_id'], $detalle['unidad_id']);
        //     $precio_sugerido = $detalle['precio_sugerido'];
        //     $importe = $cantidad * $precio;
        //     $total = $total + $importe;

        //     $body .= '<tr height="2%">';
        //     $body .= '<td class="left-right bot" align="right">' . $detalle['codigo'] . '</td>';
        //     $body .= '<td class="left-right bot" align="right">' . $detalle['nombre'].'<br><small style="color:#6d6b6b">(LOTE:'.$detalle['lote']. ' VENC.:' . $detalle['vencimiento'] . ')</small></td>';
        //     $body .= '<td class="left-right bot">' . $unidad . '</td>';
        //     $body .= '<td class="left-right bot" align="center">' . $cantidad . '</td>';
        //     $body .= '<td class="left-right bot" align="right">' . number_format(round($precio/$uni_detalle, 2),2, '.', '') . '</td>';
        //     $body .= '<td class="left-right bot" align="right">' . number_format($importe, 2, '.', '') . '</td>';
        //     $body .= '</tr>';
        // }

        // Obtiene el valor total
        $valor_total = number_format($total, 2, '.', '');
        // Obtiene los datos del monto total
        $conversor = new NumberToLetterConverter();
        $monto_textual = explode('.', $valor_total);
        $monto_numeral = $monto_textual[0];
        $monto_decimal = $monto_textual[1];
        $monto_literal = upper($conversor->to_word($monto_numeral));
        $body = ($body == '') ? '<tr><td colspan="6" align="center" class="all">Este egreso no tiene detalle, es muy importante que todos los egresos cuenten con un detalle de venta.</td></tr>' : $body;
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
<table cellpadding="2" id="cssTable">
    $body1
</table>
<!-- <p></p>
<table cellpadding="2">
    <tr>
        <td width="15%" bgcolor="#7030A0" ><h4><em><font color="#fff">CLIENTE</font></em></h4></td>
        <td width="45%" colspan="2">$valor_nombre_cliente</td>
        <td width="10%" bgcolor="#7030A0" ><h4><em><font color="#fff">FECHA</font></em></h4></td>
        <td width="30%"colspan="2" >$fecha_actual</td>
    </tr>
    <tr>
        <td width="15%" bgcolor="#7030A0" ><h4><em><font color="#fff">DIRECCION</font></em></h4></td>
        <td width="45%" colspan="2">$valor_direccion</td>
        <td width="10%" bgcolor="#7030A0"><h4><em><font color="#fff">VENDEDOR</font></em></h4></td>
        <td width="30%"colspan="2">$valor_empleado</td>
    </tr>
    <tr>
        <td width="15%" bgcolor="#7030A0" ><h4><em><font color="#fff">REFERENCIA</font></em></h4></td>
        <td width="45%" colspan="2"> $valor_descripcion</td>
        <td width="10%" bgcolor="#7030A0" ><h4><em><font color="#fff">CELULAR</font></em></h4></td>
        <td width="30%"colspan="2"> $valor_telefono</td>
    </tr>
</table>
<p></p> -->
<br><br>
<table cellpadding="5" >
    <!-- <tr>
        <th width="20%" bgcolor="#7030A0" align="center" ><font color="#fff">CODIGO</font></th>
        <th width="35%" bgcolor="#7030A0" align="center" ><font color="#fff">ARTICULO</font></th>
        <th width="10%" bgcolor="#7030A0" align="center" ><font color="#fff">U.M</font></th>
        <th width="12%" bgcolor="#7030A0" align="center" ><font color="#fff">CANTIDAD</font></th>
        <th width="10%" bgcolor="#7030A0" align="center" ><font color="#fff">P.U</font></th>
        <th width="13%" bgcolor="#7030A0" align="center" ><font color="#fff">SUBTOTAL</font></th>
    </tr> -->
    <tr>
		<th width="5%" class="all" align="center">#</th>
		<th width="12%" class="all" align="center">CÓDIGO</th>
		<th width="35%" class="all" align="center">NOMBRE</th>
		<th width="12%" class="all" align="center">CANTIDAD</th>
		<th width="12%" class="all" align="center">PRECIO $valor_moneda</th>
		<th width="12%" class="all" align="center">DESCUENTO $valor_moneda</th>
		<th width="12%" class="all" align="center">IMPORTE $valor_moneda</th>
	</tr>
    $body
    <tr>
		<th class="all" align="left" colspan="6">IMPORTE TOTAL $valor_moneda</th>
		<th class="all" align="right">$valor_total</th>
	</tr>
	<tr>
		<th class="all" align="left" colspan="6">A CUENTA: </th>
		<th class="all" align="right">$valor_a_cuenta</th>
	</tr>
	<!-- <tr>
		<th class="all" align="left" colspan="6">DESCUENTO: </th>
		<th class="all" align="right">$valor_descuento_porcentaje            $valor_descuento_global</th>
	</tr> -->
	
	<tr>
		<th class="all" align="left" colspan="6">DEUDA PENDIENTE(S) $valor_moneda</th>
		<th class="all" align="right">$deuda_pendiente</th>
	</tr>
	
</table>
<br>
<HR>
EOD;
        // Imprime la tabla
        $pdf->AddPage();
        $pdf->writeHTML($tabla, true, false, false, false, '');
        
    }
}

// Genera el nombre del archivo
$nombre = 'orden_compra' . $id_orden . '_' . date('Y-m-d_H-i-s') . '.pdf';

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>