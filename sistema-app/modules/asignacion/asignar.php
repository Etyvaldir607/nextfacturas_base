<?php

/**
 * FunctionPHP - Framework Functional PHP
 * 
 * @package  FunctionPHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */
var_dump($_POST);
// Verifica la peticion post
if (is_post()) {
	// Verifica la cadena csrf
	if (isset($_POST['unidad_id'])) {
		// Obtiene los parametros
		$ruta_id = (isset($params[0])) ? $params[0] : 0;

		// Obtiene el producto
		$ruta = $db->select('id_ruta, empleado_id')->from('gps_rutas')->where('id_ruta', $ruta_id)->fetch_first();

		// Verifica si existen los productos
		if ($ruta) {
			// Verifica la existencia de datos
			if ($ruta['empleado_id']!=0) {

                // actualiza la ruta
                $empleado_id = $_POST['unidad_id'];
                $asigna = array(
                    'empleado_id' => $empleado_id
                );
                $db->where('id_ruta',$ruta_id)->update('gps_rutas', $asigna);
                // Guarda Historial
    			$data = array(
    				'fecha_proceso' => date("Y-m-d"),
    				'hora_proceso' => date("H:i:s"),
    				'proceso' => 'u',
    				'nivel' => 'l',
    				'direccion' => '?/control/asignar',
    				'detalle' => 'Se actualizo rutas de empleado con identificador número ' . $empleado_id ,
    				'usuario_id' => $_SESSION[user]['id_user']
    			);
    			$db->insert('sys_procesos', $data) ;

                //registra el final y inicio de asignacion de rutas
                $hist1 = $db->select('*')->from('gps_historial_asinacion')->where(array('ruta_id' => $ruta_id, 'empleado_id' => $empleado_id))->order_by('id_historial desc')->fetch_first();
                $hist1_id = $hist1['id_historial'];
                $fin = array(
                    'fecha_fin' => date('Y-m-d')
                );
                $db->where('id_historial',$hist1_id)->update('gps_historial_asinacion',$fin);
                // Guarda Historial
    			$data = array(
    				'fecha_proceso' => date("Y-m-d"),
    				'hora_proceso' => date("H:i:s"), 
    				'proceso' => 'u',
    				'nivel' => 'l',
    				'direccion' => '?/control/asignar',
    				'detalle' => 'Se actualizo historial de asignacion con identificador número ' . $hist1_id ,
    				'usuario_id' => $_SESSION[user]['id_user']
    			);
    			$db->insert('sys_procesos', $data) ;

                $ini = array(
                    'ruta_id' => $ruta_id,
                    'empleado_id' => $empleado_id,
                    'fecha_ini' => date('Y-m-d')
                );
                $id = $db->insert('gps_historial_asinacion', $ini);
                    // Guarda Historial
        			$data = array(
        				'fecha_proceso' => date("Y-m-d"),
        				'hora_proceso' => date("H:i:s"), 
        				'proceso' => 'c',
        				'nivel' => 'l',
        				'direccion' => '?/control/asignar',
        				'detalle' => 'Se creó historial asignacion con identificador número ' . $id ,
        				'usuario_id' => $_SESSION[user]['id_user']			
        			);
        			$db->insert('sys_procesos', $data) ; 
                
                    // Crea el precio
                    $estado_precio = true;
                    $estado_asignacion = true;
            }else {
                // actualiza la ruta
                $empleado_id = $_POST['unidad_id'];
                $asigna = array(
                    'empleado_id' => $empleado_id
                );
                $db->where('id_ruta',$ruta_id)->update('gps_rutas', $asigna);
                // Guarda Historial
    			$data = array(
    				'fecha_proceso' => date("Y-m-d"),
    				'hora_proceso' => date("H:i:s"), 
    				'proceso' => 'c',
    				'nivel' => 'l',
    				'direccion' => '?/control/asignar',
    				'detalle' => 'Se creó ruta con identificador número ' . $ruta_id ,
    				'usuario_id' => $_SESSION[user]['id_user']			
    			);
    			$db->insert('sys_procesos', $data) ; 

                //registra el final y inicio de asignacion de rutas
                $ini = array(
                    'ruta_id' => $ruta_id,
                    'empleado_id' => $empleado_id,
                    'fecha_ini' => date('Y-m-d')
                );
                $id = $db->insert('gps_historial_asinacion', $ini);
                // Guarda Historial
    			$data = array(
    				'fecha_proceso' => date("Y-m-d"),
    				'hora_proceso' => date("H:i:s"), 
    				'proceso' => 'c',
    				'nivel' => 'l',
    				'direccion' => '?/control/asignar',
    				'detalle' => 'Se creó historial asignacion con identificador número ' . $id ,
    				'usuario_id' => $_SESSION[user]['id_user']			
    			);
    			$db->insert('sys_procesos', $data) ; 
                // Crea el precio
                $estado_precio = true;
                $estado_asignacion = true;
            }
				// Verifica los estados
				if ($estado_asignacion && $estado_precio) {
					// Crea la notificacion
					set_notification('success', 'Asignación exitosa!', 'La ruta se fijó satisfactoriamente.');
                    // Redirecciona la pagina
                    redirect('?/control/listar');
				} else {
					if ($estado_asignacion) {
						// Crea la notificacion
						set_notification('success', 'Asignación exitosa!', 'La ruta se asignó satisfactoriamente.');
                        // Redirecciona la pagina
                        redirect('?/control/listar');
					} else {
						if ($estado_precio) {
							// Crea la notificacion
							set_notification('success', 'Asignación exitosa!', 'La ruta se fijó satisfactoriamente.');
                            // Redirecciona la pagina
                            redirect('?/control/listar');
						} else {
							// Crea la notificacion
							set_notification('danger', 'Asignación fallida!', 'Los cambios no fueron registrados.');
                            // Redirecciona la pagina
                            redirect('?/control/listar');
						}
					}
				}



		} else {
			// Error 400
			require_once bad_request();
			exit;
		}
	} else {
		// Redirecciona la pagina
		redirect(back());
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>
<select name="" id="op">
    <option value=""></option></select>