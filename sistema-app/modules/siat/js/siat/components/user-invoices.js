(function(ns)
{
	ns.UserInvoices = {
		template: `<div id="com-siat-invoice-listing" class="col">
			<h2>Listado de Facturas</h2>
			<div class="card border shadow" v-for="(invoice, index) in items">
				<div class="card-body">
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
								<template v-if="invoice.status == 'issued' && invoice.cuf">
									<a v-bind:href="lt.baseurl + '/portal/misfacturas/'+ invoice.invoice_id" target="_blank" class="btn btn-sm btn-warning w-100 mb-1">
										Imprimir
									</a>
								</template>
								<template v-if="invoice.siat_url">
									<a v-bind:href="invoice.siat_url" target="_blank" class="btn btn-sm btn-info w-100 mb-1">Siat Url</a>
								</template>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>`,
		components: {
		},
		props: {
			items: {type: Array, required: true}
		},
		data()
		{
			return {
				statuses: {
					'issued': 'Emitida',
					'error': 'Error',
					'void': 'Anulada',
				},
			};	
		},
		methods:
		{
			getStatus(status)
			{
				return this.statuses[status] || 'Desconocido';
			},
		},
		async mounted()
		{
			const frame = (window.bootstrap || window.coreui);
		},
		async created()
		{
			
		}
	};
})(SBFramework.Components.Siat);