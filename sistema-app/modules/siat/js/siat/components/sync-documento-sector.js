(function (ns) {
	ns.ComSyncDocumentoSector = {
		template: `
		<div id="com-actividades-documento-sector">
			<div class="mb-3"><button type="button" class="btn btn-primary" v-on:click="getData()">Sincronizar</button></div>
			<div class="table-responsive">
				<table class="table table-sm">
					<thead>
						<tr>
							<th>Nro</th>
							<th>Codigo Actividad</th>
							<th>Cod. Doc. Sector</th>
							<th>Tipo Doc. Sector</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="(item, index) in lista">
							<td>{{ index + 1 }}</td>
							<td>{{ item.codigoActividad }}</td>
							<td>{{ item.codigoDocumentoSector }}</td>
							<td>{{ item.tipoDocumentoSector  }}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>`,
		props: {
			//sucursal: { type: Number, required: false, default: 0 },
			//puntoventa: { type: Number, required: false, default: 0 },
		},
		data() {
			
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

			async getData() {
				const sucursal = this.sucursal_local
				const puntoventa = this.puntoventa_local

				this.$root.$processing.show('procesando...');
				const res = await this.$root.http.Get(`?/siat/api_sincronizaciones/sync_actividades_documento_sector/${sucursal}/${puntoventa}`);
				//console.log(res);
				this.lista = res.data.RespuestaListaActividadesDocumentoSector.listaActividadesDocumentoSector; 
				this.$root.$processing.hide();
			}
		},
		mounted() {
		},
		created() {
			this.getData();
		}
	};
})(SBFramework.Components.Siat);