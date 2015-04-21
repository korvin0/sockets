<?php
$ch = curl_init();
for ($i = 0; $i < 3; $i += 1) {
    curl_setopt_array($ch, array(
        CURLOPT_URL => "http://127.0.0.1:10000/",
        CURLOPT_VERBOSE => True,
        CURLOPT_RETURNTRANSFER => True,
        CURLOPT_FORBID_REUSE => false
    ));
    $resp = curl_exec($ch);
}
curl_close($ch);
?>