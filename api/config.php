<?php
/**
 * Configuração da API de contacto (api/contact.php).
 * Este ficheiro não imprime nada: se alguém o abrir no navegador, não vê conteúdo.
 * Se colocar aqui a palavra-passe de SMTP, mantenha o ficheiro fora do controlo de versões público.
 */
return [
    // Destinatário e remetente. O remetente DEVE ser do domínio do site (SPF/DKIM) para o email não ir para o spam.
    'to'             => 'info@foremost.co.mz',
    'from'           => 'info@foremost.co.mz',
    'from_name'      => 'Site Foremost',
    'subject_prefix' => '[foremost.co.mz]',

    // 'mail' = função mail() do PHP (a mais simples; funciona na maioria dos cPanel)
    // 'smtp' = servidor SMTP autenticado (mais fiável; preencha o bloco "smtp" abaixo)
    'transport'      => 'mail',
    'envelope_from'  => true,       // usa -f<from> no mail(); ponha false se o alojamento recusar este parâmetro

    'smtp' => [
        'host'        => 'mail.foremost.co.mz',
        'port'        => 465,        // 465 com 'ssl'  |  587 com 'tls' (STARTTLS)
        'secure'      => 'ssl',      // 'ssl' | 'tls' | '' (sem cifra, só para testes)
        'user'        => 'info@foremost.co.mz',
        'pass'        => '',
        'timeout'     => 12,
        'verify_peer' => true,
    ],

    // Sites autorizados a usar a API (o próprio host do pedido também é aceite)
    'allowed_hosts'  => ['foremost.co.mz', 'www.foremost.co.mz'],

    // Máximo de envios por endereço IP numa janela de tempo (segundos)
    'rate_limit'     => ['max' => 5, 'window' => 900],
    'min_ms'         => 2500,        // preenchimento mais rápido do que isto é tratado como robot
    'trust_proxy'    => false,       // true apenas se o site estiver atrás de Cloudflare/proxy
    'secret'         => '64d53f379f45ffefb227f2077dbbc40e',
    'debug'          => false,       // true mostra o motivo do erro na resposta (apenas para testes!)
];
