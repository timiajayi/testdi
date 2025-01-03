<?php

return [
    'host' => env('LDAP_HOST', '10.10.15.50'),
    'username' => env('LDAP_USERNAME', "CN=SA-ferrum gate,OU=Service Accounts,OU=SBC.Management,DC=sevenup,DC=org"),
    'password' => env('LDAP_PASSWORD', ""),
    'port' => env('LDAP_PORT', 389),
    'base_dn' => env('LDAP_BASE_DN', "DC=sevenup,DC=org"),
    'user_search_base' => env('LDAP_USER_SEARCH_BASE', "DC=sevenup,DC=org"),
    'groups' => [
        'admin' => 'sevenup-zymera-admin',
        'staff' => 'sevenup-zymera-staff',
        'user' => 'sevenup-zymera-user'
    ]
];
