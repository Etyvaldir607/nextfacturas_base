(function (ns) {
	ns.InvoiceListing = {
		template: `<div id="com-siat-invoice-listing">
			<h2>Listado de Facturas</h2>
			<div class="mb-2">
				<!--
				<div class="input-group">
					<input type="text" name="" class="form-control" placeholder="Buscar factura..." />
					<button type="button" class="btn btn-primary"><i class="fa fa-search"></i></button>
				</div>
				-->
			</div>
			<div class="panel panel-default card border shadow" v-for="(invoice, index) in items">
				<div class="panel-body card-body">
					<div class="container-fluid">
						<div class="row">
							<div class="col-12 col-sm-5">
								<div><b>Factura Nro:</b> {{ invoice.invoice_number }}</div>
								<div><b>Cliente:</b> {{ invoice.customer }}</div>
								<div><b>Punto Venta:</b> {{ invoice.punto_venta }}</div>
								<div><b>Fecha emision:</b> {{ invoice.invoice_date_time }}</div>
								<div>
									<span class="label label-as-badge" 
										v-bind:class="{'label-success': invoice.status == 'issued', 'label-danger': invoice.status == 'error', 'label-warning': invoice.status == 'void'}">
										{{ formatStatus(invoice.status) }}
									</span>
								</div>
							</div>
							<div class="col-12 col-sm-3">
								<div><b>Impuesto:</b> {{ invoice.total_tax.toFixed(2) }}</div>
								<div><b>Total:</b> {{ invoice.total.toFixed(2) }}</div>
							</div>
							<div class="col-12 col-sm-4">
								<div class="btn-group btn-group-sm" role="group" aria-label="...">
									<a v-bind:href="'?/siat/facturas/'+ invoice.invoice_id  +'/view'" target="_blank" class="btn btn-sm btn-warning w-100 mb-1">Imprimir</a>
									<a v-bind:href="'?/siat/facturas/'+ invoice.invoice_id  +'/view/rollo'" target="_blank" class="btn btn-sm btn-success w-100 mb-1">Imprimir Ticket</a>
									<template v-if="invoice.siat_id && invoice.siat_url">
										<a v-bind:href="invoice.siat_url" target="_blank" class="btn btn-sm btn-info w-100 mb-1">Siat Url</a>
									</template>
								
									<button type="button" class="btn btn-default" v-on:click="estado_factura(invoice)" >Estado factura</button>
									<button type="button" class="btn btn-danger" v-on:click="openAnular(invoice, index)" :disabled="invoice.status != 'issued'">Anular</button>
								
								</div>

							</div>
						</div>
					</div>
				</div>
			</div>
			<div ref="modalvoid" class="modal fade">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title">Anular Factura</h5>
						</div>
						<div class="modal-body">
							<template v-if="currentInvoice">
								<siat-void-invoice ref="comvoid" v-bind:invoice="currentInvoice" v-bind:invoice2="currentInvoice2"
									v-on:void-success="onVoidSuccess" />
							</template>
						</div>
						<div class="modal-footer">
							<template v-if="!processingVoid">
								<button type="button" class="btn btn-danger" v-on:click="close()" data-bs-dismiss="modal" data-coreui-dismiss="modal">Cerrar</button>
								<button type="button" class="btn btn-primary" v-on:click="submit($event)">Anular Factura</button>
							</template>
							<template v-else>
								<span class="spinner-border text-primary" role="status">
								  <span class="visually-hidden">Loading...</span>
								</span>
								Procesando...
							</template>
						</div>
					</div>
				</div>
			</div>
		</div>`,
		components: {
			'siat-void-invoice': ns.VoidInvoice,
		},
		data() {
			return {
				statuses: {
					'issued': 'Emitida',
					'error': 'Error',
					'void': 'Anulada',
				},
				currentInvoice: null,
				currentInvoice2: null,
				items: [],
				modalVoid: null,
				processingVoid: false,
			};
		},
		methods:
		{
			show_notify(data) {
				switch (data.status) {
					case 690:
						data.title = 'Exito !!'
						data.type = 'success'
						data.icon = 'glyphicon glyphicon-ok'
						break;
					case 691:
						data.title = 'Atencion !!'
						data.type = 'warning'
						data.icon = 'glyphicon glyphicon-remove'
						break;
					default:
						data.title = 'Error !!'
						data.type = 'warning'
						data.icon = 'glyphicon glyphicon-remove'
						data.message = data.data.RespuestaServicioFacturacion.mensajesList.descripcion || 'Error inesperado contactese con sistemas'
				}

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

			async estado_factura(invoice) {
				try {
					this.$root.$processing.show('Verificando estado de factura en Siat...');
					const invoice_id = parseInt(invoice.invoice_id)
			
					const resp = await this.$root.http.Get(`?/siat/api_facturas/estado_factura/${invoice_id}`);

					this.show_notify(resp);
					this.$root.$processing.hide();
				}
				catch (e) {
					this.$root.$processing.hide();
					console.log('ERROR', e);
					this.$root.$toast.ShowError('Ocurrio un error inesperado contacte con su proveedor');
				}
			},

			async getInvoices() {

				try {
					const sucursal = 0
					const puntoventa = 0
					this.$root.$processing.show('Obteniendo facturas...');
					const resp = await this.$root.http.Get(`?/siat/api_facturas/obtener_facturas/${sucursal}/${puntoventa}`);
					this.items = resp.data;
					this.$root.$processing.hide();
				}
				catch (e) {
					this.$root.$processing.hide();
					console.log('ERROR', e);
					this.$root.$toast.ShowError('Ocurrio un error inesperado contacte con su proveedor');
				}
			},
			formatStatus(status) {
				return this.statuses[status] || 'Desconocido';
			},
			openAnular(invoice, index) {
				this.currentInvoice = invoice;
				const invoi = {
					codigoSucursal: invoice.codigo_sucursal,
					codigoPuntoVenta: invoice.punto_venta,
					codigoEmision: invoice.tipo_emision,
					tipoFacturaDocumento: invoice.tipo_factura_documento,
					codigoDocumentoSector: invoice.codigo_documento_sector,
					cuf: invoice.cuf
				}
				this.currentInvoice2 = { ...invoi };
				this.modalVoid.modal('show');
			},
			async submit($event) {
				try {
					this.processingVoid = true;
					await this.$refs.comvoid.submit();
					this.processingVoid = false;
				}
				catch (e) {
					this.processingVoid = false;
				}
			},
			close() {
				this.modalVoid.modal('hide');
				this.currentInvoice = null;
			},
			onVoidSuccess(xhr_res) {
				this.modalVoid.modal('hide');
				this.currentInvoice = null;

				this.getInvoices();
			},

		},

		async mounted() {
			//const frame = (window.bootstrap || window.coreui);
			this.modalVoid = jQuery(this.$refs.modalvoid);
			this.getInvoices();
		},
		async created() {


		}
	};
	SBFramework.AppComponents = {
		'siat-invoice-listing': ns.InvoiceListing,
	};
})(SBFramework.Components.Siat);