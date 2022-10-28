<?php

// Obtiene el orden de compra
$distribuidor = (isset($params[0])) ? $params[0] : 0;
$un_egreso = 0;

if($distribuidor==0){
    $un_egreso = (isset($params[1])) ? $params[1] : 0;
}

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

if ($distribuidor == 0 && $un_egreso==0) {
    // Error 404
    require_once not_found();
    exit;
}

if ($distribuidor != 0) {
    // Obtiene los empleados
    $distro = $db->select('CONCAT(nombres," ", paterno," ", materno) as distribuidor')
                    ->from('sys_empleados')
                    ->where('id_empleado',$distribuidor)
                    ->fetch_first();
    
    $solo_empleados= $db->query('select CONCAT(em.nombres," ", em.paterno," ", em.materno) as empleado
                                from inv_asignaciones_clientes ac
                                left join inv_egresos e ON ac.egreso_id = e.id_egreso
                                left join sys_empleados em ON e.vendedor_id = em.id_empleado
                                where ac.distribuidor_id="'.$distribuidor.'"
                                        AND ac.estado_pedido="salida"
                                        AND fecha_hora_salida IN (  SELECT  MAX(fecha_hora_salida)as fecha_hora_salida
                                                                    FROM    inv_asignaciones_clientes ac  
                                                                    WHERE   distribuidor_id="'.$distribuidor.'" 
                                                                )
        
                                group by e.vendedor_id')
                    ->fetch();
    
    $egresos = $db->query(' select e.*, ac.nro_salida
                            from inv_asignaciones_clientes ac
                            left join inv_egresos e ON ac.egreso_id = e.id_egreso
                            where ac.distribuidor_id ="'.$distribuidor.'"
                                AND ac.estado_pedido="salida"
                                AND fecha_hora_salida IN (  SELECT  MAX(fecha_hora_salida)as fecha_hora_salida
                                                            FROM    inv_asignaciones_clientes ac  
                                                            WHERE   distribuidor_id="'.$distribuidor.'" )
                            ')
                    ->fetch();
                    
                    
    // Obtiene los empleados
    $empleados = $db->query('select e.*, CONCAT(em.nombres," ", em.paterno," ", em.materno) as empleado, 
                                    cl.cliente, cl.direccion, al.almacen, SUM(precio*cantidad)as monto_total, ax.reasignado
                        from inv_asignaciones_clientes ac
                        left join inv_egresos e ON ac.egreso_id = e.id_egreso
                        left join inv_egresos_detalles ed ON e.id_egreso = ed.egreso_id
                        left join inv_clientes cl on e.cliente_id = cl.id_cliente
                        left join sys_empleados em on e.empleado_id = em.id_empleado
                        left join inv_almacenes al on e.almacen_id = al.id_almacen
                        
                        LEFT JOIN (
                                            SELECT x.egreso_id, IF(reasignado>1,2,1)as reasignado
                                            FROM (  SELECT axc.egreso_id, count(id_asignacion_cliente)as reasignado
                                                    FROM inv_asignaciones_clientes axc
                                                    GROUP BY egreso_id
                                                  )x
                                          )ax ON e.id_egreso = ax.egreso_id
                                
                        where e.estadoe="2"
                            AND ac.distribuidor_id="'.$distribuidor.'"
                            AND ac.estado_pedido="salida"
                            AND fecha_hora_salida IN (  SELECT  MAX(fecha_hora_salida)as fecha_hora_salida
                                                                FROM    inv_asignaciones_clientes ac  
                                                                WHERE   distribuidor_id="'.$distribuidor.'" 
                                                            )
    
                        GROUP BY id_egreso    
                            ')
                        //->where('ac.fecha_asignacion >=', $fecha_inicial)
                        //->where('ac.fecha_asignacion <=', $fecha_final)
                        // ->group_by('e.empleado_id')
                ->fetch();
                
}else{
    // Obtiene los empleados
    $distro = $db->select('CONCAT(nombres," ", paterno," ", materno) as distribuidor')
                    ->from('sys_empleados')
                    ->where('id_empleado',$_user['persona_id'])
                    ->fetch_first();
    
    $solo_empleados= $db->query('select CONCAT(em.nombres," ", em.paterno," ", em.materno) as empleado
                                from inv_egresos e 
                                left join sys_empleados em ON e.vendedor_id = em.id_empleado
                                where id_egreso="'.$un_egreso.'"')
                        ->fetch();
    
    $egresos = $db->query(' select e.*, ac.nro_salida
                            from inv_asignaciones_clientes ac
                            left join inv_egresos e ON ac.egreso_id = e.id_egreso
                            where id_egreso="'.$un_egreso.'"')
                    ->fetch();
}
// echo json_encode($egresos); die();


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
$pdf->SetAutoPageBreak(true, 55);

$orden = '';

// Adiciona la pagina
$pdf->AddPage();

if($egresos){
    // Asigna la orientacion de la pagina
    $pdf->SetPageOrientation('P');
    // Establece la fuente del titulo
    $pdf->SetFont(PDF_FONT_NAME_MAIN, 'B', 16);
    // Titulo del documento
    $pdf->Cell(0, 5, '', 0, true, 'C', false, '', 0, false, 'T', 'M');
    // Establece la fuente del contenido
    $pdf->SetFont(PDF_FONT_NAME_DATA, '', 9);
    // Define las variables
    $valor_fecha = escape(date_decode(date('Y-m-d'), $_institution['formato']) . ' ' . date('H:i:s') ); // . ' ' . $orden['hora_egreso']
    $valor_empleado = escape($distro['distribuidor']);
    $valor_empleado2 = '';
    $valor_moneda = $moneda;
    $total = 0;
    $total_total = 0;
    // Establece la fuente del contenido
    $pdf->SetFont(PDF_FONT_NAME_DATA, '', 8);

    //echo json_encode($egresos); die();
    
    $egresos_id = array();
    foreach ($egresos as $key => $egreso) {
        $nro_salida=$egreso['nro_salida'];
        array_push($egresos_id, $egreso['id_egreso']);
        //echo $egreso['id_egreso']." --- ";
    }
    foreach ($solo_empleados as $key => $solo_empleado) {
        $valor_empleado2 .= $solo_empleado['empleado'] . '<br>';
    }
        
    if ($distribuidor != 0) {
        $detalles = $db->query("SELECT d.producto_id,p.codigo,p.nombre,p.nombre_factura,p.descripcion,c.categoria,
                                        GROUP_CONCAT(d.precio) as precio, GROUP_CONCAT(d.cantidad) as cantidad, 
                                        (d.precio * d.cantidad) as subtotal, u.unidad, u.id_unidad,
                                        d.lote, d.vencimiento, a.almacen, ax.reasignado
                                FROM inv_egresos_detalles d
                                INNER JOIN inv_egresos e ON d.egreso_id = e.id_egreso
                                INNER JOIN inv_almacenes a ON e.almacen_id = a.id_almacen
                                
                                LEFT JOIN inv_asignaciones_clientes ac ON e.id_egreso = ac.egreso_id
                                LEFT JOIN (
                                            SELECT x.egreso_id, IF(reasignado>1,2,1)as reasignado
                                            FROM (  SELECT axc.egreso_id, count(id_asignacion_cliente)as reasignado
                                                    FROM inv_asignaciones_clientes axc
                                                    GROUP BY egreso_id
                                                  )x
                                          )ax ON e.id_egreso = ax.egreso_id
                                        
                                LEFT JOIN inv_productos p ON d.producto_id = p.id_producto 
                                LEFT JOIN inv_categorias c ON p.categoria_id = c.id_categoria 
                                LEFT JOIN inv_unidades u ON d.unidad_id = u.id_unidad
                                WHERE ac.distribuidor_id = $distribuidor
                                AND ac.estado_pedido = 'salida'
                                AND p. promocion != 'si'
                                AND e.id_egreso IN (".implode(',', array_map('intval', $egresos_id)).")
                                
                                AND fecha_hora_salida IN (  SELECT  MAX(fecha_hora_salida)as fecha_hora_salida
                                                            FROM    inv_asignaciones_clientes ac  
                                                            WHERE   distribuidor_id='".$distribuidor."' 
                                                        )
    
                                GROUP BY ax.reasignado, d.producto_id, d.lote, d.vencimiento, a.id_almacen
                                ORDER BY ax.reasignado ASC, a.almacen
                            ")->fetch(); 
    }else{
        $detalles = $db->query("SELECT d.producto_id,p.codigo,p.nombre,p.nombre_factura,p.descripcion,c.categoria,
                                        GROUP_CONCAT(d.precio) as precio, GROUP_CONCAT(d.cantidad) as cantidad, 
                                        (d.precio * d.cantidad) as subtotal, u.unidad, u.id_unidad,
                                        d.lote, d.vencimiento, a.almacen, ax.reasignado
                                FROM inv_egresos_detalles d
                                INNER JOIN inv_egresos e ON d.egreso_id = e.id_egreso
                                INNER JOIN inv_almacenes a ON e.almacen_id = a.id_almacen
                                
                                LEFT JOIN inv_asignaciones_clientes ac ON e.id_egreso = ac.egreso_id
                                LEFT JOIN (
                                            SELECT x.egreso_id, IF(reasignado>1,2,1)as reasignado
                                            FROM (  SELECT axc.egreso_id, count(id_asignacion_cliente)as reasignado
                                                    FROM inv_asignaciones_clientes axc
                                                    GROUP BY egreso_id
                                                  )x
                                          )ax ON e.id_egreso = ax.egreso_id
                                        
                                LEFT JOIN inv_productos p ON d.producto_id = p.id_producto 
                                LEFT JOIN inv_categorias c ON p.categoria_id = c.id_categoria 
                                LEFT JOIN inv_unidades u ON d.unidad_id = u.id_unidad
                                WHERE e.id_egreso = '".$un_egreso."'
                                AND ac.estado_pedido = 'salida'
                                AND p. promocion != 'si'
                                AND e.id_egreso IN (".implode(',', array_map('intval', $egresos_id)).")
                                
                                GROUP BY ax.reasignado, d.producto_id, d.lote, d.vencimiento, a.id_almacen
                                ORDER BY ax.reasignado ASC, a.almacen
                            ")->fetch(); 
    }    
        // u.id_unidad
        // implode(',', $egresos_id)
        //echo json_encode($detalles); die();
        // echo $db->last_query();

        $subtotal = 0;
        $can = 0;
        $existe = array();
        $almacen="";
        $reasignado=true;

        foreach ($detalles as $nro => $detalle) {
            
            if( ($almacen!=$detalle['almacen'] || $detalle['reasignado']>1) && $reasignado){
                if($detalle['reasignado']==1){
                    $body .= '<tr>';
                    $body .= '<th class="left-right bot" align="left" colspan="5"> ALMACEN DE ORIGEN: ' . $detalle['almacen'] . '</th>';
                    $body .= '</tr>';
                    $body .= '<tr>
                    			<th width="16%" class="all" align="left">CODIGO</th>
                    			<th width="54%" class="all" align="left">DETALLE</th>
                    			<th width="8%" class="all" align="left">CANT.</th>
                    			<th width="10%" class="all" align="left">UNIDAD</th>
                    			<th width="12%" class="all" align="right">IMPORTE '.$valor_moneda.' </th>
                    		</tr>';
                    $almacen=$detalle['almacen'];
                }else{
                    $reasignado=false;
                    
                    $body .= '<tr>';
                    $body .= '<th class="left-right bot" align="left" colspan="5"> AREA DE ENTREGA </th>';
                    $body .= '</tr>';
                    $body .= '<tr>
                    			<th width="16%" class="all" align="left">CODIGO</th>
                    			<th width="54%" class="all" align="left">DETALLE</th>
                    			<th width="8%" class="all" align="left">CANT.</th>
                    			<th width="10%" class="all" align="left">UNIDAD</th>
                    			<th width="12%" class="all" align="right">IMPORTE '.$valor_moneda.' </th>
                    		</tr>';
                    $almacen=$detalle['almacen'];
                }
            }
            
            $precios = explode(',', $detalle['precio']);
            $cantidades = explode(',', $detalle['cantidad']);

            $pr = $db->select('*')
                     ->from('inv_productos a')
                     ->join('inv_unidades b', 'a.unidad_id = b.id_unidad')
                     ->where('a.id_producto',$detalle['producto_id'])
                     ->fetch_first();

            $can_imprimir=0;
            foreach ($precios as $key => $precio) {
                $cantidad = escape($cantidades[$key]);
                if($pr['unidad_id'] == $detalle['id_unidad']){
                    $unidad = $pr['unidad'];
                    $can = $cantidad;
                }else{
                    $pr = $db->select('*')->from('inv_asignaciones a')
                             ->join('inv_unidades b', 'a.unidad_id = b.id_unidad')
                             ->where(array('a.producto_id'=>$detalle['producto_id'],'a.unidad_id'=>$detalle['id_unidad']))->fetch_first();
                    //Validacion
                    if($pr['cantidad_unidad'])
                    {
                        $unidad = $pr['unidad'];
                        $can = $cantidad / $pr['cantidad_unidad'];
                    }
                }

                // $can = $can + $cantidades[$key];
                $subtotal = $subtotal + ($precio * $can);
                $total = $total + ($precio * $can);
                $can_imprimir+=$can;
            }
            $total_total = $total;

            $body .= '<tr height="2%" >';
            $body .= '<td class="left-right bot" align="left">' . $detalle['codigo']. '</td>';
            $body .= '<td class="left-right bot" align="left">' . escape($detalle['nombre_factura']) . '<br> <small>(LOTE: '. escape($detalle['lote']) .' VENC.: '. escape(date_decode($detalle['vencimiento'], $_institution['formato'])).')</small></td>';
            //$body .= '<td class="left-right bot" align="right">' . $detalle['descripcion']. '</td>';
            $body .= '<td class="left-right bot" align="right">' . number_format(($can_imprimir), 0, ',', '.') . '</td>';
            $body .= '<td class="left-right bot">' . $unidad . '</td>';
            $body .= '<td class="left-right bot" align="right">' . number_format(($subtotal), 2, ',', '.') . '</td>';
            $body .= '</tr>';

            $can = 0;$subtotal = 0;
        }
        // Obtiene el valor total
        $valor_total = number_format($total_total, 2, '.', '');
        // Obtiene los datos del monto total
        $conversor = new NumberToLetterConverter();
        $monto_textual = explode('.', $valor_total);
        $monto_numeral = $monto_textual[0];
        $monto_decimal = $monto_textual[1];
        $monto_literal = upper($conversor->to_word($monto_numeral));

        $valor_total = number_format($total_total, 2, ',', '.');
        
        $body = ($body == '') ? '<tr><td colspan="5" align="center" class="all">Este egreso no tiene detalle, es muy importante que todos los egresos cuenten con un detalle de venta.</td></tr>' : $body;
    // }





        $total_total_222=0;
        $body2 = '';
        $body2 .= '<tr>
        			<th width="20%" class="all" align="left">CLIENTE</th>
        			<th width="50%" class="all" align="left">DIRECCION</th>
        			<th width="10%" class="all" align="left">LUGAR</th>
        			<th width="10%" class="all" align="left">NRO. NOTA</th>
        			<th width="10%" class="all" align="left">TOTAL</th>
        		</tr>';
        
        foreach ($empleados as $nro => $detalle) {
            $total_total_222 += $detalle['monto_total'];

            $body2 .= '<tr height="2%" >';
            $body2 .= '<td class="left-right bot" align="left">' . $detalle['cliente']. '</td>';
            $body2 .= '<td class="left-right bot" align="left">' . escape($detalle['direccion']).'</td>';
            
            // if($detalle['plan_de_pagos']=='no'){
            //     $body2 .= '<td class="left-right bot" align="left">Al contado</td>';
            // }else{
            //     $body2 .= '<td class="left-right bot" align="left">A credito</td>';
            // }
            
            if($detalle['reasignado']=='1'){
                $body2 .= '<td class="left-right bot" align="left">Almacen</td>';
            }else{
                $body2 .= '<td class="left-right bot" align="left">Area de Entrega</td>';
            }
            
            $body2 .= '<td class="left-right bot" align="left">' . $detalle['nro_nota'] . '</td>';
            $body2 .= '<td class="left-right bot" align="right">' . number_format($detalle['monto_total'], 2, ',', '.') . '</td>';
            $body2 .= '</tr>';
        }
        
        $valor_total_222 = number_format($total_total_222, 2, '.', '');
        // Obtiene los datos del monto total
        $conversor = new NumberToLetterConverter();
        $monto_textual_222 = explode('.', $valor_total_222);
        $monto_numeral_222 = $monto_textual_222[0];
        $monto_decimal_222 = $monto_textual_222[1];
        $monto_literal_222 = upper($conversor->to_word($monto_numeral_222));

        $valor_total_222 = number_format($total_total_222, 2, ',', '.');
        
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
	</style>
	<table cellpadding="1">
      <tr>
        <td  colspan="1" align="center" bgcolor="#CCCCCC" ><h2></h2></td>
        <td  colspan="2" align="center" bgcolor="#CCCCCC" ><h2>HOJA DE SALIDA</h2></td>
        <td  colspan="1" align="center" bgcolor="#CCCCCC" ><h2>Nro. $nro_salida</h2></td>
      </tr>
      <tr>
        <td align="left" width="20%"><b>VENDEDORES:</b></td>
        <td align="left" width="30%" >$valor_empleado2</td>
        <td align="left" width="20%"><b>DISTRIBUIDOR:</b></td>
        <td align="left" width="30%">$valor_empleado</td>
      </tr>
    </table><br><br>

    <table cellpadding="3" class="bor">
		$body
		<tr>
			<th class="all" align="left" colspan="4">IMPORTE TOTAL $valor_moneda</th>
			<th class="all" align="right">$valor_total</th>
		</tr>
	</table>
	
    <p align="right">$monto_literal $monto_decimal /100</p>
    
    <table cellpadding="3" class="bor">
		$body2
		<tr>
			<th class="all" align="left" colspan="4">IMPORTE TOTAL $valor_moneda</th>
			<th class="all" align="right">$valor_total_222</th>
		</tr>
	</table>
	
	<p align="right">$monto_literal_222 $monto_decimal_222 /100</p>
    
    <table>
        <tr>
            <td align="right"><b>FECHA:</b></td>
            <td align="left">$valor_fecha</td>
            <td align="right"><b>FECHA ENTREGA:</b></td>
            <td align="left">____ / __ / __</td>
        </tr>
    </table>
    
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
            <td width="50%" class="none" align="center" >Recibí conforme:<br>$valor_empleado</td>
            <td width="50%" class="none" align="center" >Entregué conforme:<br>Nombre:_________________________ </td>
        </tr>
        <tr>
            <td width="50%" class="none" align="center" ></td>
            <td width="50%" class="none" align="center" ></td>
        </tr>
        <tr>
            <td width="50%" class="none" align="center" ></td>
            <td width="50%" class="none" align="center" ></td>
        </tr>
        <!--<tr>

            <td width="100%" class="none" align="center" colspan='2'>------------------------------------------------------</td>
        </tr>
        <tr>
            <td width="100%" class="none" align="center" colspan='2' >Responsable de carga:<br>Nombre:_________________________ </td>
        </tr>-->
    </table>
EOD;

// Imprime la tabla
    $pdf->writeHTML($tabla, true, false, false, false, '');

$style3 = array('width' => 1, 'cap' => 'round', 'join' => 'round', 'dash' => '2,10', 'color' => array(255, 0, 0));

}

// Genera el nombre del archivo
$nombre = 'orden_compra' . $id_orden . '_' . date('Y-m-d_H-i-s') . '.pdf';

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>
