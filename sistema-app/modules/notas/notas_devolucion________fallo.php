<?php
echo json_encode($_POST); //die();

// Verifica si es POST
if (is_post()) {
	// Verifica la existencia de los datos enviados
    if (isset($_POST['id_egreso']) && isset($_POST['id_cliente']) && isset($_POST['nit_ci']) && isset($_POST['nombre_cliente']) && 
        isset($_POST['productos']) && isset($_POST['nombres']) && isset($_POST['lotes']) && isset($_POST['vencimiento']) && 
        isset($_POST['cantidades']) && isset($_POST['unidad'])&&  isset($_POST['tipo']) ) {
		
		// Importa la libreria para convertir el numero a letra
        require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

        $id_egreso = trim($_POST['id_egreso']);
        
        $productos = (isset($_POST['productos'])) ? $_POST['productos'] : array();
        $lotes = (isset($_POST['lotes'])) ? $_POST['lotes'] : array();
        $vencimiento = (isset($_POST['vencimiento'])) ? $_POST['vencimiento'] : array();
        $cantidades = (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
        $precios = (isset($_POST['precios'])) ? $_POST['precios'] : array();
        $descuentos = (isset($_POST['descuentos'])) ? $_POST['descuentos'] : array();
        $tipo = trim($_POST['tipo']);
        
        // PARA LOS PAGOS
        $para_pagar = (isset($_POST['para_pagar'])) ? $_POST['para_pagar'] : array(); 
        $monto_pagar = (isset($_POST['monto_pagar'])) ? $_POST['monto_pagar'] : array(); 
        $egreso_pagar = (isset($_POST['egreso_pagar'])) ? $_POST['egreso_pagar'] : array(); 
        
        
        
        
        
        $nombre_cliente = trim($_POST['nombre_cliente']);
        $descripcion = trim($_POST['descripcion']);
        $nro_registros = trim($_POST['nro_registros']);
        $monto_total_ext = trim($_POST['monto_total']);
        
        
        
        foreach ($monto_pagar as $key222 => $para_pagarx) {
            $cuotax=$para_pagar[$key222];
            $idx=$egreso_pagar[$key222];
            $montox=$monto_pagar[$key222];
        
            $deuda_v = $db->query("SELECT SUM(i.monto)as saldo
                                   from inv_pagos_detalles i 
                                   inner join inv_pagos p ON id_pago=pago_id 
                                   where i.estado=1 AND movimiento_id=".$idx." AND p.tipo='Egreso'")
                                  ->fetch_first();
            
            $monto_total = $db->query(" SELECT SUM(cantidad*precio) as monto_total 
                                        from inv_egresos i 
                                        inner join inv_egresos_detalles ON egreso_id=id_egreso 
                                        where id_egreso='".$idx."' ")
                                    ->fetch_first();
            
            if ($deuda_v) {
                $saldo=$monto_total['monto_total']-$deuda_v['saldo'];
                    
                if ( $saldo<$montox ) {
                    set_notification('danger','La deuda es menor al monto que desea devolver!','Pr favor seleccione otra deuda para pagar con la devolucion.');
                    return redirect(back());
                }
            }
        }
        
        
        if ($tipo == 'Devolucion') {
            
            $egreso_modif =  $db->from('inv_egresos')
                                ->where('id_egreso', $id_egreso)
                                ->fetch_first();

            $egreso_no_venta =  $db->from('inv_egresos')
                                  ->where('nro_nota', $egreso_modif['nro_nota'])
                                  ->where('tipo', "Devolucion")
                                  ->fetch_first();

            if(!$egreso_no_venta){
                $data = array(
                    'fecha_egreso' =>  	$egreso_modif['fecha_egreso'],
                    'hora_egreso' =>  	$egreso_modif['hora_egreso'],
                    'fecha_habilitacion' =>  	$egreso_modif['fecha_habilitacion'],
                    'fecha_factura' =>  $egreso_modif['fecha_factura'],
                    'tipo' =>  	        "Devolucion",
                    'tipo_inicial' =>  	"Devolucion",
                    'distribuir' =>  	$egreso_modif['distribuir'],
                    'provisionado' =>  	$egreso_modif['provisionado'],
                    'descripcion' =>  	$egreso_modif['descripcion'],
                    'nro_nota' =>  	$egreso_modif['nro_nota'],
                    'nro_factura' =>  	$egreso_modif['nro_factura'],
                    'nro_movimiento' =>  	$egreso_modif['nro_movimiento'],
                    'nro_autorizacion' =>  	$egreso_modif['nro_autorizacion'],
                    'codigo_control' =>  	$egreso_modif['codigo_control'],
                    'fecha_limite' =>  	$egreso_modif['fecha_limite'],
                    'monto_total' =>  	0,
                    'descuento_porcentaje' =>  	0,
                    'descuento_bs' =>  	0,
                    'monto_total_descuento' =>  0,
                    'tipo_pago' =>  	$egreso_modif['tipo_pago'],
                    'nro_pago' =>  	$egreso_modif['nro_pago'],
                    'cliente_id' =>  	$egreso_modif['cliente_id'],
                    'nombre_cliente' =>  	$egreso_modif['nombre_cliente'],
                    'nit_ci' =>  	$egreso_modif['nit_ci'],
                    'nro_registros' =>  	0,
                    'estadoe' =>  	$egreso_modif['estadoe'],
                    'coordenadas' =>  	$egreso_modif['coordenadas'],
                    'observacion' =>  	$egreso_modif['observacion'],
                    'dosificacion_id' =>  	$egreso_modif['dosificacion_id'],
                    'almacen_id' =>  	$egreso_modif['almacen_id'],
                    'almacen_id_s' =>  	$egreso_modif['almacen_id_s'],
                    'empleado_id' =>  	$egreso_modif['empleado_id'],
                    'vendedor_id' =>  	$egreso_modif['vendedor_id'],
                    'codigo_vendedor' =>  	$egreso_modif['codigo_vendedor'],
                    'motivo_id' =>  	$egreso_modif['motivo_id'],
                    'duracion' =>  	$egreso_modif['duracion'],
                    'grupo' =>  	$egreso_modif['grupo'],
                    'descripcion_venta' =>  	$egreso_modif['descripcion_venta'],
                    'ruta_id' =>  	$egreso_modif['ruta_id'],
                    'estado' =>  	$egreso_modif['estado'],
                    'plan_de_pagos' =>  	$egreso_modif['plan_de_pagos'],
                    'ingreso_id' =>  	$egreso_modif['ingreso_id'],
                    'preventa' =>  	$egreso_modif['preventa'],
                    'factura' =>  	$egreso_modif['factura']
                );
                $id_egreso_no_venta=$db->insert('inv_egresos', $data);
            }else{
                $id_egreso_no_venta=$egreso_no_venta['id_egreso'];
            }

            /************************/
            /************************/
            /************************/
            
            foreach ($monto_pagar as $key222 => $para_pagarx) {
                $cuotax=$para_pagar[$key222];
                $idx=$egreso_pagar[$key222];
                $montox=$monto_pagar[$key222];
            
                $deuda_v = $db->query("SELECT *
                                       from inv_pagos_detalles i 
                                       inner join inv_pagos p ON id_pago=pago_id 
                                       where i.estado=0 AND movimiento_id=".$idx." AND p.tipo='Egreso'")
                                      ->fetch();
                
                foreach ($deuda_v as $key333 => $deuda_w) {
                    if($montox>0){
                        if($montox>$deuda_w['monto']){
                            $monto_reg=$deuda_w['monto'];
                            $montox=$montox-$deuda_w['monto'];
                            $monto_adicional=0;
                        }else{
                            $monto_reg=$montox;
                            $monto_adicional=$deuda_w['monto']-$montox;
                            $montox=0;
                        }
                        $data_detalle = array(
                            'monto'	=>$monto_reg,
                            'estado'=>'1',	
                            'fecha_pago' => date("Y-m-d"),	
                            'tipo_pago'	=>"DEVOLUCION",
                            'nro_pago'=>$egreso_modif['nro_nota'],
                            'empleado_id'=>$_user['persona_id'],	
                            'deposito'=>"inactivo",	
                            'codigo'=>"0"
                        );
                        $condicion = array('id_pago_detalle' => $deuda_w['id_pago_detalle']);
                        $db->where($condicion)->update('inv_pagos_detalles', $data_detalle);

                        //crear una nueva cuota por el saldo
                        
                        if($montox<=0 && $monto_adicional>0){
                            $cl = array(
                                'pago_id'=>	$deuda_w['pago_id'],
                                'fecha'	=>	$deuda_w['fecha'],
                                'monto'	=>	$monto_adicional,
                                'estado'=>	0,
                                'fecha_pago'=>'0000-00-00',	
                                'tipo_pago'	=> '',
                                'nro_pago'	=>	'',
                                'nro_cuota'	=>	0,
                                'empleado_id'=>	0,
                                'deposito'	=>	'inactivo',
                                'fecha_deposito'=>	'0000-00-00',
                                'codigo'	=> 0
	                        );
                			$db->insert('inv_pagos_detalles',$cl);
                        }
                    }
                }
                
                $deuda_v = $db->query("SELECT *
                                       from inv_pagos p 
                                       where movimiento_id=".$idx." AND p.tipo='Egreso'")
                                      ->fetch();
                
                if(!$deuda_v){
                    $cl = array(
                        'movimiento_id'	=>$idx,
                        'interes_pago'=>0,	
                        'tipo'=> 'Egreso'
                    );
        			$nro_pago=$db->insert('inv_pagos_detalles',$cl);
                }else{
                    $nro_pago=$deuda_v['id_pago'];
                }
                
                if($montox>0){
                    $cl = array(
                        'pago_id'=>	$nro_pago,
                        'fecha'	=>	date("Y-m-d"),
                        'monto'	=>	$montox,
                        'estado'=>	1,
                        'fecha_pago'=> date("Y-m-d"),	
                        'tipo_pago'	=> 'DEVOLUCION',
                        'nro_pago'	=>	$egreso_modif['nro_nota'],
                        'nro_cuota'	=>	0,
                        'empleado_id'=>	$_user['persona_id'],
                        'deposito'	=>	'inactivo',
                        'fecha_deposito'=>	'0000-00-00',
                        'codigo'	=> 0
                    );
        			$db->insert('inv_pagos_detalles',$cl);
                }
            }
        
            $ingreso = array(
                        'fecha_ingreso'     => date('Y-m-d'),
                        'hora_ingreso'      => date('H:i:s'),
                        'tipo'              => 'Devolucion',
                        'descripcion'       => 'Devolucion: nro_nota ' . $egreso_modif['nro_nota'],
                        'monto_total'       => $monto_total_ext,
                        'descuento'         => 0,
                        'monto_total_descuento' => 0,
                        'nombre_proveedor'  => $nombre_cliente,
                        'nro_registros'     => $nro_registros,
                        'almacen_id'        => $egreso_modif['almacen_id'],
                        'empleado_id'       => $_user['persona_id'],
                        'egreso_id'         => $egreso_modif['id_egreso'],
                        'tipo_devol'        => 'notas',
                        'nro_movimiento'    => 0 // + 1
                    );
                    // Guardamos el ingreso
                    $id_ingreso = $db->insert('inv_ingresos', $ingreso);

            //Creamos y guardamos el detalle del ingreso
            foreach($productos as $nro => $producto){
                $unidad2 = $db->select('id_unidad')
                              ->from('inv_unidades')
                              ->where('unidad',$unidad[$nro])
                              ->fetch_first();
                              
                $unidad3 = $unidad2['id_unidad'];
                
                echo $cantidad = $cantidades[$nro];
                
                $detalles_query = "SELECT * 
                                          from inv_egresos_detalles 
                                          where egreso_id='".$id_egreso."'
                                                AND producto_id='".$productos[$nro]."'
                                                AND lote='".$lotes[$nro]."'
                                                AND vencimiento='".$vencimiento[$nro]."'
                                                AND precio='".$precios[$nro]."'
                                        ";
            
                $detalles_modif = $db->query($detalles_query)->fetch();
                
                $cantidad_descontar=$cantidad;
                
                foreach ($detalles_modif as $key222 => $detal_modif) {
                
                    if($detal_modif['cantidad']<$cantidad_descontar){
                        $cantidad_egreso=$detal_modif['cantidad'];
                        $cantidad_modificar=0;
                        $cantidad_descontar=$cantidad_descontar-$detal_modif['cantidad'];
                    }else{
                        $cantidad_egreso=$cantidad_descontar;
                        $cantidad_modificar=$detal_modif['cantidad']-$cantidad_descontar;
                        $cantidad_descontar=0;
                    }
                    
                    if($cantidad_egreso>0){
                        $ing_detalle = $db->query("select *
                                                  from inv_ingresos_detalles
                                                  where id_detalle='".$detal_modif['ingresos_detalles_id']."'")
                                          ->fetch_first();
                                      
                        $detalle = array(
                            'cantidad'      => $cantidad_egreso,
                            'lote_cantidad' => $cantidad_egreso,
                            'costo'         => $ing_detalle['costo'],
                            'lote'          => $ing_detalle['lote'],
                            'producto_id'   => $ing_detalle['producto_id'],
                            'ingreso_id'    => $id_ingreso,
                            'vencimiento'   => $ing_detalle['vencimiento'],
                            'dui'           => 0,
                            'contenedor'    => 0,
                            'factura'       => 0,
                            'factura_v'     => 0,
                            'almacen_id'    => $egreso_modif['almacen_id'],
                            'IVA'           => 'no',
                        );
                    
                        // Guarda la informacion
                        $id_detalle = $db->insert('inv_ingresos_detalles', $detalle);
                        
                        /*************************/
                        
                        $detalle = array(
                            'precio'                => 	$detal_modif['precio'],
                            'unidad_id'             => 	$detal_modif['unidad_id'],
                            'cantidad'              => 	$cantidad_egreso,
                            'descuento'             => 	$detal_modif['descuento'],
                            'producto_id'           => 	$detal_modif['producto_id'],
                            'egreso_id'             => 	$id_egreso_no_venta,
                            'promocion_id'          => 	$detal_modif['promocion_id'],
                            'asignacion_id'         => 	$detal_modif['asignacion_id'],
                            'lote'                  => 	$detal_modif['lote'],
                            'vencimiento'           => 	$detal_modif['vencimiento'],
                            'detalle_ingreso_id'    => 	$detal_modif['detalle_ingreso_id'],
                            'ingresos_detalles_id'  => 	$detal_modif['ingresos_detalles_id'],
                        );
                    
                        // Guarda la informacion
                        $id_detalle = $db->insert('inv_egresos_detalles', $detalle);
                       
                        $data_detalle = array(
                			'cantidad'=>($detal_modif['cantidad']-$cantidad_egreso),
                		);
 
                        
                        $condicion = array('id_detalle' => $detal_modif['id_detalle']);
                        $db->where($condicion)->update('inv_egresos_detalles', $data_detalle);
        
                    }
                }
                
                // Guarda Historial
                $data = array(
                    'fecha_proceso' => date("Y-m-d"),
                    'hora_proceso' => date("H:i:s"),
                    'proceso' => 'c',
                    'nivel' => 'l',
                    'direccion' => '?/operaciones/notas_guardar',
                    'detalle' => 'Se creo el detalle de ingreso con identificador numero ' . $id_detalle ,
                    'usuario_id' => $_SESSION[user]['id_user']
                );
                $db->insert('sys_procesos', $data) ;
            }
        }

        $_SESSION[temporary] = array(
            'alert' => 'success',
            'title' => 'Se realizo la devolucion!',
            'message' => 'El registro se realiz贸 correctamente.'
        );
        //enviamos a imprimir el nuevo egreso
        if ($tipo == 'Reposicion') {
            $_SESSION['imprimir'] = $id_egreso;
        }
        if($tipo == 'Devolucion') {
            $_SESSION['imprimir'] = $egreso_modif['id_egreso'];
        }

        redirect('?/notas/mostrar');

		// Envia respuesta
        echo json_encode($id_egreso);

    } else {
		// Envia respuesta
		echo 'error';
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>