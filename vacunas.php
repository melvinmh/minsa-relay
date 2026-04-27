<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$idpaciente = preg_replace('/[^0-9]/', '', $_GET['idpaciente'] ?? '');
if (empty($idpaciente)) { echo json_encode(['error' => 'Falta idpaciente']); exit; }

$cookie = getenv('MINSA_COOKIE') ?: '';
$apiKey = getenv('ZENROWS_API_KEY') ?: '';

if (empty($cookie)) { echo json_encode(['error' => 'MINSA_COOKIE no configurada']); exit; }

$params = http_build_query([
    '_dc' => time().'000', 'confighistorialpacid' => 1,
    'idtipodoc' => 1, 'numdoc' => $idpaciente, 'idpersona' => $idpaciente,
    'paciente' => '', 'fecnacimiento' => '', 'edadactual' => '',
    'C' => 'HISTORIALATENCIONES', 'S' => 'SEARCH',
    'page' => 1, 'start' => 0, 'limit' => 50
]);
$targetUrl = "https://websalud.minsa.gob.pe/hisminsa/his/his?" . $params;

if (!empty($apiKey)) {
    $requestUrl = "https://api.zenrows.com/v1/"
        . "?apikey="        . urlencode($apiKey)
        . "&url="           . urlencode($targetUrl)
        . "&antibot=true"
        . "&premium_proxy=true"
        . "&custom_headers=true";
} else {
    $requestUrl = $targetUrl;
}

$ch = curl_init($requestUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        "Cookie: $cookie",
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
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
$errno    = curl_errno($ch);
curl_close($ch);

if ($error || $errno) { echo json_encode(['error' => "cURL ($errno): $error"]); exit; }

if ($httpCode !== 200) {
    echo json_encode([
        'error'   => "HTTP $httpCode" . (!empty($apiKey) ? " vía ZenRows" : " directo"),
        'snippet' => mb_substr($response, 0, 500)
    ], JSON_UNESCAPED_UNICODE); exit;
}

$encoding = mb_detect_encoding($response, ['UTF-8','ISO-8859-1','Windows-1252'], true);
if ($encoding !== 'UTF-8') $response = mb_convert_encoding($response, 'UTF-8', $encoding);

$json = json_decode($response, true);
echo json_encode(
    json_last_error() === JSON_ERROR_NONE ? $json : ['raw' => $response],
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
?>
