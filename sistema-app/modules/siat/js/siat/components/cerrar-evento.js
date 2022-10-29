(function (ns) {
	ns.CerrarEvento = {
		template: `
		<div id="com-siat-cerrar-evento">
			<div><b>{{ evento.evento_id }}: {{ evento.descripcion }}</b></div>
			<div><b>Codigo recepcion:</b> {{ evento.codigo_recepcion }}</div>
			<div><b>Fecha inicio:</b> {{ evento.fecha_inicio }}</div>
			<div v-if="evento.fecha_fin">
				<b>Fecha fin:</b> {{ evento.fecha_fin }}
			</div>
			<div><b>CUFD:</b> {{ evento.cufd_evento }}</div>
			<div><b>Total Facturas:</b> {{ total_facturas }}</div>
			<div><b>Metodo de envio:</b> {{ total_facturas <= 500 ? 'PAQUETES' : 'MASIVA'  }}</div>
			<div v-if="evento.cafc">
				<b>Codigo CAFC:</b>
				{{ evento.cafc }}
			</div>
		</div>`,
		props: {
			evento: { type: Object, required: true }
		},
		data() {
			return {
				stats: {},
				total_facturas: 0,
			};
		},
		methods:
		{
			async getStats() {
				try {
					this.$root.$processing.show('Obteniendo datos...');
					//const res = await this.$root.api.Get(`/invoices/siat/v2/eventos/${this.evento.id}/stats`);
					const res = await this.$root.http.Get(`?/siat/api_eventos/stats/${this.evento.id}`);
					this.stats = res.data;
					this.total_facturas = res.total_facturas;
					//this.total_facturas = this.stats.total_facturas;
					this.$root.$processing.hide();
				}
				catch (e) {
					this.$root.$processing.hide();
					alert(e.error || e.message || 'Error');
				}
			},
			async send() {
				try {
					//const res = await this.$root.api.Get(`/invoices/siat/v2/eventos/${this.evento.id}/cerrar`);
					const res = await this.$root.http.Post(`?/siat/api_eventos/cerrar_evento`, { id_evento: this.evento.id });
					this.$emit('evento-cerrado', res);
				}
				catch (e) {
					this.$root.$processing.hide();
					alert(e.error || e.message || 'Error');
				}
			}
		},
		mounted() {

		},
		created() {
			Promise.all([
				this.getStats(),
			]);
		}
	};
})(SBFramework.Components.Siat);