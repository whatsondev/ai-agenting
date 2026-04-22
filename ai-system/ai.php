<?php
include "config.php";

function generateReply($comment) {

    global $conn, $OPENAI_KEY;

    // TRAINING MATCH
    $res = $conn->query("SELECT * FROM training_data");

    while($row = $res->fetch_assoc()){
        if (stripos($comment, $row['keyword']) !== false) {
            return $row['response'];
        }
    }

    // AI RESPONSE
    $prompt = "You are managing WhatsOnUK, a UK magazine.

Comment: $comment

Reply professionally, engaging, max 2 lines.";

    $data = [
        "model"=>"gpt-4o-mini",
        "messages"=>[
            ["role"=>"user","content"=>$prompt]
        ]
    ];

    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $OPENAI_KEY",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($data));

    $res = json_decode(curl_exec($ch),true);
    curl_close($ch);

    return $res['choices'][0]['message']['content'] ?? '';
}