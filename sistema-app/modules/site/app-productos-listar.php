<?php

if(is_post()) {
    if (isset($_POST['pagina']) && isset($_POST['filtro']) && isset($_POST['id_user']) && isset($_POST['tipo_pago']) && isset($_POST['id_almacen']) ) {
        
        require config . '/database.php';
        
        $item = 10;
        $pagina = ($_POST['pagina']);
        $busqueda = $_POST['filtro'];
        $usuario = $db->select('*')
                      ->from('sys_users a')
                      ->join('sys_empleados b','a.persona_id = b.id_empleado')
                      ->where('a.id_user',$_POST['id_user'])
                      ->fetch_first();
        
        if($usuario['fecha'] == date('Y-m-d')){
            
            $id_almacen = $_POST['id_almacen'];
            
            $productos = $db->query("
                                        SELECT p.id_producto, p.asignacion_rol, p.descuento ,p.promocion, p.precio_actual, p.precio_contado, p.precio_mayor, p.cantidad_mayor,
                        						z.id_asignacion, z.unidad_id, z.unidade, z.cantidad2,
                        						p.descripcion,p.imagen,p.codigo,p.nombre_factura as nombre,p.nombre_factura,p.cantidad_minima,
                        						IFNULL(e.cantidad_lote, 0) AS cantidad_ingresos, 
                        						u.unidad, u.sigla, c.categoria, e.vencimiento, e.lote,e.id_detalle, '' as id_detalle_productos, if(e.id_promocion_precio>0, 1, 0)as id_promocion_precio
                        						
                        						FROM inv_productos p
                        						
                        						LEFT JOIN (
                        							SELECT d.producto_id, SUM(d.lote_cantidad) AS cantidad_lote, d.vencimiento, d.lote, d.id_detalle, IFNULL(id_promocion_precio,0)as id_promocion_precio
                        							FROM inv_ingresos_detalles d
                        							LEFT JOIN inv_ingresos i ON i.id_ingreso = d.ingreso_id
                        							left join inv_promocion_precios pp on pp.producto_id=d.producto_id AND pp.lote=d.lote AND pp.vencimiento=d.vencimiento
                                                    WHERE i.almacen_id = '$id_almacen'
                        							GROUP BY d.producto_id, d.vencimiento, d.lote
                        						) AS e ON e.producto_id = p.id_producto
                        						
                        						LEFT JOIN inv_unidades u ON u.id_unidad = p.unidad_id
                        				LEFT JOIN inv_categorias c ON c.id_categoria = p.categoria_id
                        				LEFT JOIN (
                        					SELECT w.producto_id,
                        						GROUP_CONCAT(w.id_asignacion SEPARATOR '|') AS id_asignacion,
                        						GROUP_CONCAT(w.unidad_id SEPARATOR '|') AS unidad_id,
                        						GROUP_CONCAT(w.cantidad_unidad,')',w.unidad,':',w.otro_precio SEPARATOR '&') AS unidade,
                        						GROUP_CONCAT(w.cantidad_unidad SEPARATOR '*') AS cantidad2
                        					FROM (
                        						SELECT q.*,u.*
                        						FROM inv_asignaciones q
                        						LEFT JOIN inv_unidades u ON q.unidad_id = u.id_unidad
                        						ORDER BY u.unidad DESC
                        					) w
                        					GROUP BY w.producto_id
                        				) z ON p.id_producto = z.producto_id 
                        				WHERE p.visible='s' 
                        				
                        				AND p.codigo like '%" . $busqueda . "%' OR p.nombre_factura like '%" . $busqueda . "%' OR p.nombre like '%" . $busqueda . "%'
                        				
                        				ORDER BY nombre_factura, e.vencimiento ASC
            
                                    ")->fetch();
		        
		                            //LIMIT $pagina, ".($pagina+1*10)."
                                    
		    $nroProducts = $db->affected_rows;
			$nroPaginas= ceil($nroProducts / $item);
        
            $nro = 0;
            $nro_real = 0;
            foreach($productos as $nro1 => $producto){
                if( $producto['cantidad_ingresos'] > 0){
                    
                    if( $nro>=(($pagina-1)*$item)  && $nro<($pagina*$item)  ){
                        
                        if($_POST['tipo_pago'] == 'Contado'){
                            $precio=$producto['precio_contado'];
                        }else{
                            $precio=$producto['precio_actual'];
                        }
    
                        $venc=explode("-",$producto['vencimiento']);
                        $producto['vencimiento']=$venc[2]."/".$venc[1]."/".$venc[0];
                            
    
    
                        $datos[$nro_real] = array(
                            'id_producto' => (int)$producto['id_detalle'],
                            'descripcion' => $producto['descripcion'],
                                                //'imagen' => ($producto['imagen'] == '') ? url1 . imgs . '/image.jpg' : url1. productos . '/' . $producto['imagen'],
                            'imagen' => ($producto['imagen'] == '') ? url1 . imgs2 . '/image.jpg' : url1. productos2 . '/' . $producto['imagen'],
                            'codigo' => $producto['codigo'],
                            'nombre' => $producto['nombre'],
                            'promocion' => $producto['id_promocion_precio'],
                            'nombre_factura' => $producto['nombre_factura'],
                            
                            'cantidad_minima' => $producto['cantidad_minima'],
                            'stock' => $producto['cantidad_ingresos'],
                            'lote' => $producto['lote'],
                            'vencimiento' => $producto['vencimiento'],
                            'precios' => array()
                        );
                        
                        array_push($datos[$nro_real]['precios'],array('unidad' => $producto['unidad'],
                                                                 'precio' => $precio,
                                                                 'cantidad' => 1,
                                                                 'precio mayor' => $producto['precio_mayor'],
                                                                 'cantidad_mayor' => $producto['cantidad_mayor'])
                        );
                        $nro_real++;
                    }    
                    $nro++;
                }
            }
            if($productos){
                $respuesta = array(
                    'nro_products' => $nroProducts,
                    'nro_pages' => $nroPaginas,
                    'page' => (int)$_POST['pagina'],
                    'estado' => 's',
                    'producto' => $datos
                );
                echo json_encode($respuesta);
            }else{
                echo json_encode(array('estado' => 'no se encuentran productos'));
            }
        }else{
            echo json_encode(array('estado' => 'Inactivo'));
        }
    } else {
        echo json_encode(array('estado' => 'no llego uno de los datos'));
    }
}else{
    echo json_encode(array('estado' => 'no llego los datos'));
}
?>