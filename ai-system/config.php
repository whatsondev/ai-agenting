<?php


$VERIFY_TOKEN = "verify123";
$PAGE_ACCESS_TOKEN = "EAAbMBWIUKjsBRXQS3aoTfHwVctb3j7qydZB9WhajswiTx1SKSbuZBNo5m2gyMrRggPZCt27hd4ZC1PZAO5OzbSKK6fBsYU9XykPRpRnb9KHgWjZBMHiNQvAzF2CtUz2Hsv1rj9GcVfd1a2qeDCdXgM9940fYnyvxLLGSkj2oJmQQIQcXaQJ8AQCxfkMgFFGmxfLrIvyKsZD";
$OPENAI_KEY = "YOUR_OPENAI_KEY";
$PAGE_ID = "281282521900932";


$conn = new mysqli(
    "localhost",
    "root",
    "",
    "aiagenting"
);

if ($conn->connect_error) {
    die("DB Error");
}
?>