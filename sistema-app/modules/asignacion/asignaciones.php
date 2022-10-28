<?php

$id_externo = (isset($params[0])) ? $params[0] : 0;

// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

$asignacionesQuery= "SELECT  ac.*, em.id_empleado, em.nombres, em.paterno, em.materno, em.fecha_validar, em.hora,  
                            IFNULL(SUM(e.monto_total), 0) as total, COUNT(e.id_egreso) as registros
                    FROM sys_users u
                    
                    LEFT JOIN sys_empleados em ON u.persona_id = em.id_empleado
                    
                    LEFT JOIN inv_asignaciones_clientes ac ON em.id_empleado = ac.distribuidor_id AND ac.estado = 'A' 
                            
                    LEFT JOIN inv_egresos e ON ac.egreso_id = e.id_egreso AND 
                              (e.tipo = 'Preventa' OR e.tipo = 'Venta') AND 
                              e.preventa = 'habilitado' AND e.estadoe = 2
                              
                    LEFT JOIN 
                    (
                        SELECT ua.*, ua2.user_id as user_idd
                        FROM inv_users_almacenes ua  
                    	LEFT JOIN inv_users_almacenes ua2 ON ua2.almacen_id=ua.almacen_id 
                    )ux ON ux.user_id=u.id_user AND '".$_user['id_user']."' = ux.user_idd
                    
                    WHERE   u.rol_id = 4 AND 
                            (
                                (u.id_user='".$_user['id_user']."' AND '".$_user['rol_id']."' = 4)
                                OR '".$_user['rol_id']."' = 1
                                OR '".$_user['rol_id']."' = 17
                            )

                    GROUP BY u.id_user
                    ";
                    
$asignaciones = $db->query($asignacionesQuery)->fetch();
// echo $db->last_query();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_preventas_listar = in_array('preventas_listar', $permisos);
$permiso_asignacion_ver = in_array('asignacion_ver', $permisos);
$permiso_ver = in_array('preventas_ver', $permisos);
$permiso_cliente = in_array('preventas_asignar', $permisos);
$permiso_imprimir = in_array('asignacion_imprimir', $permisos);
$permiso_imprimir1 = in_array('asignacion_imprimir1', $permisos);
$permiso_imprimir2 = in_array('asignacion_imprimir2', $permisos);
$permiso_activar = in_array('asignacion_activar', $permisos);
$permiso_activar_reabrir = in_array('asignacion_activar3', $permisos);
$permiso_activar_cerrar = in_array('asignacion_activar2', $permisos);
$permiso_despachar_distribuidor = in_array('despachar_distribuidor', $permisos);
$permiso_cambiar = true;

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading" data-formato="<?= strtoupper($formato_textual); ?>" data-mascara="<?= $formato_numeral; ?>" data-gestion="<?= date_decode($gestion_base, $_institution['formato']); ?>">
    <h3 class="panel-title">
        <span class="glyphicon glyphicon-option-vertical"></span>
        <strong>Listar distribuidores</strong>
    </h3>
</div>
<div class="panel-body">
    <?php if ($permiso_cambiar) { ?>
        <div class="row">
            <div class="col-sm-8 hidden-xs">
                <div class="text-label">Para filtrar por fechas hacer clic en el siguiente bot贸n: </div>
            </div>
            <?php if ($permiso_preventas_listar) { ?>
            <div class="col-xs-12 col-sm-4 text-right">
                <a href="?/asignacion/preventas_listar" class="btn btn-primary" ><i class="glyphicon glyphicon-arrow-left"></i><span class="hidden-xs"> Listar</span></a>
            </div>
            <?php } ?>
        </div>
        <hr>
    <?php } ?>
    <?php if (isset($_SESSION[temporary])) { ?>
        <div class="alert alert-<?= $_SESSION[temporary]['alert']; ?>">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><?= $_SESSION[temporary]['title']; ?></strong>
            <p><?= $_SESSION[temporary]['message']; ?></p>
        </div>
        <?php unset($_SESSION[temporary]); ?>
    <?php } ?>

    <?php if ($asignaciones) { ?>
        <table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
            <thead>
                <tr>
                    <th class="text-nowrap">#</th>
                    <th class="text-nowrap">Nombres</th>
                    <th class="text-nowrap">Paterno</th>
                    <th class="text-nowrap">Materno</th>
                    <th class="text-nowrap">Total</th>
                    <th class="text-nowrap">Registros</th>
                    <th class="text-nowrap">Acciones</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="active">
                    <th class="text-nowrap text-middle" data-datafilter-filter="false">#</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Nombres</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Paterno</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Materno</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Total</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Registros</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="false">Acciones</th>
                </tr>
            </tfoot>
            <tbody>
                <?php foreach ($asignaciones as $key => $asignacion) { //if($asignacion['registros'] != 0) {?>
                    <tr>
                        <td class="text-nowrap"><?= $key+1 ?></td>
                        <td class="text-nowrap"><?= $asignacion['nombres'] ?></td>
                        <td class="text-nowrap"><?= $asignacion['paterno'] ?></td>
                        <td class="text-nowrap"><?= $asignacion['materno'] ?></td>
                        <td class="text-nowrap text-right"><?= number_format($asignacion['total'],2,',','.') ?></td>
                        <td class="text-nowrap"><?= $asignacion['registros'] ?></td>
                        <td class="text-nowrap">
                        
                        <?php 
                        $asignacionesX2 = $db->query("SELECT  ac.*
                                                    FROM inv_asignaciones_clientes ac  
                                                    WHERE distribuidor_id='".$asignacion['id_empleado']."' AND estado_pedido='sin_aprobacion' 
                                                ")->fetch();

                        //WHERE distribuidor_id='".$asignacion['id_empleado']."' AND estado_pedido='sin_aprobacion' AND estado='".$asignacion['estado']."'
                        
                        if( $asignacionesX2) { 
                            if( $permiso_despachar_distribuidor) { ?>
                                <a href="?/asignacion/despachar_distribuidor/<?= $asignacion['id_empleado']; ?>" data-toggle="tooltip" data-title="Despachar al repartidor"><i class="glyphicon glyphicon-send"></i></a>
                            <?php 
                            }
                        }else{ 
                            //echo 
                            $asigQuery="SELECT  *
                                        FROM    inv_asignaciones_clientes ax  
                                        WHERE distribuidor_id='".$asignacion['id_empleado']."' AND fecha_hora_salida IN (
                                            SELECT  MAX(fecha_hora_salida)as fecha_hora_salida2
                                            FROM    inv_asignaciones_clientes ax  
                                            WHERE distribuidor_id='".$asignacion['id_empleado']."'  
                                        ) 
                                        GROUP BY distribuidor_id
                                        ";

                            $asigQueryTxt = $db->query($asigQuery)->fetch_first();

                            //echo $asigQueryTxt['fecha_hora_salida']." *** ";
                            //echo $asigQueryTxt['fecha_hora_liquidacion'];
                            
                            if ($permiso_asignacion_ver && $asigQueryTxt['fecha_hora_liquidacion']=="0000-00-00 00:00:00") { ?>
                                <a href="?/asignacion/asignacion_ver/<?= $asignacion['id_empleado']; ?>" data-toggle="tooltip" data-title="Ver ruta"><i class="glyphicon glyphicon-search"></i></a>
                            <?php 
                            } 
                            if( ($permiso_imprimir1 || $permiso_imprimir2) && $asigQueryTxt['fecha_hora_liquidacion']=="0000-00-00 00:00:00") { ?>
                                <a href="?/asignacion/asignacion_imprimir2/<?= $asignacion['id_empleado']; ?>" target="_blank" data-toggle="tooltip" data-title="Hoja de salida"><i class="glyphicon glyphicon-print"></i></a>
                                <a href="?/asignacion/asignacion_imprimir1/<?= $asignacion['id_empleado']; ?>" target="_blank" data-toggle="tooltip" data-title="Notas de venta"><i class="glyphicon glyphicon-list"></i></a>
                            <?php 
                            } 
                            if ($permiso_activar) { 
                                if ($asignacion['fecha_validar'] != date('Y-m-d') && true) { 
                                    if ($asignacion['registros'] != 0 || $asigQueryTxt['fecha_hora_liquidacion']=="0000-00-00 00:00:00") { 
                                        if ($permiso_activar_cerrar) { 
                                    
                                        //echo $asignacion['registros']." || ".$asigQueryTxt['fecha_hora_liquidacion']; 

                                        ?>
                                            <a href="?/asignacion/asignacion_activar2/<?= $asignacion['id_empleado']; ?>" class="text-info" data-toggle="tooltip" data-title="Cerrar distribucion" data-activar="true"><i class="glyphicon glyphicon-unchecked"></i></a>
                                            <!--<a href="?/asignacion/asignacion_activar/<?= $asignacion['id_empleado']; ?>" class="text-danger" data-toggle="tooltip" data-title="Cerrar distribucion (limpiar)" data-activar="true"><i class="glyphicon glyphicon-unchecked"></i></a>-->
                                        <?php
                                        }
                                    }
                                } else {
                                    
                                    
                                    //echo $asignacion['fecha'].' != '.date('Y-m-d');
                                    
                                    
                                ?>
                                    <a href="?/asignacion/imprimir3/<?= $asignacion['id_empleado']; ?>" class="text-success" target="_blank" data-toggle="tooltip" data-title="Imprimir liquidaci贸n"><i class="glyphicon glyphicon-print"></i></a>
                                    <?php
                                    if($permiso_activar_reabrir){
                                    ?>
                                        <a href="?/asignacion/asignacion_activar3/<?= $asignacion['id_empleado']; ?>" class="text-success" data-toggle="tooltip" data-title="Entrega realizada"><i class="glyphicon glyphicon-check"></i></a>
                                    <?php
                                    }
                                    ?>
                                <?php 
                                } 
                            } 
                        } 
                        ?>
                        </td>
                    </tr>
                <?php } // } ?>
            </tbody>
        </table>
    <?php } else { ?>

        <div class="alert alert-danger" role="alert">
            <strong>Advertencia!</strong>
            <p>No existen registros en la base de datos, prueba cambiando las fechas.</p>
        </div>
    <?php } ?>
</div>

<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/jquery.base64.js"></script>
<script src="<?= js; ?>/pdfmake.min.js"></script>
<script src="<?= js; ?>/vfs_fonts.js"></script>
<script src="<?= js; ?>/jquery.dataFilters.min.js"></script>
<script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>

<script>
    $(function() {
        <?php if ($asignaciones) { ?>
            var table = $('#table').DataFilter({
                filter: false,
                name: 'asignaciones',
                reports: 'excel|word|pdf|html'
            });
        <?php } ?>
        
        <?php if($id_externo !=0){ ?>
            imprimir_hoja_salida(<?= $id_externo ?>);
    	<?php } ?>
    });
    
    <?php if($id_externo !=0){ ?>
        function imprimir_hoja_salida(nota) {
    		bootbox.confirm('¿Desea imprimir hoja de salida?', function(result) {
    			if (result) {
    		        $.open('?/asignacion/asignacion_imprimir2/' + nota, true);
    		        imprimir_notas_venta(nota);
    			}
    			else{
    	    		imprimir_notas_venta(nota);
    			}
    		});
    	}
    	
        function imprimir_notas_venta(nota) {
    		bootbox.confirm('¿Desea Imprimir las notas de venta?', function(result) {
    			if (result) {
            		window.open('?/asignacion/asignacion_imprimir1/' + nota, true);
    			}
    		});
    	}
	<?php } ?>
</script>

<?php require_once show_template('footer-advanced'); ?>