<?php

// Obtiene el id_venta
//$id_venta = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene la venta
$almacen = $db->select('i.*')->from('inv_almacenes i')->fetch();
///var_dump($almacen);exit();

// Verifica si existe el egreso
// if (!$almacen) {
// 	// Error 404
// 	require_once not_found();
// 	exit;
// }

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_editar = in_array('editar', $permisos);
$permiso_mostrar = in_array('mostrar', $permisos);
$permiso_reimprimir = in_array('reimprimir', $permisos);


?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Seleccione el Almacen</strong>
	</h3>
</div>
<div class="panel-body">
<!-- Inicio modal cliente -->
<?php if ($almacen) { ?>
<div class="">
	<div class="">

			<div >
            <?php foreach ($almacen as $value){?>
				<a href="?/notas/crear/<?= $value['id_almacen'];?>">
				<div class="row">
					<div class="col-sm-12">

						<!-- <div class="form-group">
							<label for="nit_ci">Almacen:</label>
							<?= $value['almacen']; ?>
						</div> -->

						<div class="col-xs-4 text-center">
							<div class="well lead">
								<label for="nit_ci">Almacen:</label>
								<?= $value['almacen']; ?>
							</div>
						</div>
					</div>
				</div>
				</a>
            <?php  } ?>
			</div>
	</div>
</div>
<?php } ?>
<!-- Fin modal cliente -->
</div>
<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/bootstrap-notify.min.js"></script>
<script>
</script>
<?php require_once show_template('footer-advanced'); ?>