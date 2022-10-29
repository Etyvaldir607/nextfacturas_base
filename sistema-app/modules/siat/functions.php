<?php
function sb_number_format($number, $decimals = null, $ds = null, $hs = null)
{
	$decimal_separator 	= $ds !== null ? $ds : (defined('DECIMAL_SEPARATOR') ? DECIMAL_SEPARATOR : '.');
	$hundred_separator 	= $hs !== null ? $hs : (defined('HUNDRED_SEPARATOR') ? HUNDRED_SEPARATOR : ',');
	$decimals 			= $decimals !== null ? (int)$decimals : ((defined('DECIMALS') && (int)DECIMALS) ? DECIMALS : 2);
	
	return number_format((float)$number, $decimals, $decimal_separator, $hundred_separator);
}
function sb_format_datetime($date, $format = null)
{
	$date_format = 'Y-m-d';
	$time_format = 'H:i:s';
	if( defined('DATE_FORMAT') )
	{
		$date_format = DATE_FORMAT;
	}
	if( defined('TIME_FORMAT') )
	{
		$time_format = TIME_FORMAT;
	}
	$the_format = "$date_format $time_format";
	if( $format != null )
	{
		$the_format = $format;
	}
	if( is_numeric($date) )
		return date("$the_format", $date);
		
		//$date_time = is_numeric($date) ? $date : strtotime($date);
		$date_time = strtotime(str_replace('/', '-', $date));
		return date($the_format, $date_time);
}