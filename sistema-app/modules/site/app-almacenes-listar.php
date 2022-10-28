<?php
// Define las cabeceras
header('Content-Type: application/json');

// Verifica la peticion post
if (is_post()) {
    // Verifica la existencia de datos
    if (isset($_POST['id_user'])) {
        // Importa la configuracion para el manejo de la base de datos
        require config . '/database.php';

        // Obtiene los usuarios que cumplen la condicion
        $almacenes = $db->select('a.id_almacen, a.almacen, a.direccion')
                        ->from('inv_users_almacenes')
                        ->join('inv_almacenes a','id_almacen=almacen_id','left')
                        ->where('user_id',$_POST['id_user'])
                        ->fetch();

        // Verifica la existencia del usuario
        if ($almacenes) {
            // Instancia el objeto
            $respuesta = array(
                'estado' => 's',
                'categorias' => $almacenes
            );
            // Devuelve los resultados
            echo json_encode($respuesta);
        } else {
            // Devuelve los resultados
            echo json_encode(array('estado' => 'no existen almacenes'));
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