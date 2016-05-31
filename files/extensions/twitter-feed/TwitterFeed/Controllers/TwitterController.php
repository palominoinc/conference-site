<?php

  /**Chris Grass
   *Palomino System Innovations Inc.
   *July-08-13
   *TweetFunctions.php
   *
   *NOTE: Some unicode characters are not showing up correctly, but this should be corrected when it is implemented in a document specifying the encoding.
   **/


namespace TwitterFeed\Controllers;
use \TwitterFeed\Source\Twitter\TwitterAPIExchange;

class TwitterController extends \BaseController
{
  
  // private $allowed_accounts = array(
  //   'SMExaminer'
  // );
  
  private $palomino_settings = array(
          'oauth_access_token' => '77360958-C1MwSMREp9KcJplD5BqK9RbTE4Tg9CRFvjTlhUBN1',
          'oauth_access_token_secret' => 'hiOyBIwRums6HHYs0yoTLTur3U38XDZLXYRErSVA6M',
          'consumer_key' => 'VO1BuV9EJP4vZQ5mw2bK3A',
          'consumer_secret' => 'cJ0VB6heN9km6sfketlfH8nnbuyc127yKhlcQmznDsA'
  );
  public function index() {
    
    if(isset($_GET)){
      
      // if (array_search($_GET['username'], $this->allowed_accounts) === false) {
      //   return "Unauthorized";
      // }
      $palomino_settings = array(
          'oauth_access_token' => $_GET['access-token'],
          'oauth_access_token_secret' => $_GET['access-token-secret'],
          'consumer_key' => $_GET['consumer-key'],
          'consumer_secret' => $_GET['consumer-secret']
      );
  
      $palomino_data = $this->getTweets($this->palomino_settings, $_GET['username'], $_GET['num-tweets']);
  
      // echo '<pre>here</pre>';
      // echo '<pre>' . print_r($palomino_data, true) . '</pre>';
      // echo '<pre>' . print_r($_REQUEST, true) . '</pre>';
      $this->print_tweets($palomino_data);
    }
  }
   
  /**Recursive function that echos the contents of an array and all sub-arrays
   *Mainly for testing
   **/
  private function echo_array($arr) {
    echo "<ul>";
    foreach($arr as $x=>$x_val) {
      echo "<li>";
      echo "Key: " . $x . "</br>";
      if (is_array($x_val)) {
      $this->echo_array($x_val);
    } else {
      echo "Value: " . $x_val . "</br>";
    }
    echo "</li>";
    }
    echo "</ul>";
  }
  
  /**Retrieves the latest 6 tweets from palominoinc's Twitter account.
   *To alter this code to work with other Twitter accounts, go to
   * twitter dev and generate the necessary tokens/keys and
   * alter the variables in this function accordingly
   *
   *$settings contains the oauth tokens and keys, as well as the screen name of the user
   *$screen_name is the screen_name from whom to retrieve the tweets 
   *
   *returns an array
   **/
  private function getTweets($settings, $screen_name, $num_tweets) {
  
    /**For GET request of user $screen_name's timeline**/
    $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';

    /**Req'd vars for Oauth authentication**/  
    $requestMethod = 'GET';
    $getfield = "?screen_name={$screen_name}&count={$num_tweets}&include_rts=1";
    $twitter = new TwitterAPIExchange($settings);
    $twitter->setGetfield($getfield);
    $twitter->buildOauth($url, $requestMethod);
    /**json request**/
    $json_req = $twitter->performRequest();
    $data = json_decode($json_req, TRUE);
  
  
    return $data;
  }
  
  /**Necessary for parsing through and converting #hashtags,addresses (only t.co), and @mentions into clickable links
   *Returns a string
   **/
  private function parseText($text) {
    
    $ht_pattern = "%^[#.+]%";
    $mention_pattern = "%^[@.+]%";
    $num_pattern = "%^[0-9]+.*%";
    $link_pattern = "%^https+://t.co/.+$%";
    
    $token = strtok($text, " ");
    $return_text = "";
    
    while ($token !== false) {
      //if matches ht pattern
        //change to a . ht link . cur text . close
      //else if matches link pattern
        //change to a. link . curr text . close
      if ( preg_match($ht_pattern, $token) ) {
        preg_match($num_pattern,trim($token,"#")) ? $token : $token = "<a href='//twitter.com/search?q=%23" . trim($token,"#!.,;") . "' target='_blank'>{$token}</a>";
      } else if (preg_match($link_pattern, $token)){
        $token = "<a href='{$token}' target='_blank'>{$token}</a>";
      } else if (preg_match($mention_pattern, $token)) {
        $token = "<a href='//twitter.com/" . trim($token,"@!.,;") . "' target='_blank'>{$token}</a>";
      }
      $return_text .= " {$token}";
      $token = strtok(" ");
    }

    return $return_text;
  }
  
  /**This function takes a decoded json tweet and prints it in the format
   *  of Palomino's site
   *$tweet = array (
   *  'date'
   *  'rt_user' <--if a RT
   *  'text'
   *)
   * TODO:
   * Make links and hash tags orange
   * Properly format date & text
   **/
  private function print_tweet($tweet) {
    //dummies for testing
    $date = "j-M-Y"; //placeholder format
    $text = "This is an example tweet of 140 chars that should display and be formatted normally. This is taken from Palomino's twitter feed using v1.1!";
    
    //real vals
    $date = strtotime($tweet['date']);
    $date = date('j-M-Y', $date);
    $text = array_key_exists('rt_user', $tweet) ? "RT <a href='http://twitter.com/" . $tweet['rt_user'] . "' target='_blank'>@" . $tweet['rt_user'] . ":</a> " . $this->parseText($tweet['text']) : $this->parseText($tweet['text']);
    
    echo "<div class='rss_date'>{$date}</div>";
    echo "<div class='rss_link'>{$text}</div>";
  
  }
  
  /**Loops through the array of tweets, parses the necessary info, and calls
   *  function print_tweet iteratively**/
  private function print_tweets($tweets) {
    $int = 1;
    if ($int > count($tweets)) {
      break;
    }
    echo "<div class='rss'>";
    for ($i=0; $i<count($tweets); $i++) {
    
      //This code block parses and sends only the necessary information to the next function
      if (array_key_exists('retweeted_status', $tweets[$i])) {
        $tweet = array (
          'date' => $tweets[$i]['created_at'],
          'rt_user' => $tweets[$i]['retweeted_status']['user']['screen_name'],
          'text' => $tweets[$i]['retweeted_status']['text']
        );
      } else {
        $tweet = array (
          'date' => $tweets[$i]['created_at'],
          'text' => $tweets[$i]['text']
        );
      }
      
      $highlight = $int % 2 ? 'rss_highlight' : '';
      echo "<div class='rss_item {$highlight}'>";
      $this->print_tweet($tweet);
      echo "</div>";
      
      $int++;
    }
    echo "</div>";
  }

}

