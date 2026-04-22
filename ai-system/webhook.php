<?php
include "config.php";
include "ai.php";
include "functions.php";

// VERIFY
if($_SERVER['REQUEST_METHOD']==='GET'){
    if($_GET['hub_verify_token']==$VERIFY_TOKEN){
        echo $_GET['hub_challenge'];
    }
    exit;
}

// RECEIVE DATA
$data = json_decode(file_get_contents("php://input"),true);

foreach($data['entry'] as $entry){
    foreach($entry['changes'] as $change){

        $val = $change['value'];

        $comment = $val['text'] ?? $val['comment_text'] ?? '';
        $comment_id = $val['id'] ?? $val['comment_id'] ?? '';
        $user = $val['from']['name'] ?? '';

        if(!$comment) continue;

        // Ignore own comments
        if(isset($val['from']['id']) && $val['from']['id']=="$PAGE_ID"){
            continue;
        }

        // SPAM FILTER
        if(preg_match('/http|crypto|bitcoin/i',$comment)){
            continue;
        }

        // SAVE
        $stmt = $conn->prepare("INSERT INTO comments(platform,comment_id,user_name,comment) VALUES('fb_ig',?,?,?)");
        $stmt->bind_param("sss",$comment_id,$user,$comment);
        $stmt->execute();

        // DELAY (avoid ban)
        sleep(rand(5,15));

        // AI
        $reply = generateReply($comment);

        if($reply){
            sendReply($comment_id,$reply);

            $stmt = $conn->prepare("UPDATE comments SET reply=?,status='replied' WHERE comment_id=?");
            $stmt->bind_param("ss",$reply,$comment_id);
            $stmt->execute();
        }
    }
}

http_response_code(200);