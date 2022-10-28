<?php
//  echo json_encode($_POST); die();

// Verifica si es una peticion post
if (is_post()) {
	// Verifica la existencia de los datos enviados
	if (isset($_POST['almacen_id']) && isset($_POST['tipo']) && isset($_POST['descripcion']) && isset($_POST['productos']) && isset($_POST['nombres']) && 
	    isset($_POST['cantidades']) && isset($_POST['precios']) && isset($_POST['nro_registros']) && isset($_POST['monto_total'])) {
		// Obtiene los datos de la venta
		$almacen_id = trim($_POST['almacen_id']);
		$tipo = trim($_POST['tipo']);
		$descripcion = trim($_POST['descripcion']);
		$productos = (isset($_POST['productos'])) ? $_POST['productos'] : array();
		$nombres = (isset($_POST['nombres'])) ? $_POST['nombres'] : array();
		$cantidades = (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
		$precios = (isset($_POST['precios'])) ? $_POST['precios'] : array();
		$preciossi = (isset($_POST['preciossi'])) ? $_POST['preciossi'] : array(); // sin factura
		$nro_registros = trim($_POST['nro_registros']);
		$monto_total = trim($_POST['monto_total']);
		$monto_total_si = trim($_POST['monto_total_si']); // sin factura
		$lote			= (isset($_POST['lote'])) ? $_POST['lote'] : array();
		$vencimiento	= (isset($_POST['vencimiento'])) ? $_POST['vencimiento'] : array();
		$visitador_id_empleado = trim($_POST['visitador']);

		if($tipo != 'Egreso especial'){
		    $visitador_id_empleado = 0;
		    $nombres = '';
		    $nro_nota = 0;
		}else{
		    $visitador = $db->query("SELECT * FROM sys_empleados where id_empleado = '$visitador_id_empleado'")->fetch_first();
		    $nombres = $visitador['paterno'].' '.$visitador['materno'].' '.$visitador['nombres'];
		    $nro = $db->query("SELECT max(nro_nota) as nro FROM inv_egresos")->fetch_first();
		    $nro_nota = $nro['nro'] + 1;
		}

        // $movimientoE = generarMovimiento($db, $_user['persona_id'], 'BJ', $almacen_id);
        $nro_correlativo = $db->query(" SELECT MAX(nro_movimiento) as max 
                                        FROM inv_egresos 
                                        WHERE tipo = 'Baja' or tipo = 'Baja Especial'
                                      ")->fetch_first()['max'];
        if($movimiento == ''){
            $movimiento = 0;    
        }
        
		// Instancia la venta
		$venta = array(
			'fecha_egreso' => date('Y-m-d'),
			'hora_egreso' => date('H:i:s'),
			'tipo' => $tipo,
			'cliente_id' => $visitador_id_empleado,
			'provisionado' => 'N',
			'descripcion' => 'Egreso especial',
			'descripcion_venta' => $descripcion,
			'nro_factura' => 0,
			'nro_movimiento' => $nro_correlativo +1, 
			'nro_autorizacion' => 0,
			'codigo_control' => '',
			'fecha_limite' => '0000-00-00',
			'monto_total' => $monto_total,
			'nombre_cliente' => $nombres,
			'nit_ci' => 0,
			'nro_registros' => $nro_registros,
			'dosificacion_id' => 0,
			'almacen_id' => $almacen_id,
			'empleado_id' => $_user['persona_id'],
			'nro_nota'  => $nro_nota
		);

		// Guarda la informacion
		$egreso_id = $db->insert('inv_egresos', $venta);
		
		// Guarda en el historial
		$data = array(
			'fecha_proceso' => date("Y-m-d"),
			'hora_proceso' => date("H:i:s"),
			'proceso' => 'c',
			'nivel' => 'l',
			'direccion' => '?/egresos/guardar',
			'detalle' => 'Se inserto inventario egreso con identificador numero ' . $egreso_id,
			'usuario_id' => $_SESSION[user]['id_user']
		);
		$db->insert('sys_procesos', $data);

		// Recorre los productos
		foreach ($productos as $nro => $elemento) {
			$id_unidad = $db->query('SELECT unidad_id 
			                         FROM inv_productos 
			                         WHERE id_producto = "'.trim($productos[$nro]).'" 
			                         LIMIT 1 
			                         ')->fetch_first();
			
			// Forma el detalle
			$loteX=explode(': ',$lote[$nro])[1];
			$vencX=explode(': ',$vencimiento[$nro])[1];
			
			/*****************************************/

            $ingresos = $db->query('SELECT *
                                    FROM inv_ingresos_detalles
                                    LEFT JOIN inv_ingresos i ON id_ingreso=ingreso_id
                                    WHERE lote="'.$loteX.'" AND producto_id="'.$productos[$nro].'" AND i.almacen_id="'.$almacen_id.'" 
                                ')->fetch();

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
                
                if($cantidad_egreso>0){
                    $detalle = array(
        			    'cantidad'      => $cantidad_egreso,
        				'precio'        => $precios[$nro],
                        'descuento'     => 0,
                        'unidad_id'     => $id_unidad['unidad_id'],
                        'producto_id'   => $productos[$nro],
                        'egreso_id'     => $egreso_id,
                        'lote'          => $loteX,
                        'vencimiento'   => $vencX,
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
				'direccion' => '?/egresos/guardar',
				'detalle' => 'Se inserto inventario egreso detalle con identificador numero ' . $id_detalle,
				'usuario_id' => $_SESSION[user]['id_user']
			);
			$db->insert('sys_procesos', $data);
		}
    	
    	// Instancia la variable de notificacion
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Adición satisfactoria!',
			'message' => 'El registro se guardó correctamente.'
		);
        
        $egreso_id_especial = $egreso_id;
		// Redirecciona a la pagina principal
		
		if($tipo == 'Egreso especial'){
		    redirect('?/egresos/listar/'.$egreso_id_especial);
		}else{
		    redirect('?/egresos/listar');
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
