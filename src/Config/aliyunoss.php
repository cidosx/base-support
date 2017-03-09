<?php

return [
	'endpoint' => env('ALIYUN_OSS_ENDPOINT', 'config error'),
	'endpoint_internal' => env('ALIYUN_OSS_ENDPOINTINTERNAL', false) ?: env('ALIYUN_OSS_ENDPOINT', 'config error'),
	'accesskey_id' => env('ALIYUN_OSS_ACCESSKEY_ID', 'config error'),
	'accesskey_secret' => env('ALIYUN_OSS_ACCESSKEY_SECRET', 'config error'),
	'internal_upload' => env('ALIYUN_OSS_INTERNAL_UPLOAD', false),
	'use_secure_protocol' => env('ALIYUN_OSS_USE_SECURE_PROTOCOL', false),
];
