<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    
    private static $config;

    private static function init() {
        if (self::$config === null) {
            self::$config = include __DIR__ . '/../../config/mail.php';
        }
    }

    public static function enviarCorreo($destinatario, $asunto, $mensaje, $isHtml = true) {
        
        self::init();
        
        $mail = new PHPMailer(true);
        
        try {
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->isSMTP();
            $mail->CharSet    = 'UTF-8'; 
            $mail->Host       = self::$config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = self::$config['username'];
            $mail->Password   = self::$config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = self::$config['port'];

            $mail->setFrom(self::$config['from_email'], self::$config['from_name']);
            $mail->addAddress($destinatario);

            $mail->isHTML($isHtml);
            $mail->Subject = $asunto;
            $mail->Body    = $mensaje;

            $mail->send();
            return true;
            
        } catch (Exception $e) {
            return $mail->ErrorInfo; 
        }
    }
}
?>