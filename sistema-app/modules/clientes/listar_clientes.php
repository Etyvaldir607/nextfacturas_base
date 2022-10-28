<?php
    $requestData=$_REQUEST;
    $ValorG=$requestData['search']['value'];

    // Obtiene los permisos
    $permisos = explode(',', permits);
    $permiso_imprimir = in_array('imprimir', $permisos);
    $permiso_eliminar = in_array('eliminar', $permisos);
    $permiso_modificar = in_array('editar', $permisos);

    $Campos=[
        'a.id_cliente',
        'a.imagen',
        'a.cliente',
        'a.nit',
        'a.telefono',
        'c.ciudad',
        'a.direccion',
        'a.tipo',
        'nombre_grupo',
        'nombre_dia'
    ];

//LEFT JOIN inv_egresos AS b ON a.id_cliente=b.cliente_id
//SUM(if(b.id_egreso is null, 0, 1)) as nro_visitas,                 
                
    $Sentencia="SELECT  a.id_cliente,a.imagen,a.codigo,a.cliente,a.nit,a.telefono,a.direccion,a.tipo,a.estado, bx.nombre_dia,
                        c.ciudad, 
                        IF(cg.nombre_grupo is null,'General',cg.nombre_grupo) as nombre_grupo
                FROM inv_clientes AS a
                LEFT JOIN inv_dia AS bx ON a.dia=bx.id_dia
                LEFT JOIN inv_ciudades c ON c.id_ciudad = a.ciudad_id
                LEFT JOIN inv_clientes_grupos cg ON cg.id_cliente_grupo = a.cliente_grupo_id
                
                LEFT JOIN sys_users u ON u.persona_id=cg.vendedor_id
                
                LEFT JOIN sys_supervisor ss ON cg.id_cliente_grupo=ss.cliente_grupo_id
                ";
    
    $Sentencia.=" WHERE    a.estado='si' AND (
                                (cg.vendedor_id='".$_user['persona_id']."' AND '".$_user['rol_id']."' = 15)
            			        OR 
            			        (ss.user_ids='".$_user['id_user']."' AND '".$_user['rol_id']."' = 14)
            			        OR 
            			        '".$_user['rol_id']."' = 1
            			    )
                ";



                //LEFT JOIN sys_supervisor ss ON cg.id_cliente_grupo = a.cliente_grupo_id";


    if(!empty($ValorG)):
        $Sentencia.=" AND( 
                    a.cliente LIKE '%{$ValorG}%' OR
                    a.nit LIKE '%{$ValorG}%' OR
                    a.telefono LIKE '%{$ValorG}%' OR
                    c.ciudad LIKE '%{$ValorG}%' OR
                    a.direccion LIKE '%{$ValorG}%' OR
                    a.tipo LIKE '%{$ValorG}%' OR
                    nombre_grupo LIKE '%{$ValorG}%'
                    )";
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

    $Sentencia=rtrim($Sentencia,' WHERE');

    $grupo = " GROUP BY a.id_cliente ";

    // $totalFiltered=count($db->query($Sentencia.$grupo)->fetch());
    // $totalData=$totalFiltered;

    $Sentencia.=" GROUP BY a.cliente, a.nit";
    //ORDEN
    if(isset($requestData['order'][0]['column'])):
        $Columna=$requestData['order'][0]['column'];
        $Orden=$requestData['order'][0]['dir'];
        $Sentencia.=" ORDER BY {$Campos[$Columna]} {$Orden}";
    endif;

    $totalFiltered=count($db->query($Sentencia)->fetch());
    $totalData=$totalFiltered;

    //LIMITE
    if($requestData['length']!='-1'):
        $Inicio=$requestData['start']?$requestData['start']:0;
        $Final=$requestData['length']?$requestData['length']:25;
        $Sentencia.=" LIMIT {$Inicio},{$Final}";
    endif;

    $Consulta=$db->query($Sentencia)->fetch();
    $data=[];

    foreach($Consulta as $key=>$Dato):
        $nestedData=[];
        $nestedData[]=$requestData['start']+$key+1;

        $Aux=($Dato['imagen']=='')?imgs.'/image.jpg':files.'/tiendas/'.$Dato['imagen'];
        $Aux="<img src='{$Aux}' class='img-rounded cursor-pointer' data-toggle='modal' data-target='#modal_mostrar' data-modal-size='modal-md' data-modal-title='Imagen' width='75' height='75'>";
        $nestedData[]=$Aux;

        $nestedData[]=escape($Dato['cliente']);
        $nestedData[]=escape($Dato['nit']);
        $nestedData[]=escape($Dato['telefono']);
        $nestedData[]=escape($Dato['ciudad']);
        $nestedData[]=escape($Dato['direccion']);
        $nestedData[]=escape($Dato['tipo']);
        $nestedData[]=escape($Dato['nombre_grupo']);
        $nestedData[]=escape($Dato['nombre_dia']);
        // $nestedData[]=escape($Dato['nro_visitas']);

        $Aux='';
        if($permiso_modificar || $permiso_eliminar):
            if($permiso_modificar):
                $Aux.="<a href='?/clientes/editar/{$Dato['id_cliente']}' data-toggle='tooltip' data-title='Modificar cliente'><span class='glyphicon glyphicon-edit'></span></a>";
            endif;
            if($permiso_eliminar):
                $Aux.="<a href='?/clientes/eliminar/{$Dato['id_cliente']}' data-toggle='tooltip' data-title='Eliminar cliente' data-eliminar='true'><span class='glyphicon glyphicon-trash'></span></a>";
            endif;
        endif;
        $nestedData[]=$Aux;

        $data[]=$nestedData;
    endforeach;

    $json_data=[
        'draw'           =>intval($requestData['draw']),
        'recordsTotal'   =>intval($totalData),
        'recordsFiltered'=>intval($totalFiltered),
        'data'           =>$data
    ];

    echo json_encode($json_data);