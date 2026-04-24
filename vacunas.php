<?php
// ============================================================
//  RELAY vacunas.php — Desplegado en Render.com
//  Lee la cookie desde variable de entorno (seguro)
// ============================================================
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$idpaciente = $_GET['idpaciente'] ?? '';
$idpaciente = preg_replace('/[^0-9]/', '', $idpaciente);

if (empty($idpaciente)) {
    echo json_encode(['error' => 'Falta el parámetro idpaciente'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ✅ Cookie desde variable de entorno MINSA_COOKIE
$cookie = getenv('MINSA_COOKIE') ?: '';

if (empty($cookie)) {
    echo json_encode(['error' => 'Cookie no configurada. Configura MINSA_COOKIE en Render.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$url = "https://websalud.minsa.gob.pe/hisminsa/his/his";

$params = [
    '_dc'                  => time() . '000',
    'confighistorialpacid' => 1,
    'idtipodoc'            => 1,
    'numdoc'               => $idpaciente,
    'idpersona'            => $idpaciente,
    'paciente'             => '',
    'fecnacimiento'        => '',
    'edadactual'           => '',
    'C'                    => 'HISTORIALATENCIONES',
    'S'                    => 'SEARCH',
    'page'                 => 1,
    'start'                => 0,
    'limit'                => 50
];

$fullUrl = $url . '?' . http_build_query($params);

$headers = [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0',
    'Accept: */*',
    'Accept-Language: es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
    'X-Requested-With: XMLHttpRequest',
    'Referer: https://websalud.minsa.gob.pe/hisminsa/',
    "Cookie: $cookie"
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $fullUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => '',
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_CONNECTTIMEOUT => 10,
]);

$response = curl_exec($ch);
$info     = curl_getinfo($ch);
$err      = curl_error($ch);
$errno    = curl_errno($ch);
curl_close($ch);

if ($err || $errno) {
    echo json_encode(['error' => "cURL error ($errno): $err"], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($info['http_code'] != 200) {
    echo json_encode([
        'error'     => 'HTTP ' . $info['http_code'] . '. Cookie posiblemente expirada.',
        'http_code' => $info['http_code'],
        'snippet'   => mb_substr($response, 0, 300)
    ], JSON_UNESCAPED_UNICODE);
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
