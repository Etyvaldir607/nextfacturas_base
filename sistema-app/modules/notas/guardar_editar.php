<?php
// echo json_encode($_POST); die();

if (is_ajax() && is_post()) {
	// echo json_encode($_POST); die();
	// Verifica la existencia de los datos enviados 
	if (isset($_POST['id_egreso']) && 
	    isset($_POST['nit_ci']) && isset($_POST['nombre_cliente']) && isset($_POST['productos']) && isset($_POST['nombres']) && 
	    isset($_POST['cantidades']) && isset($_POST['precios']) && isset($_POST['nro_registros']) && 
	    isset($_POST['monto_total']) && isset($_POST['almacen_id'])) {
	        
		// Importa la libreria para convertir el numero a letra
		require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

		// Obtiene los datos de la nota
		$id_egreso_ext	= trim($_POST['id_egreso']);
		$nit_ci 		= trim($_POST['nit_ci']);
		$nombre_cliente	= trim($_POST['nombre_cliente']);
		$id_cliente 	= trim($_POST['id_cliente']);
		$productos 		= (isset($_POST['productos'])) ? $_POST['productos'] : array();
		$nombres 		= (isset($_POST['nombres'])) ? $_POST['nombres'] : array();
		$cantidades		= (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
		$unidad 		= (isset($_POST['unidad'])) ? $_POST['unidad'] : array();
		$precios 		= (isset($_POST['precios'])) ? $_POST['precios'] : array();
		$nro_registros 	= trim($_POST['nro_registros']);
		
		//$monto_total 	= trim($_POST['monto_total']);
		
		$almacen_id 	= trim($_POST['almacen_id']);
		$lote			= (isset($_POST['lote'])) ? $_POST['lote'] : array();
		$vencimiento	= (isset($_POST['vencimiento'])) ? $_POST['vencimiento'] : array();
		
		// para tipo de pago
		$nro_pago = trim($_POST['nro_pago']);
		$distribuir = trim($_POST['distribuir']);
        $observacion = trim($_POST['observacion']);

        
        $monto_total=0;
        foreach ($productos as $nro => $elemento) {
			$cantidad = $cantidades[$nro];
			$precio = $precios[$nro];
            $monto_total=$monto_total+($cantidad*$precio);
        }


        $egreso_externo=$db->query("SELECT *
                                  FROM inv_egresos
                                  LEFT JOIN inv_pagos ON movimiento_id = id_egreso
                                  WHERE id_egreso='".$id_egreso_ext."'
                                ")->fetch_first();
        
        $asignacion_clienteX=$db->query("select ac.*
                                         from inv_asignaciones_clientes ac 
                                         WHERE egreso_id='".$id_egreso_ext."'
                                         ")->fetch_first();
        
        $sw_asignacion_clienteX=false;
        if($asignacion_clienteX){
            if($asignacion_clienteX['nro_salida']<=0){
                $sw_asignacion_clienteX=true;
            }
        }else{
            $sw_asignacion_clienteX=true;
        }

        if($egreso_externo['estadoe']==2 && $sw_asignacion_clienteX && $egreso_externo['distribuir']!=$distribuir){ 
            
            $db->delete()->from('inv_asignaciones_clientes')->where('egreso_id',$id_egreso_ext)->execute();
            
            if($distribuir=='N'){
                $venta = $db->query('select MAX(nro_salida)as nro_salida
                                         from inv_asignaciones_clientes')
                            ->fetch_first();
    
                $asignacion = array(
                    'egreso_id'         => $id_egreso_ext,
                    'distribuidor_id'   => $_user['persona_id'],
                    'fecha_entrega'     => date('Y-m-d H:i:s'),
                    'estado_pedido'     => 'salida',
                    'empleado_id'       => $_user['persona_id'],
                    'estado'            => 'A',
                    'fecha_hora_salida' => date('Y-m-d H:i:s'), 
                    //'nro_salida'        => ($venta['nro_salida']+1)
                    'nro_salida'        => -1
                );
                // Guardamos el asignacion
                $id_asignacion = $db->insert('inv_asignaciones_clientes', $asignacion);
            }
            
        }else{
            $distribuir=$egreso_externo['distribuir'];
        }
                        
        		
		//Cuentas
		$tipo_pago = (isset($_POST['tipo_pago'])) ? trim($_POST['tipo_pago']) : 'EFECTIVO';

        $empleado_grupo = $db->query("   select id_cliente_grupo 
                                    from inv_clientes_grupos
                                    WHERE vendedor_id='".$_POST['empleado']."'
                                 ")->fetch_first();
                        
		$nro_cuentas = (isset($_POST['nro_cuentas'])) ? trim($_POST['nro_cuentas']) : 1;
		
		$plan = (isset($_POST['forma_pago'])) ? trim($_POST['forma_pago']) : '';
		$plan = ($plan == "2") ? "si" : "no";
		
		if ($plan == "si") {
			$fechas = (isset($_POST['fecha'])) ? $_POST['fecha'] : array();
			$cuotas = (isset($_POST['cuota'])) ? $_POST['cuota'] : array();
			$estado_pago = (isset($_POST['estado_pago'])) ? $_POST['estado_pago'] : array();
		}
		else{
			$fechas_aux = $egreso_externo['fecha_egreso'];
			$fechas_aux = explode("-",$fechas_aux);
			$fechas[0] = $fechas_aux[2]."-".$fechas_aux[1]."-".$fechas_aux[0];
			$cuotas[0] = $monto_total;
			$estado_pago[0] = ($egreso_externo['estadoe']==2) ? 0 : 1;
		}

		$descuento_porc = isset($_POST['descuento_porc']) ? trim($_POST['descuento_porc']) : 0;
		$descuento_bs = (isset($_POST['descuento_bs'])) ? clear($_POST['descuento_bs']) : 0;
		$total_importe_descuento = trim($_POST['total_importe_descuento']);

		// Obtiene el numero de nota
		$nro_facturax = $db->query(" select IFNULL(MAX(nro_nota),0) + 1 as nro_factura 
                                    from inv_egresos 
                                 ")->fetch_first();
                        //where tipo = 'Venta' and provisionado = 'S'
		if($nro_facturax){
		    $nro_factura = $nro_facturax['nro_factura'];
        }else{
            $nro_factura = 1;
        }
        
		// Define la variable de subtotales
		$subtotales = array();

		// Obtiene la moneda
		$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
		$moneda = ($moneda) ? $moneda['moneda'] : '';

		// Obtiene los datos del monto total
		$conversor = new NumberToLetterConverter();
		$monto_textual = explode('.', $monto_total);
		$monto_numeral = $monto_textual[0];
		$monto_decimal = $monto_textual[1];
		$monto_literal = ucfirst(strtolower(trim($conversor->to_word($monto_numeral))));
        
		// Instancia la nota
		$nota = array(
			'distribuir'=>$distribuir,
			'monto_total' => $monto_total,
			'nit_ci' => $nit_ci,
			'nombre_cliente' => strtoupper($nombre_cliente),
			'cliente_id' => $id_cliente,
			'nro_registros' => $nro_registros,
			'almacen_id' => $almacen_id,
			'observacion' => '',
			'empleado_id' => $_user['persona_id'],
			'vendedor_id' => $_POST['empleado'],
			'codigo_vendedor'=>$empleado_grupo['id_cliente_grupo'],
			'plan_de_pagos' => $plan,
			'descuento_porcentaje' => $descuento_porc,
			'descuento_bs' => $descuento_bs,
			'monto_total_descuento' => $total_importe_descuento,
			'tipo_pago' => $tipo_pago,
			'nro_pago' => $nro_pago,
			'descripcion_venta' => $observacion,
		);

		$condicion = array('id_egreso' => $id_egreso_ext);
        $db->where($condicion)->update('inv_egresos', $nota);
        		
		// Guarda en el historial
		$data = array(
			'fecha_proceso' => date("Y-m-d"),
			'hora_proceso' => date("H:i:s"),
			'proceso' => 'c',
			'nivel' => 'l',
			'direccion' => '?/notas/guardar',
			'detalle' => 'Se modifico el inventario egreso con identificador numero ' . $id_egreso_ext,
			'usuario_id' => $_SESSION[user]['id_user']
		);
		$db->insert('sys_procesos', $data);








        $egresosdets=$db->query("SELECT *
                              FROM inv_egresos
                              LEFT JOIN inv_egresos_detalles ON egreso_id = id_egreso
                              WHERE id_egreso='".$id_egreso_ext."'
                            ")->fetch();
    
        foreach ($egresosdets as $nro => $egresosdet) {
            /*****************************************/
            //ACTUALIZAR LOTE CANTIDAD
            /*****************************************/
            $ingresod = $db->query('SELECT *
                                    FROM inv_ingresos_detalles
                                    WHERE id_detalle="'.$egresosdet['ingresos_detalles_id'].'" 
                                ')->fetch_first();

            $cantidad_modificar=$ingresod['lote_cantidad']+$egresosdet['cantidad'];
                    
            $datos = array(
    			'lote_cantidad' => $cantidad_modificar
    		);
        	
        	$condicion = array('id_detalle' => $egresosdet['ingresos_detalles_id']);
        	$db->where($condicion)->update('inv_ingresos_detalles', $datos);
        }
        $db->delete()->from('inv_egresos_detalles')->where('egreso_id',$id_egreso_ext)->execute();
                
		// Recorre los productos
		foreach ($productos as $nro => $elemento) {
			
			// Forma el detalle
			$id_unidad = $db->query('   SELECT id_unidad 
			                            FROM inv_unidades 
			                            WHERE unidad = "'.trim($unidad[$nro]).'" 
			                            LIMIT 1 
			                       ')->fetch_first();
			
			/*****************************************/
            //ACTUALIZAR LOTE CANTIDAD
            /*****************************************/
            // Forma el detalle
			$vencX=$vencimientoxx;
			
			$ingreso_query='SELECT *
                            FROM inv_ingresos_detalles
                            LEFT JOIN inv_ingresos i ON id_ingreso=ingreso_id
                            WHERE lote="'.$lote[$nro].'" AND producto_id="'.$productos[$nro].'" AND i.almacen_id="'.$almacen_id.'" 
                            ';

            $ingresos = $db->query($ingreso_query)->fetch();

            $cantidad_descontar=$cantidades[$nro];
            
            foreach ($ingresos as $nro222 => $ingreso) {
                
                if($ingreso['lote_cantidad']<$cantidad_descontar){
                    $cantidad_egreso=$ingreso['lote_cantidad'];
                    $cantidad_modificar=0;
                    $cantidad_descontar=$cantidad_descontar-$ingreso['lote_cantidad'];
                }else{
                    $cantidad_egreso=$cantidad_descontar;
                    $cantidad_modificar=$ingreso['lote_cantidad']-$cantidad_descontar;
                    $cantidad_descontar=0;
                }
                
                $datos = array(
        			'lote_cantidad' => $cantidad_modificar
        		);
        		$condicion = array('id_detalle' => $ingreso['id_detalle']);
        		$db->where($condicion)->update('inv_ingresos_detalles', $datos);
        		
        		/******************************************************/
                //echo $nro222." - ".$cantidad_egreso." - ".$lote[$nro];
                if($cantidad_egreso>0){
                    $detalle = array(
        				'cantidad' => $cantidad_egreso,
        				'precio' => $precios[$nro],
                        'unidad_id' => $id_unidad['id_unidad'],
    					'descuento' => 0,
        				'producto_id' => $productos[$nro],
        				'egreso_id' => $id_egreso_ext,
        				'lote' => $lote[$nro],
        				'vencimiento' => $vencimiento[$nro],
        				'ingresos_detalles_id'=>$ingreso['id_detalle'],
        			);
                    $id_detalle = $db->insert('inv_egresos_detalles', $detalle);
                }
            }
		    /************************************************/
            
			// Guarda en el historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"),
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/notas/guardar',
				'detalle' => 'Se inserto el inventario egreso detalle con identificador numero ' . $id,
				'usuario_id' => $_SESSION[user]['id_user']
			);
			$db->insert('sys_procesos', $data);
		}



        
        $db->query("delete 
                    from inv_pagos_detalles 
                    where pago_id='".$egreso_externo['id_pago']."' AND estado=0
                   ")->execute();

		// Cuentas
		
		$nro_cuota = 0;

		for ($nro2 = 0; $nro2 < $nro_cuentas; $nro2++) {
			if ($estado_pago[$nro2]=="0") {
    			if (isset($fechas[$nro2])) {
    				$fecha_format = $fechas[$nro2];
    			} else {
    				$fecha_format = "0000-00-00";
    			}
    			
    			$vfecha = explode("-", $fecha_format);
    			//var_dump($vfecha);
    
    			if (count($vfecha) == 3) {
    				$fecha_format = $vfecha[2] . "-" . $vfecha[1] . "-" . $vfecha[0];
    			} else {
    				$fecha_format = "0000-00-00";
    			}
    
    			$nro_cuota++;
    			
    			$dt=strtotime($fechas[0]);
                $fecha_comp = date("Y-m-d", $dt);
                
                	$detallePlan = array(
    					'nro_cuota' => $nro_cuota,
    					'pago_id' => $egreso_externo['id_pago'],
    					'fecha' => $fecha_format,
    					'fecha_pago' => $fecha_format,
    					'monto' => (isset($cuotas[$nro2])) ? $cuotas[$nro2] : 0,
    					'tipo_pago' => '',
    					'nro_pago' => '0',
    					'empleado_id' => 0,
    					'estado'  => '0',
    					'codigo' => 0
    				);
    			
    			// Guarda la informacion
    			$db->insert('inv_pagos_detalles', $detallePlan);
			}
		}
		
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
		
		// Instancia el objeto
        $respuesta = array(
            'egreso_id' => $id_egreso_ext,
            'recibo' => $recibo,
        );
        // Devuelve los resultados
        echo json_encode($respuesta);

		// Envia respuesta
// 		echo json_encode($id_egreso_ext);
	} else {
		// Envia respuesta
		echo 'error';
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}
