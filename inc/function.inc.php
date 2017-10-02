<?php
require_once 'constants.inc.php';


/**
 * main functionality function
 * @param  [string] $filename 
 * @return [array]
 */
function parseChatFile($filename){
    $error_flag = false;
    $errors = array();
    $chat = array();
    $names_array = array();

    $chat_file_path = 'conversations/' . $filename;

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

                if(strtolower($text_attribute) == MEDIA_STRING)
                    $text_attribute = null;
                else
                    $text_attribute = htmlspecialchars($text_attribute);

                $chat_block = array(
                    'i' => $user_index,
                    'p' => $text_attribute,
                    't' => $time_attribute
                );

                array_push($chat, $chat_block);
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
	global  $MESSAGE_DELIMITER;
	$TIMESTAMP_DELIMITER_POS = strpos($chat_string, $MESSAGE_DELIMITER['timestamp']);
	$USER_DELIMITER_POS = @strpos($chat_string, $MESSAGE_DELIMITER['user'], $TIMESTAMP_DELIMITER_POS+strlen($MESSAGE_DELIMITER['timestamp']));
	$chat_array = array();
	$chat_array['timestamp'] = substr($chat_string, 0, $TIMESTAMP_DELIMITER_POS);
	if($USER_DELIMITER_POS && $USER_DELIMITER_POS < $TIMESTAMP_DELIMITER_POS + 50) {
		// user found in string
		$chat_array['user'] = substr($chat_string, $TIMESTAMP_DELIMITER_POS+strlen($MESSAGE_DELIMITER['timestamp']), $USER_DELIMITER_POS-$TIMESTAMP_DELIMITER_POS-strlen($MESSAGE_DELIMITER['timestamp']));
		$MESSAGE_START_POS = $USER_DELIMITER_POS + +strlen($MESSAGE_DELIMITER['user']);
	} else {
		$MESSAGE_START_POS = $TIMESTAMP_DELIMITER_POS + +strlen($MESSAGE_DELIMITER['timestamp']);
	}
	$chat_array['message'] = substr($chat_string, $MESSAGE_START_POS);
	
	if (!($TIMESTAMP_DELIMITER_POS && strlen($chat_array['timestamp']) > 0 && strlen($chat_array['message']) > 0)) {
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
