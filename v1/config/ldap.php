<?php
define('LDAP_HOST', 'ldap://10.10.15.50');
define('LDAP_PORT', 389);
define('LDAP_DN', 'DC=sevenup,DC=org'); // Search base DN
define('LDAP_USER_SEARCH_BASE', 'OU=Service Accounts,OU=SBC.Management,DC=sevenup,DC=org'); // Search path for users
define('LDAP_BIND_DN', 'CN=SA-ferrum gate,OU=Service Accounts,OU=SBC.Management,DC=sevenup,DC=org'); // Admin bind DN
define('LDAP_BIND_PASS', ''); // Admin bind password
?>
