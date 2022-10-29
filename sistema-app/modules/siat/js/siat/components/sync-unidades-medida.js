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
				lista: []
			};
		},
		methods: 
		{
			async getData()
			{
				const res = await this.$root.api.Get('/invoices/siat/v2/sync-unidades-medida');
				this.lista = res.data.RespuestaListaParametricas.listaCodigos;
			}
		},
		created()
		{
			this.getData();
		}
	};
})(SBFramework.Components.Siat);