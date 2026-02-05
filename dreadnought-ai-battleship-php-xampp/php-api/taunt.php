<?php
require __DIR__ . DIRECTORY_SEPARATOR . "_common.php";

$body = read_json_body();
$event = $body["event"] ?? "";

$prompt = "The player just did: " . $event . "\nAs Admiral Obsidian, give a 1-sentence reaction.";
$sys = "You are Admiral Obsidian, a superior naval AI. You are witty, intimidating, and hate losing.";

$result = gemini_generate_content($GEMINI_MODEL_TAUNT, $GEMINI_API_KEY, $sys, $prompt, null);

if (!$result["ok"]) {
  json_response(["error" => "Gemini API error", "details" => $result["error"]], 502);
}

$data = $result["data"];
$text = $data["candidates"][0]["content"]["parts"][0]["text"] ?? "";
if (!$text) $text = "Intriguing move.";

json_response(["text" => $text]);
