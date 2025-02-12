<?php

// Path: api/src/Core/Component/Email.php

declare(strict_types=1);

namespace App\Core\Component;

use App\Core\Array\Environment;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Email extends PHPMailer
{
    public function __construct()
    {
        parent::__construct(true); // Passing `true` enables exceptions

        $this->setupSMTP();

        $this->setLanguage('fr', API . '/vendor/phpmailer/phpmailer/language/');

        $this->isHTML(true);   // Set email format to HTML
        $this->CharSet = 'UTF-8';
    }

    /**
     * Remplit les informations de connexion SMTP.
     */
    private function setupSMTP(): void
    {
        // Office 365 server settings
        if (Environment::getBool('MAIL_DEBUG', false)) {
            $debugLevel = Environment::getInt('MAIL_DEBUG_LEVEL', 0);
            if ($debugLevel < SMTP::DEBUG_OFF || $debugLevel > SMTP::DEBUG_LOWLEVEL) {
                $debugLevel = 0;
            }
            $this->SMTPDebug = $debugLevel;        // Enable verbose debug output

            $this->Debugoutput = function ($str, $level) {
                $timestamp = "[" . date('d-M-Y H:i:s e') . "] ";

                if (!\is_string($str)) {
                    return;
                }

                if (\is_numeric($level)) {
                    $level = (int) $level;
                } else {
                    return;
                }

                $message = $timestamp . PHP_EOL . "debug level $level; message: $str\n";

                $outputFile = '/var/log/phpmailog.log';
                $fileHandle = fopen($outputFile, 'a');

                if (!$fileHandle) {
                    return;
                }

                fwrite($fileHandle, $message);
                fclose($fileHandle);
            };
        }
        $this->isSMTP();                                           // Send using SMTP
        $this->SMTPAuth   = true;                                  // Enable SMTP authentication
        $this->SMTPSecure = self::ENCRYPTION_STARTTLS;             // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $this->Host       = Environment::getString('MAIL_HOST');   // Set the SMTP server to send through
        $this->Port       = Environment::getInt('MAIL_PORT', 587); // TCP port to connect to
        $this->Username   = Environment::getString('MAIL_USER');   // SMTP username
        $this->Password   = Environment::getString('MAIL_PASS');   // SMTP password

        if (Environment::getString('APP_ENV') === "development") {
            $this->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        }
    }

    /**
     * Ajout des adresses depuis la liste entrée en paramètre
     * 
     * @param array{email: ?string, name: ?string} $from Adresse e-mail et nom de l'expéditeur.
     * @param string[] $to   Adresses e-mail des destinataires.
     * @param string[] $cc   Adresses e-mail en copie.
     * @param string[] $bcc  Adresses e-mail en copie cachée.
     */
    public function addAddresses(
        array $from = [
            "email" => null,
            "name" => null,
        ],
        array $to = [],
        array $cc = [],
        array $bcc = [],
    ): void {
        // FROM
        $this->setFrom(
            $from["email"] ?? Environment::getString('MAIL_USER'),
            '=?utf-8?B?' . base64_encode($from["name"] ?? Environment::getString('MAIL_FROM')) . '?='
        );

        // TO
        foreach ($to as $address) {
            $address = trim($address, " \t\n\r\0\x0B-_;,");
            if (($address != '') && (\substr($address, 0, 1) != '!') && (\strpos($address, '@') == TRUE)) {
                $this->addAddress($address);
            }
        }

        // CC
        foreach ($cc as $address) {
            $this->addCC($address);
        }

        // BCC
        foreach ($bcc as $address) {
            $addresses = \explode(',', Environment::getString('MAIL_BCC'));
            foreach ($addresses as $address) {
                $address = trim($address, " \t\n\r\0\x0B-_;,");
                $this->addBCC($address);
            }
        }
    }

    /**
     * Récupération des adresses e-mail ajoutées.
     * 
     * @return array{
     *           from: string,
     *           to: string[],
     *           cc: string[],
     *           bcc: string[],
     *         } Liste des adresses e-mail réellement ajoutées.
     */
    public function getAllAddresses(): array
    {
        $fromAddress = base64_decode(\str_replace(["=?utf-8?B?", "?="], "", $this->FromName)) . " &lt;" . $this->From . "&gt;";

        $toAddresses = \array_map(
            function ($address) {
                /** @var array{string, string} $address */
                return $address[0];
            },
            $this->getToAddresses()
        );

        $ccAddresses = \array_map(
            function ($address) {
                /** @var array{string, string} $address */
                return $address[0];
            },
            $this->getCcAddresses()
        );

        $bccAddresses = \array_map(
            function ($address) {
                /** @var array{string, string} $address */
                return $address[0];
            },
            $this->getBccAddresses()
        );

        return [
            "from" => $fromAddress,
            "to" => $toAddresses,
            "cc" => $ccAddresses,
            "bcc" => $bccAddresses,
        ];
    }
}
