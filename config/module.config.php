<?php

return [
	'messaging' => [
		'host' => 'email-smtp.eu-west-1.amazonaws.com',
		'smtp_host' => '',
		'port' => 587,
		'ssl' => 'tls',
		'username' => '',
		'password' => '',
		'from_name' => '',
		'from_email' => '',
    ],
    'service_manager' => [
        'invokables' => [
            'mail' => 'Messaging\Service\Mail',
        ],
    ],
];
