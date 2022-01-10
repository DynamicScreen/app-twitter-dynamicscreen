<?php

namespace DynamicScreen\Twitter\TwitterDriver;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Domain\Module\Model\Module;
use Carbon\Carbon;
use DynamicScreen\SdkPhp\Handlers\OAuthProviderHandler;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Arr;

class TwitterAuthProviderHandler extends OAuthProviderHandler
{
    public static string $provider = 'twitter';

    public function __construct(Module $module, $config = null)
    {
        parent::__construct($module, $config);
    }

    public function provideData($settings = [])
    {
        $options = request()->get('options');

        $this->addData('users', function () use ($options) {
            $response = $this->search($options['q'], ['count' => 6]);

            return collect($response)->map(function ($tweeter) {
                return [
                    'name' => $tweeter->name,
                    'screen_name' => $tweeter->screen_name,
                ];
            })->toArray();
        });
    }

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

    public function callback($request, $redirectUrl = null)
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
    }

    public function createConnection($config = null)
    {
        $accountOptions = $config ?? $this->default_config;

        $consumer_key = config("services.{$this->getProviderIdentifier()}.client_id");
        $consumer_secret = config("services.{$this->getProviderIdentifier()}.client_secret");

        return new TwitterOAuth($consumer_key, $consumer_secret, Arr::get($accountOptions, 'oauth_token'), Arr::get($accountOptions, 'oauth_token_secret'));
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
            ->get('users/search', array_merge([
                'q' => $q,
            ], $options));
    }
}