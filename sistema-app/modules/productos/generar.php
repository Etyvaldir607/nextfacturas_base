<?php

// Obtiene el codigo
$codigo = (isset($params[0])) ? $params[0] : 0;
$nombre = '';
$cantidad = (isset($params[1])) ? $params[1] : 0;

// Obtiene los datos
$codigos = array($codigo);
$nombres = array($nombre);
$cantidades = array($cantidad);

if ($codigos && $nombres && $cantidades) {
	// Importa la libreria para generar el reporte
	require_once libraries . '/tcpdf/tcpdf.php';

	// Define tamanos y fuentes
	$font_name_main = 'roboto';
	$font_name_data = 'roboto';
	$font_size_main = 11;
	$font_size_data = 7;

	// Define longitudes
	define('margin_left', 0);
	define('margin_right', 0);
	define('margin_top', 0);
	define('margin_bottom', 0);

	// Instancia el documento pdf
	$pdf = new TCPDF('P', 'pt', 'LETTER', true, 'UTF-8', false);

	// Asigna la informacion al documento
	$pdf->SetCreator(name_autor);
	$pdf->SetAuthor(name_autor);
	$pdf->SetTitle($_institution['nombre']);
	$pdf->SetSubject($_institution['propietario']);
	$pdf->SetKeywords($_institution['sigla']);

	// Asigna margenes
	$pdf->SetMargins(margin_left, margin_top, margin_right, margin_bottom);

	// Elimina las cabeceras
	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);

	// Asigna la orientacion de la pagina
	$pdf->SetPageOrientation('P');

	// Adiciona la pagina
	$pdf->AddPage();

	// Establece la fuente del contenido
	$pdf->SetFont($font_name_data, '', $font_size_data);

	// Define el borde
	$border = 0;

	// Define las variables horizontales
	$hwidth = 612;
	$hlength = 3;
	$hposition = 0;
	$hmargin = 10;
	$hpadding = 10;
	$hspace = (($hwidth - ($hposition * 2) - ($hmargin * ($hlength + 1)) - ($hpadding * $hlength * 2)) / $hlength);

	// Define las variables verticales
	$vwidth = 734;
	$vlength = 9;
	$vposition = 40;
	$vmargin = 0;
	$vpadding = 0;
	$vspace = (($vwidth - ($vposition * 2) - ($vmargin * ($vlength + 1)) - ($vpadding * $vlength * 2)) / $vlength);
	$vspace = 71.5;

	// Define el estilo para el codigo de barras
	$style = array(
		'position' => 'S',
		'align' => 'C',
		'stretch' => false,
		'fitwidth' => false,
		'cellfitalign' => 'C',
		'border' => $border,
		'hpadding' => 20,
		'vpadding' => 20,
		'fgcolor' => array(0, 0, 0),
		'bgcolor' => false,
		'text' => false,
		'font' => 'roboto',
		'fontsize' => 10,
		'stretchtext' => 0
	);

	// Inicia las variables de recorrido
	$h = 0;
	$v = 0;
	$k = 0;

	// Recorre los codigos, nombres y cantidades
	foreach ($codigos as $p => $codigo) {
		// Obtiene el codigo, nombre y cantidad
		$codigo = $codigos[$p];
		$nombre = $nombres[$p];
		$cantidad = $cantidades[$p];

		// Inicia la variable incremental
		$n = 0;
		while ($n < $cantidad) {
			// Verifica las filas por pagina
			if ($v < $vlength) {
				// Verifica las columnas por fila
				if ($h < $hlength) {
					// Imprime la celda con el nombre recortado
					$pdf->SetFont($font_name_data, '', $font_size_data);
					$pdf->MultiCell($hspace, 10, upper(substr($nombre, 0, 70)), $border, 'C', 0, 0, $hposition + ($hpadding * $h) + ($hmargin * ($h + 1)) + ($hspace * $h) + ($hpadding * ($h + 1)), $vposition + ($vpadding * $v) + ($vmargin * ($v + 1)) + ($vspace * $v) + ($vpadding * ($v + 1)) + 5, true);

					// Imprime el codigo de barras
					$pdf->write1DBarcode($codigo, 'C128', $hposition + ($hpadding * $h) + ($hmargin * ($h + 1)) + ($hspace * $h) + ($hpadding * ($h + 1)), $vposition + ($vpadding * $v) + ($vmargin * ($v + 1)) + ($vspace * $v) + ($vpadding * ($v + 1)), $hspace, $vspace, 0, $style, 'N');

					// Imprime el valor textual del codigo de barras
					$pdf->SetFont($font_name_main, '', $font_size_main);
					$pdf->MultiCell($hspace, 10, $codigo, $border, 'C', 0, 0, $hposition + ($hpadding * $h) + ($hmargin * ($h + 1)) + ($hspace * $h) + ($hpadding * ($h + 1)), $vposition + ($vpadding * $v) + ($vmargin * ($v + 1)) + ($vspace * $v) + ($vpadding * ($v + 1)) + ($vspace - 15), true);

					// Imprime el valor secuencial del codigo de barras
					$pdf->SetFont($font_name_data, '', $font_size_data);
					$pdf->MultiCell($hspace, 10, $k + 1, $border, 'R', 0, 0, $hposition + ($hpadding * $h) + ($hmargin * ($h + 1)) + ($hspace * $h) + ($hpadding * ($h + 1)), $vposition + ($vpadding * $v) + ($vmargin * ($v + 1)) + ($vspace * $v) + ($vpadding * ($v + 1)) + ($vspace - 12), true);

					// Incrementa la variable para controlar la cantidad de columnas a imprimir
					$h = $h + 1;
				} else {
					// Reinicia la posicion de la columna
					$h = 0;

					// Incrementa la variable para controlar las filas
					$v = $v + 1;
					$n = $n - 1;
					$k = $k - 1;
				}
			} else {
				// Reinicia la posicion de la fila
				$v = 0;
				$n = $n - 1;
				$k = $k - 1;

				// Salta de pagina
				$pdf->AddPage();
			}

			// Incrementa las variables de recorrido
			$k = $k + 1;
			$n = $n + 1;
		}
	}

	// Genera el nombre del archivo
	$nombre = 'codigos_' . date('Y-m-d_H-i-s') . '.pdf';

	// Cierra y devuelve el fichero pdf
	$pdf->Output($nombre, 'I');
} else {
	// Error 400
	require_once bad_request();
	exit;
}

?>
