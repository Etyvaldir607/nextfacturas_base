<?php
$egreso_id = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene la venta
$venta = $db->select('i.*')
            ->from('inv_egresos i')
            ->where('id_egreso', $egreso_id)
            ->fetch_first();

// echo json_encode($_POST); die();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($venta) 
{
	// Obtiene el numero de nota
	



	// $nro_facturax = $db->query("SELECT IFNULL(MAX(nro_nota),0) + 1 as nro_factura 
	 //                                from inv_egresos 
	 //                             	")->fetch_first();
	 //                    			//where tipo = 'Venta' and provisionado = 'S'
	// if($nro_facturax){
	//     $nro_factura = $nro_facturax['nro_factura'];
	 //    }else{
	 //        $nro_factura = 1;
	 //    }
    



	// Guarda en el historial
	// $data = array(
	// 	'fecha_proceso' => date("Y-m-d"),
	// 	'hora_proceso' => date("H:i:s"),
	// 	'proceso' => 'c',
	// 	'nivel' => 'l',
	// 	'direccion' => '?/electronicas/guardar',
	// 	'detalle' => 'Se inserto el inventario egreso con identificador numero ' . $egreso_id,
	// 	'usuario_id' => $_SESSION[user]['id_user']
	// );
	// $db->insert('sys_procesos', $data);

    require_once dirname(__DIR__) . '/siat/siat.php';
    try
    {
		//## generar factura siat
		$egreso = siat_recepcion_factura($egreso_id);
	}
	catch(ExceptionInvalidInvoiceData $e)
	{
		if( $e->egreso )
		{
			//TODO: borrar/revertir egreso
			die($e->getMessage());
			
		}
	}
	catch(Exception $e)
	{
		die($e->getMessage());
	}
	
	// Instancia el objeto
    // $respuesta = array(
    //     'egreso_id' 	=> $egreso_id,
    //     'recibo' 		=> $recibo,
    //     'nro_recibo'	=> $nro_recibo,
    //     'siat_url'		=> siat_factura_url((object)$egreso),
    // );
    
    // $respuesta = array(
    //     'egreso_id' 	=> $egreso_id,
    //     'recibo' 		=> 0,
    //     'nro_recibo'	=> 0,
    //     'siat_url'		=> siat_factura_url((object)$egreso),
    // );
    // // Devuelve los resultados
    // echo json_encode($respuesta);

	//echo '?/electronicas/mostrar/'.$egreso_id;
	redirect('?/electronicas/mostrar/0/0/'.$egreso_id);

	// Envia respuesta
	// 		echo json_encode($egreso_id);
	
} else {
	// Error 404
	require_once not_found();
	exit;
}
