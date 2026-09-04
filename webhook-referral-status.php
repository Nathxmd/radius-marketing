<?php

// Compatibility entry point. The canonical endpoint is /webhook/referral-status.
$_GET['route'] = 'webhook/referral-status';
require_once __DIR__ . '/index.php';
