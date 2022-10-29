(function(ns)
{
	ns.UserPortal = {
		template: `<div class="container">
			<div class="row">
				<div class="col-12"><h1>Bienvenido al portal Investec de Mis Facturas</h1></div>
				<div class="col-12">
					<p>Dentro de este portal podra obtener información de sus facturas las 24 horas del dia</p>
				</div>
			</div>
			<div class="row justify-content-center">
				<template v-if="!com_current">
					<div class="col-4">
						<form>
							<div class="mb-3">
								<div class="input-group">
									<input type="text" class="form-control" v-model="keyword" 
										v-on:keydown.enter="$event.preventDefault();search()"
										 />
									<button type="button" class="btn btn-primary" v-on:click="search()">Buscar</button>
								</div>
							</div>
						</form>
					</div>
				</template>
				<template v-else>
					<keep-alive>
						<component v-bind:is="com_current" v-bind="com_args"></component>
					</keep-alive>
				</tempalte>
				
			</div>
		</div>`,
		components: {
			'user-invoices': ns.UserInvoices
		},
		data()
		{
			return {
				com: null,
				com_key: '',
				com_args: {},
				keyword: ''
			};
		},
		computed: 
		{
			com_current: function()
			{
				return this.com;
			},
		},
		methods: 
		{
			async search()
			{
				try
				{
					this.$root.$processing.show('Por favor espere...');
					const res = await this.$root.api.Get('/invoices/siat/v2/user-search?keyword=' + this.keyword);
					this.$root.$processing.hide();
					if( !res.data || res.data.length <= 0 )
					{
						alert('Usted no tiene registro de facturas emitidas');
						return;
					}
					console.log(res);
					this.com_args = {items: res.data};
					this.com_key = Date.now();
					this.com = 'user-invoices';
				}
				catch(e)
				{
					this.$root.$processing.hide();
				}
			}
		},
		mounted()
		{
			
		},
		created()
		{
			
		}
	};
	SBFramework.AppComponents = {
		'user-portal': ns.UserPortal, 
	};
})(SBFramework.Components.Siat);