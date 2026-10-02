<?php
require_once(__DIR__ . "/dbcore.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
class Settings {
    public static $stripeKey = "";
    public static $loginUnknown = "inconnu";
    public static $codeCourseEpreuve = "C001";
    public static $paymentPeriodicity = array(
                                            ["Month", "Mois"], 
                                            ["Term", "Trimestre"], 
                                            ["Semester", "Semestre"], 
                                            ["Year", "Annee"]);

    private static function checkPeriodicity(array $arrayValues, string $perodicity): bool {
        for ($i=0; $i <count($arrayValues) ; $i++) { 
            if(strtoupper($perodicity) == strtoupper($arrayValues[$i])) {
                return true;
            }
        }
        return false;
    }
    public static function getNextDateFromPeriod(DateTime $date, string $perodicity):DateTime {
        $dateFinale = clone $date;
        if(self::checkPeriodicity(self::$paymentPeriodicity[0], $perodicity)) { // month
            $dateFinale->modify('+1 month');
        }

        else if(self::checkPeriodicity(self::$paymentPeriodicity[1], $perodicity)) { // Term
            $dateFinale->modify('+3 month');
        }

        else if(self::checkPeriodicity(self::$paymentPeriodicity[2], $perodicity)) { // Term
            $dateFinale->modify('+6 month');
        }

        else if(self::checkPeriodicity(self::$paymentPeriodicity[3], $perodicity)) { // month
            $dateFinale->modify('+12 month');
        }
        else {
            $dateFinale->modify('+1 month');
        }

        return $dateFinale;
    }

    public static function proceedPaymentByStripe($code, $childId, $amount, $startDate, $endDate):bool {
        return true;
    }

    public static function getHomePage(): string {
        return isLocal() ? "https://api.institutblaina.cm/webcourse/" : "https://learningkids.ca/";//"https://yehoshoualevivant.com/webcourse/";
    }
    public static function getWebsiteName(): string {
        return isLocal() ? "Learning Kids" : "Learning Kids";
    }
    public static function waitingTimeBetweenEmailsInSeconds(): int {
        return 30; //seconds
    }
    public static function maxBulkEmailsSentInAbatch(): int {
        return 500; //seconds
    }
    public static function warningDaysBeforeExpiration(): int {
        return 10; //seconds
    }

    public static function numberOfTrialMonths(): int {
        return 1; //months
    }

    public static function annualReduction(): int {
        return 10; //seconds
    }
    public static function buttonDimensionStyle() {
        return "padding: 15px 40px; font-size: 18px; border-radius: 5px; text-decoration: none; margin:10px; width:350px;";
    }
    public static function getLevelsInEnglish() {
        return [
                "Level1" => "1st Grade",
                "Level2" => "2nd Grade",
                "Level3" => "3rd Grade",
                "Level4" => "4th Grade",
                "Level5" => "5th Grade",
                "Level6" => "6th Grade",
                "Expert" => "Expert Level"
            ];
    }
    public static function getLevelsInFrench() {
        return [
                "COD1" => "1ère Année",
                "COD2" => "2ème Année",
                "COD3" => "3ème Année",
                "COD4" => "4ème Année",
                "COD5" => "5ème Année",
                "COD6" => "6ème Année",
                "Expert"=>"Niveau Expert"
            ];
    }
    public static function getLevelsInFrenchAutre() {
        return [
                "Level1" => "1ère Année",
                "Level2" => "2ème Année",
                "Level3" => "3ème Année",
                "Level4" => "4ème Année",
                "Level5" => "5ème Année",
                "Level6" => "6ème Année",
                "Expert"=>"Niveau Expert"
            ];
    }
}
?>
