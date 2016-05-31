<?php
  
namespace TwitterFeed\Source\Twitter;

// use \CpiaApp\Source\Twitter\TwitterAPIExchange;

  /**Chris Grass
   *Palomino System Innovations Inc.
   *July-08-13
   *TweetFunctions.php
   *
   *NOTE: Some unicode characters are not showing up correctly, but this should be corrected when it is implemented in a document specifying the encoding.
   **/

//   require_once('TwitterAPIExchange.php');
   
  /**Recursive function that echos the contents of an array and all sub-arrays
   *Mainly for testing
   **/
  function echo_array($arr) {
    echo "<ul>";
    foreach($arr as $x=>$x_val) {
      echo "<li>";
      echo "Key: " . $x . "</br>";
      if (is_array($x_val)) {
      echo_array($x_val);
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
  function getTweets($settings, $screen_name, $num_tweets) {
  
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
  function parseText($text) {
    
    $ht_pattern = "%^[#.+]%";
    $mention_pattern = "%^[@.+]%";
    $num_pattern = "%^[0-9]+.*%";
    $link_pattern = "%^http://t.co/.+$%";
    
    $token = strtok($text, " ");
    $return_text = "";
    
    while ($token !== false) {
      //if matches ht pattern
        //change to a . ht link . cur text . close
      //else if matches link pattern
        //change to a. link . curr text . close
      if ( preg_match($ht_pattern, $token) ) {
        preg_match($num_pattern,trim($token,"#")) ? $token : $token = "<a href='http://twitter.com/search?q=%23" . trim($token,"#!.,;") . "' target='_blank'>{$token}</a>";
      } else if (preg_match($link_pattern, $token)){
        $token = "<a href='{$token}' target='_blank'>{$token}</a>";
      } else if (preg_match($mention_pattern, $token)) {
        $token = "<a href='http://twitter.com/" . trim($token,"@!.,;") . "' target='_blank'>{$token}</a>";
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
  function print_tweet($tweet) {
    //dummies for testing
    $date = "j-M-Y"; //placeholder format
    $text = "This is an example tweet of 140 chars that should display and be formatted normally. This is taken from Palomino's twitter feed using v1.1!";
    
    //real vals
    $date = strtotime($tweet['date']);
    $date = date('j-M-Y', $date);
    $text = array_key_exists('rt_user', $tweet) ? "RT <a href='http://twitter.com/" . $tweet['rt_user'] . "' target='_blank'>@" . $tweet['rt_user'] . ":</a> " . parseText($tweet['text']) : parseText($tweet['text']);
    
    echo "<div class='rss_date'>{$date}</div>";
    echo "<div class='rss_link'>{$text}</div>";
  
  }
  
  /**Loops through the array of tweets, parses the necessary info, and calls
   *  function print_tweet iteratively**/
  function print_tweets($tweets) {
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
      print_tweet($tweet);
      echo "</div>";
      
      $int++;
    }
    echo "</div>";
  }

?>