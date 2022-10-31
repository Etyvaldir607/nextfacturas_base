<?php
require_once libraries . '/Siat/autoload.php';
require_once __DIR__ . '/Classes/ExceptionInvalidInvoiceData.php';
require_once __DIR__ . '/Classes/ExceptionInvalidNit.php';
require_once __DIR__ . '/functions.php';

use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Services\ServicioSiat;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Services\ServicioFacturacionCodigos;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Services\ServicioFacturacionSincronizacion;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Invoices\CompraVenta;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Invoices\InvoiceDetail;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\DocumentTypes;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Invoices\SiatInvoice;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Services\ServicioFacturacionComputarizada;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Services\ServicioOperaciones;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\SiatFactory;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\SiatConfig;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Services\ServicioFacturacion;

define('SIAT_DATA_DIR', __DIR__ . '/data');
if (!is_dir(SIAT_DATA_DIR))
	mkdir(SIAT_DATA_DIR);

date_default_timezone_set('America/La_Paz');

function siat_file_needs_sync($filename)
{
	if (!is_file($filename))
		return true;

	return (time() - filemtime($filename)) > 86400;
}

/**
 * 
 * @return unknown|\SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\SiatConfig
 */

function siat_get_config()
{
	static $config;

	if ($config)
		return $config;

	// checkcode
	$config = new SiatConfig([
		'nombreSistema' => 'CheckcoDGgs',
		'codigoSistema' => '721D41E476C1EA84A931C7F',
		//'nombreSistema' => 'EducheckBET',
		//'codigoSistema' => '7237DCD25FECBC823F19C7F',
		'nit'           => 374898027,
		'razonSocial'   => 'CHECKCODE SOLUTION INDUSTRY S.R.L.',
		'modalidad'     => ServicioSiat::MOD_COMPUTARIZADA_ENLINEA,
		//'modalidad'     => ServicioSiat::MOD_ELECTRONICA_ENLINEA,
		'ambiente'      => ServicioSiat::AMBIENTE_PRUEBAS,
		'tokenDelegado'	=> 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJjaGVja2NvZGUiLCJjb2RpZ29TaXN0ZW1hIjoiNzIxRDQxRTQ3NkMxRUE4NEE5MzFDN0YiLCJuaXQiOiJINHNJQUFBQUFBQUFBRE0yTjdHd3REQXdNZ2NBQzZpd0lna0FBQUE9IiwiaWQiOjk3OTY2MCwiZXhwIjoxNjY5NzY2NDAwLCJpYXQiOjE2NjE5ODQ0ODYsIm5pdERlbGVnYWRvIjozNzQ4OTgwMjcsInN1YnNpc3RlbWEiOiJTRkUifQ.TkS3tRWN0nskjM4sDU-9Keqbsqcgpw3MGV6Qi4Gy3Z2AyU8jHqesGpKl_127SDKsB21zNL8cfn4nWPwZZN_dsw',

		//'pubCert'		=> MOD_SIAT_DIR . SB_DS . 'certs' . SB_DS . 'creativa-pos' . SB_DS . 'CREATIVA_CER.pem',
		//'privCert'		=> MOD_SIAT_DIR . SB_DS . 'certs' . SB_DS . 'creativa-pos' . SB_DS . 'EDWIN_FIGUEROA_VASQUEZ.pem',
		'telefono'		=> '34345435',
		'ciudad'		=> 'LA PAZ II'
	]);


	return $config;
}

function siat_renovar_cufd($sucursal = 0, $puntoventa = 0)
{
	global $db;

	$config 	= siat_get_config();
	$cuis 		= siat_obtener_cuis($sucursal, $puntoventa);
	$service 	= new ServicioFacturacionCodigos($cuis->codigo, null, $config->tokenDelegado);
	$service->setConfig((array)$config);
	$res = $service->cufd($puntoventa, $sucursal);

	if (!isset($res->RespuestaCufd->codigo))
		throw new Exception(('Unable to get CUFD, invalid response. ' . \sb_siat_message($res->RespuestaCufd)));

	$data = [
		'user_id'					=> 1,
		'codigo'					=> $res->RespuestaCufd->codigo,
		'codigo_control'			=> $res->RespuestaCufd->codigoControl,
		'direccion'					=> $res->RespuestaCufd->direccion,
		'sucursal_id'				=> $sucursal,
		'puntoventa_id'				=> $puntoventa,
		'fecha_vigencia'			=> date('Y-m-d H:i:s', strtotime($res->RespuestaCufd->fechaVigencia)),
		'last_modification_date'	=> date('Y-m-d H:i:s'),
		'creation_date'				=> date('Y-m-d H:i:s')
	];
	$id = $db->insert('mb_siat_cufd', $data);
	$data['id'] = $id;

	return (object)$data;
}
function siat_cufd_expirado(object $cufd)
{
	//var_dump(date('Y-m-d H:i:s', time()), $cufd->fecha_vigencia, time() > strtotime($cufd->fecha_vigencia));die;
	return (!$cufd || !$cufd->fecha_vigencia || (time() > strtotime($cufd->fecha_vigencia))) ? true : false;
}

function siat_obtener_cufd_por_codigo($codigo)
{
	global $db;

	$query = sprintf("SELECT * FROM mb_siat_cufd WHERE codigo = '%s' LIMIT 1", $codigo);
	$row = $db->query($query)->fetch_first();

	return $row ? (object)$row : null;
}
function siat_servicio_syncronizacion($sucursal, $puntoventa)
{
	static $servicio;
	if ($servicio)
		return $servicio;

	$config = siat_get_config();
	$cuis 	= siat_obtener_cuis($sucursal, $puntoventa);
	$servicio = new ServicioFacturacionSincronizacion();
	$servicio->setConfig((array)$config);
	$servicio->cuis = $cuis->codigo;

	return $servicio;
}
function siat_obtener_leyendas($sucursal = 0, $puntoventa = 0)
{
	$filename = sprintf("%s/%d-%d-leyendas-facturas.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));

	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarListaLeyendasFactura($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}
function siat_actividades($sucursal = 0, $puntoventa = 0)
{
	$filename = sprintf("%s/%d-%d-actividades.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));

	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarActividades($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}
function siat_fecha_hora($sucursal = 0, $puntoventa = 0)
{
	$filename = sprintf("%s/%d-%d-fecha-hora.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	//if (!siat_file_needs_sync($filename))
	//	return json_decode(file_get_contents($filename));

	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarFechaHora($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}
function siat_mensajes_servicios($sucursal = 0, $puntoventa = 0)
{
	$filename = sprintf("%s/%d-%d-mensajes-servicios.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));

	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarListaMensajesServicios($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}

function siat_pais_origen($sucursal = 0, $puntoventa = 0)
{
	$filename = sprintf("%s/%d-%d-pais-origen.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));

	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarParametricaPaisOrigen($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}

function siat_tipos_habitacion($sucursal = 0, $puntoventa = 0)
{
	$filename = sprintf("%s/%d-%d-tipos-habitacion.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));

	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarParametricaTipoHabitacion($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}


function siat_tipos_facturas($sucursal = 0, $puntoventa = 0)
{
	$filename = sprintf("%s/%d-%d-tipos-facturas.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));

	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarParametricaTiposFactura($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}

function siat_actividades_documento_sector($sucursal, $puntoventa)
{
	$filename = sprintf("%s/%d-%d-actividades-documento-sector.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));
	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarListaActividadesDocumentoSector($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}
function siat_tipos_emision($sucursal, $puntoventa)
{
	$filename = sprintf("%s/%d-%d-tipos-emision.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));
	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarParametricaTipoEmision($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}

function siat_tipos_eventos($sucursal, $puntoventa)
{
	$filename = sprintf("%s/%d-%d-eventos.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));
	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarParametricaEventosSignificativos($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}
function siat_buscar_tipo_evento($codigo, $sucursal = 0, $puntoventa = 0)
{
	$res = siat_tipos_eventos($sucursal, $puntoventa);

	$found = null;
	foreach ($res->RespuestaListaParametricas->listaCodigos as $evt) {
		if ((int)$codigo == (int)$evt->codigoClasificador) {
			$found = $evt;
			break;
		}
	}
	return $found;
}

function siat_leyenda($sucursal = 0, $puntoventa = 0)
{
	$leyendas = siat_obtener_leyendas($sucursal, $puntoventa);
	$tl = count($leyendas->RespuestaListaParametricasLeyendas->listaLeyendas);
	if (!$tl)
		return null;
	$text = $leyendas->RespuestaListaParametricasLeyendas->listaLeyendas[rand(0, $tl - 1)]->descripcionLeyenda;

	return $text;
}
function siat_obtener_productos(int $sucursal = 0, int $puntoventa = 0)
{
	$filename = sprintf("%s/%d-%d-productos-servicios.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));

	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarListaProductosServicios($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}
function siat_tipos_documento_identidad($sucursal, $puntoventa)
{
	$filename = sprintf("%s/%d-%d-documentos-identidad.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));
	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarParametricaTipoDocumentoIdentidad($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}
function siat_tipos_documentos_sector($sucursal, $puntoventa)
{
	$filename = sprintf("%s/%d-%d-tipos-documentos-sector.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));

	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarParametricaTipoDocumentoSector($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}
function siat_tipos_metodos_pago($sucursal, $puntoventa)
{
	$filename = sprintf("%s/%d-%d-tipos-metodos-pago.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));

	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarParametricaTipoMetodoPago($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}
function siat_tipos_moneda($sucursal, $puntoventa)
{
	$filename = sprintf("%s/%d-%d-tipos-moneda.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));
	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarParametricaTipoMoneda($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}
function siat_tipos_punto_venta($sucursal, $puntoventa)
{
	$filename = sprintf("%s/%d-%d-tipos-punto-venta.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));
	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarParametricaTipoPuntoVenta($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}
function siat_tipos_unidades_medida($sucursal, $puntoventa)
{
	$filename = sprintf("%s/%d-%d-unidades-medida.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));
	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarParametricaUnidadMedida($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}
function siat_tipos_unidades_medida_buscar($codigo, $sucursal = 0, $puntoventa = 0)
{
	$unidades = siat_tipos_unidades_medida($sucursal, $puntoventa);
	$item = null;
	foreach ($unidades->RespuestaListaParametricas->listaCodigos as $unidad) {
		if ($unidad->codigoClasificador == $codigo) {
			$item = $unidad;
			break;
		}
	}
	return $item;
}
function siat_egreso2factura($egreso)
{
	global $db;

	$config = siat_get_config();
	$documentoSector = DocumentTypes::FACTURA_COMPRA_VENTA;
	$codigoActividad = '620000';
	$codigoProductoSin = '83131';
	$detailClass 	= InvoiceDetail::class;
	$facturaSiat 	= SiatFactory::construirFacturaSector($documentoSector, $config->modalidad, $detailClass);
	$montoTotal 	= ($egreso['monto_total'] - $egreso['descuento_bs']);

	$facturaSiat->cabecera->tipoCambio						= 1;
	$facturaSiat->cabecera->codigoCliente 					= $egreso['cliente_id'];
	$facturaSiat->cabecera->codigoDocumentoSector 			= $documentoSector;
	$facturaSiat->cabecera->codigoMetodoPago				= (int)$egreso['codigo_metodo_pago'];
	$facturaSiat->cabecera->numeroTarjeta					= $egreso['numero_tarjeta'] ?: null;
	$facturaSiat->cabecera->codigoMoneda					= (int)$egreso['codigo_moneda'];
	$facturaSiat->cabecera->codigoPuntoVenta				= (int)$egreso['punto_venta'];
	$facturaSiat->cabecera->codigoSucursal					= (int)$egreso['codigo_sucursal'];
	$facturaSiat->cabecera->codigoTipoDocumentoIdentidad	= (int)$egreso['tipo_documento_identidad'];
	$facturaSiat->cabecera->complemento						= $egreso['complemento'] ?: null;
	$facturaSiat->cabecera->cuf								= trim($egreso['cuf']) ?: null;
	$facturaSiat->cabecera->cafc							= $egreso['cafc'] ?: null;
	$facturaSiat->cabecera->cufd							= $egreso['cufd'] ?: null;
	$facturaSiat->cabecera->descuentoAdicional				= $egreso['descuento_bs'] ?: 0;
	$facturaSiat->cabecera->direccion						= '';
	//$facturaSiat->cabecera->fechaEmision					= date('Y-m-d\TH:i:s.v'); //para obtener codigo observada 904 
	$facturaSiat->cabecera->fechaEmision					= date('Y-m-d\TH:i:s.v', strtotime($egreso['fecha_factura']));
	$facturaSiat->cabecera->montoGiftCard					= $egreso['monto_giftcard'] > 0 ? $egreso['monto_giftcard'] : null;
	$facturaSiat->cabecera->montoTotal						= sb_number_format($montoTotal, 2, '.', '');
	$facturaSiat->cabecera->montoTotalMoneda				= sb_number_format($facturaSiat->cabecera->tipoCambio > 0 ? $montoTotal / $facturaSiat->cabecera->tipoCambio : $montoTotal, 2, '.', '');
	$facturaSiat->cabecera->montoTotalSujetoIva				= 0;
	if (!in_array($documentoSector, [6, 8, 28]))
		$facturaSiat->cabecera->montoTotalSujetoIva = sb_number_format($montoTotal - $facturaSiat->cabecera->montoGiftCard, 2, '.', '');
	$facturaSiat->cabecera->municipio						= $config->ciudad ?: 'La Paz';
	$facturaSiat->cabecera->nitEmisor						= $config->nit;
	$facturaSiat->cabecera->nombreRazonSocial				= sb_sanitize_xml_text($egreso['nombre_cliente']);
	$facturaSiat->cabecera->numeroDocumento					= $egreso['nit_ci'];
	/*
	$facturaSiat->cabecera->numeroFactura					= (isset($invoice->data) && isset($invoice->data->nro_factura) && $invoice->cafc) ? 
																$invoice->data->nro_factura : $invoice->invoice_number;
	*/
	$facturaSiat->cabecera->numeroFactura					= $egreso['nro_factura'];
	$facturaSiat->cabecera->razonSocialEmisor				= $config->razonSocial;
	$facturaSiat->cabecera->telefono						= $config->telefono ?: '77777777';
	$facturaSiat->cabecera->usuario							= 'Usuario Vendedor 01';
	$facturaSiat->cabecera->leyenda							= $egreso['leyenda'];
	//var_dump(htmlentities($facturaSiat->cabecera->nombreRazonSocial));die;
	if ((int)$egreso['excepcion'] == 1)
		$facturaSiat->cabecera->codigoExcepcion = 1;

	$items = siat_obtener_egreso_items((int)$egreso['id_egreso']);
	foreach ($items as $item) {
		$detalle = new $detailClass();
		$detalle->actividadEconomica 	= $item['codigo_actividad'];
		$detalle->codigoProductoSin 	= $item['codigo_sin'];
		$detalle->codigoProducto		= $item['producto_id'];
		$detalle->descripcion			= $item['nombre_factura'];
		$detalle->cantidad				= $item['cantidad'];
		$detalle->unidadMedida			= $item['unidad_medida_siat']; //57;
		$detalle->precioUnitario		= sb_number_format($item['precio'], 2, '.', '');
		$detalle->montoDescuento		= sb_number_format($item['descuento'], 2, '.', '');
		$detalle->subTotal				= sb_number_format(($item['precio'] * $item['cantidad']) - $item['descuento'], 2, '.', '');
		$detalle->numeroSerie			= $item['numero_serie'] ?: null;
		$detalle->numeroImei			= $item['numero_imei'] ?: null;
		$detalle->validate();

		$facturaSiat->detalle[] = $detalle;
	}

	return $facturaSiat;
}
function siat_obtener_egreso($id)
{
	global $db;

	$egreso = $db->query("	SELECT * 
							FROM inv_egresos 
							LEFT JOIN inv_egresos_facturas on id_egreso=egreso_id 
							WHERE id_egreso = '" . $id . "' 
							LIMIT 1")
		->fetch_first();

	return $egreso;
}
function siat_obtener_egreso_items(int $id)
{
	global $db;

	$query = "SELECT d.*, p.codigo, p.nombre, 
					CONCAT(p.nombre_factura,' (LOTE: ',d.lote,' VENC:',date_format(d.vencimiento,'%d/%m/%Y'),')') as nombre_factura, 
					p.codigo_sin, p.codigo_actividad, p.unidad_medida_siat 	
			  FROM inv_egresos_detalles d 
			  JOIN inv_productos p ON p.id_producto = d.producto_id 
			  WHERE d.egreso_id = $id AND precio!=0";

	$items = $db->query($query)->fetch();

	return $items;
}
function siat_es_contingencia($codigoEvento)
{
	return in_array($codigoEvento, [5, 6, 7]);
}

function siat_verificar_nit($nit)
{
	$config = siat_get_config();
	$servicio = new ServicioFacturacionCodigos();
	$servicio->setConfig((array)$config);
	$res = $servicio->verificarNit($nit);
	if ($res->RespuestaVerificarNit->mensajesList->codigo == 994)
		//throw new ExceptionInvalidNit("El NIT $nit no es valido", null, null);
		return false;
	return true;
}
function siat_puntos_venta_db()
{
	global $db;

	$query = 'SELECT spv.* FROM mb_siat_puntos_venta as spv ORDER BY spv.codigo';
	$items = $db->query($query)->fetch();

	return $items;
}
function siat_eventos_crear($data)
{
	global $db;

	$data->status 					= 'OPEN';
	$data->last_modification_date 	= date('Y-m-d H:i:s');
	$data->creation_date 			= date('Y-m-d H:i:s');

	$id 		= $db->insert('mb_siat_eventos', $data);
	$data->id 	= $id;
	return $data;
}
function siat_eventos_obtener($id)
{
	global $db;

	$query = sprintf("SELECT * FROM mb_siat_eventos WHERE id = %d LIMIT 1", $id);
	$evento = $db->query($query)->fetch_first();

	return $evento ? (object)$evento : null;
}
function siat_eventos_anular($id)
{
	global $db;

	$evento = siat_eventos_obtener($id);
	if (!$evento)
		throw new Exception('El evento no existe, no se puede anular');

	$evento->status = 'CLOSED';
	$db->where(['id' => $id])->update('mb_siat_eventos', ['status' => 'CLOSED', 'last_modification_date' => date('Y-m-d H:i:s')]);

	return $evento;
}
function siat_eventos_obtener_egresos(int $id)
{
	global $db;

	$query = sprintf("	SELECT * 
						FROM inv_egresos 
						INNER JOIN inv_egresos_facturas on id_egreso=egreso_id 
						WHERE evento_id = %d 
						ORDER BY id_egreso ASC", $id);
	$items = $db->query($query)->fetch();

	return $items;
}


function siat_eventos_verificar(object $evento)
{
	global $db;

	$config = siat_get_config();
	$cuis 	= siat_obtener_cuis($evento->sucursal_id, $evento->puntoventa_id);
	$cufd 	= siat_obtener_cufd($evento->sucursal_id, $evento->puntoventa_id);

	$service = SiatFactory::obtenerServicioFacturacion($config, $cuis->codigo, $cufd->codigo, $cufd->codigo_control);
	$res = $service->validacionRecepcionPaqueteFactura($evento->sucursal_id, $evento->puntoventa_id, $evento->codigo_recepcion_paquete);
	while ($res->RespuestaServicioFacturacion->codigoDescripcion == 'PENDIENTE') {
		$res = $service->validacionRecepcionPaqueteFactura($evento->sucursal_id, $evento->puntoventa_id, $evento->codigo_recepcion_paquete);
	}
	$evento->stado_recepcion = $res->RespuestaServicioFacturacion->codigoDescripcion;
	if (!is_object($evento->data))
		$evento->data = new \stdClass();

	$evento->data->RespuestaServicioFacturacion = $res->RespuestaServicioFacturacion;
	$db->where(['id' => $evento->id])->update('mb_siat_eventos', [
		'stado_recepcion' 	=> $evento->stado_recepcion,
		'data' 				=> json_encode($evento->data)
	]);

	if ($res->RespuestaServicioFacturacion->codigoDescripcion == 'VALIDADA')
		$db->where(['evento_id' => $evento->id])->Update('inv_egresos_facturas', [
			'siat_id' 		=> $res->RespuestaServicioFacturacion->codigoRecepcion,
			'tipo_emision'	=> SiatInvoice::TIPO_EMISION_ONLINE,
		]);

	return $res;
}
function siat_eventos_db($sucursal, $puntoventa, $page = 1, $limit = 25)
{
	global $db;

	$offset = ($page <= 1) ? 0 : (($page - 1) * $limit);

	$query = sprintf(
		"SELECT * FROM mb_siat_eventos WHERE sucursal_id = %d AND puntoventa_id = %d ORDER BY id DESC LIMIT %d, %d",
		$sucursal,
		$puntoventa,
		$offset,
		$limit
	);

	return $db->query($query)->fetch();
}
function siat_eventos_obtener_activo(int $sucursal, int $puntoventa)
{
	global $db;

	$query = sprintf("SELECT * FROM mb_siat_eventos WHERE sucursal_id = %d AND puntoventa_id = %d AND status = 'OPEN' LIMIT 1", $sucursal, $puntoventa);
	$row = $db->query($query)->fetch_first();

	return $row ? (object)$row : null;
}
function siat_cufds_db($page = 1, $limit = 25)
{
	global $db;

	$offset = ($page <= 1) ? 0 : (($page - 1) * $limit);
	$query = sprintf("SELECT * FROM mb_siat_cufd ORDER BY id DESC LIMIT %d, %d", $offset, $limit);

	return $db->query($query)->fetch();
}



function siat_crear_puntoventa($sucursal, $tipo_punto_venta, $nombre, $descripcion)
{
	global $db;

	$config 	= siat_get_config();
	$cuis		= siat_obtener_cuis();
	$servicio 	= new ServicioOperaciones();
	$servicio->setConfig((array)$config);
	$servicio->cuis = $cuis->codigo;

	//registroPuntoVenta(int $codigoSucursal, int $tipoPuntoVenta, string $nombrePuntoVenta, string $descripcion = '')
	$res = $servicio->registroPuntoVenta($sucursal, $tipo_punto_venta, $nombre, $descripcion);

	if (isset($res->RespuestaRegistroPuntoVenta->mensajesList) && is_object($res->RespuestaRegistroPuntoVenta->mensajesList)) {
		throw new Exception(
			sprintf(
				"%d: %s",
				$res->RespuestaRegistroPuntoVenta->mensajesList->codigo,
				$res->RespuestaRegistroPuntoVenta->mensajesList->descripcion
			)
		);
		exit;
	}

	$data = [
		'user_id' 					=> 1,
		'codigo'					=> $res->RespuestaRegistroPuntoVenta->codigoPuntoVenta,
		'sucursal_id'				=> $sucursal,
		'nombre'					=> $nombre,
		'tipo_id'					=> $tipo_punto_venta,
		'tipo'						=> $descripcion,
		'last_modification_date'	=> date('Y-m-d H:i:s'),
		'creation_date'				=> date('Y-m-d H:i:s')
	];
	$id = $db->insert('mb_siat_puntos_venta', $data);

	$query = "SELECT * 
			  FROM mb_siat_puntos_venta spv
			  WHERE id = $id 
			  LIMIT 1";
	$pv = $db->query($query)->fetch_first();

	//$pv = $db->select('*')->from('mb_siat_puntos_venta')->where('id', $id)->fetch_first();

	return (object)$res;
}


/**
 * 
 * crear punto de venta por commisionista
 * @return object
 */
function siat_crear_puntoventa_comisionista($sucursal, $tipo_punto_venta, $nombre, $descripcion, string $contract_nit, int $contract_numeral, $contract_start_date, $contract_end_date)
{
	global $db;

	$config 	= siat_get_config();
	$cuis		= siat_obtener_cuis();
	$servicio 	= new ServicioOperaciones();
	$servicio->setConfig((array)$config);
	$servicio->cuis = $cuis->codigo;

	//$servicio->registroPuntoVentaComisionista($sucursal, $tipo_punto_venta, $nombre, $descripcion, $contract_nit, $contract_numeral, $contract_start_date, $contract_end_date);
	$res = $servicio->registroPuntoVentaComisionista($sucursal, $tipo_punto_venta, $nombre, $descripcion, $contract_nit, $contract_numeral, $contract_start_date, $contract_end_date);

	if (isset($res->RespuestaRegistroPuntoVentaComisionista->mensajesList) && is_object($res->RespuestaRegistroPuntoVentaComisionista->mensajesList)) {
		throw new Exception(
			sprintf(
				"%d: %s",
				$res->RespuestaRegistroPuntoVentaComisionista->mensajesList->codigo,
				$res->RespuestaRegistroPuntoVentaComisionista->mensajesList->descripcion
			)
		);
		exit;
	}

	$data = [
		'user_id' 					=> 1,
		'codigo'					=> $res->RespuestaRegistroPuntoVentaComisionista->codigoPuntoVenta,
		'sucursal_id'				=> $sucursal,
		'nombre'					=> $nombre,
		'tipo_id'					=> $tipo_punto_venta,
		'tipo'						=> $descripcion,
		'last_modification_date'	=> date('Y-m-d H:i:s'),
		'creation_date'				=> date('Y-m-d H:i:s'),
		'contract_nit'				=> $contract_nit,
		'contract_numeral'			=> $contract_numeral,
		'contract_start_date'		=> $contract_start_date,
		'contract_end_date'		=> $contract_end_date,
	];
	$id = $db->insert('mb_siat_puntos_venta_comisionista', $data);

	$query = "SELECT * 
			  FROM mb_siat_puntos_venta_comisionista spv
			  WHERE id = $id 
			  LIMIT 1";
	$pv = $db->query($query)->fetch_first();

	//$pv = $db->select('*')->from('mb_siat_puntos_venta')->where('id', $id)->fetch_first();

	return (object)$res;
}




function siat_factura_url(object $egreso)
{
	$config = siat_get_config();
	return sprintf(
		"https://%s.impuestos.gob.bo/consulta/QR?nit=%d&cuf=%s&numero=%d&t=%d",
		$config->modalidad == ServicioSiat::AMBIENTE_PRUEBAS ? 'pilotosiat' : 'siat',
		$egreso->nit_emisor,
		$egreso->cuf,
		$egreso->nro_factura,
		1
	);
}
function siat_obtener_facturas($sucursal = 0, $puntoventa = 0)
{
	global $db;

	$query = "	SELECT * 
				FROM inv_egresos 
				INNER JOIN inv_egresos_facturas on id_egreso=egreso_id 
				WHERE nro_factura > 0 
				ORDER BY id_egreso DESC";

	$items = $db->query($query)->fetch();

	return $items;
}
/**
 * 
 * @param int $id
 * @throws Exception
 * @return \Dompdf\Dompdf
 */
function siat_factura_print(int $id, $tpl, &$egreso, &$invoice)
{
	$egreso = siat_obtener_egreso($id);
	if (!$egreso)
		throw new Exception('El egreso no existe');
	$egreso 	= (object)$egreso;
	$egreso->items = siat_obtener_egreso_items($egreso->id_egreso);
	//print_r($egreso);die;
	$config 	= siat_get_config();
	$cufd		= siat_obtener_cufd($egreso->codigo_sucursal, $egreso->punto_venta);
	$resource 	= new ResourceInvoice($egreso);
	$invoice 	= $resource->jsonSerialize();
	$direccion	= $cufd->direccion;
	$invoiceNum = $invoice->invoice_number;
	$leyenda 	= $invoice->leyenda;
	$payAmount 	= $invoice->total - $invoice->monto_giftcard;

	require_once libraries . '/dompdf/autoload.inc.php';
	require_once libraries . '/tcpdf/tcpdf_barcodes_2d.php';
	require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

	$conversor 	= new NumberToLetterConverter();
	$total_words 	= $conversor->to_word($payAmount);
	$objeto 		= new TCPDF2DBarcode(siat_factura_url($egreso), 'QRCODE,L');
	$imagen 		= $objeto->getBarcodePngData(4, 4, array(30, 30, 30));
	$qr64 			= sprintf("data:image/png;base64,%s", base64_encode($imagen));
	$tpl_file	= __DIR__ . sprintf("/tpl/siat-dc-%d%s.php", $invoice->codigo_documento_sector, $tpl ? ("-$tpl") : '');
	if (!is_file($tpl_file))
		throw new Exception('La plantilla de la factura no existe');
	ob_start();
	include $tpl_file;
	$html = ob_get_clean();
	//die($html);
	$pdf = new \Dompdf\Dompdf();
	$pdf->getOptions()->set(['isRemoteEnabled' => true, 'isHtml5ParserEnabled', true]);
	$pdf->loadHtml($html);
	$pdf->render();

	return $pdf;
}

//recent **************************************************************************************************************************************
//*********************************************************************************************************************************************
//*********************************************************************************************************************************************
function siat_motivos_anulacion($sucursal = 0, $puntoventa = 0)
{
	$filename = sprintf("%s/%d-%d-motivos-anulacion.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (!siat_file_needs_sync($filename))
		return json_decode(file_get_contents($filename));
	$servicio = siat_servicio_syncronizacion($sucursal, $puntoventa);
	$res = $servicio->sincronizarParametricaMotivoAnulacion($sucursal, $puntoventa);

	file_put_contents($filename, json_encode($res));
	return $res;
}

//recent asd *******************************************************************************



function siat_obtener_puntos_ventas($sucursal = 0, $all_status = false, $sync = false)
{

	$db_puntos_venta 	= puntos_ventas_db($sucursal, $all_status);
	if ($sync) {

		$siat 				= siat_consulta_punto_venta($sucursal);
		$siat_puntos_venta 	= $siat->RespuestaConsultaPuntoVenta->listaPuntosVentas;
		$db_codigos 		= array_column($db_puntos_venta, 'codigo');

		foreach ($siat_puntos_venta as $key => $val) {
			$codigo = strval($val->codigoPuntoVenta);
			if (!in_array($codigo, $db_codigos)) {
				$temp = [
					"id"						=> 0,
					"user_id"					=> 0,
					"codigo"					=> $val->codigoPuntoVenta,
					"sucursal_id"				=> $sucursal,
					"nombre"					=> $val->nombrePuntoVenta,
					"status"					=> "unregistered",
					"tipo_id"					=> "",
					"tipo"						=> $val->tipoPuntoVenta,
					"last_modification_date"	=> "",
					"creation_date"				=> "",
				];
				//$db_puntos_venta[] = $temp;
				array_unshift($db_puntos_venta, $temp);
			}
		}
	}

	return $db_puntos_venta;
}

function siat_consulta_punto_venta($sucursal = 0)
{
	$config 		= siat_get_config();
	$cuis			= siat_obtener_cuis($sucursal, 0);
	$servicio 		= new ServicioOperaciones();
	$servicio->setConfig((array)$config);
	$servicio->cuis	= $cuis->codigo;
	$res = $servicio->consultaPuntoVenta($sucursal);

	return $res;
}

function puntos_ventas_db($sucursal = 0, $all_status = false)
{
	global $db;
	$plus = ($all_status) ? "" : "AND spt.status = 'open'";
	$query = "SELECT *
		FROM mb_siat_puntos_venta spt
		WHERE spt.sucursal_id = $sucursal
		$plus
		ORDER BY spt.codigo";
	$data = $db->query($query)->fetch();
	return $data;
}

// end asd  *****************************************************************************

//recent *******************************************************************************
function siat_anular_factura($id, $motivo_anulacion_id)
{
	global $db;

	$query = "SELECT *
				FROM inv_egresos eg
				INNER JOIN inv_egresos_facturas egf ON eg.id_egreso = egf.egreso_id
				WHERE egf.egreso_id = $id
				AND egf.nro_factura > 0
				LIMIT 1";

	$data = $db->query($query)->fetch_first();

	$sucursal				= intval($data['codigo_sucursal']);
	$puntoventa				= intval($data['punto_venta']);
	$codigoMotivo			= intval($motivo_anulacion_id);
	$cuf					= trim($data['cuf']);
	$tipoFacturaDocumento	= intval($data['tipo_factura_documento']);
	$codigoEmision			= intval($data['tipo_emision']);
	$codigoDocumentoSector	= intval($data['codigo_documento_sector']);

	$config 	= siat_get_config();
	$cuis		= siat_obtener_cuis($sucursal, $puntoventa);
	$cufd 		= siat_obtener_cufd($sucursal, $puntoventa, null);

	$servicio 	= new ServicioFacturacion();
	$servicio->setConfig((array)$config);
	$servicio->cuis = $cuis->codigo;
	$servicio->cufd = $cufd->codigo;


	//$resp = anulacionFactura(int $motivo, string $cuf, int $sucursal, int $puntoventa, int $tipoFactura, int $tipoEmision, int $documentoSector)
	$resp = $servicio->anulacionFactura(
		$codigoMotivo,
		$cuf,
		$sucursal,
		$puntoventa,
		$tipoFacturaDocumento,
		$codigoEmision,
		$codigoDocumentoSector
	);

	return $resp;
}



function siat_obtener_cuis($sucursal = 0, $puntoventa = 0)
{

	$filename = sprintf("%s/cuis-%d-%d.json", SIAT_DATA_DIR, $sucursal, $puntoventa);
	if (is_file($filename))
		return json_decode(file_get_contents($filename));

	$config 		= siat_get_config();
	$serviceCodigos = new ServicioFacturacionCodigos(null, null, $config->tokenDelegado);
	//$serviceCodigos->debug = true;
	$serviceCodigos->setConfig((array)$config);
	$resCuis = $serviceCodigos->cuis($puntoventa, $sucursal);

	file_put_contents($filename, json_encode($resCuis->RespuestaCuis));

	return $resCuis->RespuestaCuis;
}

function siat_obtener_cufd($sucursal = 0, $puntoventa = 0, $date = null)
{
	global $db;

	$cdate = $date ?: date('Y-m-d');
	$query = sprintf(
		//"SELECT * FROM mb_siat_cufd WHERE sucursal_id = %d AND puntoventa_id = %d AND DATE(fecha_vigencia) >= '%s' ORDER BY id DESC LIMIT 1",
		"SELECT * FROM mb_siat_cufd WHERE sucursal_id = %d AND puntoventa_id = %d AND fecha_vigencia >= '%s' ORDER BY id DESC LIMIT 1",
		$sucursal,
		$puntoventa,
		$cdate
	);
	$cufd = $db->query($query)->fetch_first();
	//var_dump($cufd);
	if (!$cufd || ($cufd && siat_cufd_expirado((object)$cufd))) {
		$cufd = siat_renovar_cufd($sucursal, $puntoventa);
	}

	return (object)$cufd;
}




//recent *******************************************************************************
function siat_cierre_punto_venta($id)
{
	global $db;

	$query = "SELECT *
				FROM mb_siat_puntos_venta AS spv
				WHERE spv.codigo = $id
				AND spv.status = 'open'
				LIMIT 1";

	$data = $db->query($query)->fetch_first();

	$sucursal				= intval($data['sucursal_id']);
	$puntoventa				= intval($data['codigo']);

	$config 	= siat_get_config();
	$cuis		= siat_obtener_cuis($sucursal, 0); //en este caso mandar siempre cero para la obtencion del cuis por punto venta

	$servicio 	= new ServicioOperaciones();
	$servicio->setConfig((array)$config);
	$servicio->cuis = $cuis->codigo;
	$resp = $servicio->cierrePuntoVenta($sucursal, $puntoventa);

	return $resp;
}


//recent *******************************************************************************
function siat_estado_factura($id)
{

	global $db;

	$query = "SELECT *
				FROM inv_egresos eg
				INNER JOIN inv_egresos_facturas egf ON eg.id_egreso = egf.egreso_id
				WHERE egf.id_factura = $id
				AND egf.nro_factura > 0
				LIMIT 1";

	$data = $db->query($query)->fetch_first();

	//return $data;

	if (!$data) {
		//throw new Exception('la factura o nota de credito - debito con el identicador "{$id} no existe en la base de datos del sin');
		return [
			"RespuestaServicioFacturacion" =>
			["mensajesList" => ["descripcion" => "La factura o nota de credito - debito con el identicador : {$id} no existe en la base de datos del sin"]]
		];
	}

	$cuf				= trim($data['cuf']);
	$sucursal			= intval($data['codigo_sucursal']);
	$puntoventa			= intval($data['punto_venta']);
	$tipoFactura		= intval($data['tipo_factura_documento']);
	$tipoEmision		= intval($data['tipo_emision']);
	$documentoSector	= intval($data['codigo_documento_sector']);

	$config 			= siat_get_config();
	$cuis 				= siat_obtener_cuis($sucursal, $puntoventa);
	$cufd 				= siat_obtener_cufd($sucursal, $puntoventa, null);

	$servicio 			= new ServicioFacturacion();
	$servicio->setConfig((array)$config);
	$servicio->cuis = $cuis->codigo;
	$servicio->cufd = $cufd->codigo;
	// verificacionEstadoFactura(string $cuf, int $sucursal, int $puntoventa, int $tipoFactura, int $tipoEmision, int $documentoSector)
	$resp = $servicio->verificacionEstadoFactura($cuf,  $sucursal,  $puntoventa,  $tipoFactura,  $tipoEmision,  $documentoSector);
	return $resp;
}


function siat_recepcion_factura($egreso_id)
{
	global $db;

	$egreso = siat_obtener_egreso($egreso_id);

	$nro_facturax = $db->query("SELECT IFNULL(MAX(nro_factura),0) + 1 as nro_factura 
	                            from inv_egresos_facturas
								")->fetch_first();


	if ($nro_facturax) {
		$nro_factura = $nro_facturax['nro_factura'];
	} else {
		$nro_factura = 1;
	}
	$egreso['nro_factura'] = $nro_factura;

	if (!$egreso)
		throw new Exception('No existe el egreso, no se puede generar la factura');

	$sucursal	= intval($egreso['codigo_sucursal']);
	$puntoventa	= intval($egreso['punto_venta']);

	$config 	= siat_get_config();
	$cuis 		= siat_obtener_cuis($sucursal, $puntoventa);
	$cufd 		= siat_obtener_cufd($sucursal, $puntoventa, null, null);

	if (!$cufd)
		$cufd = siat_renovar_cufd($sucursal, $puntoventa);
	if (!$cufd)
		throw new Exception('No se puede obtener un CUFD valido');
	$egreso['leyenda'] 					= siat_leyenda($sucursal, $puntoventa);
	$egreso['tipo_factura_documento'] 	= SiatInvoice::FACTURA_DERECHO_CREDITO_FISCAL;
	$eventoActivo = siat_eventos_obtener_activo($sucursal, $puntoventa);
	if ($eventoActivo) {

		$cufdEvento = siat_obtener_cufd_por_codigo($eventoActivo->cufd_evento);
		if (!$cufdEvento)
			throw new ExceptionInvalidInvoiceData('El CUFD del evento no existe', null, $egreso);
		//if( $eventoActivo->fecha_inicio && $eventoActivo->fecha_fin && $invoice->invoice_date_time )
		//	$this->eventosModel->isValidInvoiceDateTimes($evento, $invoice);

		if ($egreso['tipo_documento_identidad'] == 5)
			$egreso['excepcion'] = 1;

		if (siat_es_contingencia($eventoActivo->evento_id)) {
			if (empty($config->cafc))
				throw new ExceptionInvalidInvoiceData('Codigo CAFC no asignado, no se puede generar la factura', null, (object)$egreso);
			if ((int)$config->cafc_inicio_nro_factura <= 0)
				throw new ExceptionInvalidInvoiceData('Intervalo inicio nro de factura CAFC no asignado, no se puede generar la factura', null, (object)$egreso);
			if ((int)$config->cafc_fin_nro_factura <= 0)
				throw new ExceptionInvalidInvoiceData('Intervalo fin nro de factura CAFC no asignado, no se puede generar la factura', null, (object)$egreso);
			/*
			if( (int)$invoice->data->nro_factura <= 0 )
				throw new ExceptionInvalidInvoiceData('El nro de factura CAFC no es valido, no se puede generar la factura', null, $invoice);
			if( (int)$invoice->data->nro_factura < (int)$config->cafc_inicio_nro_factura
				|| (int)$invoice->data->nro_factura > (int)$config->cafc_fin_nro_factura
				)
				throw new ExceptionInvalidInvoiceData('El nro de factura CAFC no es valido, intervalo incorrecto, no se puede generar la factura', null, $invoice);
			*/
			//##verificar el numero de factura CAFC ya fue utilizado
			//if( $this->cafcInvoiceExists($user, $evento->cafc, (int)$invoice->data->nro_factura) )
			//	throw new ExceptionInvalidInvoiceData('El numero de factura CAFC no esta disponible', null, $invoice);

			$egreso['cafc']	= $config->cafc;
			$egreso['fecha_factura'] = date('Y-m-d H:i:s');
		} else {
			$egreso['fecha_factura'] = date('Y-m-d H:i:s');
		}

		$egreso['tipo_emision']		= ServicioSiat::TIPO_EMISION_OFFLINE;
		$egreso['evento_id'] 		= $eventoActivo->id;
		$egreso['codigo_control']	= $cufdEvento->codigo_control;
		$egreso['cufd']				= $cufdEvento->codigo;
		$facturaSiat 				= siat_egreso2factura($egreso);

		//die(json_encode(['data' => $egreso]));
		//die(json_encode(['data' => $egreso]));
	
		$facturaSiat->buildCuf($sucursal, $config->modalidad, $egreso['tipo_emision'], $egreso['tipo_factura_documento'], $egreso['codigo_control']);
		$facturaSiat->cabecera->direccion = $cufdEvento->direccion;
		//die(json_encode(['facturaSiat' => $facturaSiat,'egreso' => $egreso,'config' => $config,'sucursal' => $sucursal]));
	} else {
		if ($egreso['tipo_documento_identidad'] == 5 && (int)$egreso['excepcion'] != 1) {
			if (!siat_verificar_nit((int)$egreso['nit_ci']))
				throw new ExceptionInvalidNit('El NIT del cliente no es valido', null, (object)$egreso);
		}

		$egreso['tipo_emision']		= ServicioSiat::TIPO_EMISION_ONLINE;
		$egreso['evento_id'] 		= null;
		$egreso['codigo_control']	= $cufd->codigo_control;
		$egreso['cufd']				= $cufd->codigo;
		$egreso['fecha_factura']	= date('Y-m-d H:i:s');

		$facturaSiat 	= siat_egreso2factura($egreso);
		$facturaSiat->cabecera->direccion = $cufd->direccion;
		$facturaSiat->cabecera->checkAmounts();
		$service = SiatFactory::obtenerServicioFacturacion($config, $cuis->codigo, $cufd->codigo, $cufd->codigo_control);
		//$service->codigoControl = $cufd->codigo_control;

		$res = $service->recepcionFactura($facturaSiat, SiatInvoice::TIPO_EMISION_ONLINE, SiatInvoice::FACTURA_DERECHO_CREDITO_FISCAL);

		//var_dump($facturaSiat);
		//var_dump($res->RespuestaServicioFacturacion);
		if ($res->RespuestaServicioFacturacion->codigoEstado != 908) {
			error_log(print_r($res, 1));
			error_log(print_r($facturaSiat, 1));
			throw new ExceptionInvalidInvoiceData(
				'Ocurrio un error con la recepcion de la factura. SIAT: ' . $egreso['nro_factura'] . "---" . \sb_siat_message($res->RespuestaServicioFacturacion),
				null,
				(object)$egreso
			);
		}
		$egreso['siat_id'] = $res->RespuestaServicioFacturacion->codigoRecepcion;
	}

	$data = [
		'cafc'						=> $egreso['cafc'],
		'codigo_sucursal'			=> $facturaSiat->cabecera->codigoSucursal,
		'punto_venta'				=> $facturaSiat->cabecera->codigoPuntoVenta,
		'cufd'						=> $egreso['cufd'],
		'codigo_control'			=> $egreso['codigo_control'],
		'cuf'						=> $facturaSiat->cabecera->cuf,
		'siat_id' 					=> $egreso['siat_id'],
		'leyenda'					=> $facturaSiat->cabecera->leyenda,
		'fecha_factura'				=> $egreso['fecha_factura'],
		'nro_factura'				=> $nro_factura,
		'tipo_emision'				=> $egreso['tipo_emision'],
		'nit_emisor'				=> $config->nit,
		'tipo_factura_documento'	=> $egreso['tipo_factura_documento'],
		'codigo_documento_sector' 	=> $facturaSiat->cabecera->codigoDocumentoSector,
		'evento_id'					=> (int)$egreso['evento_id'] > 0 ? $egreso['evento_id'] : null,
	];



	$db->where(['egreso_id' => $egreso_id])
		->update('inv_egresos_facturas', $data);

	// $existe_factura = $db->query("	SELECT * 
	//                             	from inv_egresos_facturas
	//                             	WHERE egreso_id='".$egreso_id."'
	// 							")->fetch_first();
	//                     			//where tipo = 'Venta' and provisionado = 'S'
	// if($existe_factura){
	//    	$db->where(['egreso_id' => $existe_factura['egreso_id'] ])
	// 	   ->update('inv_egresos_facturas', $data);
	//    }else{
	//   		$data['egreso_id']=;
	// 	$db->insert('inv_egresos_facturas', $data);
	//    }

	return siat_obtener_egreso($egreso_id);
}



function siat_eventos_cerrar(int $id)
{
	global $db;

	set_time_limit(0);

	$localEvent = siat_eventos_obtener($id);

	//return $localEvent;
	if (!$localEvent)
		throw new Exception('El evento no existe');

	$invoices 		= siat_eventos_obtener_egresos($localEvent->id);
	$total			= count($invoices);

	if ($total <= 0) { //si no hay ni una factura emitida con este evento se cierra sin novedad el evento nada que ver con siat
		$localEvent->status = 'CLOSED';
		$db->where(['id' => $localEvent->id])->update('mb_siat_eventos', ['status' => $localEvent->status, 'last_modification_date' => date('Y-m-d H:i:s')]);
		//sb_update_user_meta($user->user_id, '_SIAT_'. $localEvent->puntoventa_id . '_MANUAL_EVENT', 0);
		return true;
	}
	$config	= siat_get_config();
	//return $cufd;
	if (!siat_es_contingencia($localEvent->evento_id)) { //el evento no es 5,6,7 entonces ponemos fecha fin
		$localEvent->fecha_fin = date('Y-m-d H:i:s');
	}

	$sres = siat_registro_evento_significativo($localEvent, $config);
	//print_r($invoices);die;
	if ($total <= 500) {
		$srpf = siat_recepcion_paquete_factura($localEvent, $config, $invoices);
		//$srpf = 'no envio paquete';
	} else {
		//##envio masivo
	}
	return [
		'sres' => $sres,
		'srpf' => $srpf
	];
}

function siat_registro_evento_significativo($localEvent, $config)
{
	$cuis	= siat_obtener_cuis($localEvent->sucursal_id, $localEvent->puntoventa_id);
	$cufd 	= siat_obtener_cufd($localEvent->sucursal_id, $localEvent->puntoventa_id, null);

	global $db;
	$siatEvent = siat_buscar_tipo_evento($localEvent->evento_id, $localEvent->sucursal_id, $localEvent->puntoventa_id);
	if (!$siatEvent)
		throw new Exception('El evento siat no existe');
	$res = null;
	// registrando evento significativo in SIAT
	if ((int)$localEvent->codigo_recepcion <= 0) { //si el evento no tiene una recepcion 
		if (empty($localEvent->fecha_fin))
			throw new Exception('El evento no tiene fecha de finalizacion, no se puede cerrar');
		//$lastestCufd = $this->getLatestDbCufd($user, $localEvent->sucursal_id, $localEvent->puntoventa_id);
		if ($cufd->codigo == $localEvent->cufd_evento /*$lastestCufd->codigo == $localEvent->cufd_evento*/) {
			//##get new CUFD to close the event
			$cufd = siat_renovar_cufd($localEvent->sucursal_id, $localEvent->puntoventa_id);
		}


		$serviceOp = new ServicioOperaciones($cuis->codigo, $cufd->codigo, $config->tokenDelegado);
		$serviceOp->setConfig((array)$config);

		$res = $serviceOp->registroEventoSignificativo(
			$siatEvent->codigoClasificador,
			$siatEvent->descripcion,
			$localEvent->cufd_evento,
			date(SIAT_DATETIME_FORMAT, strtotime($localEvent->fecha_inicio)),
			date(SIAT_DATETIME_FORMAT, strtotime($localEvent->fecha_fin)),
			$localEvent->sucursal_id,
			$localEvent->puntoventa_id
		);

		//return $res;

		if (!isset($res->RespuestaListaEventos->codigoRecepcionEventoSignificativo) || !$res->RespuestaListaEventos->transaccion) {
			$error = \sb_siat_message($res->RespuestaListaEventos);
			if (is_object($res->RespuestaListaEventos->mensajesList) && $res->RespuestaListaEventos->mensajesList->codigo == 981)
				$error .= sprintf("%s - %s", $localEvent->fecha_inicio, $localEvent->fecha_fin);
			throw new Exception('No se pudo registrar el evento en SIAT: ' . $error);
		}

		$localEvent->codigo_recepcion = $res->RespuestaListaEventos->codigoRecepcionEventoSignificativo;
		$db->where(['id' => $localEvent->id])->update('mb_siat_eventos', ['codigo_recepcion' => $localEvent->codigo_recepcion]);
	}
	return $res;
}

function siat_recepcion_paquete_factura($localEvent, $config, $invoices)
{
	set_time_limit(0);
	global $db;
	//##envio paquete
	$cuis	= siat_obtener_cuis($localEvent->sucursal_id, $localEvent->puntoventa_id);
	$cufd 	= siat_obtener_cufd($localEvent->sucursal_id, $localEvent->puntoventa_id, null);

	$service 		= SiatFactory::obtenerServicioFacturacion($config, $cuis->codigo, $cufd->codigo, $cufd->codigo_control);

	$siatInvoices 	= [];
	foreach ($invoices as $egreso) {
		$siatInvoice 						= siat_egreso2factura($egreso);
		$siatInvoice->cabecera->direccion 	= $cufd->direccion;
		$siatInvoices[] 					= $siatInvoice;
	}
	//return $siatInvoices;
	//return $service;

	$res = $service->recepcionPaqueteFactura(
		$siatInvoices,
		$localEvent->codigo_recepcion,
		SiatInvoice::TIPO_EMISION_OFFLINE,
		(int)$invoices[0]['tipo_factura_documento'],
		$localEvent->cafc ?: null
	);

	//return $res;



	//SB_Factory::getApplication()->Log($res);
	$localEvent->stado_recepcion = $res->RespuestaServicioFacturacion->codigoDescripcion;

	if ($res->RespuestaServicioFacturacion->codigoEstado != 901) //PENDIENTE
	{
		if (!is_object($localEvent->data))
			$localEvent->data = (object)['error_envio' => null];
		$localEvent->data->error_envio = $res->RespuestaServicioFacturacion->mensajesList;
		$db->where(['id' => $localEvent->id])->update('mb_siat_eventos', ['data' => json_encode($localEvent->data)]);
		throw new Exception('Ocurrio un error enviando el paquete. SIAT: ' . \sb_siat_message($res->RespuestaServicioFacturacion));
	}

	$localEvent->codigo_recepcion_paquete = $res->RespuestaServicioFacturacion->codigoRecepcion;
	$localEvent->cufd	= $cufd->codigo;
	$localEvent->status = 'CLOSED';
	$db->where(['id' => $localEvent->id])->update('mb_siat_eventos', [
		'stado_recepcion' 			=> $localEvent->stado_recepcion,
		'codigo_recepcion_paquete'	=> $localEvent->codigo_recepcion_paquete,
		'cufd'						=> $localEvent->cufd,
		'status'					=> $localEvent->status,
	]);
	//sb_update_user_meta($user->user_id, '_SIAT_'. $localEvent->puntoventa_id . '_MANUAL_EVENT', 0);
	$res = siat_eventos_verificar($localEvent);
	return $res;
}
