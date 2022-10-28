<?php

// Define las cabeceras
header('Content-Type: application/json');

// echo var_dump($_POST);

    if (is_ajax() && is_post()) {
        if (isset($_POST['id_cliente']) ){
            $id_cliente = trim($_POST['id_cliente']);
            
            $ids = array();

            $compras = $db->query("SELECT e.id_egreso, e.fecha_egreso, e.hora_egreso, e.nro_factura, e.plan_de_pagos, ed.cantidad, ed.precio,
                                          (ed.cantidad * ed.precio) as total, p.nombre_factura as producto, a.almacen, ed.lote, e.tipo, e.preventa
                                    FROM inv_egresos e
                                    LEFT JOIN inv_egresos_detalles ed ON ed.egreso_id = e.id_egreso
                                    LEFT JOIN inv_productos p ON ed.producto_id = p.id_producto
                                    LEFT JOIN inv_almacenes a ON e.almacen_id = a.id_almacen
                                    WHERE e.cliente_id = '$id_cliente' AND e.preventa!='Anulado'
                                    ORDER BY e.fecha_egreso DESC LIMIT 50")->fetch();
            // AND e.estadoe = '3'
            if (count($compras) > 0) {
                $tabla = '<table class="table table-bordered table-hover table-responsive table-sm" id="tabla_historial" style="max-width: 100% !important;">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Monto total</th>
                                <th>Almacen</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">';

                        foreach ($compras as $key => $compra) {
                            if (in_array($compra['id_egreso'], $ids)) {
                            
                            } else {
                                array_push($ids, $compra['id_egreso']);
                            }
                            $tabla .= '<tr>
                                            <td>'. date_decode($compra['fecha_egreso'], $_institution['formato']) . ' <br><small>' . $compra['hora_egreso'] . '</small></td>
                                            <td>' . $compra['producto'] . '<br>(<b>LOTE: </b>' . $compra['lote'] . ')</td>
                                            <td>' . $compra['cantidad'] . '</td>
                                            <td>' . $compra['precio'] . '</td>
                                            <td>' . $compra['total'] . '</td>
                                            <td>' . $compra['almacen'] . '</td>
                                            <td>' . $compra['tipo'] . '</td>
                                            <td>';
                                        
                                            if($compra['tipo']=="Preventa"){
                                                if($compra['preventa']==NULL){
                                                    $tabla .= 'Sin habilitar'; 
                                                }else{
                                                    $tabla .= $compra['preventa']; 
                                                }
                                            }else{
                                                $tabla .= '';
                                            }
                            $tabla .= '     </td>
                                        </tr>';
                                        
                        }

                $tabla .= '</tbody>
                    </table>';

                $tabla .= '
                            <script>
                                $("#tabla_historial").dataTable({
                                    info: false,
                                    lengthMenu: [
                                        [10, 25, 50, 100, 500, -1],
                                        [10, 25, 50, 100, 500, "Todos"]
                                    ],
                                    order: [[0, "desc"]]
                                });

                            </script>
                            ';

                ////////////////////////////////////////////////////////////////////
                if (count($ids) > 0) {
                    $pagos = $db->query("SELECT p.movimiento_id, p.tipo, SUM(IF(d.estado = 1 , d.monto, 0)) as saldo, e.monto_total, e.descripcion as egreso, e.fecha_egreso, e.hora_egreso, e.nro_nota
                                        FROM inv_egresos e
                                        LEFT JOIN inv_pagos p ON p.movimiento_id = e.id_egreso AND p.tipo='Egreso'
                                        LEFT JOIN inv_pagos_detalles d ON p.id_pago = d.pago_id 
                                        WHERE   e.id_egreso IN (".implode(',', $ids).")
                                        GROUP BY p.id_pago
                                        ")->fetch();
                    
                    foreach ($pagos as $key => $pago) {
                        $detail = $db->query("  SELECT SUM(d.precio*cantidad) as monto_total
                                                FROM inv_egresos e
                                                LEFT JOIN inv_egresos_detalles d ON id_egreso=egreso_id
                                                WHERE nro_nota='".$pago['nro_nota']."'
                                            ")->fetch_first();

                        $pagos[$key]['monto_total']=$detail['monto_total'];
                    }
                            
                            
                                        
                    if (count($pagos) > 0) {
                        $avanzado = '<table class="table table-bordered table-hover table-responsive table-sm" id="tabla_deudas" style="max-width: 100% !important;">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Nro. Nota</th>
                                                <th>Monto total</th>
                                                <th>A cuenta</th>
                                                <th>Pendiente</th>
                                                <th>Tipo</th>
                                            </tr>
                                        </thead>
                                    <tbody class="text-sm">';
                            foreach ($pagos as $key => $pago) {
                                if($pago['monto_total'] - $pago['saldo'] > 0){
                                    $avanzado .= '<tr>
                                                <td>'. date_decode($pago['fecha_egreso'], $_institution['formato']) . ' <br><small>' . $pago['hora_egreso'] . '</small></td>
                                                <td>' . $pago['nro_nota'] . '</td>
                                                <td>' . $pago['monto_total'] . '</td>
                                                <td>' . number_format(($pago['saldo']), 2, ',', '.') . '</td>
                                                <td class="danger">' . number_format(($pago['monto_total'] - $pago['saldo']), 2, ',', '.') . '</td>
                                                <td>'. ucwords(str_replace("Venta de productos con ", "", $pago['egreso'])) .'</td>
                                            </tr>';
                                }
                            }
                            $avanzado .= '</tbody>
                        </table>';
                    } else {
                        $avanzado = '<div class="alert alert-success" role="alert">
                                    <strong>El cliente no tiene deudas pendientes.</strong>
                                </div>';
                    }
                } else {
                    $avanzado = '<div class="alert alert-success" role="alert">
                                    <strong>El cliente no tiene deudas pendientes.</strong>
                                </div>';
                }

                echo json_encode(array('basico' => $tabla, 'avanzado' => $avanzado));

            } else {
                $basico = '<div class="alert alert-info" role="alert">
                            <strong>El cliente aún no tiene compras registradas.</strong>
                        </div>';
                $avanzado = '<div class="alert alert-success" role="alert">
                                <strong>El cliente no tiene deudas pendientes.</strong>
                            </div>';
                echo json_encode(array('basico' => $basico, 'avanzado' => $avanzado));
            }

            
        } else {
            // Devuelve los resultados
            echo json_encode(array('estado' => 'No llegaron los datos requeridos'));
        }

    } else {
        // Devuelve los resultados
        echo json_encode(array('estado' => 'Verifique la peticion'));
    }

?>