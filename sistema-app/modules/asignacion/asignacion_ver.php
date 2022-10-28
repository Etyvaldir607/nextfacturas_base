<?php

// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

// Obtiene fecha inicial
$empleado = (isset($params[0])) ? $params[0] : 0;
$id_venta = (isset($params[1])) ? $params[1] : 0;
$id_venta_333 = (isset($params[2])) ? $params[2] : 0;

$distro = $db->select('CONCAT(nombres," ", paterno," ", materno) as distribuidor')
                ->from('sys_empleados')
                ->where('id_empleado',$empleado)
                ->fetch_first();

$ultimo_despacho =   $db->select('MAX(fecha_hora_salida)as fecha_hora_salida')
                        ->from('inv_asignaciones_clientes')
                        ->where('distribuidor_id',$empleado)
                        ->where('estado','A')
                        ->fetch_first();

$usuarios_query = " select *
                    from inv_asignaciones_clientes
                    where distribuidor_id='$empleado' AND estado='A' AND fecha_hora_salida ='".$ultimo_despacho['fecha_hora_salida']."'";

$usuarios = $db->query($usuarios_query)->fetch();
                                    
                                    // ->where_in('estado_pedido',['habilitado', 'reasignado'])
                                    // ->where('estado_pedido !=', 'entregado')
                                    //->where('fecha_asignacion >=', date('Y-m-d')) // , $fecha_inicial
                                    // ->where('fecha_asignacion <=', $fecha_final)
                                    
                                    // $usuario3 = $db->select('id_user')->from('sys_users')->where('persona_id',$empleado)->fetch_first();
                                    // $usuario3 = $usuario3['id_user'];
                                    // echo json_encode($usuarios); die();
                                    // Obtiene las ventas


// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_ver = in_array('asignacion_ver', $permisos);
$permiso_eliminar = in_array('proformas_eliminar', $permisos); // no se esta usando
$permiso_imprimir = in_array('asignacion_imprimir', $permisos);
$permiso_facturar = in_array('asignacion_facturar', $permisos);
$permiso_entregar = in_array('preventas_entregar', $permisos);
$permiso_cambiar = true;
$permiso_noventa = true;
$permiso_noquiere = true;

?>
<?php require_once show_template('header-advanced'); ?>
    <link rel="stylesheet" href="<?= css; ?>/leaflet.css">
    <link rel="stylesheet" href="<?= css; ?>/leaflet-routing-machine.css">
    <link rel="stylesheet" href="<?= css; ?>/site.css">
    <style>
        .table-xs tbody {
            font-size: 12px;
        }
        .width-sm {
            min-width: 150px;
        }
        .width-md {
            min-width: 200px;
        }
        .width-lg {
            min-width: 250px;
        }
        .leaflet-control-attribution,
        .leaflet-routing-container {
            display: none;
        }
    </style>
    <div class="panel-heading" data-formato="<?= strtoupper($formato_textual); ?>" data-mascara="<?= $formato_numeral; ?>" data-gestion="<?= date_decode($gestion_base, $_institution['formato']); ?>">
        <h3 class="panel-title">
            <span class="glyphicon glyphicon-option-vertical"></span>
            <b>Lista de Preventas</b>
        </h3>
    </div>
    <div class="panel-body">
    <?php if ($permiso_cambiar || $permiso_imprimir) { ?>
        <div class="row">
            <div class="col-sm-8 hidden-xs">
                <h4 class="lead">Distribuidor: <?= $distro['distribuidor'] ?></h4>
            </div>
            <div class="col-xs-12 col-sm-4 text-right">
                <input type="hidden" id="lugares1" value="<?= $lugares ?>"/>
                <?php if ($permiso_cambiar && false) { ?>
                    <button class="btn btn-default" data-cambiar="true">
                        <span class="glyphicon glyphicon-calendar"></span>
                        <span class="hidden-xs">Cambiar</span>
                    </button>
                <?php } ?>
                <?php if ($permiso_imprimir) { ?>
                    <a href="?/asignacion/asignaciones" class="btn btn-info">
                        <span class="glyphicon glyphicon-list"></span>
                        <span class="hidden-xs">Listar</span>
                    </a>
                <?php } ?>
            </div>
        </div>
        <hr>
    <?php } ?>
    <?php if ($usuarios) { ?>
        <div class="row">
        <?php if (isset($_SESSION[temporary])) { ?>
            <div class="alert alert-<?= (isset($_SESSION[temporary]['alert'])) ? $_SESSION[temporary]['alert'] : $_SESSION[temporary]['type']; ?>">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong><?= $_SESSION[temporary]['title']; ?></strong>
                <p><?= (isset($_SESSION[temporary]['message'])) ? $_SESSION[temporary]['message'] : $_SESSION[temporary]['content']; ?></p>
            </div>
            <?php unset($_SESSION[temporary]); ?>
        <?php } ?>

        <div class="col-sm-12">
            <table id="table" class="table table-bordered table-condensed table-striped table-hover table-xs">
                <thead>
                <tr class="active">
                    <th class="text-nowrap">Nro Nota</th>
                    <th class="text-nowrap">Nro Factura</th>
                    <th class="text-nowrap">Fecha</th>
                    <th class="text-nowrap">Cliente</th>
                    <th class="text-nowrap">Direccion</th>
                    <th class="text-nowrap">Forma de pago</th>
                    <th class="text-nowrap">Prioridad</th>
                    <th class="text-nowrap">Vendedor</th>
                    <th class="text-nowrap">Motivo</th>
                    <?php if ($permiso_facturar || $permiso_ver || $permiso_eliminar) { ?>
                        <th class="text-nowrap">Opciones</th>
                    <?php } ?>
                    <?php if ($permiso_imprimir) { ?>
                        <th class="text-nowrap">Imprimir</th>
                    <?php } ?>
                </tr>
                </thead>
                <tfoot>
                <tr class="active">
                    <th class="text-nowrap text-middle" data-datafilter-filter="false">Nro Nota</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="false">Nro Factura</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Fecha</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Cliente</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Direccion</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Forma de pago</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Prioridad</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Vendedor</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Motivo</th>
                    <?php if ($permiso_facturar || $permiso_ver || $permiso_eliminar) { ?>
                        <th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
                    <?php } ?>
                    <?php if ($permiso_imprimir) { ?>
                        <th class="text-nowrap text-middle" data-datafilter-filter="false">Imprimir</th>
                    <?php } ?>
                </tr>
                </tfoot>
                <tbody>
                <?php
                $nro2 = 1;
                $nro_marker = 0;
                foreach ($usuarios as $usuario){

                    // if($usuario['egreso_id'] != 0 && $usuario['estado_pedido'] != 'entregado' ){
                    
                    $proformas2query= "  select *, c.direccion, c.cliente
                                         from tmp_egresos a
                                         LEFT join sys_empleados e ON a.vendedor_id = e.id_empleado
                                         LEFT join inv_clientes c ON a.cliente_id = c.id_cliente
                                         where a.id_egreso='".$usuario['egreso_id']."'
                                             AND a.estadoe > 1
                                             AND a.estadoe < 4";

                    $proformas2= $db->query($proformas2query)
                                     ->fetch();

                                        //->from('inv_egresos a')
                                        //->where('a.fecha_egreso >=',$fecha_inicial)
                                        //->where('a.fecha_egreso <=',$fecha_final)
                                        //->where('a.estado=',3)
                         
                    foreach ($proformas2 as $nro => $proforma) {
                        $fecha_habilitacion=explode(" ",$proforma['fecha_habilitacion']);
                        ?>
                        <tr>
                            <td class="text-nowrap text-right"><?= escape($proforma['nro_nota']); ?></td>
                            <td class="text-nowrap text-right"><?= escape($proforma['nro_factura']); ?></td>
                            <td class="text-nowrap"><?= escape(date_decode($fecha_habilitacion[0], $_institution['formato'])); ?> <small class="text-success"><?= escape($fecha_habilitacion[1]); ?></small></td>
                            <!--<td class="text-nowrap"><?= escape(date_decode($proforma['distribuidor_fecha'], $_institution['formato'])); ?> <small class="text-success"><?= escape($proforma['distribuidor_hora']); ?></small></td>-->
                            
                            <td class="text-nowrap text-info" id="openPopup_<?= $nro_marker; ?>" data-toggle="tooltip" data-title="Ubicar en mapa"><?= escape($proforma['cliente']); ?></td>
                            <td class=""><?= escape($proforma['direccion']); ?></td>
                            <td class="text-nowrap"><?php if($proforma['plan_de_pagos']=='si'){ echo "Plan de pagos"; }else{ echo "Al contado"; } ?></td>
                            <td class="text-center text-middle coordenadas">
                                <?php $ubi = explode(',', $proforma['ubicacion']) ?>
                                <span class="latitud hidden"><?= $ubi[0] + 0.00005; ?></span>
                                <span class="longitud hidden"><?= $ubi[1] - 0.00003; ?></span>
                                <span class="id_c hidden"><?= $proforma['cliente_id'] ?></span>
                                <span class="nombre hidden"><?= $proforma['cliente'] ?></span>
                                <span class="estadoo hidden"><?= $proforma['distribuidor_estado'] ?></span>
                                <span><?= $proforma['observacion'] ?></span>
                            </td>
                            <td class=""><?= escape($proforma['nombres'] . ' ' . $proforma['paterno'] . ' ' . $proforma['materno']); ?></td>
                            <!--<td class="text-nowrap hidden"><?php // escape($proforma['cliente_id']); ?></td>-->
                            <td class="text-left"><?= escape($proforma['motivo_id']); ?></td>
                            <?php if ($permiso_facturar || $permiso_ver || $permiso_eliminar) { ?>
                                <td class="text-nowrap">
                                    <?php if ($permiso_facturar && $proforma['nro_factura']==0) { ?>
                                        <span class="glyphicon glyphicon-qrcode" data-reimprimir="<?= $proforma['id_egreso']; ?>"></span>
                                    <?php } ?>
                                    <?php if ($permiso_ver && false) { ?>
                                        <!--<a href="?/operaciones/preventas_ver/<?= $proforma['id_egreso']; ?>" data-toggle="tooltip" data-title="Ver detalle de la preventa"><span class="glyphicon glyphicon-list-alt"></span></a>-->
                                    <?php } ?>
                                    <?php if ($permiso_eliminar) { ?>
                                        <a href="?/operaciones/preventas_eliminar/<?= $proforma['id_egreso']; ?>" data-toggle="tooltip" data-title="Eliminar preventa" data-eliminar="true"><span class="glyphicon glyphicon-trash"></span></a>
                                    <?php } ?>
                                </td>
                            <?php } ?>
                                <td class="text-nowrap">
                                    <?php 
                                        if($proforma['distribuidor_estado']=='ENTREGA'){?>
                                            <!--<a href="?/operaciones/imprimir5/<?php // $proforma['id_egreso']; ?>/<?php // $proforma['id_tmp_egreso']; ?>" data-toggle="tooltip" data-title="Imprimir" target="_blank"><span class="glyphicon glyphicon-print" style="color:green"></span></a>-->
                                            <a href="?/notas/imprimir_nota/<?= $proforma['id_egreso']; ?>/<?= $proforma['id_tmp_egreso']; ?>" data-toggle="tooltip" data-title="Imprimir" target="_blank"><span class="glyphicon glyphicon-print" style="color:green"></span></a>
                                        <?php } if($proforma['distribuidor_estado']=='DEVUELTO'){?>
                                            <a href="?/notas/imprimir_nota/<?= $proforma['id_egreso']; ?>/<?= $proforma['id_tmp_egreso']; ?>" data-toggle="tooltip" data-title="Imprimir" target="_blank"><span class="glyphicon glyphicon-print" style="color:red"></span></a>
                                        <?php }if($proforma['distribuidor_estado']=='ALMACEN'){ ?>
                                            <a href="?/notas/imprimir_nota/<?= $proforma['id_egreso']; ?>/<?= $proforma['id_tmp_egreso']; ?>" data-toggle="tooltip" data-title="Imprimir" target="_blank"><span class="glyphicon glyphicon-print" style="color:blue"></span></a>
                                        <?php }if($proforma['distribuidor_estado']=='NO ENTREGA'){ ?>
                                            <a href="?/notas/imprimir_nota/<?= $proforma['id_egreso']; ?>/<?= $proforma['id_tmp_egreso']; ?>" data-toggle="tooltip" data-title="Imprimir" target="_blank"><span class="glyphicon glyphicon-print" style="color:black"></span></a>
                                        <?php } ?>
                                        <?php if ($proforma['nro_factura']!=0) { ?>
                                            <a href="?/operaciones/refacturado_imprimir/<?= $proforma['id_egreso']; ?>" data-toggle="tooltip" data-title="Imprimir Factura" target="_blank"><span class="glyphicon glyphicon-qrcode" style="color:blue"></span></a>
                                        <?php } ?> 
                                        
                                        <?php 
                                        // echo $proforma['estadoe'];
                                        // echo $proforma['estadoe'];
                                        // echo $proforma['estadoe'];
                                        // echo $proforma['estadoe'];
                                        
                                        if ($proforma['estadoe']==3 && $proforma['plan_de_pagos']=='no') { 
                                            $code = $db->query("select *
                                                               from inv_pagos_detalles
                                                               left join inv_pagos ON inv_pagos.id_pago = inv_pagos_detalles.pago_id
                                                               where inv_pagos.tipo='Egreso'
                                                                    AND movimiento_id ='".$proforma['id_egreso']."'")
                                                   ->fetch_first();
                                            ?>
                                            <a href="?/cobrar/recibo_dinero/<?= $code['id_pago_detalle']; ?>" data-toggle="tooltip" data-title="Imprimir Recibo" target="_blank"><span class="glyphicon glyphicon-tag" style="color:blue"></span></a>
                                        <?php } 
                                     ?>
                                </td>
                            <?php  ?>
                        </tr>
                    <?php $nro_marker++; } 
                    
                    if($usuario['estado_pedido'] != 'entregado' ) {
                        $proformas1query = "select *
                                            from inv_egresos a
                                            left join sys_empleados e ON a.vendedor_id = e.id_empleado
                                            left join inv_clientes c ON a.cliente_id = c.id_cliente
                                            
                                            where   a.id_egreso ='".$usuario['egreso_id']."'
                                                    and a.estadoe >=2
                                                    and a.estadoe <=4";
                                            
                                            //->join('gps_noventa_motivos g', 'a.motivo_id = g.id_motivo', 'left')
                                            //->where('a.preventa', 'habilitado')
                                            //->where('a.fecha_egreso >=',$fecha_inicial)
                                            //->where('a.fecha_egreso <=',$fecha_final)

                        $proformas1 = $db->query($proformas1query)->fetch();
                        
                    } else {
                        $proformas1 = [];
                    }

                    // var_dump($proformas);
                    // echo $db->last_query();

                    foreach ($proformas1 as $nro => $proforma) {
                        $fecha_habilitacion=explode(" ",$proforma['fecha_habilitacion']);
                        ?>
                        <tr class="<?= (($proforma['estadoe']==4 && ($proforma['preventa']=='habilitado' || $proforma['preventa']==NULL) ) ? 'warning': (($proforma['preventa']=='eliminado') ? 'danger':'')); ?>">
                            <td class="text-nowrap text-right"><?= escape($proforma['nro_nota']); ?></td>
                            <td class="text-nowrap text-right"><?= escape($proforma['nro_factura']); ?></td>
                            <td class="text-nowrap"><?= escape(date_decode($fecha_habilitacion[0], $_institution['formato'])); ?> <small class="text-success"><?= escape($fecha_habilitacion[1]); ?></small></td>
                            
                            <!--<td class="text-nowrap" ><?= escape($proforma['cliente_id']); ?></td>-->
                            <td class="text-nowrap text-info" id="openPopup_<?= $nro_marker; ?>" data-toggle="tooltip" data-title="Ubicar en mapa"><?= escape($proforma['cliente']); ?></td>
                            <td class=""><?= escape($proforma['direccion']); ?></td>
                            <td class="text-nowrap"><?php if($proforma['plan_de_pagos']=='si'){ echo "Plan de pagos"; }else{ echo "Al contado"; } ?></td>
                            <td class="text-nowrap text-center text-middle coordenadas">
                                <?php $ubi = explode(',', $proforma['ubicacion']) ?>
                                <span class="latitud hidden"><?= $ubi[0] ?></span>
                                <span class="longitud hidden"><?= $ubi[1] ?></span>
                                <span class="id_c hidden"><?= $proforma['cliente_id'] ?></span>
                                <span class="nombre hidden"><?= $proforma['cliente'] ?></span>
                                <span class="estadoo hidden"><?= ($proforma['estadoe'] == 2)?2:4 ?></span>
                                <span><?= $proforma['observacion'] ?></span>
                            </td>
                            <td class=""><?= escape($proforma['nombres'] . ' ' . $proforma['paterno'] . ' ' . $proforma['materno']); ?></td>
                            <td class="text-left"><?= escape($proforma['motivo_id']); ?></td>
                            <?php if ($permiso_facturar || $permiso_ver || $permiso_eliminar || $permiso_noventa) { ?>
                                <td class="text-nowrap">
                                    <?php if ($permiso_facturar && $proforma['nro_factura']==0 && $proforma['estadoe'] == 2 && $proforma['preventa'] != NULL) { ?>
                                        <span class="glyphicon glyphicon-qrcode" data-reimprimir="<?= $proforma['id_egreso']; ?>"></span>
                                    <?php } ?>
                                    <?php if ($permiso_ver && false) { ?>
                                        <!--<a href="?/operaciones/preventas_ver/<?= $proforma['id_egreso']; ?>" data-toggle="tooltip" data-title="Ver detalle de la preventa" target="_blank"><span class="glyphicon glyphicon-list-alt"></span></a>-->
                                    <?php } ?>
                                    <?php if ($permiso_eliminar && $proforma['preventa'] != NULL) { ?>
                                        <a href="?/operaciones/preventas_eliminar/<?= $proforma['id_egreso']; ?>" data-toggle="tooltip" data-title="Eliminar preventa" data-eliminar="true"><span class="glyphicon glyphicon-trash"></span></a>
                                    <?php } ?>
                                    
                                    <?php if ($permiso_entregar && $proforma['estadoe'] == 2 && $proforma['preventa'] != NULL) { ?>
                                        <a onclick='entregar_asignacion(<?= $usuario["id_asignacion_cliente"] ?>, <?= $proforma["id_egreso"] ?>)' data-toggle='tooltip' data-title='Entregar asignacion' title='Entregar asignacion' class='text-success'><i class='glyphicon glyphicon-download'></i></a>
                                    <?php }?>
                                    <?php if ($permiso_noventa && $proforma['estadoe'] == 2 && $proforma['preventa'] != NULL) { ?>
                                        <a onclick='no_venta(<?= $usuario["id_asignacion_cliente"] ?>)' data-toggle='tooltip' data-title='No entregar' title='No entregar' class='text-warning'><i class='glyphicon glyphicon-upload'></i></a>
                                    <?php }?>
                                    <?php // if ($permiso_noquiere && $proforma['estadoe'] == 2) { ?>
                                        <!-- <a onclick='no_quiere(<?php // $usuario["id_asignacion_cliente"] ?>)' data-toggle='tooltip' data-title='No quiere' title='No quiere' class='text-danger'><i class='glyphicon glyphicon-remove-circle'></i></a> -->
                                    <?php // }?>
                                </td>
                            <?php } ?>
                            <?php if ($permiso_imprimir) { ?>
                                <td class="text-nowrap">
                                    <?php if ($permiso_imprimir && $proforma['preventa'] != NULL) {
                                        if($proforma['estadoe']==2 && $proforma['preventa'] != NULL){?>
                                            <a href="?/notas/imprimir_nota/<?= $proforma['id_egreso']; ?>" data-toggle="tooltip" data-title="Imprimir Nota" target="_blank"><span class="glyphicon glyphicon-print" style="color:blue"></span></a>
                                        <?php } if($proforma['estadoe']==1 && $proforma['preventa'] != NULL){?>
                                            <a href="?/notas/imprimir_nota/<?= $proforma['id_egreso']; ?>" data-toggle="tooltip" data-title="Imprimir Nota" target="_blank"><span class="glyphicon glyphicon-print" style="color:red"></span></a>
                                        <?php }if($proforma['estadoe']==3 && $proforma['preventa'] != NULL){ ?>
                                            <a href="?/notas/imprimir_nota/<?= $proforma['id_egreso']; ?>" data-toggle="tooltip" data-title="Imprimir Nota" target="_blank"><span class="glyphicon glyphicon-print" style="color:blue"></span></a>
                                        <?php }
                                    } ?>
                                    
                                    <?php if ($proforma['nro_factura']!=0) { ?>
                                        <a href="?/operaciones/refacturado_imprimir/<?= $proforma['id_egreso']; ?>" data-toggle="tooltip" data-title="Imprimir Factura" target="_blank"><span class="glyphicon glyphicon-qrcode" style="color:blue"></span></a>
                                    <?php } ?>
                                    <?php 
                                    if ($proforma['estadoe']==3 && $proforma['plan_de_pagos']=='no') { 
                                        
                                        $code = $db->select('*')
                                                   ->from('inv_pagos_detalles')
                                                   ->join('inv_pagos', 'inv_pagos.id_pago = inv_pagos_detalles.pago_id')
                                                   ->where('inv_pagos.tipo', 'Egreso')
                                                   ->where('movimiento_id', $proforma['id_egreso'])
                                                   ->fetch_first();
                                        
                                    ?>
                                        <a href="?/cobrar/recibo_dinero/<?= $code['id_pago_detalle']; ?>" data-toggle="tooltip" data-title="Imprimir Recibo" target="_blank"><span class="glyphicon glyphicon-tag" style="color:blue"></span></a>
                                    <?php } ?>
                                </td>
                            <?php } ?>
                        </tr>
                        <?php 
                        $nro_marker++; 
                    }
                    
                     
                }
                ?>
                </tbody>
            </table>
        </div>
        <div class="col-sm-12">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="lead">Ruta de preventas</h4>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="?/distribuidor/ver/<?= $empleado ?>" class="btn btn-success" target="_blank">
                        <span class="glyphicon glyphicon-fullscreen"></span>
                        <span class="hidden-xs">Expandir</span>
                    </a>
                </div>
            </div>
            <hr>
            <div id="map" class="embed-responsive embed-responsive-16by9"></div>
        </div>
        </div>
    <?php } else { ?>
        <div class="alert alert-danger">
            <strong>Advertencia!</strong>
            <p>No existen proformas registradas en la base de datos.</p>
        </div>
    <?php } ?>
    </div>

    <script src="<?= js; ?>/jquery.dataTables.min.js"></script>
    <script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
    <script src="<?= js; ?>/jquery.form-validator.min.js"></script>
    <script src="<?= js; ?>/jquery.form-validator.es.js"></script>
    <script src="<?= js; ?>/jquery.maskedinput.min.js"></script>
    <script src="<?= js; ?>/jquery.base64.js"></script>
    <script src="<?= js; ?>/pdfmake.min.js"></script>
    <script src="<?= js; ?>/vfs_fonts.js"></script>
    <script src="<?= js; ?>/jquery.dataFilters.min.js"></script>
    <script src="<?= js; ?>/moment.min.js"></script>
    <script src="<?= js; ?>/moment.es.js"></script>
    <script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>
    <script src="<?= js; ?>/leaflet.js"></script>
    <script src="<?= js; ?>/leaflet-routing-machine.js"></script>
    <script src="<?= js; ?>/Leaflet.Icon.Glyph.js"></script>

    <script>
        $(function () {
            <?php if($id_venta){ ?>
                imprimir_nota_final(<?= $id_venta ?>,<?= $id_venta_333 ?>);
            <? } ?>

            var table = $('#table').DataFilter({
                filter: true,
                name: 'proformas',
                reports: 'excel|word|pdf|html'
            });

            $('#states_0').find(':radio[value="hide"]').trigger('click');

            var latitudes = new Array(), longitudes = new Array(), estados = new Array(), nombres = new Array(), lugar = new Array();
            var markers = [];

            $('.coordenadas').each(function (i) {
                var latitud = $.trim($(this).find('.latitud').text());
                var longitud = $.trim($(this).find('.longitud').text());
                var luga = $.trim($(this).find('.id_c').text());
                var estado = $.trim($(this).find('.estadoo').text());
                var nombre = $.trim($(this).find('.nombre').text());
                if (latitud != '0.0' && longitud != '0.0') {
                    latitudes.push(latitud);
                    longitudes.push(longitud);
                    estados.push(estado);
                    nombres.push(nombre);
                    lugar.push(luga);
                    if($("#table tbody tr").length === 1){
                        latitudes.push(latitud);
                        longitudes.push(longitud);
                        estados.push(estado);
                        nombres.push(nombre);
                        lugar.unshift(0)
                    }
                }
            });
            console.log(latitudes);
            console.log(longitudes);
            console.log(estados);
            if (latitudes.length != 0 && longitudes.length != 0) {

                var LeafIcon = L.Icon.extend({
                    options: {
                        iconSize: [25, 41],
                        iconAnchor:  [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize:  [41, 41],
                        // 		iconUrl: 'glyph-marker-icon.png',
                        // 		iconSize: [35, 45],
                        // 		iconAnchor:   [17, 42],
                        // 		popupAnchor: [1, -32],
                        // 		shadowAnchor: [10, 12],
                        // 		shadowSize: [36, 16],
                        // 		bgPos: (Point)
                        className: '',
                        prefix: '',
                        glyph: 'home',
                        glyphColor: 'white',
                        glyphSize: '11px',	// in CSS units
                        glyphAnchor: [0, -7]
                    }
                });
                var greenIcon = new LeafIcon({iconUrl: '<?= files .'/puntero/green.png' ?>'}),
                    redIcon = new LeafIcon({iconUrl: '<?= files .'/puntero/red.png' ?>'}),
                    blueIcon = new LeafIcon({iconUrl: '<?= files .'/puntero/blue.png' ?>'});

                function handleError(e) {
                    if (e.error.status === -1) {
                        // HTTP error, show our error banner
                        document.querySelector('#osrm-error').style.display = 'block';
                        L.DomEvent.on(document.querySelector('#osrm-error-close'), 'click', function(e) {
                            document.querySelector('#osrm-error').style.display = 'none';
                            L.DomEvent.preventDefault(e);
                        });
                    }
                }


                console.log(latitudes[4]);

                var waypoints1 = new Array();

                var centerPoint = [latitudes[0], longitudes[0]];

                // Create leaflet map.
                var map = L.map('map').setView(centerPoint, 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                }).addTo(map);

                for (var i=0 ; latitudes.length > i; i++) {
                        if(estados[i] === '2'){
                            var marker = L.marker([latitudes[i], longitudes[i]], {icon: blueIcon});
                        }else if(estados[i] === 'ENTREGA'){
                            var marker = L.marker([latitudes[i], longitudes[i]], {icon: greenIcon});
                        }else if(estados[i] === 'DEVUELTO'){
                            var marker = L.marker([latitudes[i], longitudes[i]], {icon: redIcon});
                        }else{
                            var marker = L.marker([latitudes[i], longitudes[i]], {icon: redIcon});
                        }
                        marker.bindPopup(nombres[i]+'<br> Id Cli: '+lugar[i]);
                        marker.off('click');
                        marker.on('click', function() {return;});
                        marker.addTo(map);
                        markers.push(marker);
                }
            }
            
            <?php for($i = 0; $i <= $nro_marker; $i++){ ?>
                jQuery('#openPopup_'+<?= $i ?>).click(function(){
                   
                   var posicion = $("#map").offset().top;
                    $("html, body").animate({
                        scrollTop: posicion
                    }, 500);
                    if (markers.length) {
                        markers[<?= $i ?>].openPopup();
                    }
                });
            <?php } ?>
        
        });
        
        
        


        function entregar_asignacion(id_asignacion, id_egreso) {
            // bootbox.confirm('Está seguro de entregar la asignación? no podrá rehacer esta acción.', function (result) {
            //                 if(result){
            //                     window.location = '?/asignacion/preventas_entregar/' + id_asignacion;
            //                 }
            //             });
            
            // $.ajax({
            //             url: "?/cobrar/notas_ver/" + id_egreso,
            //             type: 'GET',
            //             dataType: 'html',
            //             // data: {titulo:titulo,dcorta:dcorta,dlarga:dlarga,importancia:importancia},
            //             succes: function(data){
            //                 // window.location.assign('/?view=noticias'+'&id='+id)
            //                 $('.bootbox-body').html(data);
            //             }
            //         });
            
            
            var dialog = bootbox.dialog({
                title: 'Está seguro de entregar la asignación? no podrá rehacer esta acción.',
                message: '<p><i class="fa fa-spin fa-spinner"></i> Cargando detalle...</p>',
                size: 'large',
                buttons: {
                    noclose: {
                        label: "Editar Venta",
                        className: 'btn-warning',
                        callback: function(){
                            // console.log('Custom button clicked');
                            // return false;
                            window.location = '?/asignacion/preventa_distribucion_editar/' + id_egreso +'/1';
                        }
                    },
                    ok: {
                        label: "Aceptar!",
                        className: 'btn-primary',
                        callback: function(){
                            // console.log('Custom OK clicked');
                            window.location = '?/asignacion/preventas_entregar/' + id_asignacion;
                        }
                    },
                    cancel: {
                        label: "Cancelar",
                        className: 'btn-default',
                        callback: function(){
                            console.log('Custom cancel clicked');
                        }
                    }
                }
            });
                        
            dialog.init(function(){
                setTimeout(function(){
                    dialog.find('.bootbox-body').load("?/asignacion/detallando/" + id_egreso);
                }, 1000);
            });
            
            
            
        
            // bootbox.dialog({
            //     title: '¿Está seguro de entregar la asignación?',
            //     message: function(){
            //         var url = "?/cobrar/notas_ver/" + id_egreso;
            //         $(".bootbox-body").load(url);
            //     },
            //     size: 'large',
            //     buttons: {
            //         noclose: {
            //             label: "Editar Venta",
            //             className: 'btn-warning',
            //             callback: function(){
            //                 // console.log('Custom button clicked');
            //                 // return false;
            //                 window.location = '?/asignacion/preventas_editar/' + id_egreso +'/1';
            //             }
            //         },
            //         ok: {
            //             label: "Aceptar!",
            //             className: 'btn-primary',
            //             callback: function(){
            //                 // console.log('Custom OK clicked');
            //                 window.location = '?/asignacion/preventas_entregar/' + id_asignacion;
            //             }
            //         },
            //         cancel: {
            //             label: "Cancelar",
            //             className: 'btn-default',
            //             callback: function(){
            //                 console.log('Custom cancel clicked');
            //             }
            //         }
            //     }
            // });
            
        }


        function no_venta(id_asignacion) {
            bootbox.prompt({
                title: "¿Está seguro de no vender esta asignación? no podrá rehacer esta acción.",
                inputType: 'select',
                inputOptions: [ <?php 
                                $motivos = $db->select('*')->from('gps_noventa_motivos')->fetch();
                                foreach ($motivos as $motivo) { ?> {
                                        text: '<?= $motivo['motivo'] ?>',
                                        value: '<?= $motivo['motivo'] ?>'
                                    },
                                <?php } ?>
                ],
                callback: function (result) {
                    if(result){
                        // window.location = '?/asignacion/preventas_noventa/' + id_asignacion + '/' + result;
                        // alert('La asignacion es: ' + id_asignacion +' El motivo es: ' + result);
                        cambiar_estadn(id_asignacion, result);
                    }
                }
            });
        }

        function cambiar_estadn(a, b){
            $.ajax({
                type: 'post',
                dataType: 'json',
                url: '?/asignacion/preventas_noventa',
                data: {
                    id_asignacion: a,
                    id_motivo: b
                }
            }).done(function (respuesta) {
                if (respuesta.estado === 's') {
                    $.notify({
                        message: 'Accion satisfactoria! la operacion se registró correctamente.'
                    }, {
                        type: 'success'
                    });
                    setTimeout(function(){ window.location = '?/asignacion/asignacion_ver/<?= $empleado ?>'; }, 3000);
                }else{
                    $.notify({
                        message: respuesta.estado
                    }, {
                        type: 'danger'
                    });
                    setTimeout(function(){ window.location = '?/asignacion/asignacion_ver/<?= $empleado ?>'; }, 3000);
                }
            }).fail(function () {
                $.notify({
                    message: 'La operación fue interrumpida por un fallo.'
                }, {
                    type: 'danger'
                });
            });
        }

        <?php if ($permiso_facturar) { ?>
        	$('[data-reimprimir]').on('click', function () {
        		
        		var id_venta = $(this).attr('data-reimprimir');
                        
    			bootbox.confirm('¿Esta seguro de generar la Factura?', function(result) {
        			if (result) {
        		        $('#loader').fadeIn(100);
                
                		$.ajax({
                			type: 'post',
                			dataType: 'json',
                			url: '?/operaciones/nota_obtener',
                			data: {
                				id_venta: id_venta
                			}
                		}).done(function (respuesta) {
                			console.log(respuesta);
                			$('#loader').fadeOut(100);
                			if (respuesta != 'error') {
                				$.open('?/operaciones/refacturado_imprimir/' + respuesta, true);
                				$.notify({
                					title: '<strong>Operación satisfactoria!</strong>',
                					message: '<div>Generando factura...</div>'
                				}, {
                					type: 'success'
                				});
                				setTimeout("location.reload(true);", 100);
                			} else {
                				$.notify({
                					title: '<strong>Advertencia!</strong>',
                					message: '<div>Ocurrió un problema en el envio de la información.</div>'
                				}, {
                					type: 'danger'
                				});
                			}
                		}).fail(function () {
                			$('#loader').fadeOut(100);
                			$.notify({
                				title: '<strong>Error!</strong>',
                				message: '<div>Ocurrió un problema al obtener los datos de la venta.</div>'
                			}, {
                				type: 'danger'
                			});
                		});         
        			}
        		});
            	   
        	});
    	<?php } ?>
    
    function imprimir_nota_final(nota,recibo) {
		bootbox.confirm('¿Desea imprimir la nota de venta?', function(result) {
			if (result) {
		        $.open('?/notas/imprimir_nota/' + nota, true);
		        if(recibo != 0){
				    imprimir_recibo(recibo);
				}
			}
			else{
	    		if(recibo != 0){
				    imprimir_recibo(recibo);
	    		}
			}
		});
	}
    function imprimir_recibo(nota) {
		bootbox.confirm('¿Desea Imprimir el recibo?', function(result) {
			if (result) {
        		window.open('?/cobrar/recibo_dinero/' + nota, true);
        		//window.location.reload();
			}
			else{
        		//window.location.reload();
			}
		});
	}
    </script>
<?php require_once show_template('footer-advanced'); ?>