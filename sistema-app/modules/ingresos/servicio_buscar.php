<?php

if (is_post()) {
        
    if(isset($_POST['cadena']) && isset($_POST['id_almacen'])):
        $Cadena=trim($_POST['cadena']);

        $IdAlmacen=trim($_POST['id_almacen']);
        $IdProveedor=trim($_POST['id_proveedor']);
        $VecProveedor=explode("|",$IdProveedor);
        
        $Fecha=date('Y-m-d');

        $sql = "SELECT  CONCAT(p.id_producto, '_', i.id_detalle) AS id_productodetalle, 
                                p.id_producto, z.id_asignacion, z.unidad_id, z.unidade, z.cantidad2, p.imagen,p.nombre_factura,p.codigo,
                                c.id_categoria,c.categoria,(IFNULL(i.ingresos,0))AS total,

            p.precio_actual, (IFNULL(i.costo,0)) AS costo, i.factura_v, u.id_unidad, u.unidad, u.sigla, c.categoria, i.id_detalle
            
            FROM inv_productos AS p

            LEFT JOIN(
                SELECT cid.producto_id,SUM(cid.lote_cantidad)AS ingresos, cid.id_detalle, cid.costo, cid.factura_v
                FROM inv_ingresos_detalles AS cid
                LEFT JOIN inv_ingresos AS ci ON ci.id_ingreso=cid.ingreso_id
                WHERE ci.almacen_id='{$IdAlmacen}'
                GROUP BY cid.producto_id
            )AS i ON i.producto_id = p.id_producto

            left join inv_asignaciones a on a.producto_id = p.id_producto
            LEFT JOIN inv_unidades AS u ON p.unidad_id=u.id_unidad
            LEFT JOIN inv_categorias AS c ON p.categoria_id=c.id_categoria

            LEFT JOIN (
                SELECT w.producto_id, 
                GROUP_CONCAT(w.id_asignacion SEPARATOR '|') AS id_asignacion, 
                GROUP_CONCAT(w.unidad_id SEPARATOR '|') AS unidad_id, 
                GROUP_CONCAT(w.cantidad_unidad,')',w.unidad,':',w.otro_precio SEPARATOR '&') AS unidade, 
                GROUP_CONCAT(w.cantidad_unidad SEPARATOR '*') AS cantidad2
                FROM (
                    SELECT *
                    FROM inv_asignaciones q
                    LEFT JOIN inv_unidades u ON q.unidad_id = u.id_unidad
                    ORDER BY u.unidad DESC
                ) w GROUP BY w.producto_id 
            ) z ON p.id_producto = z.producto_id ";

            /*
            -IFNULL(e.egresos,0)
            
            
            LEFT JOIN(
                SELECT cid.producto_id,SUM(cid.cantidad)AS ingresos, cid.id_detalle, cid.costo, cid.factura_v
                FROM inv_ingresos_detalles AS cid
                LEFT JOIN inv_ingresos AS ci ON ci.id_ingreso=cid.ingreso_id
                WHERE ci.almacen_id='{$IdAlmacen}'
                GROUP BY cid.producto_id
            )AS i ON i.producto_id = p.id_producto

            LEFT JOIN(
                SELECT ced.producto_id,SUM(ced.cantidad)AS egresos
                FROM inv_egresos_detalles AS ced
                LEFT JOIN inv_egresos AS ce ON ce.id_egreso=ced.egreso_id
                WHERE ce.almacen_id='{$IdAlmacen}'
                GROUP BY ced.producto_id
            )AS e ON e.producto_id = p.id_producto
            */

        if ($Cadena != '' && $Cadena != null) {
            $sql .=  " WHERE (p.codigo LIKE '%{$Cadena}%' OR p.nombre LIKE '%{$Cadena}%' OR p.nombre_factura LIKE '%{$Cadena}%') ";
            //$sql .=  " AND (i.ingresos > 0 OR i.ingresos IS NOT NULL OR  e.egresos IS NOT NULL) ";
            //$sql .=  " AND i.ingresos >(e.egresos IS NOT NULL)";
            $sql .=  " AND p.proveedor_id='".$VecProveedor[0]."' AND p.visible='s' ";
        }
        else{
            $sql .=  " WHERE p.proveedor_id='".$VecProveedor[0]."' AND p.visible='s' ";
        }

        $sql .=  "  ORDER BY p.codigo ASC 
                    LIMIT 50";

        $Consulta=$db->query($sql)->fetch();
        echo json_encode($Consulta);
    else:
        require_once not_found();
	    die;
    endif;
}else{
    require_once not_found();
    die;
}