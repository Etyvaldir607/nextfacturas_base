<?php

class AllegedRC4 {
	static function encryptMessageRC4($message, $key, $unscripted = false){
		$state = range(0, 255);
		$x = 0;
		$y = 0;
		$index1 = 0;
		$index2 = 0;
		$nmen = '';
		$messageEncryption = '';
		
		for ($i = 0; $i <= 255; $i++) {
			$index2 = (ord($key[$index1]) + $state[$i] + $index2) % 256;
			$aux = $state[$i];
			$state[$i] = $state[$index2];
			$state[$index2] = $aux;
			$index1 = ($index1 + 1) % strlen($key);
		}

		for ($i = 0; $i < strlen($message); $i++) {
			$x = ($x + 1) % 256;
			$y = ($state[$x] + $y) % 256;
			$aux = $state[$x];
			$state[$x] = $state[$y];
			$state[$y] = $aux;
			$nmen = (ord($message[$i])) ^ $state[($state[$x] + $state[$y]) % 256];
			$nmenHex = strtoupper(dechex($nmen));
			$messageEncryption = $messageEncryption . (($unscripted) ? '' : '-') . ((strlen($nmenHex) == 1) ? ('0' . $nmenHex) : $nmenHex);
		}
		return (($unscripted) ? $messageEncryption : substr($messageEncryption, 1, strlen($messageEncryption))); 
	}
}