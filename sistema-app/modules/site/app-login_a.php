<?php
if(is_post()) {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        require config . '/database.php';

        $usuario = trim($_POST['username']);
        $password = trim($_POST['password']);

        $contrasenia = sha1(prefix . md5($password));

        $usuario = $db->query("select id_user,a.persona_id, a.almacen_id, a.avatar, b.nombres, b.paterno, b.materno, b.genero, b.telefono, b.fecha, a.rol_id from sys_users a LEFT JOIN sys_empleados b ON a.persona_id = b.id_empleado where (md5(a.username) = md5('$usuario') or md5(a.email) = md5('$usuario')) and a.password = '$contrasenia'  and a.active = '1' limit 1")->fetch_first();

        if ($usuario) {
            if ($usuario['fecha'] != date('Y-m-d')) {

            $usuario['avatar'] = ($usuario['avatar'] == '') ? imgs2 . '/avatar.jpg' : url1 . profiles2 . '/' . $usuario['avatar'];
            // $usuario['avatar'] = ($usuario['avatar'] == '') ? imgs . '/avatar.jpg' : url1 . profiles . '/' . $usuario['avatar'];

            $usuario['id_user'] = (int)$usuario['id_user'];
            $emp = $usuario['persona_id'];

            if($usuario['rol_id'] == 4){

                $dis=$db->query('SELECT b.fecha_egreso, b.monto_total, b.observacion as estadod, c.*, c.ubicacion AS latitud, c.ubicacion AS longitud, b.fecha_egreso, b.estadoe, b.empleado_id FROM gps_asigna_distribucion a
                LEFT JOIN gps_rutas d ON a.ruta_id = d.id_ruta
                LEFT JOIN sys_empleados e ON d.empleado_id = e.id_empleado
                LEFT JOIN inv_egresos b ON d.id_ruta = b.ruta_id
                LEFT JOIN inv_clientes c ON b.cliente_id = c.id_cliente
                WHERE a.distribuidor_id = '.$emp.' AND a.estado = 1 AND b.grupo="" AND b.estadoe > 1 AND (b.fecha_egreso < CURDATE() OR b.fecha_egreso <= e.fecha) GROUP BY b.cliente_id')->fetch();

                $dis1 = $db->query('SELECT b.fecha_egreso, b.monto_total, b.observacion AS estadod, c.*, c.ubicacion AS latitud, c.ubicacion AS longitud, b.fecha_egreso, b.estadoe, b.empleado_id FROM gps_asigna_distribucion a
                LEFT JOIN gps_rutas d ON a.ruta_id = d.id_ruta
                LEFT JOIN tmp_egresos b ON d.id_ruta = b.ruta_id
                LEFT JOIN inv_clientes c ON b.cliente_id = c.id_cliente
                WHERE a.distribuidor_id = '. $emp .' AND a.estado = 1 AND b.grupo="" AND b.estadoe > 1 AND b.distribuidor_estado = "NO ENTREGA" AND b.estado = 3 GROUP BY b.cliente_id ORDER BY b.estadoe DESC')->fetch();

                $dis = array_merge ($dis, $dis1);
                
                $aux = array();
                foreach ($dis as $nro => $di) {
                    if($usuario['fecha'] >= $di['fecha_egreso'] && $di['estadoe'] == 3){

                    }else{
                        array_push($aux,$dis[$nro]);
                    }
                }

                if(!empty($aux)){
                    $usuario['ruta'] = '';
                    $usuario['estado_precio'] = 0;
                    $respuesta = array(
                        'estado' => 's',
                        'vendedor' => $usuario
                    );
                }else{
                    $respuesta = array('estado' => 'no tiene clientes que repartir');
                }
            }else{
                $dia = date('w');
                $area = $db->select('*')->from('sys_users a')->join('sys_empleados b', 'a.persona_id = b.id_empleado')->join('gps_rutas c','b.id_empleado = c.empleado_id')->where('a.id_user',$usuario['id_user'])->where('c.dia',$dia)->fetch_first();
                if($area){
                    $usuario['ruta'] = $area['nombre'];
                    if($usuario['rol_id'] > 2){
                        $usuario['estado_precio'] = 0;
                    }else{
                        $usuario['estado_precio'] = 1;
                    }
                    $respuesta = array(
                        'estado' => 's',
                        'vendedor' => $usuario
                    );
                }else{
                    $usuario['ruta'] = '';
                    $respuesta = array(
                        'estado' => 'sr',
                        'vendedor' => $usuario
                    );
                }
            }
            echo json_encode($respuesta);
            }else{
                echo json_encode(array('estado' => 'sv'));
            }
        }else{
        echo json_encode(array('estado' => 'password incorrecto'));
        }
        
    } else {
        echo json_encode(array('estado' => 'uno de los datos fallo'));
    }
}else{
    echo json_encode(array('estado' => 'no llego los datos'));
}
?>