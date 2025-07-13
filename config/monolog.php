<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;

return [
	'channels' => [
		'default' => [
			'handlers' => ['stream', 'error_log'],
			'processors' => [],
		],
		'app' => [
			'handlers' => ['stream'],
			'processors' => [],
		],
		'database' => [
			'handlers' => ['stream'],
			'processors' => [],
		],
		'api' => [
			'handlers' => ['stream', 'error_log'],
			'processors' => [],
		],
	],
	'handlers' => [
		'stream' => [
			'class' => StreamHandler::class,
			'constructor' => [
				'stream' => WP_CONTENT_DIR . '/debug.log',
				'level' => Level::Debug,
			],
			'formatter' => 'line',
		],
		'error_log' => [
			'class' => ErrorLogHandler::class,
			'constructor' => [
				'messageType' => ErrorLogHandler::OPERATING_SYSTEM,
				'level' => Level::Error,
			],
			'formatter' => 'line',
		],
	],
	'formatters' => [
		'line' => [
			'class' => LineFormatter::class,
			'constructor' => [
				'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
				'dateFormat' => 'Y-m-d H:i:s',
			],
		],
	],
];