<?php
    $requestData=$_REQUEST;
    $ValorG=$requestData['search']['value'];

    $formato_textual = get_date_textual($_institution['formato']);
    $formato_numeral = get_date_numeral($_institution['formato']);

    // Obtiene los permisos
    $permisos = explode(',', permits);
    // Almacena los permisos en variables
    $permiso_crear = in_array('notas_crear', $permisos);
    $permiso_ver = in_array('notas_ver', $permisos);
    $permiso_eliminar = in_array('notas_eliminar', $permisos);
    $permiso_imprimir = in_array('notas_imprimir', $permisos);
    $permiso_devolucion = in_array('notas_devolucion', $permisos);
    $permiso_facturar = in_array('nota_ver', $permisos);
    $permiso_cambiar = true;

    // echo json_encode($permisos); die();

    $fecha_inicial=$params[0];
    $fecha_final=$params[1];

    $Campos=[
        'i.id_egreso',
        'i.fecha_egreso',
        'i.id_egreso',
        'i.nombre_cliente',
        'i.nit_ci',
        'i.nro_nota',
        'i.monto_total',
        'i.nro_registros',
        'a.almacen',
        'e.nombres'
    ];
    $IdUsuario=$_user['id_user'];
    
    
    // if($_user['rol'] == 'Superusuario'){
	   // $Sentencia = "   select i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno 
    //         			 from inv_egresos i 
    //         			 left join inv_almacenes a on i.almacen_id = a.id_almacen
    //         			 left join sys_empleados e on i.empleado_id = e.id_empleado
    //         			 where 
    //         			        (i.tipo='Venta')
    //             			    AND i.fecha_egreso >= '$fecha_inicial'
    //             			    and i.fecha_egreso <= '$fecha_final'
                		
    //             		UNION
                		
    //             		select i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno 
    //         			from inv_egresos i 
    //         			inner join inv_asignaciones_clientes ac ON i.id_egreso=ac.egreso_id 
    //         			left join inv_almacenes a on i.almacen_id = a.id_almacen
    //         			left join sys_empleados e on i.empleado_id = e.id_empleado
    //         			where 
    //         			        (i.tipo='Preventa')
    //         			        AND i.fecha_egreso >= '$fecha_inicial'
    //             			    and i.fecha_egreso <= '$fecha_final'
    //         			";
    // } else {
	   // $Sentencia = "   select i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno 
    //         			 from inv_egresos i 
    //         			 left join inv_almacenes a on i.almacen_id = a.id_almacen
    //         			 left join sys_empleados e on i.empleado_id = e.id_empleado
    //         			 where 
    //         			    (i.tipo='Venta')
    //         			    AND i.empleado_id='".$_user['persona_id']."'
    //             			AND i.fecha_egreso >= '$fecha_inicial'
    //             			AND i.fecha_egreso <= '$fecha_final'
                		
    //             		 UNION
                		
    //             		 select i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno 
    //         			 from inv_egresos i 
    //         			 inner join inv_asignaciones_clientes ac ON i.id_egreso=ac.egreso_id 
    //         			 left join inv_almacenes a on i.almacen_id = a.id_almacen
    //         			 left join sys_empleados e on i.empleado_id = e.id_empleado
    //         			 where 
    //         			    (i.tipo='Preventa')
    //         			    AND i.empleado_id='".$_user['persona_id']."'
    //         			    AND i.fecha_egreso >= '$fecha_inicial'
    //             			AND i.fecha_egreso <= '$fecha_final'
    //         			";
    // }

    //          i.estadoe = 0 AND
    //          i.tipo='Venta' AND
    //          i.codigo_control='' AND
    //          i.provisionado='S' AND
    
    $Sentencia_valorG="";        
    if(!empty($ValorG)):
        $Sentencia_valorG=" AND (i.id_egreso LIKE '%{$ValorG}%' OR
                    i.fecha_egreso LIKE '%{$ValorG}%' OR
                    i.nombre_cliente LIKE '%{$ValorG}%' OR
                    i.nit_ci LIKE '%{$ValorG}%' OR
                    i.nro_factura LIKE '%{$ValorG}%' OR
                    i.nro_nota LIKE '%{$ValorG}%' OR
                    i.monto_total LIKE '%{$ValorG}%' OR
                    i.nro_registros LIKE '%{$ValorG}%' OR
                    a.almacen LIKE '%{$ValorG}%' OR
                    e.nombres LIKE '%{$ValorG}%')
                    ";
    endif;
    
    $Sentencia="
             select i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno, ac.estado_pedido, CONCAT(em.nombres,' ',em.paterno,' ',em.materno) as distribuidor
    		 from inv_egresos i 
    		 left join inv_almacenes a on i.almacen_id = a.id_almacen
    		 left join sys_empleados e on i.empleado_id = e.id_empleado
    		 LEFT JOIN inv_asignaciones_clientes ac ON i.id_egreso = ac.egreso_id AND ac.estado = 'A'
             LEFT JOIN sys_empleados em ON ac.distribuidor_id=em.id_empleado
             WHERE 
    		     i.tipo IN('Preventa','Venta', 'No venta')
    	         AND preventa is NULL OR preventa='habilitado' OR preventa='anulado'  
    		     
    		     AND i.fecha_egreso >= '$fecha_inicial'
    			 AND i.fecha_egreso <= '$fecha_final'
            ".$Sentencia_valorG."
		";
		
		//inner join inv_asignaciones_clientes ac ON i.id_egreso=ac.egreso_id 
		
    $Sentencia.=" ORDER BY nro_nota DESC ";

    //FILTRO INDEPENDIENTE
    foreach($Campos as $Nro=>$Campo):
        if($Campo):
            $filtro=$requestData['columns'][$Nro]['search']['value'];
            $filtro=str_replace('.*(','',$filtro);
            $filtro=str_replace(').*','',$filtro);
            if($filtro!='' && substr($Sentencia,-5)!='WHERE')
                $Sentencia.=' AND';
            if($filtro!='')
                $Sentencia.=" {$Campo} LIKE '%{$filtro}%'";
        endif;
    endforeach;

    //ORDEN
    if(isset($columns[$requestData['order'][0]['column']])):
        $Columna=$columns[$requestData['order'][0]['column']];
        $Orden=$requestData['order'][0]['dir'];
        $Sentencia.=" ORDER BY {$Columna} {$Orden}";
    endif;

    $totalFiltered=count($db->query($Sentencia)->fetch());
    $totalData=$totalFiltered;

    //LIMITE
    $Inicio=$requestData['start']?$requestData['start']:0;
    $Final=$requestData['length']?$requestData['length']:50;
    $Sentencia.=" LIMIT {$Inicio},{$Final}";

    $Consulta=$db->query($Sentencia)->fetch();
    // echo $db->last_query();
    $data=[];

    foreach($Consulta as $key=>$Dato):
        
        $ventas_anuladas = $db->query("  select COUNT(i.id_egreso) as cantidad_notas
                            			 from inv_egresos i 
                            			 WHERE nro_nota='".$Dato['nro_nota']."' AND nro_nota!=0
                            		  ")->fetch_first();
        
        if($ventas_anuladas['cantidad_notas']<=1 || $Dato['preventa']!='anulado' ){
    	
        
            if($Dato['sub'] || $IdUsuario<=2):
                $nestedData=[];
                $nestedData[]=escape($Dato['nro_nota']);
                $nestedData[]=escape($Dato['nro_factura']);
                
                $Aux=escape(date_decode($Dato['fecha_egreso'],$_institution['formato']));
                $nestedData[]="{$Aux}<small class='text-success'>{$Dato['hora_egreso']}</small>";
                
                $tipo_doc='';
                if($Dato['tipo']=='Venta'){
                    if($Dato['codigo_control']!='' && $Dato['provisionado']=='N'){
                        $tipo_doc.='Venta electrónica';
                    }
                    else{
                        $tipo_doc.=$Dato['tipo'];
                    }
                }else{
                    $tipo_doc='Preventa ';
                }
                
                // if($Dato['codigo_control']!='' && $Dato['provisionado']=='N'){
                //     $tipo_doc.=' con Factura ';
                // }
                
                $nestedData[]=$tipo_doc;
                $nestedData[]=escape($Dato['nombre_cliente']);
                $nestedData[]=escape($Dato['nit_ci']);
                $nestedData[]=escape(number_format($Dato['monto_total'],2 ,',',''));
                $nestedData[]=escape($Dato['nro_registros']);
                $nestedData[]=escape($Dato['almacen']);
                $nestedData[]=escape($Dato['nombres'].' '.$Dato['paterno'].' '.$Dato['materno']);
                
                
                
    
    			$id_motivo = $Dato['motivo_id'];
                $nombre_motivo = $db->query("   select * 
                                            from gps_noventa_motivos 
                                            where id_motivo = '$id_motivo'
                                        ")->fetch_first();
                                        
                if ($Dato['distribuir']=='N'):
                    $estado='<span style="color: green;">Ya fue entregado</span>';
                
                elseif ($Dato['preventa'] == NULL && $Dato['estado_pedido'] == NULL):
                    $estado='<span style="color: red;">No esta habilitado</span>';
                
                elseif ($Dato['preventa'] == 'habilitado' && $Dato['estado_pedido'] == 'salida'):
                    $estado='Ya fue asignado al distribuidor ('.$Dato['distribuidor'].')';
                
                elseif ($Dato['preventa'] == 'habilitado' && $Dato['estado_pedido'] == 'entregado'):
                    $estado='<span style="color: green;">Ya fue entregado</span>';
                
                elseif ($Dato['preventa'] == 'habilitado' && $Dato['estado_pedido'] == NULL):
                    $estado='<span style="color: blue;">Aun no fue asignado a un repartidor</span>';
                
                elseif ($Dato['preventa'] == NULL && $Dato['estado_pedido'] == 'reasignado') :
                    $estado='<span style="color: blue;">Puede reasignar ('.$nombre_motivo['motivo'].')</span>';
                
                elseif ($Dato['preventa'] == 'habilitado' && $Dato['estado_pedido'] == 'reasignado') :
                    $estado='<span style="color: blue;">Puede reasignar ('.$nombre_motivo['motivo'].')</span>';
                
                elseif ($Dato['preventa'] == 'anulado') :
                    $estado='<span style="color: red;">Anulado</span>';
                endif;
                
                $nestedData[]=$estado;
                $Aux='';
                
                if($permiso_facturar):
                    $Aux.="<a href='?/operaciones/nota_ver/{$Dato['id_egreso']}' data-toggle='tooltip' data-title='Convertir en factura' title='Convertir en factura'><i class='glyphicon glyphicon-qrcode'></i> </a>";
                endif;
                if($permiso_ver10):
                    $Aux.="<a href='?/operaciones/notas_ver/{$Dato['id_egreso']}' data-toggle='tooltip' data-title='Ver detalle de nota de venta' title='Ver detalle de nota de venta'><i class='glyphicon glyphicon-list-alt'></i> </a>";
                endif;
                if($permiso_eliminar):
                    $Aux.="<a href='?/operaciones/notas_eliminar/{$Dato['id_egreso']}' data-toggle='tooltip' data-title='Eliminar nota de venta' title='Eliminar nota de venta' data-eliminar='true'><span class='glyphicon glyphicon-trash'></span> </a>";
                endif;
                if($permiso_devolucion){ 
                    if ($Dato['estadoe'] == '3' || ($Dato['distribuir'] == 'N' && $Dato['estadoe']==0) ){ 
                        $Aux.="<a href='?/operaciones/notas_devolucion/{$Dato['id_egreso']}' data-toggle='tooltip' data-title='Devolver nota de venta' title='Devolver nota de venta' data-eliminar='true'><span class='glyphicon glyphicon-transfer'></span> </a>";
                    }
                }
                $nestedData[]=$Aux;
                
                
                
	            $Aux2='<a href="?/operaciones/notas_imprimir/'.$Dato['id_egreso'].'" data-toggle="tooltip" data-title="Imprimir" target="_blank"><span class="glyphicon glyphicon-print" style="color:green"></span></a>&nbsp;';
                
                if ($Dato['nro_factura']!=0) { 
                    $Aux2.='<a href="?/operaciones/refacturado_imprimir/'.$Dato['id_egreso'].'" data-toggle="tooltip" data-title="Imprimir Factura" target="_blank"><span class="glyphicon glyphicon-qrcode" style="color:blue"></span></a>&nbsp;';
                } 
                if ($Dato['estadoe']==3 || $Dato['distribuir']=='N') { 
                    $Aux2.='<a href="?/notas/recibo_dinero/'.$Dato['id_egreso'].'" data-toggle="tooltip" data-title="Imprimir Recibo" target="_blank"><span class="glyphicon glyphicon-tag" style="color:blue"></span></a>';
                }
                $nestedData[]=$Aux2;

                
                
                // $nestedData[]="<input type='checkbox' data-toggle='tooltip' data-title='Seleccionar' data-seleccionar='{$Dato['id_egreso']}'>";
                $nestedData[]=$Dato['id_egreso'];
    
                $data[]=$nestedData;
            endif;
        }
    endforeach;

    $json_data=[
        'draw'           =>intval($requestData['draw']),
        'recordsTotal'   =>intval($totalData),
        'recordsFiltered'=>intval($totalFiltered),
        'data'           =>$data
    ];

    echo json_encode($json_data);