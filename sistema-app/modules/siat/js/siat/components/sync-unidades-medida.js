(function(ns)
{
	ns.ComSyncUnidadesMedida = {
		template: `<div id="com-sync-unidades-medida">
			<div class="mb-3"><button type="button" class="btn btn-primary" v-on:click="getData()">Sincronizar</button></div>
			<div class="table-responsive">
				<table class="table table-sm table-striped">
				<thead>
				<tr>
					<th>Nro</th>
					<th>Codigo</th>
					<th>Descripcion</th>
				</tr>
				</thead>
				<tbody>
				<tr v-for="(item, index) in lista">
					<td>{{ index + 1 }}</td>
					<td>{{ item.codigoClasificador }}</td>
					<td>{{ item.descripcion }}</td>
				</tr>
				</tbody>
				</table>
			</div>
		</div>`,
		data()
		{
			return {
				lista: [],
				sucursal_local: 0,
				puntoventa_local: 0,
			};
		},
		methods: 
		{
			setSucursal(s) {
				this.sucursal_local = parseInt(s);
			},
			setPuntoVenta(pv) {
				this.puntoventa_local = parseInt(pv);
			},
			async getData()
			{

				const sucursal = this.sucursal_local
				const puntoventa = this.puntoventa_local
				try {
					this.$root.$processing.show('procesando...');
					const res = await this.$root.http.Get(`?/siat/api_sincronizaciones/sync_unidades_medida/${sucursal}/${puntoventa}`);
					this.lista = res.data.RespuestaListaParametricas.listaCodigos;
					this.$root.$processing.hide();
				}
				catch (e) {
					this.$root.$processing.hide();
					alert(e.error || e.message || 'Error desconocido');
				}
			}
		},
		created()
		{
			this.getData();
		}
	};
})(SBFramework.Components.Siat);