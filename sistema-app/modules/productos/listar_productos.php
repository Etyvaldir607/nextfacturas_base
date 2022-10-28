<?php
    $requestData=$_REQUEST;
    $ValorG=$requestData['search']['value'];

    $permisos = explode(',', permits);
    $permiso_crear = in_array('crear', $permisos);
    $permiso_editar = in_array('editar', $permisos);
    $permiso_ver = in_array('ver', $permisos);
    $permiso_eliminar = in_array('eliminar', $permisos);
    $permiso_imprimir = in_array('imprimir', $permisos);
    $permiso_cambiar = in_array('cambiar', $permisos);
    $permiso_distribuir = in_array('activar', $permisos);
    $permiso_promocion = in_array('promocion', $permisos);
    $permiso_fijar = false;
    $permiso_quitar = in_array('quitar', $permisos);
    $permiso_ver_precio = true;
    $permiso_asignar_precio = true;
    $permiso_regalo = in_array('regalo', $permisos);

    $Campos=[
        'p.id_producto',
        'p.imagen',
        'p.codigo',
        'p.nombre',
        'p.nombre_factura',
        'c.categoria',
        'pr.proveedor',
        // 'p.descripcion',
        'p.cantidad_minima',
        'p.precio_actual',
        'u.unidad',
        'cantidad_mayor',
        'p.visible'
    ];

    $Sentencia="SELECT p.*,u.unidad,c.categoria, pr.proveedor
                FROM inv_productos p
                LEFT JOIN inv_unidades u ON p.unidad_id=u.id_unidad
                LEFT JOIN inv_categorias c ON p.categoria_id=c.id_categoria
                LEFT JOIN inv_proveedores pr ON pr.id_proveedor=p.proveedor_id";
                // WHERE visible = 's' ";
                
    //FILTRO GENERAL
    $Sentencia.=" WHERE";
    if(!empty($ValorG)):
        $Sentencia.=" (p.codigo LIKE '%{$ValorG}%' OR
                    p.nombre LIKE '%{$ValorG}%' OR
                    p.nombre_factura LIKE '%{$ValorG}%' OR
                    c.categoria LIKE '%{$ValorG}%' OR
                    pr.proveedor LIKE '%{$ValorG}%' OR
                    p.descripcion LIKE '%{$ValorG}%' OR
                    p.precio_actual LIKE '%{$ValorG}%' OR
                    u.unidad LIKE '%{$ValorG}%' OR
                    p.cantidad_minima LIKE '%{$ValorG}%')";
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
    $totalFiltered=count($db->query($Sentencia)->fetch());
    $totalData=$totalFiltered;

    //ORDEN
    if(isset($requestData['order'][0]['column'])):
        $Columna=$requestData['order'][0]['column'];
        $Orden=$requestData['order'][0]['dir'];
        $Sentencia.=" ORDER BY {$Campos[$Columna]} {$Orden}";
    endif;
    //LIMITE
    $Inicio=$requestData['start']?$requestData['start']:0;
    $Final=$requestData['length']?$requestData['length']:50;
    $Sentencia.=" LIMIT {$Inicio},{$Final}";

    $Consulta=$db->query($Sentencia)->fetch();
    $data=[];

    foreach($Consulta as $key=>$Dato):
        $nestedData=[];

        $nestedData[]=$requestData['start']+$key+1;
        $Aux=($Dato['imagen']=='')?imgs.'/image.jpg':files.'/productos/'.$Dato['imagen'];
        $nestedData[]="<img src='{$Aux}'  class='img-rounded cursor-pointer' data-toggle='modal' data-target='#modal_mostrar' data-modal-size='modal-md' data-modal-title='Imagen' width='75' height='75'>";
        $Aux=escape($Dato['codigo']);
        //$nestedData[]="<samp class='lead'>{$Aux}</samp>";
        $nestedData[]="{$Aux}";
        $nestedData[]=escape($Dato['nombre']);
        $nestedData[]=escape($Dato['nombre_factura']);
        $nestedData[]=escape($Dato['categoria']);
        $nestedData[]=escape($Dato['proveedor']);
        // $nestedData[]=escape($Dato['descripcion']);
        $nestedData[]=escape($Dato['cantidad_minima']);
        $nestedData[]="Precio producto: <b>" . escape(number_format($Dato['precio_actual'],2,',','.')) . '</b>,<br>' . "Precio contado: <b>" .escape(number_format($Dato['precio_contado'],2,',','.')) . '</b>,<br>' . "Precio por mayor: <b>" .escape(number_format($Dato['precio_mayor'],2,',','.')) . "</b>";
        $nestedData[]=escape($Dato['unidad']);
        $nestedData[]=escape($Dato['cantidad_mayor']);
        
        $nestedData[]=escape($Dato['codigo_sanitario']);
        
        /*
        $Aux=[escape('(1)'.$Dato['unidad']),escape(number_format($Dato['precio_actual'],2,',','.')),escape(number_format($Dato['precio_contado'],2,',','.')),escape(number_format($Dato['precio_mayor'],2,',','.'))];
        $Aux="<span class='glyphicon glyphicon-remove-circle'></span> {$Aux[0]}: <span class='text-info'>P</span><b>{$Aux[1]}</b><span class='text-info'>PC</span><b>{$Aux[2]}</b><span class='text-info'>PM</span><b>{$Aux[3]}</b><br>";
        $ids_asignaciones=$db->select('*')->from('inv_asignaciones a')->join('inv_unidades b','a.unidad_id = b.id_unidad' )->where('a.producto_id',$Dato['id_producto'])->fetch();
        foreach($ids_asignaciones as $i=>$id_asignacion):
            if(empty($ids_asignaciones)):
                $Aux.='<span>No asignado</span>';
            else:
                if($permiso_quitar):
                    $Aux.="<a href='?/productos/quitar/{$id_asignacion['id_asignacion']}' class='underline-none' data-toggle='tooltip' style='margin-right: 5px;' title='Eliminar unidad' data-title='Eliminar unidad' data-quitar='true'>
                                <span class='glyphicon glyphicon-remove-circle'></span>
                            </a>";
                endif;
                $Extra=escape('('.$id_asignacion['cantidad_unidad'].')'.$id_asignacion['unidad']);
                $Aux.="<span>{$Extra}:</span>";
                if($permiso_fijar):
                    $Extra=[escape($id_asignacion['otro_precio']),escape($id_asignacion['precio_contado']),escape($id_asignacion['precio_mayor'])];
                    $Aux.="<a href='?/productos/fijar/{$id_asignacion['id_asignacion']}' class='underline-none text-primary' data-toggle='tooltip' style='margin-right: 5px;' title='Fijar precio' data-title='Fijar precio' data-fijar='true'>
                                <span class='text-info'>P</span><b>{$Extra[0]}</b><span class='text-info'>PC</span><b>{$Extra[1]}</b><span class='text-info'>PM</span><b>{$Extra[2]}</b>
                            </a>";
                else:
                    $Extra=[escape($id_asignacion['otro_precio']),escape($id_asignacion['precio_contado']),escape($id_asignacion['precio_mayor'])];
                    $Aux.="<span class='text-info'>P</span><b>{$Extra[0]}</b><span class='text-info'>PC</span><b>{$Extra[1]}</b><span class='text-info'>PM</span><b>{$Extra[2]}</b>";
                endif;
            endif;
            $Aux.='<br>';
        endforeach;
        $nestedData[]=$Aux;
        */
        
        if($Dato['visible']=='s'){
            $visible_aux = 'Visible';            
        }else{
            $visible_aux = 'No visible';
        }
        
        $nestedData[]=escape($visible_aux);

        $Aux='';
        if($permiso_ver || $permiso_editar || $permiso_eliminar || $permiso_cambiar):
            if($permiso_ver):
                if($Dato['promocion']==''):
                    $Aux.="<a href='?/productos/ver/{$Dato['id_producto']}' data-toggle='tooltip' style='margin-right: 5px;' title='Ver producto' data-title='Ver producto'><i class='glyphicon glyphicon-search'></i></a>";
                else:
                    $Aux.="<a href='?/productos/ver_promocion/{$Dato['id_producto']}' data-toggle='tooltip' style='margin-right: 5px;' title='Ver producto' data-title='Ver producto'><i class='glyphicon glyphicon-search'></i></a>";
                endif;
            endif;
            if($permiso_editar):
                if($Dato['promocion']==''):
                    $Aux.="<a href='?/productos/editar/{$Dato['id_producto']}' data-toggle='tooltip' style='margin-right: 5px;' title='Editar producto' data-title='Editar producto'><i class='glyphicon glyphicon-edit'></i></a>";
                else:
                    $Aux.="<a href='?/productos/editar_promocion/{$Dato['id_producto']}' data-toggle='tooltip' style='margin-right: 5px;' title='Editar producto' data-title='Editar producto'><i class='glyphicon glyphicon-edit'></i></a>";
                endif;
            endif;
            if($permiso_eliminar):
                if($Dato['visible']=='s'):
                    $Aux.="<a href='?/productos/eliminar/{$Dato['id_producto']}' data-toggle='tooltip' style='margin-right: 5px;' title='Cambiar a: No visible' data-title='Cambiar a: No visible' data-eliminar='true'><i class='glyphicon glyphicon-eye-close'></i></a>";
                else:
                    $Aux.="<a href='?/productos/eliminar/{$Dato['id_producto']}' data-toggle='tooltip' style='margin-right: 5px; color:#d00;' title='Cambiar a: Visible' data-title='Cambiar a: Visible' data-eliminar='true'><i class='glyphicon glyphicon-eye-open'></i></a>";
                endif;
            endif;
            if($permiso_cambiar && $Dato['promocion']==''):
                $Aux.="<a href='#' data-toggle='tooltip' style='margin-right: 5px;' title='Actualizar precio' data-title='Actualizar precio' data-actualizar='{$Dato['id_producto']}'><span class='glyphicon glyphicon-refresh'></span></a>";
            endif;
            
            // if($permiso_ver_precio):
            //     $Aux.="<a href='?/precios/ver/{$Dato['id_producto']}' target='_blank' data-toggle='tooltip' style='margin-right: 5px;' title='Ver historial' data-title='Ver historial'><i class='glyphicon glyphicon-list-alt'></i></a>";
            // endif;
            // if($permiso_asignar_precio):
            //     $Aux.="<a href='?/productos/asignar/{$Dato['id_producto']}' class='underline-none' data-toggle='tooltip' style='margin-right: 5px;' title='Asignar nuevo precio' data-title='Asignar nuevo precio' data-asignar-precio='true'>
            //                 <span class='glyphicon glyphicon-tag'></span>
            //             </a>";
            // endif;
        endif;
        if($permiso_regalo):
            if ($Dato['regalo'] == 0):
                $Aux.="<a onclick='regalo({$Dato['id_producto']})' class='underline-none' data-toggle='tooltip' style='margin-right: 5px;' title='Agregar a regalos de promocion' data-title='Agregar a regalos de promocion' data-regalar='true'>
                    <span class='glyphicon glyphicon-gift'></span>
                </a>";
            else:
                $Aux.="<a onclick='quitar_regalo({$Dato['id_producto']})' class='underline-none text-danger' data-toggle='tooltip' style='margin-right: 5px;' title='Quitar de regalos de promocion' data-title='Quitar de regalos de promocion' data-regalar='true'>
                    <span class='glyphicon glyphicon-gift'></span>
                </a>";
            endif;
        endif;
        $nestedData[]=$Aux;
        $nestedData[]=$Dato['id_producto'];
        $data[]=$nestedData;
    endforeach;

    $json_data=[
        'draw'           =>intval($requestData['draw']),
        'recordsTotal'   =>intval($totalData),
        'recordsFiltered'=>intval($totalFiltered),
        'data'           =>$data
    ];

    echo json_encode($json_data);





/*
    Array (
        [order] => Array (
            [0] => Array (
                [column] => 0
                [dir] => asc
            )
        )
        [start] => 0
        [length] => 25
        [search] => Array (
            [value] =>
            [regex] => false
        )
    )


    Array (
        [order] => Array (
            [0] => Array (
                [column] => 1
                [dir] => asc
            )
        )
        [start] => 0
        [length] => 25
        [search] => Array (
            [value] =>
            [regex] => false
        )
    )
    */