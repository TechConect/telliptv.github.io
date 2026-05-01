<?php
header('Content-Type: application/json');

// --- CONFIGURAÇÕES ---
$dns_streaming = "http://newdg.vip"; 
$arquivo_log = "logs_ips.txt";
$tempo_espera = 3 * 60; // 3 minutos
$ip_usuario = $_SERVER['REMOTE_ADDR'];
$agora = time();

// --- RECEBER DADOS DO CHAT ---
// Se não vier nada, ele usa um padrão para não dar erro
$nome_cliente = isset($_GET['nome']) ? strip_tags($_GET['nome']) : "CLIENTE_WEB";
$whatsapp_cliente = isset($_GET['whatsapp']) ? strip_tags($_GET['whatsapp']) : "00000000000";

// --- VERIFICAÇÃO DE BLOQUEIO POR IP ---
if (file_exists($arquivo_log)) {
    $linhas = file($arquivo_log);
    foreach ($linhas as $linha) {
        $dados = explode("|", trim($linha));
        if (count($dados) >= 2 && $ip_usuario == $dados[0]) {
            if (($agora - $dados[1]) < $tempo_espera) {
                $restante = $tempo_espera - ($agora - $dados[1]);
                echo json_encode(["erro" => "Aguarde " . ceil($restante/60) . " min para gerar novo teste."]);
                exit;
            }
        }
    }
}

// --- CHAMADA PARA A API MATRIX ---
$chatbot_url = "https://matrixtv.sigma.st/api/chatbot/o231q8EW4q/bOxLAQLZ7a";
$payload = json_encode([
    "receiveMessageAppId" => "com.whatsapp",
    "receiveMessagePattern" => ["*"],
    "senderName" => $nome_cliente . " - " . $whatsapp_cliente, // APARECERÁ ASSIM NO SISTEMA
    "senderMessage" => "api_cadastro",
    "messageDateTime" => $agora,
    "isMessageFromGroup" => false
]);

$ch = curl_init($chatbot_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (isset($data['username'])) {
    file_put_contents($arquivo_log, $ip_usuario . "|" . $agora . PHP_EOL, FILE_APPEND);
    echo json_encode([
        "sucesso" => true,
        "dns" => $dns_streaming,
        "user" => $data['username'],
        "pass" => $data['password'],
        "m3u" => $dns_streaming . "/get.php?username=" . $data['username'] . "&password=" . $data['password'] . "&type=m3u_plus&output=mpegts",
        "renovar" => $data['payUrl'] ?? $data['renovarLink'] ?? "https://wa.me/5521978838643"
    ]);
} else {
    echo json_encode(["erro" => "Servidor instável. Tente novamente."]);
}
exit;