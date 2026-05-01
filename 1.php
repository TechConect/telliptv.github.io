<?php
// Configurações
$dns_streaming = "http://newdg.vip"; 
$resultado = null;
$erro = null;

// --- LÓGICA DE BLOQUEIO POR IP (10 MINUTOS) ---
$ip_usuario = $_SERVER['REMOTE_ADDR'];
$arquivo_log = "logs_ips.txt";
$tempo_espera = 10 * 60; // 600 segundos

if (isset($_POST['gerar'])) {
    $pode_gerar = true;
    $agora = time();

    if (file_exists($arquivo_log)) {
        $linhas = file($arquivo_log);
        foreach ($linhas as $linha) {
            $dados = explode("|", trim($linha));
            if (count($dados) >= 2) {
                list($ip_salvo, $timestamp_salvo) = $dados;
                if ($ip_usuario == $ip_salvo) {
                    if (($agora - $timestamp_salvo) < $tempo_espera) {
                        $pode_gerar = false;
                        $restante = $tempo_espera - ($agora - $timestamp_salvo);
                        $minutos = floor($restante / 60);
                        $segundos = $restante % 60;
                        $erro = "Aguarde {$minutos}m {$segundos}s para gerar um novo teste (Limite de 10 min ativo).";
                        break;
                    }
                }
            }
        }
    }

    if ($pode_gerar) {
        $chatbot_url = "https://matrixtv.sigma.st/api/chatbot/o231q8EW4q/bOxLAQLZ7a";
        $payload = json_encode([
            "receiveMessageAppId" => "com.whatsapp",
            "receiveMessagePattern" => ["*"],
            "senderName" => "TELLIPTV_SISTEMA",
            "senderMessage" => "api_cadastro",
            "messageDateTime" => time(),
            "isMessageFromGroup" => false
        ]);

        $ch = curl_init($chatbot_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['username'])) {
            $link_pagamento = $data['payUrl'] ?? $data['renovarLink'] ?? "https://wa.me/5521978838643?text=Quero+renovar+meu+acesso+TellIPTV";

            $resultado = [
                "user" => $data['username'],
                "pass" => $data['password'],
                "dns"  => $dns_streaming,
                "m3u"  => $dns_streaming . "/get.php?username=" . $data['username'] . "&password=" . $data['password'] . "&type=m3u_plus&output=mpegts",
                "renovar" => $link_pagamento
            ];

            // Registra a nova geração
            $nova_entrada = $ip_usuario . "|" . $agora . PHP_EOL;
            file_put_contents($arquivo_log, $nova_entrada, FILE_APPEND);
        } else {
            $erro = "Ocorreu um erro ao conectar com o servidor. Tente novamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TELLIPTV - Painel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap');
        body { background: #0f172a; color: white; font-family: 'Poppins', sans-serif; }
        .cyber-card { background: rgba(15, 23, 42, 0.95); border: 1px solid rgba(0, 217, 255, 0.2); backdrop-filter: blur(10px); }
        .gradient-text { background: linear-gradient(90deg, #ff004c, #00d9ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .item-box { background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.05); }
        .btn-renovar { background: linear-gradient(90deg, #25d366, #128c7e); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-lg cyber-card rounded-3xl p-8 shadow-2xl border-t-4 border-t-[#00d9ff]">
        <div class="text-center mb-8">
            <h1 class="text-5xl font-black gradient-text tracking-tighter">TELLIPTV</h1>
            <p class="text-yellow-500 text-[10px] mt-2 font-bold uppercase tracking-widest">Trava de Segurança: 10 Minutos</p>
        </div>

        <?php if (!$resultado): ?>
            <div class="text-center">
                <?php if ($erro): ?>
                    <div class="bg-yellow-500/10 border border-yellow-500/50 text-yellow-500 p-4 rounded-xl mb-6 text-sm">
                        <i class="fas fa-history mr-2"></i> <?= $erro ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <button type="submit" name="gerar" class="w-full bg-[#ff004c] py-5 rounded-2xl font-bold text-xl hover:scale-105 transition-all shadow-lg">
                        GERAR MEU TESTE
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                
                <div class="item-box p-4 rounded-2xl">
                    <span class="text-[10px] text-[#00d9ff] font-bold uppercase">DNS (Servidor)</span>
                    <div class="flex justify-between items-center">
                        <span class="font-mono text-gray-200 text-sm"><?= $resultado['dns'] ?></span>
                        <button onclick="copy('<?= $resultado['dns'] ?>')" class="text-gray-500 hover:text-white"><i class="fas fa-copy"></i></button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="item-box p-4 rounded-2xl">
                        <span class="text-[10px] text-gray-500 uppercase font-bold">Usuário</span>
                        <div class="flex justify-between items-center">
                            <span class="font-mono text-lg"><?= $resultado['user'] ?></span>
                            <button onclick="copy('<?= $resultado['user'] ?>')" class="text-gray-600 hover:text-white"><i class="fas fa-copy text-xs"></i></button>
                        </div>
                    </div>
                    <div class="item-box p-4 rounded-2xl">
                        <span class="text-[10px] text-gray-500 uppercase font-bold">Senha</span>
                        <div class="flex justify-between items-center">
                            <span class="font-mono text-lg"><?= $resultado['pass'] ?></span>
                            <button onclick="copy('<?= $resultado['pass'] ?>')" class="text-gray-600 hover:text-white"><i class="fas fa-copy text-xs"></i></button>
                        </div>
                    </div>
                </div>

                <div class="item-box p-4 rounded-2xl">
                    <span class="text-[10px] text-[#ff004c] font-bold uppercase">Link M3U</span>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] truncate text-gray-500 w-4/5"><?= $resultado['m3u'] ?></span>
                        <button onclick="copy('<?= $resultado['m3u'] ?>')" class="text-gray-500 hover:text-white"><i class="fas fa-copy"></i></button>
                    </div>
                </div>

                <div class="pt-4">
                    <a href="<?= $resultado['renovar'] ?>" target="_blank" class="flex items-center justify-center w-full btn-renovar py-4 rounded-2xl font-bold text-lg shadow-lg hover:brightness-110 transition text-white">
                        <i class="fab fa-whatsapp mr-2 text-2xl"></i> RENOVAR ACESSO
                    </a>
                </div>

                <div class="flex justify-center pt-2">
                     <a href="index.html" class="text-blue-400 text-xs font-bold hover:underline uppercase">Voltar ao Início</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function copy(text) {
            navigator.clipboard.writeText(text);
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Copiado!', showConfirmButton: false, timer: 1500 });
        }
    </script>
</body>
</html>