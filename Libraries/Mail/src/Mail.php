<?php

namespace nsc\sdc\mail;

require __DIR__ . '/../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

    class Mail {

        public static function send_new_subscriber_email() {

            $mail = new PHPMailer(true);

            $_config_fname = __DIR__ . '/../data/config.json';
            $_config_file = file_get_contents( $_config_fname );
            $config_data = json_decode( $_config_file, true );

            // SMTP settings
            $mail->isSMTP();
            $mail->Host = 'email-smtp.' . getenv('SES_REGION') . '.amazonaws.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('SES_SMTP_USERNAME');
            $mail->Password = getenv('SES_SMTP_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $to_email = $config_data[ 'config' ][ 'to_email' ];
            $to_name = $config_data[ 'config' ][ 'to_name' ];

            $mail->setFrom($config_data[ 'config' ][ 'from_email' ], $config_data[ 'config' ][ 'from_name' ]);
            $mail->addAddress($to_email, $to_name);
            $mail->Subject = $config_data['config']['subject'];
            
            $mail->isHTML(true);
            $mail->Body = "
                <h2>New Subscriber</h2>
                <p>There is a new subscriber</p>
            ";
            $mail->AltBody = "There is a new subscriber";

            $mail->send();

        }
    }
?>