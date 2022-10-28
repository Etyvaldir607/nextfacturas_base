<?php
    $requestData=$_REQUEST;
    $ValorG=$requestData['search']['value'];

    $fecha_inicial=$params[0];
    $fecha_final=$params[1];

    $Campos=[
        'p.id_producto',
        'p.imagen',
        'p.codigo',
        'p.codigo_barras',
        'p.nombre',
        'p.nombre_factura',
        'c.categoria',
        'p.precio_actual',
        'u.unidad',
        'p.cantidad_minima',
        'p.precio_sugerido'
    ];

    // Obtiene las ventas
    $Sentencia="SELECT i.*,a.almacen,a.principal,e.nombres,e.paterno,e.materno
        FROM inv_egresos i
        LEFT JOIN inv_almacenes a ON i.almacen_id=a.id_almacen
        LEFT JOIN sys_empleados e ON i.empleado_id=e.id_empleado
        WHERE i.tipo='Venta' AND
            i.codigo_control!='' AND
            i.fecha_egreso>='{$fecha_inicial}' AND
            i.fecha_egreso<='{$fecha_final}'
        ORDER BY i.fecha_egreso DESC,i.hora_egreso DESC";

    