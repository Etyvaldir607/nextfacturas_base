<?php

//var_dump($clientes);
// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);
$Datos=$db->query('SELECT*FROM inv_ciudades LEFT JOIN inv_departamentos ON inv_ciudades.departamento_id=inv_departamentos.id_departamento')->fetch();

// Obtiene el rango de fechas
$gestion = date('Y');
$gestion_base = date('Y-m-d');
//$gestion_base = ($gestion - 16) . date('-m-d');
$gestion_limite = ($gestion + 16) . date('-m-d');

// Obtiene fecha inicial
$fecha_inicial = (isset($params[0])) ? $params[0] : $gestion_base;
$fecha_inicial = (is_date($fecha_inicial)) ? $fecha_inicial : $gestion_base;
$fecha_inicial = date_encode($fecha_inicial);

// Obtiene fecha final
$fecha_final = (isset($params[1])) ? $params[1] : $gestion_limite;
$fecha_final = (is_date($fecha_final)) ? $fecha_final : $gestion_limite;
$fecha_final = date_encode($fecha_final);

// Obtiene los clientes
//$clientes = $db->select('*')->from('inv_clientes')->fetch();
$clientes = $db->select('a.*, GROUP_CONCAT(DISTINCT c.cargo SEPARATOR "|") as empresa')
               ->from('inv_clientes a')
               ->join('inv_egresos b','a.id_cliente = b.cliente_id')
               ->join('sys_empleados c','b.empleado_id = c.id_empleado')
               ->group_by('a.id_cliente')
               ->fetch();

$t_clientes = '';
$n_clientes = '';
$empresa = '';
foreach($clientes as $cliente){
    $t_clientes = $t_clientes.'*'.$cliente['ubicacion'];
    $n_clientes = $n_clientes.'*'.$cliente['cliente'];

    $aux = explode('|',$cliente['empresa']);

    $cliente['empresa'];
    if(count($aux)==1){
        if($aux[0]==2){
            $empresa = $empresa.'*'.'1';
        }elseif($aux[0]==1){
            $empresa = $empresa.'*'.'2';
        }else{
            $empresa = $empresa.'*'.'0';
        }
    }else{
        $empresa = $empresa.'*'.'3';
    }
}
// Obtiene empleados
$empleados = $db->query("SELECT CONCAT(e.nombres, ' ', e.paterno, ' ', e.materno) as empleado, e.id_empleado, r.rol, u.id_user
						FROM sys_users u
						LEFT JOIN sys_empleados e ON u.persona_id = e.id_empleado
						LEFT JOIN sys_roles r ON u.rol_id = r.id_rol
						WHERE r.id_rol != 4
						AND u.active = 1")->fetch();

//obtener las rutas
$rutas = $db->select('*')->from('gps_rutas')->where('estado',1)->fetch();

// Obtenemos los grupos

// $grupos = $db->select('*')->from('inv_clientes_grupos')->fetch();
$grupos = $db->query("SELECT g.*, CONCAT(e.codigo ,' - ', e.nombres, ' ', e.paterno, ' ', e.materno) as vendedor 
        			FROM inv_clientes_grupos g
        			LEFT JOIN sys_empleados e ON g.vendedor_id = e.id_empleado
        			
        			LEFT JOIN sys_users u ON u.persona_id=e.id_empleado
                    LEFT JOIN sys_supervisor ss ON g.id_cliente_grupo=ss.cliente_grupo_id
                	WHERE
        			(
                        (e.id_empleado='".$_user['persona_id']."' AND '".$_user['rol_id']."' = 15)
    			        OR 
    			        (ss.user_ids='".$_user['id_user']."' AND '".$_user['rol_id']."' = 14)
    			        OR 
    			        '".$_user['rol_id']."' = 1
    			    )
        			")
        			->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_ver = in_array('proformas_ver', $permisos);
$permiso_eliminar = in_array('proformas_eliminar', $permisos);
$permiso_imprimir = in_array('imprimir', $permisos);
$permiso_facturar = in_array('proformas_facturar', $permisos);
$permiso_cambiar = true;

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
        <b>Crear cliente <?= $_institution['empresa1'] ?></b>
    </h3>
</div>
<div class="panel-body">

    <div class="row">
        <div class="col-sm-9 hidden-xs">

        </div>
        <div class="col-xs-12 col-sm-3 text-right">
            <a href="?/clientes/listar" type="button" id="listar" class="btn btn-primary" >Listar</a>
        </div>
    </div>
    <hr/>
    <div>
        <table id="coord" class="hidden">
            <tbody >
            <?php foreach($rutas as $ruta){ ?>
                <tr><td><?= $ruta['nombre'] ?></td>
                    <td><?= $ruta['coordenadas'] ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="row">
        <div class="col-sm-6">
            <form method="post" id="cliente_form" action="?/clientes/guardar" class="form-horizontal" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nombres" class="col-md-3 control-label">Cliente:</label>
                    <div class="col-md-9">
                        <input type="hidden" value="0" name="id_empleado" data-validation="required number">
                        <input type="text" value="" name="nombres" id="nombres" class="form-control" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="-.,(&/)ºª " data-validation-length="max100">
                    </div>
                </div>
                <div class="form-group">
                    <label for="nombres_factura" class="col-md-3 control-label">Nombres de factura:</label>
                    <div class="col-md-9">
                        <input type="text" value="" name="nombres_factura" id="nombres_factura" class="form-control" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="-.,(&/)ºª " data-validation-length="max100" data-validation-optional="true">
                    </div>
                </div>
                <div class="form-group">
                    <label for="ci" class="col-md-3 control-label">CI/NIT:</label>
                    <div class="col-md-9">
                        <input type="text" value="" name="ci" id="ci" class="form-control" autocomplete="off" data-validation="letternumber length" data-validation-allowing="-.,(&/)ºª " data-validation-length="max100">
                    </div>
                </div>
                <div class="form-group">
                    <label for="direccion" class="col-md-3 control-label">Dirección:</label>
                    <div class="col-md-9">
                        <input type="text" value="" name="direccion" id="direccion" class="form-control" autocomplete="off" data-validation="letternumber length" data-validation-allowing="-.,(&/)ºª " data-validation-length="max100" >
                    </div>
                </div>
                <div class="form-group">
                    <label for="telefono" class="col-md-3 control-label">Telefono:</label>
                    <div class="col-md-9">
                        <input type="text" value="" name="telefono" id="telefono" class="form-control" autocomplete="off" data-validation="letternumber length" data-validation-allowing="-.,(&/)ºª " data-validation-length="max100">
                    </div>
                </div>
                <div class="form-group">
                    <label for="descripcion" class="col-md-3 control-label">Descripción:</label>
                    <div class="col-md-9">
                        <input type="text" value="" name="descripcion" id="descripcion" class="form-control" autocomplete="off" data-validation="alphanumeric length" data-validation-allowing="-.,(&/)ºª+ " data-validation-length="max100" >
                    </div>
                </div>
                <div class="form-group">
                    <label for="telefono" class="col-md-3 control-label">Imagen:</label>
                    <div class="col-md-9 card" >
                        <input type="file" class="form-control" name="imagen" id="imagen">
                    </div>
                </div>
                <div class="form-group">
                    <label for="ciudad" class="col-sm-3 control-label">Ciudad:</label>
                    <div class="col-sm-9">
                        <select name="ciudad" id="ciudad" class="form-control">
                        <?php
                            foreach($Datos as $Fila=>$Dato):
                        ?>
                            <option value='<?=$Dato['id_ciudad']?>'><?="{$Dato['ciudad']} ({$Dato['departamento']})"?></option>
                        <?php
                            endforeach;
                        ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="tipo" class="col-sm-3 control-label">Tipo:</label>
                    <div class="col-sm-9">
                        <select name="tipo" id="tipo" class="form-control">
                            <?php $tipos = $db->select('*')->from('inv_tipos_clientes')->fetch();
                            foreach ($tipos as $nro => $tipo) { ?>
                                <option value="<?= $tipo['tipo_cliente'] ?>"><?= $tipo['tipo_cliente'] ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="tipo" class="col-sm-3 control-label">Grupo:</label>
                    <div class="col-sm-9">
                        <select name="id_grupo" id="id_grupo" class="form-control">
                            <?php foreach ($grupos as $nro => $grupo) { ?>
                                <option value="<?= $grupo['id_cliente_grupo'] ?>"><?= $grupo['nombre_grupo'] .' - ('. $grupo['vendedor'] . ')'?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="tipo" class="col-sm-3 control-label">Semana:</label>
                    <div class="col-sm-9">
                        <select name="id_dia" id="id_dia" class="form-control">
                            <option value=""></option>
                            <option value="1">Lunes</option>
                            <option value="2">Martes</option>
                            <option value="3">Miercoles</option>
                            <option value="4">Jueves</option>
                            <option value="5">Viernes</option>
                            <option value="6">Sabado</option>
                        </select>
                    </div>
                </div>
                <!-- <div class="form-group">
                    <label for="empleado" class="col-sm-3 control-label">Asignar vendedor:</label>
                    <div class="col-sm-9">
                        <select name="empleado" id="empleado" class="form-control text-uppercase" data-validation="required" data-validation-allowing="-+./&()">
                            <option value="">Buscar</option>
                            <?php // foreach ($empleados as $empleado) { ?>
                                <option value="<?php // escape($empleado['id_empleado']); ?>" <?php // ($empleado['id_user'] == $_user['id_user']) ? 'selected' : ''?>  ><?php // escape($empleado['empleado']); ?></option>
                            <?php // } ?>
                        </select>
                    </div>
                </div> -->
                <div class="form-group">
                    <div class="col-md-9 col-md-offset-3">
                        <button type="button" id="botonenviar" class="btn btn-primary">
                            <span class="glyphicon glyphicon-floppy-disk"></span>
                            <span>Guardar</span>
                        </button>
                        <button type="reset" class="btn btn-default">
                            <span class="glyphicon glyphicon-refresh"></span>
                            <span>Restablecer</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-sm-6">
            <div id="map" class="map col-sm-12 embed-responsive embed-responsive-16by9"></div>
        </div>
    </div>

</div>

<!-- Inicio modal fecha -->
<?php if ($permiso_cambiar) { ?>
    <div id="modal_fecha" class="modal fade">
        <div class="modal-dialog">
            <form id="form_fecha" class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Cambiar fecha</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label for="inicial_fecha">Fecha inicial:</label>
                                <input type="text" name="inicial" value="<?= ($fecha_inicial != $gestion_base) ? date_decode($fecha_inicial, $_institution['formato']) : ''; ?>" id="inicial_fecha" class="form-control" autocomplete="off" data-validation="date" data-validation-format="<?= $formato_textual; ?>" data-validation-optional="true">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label for="final_fecha">Fecha final:</label>
                                <input type="text" name="final" value="<?= ($fecha_final != $gestion_limite) ? date_decode($fecha_final, $_institution['formato']) : ''; ?>" id="final_fecha" class="form-control" autocomplete="off" data-validation="date" data-validation-format="<?= $formato_textual; ?>" data-validation-optional="true">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-aceptar="true">
                        <span class="glyphicon glyphicon-ok"></span>
                        <span>Aceptar</span>
                    </button>
                    <button type="button" class="btn btn-default" data-cancelar="true">
                        <span class="glyphicon glyphicon-remove"></span>
                        <span>Cancelar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php } ?>
<!-- Fin modal fecha -->

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
<script src="<?= js; ?>/Leaflet.Editable.js"></script>
<script src="<?= js; ?>/leaflet_measure.js"></script>
<script>
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

    var LeafIcon = L.Icon.extend({
        options: {
            iconSize: [25, 41],
            iconAnchor:  [12, 41],
            popupAnchor: [1, -34],
            shadowSize:  [41, 41],
            className: '',
            prefix: '',
            glyph: 'home',
            glyphColor: 'white',
            glyphSize: '11px',	// in CSS units
            glyphAnchor: [0, -7]
        }
    });
    var lime1Icon = new LeafIcon({iconUrl: '<?= files .'/puntero/lime1.png' ?>'}),
        lime2Icon = new LeafIcon({iconUrl: '<?= files .'/puntero/lime2.png' ?>'}),
        lime3Icon = new LeafIcon({iconUrl: '<?= files .'/puntero/lime3.png' ?>'}),
        blueIcon = new LeafIcon({iconUrl: '<?= files .'/puntero/blue.png' ?>'});

    window.LRM = {
        apiToken: 'pk.eyJ1IjoibGllZG1hbiIsImEiOiJjamR3dW5zODgwNXN3MndqcmFiODdraTlvIn0.g_YeCZxrdh3vkzrsNN-Diw'
    };

    //    console.log(nomb);

    var waypoints1 = new Array();

    var centerPoint = [-16.507354, -68.162908];


    // Create leaflet map.
    var map = L.map('map').setView(centerPoint, 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    }).addTo(map);





    // Create custom measere tools instances.
    var measure = L.measureBase(map, {});
    //measure.circleBaseTool.startMeasure()

    function afterRender(result) {
        return result;
    }

    function afterExport(result) {
        return result;
    }

    $(function () {
        $("html, body").animate({
            scrollTop: 0
        }, 500);

        $.validate({
            modules: 'basic,date,file'
        });

        $c1 = 1;

        $("#coord tbody tr").each(function (i) {
            var rutas = $.trim($(this).find("td").text());
            $rutas1 = new Array();
            var ruta = rutas.split('*');
            for (var i=1; ruta.length > i; i++) {
                var parte1 = ruta[i].split(',');
                $rutas1.push([parte1[0],parte1[1]]);
            }

            L.polygon($rutas1).addTo(map).bindPopup(ruta[0]);

        });




        measure.markerBaseTool.startMeasure();

        $("#botonenviar").click(
            function() {
                if(validaForm()){
                    var lat = measure.markerBaseTool.measureLayer._latlng.lat;
                    var lng = measure.markerBaseTool.measureLayer._latlng.lng;
                    //var way = JSON.stringify(wayt);
                    var wayt = lat + ',' + lng;
                    console.log(wayt);
                    var nombre = $('#nombres').val();
                    var nombrefactura = $('#nombres_factura').val();
                    var ci = $("#ci").val();

                    var direccion = $("#direccion").val();
                    var ciudad = $("#ciudad").val();
                    var tipo = $("#tipo option:selected").text();
                    var id_grupo = $("#id_grupo option:selected").val();
                    var id_dia = $("#id_dia option:selected").val();
                    var telefono = $("#telefono").val();

                    var empleado = $("#empleado").val();

                    var descripcion = $("#descripcion").val();

                    var formData = new FormData();
                    var files = $('#imagen')[0].files[0];
                    formData.append('imagen',files);
                    formData.append('nombre',nombre);
                    formData.append('nombre_factura',nombrefactura);
                    formData.append('ci',ci);
                    formData.append('direccion',direccion);
                    formData.append('ciudad',ciudad);
                    formData.append('telefono',telefono);
                    formData.append('tipo',tipo);
                    formData.append('id_grupo',id_grupo);
                    formData.append('id_dia',id_dia);
                    formData.append('descripcion',descripcion);
                    formData.append('atencion',wayt);
                    formData.append('empleado',empleado);

                    $.ajax({ //datos que se envian a traves de ajax
                        type:  'post', //método de envio
                        dataType: 'json',
                        url:   '?/clientes/guardar', //archivo que recibe la peticion
                        data:  formData,
                        contentType: false,
                        processData: false
                    }).done(function (ruta) {
                        console.log(ruta);
                        if (ruta.estado == 's') {
                            $('#cliente_form').trigger("reset");
                            $.notify({
                                message: 'El cliente fue registrado satisfactoriamente.'
                            }, {
                                type: 'success'
                            });
        				    
        				    setTimeout(function(){},3000);
        				    window.location.reload(); 
        				    
                        } else if(ruta.estado == 'y'){
                            $('#loader').fadeOut(100);
                            $.notify({
                                message: 'Ocurrió un problema en el proceso, el cliente ya se encuentra registrado..........'
                            }, {
                                type: 'danger'
                            });
                        } else{
                            $('#loader').fadeOut(100);
                            $.notify({
                                message: 'Ocurrió un problema en el proceso, no se puedo guardar los datos ..........'
                            }, {
                                type: 'danger'
                            });
                        }
                    }).fail(function () {
                        $('#loader').fadeOut(100);
                        $.notify({
                            message: 'Ocurrió un problema en el proceso, no se puedo guardar los datos, verifique si la se guardó parcialmente.'
                        }, {
                            type: 'danger'
                        });
                    });

                }

            });

    });
    function validaForm(){
        // Campos de texto
        if($("#nombres").val() == ""){
            $("#nombres").focus();       // Esta función coloca el foco de escritura del usuario en el campo Nombre directamente.
            return false;
        }
        if($("#ci").val() == ""){
            $("#ci").focus();       // Esta función coloca el foco de escritura del usuario en el campo Nombre directamente.
            return false;
        }
        if($("#direccion").val() == ""){
            $("#direccion").focus();       // Esta función coloca el foco de escritura del usuario en el campo Nombre directamente.
            return false;
        }
        if($("#telefono").val() == ""){
            $("#telefono").focus();       // Esta función coloca el foco de escritura del usuario en el campo Nombre directamente.
            return false;
        }
        if($("#descripcion").val() == ""){
            $("#descripcion").focus();       // Esta función coloca el foco de escritura del usuario en el campo Nombre directamente.
            return false;
        }
//        if($("#imagen").val() == ""){
//            $("#imagen").focus();       // Esta función coloca el foco de escritura del usuario en el campo Nombre directamente.
//            return false;
//        }
        if(typeof measure.markerBaseTool.measureLayer.dragging == 'undefined'){
            $.notify({
                message: 'Debe seleccionar un punto en el mapa.'
            }, {
                type: 'danger'
            });
            return false;
        }
        return true;
    }

</script>
<?php require_once show_template('footer-advanced'); ?>