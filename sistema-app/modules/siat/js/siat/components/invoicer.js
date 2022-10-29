(function(ns)
{
	ns.ComInvoicer = {
		template: `<div id="com-invoicer" style="position:relative;">
			<div class="mb-1 shadow" style="position:sticky;top:114px;z-index:100;background:#fff;margin:-15px;">
				<div class="row p-1">
					<div class="col-6 col-sm-3">
						<template v-if="!activeEvent">
							<button class="btn dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" data-coreui-toggle="dropdown" aria-expanded="false"
								v-bind:class="{'btn-success': !activeEvent, 'btn-danger': activeEvent}">
								{{ activeEvent ? 'Fuera de linea' : 'En Linea' }}
							</button>
							<ul class="dropdown-menu" style="z-index:800;">
								<li><a class="dropdown-item" href="javascript:;" v-on:click="openEvent(1)">1. CORTE DEL SERVICIO DE INTERNET</a></li>
								<li><a class="dropdown-item" href="javascript:;" v-on:click="openEvent(2)">2. INACCESIBILIDAD AL SERVICIO WEB DE LA ADMINISTRACIÓN TRIBUTARIA</a></li>
								<li><a class="dropdown-item" href="javascript:;" v-on:click="openEvent(3)">3. INGRESO A ZONAS SIN INTERNET POR DESPLIEGUE DE PUNTO DE VENTA EN VEHICULOS AUTOMOTORES</a></li>
								<li><a class="dropdown-item" href="javascript:;" v-on:click="openEvent(4)">4. VENTA EN LUGARES SIN INTERNET</a></li>
							</ul>
						</template>
						<a v-else class="btn btn-danger w-100" href="javascript:;" v-on:click="abriCerrarEvento">
							{{ this.activeEvent.evento_id < 5 ? 'FUERA DE LINEA' : 'CERRAR CONTINGENCIA' }}
						</a>
					</div>
					<div class="col-12 col-sm-4">
						<div class="">
							<select class="form-control form-select" v-model="invoice.punto_venta" required>
								<option value="">-- punto de venta --</option>
								<option value="0">Punto de Venta 0 (por defecto)</option>
								<option v-bind:value="pv.codigo" v-for="(pv, ipv) in puntosventa">
									({{ pv.codigo }}) {{ pv.nombre }}
								</option>
							</select>
							<div class="invalid-feedback">Debe debe seleccionar un punto de venta</div>
						</div>
					</div>
					<div class="col-6 col-sm-4">
						<div class="input-group">
							<select class="form-control form-select" required v-model="invoice.codigo_moneda">
								<option value="">-- moneda --</option>
								<option v-bind:value="m.codigoClasificador" v-for="(m, mi) in monedas">
									{{ m.descripcion }}
								</option>
							</select>
							<input type="text" class="form-control" v-model="invoice.tipoDeCambio"" />
							<div class="invalid-feedback">Debe seleccionar el tipo de moneda</div>
						</div>
					</div>
					<div class="col-1">
						<a class="btn btn-primary float-end" data-coreui-toggle="offcanvas" data-bs-toggle="offcanvas" href="#offcanvasInvoice" 
							role="button" aria-controls="offcanvasExample">
							<i class="fa fa-bars"></i>
						</a>
					</div>
				</div>
			</div>
			<div class="alert alert-warning" v-if="activeEvent">
				<div>
					<b>Evento/Contingencia activa!!!</b>
				</div> 
				<div><b>{{ activeEvent.descripcion }}, {{ activeEvent.fecha_inicio}} - {{ activeEvent.fecha_fin }}</b></div>
				<div>Todas las facturas generadas se almacenaran localmente y no se enviaran a SIAT hasta cerrar el evento/contingencia.</div>
			</div>
			<form ref="forminvoice" novalidate>
			<div class="container-fluid">
				<div class="row">
					<div class="col-12 col-sm-4">
						<div class="mb-1">
							<label for="customer_name">Cliente</label>
							<div class="input-group" style="position:relative;">
								<input type="text" id="customer_name" class="form-control" autocomplete="off" 
									v-model="keyword_customer" 
									v-on:keyup="buscarCliente('customer')" required />
								<button type="button" class="btn btn-primary" title="Buscar cliente"><i class="fa fa-search"></i></button>
								<button type="button" class="btn btn-warning" title="Crear cliente" v-on:click="crearCliente()">
									<i class="fa fa-plus"></i>
								</button>
								<div class="invalid-feedback">Debe ingresar el nombre del cliente</div>
								<template v-if="customers_list.length > 0">
									<div class="com-completion-results shadow" style="z-index:500;position:absolute;top:100%;width:100%;background:#fff;border:1px solid #ececec;max-height:250px;overflow:auto;" 
										v-bind:style="{display: customers_list.length > 0 ? 'block' : 'none'}">
										<ul style="list-style:none;padding:0;margin:0;">
											<li v-for="(item, index) in customers_list" v-on:click="selectCustomer(item, index)" style="cursor:pointer;:hover:background:#ececec;padding:6px;border-bottom:1px solid #ececec;">
												<div class="container-fluid p-0 dropdown-item">
													<div class="row p-2 no-gutters">
														<div class="col-sm-2">
															<img v-bind:src="lt.baseurl + '/images/client.png'" alt="" class="rounded-circle img-fluid img-thumbnail" />
														</div>
														<div class="col-sm-10">
															<div class="search-item-title">{{ item.first_name }} {{ item.last_name }}</div>
															<div class="search-item-excerpt">{{ item.email }}</div>
														</div>
													</div>
												</div>
											</li>
										</ul>
									</div>
								</template>
							</div>
						</div>
					</div>
					<div class="col-12 col-sm-3">
						<div class="mb-1">
							<label class="d-block">
								NIT/CI
							</label>
							<input type="text" class="form-control" required v-model="keyword_nit" />
							<div class="invalid-feedback">Debe ingresar el NIT/CI del cliente</div>
						</div>
					</div>
					<div class="col-12 col-sm-1">
						<div class="mb-1">
							<label title="Complemento">Com</label>
							<input type="text" class="form-control" v-model="invoice.complemento" />
						</div>
					</div>
					<div class="col-12 col-sm-4">
						<div class="mb-1">
							<label>Tipo de Documento de Identidad</label>
							<select class="form-control form-select" v-model="invoice.tipo_documento_identidad" required>
								<option value="">-- tipo de documento --</option>
								<option v-bind:value="tc.codigoClasificador" v-for="(tc, itc) in tipos_documentos">
									{{ tc.descripcion }}
								</option>
							</select>
							<div class="invalid-feedback">Debe seleccionar el tipo de documento</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col">
						<div class="mb-2">
							<label v-on:click="controlTributario('normal')" style="font-size:11px;">
								<input type="radio" name="im" value="normal" v-model="tipo_cliente" />
								Normal
							</label>
							<label v-on:click="controlTributario('control_tributario')" style="font-size:11px;">
								<input type="radio" name="im" value="control_tributario" v-model="tipo_cliente" />
								Control Tributario
							</label>
							<label v-on:click="controlTributario('ventas_menores')" style="font-size:11px;">
								<input type="radio" name="im" value="ventas_menores" v-model="tipo_cliente" />
								Ventas Menores del Dia
							</label>
							<label v-on:click="controlTributario('caso_especial')" style="font-size:11px;">
								<input type="radio" name="im" value="caso_especial" v-model="tipo_cliente" />
								Caso Especial
							</label>
						</div>
					</div>
				</div>
				<div class="row" v-show="activeEvent && [5, 6, 7].indexOf(activeEvent.evento_id) != -1">
					<div class="col-6 col-sm-3">
						<div class="mb-3">
							<label>Nro. Factura (talonario)</label>
							<input type="text" class="form-control" v-model="invoice.data.nro_factura" />
						</div>
					</div>
					<div class="col-6 col-sm-3">
						<div class="mb-3">
							<label>Fecha</label>
							<input type="date" class="form-control" ref="fecha" />
						</div>
					</div>
					<div class="col-6 col-sm-3">
						<div class="mb-3">
							<label>Hora</label>
							<input type="time" class="form-control" ref="hora" />
						</div>
					</div>
				</div>
				<template v-if="scom_detalle">
					<keep-alive>
						<component ref="headercom" v-bind:is="sector_com_detalle" v-bind:invoice="invoice"></component>
					</keep-alive>
				</template>
				<div class="row">
					<div class="col-12 col-sm-12">
						<div class="mb-3" style="position:relative;">
							<label>Adicionar item</label>
							<div class="input-group">
								<input type="text" v-model="keyword" class="form-control" placeholder="Nombre del item/producto"
									v-on:keydown.enter="$event.preventDefault()"
									v-on:keyup="buscarProducto()"
									v-on:keyup.enter="addItem($event)" />
								<button class="btn btn-primary" v-on:click="addItem($event)">Adicionar</button>
							</div>
							<template v-if="products_list.length > 0">
								<div class="com-completion-results shadow" style="z-index:500;position:absolute;top:100%;width:100%;background:#fff;border:1px solid #ececec;max-height:250px;overflow:auto;" 
									v-bind:style="{display: products_list.length > 0 ? 'block' : 'none'}">
									<ul style="list-style:none;padding:0;margin:0;">
										<li v-for="(item, index) in products_list" v-on:click="selectProduct(item, index)" style="cursor:pointer;:hover:background:#ececec;padding:6px;border-bottom:1px solid #ececec;">
											<div class="container-fluid p-0 dropdown-item">
												<div class="row p-2 no-gutters">
													<div class="col-sm-2">
														<span class="rounded-circle img-fluid img-thumbnail" style="font-size:30px;">
															<i class="fa fa-box"></i>
														</span>
													</div>
													<div class="col-sm-10">
														<div class="search-item-title">({{ item.code }}) {{ item.name }}</div>
														<div class="search-item-excerpt"><b>Precio:</b> {{ item.price }}</div>
													</div>
												</div>
											</div>
										</li>
									</ul>
								</div>
							</template>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-12"><h2>Detalle</h2></div>
				</div>
				<div class="row border-bottom">
					<div class="col-sm-6 text-center"><b>Item</b></div>
					<div class="col-sm-2 text-center"><b>Cant.</b></div>
					<div class="col-sm-2 text-center"><b>Precio</b></div>
					<div class="col-sm-2 text-center"><b>Total</b></div>
				</div>
				<template v-if="invoice.items.length > 0 ">
					<template v-for="(item, index) in invoice.items">
						<template v-if="item_edit == index">
							<div class="row">
								<div class="col-12 col-sm-2">
									<div class="mb-2">
										<label>Codigo</label><input type="text" class="form-control" required v-model="item.product_code" />
									</div>
								</div>
								<div class="col-12 col-sm-4">
									<div class="mb-2">
										<label>Producto</label><input type="text" class="form-control" required v-model="item.product_name" />
									</div>
								</div>
								<div class="col-12 col-sm-2">
									<div class="mb-2"><label>Cantidad</label><input type="number" class="form-control" v-model="item.quantity" /></div>
								</div>
								<div class="col-12 col-sm-2">
									<div class="mb-2"><label>Precio</label><input type="text" class="form-control" v-model="item.price" /></div>
								</div>
								<div class="col-12 col-sm-2">
									<div class="mb-2"><label>Descuento</label><input type="text" class="form-control" v-model="item.discount" /></div>
								</div>
								<template v-if="!item.product_id || item.product_id <= 0">
									<div class="col-12 col-sm-4">
										<div class="mb-2">
											<label class="">Actividad</label>
											<select class="form-control form-select" required v-model="item.codigo_actividad">
												<option value="">-- actividad --</option>
												<option v-for="(a, ai) in lista_actividades" v-bind:value="a.codigoCaeb">
													{{ a.descripcion }}
												</option>
											</select>
										</div>
									</div>
									<div class="col-12 col-sm-4">
										<div class="mb-2">
											<label class="">Tipo Producto</label>
											<select class="form-control form-select" required v-model="item.codigo_producto_sin">
												<option value="">-- producto SIN --</option>
												<option v-for="(sp, spi) in item_sin_products" v-bind:value="sp.codigoProducto">
													{{ sp.descripcionProducto }}
												</option>
											</select>
										</div>
									</div>
									<div class="col-12 col-sm-4">
										<div class="mb-2">
											<label>Unidad Medida</label>
											<select class="form-select" v-model="item.unidad_medida">
												<template v-for="(um, umi) in unidadesMedida">
													<option v-bind:value="um.codigoClasificador">{{ um.descripcion }}</option>
												</template>
											</select>
										</div>
									</div>
								</template>
								<div class="col-12 col-sm-3">
									<div class="mb-2"><label>Num. Serie</label><input type="text" class="form-control" v-model="item.numero_serie" /></div>
								</div>
								<div class="col-12 col-sm-3">
									<div class="mb-2"><label>Num. IMEI</label><input type="text" class="form-control" v-model="item.numero_imei" /></div>
								</div>
							</div>
							<template v-if="scom_item">
								<keep-alive>
									<component v-bind:is="sector_com_item" v-bind:invoice="invoice" v-bind:item="item"></component>
								</keep-alive>
							</template>
							<div class="row">
								<div class="col">
									<button type="button" class="btn btn-sm btn-primary" v-on:click="saveItem(index)">Guardar</button>
								</div>
							</div>
						</template>
						
						<div v-else class="row border-bottom p-2" style="position:relative;">
							<div class="col-12 col-sm-6">
								<div class="fw-bold text-primary" style="font-size:13px;">Codigo: {{ item.product_code }}</div>
								<div>{{ item.product_name }}</div>
							</div>
							<div class="col-12 col-sm-2"><div class="text-center">{{ item.quantity }}</div></div>
							<div class="col-12 col-sm-2"><div class="text-end">{{ item.price.toFixed(2) }}</div></div>
							<div class="col-12 col-sm-2"><div class="text-end">{{ item.total.toFixed(2) }}</div></div>
							<div class="col-12 col-sm-12">
								<button type="button" class="btn btn-sm btn-primary" v-on:click="editItem(index)"><i class="fa fa-edit"></i></button>
								<button type="button" class="btn btn-sm btn-danger" v-on:click="removeItem(index)"><i class="fa fa-trash"></i></button>
							</div>
						</div>
					</template>
				</template>
				<div v-else><h5 class="text-primary p-3 text-center">Aun no existen items en su factura</h5></div>
				<div class="row border-bottom">
					<div class="col-12 col-sm-10 text-end"><b>Subtotal:</b></div>
					<div class="col-12 col-sm-2 text-end"><b>{{ invoice.subtotal.toFixed(2) }}</b></div>
					
					<template v-if="with_giftcard">
						<div class="col-12 col-sm-10 text-end"><div style="line-height:37px;"><b>Monto Gift Card:</b></div></div>
						<div class="col-12 col-sm-2">
							<input type="text" class="form-control text-end" v-model="invoice.monto_giftcard" required />
						</div>
					</template>
					<div class="col-12 col-sm-10 text-end"><div style="line-height:37px;"><b>Descuento:</b></div></div>
					<div class="col-12 col-sm-2">
						<input type="text" class="form-control text-end" v-model="invoice.discount"/>
					</div>
						
					<div class="col-12 col-sm-10 text-end"><b>Total Base Credito Fiscal:</b></div>
					<div class="col-12 col-sm-2 text-end"><b>{{ invoice.total.toFixed(2) }}</b></div>
					
					<div class="col-12 col-sm-10 text-end"><b>Crédito fiscal:</b></div>
					<div class="col-12 col-sm-2 text-end"><b>{{ invoice.total_tax.toFixed(2) }}</b></div>
					
					
					<div class="col-12 col-sm-10 text-end"><b>Metodo de pago:</b></div>
					<div class="col-12 col-sm-2 text-end">
						<select class="form-select form-select-sm bg-warning" v-model="invoice.codigo_metodo_pago" required>
							<option value="">-- metodo de pago --</option>
							<option v-bind:value="mp.codigoClasificador" v-for="(mp, imp) in metodos_pago">{{ mp.descripcion }}</option>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-12 col-sm-12">
						<div class="mt-3"><button type="button" class="btn btn-primary" v-on:click="save()">Generar Factura</button></div>
					</div>
				</div>
			</div>
			</form>
			<div ref="modalcustomer" class="modal fade">
				<div class="modal-dialog">
					<form ref="formcustomer" class="modal-content" novalidate>
						<div class="modal-header"><h5 class="modal-title">Crear Cliente</h5></div>
						<div class="modal-body">
							<div class="mb-3">
								<label>Nombres</label>
								<input type="text" class="form-control" v-model="customer.first_name" />
								<div class="invalid-feedback">Ingrese el nombre del cliente</div>
							</div>
							<div class="mb-3">
								<label>Apellidos/Razon Social</label>
								<input type="text" class="form-control" v-model="customer.last_name" required />
								<div class="invalid-feedback">Ingrese el apellido del cliente</div>
							</div>
							<div class="mb-3">
								<label>NIT/CI</label>
								<input type="text" class="form-control" v-model="customer.meta._nit_ruc_nif" required />
								<div class="invalid-feedback">Ingrese el NIT/CI del cliente</div>
							</div>
							<div class="mb-3">
								<label>Direccion</label>
								<input type="text" class="form-control" v-model="customer.address_1" />
								<div class="invalid-feedback">Ingrese la direccion del cliente</div>
							</div>
							<div class="mb-3">
								<label>Email</label>
								<input type="email" class="form-control" v-model="customer.email" />
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-coreui-dismiss="modal">Cancelar</button>
							<button type="button" class="btn btn-primary" v-on:click="guardarCliente()">Guardar</button>
						</div>
					</form>
				</div>
			</div>
			<div ref="modaldatostarjeta" class="modal fade">
				<div class="modal-dialog">
					<form ref="formtarjeta" class="modal-content" novalidate>
						<div class="modal-header"><h5 class="modal-title">Datos Tarjeta</h5></div>
						<div class="modal-body">
							<p>Ingrese los datos de la tarjeta de cr&eacute;dito/d&eacute;bito</p>
							<div class="mb-3">
								<label>Nro. de Tarjeta</label>
								<div class="input-group">
									<span class="input-group-text"><i class="fa fa-credit-card"></i></span>
									<input type="text" class="form-control" required pattern="[0-9]{16}" maxlength="16" ref="inputcard" />
									<div class="invalid-feedback">Debe un n&uacute;mero de tarjeta v&aacute;lido</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-coreui-dismiss="modal">Cancelar</button>
							<button type="button" class="btn btn-primary" v-on:click="save()">Continuar</button>
						</div
					</form>
				</div>
			</div>
			<div ref="modalcerrarevento" class="modal fade">
				<div class="modal-dialog">
					<form ref="formcerrar" action="" method="" class="modal-content" novalidate>
						<div class="modal-header">
							<h5 class="modal-title">Cerrar Evento</h5>
							<button class="btn-close" type="button" data-coreui-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<template v-if="activeEvent">
								<siat-cerrar-evento 
									ref="comcerrar" 
									v-bind:evento="activeEvent"
									v-on:evento-cerrado="eventoCerrado" />
							</template>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-danger" data-dismiss="modal" data-coreui-dismiss="modal">Cerrar</button>
							<button type="button" class="btn btn-primary" v-on:click="cerrarEvento()">Cerrar Evento</button>
						</div>
					</form>
				</div>
			</div>
			<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasInvoice" aria-labelledby="offcanvasExampleLabel">
			  <div class="offcanvas-header">
			    <h5 class="offcanvas-title" id="offcanvasExampleLabel">Opciones Avanzadas</h5>
			    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" data-coreui-dismiss="offcanvas" aria-label="Close"></button>
			  </div>
			  <div class="offcanvas-body">
				<div class="mb-3">
					<label>Sucursal</label>
					<select class="form-select" v-model="invoice.codigo_sucursal">
						<option value="0">Sucursal Principal - Casa Matriz</option>
					</select>
				</div>
				<div class="mb-3">
					<label>Documento Sector</label>
					<select class="form-select" v-model="documento_sector">
						<option v-bind:value="ds.com" v-for="(ds, ids) in sectores">{{ ds.nombre }}</option>
					</select>
				</div>
			  </div>
			</div>
			
			<div ref="modalimprimir" class="modal fade show">
				<div class="modal-dialog">
					<div class="modal-content" novalidate>
						<div class="modal-header">
							<h5 class="modal-title">Impresion</h5>
							<button class="btn-close" type="button" data-coreui-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body row">
							<div class="col-3 text-center">
								<i class="fa fa-print" style="font-size:50px;"></i>
							</div>
							<div class="col-9">
								<p>La factura fue generada correctamente</p>
								<p>Desea imprimir la factura?</p>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-danger" data-dismiss="modal" data-coreui-dismiss="modal">Cerrar</button>
							<a v-if="generatedInvoice" 
								v-bind:href="generatedInvoice.print_url" 
								target="_blank" 
								class="btn btn-primary"
								v-on:click="modalImprimir.hide()">
								Imprimir
							</a>
							<a v-if="generatedInvoice" 
								v-bind:href="generatedInvoice.print_url + '?tpl=rollo'" 
								target="_blank" 
								class="btn btn-warning"
								v-on:click="modalImprimir.hide()">
								Imprimir Ticket
							</a>
						</div>
					</div>
				</div>
			</div>
			<div ref="modalexcepcion" class="modal fade show">
				<div class="modal-dialog">
					<div class="modal-content" novalidate>
						<div class="modal-header">
							<h5 class="modal-title">Verificacion de NIT</h5>
							<button class="btn-close" type="button" data-coreui-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body row">
							<div class="col-3 text-center">
								<i class="fa fa-exclamation" style="font-size:50px;"></i>
							</div>
							<div class="col-9">
								<p>El NIT del cliente no es valido</p>
								<p>Desea generar la factura de todas formas?</p>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-danger" data-dismiss="modal" data-coreui-dismiss="modal">Cerrar</button>
							<button type="button" data-dismiss="modal" data-coreui-dismiss="modal"
								class="btn btn-primary"
								v-on:click="invoice.excepcion = 1;save()">
								Generar Factura
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>`,
		components: {
			'siat-cerrar-evento': ns.CerrarEvento,
			'educativo_item': ns.ItemColegio,
			'educativo_detalle': ns.DetalleColegio,
			'turistico_item': ns.ItemTuristico,
			'turistico_detalle': ns.DetalleTuristico,
			'hotel_item': ns.ItemHotel,
			'hotel_detalle': ns.DetalleHotel,
			'hospital_item': ns.ItemHospital,
			'hospital_detalle': ns.DetalleHospital,
			'financiera_item': ns.ItemFinanciera,
			'financiera_detalle': ns.DetalleFinanciera,
			'tasacero_item': ns.ItemTasaCero,
			'tasacero_detalle': ns.ItemTasaCero,
			'exportservicio_item': ns.ItemExportacionServicio,
			'exportservicio_detalle': ns.DetalleExportacionServicio,
			'export_item': ns.ItemExportacion,
			'export_detalle': ns.DetalleExportacion,
		},
		mixins: [SBFramework.Mixins.SiatOnlineStatus],
		data()
		{
			return {
				sectores: [
					{nombre: 'Compra Venta', com: null, codigo: 1},
					//*
					{nombre: 'Servicio Turistico y Hospedaje', com: 'turistico', codigo: 6},
					{nombre: 'Tasa Cero', com: 'tasacero', codigo: 8},
					{nombre: 'Sector Educativo', com: 'educativo', codigo: 11},
					//{nombre: 'Entidades Financieras', com: 'financiera', codigo: 15},
					{nombre: 'Hoteles', com: 'hotel', codigo: 16},
					{nombre: 'Hospitales y Clinicas', com: 'hospital', codigo: 17},
					{nombre: 'Comercial Exportacion', com: 'export', codigo: 3},
					{nombre: 'Comercial Exportacion Servicio', com: 'exportservicio', codigo: 28},
					//*/
				],
				documento_sector: null,
				keyword: '',
				itemfound: null,
				item_edit: -1,
				puntosventa: [],
				tipos_productos: [],
				modalCustomer: null,
				modalDatosTarjeta: null,
				modalCerrarEvento: null,
				modelImprimir: null,
				modalExcepcion: null,
				invoice: new SBFramework.Models.Invoice(),
				generatedInvoice: null,
				customer: {},
				currentCustomer: {id: 0, customer_id: 0,},
				searchTimeout: null,
				searchProdTimeout: null,
				keyword_customer: '',
				keyword_nit: '',
				customers_list: [],
				products_list: [],
				unidadesMedida: [],
				monedas: [],
				tipos_documentos: [],
				metodos_pago: [],
				lista_actividades: [],
				activeEvent: null,
				tipo_cliente: 'normal',
				with_giftcard: false,
				scom_detalle: null,
				scom_item: null,
				isOnline: true,
			};
		},
		computed:
		{
			tipos_productos_filter()
			{
				if( !this.actividad_economica )
					return this.tipos_productos;
				return this.tipos_productos.filter( tp => tp.codigoActividad == this.actividad_economica );
				//return this.tipos_productos
			},
			sector_com_detalle()
			{
				return this.scom_detalle;
			},
			sector_com_item()
			{
				return this.scom_item;
			},
			current_doc_sector()
			{
				let doc_sector = this.sectores.find( ds => ds.com == this.documento_sector);
				return doc_sector ? doc_sector.codigo : 1;
			},
			item_sin_products()
			{
				if( this.item_edit <= -1 )
					return [];
				return this.filterSinProducts(this.invoice.items[this.item_edit].codigo_actividad);
			}
		},
		watch:
		{
			documento_sector()
			{
				console.log('documento_sector', this.documento_sector);
				this.invoice.codigo_documento_sector = 1; //compra venta
				this.invoice.tipo_factura_documento = 1; //credito fiscal
				this.scom_detalle = null;
				this.scom_item 	= null;
				this.invoice.items = [];
				if( !this.documento_sector )
				{
					this.calculateTotals();
					return;
				}
				this.invoice.codigo_documento_sector = this.current_doc_sector;
				if( [6, 8, 28].indexOf(this.invoice.codigo_documento_sector) != -1 )
				{
					this.invoice.tipo_factura_documento = 2; //sin credito fiscal
				}
				console.log(this.invoice.codigo_documento_sector, this.invoice.tipo_factura_documento);
				this.scom_detalle = this.documento_sector + '_detalle';
				this.scom_item = this.documento_sector + '_item';
				console.log(this.scom_detalle, this.scom_item);
				this.calculateTotals();
			},
			'invoice.punto_venta'()
			{
				this.checkActiveEvent();
			},
			'invoice.monto_giftcard'()
			{
				if( isNaN(parseFloat(this.invoice.monto_giftcard)) )
				{
					this.invoice.monto_giftcard = 0;
					return;
				}
				this.invoice.monto_giftcard = parseFloat(parseFloat(this.invoice.monto_giftcard).toFixed(2));
				/*
				if( isNaN(parseFloat(this.invoice.total)) )
				{
					this.invoice.total = 0; 
				}
				this.invoice.total = parseFloat(parseFloat(this.invoice.total).toFixed(2));
				this.invoice.total -= this.invoice.monto_giftcard;
				*/
			},
			'invoice.codigo_metodo_pago'()
			{
				this.invoice.monto_giftcard = 0;
				if( [27,28,29,30,35,40,49,53,60,64,68,72,76,77,78,86,94,102,109,115,120,124,128,129,130,138,146,153,159,164,168,172,173,174].indexOf(this.invoice.codigo_metodo_pago) != -1)
				{
					this.with_giftcard = true;
					return true;
				}
				this.with_giftcard = false;
			},
			'invoice.discount'()
			{
				if( isNaN(parseFloat(this.invoice.discount)) )
				{
					this.invoice.discount = 0;
					//return;
				}	
				this.calculateTotals();
			},
			'invoice.monto_giftcard'()
			{
				/*
				if( this.invoice.monto_giftcard.toString().trim() <= 0 )
				{
					this.calculateTotals();
					return;
				}	
				*/	
				if( isNaN(parseFloat(this.invoice.monto_giftcard)) )
				{
					this.invoice.monto_giftcard = 0;
				}	
				this.calculateTotals();
			},
		},
		methods: 
		{
			filterSinProducts(codigoActividad)
			{
				return this.tipos_productos.filter( tp => tp.codigoActividad == codigoActividad );
			},
			resetCustomer()
			{
				this.keyword_customer = '';
				this.keyword_nit = '';
				this.customer = {
					first_name: '',
					last_name: '',
					address_1: '',
					email: '',
					meta: {
						_nit_ruc_nif: '',
					},
					
				};	
			},
			addItem(e, prod)
			{
				e ? e.preventDefault() : null;
				if( this.keyword.trim().length <= 0 )
					return false;
					
				let item_name = this.keyword;
				if( this.itemfound )
				{
					item_name = this.itemfound;
				}
				let found = this.invoice.items.find( item => prod && item.product_id == prod.id);
				if( typeof(found) != 'undefined' )
				{
					found.quantity++;
					found.total = (found.price * found.quantity) - found.discount;
				}
				else
				{
					this.invoice.items.push(
						{
							item_id: 0,
							invoice_id: 0,
							product_id: prod ? prod.id : 0,
							product_code: prod ? prod.code : '',
							product_name: prod ? prod.name : item_name,
							price: prod ? prod.price : 0,
							quantity: 1,
							total: prod ? prod.price : 0,
							unidad_medida: prod ? prod.unidad_medida : 58,
							numero_serie: prod ? prod.numserie : '',
							numero_imei: prod ? prod.imei : '',
							codigo_producto_sin: prod ? prod.codigo_sin : '',
							codigo_actividad: prod ? prod.codigo_actividad : '',
							discount: 0,
							data: {custom_fields: {}}
						}
					);
				}
				
				this.keyword = '';
				this.calculateTotals();
			},
			removeItem(index)
			{
				this.invoice.items.splice(index, 1);
				this.calculateTotals();
			},
			calculateTotals()
			{
				let total = 0;
				for(let item of this.invoice.items)
				{
					total += item.total;
				}
				this.invoice.subtotal = total;
				this.invoice.total = this.invoice.subtotal - this.invoice.discount - this.invoice.monto_giftcard;
				if( [6, 8, 28].indexOf(this.invoice.codigo_documento_sector) == -1 )
					this.invoice.total_tax = this.invoice.total * 0.13;
				else
					this.invoice.total_tax = 0;
			},
			editItem(index)
			{
				this.item_edit = index;
			},
			saveItem(index)
			{
				const item = this.invoice.items[index];
				item.quantity 	= isNaN(parseInt(item.quantity)) ? 0 : parseInt(item.quantity);
				item.price 		= isNaN(parseFloat(item.price)) ? 0 : parseFloat(item.price);
				item.discount 	= isNaN(parseFloat(item.discount)) ? 0 : parseFloat(item.discount);
				item.total 		= (item.quantity * item.price) - item.discount;
				this.calculateTotals();
				this.item_edit = -1;
			},
			async getPuntosVenta()
			{
				const res = await this.$root.api.Get('/invoices/siat/v2/puntos-venta');
				this.puntosventa = res.data;
			},
			async tiposProductos()
			{
				const res = await this.$root.api.Get('/invoices/siat/v2/lista-productos-servicios');
				this.tipos_productos = res.data.RespuestaListaProductos.listaCodigos;
			},
			async getUnidadesMedida()
			{
				const res = await this.$root.api.Get('/invoices/siat/v2/sync-unidades-medida');
				this.unidadesMedida = res.data.RespuestaListaParametricas.listaCodigos;	
			},
			async getMonedas()
			{
				const res = await this.$root.api.Get('/invoices/siat/v2/sync-tipos-moneda');
				this.monedas = res.data.RespuestaListaParametricas.listaCodigos;	
			},
			async getTiposDocumentosIdentidad()
			{
				const res = await this.$root.api.Get('/invoices/siat/v2/sync-documentos-identidad');
				this.tipos_documentos = res.data.RespuestaListaParametricas.listaCodigos;	
			},
			async getMetodosPago()
			{
				const res = await this.$root.api.Get('/invoices/siat/v2/sync-metodos-pago');
				this.metodos_pago = res.data.RespuestaListaParametricas.listaCodigos;	
			},
			async getListaActividades()
			{
				const res = await this.$root.api.Get('/invoices/siat/v2/actividades');
				this.lista_actividades = Array.isArray(res.data.RespuestaListaActividades.listaActividades) ?
					res.data.RespuestaListaActividades.listaActividades : [res.data.RespuestaListaActividades.listaActividades];
				if( this.lista_actividades.length > 0 )
					this.actividad_economica = this.lista_actividades[0].codigoCaeb;	
			},
			crearCliente()
			{
				this.modalCustomer.show();
			},
			async guardarCliente()
			{
				this.$refs.formcustomer.classList.remove('was-validated')
				try
				{
					if( !this.$refs.formcustomer.checkValidity() )
					{
						this.$refs.formcustomer.classList.add('was-validated')
						return false;
					}
					this.$root.$processing.show('Creando cliente...');
					if( !this.customer.company )
						this.customer.company = this.customer.last_name;
					const res = await this.$root.api.Post('/customers', this.customer);
					this.$root.$processing.hide();
					this.modalCustomer.hide();
				}
				catch(e)
				{
					this.$root.$processing.hide();
					alert(e.error || e.message || 'Ocurrio un error al crear el cliente');
				}
			},
			buscarCliente(by)
			{
				if( this.searchTimeout )
					clearTimeout(this.searchTimeout);
				let keyword = '';
				if( by == 'customer' )
					keyword = this.keyword_customer;
				else 
					keyword = this.keyword_nit;
				if( keyword.trim().length <= 0 )
					return;
				this.searchTimeout = setTimeout(async () => 
				{
					try
					{
						const res = await this.$root.api.Get(`/customers/search?keyword=${keyword}&user_id=${this.$root.user.user_id}`);
						this.customers_list = res.data;
						
					}
					catch(e)
					{
						console.log(e);
					}
				}, 500);
			},
			selectCustomer(item, index)
			{
				this.currentCustomer = item;
				this.keyword_customer = `${item.first_name} ${item.last_name}`;
				this.keyword_nit = item.meta && item.meta._nit_ruc_nif ? item.meta._nit_ruc_nif : '';
				this.customers_list = [];
				
			},
			buscarProducto(by)
			{
				if( this.searchProdTimeout )
					clearTimeout(this.searchProdTimeout);
				let keyword = this.keyword;
				if( keyword.trim().length <= 0 )
					return;
				this.searchProdTimeout = setTimeout(async () => 
				{
					try
					{
						const res = await this.$root.api.Get(`/invoices/products/search?keyword=${keyword}`);
						this.products_list = res.data;
						
					}
					catch(e)
					{
						console.log(e);
					}
				}, 500);
			},
			selectProduct(item, index)
			{
				//item.price = isNaN(parseFloat(item.price.toString())) ? 0 : parseFloat(item.price.toString());
				this.addItem(null, item);
				this.products_list = [];
			},
			async checkActiveEvent()
			{
				try
				{
					this.$root.$processing.show('Verificando...');
					const res = await this.$root.api.Get(`/invoices/siat/v2/eventos/activo?sucursal=${this.invoice.codigo_sucursal}&puntoventa=${this.invoice.punto_venta}`);
					this.activeEvent = res.data;
					this.$root.$processing.hide();
				}	
				catch(e)
				{
					this.$root.$processing.hide();
					alert(e.error || e.message || 'Error desconocido al verificar eventos activos');
				}
			},
			async openEvent(eventoId, system)
			{
				try
				{
					system = system === true ? 1 : 0;
					await this.checkActiveEvent();
					if( this.activeEvent )
						return;
					this.$root.$processing.show('Generando evento significativo...');
					const event 		= new SBFramework.Models.Evento();
					event.sucursal_id 	= 0;
					event.puntoventa_id = this.invoice.punto_venta;
					event.evento_id 	= eventoId;
					event.fecha_inicio 	= new Date();
					const res = await this.$root.api.Post(`/invoices/siat/v2/eventos?system=${system}`, event);
					console.log(res);
					if( res.data )
						this.activeEvent = res.data;
					this.$root.$processing.hide();
				}	
				catch(e)
				{
					this.$root.$processing.hide();
					alert(e.error || e.message || 'Error desconocido al verificar eventos activos');
				}
			},
			async save()
			{
				this.$refs.forminvoice.classList.remove('was-validated');
				this.$refs.formtarjeta.classList.remove('was-validated');
				try
				{
					this.modalDatosTarjeta.hide();
					if( !this.$refs.forminvoice.checkValidity() )
					{
						this.$refs.forminvoice.classList.add('was-validated');
						return;
					}
					if( this.currentCustomer == null || this.currentCustomer.customer_id <= 0 )
						throw {error: 'Debe seleccionar un cliente para la factura'};
					if( this.invoice.items.length <= 0 )
						throw {error: 'Debe adicionar almenos un item a la factura'};
						
					if( this.activeEvent && [5, 6, 7].indexOf( parseInt(this.activeEvent.evento_id) ) != -1)
					{
						if( !this.$refs.fecha )
							throw {error: 'Debe seleccionar la fecha de la factura'};
						if( !this.$refs.hora )
							throw {error: 'Debe seleccionar la hora de la factura'};
						if( !this.invoice.data.nro_factura )
							throw {error: 'Debe ingresar el numero de factura'};
							
						this.invoice.invoice_date_time = new Date(`${this.$refs.fecha.value} ${ this.$refs.hora.value}`);
						//this.evento.fecha_inicio.setSeconds((new Date()).getSeconds());
					}
					
					if( [6, 16].indexOf(this.invoice.codigo_documento_sector) != -1 )
					{
						this.invoice.data.custom_fields['fechaIngresoHospedaje'] = this.$refs.headercom.getFechaHoraIngreso();
					}
					//if( this.invoice.total <= 0 )
					//	throw {error: 'El monto de la factura no puede ser cero'};
					if( [2, 10, 16, 17, 18, 19, 20, 29, 39, 40, 41, 42, 43].indexOf( this.invoice.codigo_metodo_pago ) != -1 ) 
					{
						this.invoice.numero_tarjeta = this.$refs.inputcard.dataset.realvalue || null;
						this.$refs.formtarjeta.classList.add('was-validated');
						if( !this.invoice.numero_tarjeta )
						{
							this.modalDatosTarjeta.show();
							return;
						}
						if( !this.$refs.formtarjeta.checkValidity() )
						{
							this.modalDatosTarjeta.show();
							throw {error: 'Datos de tarjeta invalidos'};
						}
					}
					this.invoice.customer_id 			= this.currentCustomer.customer_id;
					this.invoice.customer 				= `${this.currentCustomer.first_name} ${this.currentCustomer.last_name}`;
					this.invoice.nit_ruc_nif 			= this.keyword_nit;
					this.invoice.actividad_economica	= this.actividad_economica;
					
					console.log(this.invoice);
					this.$root.$processing.show('Procesando factura...');
					const res = await this.$root.api.Post('/invoices', this.invoice);
					if( res.data && res.data.invoice_id )
					{
						this.generatedInvoice = res.data;
						this.modalImprimir.show();
					}
					else
					{
						this.$root.$toast.ShowSuccess('La factura fue generada correctamente');
					}
					this.activeEvent = null;
					await this.checkActiveEvent();
					this.$root.$processing.hide();
					
					this.reset();
					
				}
				catch(e)
				{
					this.$root.$processing.hide();
					if( e.response == 'error_nit' )
						this.modalExcepcion.show();
					else
						this.$root.$toast.ShowError(e.error || e.message || 'Error desconocido');
				}
			},
			reset()
			{
				const pv = this.invoice.punto_venta;
				this.invoice = 	new SBFramework.Models.Invoice();
				this.invoice.codigo_documento_sector = this.current_doc_sector;
				this.invoice.punto_venta = pv;
				this.resetCustomer();
				this.currentCustomer = {id: 0, customer_id: 0};
			},
			controlTributario(val)
			{
				if( val == 'control_tributario' )
				{
					this.keyword_customer = 'Control Tributario';
					this.keyword_nit = 99002;
				}
				else if( val == 'ventas_menores' )
				{
					this.keyword_customer = 'Ventas Menores del Dia';
					this.keyword_nit = 99003;
				}
				else if( val == 'caso_especial' )
				{
					this.keyword_customer = '';
					this.keyword_nit = 99001; 
				}
				else
				{
					this.keyword_customer = '';
					this.keyword_nit = '';
				}
			},
			async searchProduct()
			{
				
			},
			setInputCardEvents()
			{
				this.$refs.inputcard.addEventListener('keydown', function(e)
				{
					if( e.keyCode == 8 )
						return true;
					if( this.dataset.realvalue && this.dataset.realvalue.length >= 16 || this.readOnly )
					{
						e.preventDefault();
						return false;
					}
					if( (e.keyCode >= 48 && e.keyCode <= 57 ) || e.keyCode == 8 )
						return true;
					e.preventDefault();
					return false;
				});
				this.$refs.inputcard.addEventListener('keyup', function(e)
				{
					if( typeof this.dataset.realvalue == 'undefined')
						this.dataset.realvalue = '';
					if( e.keyCode == 8 && this.dataset.realvalue )
					{
						this.dataset.realvalue = this.dataset.realvalue.substring(0, this.dataset.realvalue.length - 1);
						return true;
					}
					if( this.dataset.realvalue && this.dataset.realvalue.length >= 16 )
					{
						return false;
					}
					//console.log(e);
					if( !this.value || this.value.length <= 0 )
					{
						this.dataset.realvalue = '';
						return true;
					}
					this.readOnly = true;
					
					const lastChar = this.value.substr(-1);
					this.dataset.realvalue += this.value.substr(-1);
					this.value = '';
					for(let i in this.dataset.realvalue)
					{
						if( i > 3 && i < 12 )
							this.value += '0';
						else
							this.value += this.dataset.realvalue[i];
					}
					this.readOnly = false;
					
				});
			},
			abriCerrarEvento()
			{
				//this.modalCerrarEvento.show();
				this.cerrarEvento();	
			},
			async cerrarEvento()
			{
				try
				{
					this.$root.$processing.show('Cerrando evento/contingencia');
					await this.$refs.comcerrar.send();
					this.reset();
					await this.checkActiveEvent();
					this.$root.$processing.hide();
				}
				catch(e)
				{
					this.$root.$processing.hide();
					alert(e.error || e.message || 'Ocurrio un error al cerrar el evento');
				}
				
			},
			eventoCerrado(xhr_res)
			{
				console.log('eventoCerrado', xhr_res);
				this.modalCerrarEvento.hide();
				this.$root.$toast.ShowSuccess('El evento fue cerrado correctamente');
				this.checkActiveEvent();	
			},
		},
		async mounted()
		{
			const frame = (window.bootstrap || window.coreui);
			
			this.modalCustomer = frame.Modal.getInstance(this.$refs.modalcustomer);
			if( !this.modalCustomer )
				this.modalCustomer = new frame.Modal(this.$refs.modalcustomer, {});
			
			this.modalDatosTarjeta = frame.Modal.getInstance(this.$refs.modaldatostarjeta);
			if( !this.modalDatosTarjeta )
				this.modalDatosTarjeta = new frame.Modal(this.$refs.modaldatostarjeta, {});
				
			this.modalCerrarEvento = frame.Modal.getInstance(this.$refs.modalcerrarevento);
			if( !this.modalCerrarEvento )
				this.modalCerrarEvento = new frame.Modal(this.$refs.modalcerrarevento, {});
				
			this.modalImprimir = frame.Modal.getInstance(this.$refs.modalimprimir);
			if( !this.modalImprimir )
				this.modalImprimir = new frame.Modal(this.$refs.modalimprimir, {});
			
			this.modalExcepcion = new frame.Modal(this.$refs.modalexcepcion, {});
			
			this.$root.$processing.show('Estableciendo datos...');
			await Promise.all([
				this.getPuntosVenta(), 
				this.getUnidadesMedida(), 
				this.getMonedas(), 
				this.getTiposDocumentosIdentidad(),
				this.tiposProductos(),
				this.getMetodosPago(),
				this.getListaActividades(),
				this.checkActiveEvent(),
				this.checkOnlineStatus(),
			]);
			this.$root.$processing.hide();
			this.setInputCardEvents();
			//this.modalImprimir.show();
			this.startInterval();
		},
		async created()
		{
			this.resetCustomer();
			
		}
	};
	SBFramework.AppComponents = {
		'siat-invoicer': ns.ComInvoicer, 
	};
})(SBFramework.Components.Siat);