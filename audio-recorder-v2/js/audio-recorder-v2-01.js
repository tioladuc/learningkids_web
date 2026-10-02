class AudioRecorderV2 extends HTMLElement {

    constructor() {
        super();

        this.audioContext = null;
        this.mediaStream = null;
        this.source = null;
        this.processor = null;

        this.audioData = [];

        this.recording = false;

        this.recordingLength = 0;
        this.recordingSampleRate = 44100;

        this.timer = null;
        this.seconds = 0;
    }

    connectedCallback() {

        this.innerHTML = `

            <div class="audio-recorder">

                <h3>Voice Recorder</h3>

                <div class="audio-timer">
                    00:00
                </div>

                <div class="level-container">
                    <div class="level-bar"></div>
                </div>

                <div class="audio-buttons">

                    <button
                        type="button"
                        class="start">
                        🎤 Start
                    </button>

                    <button
                        type="button"
                        class="stop"
                        disabled>
                        ■ Stop
                    </button>

                </div>

                <audio
                    controls
                    class="audio-preview">
                </audio>

                <div class="audio-status">
                    Ready
                </div>

            </div>

        `;

        this.startButton =
            this.querySelector(".start");

        this.stopButton =
            this.querySelector(".stop");

        this.audio =
            this.querySelector(".audio-preview");

        this.status =
            this.querySelector(".audio-status");

        this.timerLabel =
            this.querySelector(".audio-timer");

        this.level =
            this.querySelector(".level-bar");


        this.startButton.addEventListener(
            "click",
            () => this.startRecording()
        );


        this.stopButton.addEventListener(
            "click",
            () => this.stopRecording()
        );
    }


    async startRecording() {

        if (this.recording) {
            return;
        }

        try {

            this.status.textContent =
                "Requesting microphone...";


            /*
             * Ask the browser for microphone access.
             */
            this.mediaStream =
                await navigator.mediaDevices.getUserMedia({
                    audio: {
                        channelCount: 1,
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    }
                });


            /*
             * Create AudioContext.
             */
            this.audioContext =
                new (
                    window.AudioContext ||
                    window.webkitAudioContext
                )();


            /*
             * Safari may create the AudioContext
             * in suspended state.
             */
            if (
                this.audioContext.state ===
                "suspended"
            ) {

                await this.audioContext.resume();

            }


            this.recordingSampleRate =
                this.audioContext.sampleRate;


            /*
             * Create microphone source.
             */
            this.source =
                this.audioContext.createMediaStreamSource(
                    this.mediaStream
                );


            /*
             * Create ScriptProcessor.
             *
             * 4096 = buffer size
             * 1 input channel
             * 1 output channel
             */
            this.processor =
                this.audioContext.createScriptProcessor(
                    4096,
                    1,
                    1
                );


            /*
             * Clear previous recording.
             */
            this.audioData = [];

            this.recordingLength = 0;


            /*
             * Receive PCM audio.
             */
            this.processor.onaudioprocess =
                (event) => {

                    if (!this.recording) {
                        return;
                    }


                    const input =
                        event.inputBuffer.getChannelData(0);


                    /*
                     * Copy the Float32Array.
                     *
                     * We MUST copy it because the
                     * browser reuses the original buffer.
                     */
                    const buffer =
                        new Float32Array(
                            input.length
                        );

                    buffer.set(input);


                    this.audioData.push(buffer);

                    this.recordingLength +=
                        buffer.length;


                    /*
                     * Calculate microphone level.
                     */
                    let sum = 0;

                    for (
                        let i = 0;
                        i < buffer.length;
                        i++
                    ) {

                        sum +=
                            buffer[i] *
                            buffer[i];

                    }


                    const rms =
                        Math.sqrt(
                            sum / buffer.length
                        );


                    /*
                     * Convert to percentage.
                     */
                    let level =
                        Math.min(
                            100,
                            rms * 500
                        );


                    this.level.style.width =
                        level + "%";
                };


            /*
             * Connect microphone → processor.
             */
            this.source.connect(
                this.processor
            );


            /*
             * IMPORTANT:
             *
             * ScriptProcessorNode needs to be
             * connected to an output for some
             * browsers to process it.
             *
             * We don't want microphone audio
             * playing through the speakers,
             * so connect through a zero-gain node.
             */
            this.silentGain =
                this.audioContext.createGain();

            this.silentGain.gain.value = 0;


            this.processor.connect(
                this.silentGain
            );


            this.silentGain.connect(
                this.audioContext.destination
            );


            /*
             * Start recording.
             */
            this.recording = true;

            this.seconds = 0;

            this.startTimer();


            this.startButton.disabled =
                true;

            this.stopButton.disabled =
                false;


            this.status.textContent =
                "Recording...";


            /*
             * Clear previous preview.
             */
            this.audio.removeAttribute("src");

        } catch (error) {

            console.error(
                "Microphone error:",
                error
            );


            this.status.textContent =
                "Microphone error: " +
                error.name;


            this.cleanup();

        }

    }


    stopRecordingOld() {

        if (!this.recording) {
            return;
        }


        this.recording = false;


        this.stopTimer();


        this.startButton.disabled =
            false;

        this.stopButton.disabled =
            true;


        this.status.textContent =
            "Creating audio...";


        /*
         * Stop microphone.
         */
        this.stopMicrophone();


        /*
         * Create WAV from PCM.
         */
        /*****duclair***const wavBlob =
            this.createWavBlob();********/


        /*
         * Preview.
         */
        /*******duclair*****const url =
            URL.createObjectURL(
                wavBlob
            );*********/

        /***======================***/
        const mp3Blob =
            this.createMp3Blob();

        const url =
            URL.createObjectURL(
                mp3Blob
            );
        /***======================***/

        this.audio.src = url;


        this.status.textContent =
            "Recording ready";


        /*
         * Reset level.
         */
        this.level.style.width =
            "0%";


        console.log(
            "Recording sample rate:",
            this.recordingSampleRate
        );


        console.log(
            "Recording length:",
            this.recordingLength
        );


        console.log(
            "WAV size:",
            wavBlob.size
        );

    }

    /**aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa */
    stopRecording() {

        if (!this.recording) {
            return;
        }


        this.recording = false;


        this.stopTimer();


        this.startButton.disabled =
            false;

        this.stopButton.disabled =
            true;


        this.status.textContent =
            "Encoding MP3...";


        /*
         * Stop microphone.
         */
        this.stopMicrophone();


        try {
            console.log('Encoding MP3...01');
            /*
             * Convert PCM → MP3.
             */
            const mp3Blob =
                this.createMp3Blob();
            console.log('Encoding MP3...02');

            /*
             * Create preview URL.
             */
            const url =
                URL.createObjectURL(
                    mp3Blob
                );

                console.log('Encoding MP3...03');   
            /*
             * Give MP3 to <audio>.
             */
            this.audio.src =
                url;

                console.log('Encoding MP3...04');
            /*
             * Reset level.
             */
            this.level.style.width =
                "0%";
                console.log('Encoding MP3...05');

            this.status.textContent =
                "MP3 recording ready";

                console.log('Encoding MP3...06');
            /*
             * Useful debugging information.
             */
            console.log(
                "Sample rate:",
                this.recordingSampleRate
            );
            console.log('Encoding MP3...07');

            console.log(
                "MP3 size:",
                mp3Blob.size,
                "bytes"
            );
            console.log('Encoding MP3...08');

            /*
             * Keep the Blob available.
             *
             * Part 4 will use this for AJAX.
             */
            this.currentBlob =
                mp3Blob;
            console.log('Encoding MP3...09');

        } catch (error) {

            console.error(
                "MP3 encoding error:",
                error
            );


            this.status.textContent =
                "Could not encode MP3";

        }

    }
    /**aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa */

    /**ssssssssssssssssssssssssssssssssssssssssssss */
    createMp3Blob() {

        if (!this.audioData.length) {

            throw new Error(
                "No audio data available"
            );

        }


        /*
         * Calculate total number of samples.
         */
        const length =
            this.audioData.reduce(
                (total, buffer) =>
                    total + buffer.length,
                0
            );


        /*
         * Combine all Float32 buffers.
         */
        const samples =
            new Float32Array(length);


        let offset = 0;


        for (
            const buffer
            of this.audioData
        ) {

            samples.set(
                buffer,
                offset
            );

            offset += buffer.length;

        }


        /*
         * Convert Float32 samples
         * to Int16 samples.
         */
        const pcm =
            new Int16Array(
                samples.length
            );


        for (
            let i = 0;
            i < samples.length;
            i++
        ) {

            let sample =
                Math.max(
                    -1,
                    Math.min(
                        1,
                        samples[i]
                    )
                );


            pcm[i] =
                sample < 0
                    ? sample * 0x8000
                    : sample * 0x7FFF;

        }


        /*
         * MP3 settings.
         *
         * 64 kbps is enough for speech.
         */
        const mp3Encoder =
            new lamejs.Mp3Encoder(
                1,                          // mono
                this.recordingSampleRate,   // sample rate
                64                          // bitrate
            );


        const mp3Data = [];


        /*
         * lamejs processes PCM in chunks.
         *
         * 1152 samples is a standard MP3 frame size.
         */
        const sampleBlockSize = 1152;


        for (
            let i = 0;
            i < pcm.length;
            i += sampleBlockSize
        ) {

            const sampleChunk =
                pcm.subarray(
                    i,
                    i + sampleBlockSize
                );


            const mp3buf =
                mp3Encoder.encodeBuffer(
                    sampleChunk
                );


            if (
                mp3buf.length > 0
            ) {

                mp3Data.push(
                    new Int8Array(mp3buf)
                );

            }

        }


        /*
         * Finish encoding.
         */
        const end =
            mp3Encoder.flush();


        if (end.length > 0) {

            mp3Data.push(
                new Int8Array(end)
            );

        }


        /*
         * Create MP3 Blob.
         */
        return new Blob(
            mp3Data,
            {
                type: "audio/mpeg"
            }
        );

    }
    /**ssssssssssssssssssssssssssssssssssssssssssss */


    createWavBlob() {

        /*
         * Calculate total PCM length.
         */
        const length =
            this.audioData.reduce(
                (total, buffer) =>
                    total + buffer.length,
                0
            );


        /*
         * Create one Float32Array
         * containing the complete recording.
         */
        const samples =
            new Float32Array(length);


        let offset = 0;


        for (
            const buffer
            of this.audioData
        ) {

            samples.set(
                buffer,
                offset
            );

            offset +=
                buffer.length;

        }


        /*
         * Convert Float32 PCM → 16-bit PCM.
         */
        const pcm =
            new Int16Array(
                samples.length
            );


        for (
            let i = 0;
            i < samples.length;
            i++
        ) {

            let sample =
                Math.max(
                    -1,
                    Math.min(
                        1,
                        samples[i]
                    )
                );


            pcm[i] =
                sample < 0
                    ? sample * 0x8000
                    : sample * 0x7FFF;

        }


        /*
         * Create WAV file.
         */
        const wavBuffer =
            this.encodeWav(
                pcm,
                this.recordingSampleRate
            );


        return new Blob(
            [wavBuffer],
            {
                type: "audio/wav"
            }
        );

    }


    encodeWav(
        samples,
        sampleRate
    ) {

        const channels = 1;

        const bitsPerSample = 16;

        const byteRate =
            sampleRate *
            channels *
            bitsPerSample / 8;


        const blockAlign =
            channels *
            bitsPerSample / 8;


        const dataSize =
            samples.length * 2;


        const buffer =
            new ArrayBuffer(
                44 + dataSize
            );


        const view =
            new DataView(buffer);


        /*
         * RIFF header
         */
        this.writeString(
            view,
            0,
            "RIFF"
        );


        view.setUint32(
            4,
            36 + dataSize,
            true
        );


        this.writeString(
            view,
            8,
            "WAVE"
        );


        /*
         * fmt chunk
         */
        this.writeString(
            view,
            12,
            "fmt "
        );


        view.setUint32(
            16,
            16,
            true
        );


        /*
         * PCM format
         */
        view.setUint16(
            20,
            1,
            true
        );


        view.setUint16(
            22,
            channels,
            true
        );


        view.setUint32(
            24,
            sampleRate,
            true
        );


        view.setUint32(
            28,
            byteRate,
            true
        );


        view.setUint16(
            32,
            blockAlign,
            true
        );


        view.setUint16(
            34,
            bitsPerSample,
            true
        );


        /*
         * data chunk
         */
        this.writeString(
            view,
            36,
            "data"
        );


        view.setUint32(
            40,
            dataSize,
            true
        );


        /*
         * PCM samples
         */
        let offset = 44;


        for (
            let i = 0;
            i < samples.length;
            i++
        ) {

            view.setInt16(
                offset,
                samples[i],
                true
            );

            offset += 2;

        }


        return buffer;

    }


    writeString(
        view,
        offset,
        string
    ) {

        for (
            let i = 0;
            i < string.length;
            i++
        ) {

            view.setUint8(
                offset + i,
                string.charCodeAt(i)
            );

        }

    }


    startTimer() {

        this.seconds = 0;


        this.timerLabel.textContent =
            "00:00";


        this.timer =
            setInterval(() => {

                this.seconds++;


                const minutes =
                    Math.floor(
                        this.seconds / 60
                    );


                const seconds =
                    this.seconds % 60;


                this.timerLabel.textContent =

                    String(minutes)
                        .padStart(2, "0")

                    +

                    ":" +

                    String(seconds)
                        .padStart(2, "0");


            }, 1000);

    }


    stopTimer() {

        if (this.timer) {

            clearInterval(
                this.timer
            );

            this.timer = null;

        }

    }


    stopMicrophone() {

        /*
         * Disconnect audio nodes.
         */
        try {

            if (this.processor) {

                this.processor.disconnect();

            }

            if (this.source) {

                this.source.disconnect();

            }

            if (this.silentGain) {

                this.silentGain.disconnect();

            }

        } catch (e) {

            console.warn(
                "Disconnect error:",
                e
            );

        }


        /*
         * Stop microphone tracks.
         */
        if (this.mediaStream) {

            this.mediaStream
                .getTracks()
                .forEach(
                    track => track.stop()
                );

        }


        /*
         * Close AudioContext.
         */
        if (this.audioContext) {

            this.audioContext
                .close()
                .catch(() => { });

        }


        this.processor = null;
        this.source = null;
        this.silentGain = null;
        this.mediaStream = null;
        this.audioContext = null;

    }


    cleanup() {

        this.recording = false;

        this.stopTimer();

        this.stopMicrophone();

        this.startButton.disabled =
            false;

        this.stopButton.disabled =
            true;

        this.level.style.width =
            "0%";

    }
}


customElements.define(
    "audio-recorder-v2",
    AudioRecorderV2
);