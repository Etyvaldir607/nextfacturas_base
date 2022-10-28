<?php
date_default_timezone_set('America/La_Paz');
if(is_post()) {
    if (isset($_POST['id_cliente']) && isset($_POST['id_user'])) {
        require config . '/database.php';

        //datos del producto
        $db->delete()
           ->from('inv_egresos')
           ->where('cliente_id',$_POST['id_cliente'])
           ->where('fecha_egreso',date('Y-m-d'))
           ->where('tipo',"No venta")
           ->limit(1)
           ->execute();

        
        // if($proforma_id){
            $respuesta = array(
                'estado' => 's',
                'estadoe' => 0
            );
            echo json_encode($respuesta);
        // }else{
        //     echo json_encode(array('estado' => 'no guardo'));
        // }
    } else {
        echo json_encode(array('estado' => 'no llego uno de los datos1'));
    }
}else{
    echo json_encode(array('estado' => 'no llego los datos'));
}
?>