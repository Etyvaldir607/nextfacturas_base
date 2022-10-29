<?php
use SinticBolivia\SBFramework\Classes\SB_Module;
use SinticBolivia\SBFramework\Classes\SB_Factory;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\LT_MBInvoice;
use SinticBolivia\SBFramework\Classes\SB_Meta;

function mb_invoice_get_meta($invoice_id, $meta_key)
{
	return SB_Meta::getMeta('mb_invoice_meta', $meta_key, 'invoice_id', $invoice_id);
}
function mb_invoice_add_meta($invoice_id, $meta_key, $meta_value)
{
	return SB_Meta::addMeta('mb_invoice_meta', $meta_key, $meta_value, 'invoice_id', $invoice_id);
}
function mb_invoice_update_meta($invoice_id, $meta_key, $meta_value)
{
	SB_Meta::updateMeta('mb_invoice_meta', $meta_key, $meta_value, 'invoice_id', $invoice_id);	
}
function mb_invoices_get_templates()
{
	$templates = array();
	$dh = opendir(MOD_INVOICES_TPL_DIR);
	while( ($file = readdir($dh)) !== false )
	{
		if( $file[0] == '.' ) continue;
		$templates[] = sb_get_template_info(MOD_INVOICES_TPL_DIR . SB_DS . $file);
	}
	closedir($dh);
	return $templates;
}
/**
 * Build a country invoice object
 * 
 * @param string $code
 * @return NULL|SB_IMBInvoice
 */
function sb_mb_invoices_get_country_obj($code)
{
	$class_file = MOD_INVOICES_DIR . SB_DS . 'countries' . SB_DS . 'class.' . $code . '.php';
	if( !file_exists($class_file) )
	{
		return null;
	}
	require_once $class_file;
	$class_name = 'SB_Invoice' . $code;
	if( !class_exists($class_name) )
		return null;
	return new $class_name;
}
if( !function_exists('sb_num2letras')):
/**
 * Convertir numeros a letras (solo español)
 * Máxima cifra soportada: 18 dígitos con 2 decimales
 * --- > 999,999,999,999,999,999.99
 * 
 * @param float $xcifra
 * @return string
 */
function sb_num2letras($xcifra, $currency_text = 'BOLIVIANOS')
{
	$xarray = array(
			0 => "Cero",
			1 => "UN",
			"DOS",
			"TRES",
			"CUATRO",
			"CINCO", 
			"SEIS", 
			"SIETE", 
			"OCHO",
			"NUEVE",
			"DIEZ", 
			"ONCE", 
			"DOCE", 
			"TRECE", 
			"CATORCE", 
			"QUINCE", 
			"DIECISEIS", 
			"DIECISIETE", 
			"DIECIOCHO", 
			"DIECINUEVE",
			"VEINTI",
			30 => "TREINTA",
			40 => "CUARENTA",
			50 => "CINCUENTA",
			60 => "SESENTA",
			70 => "SETENTA",
			80 => "OCHENTA",
			90 => "NOVENTA",
			100 => "CIENTO",
			200 => "DOSCIENTOS",
			300 => "TRESCIENTOS",
			400 => "CUATROCIENTOS",
			500 => "QUINIENTOS",
			600 => "SEISCIENTOS",
			700 => "SETECIENTOS",
			800 => "OCHOCIENTOS",
			900 => "NOVECIENTOS"
	);
	//
	$xcifra = trim($xcifra);
	$xlength = strlen($xcifra);
	$xpos_punto = strpos($xcifra, ".");
	$xaux_int = $xcifra;
	$xdecimales = "00";
	if (!($xpos_punto === false)) {
		if ($xpos_punto == 0) {
			$xcifra = "0" . $xcifra;
			$xpos_punto = strpos($xcifra, ".");
		}
		$xaux_int = substr($xcifra, 0, $xpos_punto); // obtengo el entero de la cifra a covertir
		$xdecimales = substr($xcifra . "00", $xpos_punto + 1, 2); // obtengo los valores decimales
	}

	$XAUX = str_pad($xaux_int, 18, " ", STR_PAD_LEFT); // ajusto la longitud de la cifra, para que sea divisible por centenas de miles (grupos de 6)
	$xcadena = "";
	for ($xz = 0; $xz < 3; $xz++) {
		$xaux = substr($XAUX, $xz * 6, 6);
		$xi = 0;
		$xlimite = 6; // inicializo el contador de centenas xi y establezco el límite a 6 dígitos en la parte entera
		$xexit = true; // bandera para controlar el ciclo del While
		while ($xexit) {
			if ($xi == $xlimite) { // si ya llegó al límite máximo de enteros
				break; // termina el ciclo
			}

			$x3digitos = ($xlimite - $xi) * -1; // comienzo con los tres primeros digitos de la cifra, comenzando por la izquierda
			$xaux = substr($xaux, $x3digitos, abs($x3digitos)); // obtengo la centena (los tres dígitos)
			for ($xy = 1; $xy < 4; $xy++) { // ciclo para revisar centenas, decenas y unidades, en ese orden
				switch ($xy) {
					case 1: // checa las centenas
						if (substr($xaux, 0, 3) < 100) { // si el grupo de tres dígitos es menor a una centena ( < 99) no hace nada y pasa a revisar las decenas

						} else {
							$key = (int) substr($xaux, 0, 3);
							if (TRUE === array_key_exists($key, $xarray)){  // busco si la centena es número redondo (100, 200, 300, 400, etc..)
								$xseek = $xarray[$key];
								$xsub = subfijo($xaux); // devuelve el subfijo correspondiente (Millón, Millones, Mil o nada)
								if (substr($xaux, 0, 3) == 100)
									$xcadena = " " . $xcadena . " CIEN " . $xsub;
								else
									$xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
								$xy = 3; // la centena fue redonda, entonces termino el ciclo del for y ya no reviso decenas ni unidades
							}
							else { // entra aquí si la centena no fue numero redondo (101, 253, 120, 980, etc.)
								$key = (int) substr($xaux, 0, 1) * 100;
								$xseek = $xarray[$key]; // toma el primer caracter de la centena y lo multiplica por cien y lo busca en el arreglo (para que busque 100,200,300, etc)
								$xcadena = " " . $xcadena . " " . $xseek;
							} // ENDIF ($xseek)
						} // ENDIF (substr($xaux, 0, 3) < 100)
						break;
					case 2: // checa las decenas (con la misma lógica que las centenas)
						if (substr($xaux, 1, 2) < 10) {

						} else {
							$key = (int) substr($xaux, 1, 2);
							if (TRUE === array_key_exists($key, $xarray)) {
								$xseek = $xarray[$key];
								$xsub = subfijo($xaux);
								if (substr($xaux, 1, 2) == 20)
									$xcadena = " " . $xcadena . " VEINTE " . $xsub;
								else
									$xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
								$xy = 3;
							}
							else {
								$key = (int) substr($xaux, 1, 1) * 10;
								$xseek = $xarray[$key];
								if (20 == substr($xaux, 1, 1) * 10)
									$xcadena = " " . $xcadena . " " . $xseek;
								else
									$xcadena = " " . $xcadena . " " . $xseek . " Y ";
							} // ENDIF ($xseek)
						} // ENDIF (substr($xaux, 1, 2) < 10)
						break;
					case 3: // checa las unidades
						if (substr($xaux, 2, 1) < 1) { // si la unidad es cero, ya no hace nada

						} else {
							$key = (int) substr($xaux, 2, 1);
							$xseek = $xarray[$key]; // obtengo directamente el valor de la unidad (del uno al nueve)
							$xsub = subfijo($xaux);
							$xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
						} // ENDIF (substr($xaux, 2, 1) < 1)
						break;
				} // END SWITCH
			} // END FOR
			$xi = $xi + 3;
		} // ENDDO

		if (substr(trim($xcadena), -5, 5) == "ILLON") // si la cadena obtenida termina en MILLON o BILLON, entonces le agrega al final la conjuncion DE
			$xcadena.= " DE";

		if (substr(trim($xcadena), -7, 7) == "ILLONES") // si la cadena obtenida en MILLONES o BILLONES, entoncea le agrega al final la conjuncion DE
			$xcadena.= " DE";

		// ----------- esta línea la puedes cambiar de acuerdo a tus necesidades o a tu país -------
		if (trim($xaux) != "") {
			switch ($xz) {
				case 0:
					if (trim(substr($XAUX, $xz * 6, 6)) == "1")
						$xcadena.= "UN BILLON ";
						else
							$xcadena.= " BILLONES ";
						break;
				case 1:
					if (trim(substr($XAUX, $xz * 6, 6)) == "1")
						$xcadena.= "UN MILLON ";
						else
							$xcadena.= " MILLONES ";
						break;
				case 2:
					if ($xcifra < 1) {
						$xcadena = "CERO $xdecimales/100 $currency_text ";
					}
					if ($xcifra >= 1 && $xcifra < 2) {
						$xcadena = "UN $xdecimales/100 $currency_text ";
					}
					if ($xcifra >= 2) {
						$xcadena.= " $xdecimales/100 $currency_text "; //
					}
					break;
			} // endswitch ($xz)
		} // ENDIF (trim($xaux) != "")
		// ------------------      en este caso, para México se usa esta leyenda     ----------------
		$xcadena = str_replace("VEINTI ", "VEINTI", $xcadena); // quito el espacio para el VEINTI, para que quede: VEINTICUATRO, VEINTIUN, VEINTIDOS, etc
		$xcadena = str_replace("  ", " ", $xcadena); // quito espacios dobles
		$xcadena = str_replace("UN UN", "UN", $xcadena); // quito la duplicidad
		$xcadena = str_replace("  ", " ", $xcadena); // quito espacios dobles
		$xcadena = str_replace("BILLON DE MILLONES", "BILLON DE", $xcadena); // corrigo la leyenda
		$xcadena = str_replace("BILLONES DE MILLONES", "BILLONES DE", $xcadena); // corrigo la leyenda
		$xcadena = str_replace("DE UN", "UN", $xcadena); // corrigo la leyenda
	} // ENDFOR ($xz)
	return trim($xcadena);
}
endif;
if( !function_exists('subfijo') ):
function subfijo($xx)
{ 
	// esta función regresa un subfijo para la cifra
	$xx = trim($xx);
	$xstrlen = strlen($xx);
	if ($xstrlen == 1 || $xstrlen == 2 || $xstrlen == 3)
		$xsub = "";
	//
	if ($xstrlen == 4 || $xstrlen == 5 || $xstrlen == 6)
		$xsub = "MIL";
	//
	return $xsub;
}
endif;
function mb_invoices_get_dosages($store_id = null)
{
	$query = "SELECT * ".
				"FROM mb_invoice_dosages ".
				"WHERE 1 = 1 " .
				(($store_id) ? "AND store_id = $store_id " : "") .
				"AND emission_limit_date >= curdate() " .
				"ORDER BY name ASC";
	
	return SB_Factory::getDbh()->FetchResults($query);
}
/**
 * Get default dosage
 * 
 * @return object
 */
function mb_invoices_get_default_dosage($store_id = null)
{
	$query = "SELECT * FROM mb_invoice_dosages ".
				"WHERE is_default = 1 ";
	if( !SB_Module::moduleExists('mb') )
	{
		$query .= "AND store_id = 0 ";
	}
	elseif( $store_id )
	{
		$query .= "AND store_id = $store_id ";
	}
	$query .= "LIMIT 1";
	return SB_Factory::getDbh()->FetchRow($query);
}
/**
 * Get available currencies for invoices
 * 
 * @return Array
 */
function mb_invoices_get_currencies()
{
	return SB_Module::do_action('mb_invoices_currencies', array('bob' => __('Bolivianos', 'invoices'), 
			'usd' => __('United States Dollar (USD)', 'invoices')));
}
/**
 * Create and insert a new invoice into database
 * 
 * @param array|object $data
 * @param array $items
 * @return LT_MBInvoice
 */
function mb_invoices_insert_new($data, $items = array())
{
	$invoice = null;
	if( !isset($data['invoice_id']) || !$data['invoice_id'] )
	{
		$invoice = new LT_MBInvoice();
		$invoice->SetDbData($data);
		foreach($items as $item)
		{
			$invoice->AddItem($item);
		}
		//print_r($invoice);
		SB_Module::do_action_ref('mb_invoices_before_insert', $invoice);
		$invoice->Save();
		SB_Module::do_action_ref('mb_invoices_after_insert', $invoice);
		//return $invoice;
	}
	else 
	{
		$invoice = new LT_MBInvoice($data['invoice_id']);
		$invoice->SetDbData($data);
		SB_Module::do_action_ref('mb_invoices_before_insert', $invoice);
		$invoice->Save();
		SB_Module::do_action_ref('mb_invoices_after_insert', $invoice);
		//return $invoice;
	}
	SB_Module::do_action_ref('mb_invoices_after_insert', $invoice);
	return $invoice;
}
function mb_invoice_get_templates()
{
	$templates = array();
	$path = MOD_INVOICES_DIR . SB_DS . 'tpl';
	$dh = opendir($path);
	while( ($file = readdir($dh)) !== false )
	{
		if( $file[0] == '.' ) continue;
		$info_file = $path . SB_DS . $file;
		if( !file_exists($info_file) ) continue;
		$templates[$file] = sb_get_template_info($info_file);
	}
	closedir($dh);

	return $templates;
}
function mb_invoices_show_tpl_fields($fields)
{
	
}
/**
 *
 * @param string $title
 * @param string $subject
 * @return Dompdf\Dompdf a pdf instance
 */
function mb_invoices_get_pdf_instance($title, $subject = '')
{
	/*
	sb_include_lib('tcpdf/tcpdf.php');
	// create new PDF document
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	// set document information
	$pdf->SetCreator('MonoBusiness - Sintic Bolivia');
	$pdf->SetAuthor('J. Marcelo Aviles Paco');
	$pdf->SetTitle($title);
	$pdf->SetSubject($subject);
	//$pdf->SetKeywords('TCPDF, PDF, example, test, guide');
	// set default monospaced font
	$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
	$pdf->SetFont('dejavusans', '', 14, '', true);
	$pdf->SetFont('freesans', '', 10, '', true);
	// set margins
	$pdf->SetMargins(4, 5, 3);
	$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
	$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);
	// set auto page breaks
	$pdf->SetAutoPageBreak(TRUE, 10);
	*/
	//if( !class_exists('FontLib\Autoloader', !true) )
	//	sb_include_lib('dompdf/autoload.inc.php');
	
	$pdf = new Dompdf\Dompdf();
	$pdf->set_option('defaultFont', 'Helvetica');
	$pdf->set_option('isRemoteEnabled', true);
	$pdf->set_option('isPhpEnabled', true);
	//$pdf->set_option('defaultPaperSize', 'Legal');
	// (Optional) Setup the paper size and orientation
	$pdf->setPaper('letter'/*, 'landscape'*/);
	return $pdf;
}
/**
 * Build and issue a new invoice from an order id or object
 * 
 * The function returns the new invoice id or false on failure
 * 
 * @param int|SB_MBOrder $id
 * @return int $invoice_id
 */
function mb_invoices_order2invoice($id, $user_id)
{
	$order = null;
	if( is_int($id) )
	{
		$order = new SB_MBOrder((int)$id);
	}
	elseif( is_object($id) )
	{
		$order = $id;
	}
	else
	{
		throw new Exception(__('Invalid order identifier', 'invoices'));
	}
	//##check if order already has an invoice id
	if( $order->_invoice_id )
		return (int)$order->_invoice_id;
	//##check if the order has a customer
	if( !$order->customer )
		throw new Exception(__('The order has no customer', 'invoices'));
	if( !$order->customer->_nit_ruc_nif )
	{
		throw new Exception(__('The customer has no NIT/RUC/NIF', 'invoices'));
	}
	
	//##get the default dosage
	$dosage	= mb_invoices_get_default_dosage($order->store_id);
	if( !$dosage )
		throw new Exception(__('The store has no default dosage, you need to assign atleast one and mark it as default', 'invoices'));
	$invoice_number = LT_MBInvoice::GetNextInvoiceNumber($dosage->id, $order->store_id);
	require_once MOD_INVOICES_DIR . SB_DS . 'countries' . SB_DS . 'class.BO.php';
	
	$builder = new SB_InvoiceBO();
	$control_code = $builder->buildControlCode(array(
			'authorization_number'	=> $dosage->authorization,
			'invoice_number'		=> $invoice_number,
			'nit_ci'				=> $order->customer->_nit_ruc_nif,
			'transaction_date'		=> date('Ymd'),
			'transaction_amount'	=> $order->total,
			'dosage'				=> $dosage->dosage
	));
	$data = array(
			'dosage_id'				=> $dosage->id,
			'customer_id'			=> $order->customer_id,
			'customer'				=> $order->customer ? $order->customer->first_name . ' ' . $order->customer->last_name : '',
			'user_id'				=> $user_id,
			'store_id'				=> $order->store_id,
			'nit_ruc_nif'			=> $order->customer ? $order->customer->_nit_ruc_nif : '',
			'tax_id'				=> 0,
			'tax_rate'				=> 0,
			'subtotal'				=> $order->subtotal,
			'total_tax'				=> $order->total_tax,
			'total'					=> $order->total,
			'invoice_number'		=> $invoice_number,
			'dosage'				=> $dosage->dosage,
			'control_code'			=> $control_code,
			'authorization'			=> $dosage->authorization,
			'invoice_date_time'		=> date('Y-m-d H:i:s'),
			'invoice_limite_date' 	=> $dosage->emission_limite_date,
			'status'				=> 'issued',
			'creation_date'			=> date('Y-m-d H:i:s')
	);
	$items = array();
	foreach($order->GetItems() as $item)
	{
		$items[] = array(
				'store_id'		=> $order->store_id,
				'product_id'	=> $item->product_id,
				'product_code'	=> $item->product_code,
				'product_name'	=> $item->product_name,
				'price'			=> $item->price,
				'quantity'		=> $item->quantity,
				'total'			=> $item->price * $item->quantity,
				'creation_date'	=> date('Y-m-d H:i:s')
		);
	}
	$invoice = mb_invoices_insert_new($data, $items);
	mb_add_order_meta($order->order_id, '_invoice_id', $invoice->invoice_id);
	SB_Meta::addMeta('mb_invoice_meta', '_order_id', $order->order_id, 'invoice_id', $invoice->invoice_id);
	SB_Module::do_action('mb_invoices_order2invoice', $order, $invoice);
	return $invoice_id;
}