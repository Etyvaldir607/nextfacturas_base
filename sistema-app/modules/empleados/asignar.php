<?php
// Verifica la peticion post
if (is_post()) {
	// Obtiene los parametros
	$id_empleados = (isset($params[0])) ? $params[0] : 0;

	// Obtiene los datos
	$id_empleados = explode('-', $id_empleados);

	// Obtiene los empleados
	$empleados = $db->select('id_empleado')->from('sys_empleados')->where_in('id_empleado', $id_empleados)->fetch();

	// Verifica si existen los empleados
	if ($empleados) {
		// Verifica la existencia de datos
		if (isset($_POST['horario_id'])) {
			// Obtiene los datos
			$horario_id = $_POST['horario_id'];

			// Recorre todos los ids
			foreach ($id_empleados as  $id_empleado) {
				$Datos=[
						'estado'=>'0',
					];
				$Condicion=[
						'empleado_id'=>$id_empleado,
					];
				$db->where($Condicion)->update('rrhh_asignacion',$Datos);
				for($i=0;$i<count($horario_id);++$i):
					$Datos=[
							'empleado_id'=>$id_empleado,
							'horario_id'=>$horario_id[$i],
						];
					$db->insert('rrhh_asignacion',$Datos);
				endfor;
			}

			// Redirecciona la pagina
			redirect(back());
		} else {
			// Error 400
			require_once bad_request();
			exit;
		}
	} else {
		// Error 400
		require_once bad_request();
		exit;
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}