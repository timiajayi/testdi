<!DOCTYPE html>
<html>
<head>
    <title>Login - ID Card Generator</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
    <div class="form-container">
        <div class="company-header">
            <img src="{{ asset('templates/logo.png') }}" alt="Company Logo" class="company-logo">
            <h1>ID Card Generator</h1>
        </div>

        <div class="login-toggle">
            <button onclick="showLogin('ldap')" class="active">LDAP Login</button>
            <button onclick="showLogin('saml')">SAML SSO</button>
            <button onclick="showLogin('normal')">Standard Login</button>
        </div>

        <div class="login-options">
            <!-- LDAP Login Form -->
            <div id="ldap-login" class="login-form active">
                <form method="POST" action="{{ route('ldap.login') }}">
                    @csrf
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit">Login with LDAP</button>
                </form>
            </div>

            <!-- SAML Login Button -->
            <div id="saml-login" class="login-form">
                <a href="{{ route('saml.login') }}" class="saml-button">
                    Login with SAML SSO
                </a>
            </div>

            <!-- Standard Login Form -->
            <div id="normal-login" class="login-form">
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit">Login</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showLogin(type) {
            // Hide all forms
            document.querySelectorAll('.login-form').forEach(form => {
                form.classList.remove('active');
            });
            
            // Show selected form
            document.getElementById(type + '-login').classList.add('active');
            
            // Update button states
            document.querySelectorAll('.login-toggle button').forEach(button => {
                button.classList.remove('active');
            });
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
