<?php
if (is_post()) {
	if (isset($_POST['id']) && isset($_POST['dia'])) {
        $id = trim($_POST['id']);
        $dia = trim($_POST['dia']);
        
        $cliente = array(
            'dia' => $dia
        );
    
        $db->where('id_cliente',$id)->update('inv_clientes', $cliente);
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