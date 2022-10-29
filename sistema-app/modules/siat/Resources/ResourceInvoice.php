<?php
require_once __DIR__ . '/ResourceInvoiceItem.php';

class ResourceInvoice implements JsonSerializable
{
	protected	$egreso;
	
	public function __construct(object $egreso)
	{
		$this->egreso = $egreso;
	}
	public function jsonSerialize()
	{
		$data = (object)[
			'invoice_id' 		=> $this->egreso->id_egreso,
			'invoice_date_time' => date('d-m-Y H:i:s', strtotime($this->egreso->fecha_factura)),
			'invoice_number'	=> (int)$this->egreso->nro_factura,
			'customer_id'		=> $this->egreso->cliente_id,
			'customer'			=> $this->egreso->nombre_cliente,
			'codigo_sucursal'	=> (int)$this->egreso->codigo_sucursal,
			'punto_venta'		=> (int)$this->egreso->punto_venta,
			'status'			=> 'issued',
			'total_tax'			=> (float)sb_number_format((float)$this->egreso->monto_total * 0.13),
			'subtotal'			=> 0,
			'discount'			=> (float)$this->egreso->descuento_bs,
			'total'				=> (float)sb_number_format((float)$this->egreso->monto_total),
			'siat_id'			=> $this->egreso->siat_id,
			'siat_url'			=> siat_factura_url($this->egreso),
			'cufd'				=> $this->egreso->cufd,
			'cuf'				=> $this->egreso->cuf,
			'control_code'		=> $this->egreso->codigo_control,
			'nit_ruc_nif'		=> $this->egreso->nit_ci,
			'complemento'		=> $this->egreso->complemento,
			'monto_giftcard'	=> (float)$this->egreso->monto_giftcard,
			'codigo_documento_sector'	=> (int)$this->egreso->codigo_documento_sector,
			'tipo_factura_documento'	=> (int)$this->egreso->tipo_factura_documento,
			'tipo_emision'				=> (int)$this->egreso->tipo_emision,
			'nit_emisor'				=> $this->egreso->nit_emisor,
			'leyenda'					=> $this->egreso->leyenda,
			'excepcion'					=> $this->egreso->excepcion,
			'items'						=> []
		];
		if( !isset($this->egreso->items) || $this->egreso->items === null )
			$this->egreso->items = siat_obtener_egreso_items((int)$this->egreso->id_egreso);
		foreach($this->egreso->items as $item)
		{
			$invItem = new ResourceInvoiceItem((object)$item);
			$data->items[] = $invItem->jsonSerialize();
		}
		return $data;
	}
}