<?php

$ingreso_id_global=0;

//  echo json_encode($_POST); die();
// var_dump($_POST['atras']);
// exit();

// Verifica si es una peticion ajax y post
if (is_post()) {
	// Verifica la existencia de los datos enviados
	if (isset($_POST['nit_ci']) && isset($_POST['nombre_cliente']) && 
	    isset($_POST['productos']) && isset($_POST['nombres']) && isset($_POST['cantidades']) && isset($_POST['precios'])  && 
	    isset($_POST['nro_registros']) && isset($_POST['monto_total']) && isset($_POST['almacen_id'])) {

		// Importa la libreria para convertir el numero a letra
		require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        echo "ingresaaa";

        try {   
            //Se abre nueva transacción.
            $db->autocommit(false);
            $db->beginTransaction();
        
    		// Obtiene los datos de la proforma
    		$nit_ci = trim($_POST['nit_ci']);
            $nombre_cliente = trim($_POST['nombre_cliente']);
            $telefono   = trim($_POST['telefono_cliente']);
            $observacion = trim($_POST['observacion']);
            $prioridad = trim($_POST['prioridad']);
            $direccion = trim($_POST['direccion']);
            $nombres        = (isset($_POST['nombres'])) ? $_POST['nombres'] : array();
    		$nro_registros  = trim($_POST['nro_registros']);
    		$almacen_id     = trim($_POST['almacen_id']);
            $id_cliente     = trim($_POST['cliente_id']);
            
            $productosXX      = (isset($_POST['productos'])) ? $_POST['productos'] : array();
    		$cantidadesXX     = (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
            $unidadXX         = (isset($_POST['unidad'])) ? $_POST['unidad']: array();
            $preciosXX        = (isset($_POST['precios'])) ? $_POST['precios'] : array();
            $precio_hiddenXX  = (isset($_POST['precio_hidden'])) ? $_POST['precio_hidden'] : array();
            $loteXX			  = (isset($_POST['lote'])) ? $_POST['lote'] : array();
            $vencimientoXX	  = (isset($_POST['vencimiento'])) ? $_POST['vencimiento'] : array();
            $detallesXX       = (isset($_POST['detalle'])) ? $_POST['detalle'] : array();
            
            $productos      = array();
    		$cantidades     = array();
            $unidad         = array();
            $precios        = array();
            $precio_hidden  = array();
            $lote			= array();
            $vencimiento	= array();
            $detalles       = array();
            
            $monto_total=0;
            foreach ($productosXX as $nro => $elemento) {
    			$cantidad = $cantidadesXX[$nro];
    			$precio = $preciosXX[$nro];
                $monto_total=$monto_total+($cantidad*$precio);
            }
            echo " total=".$monto_total;
    
            
            $incremental=0;
            foreach ($productosXX as $nro => $elemento) {
                
                echo $resultados_qwr="   SELECT *
                                    FROM inv_egresos_detalles AS ed
                                    WHERE   egreso_id='{$_POST['id_egreso']}' AND 
                                            producto_id='".$productosXX[$nro]."' AND
                                            lote='".$loteXX[$nro]."' AND
                                            vencimiento='".$vencimientoXX[$nro]."' AND
                                            precio='".$precio_hiddenXX[$nro]."'
                                    ";
                
                $resultados=$db->query($resultados_qwr)->fetch();
                
                foreach ($resultados as $ress => $resultado) {
                    if($cantidadesXX[$nro]>$resultado['cantidad']){
                        $cantx=$resultado['cantidad'];
                        $cantidadesXX[$nro]=$cantidadesXX[$nro]-$resultado['cantidad'];
                    }
                    else{
                        $cantx=$cantidadesXX[$nro];
                        $cantidadesXX[$nro]=0;
                    }
                    
                    $productos[$incremental]    = $productosXX[$nro];
            		$cantidades[$incremental]   = $cantx;
                    $unidad[$incremental]       = $unidadXX[$nro];
                    $precios[$incremental]      = $preciosXX[$nro];
                    $precio_hidden[$incremental]= $precio_hiddenXX[$nro];
                    $lote[$incremental]		    = $loteXX[$nro];
                    $vencimiento[$incremental]	= $vencimientoXX[$nro];
                    $detalles[$incremental]     = $resultado['id_detalle'];
                    
                    echo "<br>productos ".$incremental." / ".$productos[$incremental]."<br>";
                    
                    $incremental++;
                }
            }
            
            //var_dump($cantidades);
            //var_dump($monto_total);
    
            $nro_cuentas = trim($_POST['nro_cuentas']);
            $plan = (isset($_POST['forma_pago'])) ? trim($_POST['forma_pago']) : '';
            $plan = ($plan == "2") ? "si" : "no";
            if ($plan == "si") {
                $cobrar = 'si';
                $fechas = (isset($_POST['fecha'])) ? $_POST['fecha'] : array();
                $cuotas = (isset($_POST['cuota'])) ? $_POST['cuota'] : array();
            } else {
                $cobrar = 'no';
            }
            // PARA HACER O NO LA ENTREGA
            $entrega = trim($_POST['entrega']);
    
            $egreso = $db->from('inv_egresos')->where('id_egreso',$_POST['id_egreso'])->fetch_first();
            
            // Actualizamos el egreso
            echo "id_egreso= ".$_POST['id_egreso']; 
            
            if($_POST['id_egreso'] > 0){
                $proforma = array(
                    'monto_total' => $monto_total,
                    'nro_registros' => $nro_registros,
                    'observacion' => $prioridad,
                    'descripcion_venta' => "",
                    //'descripcion_venta' => $observacion,
                    'plan_de_pagos' => ($plan == 'no') ? 'no' : 'si',
                );
    
                // Guarda la informacion
                $db->where('id_egreso',$_POST['id_egreso'])->update('inv_egresos', $proforma);
                
                /////////////////////////////////////////////////////////////////////
                // DEVOLVER A SUS RESPECTIVOS INGRESOS LA CANTIDAD
                $Lotes=$db->query(" SELECT *
                                    FROM inv_egresos_detalles AS ed
                                    WHERE egreso_id='{$_POST['id_egreso']}'
                                    ")->fetch();
                
                foreach($Lotes as $Fila=>$Lote):
                    $ingresos_detalles_id=$Lote['ingresos_detalles_id'];
                    
                    $DetalleIngreso=$db->query("SELECT id_detalle,lote_cantidad
                                                FROM inv_ingresos_detalles
                                                WHERE id_detalle='{$ingresos_detalles_id}'
                                                LIMIT 1
                                                ")->fetch_first();
                    $Condicion=[
                                'id_detalle'=>$ingresos_detalles_id,
                               ];
                    $Datos=[
                            'lote_cantidad'=>(strval($DetalleIngreso['lote_cantidad'])+strval($Lote['cantidad'])),
                           ];
                    $db->where($Condicion)->update('inv_ingresos_detalles',$Datos);
                    
                    //echo "lote devueltos: ".(strval($DetalleIngreso['lote_cantidad'])+strval($Lote['cantidad']))."<br>";
                    
                endforeach;
                
                echo "se devolvio los ingresos "; 
                
                /////////////////////////////////////////////////////////////////////
                //ELIMINAR TODOS LOS DETALLES ANTIGUOS
                
                //$db->delete()->from('inv_egresos_detalles')->where('egreso_id', $_POST['id_egreso'])->execute();
                
                foreach ($Lotes as $key => $actual) {
                    
                    $sw_ingreso=true;
                    echo "<br>KEY: ".$key."<br>";
                    var_dump($productos);    
                    echo "<br>";
                    foreach($productos as $nro => $producto){
                        echo $detalles[$nro]."==".$actual['id_detalle'];
                        
                        
                        
                        if($detalles[$nro]==$actual['id_detalle']){
                            $sw_ingreso=false;
                            
                            echo "<br>/".$detalles[$nro]."==".$actual['id_detalle']."/ precio=".$precios[$nro]."<br>";
                            
                            if($cantidades[$nro] < $actual['cantidad']){
                                    
                                echo "cantidad menos al la BD <br>";

                                // si la nueva cantidad es menor a la anterior se crean los nuevos detalle de egreso (dividiendo en dos) y el nuevo ingreso
                                // creamos el egreso 1 con la nueva cantidad
                                $detalle1 = array(
                                    'cantidad' => $cantidades[$nro],
                                    'unidad_id' => $actual['unidad_id'],
                                    'precio' => $precios[$nro],
                                    'descuento' => '0',
                                    'producto_id' => $actual['producto_id'],
                                    'egreso_id' => $_POST['id_egreso'],
                                    'lote' => $actual['lote'],
                                    'vencimiento' => $actual['vencimiento'],
                                    'ingresos_detalles_id'=>$actual['ingresos_detalles_id']
                                );
                                // Guarda la informacion
                                $id1 = $db->insert('inv_egresos_detalles', $detalle1);
                                
                                // Actualizamos el ingreso
                                $ingresoA =  $db->select('d.*')
                                                ->from('inv_ingresos_detalles d')
                                                ->where('d.id_detalle', $actual['ingresos_detalles_id'])
                                                ->fetch_first();
                                
                                $db->where('id_detalle', $actual['ingresos_detalles_id'])
                                   ->update('inv_ingresos_detalles', array('lote_cantidad' => ($ingresoA['lote_cantidad'] - $cantidades[$nro])) );
                                
                                
                                /*****************************************/
                                
                                
                                $para_id = $db->select('MAX(id_egreso) as id')->from('inv_egresos')->fetch_first();
                                
                                $egreso_editar = $db->from('inv_egresos')->where('id_egreso', $_POST['id_egreso'])->fetch_first();
                                
                                $egreso_editar['id_egreso'] = $para_id['id']+1;
                                $egreso_editar['monto_total'] = $precios[$nro]*($actual['cantidad'] - $cantidades[$nro]);
                                $egreso_editar['nro_registros'] = 1;
                                $egreso_editar['empleado_id'] = $_user['persona_id'];
                                $egreso_editar['plan_de_pagos'] = 'no';
                                $egreso_editar['estadoe'] = 4;
                                $egreso_editar['descuento_bs'] = 0;
                                $egreso_editar['monto_total_descuento'] =  $precios[$nro]*($actual['cantidad'] - $cantidades[$nro]);
                                $egreso_editar['preventa'] = 'devolucion';
                                $egreso_editar['tipo'] = 'no venta';
                                
                                // Guarda la informacion
                                $egreso2_id = $db->insert('inv_egresos', $egreso_editar);
    
    
                                /*********************************************/
                                
                                
                                $detalle2 = array(
                                    'cantidad' => $actual['cantidad'] - $cantidades[$nro],
                                    'unidad_id' => $actual['unidad_id'],
                                    'precio' => $precios[$nro],
                                    'descuento' => '0',
                                    'producto_id' => $productos[$nro],
                                    'egreso_id' => $egreso2_id,
                                    'lote' => $actual['lote'],
                                    'vencimiento' => $actual['vencimiento'],
                                    'ingresos_detalles_id'=>$actual['ingresos_detalles_id']
                                );
                                // Guarda la informacion
                                $id2 = $db->insert('inv_egresos_detalles', $detalle2);
                
                
                
                
                                // Actualizamos el ingreso
                                $ingresoB =  $db->select('d.*')
                                                ->from('inv_ingresos_detalles d')
                                                ->where('d.id_detalle', $actual['ingresos_detalles_id'])
                                                ->fetch_first();
                                
                                if($ingresoB){
                                    $db->where('id_detalle', $ingresoB['id_detalle'])
                                       ->update('inv_ingresos_detalles', array('lote_cantidad' => ($ingresoB['lote_cantidad'] - ($actual['cantidad'] - $cantidades[$nro]))) );
        
                                    // creamos el ingreso con el resto de la cantidad anterior
                                    $movimiento = generarMovimiento($db, $_user['persona_id'], 'DV', $almacen_id);
                                    // Instancia el ingreso
                                    $ingreso = array(
                                        'fecha_ingreso' => date('Y-m-d'),
                                        'hora_ingreso' => date('H:i:s'),
                                        'tipo' => 'Devolucion',
                                        'nro_movimiento' => $movimiento, // + 1
                                        'descripcion' => $descripcion,
                                        'monto_total' => $ingresoB['costo']*($actual['cantidad'] - $cantidades[$nro]),
                                        'nombre_proveedor' => mb_strtoupper($nombre_cliente, 'UTF-8'),
                                        'nro_registros' => 1,
                                        'transitorio' => 0,
                                        'des_transitorio' => 0,
                                        'plan_de_pagos' => 'no',
                                        'empleado_id' => $_user['persona_id'],
                                        'almacen_id' => $almacen_id,
                                        'tipo_pago' => '',
                                        'nro_pago' =>'',
                                        'egreso_id' =>$_POST['id_egreso'],
                                    );
                                    
                                    //var_dump($ingreso);
                                    //echo "<br>";
                                    
                                    echo "devolucion ingresos".($actual['cantidad'] - $cantidades[$nro]);
                                    
                                    // Guarda la informacion
                                    if($ingreso_id_global==0){
                                        $ingreso_id_global = $db->insert('inv_ingresos', $ingreso);
                                    }
                                    
                                    $detalleI = array(
                                        'cantidad' => $actual['cantidad'] - $cantidades[$nro],
                                        'costo' => $ingresoB['costo'],
                                        'precio' => $precios[$nro],
                                        'vencimiento' => $ingresoB['vencimiento'],
                                        'dui' => $ingresoB['dui'],
                                        'lote2' => $ingresoB['lote2'],
                                        'factura' => $ingresoB['factura'],
                                        'factura_v' => $ingresoB['factura_v'],
                                        'contenedor' => $ingresoB['contenedor'],
                                        'producto_id' => $ingresoB['producto_id'],
                                        'ingreso_id' => $ingreso_id_global,
                                        'IVA' => $ingresoB['IVA'],
                                        'lote' => $ingresoB['lote'],
                                        'lote_cantidad' => $actual['cantidad'] - $cantidades[$nro],
                                        'costo_sin_factura'=>0,
                                    );
                                    // Guarda la informacion
                                    $id_detalleI = $db->insert('inv_ingresos_detalles', $detalleI);
                                }
                            } else {
                                echo "cantidad igual a BD <br>";

                                //Si las cantidades son iguales entonces se crea nuevamente los detalles
                                $detalle = array(
                                    'cantidad' => $actual['cantidad'],
                                    'unidad_id' => $actual['unidad_id'],
                                    'precio' => $precios[$nro],
                                    'descuento' => '0',
                                    'producto_id' => $productos[$nro],
                                    'egreso_id' => $_POST['id_egreso'],
                                    'lote' => $actual['lote'],
                                    'vencimiento' => $actual['vencimiento'],
                                    'ingresos_detalles_id'=>$actual['ingresos_detalles_id']
                                );
                                // Guarda la informacion
                                $id = $db->insert('inv_egresos_detalles', $detalle);
    
                                
                                echo "precio asignado: ".$precios[$nro]."<br>";
                
    
                                // Guarda Historial
                                $data = array(
                                    'fecha_proceso' => date("Y-m-d"),
                                    'hora_proceso' => date("H:i:s"),
                                    'proceso' => 'c',
                                    'nivel' => 'l',
                                    'direccion' => '?/asignacion/preventas_actualizar',
                                    'detalle' => 'Se creo inventario egreso detalle con identificador numero ' . $id ,
                                    'usuario_id' => $_SESSION[user]['id_user']
                                );
                                $db->insert('sys_procesos', $data) ;
                                
                                
                                
                                // Actualizamos el ingreso
                                $ingresoA =  $db->select('d.*')
                                                ->from('inv_ingresos_detalles d')
                                                ->where('d.id_detalle', $actual['ingresos_detalles_id'])
                                                ->fetch_first();
                                
                                $db->where('id_detalle', $actual['ingresos_detalles_id'])
                                  ->update('inv_ingresos_detalles', array('lote_cantidad' => ($ingresoA['lote_cantidad'] - $actual['cantidad'])) );
                            }
                        }
                    }
                    
                    if($sw_ingreso){
                        
                        $para_id = $db->select('MAX(id_egreso) as id')->from('inv_egresos')->fetch_first();
                                
                        $egreso_editar = $db->from('inv_egresos')
                                            ->where('id_egreso', $_POST['id_egreso'])
                                            ->fetch_first();
                        
                        $egreso_editar['id_egreso'] = $para_id['id']+1;
                        $egreso_editar['monto_total'] = $actual['precio']*$actual['cantidad'];
                        $egreso_editar['nro_registros'] = 1;
                        $egreso_editar['empleado_id'] = $_user['persona_id'];
                        $egreso_editar['plan_de_pagos'] = 'no';
                        $egreso_editar['estadoe'] = 4;
                        $egreso_editar['descuento_bs'] = 0;
                        $egreso_editar['monto_total_descuento'] = $actual['precio']*$actual['cantidad'];
                        $egreso_editar['preventa'] = 'devolucion';
                        $egreso_editar['tipo'] = 'no venta';
                        
                        // Guarda la informacion
                        $egreso2_id = $db->insert('inv_egresos', $egreso_editar);

                        /*************************************************************/

                        $detalle1 = array(
                            'cantidad' => $actual['cantidad'],
                            'unidad_id' => $actual['unidad_id'],
                            'precio' => $actual['precio'],
                            'descuento' => '0',
                            'producto_id' => $actual['producto_id'],
                            'egreso_id' => $egreso2_id,
                            'lote' => $actual['lote'],
                            'vencimiento' => $actual['vencimiento'],
                            'ingresos_detalles_id'=>$actual['ingresos_detalles_id']
                        );
                        // Guarda la informacion
                        $id1 = $db->insert('inv_egresos_detalles', $detalle1);
                        
                        
                        
                        echo "No existe: ".$actual['producto_id']." ".$actual['cantidad']."<br>";
                
                        // Actualizamos el ingreso
                        $ingresoB =  $db->select('d.*')
                                        ->from('inv_ingresos_detalles d')
                                        ->where('d.id_detalle', $actual['ingresos_detalles_id'])
                                        ->fetch_first();
                        
                        // creamos el ingreso con el resto de la cantidad anterior
                        $movimiento = generarMovimiento($db, $_user['persona_id'], 'DV', $almacen_id);
                        // Instancia el ingreso
                        $ingreso = array(
                            'fecha_ingreso' => date('Y-m-d'),
                            'hora_ingreso' => date('H:i:s'),
                            'tipo' => 'Devolucion',
                            'nro_movimiento' => $movimiento, // + 1
                            'descripcion' => '', //$descripcion,
                            'monto_total' => $ingresoB['costo']*$actual['cantidad'],
                            'nombre_proveedor' => mb_strtoupper($nombre_cliente, 'UTF-8'),
                            'nro_registros' => 1,
                            'transitorio' => 0,
                            'des_transitorio' => 0,
                            'plan_de_pagos' => 'no',
                            'empleado_id' => $_user['persona_id'],
                            'almacen_id' => $almacen_id,
                            'tipo_pago' => '',
                            'nro_pago' =>'',
                            'egreso_id' =>$_POST['id_egreso'],
                        );
                        
                        //var_dump($ingreso);
                        //echo "<br>";
                        
                        // Guarda la informacion
                        if($ingreso_id_global==0){
                            $ingreso_id_global = $db->insert('inv_ingresos', $ingreso);
                        }
                        
                        $detalleI = array(
                            'cantidad' => $actual['cantidad'],
                            'costo' => $ingresoB['costo'],
                            'precio' => $actual['precio'],
                            'vencimiento' => $ingresoB['vencimiento'],
                            'dui' => $ingresoB['dui'],
                            'lote2' => $ingresoB['lote2'],
                            'factura' => $ingresoB['factura'],
                            'factura_v' => $ingresoB['factura_v'],
                            'contenedor' => $ingresoB['contenedor'],
                            'producto_id' => $ingresoB['producto_id'],
                            'ingreso_id' => $ingreso_id_global,
                            'IVA' => $ingresoB['IVA'],
                            'lote' => $ingresoB['lote'],
                            'lote_cantidad' => $actual['cantidad'],
                            'costo_sin_factura'=>0,
                        );
                        // Guarda la informacion
                        $id_detalleI = $db->insert('inv_ingresos_detalles', $detalleI);
                    }
                    
                    echo "<br>acabo la iteracion<br>";
                }
                foreach ($Lotes as $key => $actual) {
                    $db->delete()->from('inv_egresos_detalles')->where('id_detalle', $actual['id_detalle'])->execute();
                }
                
                // Inicio para pagos
                //$plan = trim($egreso['plan_de_pagos']);
                $pagos1 = $db->from('inv_pagos')->where('movimiento_id', $_POST['id_egreso'])->where( 'tipo', 'Egreso')->fetch_first();
    
                $db->delete()->from('inv_pagos')->where('movimiento_id', $_POST['id_egreso'])->where( 'tipo', 'Egreso')->execute();
                $db->delete()->from('inv_pagos_detalles')->where('pago_id', $pagos1['id_pago'])->execute();

                $nro_recibo=0;
                if ($plan == 'no') {
                    $code = $db->select('MAX(inv_pagos_detalles.codigo) as code')
                           ->from('inv_pagos_detalles')
                           ->join('inv_pagos', 'inv_pagos.id_pago = inv_pagos_detalles.pago_id')
                           ->where('inv_pagos.tipo', 'Egreso')
                           ->fetch_first();
                
                    // Instancia el ingreso
                    $planPago = array(
                        'movimiento_id' => $_POST['id_egreso'],
                        'interes_pago' => 0,
                        'tipo' => 'Egreso'
                    );
                    // Guarda la informacion del ingreso general
                    $id_plan_egreso = $db->insert('inv_pagos', $planPago);
                    
                    // Genera el plan de pagos
                    $detallePlan = array(
                        'nro_cuota' => 1,
                        'pago_id' => $id_plan_egreso,
                        'fecha' => date('Y-m-d'),
                        'fecha_pago' => date('Y-m-d'),
                        'hora_pago' => date('H:i:s'),
                        'monto' => $monto_total,
                        'tipo_pago' => '', //$tipo_pago
                        'nro_pago' => '0',
                        'empleado_id' => $_user['persona_id'],
                        'estado'  => '1',
                        'codigo'=>($code['code']+1)
                    );
                    // Guarda la informacion
                    $nro_recibo=$db->insert('inv_pagos_detalles', $detallePlan);
                }
    
                //Cuentas
                if ($plan == "si") {
                    // Instancia el ingreso
                    $ingresoPlan = array(
                        'movimiento_id' => $_POST['id_egreso'],
                        'interes_pago' => 0,
                        'tipo' => 'Egreso'
                    );
                    // Guarda la informacion del ingreso general
                    $egreso_id_plan = $db->insert('inv_pagos', $ingresoPlan);
    
                    $nro_cuota = 0;
                    for ($nro2 = 0; $nro2 < $nro_cuentas; $nro2++) {
                        if (isset($fechas[$nro2])) {
                            $fecha_format = date_create($fechas[$nro2]); //date_format(, 'Y-m-d');
                        } else {
                            $fecha_format = date_create("00000-00-00");
                        }
    
                        $nro_cuota++;
                        $detallePlan = array(
                            'nro_cuota' => $nro_cuota,
                            'pago_id' => $egreso_id_plan,
                            'fecha' => $fecha_format->format('Y-m-d'),
                            'fecha_pago' => $fecha_format->format('Y-m-d'),
                            'monto' => (isset($cuotas[$nro2])) ? $cuotas[$nro2] : 0,
                            'tipo_pago' => '',
                            'nro_pago' => '',
                            'empleado_id' => $_user['persona_id'],
                            'estado'  => '0'
                        );
                        // Guarda la informacion
                        $db->insert('inv_pagos_detalles', $detallePlan);
                    }
                }
                
                /*****************************************************************/
                
                if ($entrega == 1) {
    
                    $asignacion = $db->from('inv_asignaciones_clientes')
                                     ->where('egreso_id', $_POST['id_egreso'])
                                     ->where('estado', 'A')
                                     ->where('estado_pedido', 'salida')
                                     ->fetch_first();
                    
                    if(!$asignacion){
                        $asignacion_datos = array(
                            'egreso_id'=>$_POST['id_egreso'],
                            'distribuidor_id'=> $_user['persona_id'],	
                            'fecha_asignacion'=>date('Y-m-d'),	
                            'fecha_entrega'=>'0000-00-00',	
                            'estado_pedido'=>'salida',	
                            'empleado_id'=> $_user['persona_id'],	
                            'estado'=>'A',	
                            'fecha_hora_salida'=>'0000-00-00',	
                            'fecha_hora_liquidacion'=>'00:00:00',	
                            'nro_salida'=>-1,	
                            'nro_liquidacion'=>0
                    	);
                    	$id_asignacion=$db->insert('inv_asignaciones_clientes', $asignacion_datos);
                    }
                
                    $asignacion = $db->from('inv_asignaciones_clientes')
                                     ->where('egreso_id', $_POST['id_egreso'])
                                     ->where('estado', 'A')
                                     ->where('estado_pedido', 'salida')
                                     ->fetch_first();
                    
                    $datos_egreso = $db->select('b.*, b.fecha_egreso as distribuidor_fecha , b.hora_egreso as distribuidor_hora, 
                                                 b.almacen_id as distribuidor_estado, b.almacen_id as distribuidor_id, b.estadoe as estado')
                                        ->from('inv_egresos b')
                                        ->where('b.id_egreso',$asignacion['egreso_id'])->fetch_first();
                    
                    if($datos_egreso){
                        $db->where('id_egreso',$asignacion['egreso_id'])
                           ->update('inv_egresos',array('estadoe' => 3));
                        
                        $datos_egreso = $db->select('b.*, b.fecha_egreso as distribuidor_fecha , b.hora_egreso as distribuidor_hora, b.almacen_id as distribuidor_estado, 
                                                     b.almacen_id as distribuidor_id, b.estadoe as estado')
                                        ->from('inv_egresos b')
                                        ->where('b.id_egreso',$asignacion['egreso_id'])
                                        ->fetch_first();
                                        
                        $datos_egreso['distribuidor_fecha'] = date('Y-m-d');
                        $datos_egreso['distribuidor_hora'] = date('H:i:s');
                        $datos_egreso['distribuidor_estado'] = 'ENTREGA';
                        $datos_egreso['distribuidor_id'] = $asignacion['distribuidor_id'];
                        $datos_egreso['estado'] = 3;
                        $id = $db->insert('tmp_egresos', $datos_egreso);
                        $egresos_detalles = $db->select('*, egreso_id as tmp_egreso_id')->from('inv_egresos_detalles')->where('egreso_id',$asignacion['egreso_id'])->fetch();
                        foreach ($egresos_detalles as $nr => $detalle) {
                            $detalle['tmp_egreso_id'] = $id;
                            $db->insert('tmp_egresos_detalles', $detalle);
                        }
                    }
                    $modificado = array(
                        'estado_pedido' => 'entregado',
                        'fecha_entrega' => date('Y-m-d')
                    );
                    
                    // Actualiza la informacion
                    $db->where('id_asignacion_cliente', $asignacion['id_asignacion_cliente'])
                       ->update('inv_asignaciones_clientes', $modificado);
                       
                    echo "Se modifico la asignacion";
                       
                    set_notification('success', 'Entrega satisfactoria!', 'La asignaci贸n fue entregada satisfactoriamente.');
    // insert
                    // Guarda en el historial
                    $data = array(
                        'fecha_proceso' => date("Y-m-d"),
                        'hora_proceso' => date("H:i:s"),
                        'proceso' => 'c',
                        'nivel' => 'l',
                        'direccion' => '?/asignacion/preventas_entregar',
                        'detalle' => 'Se modifico la asignacion cliente con identificador numero ' . $asignacion['id_asignacion_cliente'],
                        'usuario_id' => $_SESSION[user]['id_user']
                    );
                    $db->insert('sys_procesos', $data);
                    // Redirecciona a la pagina principal
                    




                    // Actualizamos el ingreso
                    $nro_nota_credito = $db->query("select MAX(nro_nota_credito)as nro_nota_credito
                                            from inv_ingresos i
                                            ")
                                    ->fetch_first();
                    if($ingreso_id_global!=0){
                        $ingresoX = $db->query("select i.*, SUM(costo*cantidad)as costo_total_x, SUM(precio*cantidad)as precio_total_x
                                                from inv_ingresos i
                                                left join inv_ingresos_detalles d on id_ingreso=ingreso_id
                                                where i.id_ingreso='$ingreso_id_global'")
                                        ->fetch_first();
                        
                        $db->where('id_ingreso', $ingresoX['id_ingreso'])
                           ->update('inv_ingresos', array(  'monto_total' => $ingresoX['costo_total_x'], 
                                                            'nro_nota_credito'=>($nro_nota_credito['nro_nota_credito']+1) 
                                                        )
                                    );
                    }    
                        
                    
                    
                    
                    // $detalle1 = array(
                    //                 'ingreso_id'=> $ingreso_id_global,
                    //                 'egreso_id'	=> $_POST['id_egreso'],
                    //                 'monto' => $ingresoX['precio_total_x']
                    // );
                    // // Guarda la informacion
                    // $id1 = $db->insert('inv_devolucion', $detalle1);
                    
                    $idx=$_POST['id_egreso'];
                    $montox=$ingresoX['precio_total_x'];
                
                    $deuda_v = $db->query("SELECT *
                                           from inv_pagos_detalles i 
                                           inner join inv_pagos p ON p.id_pago=i.pago_id 
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
                                'hora_pago' => date("H:i:s"),
                                'tipo_pago'	=>"DEVOLUCION",
                                'nro_pago'=>0,
                                'empleado_id'=>$_SESSION[user]['id_user'],	
                                'deposito'=>"inactivo",
                                'codigo'=>"0",
                                'ingreso_id'=> $ingreso_id_global
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
                                    'hora_pago' => '00:00:00',
                                    'tipo_pago'	=> '',
                                    'nro_pago'	=>	'',
                                    'nro_cuota'	=>	0,
                                    'empleado_id'=>	0,
                                    'deposito'	=>	'inactivo',
                                    'fecha_deposito'=>	'0000-00-00',
                                    'observacion_anulado'=>'',
                                    'fecha_anulado'=>'0000-00-00',
                                    'codigo'	=> 0,
    	                            'ingreso_id'=> 0
                             );
                    			$db->insert('inv_pagos_detalles',$cl);
                            }
                        }
                    }
                    
                    echo " SELECT *
                           from inv_pagos p 
                           where movimiento_id=".$idx." AND p.tipo='Egreso'";
                    
                    $deuda_v = $db->query("SELECT *
                                           from inv_pagos p 
                                           where movimiento_id=".$idx." AND p.tipo='Egreso'")
                                          ->fetch_first();
                    
                    if(!$deuda_v){
                        $cl = array(
                            'movimiento_id'	=>$idx,
                            'interes_pago'=>0,	
                            'tipo'=> 'Egreso'
                        );
            			$nro_pago=$db->insert('inv_pagos',$cl);
                    }else{
                        $nro_pago=$deuda_v['id_pago'];
                    }
                    
                    
        			echo "-.-.-.-.- ".$nro_pago;
    
                    
                    if($montox>0){
                        $cl = array(
                            'pago_id'=>	$nro_pago,
                            'fecha'	=>	date("Y-m-d"),
                            'monto'	=>	$montox,
                            'estado'=>	1,
                            'fecha_pago'=> date("Y-m-d"),
                            'hora_pago' => date("H:i:s"),
                            'tipo_pago'	=> 'DEVOLUCION',
                            'nro_pago'	=>	0,
                            'nro_cuota'	=>	0,
                            'empleado_id'=>	$_SESSION[user]['id_user'],
                            'deposito'	=>	'inactivo',
                            'fecha_deposito'=>	'0000-00-00',
                            'observacion_anulado'=>'',
                            'fecha_anulado' => '0000-00-00',
                            'codigo'	    => 0,
                            'ingreso_id'    => $ingreso_id_global
                        );
            			$db->insert('inv_pagos_detalles',$cl);
                    }
                    
                    
                    
                    
                    
                    
                    $db->commit();

                    $partes=explode("?/asignacion/asignacion_ver/",$_POST['atras']);
                    if (count($partes)>1) {
                        if ($plan == 'no') {
                            redirect("?/asignacion/asignacion_ver/".$asignacion['distribuidor_id']."/".$_POST['id_egreso']."/".$nro_recibo);
                        }else{
                            redirect("?/asignacion/asignacion_ver/".$asignacion['distribuidor_id']."/".$_POST['id_egreso']."/".$nro_recibo);
                        }
                    }
    
                    $partes=explode("?/asignacion/preventas_listar",$_POST['atras']);
                    if (count($partes)>1) {
                        if ($plan == 'no') {
                            redirect("?/asignacion/preventas_listar/".$_POST['id_egreso']."/".$nro_recibo);
                        }else{
                            redirect("?/asignacion/preventas_listar/".$_POST['id_egreso']."/".$nro_recibo);
                        }
                    }
                }
            }

        } catch (Exception $e) {
            //se cierra transaccion
            $db->rollback();

            $status = false;
            $error = $e->getMessage();

            //Se devuelve el error en mensaje json
            echo json_encode(array('estado' => 'n', 'msg'=>$error));
        }    
        
        //set_notification('success', 'Accion satisfactoria.', 'El registro se modific贸 correctamente.');
        //redirect($_POST['atras']);
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