<?php
// id_producto


// Define las cabeceras
header('Content-Type: application/json');

// echo var_dump($_POST);

    if (is_ajax() && is_post()) {
        if (isset($_POST['id_producto']) && isset($_POST['unidad']) && isset($_POST['cantidad']) && isset($_POST['forma_pago']) ){

            $producto = $db->from('inv_productos')->where('id_producto', $_POST['id_producto'])->fetch_first();

            // echo var_dump($producto);

            $id_unidad = $db->query('SELECT id_unidad FROM inv_unidades WHERE unidad = "'.$_POST['unidad'].'" LIMIT 1 ')->fetch_first();

            // echo var_dump($id_unidad);

            if ($producto['unidad_id'] == $id_unidad['id_unidad']) {

                if ( $_POST['cantidad'] >= $producto['cantidad_mayor'] ) {

                    if ($_POST['forma_pago'] == 1) {
                        $respuesta = array(
                            'cantidad' => $producto['cantidad_mayor'],
                            'precio_mayor' => $producto['precio_mayor']
                        );
                    } else {
                        $respuesta = array(
                            'cantidad' => $producto['cantidad_mayor'],
                            'precio_mayor' => $producto['precio_mayor']
                        );
                    }

                } else {

                    if ($_POST['forma_pago'] == 1) {
                        $respuesta = array(
                            'cantidad' => $producto['cantidad_mayor'],
                            'precio_mayor' => $producto['precio_contado']
                        );
                    } else {
                        $respuesta = array(
                            'cantidad' => $producto['cantidad_mayor'],
                            'precio_mayor' => $producto['precio_actual']
                        );
                    }

                    // $respuesta = array(
                    //     'cantidad' => $producto['cantidad_mayor'],
                    //     'precio_mayor' => $producto['precio_actual']
                    // );
                }

            } else {
                $precio = $db->from('inv_asignaciones')->where('producto_id', $_POST['id_producto'])->where('unidad_id', $id_unidad['id_unidad'])->fetch_first();

                if ( $_POST['cantidad'] >= $precio['cantidad_mayor'] ) {
                    if ($_POST['forma_pago'] == 1) {
                        $respuesta = array(
                            'cantidad' => $precio['cantidad_mayor'],
                            'precio_mayor' => $precio['precio_mayor']
                        );
                    } else {
                        $respuesta = array(
                            'cantidad' => $precio['cantidad_mayor'],
                            'precio_mayor' => $precio['precio_contado']
                        );
                    }

                    // $respuesta = array(
                    //     'cantidad' => $precio['cantidad_mayor'],
                    //     'precio_mayor' => $precio['precio_mayor']
                    // );
                } else {

                    if ($_POST['forma_pago'] == 1) {
                        $respuesta = array(
                            'cantidad' => $precio['cantidad_mayor'],
                            'precio_mayor' => $precio['precio_contado']
                        );
                    } else {
                        $respuesta = array(
                            'cantidad' => $precio['cantidad_mayor'],
                            'precio_mayor' => $precio['otro_precio']
                        );
                    }


                    // $respuesta = array(
                    //     'cantidad' => $precio['cantidad_mayor'],
                    //     'precio_mayor' => $precio['otro_precio']
                    // );
                }


            }

            echo json_encode($respuesta);
        } else {
            // Devuelve los resultados
            echo json_encode(array('estado' => 'No llegaron los datos requeridos'));
        }

    } else {
        // Devuelve los resultados
        echo json_encode(array('estado' => 'Verifique la peticion'));
    }

?>