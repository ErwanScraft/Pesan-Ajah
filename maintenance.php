<?php

declare(strict_types=1);

http_response_code(503);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');

$cssPath = __DIR__ . '/assets/css/pages/maintenance.css';
$cssVersion = is_file($cssPath) ? filemtime($cssPath) : '1';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#176b45">
    <meta name="robots" content="noindex, nofollow">

    <title>Maintenance — Pesan Ajah</title>

    <link rel="icon" href="/assets/images/logo.webp" type="image/webp">

    <link
        rel="stylesheet"
        href="/assets/css/pages/maintenance.css?v=<?= htmlspecialchars((string) $cssVersion, ENT_QUOTES, 'UTF-8') ?>"
    >
</head>

<body>

<main class="maintenance">

    <div class="maintenance-container">

        <!-- HEADER -->
        <header class="maintenance-header">

            <div class="brand">
                <img src="/assets/images/logo.webp" alt="Pesan Ajah">

                <div class="brand-info">
                    <strong>Pesan Ajah</strong>
                    <span>Warung lokal, pesan lebih mudah.</span>
                </div>
            </div>

            <span class="maintenance-badge">Maintenance</span>

        </header>

        <!-- CONTENT -->
        <div class="maintenance-content">

            <!-- HERO -->
            <section class="hero-panel">

                <div class="hero-illustration">

                    <svg viewBox="0 0 520 300" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Ilustrasi pemeliharaan sistem">

                        <defs>
                            <linearGradient id="screen" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#27c7ad"/>
                                <stop offset="1" stop-color="#159b94"/>
                            </linearGradient>

                            <linearGradient id="gearPink" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#ff83ae"/>
                                <stop offset="1" stop-color="#e83d83"/>
                            </linearGradient>

                            <linearGradient id="gearGold" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#ffcf72"/>
                                <stop offset="1" stop-color="#eea43f"/>
                            </linearGradient>

                            <filter id="shadow">
                                <feGaussianBlur stdDeviation="7"/>
                            </filter>
                        </defs>

                        <!-- Background decoration -->
                        <circle cx="260" cy="145" r="125" fill="#f4d99d" opacity=".45"/>

                        <!-- Shadow -->
                        <ellipse cx="265" cy="260" rx="125" ry="12"
                            fill="#805c38" opacity=".18" filter="url(#shadow)"/>

                        <!-- Back gear -->
                        <g transform="translate(165 85)">
                            <circle r="42" fill="url(#gearGold)"/>
                            <circle r="27" fill="#f6b44e"/>
                            <circle r="12" fill="#fff0c8"/>
                            <g fill="#e8a044">
                                <rect x="-9" y="-52" width="18" height="18" rx="4"/>
                                <rect x="-9" y="34" width="18" height="18" rx="4"/>
                                <rect x="-52" y="-9" width="18" height="18" rx="4"/>
                                <rect x="34" y="-9" width="18" height="18" rx="4"/>
                            </g>
                        </g>

                        <!-- Browser window -->
                        <g transform="rotate(-7 275 150)">
                            <rect x="165" y="74" width="218" height="144" rx="15"
                                fill="#137d76"/>
                            <rect x="165" y="65" width="218" height="135" rx="13"
                                fill="url(#screen)"/>

                            <rect x="178" y="79" width="192" height="20" rx="6"
                                fill="#d5fff0" opacity=".9"/>

                            <circle cx="190" cy="89" r="4" fill="#f18b85"/>
                            <circle cx="204" cy="89" r="4" fill="#f6c56d"/>
                            <circle cx="218" cy="89" r="4" fill="#91cf83"/>

                            <rect x="190" y="116" width="164" height="12" rx="6"
                                fill="#e6fff6" opacity=".75"/>

                            <rect x="190" y="140" width="108" height="9" rx="4"
                                fill="#e6fff6" opacity=".55"/>

                            <rect x="190" y="160" width="145" height="9" rx="4"
                                fill="#e6fff6" opacity=".55"/>

                            <path d="M240 218 L240 239 L310 239 L310 218"
                                fill="#178c80"/>

                            <rect x="215" y="237" width="120" height="13" rx="6"
                                fill="#16a99a"/>
                        </g>

                        <!-- Pink gear left -->
                        <g transform="translate(175 166)">
                            <circle r="43" fill="url(#gearPink)"/>
                            <circle r="25" fill="#f35b98"/>
                            <circle r="11" fill="#ffc1d7"/>
                            <g fill="#e84b8c">
                                <rect x="-9" y="-53" width="18" height="19" rx="4"/>
                                <rect x="-9" y="34" width="18" height="19" rx="4"/>
                                <rect x="-53" y="-9" width="19" height="18" rx="4"/>
                                <rect x="34" y="-9" width="19" height="18" rx="4"/>
                            </g>
                        </g>

                        <!-- Small gear right -->
                        <g transform="translate(365 175) scale(.65)">
                            <circle r="43" fill="url(#gearGold)"/>
                            <circle r="23" fill="#f4b84f"/>
                            <circle r="10" fill="#fff0c8"/>
                            <g fill="#e8a044">
                                <rect x="-9" y="-53" width="18" height="19" rx="4"/>
                                <rect x="-9" y="34" width="18" height="19" rx="4"/>
                                <rect x="-53" y="-9" width="19" height="18" rx="4"/>
                                <rect x="34" y="-9" width="19" height="18" rx="4"/>
                            </g>
                        </g>

                        <!-- Wrench -->
                        <g transform="rotate(38 295 150)">
                            <path d="M292 105 L292 185"
                                stroke="#d7d4c6" stroke-width="17"
                                stroke-linecap="round"/>

                            <path d="M276 105 Q263 82 281 68 L291 84 L303 68 Q321 84 306 105 Z"
                                fill="#a9a99e"/>

                            <circle cx="292" cy="183" r="10" fill="#b9b7a9"/>
                        </g>

                        <!-- Screwdriver -->
                        <g transform="rotate(40 345 130)">
                            <rect x="337" y="105" width="16" height="93" rx="8"
                                fill="#17b8a7"/>
                            <rect x="333" y="92" width="24" height="49" rx="10"
                                fill="#18a99d"/>
                            <path d="M345 198 L345 220"
                                stroke="#a8a89c" stroke-width="5"
                                stroke-linecap="round"/>
                        </g>

                    </svg>

                </div>

                <div class="hero-status">
                    <span></span>
                    SEDANG DALAM PEMELIHARAAN
                </div>

                <h1>
                    Kami sedang menyiapkan sesuatu
                    yang lebih baik.
                </h1>

                <p>
                    Pesan Ajah sedang menjalani pemeliharaan sistem
                    untuk meningkatkan kualitas layanan.
                </p>

            </section>

            <!-- TASKS -->
            <section class="tasks-panel">

                <h2>Apa yang sedang kami lakukan?</h2>

                <div class="task-item">

                    <span class="task-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.7 1.7-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5v.2h-2.4v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1L8 17l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H6.7v-2.4h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9L8 8.6l1.7-1.7.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.5v-.2h2.4v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1 1.7 1.7-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1h.2V14h-.2a1.7 1.7 0 0 0-1.5 1Z"/>
                        </svg>
                    </span>

                    <span>Peningkatan sistem dan performa website</span>

                </div>

                <div class="task-item">

                    <span class="task-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/>
                            <path d="m9 12 2 2 4-4"/>
                        </svg>
                    </span>

                    <span>Pemeriksaan keamanan dan stabilitas</span>

                </div>

                <div class="task-item">

                    <span class="task-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="7" width="18" height="14" rx="2"/>
                            <path d="M7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"/>
                        </svg>
                    </span>

                    <span>Peningkatan pengalaman belanja</span>

                </div>

            </section>

            <!-- RETURN BANNER -->
            <section class="return-panel">

                <h2>Kami akan segera kembali.</h2>

                <p>
                    Terima kasih telah menunggu dan menggunakan Pesan Ajah.
                </p>

                <button
                    type="button"
                    class="reload-button"
                    onclick="window.location.reload()"
                >
                    Coba Muat Ulang
                </button>

            </section>

        </div>

        <!-- FOOTER -->
        <footer class="maintenance-footer">
            © <?= date('Y') ?> Pesan Ajah · Developed by Erwan Sudandi
        </footer>

    </div>

</main>

</body>
</html>
