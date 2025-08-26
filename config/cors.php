<?php

return [
	'paths' => ['*'],

	'allowed_methods' => ['*'],

	// Exact origins (CI/prod or specific LAN IPs). Comma-separated in env.
	'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '')),

	// Allow localhost and 127.0.0.1 on any port during development
	'allowed_origins_patterns' => [
		'#^http://localhost(:\d+)?$#',
		'#^http://127\.0\.0\.1(:\d+)?$#',
	],

	'allowed_headers' => ['*'],

	'exposed_headers' => ['Authorization', 'XSRF-TOKEN'],

	'max_age' => 0,

	'supports_credentials' => true,
];


