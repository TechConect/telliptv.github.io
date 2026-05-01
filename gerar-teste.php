<?php
// --- CONFIGURAÇÕES ---
$dns_streaming = "http://newdg.vip"; 
$url_api_matrix = "https://matrixtv.sigma.st/api/chatbot/o231q8EW4q/bOxLAQLZ7a";
$link_base_checkout = "https://matrixtv.sigma.st/#/checkout?username=";

$resultado = null;
$erro = null;

// --- LÓGICA DE BLOQUEIO POR IP ---
$ip_usuario = $_SERVER['REMOTE_ADDR'];
$arquivo_log = "logs_ips.txt";
$tempo_espera = 10 * 60; // 10 minutos

// Garante que o arquivo de log existe
if (!file_exists($arquivo_log)) { touch($arquivo_log); }

$requisicao_ia = isset($_GET['nome']) && isset($_GET['whatsapp']);
$executar_geracao = isset($_POST['gerar']) || $requisicao_ia;

if ($executar_geracao) {
    $pode_gerar = true;
    $agora = time();

    // Verifica bloqueio de IP
    $linhas = file($arquivo_log);
    foreach ($linhas as $linha) {
        $dados = explode("|", trim($linha));
        if (count($dados) >= 2 && $ip_usuario == $dados[0] && ($agora - $dados[1]) < $tempo_espera) {
            $pode_gerar = false;
            $restante = $tempo_espera - ($agora - $dados[1]);
            $erro = "Aguarde " . floor($restante / 60) . "m para gerar novo teste.";
            break;
        }
    }

    if ($pode_gerar) {
        $payload = json_encode([
            "receiveMessageAppId" => "com.whatsapp",
            "receiveMessagePattern" => ["*"],
            "senderName" => $requisicao_ia ? $_GET['nome'] : "WEB_SISTEMA",
            "senderMessage" => "api_cadastro",
            "messageDateTime" => $agora,
            "isMessageFromGroup" => false
        ]);

        $ch = curl_init($url_api_matrix);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignora erros de SSL
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        if ($httpCode == 200 && isset($data['username'])) {
            $user = trim($data['username']);
            $pass = trim($data['password']);
            $link_final = $link_base_checkout . $user;

            $texto = "🌐 DNS: $dns_streaming\n👤 USUÁRIO: $user\n🔑 SENHA: $pass\n🔗 M3U: $dns_streaming/get.php?username=$user&password=$pass&type=m3u_plus&output=mpegts";

            // Salva no log
            file_put_contents($arquivo_log, $ip_usuario . "|" . $agora . PHP_EOL, FILE_APPEND);

            if ($requisicao_ia) {
                echo $texto . "RENOVAR_LINK:" . $link_final;
                exit;
            } else {
                // Para o painel manual (HTML)
                $resultado = ["dns" => $dns_streaming, "user" => $user, "pass" => $pass, "m3u" => $texto, "link" => $link_final];
            }
        } else {
            $erro = "API Matrix Offline (Erro $httpCode). Verifique seu token.";
            if ($requisicao_ia) { echo "❌ " . $erro; exit; }
        }
    } else {
        if ($requisicao_ia) { echo "❌ " . $erro; exit; }
    }
}
?>