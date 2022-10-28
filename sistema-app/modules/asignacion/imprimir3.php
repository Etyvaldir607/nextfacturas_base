<?php

// Obtiene el orden de compra
$distribuidor = (isset($params[0])) ? $params[0] : 0;
// Obtiene el rango de fechas
$gestion = date('Y');
//$gestion_base = date('Y-m-d');
$gestion_base = date("d-m-Y",strtotime(date('Y-m-d')."- 1 days"));

//$gestion_base = ($gestion - 16) . date('-m-d');
$gestion_limite = $gestion_base;

// Obtiene fecha inicial
$fecha_inicial = (isset($params[1])) ? $params[1] : $gestion_base;
$fecha_inicial = (is_date($fecha_inicial)) ? $fecha_inicial : $gestion_base;
$fecha_inicial = date_encode($fecha_inicial);

// Obtiene fecha final
$fecha_final = (isset($params[2])) ? $params[2] : $gestion_limite;
$fecha_final = (is_date($fecha_final)) ? $fecha_final : $gestion_limite;
$fecha_final = date_encode($fecha_final);

$caja = $db->select('id_unidad')->from('inv_unidades')->where('unidad', 'CAJA')->fetch_first();

$id_caja = (isset($caja['id_unidad'])) ? $caja['id_unidad'] : 0;

if ($distribuidor == 0) {
    // Error 404
    require_once not_found();
    exit;
}

$ultimo_despacho =  $db->query('select fecha_hora_salida, nro_liquidacion
                                from inv_asignaciones_clientes
                                where fecha_hora_salida IN 
                                    (
                                        select MAX(fecha_hora_salida)as fecha_hora_salida
                                        from inv_asignaciones_clientes
                                        where distribuidor_id="'.$distribuidor.'"
                                              AND estado="A"
                                    )
                                ')
                            ->fetch_first();

$ultimo_liquidacion =   $db->select('MAX(fecha_hora_liquidacion)as fecha_hora_liquidacion')
                        ->from('inv_asignaciones_clientes')
                        ->where('distribuidor_id',$distribuidor)
                        ->where('estado','A')
                        ->fetch_first();

// Obtiene los empleados
$empleados = $db->query("select w.id_empleado, w.nombres, w.paterno, w.materno, GROUP_CONCAT(a.distribuidor_id SEPARATOR '&') as emp
                        from inv_asignaciones_clientes a
                        left join sys_empleados w ON a.distribuidor_id = w.id_empleado
                        where a.distribuidor_id='$distribuidor'
                            AND fecha_hora_salida ='".$ultimo_despacho['fecha_hora_salida']."'
                        group by a.distribuidor_id")
                        ->fetch_first();
                        
                        //->where('a.estado_pedido', 'salida')
                        
// $empleados = $db->select('w.id_empleado, w.nombres, w.paterno, w.materno, GROUP_CONCAT(a.distribuidor_id SEPARATOR "&") as emp')
//                 ->from('inv_asignaciones_clientes a')
//                 ->join('sys_empleados w','a.distribuidor_id = w.id_empleado')
//                 ->where('a.distribuidor_id',$distribuidor)
//                 ->where('fecha_hora_salida =', $ultimo_despacho['fecha_hora_salida'])
//                 //->where('a.estado_pedido', 'salida')
//                 ->group_by('a.distribuidor_id')
//                 ->fetch_first();

$prueba = explode('&',$empleados['emp']);
$c=0;
$preg = '(';
for ($c = 0; $c < count($prueba); $c++) {
    if ($c == 0) {
        $preg = $preg . 'a.empleado_id = ' . $prueba[$c] . ' ';
    } else {
        $preg = $preg . 'OR a.empleado_id = ' . $prueba[$c] . ' ';
    }
}
$empleados2 = $db->query('  SELECT  w.*
                            FROM inv_asignaciones_clientes a
                            LEFT JOIN inv_egresos b ON a.egreso_id = b.id_egreso
                            LEFT JOIN sys_empleados w ON b.vendedor_id = w.id_empleado
                            WHERE a.distribuidor_id = '.$distribuidor.'  
                                    AND a.fecha_hora_salida ="'.$ultimo_despacho['fecha_hora_salida'].'"

                            GROUP BY w.id_empleado 
                            ORDER BY w.paterno ASC
                        ')->fetch();
    
$valor_empleado2 = '';
// echo json_encode($empleados2); die();
foreach($empleados2 as $empleado2){
    $valor_empleado2 = $valor_empleado2.'<br>'.$empleado2['nombres'].' '.$empleado2['paterno'];
}
$preg = $preg . ')';

//var_dump($empleados);
// Obtiene los permisos
$permisos = explode(',', permits);

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Importa la libreria para el generado del pdf
require_once libraries . '/tcpdf/tcpdf.php';

// Importa la libreria para convertir el numero a letra
require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

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
class MYPDF extends TCPDF {
    public function Header() {
    }
    public function Footer() {
    }
}

// Instancia el documento PDF
$pdf = new MYPDF('P', 'pt', array(612,935), true, 'UTF-8', false);

// Asigna la informacion al documento
$pdf->SetCreator(name_autor);
$pdf->SetAuthor(name_autor);
$pdf->SetTitle($_institution['nombre']);
$pdf->SetSubject($_institution['propietario']);
$pdf->SetKeywords($_institution['sigla']);

// Asignamos margenes
$pdf->SetMargins(30, 10 , 30);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);
$pdf->SetAutoPageBreak(true, alto_footer + 55);

$orden = '';

// Adiciona la pagina
$pdf->AddPage();

if (true) {

    // Obtiene los detalles
    $detalles = $db->query('SELECT a.*,cl.cliente
                    		FROM tmp_egresos a
                    		LEFT JOIN inv_asignaciones_clientes ac ON a.id_egreso=ac.egreso_id
                    		LEFT JOIN inv_clientes cl ON cl.id_cliente=a.cliente_id
                    		WHERE ac.fecha_hora_salida ="'.$ultimo_despacho['fecha_hora_salida'].'"
                    		AND ac.estado_pedido = "entregado"
                    		AND a.distribuidor_id = '.$distribuidor.' 
                    		AND a.distribuidor_estado = "ENTREGA"
                    		')->fetch();
                            
    $detalles2_query =     'SELECT u.unidad, SUM(b.cantidad) AS m_cantidad, SUM(b.cantidad*b.precio) AS m_importe, dev.nro_nota,
                                    c.*, b.*, c.unidad_id AS unidad_producto, d.categoria, cl.cliente
                            FROM inv_egresos dev
                            LEFT JOIN inv_clientes cl ON cl.id_cliente=dev.cliente_id
                    		inner JOIN inv_egresos_detalles b ON dev.id_egreso = b.egreso_id
                    		LEFT JOIN inv_productos c ON b.producto_id = c.id_producto
                    		LEFT JOIN inv_categorias d ON c.categoria_id = d.id_categoria
                    		LEFT JOIN inv_unidades u ON b.unidad_id = u.id_unidad
                    		
                    		inner join 
                    		(	        
            		            SELECT *
                                FROM inv_egresos e
                                WHERE id_egreso IN (
                                    SELECT egreso_id
                                    FROM inv_asignaciones_clientes ac
                                    WHERE   ac.distribuidor_id = '.$distribuidor.'
                                            AND ac.fecha_hora_salida ="'.$ultimo_despacho['fecha_hora_salida'].'"
                                ) 
                            )as e ON e.nro_nota=dev.nro_nota
                            WHERE dev.estadoe=4 and dev.preventa="devolucion"
                        	GROUP BY dev.nro_nota, c.id_producto, b.lote, b.vencimiento 
                    		ORDER BY c.descripcion ASC, c.nombre_factura ASC
                    		';
    $detalles2 = $db->query($detalles2_query)->fetch();
                    		
    $detalles3 = $db->query('SELECT pd.tipo_pago, i.nro_nota, pd.codigo, pd.monto, cl.cliente
                    		 FROM inv_asignaciones_clientes ac
                    		 LEFT JOIN inv_egresos i ON ac.egreso_id = i.id_egreso
                    		 LEFT JOIN inv_clientes cl ON cl.id_cliente=i.cliente_id
                    		 INNER JOIN inv_pagos p ON p.movimiento_id=ac.egreso_id AND p.tipo="Egreso"
                    		 INNER JOIN inv_pagos_detalles pd ON pd.pago_id=p.id_pago
                    		 WHERE pd.estado=1 
                    		 AND ac.distribuidor_id = '.$distribuidor.'
                    		 AND ac.estado_pedido="entregado" 
                    		 AND fecha_hora_salida ="'.$ultimo_despacho['fecha_hora_salida'].'"
                    		 AND pd.tipo_pago!="Devolucion"
                    		')->fetch();

    $detalles4 = $db->query('SELECT pd.tipo_pago, i.nro_nota, pd.codigo, SUM(pd.monto)monto, cl.cliente
                    		 FROM inv_asignaciones_clientes ac
                    		 LEFT JOIN inv_egresos i ON ac.egreso_id = i.id_egreso
                    		 LEFT JOIN inv_clientes cl ON i.cliente_id = cl.id_cliente
                    		 INNER JOIN inv_pagos p ON p.movimiento_id=ac.egreso_id AND p.tipo="Egreso"
                    		 INNER JOIN inv_pagos_detalles pd ON pd.pago_id=p.id_pago
                    		 WHERE pd.estado=0 
                    		 AND ac.distribuidor_id = '.$distribuidor.'
                    		 AND estado_pedido="entregado" 
                    		 AND ac.fecha_hora_salida ="'.$ultimo_despacho['fecha_hora_salida'].'"
                    		 AND pd.tipo_pago!="Devolucion"
                    		 GROUP BY pd.pago_id
                    		 
                    		')->fetch();
    //AND  ac.fecha_entrega = "'.date("Y-m-d").'"
                    		 
    $preventas_anuladas_query = "   SELECT *, cl.cliente
                                    FROM inv_egresos e 
                                    LEFT JOIN inv_clientes cl ON cl.id_cliente=cliente_id
                    		        INNER JOIN  inv_asignaciones_clientes ac ON e.id_egreso=ac.egreso_id
                                    WHERE       
                                            ac.distribuidor_id = '$distribuidor'
                		                AND fecha_hora_liquidacion ='".$ultimo_liquidacion['fecha_hora_liquidacion']."'
                		                AND estado_pedido='reasignado'
                                        AND e.tipo = 'No venta'
                                ";
                                //LEFT JOIN gps_noventa_motivos ON id_motivo=motivo_id
                                    
    $preventas_anuladas = $db->query($preventas_anuladas_query)->fetch();
                    		        
    // echo $db->last_query(); die();
    $auxiliar = $db->affected_rows;

// Asigna la orientacion de la pagina
    $pdf->SetPageOrientation('P');

// Establece la fuente del titulo
    $pdf->SetFont(PDF_FONT_NAME_MAIN, 'B', 16);

// Titulo del documento
    $pdf->Cell(0, 5, '', 0, true, 'C', false, '', 0, false, 'T', 'M');


// Establece la fuente del contenido
    $pdf->SetFont(PDF_FONT_NAME_DATA, '', 9);

// Define las variables
    $fechax = explode(" ",$ultimo_liquidacion['fecha_hora_liquidacion']);
    $valor_fecha = escape(date_decode($fechax[0], $_institution['formato']) . ' ' . $fechax[1]);
    //$valor_fecha = escape(date('d/m/Y H:i:s'));
    
    $valor_nombre_cliente = escape($orden['cliente']);
    $valor_nit_ci = escape($orden['nit_ci']);
    $valor_direccion = escape($orden['direccion']);
    $valor_telefono = escape($orden['telefono']);
    $valor_monto_total = escape($orden['monto_total']);
    $valor_empleado = escape($empleados['nombres'] . ' ' . $empleados['paterno'] . ' ' . $empleados['materno']);
    $valor_descuento = escape($orden['descuento']);
    $valor_observacion = escape($orden['observacion']);

    $valor_moneda = $moneda;
    $total = 0;

// Establece la fuente del contenido
    $pdf->SetFont(PDF_FONT_NAME_DATA, '', 8);

// Estructura la tabla
    $body = '';
    $body2 = '';
    $body3 = '';
    $body4 = '';
    $body5 = '';

    $total_1w = 0;
    $total_1x = 0;
    $total = 0;
    $total_1z = 0;
    
    $valor_total=0;
    foreach ($detalles as $nro => $detalle) {
    
        $detallexxx = $db->query('SELECT SUM(a.monto_total) monto_sin_devolucion
                        		FROM inv_egresos a
                        		WHERE nro_nota = "'.$detalle['nro_nota'].'" 
                        		GROUP BY nro_nota
                        		')->fetch_first();
        
        $detallesyyy = $db->query('SELECT SUM(pd.monto) as monto_pagado
                    		FROM inv_pagos p 
                    		LEFT JOIN inv_pagos_detalles pd ON pd.pago_id=p.id_pago AND pd.estado=1
                    		WHERE p.movimiento_id="'.$detalle['id_egreso'].'" AND p.tipo="Egreso" AND pd.tipo_pago!="Devolucion"
                    		')->fetch_first();
    
        //$total += $detalle['m_importe'];
        $body .= '<tr height="2%" >';
        $body .= '<td class="left-right bot" align="left">' . escape($detalle['nro_nota']).'</td>';
        $body .= '<td class="left-right bot" align="left">' . $detalle['cliente']. '</td>';
        $body .= '<td class="left-right bot" align="right">' . number_format($detallexxx['monto_sin_devolucion'], 2, ',', '.') . '</td>';
        $body .= '<td class="left-right bot" align="right">' . number_format(($detallexxx['monto_sin_devolucion']-$detalle['monto_total']), 2, ',', '.') . '</td>';
        $body .= '<td class="left-right bot" align="right">' . number_format($detalle['monto_total'], 2, ',', '.') . '</td>';
        $body .= '<td class="left-right bot" align="right">' . number_format($detallesyyy['monto_pagado'], 2, ',', '.') . '</td>';
        $body .= '<td class="left-right bot" align="right">' . number_format(($detalle['monto_total']-$detallesyyy['monto_pagado']), 2, ',', '.') . '</td>';
        $body .= '</tr>';
        
        $total_1w+=($detallexxx['monto_sin_devolucion']-$detalle['monto_total']);
        $total_1x+=$detalle['monto_total'];
        $total+=$detallesyyy['monto_pagado'];
        $total_1z+=$detalle['monto_total']-$detallesyyy['monto_pagado'];
    }

    $total2 = 0;
    
    // $body2 .= '<tr height="2%">';
    // $body2 .= '<td class="left-right bot" align="left">' . $detalles2_query .'</td>';
    // $body2 .= '<td class="left-right bot" align="right"></td>';
    // $body2 .= '<td class="left-right bot" align="right"></td>';
    // $body2 .= '<td class="left-right bot" align="right"></td>';
    // $body2 .= '<td class="left-right bot"></td>';
    // $body2 .= '<td class="left-right bot" align="right"></td>';
    // $body2 .= '</tr>';

    foreach ($detalles2 as $nro => $detalle) {
        $total2 += $detalle['m_importe'];
        $body2 .= '<tr height="2%">';
        $body2 .= '<td class="left-right bot" align="left">' . escape($detalle['nombre_factura']) . '<br> <small>(LOTE: '. escape($detalle['lote']) .' VENC.: '. escape(date_decode($detalle['vencimiento'], $_institution['formato'])).')</small></td>';
        $body2 .= '<td class="left-right bot" align="right">' . $detalle['nro_nota']. '</td>';
        $body2 .= '<td class="left-right bot" align="right">' . $detalle['categoria']. '</td>';
        $body2 .= '<td class="left-right bot" align="right">' . $detalle['m_cantidad']. '</td>';
        $body2 .= '<td class="left-right bot">' . $detalle['unidad']. '</td>';
        $body2 .= '<td class="left-right bot" align="right">' . number_format($detalle['m_importe'], 2, ',', '.') . '</td>';
        $body2 .= '</tr>';
    }
    
    $total3 = 0;
    $total4 = 0;
    $total5 = 0;
    
    foreach ($detalles3 as $nro => $detalle) {
        $total3 += $detalle['monto'];
        $body3 .= '<tr height="2%">';
        $body3 .= '<td class="left-right bot" align="left">' . $detalle['cliente']. '</td>';
        $body3 .= '<td class="left-right bot" align="left">' . $detalle['nro_nota']. '</td>';
        $body3 .= '<td class="left-right bot" align="left">' . $detalle['codigo']. '</td>';
        $body3 .= '<td class="left-right bot" align="left">' . $detalle['tipo_pago']. '</td>';
        $body3 .= '<td class="left-right bot" align="right">' . number_format($detalle['monto'], 2, ',', '.'). '</td>';
        $body3 .= '</tr>';
    }
    
    foreach ($detalles as $nro => $detalle) {
        $detallexxx = $db->query('SELECT SUM(a.monto_total) monto_sin_devolucion
                        		FROM inv_egresos a
                        		WHERE nro_nota = "'.$detalle['nro_nota'].'" 
                        		GROUP BY nro_nota
                        		')->fetch_first();
        
        $detallesyyy = $db->query('SELECT SUM(pd.monto) as monto_pagado
                    		FROM inv_pagos p 
                    		LEFT JOIN inv_pagos_detalles pd ON pd.pago_id=p.id_pago AND pd.estado=1
                    		WHERE p.movimiento_id="'.$detalle['id_egreso'].'" AND p.tipo="Egreso" AND pd.tipo_pago!="Devolucion"
                    		')->fetch_first();
    
        $total4x = $detalle['monto_total']-$detallesyyy['monto_pagado'];
        $total4 += $total4x;
        
        if(number_format($total4x, 2, '.','')>0){
            $body4 .= '<tr height="2%" >';
            $body4 .= '<td class="left-right bot" align="left">' . $detalle['cliente']. '</td>';
            $body4 .= '<td class="left-right bot" align="left">' . escape($detalle['nro_nota']).'</td>';
            $body4 .= '<td class="left-right bot" align="left">CUENTAS POR COBRAR</td>';
            $body4 .= '<td class="left-right bot" align="right">' . number_format($total4x, 2, ',', '.') . '</td>';
            $body4 .= '</tr>';
        }
    }
    
    foreach ($preventas_anuladas as $nro => $detalle){
        $total5 += $detalle['monto_total'];
        
        $body5 .= '<tr height="2%">';
            $body5 .= '<th class="left-right bot" align="left" colspan="5">Nombre cliente: ' . $detalle['cliente']. '</th>';
        $body5 .= '</tr>';
        $body5 .= '<tr height="2%">';
            $body5 .= '<th class="left-right bot" align="left" colspan="5">Nro de Nota: ' . $detalle['nro_nota']. '</th>';
        $body5 .= '</tr>';
        $body5 .= '<tr height="2%">';
            $body5 .= '<th class="left-right bot" align="left" colspan="5">Motivo: ' . $detalle['motivo_id']. '</th>';
        $body5 .= '</tr>';
        
        $total_reasignado_5 = 0;
        
        $detalles5Aux = $db->query('SELECT u.unidad, SUM(b.cantidad) AS m_cantidad, SUM(b.cantidad*b.precio) AS m_importe, c.*, b.*, c.unidad_id AS unidad_producto,d.categoria 
                            FROM inv_egresos dev 
                            inner JOIN inv_egresos_detalles b ON dev.id_egreso = b.egreso_id
                            LEFT JOIN inv_productos c ON b.producto_id = c.id_producto 
                            LEFT JOIN inv_categorias d ON c.categoria_id = d.id_categoria 
                            LEFT JOIN inv_unidades u ON b.unidad_id =u.id_unidad 
                            
                            inner join ( SELECT * 
                                        FROM inv_egresos e 
                                        WHERE id_egreso IN ( 
                                                    	SELECT egreso_id 
                                                    	FROM inv_asignaciones_clientes ac 
                                                        WHERE 
                                                        ac.distribuidor_id = '.$distribuidor.'
                                                        AND ac.fecha_hora_salida ="'.$ultimo_despacho['fecha_hora_salida'].'"
                                                        AND nro_nota = "'.$detalle['nro_nota'].'"
                                                    ) 
                                        )as e ON e.id_egreso<=dev.id_egreso AND e.fecha_egreso=dev.fecha_egreso AND e.hora_egreso=dev.hora_egreso AND e.fecha_habilitacion=dev.fecha_habilitacion
                            WHERE dev.estadoe=4 and dev.preventa="habilitado"
                        	GROUP BY c.id_producto, b.lote, b.vencimiento 
                    		ORDER BY c.descripcion ASC, c.nombre_factura ASC
                    		')->fetch();

        foreach ($detalles5Aux as $nro => $detalle) {
            $total_reasignado_5 += ($detalle['m_importe']);
            $body5 .= '<tr height="2%">';
            $body5 .= '<td class="left-right bot" width="52%" align="left">' . escape($detalle['nombre_factura']) . '<br> <small>(LOTE: '. escape($detalle['lote']) .' VENC.: '. escape(date_decode($detalle['vencimiento'], $_institution['formato'])).')</small></td>';
            $body5 .= '<td class="left-right bot" width="12%" align="right">' . $detalle['m_cantidad']. '</td>';
            $body5 .= '<td class="left-right bot" width="12%">' . $detalle['unidad']. '</td>';
            $body5 .= '<td class="left-right bot" width="12%" align="right">' . number_format($detalle['precio'], 2, ',', '.') . '</td>';
            $body5 .= '<td class="left-right bot" width="12%" align="right">' . number_format($detalle['m_importe'], 2, ',', '.') . '</td>';
            $body5 .= '</tr>';
        }
        
        $body5 .= '<tr height="2%">';
            $body5 .= '<th class="left-right bot" align="right" colspan="5">' . number_format($total_reasignado_5, 2, ',', '.'). '</th>';
        $body5 .= '</tr>';

        $body5 .= '<tr height="2%">';
            $body5 .= '<td class="left-right bot" align="right" colspan="5" class="all"></td>';
        $body5 .= '</tr>';
    }
    
// Obtiene el valor total
    $valor_total_1w  = number_format($total_1w, 2, '.', '');
    $valor_total_1x  = number_format($total_1x, 2, '.', '');
    $valor_total  = number_format($total, 2, '.', '');
    $valor_total_1z  = number_format($total_1z, 2, '.', '');
    $valor_total2 = number_format($total2, 2, '.', '');
    $valor_total3 = number_format($total3, 2, '.', '');
    $valor_total4 = number_format($total4, 2, '.', '');
    $valor_total5 = number_format($total5, 2, '.', '');
    
    $conversor = new NumberToLetterConverter();
    $monto_textual = explode('.', $valor_total);
    $monto_numeral = $monto_textual[0];
    $monto_decimal = $monto_textual[1];
    $monto_literal = upper($conversor->to_word($monto_numeral));
    $body = ($body == '') ? '<tr><td colspan="7" align="center" class="all">Este egreso no tiene detalle, es muy importante que todos los egresos cuenten con un detalle de venta.</td></tr>' : $body;

    $conversor = new NumberToLetterConverter();
    $monto_textual2 = explode('.', $valor_total2);
    $monto_numeral2 = $monto_textual2[0];
    $monto_decimal2 = $monto_textual2[1];
    $monto_literal2 = upper($conversor->to_word($monto_numeral2));
    $body = ($body == '') ? '<tr><td colspan="7" align="center" class="all">Este egreso no tiene detalle, es muy importante que todos los egresos cuenten con un detalle de venta.</td></tr>' : $body;

    $conversor = new NumberToLetterConverter();
    $monto_textual3 = explode('.', $valor_total3);
    $monto_numeral3 = $monto_textual3[0];
    $monto_decimal3 = $monto_textual3[1];
    $monto_literal3 = upper($conversor->to_word($monto_numeral3));

    $conversor = new NumberToLetterConverter();
    $monto_textual4 = explode('.', $valor_total4);
    $monto_numeral4 = $monto_textual4[0];
    $monto_decimal4 = $monto_textual4[1];
    $monto_literal4 = upper($conversor->to_word($monto_numeral4));

    $conversor = new NumberToLetterConverter();
    $monto_textual5 = explode('.', $valor_total5);
    $monto_numeral5 = $monto_textual5[0];
    $monto_decimal5 = $monto_textual5[1];
    $monto_literal5 = upper($conversor->to_word($monto_numeral5));

    $valor_total_1w  = number_format($total_1w, 2, ',', '.');
    $valor_total_1x  = number_format($total_1x, 2, ',', '.');
    $valor_total     = number_format($total   , 2, ',', '.');
    $valor_total_1z  = number_format($total_1z, 2, ',', '.');
    
    $valor_total2 = number_format(($valor_total2), 2, ',', '.');
    $valor_total3 = number_format(($valor_total3), 2, ',', '.');
    $valor_total4 = number_format(($valor_total4), 2, ',', '.');
    $valor_total5 = number_format(($valor_total5), 2, ',', '.');
    
    $nro_liquidacion=$ultimo_despacho['nro_liquidacion'];
        
        
        //$ultimo_despacho_fecha_hora_salida=$ultimo_despacho['fecha_hora_salida'];
        
        
// Formateamos la tabla
    $tabla = <<<EOD
	<style>
	th {
		background-color: #eee;
		font-weight: bold;
	}
	.left-right {
		border-left: 1px solid #444;
		border-right: 1px solid #444;
	}
	.none {
		border: 1px solid #fff;
	}
	.all {
		border: 1px solid #444;
	}
	.bot{
        border-top: 1px solid #444;
	}
	.bot2{
        border: 1px solid #444;
	}
	</style>
	<table cellpadding="1">
        <tr>
            <td colspan="1" align="center"  bgcolor="#CCCCCC" ></td>
            <td colspan="2" align="center"  bgcolor="#CCCCCC" ><h1>LIQUIDACIÓN</h1></td>
            <td colspan="1" align="center"  bgcolor="#CCCCCC" ><h1>Nro. $nro_liquidacion</h1></td>
        </tr>
        
        <tr><td align="right"><b>VENDEDORES:</b></td>
            <td align="left">$valor_empleado2</td>
            <td align="right"><b>HOJA DE SALIDA:</b></td>
            <td  align="left">$valor_final</td>
        </tr>
            <tr>
            <td align="right"><b>DISTRIBUIDOR:</b></td>
            <td align="left">$valor_empleado</td>
            <td align="right"><strong>FECHA</strong></td>
            <td align="left">$valor_fecha</td>
        </tr>
        <tr>
            <td  align="center" colspan="4" ></td>
        </tr>
        
        <tr>
            <td  align="center" colspan="4"  bgcolor="#CCCCCC"><h3>NOTAS DE VENTA ENTREGADAS</h3></td>
        </tr>
    </table>
	<br><br>
	<table cellpadding="2">
		<tr>
			<th width="10%" class="all" align="left">NRO NOTA</th>
			<th width="30%" class="all" align="left">CLIENTE</th>
			<th width="12%" class="all" align="right">MONTO</th>
			<th width="12%" class="all" align="right">DEVUELTO</th>
			<th width="12%" class="all" align="right">MONTO TOTAL</th>
			<th width="12%" class="all" align="left">PAGOS</th>
			<th width="12%" class="all" align="left">SALDO</th>
		</tr>
		$body
		<tr>
			<th class="all" align="left" colspan="3">TOTALES $valor_moneda</th>
			<th class="all" align="right">$valor_total_1w</th>
			<th class="all" align="right">$valor_total_1x</th>
			<th class="all" align="right">$valor_total</th>
			<th class="all" align="right">$valor_total_1z</th>
		</tr>
	</table>
	<p align="right">$monto_literal $monto_decimal /100</p>
	
	<br><br>
    <table cellpadding="2">
        <tr>
            <td width="50%" align="right"></td>
			<td width="38%" align="right">MONTO LIQUIDACIÓN $valor_moneda</td>
			<td width="12%" class="bot2" align="RIGHT">$valor_total</td>
		</tr>
    </table>

    <table cellpadding="1">
		<tr>
			<td colspan="3"></td>
		</tr>
		<tr>
			<td class="none" align="center" colspan="3" bgcolor="#CCCCCC" ><h3>PRODUCTOS DEVUELTOS</h3></td>
		</tr>
	</table>
	
	<br><br>
	<table cellpadding="2">
		<tr>
			<th width="40%" class="all" align="left">DETALLE</th>
			<th width="13%" class="all" align="left">NRO NOTA</th>
			<th width="13%" class="all" align="right">CATEGORÍA</th>
			<th width="7%" class="all" align="left">CANT.</th>
			<th width="15%" class="all" align="left">UNIDAD</th>
			<th width="12%" class="all" align="right">IMPORTE $valor_moneda</th>
		</tr>
		$body2
		<tr>
			<th class="all" align="left" colspan="5">IMPORTE TOTAL $valor_moneda</th>
			<th class="all" align="right">$valor_total2</th>
		</tr>
	</table>
	<p align="right">$monto_literal2 $monto_decimal2 /100</p>
	
	
	
    <br><br>
    <table cellpadding="1">
		<tr>
			<td colspan="3"></td>
		</tr>
		<tr>
			<td class="none" align="center" colspan="3" bgcolor="#CCCCCC" ><h3>PAGOS RECIBIDOS</h3></td>
		</tr>
	</table>
	<br><br>
    <table cellpadding="2">
		<tr>
		    <th width="30%" class="all" align="left">CLIENTE</th>
		    <th width="15%" class="all" align="left">NRO. NOTA</th>
		    <th width="15%" class="all" align="left">NRO. RECIBO</th>
			<th width="20%" class="all" align="left">TIPO PAGO</th>
			<th width="20%" class="all" align="left">MONTO</th>
		</tr>
		$body3
		<tr>
			<th class="all" align="left" colspan="4">IMPORTE TOTAL $valor_moneda</th>
			<th class="all" align="right">$valor_total3</th>
		</tr>
	</table>
	<p align="right">$monto_literal3 $monto_decimal3 /100</p>



    <br><br>
    <table cellpadding="1">
		<tr>
			<td colspan="3"></td>
		</tr>
		<tr>
			<td class="none" align="center" colspan="3" bgcolor="#CCCCCC" ><h3>CUENTAS POR COBRAR</h3></td>
		</tr>
	</table>
	<br><br>
    <table cellpadding="2">
		<tr>
			<th width="40%" class="all" align="left">CLIENTE</th>
		    <th width="15%" class="all" align="left">NRO. NOTA</th>
		    <th width="30%" class="all" align="left">TIPO PAGO</th>
			<th width="15%" class="all" align="left">MONTO</th>
		</tr>
		$body4
		<tr>
			<th class="all" align="left" colspan="3">IMPORTE TOTAL $valor_moneda</th>
			<th class="all" align="right">$valor_total4</th>
		</tr>
	</table>
	<p align="right">$monto_literal4 $monto_decimal4 /100</p>



    <br><br>
    <table cellpadding="1">
		<tr>
			<td colspan="3"></td>
		</tr>
		<tr>
			<td class="none" align="center" colspan="3" bgcolor="#CCCCCC" ><h3>NOTAS DE VENTA NO ENTREGADAS</h3></td>
		</tr>
	</table>
	<br><br>
	<table cellpadding="2">
		$body5
		<tr>
			<th class="all" align="left" colspan="4">IMPORTE TOTAL $valor_moneda</th>
			<th class="all" align="right">$valor_total5</th>
		</tr>
	</table>
	<p align="right">$monto_literal5 $monto_decimal5 /100</p>
	
    
    
    
    
    
    
    
    
    <table cellpadding="1">
        <tr>
            <td width="50%" class="none" align="center" ></td>
            <td width="50%" class="none" align="center" ></td>
        </tr>
        <tr>
            <td width="50%" class="none" align="center" ></td>
            <td width="50%" class="none" align="center" ></td>
        </tr>
        <tr>
            <td width="50%" class="none" align="center" ></td>
            <td width="50%" class="none" align="center" ></td>
        </tr>
        <tr>
            <td width="50%" class="none" align="center" >------------------------------------------------------</td>
            <td width="50%" class="none" align="center" >------------------------------------------------------</td>
        </tr>
        <tr>
            <td width="50%" class="none" align="center" >Entregué conforme </td>
            <td width="50%" class="none" align="center" >Recibi conforme cajas</td>
        </tr>
        <tr>
            <td width="50%" class="none" align="center" >$valor_empleado</td>
            <td width="50%" class="none" align="center" >Nombre:_________________________ </td>
        </tr>
        <tr>
            <td width="50%" class="none" align="center" ></td>
            <td width="50%" class="none" align="center" ></td>
        </tr>
        <tr>
            <td width="50%" class="none" align="center" ></td>
            <td width="50%" class="none" align="center" ></td>
        </tr>
        <tr>
            <td width="50%" class="none" align="center" ></td>
            <td width="50%" class="none" align="center" ></td>
        </tr>
        <tr>
            <td width="50%" class="none" align="center" >------------------------------------------------------</td>
            <td width="50%" class="none" align="center" >__ / __ / ____</td>
        </tr>
        <tr>
            <td width="50%" class="none" align="center" >Recibi conforme (Almacen)</td>
            <td width="50%" class="none" align="center" >Fecha liquidación</td>
        </tr>
        <tr>
            <td width="49%" class="none" align="center" >Nombre:_________________________ </td>
            <td width="49%" class="none" align="center" ></td>
        </tr>
    </table>
EOD;

// Imprime la tabla
    $pdf->writeHTML($tabla, true, false, false, false, '');


    if ($auxiliar == 10) {
// Salto de linea
        $pdf->Ln(2);
    }
    elseif ($auxiliar == 9) {
// Salto de linea
        $pdf->Ln(25);
    }elseif ($auxiliar == 8) {
        // Salto de linea
        $pdf->Ln(65);
    }elseif ($auxiliar == 7) {
        // Salto de linea
        $pdf->Ln(65);
    }elseif ($auxiliar == 6) {
        // Salto de linea
        $pdf->Ln(85);
    }elseif ($auxiliar == 5) {
        // Salto de linea
        $pdf->Ln(105);
    }elseif ($auxiliar < 5) {
        // Salto de linea
        $pdf->Ln(185);
    }
$style3 = array('width' => 1, 'cap' => 'round', 'join' => 'round', 'dash' => '2,10', 'color' => array(255, 0, 0));

}

// Genera el nombre del archivo
$nombre = 'orden_compra' . $id_orden . '_' . date('Y-m-d_H-i-s') . '.pdf';

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>
