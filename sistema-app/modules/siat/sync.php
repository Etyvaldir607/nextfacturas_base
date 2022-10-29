<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading" data-servidor="<?= ip_local . name_project . '/proforma.php'; ?>">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Sicronizacion SIAT</strong>
	</h3>
</div>
<div id="siat-app" class="panel-body">
	<siat-sync></siat-sync>
</div>
<script>window.baseurl = '<?php print ip_server ?>';</script>
<script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
<script src="<?php print path_app ?>/modules/siat/js/config.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/config.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/api.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/modal.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/toast.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/processing.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/Model.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/sync-actividades-document-sector.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/sync-actividades.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/sync-anulacion.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/sync-codigos.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/sync-documentos-identidad.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/sync-eventos.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/sync-leyendas.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/sync-productos-servicios.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/sync-tipos-documento-sector.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/sync-tipos-emision.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/sync-tipos-factura.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/sync-tipos-metodo-pago.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/sync-tipos-moneda.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/sync-tipos-punto-venta.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/sync-unidades-medida.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/siat-sync.js"></script>
<!--
<script src="<?php print path_app ?>/libraries/mod_invoices/js/siat/app.js"></script>
-->
<script>
(function()
{
	Vue.use(SBFramework.Plugins.PluginToast, {});
	Vue.use(SBFramework.Plugins.Processing, {});
	const app = new Vue({
		el: '#siat-app',
		components: {
			'siat-sync': SBFramework.Components.ComSiatSync,
		},
		data: {
			api: new SBFramework.Classes.Api(),
		}
	})
})();
</script>
<?php require_once show_template('footer-advanced'); ?>
