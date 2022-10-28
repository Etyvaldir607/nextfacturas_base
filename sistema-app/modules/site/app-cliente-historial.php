<?php

/**
 * FunctionPHP - Framework Functional PHP
 *
 * @package  FunctionPHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Define las cabeceras
header('Content-Type: application/json');

// Verifica la peticion post
if (is_post()) {
    // Verifica la existencia de datos
    if (isset($_POST['id_cliente'])) {
        // Importa la configuracion para el manejo de la base de datos
        require config . '/database.php';

        $id_cliente = $_POST['id_cliente'];

        // Obtiene las compras del cliente
        $compras = $db->query("SELECT e.id_egreso, e.fecha_egreso, e.hora_egreso, e.nro_factura, e.plan_de_pagos, ed.cantidad, (ed.cantidad * ed.precio) as total, p.nombre_factura as producto, a.almacen
                                    FROM inv_egresos e, inv_egresos_detalles ed, inv_productos p, inv_almacenes a
                                    WHERE ed.egreso_id = e.id_egreso
                                    AND ed.producto_id = p.id_producto
                                    AND e.almacen_id = a.id_almacen
                                    
                                    AND e.estadoe = 3
                                    AND e.cliente_id = '$id_cliente'
                                    ORDER BY e.fecha_egreso DESC")->fetch();
                                    
                                    // AND e.tipo = 'Venta'
        //verifica si hay compras
        if($compras){
            // Instancia el objeto
            $respuesta = array(
                'estado' => 's',
                'compras' => $compras
            );
            // Devuelve los resultados
            echo json_encode($respuesta);
        }else{
            // Devuelve los resultados
            echo json_encode(array('estado' => 'no existe compras hechas'));
        }
    } else {
        // Devuelve los resultados
        echo json_encode(array('estado' => 'n'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'n'));
}

?>