(function (ns) {
	ns.ComSiatSync = {
		template: `
		<div id="siat-sync">
			<h2>Sincronizacion SIAT</h2>
			<div class="container-fluid">
				<div class="row">
					<div class="col-12 col-sm-3">
						<div class="list-group">
							<template v-for="(item, index) in menus">
								<div class="list-group-item list-group-item-action" v-bind:class="{active: com == item.com}" v-on:click="showComponent(item.com)">
									{{ item.label }}
								</div>
							</template>
						</div>
					</div>
					<div class="col-12 col-sm-9">
						<div class="row mb-2">
							<div class="col-12 col-sm-6">
								<select v-model="priv_sucursal_id" class="form-control form-select">
									<option value="0">Sucursal Principal (0)</option>
								</select>
							</div>
							<div class="col-12 col-sm-6">
								<select v-model="priv_puntoventa_id" class="form-control form-select">
									<option value="0">Punto Venta Principal (0)</option>
									<option v-bind:value="pv.codigo" v-for="(pv, pvi) in puntos_venta">{{ pv.nombre }} -- {{pv.codigo}}</option>
								</select>
							</div>
						</div>
						<keep-alive>
							<component ref="thecom" v-bind:is="com_current" v-bind="com_args"></component>
						</keep-alive>
					</div>
				</div>
			</div>
		</div>`,
		components: {
			'sync-codigos': SBFramework.Components.Siat.ComSyncCodigos,
			'sync-actividades': SBFramework.Components.Siat.ComSyncActividades,
			'sync-fecha-hora': SBFramework.Components.Siat.ComSyncFechaHora,
			'sync-documento-sector': SBFramework.Components.Siat.ComSyncDocumentoSector,
			'sync-leyendas-facturas': SBFramework.Components.Siat.ComSyncLeyendas,
			'sync-mensajes-servicios': SBFramework.Components.Siat.ComSyncMensajesServicios,
			'sync-product-servicios': SBFramework.Components.Siat.ComSyncProductosServicios,
			'sync-eventos': SBFramework.Components.Siat.ComSyncEventos,
			'sync-anulacion': SBFramework.Components.Siat.ComSyncAnulacion,
			'sync-pais-origen': SBFramework.Components.Siat.ComSyncPaisOrigen,
			'sync-documento-identidad': SBFramework.Components.Siat.ComSyncDocumentosIdentidad,
			'sync-tipos-documentos-sector': SBFramework.Components.Siat.ComSyncTiposDocumentosSector,
			'sync-tipos-emision': SBFramework.Components.Siat.ComSyncTiposEmision,
			'sync-tipos-habitacion': SBFramework.Components.Siat.ComSyncTipoHabitacion,
			'sync-metodos-pago': SBFramework.Components.Siat.ComSyncMetodosPago,
			'sync-tipos-moneda': SBFramework.Components.Siat.ComSyncTiposMoneda,
			'sync-puntos-venta': SBFramework.Components.Siat.ComSyncTiposPuntoVenta,
			'sync-tipos-factura': SBFramework.Components.Siat.ComSyncTiposFactura,
			'sync-unidades-medida': SBFramework.Components.Siat.ComSyncUnidadesMedida,
		},
		data() {
			return {
				com: null,
				com_key: '',
				com_args: {},
				menus: [
					{ label: 'Codigos', com: 'sync-codigos' },
					{ label: 'Actividades', com: 'sync-actividades' },
					{ label: 'Fecha y Hora', com: 'sync-fecha-hora' },
					{ label: 'Actividades Documento Sector', com: 'sync-documento-sector' },
					{ label: 'Leyendas Factura', com: 'sync-leyendas-facturas' },
					{ label: 'Mensajes Servicios', com: 'sync-mensajes-servicios' },
					{ label: 'Productos y Servicios', com: 'sync-product-servicios' },
					{ label: 'Eventos Significativos', com: 'sync-eventos' },
					{ label: 'Motivos Anulacion', com: 'sync-anulacion' },
					{ label: 'País Origen', com: 'sync-pais-origen' },
					{ label: 'Tipos Documento Identidad', com: 'sync-documento-identidad' },
					{ label: 'Tipos Documento Sector', com: 'sync-tipos-documentos-sector' },
					{ label: 'Tipos Emision', com: 'sync-tipos-emision' },
					{ label: 'Tipo Habitación', com: 'sync-tipos-habitacion' },
					{ label: 'Tipos Metodo de Pago', com: 'sync-metodos-pago' },
					{ label: 'Tipos Moneda', com: 'sync-tipos-moneda' },
					{ label: 'Tipos Punto Venta', com: 'sync-puntos-venta' },
					{ label: 'Tipos Factura', com: 'sync-tipos-factura' },
					{ label: 'Unidades de Medida', com: 'sync-unidades-medida' },
				],
				priv_sucursal_id: 0,
				priv_puntoventa_id: 0,
				sucursales: [],
				puntos_venta: [],
			};
		},
		watch:
		{
			priv_sucursal_id(nv, ov) {
				if (!this.$refs.thecom || !this.$refs.thecom.setSucursal)
					return;
				this.$refs.thecom.setSucursal(nv);
			},
			priv_puntoventa_id(nv, ov) {
				if (!this.$refs.thecom || !this.$refs.thecom.setPuntoVenta)
					return;
				this.$refs.thecom.setPuntoVenta(nv);
			}
		},
		computed:
		{
			com_current: function () {
				return this.com;
			},
		},
		methods:
		{
			showComponent(com) {
				//console.log(com);
				this.key = Date.now();
				this.com_args = {
					sucursal: parseInt(this.priv_sucursal_id),
					puntoventa: parseInt(this.priv_puntoventa_id)
				};
				this.com = com;
			},

			async getPuntosVenta() {
				try {
					this.$root.$processing.show('procesando...');
					const res = await this.$root.http.Get(`?/siat/api_sincronizaciones/sync_punto_ventas`);
					this.puntos_venta = res.data;
					this.$root.$processing.hide();
				}
				catch (e) {
					console.error(e);
				}
			}
		},
		mounted() {

		},
		created() {
			this.getPuntosVenta();
			this.com = 'sync-fecha-hora';
			//this.com = 'sync-tipos-habitacion';
			//this.com = 'sync-documento-sector';
		}
	};
	SBFramework.AppComponents = {
		'siat-sync': ns.ComSiatSync,
	};
})(SBFramework.Components);
