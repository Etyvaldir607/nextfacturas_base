(function (ns) {
	ns.ComSyncCodigos = {
		template: `
		<div id="com-sync-codigos">
			<!--<div class="mb-3"><button type="button" class="btn btn-primary">Sincronizar</button></div>-->
			</br>
			<div class="row">
				<div class="col-12 col-sm-6">
					<h3>CUIS</h3>
					<div class="mb-3">
						<button type="button" class="btn btn-primary w-100" v-on:click="obtenerCuis()">Ver codigo</button>
						<button type="button" class="btn btn-primary w-100" v-on:click="obtenerCuis(1)">Renovar codigo</button>
					</div>
					</br>
					<div class="form-group mb-3">
						<label>Cuis Su:({{sucursal_local}}) / Pv:({{puntoventa_local}}) </label>
						<input type="text" name="" v-bind:value="cuis.codigo" class="form-control" />
					</div>
					<div class="form-group mb-3">
						<label>Fecha Expiracion</label>
						<input type="text" name="" class="form-control" v-bind:value="dateFormat(cuis.fechaVigencia)" />
					</div>
				</div>
				<div class="col-12 col-sm-6">
					<h3>CUFD</h3>
					<div class="mb-3">
						<button type="button" class="btn btn-primary w-100" v-on:click="obtenerCufd()">Ver codigo</button>
						<button type="button" class="btn btn-primary w-100" v-on:click="obtenerCufd(1)">Renovar codigo</button>
					</div>
					</br>
					<div class="mb-3">
						<label>Cufd Su:({{sucursal_local}}) / Pv:({{puntoventa_local}}) </label>
						<input type="text" name="" v-bind:value="cufd.codigo" class="form-control" />
					</div>
					<div class="mb-3">
						<label>Codigo Control</label>
						<input type="text" name="" v-bind:value="cufd.codigo_control" class="form-control" />
					</div>
					<div class="mb-3">
						<label>Fecha Expiracion</label>
						<input type="text" name="" v-bind:value="dateFormat(cufd.fecha_vigencia)" class="form-control" />
					</div>
					<div class="mb-3">
						<label>Direccion</label>
						<textarea name="" rows="2" v-bind:value="cufd.direccion" class="form-control" />
					</div>
				</div>
			</div>
		</div>`,
		props: {
			//sucursal: { type: Number, required: false, default: 0 },
			//puntoventa: { type: Number, required: false, default: 0 },
		},
		data() {
			return {
				cuis: {},
				cufd: {},
				sucursal_local: 0,
				puntoventa_local: 0,
			};
		},

		watch: {
			puntoventa_local(newVal, oldVal) {
				this.cuis = {};
				this.cufd = {};
				this.obtenerCuis();
				this.obtenerCufd();
			}
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
			async getAllCodes(sync = 0) {

				const sucursal = this.sucursal_local | null;
				const punto_venta = this.puntoventa_local | null

				const message = (!sync) ? 'Obteniendo codigo...' : 'Renovando codigo...'
				this.$root.$processing.show(message);

				try {
					//const res = await this.$root.http.Get(`?/siat/api_sincronizaciones/sync_cuis/${sucursal}/${punto_venta}/${sync}`);
					const res = await this.$root.http.Get(`?/siat/api_sincronizaciones/sync_cuis/${sucursal}/${punto_venta}`);
					this.cuis = res.data;
					const res2 = await this.$root.http.Get(`?/siat/api_sincronizaciones/sync_cufd/${sucursal}/${punto_venta}/${null}`);
					this.cufd = res2.data;
					this.$root.$processing.hide();
				}
				catch (e) {
					this.$root.$processing.hide();
					alert(e.error || e.message || 'Error desconocido');
				}
			},
			async obtenerCuis(sync = 0) {

				const sucursal = this.sucursal_local | null;
				const punto_venta = this.puntoventa_local | null;

				try {
					const message = (!sync) ? 'Obteniendo codigo...' : 'Renovando codigo...'
					this.$root.$processing.show(message);
					const res = await this.$root.http.Get(`?/siat/api_sincronizaciones/sync_cuis/${sucursal}/${punto_venta}`);
					this.cuis = res.data;
					this.$root.$processing.hide();
				}
				catch (e) {
					this.$root.$processing.hide();
					alert(e.error || e.message || 'Error desconocido');
				}
			},
			async obtenerCufd(sync = 0) {
				const sucursal = this.sucursal_local | null;
				const punto_venta = this.puntoventa_local | null;
				try {
					const message = (!sync) ? 'Obteniendo codigo...' : 'Renovando codigo...'
					this.$root.$processing.show(message);
					const res = await this.$root.http.Get(`?/siat/api_sincronizaciones/sync_cufd/${sucursal}/${punto_venta}/${null}`);
					this.cufd = res.data;
					this.$root.$processing.hide();
				}
				catch (e) {
					this.$root.$processing.hide();
					alert(e.error || e.message || 'Error desconocido');
				}
			},

		},
		mounted() {
		},
		created() {
			this.obtenerCuis();
			this.obtenerCufd();
		}
	};
})(SBFramework.Components.Siat);
