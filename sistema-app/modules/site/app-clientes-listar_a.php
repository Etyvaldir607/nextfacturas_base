<?php

/**
 * FunctionPHP - Framework Functional PHP
 *
 * @package  FunctionPHP
 * @author   Fabio Choque
 */

// Define las cabeceras
header('Content-Type: application/json');

// Verifica la peticion post
if (is_post()) {
    // Verifica la existencia de datos
    if (isset($_POST['id_user'])) {
        
        // Importa la configuracion para el manejo de la base de datos
        require config . '/database.php';
        require config . '/poligono.php';
        
        //$busqueda = $_POST['nombre'];

        $id_user = $_POST['id_user'];
        $dia = date('w');

        $area = $db->select('*')->from('sys_users a')->join('sys_empleados b', 'a.persona_id = b.id_empleado')->join('gps_rutas c','b.id_empleado = c.empleado_id')->where('a.id_user',$id_user)->where('c.dia',$dia)->fetch_first();

        // Obtiene los usuarios que cumplen la condicion
        $usuario = $db->from('sys_users')->join('sys_empleados','persona_id = id_empleado')->where('id_user',$_POST['id_user'])->fetch_first();
        
        $emp = $usuario['id_empleado'];
        
        $fecha_actual = "".date('Y-m-d')."";
        //buscar si es vendedor o distribuidor
        if($usuario['rol_id'] == 4){
            $dis=$db->query('SELECT  b.monto_total, e.nombres, e.paterno, b.observacion as estadod, c.id_cliente, c.cliente, c.nombre_factura, c.nit, c.telefono, c.direccion, c.descripcion, c.imagen, c.tipo, c.ubicacion AS latitud, c.ubicacion AS longitud,  b.estadoe, b.plan_de_pagos FROM gps_asigna_distribucion a
                LEFT JOIN gps_rutas d ON a.ruta_id = d.id_ruta
                LEFT JOIN sys_empleados e ON d.empleado_id = e.id_empleado
                LEFT JOIN inv_egresos b ON d.id_ruta = b.ruta_id
                LEFT JOIN inv_clientes c ON b.cliente_id = c.id_cliente
                WHERE a.distribuidor_id = '.$emp.' AND a.estado = 1 AND b.grupo="" AND b.estadoe > 1 AND b.estadoe < 3 AND (b.fecha_egreso < CURDATE() OR b.fecha_egreso <= e.fecha) GROUP BY b.cliente_id')->fetch();

            $dis1 = $db->query('SELECT  b.monto_total, e.nombres, e.paterno, b.observacion AS estadod, c.id_cliente, c.cliente, c.nombre_factura, c.nit, c.telefono, c.direccion, c.descripcion, c.imagen, c.tipo, c.ubicacion AS latitud, c.ubicacion AS longitud,  IF(b.distribuidor_estado = "ENTREGA","3",b.estadoe) as estadoe, b.plan_de_pagos FROM gps_asigna_distribucion a
                LEFT JOIN gps_rutas d ON a.ruta_id = d.id_ruta
                LEFT JOIN sys_empleados e ON d.empleado_id = e.id_empleado
                LEFT JOIN tmp_egresos b ON d.id_ruta = b.ruta_id
                LEFT JOIN inv_clientes c ON b.cliente_id = c.id_cliente
                WHERE a.distribuidor_id = '. $emp .' AND a.estado = 1 AND b.grupo="" AND b.estadoe > 1 AND (b.distribuidor_estado = "NO ENTREGA" OR b.distribuidor_estado = "ENTREGA") AND b.estado = 3 GROUP BY b.cliente_id ORDER BY b.estadoe DESC')->fetch();

            $dis = array_merge ($dis, $dis1);

            foreach ($dis as $nro => $di) {
                if($di['plan_de_pagos']=='si'){
                    $deuda = $db->select('*')->from('inv_egresos a')
                        ->join('inv_pagos b','b.movimiento_id = a.id_egreso')
                        ->join('inv_pagos_detalles c','c.pago_id = b.id_pago')
                        ->where('c.estado',0)->where('a.plan_de_pagos','si')->where('a.cliente_id',$di['cliente_id'])->fetch_first();
                    if(empty($deuda)){
                        $dis[$nro]['estadoe'] = $di['estadoe'] + 3;
                    }
                }
                $dis[$nro]['imagen'] = ($di['imagen'] == '') ? url1 . imgs2 . '/image.jpg' : url1 . tiendas . '/' . $di['imagen'];
                $a = explode(',',$di['latitud']);
                $dis[$nro]['latitud'] = (float)$a[0];
                $dis[$nro]['longitud'] = (float)$a[1];
            }
            $aux = array();
            foreach ($dis as $nro => $di) {
                if(false){

                }else{
                    array_push($aux,$di);
                }
            }
            $respuesta = array(
                'estado' => 'd',
                'cliente' => $aux
            );
            echo json_encode($respuesta);
        }else{
            $id_almacen = $usuario['almacen_id'];
            // Obtiene los productos
            $fech = date('Y-m-d');
            $clientes = $db->query("SELECT id_cliente, cliente, nombre_factura, nit, telefono, direccion, descripcion, imagen, ubicacion, ubicacion as latitud, ubicacion as longitud, ubicacion as area, e.estadoe, e.estadoe as estadod 
            FROM inv_clientes a 
            LEFT JOIN ( 
                SELECT b.cliente_id, b.estadoe 
                FROM inv_egresos b 
                WHERE b.fecha_egreso = '$fech' ) AS e ON a.id_cliente = e.cliente_id WHERE a.empleado_id = '$emp' GROUP BY a.id_cliente")->fetch();
                //WHERE b.fecha_egreso = '$fech' ) AS e ON a.id_cliente = e.cliente_id WHERE a.empleado_id = '$emp' AND a.cliente like '%" . $busqueda . "%' OR a.nombre_factura like '%" . $busqueda . "%' OR a.nit like '%" . $busqueda . "%' OR a.codigo like '%" . $busqueda . "%' OR a.id_cliente like '%" . $busqueda . "%' GROUP BY a.id_cliente")->fetch();

            $clientes2 = array();
            // Reformula los productos
            foreach ($clientes as $nro => $cliente) {
                $a = explode(',',$cliente['ubicacion']);
                    $deuda = $db->select('*')->from('inv_egresos a')
                            ->join('inv_pagos b','b.movimiento_id = a.id_egreso')
                            ->join('inv_pagos_detalles c','c.pago_id = b.id_pago')
                            ->where('c.estado',0)->where('a.plan_de_pagos','si')->where('a.cliente_id',$cliente['id_cliente'])->fetch_first();
                    if($deuda['plan_de_pagos'] == 'si'){
                        if($deuda['id_egreso'] > 0){
                            if($cliente['estadoe'] == 0){
                                $cliente['estadoe'] = 5;
                            }
                            if($cliente['estadoe'] == 2){
                                $cliente['estadoe'] = 6;
                            }
                            if($cliente['estadoe'] == 3){
                                $cliente['estadoe'] = 6;
                            }
                            if($cliente['estadoe'] == 1){
                                $cliente['estadoe'] = 7;
                            }
                            if(!$cliente['estadoe']){ 
                                $cliente['estadoe'] = 5; 
                            }
                        }
                    }else{
                        if(!$cliente['estadoe']){$cliente['estadoe'] = 0;}
                    }
                    $cliente['latitud'] = (float)$a[0];
                    $cliente['longitud'] = (float)$a[1];
                    $cliente['nombres'] = '';
                    $cliente['paterno'] = '';
                    $cliente['imagen'] = ($cliente['imagen'] == '') ? url1 . imgs2 . '/image.jpg' : url1 . tiendas . '/' . $cliente['imagen'];
                    array_push($clientes2, $cliente);
            }

            // Instancia el objeto
            $respuesta = array(
                'estado' => 'v',
                'cliente' => $clientes2
            );
            // Devuelve los resultados
            echo json_encode($respuesta);
        }
    } else {
        // Devuelve los resultados
        echo json_encode(array('estado' => 'no llega el id usuario'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'no llega ningun dato'));
}


?>