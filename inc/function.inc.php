<?php
require_once 'constants.inc.php';


/**
 * main functionality function
 * @param  [string] $filename
 * @return [array]
 */
function parseChatFile($filename, $localMediaPath = null){
	$error_flag = false;
	$errors = array();
	$chat = array();
	$names_array = array();

	$chat_file_path = 'conversations/' . $filename;

	ini_set("auto_detect_line_endings", true);
	if(!file_exists($chat_file_path)){
		addErrorMessage($errors, $error_flag, 'File 404<br>File is like a unicorn to our servers, file was not uploaded properly');
	} else {
		$file_handle = fopen($chat_file_path, "r+");

		if(!$file_handle){
			addErrorMessage($errors, $error_flag, 'Oh Snap!<br>Some technical glitch, it\'ll be resolved soon!');
		} else {
			$index = 0;

			while (($line = fgets($file_handle)) !== false){
				$chat_array = parseChatString($line);
					
				if(!$chat_array) {
					//skip invalid lines
					continue;
				}
				if ($chat_array['multiline'] === true && count($chat) > 0) {
					// this is part of a multiline message, so append to the last chat block
					$last_block = array_pop($chat);
					$last_block['p'] .= '<br />' . htmlspecialchars(trim($chat_array['message']));
					array_push($chat, $last_block);
					continue;
				} else {
					// new chat block
					if(!array_key_exists("timestamp", $chat_array)) {
											print_r($chat_array);
						
					}
					$converted_timestamp = getConvertedTimestamp($chat_array['timestamp']);
					if($converted_timestamp === false)
					$time_attribute = $chat_array['timestamp'];
					else
					$time_attribute = $converted_timestamp;

					if(array_key_exists('user', $chat_array)) {
						$user_attribute = trim($chat_array['user']);
						$user_index = getUserIndex($names_array, $user_attribute);
					} else {
						$user_attribute = false;
						$user_index = 999;
					}

					$text_attribute = trim($chat_array['message']);
					$media_attribute = null;
					if($localMediaPath) {
						preg_match('/^([0-9a-zA-Z-.\/]+) <.+>/', $text_attribute, $matches);
						if (count($matches) > 0) {
							$mediaSrc = $matches[1];
							$path_parts = pathinfo($mediaSrc);
							$mediaSrc = $localMediaPath.'/'.$mediaSrc;
							$uid = uniqid();
							switch(strtolower($path_parts['extension'])) {
								case "jpg":
								case "png":
								case "jpeg":
									$media_attribute = '<img src="'.$mediaSrc.'" />';
									break;
								case "mp4":
								case "ogg":
								case "mpg":
								case "avi":
									$media_attribute = '<div class="player" id="'.$uid.'" onClick="pause(\''.$uid.'\')"><img src="img/play_button.png" onClick="play(\''.$uid.'\')" /><video><source src="'.$mediaSrc.'">Your browser does not support the video tag.</video></player>';
									break;
								case "m4a":
								case "aac":
								case "mp3":
								case "opus":
									$media_attribute = '<audio controls><source src="'.$mediaSrc.'">Your browser does not support the audio tag.</audio>';
									break;

							}
							if ($media_attribute != null) {
								//$media_attribute = '<a target="_blank" href="'.$mediaSrc.'">'.$media_attribute.'</a>';
							} else {
								$media_attribute = "Unknown media type";
							}
						}
					}

					if($media_attribute) {
						$text_attribute = null;
					} else {
						$text_attribute = htmlspecialchars($text_attribute);
					}
					$chat_block = array(
                    'i' => $user_index,
                    'p' => $text_attribute,
                    't' => $time_attribute,
                	'm' => $media_attribute
					);

					array_push($chat, $chat_block);
				}
			}
			// close file handle
			fclose($file_handle);

			// delete file
			// yes, i respect privary
			//unlink($chat_file_path);
		}
	}

	if(sizeof($chat) == 0) {
		addErrorMessage($errors, $error_flag, 'It wasn\'t a valid text file or we were not able to convert it');
	}

	$final_response = array(
        'success'   => !$error_flag,
	);

	if($error_flag){
		$final_response['errors']   = $errors;
	} else {
		$final_response['chat']     = $chat;
		$final_response['users']    = $names_array;
	}

	return $final_response;
}

/**
 * Extract Timestamp, Message and User (if available) from message
 * @param  [string]     $chat_string
 * @return [array] ('timestamp', 'user', 'message')
 * 	       [boolean] false if chat_string invalid
 */
function parseChatString($chat_string){
	global $MESSAGE_FORMAT;
	preg_match($MESSAGE_FORMAT, $chat_string, $matches);
	
	if (count($matches) > 0) {
		// Regular message
		$chat_array['multiline'] = false;
		$chat_array['timestamp'] = $matches['t'];
		if (strlen($matches['u']) > 0) {
			$chat_array['user'] = $matches['u'];
		}
		$chat_array['message'] = $matches['m'];
	} else if (strlen(trim($chat_string)) > 0) {
		// This is probably part of a multi-line message, therefore has no timestamp / user attribute
		// set the multi-line flag and just take the whole line as message
		$chat_array['multiline'] = true;
		$chat_array['message'] = $chat_string;
	} else {
		$chat_array = false;
	}
	
	return $chat_array;
}

/**
 * function to get the timestamp from the messages
 * and check if they are continuation of previous messages
 * @param  [string]     $chat_string
 * @return [boolean]                    false when no time pattern is found
 *         [timestamp]                  timestamp when time pattern is successfully found
 */
function getConvertedTimestamp($chat_string){
	$pattern = "(?<time_hour>(\d)*)"
	. "(:)+(?<time_minute>(\d)*)"
	. "(?<time_type>AM|PM)?"
	. "(,)+( )*(?<date_day>(\d)*)"
	. "( )*(?<date_month>(\w)*)";
	$matches = array();

	if(preg_match("/^" . $pattern . "$/i", trim($chat_string), $matches) > 0) {
		$time_hour = floatval($matches['time_hour']);
		$time_minute = $matches['time_minute'];
		$time_type = $matches['time_type'];
		$date_day = $matches['date_day'];
		$date_month = $matches['date_month'];
	} else {
		$pattern = "(?<date_year>(\d)*)"
		. "(\/)+(?<date_month>(\d)*)"
		. "(\/)+(?<date_day>(\d)*)"
		. "(,)+( )*(?<time_hour>(\d)*)"
		. "(:)*(?<time_minute>(\d)*)"
		. "( )*(?<time_type>AM|PM)*";
		$matches = array();
		if(preg_match("/^" . $pattern . "$/i", trim($chat_string), $matches) > 0) {
			$time_hour = intval($matches['time_hour']);

			if(!isset($matches['time_type'])){
				if($time_hour >= 12)
				$time_type = "PM";
				else
				$time_type = "AM";
			} else {
				$time_type = $matches['time_type'];
			}

			if($time_hour > 12)
			$time_hour = $time_hour - 12;

			$time_minute = $matches['time_minute'];
			$date_year = intval($matches['date_year']);
			$date_day = $matches['date_day'];
			$date_month = $matches['date_month'];
		} else {
			// case where time pattern was not found
			return false;
		}
	}

	if(isset($date_year))
	$timestamp = $date_year;
	else
	$timestamp = date('Y', time());

	$timestamp .= '-' . $date_month . '-' . $date_day . " " . $time_hour . ':' . $time_minute . ' ' . $time_type;
	return strtotime($timestamp);
}


/**
 * function to track all the users involved in the chat
 * @param  [array]  $users_array
 * @param  [string] $user_name
 * @return [integer]                index of the user passed in parameters
 */
function getUserIndex(&$users_array, $user_name){
	if(!in_array($user_name, $users_array)){
		array_push($users_array, $user_name);
	}

	$user_index = array_search($user_name, $users_array);
	return $user_index;
}


/**
 * function to neatly add error messages to error messages array
 * @param [array]   $error_messages_array
 * @param [boolean] $error_flag
 * @param [string]  $error_message
 */
function addErrorMessage(&$error_messages_array, &$error_flag, $error_message){
	array_push($error_messages_array, $error_message);
	$error_flag = true;
}


/**
 * function to get the URL of any page
 * @param  [boolean]    $mode   if mode is true, it removes the part after last slash and returns with trailing slash
 *                              if mode is false, it returns complete URL
 * @return [string]
 */
function getCurrentURL($mode=false){
	$url_name = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	if($mode){
		$url_array = explode('/', $url_name);
		$last_index = sizeof($url_array) - 1;

		unset($url_array[$last_index]);

		$url_name = implode('/', $url_array);
		return $url_name . '/';
	} else {
		return $url_name;
	}
}
