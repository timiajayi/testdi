<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config/ldap.php';

function authenticate($username, $password) {
    // Local admin check
    if ($username === 'admin' && $password === 'password') {
        $_SESSION['user'] = $username;
        $_SESSION['authenticated'] = true;
        return true;
    }
    
    // LDAP authentication
    $ldap_conn = ldap_connect(LDAP_HOST, LDAP_PORT);
    
    if (!$ldap_conn) {
        die("Could not connect to LDAP server at " . LDAP_HOST);
    }
    
    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3); // Use LDAP v3 for Active Directory
    ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0); // Disable referrals

    // Bind with the Read-only Administrator DN to search for the user
    $ldap_bind = @ldap_bind($ldap_conn, "CN=SA-ferrum gate,OU=Service Accounts,OU=SBC.Management," . LDAP_DN, LDAP_BIND_PASS);

    if (!$ldap_bind) {
        ldap_unbind($ldap_conn);
        die("LDAP bind failed: " . ldap_error($ldap_conn));
    }

    // Search for the user by sAMAccountName (username)
    $search = ldap_search($ldap_conn, LDAP_USER_SEARCH_BASE, "(sAMAccountName=$username)");
    $entries = ldap_get_entries($ldap_conn, $search);

    if ($entries['count'] > 0) {
        // User found, attempt to bind with user credentials
        $user_dn = $entries[0]['dn'];
        $user_bind = @ldap_bind($ldap_conn, $user_dn, $password);

        if ($user_bind) {
            $_SESSION['user'] = $username;
            $_SESSION['authenticated'] = true;
            ldap_unbind($ldap_conn);
            return true;
        }
    }

    ldap_unbind($ldap_conn); // Unbind connection after attempt
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (authenticate($username, $password)) {
        header('Location: home.php');
        exit;
    } else {
        $error = "Invalid credentials";
    }
}
?>