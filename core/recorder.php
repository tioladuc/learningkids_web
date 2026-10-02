<?php
require_once __DIR__ . "/setting.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
class Recorder
{   
    //public $savingPath = __DIR__ . "/../uploads/recordings/";
    public $savingPath = __DIR__ . "/../audio-recorder-v2/audio/";
    public $urlPath = "";
    
    public function __construct()
    {
        //$this->urlPath = ($_SERVER['HTTP_HOST'] == "api.institutblaina.cm") ? "https://api.institutblaina.cm/webcourse/uploads/recordings/" : "https://yehoshoualevivant.com/webcourse/uploads/recordings/";
        $this->urlPath = ($_SERVER['HTTP_HOST'] == "api.institutblaina.cm") ? "https://api.institutblaina.cm/webcourse/audio-recorder-v2/audio/" : "https://learningkids.ca/audio-recorder-v2/audio/";
    }

    function displayYoutubeVideo($url)
    {
        if (empty($url)) {
            return '';
        }

        $videoId = '';

        if (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $url, $matches)) {
            $videoId = $matches[1];
        } elseif (preg_match('/youtu\.be\/([^?]+)/', $url, $matches)) {
            $videoId = $matches[1];
        }

        if (empty($videoId)) {
            return '';
        }

        return '
        <div class="youtube-container">
            <iframe
                src="https://www.youtube.com/embed/' . htmlspecialchars($videoId) . '"
                title="YouTube video"
                allowfullscreen>
            </iframe>
        </div>';
    }

    public function is_audio_existing(string $codeCourse, string $codeLesson, string $login):bool {
        $prefixFile = $login . '_' .
                      //$codeCourse . '_' .
                      $codeLesson . '_' .$codeCourse  ;
        
        $files = glob($this->savingPath . "$codeCourse/$prefixFile*");
        
        return !empty($files);
    }

    public function displayRecorderTag(string $codeCourse, string $codeLesson, string $login):string
    {
        $prefixFile = $login . '_' .                      
                      $codeLesson . '_' .
                      $codeCourse ;
        $files = glob($this->savingPath . "$codeCourse/$prefixFile*");
        //echo $this->savingPath . " ==== $codeCourse/$prefixFile*";
        //print_r($files);exit;
        /*$audioBaseRecorder = "<audio-recorder
            code='$codeCourse'
            course='$codeLesson'
            login='$login'>
        </audio-recorder>";*/
        $audioBaseRecorder = "<audio-recorder-v2
                                    code=\"$codeCourse\"
                                    course=\"$codeLesson\"
                                    login=\"$login\" 
                                    upload-url=\"" . Settings::getHomePage() . "audio-recorder-v2/audio_recorder.php\">
                                </audio-recorder-v2>";
        $audioRecorder = "<div id='audio-recorder'>$audioBaseRecorder</div>";
        $audioRecorderHidden = "<div id='audio-recorder' style='display: none;'>$audioBaseRecorder</div>";
            
        if (!empty($files)) {
            $UrlfirstFile = $this->urlPath . "$codeCourse/" . basename($files[0]);
            
            return "<div class='audio-file' id='audio-player'>
                        <audio id=\"player\">
                            <source src=\"$UrlfirstFile\" type=\"audio/webm\">
                            Your browser does not support audio playback.
                        </audio>

                        <div>
                            <button onclick=\"playAudio(this)\">▶ Play</button>
                            <button onclick=\"pauseAudio(this)\">⏸ Pause</button>
                            <button onclick=\"stopAudio(this)\">⏹ Stop</button>

                            <button onclick=\"backward(this)\">⏪ -10s</button>
                            <button onclick=\"forward(this)\">⏩ +10s</button>

                            <button onclick=\"slower(this)\">➖ Slower</button>
                            <button onclick=\"faster(this)\">➕ Faster</button>
                            <button onclick=\"recordback(this)\" style=\"background-color: coral;\">Record Back</button>

                            <span id=\"speed\">1.0x</span>
                        </div>
                    </div>
                    $audioRecorderHidden";
        } 

        return $audioRecorder;        
    }

    public function css() {
        return "
            .audio-recorder {

                width: 420px;
                max-width: 100%;

                border: 1px solid #CCCCCC;
                border-radius: 10px;

                padding: 20px;

                background: #FAFAFA;

                font-family: Arial, Helvetica, sans-serif;

                box-sizing: border-box;
            }

            .audio-recorder h3 {

                margin-top: 0;
                text-align: center;
            }

            .audio-buttons {

                display: flex;

                justify-content: center;

                gap: 10px;

                margin-top: 15px;
            }

            .audio-buttons button {

                cursor: pointer;

                padding: 10px 18px;

                border: none;

                border-radius: 6px;

                background: #1976D2;

                color: white;

                font-size: 15px;
            }

            .audio-buttons button:disabled {

                background: #999;
            }

            .audio-status {

                margin-top: 15px;

                text-align: center;

                color: #666;
            }

            .audio-timer {

                text-align: center;

                margin-top: 12px;

                font-size: 24px;

                font-weight: bold;

                color: #444;
            }

            .audio-preview {

                width: 100%;

                margin-top: 15px;
            }

            .level-container {

                width: 100%;

                height: 12px;

                background: #DDD;

                border-radius: 20px;

                overflow: hidden;

                margin-top: 15px;
            }

            .level-bar {

                width: 0%;

                height: 100%;

                background: #4CAF50;

                transition: width .08s;
            }









            .audio-file {
                text-align: center;
                margin-top: 20px;
            }

            .audio-file audio {
                width: 100%;
                max-width: 400px;
                margin-bottom: 10px;
            }

            .audio-file button {
                padding: 10px 15px;
                margin: 5px;
                font-size: 16px;
                cursor: pointer;
                border-radius: 20px;
            }

            .audio-file #speed {
                font-weight: bold;
                margin-left: 10px;
            }
            
            .boutonAudioPlayerRecorder {
                background-color: aqua !important;
            }
            #startBtn{
                background-color: aqua !important;
                padding: 10px 20px;
                border-radius: 20px;
                border: 1px solid #ccc;
                cursor: pointer;
            }
            #stopBtn{
                background-color: coral !important;
                padding: 10px 20px;
                border-radius: 20px;
                border: 1px solid #ccc;
                cursor: pointer;
            }
        ";
    }

    public function javascript()
    {
?>
    <!--script src="https://cdnjs.cloudflare.com/ajax/libs/lamejs/1.2.1/lame.min.js"></script-->
    <script src="<?= Settings::getHomePage() ?>audio-recorder-v2/js/audio-recorder-v2-02.js"></script>
    <script src="<?= Settings::getHomePage() ?>audio-recorder-v2/js/lame.min.js"></script>
        <script>
            class AudioRecorder extends HTMLElement {

                constructor() {
                    super();
                }

                connectedCallback() {

                    this.innerHTML = `
                        <center>
                            <button id="startBtn">&#127908;Start Recording</button>
                            <button id="stopBtn" disabled>&#127897;&#65039; Stop Recording</button>
                            <br/><br/>
                            <audio controls></audio>
                        </center>
                    `;

                    const startBtn = this.querySelector("#startBtn");
                    const stopBtn = this.querySelector("#stopBtn");
                    const audio = this.querySelector("audio");

                    let recorder;
                    let chunks = [];

                    startBtn.addEventListener("click", async () => {
                        /*alert('before');
                        alert(
  "getUserMedia = " +
  !!(navigator.mediaDevices &&
     navigator.mediaDevices.getUserMedia)
);*/
//alert("secure = " + window.isSecureContext);
                        let stream = null;
                        try {
                            stream =
                                await navigator.mediaDevices.getUserMedia({
                                    audio: true
                                });

                            //alert('Microphone access granted');

                        } catch (e) {
                            /*alert('Microphone access denied: Duclaier ' + e);
                            alert(
      'name=' + e.name +
      '\nmessage=' + e.message
    );*/
                        }
                        /*const stream =
                            await navigator.mediaDevices.getUserMedia({
                                audio: true
                            });*/
                        //alert('after 01');

                        recorder = new MediaRecorder(stream);
                        //alert('after 02');

                        chunks = [];
                        //alert('after 03');

                        recorder.ondataavailable = e => {
                            chunks.push(e.data);
                        };
                        //alert('after 04');

                        recorder.onstop = async () => {

                            const blob = new Blob(
                                chunks,
                                { type: 'audio/webm' }
                            );

                            audio.src =
                                URL.createObjectURL(blob);

                            const fd = new FormData();

                            fd.append(
                                'audio',
                                blob,
                                'recording.webm'
                            );

                            fd.append(
                                'code',
                                this.getAttribute('code')
                            );

                            fd.append(
                                'course',
                                this.getAttribute('course')
                            );

                            fd.append(
                                'login',
                                this.getAttribute('login')
                            );

                            const response =
                                await fetch(
                                    'audio_recorder.php',
                                    {
                                        method: 'POST',
                                        body: fd
                                    }
                                );

                            console.log(
                                await response.text()
                            );
                        };
                        //alert('after 01x');
                        recorder.start();
                        //alert('after 01y');

                        startBtn.disabled = true;
                        stopBtn.disabled = false;
                    });

                    stopBtn.addEventListener("click", () => {

                        recorder.stop();

                        startBtn.disabled = false;
                        stopBtn.disabled = true;
                    });
                }
            }

            customElements.define(
                'audio-recorder',
                AudioRecorder
            );

            function activateAudioPlayerRecorderClasse(element) {

                document.querySelectorAll('.boutonAudioPlayerRecorder').forEach(el => {
                    el.classList.remove('boutonAudioPlayerRecorder');
                });

                element.classList.add('boutonAudioPlayerRecorder');
            }
            
            const player = document.getElementById('player');
            const speedDisplay = document.getElementById('speed');

            function recordback(element) {
                const audioPlayer = document.getElementById('audio-player');
                const audioRecorder = document.getElementById('audio-recorder');

                audioPlayer.style.display = 'none';
                audioRecorder.style.display = 'block';
                activateAudioPlayerRecorderClasse(element);
            }

            function playAudio(element) {
                const player = document.getElementById('player');
                const speedDisplay = document.getElementById('speed');

                player.play();
                activateAudioPlayerRecorderClasse(element);
            }

            function pauseAudio(element) {
                const player = document.getElementById('player');
                const speedDisplay = document.getElementById('speed');

                player.pause();
                activateAudioPlayerRecorderClasse(element);
            }

            function stopAudio(element) {
                const player = document.getElementById('player');
                const speedDisplay = document.getElementById('speed');

                player.pause();
                player.currentTime = 0;
                activateAudioPlayerRecorderClasse(element);
            }

            function forward(element) {
                const player = document.getElementById('player');
                const speedDisplay = document.getElementById('speed');

                player.currentTime += 10;
                activateAudioPlayerRecorderClasse(element);
            }

            function backward(element) {
                const player = document.getElementById('player');
                const speedDisplay = document.getElementById('speed');

                player.currentTime = Math.max(0, player.currentTime - 10);
                activateAudioPlayerRecorderClasse(element);
            }

            function faster(element) {
                const player = document.getElementById('player');
                const speedDisplay = document.getElementById('speed');

                player.playbackRate += 0.25;
                updateSpeed();
                activateAudioPlayerRecorderClasse(element);
            }

            function slower(element) {
                const player = document.getElementById('player');
                const speedDisplay = document.getElementById('speed');

                player.playbackRate = Math.max(
                    0.25,
                    player.playbackRate - 0.25
                );
                updateSpeed();
                activateAudioPlayerRecorderClasse(element);
            }

            function updateSpeed(element) {
                const player = document.getElementById('player');
                const speedDisplay = document.getElementById('speed');

                speedDisplay.textContent =
                    player.playbackRate.toFixed(2) + 'x';
                activateAudioPlayerRecorderClasse(element);
            }
            </script>
<?php
    }

    function uploadAudio() {

        $code   = $_POST['code'] ?? '';
        $course = $_POST['course'] ?? '';
        $login  = $_POST['login'] ?? '';

        if (!isset($_FILES['audio'])) {
            die("No audio uploaded");
        }

        $uploadDir = $this->savingPath . "$code/";
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $folder = $uploadDir;
        $prefixFile = $login . '_' .
                      $code . '_' .
                      $course . '_' ;

        foreach (glob($folder . $prefixFile . '*') as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $filename =
            $prefixFile .            
            time() .
            '.webm';

        $destination =
            $uploadDir . $filename;

        move_uploaded_file(
            $_FILES['audio']['tmp_name'],
            $destination
        );

        echo "Saved";
    }
}

?>