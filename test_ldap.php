<?php

$ldap_conn = ldap_connect('10.10.15.50', 389);
ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

$username = "oluwatimilehin.ajayi";
$password = "";

// Test different bind formats
$formats = [
    $username,                              // Format 1: just username
    "sevenup\\$username",                   // Format 2: domain\username
    "$username@sevenup.org",                // Format 3: username@domain
    "CN=$username,DC=sevenup,DC=org"        // Format 4: full DN
];

foreach ($formats as $format) {
    echo "Testing bind with: $format\n";
    $bind = @ldap_bind($ldap_conn, $format, $password);
    echo "Result: " . ($bind ? "SUCCESS" : "FAILED") . "\n\n";
}
