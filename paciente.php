<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

if (!isset($_GET['dni'])) {
    echo json_encode(["status" => "error", "message" => "DNI no proporcionado"]);
    exit;
}

$dni    = preg_replace('/[^0-9]/', '', $_GET['dni']);
$cookie = getenv('MINSA_COOKIE') ?: '';
$apiKey = getenv('ZENROWS_API_KEY') ?: '';  // ← nueva variable

if (empty($cookie)) {
    echo json_encode(["status" => "error", "message" => "MINSA_COOKIE no configurada"]);
    exit;
}

$targetUrl  = "https://websalud.minsa.gob.pe/hisminsa/his/paciente";
$postfields = "C=PACIENTE&S=INFOGETBYIDSINUPD&idtipodoc=1&numdoc=" . urlencode($dni);

// ✅ ZenRows: POST a su endpoint pasando url destino como parámetro
if (!empty($apiKey)) {
    $requestUrl = "https://api.zenrows.com/v1/"
        . "?apikey="        . urlencode($apiKey)
        . "&url="           . urlencode($targetUrl)
        . "&antibot=true"
        . "&premium_proxy=true"
        . "&custom_headers=true";
} else {
    $requestUrl = $targetUrl; // fallback directo
}

$ch = curl_init($requestUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postfields,
    CURLOPT_HTTPHEADER     => [
        "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
        "Cookie: $cookie",
        "Origin: https://websalud.minsa.gob.pe",
        "Referer: https://websalud.minsa.gob.pe/hisminsa/",
        "X-Requested-With: XMLHttpRequest",
        "Accept: */*",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_CONNECTTIMEOUT => 20,
]);

$response = curl_exec($ch);
$error    = curl_error($ch);
$errno    = curl_errno($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error || $errno) {
    echo json_encode(["status" => "error", "message" => "cURL ($errno): $error"]);
    exit;
}

if ($httpCode !== 200) {
    echo json_encode([
        "status"    => "error",
        "message"   => "HTTP $httpCode" . (!empty($apiKey) ? " vía ZenRows" : " directo"),
        "http_code" => $httpCode,
        "snippet"   => mb_substr($response, 0, 500)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$encoding = mb_detect_encoding($response, ['UTF-8','ISO-8859-1','Windows-1252'], true);
if ($encoding !== 'UTF-8') $response = mb_convert_encoding($response, 'UTF-8', $encoding);

$json = json_decode($response, true);
echo json_encode(
    json_last_error() === JSON_ERROR_NONE ? $json : ["raw" => $response],
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
?>
