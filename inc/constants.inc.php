<?php
// used to remove redundant data
define("MEDIA_STRING", "<media omitted>");

$TIMESTAMP_DELIMITER = ['-', ':'];

$IGNORE_STRINGS = [
    "Messages you send to this group are now secured with end-to-end encryption",
];

// We expect chat messages to have three parts: Timestamp, User (optional) and Message
// This regex needs to define these three groups, please use named groups (t,u,m) as in the sample regex
// It is important, that the regex can deal with strings where the user is not present, as well as with 
// usernames containing (parts) of the message delimeters
// Example: /^(?<t>[\d\s,\/:.-]+):\s((?<u>.*?):\s)?(?<m>.+)$/
$MESSAGE_FORMAT = "/^(?P<t>[\d\s,\/:.-]+(am|pm|AM|PM){0,1}):\s((?P<u>.*?):\s)?(?P<m>.+)$/";
