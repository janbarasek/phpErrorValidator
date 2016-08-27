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

$arr = [1, 2, 3, 4];

$obj     = new stdClass;
$obj->a  = 123;
$obj->pl = 44;
$obj->l  = [31, 32];

$options = [
	'Orchestra' => [1 => 'Strings', 8 => 'Brass', 9 => $obj, 3 => 'Woodwind', 16 => 'Percussion'], 2 => 'Car',
	4 => 'Bus', 'TV' => [21 => 'Only Fools', 215 => 'Brass Eye', 23 => 'Vic Bob', 44 => NULL, 89 => FALSE]
];

Validator::dump($options);

// echo $arr[8];

// require 'neexistujici_soubor.php';

$chars = 6;

// výpočet
$a      = 4;
$result = $a / $char;


echo 'Pokud všechno funguje, tak uvidíte tento text.';