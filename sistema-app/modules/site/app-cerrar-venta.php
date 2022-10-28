<?php

/**
 * FunctionPHP - Framework Functional PHP
 *
 * @package  FunctionPHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Define las cabeceras
header('Content-Type: application/json');

// Verifica la peticion post
if (is_post()) {
    // Verifica la existencia de datos
    if (isset($_POST['id_user'])) {
        // Importa la configuracion para el manejo de la base de datos
        require config . '/database.php';

        // Obtiene los usuarios que cumplen la condicion
        $id_user = $_POST['id_user'];

        // Obtiene el user
        $user = $db->from('sys_users')->where('id_user', $id_user)->fetch_first();

// Verifica si el user existe
        if ($user) {
            $empleado = $db->from('sys_empleados')->where('id_empleado',$user['persona_id'])->fetch_first();
            // Obtiene el nuevo estado
            if($empleado){
                $fecha_cierre = date("Y-m-d");
                $hora_cierre = date('H:i:s');

                // Instancia el user
                $user = array(
                    'fecha' => $fecha_cierre,
                    'hora' => $hora_cierre
                );
                // Genera la condicion
                $condicion = array('id_empleado' => $empleado['id_empleado']);

                // Actualiza la informacion
                $idg = $db->where($condicion)->update('sys_empleados', $user);
                // Instancia el objeto
                $respuesta = array(
                    'estado' => 'v',
                    'cliente' => $idg
                );

                // Devuelve los resultados
                echo json_encode($respuesta);
                // Redirecciona a la pagina principal
            }else{
                echo json_encode(array('estado' => 'no se encuentra a el empleado'));
            }

        } else {
            echo json_encode(array('estado' => 'no llega el id usuario'));
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