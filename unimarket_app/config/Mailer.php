<?php
class Mailer {
    public static function enviar($to, $subject, $htmlBody, &$error = null) {
        $configPath = __DIR__ . '/mail_config.php';
        $config = file_exists($configPath) ? require $configPath : [];

        if (empty($config['enabled'])) {
            $error = 'SMTP desactivado en modo local.';
            return false;
        }

        $host = $config['host'] ?? '';
        $port = (int)($config['port'] ?? 465);
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $fromEmail = $config['from_email'] ?? $username;
        $fromName = $config['from_name'] ?? 'UniMarket';
        $encryption = $config['encryption'] ?? 'ssl';

        if (!$host || !$username || !$password || !$fromEmail) {
            $error = 'Configuración SMTP incompleta.';
            return false;
        }

        $target = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $socket = @stream_socket_client($target, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            $error = "No se pudo conectar al SMTP: $errstr";
            return false;
        }

        stream_set_timeout($socket, 20);

        $read = function() use ($socket) {
            $data = '';
            while (($line = fgets($socket, 515)) !== false) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $data;
        };

        $cmd = function($command, $expected = null) use ($socket, $read, &$error) {
            fwrite($socket, $command . "\r\n");
            $response = $read();
            if ($expected !== null) {
                $valid = false;
                foreach ((array)$expected as $code) {
                    if (strpos($response, (string)$code) === 0) {
                        $valid = true;
                        break;
                    }
                }
                if (!$valid) {
                    $error = trim($response);
                    return false;
                }
            }
            return $response;
        };

        $read();
        if (!$cmd('EHLO localhost', 250)) { fclose($socket); return false; }
        if (!$cmd('AUTH LOGIN', 334)) { fclose($socket); return false; }
        if (!$cmd(base64_encode($username), 334)) { fclose($socket); return false; }
        if (!$cmd(base64_encode($password), 235)) { fclose($socket); return false; }
        if (!$cmd('MAIL FROM: <' . $fromEmail . '>', 250)) { fclose($socket); return false; }
        if (!$cmd('RCPT TO: <' . $to . '>', [250, 251])) { fclose($socket); return false; }
        if (!$cmd('DATA', 354)) { fclose($socket); return false; }

        $headers = [];
        $headers[] = 'From: ' . self::encodeHeader($fromName) . ' <' . $fromEmail . '>';
        $headers[] = 'To: <' . $to . '>';
        $headers[] = 'Subject: ' . self::encodeHeader($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.";
        if (!$cmd($message, 250)) { fclose($socket); return false; }
        $cmd('QUIT');
        fclose($socket);
        return true;
    }

    private static function encodeHeader($value) {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
?>
