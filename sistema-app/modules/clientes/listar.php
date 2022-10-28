<?php
    // Obtiene los permisos
    $permisos = explode(',', permits);
    // Almacena los permisos en variables
    $permiso_imprimir = in_array('imprimir', $permisos);
    $permiso_eliminar = in_array('eliminar', $permisos);
    $permiso_modificar = in_array('editar', $permisos);

    $permiso_tipo = in_array('crear_tipo', $permisos);
    $permiso_grupo = in_array('crear_grupo', $permisos);

    // Obtiene la moneda oficial
    //$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
    //$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';
    require_once show_template('header-advanced');
?>
<div class="panel-heading">
    <h3 class="panel-title">
        <span class="glyphicon glyphicon-option-vertical"></span>
        <strong>Clientes</strong>
    </h3>
</div>
<div class="panel-body">
    <?php if ($permiso_imprimir) { ?>
        <div class="row">
            <div class="col-sm-6 hidden-xs">
                <div class="text-label">Para ver el reporte hacer clic en el siguiente botón: </div>
            </div>
            <div class="col-xs-12 col-sm-6 text-right">
                <a href="?/clientes/imprimir" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs"> Imprimir</span></a>
                <a href="?/clientes/crear" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs"> Crear cliente</span></a>
                <?php if($permiso_tipo){ ?>
                <a href="?/clientes/crear_tipo" class="btn btn-primary"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs"> Crear tipo</span></a>
                <?php } ?>
                
                <?php if($permiso_grupo){ ?>
                <a href="?/clientes/crear_grupo" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs"> Crear grupo</span></a>
                <?php } ?>
                
            </div>
        </div>
        <hr>
    <?php
    }
    //if($clientes){
    ?>
    <table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
        <thead>
            <tr class="active">
                <th class="text-nowrap hidden">#</th>
                <th class="text-nowrap">Imagen</th>
                <th class="text-nowrap">Cliente</th>
                <th class="text-nowrap">NIT/CI</th>
                <th class="text-nowrap">Telefono</th>
                <th class="text-nowrap">Ciudad</th>
                <th class="text-nowrap">Dirección</th>
                <th class="text-nowrap">Tipo</th>
                <th class="text-nowrap">Grupo</th>
                <th class="text-nowrap">Dia</th>
                <!--<th class="text-nowrap">Visitas</th>-->
                <?php if ($permiso_modificar || $permiso_eliminar) : ?>
                    <th class="text-nowrap">Opciones</th>
                <?php endif ?>
            </tr>
        </thead>
        <tfoot>
            <tr class="active">
                <th class="text-nowrap hidden" data-datafilter-filter="false">#</th>
                <th class="text-nowrap" data-datafilter-filter="false">Imagen</th>
                <th class="text-nowrap" data-datafilter-filter="true">Cliente</th>
                <th class="text-nowrap" data-datafilter-filter="true">NIT/CI</th>
                <th class="text-nowrap" data-datafilter-filter="true">Telefono</th>
                <th class="text-nowrap" data-datafilter-filter="true">Ciudad</th>
                <th class="text-nowrap" data-datafilter-filter="true">Dirección</th>
                <th class="text-nowrap" data-datafilter-filter="true">Tipo</th>
                <th class="text-nowrap" data-datafilter-filter="true">Grupo</th>
                <th class="text-nowrap" data-datafilter-filter="true">Dia</th>
                <!--<th class="text-nowrap text-middle" data-datafilter-filter="false">Visitas</th>-->
                <?php if ($permiso_modificar || $permiso_eliminar) : ?>
                    <th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
                <?php endif ?>
            </tr>
        </tfoot>
        <tbody>
        </tbody>
    </table>
    <div id="modal_mostrar" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content loader-wrapper">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"></h4>
                </div>
                <div class="modal-body">
                    <img src="" class="img-responsive img-rounded" data-modal-image="">
                </div>
                <div id="loader_mostrar" class="loader-wrapper-backdrop">
                    <span class="loader"></span>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/jquery.base64.js"></script>
<script src="<?= js; ?>/pdfmake.min.js"></script>
<script src="<?= js; ?>/vfs_fonts.js"></script>

<!--script src="<?= js; ?>/jquery.dataFilters.min.js"></script-->
<script src="<?= js; ?>/dataFiltersCustom.min.js"></script>
<script>
    $(function() {
        <?php
        if ($permiso_imprimir) {
        ?>
            $(window).bind('keydown', function(e) {
                if (e.altKey || e.metaKey) {
                    switch (String.fromCharCode(e.which).toLowerCase()) {
                        case 'p':
                            e.preventDefault();
                            window.location = '?/clientes/imprimir';
                            break;
                    }
                }
            });
        <?php
        }
        //if($clientes){
        ?>
        $loader_mostrar = $('#loader_mostrar')
        <?php
        $url = institucion . '/' . $_institution['imagen_encabezado'];
        $image = file_get_contents($url);
        if ($image !== false) :
            $imag = 'data:image/jpg;base64,' . base64_encode($image);
        endif;
        ?>
        var table = $('#table').DataFilter({
            filter: false,
            name: 'Preventista - Lista de Clientes',
            imag: '<?= imgs . '/logo-color.jpg'; ?>',
            imag2: '<?= $imag; ?>',
            
            fechas: '',
            creacion: "Fecha y hora de Impresion: <?= date("d/m/Y H:i:s") ?>",
            
            empresa: '<?= $_institution['nombre']; ?>',
            direccion: '<?= $_institution['direccion'] ?>',
            telefono: '<?= $_institution['telefono'] ?>',
            reports: 'excel|pdf',
            size: 8,
            values: {
                serverSide: true,
                order: [
                    [0, 'asc']
                ],
                ajax: {
                    url: '?/clientes/listar_clientes',
                    type: 'POST',
                    beforeSend: function() {
                        $loader_mostrar.show();
                    },
                    error: function() {}
                },
                drawCallback: function(settings) {
                    $loader_mostrar.hide();
                },
                createdRow: function(nRow, aData, iDisplayIndex) {
                    $(nRow).attr('data-producto', aData[0]);
                    $('td', nRow).eq(0).addClass('hidden');
                    $('td', nRow).eq(1).addClass('text-center');
                    $('td', nRow).eq(2).addClass('');
                    $('td', nRow).eq(3).addClass('');
                    $('td', nRow).eq(4).addClass('');
                    $('td', nRow).eq(5).addClass('');
                    $('td', nRow).eq(6).addClass('');
                    $('td', nRow).eq(7).addClass('');
                    $('td', nRow).eq(8).addClass('text-nowrap');
                    <?php
                    if ($permiso_modificar || $permiso_eliminar) :
                    ?>
                        $('td', nRow).eq(9).addClass('text-nowrap');
                    <?php
                    endif;
                    ?>
                }
            }
        });
        <?php
        //}
        ?>
    });
    var $modal_mostrar = $('#modal_mostrar'),
        $loader_mostrar = $('#loader_mostrar'),
        size,
        title,
        image;
    $modal_mostrar.on('hidden.bs.modal', function() {
        $loader_mostrar.show();
        $modal_mostrar.find('.modal-dialog').attr('class', 'modal-dialog');
        $modal_mostrar.find('.modal-title').text('');
    }).on('show.bs.modal', function(e) {
        size = $(e.relatedTarget).attr('data-modal-size');
        title = $(e.relatedTarget).attr('data-modal-title');
        image = $(e.relatedTarget).attr('src');
        size = (size) ? 'modal-dialog ' + size : 'modal-dialog';
        title = (title) ? title : 'Imagen';
        $modal_mostrar.find('.modal-dialog').attr('class', size);
        $modal_mostrar.find('.modal-title').text(title);
        $modal_mostrar.find('[data-modal-image]').attr('src', image);
    }).on('shown.bs.modal', function() {
        $loader_mostrar.hide();
    });
</script>
<?php require_once show_template('footer-advanced'); ?>