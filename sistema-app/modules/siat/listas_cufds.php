<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading" data-servidor="<?= ip_local . name_project . '/proforma.php'; ?>">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Lista CUFDs</strong>
	</h3>
</div>
<div id="siat-app" class="panel-body">
	<siat-cufd-listing />
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
<script src="<?php print path_app ?>/modules/siat/js/siat/components/cufd-listing.js"></script>
<script>
(function()
{
	Vue.use(SBFramework.Plugins.PluginToast, {});
	Vue.use(SBFramework.Plugins.Processing, {});
	const app = new Vue({
		el: '#siat-app',
		components: {
			'siat-cufd-listing': SBFramework.Components.Siat.CufdListing, 
		},
		data: {
			api: new SBFramework.Classes.Api(),
		}
	})
})();
</script>
<?php require_once show_template('footer-advanced'); ?>
