<?php

namespace nsc\sdc\mail;

require __DIR__ . '/../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

    class Mail {

        public static function send_new_crew_email( $_crew ){

            $_html_body =
                "<h2>New crew</h2></br>" .
                "<p>" . $_crew->display_name . "</p></br>" .
                "<p>" . $_crew->membership_number . "</p></br>" .
                "<p>" . $_crew->skill . "</p></br>" .
                "<p>" . $_crew->email . "</p></br>" .
                "<p>" . $_crew->mobile . "</p></br>" .
                "<p>" . $_crew->social_preference . "</p></br>";

            self::send_new_subscriber_email( $_html_body );

        }

        public static function send_new_boat_email( $_boat ){

            $_html_body =
                "<h2>New boat</h2></br>" .
                "<p>" . $_boat->display_name . "</p></br>" .
                "<p>" . $_boat->email . "</p></br>" .
                "<p>" . $_boat->mobile . "</p></br>" .
                "<p>" . $_boat->social_preference . "</p></br>";


            self::send_new_subscriber_email( $_html_body );

        }

        private static function send_new_subscriber_email( $_html_body ) {

            try {
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
                $mail->Body = $_html_body;

//                $mail->AltBody = "";

                $mail->send();

                return;
            }
            catch (Exception $e) {
                return;
            }
        }
    }
?>