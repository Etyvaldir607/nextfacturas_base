(function(ns)
{
	ns.InvoiceListing = {
		template: `<div id="com-siat-invoice-listing">
			<h2>Listado de Facturas</h2>
			<div class="mb-2">
				<div class="input-group">
					<input type="text" name="" class="form-control" placeholder="Buscar factura..." />
					<button type="button" class="btn btn-primary"><i class="fa fa-search"></i></button>
				</div>
			</div>
			<div class="panel panel-default card border shadow" v-for="(invoice, index) in items">
				<div class="panel-body card-body">
					<div class="container-fluid">
						<div class="row">
							<div class="col-12 col-sm-7">
								<div><b>Factura Nro:</b> {{ invoice.invoice_number }}</div>
								<div><b>Cliente:</b> {{ invoice.customer }}</div>
								<div><b>Punto Venta:</b> {{ invoice.punto_venta }}</div>
								<div><b>Fecha emision:</b> {{ invoice.invoice_date_time }}</div>
								<div>
									<span class="badge" 
										v-bind:class="{'bg-success': invoice.status == 'issued', 'bg-danger': invoice.status == 'error', 'bg-warning': invoice.status == 'void'}">
										{{ getStatus(invoice.status) }}
									</span>
								</div>
							</div>
							<div class="col-12 col-sm-3">
								<div><b>Impuesto:</b> {{ invoice.total_tax.toFixed(2) }}</div>
								<div><b>Total:</b> {{ invoice.total.toFixed(2) }}</div>
							</div>
							<div class="col-12 col-sm-2">
								<a v-bind:href="'?/siat/facturas/'+ invoice.invoice_id  +'/view'" target="_blank" class="btn btn-sm btn-warning w-100 mb-1">Imprimir</a>
								<a v-bind:href="'?/siat/facturas/'+ invoice.invoice_id  +'/view/rollo'" target="_blank" class="btn btn-sm btn-warning w-100 mb-1">Imprimir Ticket</a>
								<template v-if="invoice.siat_id && invoice.siat_url">
									<a v-bind:href="invoice.siat_url" target="_blank" class="btn btn-sm btn-info w-100 mb-1">Siat Url</a>
								</template>
								<!--
								<a href="javascript:;" class="btn btn-sm btn-primary w-100 mb-1">Editar</a>
								-->
								<template v-if="invoice.status == 'issued'">
									<a href="javascript:;" class="btn btn-sm btn-danger w-100 mb-1" v-on:click="anular(invoice, index)">Anular</a>
								</template>
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
								<siat-void-invoice ref="comvoid" v-bind:invoice="currentInvoice"
									v-on:void-success="onVoidSuccess" />
							</template>
						</div>
						<div class="modal-footer">
							<template v-if="!processingVoid">
								<button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-coreui-dismiss="modal">Cerrar</button>
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
		data()
		{
			return {
				statuses: {
					'issued': 'Emitida',
					'error': 'Error',
					'void': 'Anulada',
				},
				currentInvoice: null,
				items: [],
				modalVoid: null,
				processingVoid: false,
			};	
		},
		methods:
		{
			async getInvoices()
			{
				const res = await this.$root.api.Get('/../sistema/?/siat/facturas');
				this.items = res.data;
			},
			getStatus(status)
			{
				return this.statuses[status] || 'Desconocido';
			},
			anular(invoice, index)
			{
				this.currentInvoice = invoice;
				//setTimeout(() => {this.modalVoid.show();}, 1000)
				this.modalVoid.modal('show');
			},
			async submit($event)
			{
				try
				{
					this.processingVoid = true;
					await this.$refs.comvoid.submit();
					this.processingVoid = false;
				}
				catch(e)
				{
					this.processingVoid = false;
				}
			},
			onVoidSuccess(xhr_res)
			{
				this.modalVoid.modal('hide');
				this.currentInvoice = null;
				let invoice = this.items.find((inv) => inv.invoice_id == xhr_res.data.invoice_id );
				if( !invoice )
					return false;
				invoice = xhr_res.data;
				this.$root.$toast.ShowSuccess('La factura fue anulada correctamente');
				this.getInvoices();
			}
		},
		async mounted()
		{
			//const frame = (window.bootstrap || window.coreui);
			this.modalVoid = jQuery(this.$refs.modalvoid);
			this.$root.$processing.show('Obteniendo datos...');
			await Promise.all([this.getInvoices()]);
			this.$root.$processing.hide();
		},
		async created()
		{
			
			
		}
	};
	SBFramework.AppComponents = {
		'siat-invoice-listing': ns.InvoiceListing, 
	};
})(SBFramework.Components.Siat);