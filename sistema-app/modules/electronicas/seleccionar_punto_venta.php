<?php

//$fecha = date("Y-m-d\TH:i:s.v");


// function calculaDigitoMod11(string $cadena, int $numDig, int $limMult, bool $x10)
// {
// 	$cadenaSrc = $cadena;
	
// 	$mult = $suma = $i = $n = $dig = 0;
	
// 	if (!$x10) $numDig = 1;
	
// 	for($n = 1; $n <= $numDig; $n++) 
// 	{
// 		$suma = 0;
// 		$mult = 2;
// 		for($i = strlen($cadena) - 1; $i >= 0; $i--) 
// 		{
// 			$cadestr = $cadena[$i];//substr($cadena, $i, $i + 1);
// 			$intNum = (int)($cadestr);
// 			//echo 'cadestr: ', $cadestr, "\n";
// 			//echo 'intNum: ', $intNum, "\n";
// 			$suma += ($mult * $intNum);
// 			if(++$mult > $limMult) $mult = 2;
// 		}
// 		if ($x10) 
// 		{
// 			$dig = (($suma * 10) % 11) % 10;
// 		}
// 		else 
// 		{
// 			$dig = $suma % 11;
// 		}
// 		if ($dig == 10) 
// 		{
// 			$cadena .= "1";
// 		}
// 		if ($dig == 11) 
// 		{
// 			$cadena .= "0";
// 		}
// 		if ($dig < 10) {
			
// 			//$cadena .= String.valueOf(dig);
// 			$cadena .= $dig;
// 		}
// 		//echo "Dig: ", $dig, "\n";
// 	}
	
// 	$modulo = substr($cadena, strlen($cadena) - $numDig, strlen($cadena));
	
// 	//echo $cadena, "\n";
// 	//echo 'Calculado modulo 11: ', $cadenaSrc, " => ", $modulo, "\n";
	
// 	return $modulo;
// }

// $verificador 	= calculaDigitoMod11('3748980272022102522171300000002210100000000010000', 1, 9, false);
// var_dump($verificador);
// exit;

$fecha = date("Y-m-d\TH:i:s.v", strtotime('2022-10-19'));


$fecha = date("Y-m-d", strtotime('2022-10-19'));

var_dump($fecha);
// Obtiene los permisos
$permisos = explode(',', permits);
// Almacena los permisos en variables
$permiso_almacen = in_array('permiso_almacen', $permisos);
$permiso_mostrar = in_array('mostrar', $permisos);

if($_user['rol_id'] == 1) {
	$punto_ventas = $db->query("SELECT *
									FROM mb_siat_puntos_venta
									ORDER BY codigo")
								->fetch();
}else{
    $punto_ventas = $db->query("SELECT *
							FROM mb_siat_puntos_venta
							ORDER BY codigo")
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
    					<a href="?/electronicas/mostrar" class="btn btn-warning">Mis ventas</a>
    				</p>
				<?php endif ?>

					<div class="alert alert-info">
						<button type="button" class="close" data-dismiss="alert">&times;</button>
						<strong>Advertencia!</strong>
						<ul>
							<li>Elija el punto de venta desde el cual hara la venta.</li>
						</ul>
					</div>
						<div class="col-md-4">
							<a class="seleccionarAlmacen" href="?/electronicas/crear/0">
								<div class="panel panel-default">
									<div class="panel-heading">
										<h2 class="panel-title">
											<span class="glyphicon glyphicon-list"></span>
											<br>
											<br>
												Principal
										</h2>
									</div>
								</div>
							</a>
						</div>

						<?php
						foreach($punto_ventas as $key => $el){
						?>
						<div class="col-md-4">
							<a class="seleccionarAlmacen" href="?/electronicas/crear/<?php echo $el["codigo"]; ?>">
								<div class="panel panel-default">
								<div class="panel-heading">
									<h2 class="panel-title">
										<span class="glyphicon glyphicon-list"></span>
										<br>
										<br>
										<?php echo $el["nombre"]; ?>
										<br>
										<?php echo $el["tipo"]; ?>
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