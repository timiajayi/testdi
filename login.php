<?php
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
    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    
    $ldap_bind = @ldap_bind($ldap_conn, "uid=$username," . LDAP_DN, $password);
    
    if ($ldap_bind) {
        $_SESSION['user'] = $username;
        $_SESSION['authenticated'] = true;
        return true;
    }
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
