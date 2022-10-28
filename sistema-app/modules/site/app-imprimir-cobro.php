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

function printer_center($quantity = '', $detail = '', $price = '', $amount = '')
{
    $val1 = explode(" ", utf8_decode("Á É Í Ó Ú á é í ó ú ñ Ñ"));
    $val2 = array('\B5', '\90', '\D6', '\E3', '\E9', '\A0', '\82', '\A1', '\A2', '\A3', '\A4', '\A5');
    global $full_width;
    $column   = 10;
    $detail   = (($full_width - ($column * 3) - 2) > strlen($detail)) ? $detail : substr($detail, 0, ($full_width - ($column * 3) - 2));
    $quantity = ' ' . $quantity . ' ';
    $detail   = ' ' . str_replace($val1, $val2, utf8_decode($detail)) . ' ';
    $price    = ' ' . $price . ' ';
    $amount   = ' ' . $amount . ' ';
    $quantity = str_pad($quantity, $column + 5, ' ', STR_PAD_RIGHT);
    $detail   = str_pad($detail, $column + 1, ' ', STR_PAD_LEFT);
    $price    = str_pad($price, $column + 1, ' ', STR_PAD_LEFT);
    $amount   = str_pad($amount, $column , ' ', STR_PAD_LEFT);
    return $quantity . $detail . $price . $amount;
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
$anchoEtiqueta = 70;
/**
 * convirtiendo el ancho de la etiqueta en su equivalente en puntos
 */
$widthLabel    = intval(8 * $anchoEtiqueta) - 2;
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

    global $altoLetra, $jump, $anchoLetra, $widthLabel, $printWidth, $full_width, $column;

    /**
     * Definimos una lista con los carácteres especiales
     */
    $val1 = explode(" ", utf8_decode("Á É Í Ó Ú á é í ó ú ñ Ñ º ¡"));
    $val2 = array('\B5', '\90', '\D6', '\E3', '\E9', '\A0', '\82', '\A1', '\A2', '\A3', '\A4', '\A5', '\A7', '\AD');

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
    $factura_codigo         = utf8_decode($datos['factura_codigo']);
    $factura_limite         = utf8_decode($datos['factura_limite']);
    $factura_autenticidad   = utf8_decode($datos['factura_autenticidad']);
    $factura_leyenda        = utf8_decode($datos['factura_leyenda']);
    $cliente_nit            = utf8_decode($datos['cliente_nit']);
    $cliente_nombre         = utf8_decode($datos['cliente_nombre']);
    $venta_titulos          = $datos['venta_titulos'];
    $venta_cantidades       = $datos['venta_cantidades'];
    $venta_detalles         = $datos['venta_detalles'];
    $venta_precios          = $datos['venta_precios'];
    $venta_subtotales       = $datos['venta_subtotales'];
    $venta_total_titulo     = utf8_decode($datos['venta_total_titulo']);
    $venta_total_numeral    = $datos['venta_total_numeral'];
    $venta_total_literal    = utf8_decode($datos['venta_total_literal']);





    $venta_titulosv          = $datos['venta_titulosm'];
    $venta_pendientev        = $datos['venta_pendientem'];
    $venta_vendidov          = $datos['venta_vendidom'];
    $venta_entregadov        = $datos['venta_entregadom'];
    $venta_detallesv         = $datos['venta_detallesm'];
    $venta_preciosv          = $datos['venta_preciosm'];
    $venta_subtotalesv       = $datos['venta_subtotalesm'];
    $venta_total_titulov     = utf8_decode($datos['venta_total_titulom']);
    $venta_total_numeralv    = $datos['venta_total_numeralm'];
    $venta_total_literalv    = utf8_decode($datos['venta_total_literalm']);



    $venta_titulosp          = $datos['venta_titulosp'];
    $nro_cuotap              = $datos['nro_cuotap'];
    $montop                  = $datos['montop'];
    $tipo_pagop              = $datos['tipo_pagop'];
    $venta_total_titulop     = utf8_decode($datos['venta_total_titulop']);
    $venta_total_numeralp    = $datos['venta_total_numeralp'];
    $venta_total_literalp    = utf8_decode($datos['venta_total_literalp']);



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
    $empresa_nombre = str_replace($val1, $val2, printer_draw_text_custom($empresa_nombre));
    /**
     * Imprime la sucursal de la empresa
     */
    $empresa_sucursal  = str_replace($val1, $val2, printer_draw_text_custom($empresa_sucursal));
    /**
     * Imprime la direccion de la empresa
     */
    $empresa_direccion = str_replace($val1, $val2, printer_draw_text_custom($empresa_direccion));
    /**
     * Imprime el telefono de la empresa
     */
    $empresa_telefono  = str_replace($val1, $val2, printer_draw_text_custom($empresa_telefono));
    /**
     * Imprime la ciudad de funcionamiento de la empresa
     */
    $empresa_ciudad    = str_replace($val1, $val2, printer_draw_text_custom($empresa_ciudad));

    ln();
    /**
     * Imprime el titulo de la factura
     */
    $factura_titulo       = str_replace($val1, $val2, printer_draw_text_custom($factura_titulo));

    
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
    $fecha_hora           = printer_justify($factura_fecha, $factura_hora);
    /**
     * Imprime el nit del cliente
     */
    $cliente_nit          = str_replace($val1, $val2, printer_draw_text_custom($cliente_nit, STR_PAD_RIGHT));
    /**
     * Imprime el nombre del cliente
     */
    $cliente_nombre       = str_replace($val1, $val2, printer_draw_text_custom($cliente_nombre, STR_PAD_RIGHT)); //printer_draw_text_custom(str_replace($val1, $val2, $cliente_nombre),STR_PAD_RIGHT);











    $linea_1top = $jump + intval(($altoLetra / 2));
    /**
     * Dibuja una linea
     */
    $linea_cab  = printer_draw_line_custom();
    $linea_1 = ln();
    /**
     * Imprime los titulos de la venta
     */
    $res = printer_center(
            isset($venta_titulos[0]) ? $venta_titulos[0] : '',
            isset($venta_titulos[1]) ? $venta_titulos[1] : '',
            isset($venta_titulos[2]) ? $venta_titulos[2] : '',
            isset($venta_titulos[3]) ? $venta_titulos[3] : ''
        );
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

    $spaceH = $spaceH + ($altoLetra * 1 + $padding);
    /**
     * Imprime las cantidades, los detalles, los precios y los subtotales de la venta
     */
    foreach ($venta_cantidades as $key => $cantidad) {
        $lineat = ln();
        $detalle  = (isset($venta_detalles[$key])) ? $venta_detalles[$key] : '';
        $precio   = (isset($venta_precios[$key])) ? $venta_precios[$key] : '';
        $subtotal = (isset($venta_subtotales[$key])) ? $venta_subtotales[$key] : '';
        $res = printer_center($cantidad, $detalle, $precio, $subtotal);
        $tabla .= "^FT0,$lineat^AcN,$altoLetra,$anchoLetra^FH\^FD$res^FS";
    }
    /**
     * Dibuja una linea
     */
    $linea_cab3          = printer_draw_line_custom();
    /**
     * Imprime el total de la venta
     */
    $pie                 = printer_justify(' ' . $venta_total_titulo, $venta_total_numeral . ' ');
    $temC                = $jump;
    /**
     * Dibuja una linea
     */
    $linea_cab4          = printer_draw_line_custom();
    $temLR               = $jump;
    /**
     * Imprime el monto total en literal
     */
    $venta_total_literal = str_replace($val1, $val2, printer_draw_text_custom($venta_total_literal, STR_PAD_RIGHT));
    /**
     * Dibuja una linea
     */
    $linea_cab5          = printer_draw_line_custom();

    /**
     * Imprime el codigo de control
     */
    $factura_codigo      = str_replace($val1, $val2, printer_draw_text_custom($factura_codigo, STR_PAD_RIGHT));
    /**
     * Imprime la fecha limite de emision
     */
    $factura_limite        = str_replace($val1, $val2, printer_draw_text_custom($factura_limite, STR_PAD_RIGHT));

    $temC   = $temC - intval(($altoLetra / 2));
    $lineat = $temLR + intval(($altoLetra / 2));
    /**
     * Definimos la posicion en que se imprimira el monto total de la venta
     */
    $pie = "^FT0,$lineat^AcN,$altoLetra,$anchoLetra^FH\^FD$pie^FS";
    /**
     * Imprime el nombre de la empresa
     */
    $alv = $lineat - $linea_1 + ($altoLetra / 2);
    $alvC = $temC - $linea_1 + ($altoLetra / 2);

    $verticalL = "^FO0,$linea_1top^GB1,$alv,3^FS";
    $verticalR = "^FO$widthLabel,$linea_1top^GB1,$alv,3^FS";

    //$p3 = $p2+9*($anchoLetra+2);

    $p3 = $printWidth - 120;
    $p2 = $printWidth - 240;
    $p1 = 180;

    /**
     *  Dibuja las lineas verticales
     */
    $verticalC1 = "^FO$p1,$linea_1top^GB1,$alvC,3^FS";
    $verticalC2 = "^FO$p2,$linea_1top^GB1,$alvC,3^FS";
    $verticalC3 = "^FO$p3,$linea_1top^GB1,$alvC,3^FS";








    if($venta_pendientev!=false):
        $ventas_materiales       = str_replace($val1, $val2,printer_draw_text_custom('MATERIALES',STR_PAD_RIGHT));
        $linea_1top = $jump + intval(($altoLetra / 2));
        /**
         * Dibuja una linea
         */
        $linea_cabv  = printer_draw_line_custom();
        $linea_1 = ln();
        /**
         * Imprime los titulos de la venta
         */
        $resv = printer_center2(
                isset($venta_titulosv[0]) ? $venta_titulosv[0] : '',
                isset($venta_titulosv[1]) ? $venta_titulosv[1] : ''
            );
        //print_r($resv);
        /**
         * Definime la posicion de los titulos de la venta
         */
        $cabecerav   = "^FT0,$linea_1^AcN,$altoLetra,$anchoLetra^FH\^FD$resv^FS";
        $spaceH     = $linea_1;
        /**
         * Dibuja una linea
         */
        $linea_cab2v = printer_draw_line_custom();
    
        $padding = 2;
        $tablav = "";
    
        $spaceH = $spaceH + ($altoLetra * 1 + $padding);
        /**
         * Imprime las cantidades, los detalles, los precios y los subtotales de la venta
         */
        foreach ($venta_pendientev as $key => $cantidad) {
            $lineat = ln();
            $detallev  = (isset($venta_detallesv[$key])) ? $venta_detallesv[$key] : '';
            $preciosv  = (isset($venta_preciosv[$key])) ? $venta_preciosv[$key] : '';
            $venta_pendientev   = (isset($venta_pendientev[$key])) ? $venta_pendientev[$key] : '';
            $venta_vendidov   = (isset($venta_vendidov[$key])) ? $venta_vendidov[$key] : '';
            $venta_entregadov   = (isset($venta_entregadov[$key])) ? $venta_entregadov[$key] : '';
            $subtotalv = (isset($venta_subtotalesv[$key])) ? $venta_subtotalesv[$key] : '';
            $resv = printer_center2($detallev,$preciosv);
            $tablav.= "^FT0,$lineat^AcN,$altoLetra,$anchoLetra^FH\^FD$resv^FS";
        }
        /**
         * Dibuja una linea
         */
        $linea_cab3v          = printer_draw_line_custom();
        /**
         * Imprime el total de la venta
         */
        $piev                 = printer_justify(' ' . $venta_total_titulov,$venta_total_numeralv . ' ');
        $temC                = $jump;
        /**
         * Dibuja una linea
         */
        
        $temLR               = $jump;
        
        /**
         * Dibuja una linea
         */
        $linea_cab5v          = printer_draw_line_custom();
    
        /**
         * Imprime el codigo de control
         */
        $factura_codigo      = str_replace($val1, $val2, printer_draw_text_custom($factura_codigo, STR_PAD_RIGHT));
        /**
         * Imprime la fecha limite de emision
         */
        $factura_limite        = str_replace($val1, $val2, printer_draw_text_custom($factura_limite, STR_PAD_RIGHT));
    
        $temC   = $temC - intval(($altoLetra / 2));
        $lineat = $temLR + intval(($altoLetra / 2));
        /**
         * Definimos la posicion en que se imprimira el monto total de la venta
         */
        $piev = "^FT0,$lineat^AcN,$altoLetra,$anchoLetra^FH\^FD$piev^FS";
        /**
         * Imprime el nombre de la empresa
         */
        $alv = $lineat - $linea_1 + ($altoLetra / 2);
        $alvC = $temC - $linea_1 + ($altoLetra / 2);
    
        $verticalLv = "^FO0,$linea_1top^GB1,100,3^FS";
        $verticalRv = "^FO$widthLabel,$linea_1top^GB1,100,3^FS";
    
        //$p3 = $p2+9*($anchoLetra+2);
        //Total 560
        $p1 = 460;
        $p2 = $printWidth - (100);
        
    
        /**
         *  Dibuja las lineas verticales
         */
        $verticalC1v = "^FO$p1,$linea_1top^GB1,$alvC,3^FS";
    
        /**
         * Definimos las dimensiones del codigo QR
         */

         $Salida1=$ventas_materiales.$cabecerav.$tablav . $linea_cabv . $linea_cab2v . $linea_cab3v . $verticalLv . $verticalRv . $verticalC1v   ;
    endif;





    if($nro_cuotap!=false):
        $pagosp       = str_replace($val1, $val2,printer_draw_text_custom('PAGOS',STR_PAD_RIGHT));
        $linea_1top = $jump + intval(($altoLetra / 2));
        /**
         * Dibuja una linea
         */
        $linea_cabp  = printer_draw_line_custom();
        $linea_1 = ln();
        /**
         * Imprime los titulos de la venta
         */
        $resp = printer_center3(
                isset($venta_titulosp[0]) ? $venta_titulosp[0] : '',
                isset($venta_titulosp[1]) ? $venta_titulosp[1] : '',
                isset($venta_titulosp[2]) ? $venta_titulosp[2] : ''
            );
        //print_r($resv);
        /**
         * Definime la posicion de los titulos de la venta
         */
        $cabecerap   = "^FT0,$linea_1^AcN,$altoLetra,$anchoLetra^FH\^FD$resp^FS";
        $spaceH     = $linea_1;
        /**
         * Dibuja una linea
         */
        $linea_cab2p = printer_draw_line_custom();
    
        $padding = 2;
        $tablap = "";
    
        $spaceH = $spaceH + ($altoLetra * 1 + $padding);
        /**
         * Imprime las cantidades, los detalles, los precios y los subtotales de la venta
         */
        foreach ($nro_cuotap as $key => $cantidad) {
            $lineat = ln();
            $nro_cuotapp   = (isset($nro_cuotap[$key])) ? $nro_cuotap[$key] : '';
            $montopp   = (isset($montop[$key])) ? $montop[$key] : '';
            $tipo_pagopp   = (isset($tipo_pagop[$key])) ? $tipo_pagop[$key] : '';
            $respp = printer_center3($nro_cuotapp,$montopp,$tipo_pagopp);
            $tablap.= "^FT0,$lineat^AcN,$altoLetra,$anchoLetra^FH\^FD$respp^FS";
        }
    
        $linea_cab3p          = printer_draw_line_custom();
    
        $piep                 = printer_justify(' ' . $venta_total_titulop,$venta_total_numeralp . ' ');
        $temC                = $jump;
    
        $linea_cab4p         = printer_draw_line_custom();
        $temLR               = $jump;
    
        $venta_total_literalp = str_replace($val1, $val2, printer_draw_text_custom($venta_total_literalp,STR_PAD_RIGHT));
    
        $linea_cab5p          = printer_draw_line_custom();
    
        $factura_codigo      = str_replace($val1, $val2, printer_draw_text_custom($factura_codigo, STR_PAD_RIGHT));
    
        $factura_limite        = str_replace($val1, $val2, printer_draw_text_custom($factura_limite, STR_PAD_RIGHT));
    
        $temC   = $temC - intval(($altoLetra / 2));
        $lineat = $temLR + intval(($altoLetra / 2));
    
        $piep = "^FT0,$lineat^AcN,$altoLetra,$anchoLetra^FH\^FD$piep^FS";
    
        $alv = $lineat - $linea_1 + ($altoLetra / 2);
        $alvC = $temC - $linea_1 + ($altoLetra / 2);
    
        $verticalLp = "^FO0,$linea_1top^GB1,$alv,3^FS";
        $verticalRp = "^FO$widthLabel,$linea_1top^GB1,$alv,3^FS";
    
        $p1 = 186;
        $p2 = 186*2;
    
        $verticalC1p = "^FO$p1,$linea_1top^GB1,$alvC,3^FS";
        $verticalC2p = "^FO$p2,$linea_1top^GB1,$alvC,3^FS";

        $Salida2=$pagosp.$cabecerap.$tablap . $linea_cabp . $linea_cab2p . $linea_cab3p . $piep . $linea_cab4p . $verticalLp . $verticalRp . $verticalC1p . $verticalC2p .  $venta_total_literalp . $linea_cab5p;
    endif;








    /**
     * Imprime la leyenda
     */
    $factura_leyenda      = str_replace($val1, $val2, printer_draw_text_custom($factura_leyenda));

    $linea3               = printer_draw_line_custom();
    $factura_vendedor       = str_replace($val1, $val2, printer_draw_text_custom($factura_vendedor));
    $factura_agradecimiento       = str_replace($val1, $val2, printer_draw_text_custom($factura_agradecimiento));
    ln();

    /**
     * Definimos la estructura que tendra la etiqueta en lenguaje zpl
     */

    $zpl = "^XA
	^PW$printWidth
	^LL$jump
	^LH0,0
    ^FS" . $imagen . $empresa_nombre . $empresa_sucursal . $empresa_direccion . $empresa_telefono . $empresa_ciudad . $factura_titulo . $factura_autorizacion . $linea2 . $empresa_actividad . $fecha_hora . $cliente_nit . $cliente_nombre .
    $cabecera . $tabla . $linea_cab . $linea_cab2 . $linea_cab3 . $pie . $linea_cab4 . $verticalL . $verticalR . $verticalC1 . $verticalC2 . $verticalC3 . $venta_total_literal . $linea_cab5 .

    $Salida1.$Salida2.

    $factura_agradecimiento . "^XZ";

    return $zpl;
}

// Define las cabeceras
header('Content-Type: application/json');

// Verifica la peticion post
if (is_post()) {
    // Verifica la existencia de datos
    if (isset($_POST['id_user']) && isset($_POST['id_pago_detalle'])) {
        // Importa la configuracion para el manejo de la base de datos
        require config . '/database.php';

        // Importa la libreria para el codigo de control
        require_once libraries . '/controlcode-class/ControlCode.php';

        // Importa la libreria para la conversion de numeros a letras
        require_once libraries . '/numbertoletter-class/NumberToLetterConverter.php';

        // Obtiene los datos de la venta
        $pago_id = $_POST['id_pago_detalle'];
        $pago = $db->select('*')->from('inv_pagos_detalles a')->join('inv_pagos b','b.id_pago = a.pago_id')->join('inv_egresos c','c.id_egreso = b.movimiento_id')->join('sys_empleados d','d.id_empleado = c.empleado_id')->where('a.id_pago_detalle',$pago_id)->fetch_first();
        $pendiente = $db->select('SUM(a.monto) AS pendiente')->from('inv_pagos_detalles a')->where('a.pago_id',$pago['pago_id'])->where('a.estado',0)->fetch_first();
        // Obtiene los datos
        $id_usuario = trim($_POST['id_user']);

        // Obtiene los usuarios que cumplen la condicion
        $usuario = $db->query("select id_user, id_empleado from sys_users LEFT JOIN sys_empleados ON persona_id = id_empleado where  id_user = '$id_usuario' and active = '1' limit 1")->fetch_first();
        $id_empleado = $usuario['id_empleado'];

        $fecha = date('Y-m-d');
        
        $cobro_total = $pago['monto'];
        $ventas_c = array();
        $totales_c = array();
        $pendientes_c = array();
        $cobros_c = array();
//        foreach ($cobro as $nro => $opcion) {
//            $cobro_total = $cobro_total + $opcion['monto_c'];
//            $pendiente = $db->select('sum(if(b.monto is null, 0,b.monto)) as pendiente')
//            ->from('inv_pagos a')->join('inv_pagos_detalles b','a.id_pago = b.pago_id')
//            ->where('a.movimiento_id',$opcion['id_egreso'])->where('b.estado',0)->fetch_first();
//            $cobro[$nro]['pendiente'] = $pendiente['pendiente'];
            array_push($ventas_c, $pago['fecha_egreso']);
            array_push($totales_c, number_format(($pago['monto_total']), 2, ',', ''));
            array_push($pendientes_c, number_format(($pendiente['pendiente']), 2, ',', ''));
            array_push($cobros_c, number_format(($pago['monto']), 2, ',', ''));
//        }
        
        $cobro_total = number_format(($cobro_total), 2, '.', '');
        
        // Obtiene los datos del monto total
        $conversor = new NumberToLetterConverter();
        $monto_textual = explode('.', $cobro_total);
        $monto_numeral = $monto_textual[0];
        $monto_decimal = $monto_textual[1];
        $monto_literal = ucfirst(strtolower(trim($conversor->to_word($monto_numeral))));

        

        // Obtiene datos de la empresa $_institution = palabra reservada
        $_institution = $db->from('sys_instituciones')->fetch_first();

        // Verifica la existencia del usuario
        if (true) {
            // Verifica si la dosificación existe
            if (true) {
                // Arma los datos para la factura
                $datos = array(
                    'empresa_nombre' => $_institution['nombre'],
                    'empresa_sucursal' => $almacen['empresa_sucursal'],
                    'empresa_direccion' => $_institution['direccion'],
                    'empresa_telefono' => 'TELÉFONO: ' . $_institution['telefono'],
                    'empresa_ciudad' => 'La Paz - El Alto',
                    'empresa_actividad' => $_institution['razon_social'],
                    'empresa_nit' => 'NIT: ' . $_institution['nit'],
                    'factura_titulo' => 'N O T A  D E  P A G O',
                    'factura_numero' => 'Nº DE PAGO: ',
                    'factura_autorizacion' => ' ',
                    'factura_fecha' => 'FECHA: ' . date_decode(date('Y-m-d'), 'd/m/Y'),
                    'factura_hora' => 'HORA: ' . substr(date('H:i:s'), 0, 5),
                    'factura_codigo' => 'CÓDIGO DE CONTROL: ',
                    'factura_limite' => 'FECHA LÍMITE DE EMISIÓN: ',
                    'factura_autenticidad' => '---',
                    'factura_leyenda' => '----',
                    'cliente_nit' => 'NIT/CI: ' . $pago['nit_ci'],
                    'cliente_nombre' => 'SEÑOR(ES): ' . $pago['nombre_cliente'],
                    'venta_titulos' => array('FECHA VENTA', 'TOTAL V', 'PEND.', 'COBRO'),
                    'venta_cantidades' => $ventas_c,
                    'venta_detalles' => $totales_c,
                    'venta_precios' => $pendientes_c,
                    'venta_subtotales' => $cobros_c,
                    'venta_total_titulo' => 'TOTAL BOLIVIANOS',
                    'venta_total_numeral' => $cobro_total,
                    'venta_total_literal' => 'SON: ' . mb_strtoupper($monto_literal . ' ' . $monto_decimal . '/100 ' . $moneda, 'UTF-8'),

                    'factura_vendedor' => 'COBRADOR: ' . mb_strtoupper($empleado, 'UTF-8'),
                    'factura_agradecimiento' => 'GRACIAS POR SU COMPRA'
                );
                //print_r($datos);

                // Genera el zpl
                $zpl = generar_zpl($datos);

                // Instancia el objeto
                $respuesta = array(
                    'estado' => 's',
                    'zpl' => $zpl
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
