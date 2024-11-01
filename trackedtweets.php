<?php
/*
Plugin Name: Tracked Tweets
Plugin URI: http://www.jackmcintyre.net/projects/tracked-tweets/?utm_source=wordpress&utm_medium=plugin&utm_campaign=tracked-tweets
Description: Posts are added to your twitter account. Tweet can be formatted to user's liking. URL's are shortened using Tinyurl, and Google Analytics tracking is added.
Version: 0.2.9
Author: Jack McIntyre
Author URI: http://jackmcintyre.net?utm_source=wordpress&utm_medium=plugin&utm_campaign=tracked-tweets
Thanks to: Ed Saribatir, Lionel Roux (http://blog.websourcing.fr), Michael (http://www.zesty.fr/), Chris (http://www.dot-chris.com/)
*/
define('TRACKEDTWEETS_URL', 'http://twitter.com/');
define('TRACKEDTWEETS_MESSAGE', 'statuses/update.json');
define('TRACKEDTWEETS_LOGIN', 'statuses/user_timeline.json');
define('TRACKEDTWEETS_MAX_LENGTH', 140);
define('TRACKEDTWEETS_VERSION', '0.2.9');
global $trackedtweetsvariables;
global $tweetthis;
$trackedtweetsvariables = array('title' => __('[title]'),'author' => __('[author]'),'ga_source' => __('[ga_source]'),'ga_medium' => __('[ga_medium]'),'url' => __('[url]'),'extra' => __('[extra]'),'twdefaultstate' => __('[twdefaultstate]'),'shortener' => __('[shortener]'),'bitly_username' => __('[bitly_username]'),'bitly_api' => __('[bitly_api]'));
add_action('publish_post', 'trackedtweetssend_post');
function trackedtweetssend_post() {
	$post = get_post($_POST['post_ID']);
	$tweetthis = get_post_meta($post->ID, '_trackedtweets_tweetthis_value', true);
	$options = get_option('trackedtweets');
	if (!empty($options) && ($tweetthis === "on")) {  
		$message = trackedtweetsprepare_message($options);
		if (trackedtweetsvalid_message($message)) {
			trackedtweetssend_status($options, $message);
		}
	}
}
function trackedtweetsvalid_message() {
	return isset($_POST['publish']) && strlen($message) <= TWITTER_MAX_LENGTH;
}
function determineoperator($string) {
	if (preg_match("/\?/i", $string)) {
		$string = "&";
	} else {
	$string = "?";
	}
return $string;
}

function securitycheck(){
	$curlenabled = function_exists('curl_version') ? 'enabled' : 'disabled';
	$urlfopenenabled = ini_get('allow_url_fopen') ? 'enabled' : 'disabled';
	if(($curlenabled === 'enabled') || ($urlfopenenabled === 'enabled')){
		$securitymessage = 'false'; // Server security ok. All should be fine
	} else {
		$securitymessage = 'true'; // Server security is too tight. Tracked Tweets will not work here, sorry.
	}
	return $securitymessage;
}

function url_shortener($trackedlink){
	$values = get_option('trackedtweets');
	$post = get_post($_POST['post_ID']);
	if ($values['shortener'] === "revcanonical"){
		$shortener_url = revcanonical_shorten($post->ID);
	}
	if ($values['shortener'] === "tinyurl"){
		$temp_url = "http://tinyurl.com/api-create.php?url=".$trackedlink;
		$shortener_url = get_tiny_url($temp_url);
	}
	if ($values['shortener'] === "trim"){
		$trackedlink = urlencode($trackedlink);
		$temp_url = "http://api.tr.im/api/trim_simple?url=".$trackedlink;
		$shortener_url = get_tiny_url($temp_url);
	}
	if ($values['shortener'] === "bitly"){
		$bitly_username = $values['bitly_username'];
		$bitly_api = $values['bitly_api'];
		$shortener_url = make_bitly_url($trackedlink,$bitly_username,$bitly_api);
	}
	return $shortener_url;
}

function make_bitly_url($url,$login = null,$appkey = null,$version = '2.0.1'){
	if(empty($login) && empty($appkey)) {
		$bitly = 'http://bit.ly/api?url='.urlencode($url);
		return get_tiny_url($bitly);
	} else {
		$bitly = 'http://api.bit.ly/shorten?version='.$version.'&longUrl='.urlencode($url).'&login='.$login.'&apiKey='.$appkey;
		$response = get_tiny_url($bitly);
		$json = @json_decode($response,true);
		return $json['results'][$url]['shortUrl'];
	}
}

function get_tiny_url($url){
	$curlenabled = function_exists('curl_version') ? 'enabled' : 'disabled';
	$urlfopenenabled = ini_get('allow_url_fopen') ? 'enabled' : 'disabled';
	if($curlenabled === 'enabled'){
		$ch = curl_init();  
		$timeout = 5;  
		curl_setopt($ch,CURLOPT_URL,$url);  
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);  
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);  
		$data = curl_exec($ch);  
		curl_close($ch);  
		return $data;  
	} elseif($urlfopenenabled === 'enabled') {
		$data = file_get_contents($url);
		return $data;
	} else {
		//Plugin will break, as security settings are too strict.
	}
}
function trackedtweetsprepare_message($options) {
	global $trackedtweetsvariables;
	global $tweetthis;
	$post = get_post($_POST['post_ID']);
	$title = $post->post_title;
	$gasource = $options['ga_source'];
	$gamedium = $options['ga_medium'];
	$gacampaign = (string)$post->post_name;
	$plink = get_permalink($_POST['post_ID']) ;
	$initoperator = determineoperator($plink);
	$trackedlink = $plink . $initoperator . "utm_source=" . $gasource . "&utm_medium=" . $gamedium . "&utm_campaign=" . $gacampaign;
	$url = url_shortener($trackedlink);
	$author = get_author_name($post->post_author);
	$tweetthis = get_post_meta($post->ID, '_trackedtweets_tweetthis_value', true);
	$extra = get_post_meta($post->ID, '_trackedtweets_extra_value', true);
	$message = $options['tweet_format'];
	$message_dump = $message;
	foreach ($trackedtweetsvariables as $key => $variable) {
		$message_dump = str_replace($variable, ${$key}, $message_dump);
	}
	$full_length = strlen($message_dump);
	if($full_length > TRACKEDTWEETS_MAX_LENGTH)
		$title = trimarg($title, $full_length, TRACKEDTWEETS_MAX_LENGTH);
	foreach ($trackedtweetsvariables as $key => $variable) {
		$message = str_replace($variable, ${$key}, $message);
	}
	return $message;
}

function trimarg($arg, $currentlength, $maxlength){
	$arglength = strlen($arg);
	$excedinglength = $currentlength - $maxlength;
	$newlength = $arglength - $excedinglength - 3;
	$arg = substr($arg, 0, $newlength);
	return $arg.'...';
}
function trackedtweetsverify_user($username, $password) {
	return trackedtweetsrequest(TRACKEDTWEETS_URL . TRACKEDTWEETS_LOGIN, $username, $password);
}
function trackedtweetssend_status($options, $message) {
	trackedtweetsrequest(TRACKEDTWEETS_URL . TRACKEDTWEETS_MESSAGE, $options['twitter_username'], $options['twitter_password'], $message);
}
function trackedtweetsrequest($uri, $username, $password = null, $message = null) {
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy();
	$snoop->agent = 'Tracked Tweets';
	$snoop->rawheaders = array(
		'X-Twitter-Client' => 'trackedtweets',
		'X-Twitter-Client-Version' => TRACKEDTWEETS_VERSION, 
		'X-Twitter-Client-URL' => 'http://jackmcintyre.net/trackedtweets'
	);      
	$snoop->user = $username;
	if (!is_null($password)) {
			$snoop->pass = $password;
	}
	if (!is_null($message)) {
			$snoop->submit($uri, array('status' => $message, 'source' => 'trackedtweets'));
	} else {
			$snoop->fetch($uri);
	}
	if (@strpos('200',$snoop->response_code)) {
			$results = json_decode($snoop->results);
			return sprintf(__('Sorry, login failed: %s', 'trackedtweets'), $results->error);
	} else {
			return true;
	}
}

/**
Error Messages
This function has messy code.
**/
function trackedtweets_warning() {
	$values = get_option('trackedtweets');
	$security = securitycheck();
	$security = 'false'; //testing
	if ((isset($values['twitter_username'])) && (isset($values['twitter_password']))) { 
		if($security == 'false'){
			//all good, no error message
		} if($security == 'true'){
			echo "<div id='trackedtweets-warning' class='error'><p><strong>".__('Server security is too tight.')."</strong> ".sprintf(__(' Tracked Tweets will not work here, sorry. <a href="%1$s">Leave a comment</a> and I will tell you what to ask your host.'), "http://www.jackmcintyre.net/projects/tracked-tweets?utm_source=wordpress&utm_medium=plugin&utm_campaign=tracked-tweets")."</p></div>";
		}
	} elseif($security == 'false') {
		echo "<div id='trackedtweets-warning' class='updated fade'><p><strong>".__('Tracked Tweets is almost ready.')."</strong> ".sprintf(__('You need to <a href="%1$s">enter some settings</a> for it to work.'), "options-general.php?page=tracked-tweets/trackedtweets.php")."</p></div>";
	}
	if($security == 'true'){
		echo "<div id='trackedtweets-warning' class='error'><p><strong>".__('Server security is too tight.')."</strong> ".sprintf(__(' Tracked Tweets will not work here, sorry. <a href="%1$s">Leave a comment</a> and I will tell you what to ask your host.'), "http://www.jackmcintyre.net/projects/tracked-tweets?utm_source=wordpress&utm_medium=plugin&utm_campaign=tracked-tweets")."</p></div>";
	}
}
add_action('admin_notices', 'trackedtweets_warning');
/**
Admin Settings Pages
**/
add_action('admin_head', 'trackedtweets_css');
function trackedtweets_css() {
	echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') .'/wp-content/plugins/tracked-tweets/css/style.css" />' . "\n";
}
add_action( 'admin_menu', 'trackedtweetsmenu' );
function trackedtweetsmenu(){
	$page=add_options_page('Tracked Tweets Options', 'Tracked Tweets', 8, __FILE__, 'trackedtweetsoptions');
	add_action( 'admin_head-'. $page, 'trackedtweets_js' );
}
function trackedtweets_js() {
	echo '<script type="text/javascript" src="' . get_bloginfo('wpurl') .'/wp-content/plugins/tracked-tweets/js/trackedtweets.js" />' . "\n";
}
function _trackedtweetssave() {
	$error = array();
	if (empty($_POST['twitter_username'])) {
		$error[] = __('Enter your Twitter Username');
	} 
	if (empty($_POST['twitter_password'])) {
		$error[] = __('Enter your Twitter Password');
	}
	if (empty($_POST['ga_source'])) {
		$error[] = __('You should set the source. If you are unsure, use "twitter"');
	}
	if (empty($_POST['ga_medium'])) {
		$error[] = __('You should set the medium. If you are unsure, use "social"');
	}
	if (empty($_POST['tweet_format'])) {
		$error[] = __('Define the format of your tweets');
	}
	if (strlen($_POST['tweet_format']) >= 100) {
		$error[] = __('The maximum length of the message is 100 characters');
	}
	if (empty($_POST['shortener'])) {
		$error[] = __('Please choose a URL Shortening service');
	}
	if (count($error) == 0) {
		if (is_bool(trackedtweetsverify_user($_POST['twitter_username'], $_POST['twitter_password']))) {
			unset($_POST['savesettings']);
			delete_option('trackedtweets');
			add_option('trackedtweets', $_POST);
			return __('Options updated');
		} else {
			$error[] = __('Check username and password');
		}
	}
	return implode('<br />', $error);
}
function trackedtweetsoptions() {
	$values = get_option('trackedtweets');
	if ( 'POST' == strtoupper($_SERVER['REQUEST_METHOD']) ) {
			$response = _trackedtweetssave();
			$values = $_POST;
	}
	if ($values['twdefaultstate'] == "1"){   
			$checkboxstate = ' checked="checked" ';
	}
	if ($values['shortener'] == "tinyurl") {
		$tinyurl_checked = ' checked="checked" ';
	}
	if ($values['shortener'] == "trim") {
		$trim_checked = ' checked="checked" ';
	}
	if ($values['shortener'] == "bitly") {
		$bitly_checked = ' checked="checked" ';
	}
	if (function_exists('revcanonical_shorten')) {
		if ($values['shortener'] == "revcanonical") {
			$revcanonical_checked = ' checked="checked" ';
		}
		$revcanonicaloption = '<input type="radio" onclick="hideDiv(\'bitlyoptions\')" id="shortener" name="shortener" value="revcanonical" '.$revcanonical_checked.'/> RevCanonical Plugin<br />';
	}
	$output = '<div class="wrap">';
	$output .= '<h2>Tracked Tweets '. __('Settings', 'trackedtweets') . '</h2>';
	if (isset($response)) {
			$output .= '<div class="updated fade" id="message" style="background-color: rgb(255, 251, 204);"><p>'. $response .'</p></div>';
	}
	$output .= '<form action="" method="post">';
	$output .= '<div id="trackedtweetsoptions">';
	$output .= '    <div id="trackedtweets_leftcol">
						<div class="trackedtweets_optionsblock">
							<h3>Twitter</h3>
							<label for="twitter_username">'. __('Username:') .'</label><br />
							<input id="twitter_username" type="text" value="'. $values['twitter_username'] .'" name="twitter_username" /><br />
							<label for="twitter_password">'. __('Password:') .'</label><br />
							<input id="twitter_password" type="password" value="'. $values['twitter_password'] .'" name="twitter_password" />
						</div>
						<div class="trackedtweets_optionsblock">
							<h3>Google Analytics</h3>
							<label for="ga_source">'. __('Source:') .'</label><br />
							<input id="ga_source" type="text" value="'. $values['ga_source'] .'" name="ga_source" /><br />
							<label for="ga_medium">'. __('Medium:') .'</label><br />
							<input id="ga_medium" type="text" value="'. $values['ga_medium'] .'" name="ga_medium" /><br />
						</div>
						<div class="trackedtweets_optionsblock">
							<h3>Tweet Format</h3>
							<textarea id="tweet_format" name="tweet_format">'. $values['tweet_format'] .'</textarea><br />
						</div>
						<div class="trackedtweets_optionsblock">
							<h3>General Settings</h3>
							<label for="twdefaultstate">'. __('Tweet by default: ') .'</label> 
							<input type="checkbox" id="twdefaultstate" name="twdefaultstate" '.$checkboxstate.' value="1" /><br /><br />
							<label for="shortener">'. __('Url Shortener: ') .'</label><br />
							<input type="radio" onclick="showDiv(\'bitlyoptions\')" id="shortener" name="shortener" value="bitly" '.$bitly_checked.'/> bit.ly<br />
							<div id="bitlyoptions" class="trackedtweets_hide">
							<label for="bitly_username">'. __('bit.ly username:') .'</label><br />
							<input id="bitly_username" type="text" value="'. $values['bitly_username'] .'" name="bitly_username" /><br />
							<label for="bitly_api">'. __('bit.ly API key:') .'</label><br />
							<input id="bitly_api" type="text" value="'. $values['bitly_api'] .'" name="bitly_api" /><br />
							</div>
							<input type="radio" onclick="hideDiv(\'bitlyoptions\')" id="shortener" name="shortener" value="tinyurl" '.$tinyurl_checked.'/> TinyURL<br />
							<input type="radio" onclick="hideDiv(\'bitlyoptions\')" id="shortener" name="shortener" value="trim" '.$trim_checked.'/> tr.im<br />
							'.$revcanonicaloption.'
						</div>
						<input style="float:right" id="savetrackedtweets" class="button-primary" type="submit" value="Save settings" name="savesettings"/>
					</div>
					<div id="trackedtweets_rightcol">
						<h3>Google Analytics</h3>
						<p>There are three variables for Google Analytics:</p>
						<ul>
							<li><strong>Source:</strong> This is the referrer. I use "Twitter" in this field. </li>
							<li><strong>Medium:</strong> I use "Social" as the medium.</li>
							<li>The Post Title is set as the Google Analytics Campaign.</li>
						</ul>
						<h3>Tweet Format</h3>
						<p>There are four variables that can be used in the format field:</p>
						<ul>
							<li><strong>[url]:</strong> Inserts the URL for the post. The URL has the Google Analytics tracking embedded, and is passed through tinyurl. </li>
							<li><strong>[title]:</strong> Inserts the post title</li>
							<li><strong>[author]:</strong> Inserts the post author.</li>
							<li><strong>[extra]:</strong> Inserts extra text (post level) into the tweet. Use the "Tracked Tweets" section on the new post page.</li>
						</ul>
						<p>My preferred format is "New Post: [title] - [url] [extra]"</p>
						<h3>Support and Feedback</h3>
						<p>If you have any questions or feedback, please <a href="http://www.jackmcintyre.net/projects/tracked-tweets/" target="_blank">leave a comment</a>. To stay up to date with development, <a href="http://www.twitter.com/jackmcintyre">follow me</a> on Twitter.</p>
						<p>Please <a target="_blank" href="http://trackedtweets.uservoice.com/pages/16200-general">let me know</a> if you find bugs, or have any feature requests</p>
						<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4474012" target="_blank"><img src="https://www.paypal.com/en_AU/i/btn/btn_donate_LG.gif" border="0" style="float: right; margin-bottom: 14px;" ></a>
					</div>
					';
	$output .= '</div>';
	echo $output;
}
/**
Add settings link to plugins page
 */
function trackedtweets_filter_plugin_actions($links, $file){
	static $this_plugin;
	if( !$this_plugin ) $this_plugin = plugin_basename(__FILE__);
	if( $file == $this_plugin ){
		$settings_link = '<a href="options-general.php?page=tracked-tweets/trackedtweets.php">' . __('Settings') . '</a>';
		$links = array_merge( array($settings_link), $links); // before other links
	}
	return $links;
}
add_filter('plugin_action_links', 'trackedtweets_filter_plugin_actions', 10, 2);
/**
Add section to post page
 */
$new_meta_boxes =
array(
        "trackedtweets_extra" => array("type" => "text","name" => "_trackedtweets_extra","std" => "","description" => "Extra text for the tweet (such as hashtags) can be entered here."),
        "trackedtweets_tweetthis" => array("type" => "checkbox","name" => "_trackedtweets_tweetthis","std" => "checked","description" => "Tweet this post?"));
function new_meta_boxes() {
	global $post, $new_meta_boxes;
	$values = get_option('trackedtweets');
	foreach($new_meta_boxes as $meta_box) {
		if($meta_box['type'] == "text"){
			$meta_box_value = get_post_meta($post>ID, $meta_box['name'].'_value', true);
		} if($meta_box['type'] == "checkbox") {
			if ($values['twdefaultstate'] === "1"){   
				$checkboxstate = ' checked="checked" ';
			} else {}
		}
		if($meta_box_value == ""){
			$meta_box_value = $meta_box['std'];
			echo'<input type="hidden" name="'.$meta_box['name'].'_noncename" id="'.$meta_box['name'].'_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
			echo'<p style="margin-left:0px";><label for="'.$meta_box['name'].'_value">'.$meta_box['description'].'</label></p>';
			echo'<input type="'.$meta_box['type'].'" name="'.$meta_box['name'].'_value" size="55" '.$checkboxstate.' /><br />';
		}
	}
}
function create_meta_box() {
	global $theme_name;
		if ( function_exists('add_meta_box') ) {
			add_meta_box( 'new-meta-boxes', 'Tracked Tweets', 'new_meta_boxes', 'post', 'normal', 'high' );
		}
}
function save_postdata( $post_id ) {
	global $post, $new_meta_boxes;
	foreach($new_meta_boxes as $meta_box) {
		// Verify
		if ( !wp_verify_nonce( $_POST[$meta_box['name'].'_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id )){
				return $post_id;
			}
		} 
		else {
			if ( !current_user_can( 'edit_post', $post_id )){
				return $post_id;
			}
		}
		$data = $_POST[$meta_box['name'].'_value'];
		if(get_post_meta($post_id, $meta_box['name'].'_value') == ""){
			add_post_meta($post_id, $meta_box['name'].'_value', $data, true);
		}elseif($data != get_post_meta($post_id, $meta_box['name'].'_value', true)){
			update_post_meta($post_id, $meta_box['name'].'_value', $data);
		}elseif($data == ""){
			delete_post_meta($post_id, $meta_box['name'].'_value', get_post_meta($post_id, $meta_box['name'].'_value', true));
		}
	}
}
add_action('admin_menu', 'create_meta_box');
add_action('save_post', 'save_postdata');
?>