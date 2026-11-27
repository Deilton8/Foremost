<?php
declare(strict_types=1);

/**
 * Foremost — API de contacto
 * Recebe o formulário do site e envia um email para info@foremost.co.mz.
 *
 *   POST /api/contact.php      (devolve JSON se o pedido tiver "Accept: application/json";
 *                               caso contrário, redirecciona para /obrigado/)
 *
 * Requisitos: PHP 7.2 ou superior. Configuração: api/config.php
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);
date_default_timezone_set('Africa/Maputo');

/* ------------------------------------------------------------------ mensagens */
const MSG = [
    'pt' => [
        'ok'       => 'Mensagem enviada com sucesso! A equipa Foremost entrará em contacto consigo.',
        'method'   => 'Método não permitido.',
        'origin'   => 'Pedido recusado.',
        'size'     => 'Pedido demasiado grande.',
        'invalid'  => 'Verifique os campos assinalados e tente novamente.',
        'contact'  => 'Indique pelo menos um email ou um telefone para podermos responder.',
        'spam'     => 'A mensagem tem demasiadas ligações. Remova-as e tente novamente.',
        'rate'     => 'Enviou várias mensagens seguidas. Aguarde alguns minutos e tente novamente.',
        'send'     => 'Não foi possível enviar agora. Tente novamente, use o WhatsApp ou escreva para info@foremost.co.mz.',
        'h_err'    => 'Não foi possível enviar',
        'back'     => 'Voltar ao formulário',
    ],
    'en' => [
        'ok'       => 'Message sent successfully! The Foremost team will get back to you.',
        'method'   => 'Method not allowed.',
        'origin'   => 'Request refused.',
        'size'     => 'Request too large.',
        'invalid'  => 'Please check the highlighted fields and try again.',
        'contact'  => 'Please give at least one email or phone number so we can reply.',
        'spam'     => 'The message has too many links. Please remove them and try again.',
        'rate'     => 'You sent several messages in a row. Please wait a few minutes and try again.',
        'send'     => 'We could not send your message right now. Please try again, use WhatsApp or email info@foremost.co.mz.',
        'h_err'    => 'Could not send',
        'back'     => 'Back to the form',
    ],
];

/* ------------------------------------------------------------------ utilitários */
function u_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : (int) preg_match_all('/./us', $s);
}

function u_cut(string $s, int $n): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($s, 0, $n, 'UTF-8');
    }
    return preg_match('/^.{0,' . $n . '}/us', $s, $m) ? $m[0] : '';
}

/** Limpa texto: UTF-8 válido, sem caracteres de controlo; numa só linha, salvo $multiline. */
function clean($v, bool $multiline = false): string
{
    $s = is_string($v) ? $v : '';
    $s = str_replace("\0", '', $s);
    if (!preg_match('//u', $s)) {
        $s = (string) @iconv('UTF-8', 'UTF-8//IGNORE', $s);
    }
    if ($multiline) {
        $s = preg_replace('/[^\P{C}\n\t]+/u', '', str_replace(["\r\n", "\r"], "\n", $s));
        $s = preg_replace("/\n{3,}/", "\n\n", (string) $s);
    } else {
        $s = preg_replace('/\p{C}+/u', ' ', $s);
        $s = preg_replace('/ {2,}/', ' ', (string) $s);
    }
    return trim((string) $s);
}

/** Cabeçalho MIME (RFC 2047) para texto com acentos. */
function mime_header(string $s): string
{
    if (preg_match('/^[\x20-\x7E]*$/', $s)) {
        return $s;
    }
    $words = [];
    $chunk = '';
    foreach (preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $ch) {
        if (strlen($chunk . $ch) > 42) {
            $words[] = '=?UTF-8?B?' . base64_encode($chunk) . '?=';
            $chunk = '';
        }
        $chunk .= $ch;
    }
    if ($chunk !== '') {
        $words[] = '=?UTF-8?B?' . base64_encode($chunk) . '?=';
    }
    return implode("\r\n ", $words);
}

/** Nome de apresentação seguro para cabeçalhos "From"/"Reply-To". */
function display_name(string $name): string
{
    $name = trim(str_replace(['"', '<', '>', '\\', ',', ';'], '', $name));
    if ($name === '') {
        return '';
    }
    $enc = mime_header($name);
    return $enc === $name ? '"' . $name . '" ' : $enc . ' ';
}

function client_ip(array $cfg): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    if (!empty($cfg['trust_proxy'])) {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR'] as $h) {
            if (!empty($_SERVER[$h])) {
                $cand = trim(explode(',', (string) $_SERVER[$h])[0]);
                if (filter_var($cand, FILTER_VALIDATE_IP)) {
                    return $cand;
                }
            }
        }
    }
    return $ip;
}

/* ------------------------------------------------------------------ resposta */
function reply(bool $json, int $status, array $data, string $lang): void
{
    http_response_code($status);
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    $ok = !empty($data['success']);
    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    if ($ok) {
        header('Location: /obrigado/', true, 303);
        exit;
    }
    $m = MSG[$lang];
    header('Content-Type: text/html; charset=utf-8');
    $e = function (string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };
    echo '<!DOCTYPE html><html lang="' . ($lang === 'en' ? 'en' : 'pt-MZ') . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<meta name="robots" content="noindex"><title>' . $e($m['h_err']) . ' | Foremost</title></head>'
        . '<body style="font-family:system-ui,sans-serif;max-width:36rem;margin:3rem auto;padding:0 1rem;line-height:1.6">'
        . '<h1>' . $e($m['h_err']) . '</h1><p>' . $e((string) ($data['message'] ?? '')) . '</p>'
        . '<p><a href="/#contactos">' . $e($m['back']) . '</a> &middot; <a href="https://wa.me/258827847870">WhatsApp</a></p></body></html>';
    exit;
}

/* ------------------------------------------------------------------ limite de pedidos por IP */
/** @return int segundos a aguardar (0 = pedido permitido) */
function rate_limited(array $cfg, string $ip): int
{
    $max = (int) ($cfg['rate_limit']['max'] ?? 5);
    $win = (int) ($cfg['rate_limit']['window'] ?? 900);
    if ($max <= 0) {
        return 0;
    }
    $dir = rtrim((string) ($cfg['storage_dir'] ?? sys_get_temp_dir()), '/\\');
    $file = $dir . '/foremost_rl_' . hash('sha256', $ip . '|' . (string) ($cfg['secret'] ?? '')) . '.json';
    $fp = @fopen($file, 'c+');
    if (!$fp) {
        error_log('[foremost-contact] sem acesso à pasta temporária para o limite de pedidos');
        return 0;
    }
    flock($fp, LOCK_EX);
    $arr = json_decode((string) stream_get_contents($fp), true);
    $arr = is_array($arr) ? $arr : [];
    $now = time();
    $arr = array_values(array_filter($arr, function ($t) use ($now, $win) { return is_int($t) && $t > $now - $win; }));
    $wait = 0;
    if (count($arr) >= $max) {
        $wait = max(1, $arr[0] + $win - $now);
    } else {
        $arr[] = $now;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($arr));
        fflush($fp);
    }
    flock($fp, LOCK_UN);
    fclose($fp);
    if (mt_rand(1, 100) === 1) {   // limpeza ocasional de ficheiros antigos
        foreach (glob($dir . '/foremost_rl_*.json') ?: [] as $f) {
            if (@filemtime($f) < $now - 86400) {
                @unlink($f);
            }
        }
    }
    return $wait;
}

/* ------------------------------------------------------------------ envio por SMTP */
function smtp_send(array $c, string $from, string $to, string $data): bool
{
    $secure = (string) ($c['secure'] ?? 'ssl');            // 'ssl' (465) | 'tls' (STARTTLS, 587) | '' (sem cifra)
    $port = (int) ($c['port'] ?? ($secure === 'ssl' ? 465 : 587));
    $timeout = (int) ($c['timeout'] ?? 12);
    $verify = !isset($c['verify_peer']) || (bool) $c['verify_peer'];
    $ctx = stream_context_create(['ssl' => ['verify_peer' => $verify, 'verify_peer_name' => $verify, 'allow_self_signed' => !$verify]]);
    $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . $c['host'] . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        error_log("[foremost-contact] SMTP: ligação falhou ($errno) $errstr");
        return false;
    }
    stream_set_timeout($fp, $timeout);
    $read = function () use ($fp): string {
        $resp = '';
        while (($line = fgets($fp, 515)) !== false) {
            $resp .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $resp;
    };
    $cmd = function (?string $line, array $ok) use ($fp, $read): string {
        if ($line !== null) {
            fwrite($fp, $line . "\r\n");
        }
        $r = $read();
        if (!in_array((int) substr($r, 0, 3), $ok, true)) {
            throw new RuntimeException(trim($r));
        }
        return $r;
    };
    try {
        $name = preg_replace('/[^A-Za-z0-9.\-]/', '', (string) ($_SERVER['SERVER_NAME'] ?? 'localhost')) ?: 'localhost';
        $cmd(null, [220]);
        $caps = $cmd('EHLO ' . $name, [250]);
        if ($secure === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('STARTTLS falhou');
            }
            $caps = $cmd('EHLO ' . $name, [250]);
        }
        if (!empty($c['user'])) {
            if (stripos($caps, 'LOGIN') !== false) {
                $cmd('AUTH LOGIN', [334]);
                $cmd(base64_encode((string) $c['user']), [334]);
                $cmd(base64_encode((string) ($c['pass'] ?? '')), [235]);
            } else {
                $cmd('AUTH PLAIN ' . base64_encode("\0" . $c['user'] . "\0" . ($c['pass'] ?? '')), [235]);
            }
        }
        $cmd('MAIL FROM:<' . $from . '>', [250]);
        $cmd('RCPT TO:<' . $to . '>', [250, 251]);
        $cmd('DATA', [354]);
        $data = str_replace("\n", "\r\n", str_replace(["\r\n", "\r"], "\n", $data));
        $data = preg_replace('/^\./m', '..', $data);
        fwrite($fp, $data . "\r\n.\r\n");
        $cmd(null, [250]);
        @fwrite($fp, "QUIT\r\n");
        fclose($fp);
        return true;
    } catch (Throwable $e) {
        error_log('[foremost-contact] SMTP: ' . $e->getMessage());
        @fclose($fp);
        return false;
    }
}

/* ------------------------------------------------------------------ processamento */
$cfg = require __DIR__ . '/config.php';
$json = (stripos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false)
    || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$lang = (($_POST['lang'] ?? '') === 'en') ? 'en' : 'pt';
$M = MSG[$lang];

set_exception_handler(function (Throwable $e) use ($json, $lang, $M, $cfg) {
    error_log('[foremost-contact] ' . $e->getMessage() . ' em ' . basename($e->getFile()) . ':' . $e->getLine());
    $d = ['success' => false, 'error' => 'server', 'message' => $M['send']];
    if (!empty($cfg['debug'])) {
        $d['detail'] = $e->getMessage();
    }
    reply($json, 500, $d, $lang);
});

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    reply($json, 405, ['success' => false, 'error' => 'method', 'message' => $M['method']], $lang);
}

// Só aceita pedidos feitos a partir do próprio site
$hosts = array_map('strtolower', (array) ($cfg['allowed_hosts'] ?? []));
$hosts[] = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $h) {
    if (!empty($_SERVER[$h])) {
        $host = strtolower((string) parse_url((string) $_SERVER[$h], PHP_URL_HOST));
        if ($host === '' || !in_array($host, $hosts, true)) {
            reply($json, 403, ['success' => false, 'error' => 'origin', 'message' => $M['origin']], $lang);
        }
        break;
    }
}

if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 30000) {
    reply($json, 413, ['success' => false, 'error' => 'size', 'message' => $M['size']], $lang);
}

// Anti-spam silencioso: o campo-armadilha ("website") e o tempo de preenchimento (campo "t", em ms)
$fake_ok = ['success' => true, 'message' => $M['ok']];
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    reply($json, 200, $fake_ok, $lang);
}
$t = isset($_POST['t']) ? (int) $_POST['t'] : 0;
if ($t > 0 && $t < (int) ($cfg['min_ms'] ?? 2500)) {
    reply($json, 200, $fake_ok, $lang);
}

$f = [
    'Nome'      => u_cut(clean($_POST['Nome'] ?? ''), 100),
    'Empresa'   => u_cut(clean($_POST['Empresa'] ?? ''), 100),
    'Telefone'  => u_cut(clean($_POST['Telefone'] ?? ''), 30),
    'email'     => u_cut(clean($_POST['email'] ?? ''), 120),
    'Servico'   => u_cut(clean($_POST['Servico'] ?? ''), 80),
    'Categoria' => u_cut(clean($_POST['Categoria'] ?? ''), 80),
    'Mensagem'  => u_cut(clean($_POST['Mensagem'] ?? '', true), 3000),
];

$bad = [];
if (u_len($f['Nome']) < 2) {
    $bad[] = 'Nome';
}
if (u_len($f['Mensagem']) < 5) {
    $bad[] = 'Mensagem';
}
if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
    $bad[] = 'email';
}
if ($f['Telefone'] !== '' && !preg_match('/^[0-9+()\-.\s]{5,30}$/', $f['Telefone'])) {
    $bad[] = 'Telefone';
}
if ($bad) {
    reply($json, 422, ['success' => false, 'error' => 'validation', 'fields' => $bad, 'message' => $M['invalid']], $lang);
}
if ($f['email'] === '' && $f['Telefone'] === '') {
    reply($json, 422, ['success' => false, 'error' => 'contact_required', 'fields' => ['email', 'Telefone'], 'message' => $M['contact']], $lang);
}
if (preg_match_all('~https?://|www\.~i', $f['Mensagem']) > 3) {
    reply($json, 422, ['success' => false, 'error' => 'spam', 'message' => $M['spam']], $lang);
}

$ip = client_ip($cfg);
$wait = rate_limited($cfg, $ip);
if ($wait > 0) {
    header('Retry-After: ' . $wait);
    reply($json, 429, ['success' => false, 'error' => 'rate_limit', 'message' => $M['rate']], $lang);
}

/* ------------------------------------------------------------------ compor o email */
$to = (string) $cfg['to'];
$from = (string) $cfg['from'];
$host = preg_replace('/^.*@/', '', $from);
$subject = trim((string) ($cfg['subject_prefix'] ?? '')) . ' Novo contacto: ' . $f['Nome'];
$subject = u_cut(clean($subject), 120);

$body = "Novo pedido através do site foremost.co.mz\n\n"
    . "Nome: {$f['Nome']}\n"
    . 'Empresa: ' . ($f['Empresa'] ?: '-') . "\n"
    . 'Email: ' . ($f['email'] ?: '-') . "\n"
    . 'Telefone: ' . ($f['Telefone'] ?: '-') . "\n"
    . 'Serviço: ' . ($f['Servico'] ?: '-') . "\n"
    . 'Categoria de produtos: ' . ($f['Categoria'] ?: '-') . "\n\n"
    . "Mensagem:\n{$f['Mensagem']}\n\n"
    . "----------------------------------------\n"
    . 'Data: ' . date('Y-m-d H:i:s') . " (Africa/Maputo)\n"
    . 'Idioma da página: ' . $lang . "\n"
    . 'IP: ' . $ip . "\n"
    . 'Navegador: ' . u_cut(clean($_SERVER['HTTP_USER_AGENT'] ?? ''), 160) . "\n";
$body = str_replace("\n", "\r\n", str_replace(["\r\n", "\r"], "\n", $body));

$headers = [
    'Date: ' . date('r'),
    'From: ' . display_name((string) ($cfg['from_name'] ?? 'Site Foremost')) . '<' . $from . '>',
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: quoted-printable',
    'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $host . '>',
    'X-Mailer: Foremost-Contact/1.0',
];
if ($f['email'] !== '') {
    $headers[] = 'Reply-To: ' . display_name($f['Nome']) . '<' . $f['email'] . '>';
}
$encSubject = mime_header($subject);
$qp = quoted_printable_encode($body);

/* ------------------------------------------------------------------ enviar */
$sent = false;
if (($cfg['transport'] ?? 'mail') === 'smtp') {
    $data = implode("\r\n", array_merge($headers, ['To: ' . $to, 'Subject: ' . $encSubject])) . "\r\n\r\n" . $qp;
    $sent = smtp_send((array) $cfg['smtp'], $from, $to, $data);
} else {
    $extra = !empty($cfg['envelope_from']) ? '-f' . $from : '';
    $sent = $extra !== ''
        ? @mail($to, $encSubject, $qp, implode("\r\n", $headers), $extra)
        : @mail($to, $encSubject, $qp, implode("\r\n", $headers));
    if (!$sent) {
        error_log('[foremost-contact] mail() devolveu falso; confirme que o alojamento permite mail() ou use o transporte "smtp" em api/config.php');
    }
}

if (!$sent) {
    reply($json, 500, ['success' => false, 'error' => 'send_failed', 'message' => $M['send']], $lang);
}
reply($json, 200, ['success' => true, 'message' => $M['ok']], $lang);
