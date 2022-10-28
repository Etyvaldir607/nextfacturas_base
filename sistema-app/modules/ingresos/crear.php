<?php

// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

$gestion = date('Y');
$gestion_base = date('Y-m-d');
//$gestion_base = ($gestion - 16) . date('-m-d');
$gestion_limite = ($gestion + 16) . date('-m-d');

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

//verificamos si el rol es superusuaio
if ($_user['rol'] == 'Superusuario') {
    //obtiene los amacenes
    $Almacenes=$db->query('SELECT * FROM inv_almacenes')->fetch();
}else{
    $id_almacen_usuario = $_user['almacen_id'];
    //obtiene el almacen
    $Almacenes = ($id_almacen_usuario)? $db->query('SELECT * FROM inv_almacenes where id_almacen =' . $id_almacen_usuario)->fetch():0;
}

// Obtiene los proveedores
$proveedores = $db->select('id_proveedor, proveedor as nombre_proveedor')
                  ->from('inv_proveedores')
                  ->where('tipo',"producto")
                  ->group_by('proveedor')
                  ->order_by('proveedor asc')
                  ->fetch();

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_listar = in_array('listar', $permisos);

?>
<?php require_once show_template('header-empty'); ?>
<style>
    .table-xs tbody {
        font-size: 12px;
    }

    .input-xs {
        height: 22px;
        padding: 1px 5px;
        font-size: 12px;
        line-height: 1.5;
        border-radius: 3px;
    }
</style>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <span class="glyphicon glyphicon-option-vertical"></span>
                    <strong>Ingresos</strong>
                </h3>
            </div>
            <div class="panel-body">
                <div class="col-sm-8 hidden-xs">

                </div>
                <div class="col-sm-4 hidden-xs  text-right">
                    <div class="form-check form-check-inline">
                        <label class="form-check-label" for="inlineCheckbox1">Busqueda de Productos</label>
                        <input class="form-check-input" type="checkbox" id="inlineCheckbox1" onchange='sidenav()' checked>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row" id='ContenedorF' data-formato="<?= strtoupper($formato_textual); ?>" data-mascara="<?= $formato_numeral; ?>" data-gestion="<?= date_decode($gestion_base, $_institution['formato']); ?>">
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <span class="glyphicon glyphicon-list"></span>
                    <strong>Datos del ingreso</strong>
                </h3>
            </div>
            <div class="panel-body">
                <div class="alert alert-info">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>Advertencia!</strong>
                    <ul>
                        <li>Para un mejor control del ingreso de productos se recomienda escribir una pequeña descripción acerca de la compra.</li>
                        <li>La moneda con la que se esta trabajando es <?= escape($moneda); ?>.</li>
                        <li>Los stocks que se muestra en la búsqueda de productos son del almacén principal.</li>
                    </ul>
                </div>
                <form method="post" action="?/ingresos/guardar" id="formulario" class="form-horizontal">
                    <div class='form-group'>
                        <label for='almacen1' class='col-sm-4 control-label'>Almacen:</label>
                        <div class='col-sm-8'>
                            <select name='almacen_id' id='almacen' class='form-control text-uppercase' data-validation="required">
                                <option value=''>Buscar</option>
                                <?php  foreach($Almacenes as $Fila=>$Almacen):  ?>
                                    <?php if ($AlmacenesAux['almacen_id_s']): ?>
                                        <option value='<?=$Almacen['id_almacen']?>' <?php if($AlmacenesAux['almacen_id_s']):echo '';endif;?>>
                                            <?=$Almacen['almacen'] . (($Almacen['principal'] == 'S') ? ' (principal)' : (($Almacen['especial']=='si') ? ' (especial)' : ''))?></option> 
                                    <?php endif ?>
                                    <?php if ($Almacen['id_almacen']): ?>
                                        <option value='<?=$Almacen['id_almacen']?>' <?php if($Almacen['id_almacen']):echo '';endif;?>>
                                            <?=$Almacen['almacen'] . (($Almacen['principal'] == 'S') ? ' (principal)' : (($Almacen['especial']=='si') ? ' (especial)' : ''))?></option> 
                                    <?php endif ?>
                                <?php  endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class='form-group'>
                        <label for='almacen2' class='col-sm-4 control-label'>Proveedor:</label>
                        <div class='col-sm-8'>
                            <select name="nombre_proveedor" id="proveedor" class="form-control" data-validation="required letternumber length" data-validation-allowing="-.#()| " data-validation-length="max100" onchange="get_proveedor();">
                                <option value="">Buscar</option>
                                <?php foreach ($proveedores as $elemento) { ?>
                                    <option value="<?= escape($elemento['id_proveedor']."|".$elemento['nombre_proveedor']); ?>"><?= escape($elemento['nombre_proveedor']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class='form-group'>
                        <label for='almacen2' class='col-sm-4 control-label'>Nro. de Documento:</label>
                        <div class='col-sm-8'>
                            <input type="text" name="nro_facturag" id="nro_facturag" class="form-control" data-validation="required letternumber length" data-validation-allowing="-.#()| " data-validation-length="max20">
                        </div>
                    </div>
                    <div class='form-group'>
                        <label for='almacen2' class='col-sm-4 control-label'>IVA:</label>
                        <div class='col-sm-8'>
                            <select name="IVAg" id="IVAg" class="form-control" data-validation="required letternumber length" data-validation-allowing="-.#()| " data-validation-length="max100">
                                <option value="no">Sin IVA</option>
                                <option value="si">Con IVA</option>
                            </select>
                        </div>
                    </div>
                    <div class='form-group'>
                        <label for='almacen2' class='col-sm-4 control-label'>Fecha Documento:</label>
                        <div class='col-sm-8'>
                            <input type="date" name="fecha_factura" id="fecha_factura" value="<?= date('Y-m-d') ?>" class="form-control" autocomplete="off" data-validation-format="<?= $formato_textual; ?>" data-validation="required">  
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion" class="col-sm-4 control-label">Descripción:</label>
                        <div class="col-sm-8">
                            <textarea name="descripcion" id="descripcion" class="form-control" autocomplete="off" data-validation="letternumber" data-validation-allowing="+-/.,:;#º()\n " data-validation-optional="true"></textarea>
                        </div>
                    </div>
                    <div class=" margin-none">
                        <table id="compras" class="table table-bordered table-condensed table-striped table-hover table-xs margin-none">
                            <thead>
                                <tr class="active">
                                    <th class="text-nowrap">Código</th>
                                    <th class="text-nowrap">Nombre</th>
                                    <th class="text-nowrap">F. vencimiento</th>
                                    <th class="text-nowrap hidden">Nro. DUI</th>
                                    <th class="text-nowrap hidden">Contenedor</th>
                                    <th class="text-nowrap">Lote</th>
                                    <th class="text-nowrap hidden">Nro. documento</th>
                                    <th class="text-nowrap">Cantidad</th>
                                    <th class="text-nowrap">Costo</th>
                                    <th class="text-nowrap">Importe</th>
                                    <th class="text-nowrap text-center"><span class="glyphicon glyphicon-trash"></span></th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr class="active">
                                    <th class="text-nowrap text-right" colspan="6">Importe total <?= escape($moneda); ?></th>
                                    <th class="text-nowrap text-right" data-subtotal="">0.00</th>
                                    <th class="text-nowrap text-center"><span class="glyphicon glyphicon-trash"></span></th>
                                </tr>
                            </tfoot>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="form-group">
                        <div class="col-xs-12">
                            <input type="text" name="nro_registros" value="0" class="translate" tabindex="-1" data-compras="" data-validation="required number" data-validation-allowing="range[1;50]" data-validation-error-msg="Debe existir como mínimo 1 producto y como máximo 50 productos">
                            <input type="text" name="monto_total" value="0" class="translate" tabindex="-1" data-total="" data-validation="required number" data-validation-allowing="range[0.01;10000000.00],float" data-validation-error-msg="El costo total de la compra debe ser mayor a cero y menor a 10.000.000,00">
                        </div>
                    </div>
                    <!-- <div class="form-group"> -->
                    
                    <!--<div class="col-xs-6 text-left">-->
                    <!--    <label for="almacen" class="col-md-12 text-left">Forma de pago:</label>-->
                    <!--    <div class="col-md-12 right">-->
                    <!--        <div class="row">-->
                    <!--            <div class="col-md-7">-->
                    <!--                <select name="tipo_pago" id="tipo_pago" class="form-control">-->
                    <!--                    <option value="EFECTIVO">Efectivo</option>-->
                    <!--                    <option value="CHEQUE">Cheque</option>                                -->
                    <!--                    <option value="TRANSFERENCIA">Transferencia</option>-->
                    <!--                    <option value="TARJETA">Tarjeta</option>                                -->
                    <!--                </select>-->
                    <!--            </div>-->
                    <!--            <div class="col-md-5">-->
                    <!--                <input type="text" name="nro_pago" placeholder="Nro documento" class="form-control" autocomplete="off" data-validation="number" aria-label="..." data-validation-optional="true">-->
                    <!--            </div>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--</div>-->
                    
                    <div class="form-group">
                        <label  class="col-md-4 control-label">Tipo de pago:</label>
                        <div class="col-md-8">
                            <select name="forma_pago" id="forma_pago" class="col-md-4 form-control" data-validation="required number" onchange="set_plan_pagos()">
                                <option value="1">Contado</option>
                                <option value="2">Plan de Pagos</option>
                                <option value="3">Pago Anticipado</option>
                            </select>
                        </div>    
                    </div>    
                
                    <div id="al_contado" style="display:block;">
                        <div class="form-group">
        					<label for="categoria_id" class="col-md-4 control-label">Tipo de Transaccion:</label>
        					<div class="col-md-8">
        						<select name="tipo_pago" id="tipo_pago" class="form-control" data-validation="required">
        							<option value=""> </option>
        							<option value="cheque">Cheque</option>
        							<option value="transferencia">Transferencia</option>
        						</select>
        					</div>
        				</div>
        				<div class="form-group">
        					<label for="categoria_id" class="col-md-4 control-label">Cuenta Bancaria:</label>
        					<div class="col-md-8">
        						<select name="banco_id" id="banco_id" class="form-control" data-validation="required" onchange="get_banco();">
        							<option value=""></option>
        							<?php 
        							// Obtiene el modelo categorias
                                    $bancos = $db->from('inv_bancos')
                                                 ->order_by('banco')
                                                 ->fetch();
                                    
                                    foreach ($bancos as $elemento) { ?>
        								<option value="<?= $elemento['id_banco']; ?>"><?= $elemento['banco']." - ".$elemento['cuenta'] ?></option>
        							<?php } ?>
        						</select>
        
        						<?php 
        						    foreach ($bancos as $elemento) { 
            							$movimientos = $db->query(" SELECT ifnull(SUM(monto_deposito),0) as monto_deposito, ifnull(SUM(monto_deposito2),0) as monto_deposito2
                                                					FROM(
                                                						SELECT ifnull(SUM(d.monto_deposito),0) as monto_deposito, 0 as monto_deposito2 
                                                						FROM inv_deposito d
                                                						WHERE banco='".$elemento['id_banco']."'
                                                						
                                                						UNION
                                        
                                                						SELECT 0 as monto_deposito, ifnull(SUM(pd.monto),0) as monto_deposito2
                                                						FROM inv_pagos p
                                                						LEFT JOIN inv_pagos_detalles pd ON id_pago=pago_id
                                                						WHERE   p.tipo='Ingreso' 
                                                						        AND banco_id='".$elemento['id_banco']."'
                                                					)x
                                                				")->fetch_first();
                                    ?>
        							<span id="banco_<?= $elemento['id_banco']; ?>" style="display:none;"><?= ($movimientos['monto_deposito']-$movimientos['monto_deposito2']); ?></span>
        						<?php } ?>
        					</div>
        				</div>
        				<div class="form-group">
        					<label for="nombre" class="col-md-4 control-label">Numero de documento:</label>
        					<div class="col-md-8">
        						<input type="text" value="" name="nro_doc" id="nombre" class="form-control" data-validation="required letternumber length" data-validation-allowing='-+/.,:;#&º"()¨ ' data-validation-length="max100">
        					</div>
        				</div>
        				<div class="form-group">
        					<label for="nombre" class="col-md-4 control-label">Glosa:</label>
        					<div class="col-md-8">
        						<input type="text" value="" name="observacion" id="observacion" class="form-control" data-validation="required letternumber length" data-validation-allowing='-+/.,:;#&º"()¨ ' data-validation-length="max100">
        					</div>
        				</div>
                    </div>

                    <div id="plan_de_pagos" style="display:none;">
                        <div class="form-group">
                            <label for="almacen" class="col-md-4 control-label">Nro Cuotas:</label>
                            <div class="col-md-8">
                                <input type="text" value="1" id="nro_cuentas" name="nro_cuentas" class="form-control text-right" autocomplete="off" data-cuentas="" data-validation="required number" data-validation-allowing="range[1;360],int" data-validation-error-msg="Debe ser número entero positivo" onkeyup="set_cuotas()">
                            </div>
                        </div>
                        <table id="cuentasporpagar" class="table table-bordered table-condensed table-striped table-hover table-xs margin-none">
                            <thead>
                                <tr class="active">
                                    <th class="text-nowrap text-center col-xs-4">Detalle</th>
                                    <th class="text-nowrap text-center col-xs-4">Fecha</th>
                                    <th class="text-nowrap text-center col-xs-4">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for($i=1;$i<=36;$i++){ ?>
                                    <tr class="active cuotaclass">
                                        <?php if($i==1){ ?>
                                            <td class="text-nowrap" valign="center">
                                                <div data-cuota="<?= $i ?>" data-cuota2="<?= $i ?>" class="cuota_div">Pago Inicial:</div>
                                            </td>
                                        <?php } else{ ?>
                                            <td class="text-nowrap" valign="center">
                                                <div data-cuota="<?= $i ?>" data-cuota2="<?= $i ?>" class="cuota_div">Cuota <?= $i ?>:</div>
                                            </td>
                                        <?php } ?>
                                        <td>
                                            <div data-cuota="<?= $i ?>" class="cuota_div">
                                                <div class="col-sm-12">
                                                    <input type="date" id="inicial_fecha_<?= $i ?>" name="fecha[]" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" class="form-control" autocomplete="off" data-validation-format="<?= $formato_textual; ?>" onchange="javascript:change_date(<?= $i ?>);" <?php if($i==1){ ?> data-validation="required" <?php } ?> onblur="javascript:change_date(<?= $i ?>);" 
                                                    <?php // if($i==1){ disabled="disabled"?>  <?php // } ?>
                                                    >
                                                </div>
                                            </div>
                                        </td>
                                        <td><div data-cuota="<?= $i ?>" class="cuota_div"><input type="text" value="0" name="cuota[]" class="form-control text-right monto_cuota" maxlength="7" autocomplete="off" data-montocuota="" data-validation-allowing="range[0.01;10000000.00],float" data-validation-error-msg="Debe ser número decimal positivo" onchange="javascript:calcular_cuota('<?= $i ?>');"></div></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <tr class="active">
                                    <th class="text-nowrap text-center" colspan="2">Importe total <?= escape($moneda); ?></th>
                                    <th class="text-nowrap text-right" data-totalcuota="">0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                        <br>
                    </div>

                    <div id="anticipado" style="display:none;">
                        <div class="form-group">
        					<label for="categoria_id" class="col-md-4 control-label">Pago anticipado:</label>
        					<div class="col-md-8">
        						<?php 
        						foreach ($proveedores as $Fila => $Proveedor){
                                ?>    
            						<select name="pago_id_<?= $Proveedor['id_proveedor'] ?>" id="pago_id_<?= $Proveedor['id_proveedor'] ?>" class="form-control pago_id" data-validation="required" 
            						        onchange="get_pago(<?= $Proveedor['id_proveedor'] ?>);" style="display:none;">
            							<option value=""></option>
            							<?php    
            							// Obtiene el modelo categorias
                                        $bancos = $db->query('  SELECT id_pago, pd.observacion, pd.monto, pd.tipo_pago, pd.nro_pago, b.banco, b.cuenta
                                                                FROM inv_pagos
                                                                INNER JOIN inv_pagos_detalles pd ON id_pago=pago_id
                                                                INNER JOIN inv_bancos b ON b.id_banco=banco_id
                                                                INNER JOIN inv_ingresos i ON i.id_ingreso=movimiento_id AND i.tipo="Otros"
                                                                WHERE i.proveedor_id="'.$Proveedor['id_proveedor'].'"
                                                                ')
                                                     ->fetch();
                                        
                                        foreach ($bancos as $elemento) { ?>
            								<option value="<?= $elemento['id_pago']; ?>"><?= $elemento['observacion'] ?></option>
            							<?php } ?>
            						</select>
            						
            						<?php foreach ($bancos as $elemento) { ?>
        								<input type="hidden" id="obs_<?= $elemento['id_pago']; ?>" value="<?= $elemento['observacion'] ?>">
        								<input type="hidden" id="nro_<?= $elemento['id_pago']; ?>" value="<?= $elemento['nro_pago'] ?>">
        								<input type="hidden" id="tipo_<?= $elemento['id_pago']; ?>" value="<?= $elemento['tipo_pago'] ?>">
        								<input type="hidden" id="cuenta_<?= $elemento['id_pago']; ?>" value="<?= $elemento['banco']." - ".$elemento['cuenta'] ?>">
        								<input type="hidden" id="monto_<?= $elemento['id_pago']; ?>" value="<?= $elemento['monto'] ?>">
        							<?php } ?>

            					<?php 
            					} 
            					?>	
        					</div>
        				</div>
        				
                        <div class="form-group">
        					<label for="categoria_id" class="col-md-4 control-label">Tipo de Transaccion:</label>
        					<div class="col-md-8">
        						<p class="tipoo" style="margin-top:9px;"></p>
        					</div>
        				</div>
        				<div class="form-group">
        					<label for="categoria_id" class="col-md-4 control-label">Cuenta Bancaria:</label>
        					<div class="col-md-8">
        						<p class="nrocta" style="margin-top:9px;"></p>
        					</div>
        				</div>
        				<div class="form-group">
        					<label for="nombre" class="col-md-4 control-label">Numero de documento:</label>
        					<div class="col-md-8">
        						<p class="nrodoc" style="margin-top:9px;"></p>
        					</div>
        				</div>
        				<div class="form-group">
        					<label for="categoria_id" class="col-md-4 control-label">Monto:</label>
        					<div class="col-md-8">
        						<p class="montooo" style="margin-top:9px;"></p>
        					</div>
        				</div>
        				<div class="form-group">
        					<label for="nombre" class="col-md-4 control-label">Glosa:</label>
        					<div class="col-md-8">
        						<p class="obsss" style="margin-top:9px;" class=" control-label"></p>
        					</div>
        				</div>
                    </div>
                    
                    <div class="col-xs-12 text-right">
                        <button type="submit" class="btn btn-primary" onmouseup="SetGuardar(event);">
                            <span class="glyphicon glyphicon-floppy-disk"></span>
                            <span>Guardar</span>
                        </button>
                        <button type="reset" class="btn btn-default" onclick="Limpiar();">
                            <span class="glyphicon glyphicon-refresh"></span>
                            <span>Restablecer</span>
                        </button>
                        <a href="?/ingresos/listar" target="_self">
                        <button type="button" class="btn btn-danger">
                            <span class="glyphicon glyphicon-trash"></span>
                            <span>Salir</span>
                        </button>
                        </a>
                    </div>
                    <!-- </div> -->
                </form>
            </div>
        </div>
    </div>
    <div class='col-md-6'>
        <div class='panel panel-info'>
            <div class='panel-heading'>
                <h3 class='panel-title'>
                    <span class='glyphicon glyphicon-option-vertical'></span>
                    <strong>Busqueda de Productos</strong>
                </h3>
            </div>
            <div class='panel-body'>
                <div class='form-horizontal'>
                    <div class='form-group'>
                        <label for='search' class='col-sm-4 control-label'>Buscar Producto:</label>
                        <div class='col-sm-8'>
                            <input type='text' id='search' onkeyup='buscarProductos(this.value)' class='form-control text-uppercase' autocomplete='off'>
                        </div>
                    </div>
                    <div class='table-responsive margin-none'>
                        <table id='productos' class='table table-bordered table-condensed table-striped table-hover margin-none'>
                            <thead>
                                <tr class='active'>
                                    <th class='text-nowrap text-center width-collapse hidden'>#</th>
                                    <th class='text-nowrap text-center width-collapse'>CÓDIGO</th>
                                    <th class='text-nowrap text-center'>PRODUCTO</th>
                                    <th class='text-center width-collapse' width='8%'>CANTIDAD</th>
                                    <th class='text-nowrap text-center hidden'>UNIDAD</th>
                                    <th class='text-nowrap text-center '>COSTO</th>
                                    <th class='text-nowrap text-center width-collapse'></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/selectize.min.js"></script>
<script src="<?= js; ?>/bootstrap-notify.min.js"></script>
<script src="<?= js; ?>/buzz.min.js"></script>
<script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>
<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/number_format.js"></script>
<script>

SwGuardar=false;
    
$("[data-cantidad]:text:visible:first").focus();

function buscarProductos(cadena){
    let id_almacen=document.getElementById('almacen').value,
        id_proveedor=document.getElementById('proveedor').value,
        productos=document.getElementById('productos');
    cadena=cadena.trim();
    //if(id_almacen!='' && cadena!==''){
    if(id_almacen!=''){
        $.ajax({
            data: {cadena,id_almacen,id_proveedor},
            type: 'POST',
            dataType: 'json',
            url: '?/ingresos/servicio_buscar',
        })
        .done(function(data,textStatus,jqXHR){
            productos.children[1].innerHTML='';
            data.forEach((Dato,index)=>{
                productos.children[1].innerHTML+=`<tr>
                        <td class="hidden">${index+1}</td>
                        <td data-codigo="${Dato['id_producto']}">${Dato['codigo']}</td>
                        <td data-nombre="${Dato['id_producto']}">${Dato['nombre_factura']}</td>
                        <td>${Dato['total']}</td>
                        <td class="hidden">${Dato['unidad']}</td>
                        <td data-precio="${Dato['id_producto']}">${(Dato['factura_v'] == true)?parseFloat(Dato['costo']-(Dato['costo']*0.13)).toFixed(2):parseFloat(Dato['costo']).toFixed(2)}</td>
                        <td>
                            <button class='btn btn-success btn-sm' data-comprar="${Dato['id_producto']}" onclick='adicionar_producto("${Dato['id_producto']}")'>
                                <span class='glyphicon glyphicon-plus'></span>
                            </button>
                        </td>
                    </tr>`;                        
            });
        })
        .fail(function(e) {
            console.log(e)
        });
    }
    else if(id_almacen===''){
        $.notify({
            message: 'Debe Seleccionar un Almacen'
        }, {
            type: 'warning'
        });
    }
    else
        productos.children[1].innerHTML='';
}

var $ventas = $('#ventas tbody');
    $ventas.find('[data-cantidad]').on('click', function () {
    $(this).select();
});


$("#almacen").on('change',function() {        
    var id_almacen = $("#almacen").val();
    var id_proveedor = $("#proveedor").val();
    var dato = $('#search').val();
    $("#search").val("");    
    $('#compras').children('tbody').html('');
    var valor = 'a';
    buscarProductos(valor);  
});
$("#proveedor").on('change',function() {        
    var id_almacen = $("#almacen").val();
    var id_proveedor = $("#proveedor").val();
    var dato = $('#search').val();
    $("#search").val("");    
    $('#compras').children('tbody').html('');
    var valor = 'a';
    buscarProductos(valor);  
});

$(function() {
    var $formulario = $('#formulario');
		
	$('[data-comprar]').on('click', function() {
        adicionar_producto($.trim($(this).attr('data-comprar')));
    });
    
    $('#proveedor').selectize({
        persist: false,
        createOnBlur: true,
        create: true,
        onInitialize: function() {
            $('#proveedor').css({
                display: 'block',
                left: '-10000px',
                opacity: '0',
                position: 'absolute',
                top: '-10000px'
            });
        },
        onChange: function() {
            $('#proveedor').trigger('blur');
        },
        onBlur: function() {
            $('#proveedor').trigger('blur');
        }
    });

    $('#almacen').selectize({
        persist: false,
        onInitialize: function() {
            $('#almacen').css({
                display: 'block',
                left: '-10000px',
                opacity: '0',
                position: 'absolute',
                top: '-10000px'
            });
        },
        onChange: function() {
            $('#almacen').trigger('blur');
        },
        onBlur: function() {
            $('#almacen').trigger('blur');
        }
    });

    $(':reset').on('click', function() {
        $('#proveedor')[0].selectize.clear();
        $('#almacen')[0].selectize.clear();
    });

    $.validate({
		form: '#formulario',
		modules: 'basic',
		onSuccess: function() {
			//guardar_nota();
		}
	});

    $('#formulario').on('reset', function() {
        $('#compras tbody').find('[data-importe]').text('0.00');
        calcular_total();
    });

    $('#formulario :reset').trigger('click');

    set_cuotas();
});

function SetGuardar(event){
    SwGuardar=true;
}

function get_pago(id_proveedor){
    pago=$('#pago_id_'+id_proveedor).val();

    $('.obsss').html( $('#obs_'+pago).val() );
    $('.nrocta').html( $('#cuenta_'+pago).val() );
    $('.nrodoc').html( $('#nro_'+pago).val() );
    $('.montooo').html( $('#monto_'+pago).val() );
    $('.tipoo').html( $('#tipo_'+pago).val() );
}

function get_proveedor(){
    id_proveedor=$("#proveedor").val();
    arrTerminos = id_proveedor.split('|');
    $('.pago_id').css({'display':'none'});
    $('#pago_id_'+arrTerminos[0]).css({'display':'block'});
}

function adicionar_producto(id_producto) {
    var d = new Date();
    const seg = '_' + d.getMilliseconds();
    var $producto = $('[data-producto=' + id_producto + ']');
    var $cantidad = $producto.find('[data-cantidad]');
    var $compras = $('#compras tbody');
    var codigo = $.trim($('[data-codigo=' + id_producto + ']').text());
    var nombre = $.trim($('[data-nombre=' + id_producto + ']').text());
    var precio = $.trim($('[data-precio=' + id_producto + ']').text());
    var plantilla = '';
    var cantidad;
    var formato = $('[data-formato]').attr('data-formato');

    if ($producto.size()) {
        cantidad = $.trim($cantidad.val());
        cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
        cantidad = (cantidad < 10000000) ? cantidad + 1 : cantidad;
        $cantidad.val(cantidad).trigger('blur');
    } else {
        plantilla = '<tr class="active" data-producto="' + id_producto + seg + '">' +
            '<td class="text-nowrap"><input type="text" value="' + id_producto + '" name="productos[]" class="translate" tabindex="-1" data-validation="required number" data-validation-error-msg="Debe ser número">' + codigo + '</td>' +
            '<td>' + nombre + '</td>' +
            '<td><div class="row"><div class="col-xs-12"><input type="text" name="fechas[]" value="<?= date('Y/m/d'); ?>" class="form-control input-xs text-right" data-fecha="" data-validation="required "  data-validation-error-msg="Debe ingresar la fecha de vencimiento"></div></div></td>' +
            '<td><input type="text" value="" name="lotes[]" class="form-control input-xs text-right" maxlength="10" autocomplete="off" data-contenedor="" data-validation="required "  data-validation-error-msg="Debe ingresar el Lote" ></td>' +
            
            '<td><input type="text" value="1" name="cantidades[]" class="form-control input-xs text-right" maxlength="8" autocomplete="off" data-cantidad="" data-validation="required number" data-validation-error-msg="Debe ser número entero positivo" onkeyup="calcular_importe(\'' + id_producto + seg + '\')"></td>' +
            
            '<td><input type="text" value="' + precio + '" name="costos[]" class="form-control input-xs text-right" autocomplete="off" data-costo="" data-validation="required number" data-validation-allowing="rnge[0.01;10000000.00],float" data-validation-error-msg="Debe ser número decimal positivo" onkeyup="calcular_importe(\'' + id_producto + seg + '\')" onblur="redondear_importe(\'' + id_producto + seg + '\')"></td>' +
            
            '<td class="text-nowrap text-right" data-importe="">0.00</td>' +
            
            '<td class="text-nowrap text-center">' +
            '<button type="button" class="btn btn-xs btn-danger" data-toggle="tooltip" data-title="Eliminar producto" tabindex="-1" onclick="eliminar_producto(\'' + id_producto + seg + '\')"><span class="glyphicon glyphicon-remove"></span></button>' +
            '</td>' +
            '</tr>';

            // <div class="input-group">
            //         <div class="input-group-prepend">
            //             <div class="input-group-text">
            //             <input type='checkbox' name='factura_val[]' checked>
            //             </div>
            //         </div>
            //         <input type='text' value='' name='facturas[]' class='form-control input-xs text-right' maxlength='7' autocomplete='off' data-contenedor='' data-validation="required number" data-validation-error-msg='Debe ser número entero positivo'>
                // </div>
        $compras.append(plantilla);

        $compras.find('[data-cantidad], [data-costo]').on('click', function() {
            $(this).select();
        });

        $compras.find('[data-fecha]').datetimepicker({
            format: formato,
            minDate: '<?= date('Y-m-d') ?>'
        });

        $compras.find('[title]').tooltip({
            container: 'body',
            trigger: 'hover'
        });
    }

    calcular_importe(id_producto);
}

function eliminar_producto(id_producto) {
    bootbox.confirm('Está seguro que desea eliminar el producto?', function(result) {
        if (result) {
            $('[data-producto=' + id_producto + ']').remove();
            calcular_total();
        }
    });
}

function redondear_importe(id_producto) {
    var $producto = $('[data-producto=' + id_producto + ']');
    var $costo = $producto.find('[data-costo]');
    var costo;

    costo = $.trim($costo.val());
    costo = ($.isNumeric(costo)) ? parseFloat(costo).toFixed(2) : costo;
    $costo.val(costo);

    calcular_importe(id_producto);
}

function calcular_importe(id_producto) {
    var $producto = $('[data-producto=' + id_producto + ']');
    var $cantidad = $producto.find('[data-cantidad]');
    var $costo = $producto.find('[data-costo]');
    var $importe = $producto.find('[data-importe]');
    var cantidad, costo, importe;

    cantidad = $.trim($cantidad.val());
    cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
    costo = $.trim($costo.val());
    costo = ($.isNumeric(costo)) ? parseFloat(costo) : 0.00;
    importe = cantidad * costo;
    importe = importe.toFixed(2);
    $importe.text(importe);

    calcular_total();
}

function calcular_total() {
    var $compras = $('#compras tbody');
    var $total = $('[data-subtotal]:first');
    var $importes = $compras.find('[data-importe]');
    var importe, total = 0;

    $importes.each(function(i) {
        importe = $.trim($(this).text());
        importe = parseFloat(importe);
        total = total + importe;
    });

    $total.text(number_format(total ,2 , ',', '.'));
    $('[data-compras]:first').val($importes.size()).trigger('blur');
    $('[data-total]:first').val(total.toFixed(2)).trigger('blur');
    
    set_cuotas();
}

function sidenav() {
    let contenedor = document.getElementById('ContenedorF');
    if (contenedor.children[0].classList.contains('col-md-6')) {
        contenedor.children[0].classList.remove('col-md-6');
        contenedor.children[0].classList.add('col-md-12');
        contenedor.children[1].classList.add('hidden');
    } else {
        contenedor.children[0].classList.remove('col-md-12');
        contenedor.children[0].classList.add('col-md-6');
        contenedor.children[1].classList.remove('hidden');
    }
}
function factura(elemento, id_producto){
    // var id_producto = elemento.dataset.iva;
    // console.log(elemento);
    console.log($('#fact-' + id_producto));
   if(elemento.checked){
       console.log('si');
        $('#fact-' + id_producto).attr("data-validation","required number");
        $('#fv-' + id_producto).val("true");
    }
    else{
        console.log('no');
        $('#fact-' + id_producto).attr("data-validation","");
        $('#fv-' + id_producto).val("false");
    }
}

/****/
function set_cuotas() {
    // console.log($('#nro_cuentas').val());
        
    var cantidad = $('#nro_cuentas').val();
    var $compras = $('#cuentasporpagar tbody');
    
    $("#nro_plan_pagos").val(cantidad);

    if(cantidad>36){
        cantidad=36;
        $('#nro_cuentas').val("36")
    }
    for(i=1;i<=cantidad;i++){
        $('[data-cuota=' + i + ']').css({'height':'auto', 'overflow':'visible'});               
        $('[data-cuota2=' + i + ']').css({'margin-top':'10px;'});               
        $('[data-cuota=' + i + ']').parent('td').css({'height':'auto', 'border-width':'1px','padding':'5px'});              
    }
    for(i=parseInt(cantidad)+1;i<=36;i++){
        $('[data-cuota=' + i + ']').css({'height':'0px', 'overflow':'hidden'});             
        $('[data-cuota2=' + i + ']').css({'margin-top':'0px;'});                
        $('[data-cuota=' + i + ']').parent('td').css({'height':'0px', 'border-width':'0px','padding':'0px'});
    }
    set_cuotas_val();
    calcular_cuota(1000);
}
function set_cuotas_val() {
    nro=$('#nro_cuentas').val();
    
    valorG=parseFloat($('[data-total]:first').val());
    valor=valorG/nro;
    for(i=1;i<=nro;i++){
        if(i==nro){
            final=valorG-(valor.toFixed(1)*(i-1));
            $('[data-cuota=' + i + ']').children('.monto_cuota').val(final.toFixed(1)+"0");
        }else{
            $('[data-cuota=' + i + ']').children('.monto_cuota').val(valor.toFixed(1)+"0");
        }
    }       
}
function set_plan_pagos(){
    if($("#forma_pago").val()==1){
        $('#anticipado').css({'display':'none'});
        $('#plan_de_pagos').css({'display':'none'});
        $('#al_contado').css({'display':'block'});
    }
    if($("#forma_pago").val()==2){
        $('#anticipado').css({'display':'none'});
        $('#plan_de_pagos').css({'display':'block'});
        $('#al_contado').css({'display':'none'});
    }
    if($("#forma_pago").val()==3){
        $('#anticipado').css({'display':'block'});
        $('#plan_de_pagos').css({'display':'none'});
        $('#al_contado').css({'display':'none'});
    }
}
function calcular_cuota(x) {
    var cantidad = $('#nro_cuentas').val();
    var total = 0;
    
    for(i=1;i<=x && i<=cantidad;i++){
        importe=$('[data-cuota=' + i + ']').children('.monto_cuota').val();
        importe = parseFloat(importe);
        total = total + importe;
    }
    
    valorTotal=parseFloat($('[data-total]:first').val());
    if(nro>x){
        valor=(valorTotal-total)/(nro-x);
    }
    else{
        valor=0;
    }

    for(i=(parseInt(x)+1);i<=cantidad;i++){
        if(valor>=0){
            if(i==cantidad){
                valor=valorTotal-total;
                $('[data-cuota=' + i + ']').children('.monto_cuota').val(valor.toFixed(1)+"0");
            }
            else{
                $('[data-cuota=' + i + ']').children('.monto_cuota').val(valor.toFixed(1)+"0");
            }
            total = total + (valor.toFixed(1)*1);
        }
        else{
            $('[data-cuota=' + i + ']').children('.monto_cuota').val("0.00");
        }
    }   
    
    $('[data-totalcuota]').text( total.toFixed(2) );
    valor=parseFloat($('[data-total]:first').val());
    
    //alert(valor+" - - - "+total );

    if(valor==total.toFixed(2) ){
        $('[data-total-pagos]:first').val(1);
        $('[data-total-pagos]:first').parent('div').children('#monto_plan_pagos').attr("data-validation-error-msg",""); 
    }
    else{
        $('[data-total-pagos]:first').val(0);   
        $('[data-total-pagos]:first').parent('div').children('#monto_plan_pagos').attr("data-validation-error-msg","La suma de las cuotas es diferente al costo total « "+total.toFixed(2)+" / "+valor+" »");   
    }
}
function change_date(x){
    if($('#inicial_fecha_'+x).val()!=""){
        if(x<36){
            $('#inicial_fecha_'+(x+1)).removeAttr("disabled");
        }
    }   
    else{
        for(i=x;i<=35;i++){
            $('#inicial_fecha_'+(i+1)).val("");
            $('#inicial_fecha_'+(i+1)).attr("disabled","disabled");
        }
    }
}
function Limpiar(){
    $('#compras').children('tbody').html('');
}
function get_banco(){
    monto=parseFloat($('[data-total]:first').val());
    
    i=$('#banco_id').val();
    ix=parseInt($("#banco_"+i).html());

    if( (ix-monto)<0 ){
        alert("Cuenta Bancaria sin fondos");
    }
}
</script>
<?php require_once show_template('footer-empty'); ?>