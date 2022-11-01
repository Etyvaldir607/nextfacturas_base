(function (ns) {
	ns.ComPuntosVenta = {
		template: `
		<div id="com-siat-puntos-venta">

			<div class="row">
				<div class="col-sm-6">
					<select class="form-control form-select" v-model="sync_sucursal_id">
						<option value="" disabled>Selecione una sucursal...</option>	
						<option value="0">Sucursal principal (0)</option>
					</select>
				</div>
				<div class="col-sm-6">
					<button type="button" class="btn btn-warning" v-on:click="sync()">Sincronizar</button>
					<button type="button" class="btn btn-primary" v-on:click="nuevo()">Nuevo</button>
				</div>
			</div>
			
			</br>

			<template v-if="items.length > 0 && !itemsSiat">
				<div class="panel panel-default card border shadow mb-2" v-for="(item, index) in items">
					<div class="panel-body card-body">
						<div class="row">
							<div class="col-12 col-sm-8">
								<div>ID: {{ item.id }}, Codigo: {{ item.codigo }}</div>
								<div>Tipo: {{ item.tipo }}</div>
								<div>{{ item.nombre }}</div>
								<div class="form-text text-muted">{{ item.creation_date }}</div>
								<div>
									<span class="label label-as-badge"
										v-bind:class="{'label-success': item.status == 'open', 'label-danger': item.status == 'closed', 'label-warning': item.status == 'unregistered'}">
										{{ item.status }}
									</span>
								</div>
							</div>
							<div class="col-12 col-sm-4">
								<div class="btn-group btn-group-sm" role="group" aria-label="...">
									<button type="button" class="btn btn-danger" v-on:click="cierre_punto_venta(item)"
										:disabled="item.status != 'open'">Cierre punto venta</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</template>
			<div v-else>
				<template v-if="!itemsSiat">
					<b class="text-primary">No se encontraron registros</b>
				</template>
			</div>

			<template v-if="items.length > 0 && itemsSiat">
				<div class="panel panel-default card border shadow mb-2" v-for="(item, index) in items">
					<div class="panel-body card-body">
						<div class="row">
							<div class="col-12 col-sm-8">
								<div>ID: {{ item.id }}, Codigo: {{ item.codigo }}</div>
								<div>Tipo: {{ item.tipo }}</div>
								<div>{{ item.nombre }}</div>
								<div class="form-text text-muted">{{ item.creation_date }}</div>
								<div>
									<span class="label label-as-badge"
										v-bind:class="{'label-success': item.status == 'open', 'label-danger': item.status == 'closed', 'label-warning': item.status == 'unregistered'}">
										{{ item.status }}
									</span>
								</div>
							</div>
							<div class="col-12 col-sm-4">
								<div class="btn-group btn-group-sm" role="group" aria-label="...">
									<button type="button" class="btn btn-info" v-on:click="crear_pv(item)"
										:disabled="item.status != 'unregistered'">Enviar base de datos</button>
									<button type="button" class="btn btn-danger" v-on:click="cierre_punto_venta(item)"
										:disabled="item.status != 'open'">Cierre punto venta</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</template>

			<div ref="modal" class="modal fade">
				<div class="modal-dialog">
					<form action="" method="" class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title">Nuevo Punto de Venta</h5>
							<button class="btn-close" type="button" data-coreui-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<div class="mb-3">
								<label>Sucursal</label>
								<select class="form-control form-select" required v-model="form.sucursal_id">
									<option value="">-- sucursal --</option>
									<option value="0">Sucursal Principal</option>
								</select>
							</div>
							<div class="mb-3">
								<label>Tipo Punto de Venta</label>
								<select class="form-control form-select" required v-model="form.codigo_tipo_punto_venta">
									<option value="">-- tipo --</option>
									<template v-for="(tipo, ti) in tipos">
										<option v-bind:value="tipo.codigoClasificador">{{ tipo.descripcion }}</option>
									</template>
								</select>
							</div>
							<div class="mb-3">
								<label>Nombre Punto de Venta</label>
								<input type="text" name="" value="" class="form-control" required
									v-model="form.nombre_punto_venta" />
							</div>

							<div v-show="esComisionista">
								<div class="mb-3">
									<label>Nro de contrato</label>
									<input type="text" name="" value="" class="form-control" required
										v-model="form.contrato_nit" />
								</div>

								<div class="mb-3">
									<label>NIT Comisionista</label>
									<input type="text" name="" value="" class="form-control" required
										v-model="form.contrato_nro" />
								</div>
								<div class="form-group mb-3">
									<div class="row">
										<div class="col-12 col-sm-6">
											<label>Fecha Inicio</label>
											<input type="date" name="" value="" class="form-control" ref="puntocomisionistafechainicio" required />
											<div class="invalid-feedback">Necesita seleccionar una fecha de inicio del contrato</div>
										</div>
										<div class="col-12 col-sm-6">
											<label>Fecha Fin</label>
											<input type="date" name="" value="" class="form-control" ref="puntocomisionistafechafin" required />
											<div class="invalid-feedback">Necesita seleccionar una fecha de fin del contrato</div>
										</div>
									</div>
								</div>
							</div>

						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-danger" data-dismiss="modal"
								data-coreui-dismiss="modal">Cerrar</button>
							<button type="button" class="btn btn-primary" v-on:click="guardar()">Guardar</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		`,

		data() {
			return {
				modal: null,
				items: [],
				tipos: [],
				itemsSiat: false,
				motivo_aunlaciones: [],
				sync_sucursal_id: 0,
				form: {
					sucursal_id: 0,
					codigo_tipo_punto_venta: '',
					nombre_punto_venta: '',
					descripcion: null
				},

			};
		},
		computed:
		{
			esComisionista() {
				console.log(this.form.codigo_tipo_punto_venta == 1)
				return [1].indexOf(this.form.codigo_tipo_punto_venta) > -1;
			}
		},
		methods:
		{
			async getPuntosVentas() {
				const sucursal = this.sync_sucursal_id
				const all_status = 1; //true para traer todos los estados
				const res = await this.$root.http.Get(`?/siat/api_sincronizaciones/sync_punto_ventas/${sucursal}/${all_status}`);
				this.items = res.data;
			},
			async getSucursales() {
				//implementar cuando se tenga sucursales
			},
			async getTipoPuntoVentas() {
				const sucursal = this.sync_sucursal_id
				const res = await this.$root.http.Get(`?/siat/api_sincronizaciones/sync_tipo_punto_ventas/${sucursal}`);
				this.tipos = res.data.RespuestaListaParametricas.listaCodigos;
			},

			nuevo() {
				//this.getTipoPuntoVentas();
				this.form = {
					sucursal_id: 0,
					codigo_tipo_punto_venta: '',
					nombre_punto_venta: '',
					descripcion: null
				}
				this.form.contrato_fecha_inicio = new Date();
				this.form.contrato_fecha_fin = new Date();
				this.$refs.puntocomisionistafechainicio.value = sb_formatdate(new Date(), 'Y-m-d');
				this.$refs.puntocomisionistafechafin.value = sb_formatdate(new Date(), 'Y-m-d');

				this.modal.modal('show');
			},
			async guardar() {
				try {
					this.$root.$processing.show('Guardando datos...');
					for (let t of this.tipos) {
						if (t.codigoClasificador == this.form.codigo_tipo_punto_venta) {
							this.form.descripcion = t.descripcion;
							break;
						}
					}
					const resp = await this.$root.http.Post('?/siat/api_punto_ventas/crear_punto_venta', this.form);
					this.show_notify(resp);
					this.$root.$processing.hide();
					this.modal.modal('hide');
					this.getPuntosVentas();
				}
				catch (e) {
					this.$root.$processing.hide();
					console.log('ERROR', e);
					this.$root.$toast.ShowError(e.error || e.message || 'Error desconocido');
				}
			},
			async sync() {
				try {
					this.$root.$processing.show('Sincronizando datos...');
					//const res = await this.$root.http.Post('?/siat/sync_puntos_venta', this.form_sync);
					const sucursal = this.sync_sucursal_id
					const all_status = 1; //true para traer todos los estados
					const sync = 1; //true para sincronizar desde siat
					const res = await this.$root.http.Get(`?/siat/api_sincronizaciones/sync_punto_ventas/${sucursal}/${all_status}/${sync}`);

					this.items = res.data
					this.itemsSiat = true;
					//console.log(this.items)
					this.$root.$processing.hide();

				}
				catch (e) {
					this.$root.$processing.hide();
					console.log('ERROR', e);
				}
			},

			async cierre_punto_venta(item) {

				let self = this
				const punto_venta_id = parseInt(item.codigo) || null
				const nombre = (item.nombre) ? item.nombre.toUpperCase() : 'NO ENCONTRADO'

				bootbox.dialog({
					closeButton: false,
					message: `<div class="row">
								<div class="col-sm-12 pt-4">
									<h5>
										<strong class="form-text text-muted">
											¿ESTAS SEGURO DE REALIZAR EL CIERRE DE </br> &nbsp;PUNTO DE VENTA " ${nombre} " CON EL CODIGO : " ${punto_venta_id} "?
										</strong>
									</h5>
								</div>
							</div>`,
					buttons: {
						confirm: {
							label: `Si, cerrar !`,
							className: 'btn-primary mr-3',
							callback: function (result) {
								self.confirm_cierre_punto_venta({ punto_venta_id: punto_venta_id, nombre: nombre });
							}
						},
						cancel: {
							label: `Cancelar`,
							className: 'btn-default mr-3',
						}
					},
				});
			},

			async confirm_cierre_punto_venta(params) {
				try {
					this.$root.$processing.show(`CERRANDO PUNTO DE VENTA ${params.nombre} EN SIAT...`);
					const resp = await this.$root.http.Put('?/siat/api_punto_ventas/cerrar_punto_venta', params);
					this.$root.$processing.hide();
					this.show_notify(resp);
					this.getPuntosVentas();
				}
				catch (e) {
					this.$root.$processing.hide();
					console.log('ERROR', e);
					this.$root.$toast.ShowError('Ocurrio un error al borrar el Punto de Venta');
				}
			},

			crear_pv(item) {
				this.$root.$toast.ShowSuccess('Punto de venta enviado a la base de datos correctamente');
			},

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
		},
		mounted() {
			this.modal = jQuery(this.$refs.modal);
		},
		created() {
			this.getTipoPuntoVentas();
			this.getPuntosVentas();
		}
	};
	SBFramework.AppComponents = {
		'siat-puntos-venta': ns.ComPuntosVenta,
	};
})(SBFramework.Components.Siat);
