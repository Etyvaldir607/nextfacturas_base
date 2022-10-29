<?php
use SinticBolivia\SBFramework\Classes\SB_Route;
use SinticBolivia\SBFramework\Modules\Invoices\Controllers\SiatController;

SB_Route::module('invoices', $routes, '/admin/invoices')
	->get('/sync/?$', [SiatController::class, 'Default'])
	->get('/puntos-venta/?$', [SiatController::class, 'PuntosVenta'])
	->get('/eventos/?$', [SiatController::class, 'Eventos'])
	->get('/pos/?$', [SiatController::class, 'Pos'])
	->get('/siat/?$', [SiatController::class, 'Listing'])
	->get('/siat/(\d+)/view/?$', [SiatController::class, 'View'])
	->get('/siat/cufds/?$', [SiatController::class, 'CufdListing'])
	
;
SB_Route::module('invoices', $routes, '/portal')
	->get('/misfacturas/?$', [SiatController::class, 'UserPortal'])
	->get('/misfacturas/(\d+)/?$', [SiatController::class, 'View'])
;
