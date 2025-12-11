<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Permissions
    |--------------------------------------------------------------------------
    |
    | This file contains the list of available permissions for tenant users.
    | These permissions are used to control access to various features
    | within the tenant portal.
    |
    */

    'permissions' => [
        'view_dashboard' => [
            'label' => 'View Dashboard',
            'description' => 'Access the tenant dashboard',
        ],
        'manage_invoices' => [
            'label' => 'Manage Invoices',
            'description' => 'Create, edit, and delete invoices',
        ],
        'view_invoices' => [
            'label' => 'View Invoices',
            'description' => 'View invoices (read-only)',
        ],
        'manage_bills' => [
            'label' => 'Manage Bills',
            'description' => 'Create, edit, and delete bills from suppliers',
        ],
        'view_bills' => [
            'label' => 'View Bills',
            'description' => 'View bills (read-only)',
        ],
        'manage_payments' => [
            'label' => 'Manage Payments',
            'description' => 'Create, edit, and delete payments',
        ],
        'view_payments' => [
            'label' => 'View Payments',
            'description' => 'View payments (read-only)',
        ],
        'manage_contacts' => [
            'label' => 'Manage Contacts',
            'description' => 'Create, edit, and delete contacts',
        ],
        'view_contacts' => [
            'label' => 'View Contacts',
            'description' => 'View contacts (read-only)',
        ],
        'manage_products' => [
            'label' => 'Manage Products',
            'description' => 'Create, edit, and delete products',
        ],
        'view_products' => [
            'label' => 'View Products',
            'description' => 'View products (read-only)',
        ],
        'manage_chart_of_accounts' => [
            'label' => 'Manage Chart of Accounts',
            'description' => 'Create, edit, and delete chart of accounts',
        ],
        'view_chart_of_accounts' => [
            'label' => 'View Chart of Accounts',
            'description' => 'View chart of accounts (read-only)',
        ],
        'view_reports' => [
            'label' => 'View Reports',
            'description' => 'Access financial reports',
        ],
        'manage_users' => [
            'label' => 'Manage Users',
            'description' => 'Invite, edit, and manage tenant users',
        ],
        'manage_billing' => [
            'label' => 'Manage Billing',
            'description' => 'Access and manage billing & subscriptions',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Groups
    |--------------------------------------------------------------------------
    |
    | Group permissions for easier UI organization
    |
    */

    'groups' => [
        'general' => [
            'label' => 'General',
            'permissions' => ['view_dashboard'],
        ],
        'invoicing' => [
            'label' => 'Invoicing',
            'permissions' => ['manage_invoices', 'view_invoices', 'manage_bills', 'view_bills', 'manage_payments', 'view_payments'],
        ],
        'contacts' => [
            'label' => 'Contacts',
            'permissions' => ['manage_contacts', 'view_contacts'],
        ],
        'products' => [
            'label' => 'Products',
            'permissions' => ['manage_products', 'view_products'],
        ],
        'accounting' => [
            'label' => 'Accounting',
            'permissions' => ['manage_chart_of_accounts', 'view_chart_of_accounts', 'view_reports'],
        ],
        'administration' => [
            'label' => 'Administration',
            'permissions' => ['manage_users', 'manage_billing'],
        ],
    ],
];

