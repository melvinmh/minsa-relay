<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$idpaciente = preg_replace('/[^0-9]/', '', $_GET['idpaciente'] ?? '');
if (empty($idpaciente)) {
    echo json_encode(['error' => 'Falta idpaciente']);
    exit;
}

$cookie = getenv('MINSA_COOKIE') ?: '';
$apiKey = getenv('SCRAPER_API_KEY') ?: '';

if (empty($cookie)) {
    echo json_encode(['error' => 'MINSA_COOKIE no configurada']);
    exit;
}

$params = http_build_query([
    '_dc' => time().'000', 'confighistorialpacid' => 1,
    'idtipodoc' => 1, 'numdoc' => $idpaciente, 'idpersona' => $idpaciente,
    'paciente' => '', 'fecnacimiento' => '', 'edadactual' => '',
    'C' => 'HISTORIALATENCIONES', 'S' => 'SEARCH',
    'page' => 1, 'start' => 0, 'limit' => 50
]);
$targetUrl = "https://websalud.minsa.gob.pe/hisminsa/his/his?" . $params;

if (!empty($apiKey)) {
    $ch = curl_init("http://api.scraperapi.com/");
    $payload = json_encode([
        "apiKey"  => $apiKey,
        "url"     => $targetUrl,
        "method"  => "GET",
        "headers" => [
            "Cookie"          => $cookie,
            "Referer"         => "https://websalud.minsa.gob.pe/hisminsa/",
            "X-Requested-With"=> "XMLHttpRequest",
            "User-Agent"      => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
        ]
    ]);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
} else {
    $ch = curl_init($targetUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            "Cookie: $cookie",
            "Referer: https://websalud.minsa.gob.pe/hisminsa/",
            "X-Requested-With: XMLHttpRequest",
            "User-Agent: Mozilla/5.0"
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

if ($error) { echo json_encode(['error' => $error]); exit; }
if ($httpCode !== 200) {
    echo json_encode(['error' => "HTTP $httpCode", 'snippet' => mb_substr($response, 0, 300)]);
    exit;
}

$encoding = mb_detect_encoding($response, ['UTF-8','ISO-8859-1','Windows-1252'], true);
if ($encoding !== 'UTF-8') $response = mb_convert_encoding($response, 'UTF-8', $encoding);

$json = json_decode($response, true);
echo json_encode(
    json_last_error() === JSON_ERROR_NONE ? $json : ['raw' => $response],
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
?>
