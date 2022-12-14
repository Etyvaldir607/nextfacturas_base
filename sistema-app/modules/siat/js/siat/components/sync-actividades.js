(function (ns) {
	ns.ComSyncActividades = {
		template: `
		<div id="com-sync-actividades">
			<div class="mb-3"><button type="button" class="btn btn-primary" v-on:click="getData()">Sincronizar</button></div>
			<div class="table-responsive">
				<table class="table table-sm">
					<thead>
						<tr>
							<th>Nro</th>
							<th>Codigo</th>
							<th>Descripcion</th>
							<th>Tipo</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="(item, index) in lista">
							<td>{{ index + 1 }}</td>
							<td>{{ item.codigoCaeb }}</td>
							<td>{{ item.descripcion }}</td>
							<td>{{ item.tipoActividad  }}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>`,
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
				//const res = await this.$root.http.Post('?/siat/sync_actividades', form);
				try {
					this.$root.$processing.show('procesando...');
					const res = await this.$root.http.Get(`?/siat/api_sincronizaciones/sync_actividades/${sucursal}/${puntoventa}`);
					this.lista = Array.isArray(res.data.RespuestaListaActividades.listaActividades) ? res.data.RespuestaListaActividades.listaActividades : [res.data.RespuestaListaActividades.listaActividades];
					this.$root.$processing.hide();
				}
				catch (e) {
					this.$root.$processing.hide();
					alert(e.error || e.message || 'Error desconocido');
				}
			}
		},
		mounted() {

		},
		created() {
			this.getData();
		}
	};
})(SBFramework.Components.Siat);