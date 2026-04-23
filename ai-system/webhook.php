<?php

include "config.php";
include "ai.php";
include "functions.php";

/*
|--------------------------------------------------------------------------
| WEBHOOK VERIFY
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $verify_token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($verify_token === $VERIFY_TOKEN) {
        echo $challenge;
    } else {
        http_response_code(403);
        echo "Invalid Verify Token";
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| RECEIVE WEBHOOK DATA
|--------------------------------------------------------------------------
*/

$raw = file_get_contents("php://input");

file_put_contents(
    __DIR__ . "/logs/webhook_log.txt",
    date("Y-m-d H:i:s") . PHP_EOL .
    $raw . PHP_EOL . PHP_EOL,
    FILE_APPEND
);

$data = json_decode($raw, true);

if (!$data || !isset($data['entry'])) {
    http_response_code(200);
    exit;
}

/*
|--------------------------------------------------------------------------
| PROCESS EVENTS
|--------------------------------------------------------------------------
*/

foreach ($data['entry'] as $entry) {

    if (!isset($entry['changes'])) {
        continue;
    }

    foreach ($entry['changes'] as $change) {

        /*
        |--------------------------------------------------------------------------
        | ONLY HANDLE COMMENT EVENTS
        |--------------------------------------------------------------------------
        */

        if (($change['field'] ?? '') !== 'feed') {
            continue;
        }

        $val = $change['value'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | ONLY NEW COMMENTS
        |--------------------------------------------------------------------------
        */

        if (($val['verb'] ?? '') !== 'add') {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | GET COMMENT DATA
        |--------------------------------------------------------------------------
        */

        $comment_id = $val['comment_id'] ?? '';
        $comment = trim($val['message'] ?? '');
        $post_id = $val['post_id'] ?? '';

        $user_name = $val['from']['name'] ?? 'Unknown';
        $user_id = $val['from']['id'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if (empty($comment_id) || empty($comment)) {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | IGNORE OWN COMMENTS
        |--------------------------------------------------------------------------
        */

        if ($user_id == $PAGE_ID) {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | BASIC SPAM FILTER
        |--------------------------------------------------------------------------
        */

        if (preg_match('/http|crypto|bitcoin|forex|investment/i', $comment)) {

            file_put_contents(
                __DIR__ . "/logs/spam_log.txt",
                "[" . date("Y-m-d H:i:s") . "] " . $comment . PHP_EOL,
                FILE_APPEND
            );

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | AVOID DUPLICATE REPLY
        |--------------------------------------------------------------------------
        */

        $check = $conn->prepare("SELECT id FROM comments WHERE comment_id=? LIMIT 1");
        $check->bind_param("s", $comment_id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | SAVE COMMENT
        |--------------------------------------------------------------------------
        */

        $platform = "facebook";

        $stmt = $conn->prepare("
            INSERT INTO comments 
            (platform, comment_id, user_name, comment, status, created_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");

        $stmt->bind_param(
            "ssss",
            $platform,
            $comment_id,
            $user_name,
            $comment
        );

        $stmt->execute();

        /*
        |--------------------------------------------------------------------------
        | GENERATE AI REPLY
        |--------------------------------------------------------------------------
        */

        $reply = generateReply($comment);

        if (!$reply) {

            file_put_contents(
                __DIR__ . "/logs/ai_error_log.txt",
                "[" . date("Y-m-d H:i:s") . "] Failed AI reply for: " . $comment . PHP_EOL,
                FILE_APPEND
            );

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | SEND REPLY
        |--------------------------------------------------------------------------
        */

        $response = sendReply($comment_id, $reply);

        /*
        |--------------------------------------------------------------------------
        | SAVE RESPONSE STATUS
        |--------------------------------------------------------------------------
        */

        $status = "failed";

        if (!empty($response['id'])) {
            $status = "replied";
        }

        $update = $conn->prepare("
            UPDATE comments 
            SET reply=?, status=?, replied_at=NOW()
            WHERE comment_id=?
        ");

        $update->bind_param(
            "sss",
            $reply,
            $status,
            $comment_id
        );

        $update->execute();

        /*
        |--------------------------------------------------------------------------
        | SAVE API RESPONSE LOG
        |--------------------------------------------------------------------------
        */

        file_put_contents(
            __DIR__ . "/logs/reply_log.txt",
            "[" . date("Y-m-d H:i:s") . "]" . PHP_EOL .
            "Comment ID: " . $comment_id . PHP_EOL .
            "User: " . $user_name . PHP_EOL .
            "Comment: " . $comment . PHP_EOL .
            "Reply: " . $reply . PHP_EOL .
            "Response: " . json_encode($response) . PHP_EOL .
            "-----------------------------------" . PHP_EOL,
            FILE_APPEND
        );
    }
}

/*
|--------------------------------------------------------------------------
| RESPONSE TO META
|--------------------------------------------------------------------------
*/

http_response_code(200);
echo "EVENT_RECEIVED";