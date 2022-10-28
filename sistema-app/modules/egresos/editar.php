<?php
    // Obtiene la moneda oficial
    $moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
    $moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';
    $IdEgreso=$params[0]??0;
    $Egreso=$db->query("SELECT e.descripcion,e.almacen_id,a.almacen,e.tipo,a1.almacen AS almacen_s,e.almacen_id_s
                        FROM inv_egresos AS e
                        LEFT JOIN inv_almacenes AS a ON e.almacen_id=a.id_almacen
                        LEFT JOIN inv_almacenes AS a1 ON e.almacen_id_s=a1.id_almacen
                        WHERE e.id_egreso='{$IdEgreso}'")->fetch_first();
    $Detalles=$db->query("SELECT p.id_producto,p.codigo,p.nombre,ed.cantidad,ed.precio,u.unidad
                        FROM inv_egresos_detalles AS ed
                        LEFT JOIN inv_productos AS p ON p.id_producto=ed.producto_id
                        LEFT JOIN inv_unidades AS u ON u.id_unidad=ed.unidad_id
                        WHERE egreso_id='{$IdEgreso}'")->fetch();
    require_once show_template('header-empty');
?>
<div class='row'>
    <div class='col-md-6'>
        <div class='panel panel-success'>
            <div class='panel-heading'>
                <h3 class='panel-title'>
                    <span class='glyphicon glyphicon-option-vertical'></span>
                    <strong>Editar Traspaso</strong>
                </h3>
            </div>
            <div class='panel-body'>
                <input type="hidden" value="<?=$Egreso['almacen_id_s']?>" id='IdAlmacenF'>
                <form class='form-horizontal' id='actualizarF'>
                    <input type='hidden' id='id_egresoF' value='<?=$IdEgreso?>'>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Tipo:</label>
                        <div class="col-sm-8">
                            <p class="form-control-static"><?= escape($Egreso['tipo']); ?></p>
                        </div>
                    </div>
                    <div class="form-group">
						<label class="col-sm-4 control-label">Almacén:</label>
						<div class="col-sm-8">
							<p class="form-control-static"><?= escape($Egreso['almacen']); ?></p>
						</div>
                    </div>
                    <?php
                        if($Egreso['tipo']=='Traspaso'):
                    ?>
                    <div class="form-group">
						<label class="col-sm-4 control-label">Almacén Salida:</label>
						<div class="col-sm-8">
							<p class="form-control-static"><?= escape($Egreso['almacen_s']); ?></p>
						</div>
					</div>
                    <?php
                        endif;
                    ?>
                    <div class="form-group">
						<label for="descripcionF" class="col-sm-4 control-label">Descripción:</label>
						<div class="col-sm-8">
							<textarea name="descripcion" id='descripcionF' class="form-control" autocomplete="off" data-validation="letternumber" data-validation-allowing="+-/.,:;#º()\n " data-validation-optional="true"></textarea>
						</div>
                    </div>
                    <div class="table-responsive margin-none">
						<table id="ventas" class="table table-bordered table-condensed table-striped table-hover table-xs margin-none">
							<thead>
								<tr class="active">
									<th class="text-nowrap">Código</th>
									<th class="text-nowrap">Nombre</th>
									<th class="text-nowrap">Unidad</th>
									<th class="text-nowrap">Precio</th>
									<th class="text-nowrap">Importe</th>
									<th class="text-nowrap text-center"><span class="glyphicon glyphicon-trash"></span></th>
								</tr>
							</thead>
                            <tbody>
                                <?php
                                    foreach($Detalles as $Fila=>$Detalle):
                                ?>
                                <tr>
                                    <td>
                                        <input type='hidden' name='id_producto[]' value='<?=$Detalle['id_producto']?>'>
                                        <input type='hidden' name='unidad[]' value='<?=$Detalle['unidad']?>'>
                                        <input type='hidden' name='precio[]' value='<?=$Detalle['precio']?>'>
                                        <?=$Detalle['codigo']?>
                                    </td>
                                    <td><?=$Detalle['nombre']?></td>
                                    <td><?=$Detalle['unidad']?></td>
                                    <td><?=$Detalle['precio']?></td>
                                    <td>
                                        <input type='text' name='cantidad[]' onkeypress='return validarEntero(this.value,event,9999)' value='<?=$Detalle['cantidad']?>'>
                                    </td>
                                    <td>
                                        <button class='btn btn-danger btn-sm' onclick='quitar(this)'>
                                            <span class='glyphicon glyphicon-trash'></span>
                                        </button>
                                    </td>
                                </tr>
                                <?php
                                    endforeach;
                                ?>
                            </tbody>
							<!--<tfoot>
								<tr class="active">
									<th class="text-nowrap text-right" colspan="4">Importe total <?=escape($moneda)?></th>
									<th class="text-nowrap text-right"><input type="text" value='0' style='width:100px' readonly></th>
									<th class="text-nowrap text-center"><span class="glyphicon glyphicon-trash"></span></th>
								</tr>
							</tfoot>-->
						</table>
                    </div>
                    <br>
                    <div class='form-group'>
                        <div class='col-xs-12 text-right'>
                            <button type='submit' class='btn btn-success'>Actualizar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class='col-md-6'>
        <div class='panel panel-success'>
            <div class='panel-heading'>
                <h3 class='panel-title'>
                    <span class='glyphicon glyphicon-option-vertical'></span>
                    <strong>Buscar Productos</strong>
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
                                    <th class='text-nowrap text-center width-collapse'>#</th>
                                    <th class='text-nowrap text-center width-collapse'>CÓDIGO</th>
                                    <th class='text-nowrap text-center'>PRODUCTO</th>
                                    <th class='text-center width-collapse' width='8%'>CANTIDAD</th>
                                    <th class='text-nowrap text-center '>UNIDAD</th>
                                    <th class='text-nowrap text-center '>PRECIO</th>
                                    <th class='text-nowrap text-center width-collapse'>IMPORTE</th>
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
<script>
    function buscarProductos(cadena){
        let id_almacen=document.getElementById('IdAlmacenF').value,
            productos=document.getElementById('productos');
        cadena=cadena.trim();
        if(id_almacen!=='' && cadena!==''){
            $.ajax({
                data: {cadena,id_almacen},
                type: 'POST',
                dataType: 'json',
                url: '?/egresos/servicio_buscar',
            })
            .done(function(data,textStatus,jqXHR){
                productos.children[1].innerHTML='';
                data.forEach((Dato,index)=>{
                    productos.children[1].innerHTML+=`<tr>
                            <td>${index+1}</td>
                            <td>${Dato['codigo']}</td>
                            <td>${Dato['nombre']}</td>
                            <td>${Dato['total']}</td>
                            <td>${Dato['unidad']}</td>
                            <td>${Dato['precio_actual']}</td>
                            <td>
                                <button class='btn btn-success btn-sm' onclick='agregar(this,${Dato['id_producto']})'>
                                    <span class='glyphicon glyphicon-plus'></span>
                                </button>
                            </td>
                        </tr>`;
                });
            })
            .fail(function(jqXHR,textStatus,errorThrown) {
                console.log(textStatus)
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
    function agregar(elemento,id_producto){
        let Fila=elemento.parentNode.parentNode,
            Codigo=Fila.children[1].innerText,
            Nombre=Fila.children[2].innerText,
            Maximo=parseInt(Fila.children[3].innerText),
            Unidad=Fila.children[4].innerText,
            Precio=parseFloat(Fila.children[5].innerText);
        let ventas=document.getElementById('ventas').children[1],
            index=0,
            Sw=false;
        for(let i=0;i<ventas.children.length;++i){
            if(ventas.children[i].children[0].children[0].value==id_producto){
                index=i;
                Sw=true;
                break;
            }
        }
        if(Sw){
            if(ventas.children[index].children[4].children[0].value<Maximo)
                ventas.children[index].children[4].children[0].value++;
        }
        else{
            Fila=`<tr>
                    <td>
                        <input type='hidden' name='id_producto[]' value='${id_producto}'>
                        <input type='hidden' name='unidad[]' value='${Unidad}'>
                        <input type='hidden' name='precio[]' value='${Precio}'>
                        ${Codigo}
                    </td>
                    <td>${Nombre}</td>
                    <td>${Unidad}</td>
                    <td>${Precio}</td>
                    <td>
                        <input type='text' name='cantidad[]' onkeypress='return validarEntero(this.value,event,${Maximo})' value='1'>
                    </td>
                    <td>
                        <button class='btn btn-danger btn-sm' onclick='quitar(this)'>
                            <span class='glyphicon glyphicon-trash'></span>
                        </button>
                    </td>
                </tr>`;
            ventas.insertAdjacentHTML('beforeend',Fila);
        }
    }
    function quitar(elemento){
        elemento.parentNode.parentNode.parentNode.removeChild(elemento.parentNode.parentNode);
    }
    function validarEntero(valor,e,limite){
        Evento=e.keyCode||e.charCode;
        if(Evento>47&&Evento<58){
            let nuevoNumero=(valor*10)+(Evento-48);
            if(nuevoNumero<=limite)
                return true;
        }
        return false;
    }
    document.getElementById('actualizarF').addEventListener('submit',e=>{
        e.preventDefault();
        if(document.getElementById('ventas').children[1].innerHTML!=''){
            let id_egreso=document.getElementById('id_egresoF').value,
                descripcion=document.getElementById('descripcionF').value,
                id_productos=document.getElementsByName('id_producto[]'),
                cantidades  =document.getElementsByName('cantidad[]'),
                unidades    =document.getElementsByName('unidad[]'),
                precios     =document.getElementsByName('precio[]'),
                id_producto='',
                cantidad   ='',
                unidad     ='',
                precio     ='';
            for(i=0;i<id_productos.length;++i){
                id_producto+=id_productos[i].value+',';
                cantidad+=cantidades[i].value+',';
                unidad+=unidades[i].value+',';
                precio+=precios[i].value+',';
            }
            id_producto=id_producto.substring(0,(id_producto.length-1));
            cantidad=cantidad.substring(0,(cantidad.length-1));
            unidad=unidad.substring(0,(unidad.length-1));
            precio=precio.substring(0,(precio.length-1));
            let Datos={
                    id_egreso,
                    descripcion,
                    id_producto,
                    cantidad,
                    unidad,
                    precio,
                }
            actualizar(Datos);
        }
        else
            $.notify({
                title: '<strong>Error</strong>',
                message: '<div>Necesita agregar un producto!</div>'
            }, {
                type: 'danger'
            });
    });
    function actualizar(Datos){
        $.ajax({
            data: Datos,
            type: 'POST',
            dataType: 'json',
            url: '?/egresos/servicio_editar',
        })
        .done(function(data,textStatus,jqXHR){
            $.notify({
                title: data.message.title,
                message: data.message.message
            }, {
                type: data.message.type
            });
            //redireccionar
            setTimeout(()=>{
                window.location.href ='?/egresos/listar';
            },500);
        })
        .fail(function(jqXHR,textStatus,errorThrown) {
            console.log(textStatus)
        });
    }
</script>
<?php
    require_once show_template('footer-empty');