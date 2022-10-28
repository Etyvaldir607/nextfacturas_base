<?php
    $requestData=$_REQUEST;
    $ValorG=$requestData['search']['value'];

    $formato_textual = get_date_textual($_institution['formato']);
    $formato_numeral = get_date_numeral($_institution['formato']);

    // Obtiene los permisos
    $permisos = explode(',', permits);
    // Almacena los permisos en variables
    $permiso_ver = in_array('preventas_ver', $permisos);
    $permiso_imprimir = false;
    $permiso_cliente = in_array('preventas_asignar', $permisos);
    $permiso_habilitar=in_array('preventas_habilitar', $permisos);
    $permiso_eliminar=in_array('preventas_eliminar', $permisos);
    $permiso_despachar=in_array('preventas_asignar_despachar', $permisos);
    
    $permiso_anular_reasignada = in_array('anular', $permisos);
    
    $permiso_cambiar = true;
    $permiso_preventa_distribucion_editar=in_array('preventa_distribucion_editar', $permisos);

    $Campos=[
        'i.nro_nota',
        'i.fecha_habilitacion',
        'c.cliente',
        'i.monto_total',
        'i.distribuir',
        'i.plan_de_pagos',
        'i.descripcion_venta',
        'a.almacen',
        'e.nombres'
    ];
    $IdUsuario=$_user['id_user'];
    
    $Sentencia="SELECT  DISTINCT(i.nro_movimiento), i.*,c.codigo,a.almacen,a.principal,e.nombres,e.paterno,e.materno,e.cargo,
                        ac.estado_pedido, ac.estado as estado_a, ifnull(ac.id_asignacion_cliente,-1)as id_asignacion_cliente, CONCAT(em.nombres,' ',em.paterno,' ',em.materno) as distribuidor, i.plan_de_pagos,
                        c.cliente as nombre_cliente2
                FROM inv_egresos i
                LEFT JOIN inv_almacenes a ON i.almacen_id=a.id_almacen
                LEFT JOIN sys_empleados e ON i.vendedor_id=e.id_empleado
                LEFT JOIN sys_users AS u ON e.id_empleado=u.persona_id
                LEFT JOIN inv_clientes c ON i.cliente_id = c.id_cliente
                LEFT JOIN inv_asignaciones_clientes ac ON i.id_egreso = ac.egreso_id AND ac.estado = 'A'
                LEFT JOIN sys_empleados em ON ac.distribuidor_id=em.id_empleado
                
                LEFT join inv_clientes_grupos cg on cg.id_cliente_grupo=i.codigo_vendedor
                LEFT JOIN sys_supervisor ss ON cg.id_cliente_grupo=ss.cliente_grupo_id
                        
                LEFT JOIN inv_users_almacenes ua ON ua.almacen_id=i.almacen_id
                
                WHERE i.tipo IN('Preventa', 'No venta', 'Venta')
                        AND (
				            preventa is NULL
				                OR 
				            (preventa='habilitado' AND estadoe!=3)
				            )
				        AND ( 
				            NOT ( (preventa='habilitado' OR preventa is NULL) AND estadoe=4 AND estado_pedido!='reasignado') 
				        )
				        AND (
        			        (i.vendedor_id='".$_user['persona_id']."' AND '".$_user['rol_id']."' = 15)
        			        OR 
        			        (ss.user_ids='".$_user['id_user']."' AND '".$_user['rol_id']."' = 14)
        			        OR 
        			        '".$_user['rol_id']."' = 1
        			        OR 
        			        (ua.user_id='".$_user['id_user']."' AND '".$_user['rol_id']."' = 17)
        			    )
				";            
    
    if(!empty($ValorG)):
        $Sentencia.=" AND (i.id_egreso LIKE '%{$ValorG}%' OR
                        i.fecha_habilitacion LIKE '%{$ValorG}%' OR
                        i.nro_movimiento LIKE '%{$ValorG}%' OR
                        i.cliente_id LIKE '%{$ValorG}%' OR
                        i.nombre_cliente LIKE '%{$ValorG}%' OR
                        i.nit_ci LIKE '%{$ValorG}%' OR
                        i.nro_factura LIKE '%{$ValorG}%' OR
                        i.monto_total LIKE '%{$ValorG}%' OR
                        i.nro_registros LIKE '%{$ValorG}%' OR
                        i.id_egreso LIKE '%{$ValorG}%' OR
                        a.almacen LIKE '%{$ValorG}%' OR
                        e.nombres LIKE '%{$ValorG}%')";
    endif;

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

    if(empty($ValorG)):
        $Sentencia.=" ORDER BY i.fecha_habilitacion DESC";
    endif;


    //LIMITE
    $Inicio=$requestData['start']?$requestData['start']:0;
    $Final=$requestData['length']?$requestData['length']:50;
    $Sentencia.=" LIMIT {$Inicio},{$Final}";

    $Consulta=$db->query($Sentencia)->fetch();

    $data=[];

    foreach($Consulta as $key=>$Dato){
        
        $id_motivo = $Dato['motivo_id'];
        $nombre_motivo = $db->query("select * from gps_noventa_motivos where id_motivo = '$id_motivo'")->fetch_first();
        
        $fecha_habilitacion=explode(" ",$Dato['fecha_habilitacion']);
        
        //if($Dato['sub'] || $IdUsuario<=2):
            $nestedData=[];
            $nestedData[]=$Dato['nro_nota'];
            $Aux=escape(date_decode($fecha_habilitacion[0], $_institution['formato']));
            
            //$nestedData[]="{$Dato['sub']} $Aux <small class='text-success'>{$Dato['hora_egreso']}</small>";
            
            $nestedData[]="$Aux <small class='text-success'>{$fecha_habilitacion[1]}</small>";
            $nestedData[]=escape($Dato['nombre_cliente2']);
            //$nestedData[]=escape($Dato['nro_factura']);
            $nestedData[]=number_format($Dato['monto_total'],2,',','.');
            
            $nestedData[]= ($Dato['distribuir'] == 'S')? 'Distribuir': 'Entrega Inmediata';
            $nestedData[]= ($Dato['plan_de_pagos'] == 'si')? 'Plan de pagos': 'Contado';
            $nestedData[]=escape($Dato['descripcion_venta']);
            
            $nestedData[]=escape($Dato['almacen']);
            $nestedData[]=escape($Dato['nombres'].' '.$Dato['paterno'].' '.$Dato['materno']);
            
            if ($Dato['preventa'] == NULL && $Dato['estado_pedido'] == NULL):
                $nestedData[]='<span style="color: red;">No esta habilitado</span>';
            
            /***********************/
            
            elseif ($Dato['preventa'] == 'habilitado' && $Dato['estado_pedido'] == 'salida' && $Dato['distribuir'] == "S"):
                $nestedData[]='Ya fue asignado';

            elseif ($Dato['preventa'] == 'habilitado' && $Dato['estado_pedido'] == 'salida' && $Dato['distribuir'] == "N"):
                $nestedData[]='En espera del cliente';
            
            elseif ($Dato['preventa'] == 'habilitado' && $Dato['estado_pedido'] == 'entregado' && $Dato['estadoe'] == "2"):
                $nestedData[]='En espera del cliente';
            
            /***********************/
            
            elseif ($Dato['preventa'] == 'habilitado' && $Dato['estado_pedido'] == 'entregado' && $Dato['estadoe'] == '3'):
                $nestedData[]='<span style="color: green;">Ya fue entregado</span>';
            
            elseif ($Dato['preventa'] == 'habilitado' && $Dato['estado_pedido'] == NULL):
                $nestedData[]='<span style="color: blue;">Falta asignacion</span>';
            
            elseif ($Dato['preventa'] == NULL && $Dato['estado_pedido'] == 'reasignado') :
                $nestedData[]='<span style="color: blue;">Puede reasignar ('.$nombre_motivo['motivo'].')</span>';
            
            elseif ($Dato['preventa'] == 'habilitado' && $Dato['estado_pedido'] == 'reasignado') :
                $nestedData[]='<span style="color: blue;">Puede reasignar ('.$nombre_motivo['motivo'].')</span>';
            
            elseif ($Dato['preventa'] == 'habilitado' && $Dato['estado_pedido'] == 'sin_aprobacion') :
                $nestedData[]='<span style="color: green;">Almacen preparando los productos</span>';
            
            else:
                //$nestedData[]=$Dato['preventa']." ---".$Dato['estado_pedido'];
                $nestedData[]='';
            endif;

            /*********************/

            if ($Dato['distribuidor'] == '' || ($Dato['estadoe'] == '4' && $Dato['preventa'] != NULL) ):
                $nestedData[]='<span style="color: red;">Falta asignacion</span>';
            elseif ($Dato['distribuir'] == "S"):
                $nestedData[]=escape($Dato['distribuidor']);
            else:
                $nestedData[]='';
            endif;

            $Aux='';
            if($permiso_eliminar || $permiso_cliente || $permiso_habilitar):
                if($Dato['distribuir'] == "S"):
                    if(!$Dato['estado_a'] || $Dato['preventa'] == NULL):
                        if($Dato['preventa'] != 'habilitado'):
                            if($permiso_habilitar):
                                $Aux.=" <a href='?/asignacion/preventas_habilitar/{$Dato['id_egreso']}' data-toggle='tooltip' data-title='Habilitar preventa' title='Habilitar preventa' class='text-success'><i class='glyphicon glyphicon-thumbs-up'></i></a>";
                            endif;
                        else:
                            if($permiso_cliente):
                                $Aux.=" <a href='?/asignacion/preventas_asignar/{$Dato['id_egreso']}' data-toggle='tooltip' data-title='Asignar distribuidor' title='Asignar distribuidor' class='text-success'><i class='glyphicon glyphicon-user'></i></a>";
                            endif;
                

                            if($permiso_preventa_distribucion_editar):
                                $Aux.=' <a onclick="entregar_asignacion('.$Dato['id_asignacion_cliente'].', '.$Dato['id_egreso'].')" data-toggle="tooltip" data-title="Entregar asignacion" title="Entregar asignacion" class="text-success"><i class="glyphicon glyphicon-download"></i></a>';
                            endif;
                    

                        endif;
                    else:
                        if($Dato['estadoe'] == '4' && $Dato['preventa'] != NULL):
                            if($permiso_cliente):
                                $Aux.=" <a href='?/asignacion/preventas_asignar/{$Dato['id_egreso']}' data-toggle='tooltip' data-title='Reasignar distribuidor' title='Reasignar distribuidor' class='text-warning'><i class='glyphicon glyphicon-user'></i></a>";
                            endif;
                            if($permiso_anular_reasignada):
                                $Aux.=" <a onclick='eliminar_nota_venta({$Dato['id_egreso']})' data-toggle='tooltip' data-title='Anular nota de venta' title='Anular nota de venta' class='text-danger'><i class='glyphicon glyphicon-remove'></i></a>";
                            endif;
                        else:
                            $Aux.=" <a href='?/asignacion/preventas_ver/{$Dato['id_egreso']}' data-toggle='tooltip' data-title='Ver asignacion' title='Ver asignacion' class='text-info'><i class='glyphicon glyphicon-eye-open'></i></a>";
                        
                            if($Dato['estado_pedido'] != 'entregado' && $Dato['preventa'] != NULL && $permiso_eliminar):
                                // $Aux.=" <a onclick='entregar_asignacion({$Dato['id_asignacion_cliente']})' data-toggle='tooltip' data-title='Entregar asignacion' title='Entregar asignacion' class='text-info'><i class='glyphicon glyphicon-transfer'></i></a>";
                                $Aux.=" <a onclick='eliminarar_asignacion({$Dato['id_asignacion_cliente']})' data-toggle='tooltip' data-title='Eliminar asignacion' title='Eliminar asignacion' class='text-danger'><i class='glyphicon glyphicon-remove'></i></a>";
                            endif;
                        endif;
                    endif;
                else:
                    if($Dato['preventa'] != 'habilitado'):
                        if($permiso_habilitar):
                            $Aux.=" <a href='?/asignacion/preventas_habilitar/{$Dato['id_egreso']}' data-toggle='tooltip' data-title='Habilitar preventa' title='Habilitar preventa' class='text-success'><i class='glyphicon glyphicon-thumbs-up'></i></a>";
                        endif;
                    else:
                        if($permiso_preventa_distribucion_editar && $Dato['distribuir'] != 'S'):
                            $Aux.=' <a onclick="entregar_asignacion('.$Dato['id_asignacion_cliente'].', '.$Dato['id_egreso'].')" data-toggle="tooltip" data-title="Entregar asignacion" title="Entregar asignacion" class="text-success"><i class="glyphicon glyphicon-download"></i></a>';
                        endif;
                        //$Aux.=" <a href='?/asignacion/preventas_asignar_despachar/{$Dato['id_egreso']}' data-toggle='tooltip' data-title='Despachar productos' title='Despachar productos' class='text-success'><i class='glyphicon glyphicon-send'></i>".$Dato['estado_pedido']." ".$permiso_despachar."</a>";

                        //$Aux.=' <a onclick="entregar_asignacion('.$Dato['id_asignacion_cliente'].', '.$Dato['id_egreso'].')" data-toggle="tooltip" data-title="Entregar asignacion" title="Entregar asignacion" class="text-success"><i class="glyphicon glyphicon-download"></i></a>';
                        
                        
                        //$Aux.=' <a href="?/asignacion/asignacion_imprimir2/0/'.$Dato['id_egreso'].'" target="_blank" data-toggle="tooltip" data-title="Hoja de salida"><i class="glyphicon glyphicon-print"></i></a>';
                        //$Aux.=' <a href="?/notas/imprimir_nota/'.$Dato['id_egreso'].'" target="_blank" data-toggle="tooltip" data-title="Notas de venta"><i class="glyphicon glyphicon-list"></i></a>';

                        // if($Dato['estado_pedido'] == 'salida'):
                        //     $Aux.=' <a onclick="entregar_asignacion('.$Dato['id_asignacion_cliente'].', '.$Dato['id_egreso'].')" data-toggle="tooltip" data-title="Entregar asignacion" title="Entregar asignacion" class="text-success"><i class="glyphicon glyphicon-download"></i></a>';
                        //     $Aux.=' <a href="?/asignacion/asignacion_imprimir2/0/'.$Dato['id_egreso'].'" target="_blank" data-toggle="tooltip" data-title="Hoja de salida"><i class="glyphicon glyphicon-print"></i></a>';
                        //     $Aux.=' <a href="?/notas/imprimir_nota/'.$Dato['id_egreso'].'" target="_blank" data-toggle="tooltip" data-title="Notas de venta"><i class="glyphicon glyphicon-list"></i></a>';
                        // else:
                        //     if($permiso_despachar):
                        //         $Aux.=" <a href='?/asignacion/preventas_asignar_despachar/{$Dato['id_egreso']}' data-toggle='tooltip' data-title='Despachar productos' title='Despachar productos' class='text-success'><i class='glyphicon glyphicon-send'></i></a>";
                        //     endif;
                        // endif;
                    endif;
                endif;
                $Aux.=' <a href="?/notas/imprimir_nota/'.$Dato['id_egreso'].'" target="_blank" data-toggle="tooltip" data-title="Imprimir nota de venta" title="Imprimir nota de venta"><i class="glyphicon glyphicon-print"></i></a>';
            endif;
            $nestedData[]=$Aux;




            $Aux='';
            //if(!$Dato['estado_a'] || $Dato['preventa'] == NULL){
            if(!$Dato['estado_a']){
                if($Dato['preventa'] == 'habilitado'){
                    if($permiso_cliente && $Dato['distribuir'] == 'S'){
                        $Aux.='<input type="checkbox" id="id_detalle_'.$Dato['id_egreso'].'" name="id_detalle[]" value="'.$Dato['id_egreso'].'" onchange="checkk('.$Dato['id_egreso'].')">';
                    }
                }
            }else{
                if($Dato['estadoe'] == '4' && $Dato['preventa'] != NULL){
                    if($permiso_cliente){
                         $Aux.='<input type="checkbox" id="id_detalle_'.$Dato['id_egreso'].'" name="id_detalle[]" value="'.$Dato['id_egreso'].'" >';
                    }
                }
            }
            $nestedData[]=$Aux;

            $nestedData[]="<input type='checkbox' data-toggle='tooltip' data-title='Seleccionar' data-seleccionar='{$Dato['id_egreso']}'>";
            $nestedData[]=escape($Dato['monto_total']);
            $data[]=$nestedData;
        //endif;
    }

    $json_data=[
        'draw'           =>intval($requestData['draw']),
        'recordsTotal'   =>intval($totalData),
        'recordsFiltered'=>intval($totalFiltered),
        'data'           =>$data
    ];

    echo json_encode($json_data);