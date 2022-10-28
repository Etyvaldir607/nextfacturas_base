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
if (true) {
    // Verifica la existencia de datos
    if (true) {
        // Importa la configuracion para el manejo de la base de datos
        require config . '/database.php';

        // Obtiene los usuarios que cumplen la condicion
        $tipos = $db->from('inv_prioridades_ventas')->fetch();

        // Verifica la existencia del usuario
        if ($tipos) {
            foreach($tipos as $nro => $tipo){
                $tipos[$nro]['id_prioridad_venta'] = (int)$tipo['prioridad'];
            }

            // Instancia el objeto
            $respuesta = array(
                'estado' => 's',
                'prioridades' => $tipos
            );

            // Devuelve los resultados
            echo json_encode($respuesta);
        } else {
            // Devuelve los resultados
            echo json_encode(array('estado' => 'no existen prioridades'));
        }
    } else {
        // Devuelve los resultados
        echo json_encode(array('estado' => 'n'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'n'));
}

?>