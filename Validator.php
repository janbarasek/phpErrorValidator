<?php
/*
 * Baraja Validator
 *
 * @author Jan Barasek <jan@baraja.cz>
 *
 * This is core file of Baraja Validator, you can use it like:
 * include 'Validator.php'; in all your scripts.
 */


Validator::$allVariables = get_defined_vars();

function myErrorHandler ($errno, $errstr, $errfile, $errline) {
	if (!(error_reporting() & $errno)) {
		if (!isset($_GET['_ignore_errors_'])) Validator::balancer(E_WARNING, 'Error report was forbidden', $errfile, 1);

		return;
	}

	if (!isset($_GET['_ignore_errors_'])) {
		Validator::balancer($errno, $errstr, $errfile, $errline);
	} else {
		error_reporting(0);
	}

	return TRUE;
}

function fatal_handler () {
	$error = error_get_last();
	// fatal error, E_ERROR === 1
	if ($error['type'] === E_ERROR) {
		echo '<div style="z-index: 100000000; position: absolute; left: 0; top: 0; width: 100%; height: 100%; background: white; border-bottom: 3px solid #F44336; padding: 64px 0;">';
			echo '<div style="max-width: 500px; margin: auto; padding: 8px; background: #F44336; color: white;">';
				echo '<center style="font-size: 18pt; margin-bottom: 16px;">Nastala fatální chyba.</center>';
				echo 'Typ chyby: '. $error['type'] .'<br>';
				echo 'Hlášení: '. $error['message'] .'<br>';
				echo 'Soubor: '. $error['file'] .'<br>';
				echo 'Řádek: '. $error['line'] .'<br>';
			echo '</div>';
		echo '</div>';
		trigger_error('Fatal');
	}
}

set_error_handler('myErrorHandler');
register_shutdown_function('fatal_handler');

/*
 * Database of handler messages
 */

class Translator
{

	public static  $languages   = 'CZ';
	private static $wasDeclared = FALSE;

	// Headers
	public static $header = [];

	// Texts
	public static $text = [];

	// Tips
	public static $tip = [];

	public static function declarator () {
		if (!self::$wasDeclared) {
			self::headerDeclarator();
			self::textDeclarator();
			self::tipDeclarator();
			self::$wasDeclared = TRUE;
		}

		return 0;
	}

	public static function headerDeclarator () {
		self::$header['example']                    = 'Example value';
		self::$header['error-report-was-forbidden'] = 'Hlášení chyb bylo potlačeno';
		self::$header['undefined-variable']         = 'Neznámá proměnná $';
		self::$header['array-to-string-conversion'] = 'S polem nelze pracovat jako s řetězcem';
		self::$header['failed-to-open-stream']      = 'Nepodařilo se načíst soubor';
		self::$header['undefined-offset']           = 'Odkaz na nedefinovaný index';
		self::$header['division-by-zero']           = 'Nulou nelze dělit';
	}

	public static function textDeclarator () {
		self::$text['example']                    = 'Example value';
		self::$text['error-report-was-forbidden'] = 'Váš script potlačuje zobrazování chyb, proto je není možné detekovat. Potlačení chyb se obvykle provádí funkcí error_reporting(...) nebo v nastavení serveru.';
		self::$text['undefined-variable']         = 'Tato proměnná nebyla definována. Zkontrolujte, zda není v názvu překlep. Existenci proměnné můžete také zkontrolovat funkcí isset().';
		self::$text['array-to-string-conversion'] = 'PHP rozlišuje pole a řetězce. Pole je datová struktura, která obsahuje mnoho různých dat, na která se odkazujeme za pomoci indexů.<br>Příklad:<br><code>$pole = [1, 2, 3];<br>echo $pole[0];&nbsp;//&nbsp;vypíše číslo 1<br>echo $pole[1];&nbsp;//&nbsp;vypíše číslo 2<br>echo $pole;&nbsp;&nbsp;&nbsp;&nbsp;//&nbsp;vyhodí chybu, protože není zadán index</code>';
		self::$text['failed-to-open-stream']      = 'Snažíte se načíst soubor, který pravděpodobně neexistuje. Zkontrolujte, zda v adrese není překlep a že máte přístupová práva pro čtení.';
		self::$text['undefined-offset']           = 'Snažíte se odkazovat na neexistující index. Existenci indexu můžete ověřit funkcí isset($pole[$index]).';
		self::$text['division-by-zero']           = 'Pokoušíte se dělit nulou, tato operace není definována na oboru reálných čísel.';
	}

	public static function tipDeclarator () {
		self::$tip['example']          = 'Example value';
		self::$tip['division-by-zero'] = 'Zkontrolujte všechny proměnné v okolí problémového řádku a zajistěte, aby jmenovatel nebyl nulový.';
		self::$tip['undefined-offset'] = 'Nevíte, jak volané pole vypadá? Snadno si můžete vypsat jeho strukturu zavoláním speciální metody: <code>Validator::dump($pole);</code>';
	}


}


/**
 * Class Validator
 */
class Validator
{

	// System info
	public static $version      = '1.0 ALPHA';
	public static $allVariables = [];

	// Validator run variables
	private static $errors, $variables, $specialFilter, $specialData;

	public static function contains ($string, $search) {
		if (strpos($string, $search) !== FALSE) return TRUE;

		return FALSE;
	}

	public static function terminator () {
		echo '<!-- SCRIPT WAS TERMINED BY BARAJA VALIDATOR | ' . date('U') . ' -->';
		exit(1);
	}

	public static function smartMessage ($message) {
		$messageIndex      = str_replace(' ', '-', strtolower($message));
		$undefinedVariable = explode('undefined-variable:-', $messageIndex);
		if (isset($undefinedVariable[1])) {
			return [
				'special' => 'checkVariables', 'specialData' => $undefinedVariable[1],
				'header' => Translator::$header['undefined-variable'] . $undefinedVariable[1],
				'text' => Translator::$text['undefined-variable'],
			];
		}

		$failedToOpenStream = explode(':-failed-to-open-stream', $messageIndex);
		if (isset($failedToOpenStream[0]) && @$failedToOpenStream[0] && isset($failedToOpenStream[1])) {
			return [
				'header' => Translator::$header['failed-to-open-stream'],
				'text' => Translator::$text['failed-to-open-stream'],
			];
		}

		$undefinedOffset = explode('undefined-offset:-', $messageIndex, 2);
		if (isset($undefinedOffset[1])) {
			return [
				'header' => Translator::$header['undefined-offset'] . ' [' . $undefinedOffset[1] . ']',
				'text' => Translator::$text['undefined-offset'], 'tip' => Translator::$tip['undefined-offset'],
			];
		}


		return FALSE;
	}

	public static function balancer ($errno, $errstr, $errfile, $errline) {
		Translator::declarator();
		if (!isset(self::$errors)) self::$errors = 0;
		self::$errors++;

		$messageIndex      = str_replace(' ', '-', strtolower($errstr));
		$message           = [];
		$message['header'] = (isset(Translator::$header[$messageIndex]) ? Translator::$header[$messageIndex] : NULL);
		$message['text']   = (isset(Translator::$text[$messageIndex]) ? Translator::$text[$messageIndex] : NULL);
		$message['tip']    = (isset(Translator::$tip[$messageIndex]) ? Translator::$tip[$messageIndex] : NULL);

		$smartMessage = self::smartMessage($errstr);
		if ($smartMessage) {
			$message = $smartMessage;
			if (isset($smartMessage['special'])) self::$specialFilter = $smartMessage['special'];
			if (isset($smartMessage['specialData'])) self::$specialData = $smartMessage['specialData'];
		}
		$message['original'] = $errstr;

		switch ($errno) {
			case E_USER_ERROR: // 256
				self::prerender($errno, 'Uživatelská chyba na řádku ' . $errline . ' | Kód chyby: ' . $errno, $message,
					$errline, $errfile
				);
				break;

			case E_USER_WARNING: // 512
				self::prerender($errno, 'Uživatelské varování na řádku ' . $errline . ' | Kód chyby: ' . $errno,
					$message, $errline, $errfile
				);
				break;

			case E_USER_NOTICE: // 1024
				self::prerender($errno, 'Uživatelská poznámka na řádku ' . $errline . ' | Kód chyby: ' . $errno,
					$message, $errline, $errfile
				);
				break;

			case E_WARNING: // 2
				self::prerender($errno, 'Varování na řádku ' . $errline . ' | Kód chyby: ' . $errno, $message, $errline,
					$errfile
				);
				break;

			case E_NOTICE: // 8
				self::prerender($errno, 'Poznámka na řádku ' . $errline . ' | Kód chyby: ' . $errno, $message, $errline,
					$errfile
				);
				break;

			default:
				self::prerender($errno, 'Potenciální problém na řádku ' . $errline . ' | Kód chyby: ' . $errno,
					$message, $errline, $errfile
				);
				break;
		}

	}

	private static function fileLoader ($url) {
		if (file_exists($url)) {
			return htmlspecialchars(file_get_contents($url));
		} else return FALSE;
	}

	private static function renderSource ($code, $errorLine = NULL, $smartLine = NULL, $smartString = NULL) {
		// first simple replace
		$code = str_replace([' ', "\t", '\'', '(', ')', '{', '}'], [
				'&nbsp;', '&nbsp;&nbsp;&nbsp;&nbsp;', '<span style="color:#4CAF50">\'</span>',
				'<span style="color:red">(</span>', '<span style="color:red">)</span>',
				'<span style="color:red">{</span>', '<span style="color:red">}</span>'
			], $code
		);

		// syntax highlighter
		$variablesBuffer       = [];
		$simpleVariablesBuffer = '';
		$dolar                 = FALSE;
		for ($i = 0; isset($code[$i]); $i++) {
			if ($code[$i] == '$') {
				$dolar                 = TRUE;
				$simpleVariablesBuffer = '$';
			} elseif ($dolar == TRUE) {
				if ($code[$i] == ' ' || $code[$i] == '.' || $code[$i] == '!' || $code[$i] == '=' || $code[$i] == '&' || $code[$i] == ';' || $code[$i] == '+' || $code[$i] == '-' || $code[$i] == '*' || $code[$i] == '/') {
					$dolar                 = FALSE;
					$variablesBuffer[]     = $simpleVariablesBuffer;
					$simpleVariablesBuffer = '';
				}
				if ($dolar) $simpleVariablesBuffer .= $code[$i];
			}
		}

		for ($i = 0; isset($variablesBuffer[$i]); $i++) {
			$code = str_replace($variablesBuffer[$i], '<span style="color:#3F51B5">' . $variablesBuffer[$i] . '</span>',
				$code
			);
		}
		self::$variables = $variablesBuffer;

		// parser lines
		$parserLine = explode("\n", $code);

		// special filters
		switch (self::$specialFilter) {
			case 'checkVariables':
				for ($i = 0; isset($variablesBuffer[$i]); $i++) {
					$var         = trim(str_replace('$', '', strip_tags($variablesBuffer[$i])));
					$levenshtein = levenshtein($var, self::$specialData);
					if ($levenshtein > 0 && $levenshtein < 3) {
						for ($j = 0; isset($parserLine[$j]); $j++) {
							$search = strip_tags($parserLine[$j]);
							if (self::contains($search, $var)) {
								$smartLine = $j + 1;
							}
						}
					}
				}
				break;
		}

		// render
		$buffer = '';
		for ($i = 0; isset($parserLine[$i]); $i++) {
			$line   = $i + 1;
			$style  = 'background: #eee;';
			$styled = FALSE;
			if ($line == $smartLine) {
				$style .= 'background: #03A9F4; color: white;';
				$styled = TRUE;
			}
			if ($line == $errorLine) {
				$style .= 'background: #F44336; color: white;';
				$styled = TRUE;
			}
			if ($line == $smartLine && $line == $errorLine) $style .= 'border-left: 6px solid #03A9F4; padding-left: 2px;';
			if (strlen($parserLine[$i]) < 2) $style .= 'background: #dedede;';
			if ($errorLine - $line <= 10 && $line - $errorLine <= ($errorLine < 8 ? 8
					: 4)
			) $buffer .= '<div class="line"' . ($style ? ' style="' . $style . '"' : '') . '>' . $line . ': ' . ($styled
					? strip_tags($parserLine[$i]) : $parserLine[$i]) . '</div>';
		}

		return $buffer;
	}

	private static function prerender ($code, $typeError, $text, $line, $file) {
		$configurator   = [];
		$configurator[] = 'Verze PHP|' . PHP_VERSION;
		$configurator[] = 'Operační systém|' . PHP_OS;
		$configurator[] = 'Aktuální čas|' . date('U') . ' | ' . date('d. m. Y / g:i:s');
		$configurator[] = 'Aktuální URL|' . $_SERVER['PHP_SELF'] . ' (<i>script: ' . $_SERVER["SCRIPT_NAME"] . '</i>)';
		$configurator[] = 'Aktuální adresář|' . $_SERVER['REQUEST_URI'];

		$code           = [];
		$code['file']   = $file;
		$code['source'] = self::renderSource(self::fileLoader($file), $line);

		self::template($typeError, $text, $configurator, $code);
	}

	private static function template ($header, $text, $configurator, $code = []) {
		if (self::$errors > 10) return 0;
		echo "\n\n\n\n\n\n\n\n\n\n\n\n" . '<!-- BARAJA VALIDATOR | VERSION: ' . self::$version . ' -->';
		echo '<meta name="robots" content="noindex, nofollow">';
		echo '<link href=\'https://fonts.googleapis.com/css?family=Open+Sans|Inconsolata\' rel=\'stylesheet\' type=\'text/css\'>';
		echo '<style>';
		echo '* { font-family: \'Open Sans\', sans-serif; }';
		echo 'code { font-family: \'Inconsolata\'; }';
		echo 'hr { border: 1px solid #EF5350; }';
		echo 'button { background: #2196F3; color: white; padding: .5em 1em; margin: 0 .2em; border: 0; border-radius: 3px; }';
		echo 'button:hover { background: #0D47A1; }';
		echo '.bios { z-index: 10000000; position: absolute; left: 0; top: 0; width: 100%; background: white; border-bottom: 3px solid #F44336; margin-bottom: 64px; }';
		echo '.error_header { background: #3F51B5; color: white; padding: 1em; font-size: 18pt; }';
		echo '.error_buttons { margin: .3em; }';
		echo '.error_text { margin: 2em 1em; }';
		echo '.error_text .header { margin-bottom: .5em; color: #F44336; font-size: 18pt; }';
		echo '.error_text .text { margin: .5em 0; padding: .5em; border-left: 3px solid red; }';
		echo '.error_text .tip { margin: .5em 0; padding: .5em; border-left: 3px solid blue; }';
		echo '.error_code { border: 1px solid #aaa; margin: 1em; padding: .5em; }';
		echo '.error_code .line { padding: 2px 8px; margin: 1px 0; font-size: 10pt; font-family: \'Inconsolata\'; }';
		echo '.error_code .line span { font-family: \'Inconsolata\'; }';
		echo '.error_configurator { margin: 1em; }';
		echo '.error_configurator table { border: 1px solid #2196F3; border-spacing: 0; }';
		echo '.error_configurator table tr th { background: #BBDEFB; padding: .3em .5em; text-align: right; }';
		echo '.error_configurator table tr td { padding: .3em .5em; }';
		echo '.about { text-align: right; margin: .3em .5em; }';
		echo '</style>';
		echo '<div class="bios" id="validatorCore">';
		echo '<div class="error_header">' . ($text['header'] ? $text['header']
				: '') . ($text['header'] && $text['original'] ? ' | ' : '') . ($text['original'] ? $text['original']
				: '') . ($text['header'] || $text['original'] ? '<br>' : '') . $header . '</div>';
		echo '<div class="error_buttons">';
		echo '<a href="https://www.google.com/search?q=' . urlencode('PHP error ' . $text['original']
			) . '"><button>Googlovat chybu</button></a>';
		echo '<a href="#" onClick="document.getElementById(\'validatorCore\').innerHTML=\'<div style=\\\'text-align:center;margin:16px;\\\'>Takto by se stránka vykreslila s vypnutým chybovým hlášením.</div>\';"><button>Ukázat výstup</button></a>';
		echo '<a href="?_ignore_errors_"><button>Ignorovat chyby</button></a>';
		echo '</div>';
		echo '<hr>';
		if ($code['file']) {
			echo '<div class="error_code">';
			echo '<p style="font-family: \'Courier New\', Courier, monospace;">' . $code['file'] . '</p>';
			echo($code['source'] ? str_replace("\n", '<br>', $code['source']) : 'Zdrojový kód se nepodařilo načíst.');
			echo '</div>';
			echo '<hr>';
		}
		if ($text['header'] || $text['text'] || $text['tip']) {
			echo '<div class="error_text">';
			if (@$text['header']) echo '<div class="header">' . $text['header'] . '</div>';
			if (@$text['text']) echo '<div class="text">' . $text['text'] . '</div>';
			if (@$text['tip']) echo '<div class="tip">' . $text['tip'] . '</div>';
			echo '</div>';
			echo '<hr>';
		}
		echo '<div class="error_configurator">';
		echo '<p>Konfigurace webového serveru:</p>';
		echo '<table>';
		for ($i = 0; isset($configurator[$i]); $i++) {
			$parserConfigurator = explode('|', $configurator[$i], 2);
			echo '<tr><th>' . $parserConfigurator[0] . '</th><td>' . $parserConfigurator[1] . '</td></tr>';
		}
		echo '</table>';
		if (isset(self::$variables[0])) {
			echo '<p>Seznam proměnných:</p>';
			echo '<table>';
			$printedVariables = [];
			sort(self::$variables);
			for ($i = 0; isset(self::$variables[$i]); $i++) {
				$var = trim(strip_tags(self::$variables[$i]));
				if (!isset($printedVariables[$var])) {
					echo '<tr><th>' . $var . '</th><td>?</td></tr>';
					$printedVariables[$var] = TRUE;
				}
			}
			echo '</table>';

			echo '<p>Podrobná konfigurace serverových proměnných:</p>';
			echo '<table>';
			foreach (self::$allVariables as $key => $value) {
				echo '<tr><th>' . $key . '</th><td>';
				if ($value) {
					echo '<table>';
					foreach ($value as $keyIn => $valueIn) {
						echo '<tr><th>' . $keyIn . '</th><td>';
						echo $valueIn;
						echo '</td></tr>';
					}
					echo '</table>';
				} else {
					echo '<i>Žádná data.</i>';
				}
				echo '</td></tr>';
			}
			echo '</table>';
		}
		echo '</div>';
		echo '<hr>';
		echo '<div class="about">Baraja Validator | Jan Barášek | Verze ' . self::$version . '</div>';
		echo '</div>';

		self::terminator();
	}

	public static function dump ($data, $text = '') {
		echo "\n\n\n\n\n\n\n\n\n\n\n\n" . '<!-- BARAJA VALIDATOR DUMPER | VERSION: ' . self::$version . ' -->';
		echo '<link href=\'https://fonts.googleapis.com/css?family=Open+Sans|Inconsolata\' rel=\'stylesheet\' type=\'text/css\'>';
		echo '<style>#ValidatorDumpText, #ValidatorDumpData { font-family: \'Open Sans\', sans-serif; }</style>';
		echo '<div style="z-index: 100000000; position: fixed; right: 0; bottom: 0; width: 400px; height: 400px; border: 3px solid #2196F3; border-radius: 8px; background: white;">';
		echo '<div id="ValidatorDumpText" style="background: #3F51B5; color: white; padding: 8px; border-radius: 4px;">' . ($text ? $text
				: 'Datová struktura') . '</div>';
		echo '<div id="ValidatorDumpData" style="height: 345px; padding: 8px; overflow: auto;"><pre style="margin-top:0;"><code>';
		echo self::dumper($data);
		echo '</code></pre></div>';
		echo '</div>';
		return TRUE;
	}

	public static function dumper ($data, $indent = 0) {
		$retval = '';
		$prefix = \str_repeat(' |  ', $indent);
		if (\is_numeric($data)) $retval .= "Number: $data"; elseif (\is_string($data)) $retval .= "String: '$data'";
		elseif (\is_null($data)) $retval .= "NULL";
		elseif ($data === TRUE) $retval .= "TRUE";
		elseif ($data === FALSE) $retval .= "FALSE";
		elseif (is_array($data)) {
			$retval .= "Array (" . count($data) . ')';
			$indent++;
			foreach ($data AS $key => $value) {
				$retval .= "\n$prefix [$key] = ";
				$retval .= self::dumper($value, $indent);
			}
		} elseif (is_object($data)) {
			$retval .= "Object (" . get_class($data) . ")";
			$indent++;
			foreach ($data AS $key => $value) {
				$retval .= "\n$prefix $key -> ";
				$retval .= self::dumper($value, $indent);
			}
		}

		return $retval;
	}

}