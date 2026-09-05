<style>
    @font-face {
        font-family: Poppins;
        src: url("{{ ($web ?? false)
            ? asset('fonts/Poppins-Bold.ttf')
            : storage_path('fonts/Poppins-Bold.ttf') }}");
        font-weight: bold;
    }

    @font-face {
        font-family: Poppins;
        src: url("{{ ($web ?? false)
            ? asset('fonts/Poppins-Black.ttf')
            : storage_path('fonts/Poppins-Black.ttf') }}");
        font-weight: 900;
    }

    @font-face {
        font-family: "Poppins Black";
        src: url("{{ ($web ?? false)
            ? asset('fonts/Poppins-Black.ttf')
            : storage_path('fonts/Poppins-Black.ttf') }}");
        font-weight: normal;
    }

    @php
        $bgUrl = null;

        if ($card->image_path) {
            if ($web ?? false) {
                $bgUrl = asset('storage/' . $card->image_path);
            } else {
                $path = storage_path('app/public/' . $card->image_path);

                $bgUrl = file_exists($path)
                    ? str_replace('\\', '/', $path)
                    : null;
            }
        }

        $bc = $card->background_color ?: '#ffffff';

        if ($bgUrl === null) {
            $bc = '#ffffff';
        }

        /*
        |--------------------------------------------------------------------------
        | OPEN GATE COLORS
        |--------------------------------------------------------------------------
        */
        $primary = '#0758B8';
        $primaryDark = '#06448E';
        $green = '#015425';
        $dark = '#10233F';
        $lightBlue = '#EAF4FF';
        $gold = '#F2C300';
    @endphp


    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    .pdf-page {
        width: 1080px;
        height: 1350px;
        position: relative;
        overflow: hidden;

        font-family: Poppins, Arial, sans-serif;
        color: {{ $dark }};

        background: {{ $bc }};

        @if($bgUrl)
            background-image: url("{{ $bgUrl }}");
            background-size: cover;
            background-position: center;
        @endif
    }

    .pdf-page .scrim {
        position: absolute;
        inset: 0;
        width: 1080px;
        height: 1350px;
        background: rgba(255,255,255,.90);
    }

    .content {
        position: relative;
        z-index: 2;
        width: 100%;
        min-height: 1350px;
        padding-bottom: 25px;
    }

    /* =========================================================
       LOGO
    ========================================================= */

    .logo-area {
        text-align: center;
        padding-top: 24px;
        padding-bottom: 12px;
    }

    .logo-area img {
        width: 105px;
        height: 105px;
        object-fit: contain;
    }


    /* =========================================================
       ORGANIZATION HEADER
    ========================================================= */

    .organization {
        text-align: center;
        padding: 0 45px 15px;
    }

    .organization .main {
        font-family: "Poppins Black", Poppins, Arial, sans-serif;
        font-size: 30px;
        font-weight: bold;
        letter-spacing: .8px;
        color: #000000;
        line-height: 1.15;
        text-transform: uppercase;
    }

    .organization .sub {
        margin-top: 6px;
        font-size: 21px;
        font-weight: bold;
        letter-spacing: 1.2px;
        color: {{ $primary }};
        text-transform: uppercase;
    }


    /* =========================================================
       BLUE CAMPAIGN HEADER — TAFES STYLE
    ========================================================= */

    .campaign-header {
        width: 100%;
        background: {{ $primary }};
        color: #ffffff;
        text-align: center;

        padding: 20px 30px 22px;

        margin-top: 5px;
    }

    .campaign-header .small {
        font-size: 20px;
        font-weight: bold;
        letter-spacing: 2px;
        text-transform: uppercase;
    }

    .campaign-header .large {
        margin-top: 5px;

        font-size: 43px;
        line-height: 1.05;
        font-weight: 900;

        text-transform: uppercase;
    }


    /* =========================================================
       DONOR
    ========================================================= */

    .donor-section {
        text-align: center;
        padding: 18px 45px 8px;
    }

    .donor-title {
        font-size: 24px;
        font-weight: bold;
        color: {{ $dark }};
    }

    .donor-name {
        display: inline-block;

        margin-top: 5px;
        padding: 0 18px 5px;

        font-size: 42px;
        line-height: 1.1;

        font-weight: 900;
        color: {{ $primary }};

        border-bottom: 4px solid {{ $primary }};
    }


    /* =========================================================
       BODY
    ========================================================= */

    .body {
        padding: 5px 45px 0;
    }

    .paragraph {
        font-size: 27px;
        line-height: 1.42;

        font-weight: 600;

        text-align: center;

        margin-top: 14px;
    }

    .paragraph b {
        font-weight: 900;
        color: {{ $primary }};
    }


    /* =========================================================
       CONTRIBUTION BOX
    ========================================================= */

    .contribution-box {
        margin: 18px 40px 12px;

        background: {{ $lightBlue }};

        border-left: 8px solid {{ $primary }};
        border-right: 8px solid {{ $primary }};

        border-radius: 8px;

        text-align: center;

        padding: 13px 20px;
    }

    .contribution-box .label {
        font-size: 18px;
        font-weight: bold;
        color: {{ $dark }};
        text-transform: uppercase;
    }

    .contribution-box .amount {
        font-size: 38px;
        font-weight: 900;
        color: {{ $primary }};
        line-height: 1.1;

        margin-top: 2px;
    }

    .contribution-box .target {
        font-size: 17px;
        font-weight: bold;
        color: {{ $dark }};
        margin-top: 4px;
    }

    .contribution-box .note {
        font-size: 16px;
        font-weight: bold;
        color: {{ $primary }};
        margin-top: 8px;
        line-height: 1.4;
    }


    /* =========================================================
       QR
    ========================================================= */

    .qr-section {
        text-align: center;
        margin-top: 10px;
    }

    .qr-title {
        font-size: 16px;
        font-weight: bold;
        color: {{ $dark }};
        margin-bottom: 5px;
    }

    .qr img {
        width: 105px;
        height: 105px;
        object-fit: contain;
    }


    /* =========================================================
       SIGNATURES
    ========================================================= */

    .signatures {
        width: 100%;
        margin-top: 12px;

        table-layout: fixed;
    }

    .signatures td {
        width: 33.33%;

        text-align: center;

        vertical-align: bottom;
    }

    .signatures .sign-line {
        margin: 0 16px;

        border-bottom: 2px solid {{ $dark }};

        height: 46px;
    }

    .signatures .sign-name {
        margin-top: 4px;

        font-size: 15px;
        font-weight: 900;

        color: {{ $dark }};

        text-transform: uppercase;
    }

    .signatures .sign-role {
        font-size: 13px;
        font-weight: bold;

        color: {{ $primary }};

        text-transform: uppercase;
    }

    .signatures .sign-contact {
        font-size: 11.5px;
        font-weight: bold;

        color: {{ $dark }};
    }


    /* =========================================================
       MOTTO
    ========================================================= */

    .motto {
        text-align: center;

        margin: 8px 50px 0;

        font-size: 17px;
        font-weight: 900;

        color: {{ $primary }};

        font-style: italic;
    }


    /* =========================================================
       FOOTER
    ========================================================= */

    .footer {
        margin-top: 12px;

        background: {{ $primary }};

        color: #ffffff;

        padding: 9px 35px;

        text-align: center;
    }

    .footer .contact {
        font-size: 16px;
        font-weight: bold;
    }

    .footer .bottom {
        margin-top: 3px;

        font-size: 14px;
        font-weight: 600;
    }

</style>


<div class="pdf-page">

    @if($bgUrl)
        <div class="scrim"></div>
    @endif


    <div class="content">

        {{-- =====================================================
             LOGO
        ====================================================== --}}
        <div class="logo-area">

            @if($web ?? false)

                <img
                    src="{{ asset('logo.png') }}"
                    alt="OpenGate Camp Connect"
                >

            @elseif(file_exists(public_path('logo.png')))

                <img
                    src="{{ public_path('logo.png') }}"
                    alt="OpenGate Camp Connect"
                >

            @endif

        </div>


        {{-- =====================================================
             ORGANIZATION
        ====================================================== --}}
        <div class="organization">

            <div class="main">
                UMOJA WA VYUO KARISMATIKI KATOLIKI TANZANIA
            </div>

            <div class="sub">
                JIMBO KUU KATOLIKI LA ARUSHA NA JIMBO LA MOSHI
            </div>

        </div>


        {{-- =====================================================
             MAIN BLUE HEADER
        ====================================================== --}}
        <div class="campaign-header">

            <div class="small">
                OPEN GATE CAMP
            </div>

            <div class="large">
                SEASON THREE
            </div>

        </div>


        {{-- =====================================================
             DONOR
        ====================================================== --}}
        <div class="donor-section">

            <div class="donor-title">
                Ask./Prof./Mch./Mhe./Dkt./Bw. &amp; Bi.
            </div>

            <div class="donor-name">
                {{ $recipientName ?? '[JINA LA MCHANGIAJI]' }}
            </div>

        </div>


        {{-- =====================================================
             THREE SHORT PARAGRAPHS
        ====================================================== --}}
        <div class="body">

            <div class="paragraph">

                Tunayo furaha kukualika kushiriki katika
                <b>Open Gate Camp Season Three</b>,
                tukio linalolenga kuwaunganisha na kuwajenga
                vijana wa vyuo katika <b>imani, umoja na maendeleo.</b>

            </div>


            <div class="paragraph">

                Mwaka huu tunalenga kukusanya
                <b>TZS 20,000,000/=</b> kwa ajili ya kugharamia
                mahitaji muhimu ya Camp. Tunaomba mchango wako wa
                <b>TZS 15,000/= au zaidi</b> ili kwa pamoja tufanikishe
                huduma hii.

            </div>


            <div class="paragraph">

                Mchango wako ni sehemu ya mafanikio ya
                <b>Open Gate Camp Season Three.</b>
                Kila kiasi kina thamani na kina mchango katika
                kuwafikia, kuwaunganisha na kuwajenga vijana.

            </div>

        </div>


        {{-- =====================================================
             CONTRIBUTION
        ====================================================== --}}
        <div class="contribution-box">

            @php
                $noteSecName = (string) \App\Models\Setting::get('digital_card.leader_secretary_name', '');
                $noteSecPhone = (string) \App\Models\Setting::get('digital_card.leader_secretary_phone', '');
                $noteTreName = (string) \App\Models\Setting::get('digital_card.leader_treasurer_name', '');
                $noteTrePhone = (string) \App\Models\Setting::get('digital_card.leader_treasurer_phone', '');
                $noteSecBlock = ($noteSecName ?: '[Jina]').' — '.($noteSecPhone ?: '[Namba ya Simu]');
                $noteTreBlock = ($noteTreName ?: '[Jina]').' — '.($noteTrePhone ?: '[Namba ya Simu]');
            @endphp
            <div class="note">
                Unaweza kuwasilisha mchango wako sasa kupitia Lipa Namba ya Vodacom: 67676666 au kwa namba ya simu 0756 112 102. Kwa mawasiliano zaidi kuhusu namna ya kuwasilisha mchango wako, wasiliana na Katibu {{ $noteSecBlock }} au Mhazini {{ $noteTreBlock }}.
            </div>

        </div>


        {{-- =====================================================
             QR
        ====================================================== --}}
        @if($qrData)

            <div class="qr-section">

                <div class="qr-title">
                    Scan QR Code kuchangia Online
                </div>

                <div class="qr">
                    <img
                        src="{{ $qrData }}"
                        alt="Scan to contribute"
                    >
                </div>

            </div>

        @endif


        {{-- =====================================================
             MOTTO
        ====================================================== --}}
        <div class="motto">

            “Wahubirije Wasipopelekwa?...” — Warumi 10:15

        </div>


        {{-- =====================================================
             FOOTER
        ====================================================== --}}
        <div class="footer">

            <div class="contact">
                OPEN GATE CAMP SEASON THREE
            </div>

            <div class="bottom">
                {{ $card->card_no }}
                · OpenGate Camp Connect
                · Mchango wako una thamani
            </div>

        </div>

    </div>

</div>