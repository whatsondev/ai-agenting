<?php
include "config.php";

function sendReply($comment_id, $message){
    global $PAGE_ACCESS_TOKEN;

    $url = "https://graph.facebook.com/v18.0/$comment_id/comments";

    $data = [
        "message"=>$message,
        "access_token"=>$PAGE_ACCESS_TOKEN
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST,true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    curl_exec($ch);
    curl_close($ch);
}