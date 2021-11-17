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
    public function identifier()
    {
        return 'dynamicscreen.twitter';
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

    public function renderOptions(Account $account) : View
    {
        try {
            $infos = $this->getUserInfos($account);
            return view('accounts-options.twitter', compact('account', 'infos'));
        } catch (\Exception $e) {
            return view('accounts-options.twitter', compact('account'));
        }
    }

    public function tryConnection(Account $account)
    {
        $options = $account->getOptions();
        $twitterConnection = new TwitterOAuth(config('ttwitter.CONSUMER_KEY'), config('ttwitter.CONSUMER_SECRET'), $options['oauth_token'], $options['oauth_token_secret']);
        $twitterConnection->get('application/rate_limit_status');
        return response('', $twitterConnection->getLastHttpCode());

    }

    public function getUserInfos(Account $account)
    {
        $options = $account->getOptions();
        $twitterConnection = new TwitterOAuth(config('ttwitter.CONSUMER_KEY'), config('ttwitter.CONSUMER_SECRET'), $options['oauth_token'], $options['oauth_token_secret']);
        $infos = $twitterConnection->get('users/show', ['user_id' => $options['user_id']]);
        if ($twitterConnection->getLastHttpCode() != 200) {
            throw new \Exception('Cannot get user informations');
        }
        return $infos;

    }

    public function signin($space_name, $account_id)
    {
        $consumer_key = config('ttwitter.CONSUMER_KEY');
        $consumer_secret = config('ttwitter.CONSUMER_SECRET');
        $ds_uuid = 'oauth.twitter.' . (string)Str::uuid();
        $url_callback = route('oauth.callback', ['driver_id' => $this->identifier(), 'ds_uuid' => $ds_uuid, 'space_name' => $space_name, 'account_id' => $account_id]);
        $twitteroauth = new TwitterOAuth($consumer_key, $consumer_secret);

        $request_token = $twitteroauth->oauth('oauth/request_token', [
            'oauth_callback' => $url_callback,
        ]);

        if ($twitteroauth->getLastHttpCode() != 200) {
            throw new \Exception('Request token not found.');
        }

        Session::put($ds_uuid, ['oauth_token' => $request_token['oauth_token'], 'oauth_token_secret' => $request_token['oauth_token_secret']]);

        $url = $twitteroauth->url(
            'oauth/authorize', [
                'oauth_token' => $request_token['oauth_token'],
            ]
        );

        return $url;
    }

    public function callback($request)
    {
        $space_name = $request->input('space_name');

        if ($request->get('denied') != null) {
            return route('manager.settings.accounts.create', ['_spacename' => $space_name]);
        }

        $consumer_key = config('ttwitter.CONSUMER_KEY');
        $consumer_secret = config('ttwitter.CONSUMER_SECRET');

        $account = $this->extractAccount($request->input('account_id'));
        if (!$account) {
            abort(404);
        }

        $oauth_verifier = $request->input('oauth_verifier');
        $ds_uuid = $request->input('ds_uuid');
        if (empty($oauth_verifier) || !Session::has($ds_uuid)) {
            throw new \Exception('Missing token');
        }

        $connection = new TwitterOAuth($consumer_key, $consumer_key, Session::get($ds_uuid)['oauth_token'], Session::get($ds_uuid)['oauth_token_secret']);
        $request->session()->forget($ds_uuid);

        $token = $connection->oauth(
            'oauth/access_token', ['oauth_verifier' => $oauth_verifier]);

        $account->options = $account->getDriver()->processOptions($token);
        if (!$account->space->hasTwitterAccountSet()) {
            Slide::getSlidesByTypeAndSpace('dynamicscreen.slides-essentials.twitter', $account->space_id)
                ->each(function (&$slide) use ($account) {
                    Slide::setSlideExternalAccount($slide, $account);
                    $slide->save();
                });
        }
        $account->active = true;
        $account->save();

        return route('manager.settings.accounts.edit', ['_spacename' => $space_name, 'account' => $account]);
    }

    public function createConnection(Account $account)
    {
        $accountOptions = $account->getOptions();
        return new TwitterOAuth(config('ttwitter.CONSUMER_KEY'), config('ttwitter.CONSUMER_SECRET'), $accountOptions['oauth_token'], $accountOptions['oauth_token_secret']);
    }

    public function getTweetsOf($account, $username, $options = [])
    {
        return $this->createConnection($account)
            ->get('statuses/user_timeline', array_merge([
                'screen_name' => $username,
            ], $options));
    }

    public function search($account, $q, $options = [])
    {
        return $this->createConnection($account)
            ->get('search/tweets', array_merge([
                'q' => $q,
            ], $options));
    }
}