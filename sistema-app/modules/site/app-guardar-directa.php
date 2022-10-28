<?php
/**
 * SimplePHP - Simple Framework PHP
 *
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Define las cabeceras
header('Content-Type: application/json');

// Verifica si es una peticion post
if (is_post()) 
{
	// Verifica la existencia de los datos enviados
	
    if (isset($_POST['id_user']) && isset($_POST['cantidad']) && isset($_POST['id_unidad']) && isset($_POST['id_producto']) && isset($_POST['precio']) && isset($_POST['monto_total']))
    { 
        require config . '/database.php';

        $id_user        = trim($_POST['id_user']);
        $cantidad       = trim($_POST['cantidad']);
        $unidad         = trim($_POST['id_unidad']);
        $producto       = trim($_POST['id_producto']);
        $precio         = trim($_POST['precio']);
        $total         = trim($_POST['monto_total']);

        $usuario = $db->query("select * from sys_users LEFT JOIN sys_empleados ON persona_id = id_empleado where id_user = '$id_user' and active = '1' limit 1")->fetch_first();

        $nro_factura = $db->query("select count(id_egreso) + 1 as nro_factura from inv_egresos where tipo = 'Venta' and provisionado = 'S'")->fetch_first();
        $nro_factura = $nro_factura['nro_factura'];


        if($_POST['cantidad'] > 0){
            $nota = array(
                'fecha_egreso' => date('Y-m-d'),
                'hora_egreso' => date('H:i:s'),
                'tipo' => 'Venta',
                'provisionado' => 'S',
                'descripcion' => 'Venta de productos con productos del distribuidor',
                'nro_factura' => $nro_factura,
                'nro_autorizacion' => '',
                'codigo_control' => '',
                'fecha_limite' => '0000-00-00',
                'monto_total' => $total,
                'descuento_porcentaje' => 0,
                'descuento_bs' => 0,
                'monto_total_descuento' => 0,
                'nit_ci' => 0,
                'nombre_cliente' => strtoupper($usuario['nombres']),
                'nro_registros' => 1,
                'dosificacion_id' => 0,
                'almacen_id' => 1,
                'cobrar' => '',
                'observacion' => '',
                'empleado_id' => $usuario['id_empleado']
            );
            // Guarda la informacion
            $egreso_id = $db->insert('inv_egresos', $nota);

            $nota['distribuidor_fecha'] = date('Y-m-d');
            $nota['id_egreso'] = $egreso_id;
            $nota['distribuidor_hora'] = date('H:i:s');
            $nota['distribuidor_estado'] = 'VENTA';
            $nota['distribuidor_id'] = $usuario['id_empleado'];
            $nota['estado'] = 3;
            $id = $db->insert('tmp_egresos', $nota);

            $detalle = array(
                'cantidad' => $cantidad,
                'precio' => $precio,
                'descuento' => 0,
                'unidad_id' => $unidad,
                'producto_id' => $producto,
                'egreso_id' => $egreso_id,
                'promocion_id' => 0
            );
            // Guarda la informacion
            $ide = $db->insert('inv_egresos_detalles', $detalle);

            $detalle['tmp_egreso_id'] = $id;
            $detalle['id_detalle'] = $ide;
            $db->insert('tmp_egresos_detalles', $detalle);

            $respuesta = array(
                'estado' => 's'
            );
            echo json_encode($respuesta);
        }else{
            // Instancia el objeto
            $respuesta = array(
                'estado' => 'la cantidad debe ser mayor a cero'
            );
            // Devuelve los resultados
            echo json_encode($respuesta);
        }
    } else {
        // Instancia el objeto
        $respuesta = array(
            'estado' => 'no llego algun dato'
        );

        // Devuelve los resultados
        echo json_encode($respuesta);
	}
} else {
    echo json_encode(array('estado' => 'no llega ningun dato'));
}
?>