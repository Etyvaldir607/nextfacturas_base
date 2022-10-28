<?php
    $requestData=$_REQUEST;
    $ValorG=$requestData['search']['value'];

    $formato_textual = get_date_textual($_institution['formato']);
    $formato_numeral = get_date_numeral($_institution['formato']);

    // Obtiene los permisos
    $permisos = explode(',', permits);
    // Almacena los permisos en variables
    $permiso_ver = in_array('preventas_ver', $permisos);
    $permiso_eliminar = in_array('preventas_eliminar', $permisos);
    $permiso_imprimir = false;
    $permiso_facturar = in_array('preventas_facturar', $permisos);
    $permiso_editar = in_array('preventas_editar', $permisos);
    $permiso_devolucion = in_array('preventas_devolucion', $permisos);
    $permiso_cambiar = true;

    $fecha_inicial=$params[0];
    $fecha_final=$params[1];

    $Campos=[
        'i.id_egreso',
        'i.fecha_egreso',
        'i.cliente_id',
        'i.nombre_cliente',
        'i.nit_ci',
        'i.nro_factura',
        'i.monto_total',
        'i.nro_registros',
        'i.id_egreso',
        'a.almacen',
        'e.nombres'
    ];
    $IdUsuario=$_user['id_user'];
    $Sentencia="SELECT i.*,a.almacen,a.principal,e.nombres,e.paterno,e.materno,e.cargo,IFNULL(
                        (SELECT true FROM sys_supervisor AS s WHERE s.user_ids='{$IdUsuario}' AND s.user_id=u.id_user)
                    ,false)AS sub
                FROM inv_egresos i
                LEFT JOIN inv_almacenes a ON i.almacen_id=a.id_almacen
                LEFT JOIN sys_empleados e ON i.empleado_id=e.id_empleado
                LEFT JOIN sys_users AS u ON e.id_empleado=u.persona_id
                WHERE i.fecha_egreso>='{$fecha_inicial}' AND i.fecha_egreso<='{$fecha_final}' AND i.estadoe>'1'";

    if(!empty($ValorG)):
        $Sentencia.=" AND (i.id_egreso LIKE '%{$ValorG}%' OR
                        i.fecha_egreso LIKE '%{$ValorG}%' OR
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

    //LIMITE
    $Inicio=$requestData['start']?$requestData['start']:0;
    $Final=$requestData['length']?$requestData['length']:50;
    $Sentencia.=" LIMIT {$Inicio},{$Final}";

    $Consulta=$db->query($Sentencia)->fetch();
    $data=[];

    foreach($Consulta as $key=>$Dato):
        if($Dato['sub'] || $IdUsuario<=2):
            $nestedData=[];
            $nestedData[]=$requestData['start']+$key+1;
            $Aux=escape(date_decode($Dato['fecha_egreso'], $_institution['formato']));
            $nestedData[]="{$Dato['sub']} $Aux <small class='text-success'>{$Dato['hora_proforma']}</small>";
            $nestedData[]=escape($Dato['cliente_id']);
            $nestedData[]=escape($Dato['nombre_cliente']);
            $nestedData[]=escape($Dato['nit_ci']);
            $nestedData[]=escape($Dato['nro_factura']);
            $nestedData[]=number_format($Dato['monto_total'],2,'.','');
            $nestedData[]=escape($Dato['nro_registros']);
            $surtido=$db->select('SUM(k.cantidad) as surtido')->from('inv_egresos_detalles k')->join('inv_unidades a','k.unidad_id = a.id_unidad')->where('k.egreso_id',$Dato['id_egreso'])->where('a.unidad','DCTO SURTIDO')->fetch_first();
            $nestedData[]=$surtido['surtido']||'NINGUNO';
            $nestedData[]=escape($Dato['almacen']);
            $nestedData[]=escape($Dato['nombres'].' '.$Dato['paterno'].' '.$Dato['materno']);
            $nestedData[]=($Dato['cargo']==1)?$_institution['empresa1']:$_institution['empresa2'];
            $Aux='';
            if($permiso_facturar || $permiso_ver || $permiso_eliminar):
                if($permiso_facturar):
                    if(!$Dato['nro_autorizacion']):
                        $Aux.="<a href='?/operaciones/preventa_ver/{$Dato['id_egreso']}' data-toggle='tooltip' data-title'Convertir en factura'><i class='glyphicon glyphicon-qrcode'></i></a>";
                    else:
                        $Aux.="<a data-toggle='tooltip' data-title='Ya se facturo'><i class='glyphicon glyphicon-qrcode'></i></a>";
                    endif;
                endif;
                if($permiso_ver):
                    $Aux.="<a href='?/operaciones/preventas_ver/{$Dato['id_egreso']}' data-toggle='tooltip' data-title='Ver detalle de la preventa'><i class='glyphicon glyphicon-list-alt'></i></a>";
                endif;
                if($permiso_eliminar):
                    $Aux.="<a href='?/operaciones/preventas_eliminar/{$Dato['id_egreso']}' data-toggle='tooltip' data-title='Eliminar preventa' data-eliminar='true'><span class='glyphicon glyphicon-trash'></span></a>";
                endif;
                if($permiso_editar):
                    $Aux.="<a href='?/operaciones/preventas_editar/{$Dato['id_egreso']}'data-toggle='tooltip' data-title='Editar preventa'><span class='glyphicon glyphicon-edit'></span></a>";
                endif;
                if($permiso_devolucion):
                    $Aux.="<a href='?/operaciones/preventas_devolucion/{$Dato['id_egreso']}' data-toggle='tooltip' data-title='devolución'><span class='glyphicon glyphicon-transfer'></span></a>";
                endif;
            endif;
            $nestedData[]=$Aux;
            $nestedData[]="<input type='checkbox' data-toggle='tooltip' data-title='Seleccionar' data-seleccionar='{$Dato['id_egreso']}'>";
            $nestedData[]=escape($Dato['monto_total']);
            $data[]=$nestedData;
        endif;
    endforeach;

    $json_data=[
        'draw'           =>intval($requestData['draw']),
        'recordsTotal'   =>intval($totalData),
        'recordsFiltered'=>intval($totalFiltered),
        'data'           =>$data
    ];

    echo json_encode($json_data);