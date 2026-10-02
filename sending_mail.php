<?php
require_once  __DIR__ . '/core/dbcore.php';
require_once  __DIR__ . '/core/setting.php';

$pdo = connectBD();
$query = "SELECT count(*) as total FROM learn4kids_emails WHERE sentDate IS NULL AND is_active=1";
$stmt = $pdo->prepare($query);
$stmt->execute();
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

if($total > 0) {
    SendingBulkEmails();
}
else {
    PreparingEmailsOfToday();
}

function SendingBulkEmails() {
    $pdo = connectBD();
    $waitingTime = Settings::waitingTimeBetweenEmailsInSeconds();
    $numberOfBunchEmails = Settings::maxBulkEmailsSentInAbatch();
    set_time_limit($waitingTime * $numberOfBunchEmails + 5 * $numberOfBunchEmails); // set time limit for the script to run

    $query = "SELECT * FROM learn4kids_emails WHERE sentDate IS NULL AND is_active=1 LIMIT $numberOfBunchEmails;";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($emails as $email) {
        // send email
        EmailSender::sendEmail($email['sent_to'], $email['subject'], $email['content'], $email['send_from']);
        // update sentDate
        $updateQuery = "UPDATE learn4kids_emails SET sentDate=now() WHERE id=?";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute([$email['id']]);

        // wait for the specified time before sending the next email
        sleep($waitingTime);
    }
}

function PreparingEmailsOfToday(){
    $pdo = connectBD();
    $alertNumberOfDaysBeforeExpiration = Settings::warningDaysBeforeExpiration(); // days
    #region email content sql
    $query = "SELECT 

                child.parent_id,
                parent.first_name,
                parent.last_name,
                parent.email

                lcc.child_id,
                child.name as child_name,

                lcc.course_code, 
                crs.name as course_name,

                crs.amount as course_price,
                crs.validity as course_periodicity,

                lcc.id, 
                lcc.is_paid, 
                lcc.picked_date, 
                lcc.expiry_date

            FROM learn4kids_child_courses lcc 
            JOIN learn4kids_courses crs ON (crs.code = lcc.course_code)
            JOIN learn4kids_children child ON (child.id = lcc.child_id)
            JOIN learn4kids_parents parent ON (parent.id = child.parent_id)

            WHERE ( lcc.expiry_date IS NULL OR lcc.expiry_date <= DATE_ADD(NOW(), INTERVAL $alertNumberOfDaysBeforeExpiration DAY) ) AND
            lcc.is_active = 1 AND  crs.is_active = 1 AND child.is_active = 1 AND parent.is_active = 1

    GROUP BY child.parent_id, lcc.expiry_date";
    #endregion

    //  {first_last_name}    {courses_not_yet_purchased}    {courses_expired}    {courses_soon_to_expire}    {url_website}    {website_name}

    #region email content manager
    $title_fr = "État de vos cours sur la plateforme";
    $title_en = "Status of your courses on the platform";
    $email_content_fr = "Bonjour <strong>{first_last_name}</strong>,<br><br><br>

    Nous souhaitons vous informer de l’état actuel de vos cours sur notre plateforme d’apprentissage.<br><br>

    Voici les cours qui nécessitent votre attention :<br><br>

    <strong>📚 Cours non encore achetés</strong>
    <ol>
        {courses_not_yet_purchased}
    </ol>
    <br><br>


    <strong>⏰ Cours dont la souscription est expirée</strong>
    <ol>
        {courses_expired}
    </ol>
    <br><br>


    <strong>⚠️ Cours dont la souscription arrive bientôt à expiration</strong>
    <ol>
        {courses_soon_to_expire}
    </ol>
    <br><br>


    Pour continuer à bénéficier de l’accès à vos cours et renouveler vos souscriptions, nous vous invitons à vous connecter à votre espace personnel sur :

    <strong><i>{url_website}</i></strong>
    <br><br>
    Une fois connecté, vous pourrez consulter vos cours, renouveler vos souscriptions et accéder aux contenus disponibles pour votre enfant.
    <br><br>

    Nous vous remercions de votre confiance et vous souhaitons une excellente expérience d’apprentissage avec <strong><i>{website_name}</i></strong>.
    <br><br>

    Cordialement,
    <br><br>
    <strong>L’équipe <strong><i>{website_name}</i></strong></strong>
    ";

    $email_content_en = "Hello <strong>{first_last_name}</strong>,<br><br><br>

    We would like to inform you about the current status of your courses on our learning platform.<br><br>

    Here are the courses that require your attention:<br><br>

    <strong>📚 Courses not yet purchased</strong>

    <ol>
        {courses_not_yet_purchased}
    </ol>
    <br><br>

    <strong>⏰ Courses with expired subscriptions</strong>

    <ol>
        {courses_expired}
    </ol>
    <br><br>

    <strong>⚠️ Courses whose subscriptions are about to expire</strong>

    <ol>
        {courses_soon_to_expire}
    </ol>
    <br><br>

    To continue enjoying access to your courses and renew your subscriptions, we invite you to log in to your personal account at:

    <strong><i>{url_website}</i></strong> <br><br>
    Once logged in, you will be able to view your courses, renew your subscriptions, and access the content available for your child. <br><br>

    Thank you for your trust. We wish you an excellent learning experience with <strong><i>{website_name}</i></strong>. <br><br>

    Best regards, <br><br> <strong>The <strong><i>{website_name}</i></strong> Team</strong>
    ";
    #endregion

    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $currentTitleFr = $title_fr;
    $currentTitleEn = $title_en;
    $currentEmailContentFr = $email_content_fr;
    $currentEmailContentEn = $email_content_en;
    $currentCoursesExpired = "";
    $currentCoursesSoonToExpire = "";
    $currentCoursesNotYetPurchased = "";
    $parent_id = null;

    for ($i=0; $i < count($items) ; $i++) { 
        
        $item = $items[$i];

        if($parent_id == null) {
            $parent_id = $item['parent_id'];
            $currentTitleFr = $title_fr;
            $currentTitleEn = $title_en;
            $currentEmailContentFr = $email_content_fr;
            $currentEmailContentEn = $email_content_en;
            $currentCoursesExpired = "";
            $currentCoursesSoonToExpire = "";
            $currentCoursesNotYetPurchased = "";
        }

        if($parent_id != $item['parent_id']) {
            // send email to previous parent
            prepareEmailToSendInDatabase($currentTitleFr, $currentTitleEn, $currentEmailContentFr, $currentEmailContentEn, $parent_id, $currentCoursesExpired, $currentCoursesSoonToExpire, $currentCoursesNotYetPurchased, $items[$i-1]);
            // reset variables        
            $parent_id = $item['parent_id'];
            $currentTitleFr = $title_fr;
            $currentTitleEn = $title_en;
            $currentEmailContentFr = $email_content_fr;
            $currentEmailContentEn = $email_content_en;
            $currentCoursesExpired = "";
            $currentCoursesSoonToExpire = "";
            $currentCoursesNotYetPurchased = "";
        }

        // check if course is expired or soon to expire or not yet purchased
        if($item['is_paid'] == 0) {
            // not yet purchased
            $currentCoursesNotYetPurchased .= "<li>" . htmlspecialchars($item['course_name']) . " [ " . htmlspecialchars($item['course_code']) . " ] - <i>" . htmlspecialchars($item['child_name']) . "</i></li>";
        } else {
            // check expiry date
            if($item['expiry_date'] != null) {
                $expiryDate = new DateTime($item['expiry_date']);
                $now = new DateTime();
                if($expiryDate < $now) {
                    // expired
                    $currentCoursesExpired .= "<li>" . htmlspecialchars($item['course_name']) . " [ " . htmlspecialchars($item['course_code']) . " ] - <i>" . htmlspecialchars($item['child_name']) . "</i></li>";
                } else {
                    // check if soon to expire (within 10 days)
                    $interval = $now->diff($expiryDate);
                    if($interval->days <= $alertNumberOfDaysBeforeExpiration) {
                        // soon to expire
                        $currentCoursesSoonToExpire .= "<li>" . htmlspecialchars($item['course_name']) . " [ " . htmlspecialchars($item['course_code']) . " ] - <i>" . htmlspecialchars($item['child_name']) . "</i></li>";
                    }
                }
            }
        }
    }
    prepareEmailToSendInDatabase($currentTitleFr, $currentTitleEn, $currentEmailContentFr, $currentEmailContentEn, $parent_id, $currentCoursesExpired, $currentCoursesSoonToExpire, $currentCoursesNotYetPurchased, $items[count($items) - 1]);
}

function prepareEmailToSendInDatabase($titleFr, $titleEn, $contentFr, $contentEn, $parentId, $coursesExpired, $coursesSoonToExpire, $coursesNotYetPurchased, $lastItem) {
    // get parent info
    /*$pdo = connectBD();
    $stmt = $pdo->prepare("SELECT * FROM learn4kids_parents WHERE id = ?");
    $stmt->execute([$parentId]);*/
    $parent = $lastItem;//$stmt->fetch(PDO::FETCH_ASSOC);

    if($parent) {
        // replace placeholders in content
        $contentFr = str_replace("{first_last_name}", htmlspecialchars($parent['first_name'] . " " . $parent['last_name']), $contentFr);
        $contentEn = str_replace("{first_last_name}", htmlspecialchars($parent['first_name'] . " " . $parent['last_name']), $contentEn);
        $contentFr = str_replace("{courses_not_yet_purchased}", $coursesNotYetPurchased == "" ? "<li>Aucun</li>" : $coursesNotYetPurchased, $contentFr);
        $contentEn = str_replace("{courses_not_yet_purchased}", $coursesNotYetPurchased == "" ? "<li>None</li>" : $coursesNotYetPurchased, $contentEn);
        $contentFr = str_replace("{courses_expired}", $coursesExpired == "" ? "<li>Aucun</li>" : $coursesExpired, $contentFr);
        $contentEn = str_replace("{courses_expired}", $coursesExpired == "" ? "<li>None</li>" : $coursesExpired, $contentEn);
        $contentFr = str_replace("{courses_soon_to_expire}", $coursesSoonToExpire == "" ? "<li>Aucun</li>" : $coursesSoonToExpire, $contentFr);
        $contentEn = str_replace("{courses_soon_to_expire}", $coursesSoonToExpire == "" ? "<li>None</li>" : $coursesSoonToExpire, $contentEn);

        // website url and name
        $url = Settings::getHomePage();
        $websiteName = Settings::getWebsiteName();
        $contentFr = str_replace("{url_website}", $url, $contentFr);
        $contentEn = str_replace("{url_website}", $url, $contentEn);
        $contentFr = str_replace("{website_name}", $websiteName, $contentFr);
        $contentEn = str_replace("{website_name}", $websiteName, $contentEn);
        
        $finalTitle = $titleFr . " / " . $titleEn;
        $finalContent = $contentFr . "<br><br><hr><br><br>" . $contentEn;

        //Insert into learn4kids_emails
        $pdo = connectBD();
        $stmt = $pdo->prepare("INSERT INTO learn4kids_emails (createdDate, sentDate, is_active, subject, sent_to, send_from, content, parent_id) VALUES (now(), null, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([ 1, $finalTitle, $parent['email'], 'no-reply@learningkids.ca', $finalContent, $parentId]);
    }
}
        
?>