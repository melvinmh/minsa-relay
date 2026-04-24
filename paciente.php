<?php
// ============================================================
//  RELAY paciente.php — Desplegado en Render.com
//  Lee la cookie desde variable de entorno (seguro)
// ============================================================
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!isset($_GET['dni'])) {
    echo json_encode(["status" => "error", "message" => "DNI no proporcionado"], JSON_UNESCAPED_UNICODE);
    exit;
}

$dni = preg_replace('/[^0-9]/', '', $_GET['dni']); // Solo dígitos
if (strlen($dni) < 7 || strlen($dni) > 12) {
    echo json_encode(["status" => "error", "message" => "DNI con formato inválido"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ✅ Cookie leída desde variable de entorno (NO hardcodeada)
//    Configúrala en Render → Environment → MINSA_COOKIE
$cookie = getenv('MINSA_COOKIE') ?: '';

if (empty($cookie)) {
    echo json_encode(["status" => "error", "message" => "Cookie no configurada en el servidor relay. Configura la variable MINSA_COOKIE en Render."], JSON_UNESCAPED_UNICODE);
    exit;
}

$url = "https://websalud.minsa.gob.pe/hisminsa/his/paciente";

$headers = [
    "Accept: */*",
    "Accept-Language: es-419,es;q=0.7",
    "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
    "Origin: https://websalud.minsa.gob.pe",
    "Referer: https://websalud.minsa.gob.pe/hisminsa/",
    "Sec-Fetch-Dest: empty",
    "Sec-Fetch-Mode: cors",
    "Sec-Fetch-Site: same-origin",
    "X-Requested-With: XMLHttpRequest",
    "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36",
    "Cookie: $cookie"
];

$postfields = "C=PACIENTE&S=INFOGETBYIDSINUPD&idtipodoc=1&numdoc=" . urlencode($dni);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postfields,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_ENCODING       => "",
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_CONNECTTIMEOUT => 10,
]);

$response = curl_exec($ch);
$error    = curl_error($ch);
$errno    = curl_errno($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error || $errno) {
    echo json_encode(["status" => "error", "message" => "cURL error ($errno): $error"], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($httpCode !== 200) {
    $snippet = mb_substr($response, 0, 300);
    echo json_encode([
        "status"      => "error",
        "message"     => "MINSA respondió HTTP $httpCode. Cookie posiblemente expirada.",
        "http_code"   => $httpCode,
        "raw_snippet" => $snippet
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$encoding = mb_detect_encoding($response, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
if ($encoding !== 'UTF-8') {
    $response = mb_convert_encoding($response, 'UTF-8', $encoding);
}

$json = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo $response;
}
?>
