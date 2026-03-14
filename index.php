<?php
declare(strict_types=1);

$singleDigits = [
    'N' => '0',
    'E' => '1',
    'T' => '3',
    'A' => '4',
    'I' => '6',
    'L' => '7',
    'S' => '8',
];

$doubleDigits = [
    'B' => '20',
    'C' => '21',
    'D' => '22',
    'F' => '23',
    'G' => '24',
    'H' => '25',
    'J' => '26',
    'K' => '50',
    'M' => '51',
    'Ñ' => '52',
    'O' => '53',
    'P' => '54',
    'Q' => '55',
    'R' => '56',
    'U' => '90',
    'V' => '91',
    'W' => '92',
    'X' => '93',
    'Y' => '94',
    'Z' => '95',
];

$encodeMap = $singleDigits + $doubleDigits;
$decodeSingleMap = array_flip($singleDigits);
$decodeDoubleMap = array_flip($doubleDigits);
$defaultExample = 'REPORT AGENT STATUS MEET TUESDAY';

function normalize_text(string $input): string
{
    $input = mb_strtoupper($input, 'UTF-8');
    $input = str_replace('Ñ', '__ENYE__', $input);
    if (class_exists('Normalizer')) {
        $input = Normalizer::normalize($input, Normalizer::FORM_D);
    }
    $input = preg_replace('/\p{Mn}+/u', '', $input) ?? $input;
    $input = str_replace('__ENYE__', 'Ñ', $input);

    preg_match_all('/[A-ZÑ]/u', $input, $matches);

    return implode('', $matches[0]);
}

function encode_plaintext(string $plaintext, array $encodeMap): array
{
    $normalized = normalize_text($plaintext);
    $digits = '';

    foreach (mb_str_split($normalized) as $char) {
        $digits .= $encodeMap[$char] ?? '';
    }

    $unpaddedLength = strlen($digits);
    $paddedDigits = $digits;
    $remainder = $unpaddedLength % 5;
    if ($remainder !== 0) {
        $paddedDigits .= str_repeat('0', 5 - $remainder);
    }

    return [
        'normalized' => $normalized,
        'digits' => $paddedDigits,
        'unpaddedLength' => $unpaddedLength,
        'groups' => group_digits($paddedDigits),
    ];
}

function group_digits(string $digits): string
{
    $clean = preg_replace('/\D+/', '', $digits) ?? '';
    if ($clean === '') {
        return '';
    }

    return trim(chunk_split($clean, 5, ' '));
}

function otp_encrypt(string $digits, string $otp): string
{
    $cipher = '';
    $length = min(strlen($digits), strlen($otp));

    for ($index = 0; $index < $length; $index++) {
        $cipher .= (string) (((int) $digits[$index] - (int) $otp[$index] + 10) % 10);
    }

    return $cipher;
}

function otp_decrypt(string $cipher, string $otp): string
{
    $digits = '';
    $length = min(strlen($cipher), strlen($otp));

    for ($index = 0; $index < $length; $index++) {
        $digits .= (string) (((int) $cipher[$index] + (int) $otp[$index]) % 10);
    }

    return $digits;
}

function decode_digits(string $digits, array $decodeSingleMap, array $decodeDoubleMap, ?int $limit = null): string
{
    $clean = preg_replace('/\D+/', '', $digits) ?? '';
    if ($limit !== null) {
        $clean = substr($clean, 0, $limit);
    }

    $plaintext = '';
    $index = 0;
    $length = strlen($clean);

    while ($index < $length) {
        $digit = $clean[$index];
        if (isset($decodeSingleMap[$digit])) {
            $plaintext .= $decodeSingleMap[$digit];
            $index++;
            continue;
        }

        if ($index + 1 >= $length) {
            break;
        }

        $pair = $digit . $clean[$index + 1];
        if (!isset($decodeDoubleMap[$pair])) {
            break;
        }

        $plaintext .= $decodeDoubleMap[$pair];
        $index += 2;
    }

    return $plaintext;
}

function run_selftest(
    string $example,
    array $encodeMap,
    array $decodeSingleMap,
    array $decodeDoubleMap
): int {
    $encoded = encode_plaintext($example, $encodeMap);
    $otp = substr(str_repeat('3141592653', 10), 0, strlen($encoded['digits']));
    $cipher = otp_encrypt($encoded['digits'], $otp);
    $decrypted = otp_decrypt($cipher, $otp);
    $decoded = decode_digits($decrypted, $decodeSingleMap, $decodeDoubleMap, $encoded['unpaddedLength']);

    $checks = [
        'example normalizes as expected' => $encoded['normalized'] === 'REPORTAGENTSTATUSMEETTUESDAY',
        'encoded digit string is grouped to 5s' => preg_match('/^(\d{5})( \d{5})*$/', $encoded['groups']) === 1,
        'encoded digits are padded to a multiple of 5' => strlen($encoded['digits']) % 5 === 0,
        'otp encrypt/decrypt round trip matches padded digits' => $decrypted === $encoded['digits'],
        'decode recovers original normalized plaintext' => $decoded === $encoded['normalized'],
        'cipher length matches otp length' => strlen($cipher) === strlen($otp),
        'random-length grouping helper trims non-digits' => group_digits('12 345-67890') === '12345 67890',
    ];

    foreach ($checks as $label => $passed) {
        fwrite(STDOUT, sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $label));
        if (!$passed) {
            return 1;
        }
    }

    return 0;
}

if (PHP_SAPI === 'cli' && isset($argv[1]) && $argv[1] === 'selftest') {
    exit(run_selftest($defaultExample, $encodeMap, $decodeSingleMap, $decodeDoubleMap));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atencion Number Station Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        :root {
            --bg-top: #061018;
            --bg-bottom: #12110c;
            --panel: rgba(9, 19, 27, 0.86);
            --panel-border: rgba(200, 190, 150, 0.22);
            --accent: #d0bb6f;
            --accent-muted: #8b7d4c;
            --text-main: #f4f1e6;
            --text-dim: #b8b1a2;
            --danger-soft: rgba(255, 99, 71, 0.14);
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top, rgba(208, 187, 111, 0.12), transparent 28%),
                linear-gradient(160deg, var(--bg-top), var(--bg-bottom));
            color: var(--text-main);
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        .app-shell {
            max-width: 1100px;
        }

        .hero-card,
        .panel-card,
        .transmit-alert {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            backdrop-filter: blur(12px);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
        }

        .hero-card {
            position: relative;
            overflow: hidden;
        }

        .hero-card::after {
            content: "";
            position: absolute;
            inset: auto -8% -50% auto;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(208, 187, 111, 0.18), transparent 70%);
            pointer-events: none;
        }

        .spy-kicker {
            color: var(--accent);
            letter-spacing: 0.24em;
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        .mono {
            font-family: "SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            letter-spacing: 0.08em;
        }

        .nav-tabs {
            border-bottom-color: rgba(208, 187, 111, 0.18);
        }

        .station-tabs-wrap {
            overflow-x: auto;
            padding-bottom: 0.35rem;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .station-tabs-wrap .nav-tabs {
            flex-wrap: nowrap;
            min-width: max-content;
        }

        .nav-tabs .nav-link {
            color: var(--text-dim);
            border: 0;
            border-bottom: 2px solid transparent;
            border-radius: 0;
        }

        .nav-tabs .nav-link.active,
        .nav-tabs .nav-link:hover {
            color: var(--text-main);
            background: transparent;
            border-bottom-color: var(--accent);
        }

        .checkerboard-table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--text-main);
            --bs-table-border-color: rgba(208, 187, 111, 0.16);
        }

        .checkerboard-table th,
        .checkerboard-table td {
            text-align: center;
            vertical-align: middle;
            min-width: 3.75rem;
        }

        .freq-letter {
            background: rgba(208, 187, 111, 0.18);
            color: #fff5c7;
        }

        .panel-card .form-control,
        .panel-card .form-control:focus {
            background: rgba(4, 10, 16, 0.7);
            border-color: rgba(208, 187, 111, 0.25);
            color: var(--text-main);
            box-shadow: none;
        }

        .panel-card .form-text,
        .status-text {
            color: var(--text-dim);
        }

        .result-box {
            background: rgba(4, 10, 16, 0.6);
            border: 1px solid rgba(208, 187, 111, 0.16);
            border-radius: 0.75rem;
            min-height: 5rem;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .transmit-alert {
            background:
                linear-gradient(135deg, rgba(0, 0, 0, 0.75), rgba(24, 27, 31, 0.92)),
                var(--danger-soft);
        }

        .btn-station {
            background: linear-gradient(135deg, #d0bb6f, #8b7d4c);
            border: 0;
            color: #111;
        }

        .btn-station:hover {
            color: #111;
            filter: brightness(1.05);
        }

        .transmit-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }

        .transmit-controls .btn {
            min-height: 2.75rem;
        }

        .speed-readout {
            color: var(--accent);
        }

        .speed-select {
            max-width: 12rem;
        }

        .subtle-divider {
            border-top: 1px solid rgba(208, 187, 111, 0.12);
        }

        @media (max-width: 767.98px) {
            .app-shell {
                padding-left: 0.85rem;
                padding-right: 0.85rem;
            }

            .checkerboard-table th,
            .checkerboard-table td {
                min-width: 2.75rem;
                font-size: 0.85rem;
            }

            .hero-card,
            .panel-card,
            .transmit-alert {
                border-radius: 1.25rem !important;
            }

            .result-box {
                min-height: 4rem;
                font-size: 0.92rem;
            }

            .transmit-controls {
                display: grid;
                grid-template-columns: 1fr;
                align-items: stretch;
            }

            .transmit-controls .btn,
            .transmit-controls .status-text {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <main class="container py-4 py-lg-5 app-shell">
        <section class="hero-card rounded-4 p-4 p-lg-5 mb-4">
            <div class="row g-4 align-items-end">
                <div class="col-lg-8">
                    <div class="spy-kicker mb-2">Educational Numbers Station Demo</div>
                    <h1 class="display-6 mb-3">Atencion Broadcast Simulator</h1>
                    <p class="mb-0 text-light-emphasis">
                        Encode plaintext through the recovered DGI checkerboard, encrypt it with one-time pad subtraction,
                        then transmit it as grouped digits in a spoken station-style broadcast.
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="border rounded-4 p-3 mono text-center">
                        <div class="small text-uppercase text-secondary mb-2">Reference Traffic</div>
                        <div id="heroGroups">----- ----- -----</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="station-tabs-wrap mb-4">
            <ul class="nav nav-tabs" id="stationTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-panel" type="button" role="tab" aria-controls="home-panel" aria-selected="true">Home</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="encode-tab" data-bs-toggle="tab" data-bs-target="#encode-panel" type="button" role="tab" aria-controls="encode-panel" aria-selected="false">Encode</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="encrypt-tab" data-bs-toggle="tab" data-bs-target="#encrypt-panel" type="button" role="tab" aria-controls="encrypt-panel" aria-selected="false">Encrypt</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="transmit-tab" data-bs-toggle="tab" data-bs-target="#transmit-panel" type="button" role="tab" aria-controls="transmit-panel" aria-selected="false">Transmit</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="decode-tab" data-bs-toggle="tab" data-bs-target="#decode-panel" type="button" role="tab" aria-controls="decode-panel" aria-selected="false">Decode</button>
                </li>
            </ul>
        </div>

        <div class="tab-content" id="stationTabContent">
            <section class="tab-pane fade show active" id="home-panel" role="tabpanel" aria-labelledby="home-tab" tabindex="0">
                <div class="panel-card rounded-4 p-4 mb-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                        <div>
                            <h2 class="h4 mb-1">Recovered Straddling Checkerboard</h2>
                            <p class="status-text mb-0">Single digits cover the frequent letters. Rows 2, 5, and 9 carry the rest.</p>
                        </div>
                        <button class="btn btn-station px-4" id="loadExampleBtn" type="button">Load Example Traffic</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table checkerboard-table mono align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Row</th>
                                    <th scope="col">0</th>
                                    <th scope="col">1</th>
                                    <th scope="col">2</th>
                                    <th scope="col">3</th>
                                    <th scope="col">4</th>
                                    <th scope="col">5</th>
                                    <th scope="col">6</th>
                                    <th scope="col">7</th>
                                    <th scope="col">8</th>
                                    <th scope="col">9</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th scope="row">Single</th>
                                    <td class="freq-letter">N</td>
                                    <td class="freq-letter">E</td>
                                    <td>&middot;</td>
                                    <td class="freq-letter">T</td>
                                    <td class="freq-letter">A</td>
                                    <td>&middot;</td>
                                    <td class="freq-letter">I</td>
                                    <td class="freq-letter">L</td>
                                    <td class="freq-letter">S</td>
                                    <td>&middot;</td>
                                </tr>
                                <tr>
                                    <th scope="row">2</th>
                                    <td>B</td>
                                    <td>C</td>
                                    <td>D</td>
                                    <td>F</td>
                                    <td>G</td>
                                    <td>H</td>
                                    <td>J</td>
                                    <td>&middot;</td>
                                    <td>&middot;</td>
                                    <td>&middot;</td>
                                </tr>
                                <tr>
                                    <th scope="row">5</th>
                                    <td>K</td>
                                    <td>M</td>
                                    <td>Ñ</td>
                                    <td>O</td>
                                    <td>P</td>
                                    <td>Q</td>
                                    <td>R</td>
                                    <td>&middot;</td>
                                    <td>&middot;</td>
                                    <td>&middot;</td>
                                </tr>
                                <tr>
                                    <th scope="row">9</th>
                                    <td>U</td>
                                    <td>V</td>
                                    <td>W</td>
                                    <td>X</td>
                                    <td>Y</td>
                                    <td>Z</td>
                                    <td>&middot;</td>
                                    <td>&middot;</td>
                                    <td>&middot;</td>
                                    <td>&middot;</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="tab-pane fade" id="encode-panel" role="tabpanel" aria-labelledby="encode-tab" tabindex="0">
                <div class="panel-card rounded-4 p-4">
                    <h2 class="h4 mb-3">Encode Plaintext</h2>
                    <div class="mb-3">
                        <label class="form-label" for="plaintextInput">Plaintext</label>
                        <textarea class="form-control" id="plaintextInput" rows="4" placeholder="Type message text here"><?=$defaultExample?></textarea>
                        <div class="form-text">Only letters A-Z and Ñ are retained. Other characters are ignored.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <button class="btn btn-station" id="encodeBtn" type="button">Encode To Digits</button>
                        <button class="btn btn-outline-light" id="copyNormalizedBtn" type="button">Copy Clean Plaintext</button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="status-text mb-2">Normalized plaintext</div>
                            <div class="result-box rounded-4 p-3 mono" id="normalizedOutput"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="status-text mb-2">Digit string (padded)</div>
                            <div class="result-box rounded-4 p-3 mono" id="digitOutput"></div>
                        </div>
                        <div class="col-12">
                            <div class="status-text mb-2">Transmission groups</div>
                            <div class="result-box rounded-4 p-3 mono" id="groupOutput"></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="tab-pane fade" id="encrypt-panel" role="tabpanel" aria-labelledby="encrypt-tab" tabindex="0">
                <div class="panel-card rounded-4 p-4">
                    <h2 class="h4 mb-3">Encrypt With One-Time Pad</h2>
                    <div class="row g-3 mb-3">
                        <div class="col-lg-6">
                            <div class="status-text mb-2">Current digits</div>
                            <div class="result-box rounded-4 p-3 mono" id="encryptDigitsOutput"></div>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label" for="otpInput">OTP digits</label>
                            <textarea class="form-control mono" id="otpInput" rows="3" placeholder="Enter digits or generate a random pad"></textarea>
                            <div class="form-text">The pad is cleaned to digits and matched to the message length.</div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <button class="btn btn-outline-light" id="generateOtpBtn" type="button">Generate Random OTP</button>
                        <button class="btn btn-station" id="encryptBtn" type="button">Encrypt Message</button>
                    </div>
                    <div class="status-text mb-2">Ciphertext groups</div>
                    <div class="result-box rounded-4 p-3 mono" id="cipherOutput"></div>
                </div>
            </section>

            <section class="tab-pane fade" id="transmit-panel" role="tabpanel" aria-labelledby="transmit-tab" tabindex="0">
                <div class="transmit-alert rounded-4 p-4">
                    <h2 class="h4 mb-2">Transmit</h2>
                    <p class="mb-3 text-light-emphasis">Atencion. Message number 047. Digits are spoken individually with short pauses between groups. Use the speed control to match the room and the device you are demoing on.</p>
                    <div class="result-box rounded-4 p-3 mono mb-3" id="transmitOutput"></div>
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-lg-8">
                            <label class="form-label d-flex justify-content-between align-items-center gap-2" for="transmitSpeedInput">
                                <span>Transmission speed</span>
                                <span class="mono speed-readout" id="transmitSpeedValue">100 WPM</span>
                            </label>
                            <select class="form-select speed-select mono" id="transmitSpeedInput">
                                <option value="5">5 WPM</option>
                                <option value="10">10 WPM</option>
                                <option value="15">15 WPM</option>
                                <option value="20">20 WPM</option>
                                <option value="25">25 WPM</option>
                                <option value="30">30 WPM</option>
                                <option value="35">35 WPM</option>
                                <option value="40">40 WPM</option>
                                <option value="45">45 WPM</option>
                                <option value="50">50 WPM</option>
                                <option value="75">75 WPM</option>
                                <option value="100" selected>100 WPM</option>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <div class="status-text small">Ham-style presets from 5 to 50 WPM, plus 75 and 100 for fast demo playback.</div>
                        </div>
                    </div>
                    <div class="transmit-controls">
                        <button class="btn btn-station" id="playTransmissionBtn" type="button">Play Broadcast</button>
                        <button class="btn btn-outline-light" id="stopTransmissionBtn" type="button">Stop</button>
                        <span class="status-text" id="voiceStatus">Speech synthesis ready.</span>
                    </div>
                </div>
            </section>

            <section class="tab-pane fade" id="decode-panel" role="tabpanel" aria-labelledby="decode-tab" tabindex="0">
                <div class="panel-card rounded-4 p-4">
                    <h2 class="h4 mb-3">Decode Traffic</h2>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label" for="decodeCipherInput">Cipher groups</label>
                            <textarea class="form-control mono" id="decodeCipherInput" rows="4" placeholder="Paste grouped ciphertext"></textarea>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label" for="decodeOtpInput">OTP groups</label>
                            <textarea class="form-control mono" id="decodeOtpInput" rows="4" placeholder="Paste grouped OTP"></textarea>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 my-4">
                        <button class="btn btn-station" id="decodeBtn" type="button">Decrypt And Decode</button>
                        <button class="btn btn-outline-light" id="loadCipherBtn" type="button">Load Current Cipher</button>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="status-text mb-2">Recovered digits</div>
                            <div class="result-box rounded-4 p-3 mono" id="decodedDigitsOutput"></div>
                        </div>
                        <div class="col-lg-6">
                            <div class="status-text mb-2">Recovered plaintext</div>
                            <div class="result-box rounded-4 p-3 mono" id="decodedPlaintextOutput"></div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        const ENCODE_MAP = <?=json_encode($encodeMap, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)?>;
        const DECODE_SINGLE_MAP = <?=json_encode($decodeSingleMap, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)?>;
        const DECODE_DOUBLE_MAP = <?=json_encode($decodeDoubleMap, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)?>;
        const DEFAULT_EXAMPLE = <?=json_encode($defaultExample, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)?>;

        window.currentDigits = '';
        window.currentGroups = '';
        window.currentCipher = '';
        window.currentOtp = '';
        window.currentDigitLength = 0;
        window.currentTransmitWpm = 100;

        const plaintextInput = document.getElementById('plaintextInput');
        const normalizedOutput = document.getElementById('normalizedOutput');
        const digitOutput = document.getElementById('digitOutput');
        const groupOutput = document.getElementById('groupOutput');
        const encryptDigitsOutput = document.getElementById('encryptDigitsOutput');
        const otpInput = document.getElementById('otpInput');
        const cipherOutput = document.getElementById('cipherOutput');
        const transmitOutput = document.getElementById('transmitOutput');
        const transmitSpeedInput = document.getElementById('transmitSpeedInput');
        const transmitSpeedValue = document.getElementById('transmitSpeedValue');
        const decodeCipherInput = document.getElementById('decodeCipherInput');
        const decodeOtpInput = document.getElementById('decodeOtpInput');
        const decodedDigitsOutput = document.getElementById('decodedDigitsOutput');
        const decodedPlaintextOutput = document.getElementById('decodedPlaintextOutput');
        const heroGroups = document.getElementById('heroGroups');
        const voiceStatus = document.getElementById('voiceStatus');

        function normalizeText(input) {
            return input
                .toUpperCase()
                .replaceAll('Ñ', '__ENYE__')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replaceAll('__ENYE__', 'Ñ')
                .replace(/[^A-ZÑ]/g, '');
        }

        function groupDigits(digits) {
            const clean = digits.replace(/\D/g, '');
            return clean.match(/.{1,5}/g)?.join(' ') ?? '';
        }

        function setTab(tabId) {
            const trigger = document.querySelector(`[data-bs-target="${tabId}"]`);
            if (trigger) {
                bootstrap.Tab.getOrCreateInstance(trigger).show();
            }
        }

        function syncHero(groups) {
            heroGroups.textContent = groups || '----- ----- -----';
        }

        function getTransmitWpm() {
            const parsed = Number(transmitSpeedInput.value);
            if (!Number.isFinite(parsed)) {
                return 100;
            }
            return Math.min(100, Math.max(5, parsed));
        }

        function syncTransmitSpeed() {
            const wpm = getTransmitWpm();
            window.currentTransmitWpm = wpm;
            transmitSpeedValue.textContent = `${wpm} WPM`;
            return wpm;
        }

        function mapWpmToSpeechRate(wpm) {
            if (wpm <= 10) {
                return 0.5;
            }
            if (wpm <= 20) {
                return 0.65;
            }
            if (wpm <= 30) {
                return 0.8;
            }
            if (wpm <= 40) {
                return 0.95;
            }
            if (wpm <= 50) {
                return 1.1;
            }
            if (wpm <= 75) {
                return 1.35;
            }
            return 1.7;
        }

        function syncEncodeOutputs(encoded) {
            normalizedOutput.textContent = encoded.normalized || 'No valid letters retained yet.';
            digitOutput.textContent = encoded.digits || 'No digits yet.';
            groupOutput.textContent = encoded.groups || 'No transmission groups yet.';
            encryptDigitsOutput.textContent = encoded.groups || 'Encode a message first.';
            syncHero(encoded.groups);
        }

        function encodePlaintext(input) {
            const normalized = normalizeText(input);
            const rawDigits = Array.from(normalized, (character) => ENCODE_MAP[character] ?? '').join('');
            const paddingLength = rawDigits ? (5 - (rawDigits.length % 5 || 5)) : 0;
            const digits = rawDigits + '0'.repeat(paddingLength);
            const groups = groupDigits(digits);

            window.currentDigits = digits;
            window.currentGroups = groups;
            window.currentDigitLength = rawDigits.length;

            return { normalized, digits, groups };
        }

        function generateOtp(length) {
            const values = new Uint32Array(length);
            window.crypto.getRandomValues(values);
            return Array.from(values, (value) => String(value % 10)).join('');
        }

        function otpEncrypt(digits, otp) {
            let cipher = '';
            for (let index = 0; index < digits.length; index += 1) {
                cipher += String((Number(digits[index]) - Number(otp[index]) + 10) % 10);
            }
            return cipher;
        }

        function otpDecrypt(cipher, otp) {
            let digits = '';
            for (let index = 0; index < cipher.length; index += 1) {
                digits += String((Number(cipher[index]) + Number(otp[index])) % 10);
            }
            return digits;
        }

        function decodeDigits(digits, exactLength = null) {
            const clean = digits.replace(/\D/g, '');
            const scoped = Number.isInteger(exactLength) && exactLength > 0 ? clean.slice(0, exactLength) : clean;
            let plaintext = '';

            for (let index = 0; index < scoped.length;) {
                const single = scoped[index];
                if (DECODE_SINGLE_MAP[single]) {
                    plaintext += DECODE_SINGLE_MAP[single];
                    index += 1;
                    continue;
                }

                const pair = scoped.slice(index, index + 2);
                if (pair.length < 2 || !DECODE_DOUBLE_MAP[pair]) {
                    break;
                }

                plaintext += DECODE_DOUBLE_MAP[pair];
                index += 2;
            }

            return plaintext;
        }

        function copyText(text) {
            if (!navigator.clipboard) {
                return Promise.reject(new Error('Clipboard API unavailable'));
            }
            return navigator.clipboard.writeText(text);
        }

        function handleEncode() {
            const encoded = encodePlaintext(plaintextInput.value);
            syncEncodeOutputs(encoded);
            if (!encoded.digits) {
                otpInput.value = '';
                cipherOutput.textContent = 'No ciphertext yet.';
                transmitOutput.textContent = 'No grouped ciphertext to transmit.';
            }
            return encoded;
        }

        function prepareOtp(rawOtp, requiredLength) {
            const cleanOtp = rawOtp.replace(/\D/g, '');
            if (cleanOtp.length !== requiredLength) {
                throw new Error(`OTP must be exactly ${requiredLength} digits.`);
            }
            return cleanOtp;
        }

        function syncCipher(cipherDigits) {
            const groups = groupDigits(cipherDigits);
            window.currentCipher = cipherDigits;
            cipherOutput.textContent = groups || 'No ciphertext yet.';
            transmitOutput.textContent = groups || 'No grouped ciphertext to transmit.';
            decodeCipherInput.value = groups;
            syncHero(groups);
        }

        function speakTransmission(groups) {
            if (!('speechSynthesis' in window)) {
                voiceStatus.textContent = 'Speech synthesis is not available in this browser.';
                return;
            }

            const cleanGroups = groups.trim();
            if (!cleanGroups) {
                voiceStatus.textContent = 'Nothing to transmit yet.';
                return;
            }

            const spokenGroups = cleanGroups
                .split(/\s+/)
                .map((group) => group.split('').join(' '))
                .join(' . ');
            const wpm = syncTransmitSpeed();
            const script = `Atención. Message number 047. ${spokenGroups}. ${spokenGroups}. End of message.`;
            const utterance = new SpeechSynthesisUtterance(script);
            utterance.rate = mapWpmToSpeechRate(wpm);
            utterance.onstart = () => {
                voiceStatus.textContent = `Broadcast in progress at ${wpm} WPM.`;
            };
            utterance.onend = () => {
                voiceStatus.textContent = 'Broadcast complete.';
            };
            utterance.onerror = () => {
                voiceStatus.textContent = 'Speech synthesis failed for this browser session.';
            };
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(utterance);
        }

        document.getElementById('loadExampleBtn').addEventListener('click', () => {
            plaintextInput.value = DEFAULT_EXAMPLE;
            const encoded = handleEncode();
            window.currentCipher = '';
            window.currentOtp = '';
            syncCipher('');
            otpInput.value = '';
            decodeOtpInput.value = '';
            decodedDigitsOutput.textContent = 'No digits recovered yet.';
            decodedPlaintextOutput.textContent = 'No plaintext recovered yet.';
            if (encoded.groups) {
                setTab('#encode-panel');
            }
        });

        document.getElementById('encodeBtn').addEventListener('click', () => {
            handleEncode();
        });

        document.getElementById('copyNormalizedBtn').addEventListener('click', async () => {
            const text = normalizedOutput.textContent === 'No valid letters retained yet.' ? '' : normalizedOutput.textContent;
            try {
                await copyText(text);
            } catch (error) {
                void error;
            }
        });

        document.getElementById('generateOtpBtn').addEventListener('click', () => {
            if (!window.currentDigits) {
                handleEncode();
            }
            if (!window.currentDigits) {
                return;
            }

            const otp = generateOtp(window.currentDigits.length);
            window.currentOtp = otp;
            otpInput.value = groupDigits(otp);
            decodeOtpInput.value = groupDigits(otp);
        });

        document.getElementById('encryptBtn').addEventListener('click', () => {
            if (!window.currentDigits) {
                handleEncode();
            }
            if (!window.currentDigits) {
                return;
            }

            try {
                const otp = prepareOtp(otpInput.value, window.currentDigits.length);
                const cipherDigits = otpEncrypt(window.currentDigits, otp);
                window.currentOtp = otp;
                otpInput.value = groupDigits(otp);
                decodeOtpInput.value = groupDigits(otp);
                syncCipher(cipherDigits);
                setTab('#transmit-panel');
            } catch (error) {
                cipherOutput.textContent = error.message;
            }
        });

        document.getElementById('playTransmissionBtn').addEventListener('click', () => {
            speakTransmission(transmitOutput.textContent);
        });

        transmitSpeedInput.addEventListener('change', () => {
            syncTransmitSpeed();
        });

        document.getElementById('stopTransmissionBtn').addEventListener('click', () => {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                voiceStatus.textContent = 'Broadcast stopped.';
            }
        });

        document.getElementById('loadCipherBtn').addEventListener('click', () => {
            decodeCipherInput.value = groupDigits(window.currentCipher);
            decodeOtpInput.value = groupDigits(window.currentOtp);
            setTab('#decode-panel');
        });

        document.getElementById('decodeBtn').addEventListener('click', () => {
            const cipher = decodeCipherInput.value.replace(/\D/g, '');
            if (!cipher) {
                decodedDigitsOutput.textContent = 'Paste ciphertext groups first.';
                decodedPlaintextOutput.textContent = 'No plaintext recovered.';
                return;
            }

            try {
                const otp = prepareOtp(decodeOtpInput.value, cipher.length);
                const digits = otpDecrypt(cipher, otp);
                const exactLength = (
                    cipher === window.currentCipher &&
                    otp === window.currentOtp &&
                    Number.isInteger(window.currentDigitLength) &&
                    window.currentDigitLength > 0
                ) ? window.currentDigitLength : null;
                const plaintext = decodeDigits(digits, exactLength);

                decodedDigitsOutput.textContent = groupDigits(digits);
                decodedPlaintextOutput.textContent = plaintext || 'No plaintext recovered.';
            } catch (error) {
                decodedDigitsOutput.textContent = error.message;
                decodedPlaintextOutput.textContent = 'No plaintext recovered.';
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const encoded = handleEncode();
            syncTransmitSpeed();
            syncCipher('');
            decodedDigitsOutput.textContent = 'No digits recovered yet.';
            decodedPlaintextOutput.textContent = 'No plaintext recovered yet.';
            if (encoded.groups) {
                syncEncodeOutputs(encoded);
            }
        });
    </script>
</body>
</html>
