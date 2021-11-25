<?php

namespace DynamicScreen\Twitter\TwitterDriver;

use Abraham\TwitterOAuth\TwitterOAuthException;
use DynamicScreen\SdkPhp\Handlers\OAuthProviderHandler;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Abraham\TwitterOAuth\TwitterOAuth;
use Illuminate\Support\Facades\Session;

class TwitterAuthProviderHandler extends OAuthProviderHandler
{
    public static string $provider = 'twitter';

    public $default_config = [];

    public function __construct($config = null)
    {
        $this->default_config = $config;
    }

    public function identifier()
    {
        return 'twitter-driver';
    }

    public function name()
    {
        return "Twitter";
    }

    public function description()
    {
        return "OAuth Twitter";
    }

    public function icon()
    {
        return "fab fa-twitter";
    }

    public function color()
    {
        return '#00acee';
    }

//    public function renderOptions(Account $account) : View
//    {
//        try {
//            $infos = $this->getUserInfos($account);
//            return view('accounts-options.twitter', compact('account', 'infos'));
//        } catch (\Exception $e) {
//            return view('accounts-options.twitter', compact('account'));
//        }
//    }

    public function testConnection($config = null)
    {
        $config = $config ?? $this->default_config;

        $twitterConnection = $this->createConnection($config);

        $twitterConnection->get('application/rate_limit_status');
        return response('', $twitterConnection->getLastHttpCode());

    }

    public function getUserInfos($config = null)
    {
        $options = $config ?? $this->default_config;

        $connection = $this->createConnection($config);
        $infos = $connection->get('users/show', ['user_id' => $options['user_id']]);
        if ($connection->getLastHttpCode() != 200) {
            throw new \Exception('Cannot get user informations');
        }
        return $infos;

    }

    public function signin($callbackUrl = null)
    {

        $consumer_key = config("services.{$this->getProviderIdentifier()}.client_id");
        $consumer_secret = config("services.{$this->getProviderIdentifier()}.client_secret");
        $ds_uuid = 'oauth.twitter.' . (string)Str::uuid();
        $callbackUrl = route('api.oauth.callback', ['ds_uuid' => $ds_uuid]);

        $twitteroauth = new TwitterOAuth($consumer_key, $consumer_secret);
        $request_token = $twitteroauth->oauth('oauth/request_token', [
            'oauth_callback' => $callbackUrl,
        ]);

        if ($twitteroauth->getLastHttpCode() != 200) {
            throw new \Exception('Request token not found.');
        }

        Session::put($ds_uuid, ['oauth_token' => $request_token['oauth_token'], 'oauth_token_secret' => $request_token['oauth_token_secret']]);

        $url = $twitteroauth->url(
            'oauth/authorize', [
                'oauth_token' => $request_token['oauth_token'],
                'ds_uuid' => $ds_uuid,
            ]
        );

        return $url;
    }

    public function callback($request, $redirectUrl)
    {
        $consumer_key = config("services.{$this->getProviderIdentifier()}.client_id");
        $consumer_secret = config("services.{$this->getProviderIdentifier()}.client_secret");

        $oauth_verifier = $request->input('oauth_verifier');
        $ds_uuid = $request->input('ds_uuid');
        if (empty($oauth_verifier) || !Session::has($ds_uuid)) {
            throw new \Exception('Missing token');
        }

        $connection = new TwitterOAuth($consumer_key, $consumer_secret, Session::get($ds_uuid)['oauth_token'], Session::get($ds_uuid)['oauth_token_secret']);
        $request->session()->forget($ds_uuid);

        $token = $connection->oauth('oauth/access_token', ['oauth_verifier' => $oauth_verifier]);
        $token['active'] = true;

        $data = $this->processOptions($token);
        $dataStr = json_encode($data);

        return redirect()->away($redirectUrl ."&data=$dataStr");


//        return route('manager.settings.accounts.edit', ['_spacename' => $space_name, 'account' => $account]);
    }

    public function createConnection($config = null)
    {
        $accountOptions = $config ?? $this->default_config;

        $consumer_key = config("services.{$this->getProviderIdentifier()}.client_id");
        $consumer_secret = config("services.{$this->getProviderIdentifier()}.client_secret");

        return new TwitterOAuth($consumer_key, $consumer_secret, $accountOptions['oauth_token'], $accountOptions['oauth_token_secret']);
    }

    public function getTweetsOf($username, $options = [], $config = null)
    {
        $config = $config ?? $this->default_config;

        return $this->createConnection($config)
            ->get('statuses/user_timeline', array_merge([
                'screen_name' => $username,
            ], $options));
    }

    public function search($q, $options = [], $config = null)
    {
        $config = $config ?? $this->default_config;

        return $this->createConnection($config)
            ->get('search/tweets', array_merge([
                'q' => $q,
            ], $options));
    }
}