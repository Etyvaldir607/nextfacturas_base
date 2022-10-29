<?php
class ResourceInvoiceItem implements JsonSerializable
{
	protected	$detalle;
	
	public function __construct(object $detalle)
	{
		$this->detalle = $detalle;
	}
	public function jsonSerialize()
	{
		$data = (object)[
			'product_id' 	=> $this->detalle->producto_id,
			'product_code' 	=> $this->detalle->codigo,
			'quantity'		=> (int)$this->detalle->cantidad,
			'unidad_medida'	=> (int)$this->detalle->unidad_medida,
			'product_name'	=> $this->detalle->nombre_factura,
			'price'			=> (float)sb_number_format((float)$this->detalle->precio),
			'discount'		=> 0,
			'total'					=> (float)sb_number_format((float)$this->detalle->precio * (int)$this->detalle->cantidad),
			'unidad_medida_siat'	=> (int)$this->detalle->unidad_medida_siat,
		];
		
		return $data;
	}
	
}