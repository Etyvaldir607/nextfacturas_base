<?php
$id_detalle	= (isset($_POST['id_detalle'])) ? $_POST['id_detalle'] : array();

$venta = $db->select('  i.*, a.id_almacen, a.almacen, a.principal, e.nombres, e.paterno, e.materno, 
                        v.nombres nombresv, v.paterno paternov, v.materno maternov, cl.direccion, cl.cliente')
            ->from('inv_egresos i')
            ->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
            ->join('inv_clientes cl', 'i.cliente_id = cl.id_cliente', 'left')
            ->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')
            ->join('sys_empleados v', 'i.vendedor_id = v.id_empleado', 'left')
            ->where('id_egreso', $id_detalle[0])
            ->fetch_first();
                                        
// Obtiene los distribuidores
$distribuidores = $db->query("SELECT CONCAT(e.nombres, ' ', e.paterno, ' ', e.materno) as empleado, e.id_empleado
                              FROM sys_users u
                              LEFT JOIN sys_empleados e ON u.persona_id = e.id_empleado
                              LEFT JOIN inv_users_almacenes ON user_id=id_user
                              WHERE u.rol_id = 4 
                                    AND almacen_id='".$venta['id_almacen']."'
                            ")->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

$permiso_listar = true;

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading" data-venta="<?= $id_venta; ?>" data-servidor="<?= ip_local . name_project . '/factura.php'; ?>">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Detalles de la preventa</strong>
	</h3>
</div>
<div class="panel-body">
    <?php if ($permiso_listar) { ?>
        <div class="row">
            <div class="col-sm-8 hidden-xs">
                <div class="text-label">Para realizar una acci贸n hacer clic en los siguientes botones: </div>
            </div>
            <div class="col-xs-12 col-sm-4 text-right">

                <a href="?/asignacion/preventas_listar" class="btn btn-primary" ><i class="glyphicon glyphicon-arrow-left"></i><span class="hidden-xs"> Listar</span></a>
            </div>
        </div>
        <hr>
	<?php } ?>
	<?php if (isset($_SESSION[temporary])) { ?>
		<div class="alert alert-<?= (isset($_SESSION[temporary]['alert'])) ? $_SESSION[temporary]['alert'] : $_SESSION[temporary]['type']; ?>">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
			<strong><?= $_SESSION[temporary]['title']; ?></strong>
			<p><?= (isset($_SESSION[temporary]['message'])) ? $_SESSION[temporary]['message'] : $_SESSION[temporary]['content']; ?></p>
		</div>
		<?php unset($_SESSION[temporary]); ?>
	<?php } ?>

    <form action="?/asignacion/preventas_guardar" id="form_asignar" method="POST">
        <div class="row">
            <div class="col-md-5">
                <div class="panel panel-primary">
    				<div class="panel-heading">
    					<h3 class="panel-title"><i class="glyphicon glyphicon-user"></i> Asignaci贸n de la preventa</h3>
    				</div>
                    <div class="panel-body">
                        <div class="form-horizontal">
                            <input type="hidden" name="id_venta" value="<?= $id_venta ?>">
                            <div class="form-group">
                                <label class="col-md-3 control-label hidden">Nro. de movimiento:</label>
                                <div class="col-md-9">
                                    <input type="text" class="form-control hidden" value="<?= $venta['nro_movimiento'] ?>" name="nro_movimiento" id="nro_movimiento" readonly>
                                </div>
                            </div>
                            <div class="form-group" id="distro_group">
                                <label class="col-md-3 control-label">Distribuidor:</label>
                                <div class="col-md-9">
                                    <select class="form-control text-uppercase " name="id_distribuidor" id="id_distribuidor" data-validation="required number" onchange="setDistribuidor();"
                                            readonly data-validation-allowing="range[1;999999]">
                                        <option value="" selected disabled>Seleccione...</option>
                                        <?php 
                                        foreach($distribuidores as $distro) { 
                                            $distrix1 = $db->query('  SELECT *
                                                                            from      inv_asignaciones_clientes ac
                                                                            left join sys_empleados e ON distribuidor_id=id_empleado 
                                                                            where   ac.distribuidor_id="'.$distro['id_empleado'].'"
                                                                                    AND 
                                                                                    (
                                                                                        e.fecha_validar="'.date('Y-m-d').'"
                                                                                        OR 
                                                                                        fecha_hora_salida IN (  SELECT  MAX(fecha_hora_salida)as fecha_hora_salida
                                                                                                                FROM    inv_asignaciones_clientes ac  
                                                                                                                WHERE   distribuidor_id="'.$distro['id_empleado'].'" 
                                                                                                                        AND fecha_hora_liquidacion="0000-00-00 00:00:00" 
                                                                                                                        AND fecha_hora_salida!="0000-00-00 00:00:00" 
                                                                                                            )
                                                                                    )
                                                                        ')->fetch_first();
                                            if($distrix1){ ?>
                                                <option value="-1"><?= $distro['empleado']." (El repartidor no esta disponible)" ?></option>
                                            <?php }else{ ?>
                                                <option value="<?= $distro['id_empleado'] ?>"><?= $distro['empleado'] ?></option>
                                            <?php } ?>
                                        <?php } ?>
                                    </select>
                                    <span class="help-block form-error text-danger hidden" id="para_distro">El campo es requerido</span>
                                    
                                    <div>
                                    <input type="text" value="0" id="ix_distrib" data-validation="required number" data-validation-allowing="range[1;999999],int" data-validation-error-msg="No se puede asignar al distribuidor" style="opacity:0;">
                                    </div>
                                    
                                </div>
                            </div>
                            <div class="form-group hidden">
                                <label class="col-md-3 control-label">Fecha entrega:</label>
                                <div class="col-md-9">
                                    <input type="date" class="form-control " min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" name="fecha_entrega" id="fecha_entrega" data-validation="required" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label hidden">Empleado:</label>
                                <div class="col-md-9">
                                    <input  type="text" class="form-control text-uppercase hidden" value="<?= $_user['nombres'] . ' ' . $_user['paterno'] . ' ' . $_user['materno'] ?>" 
                                            name="empleado" id="empleado">
                                </div>
                            </div>
                            
                            <hr>
                            <div class="form-group">
                                <div class="col-xs-12 text-right">
                                    <button type="button" onclick="enviar_form()" class="btn btn-success">Guardar asignacion</button>
                                    <button type="reset" class="btn btn-default">Restablecer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="panel panel-primary">
    				<div class="panel-heading">
    					<h3 class="panel-title"><i class="glyphicon glyphicon-log-out"></i> Listado de preventas</h3>
    				</div>
    				<div class="panel-body">
    					<div class="form-horizontal">
    						
    						<?php
    						$fecha_habilitacion=explode(" ",$venta['fecha_habilitacion']);
                            $Aux=escape(date_decode($fecha_habilitacion[0], $_institution['formato']));
                            $Aux=$Aux." <small class='text-success'>".$fecha_habilitacion[1]."</small>";
                            ?>
    						
    						<div class="form-group">
    							<div class="table-responsive col-md-12">
            						<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
            							<thead>
                    					    <tr>
            							        <th>Cliente</th>
            							        <th>Direccion</th>
            							        <th>Nro Nota</th>
            							        <th>Monto total</th>
            							        <th>Observacion</th>
            							    </tr>
            						    </thead>
            						    <tbody>
            						        <?php
            						        foreach($id_detalle as $nro => $id_det){
            						            $id_venta = $id_det;
                                                
                                                // Obtiene la venta
                                                $venta = $db->select('  i.*, a.id_almacen, a.almacen, a.principal, e.nombres, e.paterno, e.materno, 
                                                                        v.nombres nombresv, v.paterno paternov, v.materno maternov, cl.direccion, cl.cliente')
                                                            ->from('inv_egresos i')
                                                            ->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
                                                            ->join('inv_clientes cl', 'i.cliente_id = cl.id_cliente', 'left')
                                                            ->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')
                                                            ->join('sys_empleados v', 'i.vendedor_id = v.id_empleado', 'left')
                                                            ->where('id_egreso', $id_venta)
                                                            ->fetch_first();
                                                ?>
                                                
                                                <input type="hidden" value="<?= $id_venta ?>" name="nro_egresos[]">
                                                
                                                <tr>
                                                    <td><?= $venta['cliente'] ?></td>
                							        <td><?= $venta['direccion'] ?></td>
                							        <td><?= $venta['nro_nota'] ?></td>
                							        <td style="text-align:right;"><?= $venta['monto_total'] ?></td>
            							            <td><?= $venta['descripcion_venta'] ?></td>
            							        </tr>    
                                                <?php    
            						        }
            						        ?>
            						    </tbody>
    							    </table>
    							</div>
    						</div>
    						
    					</div>
    				</div>
    			</div>
            </div>
            
        </div>
    </form>
</div>

<script src="<?= js; ?>/selectize.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script>
$(function() {
    $('#id_distribuidor').selectize({
        create: false,
        createOnBlur: false,
        maxOptions: 7,
        persist: false
    });

    $.validate({
        form: '#form_asignar',
        modules: 'basic'
    });

    $("#id_distribuidor").change(function(){
        if( $("#id_distribuidor").val() == "" ){
            $("#distro_group").addClass('has-error');
            $('#para_distro').removeClass('hidden');
        } else {
            $("#distro_group").addClass('has-success');
            $('#para_distro').addClass('hidden');
        }
    });

});

function enviar_form() {
    if( $("#id_distribuidor").val() == "" ){
        $("#id_distribuidor").addClass('error');
        $('#para_distro').removeClass('hidden');
    } else {
        $('#form_asignar').submit();
    }
}

function setDistribuidor(){
    id_distribuidor=$('#id_distribuidor').val();
    if(id_distribuidor==-1){
        alert("No se puede asignar al distribuidor");
    }
    $('#ix_distrib').val(id_distribuidor);
}
</script>
<?php require_once show_template('footer-advanced'); ?>