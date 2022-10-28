<?php
    $requestData=$_REQUEST;
    $ValorG=$requestData['search']['value'];

    $Campos=[
        'p.id_producto',
        'p.codigo',
        'p.nombre_factura',
        'p.descripcion',
        'c.categoria'
    ];

    // Obtiene el almacen principal
    $almacen = $db->from('inv_almacenes')->where('principal', 'S')->fetch_first();
    $id_almacen = ($almacen) ? $almacen['id_almacen'] : 0;

    // Verifica si existe el almacen
    if ($id_almacen!=0):
        $Sentencia="SELECT  p.id_producto,p.codigo,p.nombre,p.nombre_factura,p.descripcion,p.promocion,p.cantidad_minima,p.precio_actual,
                            IFNULL(e.cantidad_ingresos,0)AS cantidad_ingresos,IFNULL(s.cantidad_egresos,0)AS cantidad_egresos,u.unidad,u.sigla,c.categoria
                    FROM inv_productos p
                    LEFT JOIN(
                        SELECT d.producto_id,SUM(d.cantidad)AS cantidad_ingresos
                        FROM inv_ingresos_detalles d
                        LEFT JOIN inv_ingresos i ON i.id_ingreso=d.ingreso_id
                        WHERE i.almacen_id={$id_almacen}
                        GROUP BY d.producto_id
                    )AS e on e.producto_id = p.id_producto
                    LEFT JOIN(
                        SELECT d.producto_id,SUM(d.cantidad)AS cantidad_egresos
                        FROM inv_egresos_detalles d
                        LEFT JOIN inv_egresos e ON e.id_egreso=d.egreso_id
                        WHERE e.almacen_id={$id_almacen}
                        GROUP BY d.producto_id
                    )AS s ON s.producto_id=p.id_producto
                    LEFT JOIN inv_unidades u ON u.id_unidad=p.unidad_id
                    LEFT JOIN inv_categorias c ON c.id_categoria=p.categoria_id";
    endif;

    //FILTRO GENERAL
    $Sentencia.=" WHERE";
    if(!empty($ValorG)):
        $Sentencia.=" (p.id_producto LIKE '%{$ValorG}%' OR
                    p.codigo LIKE '%{$ValorG}%' OR
                    p.nombre LIKE '%{$ValorG}%' OR
                    p.nombre_factura LIKE '%{$ValorG}%' OR
                    p.descripcion LIKE '%{$ValorG}%' OR
                    c.categoria LIKE '%{$ValorG}%')";
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
        $nestedData=[];
        $nestedData[]=$requestData['start']+$key+1;
        $nestedData[]=escape($Dato['codigo']);
        $nestedData[]=escape($Dato['nombre_factura']);//text-nowrap
        $nestedData[]=escape($Dato['descripcion']);//text-nowrap
        $nestedData[]=escape($Dato['categoria']);//text-nowrap
        $nestedData[]=escape($Dato['cantidad_ingresos']-$Dato['cantidad_egresos']);//text-nowrap text-right
        $precio_r=$db->query("SELECT MAX(i.fecha_ingreso),d.costo FROM inv_ingresos_detalles d LEFT JOIN inv_ingresos i on i.id_ingreso = d.ingreso_id WHERE d.producto_id={$Dato['id_producto']}")->fetch_first();
        $Aux=escape($Dato['precio_actual']);
        $nestedData[]='<span style="display: none" data-precio="'.$Dato['id_producto'].'">'.$precio_r['costo'].'</span>'.$Aux;//text-nowrap text-right          ///data-precio=$Dato['id_producto']
        $nestedData[]="<button type='button' class='btn btn-xs btn-primary' data-comprar='{$Dato['id_producto']}' data-toggle='tooltip' data-title='Comprar'>
                        <span class='glyphicon glyphicon-share-alt'></span>
                    </button>";//text-nowrap
        $nestedData[]=escape($Dato['id_producto']);
        $data[]=$nestedData;
    endforeach;

    $json_data=[
        'draw'           =>intval($requestData['draw']),
        'recordsTotal'   =>intval($totalData),
        'recordsFiltered'=>intval($totalFiltered),
        'data'           =>$data
    ];

    echo json_encode($json_data);