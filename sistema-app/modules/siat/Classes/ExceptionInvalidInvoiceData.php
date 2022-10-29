<?php
//use Exception;

class ExceptionInvalidInvoiceData extends Exception
{
	public	$egreso;
	
	public function __construct($message, $code = null, ?object $egreso = null)
	{
		parent::__construct($message, $code);
		$this->egreso = $egreso;
	}
}