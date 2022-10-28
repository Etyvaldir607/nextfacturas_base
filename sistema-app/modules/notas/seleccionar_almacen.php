<?php
// Obtiene los permisos
$permisos = explode(',', permits);
// Almacena los permisos en variables
$permiso_almacen = in_array('permiso_almacen', $permisos);
$permiso_mostrar = in_array('mostrar', $permisos);
$permiso_crear = in_array('crear', $permisos);

if(!$permiso_crear){
    header('Location: ?/notas/mostrar'); 
    //exit();
}

if($_user['rol_id'] == 1) {
	$almacen = $db->from('inv_almacenes')
			            ->where('especial','no')
						->fetch();
}else{
    $almacen = $db->query(" SELECT *
                            FROM inv_almacenes
    		                LEFT JOIN inv_users_almacenes ON almacen_id=id_almacen
    		                WHERE user_id='".$_SESSION[user]['id_user']."'")
    					->fetch();
}
?>
	<style>
		.panel-heading h2{
			text-align: center;
		}
		.panel-heading h2 span{
			font-size: 40px;
		}
	</style>

	<?php require_once show_template('header-empty'); ?>

	<div class="row">
		<div class="col-md-12">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">
						<span class="glyphicon glyphicon-list"></span>
						<strong>Seleccionar el punto de venta</strong>
					</h3>
				</div>

				<div class="panel-body">
				<?php if ($permiso_mostrar) : ?>
    				<p class="text-right">
    					<a href="?/notas/mostrar" class="btn btn-warning">Mis notas de venta</a>
    				</p>
				<?php endif ?>

					<div class="alert alert-info">
						<button type="button" class="close" data-dismiss="alert">&times;</button>
						<strong>Advertencia!</strong>
						<ul>
							<li>Elija el punto de venta desde el cual hara la compra.</li>
						</ul>
					</div>
						<?php
						foreach($almacen as $nro => $almacenX){
						?>
						<div class="col-md-4">
							<a class="seleccionarAlmacen" href="?/notas/crear/<?php echo $almacenX["id_almacen"]; ?>">
								<div class="panel panel-default">
								<div class="panel-heading">
									<h2 class="panel-title">
										<span class="glyphicon glyphicon-list"></span>
										<br>
										<br>
										<?php echo $almacenX["almacen"]; ?>
									</h2>
								</div>
								</div>
							</a>
						</div>
						<?php
						}
						?>
				</div>
			</div>
		</div>
	</div>
	<script>
		$(document).ready(function(){
			$('.seleccionarAlmacen').hover(
				function(){ $(this).children('div').addClass("panel-primary"); $(this).children('div').removeClass("panel-default"); },
				function(){ $(this).children('div').addClass("panel-default"); $(this).children('div').removeClass("panel-primary"); }
			);
		});
	</script>

<?php require_once show_template('footer-empty'); ?>