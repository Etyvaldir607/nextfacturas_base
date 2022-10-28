<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica si es una peticion post
if (is_post()) {

    //var_dump($_POST);die();

	// Verifica la existencia de los datos enviados
	if (isset($_POST['grupo']) ) {

        // Importa la libreria para subir la imagen
        require_once libraries . '/upload-class/class.upload.php';
        // Obtiene los datos del cliente
        $grupo = trim($_POST['grupo']);
        // $descuento = trim($_POST['descuento']);
        $credito = trim($_POST['credito']);
        $permiso = trim($_POST['estado']);
        $estado = trim($_POST['estado']);
        $vendedor = trim($_POST['vendedor']);

        $datos = array(
            'nombre_grupo' => $grupo,
            // 'descuento_grupo' => $descuento,
            'credito_grupo' => $credito,
            'permiso_grupo' => $permiso,
            'estado_grupo' => $estado,
            'vendedor_id' => $vendedor
        );
        $id = $db->insert('inv_clientes_grupos', $datos);

        // Guardar Historial
        $data = array(
                    'fecha_proceso' => date("Y-m-d"),
                    'hora_proceso' => date("H:i:s"),
                    'proceso' => 'c',
                    'nivel' => 'l',
                    'direccion' => '?/clientes/guardar_grupo',
                    'detalle' => 'Se inserto grupo cliente con identificador numero ' . $id ,
                    'usuario_id' => $_SESSION[user]['id_user']
                );
        $db->insert('sys_procesos', $data) ;

        set_notification('success', 'Acción satisfactoria!', 'El grupo se registro satisfactoriamente.');
		// Redirecciona a la pagina principal
		redirect('?/clientes/crear_grupo');

	} else {
		// Error 401
		require_once bad_request();
		exit;
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>