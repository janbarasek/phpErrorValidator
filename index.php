<?php
include 'validator.php';

/*
 * Baraja Validator
 *
 * @author Jan Barasek <jan@baraja.cz>
 *
 * Toto je ukázkový PHP script, který demonstruje chování Validátoru.
 */

set_time_limit(1);

// sleep(1);

// Takto je možné vyhodit vlastní chybovou hlášku:
// trigger_error('Moje vlastní chybovka.');

$j = 5;
for ($i = 0; $i <= 25; $i++) {
	echo $j + $i . '<br>' . "\n";
	if ($j > $i) {
		echo $j . ' je větší, než ' . $i . '!<br>' . "\n";
	}
}

// require 'neexistujici_soubor.php';

$chars = 6;

// výpočet
$a      = 4;
$result = $a / $char;





echo 'Pokud všechno funguje, tak uvidíte tento text.';