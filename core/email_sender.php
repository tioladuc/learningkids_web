<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/setting.php';
require_once __DIR__ . '/dbcore.php';

class EmailSender
{
    private $to;
    private $subject;
    private $message;
    private $headers;

    public function __construct($to, $subject, $message)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->message = $message;
        $this->headers = "From: no-reply@learningkids.com";
    }

    public function send()
    {
        return mail($this->to, $this->subject, $this->message, $this->headers);
    }

    public static function sendEmail(
        string $to,
        string $subject,
        string $htmlMessage,
        string $sendFrom = "noreply@learningkids.ca"
    ): bool {

        $headers = [];

        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=UTF-8";
        $headers[] = "From: " . Settings::getWebsiteName() . " <" . $sendFrom . ">";
        $headers[] = "Reply-To: " . $sendFrom;
        $headers[] = "X-Mailer: PHP/" . phpversion();

        return mail(
            $to,
            $subject,
            $htmlMessage,
            implode("\r\n", $headers)
        );
    }

    public static function newAccountWelcomeMessage(
        $email,
        $login,
        $codeParent,
        $first_name,
        $last_name
    ) {

        $subject = "Bienvenue sur Learn4Kids";

        $message = '
                    <html>
                    <head>
                        <meta charset="UTF-8">
                    </head>
                    <body style="font-family: Arial, sans-serif; background:#f4f6f9; padding:20px;">

                        <div style="
                            max-width:700px;
                            margin:auto;
                            background:#ffffff;
                            border-radius:12px;
                            overflow:hidden;
                            box-shadow:0 2px 10px rgba(0,0,0,.08);
                        ">

                            <div style="
                                background:linear-gradient(135deg,#4f46e5,#7c3aed);
                                color:white;
                                padding:30px;
                                text-align:center;
                            ">
                                <h1 style="margin:0;">
                                    Bienvenue sur Learn4Kids 🎉
                                </h1>
                            </div>

                            <div style="padding:30px;">

                                <p>
                                    Bonjour <strong>' . htmlspecialchars($first_name . ' ' . $last_name) . '</strong>,
                                </p>

                                <p>
                                    Votre compte parent a été créé avec succès.
                                </p>

                                <p>
                                    Vous pouvez désormais vous connecter à votre espace parent,
                                    suivre les activités de vos enfants et créer leurs comptes.
                                </p>

                                <div style="
                                    background:#eef2ff;
                                    border-left:5px solid #4f46e5;
                                    padding:20px;
                                    margin:20px 0;
                                    border-radius:8px;
                                ">

                                    <h3 style="margin-top:0;">
                                        Informations de connexion
                                    </h3>

                                    <p>
                                        <strong>Adresse e-mail :</strong>
                                        ' . htmlspecialchars($email) . '
                                    </p>

                                    <p>
                                        <strong>Login :</strong>
                                        ' . htmlspecialchars($login) . '
                                    </p>

                                    <p>
                                        <strong>Code Parent :</strong>
                                        ' . htmlspecialchars($codeParent) . "
                                    </p>

                                </div>

                                <p>
                                    Conservez ces informations dans un endroit sûr.
                                </p>

                                <p>
                                    Pour des raisons de sécurité, votre mot de passe n'est jamais envoyé par e-mail.
                                </p>

                                <p>
                                    Merci de votre confiance et bienvenue dans la communauté Learn4Kids.
                                </p>

                                <br>

                                <p>
                                    L'équipe Learn4Kids
                                </p>

                            </div>

                        </div>

                    </body>
                    </html>";


        return self::sendEmail(
            $email,
            $subject,
            $message
        );
    }


    public static function sendPasswordResetCode(
        $email,
        $first_name,
        $code, $codeParent
    ) {        
        $subject = "Code de réinitialisation Learn4Kids";

        $message = '
                <html>
                <body style="font-family:Arial,sans-serif">

                    <h2>Réinitialisation du mot de passe</h2>

                    <p>Bonjour ' . htmlspecialchars($first_name) . ',</p>

                    <p>
                        Vous avez demandé la réinitialisation de votre mot de passe.
                    </p>

                    <p>
                        Votre code parent est :
                    </p>

                    <div style="
                        font-size:32px;
                        font-weight:bold;
                        text-align:center;
                        padding:20px;
                        margin:20px 0;
                        background:#eef2ff;
                        border-radius:10px;
                    ">
                        ' . $codeParent . '
                    </div>

                    <p>
                        Votre code de vérification est :
                    </p>

                    <div style="
                        font-size:32px;
                        font-weight:bold;
                        text-align:center;
                        padding:20px;
                        margin:20px 0;
                        background:#eef2ff;
                        border-radius:10px;
                    ">
                        ' . $code . '
                    </div>

                    <p>
                        Si vous n\'êtes pas à l\'origine de cette demande,
                        ignorez simplement ce message.
                    </p>

                </body>
                </html>';

        return self::sendEmail(
            $email,
            $subject,
            $message
        );
    }

    public static function sendPaymentEmailUniqueCourse($idCourseChild)
    {
        $pdo = connectBD();
        $query = "SELECT
                    c.name as course_name,
                    c.validity as course_periodicity,
                    c.amount as course_amount,
                    now() as payment_date,

                    child.name as child_name,

                    parent.last_name as parent_last_name,
                    parent.first_name as parent_first_name,
                    parent.email

                    FROM learn4kids_child_courses cc
                    JOIN learn4kids_courses c ON (cc.course_code = c.code)
                    JOIN learn4kids_children child ON (cc.child_id = child.id)
                    JOIN learn4kids_parents parent ON (child.parent_id = parent.id)

                    WHERE cc.id = $idCourseChild";

        $stmt = $pdo->prepare($query);
        $stmt->execute();

        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        self::courseSubscriptionPaymentConfirmation(
            $item['email'],
            $item['parent_first_name'],
            $item['parent_last_name'],
            $item['child_name'],
            $item['course_name'],
            $item['course_periodicity'],
            $item['course_amount'],
            $item['payment_date']
        );
    }

    /**
     * Send course subscription payment confirmation
     *
     * @param string $email
     * @param string $parentFirstName
     * @param string $parentLastName
     * @param string $childName
     * @param string $courseName
     * @param string $coursePeriodicity
     * @param float  $courseAmount
     * @param string $paymentDate
     * @return bool
     */
    private static function courseSubscriptionPaymentConfirmation(
        $email,
        $parentFirstName,
        $parentLastName,
        $childName,
        $courseName,
        $coursePeriodicity,
        $courseAmount,
        $paymentDate
    ) {

        $subject = "Confirmation de paiement / Payment Confirmation";

        $amount = number_format($courseAmount, 2);

        $message = <<<HTML
            <!DOCTYPE html>
            <html>
                <head>
                    <meta charset="UTF-8">
                </head>

                <body style="font-family:Arial,Helvetica,sans-serif;background:#f5f5f5;margin:0;padding:20px;">

                    <table width="700" align="center" cellpadding="0" cellspacing="0"
                    style="background:#ffffff;border-radius:8px;padding:30px;">

                    <tr>
                    <td>

                    <h2 style="color:#2E86C1;">
                    Confirmation de paiement
                    </h2>

                    <p>
                    Bonjour <strong>{$parentFirstName} {$parentLastName}</strong>,
                    </p>

                    <p>
                    Nous vous remercions pour votre paiement.
                    Votre abonnement a été activé avec succès.
                    </p>

                    <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;">

                    <tr style="background:#eeeeee;">
                    <td><strong>Enfant</strong></td>
                    <td>{$childName}</td>
                    </tr>

                    <tr>
                    <td><strong>Cours</strong></td>
                    <td>{$courseName}</td>
                    </tr>

                    <tr style="background:#eeeeee;">
                    <td><strong>Validité</strong></td>
                    <td>{$coursePeriodicity}</td>
                    </tr>

                    <tr>
                    <td><strong>Montant payé</strong></td>
                    <td>{$amount}</td>
                    </tr>

                    <tr style="background:#eeeeee;">
                    <td><strong>Date du paiement</strong></td>
                    <td>{$paymentDate}</td>
                    </tr>

                    </table>

                    <br>

                    <p>
                    Votre enfant peut maintenant accéder immédiatement à ce cours depuis son compte Learning Kids.
                    </p>

                    <p>
                    Merci pour votre confiance.
                    </p>

                    <hr>

                    <h2 style="color:#2E86C1;">
                    Payment Confirmation
                    </h2>

                    <p>
                    Dear <strong>{$parentFirstName} {$parentLastName}</strong>,
                    </p>

                    <p>
                    Thank you for your payment.
                    Your course subscription has been successfully activated.
                    </p>

                    <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;">

                    <tr style="background:#eeeeee;">
                    <td><strong>Child</strong></td>
                    <td>{$childName}</td>
                    </tr>

                    <tr>
                    <td><strong>Course</strong></td>
                    <td>{$courseName}</td>
                    </tr>

                    <tr style="background:#eeeeee;">
                    <td><strong>Subscription Period</strong></td>
                    <td>{$coursePeriodicity}</td>
                    </tr>

                    <tr>
                    <td><strong>Amount Paid</strong></td>
                    <td>{$amount}</td>
                    </tr>

                    <tr style="background:#eeeeee;">
                    <td><strong>Payment Date</strong></td>
                    <td>{$paymentDate}</td>
                    </tr>

                    </table>

                    <br>

                    <p>
                    Your child now has immediate access to this course through their Learning Kids account.
                    </p>

                    <p>
                    Thank you for choosing Learning Kids.</p>

                    <br>

                    <div style="text-align:center;color:#888888;font-size:12px;">
                    © Learning Kids
                    </div>

                    </td>
                    </tr>

                    </table>

                </body>
            </html>
            HTML;

        return self::sendEmail($email, $subject, $message);
    }

    /**
     * Send a bilingual payment confirmation email after a basket has been paid.
     *
     * @param PDO $pdo
     * @param int $idBasket
     * @return bool
     */
    public static function sendPaymentEmailForUniqueBasket(int $idBasket): bool
    {
        $pdo = connectBD();
        $sql = "
                SELECT
                    item.course_code AS course_code,
                    item.price AS course_price,
                    c.name AS course_name,
                    c.validity AS course_periodicity,

                    item.child_id,
                    child.name AS child_name,

                    basket.current_id AS parent_id,
                    parent.first_name AS parent_first_name,
                    parent.last_name AS parent_last_name,
                    parent.email,

                    basket.total_items,
                    basket.total_amount,
                    basket.payment_date

                FROM learn4kids_basket basket

                JOIN learn4kids_basket_item item
                    ON basket.id = item.basket_id

                JOIN learn4kids_children child
                    ON child.id = item.child_id

                JOIN learn4kids_parents parent
                    ON parent.id = basket.current_id

                JOIN learn4kids_courses c
                    ON c.code = item.course_code

                WHERE basket.id = ?

                ORDER BY child.name, c.name
                ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idBasket]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return false;
        }

        //-----------------------------------------
        // Parent information
        //-----------------------------------------

        $parentFirstName = $rows[0]['parent_first_name'];
        $parentLastName  = $rows[0]['parent_last_name'];
        $email           = $rows[0]['email'];

        $totalItems      = $rows[0]['total_items'];
        $totalAmount     = number_format($rows[0]['total_amount'], 2);
        $paymentDate     = $rows[0]['payment_date'];

        //-----------------------------------------
        // Group courses by child
        //-----------------------------------------

        $children = [];

        foreach ($rows as $row) {

            $child = $row['child_name'];

            if (!isset($children[$child])) {
                $children[$child] = [];
            }

            $children[$child][] = $row;
        }

        //-----------------------------------------
        // French table
        //-----------------------------------------

        $tableFR = "";

        foreach ($children as $childName => $courses) {

            $tableFR .= "
        <h3 style='color:#1E88E5;'>Enfant : {$childName}</h3>

        <table width='100%' cellpadding='8' cellspacing='0'
        style='border-collapse:collapse;border:1px solid #DDD;margin-bottom:20px;'>

        <tr style='background:#F2F2F2;font-weight:bold;'>
            <td>Cours</td>
            <td>Validité</td>
            <td align='right'>Prix</td>
        </tr>";

            foreach ($courses as $course) {

                $price = number_format($course['course_price'], 2);

                $tableFR .= "
            <tr>
                <td>{$course['course_name']}</td>
                <td>{$course['course_periodicity']}</td>
                <td align='right'>{$price}</td>
            </tr>";
            }

            $tableFR .= "</table>";
        }

        //-----------------------------------------
        // English table
        //-----------------------------------------

        $tableEN = "";

        foreach ($children as $childName => $courses) {

            $tableEN .= "
        <h3 style='color:#1E88E5;'>Child: {$childName}</h3>

        <table width='100%' cellpadding='8' cellspacing='0'
        style='border-collapse:collapse;border:1px solid #DDD;margin-bottom:20px;'>

        <tr style='background:#F2F2F2;font-weight:bold;'>
            <td>Course</td>
            <td>Validity</td>
            <td align='right'>Price</td>
        </tr>";

            foreach ($courses as $course) {

                $price = number_format($course['course_price'], 2);

                $tableEN .= "
            <tr>
                <td>{$course['course_name']}</td>
                <td>{$course['course_periodicity']}</td>
                <td align='right'>{$price}</td>
            </tr>";
            }

            $tableEN .= "</table>";
        }

        //-----------------------------------------
        // Email
        //-----------------------------------------

        $subject = "Learn4Kids - Payment Confirmation / Confirmation de paiement";

        $html = "
            <!DOCTYPE html>
            <html>
                <head>
                <meta charset='UTF-8'>
                </head>

                <body style='font-family:Arial;background:#F4F6F8;margin:0;padding:30px;'>

                <div style='max-width:900px;background:#FFFFFF;margin:auto;padding:40px;border-radius:8px;'>

                <h2 style='color:#1E88E5;'>
                Confirmation de paiement
                </h2>

                <p>
                Bonjour <strong>{$parentFirstName} {$parentLastName}</strong>,
                </p>

                <p>
                Merci d'avoir choisi <strong>Learn4Kids</strong>.
                Votre paiement a été reçu avec succès.
                Les abonnements suivants sont maintenant actifs.
                </p>

                {$tableFR}

                <table width='100%' style='margin-top:20px;'>

                <tr>
                <td><strong>Nombre de cours :</strong></td>
                <td>{$totalItems}</td>
                </tr>

                <tr>
                <td><strong>Montant payé :</strong></td>
                <td><strong>{$totalAmount}</strong></td>
                </tr>

                <tr>
                <td><strong>Date du paiement :</strong></td>
                <td>{$paymentDate}</td>
                </tr>

                </table>

                <hr style='margin:40px 0;'>

                <h2 style='color:#1E88E5;'>
                Payment Confirmation
                </h2>

                <p>
                Dear <strong>{$parentFirstName} {$parentLastName}</strong>,
                </p>

                <p>
                Thank you for choosing <strong>Learn4Kids</strong>.
                Your payment has been successfully received.
                The following subscriptions are now active.
                </p>

                {$tableEN}

                <table width='100%' style='margin-top:20px;'>

                <tr>
                <td><strong>Number of Courses:</strong></td>
                <td>{$totalItems}</td>
                </tr>

                <tr>
                <td><strong>Total Paid:</strong></td>
                <td><strong>{$totalAmount}</strong></td>
                </tr>

                <tr>
                <td><strong>Payment Date:</strong></td>
                <td>{$paymentDate}</td>
                </tr>

                </table>

                <p style='margin-top:40px;'>
                Your children can now immediately access their courses from their Learn4Kids accounts.
                </p>

                <p>
                Thank you for your trust.<br>
                The ". Settings::getWebsiteName() ." Team
                </p>

                <hr>

                <div style='text-align:center;color:#777;font-size:12px;'>
                © Learn4Kids
                </div>

                </div>

                </body>
            </html>";

        //-----------------------------------------
        // Send
        //-----------------------------------------

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Learn4Kids <no-reply@learn4kids.ca>\r\n";
        $headers .= "Reply-To: support@learn4kids.ca\r\n";

        return mail($email, $subject, $html, $headers);

        // If you already have your own email sender:
        // return EmailSender::sendEmail($email, $subject, $html);
    }
}
