<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */
// var_dump($_POST);
// Verifica si es una peticion post
if (is_post()) {
    
	// Verifica la existencia de los datos enviados
	if (isset($_POST['id_empleado']) && isset($_POST['nombres']) && isset($_POST['paterno']) && isset($_POST['materno']) && isset($_POST['genero']) && isset($_POST['telefono'])) {
		// Obtiene los datos del empleado
	
		$id_empleado = trim($_POST['id_empleado']);
		$nombres = trim($_POST['nombres']);
		$paterno = trim($_POST['paterno']);
		$materno = trim($_POST['materno']);
		$genero = trim($_POST['genero']);
		$fecha_nacimiento = trim($_POST['fecha_nacimiento']!='')?$_POST['fecha_nacimiento']:'0000-00-00';
		$telefono = trim($_POST['telefono']);
		$cargo = trim($_POST['cargo']);
		$departamento = trim($_POST['departamento']);
		$codigo = trim($_POST['codigo']);
		$fecha_ingreso = trim($_POST['fecha_ingreso']!='')?$_POST['fecha_ingreso']:'0000-00-00';
		$ci = trim($_POST['ci']);

		// Instancia el empleado
		$empleado = array(
			'nombres' => $nombres,
			'paterno' => $paterno,
			'materno' => $materno,
			'genero' => $genero,
			'fecha_nacimiento' => date_encode($fecha_nacimiento),
			'telefono' => $telefono,
			'cargo' => 1,
			'departamento_id'=>$departamento,
			'fecha_ingreso' => date_encode($fecha_ingreso),
			'ci' => $ci,
			'codigo'=>$codigo
		);

		// Verifica si es creacion o modificacion
		if ($id_empleado > 0) {
			//$empleado
// 			$Departamento=$db->query("SELECT abreviacion FROM inv_departamentos WHERE id_departamento='{$departamento}'")->fetch_first()['abreviacion'];
// 			$ceros=3-strlen($id_empleado);
// 			for($i=0;$i<$ceros;$i++):
// 				$codigo.='0';
// 			endfor;
// 			$codigo.=$id_empleado.$Departamento;
// 			$empleado=array_merge($empleado,['codigo'=>$codigo]);

			// Genera la condicion
			$condicion = array('id_empleado' => $id_empleado);

			// Actualiza la informacion
			$db->where($condicion)->update('sys_empleados', $empleado);

			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"),
				'proceso' => 'u',
				'nivel' => 'l',
				'direccion' => '?/empleados/guardar',
				'detalle' => 'Se actualizo empleado con identificador numero ' . $id_empleado,
				'usuario_id' => $_SESSION[user]['id_user']
			);
			$db->insert('sys_procesos', $data);

			// Instancia la variable de notificacion
			$_SESSION[temporary] = array(
				'alert' => 'success',
				'title' => 'Actualización satisfactoria!',
				'message' => 'El registro se actualizó correctamente.'
			);
		} else {
			// Guarda la informacion
			$id = $db->insert('sys_empleados', $empleado);





// 			$Departamento=$db->query("SELECT abreviacion FROM inv_departamentos WHERE id_departamento='{$departamento}'")->fetch_first()['abreviacion'];
// 			$ceros=3-strlen($id);
// 			for($i=0;$i<$ceros;$i++):
// 				$codigo.='0';
// 			endfor;
// 			$codigo.=$id.$Departamento;
// 			$empleado=array_merge($empleado,['codigo'=>$codigo]);
			$condicion = array('id_empleado' => $id);
			$db->where($condicion)->update('sys_empleados', $empleado);






			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"),
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/empleados/guardar',
				'detalle' => 'Se creo almacen con identificador numero ' . $id,
				'usuario_id' => $_SESSION[user]['id_user']
			);

			$db->insert('sys_procesos', $data);

			// Instancia la variable de notificacion
			$_SESSION[temporary] = array(
				'alert' => 'success',
				'title' => 'Adición satisfactoria!',
				'message' => 'El registro se guardó correctamente.'
			);
		}

		// Redirecciona a la pagina principal
		redirect('?/empleados/listar');
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
