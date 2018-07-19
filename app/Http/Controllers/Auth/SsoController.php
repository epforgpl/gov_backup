<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Jumbojett\OpenIDConnectClient;

/**
 * Login / logout controller using the SSO server.
 *
 * We assume that the SSO server has its database of users, and that GovBackup has its own, separate database of users,
 * which however correspond to the SSO users.
 *
 * @package App\Http\Controllers\Auth
 */
class SsoController extends Controller
{
    private $open_id_client;

    public function __construct(OpenIDConnectClient $open_id_client)
    {
        $this->open_id_client = $open_id_client;
    }

    public function login()
    {
        $this->open_id_client->authenticate();
        session(['openid_connect_access_token' => $this->open_id_client->getAccessToken()]);
        $user_info = $this->open_id_client->requestUserInfo();

        // Find or create user in local database based on user info from SSO database.
        $user = User::where('email', $user_info->email)->first();
        if (!$user) {
            $user = User::create([
                'email' => $user_info->email,
                'name' => $user_info->name
            ]);
        }

        Auth::login($user);
        return redirect()->intended('/');
    }

    public function logout()
    {
        // Auth::logout() removes an entry pointing to the current user from the in-memory session object. It is
        // normally persisted by Laravel to disk/DB (whatever session store is) but only at the end of request
        // processing. However, we use a 3-rd party library (Jumbojett\OpenIDConnectClient), which performs a redirect
        // using `header('Location: ' . $url);` in its signOut() method. This prevents Laravel session persisting
        // mechanisms from working and therefore we need to call `Request::session()->save();` manually. See
        // https://github.com/laravel/framework/issues/1073 and
        // https://github.com/laravel/framework/issues/3061 for more info.
        Auth::logout();
        Request::session()->save();
        $this->open_id_client->signOut(session('openid_connect_access_token'), url('/'));
    }
}
