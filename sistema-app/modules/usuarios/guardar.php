<?php


// Verifica si es una peticion post
if (is_post()) {
	// Verifica la existencia de los datos enviados
	if (isset($_POST['id_user']) && isset($_POST['username']) && isset($_POST['password']) && isset($_POST['email']) && isset($_POST['rol_id']) && isset($_POST['active']) /*&& isset($_POST['almacen_id'])*/) {
		// Obtiene los datos del user
		$id_user = trim($_POST['id_user']);
		$username = trim($_POST['username']);
		$password = $_POST['password'];
		$email = trim($_POST['email']);
		$active = trim($_POST['active']);
		$rol_id = trim($_POST['rol_id']);
		$id_almacenes 	= (isset($_POST['id_almacen'])) ? $_POST['id_almacen'] : array();
		
		// Verifica si es creacion o modificacion
		if ($id_user > 0) {
			// Verifica si existe la contraseña
			if ($password == '') {
				// Instancia el user
				$user = array(
					'username' => trim($username),
					'email' => trim($email),
					'active' => trim($active),
					'rol_id' => trim($rol_id)
				);
			} else {
				// Instancia el user
				$user = array(
					'username' => trim($username),
					'password' => sha1(prefix . md5($password)),
					'email' => trim($email),
					'active' => trim($active),
					'rol_id' => trim($rol_id)
				);
			}

			// Genera la condicion
			$condicion = array('id_user' => $id_user);

			// Actualiza la informacion
			$db->where($condicion)->update('sys_users', $user);
			
			// Guarda en la tabla inv_users_almacenes
            
			$db->delete()->from('inv_users_almacenes')->where('user_id', $id_user)->execute();
			foreach($id_almacenes as $nro => $id_almacen){
    			$users_almacenes = array(
    				'almacen_id' => $id_almacenes[$nro],
    				'user_id' => $id_user
    			);
    			$id_user_almacen = $db->insert('inv_users_almacenes', $users_almacenes);
			}
			
			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'u',
				'nivel' => 'l',
				'direccion' => '?/usuarios/guardar',
				'detalle' => 'Se actualizo usuario con identificador número ' . $id_user ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);			
			$db->insert('sys_procesos', $data) ; 

			// Define la variable para mostrar los cambios
			$_SESSION[temporary] = array(
				'alert' => 'success',
				'title' => 'Actualización satisfactoria!',
				'message' => 'El registro se actualizó correctamente.'
			);
		} else {
			// Instancia el user
			$user = array(
				'username' => trim($username),
				'password' => sha1(prefix . md5($password)),
				'email' => trim($email),
				'avatar' => '',
				'active' => trim($active),
				'login_at' => '0000-00-00 00:00:00',
				'logout_at' => '0000-00-00 00:00:00',
				'rol_id' => trim($rol_id),
				'persona_id' => 0
			);

			// Guarda la informacion
			$id_user = $db->insert('sys_users', $user);
			
			// Guarda en la tabla inv_users_almacenes
			foreach($id_almacenes as $nro => $id_almacen){
    			$users_almacenes = array(
    				'almacen_id' => $id_almacenes[$nro],
    				'user_id' => $id_user
    			);
    			$id_user_almacen = $db->insert('inv_users_almacenes', $users_almacenes);
			}
			
			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/usuarios/guardar',
				'detalle' => 'Se creó usuario con identificador numero ' . $id_user ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);
			
			$db->insert('sys_procesos', $data) ; 

			// Define la variable para mostrar los cambios
			$_SESSION[temporary] = array(
				'alert' => 'success',
				'title' => 'Adición satisfactoria!',
				'message' => 'El registro se guardó correctamente.'
			);
		}

		// Redirecciona a la pagina principal
		redirect('?/usuarios/listar');
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