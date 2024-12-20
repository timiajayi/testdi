<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
                env('LDAP_HOST'),
                env('LDAP_PORT')
            );
    
            ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
    
            $ldap_bind = @ldap_bind(
                $ldap_conn, 
                env('LDAP_USERNAME'),
                env('LDAP_PASSWORD')
            );
    
            if (!$ldap_bind) {
                throw new \Exception('Invalid LDAP credentials');
            }
    
            $search = ldap_search(
                $ldap_conn, 
                env('LDAP_USER_SEARCH_BASE'), 
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
            Log::error('LDAP Error: ' . $e->getMessage());
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
                        'password' => Hash::make(Str::random(16))
                    ]
                );

                Auth::login($user);
                return true;
            }
        } catch (\Exception $e) {
            Log::error('SAML Error: ' . $e->getMessage());
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

    public function ldapLogin(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);
    
        Log::info('LDAP Login: Starting authentication process for user ' . $credentials['username']);
    
        try {
            // Connection attempt
            Log::info('LDAP: Attempting connection to ' . env('LDAP_HOST') . ':' . env('LDAP_PORT'));
            $ldap_conn = ldap_connect(env('LDAP_HOST'), env('LDAP_PORT'));
            
            if ($ldap_conn) {
                Log::info('LDAP: Connection established successfully with ' . env('LDAP_HOST'));
            } else {
                Log::error('LDAP: Failed to establish connection with ' . env('LDAP_HOST'));
                return back()->withErrors(['username' => 'Connection failed']);
            }
    
            // Setting options with verification
            Log::info('LDAP: Setting connection options');
            $protocol = ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            $referrals = ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
            $timeout = ldap_set_option($ldap_conn, LDAP_OPT_NETWORK_TIMEOUT, 10);
            
            Log::info('LDAP: Options set - Protocol v3: ' . ($protocol ? 'Yes' : 'No') . 
                     ', Referrals: ' . ($referrals ? 'Yes' : 'No') . 
                     ', Timeout: ' . ($timeout ? 'Yes' : 'No'));
    
            // Service account bind with detailed logging
            Log::info('LDAP: Attempting service account bind with DN: ' . env('LDAP_USERNAME'));
            $serviceBind = @ldap_bind($ldap_conn, env('LDAP_USERNAME'), env('LDAP_PASSWORD'));
            
            Log::info('LDAP Config:', [
                'host' => env('LDAP_HOST'),
                'port' => env('LDAP_PORT'),
                'username' => env('LDAP_USERNAME'),
                'base' => env('LDAP_USER_SEARCH_BASE')
            ]);
            
            if ($serviceBind) {
                Log::info('LDAP: Service account bind successful for ' . env('LDAP_USERNAME'));
                
                // User search with detailed logging
                $searchFilter = "(sAMAccountName={$credentials['username']})";
                Log::info('LDAP: Searching for user with filter: ' . $searchFilter . 
                         ' in base: ' . env('LDAP_USER_SEARCH_BASE'));
                
                $search = ldap_search($ldap_conn, env('LDAP_USER_SEARCH_BASE'), $searchFilter);
                $entries = ldap_get_entries($ldap_conn, $search);
                
                Log::info('LDAP: Search completed. Found ' . $entries['count'] . ' entries');
                
                if ($entries['count'] > 0) {
                    $userDn = $entries[0]['dn'];
                    Log::info('LDAP: Found user DN: ' . $userDn);
                    Log::info('LDAP: Attempting user bind for: ' . $userDn);
                    
                    $userBind = @ldap_bind($ldap_conn, $userDn, $credentials['password']);
                    
                    if ($userBind) {
                        Log::info('LDAP: User bind successful for DN: ' . $userDn);
                        Log::info('LDAP: User attributes found - ' . 
                                 'DisplayName: ' . ($entries[0]['displayname'][0] ?? 'Not set') . 
                                 ', Email: ' . ($entries[0]['mail'][0] ?? 'Not set'));
    
                                 $user = User::updateOrCreate(
                                    ['email' => $entries[0]['mail'][0] ?? ''],
                                    [
                                        'name' => $entries[0]['displayname'][0] ?? $credentials['username'],
                                        'username' => $credentials['username'],
                                        'password' => Hash::make($credentials['password'])
                                    ]
                                );                                
    
                        Auth::login($user);
                        Log::info('LDAP: Authentication successful for ' . $credentials['username']);
                        return redirect()->route('home');
                    } else {
                        Log::error('LDAP: User bind failed for DN: ' . $userDn . 
                                 ' - Error: ' . ldap_error($ldap_conn));
                    }
                } else {
                    Log::warning('LDAP: No user found matching username: ' . $credentials['username']);
                }
            } else {
                Log::error('LDAP: Service account bind failed - Error: ' . ldap_error($ldap_conn));
            }
        } catch (\Exception $e) {
            Log::error('LDAP Error: ' . $e->getMessage());
            Log::error('LDAP Error Trace: ' . $e->getTraceAsString());
            Log::error('LDAP Last Error: ' . ldap_error($ldap_conn));
        }
    
        Log::warning('LDAP: Authentication process completed with failure for user ' . $credentials['username']);
        return back()->withErrors(['username' => 'Invalid credentials']);
    }
    
    
    public function standardLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
    
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('home'); // Change this line to use named route
        }
    
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }    
    
}
