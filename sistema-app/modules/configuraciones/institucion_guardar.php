<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica si es una peticion post
if (is_post()) {
	// Verifica la existencia de los datos enviados
	if (isset($_POST['nombre']) && isset($_POST['lema']) && isset($_POST['razon_social']) && isset($_POST['nit']) && isset($_POST['propietario']) && isset($_POST['direccion']) && isset($_POST['correo']) && isset($_POST['telefono'])) {
		// Obtiene los datos de la institucion
		$id_institucion = trim($_institution['id_institucion']);
		$nombre = trim($_POST['nombre']);
		$lema = trim($_POST['lema']);
		$razon_social = trim($_POST['razon_social']);
		$nit = trim($_POST['nit']);
		$propietario = trim($_POST['propietario']);
		$direccion = trim($_POST['direccion']);
		$correo = trim($_POST['correo']);
        $telefono = trim($_POST['telefono']);
        $empresa1 = trim($_POST['empresa1']);
        $empresa2 = trim($_POST['empresa2']);

		// Instancia la institucion
		$institucion = array(
			'nombre' => $nombre,
			'lema' => $lema,
			'razon_social' => $razon_social,
			'nit' => $nit,
			'propietario' => $propietario,
			'direccion' => $direccion,
			'correo' => $correo,
            'telefono' => $telefono,
            'empresa1' => $empresa1,
            'empresa2' => $empresa2,
		);

		// Actualiza la informacion
		$db->where('id_institucion', $id_institucion)->update('sys_instituciones', $institucion);

		$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'u',
				'nivel' => 'l',
				'direccion' => '?/configuraciones/institucion-guardar',
				'detalle' => 'Se actualizo insitutcion con identificador numero ' . $id_institucion ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);			
		$db->insert('sys_procesos', $data) ; 
		

		// Define el mensaje de exito
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Actualizaci¨®n satisfactoria!',
			'message' => 'El registro se actualizÃ³ correctamente.'
		);

		// Redirecciona a la pagina principal
		redirect('?/configuraciones/institucion');
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