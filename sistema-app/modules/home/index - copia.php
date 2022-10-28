<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Escritorio</strong>
	</h3>
</div>
<style>
.medida{
	height:300px;
	overflow:scroll;
}
.medida2{
	height:200px;
	overflow:scroll;
}	
</style>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-4 col-md-3">
			<div class="row margin-bottom">
				<div class="col-xs-10 col-xs-offset-1">
					<img src="<?= imgs . '/logo-color.png'; ?>" class="img-responsive">
				</div>
			</div>
			<div class="well text-center">
				<?php if ($_user['persona_id']) : ?>
				<h4 class="margin-none">Bienvenido al sistema!</h4>
				<p>
					<strong><?= escape($_user['nombres'] . ' ' . $_user['paterno'] . ' ' . $_user['materno']); ?></strong>
				</p>
				<?php else : ?>
				<h4 class="margin-none">Bienvenido al sistema!</h4>
				<p>
					<strong><?= escape($_user['username']); ?></strong>
				</p>
				<?php endif ?>
				<p>
					<img src="<?= ($_user['avatar'] == '') ? imgs . '/avatar.jpg' : profiles . '/' . $_user['avatar']; ?>" class="img-circle" width="128" height="128" data-toggle="modal" data-target="#modal_mostrar">
				</p>
				<p class="margin-none">
					<strong><?= escape($_user['email']); ?></strong>
					<br>
					<span class="text-success">en línea</span>
				</p>
			</div>
			<div class="list-group">
				<a href="../sistema-app/storage/PreventasApp.apk" class="list-group-item">
					<span>Descargar aplicacion <b>PreventasApp</b></span>
				</a>
				<a href="?/home/perfil_ver" class="list-group-item">
					<span>Mostrar mi perfil</span>
					<span class="glyphicon glyphicon-menu-right pull-right"></span>
				</a>
				<a href="?/site/logout" class="list-group-item">
					<span>Cerrar mi sesión</span>
					<span class="glyphicon glyphicon-menu-right pull-right"></span>
				</a>
			</div>
		</div>
		<div class="col-sm-8 col-md-9">
			<div class="panel panel-warning">		
				<div class="panel-heading">
					<h3 class="panel-title">
						<span class="glyphicon glyphicon-search"></span>
						<strong>Fecha de vencimiento cercana</strong>
					</h3>
				</div>
				<div class="panel-body">				
					<?php
					$almacen = $db->from('inv_almacenes')->where('principal', 'S')->fetch_first();
					$id_almacen = ($almacen) ? $almacen['id_almacen'] : 0;

					$productos = $db->query("SELECT
                                    p.id_producto,
                                    p.imagen,
                                    p.codigo,
                                    p.codigo_barras,
                                    p.nombre,
                                    p.nombre_factura,
                                    p.cantidad_minima,
                                    p.descripcion,
                                    I.vencimiento as vencimientox,
                                    I.lote as lotex,
                                    IFNULL(I.cantidad_ingresos,0) AS cantidad_ingresos,
                                    IFNULL(I.cantidad_egresos,0) AS cantidad_egresos,
                                    c.categoria,
                                    z.id_asignacion, z.unidad_id, z.tamanio, z.unidad_descripcion
                                FROM inv_productos p
                                LEFT JOIN (SELECT
                                                d.producto_id,
                                                (d.cantidad + u.tamanio) AS cantidad_ingresos,
                                                d.vencimiento AS vencimiento,
                                                d.lote as lote,
                                                E.cantidad_egresos
                                            FROM
                                                inv_ingresos_detalles d
                                                LEFT JOIN ( SELECT
                                                        d.producto_id,
                                                        SUM(d.cantidad+u.tamanio) as cantidad_egresos,
                                                        d.lote as lote
                                                    FROM inv_egresos_detalles d
                                                    LEFT JOIN inv_egresos e ON
                                                        e.id_egreso = d.egreso_id
                                                    LEFT JOIN inv_asignaciones a ON
                                                        a.id_asignacion = d.asignacion_id
                                                    LEFT JOIN inv_unidades u ON
                                                        u.id_unidad = a.unidad_id
                                                    WHERE
                                                        e.almacen_id = $id_almacen
                                                        GROUP BY d.producto_id, d.lote
                                                    ) E ON (E.lote = d.lote AND E.producto_id = d.producto_id)
                                        
                                            LEFT JOIN inv_ingresos i ON
                                                i.id_ingreso = d.ingreso_id
                                            LEFT JOIN inv_asignaciones a ON
                                                a.id_asignacion = d.asignacion_id
                                            LEFT JOIN inv_unidades u ON
                                                u.id_unidad = a.unidad_id
                                            WHERE
                                                d.almacen_id = $id_almacen
                                            GROUP BY
                                                d.producto_id, d.lote
                                          ) I ON  I.producto_id = p.id_producto
                                LEFT JOIN inv_categorias c ON c.id_categoria = p.categoria_id 
                                LEFT JOIN ( SELECT
                                                w.producto_id,
                                                GROUP_CONCAT(w.id_asignacion SEPARATOR '|') AS id_asignacion,
                                                GROUP_CONCAT(w.unidad_id SEPARATOR '|') AS unidad_id,
                                                GROUP_CONCAT(
                                                    w.unidad,
                                                    ':',
                                                    w.otro_precio SEPARATOR '&'
                                                ) AS unidad_descripcion,
                                                GROUP_CONCAT(w.tamanio SEPARATOR '|') AS tamanio
                                            FROM
                                                (SELECT *
                                                    FROM
                                                        inv_asignaciones q
                                                    LEFT JOIN inv_unidades u ON
                                                        q.unidad_id = u.id_unidad
                                                    ORDER BY
                                                        u.unidad
                                                    DESC
                                                ) w GROUP BY w.producto_id 
                                          ) z ON p.id_producto=z.producto_id
                                 
								ORDER BY p.nombre asc, I.vencimiento asc")->fetch();
					?>
					<?php if ($productos) { ?>
					<div class="table-responsive medida" id="medida">
					<table id="productos" class="table table-bordered table-condensed table-striped table-hover table-xs">
						<thead>
							<tr class="active">
								<th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Código</th>
								<th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Cód. Barras</th>
								<th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Nombre comercial</th>
								<th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Nombre genérico</th>
	
								<th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Lote</th>
								<th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Vencimiento</th>
								<th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Stock</th>
							</tr>
						</thead>
						<tbody>
							<?php 
							//var_dump($productos);
							
							foreach ($productos as $nro => $producto) { ?>								
							
								<?php
								$asignacion = $producto['id_asignacion'];
								$unidad = $producto['unidad_id'];
								$descrip = $producto['unidad_descripcion'];
								$tamanio = $producto['tamanio'];
								$infoTxt = "";
								
								$id_productox=$producto['id_producto']."--".$producto['lotex'];

								if($asignacion == null){
									$id_asignacion = "";
								}else{
									$id_asignacion = explode("|",$asignacion);
									$id_unidad = explode("|",$unidad);
									$id_tamanio = explode("|",$tamanio);
									$descripcion = explode("&",$descrip);							
								}
								
								$unidxx = '';
								for ($j= 0 ; $j < count($id_asignacion); $j++){
									$unidxx .= $descripcion[$j].'|';
								}						
								
								$infoTxt .= '<span style="display:none;" data-nombre-unidad_222="'.$id_productox .'">'. $unidxx .' </span>';
								
								/********************000*********************/
								echo $producto['cantidad_ingresos'] - $producto['cantidad_egresos'];
								/******************** */
								
								if($producto['cantidad_ingresos'] - $producto['cantidad_egresos']>0) {
									
									$valon=$producto['vencimientox'];
									$vvv=explode("-",$valon);
									$fecha_data=$vvv["2"]."-".$vvv["1"]."-".$vvv["0"];
									
									$date_now = date('d-m-Y');
									$date_future = strtotime('+10 day', strtotime($date_now));
									$date_future = date('d-m-Y', $date_future);
									$xv=explode("-",$date_future);
																	
									$fechh_data = strtotime($fecha_data." 00:00:00");
									$fecha_entrada = strtotime($date_future." 00:00:00");
									
									if($fecha_entrada > $fechh_data){											
										?>
										<tr>
											<td data-codigo="<?= $id_productox; ?>"><?= $producto['codigo']; ?></td>
											<td><?= $producto['codigo_barras']; ?></td>
											<td style="width: 15%;">
												<span data-nombre='<?= $id_productox; ?>'><?= $producto['nombre']; ?></span>
											</td>
											<td style="width: 15%;">
												<span data-nombref='<?= $id_productox; ?>'><?= $producto['nombre_factura']; ?></span>
											</td>
											
											<td data-lote="<?= $id_productox; ?>"><?php echo $producto['lotex']; ?></td>
											<td data-vencimiento="<?= $id_productox; ?>" style="color:#f00;">              
												<span style="display: none;"><?php echo $vvv[0]."".$vvv[1]."".$vvv[2]; ?></span>
												<span class="latin_date"><?php echo $vvv[2]." / ".$vvv[1]." / ".$vvv[0]; ?></span>
											</td>
											<td data-stock="<?= $id_productox; ?>"><?php echo ($producto['cantidad_ingresos']-$producto['cantidad_egresos']); ?></td>
										</tr>
										<?php 
									} 
								}
							} 
							?>
						</tbody>
					</table>
					</div>
					<?php } ?>
				</div>
			</div>	

			<div class="panel panel-success">		
				<div class="panel-heading">
					<h3 class="panel-title">
						<span class="glyphicon glyphicon-search"></span>
						<strong>Nuevos productos</strong>
					</h3>
				</div>
				<div class="panel-body">				
					<?php
					$productos = $db->query("SELECT *
                                FROM inv_productos p
                                ORDER BY fecha_registro DESC, id_producto DESC
                                ")->fetch();
					?>
					<?php if ($productos) { ?>
					<div class="table-responsive medida2" id="medida2">
					<table id="productos" class="table table-bordered table-condensed table-striped table-hover table-xs">
						<thead>
							<tr class="active">
								<th class="text-nowrap" style="width: 12%; background-color: #222; color:#fff; text-align: center; font-weight: bold;">Fecha</th>
								<th class="text-nowrap" style="width: 22%; background-color: #222; color:#fff; text-align: center; font-weight: bold;">Nombre</th>
								<th class="text-nowrap" style="width: 12%; background-color: #222; color:#fff; text-align: center; font-weight: bold;">Precio actual</th>
								<th class="text-nowrap" style="width: 54%; background-color: #222; color:#fff; text-align: center; font-weight: bold;">Descripcion</th>
							</tr>
						</thead>
						<tbody>
								<?php foreach ($productos as $nro => $producto) { ?>								
									<tr>
										<td><?= $producto['fecha_registro']; ?></td>
										<td><?= $producto['nombre']; ?></td>
										<td><?= $producto['precio_actual']; ?></td>
										<td><?= $producto['descripcion']; ?></td>
									</tr>
								<?php }?>
						</tbody>
					</table>
					</div>
					<?php } ?>
				</div>
			</div>	

			<div class="panel panel-primary">		
				<div class="panel-heading">
					<h3 class="panel-title">
						<span class="glyphicon glyphicon-search"></span>
						<strong>Productos con bajo STOCK</strong>
					</h3>
				</div>
				<div class="panel-body">				
					<?php
					$select = " SELECT p.id_producto, p.codigo, p.nombre, p.nombre_factura, p.cantidad_minima, c.categoria, e.ingresos,s.egresos
								FROM inv_productos p 
								LEFT JOIN inv_categorias c on c.id_categoria = p.categoria_id									
										LEFT JOIN (
											SELECT d.producto_id, IFNULL(SUM(d.cantidad*u.tamanio),0) as ingresos
											FROM inv_ingresos_detalles d 
											left join inv_ingresos i on i.id_ingreso = d.ingreso_id 
											LEFT JOIN inv_asignaciones a ON a.id_asignacion = d.asignacion_id
											LEFT JOIN inv_unidades u ON u.id_unidad = a.unidad_id 
											group by d.producto_id
											) as e on e.producto_id = p.id_producto 																
										LEFT JOIN (
											SELECT d.producto_id, IFNULL(SUM(d.cantidad*u.tamanio),0) as egresos 
											FROM inv_egresos_detalles d 
											left join inv_egresos e on e.id_egreso = d.egreso_id 
											LEFT JOIN inv_asignaciones a ON a.id_asignacion = d.asignacion_id
											LEFT JOIN inv_unidades u ON u.id_unidad = a.unidad_id 
											group by d.producto_id
											) as s on s.producto_id = p.id_producto 										
								ORDER BY egresos";
					
					$productos = $db->query($select)->fetch();
					?>
					<div class="table-responsive medida2" id="medida2">
	
					<table id="table" class="table table-bordered table-condensed table-striped table-hover table-xs">
						<thead>
							<tr class="active">
								<th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">#</th>
								<th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Código</th>								
								<th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Nombre comercial</th>								
								<th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Nombre genérico</th>								
								<th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Mínimo</th>
								<th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Total existencias</th>
							</tr>
						</thead>
						<tfoot>
							<tr class="active">
								<th class="text-nowrap text-middle" data-datafilter-filter="true">#</th>
								<th class="text-nowrap text-middle" data-datafilter-filter="true">Código</th>								
								<th class="text-nowrap text-middle" data-datafilter-filter="true">Nombre comercial</th>								
								<th class="text-nowrap text-middle" data-datafilter-filter="true">Nombre genérico</th>															
								<th class="text-nowrap text-middle" data-datafilter-filter="true">Mínimo</th>
								<th class="text-nowrap text-middle" data-datafilter-filter="true">Total existencias</th>
							</tr>
						</tfoot>
						<tbody>
							<?php 
							foreach ($productos as $nro => $producto) { 
								$ing=intval($producto['ingresos']);
								$egr=intval($producto['egresos']);

								if($producto['cantidad_minima']>($ing-$egr) ){								
									?>
									<tr>
										<th class="text-nowrap"><?= $nro + 1; ?></th>
										<td class="text-nowrap"><?= escape($producto['codigo']); ?></td>
										
										<td class="width-lg"><?= escape($producto['nombre']); ?></td>
										<td class="width-lg"><?= escape($producto['nombre_factura']); ?></td>
																	
										<td class="text-nowrap text-right"><?= escape($producto['cantidad_minima']); ?></td>
										<td class="text-nowrap text-right"><strong class="text-primary"><?php echo ($ing-$egr); ?></strong></td>								
									</tr>
									<?php 
								}
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
<?php require_once show_template('footer-advanced'); ?>