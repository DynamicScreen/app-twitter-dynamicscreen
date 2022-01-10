<?php

namespace DynamicScreen\Twitter\Twitter;

use Carbon\Carbon;
use DynamicScreen\SdkPhp\Handlers\SlideHandler;
use DynamicScreen\SdkPhp\Interfaces\ISlide;
use Illuminate\Support\Str;

class TwitterSlideHandler extends SlideHandler
{

    public function fetch(ISlide $slide): void
    {
        $options = $slide->getOptions();

        $driver = $this->getAuthProvider($slide->getAccounts());

        if ($driver == null) return;

        $expiration = Carbon::now()->addHour();
        $cache_key = "{$driver->getProviderIdentifier()}::{$options['username']}_{$options['page']}";
        $api_response = app('cache')->remember($cache_key, $expiration, function () use ($options, $driver) {
            if (Str::startsWith($options['username'], '#')) {
                $response = $driver->search('#' . ltrim($options['username'], '#'),
                    ['count' => $options['page'] * 4]);
                if (!isset($response->statuses)) {
                    return [];
                }
                $collection_tweets = collect($response->statuses)->map(function ($tweet) {
                    return $this->presentTweet($tweet);
                });
                return $collection_tweets->toJson();
            }
            else {
                $collection_tweets = collect($driver->getTweetsOf('@' . ltrim($options['username'], '@'), ['count' => $options['page'] * 4]));
                $collection_tweets_formatted = [];
                foreach($collection_tweets as $tweet) {
                    $collection_tweets_formatted[] = $this->presentTweet($tweet);
                }
                return json_encode($collection_tweets_formatted);
            }
        });

        foreach (collect(json_decode($api_response))->chunk(4) as $chunk) {
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

    protected function linkify($text)
    {
        $text = ' ' . $text;

        $patterns             = [];
        $patterns['url']      = '(?xi)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))';
        $patterns['mailto']   = '([_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3}))';
        $patterns['user']     = ' +@([a-z0-9_]*)?';
        $patterns['hashtag']  = '(?:(?<=\s)|^)#(\w*[\p{L}-\d\p{Cyrillic}\d]+\w*)';
        $patterns['long_url'] = '>(([[:alnum:]]+:\/\/)|www\.)?([^[:space:]]{12,22})([^[:space:]]*)([^[:space:]]{12,22})([[:alnum:]#?\/&=])<';

        // Transform URL
        $pattern_url = '(?xi)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))';

        $text = preg_replace_callback('#'.$pattern_url.'#i', function($matches)
        {
            $input = $matches[0];
            $url   = preg_match('!^https?://!i', $input) ? $input : "http://$input";
            return '<span class="highlight">'."$input</span>";
        }, $text);

        // Transform patterns// Mailto
        $text = preg_replace('/'.$patterns['mailto'].'/i', "<span class=\"highlight\">\\1</span>", $text);
        // User
        $text = preg_replace('/'.$patterns['user'].'/i', " <span class=\"highlight\">@\\1</span>", $text);
        // Hashtag

        $pos = strpos($patterns['hashtag'], '-');
        $hashtag_pattern = substr_replace($patterns['hashtag'], '\\', $pos, 0);
        $text = preg_replace('/'.$hashtag_pattern.'/ui', "<span class=\"highlight\">#\\1</span>", $text);
        // Long URL
        $text = preg_replace('/'.$patterns['long_url'].'/', ">\\3...\\5\\6<", $text);
        // Remove multiple spaces
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    public function presentTweet($tweet)
    {
        if (isset($tweet->entities->media) && count($tweet->entities->media) > 0) {
            $media = $tweet->entities->media[0];
        } else {
            $media = null;
        }

        if (isset($tweet->text)) {
            $text = $tweet->text;
        } else if (isset($tweet->full_text)) {
            $text = $tweet->full_text;
        } else {
            $text = "";
        }

        if ($media !== null) {
            $text = trim(str_replace($media->url, '', $text));
        }

        $created_at = null;
        if (isset($tweet->created_at)) {
            $created_at = new Carbon($tweet->created_at);
            $created_at->timezone('Europe/Paris');
        }

        return  [
            'screen_name'    => mb_convert_encoding($tweet->user->screen_name, 'UTF-8'),
            'name'           => mb_convert_encoding($tweet->user->name, 'UTF-8'),
            'avatar'         => mb_convert_encoding($tweet->user->profile_image_url_https, 'UTF-8'),
            'color'          => mb_convert_encoding($tweet->user->profile_link_color, 'UTF-8'),
            'text'           => mb_convert_encoding($text, 'UTF-8'),
            'formatted_text' => mb_convert_encoding($this->linkify($text), 'UTF-8'),
            'retweet_count'  => mb_convert_encoding($tweet->retweet_count, 'UTF-8'),
            'favorite_count' => mb_convert_encoding($tweet->favorite_count, 'UTF-8'),
            'created_at'     => $created_at ? mb_convert_encoding($created_at->format('Y-m-d H:i:s'), 'UTF-8') : '',
            'hashtags'       => collect($tweet->entities->hashtags)->pluck('text')->toArray(),
            'url'            => "https://www.twitter.com/{$tweet->user->screen_name}/status/{$tweet->id}",
            'media_url'      => $media === null ? '' : $media->media_url_https,
        ];
    }
}
