<?php

return [
    // Public routes
    "login" => [
        "controller" => "Auth",
        "action" => "login",
        "auth" => false
    ],
    "do-login" => [
        "controller" => "Auth",
        "action" => "doLogin",
        "auth" => false
    ],
    "logout" => [
        "controller" => "Auth",
        "action" => "logout",
        "auth" => false
    ],
    
    // Protected routes
    "dashboard" => [
        "controller" => "Dashboard",
        "action" => "index"
    ],
    "branch" => [
        "controller" => "Branch",
        "action" => "index"
    ],
    "branch/create" => [
        "controller" => "Branch",
        "action" => "create"
    ],
    "branch/store" => [
        "controller" => "Branch",
        "action" => "store"
    ],
    "branch/edit/{id}" => [
        "controller" => "Branch",
        "action" => "edit"
    ],
    "branch/update/{id}" => [
        "controller" => "Branch",
        "action" => "update"
    ],
    "branch/notes/{id}" => [
        "controller" => "Branch",
        "action" => "updateNotes"
    ],
    "branch/delete/{id}" => [
        "controller" => "Branch",
        "action" => "delete",
        "role" => ROLE_ADMIN
    ],
    "branch/reprocess/{id}" => [
        "controller" => "Branch",
        "action" => "reprocess",
        "role" => ROLE_ADMIN
    ],
    "branch/{id}" => [
        "controller" => "Branch",
        "action" => "detail"
    ],
    "admin/users" => [
        "controller" => "Admin",
        "action" => "users",
        "role" => ROLE_ADMIN
    ],
    "admin/user/create" => [
        "controller" => "Admin",
        "action" => "create",
        "role" => ROLE_ADMIN
    ],
    "admin/user/store" => [
        "controller" => "Admin",
        "action" => "store",
        "role" => ROLE_ADMIN
    ],
    "referral" => ["controller" => "Referral", "action" => "index", "role" => ROLE_ADMIN],
    "referral/create" => ["controller" => "Referral", "action" => "create", "role" => ROLE_ADMIN],
    "referral/bulk" => ["controller" => "Referral", "action" => "bulkCreate", "role" => ROLE_ADMIN],
    "referral/bulk/store" => ["controller" => "Referral", "action" => "bulkStore", "role" => ROLE_ADMIN],
    "referral/store" => ["controller" => "Referral", "action" => "store", "role" => ROLE_ADMIN],
    "referral/edit/{id}" => ["controller" => "Referral", "action" => "edit", "role" => ROLE_ADMIN],
    "referral/update/{id}" => ["controller" => "Referral", "action" => "update", "role" => ROLE_ADMIN],
    "referral/regenerate/{id}" => ["controller" => "Referral", "action" => "regenerate", "role" => ROLE_ADMIN],
    "referral/{id}" => ["controller" => "Referral", "action" => "detail", "role" => ROLE_ADMIN],
    "referral/commission" => ["controller" => "Referral", "action" => "commission", "role" => ROLE_ADMIN],
    "referral/logs" => ["controller" => "Referral", "action" => "logs", "role" => ROLE_ADMIN],
    "referral/settings" => ["controller" => "Referral", "action" => "settings", "role" => ROLE_ADMIN],
    "referral/settings/save" => ["controller" => "Referral", "action" => "saveSettings", "role" => ROLE_ADMIN],
    "referral/resync" => ["controller" => "Referral", "action" => "resync", "role" => ROLE_ADMIN],
    "webhook/referral-status" => ["controller" => "Webhook", "action" => "referralStatus", "auth" => false],
];
