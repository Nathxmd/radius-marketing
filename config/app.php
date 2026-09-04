<?php

// Application config
date_default_timezone_set(getenv("TIMEZONE") ?: "Asia/Jakarta");

define("APP_NAME", getenv("APP_NAME") ?: "Radius Sistem");
define("APP_URL", getenv("APP_URL") ?: "http://localhost/radius-sistem");
define("NOMINATIM_USER_AGENT", getenv("NOMINATIM_USER_AGENT") ?: "RadiusSistem/1.0 (contact@example.com)");
// Path ke CA bundle untuk curl (kosong = pakai default PHP). Berguna di env tanpa cacert terpasang.
define("CURL_CAINFO", getenv("CURL_CAINFO") ?: "");
define("EXTERNAL_APP_PUSH_URL", getenv("EXTERNAL_APP_PUSH_URL") ?: "");
define("EXTERNAL_APP_API_KEY", getenv("EXTERNAL_APP_API_KEY") ?: "");
define("REFERRAL_SHARED_SECRET", getenv("REFERRAL_SHARED_SECRET") ?: "");

// Role constants
define("ROLE_ADMIN", "admin");
define("ROLE_MARKETING", "marketing");
