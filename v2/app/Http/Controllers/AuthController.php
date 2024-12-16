<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        // Try local database authentication first
        if (Auth::attempt(['email' => $credentials['username'], 'password' => $credentials['password']])) {
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        // LDAP Authentication
        try {
            $ldap_conn = ldap_connect(
                Config::get('ldap.host'),
                Config::get('ldap.port')
            );

            ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

            if (!$ldap_conn) {
                throw new \Exception('Could not connect to LDAP server');
            }

            $ldap_bind = @ldap_bind(
                $ldap_conn, 
                Config::get('ldap.username'),
                Config::get('ldap.password')
            );

            if (!$ldap_bind) {
                throw new \Exception('Invalid LDAP credentials');
            }

            $search = ldap_search(
                $ldap_conn, 
                Config::get('ldap.user_search_base'), 
                "(sAMAccountName={$credentials['username']})"
            );
            
            $entries = ldap_get_entries($ldap_conn, $search);

            if ($entries['count'] > 0) {
                $user_dn = $entries[0]['dn'];
                $user_bind = @ldap_bind($ldap_conn, $user_dn, $credentials['password']);

                if ($user_bind) {
                    // Create or update local user record
                    $user = User::updateOrCreate(
                        ['username' => $credentials['username']],
                        [
                            'name' => $entries[0]['displayname'][0] ?? $credentials['username'],
                            'email' => $entries[0]['mail'][0] ?? '',
                            'password' => Hash::make($credentials['password'])
                        ]
                    );

                    Auth::login($user);
                    $request->session()->regenerate();
                    return redirect()->intended('/');
                }
            }
        } catch (\Exception $e) {
            // Log LDAP error but don't expose it
            \Log::error('LDAP Error: ' . $e->getMessage());
        }

        // SAML Authentication
        if ($this->attemptSamlAuth($request)) {
            return redirect()->intended('/');
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ]);
    }

    protected function attemptSamlAuth(Request $request)
    {
        try {
            $auth = new \OneLogin\Saml2\Auth([
                'strict' => true,
                'debug' => true,
                'sp' => [
                    'entityId' => Config::get('saml2.sp_entity_id'),
                    'assertionConsumerService' => [
                        'url' => Config::get('saml2.sp_acs_url'),
                    ],
                    'singleLogoutService' => [
                        'url' => Config::get('saml2.sp_sls_url'),
                    ],
                ],
                'idp' => [
                    'entityId' => Config::get('saml2.idp_entity_id'),
                    'singleSignOnService' => [
                        'url' => Config::get('saml2.idp_sso_url'),
                    ],
                    'singleLogoutService' => [
                        'url' => Config::get('saml2.idp_sls_url'),
                    ],
                    'x509cert' => Config::get('saml2.idp_x509'),
                ],
            ]);

            if (!$request->has('SAMLResponse')) {
                $auth->login();
            }

            $auth->processResponse();
            
            if ($auth->isAuthenticated()) {
                $attributes = $auth->getAttributes();
                
                $user = User::updateOrCreate(
                    ['email' => $attributes['email'][0]],
                    [
                        'name' => $attributes['name'][0],
                        'username' => $attributes['username'][0],
                        'password' => Hash::make(str_random(16))
                    ]
                );

                Auth::login($user);
                return true;
            }
        } catch (\Exception $e) {
            \Log::error('SAML Error: ' . $e->getMessage());
        }

        return false;
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    public function samlLogout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Redirect to SAML IdP logout
        return redirect(Config::get('saml2.idp_sls_url'));
    }
}
