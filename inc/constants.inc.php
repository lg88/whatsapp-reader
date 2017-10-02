<?php
// used to remove redundant data
define("MEDIA_STRING", "<media omitted>");

$TIMESTAMP_DELIMITER = ['-', ':'];

$IGNORE_STRINGS = [
    "Messages you send to this group are now secured with end-to-end encryption",
];

// We expect chat messages to have three parts: Timestamp, User (optional) and Message - in exactly this order
// two delimiters seperate timestamp from user and user from message
$MESSAGE_DELIMITER = array (
        'timestamp' => ': ',
        'user' => ': '
);

