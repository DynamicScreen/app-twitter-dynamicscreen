<?php

namespace DynamicScreen\Twitter\Twitter;

use App\Domain\Module\Model\Module;
use DynamicScreen\SdkPhp\Handlers\SlideHandler;
use DynamicScreen\SdkPhp\Interfaces\ISlide;

class TwitterSlideHandler extends SlideHandler
{
    public function __construct(Module $module)
    {
        parent::__construct($module);
    }

    public function fetch(ISlide $slide): void
    {
        $options = $slide->getOptions();

        $driver = $this->getAuthProvider($slide->getAccounts());
        if ($driver == null) return;

        $expiration = Carbon::now()->addHour();
        $cache_key = "{$this->getProviderIdentifier()}::{$options['username']}_{$options['page']}";
        $api_response = app('cache')->remember($cache_key, $expiration, function () use ($options, $driver, $account) {
            if (starts_with($options['username'], '#')) {
                $response = $driver->search('#' . ltrim($options['username'], '#'),
                    ['count' => $options['page'] * 4]);
                if (!isset($response->statuses)) {
                    return [];
                }
                $collection_tweets = collect($response->statuses)->map(function ($tweet){
                    return $this->presentTweet($tweet);
                });
                return $collection_tweets->toJson();
            }
            else{
                $collection_tweets = collect($driver->getTweetsOf('@' . ltrim($options['username'], '@'), ['count' => $options['page'] * 4]));
                foreach($collection_tweets as $tweet) {
                    $collection_tweets_formatted[] = $this->presentTweet($tweet);
                }
                return json_encode($collection_tweets_formatted);
            }
        });

        foreach (collect(json_decode($api_response))->chunk(4) as $chunk){
            $tweets = [];
            $tweetImg =false;
            foreach ($chunk as $tweet){
                if($tweet->media_url and $tweetImg == false){
                    $tweetImg = $tweet;
                }
                else{
                    $tweets[] = $tweet;
                }
            }
            $lastTweet = null;

            if($tweetImg == false){
                $lastTweet = collect($tweets)->first();
                $tweets = collect($tweets)->slice(1);
            }
            $this->addSlide([
                'tweets' => $tweets,
                'title' => $options['title'],
                'firstImg' => $tweetImg,
                'lastTweet' => $lastTweet
            ]);
        }

        $this->addSlide([]);
    }

    public function getDefaultOptions(): array
    {
        return [];
    }
}
