<?php
/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica si es una peticion ajax y post
if (is_post()) {

	// Verifica la existencia de los datos enviados
	if (isset($_POST['unidad_id_asignar']) && isset($_POST['coorde'])) {
		// Importa la libreria para convertir el numero a letra
		require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

		// Obtiene los datos de la proforma
		$motivo = trim($_POST['unidad_id_asignar']);
        $coorde = trim($_POST['coorde']);
        $direccion = trim($_POST['direccion']);
        $nombre = trim($_POST['nombre_cliente2']);
        $nit = trim($_POST['nit_ci2']);
        $observacion = trim($_POST['observacion']);

        //obtiene al cliente
        if(! is_numeric ( $motivo ) ){
            $motivo = $db->insert('gps_noventa_motivos', array('motivo' => $motivo));
            
            // Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/preventas/guardar_noventa',
				'detalle' => 'Se creo motivo con identificador numero ' . $motivo ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);
			$db->insert('sys_procesos', $data) ; 
        }

        $noventa = array(
            'fecha_egreso' => date('Y-m-d'),
            'hora_egreso' => date('H:i:s'),
            'empleado_id' => $_user['persona_id'],
            'coordenadas' => $coorde,
            'nombre_cliente' => $nombre,
            'nit_ci' => $nit,
            'estadoe' => 2,
            'observacion' => $observacion,
            'motivo_id' => $motivo
        );

        $noventa_id = $db->insert('inv_egresos', $noventa);
        
        // Guarda Historial
		$data = array(
			'fecha_proceso' => date("Y-m-d"),
			'hora_proceso' => date("H:i:s"), 
			'proceso' => 'c',
			'nivel' => 'l',
			'direccion' => '?/preventas/guardar_noventa',
			'detalle' => 'Se creo inventario egreso con identificador numero ' . $noventa_id ,
			'usuario_id' => $_SESSION[user]['id_user']			
		);
		$db->insert('sys_procesos', $data) ;

        set_notification('success', 'Registro exitoso!', 'Se registro la no venta.');
        // Redirecciona la pagina
        redirect('?/preventas/crear');

	} else {
		// Envia respuesta
		echo 'error';
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>