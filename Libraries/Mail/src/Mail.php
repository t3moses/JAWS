<?php

namespace nsc\sdc\mail;

require __DIR__ . '/../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

    class Mail {

        public static function send_new_crew_email( $_display_name, $_membership_number,
        $_crew_skill, $_crew_email, $_crew_mobile, $_crew_social_preference,
        $_crew_notification_preference ){

            $_html_body =
                "<h2>New crew</h2></br>" .
                "<p>Display name: " . $_display_name . "</p>" .
                "<p>Membership number: " . $_membership_number . "</p>" .
                "<p>Skill: " . $_crew_skill . "</p>" .
                "<p>Email address: " . $_crew_email . "</p>" .
                "<p>Mobile number: " . $_crew_mobile . "</p>" .
                "<p>Social preference: " . $_crew_social_preference . "</p>" .
                "<p>Notification preference: " . $_crew_notification_preference . "</p>" .
                "<p>If notification preference is 'Yes', paste:</p>" .
                "</p>" .
                "<p>aws ses verify-email-identity --email-address" . " " . $_crew_email . "</p>" .
                "</p>" .
                "<p>into the SES command line.</p>";

            self::send_new_subscriber_email( $_html_body );

        }

        public static function send_new_boat_email( $_display_name, $_owner_email, $_owner_mobile, $_social_preference ){

            $_html_body =
                "<h2>New boat</h2></br>" .
                "<p>Display name: " . $_display_name . "</p>" .
                "<p>Email address: " . $_owner_email . "</p>" .
                "<p>Mobile number: " . $_owner_mobile . "</p>" .
                "<p>Social preference: " . $_social_preference . "</p>" .
                "<p>Paste:</p>" .
                "</p>" .
                "<p>aws ses verify-email-identity --email-address" . " " . $_owner_email . "</p>" .
                "</p>" .
                "<p>into the SES command line.</p>";


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