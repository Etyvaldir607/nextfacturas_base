<?php
    $Lotes=$db->query(" SELECT * 
                        FROM inv_ingresos 
                        WHERE tipo='devolucion' 
                        GROUP BY egreso_id, fecha_ingreso, hora_ingreso
                    ")->fetch();
                        
    foreach($Lotes as $Fila=>$Lote){
        $otros_ingresos=$db->query("    SELECT id_ingreso, ifnull(id.id_detalle, 0)as id_detalle
                                        FROM inv_ingresos i
                                        LEFT JOIN inv_ingresos_detalles id ON i.id_ingreso=id.ingreso_id 
                                        
                                        WHERE   i.egreso_id='".$Lote['egreso_id']."' 
                                            AND i.fecha_ingreso='".$Lote['fecha_ingreso']."' 
                                            AND i.hora_ingreso='".$Lote['hora_ingreso']."' 
                                            AND i.id_ingreso!='".$Lote['id_ingreso']."'
                                    ")->fetch();
    
        foreach($otros_ingresos as $nrx=>$otro){
            if($otro['id_detalle']!=0){
                $Condicion=[
                        'id_detalle'=>$otro['id_detalle'],
                    ];
                $Datos=[
                        'ingreso_id'=>$Lote['id_ingreso'],
                    ];
                $db->where($Condicion)->update('inv_ingresos_detalles',$Datos);
            }
            $db->delete()
              ->from('inv_ingresos')
              ->where('id_ingreso', $otro['id_ingreso'])
              ->limit(1)
              ->execute();
        }
    }

    /**************************************************************************/

    $Lotes=$db->query(" SELECT * 
                        FROM inv_ingresos 
                        WHERE tipo='devolucion' 
                        ORDER BY fecha_ingreso ASC, hora_ingreso ASC
                    ")->fetch();
    
    $nro_nota=1;
    
    foreach($Lotes as $Fila=>$Lote){
        $Condicion=[
            'id_ingreso'=>$Lote['id_ingreso'],
            ];
        $Datos=[
                'nro_nota_credito'=>$nro_nota,
            ];
        $db->where($Condicion)->update('inv_ingresos',$Datos);
        
        $nro_nota++;
    }

    /**************************************************************************/

    $Lotes=$db->query(" SELECT * 
                        FROM inv_ingresos 
                        INNER JOIN inv_ingresos_detalles ON id_ingreso=ingreso_id 
                        WHERE tipo='devolucion' 
                    ")->fetch();
                        
    foreach($Lotes as $Fila=>$Lote){
        // echo "  SELECT id_detalle, ingresos_detalles_id, precio 
        //         FROM inv_egresos_detalles
        //         WHERE egreso_id='".$Lote['egreso_id']."' 
        //       <br>";

        $otros_ingresos=$db->query("SELECT id_detalle, ingresos_detalles_id, precio 
                                    FROM inv_egresos_detalles
                                    WHERE   egreso_id='".$Lote['egreso_id']."'
                                            AND lote='".$Lote['lote']."'
                                            AND vencimiento='".$Lote['vencimiento']."'
                                            AND producto_id='".$Lote['producto_id']."'
                                  ")->fetch();

        foreach($otros_ingresos as $nrx=>$otro){
            // echo "ingreso_id".$otro['ingresos_detalles_id']." ... precio: ".$otro['precio']."<br>";
            $Condicion=[
                'id_detalle'=>$Lote['id_detalle'],
                ];
            $Datos=[
                'precio'=>$otro['precio'],
                ];
            $db->where($Condicion)->update('inv_ingresos_detalles',$Datos);
        }
    }

    /**************************************************************************/

    $Lotes=$db->query(" SELECT *, SUM(precio*cantidad)as monto_total_sum, egreso_id, id_ingreso 
                        FROM inv_ingresos 
                        INNER JOIN inv_ingresos_detalles ON id_ingreso=ingreso_id 
                        WHERE tipo='devolucion'
                        GROUP BY ingreso_id
                    ")->fetch();
                        
    foreach($Lotes as $Fila=>$Lote){
        $nuevo = array(
            'ingreso_id' =>$Lote['id_ingreso'],
        	'egreso_id' =>$Lote['egreso_id'], 	
        	'monto' =>$Lote['monto_total_sum'],
        );
        $id_nota = $db->insert('inv_devolucion', $nuevo);
        
        $nuevo = array(
            'ingreso_id' =>$Lote['id_ingreso'],
        	'egreso_id' =>$Lote['egreso_id'], 	
        	'monto' =>$Lote['monto_total_sum'],
        );
        $id_nota = $db->insert('inv_devolucion', $nuevo);
        
        /***************/
        
        $Lote_pago=$db->query(" SELECT id_pago 
                            FROM inv_pagos 
                            WHERE movimiento_id='".$Lote['egreso_id']."'
                                AND tipo='Egreso'
                        ")->fetch_first();
        
        if($Lote_pago){
            $id_pago = $Lote_pago['id_pago'];
        }else{
            $nuevo = array(
                'movimiento_id' =>$Lote['egreso_id'],	
                'interes_pago' =>0,
                'tipo' =>'Egreso'
            );
            $id_pago = $db->insert('inv_pagos', $nuevo);
        }   
        
        $nuevo = array(
            'pago_id' =>$id_pago,	
            'fecha' =>	$Lote['fecha_ingreso'],
            'monto' => $Lote['monto_total_sum'],	
            'monto_programado' =>	$Lote['monto_total_sum'],
            'estado' =>	'1',
            'fecha_pago' =>	$Lote['fecha_ingreso'],	
            'hora_pago' =>	$Lote['hora_ingreso'],
            'tipo_pago' =>	'DEVOLUCION',
            'nro_pago' =>	'0',
            'nro_cuota' =>	'0',
            'empleado_id' => $Lote['empleado_id'],	
            'deposito' =>	'inactivo',
            'fecha_deposito' =>	'0000-00-00',
            'codigo' =>	0,
            'observacion_anulado' => '',	
            'fecha_anulado' =>	'0000-00-00',
            'coordenadas' =>	'',
            'ingreso_id' =>	$Lote['id_ingreso']
        );
        $id_nota = $db->insert('inv_pagos_detalles', $nuevo);

    }



    // $Lotes=$db->query(" SELECT *, e.monto_total as monto_total_egreso
    //                     FROM inv_ingresos i
    //                     INNER JOIN inv_egresos e ON e.id_egreso=i.egreso_id 
    //                     WHERE   (e.tipo='Venta' or e.tipo='Preventa') and nro_nota>0 AND 
    //                             nro_nota IN(
    //                                             SELECT nro_nota
    //                                             FROM inv_egresos e  
    //                                             WHERE e.tipo='No venta' AND nro_nota>0
    //                                         )
    //                 ")->fetch();
                        
    // foreach($Lotes as $Fila=>$Lote):
    //     $nro_nota_credito=$db->query("  SELECT MAX(nro_nota_credito)as nro_nota_credito
    //                                     FROM inv_devolucion
    //                                 ")->fetch_first();
                            
    //     if($nro_nota_credito){
    //         $nota_credito=$nro_nota_credito['nro_nota_credito']+1;
    //     }
    //     else{
    //         $nota_credito=1;
    //     }
        
    //     $nuevo = array(
    //         'ingreso_id' =>$Lote['id_ingreso'],
    //     	'egreso_id' =>$Lote['egreso_id'], 	
    //     	'monto' =>$Lote['monto_total_egreso'],
    // 	 	'nro_nota_credito' =>$nota_credito,
    //     );
    //     $id_nota = $db->insert('inv_devolucion', $nuevo);
    // endforeach;

?>