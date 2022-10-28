<?php

if(is_post()) {
    if (isset($_POST['id_grupo']) && isset($_POST['id_vendedor'])) {
        require config . '/database.php';

        $id_grupo = trim($_POST['id_grupo']);
        $id_vendedor = trim($_POST['id_vendedor']);

        if ($id_grupo == 0 || $id_vendedor == 0) {
            echo json_encode(array('estado' => 'Verifique los datos, uno de los datos está llegando en 0.'));
            die();
        }
        // Obtenemos la grupo
        $grupo = $db->from('inv_clientes_grupos')->where('id_cliente_grupo', $id_grupo)->fetch_first();

        if ($grupo) {
            $db->where('id_cliente_grupo', $id_grupo)->update('inv_clientes_grupos', array('vendedor_id' => $id_vendedor));
            echo json_encode(array('estado' => 's'));

        } else {
            echo json_encode(array('estado' => 'No existe el grupo, por favor verifique.'));
        }
    } else {
        echo json_encode(array('estado' => 'No llego uno de los datos.'));
    }
} else {
    echo json_encode(array('estado' => 'Verifique el tipo de petición.'));
}

?>