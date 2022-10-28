<?php

// echo json_encode($_POST);
    if (is_post()) {
        // Verifica la existencia de los datos enviados
        if (isset($_POST['detalle_ingreso_id']) && isset($_POST['almacen']) && isset($_POST['nombre_cliente']) && isset($_POST['nit_ci']) && isset($_POST['tipo']) && isset($_POST['productos']) && isset($_POST['nombres']) && isset($_POST['lotes']) && isset($_POST['vencimiento']) && isset($_POST['cantidades']) && isset($_POST['almacen_id']) &&  isset($_POST['nro_registros']) ) {
            $id_ingreso = trim($_POST['ingreso_id']);
            $detalle_ingreso_id = trim($_POST['detalle_ingreso_id']); // guardamos el id del detalle del ingreso
            $id_egreso = trim($_POST['egreso_id']);
            $id_cliente = trim($_POST['cliente_id']);
            $nit_ci = trim($_POST['nit_ci']);
            $nombre_cliente = trim($_POST['nombre_cliente']);
            $descripcion = trim($_POST['descripcion']);

            $productos = (isset($_POST['productos'])) ? $_POST['productos'] : array();
            $nombres = (isset($_POST['nombres'])) ? $_POST['nombres'] : array();
            $lotes = (isset($_POST['lotes'])) ? $_POST['lotes'] : array();
            $vencimiento = (isset($_POST['vencimiento'])) ? $_POST['vencimiento'] : array();
            $cantidades = (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
            $unidad = (isset($_POST['unidad'])) ? $_POST['unidad'] : array();
            $precios = (isset($_POST['precios'])) ? $_POST['precios'] : array();

            $almacen_id = trim($_POST['almacen_id']);
            $nro_registros = trim($_POST['nro_registros']);
            $monto_total = trim($_POST['monto_total']);


            $movimiento = generarMovimiento($db, $_user['persona_id'], 'RP', $almacen_id);

            //Creamos el egreso
            $egreso = array(
                'fecha_egreso'          => date('Y-m-d'),
                'hora_egreso'           => date('H:i:s'),
                'tipo'                  => 'Devolucion',
                'provisionado'          => 'S',
                'descripcion'           => 'Reposicion: ' . $descripcion,
                'nro_factura'           => '0',
                'nro_autorizacion'      => '',
                'codigo_control'        => '',
                'fecha_limite'          => '0000-00-00',
                'monto_total'           => 0,
                'descuento_porcentaje'  => 0,
                'descuento_bs'          => 0,
                'monto_total_descuento' => 0,
                'cliente_id'            => $id_cliente,
                'nombre_cliente'        => $nombre_cliente,
                'nit_ci'                => $nit_ci,
                'nro_registros'         => $nro_registros,
                'estadoe'               => 0,
                'coordenadas'           => '',
                'observacion'           => '',
                'dosificacion_id'       => 0,
                'almacen_id'            => $almacen_id,
                'almacen_id_s'          => 0,
                'empleado_id'           => $_user['persona_id'],
                'motivo_id'             => 0,
                'duracion'              => '00:00:00',
                'cobrar'                => 'no',
                'grupo'                 => '',
                'estado'                => 1,
                'descripcion_venta'     => 'RESPOSICION',
                'ingreso_id'            => $id_ingreso,
                'nro_movimiento' => $movimiento, // + 1
            );
            // Guarda el egreso
            $id_egreso = $db->insert('inv_egresos', $egreso);
            // Guarda Historial
            $data = array(
                'fecha_proceso' => date("Y-m-d"),
                'hora_proceso' => date("H:i:s"),
                'proceso' => 'c',
                'nivel' => 'l',
                'direccion' => '?/operaciones/preventas_reponer_guardar',
                'detalle' => 'Se creo el Egreso con identificador numero ' . $id_egreso ,
                'usuario_id' => $_SESSION[user]['id_user']
            );
            $db->insert('sys_procesos', $data);

            // Guardamos los detalles del egreso
            foreach($productos as $nro => $producto){
                $unidad2 = $db->select('id_unidad')->from('inv_unidades')->where('unidad',$unidad[$nro])->fetch_first();
                $unidad3 = $unidad2['id_unidad'];
                $cantidad = cantidad_unidad($db, $productos[$nro], $unidad3)*$cantidades[$nro];
                $detalle = array(
                    'precio'        => 0,
                    'unidad_id'     => $unidad3,
                    'cantidad'      => $cantidad,
                    'descuento'     => 0,
                    'producto_id'   => $productos[$nro],
                    'egreso_id'     => $id_egreso,
                    'lote'          => explode(': ',$lotes[$nro])[1], // josema::modeificado
                    'vencimiento'   => explode(': ',$vencimiento[$nro])[1], // josema::modeificado
                    'detalle_ingreso_id' => $detalle_ingreso_id
                );
                // Guarda la informacion
                $id_detalle = $db->insert('inv_egresos_detalles', $detalle);

                // Guarda Historial
                $data = array(
                    'fecha_proceso' => date("Y-m-d"),
                    'hora_proceso' => date("H:i:s"),
                    'proceso' => 'c',
                    'nivel' => 'l',
                    'direccion' => '?/operaciones/prevenas_reponer_guardar',
                    'detalle' => 'Se creo Detalle de egreso con identificador numero ' . $id_detalle ,
                    'usuario_id' => $_SESSION[user]['id_user']
                );
                $db->insert('sys_procesos', $data) ;
            }

            $_SESSION[temporary] = array(
                'alert' => 'success',
                'title' => 'Se realizo la devolucion!',
                'message' => 'El registro se realizó correctamente.'
            );
            $_SESSION['imprimir'] = $id_egreso;
            // redirect('?/notas/ver/' . $id_egreso);
            redirect('?/ingresos/ver/'. $id_ingreso);

        }else {
            // Envia respuesta
            echo 'error';
        }
    } else {
        // Error 404
        require_once not_found();
        exit;
    }
?>
