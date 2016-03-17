<?php
namespace dvcarrot\mailer;

use Exception;

class SmtpMail
{

    protected $smtp_username;
    protected $smtp_password;
    protected $smtp_from;
    protected $smtp_host = 'ssl://smtp.yandex.ru';
    protected $smtp_port = 465;
    protected $smtp_charset = "utf-8";

    public function __construct(array $params)
    {
        $available = array('username', 'password', 'host', 'from', 'port', 'charset');
        foreach ($available as $key)
            if (array_key_exists($key, $params) && ($alias = 'smtp_' . $key))
                $this->$alias = $params[$key];
        if (!$this->smtp_from && $this->smtp_username)
            $this->smtp_from = $this->smtp_username;
    }

    public function send($mailTo, $subject, $message, $headers)
    {
        $contentMail = "Date: " . date("D, d M Y H:i:s") . " UT\r\n";
        $contentMail .= 'Subject: =?' . $this->smtp_charset . '?B?' . base64_encode($subject) . "=?=\r\n";
        $contentMail .= $headers . "\r\n";
        $contentMail .= $message . "\r\n";
        try {
            $socket = fsockopen($this->smtp_host, $this->smtp_port, $errorNumber, $errorDescription, 30);
            if (!$socket)
                throw new Exception($errorNumber . "." . $errorDescription);

            if (!$this->_parseServer($socket, 220))
                throw new Exception('Connection error');

            $server_name = $_SERVER["SERVER_NAME"];
            fputs($socket, "HELO $server_name\r\n");
            if (!$this->_parseServer($socket, 250))
                throw new Exception('Error of command sending: HELO');

            fputs($socket, "AUTH LOGIN\r\n");
            if (!$this->_parseServer($socket, 334))
                throw new Exception('Authorization error');

            fputs($socket, base64_encode($this->smtp_username) . "\r\n");
            if (!$this->_parseServer($socket, 334))
                throw new Exception('Authorization error');

            fputs($socket, base64_encode($this->smtp_password) . "\r\n");
            if (!$this->_parseServer($socket, 235))
                throw new Exception('Authorization error');

            fputs($socket, "MAIL FROM: <" . $this->smtp_from . ">\r\n");
            if (!$this->_parseServer($socket, 250))
                throw new Exception('Error of command sending: MAIL FROM');

            $toList = explode(',', $mailTo);
            foreach ($toList as $to) {
                fputs($socket, "RCPT TO: " . $to . "\r\n");
                if (!$this->_parseServer($socket, 250))
                    throw new Exception('Error of command sending: RCPT TO');
            }

            fputs($socket, "DATA\r\n");
            if (!$this->_parseServer($socket, 354))
                throw new Exception('Error of command sending: DATA');

            fputs($socket, $contentMail . "\r\n.\r\n");
            if (!$this->_parseServer($socket, 250))
                throw new Exception("E-mail didn't sent");

            fputs($socket, "QUIT\r\n");
            fclose($socket);
        } catch (Exception $e) {
            if (isset($socket))
                fclose($socket);
            return $e->getMessage();
        }
        return true;
    }

    private function _parseServer($socket, $response)
    {
        $reply = '';
        while ($line = fgets($socket, 515)) {
            $reply .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        // echo $reply . '<br>'; // debug
        return substr($reply, 0, 3) == $response;
    }
}
