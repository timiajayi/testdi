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

    // public function ldapLogin(Request $request)
    // {
    //     $credentials = $request->validate([
    //         'username' => 'required',
    //         'password' => 'required'
    //     ]);
    
    //     // Add this at the start of your ldapLogin method
    //     $command = sprintf('sudo -u sevenup php -r \'$conn = ldap_connect("%s", %s);\'', env('LDAP_HOST'), env('LDAP_PORT'));
    //     try {
    //         Log::info('LDAP: Attempting connection to ' . env('LDAP_HOST'));
            
    //         $ldap_conn = ldap_connect(env('LDAP_HOST'), env('LDAP_PORT'));
    //         ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    //         ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
    //         ldap_set_option($ldap_conn, LDAP_OPT_NETWORK_TIMEOUT, 10);
    
    //         // First bind with service account
    //         $serviceBind = @ldap_bind($ldap_conn, env('LDAP_USERNAME'), env('LDAP_PASSWORD'));
    //         Log::info('LDAP: Service account bind result: ' . ($serviceBind ? 'Success' : 'Failed'));
    
    //         if ($serviceBind) {
    //             // Then search for and authenticate the user
    //             $search = ldap_search($ldap_conn, env('LDAP_USER_SEARCH_BASE'), "(sAMAccountName={$credentials['username']})");
    //             $entries = ldap_get_entries($ldap_conn, $search);
                
    //             if ($entries['count'] > 0) {
    //                 $userDn = $entries[0]['dn'];
    //                 $userBind = @ldap_bind($ldap_conn, $userDn, $credentials['password']);
    //                 Log::info('LDAP: User bind result: ' . ($userBind ? 'Success' : 'Failed'));
    
    //                 if ($userBind) {
    //                     $user = User::updateOrCreate(
    //                         ['username' => $credentials['username']],
    //                         [
    //                             'name' => $entries[0]['displayname'][0] ?? $credentials['username'],
    //                             'email' => $entries[0]['mail'][0] ?? '',
    //                             'password' => Hash::make($credentials['password'])
    //                         ]
    //                     );
    
    //                     Auth::login($user);
    //                     return redirect()->route('home');
    //                 }
    //             }
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('LDAP Error: ' . $e->getMessage());
    //         Log::error('LDAP Error Trace: ' . $e->getTraceAsString());
    //     }
    
    //     return back()->withErrors(['username' => 'Invalid credentials']);
    // }
    
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
    
    protected function authenticated(Request $request, $user)
    {
        if ($user->isStaff()) {
            return redirect()->route('gallery');
        }
        return redirect()->intended('/home');
    }
    
    public function ldapLogin(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);
    
        // Add this at the start of your ldapLogin method
        $command = sprintf('sudo -u sevenup php -r \'$conn = ldap_connect("%s", %s);\'', env('LDAP_HOST'), env('LDAP_PORT'));
        
        try {
            Log::info('LDAP: Attempting connection to ' . env('LDAP_HOST'));
            
            $ldap_conn = ldap_connect(env('LDAP_HOST'), env('LDAP_PORT'));
            ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldap_conn, LDAP_OPT_NETWORK_TIMEOUT, 10);
    
            $serviceBind = @ldap_bind($ldap_conn, config('ldap.username'), config('ldap.password'));
            Log::info('LDAP: Service account bind result: ' . ($serviceBind ? 'Success' : 'Failed'));
    
            if ($serviceBind) {
                $search = ldap_search($ldap_conn, config('ldap.user_search_base'), "(sAMAccountName={$credentials['username']})");
                $entries = ldap_get_entries($ldap_conn, $search);
                
                if ($entries['count'] > 0) {
                    $userDn = $entries[0]['dn'];
                    $userBind = @ldap_bind($ldap_conn, $userDn, $credentials['password']);
                    Log::info('LDAP: User bind result: ' . ($userBind ? 'Success' : 'Failed'));
    
                    if ($userBind) {
                        // Get user's group memberships
                        $groups = [];
                        if (isset($entries[0]['memberof'])) {
                            for ($i = 0; $i < $entries[0]['memberof']['count']; $i++) {
                                $groups[] = $entries[0]['memberof'][$i];
                            }
                        }
    
                        // Determine role based on group membership
                        $role = 'user';
                        $is_admin = false;
                        
                        foreach ($groups as $group) {
                            if (strpos($group, config('ldap.groups.admin')) !== false) {
                                $role = 'admin';
                                $is_admin = true;
                                break;
                            } elseif (strpos($group, config('ldap.groups.staff')) !== false) {
                                $role = 'staff';
                            }
                        }
    
                        $user = User::updateOrCreate(
                            ['username' => $credentials['username']],
                            [
                                'name' => $entries[0]['displayname'][0] ?? $credentials['username'],
                                'email' => $entries[0]['mail'][0] ?? '',
                                'password' => Hash::make($credentials['password']),
                                'role' => $role,
                                'is_admin' => $is_admin,
                                'ldap_groups' => $groups
                            ]
                        );
    
                        Auth::login($user);
                        return redirect()->intended($this->getRedirectPath($user));
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('LDAP Error: ' . $e->getMessage());
            Log::error('LDAP Error Trace: ' . $e->getTraceAsString());
        }
    
        return back()->withErrors(['username' => 'Invalid credentials']);
    }
    
    private function getRedirectPath($user)
    {
        if ($user->isAdmin()) {
            return route('admin.dashboard');
        } elseif ($user->isStaff()) {
            return route('gallery');
        }
        return route('home');
    }
    

}
