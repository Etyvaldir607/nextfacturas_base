<?php

// echo json_encode($_POST); die();
// Verifica si es POST
if (is_post()) {
	// Verifica la existencia de los datos enviados
    if (isset($_POST['id_egreso']) && isset($_POST['id_cliente']) && isset($_POST['nit_ci']) && isset($_POST['nombre_cliente']) && isset($_POST['productos']) && isset($_POST['nombres']) && isset($_POST['lotes']) && isset($_POST['vencimiento']) && isset($_POST['cantidades']) && isset($_POST['unidad'])&&  isset($_POST['tipo']) ) {
		// Importa la libreria para convertir el numero a letra
        require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

        $id_egreso = trim($_POST['id_egreso']);
        $id_cliente = trim($_POST['id_cliente']);
        $nit_ci = trim($_POST['nit_ci']);
        $nombre_cliente = trim($_POST['nombre_cliente']);
        $nro_factura = trim($_POST['nro_factura']);
        $motivo = trim($_POST['motivo']);
        $descripcion = trim($_POST['descripcion']);

        $productos = (isset($_POST['productos'])) ? $_POST['productos'] : array();
        $nombres = (isset($_POST['nombres'])) ? $_POST['nombres'] : array();
        $lotes = (isset($_POST['lotes'])) ? $_POST['lotes'] : array();
        $vencimiento = (isset($_POST['vencimiento'])) ? $_POST['vencimiento'] : array();
        $cantidades = (isset($_POST['cantidades'])) ? $_POST['cantidades'] : array();
        $unidad = (isset($_POST['unidad'])) ? $_POST['unidad'] : array();
        $precios = (isset($_POST['precios'])) ? $_POST['precios'] : array();
        $descuentos = (isset($_POST['descuentos'])) ? $_POST['descuentos'] : array();

        $almacen_id = trim($_POST['almacen_id']);
        $nro_registros = trim($_POST['nro_registros']);
        $monto_total = trim($_POST['monto_total']);
        $tipo = trim($_POST['tipo']);


        if ($tipo == 'Reposicion') {
            // Creamos el ingreso
            $ingreso = array(
                'fecha_ingreso'     => date('Y-m-d'),
                'hora_ingreso'      => date('H:i:s'),
                'tipo'              => 'Devolucion',
                'descripcion'       => 'Reposicion: ' . $descripcion,
                'monto_total'       => $monto_total,
                'descuento'         => 0,
                'monto_total_descuento' => 0,
                'nombre_proveedor'  => $nombre_cliente,
                'nro_registros'     => $nro_registros,
                'almacen_id'        => $almacen_id,
                'empleado_id'       => $_user['persona_id'],
                'egreso_id'         => $id_egreso,
                'tipo_devol'        => 'factura'
            );
            // Guardamos el ingreso
            $id_ingreso = $db->insert('inv_ingresos', $ingreso);

            // Guarda Historial
            $data = array(
                'fecha_proceso' => date("Y-m-d"),
                'hora_proceso' => date("H:i:s"),
                'proceso' => 'c',
                'nivel' => 'l',
                'direccion' => '?/operaciones/facturas_guardar',
                'detalle' => 'Se creo Ingreso con identificador numero ' . $id_ingreso ,
                'usuario_id' => $_SESSION[user]['id_user']
            );
            $db->insert('sys_procesos', $data) ;

            //Creamos y guardamos el detalle del ingreso
            foreach($productos as $nro => $producto){
                $unidad2 = $db->select('id_unidad')->from('inv_unidades')->where('unidad',$unidad[$nro])->fetch_first();
                $unidad3 = $unidad2['id_unidad'];
                $cantidad = cantidad_unidad($db, $productos[$nro], $unidad3)*$cantidades[$nro];
                $verif_f = $db->select('factura, factura_v, IVA')->from('inv_ingresos_detalles')->where('producto_id', $productos[$nro])->where('lote', $lotes[$nro])->where('vencimiento', $vencimiento[$nro])->fetch_first();
                $detalle = array(
                    'cantidad'      => $cantidad,
                    'costo'         => $precios[$nro],
                    'lote'          => $lotes[$nro],
                    'producto_id'   => $productos[$nro],
                    'ingreso_id'    => $id_ingreso,
                    'vencimiento'   => $vencimiento[$nro],
                    'dui'           => 0,
                    'contenedor'    => 0,
                    'factura'       => $verif_f['factura'],
                    'factura_v'     => $verif_f['factura_v'],
                    'almacen_id'    => $almacen_id,
                    'IVA'           => $verif_f['IVA'],
                );

                // Guarda la informacion
                $id_detalle = $db->insert('inv_ingresos_detalles', $detalle);
                // Guarda Historial
                $data = array(
                    'fecha_proceso' => date("Y-m-d"),
                    'hora_proceso' => date("H:i:s"),
                    'proceso' => 'c',
                    'nivel' => 'l',
                    'direccion' => '?/operaciones/facturas_guardar',
                    'detalle' => 'Se creo el detalle de ingreso con identificador numero ' . $id_detalle ,
                    'usuario_id' => $_SESSION[user]['id_user']
                );
                $db->insert('sys_procesos', $data) ;
            }

            $_SESSION[temporary] = array(
                'alert' => 'success',
                'title' => 'Se realizo la devolucion!',
                'message' => 'El registro se realizó correctamente.'
            );

            redirect('?/ingresos/ver/' . $id_ingreso);

        }

        $_SESSION[temporary] = array(
            'alert' => 'success',
            'title' => 'Se realizo la devolucion!',
            'message' => 'El registro se realizó correctamente.'
        );
        //enviamos a imprimir el nuevo egreso
        if ($tipo == 'Reposicion') {
            $_SESSION['imprimir'] = $id_egreso;
        }

        redirect('?/operaciones/facturas_listar');

		// Envia respuesta
        echo json_encode($id_egreso);

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