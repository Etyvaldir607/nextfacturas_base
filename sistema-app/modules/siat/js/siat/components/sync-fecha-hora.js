(function (ns) {
	ns.ComSyncFechaHora = {
		template: `
		<div id="com-sync-fecha-hora">
			<div class="mb-3"><button type="button" class="btn btn-primary" v-on:click="getData()">Sincronizar</button></div>
			<div class="table-responsive">
				<table class="table table-sm">
					<thead>
						<tr>
							<th>Nro</th>
							<th>Fecha Hora [ Y-m-d\TH:i:s.v ]</th>
							<th>Fecha Hora [ Y-m-d H:i:s ]</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="(item, index) in lista">
							<td>{{ index + 1 }}</td>
							<td>{{ item.fechaHora }}</td>
							<td>{{ dateFormat(item.fechaHora) }}</td>
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
			dateFormat: function (val) {
				if (val) {
					const date = new Date(val);
					return date.toLocaleString();
				}
				return '';
			},
			setSucursal(s) {
				this.sucursal_local = parseInt(s);
			},
			setPuntoVenta(pv) {
				this.puntoventa_local = parseInt(pv);
			},

			async getData() {
				const sucursal = this.sucursal_local
				const puntoventa = this.puntoventa_local
				try {
					this.$root.$processing.show('procesando...');
					const res = await this.$root.http.Get(`?/siat/api_sincronizaciones/sync_fecha_hora/${sucursal}/${puntoventa}`);
					//console.warn(res)
					this.lista = [res.data.RespuestaFechaHora]
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