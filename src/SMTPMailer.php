<?php


namespace ShahariaAzam\SMTPMailer;


use Omnimail\EmailInterface;
use Omnimail\MailerInterface;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Psr\Log\LoggerInterface;

/**
 * Class SMTPMailer
 * @package ShahariaAzam\SMTPMailer
 */
class SMTPMailer implements MailerInterface
{
    protected $smtpHost;
    protected $username;
    protected $password;

    /**
     * @var PHPMailer
     */
    protected $processor;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SMTPMailer constructor.
     * @param $smtpHost
     * @param $username
     * @param $password
     * @param array $options
     */
    public function __construct($smtpHost, $username, $password, array $options = [], $headers = [])
    {
        $this->smtpHost = $smtpHost;
        $this->username = $username;
        $this->password = $password;

        $mailer = new PHPMailer(true);
        $mailer->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
        $mailer->isSMTP();                                            // Send using SMTP
        $mailer->Host       = $this->smtpHost;                    // Set the SMTP server to send through
        $mailer->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mailer->Username   = $this->username;                     // SMTP username
        $mailer->Password   = $this->password;                               // SMTP password
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mailer->Port       = !isset($options['port']) ? 587 : intval($options['port']);
        foreach ($headers as $header => $value) {
          $mailer->AddCustomHeader( "$header: $value" );
        }
        $this->processor = $mailer;
    }

    /**
     * @param EmailInterface $email
     * @return bool|\Exception|Exception
     * @throws Exception
     */
    public function send(EmailInterface $email)
    {
        foreach ($email->getTos() as $to){
            $this->processor->addAddress($to['email'], $to['name']);
        }

        if(!empty($email->getReplyTo())){
            $this->processor->addReplyTo($email->getReplyTo()['email'], $email->getReplyTo()['name']);
        }

        if(!empty($email->getReplyTos())){
            foreach ($email->getReplyTos() as $replyTo){
                $this->processor->addReplyTo($replyTo['email'], $replyTo['name']);
            }
        }

        foreach ($email->getCcs() as $cc){
            $this->processor->addCC($cc['email'], $cc['name']);
        }

        foreach ($email->getBccs() as $bcc){
            $this->processor->addBCC($bcc['email'], $bcc['name']);
        }

        if(!empty($email->getFrom())){
            $this->processor->setFrom($email->getFrom()['email'], $email->getFrom()['name']);
        }

        if(!empty($email->getSubject())){
            $this->processor->Subject = $email->getSubject();
        }

        if(!empty($email->getHtmlBody())){
            $this->processor->isHTML(true);
            $this->processor->Body    = $email->getHtmlBody();
        }

        if(!empty($email->getTextBody()) && !empty($email->getHtmlBody())){
            $this->processor->AltBody = $email->getTextBody();
        }

        if(!empty($email->getTextBody()) && empty($email->getHtmlBody())){
            $this->processor->Body = $email->getTextBody();
        }

        try {
            $this->processor->send();
            return true;
        } catch (Exception $e) {
            if(!empty($this->logger) && $this->logger instanceof LoggerInterface){
                $this->logger->error($e->getMessage());
                $this->logger->debug($e->getTraceAsString());
            }

            return $e;
        }
    }
}
