(function (ns) {
	ns.VoidInvoice = {
		template: `<div id="com-siat-void-invoice">
			<form ref="form" class="" novalidate>
				<div class="row">
					<div class="col-12 col-sm-6">
						<div class="mb-3">
							<label>Cliente</label>
							<input type="text" readonly class="form-control" v-bind:value="invoice.customer" />
						</div>
					</div>
					<div class="col-12 col-sm-6">
						<div class="mb-3">
							<label>Nro Factura</label>
							<input type="text" readonly class="form-control" v-bind:value="invoice.invoice_number" />
						</div>
					</div>
					<div class="col-12 col-sm-6">
						<div class="mb-3">
							<label>Fecha emision</label>
							<input type="text" readonly class="form-control" v-bind:value="invoice.invoice_date_time" />
						</div>
					</div>
					<div class="col-12 col-sm-6">
						<div class="mb-3">
							<label>Punto de Venta</label>
							<input type="text" readonly class="form-control" v-bind:value="invoice.punto_venta" />
						</div>
					</div>
					<div class="col-12 col-sm-6">
						<div class="mb-3">
							<label>Monto</label>
							<input type="text" readonly class="form-control" v-bind:value="invoice.total" />
						</div>
					</div>
				</div>
				<div class="mb-3">
					<label>CUF</label>
					<input type="text" readonly class="form-control" v-bind:value="invoice.cuf" />
				</div>
				<div class="mb-3">
					<label>Motivo anulacion df</label>
					<select class="form-control" required v-model="obj.codigoMotivo" pattern="[1-9]+">
						<option value="0">-- motivo anulacion --</option>
						<template v-for="(m, index) in motivos">
							<option v-bind:value="m.codigoClasificador">{{ m.descripcion }}</option>
						</template>
					</select>
				</div>
			</form>
		</div>`,
		props: {
			invoice: { type: Object, required: true },
			invoice2: { type: Object, required: true },
		},
		data() {
			return {
				obj: {
					invoice_id: 0,
					codigoMotivo: 0,
				},
				motivos: [],
			};
		},
		methods:
		{
			show_notify(data) {

				$.notify({
					title: `<strong>${data.title}</strong>`,
					icon: data.icon,
					message: data.message
				}, {
					type: data.type,
					animate: {
						enter: 'animated fadeInUp',
						exit: 'animated fadeOutRight'
					},
					placement: {
						from: "bottom", //from: "bottom" //top,
						align: "center" //align: "left" right
					},
					offset: 10,
					spacing: 10,
					z_index: 1031,
				});
			},

			async getMotivoAnulaciones() {
				try {
					this.$root.$processing.show('procesando...');
					const res = await this.$root.http.Get(`?/siat/api_sincronizaciones/sync_motivo_anulaciones`);
					this.motivos = res.data.RespuestaListaParametricas.listaCodigos;
					this.$root.$processing.hide();
				}
				catch (e) {
					alert(e.error || e.message || 'Ocurrio un error al anular la factura');
				}
			},

			async submit() {

				this.$refs.form.classList.remove('was-validated');
				try {

					if (!this.$refs.form.checkValidity()) {
						this.$refs.form.classList.add('was-validated');
						return;
					}
					if (this.obj.motivo_id <= 0)
						throw { error: 'Debe seleccionar un motivo para la anulacion' };
					if (this.obj.invoice_id <= 0)
						throw { error: 'Identificador de factura invalido' };
					const form = { ...this.obj, ...this.invoice2 }

					this.$root.$processing.show('Anulando factura en Siat...');

					const res = await this.$root.http.Put(`?/siat/api_facturas/anular_factura`, form);
					this.show_notify(res)
					this.$emit('void-success', res);
					this.$root.$processing.hide();

				}
				catch (e) {
					alert(e.error || e.message || 'Ocurrio un error al anular la factura');
				}
			}
		},
		mounted() {
		},
		created() {
			//console.log('VoidInvoice', this.invoice);
			this.obj.invoice_id = this.invoice.invoice_id;
			this.getMotivoAnulaciones();

		}
	};
})(SBFramework.Components.Siat);














