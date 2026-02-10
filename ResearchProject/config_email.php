<?php
// Email Configuration - load from environment (safer than hardcoding)
// This file supports a simple .env file in the project root; environment
// variables take precedence if already set.

$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
	$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '' || $line[0] === '#') {
			continue;
		}
		if (strpos($line, '=') === false) {
			continue;
		}
		list($name, $value) = explode('=', $line, 2);
		$name = trim($name);
		$value = trim($value);
		if ($name === '') {
			continue;
		}
		// Do not overwrite existing environment values
		if (getenv($name) === false) {
			putenv("$name=$value");
			$_ENV[$name] = $value;
			$_SERVER[$name] = $value;
		}
	}
}

define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.gmail.com');
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
define('MAIL_PORT', (int)(getenv('MAIL_PORT') ?: 587));
define('MAIL_SECURE', getenv('MAIL_SECURE') ?: 'tls');
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: '');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'AUJRC Library System');

// IMPORTANT: If credentials were previously committed to git, rotate those
// credentials now and remove them from the repository history (see README
// or project notes). Do NOT add real secrets into source files.
?>
