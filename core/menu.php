<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once __DIR__ . "/setting.php";

class Menu
{
    public static string $menuStaticCSSMenu = "/* Floating button */
                                    .blinking-button {
                                        background: #25eb88;
                                        color: black;
                                        border: none;
                                        padding: 10px 14px;
                                        border-radius: 8px;
                                        text-decoration: none;
                                        cursor: pointer;
                                        display: inline-block;
                                        animation: fade-blink 5.5s infinite;
                                    }
                                    @keyframes fade-blink {
                                        0%, 100% { opacity: 1; }
                                        50% { opacity: 0; }
                                    }
                                    .floating-btn{
                                        position:fixed;
                                        top:20px;
                                        right:20px;
                                        z-index:9999;

                                        background:linear-gradient(135deg,#0d6efd,#4da3ff);
                                        color:#fff;

                                        padding:12px 18px;

                                        border-radius:50px;

                                        cursor:pointer;

                                        font-weight:bold;

                                        box-shadow:0 8px 20px rgba(0,0,0,.25);

                                        transition:.3s;
                                    }

                                    .floating-btn:hover{
                                        transform:translateY(-2px);
                                        box-shadow:0 12px 25px rgba(0,0,0,.35);
                                    }


                                    /* Floating menu */

                                    .menu{

                                        position:absolute;

                                        top:60px;

                                        right:0;

                                        width:240px;

                                        background:#fff;

                                        border-radius:15px;

                                        overflow:hidden;

                                        box-shadow:0 12px 30px rgba(0,0,0,.20);

                                        animation:fadeIn .25s ease;

                                    }


                                    /* UL */

                                    .floatingMenu{

                                        margin:0;

                                        padding:0;

                                        list-style:none;

                                    }


                                    /* LI */

                                    .floatingMenu li{

                                        border-bottom:1px solid #eee;

                                    }

                                    .floatingMenu li:last-child{

                                        border-bottom:none;

                                    }


                                    /* Links */

                                    .floatingMenu li a{

                                        display:block;

                                        padding:14px 18px;

                                        color:#333;

                                        text-decoration:none;

                                        font-size:16px;

                                        transition:.25s;

                                    }


                                    /* Hover */

                                    .floatingMenu li a:hover{

                                        background:#0d6efd;

                                        color:white;

                                        padding-left:28px;

                                    }


                                    /* Active */

                                    .floatingMenu li a.active{

                                        background:#0d6efd;

                                        color:white;

                                    }


                                    /* Animation */

                                    @keyframes fadeIn{

                                        from{
                                            opacity:0;
                                            transform:translateY(-10px);
                                        }

                                        to{
                                            opacity:1;
                                            transform:translateY(0);
                                        }

                                    }


                                    /* Responsive */

                                    @media(max-width:768px){

                                        .floating-btn{

                                            left:10px;
                                            right:10px;

                                            top:10px;

                                            text-align:center;

                                            width:auto;
                                        }

                                        .menu{

                                            position:relative;

                                            width:100%;

                                            top:15px;

                                            right:auto;
                                        }

                                        .floatingMenu li a{

                                            padding:16px;

                                            font-size:18px;

                                        }

                                    }
                                        @keyframes blink {
                                                0%, 50% {
                                                    opacity: 1;
                                                }
                                                50.01%, 100% {
                                                    opacity: 0;
                                                }
                                            }
                                                
                                            
                                            
                                            
                                            
                                            
                                            
                                            
                                            
                                            ";

    
    public static string $menuStaticCSSCommon = "  
            .div-buttom-violet {
                margin-top: 20px;
                display: inline-block;
                background: #4f46e5;
                color: white;
                padding: 12px 18px;
                border-radius: 12px;
                font-weight: bold;
                text-decoration: none;
            }
            .div-buttom-red {
                margin-top: 20px;
                display: inline-block;
                background: red;
                color: white;
                padding: 12px 18px;
                border-radius: 12px;
                font-weight: bold;
                text-decoration: none;
            }
            .input-submit-blue-bottom {
                background-color:blue; color:white; font-weight:bold; font-size:16px; padding: 10px 20px; border-radius: 10px;
                transition: background-color 0.9s ease, transform 0.9s ease;
                text-decoration: none;
            }
            .input-submit-black-bottom {
                background-color:black; color:white; font-weight:bold; font-size:16px; padding: 10px 20px; border-radius: 10px;
                transition: background-color 0.9s ease, transform 0.9s ease;
                text-decoration: none;
            }
            .animated-select {
                width: 250px;
                padding: 10px 14px;
                font-size: 16px;

                border: 1px solid #ccc;
                border-radius: 8px;
                background-color:  #d6e1e9;

                cursor: pointer;
                outline: none;

                transition: all 0.3s ease;
            }

            .animated-select:hover {
                border-color: #3498db;
            }

            .animated-select:focus {
                border-color: #3498db;
                box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
            } 
            .animated-input-text  {
                border-radius: 10px; padding: 10px;min-width: 300px; border: 2px solid #3498db;
            }        
            .animated-list {
                list-style: none;
                padding: 0;
                margin: 0;
                counter-reset: item;
            }

            .animated-list li {
                counter-increment: item;
                
                position: relative;
                padding: 12px 20px 12px 60px;
                margin-bottom: 12px;
                
                outline: none;
            }

            /* The circle containing the number */
            .animated-list li::before {
                content: counter(item);

                position: absolute;
                left: 0;
                top: 50%;
                transform: translateY(-50%);

                width: 38px;
                height: 38px;

                border: 2px solid #3498db;
                border-radius: 50%;

                display: flex;
                align-items: center;
                justify-content: center;

                font-weight: bold;
                transition: all 0.3s ease;
            }

            /* Mouse hover OR keyboard focus */
            .animated-list li:hover::before,
            .animated-list li:focus::before {
                transform: translateY(-50%) scale(1.2) rotate(10deg);
                background: #3498db;
                color: white;
                box-shadow: 0 0 0 6px rgba(52, 152, 219, 0.15);
            }";

    //public static string $menuStaticCSS = Menu::$menuStaticCSSCommon . Menu::$menuStaticCSSMenu;
    
    public static function menuStaticCSS() {
        return "<style>". Menu::$menuStaticCSSCommon . Menu::$menuStaticCSSMenu ."</style>";
    }
    public static function menuStaticMenu($menuOther)
    {
        $baseUrl = Settings::getHomePage();
        $menuBase = "<div class='menu' style='display: none;' id=\"floating-menu\">";
        $menuBase .= "<ul class='floatingMenu'>";
        $menu = "";
        if ($menuOther != null && trim($menuOther) != "") $menu .= "<li>$menuOther</li>";

        if ($_SESSION['webconnect']) {
            $menu .= "<li><a href=\"" . $baseUrl . "\"><i class='fa fa-house'></i> Accueil</a></li>";

            if(isset($_SESSION['courses']['C001']) || isset($_SESSION[$_SESSION['current_id']]['courses']['C001'])) $menu .= "<li><a href=\"" . $baseUrl . "epreuves/work/\"><i class='fa fa-list-check'></i> Epreuves</a></li>";            
            if(isset($_SESSION['courses']['C002']) || isset($_SESSION[$_SESSION['current_id']]['courses']['C002'])) $menu .= "<li><a href=\"" . $baseUrl . "talk/work/\"><i class='fa fa-comments'></i> Talk</a></li>";
            if(isset($_SESSION['courses']['C003']) || isset($_SESSION[$_SESSION['current_id']]['courses']['C003'])) $menu .= "<li><a href=\"" . $baseUrl . "exposes/work/\"><i class='fa fa-chalkboard-user'></i> Présentation</a></li>";

            if(isset($_SESSION['courses']['C004']) || isset($_SESSION[$_SESSION['current_id']]['courses']['C004'])) $menu .= "<li><a href=\"" . $baseUrl . "calcul/work/\"><i class='fa fa-list-check'></i> Calcul</a></li>";
            if(isset($_SESSION['courses']['C005']) || isset($_SESSION[$_SESSION['current_id']]['courses']['C005'])) $menu .= "<li><a href=\"" . $baseUrl . "comprehension/work/\"><i class='fa fa-chalkboard-user'></i> Comprehension Texte</a></li>";
            if(isset($_SESSION['courses']['C006']) || isset($_SESSION[$_SESSION['current_id']]['courses']['C006'])) $menu .= "<li><a href=\"" . $baseUrl . "situationprobleme/work/\"><i class='fa fa-comments'></i> Situation Probleme</a></li>";

            if(isset($_SESSION['courses']['C007']) || isset($_SESSION[$_SESSION['current_id']]['courses']['C007'])) $menu .= "<li><a href=\"" . $baseUrl . "dictee/work/\"><i class='fa fa-chalkboard-user'></i> Dictée</a></li>";
            //if(isset($_SESSION['courses']['C008']) || isset($_SESSION[$_SESSION['current_id']]['courses']['C008'])) $menu .= "<li><a href=\"" . $baseUrl . "dictation/work/\"><i class='fa fa-comments'></i> English Learning</a></li>";

            if(isset($_SESSION['courses']['C009']) || isset($_SESSION[$_SESSION['current_id']]['courses']['C009'])) $menu .= "<li><a href=\"" . $baseUrl . "selfdictee/work/\"><i class='fa fa-pencil'></i> Ta Dictée</a></li>";

            if(!isset($_SESSION['child_id'])) $menu .= "<li><a href=\"" . $baseUrl . "children/basket/\"><i class='fa fa-shopping-basket'></i> Voir Panier</a></li>";
            if(!isset($_SESSION['child_id'])) $menu .= "<li><a href=\"" . $baseUrl . "children/basket/?automate=true\"><i class='fa fa-shopping-basket'></i> Panier Automatique</a></li>";
            if(!isset($_SESSION['child_id'])) $menu .= "<li><a href=\"" . $baseUrl . "children/manage/\"><i class='fa fa-child'></i> Voir Enfants</a></li>";
            if(!isset($_SESSION['child_id'])) $menu .= "<li><a href=\"" . $baseUrl . "children/payments/\"><i class='fa fa-usd'></i> Voir Paiements</a></li>";
            if(!isset($_SESSION['child_id'])) $menu .= "<li><a href=\"" . $baseUrl . "children/childreport/\"><i class='fa fa-bug'></i> Rapport Statistiques</a></li>";


            /**************** start admin menu*********************/
            if($_SESSION['isAdministrator']) $menu .= "<li><a href=\"" . $baseUrl . "epreuves/manage/\"><i class='fa fa-list-check'></i> Admin Epreuves</a></li>";            
            if($_SESSION['isAdministrator']) $menu .= "<li><a href=\"" . $baseUrl . "talk/manage/\"><i class='fa fa-comments'></i> Admin Talk</a></li>";
            if($_SESSION['isAdministrator']) $menu .= "<li><a href=\"" . $baseUrl . "exposes/manage/\"><i class='fa fa-chalkboard-user'></i> Admin Présentation</a></li>";

            if($_SESSION['isAdministrator']) $menu .= "<li><a href=\"" . $baseUrl . "calcul/manage/\"><i class='fa fa-list-check'></i> Admin Calcul</a></li>";
            if($_SESSION['isAdministrator']) $menu .= "<li><a href=\"" . $baseUrl . "comprehension/manage/\"><i class='fa fa-chalkboard-user'></i> Admin Comprehension Texte</a></li>";
            if($_SESSION['isAdministrator']) $menu .= "<li><a href=\"" . $baseUrl . "situationprobleme/manage/\"><i class='fa fa-comments'></i> Admin Situation Probleme</a></li>";

            if($_SESSION['isAdministrator']) $menu .= "<li><a href=\"" . $baseUrl . "dictee/manage/\"><i class='fa fa-chalkboard-user'></i> Admin Dictée</a></li>";
            //if($_SESSION['isAdministrator']) $menu .= "<li><a href=\"" . $baseUrl . "dictation/manage/\"><i class='fa fa-comments'></i> Admin English Learning</a></li>";
            if($_SESSION['isAdministrator']) $menu .= "<li><a href=\"" . $baseUrl . "selfdictee/manage/\"><i class='fa fa-pencil'></i> Admin Ta Dictée</a></li>";
            /****************  end admin menu*********************/

            $menu .= "<li><a href=\"" . $baseUrl . "connect/logout/\"><i class='fa fa-right-from-bracket'></i> Logout</a></li>";
        }
        $GLOBALS['menu'] = $menu;
        $menuBase .= $menu . "</ul></div>";
        $valideCourse = isset($_GET['id']) ? ("<button id=\"btnCompleteCourse\" class=\"btn btn-success\" onclick=\"mark_course_completed()\"><i class=\"fa fa-check\"></i>  ". ( isset($_SESSION['done_course'][$_SESSION['current_id']][$GLOBALS['CourseCode']][$_GET['id']]) ? "No Achevé" : "Termine" ) ."</button>") : "";
        $menuStatic = "<div class=\"floating-btn\" ><center onclick=\"toggleMenu('floating-menu')\">..:: Menu ::..</center>$valideCourse $menuBase</div>";
        return $menuStatic;
    }
    public static function menuStaticScriptMenu()
    {
        $baseUrl = Settings::getHomePage();
        $arrayCourseVisited = "["; 
        if(isset($_SESSION['child_id']) && isset($_SESSION['done_course'][$_SESSION['child_id']][$GLOBALS['CourseCode']]))
            foreach($_SESSION['done_course'][$_SESSION['child_id']][$GLOBALS['CourseCode']] as $key => $val) { $arrayCourseVisited .= "$key,"; }
        $arrayCourseVisited .= "-1];";
        $validateCourseScript = "function mark_course_completed() {

        var child_id = '". ($_SESSION['current_id']??"") ."';
        var course_code = '". ($GLOBALS['CourseCode']??"") ."';
        var course_item_id = '". ($_GET['id']??"") ."';

        $.ajax({
            url: \"". $baseUrl ."completed_course.php\",
            type: \"POST\",
            dataType: \"json\",
            data: {
                child_id: child_id,
                course_code: course_code,
                course_item_id: course_item_id
            },
            success: function (response) {

                if (response.success) {

                    $(\"#btnCompleteCourse\").text(document.getElementById(\"btnCompleteCourse\").textContent.trim() == \"Termine\" ? \"No Achevé\" : \"Termine\");
                    alert(response.message);

                    // Optional
                    // location.reload();

                } else {

                    alert(response.message);

                }

            },
            error: function () {
                alert(\"An error occurred while communicating with the server.\");
            }

        });

    };
";
        return "<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css\">
                <link rel=\"icon\" type=\"image/x-icon\" href=\"". Settings::getHomePage() ."favicon/favicon.ico\">
                <link rel=\"icon\" type=\"image/png\" href=\"". Settings::getHomePage() ."favicon/favicon-32x32.png\">
                <link rel=\"icon\" type=\"image/png\" href=\"". Settings::getHomePage() ."favicon/favicon-16x16.png\">
                <script src=\"https://code.jquery.com/jquery-3.7.1.min.js\"></script>
                <script>
                    function myToggle(menuId) {
                        var menu = document.getElementById(menuId);
                        if (menu.style.display === 'none') {
                            menu.style.display = 'block';
                        } else {
                            menu.style.display = 'none';
                        }
                    }
                    function toggleMenu(menuId) {
                        var menu = document.getElementById(menuId);
                        if (menu.style.display === 'none') {
                            menu.style.display = 'block';
                        } else {
                            menu.style.display = 'none';
                        }
                    }
                    ". (isset($_GET['id']) ? $validateCourseScript :"") ."
                        document.addEventListener(\"DOMContentLoaded\", function () { 
                                                $(\"a.card\").each(function () {
                            var href = $(this).attr(\"href\");
                            var url = new URL(href, window.location.origin);
                            var id = parseInt(url.searchParams.get(\"id\"));
                            
                            var completedIds = $arrayCourseVisited;
                            
                            console.log(completedIds);
                            console.log(id + \"   ==> \" + completedIds.includes(id));
                            console.log(\"----------------------------------------------------------\");
                            if( completedIds.includes(id) ) this.style.setProperty(\"background-color\", \"#00ff1cdb \", \"important\");
                        });

                        // Images to use
                        ". $_SESSION['images'] ."
                        /*const images = [
                            \"images-animees/top1.png\",
                            \"images-animees/top2.png\",
                            \"images-animees/top3.png\",
                            \"images-animees/top4.png\",
                            \"images-animees/top5.png\",

                            \"images-animees/top6.png\",
                            \"images-animees/top7.png\",
                            \"images-animees/top8.png\",
                            \"images-animees/top9.png\",
                            \"images-animees/top10.png\",
                            \"images-animees/top11.png\",
                            \"images-animees/top12.png\",
                            \"images-animees/top13.png\",
                            \"images-animees/top14.png\",


                             \"images-animees/gif1.gif\",
                            \"images-animees/gif2.gif\",
                            \"images-animees/gif3.gif\",
                            \"images-animees/gif4.gif\",
                            \"images-animees/gif5.gif\"
                        ];*/
                        
                        function random(min, max) {
                            return Math.random() * (max - min) + min;
                        }

                        function randomInt(min, max) {
                            return Math.floor(random(min, max + 1));
                        }

                        // Create image dynamically
                        const img = document.createElement(\"img\");

                        img.style.position = \"fixed\";
                        img.style.display = \"none\";
                        img.style.pointerEvents = \"none\";
                        img.style.zIndex = \"999999\";
                        img.style.opacity = \"0\";
                        img.style.transition = \"opacity 0.5s ease\";

                        
                        document.body.appendChild(img);

                        function showImage() {
                            
                            // Random image
                            img.src = '". $baseUrl ."' + images[randomInt(0, images.length - 1)];
                            //console.log(img.src);

                            // Random size
                            const size = randomInt(100, 240);
                            img.style.width = size + \"px\";
                            img.style.height = \"auto\";

                            // Wait until browser knows the image size
                            img.onload = () => {

                                const w = img.offsetWidth;
                                const h = img.offsetHeight;

                                let x, y, dx, dy;

                                const speed = random(2, 5);

                                switch (randomInt(0, 3)) {

                                    // left -> right
                                    case 0:

                                        x = -w;
                                        y = randomInt(0, window.innerHeight - h);

                                        dx = speed;
                                        dy = random(-1, 1);

                                        break;

                                    // right -> left
                                    case 1:

                                        x = window.innerWidth;
                                        y = randomInt(0, window.innerHeight - h);

                                        dx = -speed;
                                        dy = random(-1, 1);

                                        break;

                                    // top -> bottom
                                    case 2:

                                        x = randomInt(0, window.innerWidth - w);
                                        y = -h;

                                        dx = random(-1, 1);
                                        dy = speed;

                                        break;

                                    // bottom -> top
                                    default:

                                        x = randomInt(0, window.innerWidth - w);
                                        y = window.innerHeight;

                                        dx = random(-1, 1);
                                        dy = -speed;

                                }

                                img.style.left = x + \"px\";
                                img.style.top = y + \"px\";

                                img.style.display = \"block\";
                                img.style.opacity = \"1\";

                                const timer = setInterval(function () {

                                    x += dx;
                                    y += dy;

                                    img.style.left = x + \"px\";
                                    img.style.top = y + \"px\";

                                    if (
                                        x < -w ||
                                        x > window.innerWidth ||
                                        y < -h ||
                                        y > window.innerHeight
                                    ) {

                                        clearInterval(timer);

                                        img.style.opacity = \"0\";

                                        setTimeout(function () {

                                            img.style.display = \"none\";

                                            setTimeout(showImage, randomInt(500, 4000));

                                        }, 500);

                                    }

                                }, 20);

                            };

                            
                        }

                        // Initial delay
                        setTimeout(showImage, randomInt(1000, 5000));
                        
                    });
                </script>";
    }
}
