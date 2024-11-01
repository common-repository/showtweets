<?php
/*
Plugin Name: showTweets
Plugin URI: http://www.dynamicshark.com
Description: Display your tweets on your WordPress blog in a minimalist way
Author: DynamicShark Media
Version: 0.2
Author URI: http://www.dynamicshark.com
*/

$plugin_name = 'showTweets';
$plugin_prefix = 'showTweets_';
$plugin_ver = '0.2';


function convertLinks($text, $convert) 
{
	if($convert == 'y') 
	{
		$text = eregi_replace('(((f|ht){1}tp://)[-a-zA-Z0-9@:%_\+.~#?&//=]+)','<a href="\\1">\\1</a>', $text);
		$text = eregi_replace('([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_\+.~#?&//=]+)','\\1<a href="http://\\2">\\2</a>', $text);
		return $text;
	} else 
	{
		return $text;
	}
}
function timePassed($date) {
	// Used to find difference between two dates
	$periods = array("second", "minute", "hour", "day");
	$lengths = array("60", "60", "24", "7");
	
	$now = time();
	if($now > $date) {
		$diff = $now - $date;
		$tense = "ago";
	} else {
		$diff = $date - $now;
		$tense = "from now";
	}
	
	for ($j=0; $diff >= $lengths[$j] && $j < count($lengths)-1; $j++) {
		$diff /= $lengths[$j];
	}
	$diff = round($diff);
	
	if($diff != 1) {
		$periods[$j].= "s";
	}
	return $diff . " ". $periods[$j] . " {$tense}. <br />";
}

function getTweets() 
{
	global $plugin_prefix;
	
	$usernames = get_option($plugin_prefix . 'usernames');
	$count = get_option($plugin_prefix . 'count');
	$convert = get_option($plugin_prefix . 'convert');

	$results = array();
	$PASS = true;
	$usernames = explode(",", $usernames);		// Names are stored via comma delimited
	
	foreach($usernames as $username) 
	{
		// Retrieve last 5 tweets from a particular Twitter account
		$URL = 'http://twitter.com/statuses/user_timeline/'. $username .'.xml?count='. $count;
		$today = time();
		$PASS = true;

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $URL);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$src = curl_exec($curl);

		curl_close($curl);

		$data = simplexml_load_string($src);
		
		if($data->error != '') 
		{
			$PASS = false;
			echo " Too Many Requests - Try Again Later ";
			break;
		}

		$statuses = $data->status;
		
		foreach($statuses as $status) 
		{
			$text = $status->text;
			$time = strtotime($status->created_at);
			$results[$time] = $text .'||'.$username.'||'.$status->id;		//TACK on USERNAME & ID
		}
	}
	if($PASS) {
		krsort($results);										//SORT BY TIMESTAMP
		$results = array_slice($results, 0, $count, true);		//LIMIT ARRAY TO $COUNT LENGTH
		$data = '';
		foreach($results as $time => $tweet) {
			list($tweet, $username, $id) = explode('||', $tweet);		
			$when = timePassed($time);
			$data .= '<li>"'. convertLinks($tweet, $convert) . '" <a href="http://twitter.com/'. $username .'/statuses/'. $id.'/" style="font-size: 85%">@'. $username .' - '. $when.'</a></li>';
			
		}
		echo $data;
	}
}

function options_subpanel()
{
	global $plugin_name;
	global $plugin_ver;
	global $plugin_prefix;

  	if (isset($_POST['info_update'])) 
	{
		if (isset($_POST['usernames'])) {
			$usernames = trim($_POST['usernames']);
		} else {
			$usernames = '';
		}

		if (isset($_POST['count'])) {
			$count = trim($_POST['count']);
		} else {
			$count = '';
		}
		$convert = $_POST['convert'];

		update_option($plugin_prefix . 'usernames', $usernames);
		update_option($plugin_prefix . 'count', $count);
		update_option($plugin_prefix . 'convert', $convert);

	} 

	$usernames = get_option($plugin_prefix . 'usernames');
	$count = get_option($plugin_prefix . 'count');
	$convert = get_option($plugin_prefix . 'convert');

	echo '<div class=wrap><form method="post">';
	echo '<h2>' . $plugin_name . ' Options</h2>';
	echo "<small>Version: {$plugin_ver}</small><br />";

	?>	
	<style>
	#usernames {
		width: 200px;
		}
	#count { 
		width: 30px;
		text-align: right;
		}
	#convert {
		width: 60px;
		}
	</style>
	<p>showTweets is a plugin that will display your Tweet Statuses on your WordPress blog. You can also configure it to show more than 1 account.</p>
	<p>showTweets is returned as list items (<strong>&lt;li&gt;</strong>{tweet}<strong>&lt;/li&gt;</strong>). Please wrap around either Unordered/Ordered list tags</p>
	<p><cite>Sample output:</cite> &lt;li&gt;"Hey this is cool, I see my tweet" - about 3 hours ago.&lt;/li&gt;</p>
	<h3 style="text-decoration: underline;">General Options</h3>

	<p><strong><cite>Twitter Username(s)</cite></strong> - Your Twitter account username(s). You can display multiple twitter acounts by separating usernames with commas.</p>
	<p><strong><cite>Count</cite></strong> - How many tweets to retrieve from each account and how many to display at once.</p>
	<p><strong><cite>Convert Links/URLs</cite></strong> - Make Links or URL's clickable (yes/no)</p>
	<hr />
	<table class="form-table" cols="2">
		<tr><th>Twitter Username(s)</th>
		<td><input type="text" id="usernames" name="usernames" value="<?php echo($usernames); ?>" /></td></tr>
		<tr><th>Count</th>
		<td><input type="text" id="count" name="count" value="<?php echo($count); ?>" /></td></tr>
		<tr><th>Convert Links/URLs</th>
		<td><select id="convert" name="convert">
				<option value="y" <?php if(isset($convert) && $convert == 'y') echo 'selected="selected"';?>>Yes </option>
				<option value="n" <?php if(isset($convert) && $convert == 'n') echo 'selected="selected"';?>>No </option>
			</select></td></tr>
	</table>
	<div class="submit"><input type="submit" name="info_update" value="Update Options" /></div>
	</form>

	<small>If you like this plugin, please consider donating: </small> <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="11175264">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
		</form>

	</form>
	<p style="font-size: 8pt;"><strong>showTweets</strong> was created and maintained by Tyler Ingram of <a href="http://www.dynamicshark.com">DynamicShark Media</a> - 
		<a href="http://www.twitter.com/TylerIngram">Follow Me Twitter Too!</a></p>

	<?php
	echo('</div>');
}

function add_plugin_option() {
	global $plugin_name;
	if (function_exists('add_options_page')) {
		add_options_page($plugin_name, $plugin_name, 0, basename(__FILE__), 'options_subpanel');
    }	
}

add_action('admin_menu', 'add_plugin_option');

?>
