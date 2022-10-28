<?php

function printer_image($file = '')
{
    $interlineado = ln();
    return "^FO210,50^GFA,2185,2185,19,,:U01KF8,T03FF03FBFC,S01IF03FBFF8,S0JF83F7IF,R07FDFF83F7FFEE,Q01FFEFF83F7FFE38,Q07FFE7F83F1FFC3E,P01IFE7F83F0FFC7F8,P07JF7F83F07F87FE,P0CFDFF3F83F07F0IF,O03879FF0783F03E0IFC,O07838FF0783F03C1FFBE,O0DC1CFF87C3F0781FF1F8,N038E1C7F87C3E0603FE3FC,N07CE0E7F83C3E0603FE1FE,N0FE7073F83C3E0407FC0FB,M01BE1873F0043E0C07F80718K03E,M030F0C39F0043E0C0FF80304J07FE,M06078418F0043E080FF3IFE001IFE,I03JFC0C201IFC3E081IF800FC1JFE,I02J0406001I0C3C181FFK079JFE,I03J0603001800E3C183F8I0301CJFE,I038I0301801800E3C103EM077IFC,I03J0100C01800E3C3018M01BIFC,I03J0180401800E3C307O06IFC,I03K0CI01800E3C30EO03IFC,I03K0EI01800E3C21CO01IFC,I03K07I01800E0C638O03IFC,I03K038001800C0063P07IFC,I03K03E001800C0046P0JFC,I03K01F001800C004CJ03FC001JFC,I03L0F801800C00D8J0IF003JFC,I03L0DC01800C00D8I01IFC07FFEFC,I03L04F01800C00BJ07F9FE0IFC78,I03L06381800C00BJ07E09F1IF838,I03L031C1800C01EJ0F800F1IFC08,I03L010E1800C01EJ0CI0FBIFC,I03L01831800C01CI01CI07IFE4,I03M0803800C01CI018I07IFE4,I03M0C01800C038I018I07IFC6,I03M0601800C018I03I01JF86,I030018I0201800C018I03I0KF02,I03001CI0301800C018I03007JFE02,I03001EI0181800C018I0201MFA,I03001EI0181800C038I0601NF,I03001FJ0C3800C038I0600MFE,I03001FJ041800C038I06008KF8,I03001F8I061800C03J06001KFC,I03001FCI031800C03J06003KFC,I03001DCI011800C03J06007KFC,I030019EI019800C03J0601LF4,I030018EJ09800C03J0603IFE004,I0300187J0D800C03J040JFC004,I03001878I07800C03J060JF8004,I03001838I03800C03J061JFI04,I0300181CI03800C03J067IFEI04,I03001FFEI01800C038I06JFCI04,I03001FFEI01800C038I07JF8I04,I03001IFM0C038I07JFJ04,I03001IFM0C038I07IFEJ04,I03001IF8L0C038I07IFC03C04,I03001FFBCL0C038I07IFC07IFE,I03001FC1CL0C018I03IF7F6001F,I03001F81EL0C01CI03FFE03EI02,I03001F80EL0C01CI03FFC006I02,I03001F807L0C01CI03FFC00CI06,I03001F0078K0C00EI01IF80CI06,I03001F81F8K0C00EI01IFC18I06,I03001F07FCK0C00EJ0IFC18I04,I0300183E1EK0C00FJ0F7FC3J0C,I030019F80EK0C03FJ061FE6J0C,I03001FC00FK0C0FF8I0383ECI018,I03001FI07K0C7FF8J0F0F8I018,I03001FI038J0DIFCJ03FEJ03,I03001EI07CJ0JFEQ03,I030018001FCJ0KFQ0E,I030018003FEJ0KF8O01E,I07001800FFEJ0KFCO03C,I0F001FE1IFJ0KFEO078,I0F001LF8I0LFO0F,001F001LF8I0LF8M01E,003F001LFCI0KF3EM07C,007F001LFCI0JFC1F8K01F8,00FFI0LFEI0JF807FK0FE,01TFEIFE803FFI0FF8,03XFC400MF8,03WFE44007LF,07WFBE4003DIF86,0WFC3E2001E3FE06,1WF03F2003F3FF0C,3VFB03FI07F9FF98,7UFC303F1003FCIF,7UF8201F9001FE7FE,3TF8FE01FC821FE3FC,03SF1FE01FC9F0FF1F8,003OFDFF1FE01FCFF07F8F,J07LF01FE1FE07FE7F87FC6,N03FE03FE3FE07FE7F8FFEC,O0FC07FE3FE07FF3KF,O07807FC3FE3IF3JFE,O0180FFC3FE3IFBJFC,P0E0FFC7FE3IF9JF,P031FF87FE3IFDIFC,P01IF87FE3IFDIF8,Q07FF8FFE3IFCFFE,Q01FF0FFE3IFEFF8,R03F0FFC3IFE7C,S0F8FFC3KF,S01IFC3JF8,T01FFC3IF8,V0KF,,:^FS";
}

function printer_explode($string, $limit, $align = STR_PAD_BOTH)
{
    $string = explode(' ', $string);
    $line = '';
    $lines = array();
    $line_size = 0;
    foreach ($string as $key => $element) {

        $element_size = strlen($element) + 1;
        if ($line_size + $element_size <= $limit) {
            $line_size = $line_size + $element_size;
            $line = $line . $element . ' ';
        } else {
            array_push($lines, str_pad(trim($line), $limit, ' ', $align));
            $line_size = $element_size;
            $line = $element . ' ';
        }
    }
    array_push($lines, str_pad(trim($line), $limit, ' ', $align));
    return $lines;
}

/**
 * Alinea el texto
 */
function printer_justify($left = '', $right = '', $margin = 0)
{
    global $full_width, $altoLetra, $anchoLetra;
    $length = $full_width - strlen($left) - strlen($right) - ($margin * 2);

    if ($length > 0) {
        $space = str_pad('', $length, ' ', STR_PAD_RIGHT);
        $text = $left . $space . $right;
    } else {
        $text = substr($left . ' ' . $right, 0, $full_width - ($margin * 2));
    }
    $espacio = ln();

    return "^FT0,$espacio^AcN,$altoLetra,$anchoLetra^FH\^FD$text^FS";
}

/**
 * Imprime un texto
 */
function printer_draw_text_custom($string, $align = STR_PAD_BOTH)
{
    global $full_width;
    global $altoLetra;
    global $anchoLetra;
    $interlineado = 20;
    $string = printer_explode($string, $full_width, $align);
    $linea = "";
    foreach ($string as $key => $element) {
        $interlineado = ln();
        $linea .= "^FT0,$interlineado^AcN,$altoLetra,$anchoLetra^FH\^FD$element^FS";
    }
    return $linea;
}

/**
 * Imprime una linea
 */
function printer_draw_line_custom()
{
    global $altoLetra, $widthLabel;
    $line = ln() - intval(($altoLetra / 2));
    return "^FO0,$line^GB$widthLabel,0,2^FS";
}

/**
 * Ordena las columnas
 */

function printer_center($codes1 = '', $quantity = '', $detail = '', $price = '', $amount = '')
{
    $val1 = explode(" ", utf8_decode("Á É Í Ó Ú á é í ó ú ñ Ñ"));
    $val2 = array('\B5', '\90', '\D6', '\E3', '\E9', '\A0', '\82', '\A1', '\A2', '\A3', '\A4', '\A5');
    global $full_width;
    $column   = 10;
    $detail   = (($full_width - ($column * 3) - 3) > strlen($detail)) ? $detail : substr($detail, 0, ($full_width - ($column * 3) - 3));
    $quantity = ' ' . $quantity . ' ';

    $codes1 = ' ' . $codes1 . ' ';

    $detail   = ' ' . str_replace($val1, $val2, utf8_decode($detail)) . ' ';
    $price    = ' ' . $price . ' ';
    $amount   = ' ' . $amount . ' ';
    $quantity = str_pad($quantity, $column - 1, ' ', STR_PAD_RIGHT);
    $code1 	  = str_pad($codes1, $column - 1, ' ', STR_PAD_RIGHT); // Josema
    $detail   = str_pad($detail, $column - 2, ' ', STR_PAD_LEFT);
    $price    = str_pad($price, $column - 1, ' ', STR_PAD_LEFT);
    $amount   = str_pad($amount, $column , ' ', STR_PAD_LEFT);
    return  $code1 . $quantity . $price . $detail . $amount;
}



function printer_center2($aux1='',$aux2=''){
    $val1 = explode(" ", utf8_decode("Á É Í Ó Ú á é í ó ú ñ Ñ"));
    $val2 = array('\B5', '\90', '\D6', '\E3', '\E9', '\A0', '\82', '\A1', '\A2', '\A3', '\A4', '\A5');
    $column     = 2;
    $aux1   = ' ' . $aux1 . ' ';
    $aux2   = ' ' . str_replace($val1,$val2,utf8_decode($aux2)) . ' ';
    $aux1   = str_pad($aux1, $column+36, ' ', STR_PAD_RIGHT);
    $aux2   = str_pad($aux2, $column, ' ', STR_PAD_RIGHT);
    return $aux1.$aux2;
}



function printer_center3($aux1='',$aux2='',$aux3=''){
    $val1 = explode(" ", utf8_decode("Á É Í Ó Ú á é í ó ú ñ Ñ"));
    $val2 = array('\B5', '\90', '\D6', '\E3', '\E9', '\A0', '\82', '\A1', '\A2', '\A3', '\A4', '\A5');
    $column     = 16;
    $aux1   = ' ' . $aux1 . ' ';
    $aux2   = ' ' . $aux2 . ' ';
    $aux3   = ' ' . $aux3 . ' ';
    $aux1   = str_pad($aux1, $column, ' ', STR_PAD_RIGHT);
    $aux2   = str_pad($aux2, $column, ' ', STR_PAD_RIGHT);
    $aux3   = str_pad($aux3, $column, ' ', STR_PAD_RIGHT);
    return $aux1.$aux2.$aux3;
}


/**
 * Retorna la posición de la siguiente linea
 */
function ln($size = 1)
{
    global $jump, $altoLetra;
    $jump = $jump + ($altoLetra * $size);
    return $jump;
}

function contador_signos($string)
{
    $count = 0;
    $signos = array(utf8_decode('Á'), utf8_decode('É'), utf8_decode('Í'), utf8_decode('Ó'), utf8_decode('Ú'), utf8_decode('á'), utf8_decode('é'), utf8_decode('í'), utf8_decode('ó'), utf8_decode('ú'), utf8_decode('ñ'), utf8_decode('Ñ'), utf8_decode('º'), utf8_decode('¡'));

    for ($i = 0; $i < strlen($string); $i++) {
        $car = substr($string, $i, 1);
        if (in_array($car, $signos)) {
            $count++;
        }
    }
    return $count;
}

/**
 * Definiendo el ancho de la etiqueta (en milimetros)
 */
$anchoEtiqueta = 60;
/**
 * convirtiendo el ancho de la etiqueta en su equivalente en puntos
 */
$widthLabel    = intval(9 * $anchoEtiqueta) - 2;
/**
 * Definiendo el ancho de impresión
 */
$printWidth    = $widthLabel + 2;
/**
 * Definiendo el tamaño de la fuente
 */
$anchoLetra    = 10;
$altoLetra     = 25;
/**
 * Definimos el número de caracteres
 */
$full_width    = round($widthLabel / ($anchoLetra + 2));
/**
 * Definimos el número de columna para la tabla detalle de venta
 */
$column = 8;
/**
 * Definimos el punto de inicio de la primera linea
 */
$jump = 0 - $altoLetra;


function generar_zpl($datos)
{

    global $altoLetra,$jump,$anchoLetra,$widthLabel,$printWidth,$full_width,$column;

    /**
     * Definimos una lista con los carácteres especiales
     */
    $val1 = explode(" ",utf8_decode("Á É Í Ó Ú á é í ó ú ñ Ñ º ¡"));
    $val2 = array('\B5','\90','\D6','\E3','\E9','\A0','\82','\A1','\A2','\A3','\A4','\A5','\A7','\AD');

    $empresa_nombre         = utf8_decode($datos['empresa_nombre']);
    $empresa_sucursal       = utf8_decode($datos['empresa_sucursal']);
    $empresa_direccion      = utf8_decode($datos['empresa_direccion']);
    $empresa_telefono       = utf8_decode($datos['empresa_telefono']);
    $empresa_ciudad         = utf8_decode($datos['empresa_ciudad']);
    $empresa_actividad      = utf8_decode($datos['empresa_actividad']);
    $empresa_nit            = utf8_decode($datos['empresa_nit']);
    $factura_titulo         = utf8_decode($datos['factura_titulo']);
    $factura_numero         = utf8_decode($datos['factura_numero']);
    $factura_autorizacion   = utf8_decode($datos['factura_autorizacion']);
    $factura_fecha          = utf8_decode($datos['factura_fecha']);
    $factura_hora           = utf8_decode($datos['factura_hora']);
    $cliente_codigo         = utf8_decode($datos['cliente_codigo']);
    $factura_codigo         = utf8_decode($datos['factura_codigo']);
    $factura_limite         = utf8_decode($datos['factura_limite']);
    $factura_autenticidad   = utf8_decode($datos['factura_autenticidad']);
    $factura_leyenda        = utf8_decode($datos['factura_leyenda']);
    $cliente_nit            = utf8_decode($datos['cliente_nit']);
    $cliente_nombre         = utf8_decode($datos['cliente_nombre']);
    $venta_titulos          = $datos['venta_titulos'];
    $venta_cantidades       = $datos['venta_cantidades'];
    $venta_detalles         = $datos['venta_detalles'];
    $venta_codes         	= $datos['venta_codes'];  // josema
    $venta_precios          = $datos['venta_precios'];
    $venta_descuentos       = $datos['venta_descuentos'];
    $venta_subtotales       = $datos['venta_subtotales'];
    $venta_total_titulo     = utf8_decode($datos['venta_total_titulo']);
    $venta_total_titulo1     = utf8_decode($datos['venta_total_titulo1']);
    $venta_total_titulo2     = utf8_decode($datos['venta_total_titulo2']);
    $venta_total_numeral    = $datos['venta_total_numeral'];
    $venta_total_numeral1    = $datos['venta_total_numeral1'];
    $venta_total_numeral2    = $datos['venta_total_numeral2'];
    $venta_total_literal    = utf8_decode($datos['venta_total_literal']);
    $factura_qr             = $datos['factura_qr'];
    $factura_vendedor       = $datos['factura_vendedor'];
    $factura_agradecimiento = $datos['factura_agradecimiento'];

    ln(4);
    $imagen = printer_image();
    ln();
    ln();
    ln();
    /**
     * Imprime el nombre de la empresa
     */
    $empresa_nombre = str_replace($val1, $val2,printer_draw_text_custom($empresa_nombre));
    /**
     * Imprime la sucursal de la empresa
     */
    $empresa_sucursal  = str_replace($val1, $val2,printer_draw_text_custom($empresa_sucursal));
    /**
     * Imprime la direccion de la empresa
     */
    $empresa_direccion = str_replace($val1, $val2,printer_draw_text_custom($empresa_direccion));
    /**
     * Imprime el telefono de la empresa
     */
    $empresa_telefono  = str_replace($val1, $val2,printer_draw_text_custom($empresa_telefono));
    /**
     * Imprime la ciudad de funcionamiento de la empresa
     */
    $empresa_ciudad    = str_replace($val1, $val2, printer_draw_text_custom($empresa_ciudad));

    ln();
    /**
     * Imprime el titulo de la factura
     */
    $factura_titulo       = str_replace($val1, $val2, printer_draw_text_custom($factura_titulo));

    ln();
    /**
     * Dibuja una linea
     */
    $linea1               = printer_draw_line_custom();
    /**
     * Imprime el nit de la empresa
     */
    $empresa_nit          = str_replace($val1, $val2, printer_draw_text_custom($empresa_nit));
    /**
     * Imprime el numero de la factura
     */
    $factura_numero       = str_replace($val1, $val2, printer_draw_text_custom($factura_numero));
    /**
     * Imprime el numero de autorizacion de la factura
     */
    $factura_autorizacion = str_replace($val1, $val2, printer_draw_text_custom($factura_autorizacion));
    /**
     * Dibuja una linea
     */
    $linea2               = printer_draw_line_custom();
    /**
     * Imprime la actividad de la empresa
     */
    $empresa_actividad    = str_replace($val1, $val2, printer_draw_text_custom($empresa_actividad));
    /**
     * Imprime la fecha y hora de emision de la factura
     */
    $fecha_hora           = printer_justify($factura_fecha,$factura_hora);
    /**
     * Imprime el nit del cliente
     */
    $cliente_nit          = str_replace($val1, $val2, printer_justify($cliente_nit,$cliente_codigo ));
    /**
     * Imprime el nombre del cliente
     */
    $cliente_nombre       = str_replace($val1, $val2,printer_draw_text_custom($cliente_nombre,STR_PAD_RIGHT));//printer_draw_text_custom(str_replace($val1, $val2, $cliente_nombre),STR_PAD_RIGHT);

    $linea_1top = $jump+intval(($altoLetra/2));
    /**
     * Dibuja una linea
     */
    $linea_cab  = printer_draw_line_custom();
    $linea_1 = ln();
    /**
     * Imprime los titulos de la venta
     */
    $res = printer_center(isset($venta_titulos[0]) ? $venta_titulos[0] : '',isset($venta_titulos[1]) ? $venta_titulos[1] : '',isset($venta_titulos[2]) ? $venta_titulos[2] : '',isset($venta_titulos[3]) ? $venta_titulos[3] : '',isset($venta_titulos[4]) ? $venta_titulos[4] : '');
    /**
     * Definime la posicion de los titulos de la venta
     */
    $cabecera   = "^FT0,$linea_1^AcN,$altoLetra,$anchoLetra^FH\^FD$res^FS";
    $spaceH     = $linea_1;
    /**
     * Dibuja una linea
     */
    $linea_cab2 = printer_draw_line_custom();

    $padding = 2;
    $tabla = "";

    $spaceH = $spaceH + ($altoLetra*1+$padding);
    /**
     * Imprime las cantidades, los detalles, los precios y los subtotales de la venta
     */
    foreach ($venta_cantidades as $key => $cantidad) {
        $lineat = ln();
        $detalle  = (isset($venta_detalles[$key])) ? $venta_detalles[$key] : '';
        $code  	  = (isset($venta_codes[$key])) ? $venta_codes[$key] : ''; //josema
        $precio   = (isset($venta_precios[$key])) ? $venta_precios[$key] : '';
        $descuentos   = (isset($venta_descuentos[$key])) ? $venta_descuentos[$key] : '';
        $subtotal = (isset($venta_subtotales[$key])) ? $venta_subtotales[$key] : '';
        $res = printer_center($code,$cantidad,$descuentos,$precio,$subtotal);
        $res2 = printer_center($detalle);
        $tabla.= "^FT0,$lineat^AcN,$altoLetra,$anchoLetra^FH\^FD$res2^FS";
        $lineat = ln();
        $tabla.= "^FT0,$lineat^AcN,$altoLetra,$anchoLetra^FH\^FD$res^FS";

    }
    /**
     * Dibuja una linea
     */
    $linea_cab3          = printer_draw_line_custom();
    /**
     * Imprime el total de la venta
     */
    $pie                 = printer_justify(' ' . $venta_total_titulo,$venta_total_numeral . ' ');
    $pie1                 = printer_justify(' ' . $venta_total_titulo1,$venta_total_numeral1 . ' ');
    $pie2                 = printer_justify(' ' . $venta_total_titulo2,$venta_total_numeral2 . ' ');
    $temC                = $jump;
    /**
     * Dibuja una linea
     */
    $linea_cab4          = printer_draw_line_custom();
    $temLR               = $jump;
    /**
     * Imprime el monto total en literal
     */
    $venta_total_literal = str_replace($val1, $val2, printer_draw_text_custom($venta_total_literal,STR_PAD_RIGHT));
    /**
     * Dibuja una linea
     */
    $linea_cab5          = printer_draw_line_custom();
    /**
     * Imprime el codigo de control
     */
    if($factura_autenticidad !=  ''){
        $factura_codigo      = str_replace($val1, $val2, printer_draw_text_custom($factura_codigo,STR_PAD_RIGHT));
    }else{
        $factura_codigo = '';
    }
    /**
     * Imprime la fecha limite de emision
     */
    if($factura_autenticidad !=  ''){
        $factura_limite        = str_replace($val1, $val2, printer_draw_text_custom($factura_limite,STR_PAD_RIGHT));
    }else{
        $factura_limite = '';
    }

    $temC   = $temC-intval(($altoLetra/2));
    $lineat = $temLR+intval(($altoLetra/2));
    /**
     * Definimos la posicion en que se imprimira el monto total de la venta
     */
    $pie = "^FT0,$lineat^AcN,$altoLetra,$anchoLetra^FH\^FD$pie^FS";
    $pie1 = "^FT0,$lineat^AcN,$altoLetra,$anchoLetra^FH\^FD$pie1^FS";
    $pie2 = "^FT0,$lineat^AcN,$altoLetra,$anchoLetra^FH\^FD$pie2^FS";
    /**
     * Imprime el nombre de la empresa
     */
    $alv = $lineat-$linea_1+($altoLetra/2);
    $alvC= $temC-$linea_1+($altoLetra/2);

    $verticalL = "^FO0,$linea_1top^GB1,$alv,3^FS";
    $verticalR = "^FO$widthLabel,$linea_1top^GB1,$alv,3^FS";

    /**
     * posicion en el eje x (en puntos) para la primera linea vertical
     */
    //$p1 = $column*($anchoLetra+2);
    /**
     * posicion en el eje x (en puntos) para la segunda linea vertical
     */
    //$p2 = (($column * 3)+4)*($anchoLetra+2);
    /**
     * posicion en el eje x (en puntos) para la tercera linea vertical
     */
    //$p3 = $p2+9*($anchoLetra+2);
    $p4 = $printWidth-95; // Josema
    $p3 = $printWidth-200;
    $p2 = $printWidth-370;
    $p1 = 75;

    /**
     *  Dibuja las lineas verticales
     */
    $verticalC1 = "^FO$p1,$linea_1top^GB1,$alvC,3^FS";
    $verticalC2 = "^FO$p2,$linea_1top^GB1,$alvC,3^FS";
    $verticalC3 = "^FO$p3,$linea_1top^GB1,$alvC,3^FS";
    $verticalC4 = "^FO$p4,$linea_1top^GB1,$alvC,3^FS"; // Josema
    /**
     * Definimos las dimensiones del codigo QR
     */
    $dpi = 6;
    $lineaqr = $jump + 10;

    /**
     * Imprime el codigo QR
     */
    if($factura_autenticidad !=  ''){
        $cen = 12*(($full_width-17)/2);
        $qr = "^FO$cen,$lineaqr
    	^BQN,2,$dpi
    	^FH\^FDLA,$factura_qr^FS";
        ln(10);
    }else{
        $factura_qr = '';
    }
    if($factura_autenticidad !=  ''){
        /**
         * Imprime el texto de autenticidad
         */
        $factura_autenticidad = str_replace($val1, $val2, printer_draw_text_custom($factura_autenticidad));
    }else{
        $factura_autenticidad = '';
    }
    if($factura_autenticidad !=  ''){
        /**
         * Imprime la leyenda
         */
        $factura_leyenda      = str_replace($val1, $val2, printer_draw_text_custom($factura_leyenda));
        $linea3               = printer_draw_line_custom();
    }else{
        $factura_leyenda = '';
        $linea3               = '';
    }

    $factura_vendedor       = str_replace($val1, $val2,printer_draw_text_custom($factura_vendedor));
    $factura_agradecimiento       = str_replace($val1, $val2,printer_draw_text_custom($factura_agradecimiento));
    ln();

    /**
     * Definimos la estructura que tendra la etiqueta en lenguaje zpl
     */

    $zpl = "^XA
	^PW$printWidth
	^LL$jump
	^LH0,0
	^FS".$imagen.$empresa_nombre.$empresa_sucursal.$empresa_direccion.$empresa_telefono.$empresa_ciudad.$factura_titulo.$linea1.$empresa_nit.$factura_numero.$factura_autorizacion.$linea2.$empresa_actividad.$fecha_hora.$cliente_nit.$cliente_nombre.$cabecera.$tabla.$linea_cab.$linea_cab2.$linea_cab3.$pie.$pie1.$pie2.$linea_cab4.$verticalL.$verticalR.$venta_total_literal.$linea_cab5.$factura_codigo.$factura_limite.$qr.$factura_autenticidad.$factura_leyenda.$linea3.$factura_vendedor.$factura_agradecimiento."^XZ";

    return $zpl;
}

// Define las cabeceras
header('Content-Type: application/json');

// Verifica la peticion post
if (is_post()) {
    // Verifica la existencia de datos
    if (isset($_POST['id_cliente'])) {
        // Importa la configuracion para el manejo de la base de datos
        require config . '/database.php';

        // Importa la libreria para el codigo de control
        require_once libraries . '/controlcode-class/ControlCode.php';

        // Importa la libreria para la conversion de numeros a letras
        require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

        // Obtiene los datos de la venta
        $id_cliente = $_POST['id_cliente'];
        $id_egreso = $_POST['id_egreso'];
        $fecha = date('Y-m-d');
        $venta = $db->select('*')->from('tmp_egresos a')->where('a.cliente_id', $id_cliente)->where(array('a.estado' => 3, 'a.id_egreso' => $id_egreso, 'a.distribuidor_estado' => 'ENTREGA'))->fetch_first();
        $cliente = $db->select('*')->from('inv_clientes')->where('id_cliente',$venta['cliente_id'])->fetch_first();
        if($cliente){
            $nombre_factura = $cliente['nombre_factura'];
        }else{
            $nombre_factura = $venta['nombre_cliente'];
        }
        $empleado = $db->select("CONCAT(paterno, ' ', nombres) as empleado")->from('sys_empleados')->where('id_empleado',$venta['empleado_id'])->fetch_first()['empleado'];
        $productos = $db->select('a.descuento, a.producto_id, a.promocion_id, b.nombre_factura, b.codigo, if(c.cantidad_unidad is null, a.cantidad, a.cantidad/c.cantidad_unidad) as cantidad, a.precio, if(c.cantidad_unidad is null, a.precio*a.cantidad, a.precio*(a.cantidad/c.cantidad_unidad)) as subtotal, d.unidad')->from('inv_egresos_detalles a')->join('inv_productos b', 'a.producto_id = b.id_producto')->join('inv_asignaciones c', 'a.producto_id = c.producto_id and a.unidad_id = c.unidad_id')->join('inv_unidades d', 'a.unidad_id = d.id_unidad')->where('a.egreso_id', $venta['id_egreso'])->where('a.promocion_id !=','1')->fetch();

        // echo json_encode($productos); die();
        // $precios = $db->select('if(b.cantidad_unidad is null, a.precio*a.cantidad, a.precio*(a.cantidad/b.cantidad_unidad)) as precio')->from('inv_egresos_detalles a')->join('inv_asignaciones b','a.producto_id = b.producto_id and a.unidad_id = b.unidad_id')->join('inv_unidades c','a.unidad_id = c.id_unidad')->where('a.egreso_id',$venta['id_egreso'])->fetch();
        $producto = array();
        $codes = array();
        $precios1 = array();
        $cantidades1 = array();
        $descuento1 = array();
        $subtotales = array();
        $venta_t = $venta['monto_total'];
        if($venta['monto_total_descuento'] > 0){
            $venta_tt = $venta['monto_total'] - $venta['descuento_bs'];
        }else{
            $venta_tt = $venta['monto_total'];
        }
        $venta_t = $venta['monto_total'];
        $descuento_t = $venta['descuento_bs'];

        foreach ($productos as $nro => $opcion) {
            if($opcion['promocion_id'] > 1){
                $prod = $db->select('*')->from('inv_productos')->where('id_producto',$opcion['producto_id'])->fetch_first();
                $desc = $prod['precio_actual'];
                array_push($precios1, number_format(($desc), 2, ',', ''));
                $desc = $prod['precio_actual'] - $opcion['precio'];
                array_push($descuento1, number_format(($desc), 2, ',', ''));
//                $descuento_t = $descuento_t + $desc;


            }else{
                array_push($descuento1, number_format(($opcion['descuento']), 2, ',', ''));
                array_push($precios1, number_format(($opcion['precio']), 2, ',', ''));
            }
            array_push($producto, $opcion['nombre_factura']);
            array_push($codes, $opcion['codigo']);


            array_push($subtotales, number_format(($opcion['subtotal']), 2, ',', ''));
            $cantidad_i = round($opcion['cantidad'], 2);
            $unidad_i = $opcion['unidad'][0];
            array_push($cantidades1, $cantidad_i.' '.$unidad_i);
        }

        // Obtiene los datos del monto total
        $conversor = new NumberToLetterConverter();
        $monto_textual = explode('.', $venta['monto_total']);
        $monto_numeral = $monto_textual[0];
        $monto_decimal = $monto_textual[1];
        $monto_literal = ucfirst(strtolower(trim($conversor->to_word($monto_numeral))));


        // $materialesm=$db->query("SELECT m.nombre,m.precio,
        //     IFNULL(
        //         (SELECT SUM(cantidad) FROM inv_control WHERE estado='pendiente' AND inv_control.id_materiales=m.id_materiales AND fecha_control='{$fecha}' AND cliente_id='{$id_cliente}'
        //     ),0)AS pendiente,
        //     IFNULL(
        //         (SELECT SUM(cantidad) FROM inv_control WHERE estado='vendido' AND inv_control.id_materiales=m.id_materiales AND fecha_control='{$fecha}' AND cliente_id='{$id_cliente}'
        //     ),0)AS vendido,
        //     IFNULL(
        //         (SELECT SUM(cantidad) FROM inv_control WHERE estado='entregado' AND inv_control.id_materiales=m.id_materiales AND fecha_control='{$fecha}' AND cliente_id='{$id_cliente}'
        //     ),0)AS entregado
        //     FROM inv_control AS c
        //     LEFT JOIN inv_materiales AS m ON m.id_materiales=c.id_materiales
        //     WHERE c.fecha_control='{$fecha}' AND c.cliente_id='{$id_cliente}' GROUP BY m.id_materiales")->fetch();

        $materialesm=[];
        // $materialesm='';

        $materialm=[];
        $preciosm=[];
        $pendientem=[];
        $vendidom=[];
        $entregadom=[];
        $subtotalm=[];
        $totalm=0;
        foreach($materialesm as $nro=>$Dato):
            array_push($materialm,'Canastilla');
            array_push($preciosm,$Dato['cantidad_pres']);
            array_push($pendientem , $Dato['cantidad_pres']);
        endforeach;
        $totalm=number_format($totalm,2);
        $monto_textualm=explode('.',$totalm);
        $monto_numeralm=$monto_textualm[0];
        $monto_decimalm=$monto_textualm[1];
        $monto_literalm=ucfirst(strtolower(trim($conversor->to_word($monto_numeralm))));




        $pagos=$db->query("SELECT pd.nro_cuota,pd.monto,pd.tipo_pago
                        FROM inv_pagos_detalles AS pd
                        LEFT JOIN inv_pagos AS p ON p.id_pago=pd.pago_id
                        LEFT JOIN inv_egresos AS e ON e.id_egreso=p.movimiento_id
                        WHERE pd.fecha='{$fecha}' AND e.cliente_id='{$id_cliente}'")->fetch();
        $nro_cuotap=[];
        $montop=[];
        $tipo_pagop=[];
        $totalp=0;
        foreach($pagos as $nro=>$Dato):
            array_push($nro_cuotap,$Dato['nro_cuota']);
            array_push($montop,$Dato['monto']);
            array_push($tipo_pagop,$Dato['tipo_pago']);
            $totalp=$totalp+$Dato['monto'];
        endforeach;
        $totalp=number_format($totalp,2);
        $monto_textualp=explode('.',$totalp);
        $monto_numeralp=$monto_textualp[0];
        $monto_decimalp=$monto_textualp[1];
        $monto_literalp=ucfirst(strtolower(trim($conversor->to_word($monto_numeralp))));


        // Obtiene datos de la empresa $_institution = palabra reservada
        $_institution = $db->from('sys_instituciones')->fetch_first();

        if($_POST['factura'] == 'si'){
            $titulo = ' F A C T U R A';
            $nro_factura = $venta['nro_factura'];
            //almacen
            $id_almacen = $venta['almacen_id'];

            // Obtiene la fecha de hoy
            $hoy = date('Y-m-d');

            // Obtiene la dosificacion del periodo actual
            $dosificacion = $db->from('inv_dosificaciones')->where('fecha_registro <=', $hoy)->where('fecha_limite >=', $hoy)->where('activo', 'S')->fetch_first();
            $id_v = $venta['id_egreso'];
            $venta2 = $db->query("SELECT * FROM inv_egresos WHERE id_egreso = '$id_v'")->fetch_first();
            if($dosificacion){
                if($venta2['nro_autorizacion'] != ''){
                    $nro_autorizacion = $dosificacion['nro_autorizacion'];
                    $codigo_control = $venta2['codigo_control'];
                    $fecha_limite = $dosificacion['fecha_limite'];
                    $factura_autenticidad = '"ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS. EL USO ILÍCITO DE ÉSTA SERÁ SANCIONADO DE ACUERDO A LEY"';
                    $factura_leyenda = 'Ley Nº 453: "' . $dosificacion['leyenda'] . '".';
                    $factura_qr = $_institution['nit'] . '|' . $nro_factura . '|' . $nro_autorizacion . '|' . date_decode($venta2['fecha_egreso'], 'd/m/Y') . '|' . $venta2['monto_total'] . '|0.00|' . $codigo_control . '|' . $venta2['nit_ci'] . '|0.00|0.00|0.00|0.00';
                    $nro_factura = 'Nro DE FACT.: ' . $venta2['nro_factura'];
                }else{
                    // Obtiene los datos para el codigo de control
                    $nro_autorizacion = $dosificacion['nro_autorizacion'];
                    $nro_factura = intval($dosificacion['nro_facturas']) + 1;
                    $nit_cliente = $venta['almacen_id'];
                    $fecha = date('Ymd');
                    $total = round($venta['monto_total'], 0);
                    $llave_dosificacion = base64_decode($dosificacion['llave_dosificacion']);

                    // Genera el codigo de control
                    $codigo_control = new ControlCode();
                    $codigo_control = $codigo_control->generate($nro_autorizacion, $nro_factura, $nit_cliente, $fecha, $total, $llave_dosificacion);
                    $datos_venta = array(
                        'tipo' => 'Venta',
                        'provisionado' => 'N',
                        'descripcion' => 'Venta de productos con preventa',
                        'nro_factura' => $nro_factura,
                        'nro_autorizacion' => $nro_autorizacion,
                        'codigo_control' => $codigo_control,
                        'fecha_limite' => $dosificacion['fecha_limite'],
                        'dosificacion_id' => $dosificacion['id_dosificacion']
                    );
                    $db->where('id_egreso', $venta['id_egreso'])->update('inv_egresos', $datos_venta);
                    $db->where('id_dosificacion', $dosificacion['id_dosificacion'])->update('inv_dosificaciones', array('nro_facturas' => $nro_factura));

                    // Gereramos el codigo de seguridad QR
                    $factura_qr = $_institution['nit'] . '|' . $nro_factura . '|' . $nro_autorizacion . '|' . date_decode($venta['fecha_egreso'], 'd/m/Y') . '|' . $venta['monto_total'] . '|0.00|' . $codigo_control . '|' . $venta['nit_ci'] . '|0.00|0.00|0.00|0.00';
                    $factura_autenticidad = '"ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS. EL USO ILÍCITO DE ÉSTA SERÁ SANCIONADO DE ACUERDO A LEY"';
                    $factura_leyenda = 'Ley Nº 453: "' . $dosificacion['leyenda'] . '".';
                    $fecha_limite = $dosificacion['fecha_limite'];
                    $nro_autorizacion = 'Nro AUTORIZACIÓN'.$nro_autorizacion;
                    $nro_factura = 'Nº DE FACTURA: ' . $venta['nro_factura'];
                }

            }else{
                $nro_factura = 'Nº DE VENTA: ' . $venta['nro_factura'];
                $codigo_control = '';
                $fecha_limite = '';
                $factura_autenticidad = '';
                $factura_leyenda = '';
                $factura_qr = '';
            }

        }else{
            $titulo = 'N O T A  D E  R E M I S I O N';
            $nro_factura = 'Nº DE VENTA: ' . $venta['nro_factura'];
            $codigo_control = '';
            $fecha_limite = '';
            $factura_autenticidad = '';
            $factura_leyenda = '';
            $factura_qr = '';
        }

        // Verifica la existencia del usuario
        if (true) {
            // Verifica si la dosificación existe
            if ($venta['id_egreso']) {
                // Arma los datos para la factura
                $datos = array(
                    'empresa_nombre' => $_institution['nombre'],
                    'empresa_sucursal' => $almacen['empresa_sucursal'],
                    'empresa_direccion' => $_institution['direccion'],
                    'empresa_telefono' => 'TELÉFONO: ' . $_institution['telefono'],
                    'empresa_ciudad' => 'La Paz - Bolivia',
                    'empresa_actividad' => $_institution['razon_social'],
                    'empresa_nit' => 'NIT: ' . $_institution['nit'],
                    'factura_titulo' => $titulo,
                    'factura_numero' => $nro_factura,
                    'factura_autorizacion' => $nro_autorizacion,
                    'factura_fecha' => 'FECHA: ' . date_decode($venta['fecha_egreso'], 'd/m/Y'),
                    'factura_hora' => 'HORA: ' . substr($venta['hora_egreso'], 0, 5),
                    'factura_codigo' => 'CÓDIGO DE CONTROL: '.$codigo_control,
                    'factura_limite' => 'FECHA LÍMITE DE EMISIÓN: '.$fecha_limite,
                    'factura_autenticidad' => $factura_autenticidad,
                    'factura_leyenda' => $factura_leyenda,
                    'cliente_nit' => 'NIT/CI: ' . $venta['nit_ci'],
                    'cliente_nombre' => 'SEÑOR(ES): ' . $nombre_factura,
                    'cliente_codigo' => 'COD.CLIENTE: ' . $id_cliente,
                    'venta_titulos' => array('COD.', ' CANT.', 'DESC.', 'P. UNIT.', 'SUBT.   '),
                    'venta_cantidades' => $cantidades1,
                    'venta_detalles' => $producto,
                    'venta_codes' => $codes,   //josema
                    'venta_precios' => $precios1,
                    'venta_descuentos' => $descuento1,
                    'venta_subtotales' => $subtotales,
                    'venta_total_titulo' => 'TOTAL FACTURADO:',
                    'venta_total_titulo1' => 'DESCUENTO:',
                    'venta_total_titulo2' => 'TOTAL:',
                    'venta_total_numeral' => $venta_t,
                    'venta_total_numeral1' => $descuento_t,
                    'venta_total_numeral2' => $venta_tt,
                    'venta_total_literal' => 'SON: ' . mb_strtoupper($monto_literal . ' BOLIVIANOS ' . $monto_decimal . '/100 CENT.' . $moneda, 'UTF-8'),

                    'venta_titulosm' => array('DETALLE', 'CANT.'),
                    'venta_pendientem' => $pendientem,
                    'venta_vendidom' => $vendidom,
                    'venta_entregadom' => $entregadom,
                    'venta_detallesm' => $materialm,
                    'venta_preciosm' => $preciosm,
                    'venta_subtotalesm' => $subtotalm,
                    'venta_total_titulom' => 'TOTAL BOLIVIANOS',
                    'venta_total_numeralm' => $totalm,
                    'venta_total_literalm' => 'SON: '.mb_strtoupper($monto_literalm.' '.$monto_decimalm.'/100 '.$moneda,'UTF-8'),

                    'venta_titulosp' => array('CUOTA', 'MONTO', 'T. PAGO'),
                    'nro_cuotap' => $nro_cuotap,
                    'montop' => $montop,
                    'tipo_pagop' => $tipo_pagop,
                    'venta_total_titulop' => 'TOTAL BOLIVIANOS',
                    'venta_total_numeralp' => $totalp,
                    'venta_total_literalp' => 'SON: '.mb_strtoupper($monto_literalp.' '.$monto_decimalp.'/100 '.$moneda,'UTF-8'),

                    'factura_qr' => $factura_qr,
                    'factura_vendedor' => 'VENDEDOR: ' . mb_strtoupper($empleado, 'UTF-8'),
                    'factura_agradecimiento' => 'GRACIAS POR SU COMPRA'
                );
                //print_r($datos);

                // Genera el zpl
                $zpl = generar_zpl($datos);

                // Instancia el objeto
                $respuesta = array(
                    'estado' => 's',
                    'zpl' => ($zpl)
                );

                // Devuelve los resultados
                echo json_encode($respuesta);
            } else {
                // Devuelve los resultados
                echo json_encode(array('estado' => 'No se encuentra la venta'));
            }
        } else {
            // Devuelve los resultados
            echo json_encode(array('estado' => 'n'));
        }
    } else {
        // Devuelve los resultados
        echo json_encode(array('estado' => 'n'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'n'));
}
