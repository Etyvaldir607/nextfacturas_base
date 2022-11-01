<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading" data-servidor="<?= ip_local . name_project . '/proforma.php'; ?>">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Puntos de Venta SIAT</strong>
	</h3>
</div>
<div id="siat-app" class="panel-body">
	<siat-puntos-venta />
</div>
<script>window.baseurl = '<?php print ip_server ?>';</script>
<script src="<?php print path_app ?>/modules/siat/js/common.js"></script>

<!-- for development -->
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<!-- for production -->
<!--
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14"></script>
<script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
-->

<script src="<?php print path_app ?>/modules/siat/js/config.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/config.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/api.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/modal.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/toast.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/processing.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/Model.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/siat/components/puntos-venta.js"></script>
<script src="<?php print path_app ?>/modules/siat/js/http.js"></script>
<script>
(function()
{
	Vue.use(SBFramework.Plugins.PluginToast, {});
	Vue.use(SBFramework.Plugins.Processing, {});
	const app = new Vue({
		el: '#siat-app',
		components: {
			'siat-puntos-venta': SBFramework.Components.Siat.ComPuntosVenta,
		},
		data: {
			api: new SBFramework.Classes.Api(),
			http: new SBFramework.Classes.Http(),
		}
	})
})();
</script>
<?php require_once show_template('footer-advanced'); ?>
