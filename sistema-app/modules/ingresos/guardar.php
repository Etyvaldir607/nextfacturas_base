<?php

//echo json_encode($_POST); 
//die();

// Verifica si es una peticion post
if (is_post()) {
	// Verifica la existencia de los datos enviados
	if (isset($_POST['almacen_id']) && isset($_POST['nombre_proveedor']) && isset($_POST['descripcion']) && isset($_POST['nro_registros']) && isset($_POST['monto_total']) && isset($_POST['productos']) && isset($_POST['cantidades']) && isset($_POST['costos'])) {
		// Obtiene los datos del producto
		$almacen_id = trim($_POST['almacen_id']);
		$descripcion = trim($_POST['descripcion']);
		$nro_registros = trim($_POST['nro_registros']);
		$monto_total = trim($_POST['monto_total']);
		$des_reserva = trim($_POST['des_reserva']);
		$des_reserva = trim($_POST['des_reserva']);
		$nro_facturag = trim($_POST['nro_facturag']);
		$fecha_factura = trim($_POST['fecha_factura']);;
		$fecha_factura = $fecha_format=(is_date($fecha_factura)) ? date_encode($fecha_factura): "0000-00-00";
				
		$IVAg = trim($_POST['IVAg']);
		
		$nombre_proveedor = trim($_POST['nombre_proveedor']);
		
// 		$tipo_pago = trim($_POST['tipo_pago']);
// 		$nro_pago = trim($_POST['nro_pago']);
		
		$id_proveedor = 0;
		$VecProveedor=explode("|",$nombre_proveedor);
        if(isset($VecProveedor[1])){
        	$nombre_proveedor = $VecProveedor[1];	
        	$id_proveedor = $VecProveedor[0];	
        }
        
		if ($_POST['reserva']) {
			$reserva = 1;
		} else {
			$reserva = 0;
		}
		//descuento
		// 		$descuento = trim($_POST['descuento']);
		// 		$total_importe_descuento = trim($_POST['total_importe_descuento']);

		$productos = (isset($_POST['productos'])) ? $_POST['productos'] : array();
		$cantidades = (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
		$vencimientos = (isset($_POST['fechas'])) ? $_POST['fechas'] : array();
		$lotes = (isset($_POST['lotes'])) ? $_POST['lotes'] : array();
		
		$costos = (isset($_POST['costos'])) ? $_POST['costos'] : array();
		$duis = (isset($_POST['duis'])) ? $_POST['duis'] : array();
		$contenedores = (isset($_POST['contenedores'])) ? $_POST['contenedores'] : array();

		// Obtiene el almacen
		// $almacen = $db->from('inv_almacenes')->where('id_almacen', $almacen_id)->fetch_first();

		$nro_cuentas = trim($_POST['nro_cuentas']);
		
		
		$plan = trim($_POST['forma_pago']); //1 contado //2 plan de pagos //3 pago anticipado
		switch($plan){
		    case 1:
    			$tipoP="Contado";
    			break;
		    case 2:
    			$fechas = (isset($_POST['fecha'])) ? $_POST['fecha']: array();
    			$cuotas = (isset($_POST['cuota'])) ? $_POST['cuota']: array();
    			$tipoP="A Credito";
    			break;
		    case 3:
    			$tipoP="Pago Anticipado";
    			break;
		}
		
		
		$movimiento = $db->query("SELECT MAX(nro_movimiento) as max FROM inv_ingresos WHERE tipo = 'Compra'")->fetch_first()['max'];
		if($movimiento == ''){
            $movimiento = 0;    
        }
// 		$movimiento = generarMovimiento($db, $_user['persona_id'], 'CP', $almacen_id);
		
		
		
		
		
    	$observacion=$_POST['observacion'];
    	$banco=$_POST['banco_id'];
    	$nro_doc=$_POST['nro_doc'];
    	$tipo_pago=$_POST['tipo_pago'];
    	
    	
		
		
		
		// Obtiene el almacen
		$almacen = $db->from('inv_almacenes')->where('id_almacen', $almacen_id)->fetch_first();
		
		// Instancia el ingreso
		$ingreso = array(
			'fecha_ingreso' => date('Y-m-d'),
			'hora_ingreso' => date('H:i:s'),
			'tipo' => 'Compra',
			'nro_movimiento' => $movimiento + 1,
			'descripcion' => $descripcion,
			'monto_total' => $monto_total,
			'nombre_proveedor' => $nombre_proveedor,
			'nro_registros' => $nro_registros,
			'transitorio' => $reserva,
			'des_transitorio' => $des_reserva,
			'plan_de_pagos' => ($plan == 'si') ? 'si':'si',
			'empleado_id' => $_user['persona_id'],
			'almacen_id' => $almacen_id,
			'tipo_pago' => $tipo_pago,
			'nro_pago' => $nro_pago,
			'fecha_factura'=>$fecha_factura,
			'nro_factura' => $nro_facturag,
			'IVA' => $IVAg,
			'proveedor_id' => $id_proveedor,
		);
		// Guarda la informacion
		$ingreso_id = $db->insert('inv_ingresos', $ingreso);
		//$db->insert('inv_proveedores',array('proveedor'=> $nombre_proveedor,'direccion'=>''));
		
		
		// Guarda Historial
		$data = array(
			'fecha_proceso' => date("Y-m-d"),
			'hora_proceso' => date("H:i:s"),
			'proceso' => 'c',
			'nivel' => 'l',
			'direccion' => '?/ingresos/guardar',
			'detalle' => 'Se creo ingreso con identificador numero ' . $ingreso_id,
			'usuario_id' => $_SESSION[user]['id_user']
		);
		$db->insert('sys_procesos', $data);
		
		
		foreach ($productos as $nro => $elemento) {
			$subdate=explode("/",$vencimientos[$nro]);
			$vencimientos[$nro] = $subdate[2]."-".$subdate[1]."-".$subdate[0];
			//new DateTime($vencimientos[$nro]);
			//echo $vencimientos[$nro] = $fecha->format('Y-m-d');
			// $Cantidad = $db->query("SELECT COUNT(id_detalle)AS cantidad FROM inv_ingresos_detalles WHERE producto_id='{$productos[$nro]}' LIMIT 1")->fetch_first()['cantidad'];
			// Forma el detalle
			if ($_POST['fv'][$nro] == 'true') {
				$facV = 1;
			} 
			if ($_POST['fv'][$nro] == 'false') {
				$facV = 0;
			}

			$detalle = array(
				'cantidad' => (isset($cantidades[$nro])) ? $cantidades[$nro] : 0,
				'costo' => (isset($costos[$nro])) ? $costos[$nro] : 0,
				'vencimiento' => (isset($vencimientos[$nro])) ? $vencimientos[$nro] : 0,
				'dui' => (isset($duis[$nro])) ? $duis[$nro] : 0,
			    'lote2' => (isset($lotes[$nro])) ? $lotes[$nro] : 0,
				'factura' => $nro_facturag,
				'factura_v' => ($IVAg=="si")?1:0,
				'contenedor' => (isset($contenedores[$nro])) ? $contenedores[$nro] : 0,
				'producto_id' => $productos[$nro],
				'ingreso_id' => $ingreso_id,
				'lote' => (isset($lotes[$nro])) ? $lotes[$nro] : 0,
				'lote_cantidad' => (isset($cantidades[$nro])) ? $cantidades[$nro] : 0,
				'costo_sin_factura'=>0,
			);
			
			
			
			var_dump($detalle);
			
			
			
			
			// Guarda la informacion
			$id_detalle = $db->insert('inv_ingresos_detalles', $detalle);
			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"),
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/ingresos/guardar',
				'detalle' => 'Se creo ingreso detalle con identificador numero ' . $id_detalle,
				'usuario_id' => $_SESSION[user]['id_user']
			);

			$db->insert('sys_procesos', $data);
		}


		// ARMAMOS EN PLAN DE PAGOS
		switch($plan){
    		case 1:
            	$detallePlan = array(
        			'movimiento_id'=>$ingreso_id,
        			'interes_pago'=>0,
        			'tipo'=>'Ingreso',
        		);
        		// Guarda la informacion
            	$id_pago=$db->insert('inv_pagos', $detallePlan);
            
            	$detallePlan = array(
            			'pago_id'=>$id_pago,
            			'fecha' => date('Y-m-d'),
            			'monto' => $monto_total,			
            			'monto_programado' => $monto_total,			
            			'estado' => 1,		
            			'fecha_pago' => date('Y-m-d'),
            			'hora_pago' => date("H:i:s"),
            			
            			'tipo_pago' => $tipo_pago,
            			'nro_pago' => $nro_doc,			
            			'nro_cuota'=>0,
            			'empleado_id'=>$_user['persona_id'],
            			'deposito'=>"inactivo",
            			'fecha_deposito'=>'0000-00-00',
            
                		'codigo'=>0,	
                		'observacion_anulado'=>0,	
                		'fecha_anulado'=>'0000-00-00',	
                		'coordenadas'=>"",	
                		'ingreso_id'=>0,
            			'banco_id' => $banco,			
    
            			'observacion'=>$observacion,
            		);
            		
            		// Guarda la informacion
            	$db->insert('inv_pagos_detalles', $detallePlan);
            	
        		// Redirecciona a la pagina principal
        		redirect('?/ingresos/listar/'.$id_pago);
    		break;
    		
    		case 2:
            	// Instancia el ingreso
    			$ingresoPlan = array(
    				'movimiento_id' => $ingreso_id,
    				'interes_pago' => 0,
    				'tipo' => 'Ingreso',
    			);
    			// Guarda la informacion del ingreso general
    			$ingreso_id_plan = $db->insert('inv_pagos', $ingresoPlan);
    					
    			$nro_cuota=0;
    			for($nro2=0; $nro2<$nro_cuentas; $nro2++) {
    				$fecha_format=(is_date($fechas[$nro2])) ? date_encode($fechas[$nro2]): "0000-00-00";
    
    				$nro_cuota++;
    				
    				$detallePlan = array(
    					'nro_cuota' => $nro_cuota,
    					'pago_id' => $ingreso_id_plan,
    					'fecha' => $fecha_format,
    					'fecha_pago' => $fecha_format,
    					'empleado_id' => $_user['persona_id'],
    					'tipo_pago' => "",
    					'monto' => (isset($cuotas[$nro2])) ? $cuotas[$nro2]: 0,
    					'monto_programado' => (isset($cuotas[$nro2])) ? $cuotas[$nro2]: 0,
    					'estado'  => '0',
    					'nro_pago' => 0,
    				);
    
    				// Guarda la informacion
    				$db->insert('inv_pagos_detalles', $detallePlan);
    			}
        		// Redirecciona a la pagina principal
        		redirect('?/ingresos/listar');
        	break;
        	
    		case 3:
			    $pago_id= trim($_POST['pago_id_'.$id_proveedor]);
        
                if($pago_id!="") {
                    $pago_id1= trim($_POST['obs_'.$pago_id]);
                    $pago_id2= trim($_POST['nro_'.$pago_id]);
                    $pago_id3= trim($_POST['tipo_'.$pago_id]);
                    $pago_id4= trim($_POST['cuenta_'.$pago_id]);
                    $pago_id5= trim($_POST['monto_'.$pago_id]);
                    
                    $Condicion=[
                        'id_pago'=>$pago_id,
                    ];
                    $Datos=[
            			'movimiento_id'=>$ingreso_id,
            			'tipo'=>'Ingreso',
                    ];
                    $db->where($Condicion)->update('inv_pagos',$Datos);
                }

        		redirect('?/ingresos/listar');
			break;
		}

	} else {
		// Error 401
		require_once bad_request();
		exit;
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}
