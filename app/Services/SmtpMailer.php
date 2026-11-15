<?php
namespace App\Services;

/**
 * Minimal SMTP client (no Composer needed): SSL (port 465) or STARTTLS (port 587), AUTH LOGIN/PLAIN,
 * HTML + plain-text alternative, UTF-8 subjects. Throws RuntimeException with the server's reply on failure.
 */
class SmtpMailer
{
    /** @var resource|null */
    private $fp = null;

    public function __construct(
        private string $host,
        private int $port = 587,
        private string $encryption = 'tls', // tls (STARTTLS) | ssl (implicit TLS) | none
        private string $username = '',
        private string $password = '',
        private int $timeout = 20,
    ) {}

    public function send(string $fromAddress, string $fromName, string $to, string $subject, string $html): void
    {
        $to = $this->clean($to); $fromAddress = $this->clean($fromAddress);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { throw new \RuntimeException("Invalid recipient address: {$to}"); }
        if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) { throw new \RuntimeException('MAIL_FROM_ADDRESS is not a valid email address.'); }
        try {
            $this->connect();
            $this->hello();
            if ($this->encryption === 'tls') {
                $this->expect($this->cmd('STARTTLS'), [220]);
                $ok = @stream_socket_enable_crypto($this->fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
                if (!$ok) { throw new \RuntimeException('Could not start TLS with ' . $this->host . ' (certificate or protocol problem).'); }
                $this->hello();
            }
            if ($this->username !== '') { $this->authenticate(); }
            $this->expect($this->cmd("MAIL FROM:<{$fromAddress}>"), [250]);
            $this->expect($this->cmd("RCPT TO:<{$to}>"), [250, 251]);
            $this->expect($this->cmd('DATA'), [354]);
            $this->write($this->build($fromAddress, $fromName, $to, $subject, $html) . "\r\n.\r\n");
            $this->expect($this->read(), [250]);
            @$this->cmd('QUIT');
        } finally {
            if (is_resource($this->fp)) { @fclose($this->fp); }
            $this->fp = null;
        }
    }

    private function clean(string $s): string { return trim(str_replace(["\r", "\n", "\0"], '', $s)); }

    private function connect(): void
    {
        $scheme = $this->encryption === 'ssl' ? 'ssl://' : 'tcp://';
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true, 'peer_name' => $this->host]]);
        $fp = @stream_socket_client($scheme . $this->host . ':' . $this->port, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) { throw new \RuntimeException("Could not connect to {$this->host}:{$this->port} ({$errstr}). Check MAIL_HOST, MAIL_PORT and that the server allows outgoing SMTP."); }
        stream_set_timeout($fp, $this->timeout);
        $this->fp = $fp;
        $this->expect($this->read(), [220]);
    }

    private function hello(): void
    {
        $name = parse_url((string) config('app.url', ''), PHP_URL_HOST) ?: (gethostname() ?: 'localhost');
        $this->expect($this->cmd('EHLO ' . $name), [250]);
    }

    private function authenticate(): void
    {
        $r = $this->cmd('AUTH LOGIN');
        if ($this->code($r) === 334) {
            $this->expect($this->cmd(base64_encode($this->username)), [334]);
            $this->expect($this->cmd(base64_encode($this->password)), [235]);
            return;
        }
        $this->expect($this->cmd('AUTH PLAIN ' . base64_encode("\0{$this->username}\0{$this->password}")), [235]);
    }

    private function build(string $from, string $fromName, string $to, string $subject, string $html): string
    {
        $domain = substr(strrchr($from, '@') ?: '@localhost', 1);
        $b = 'b' . bin2hex(random_bytes(12));
        $text = trim(html_entity_decode(strip_tags(preg_replace(['#<(br|/p|/h[1-6]|/li|/tr)[^>]*>#i', '#<(style|script)\b.*?</\1>#is'], ["\n", ''], $html)), ENT_QUOTES, 'UTF-8'));
        $text = preg_replace("/[ \t]+\n/", "\n", preg_replace("/\n{3,}/", "\n\n", $text));
        $enc = fn(string $s) => '=?UTF-8?B?' . base64_encode($s) . '?=';
        $h = [
            'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000',
            'From: ' . ($fromName !== '' ? $enc($this->clean($fromName)) . " <{$from}>" : $from),
            "To: <{$to}>",
            'Subject: ' . $enc($this->clean($subject)),
            "Message-ID: <" . bin2hex(random_bytes(12)) . "@{$domain}>",
            'MIME-Version: 1.0',
            "Content-Type: multipart/alternative; boundary=\"{$b}\"",
        ];
        $part = fn(string $type, string $body) => "--{$b}\r\nContent-Type: {$type}; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n");
        $msg = implode("\r\n", $h) . "\r\n\r\n" . $part('text/plain', $text) . $part('text/html', $html) . "--{$b}--";
        $msg = preg_replace("/\r\n|\r|\n/", "\r\n", $msg);
        return preg_replace('/^\./m', '..', $msg); // dot-stuffing
    }

    private function cmd(string $line): string { $this->write($line . "\r\n"); return $this->read(); }

    private function write(string $data): void
    {
        $len = strlen($data);
        for ($sent = 0; $sent < $len;) {
            $n = @fwrite($this->fp, substr($data, $sent));
            if ($n === false || $n === 0) { throw new \RuntimeException('Connection to the mail server was lost while sending.'); }
            $sent += $n;
        }
    }

    /** Read a full (possibly multi-line) SMTP reply. */
    private function read(): string
    {
        $reply = '';
        while (($line = fgets($this->fp, 1024)) !== false) {
            $reply .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') { break; }
        }
        if ($reply === '') { throw new \RuntimeException('No reply from the mail server (timed out).'); }
        return trim($reply);
    }

    private function code(string $reply): int { return (int) substr($reply, 0, 3); }

    private function expect(string $reply, array $codes): void
    {
        if (!in_array($this->code($reply), $codes, true)) {
            $hint = $this->code($reply) === 535 ? ' (wrong username or password; many providers need an app password)' : '';
            throw new \RuntimeException('Mail server said: ' . mb_substr(preg_replace('/\s+/', ' ', $reply), 0, 300) . $hint);
        }
    }
}
