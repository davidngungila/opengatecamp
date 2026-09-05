<style>

    /* =========================================================
       FONTS
    ========================================================= */

    @font-face {
        font-family: Poppins;
        src: url("{{ ($web ?? false)
            ? asset('fonts/Poppins-Bold.ttf')
            : storage_path('fonts/Poppins-Bold.ttf') }}");
        font-weight: 700;
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
        font-weight: 900;
    }


    /* =========================================================
       VARIABLES
    ========================================================= */

    @php

        /*
        |--------------------------------------------------------------------------
        | BACKGROUND IMAGE
        |--------------------------------------------------------------------------
        */

        $bgUrl = null;

        if ($card->image_path) {

            if ($web ?? false) {

                $bgUrl = asset(
                    'storage/' . $card->image_path
                );

            } else {

                $path = storage_path(
                    'app/public/' . $card->image_path
                );

                $bgUrl = file_exists($path)
                    ? str_replace('\\', '/', $path)
                    : null;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | BACKGROUND COLOR
        |--------------------------------------------------------------------------
        */

        $bc = $card->background_color ?: '#ffffff';

        if ($bgUrl === null) {
            $bc = '#ffffff';
        }


        /*
        |--------------------------------------------------------------------------
        | OPEN GATE COLORS
        |--------------------------------------------------------------------------
        */

        $primary     = '#0758B8';
        $primaryDark = '#06448E';

        $green       = '#015425';

        $dark        = '#10233F';

        $lightBlue   = '#EAF4FF';

        $gold        = '#F2C300';

        $softGray    = '#F5F7FA';

        $border      = '#D9E4F2';

    @endphp


    /* =========================================================
       GLOBAL
    ========================================================= */

    * {
        box-sizing: border-box;

        margin: 0;

        padding: 0;
    }


    body {

        margin: 0;

        padding: 0;

        font-family:
            Poppins,
            Arial,
            sans-serif;
    }


    /* =========================================================
       PDF PAGE
    ========================================================= */

    .pdf-page {

        width: 1080px;

        height: 1350px;

        position: relative;

        overflow: hidden;

        font-family:
            Poppins,
            Arial,
            sans-serif;

        color: {{ $dark }};

        background: {{ $bc }};

        @if($bgUrl)

            background-image:
                url("{{ $bgUrl }}");

            background-size: cover;

            background-position: center;

        @endif
    }


    /* =========================================================
       BACKGROUND SCRIM
    ========================================================= */

    .pdf-page .scrim {

        position: absolute;

        inset: 0;

        width: 1080px;

        height: 1350px;

        background:
            rgba(
                255,
                255,
                255,
                0.93
            );
    }


    /* =========================================================
       MAIN CONTENT
    ========================================================= */

    .content {

        position: relative;

        z-index: 2;

        width: 100%;

        min-height: 1350px;

        padding-bottom: 20px;
    }


    /* =========================================================
       LOGO
    ========================================================= */

    .logo-area {

        text-align: center;

        padding-top: 22px;

        padding-bottom: 8px;
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

        padding:
            0
            50px
            14px;
    }


    .organization .main {

        font-family:
            "Poppins Black",
            Poppins,
            Arial,
            sans-serif;

        font-size: 29px;

        font-weight: 900;

        letter-spacing: 0.7px;

        color: {{ $dark }};

        line-height: 1.15;

        text-transform: uppercase;
    }


    .organization .sub {

        margin-top: 5px;

        font-size: 19px;

        font-weight: 700;

        letter-spacing: 1px;

        color: {{ $primary }};

        text-transform: uppercase;
    }


    /* =========================================================
       CAMPAIGN HEADER
    ========================================================= */

    .campaign-header {

        width: 100%;

        background:
            {{ $primary }};

        color: #ffffff;

        text-align: center;

        padding:
            17px
            30px
            19px;

        margin-top: 4px;

        border-top:
            5px solid
            {{ $gold }};

        border-bottom:
            5px solid
            {{ $primaryDark }};
    }


    .campaign-header .small {

        font-size: 19px;

        font-weight: 700;

        letter-spacing: 3px;

        text-transform: uppercase;
    }


    .campaign-header .large {

        margin-top: 3px;

        font-size: 43px;

        line-height: 1.05;

        font-weight: 900;

        letter-spacing: 1px;

        text-transform: uppercase;
    }


    /* =========================================================
       DONOR SECTION
    ========================================================= */

    .donor-section {

        text-align: center;

        padding:
            17px
            45px
            7px;
    }


    .donor-title {

        font-size: 18px;

        font-weight: 700;

        color: {{ $dark }};

        text-transform: uppercase;

        letter-spacing: 0.5px;
    }


    .donor-name {

        display: inline-block;

        margin-top: 4px;

        padding:
            0
            25px
            5px;

        font-size: 34px;

        line-height: 1.1;

        font-weight: 900;

        color: {{ $primary }};

        border-bottom:
            4px solid
            {{ $primary }};
    }


    /* =========================================================
       BODY
    ========================================================= */

    .body {

        padding:
            4px
            65px
            0;
    }


    .paragraph {

        font-size: 22px;

        line-height: 1.38;

        font-weight: 600;

        text-align: center;

        margin-top: 12px;
    }


    .paragraph b {

        font-weight: 900;

        color: {{ $primary }};
    }


    /* =========================================================
       CONTRIBUTION BOX
    ========================================================= */

    .contribution-box {

        margin:
            16px
            55px
            11px;

        background:
            {{ $lightBlue }};

        border:
            2px solid
            {{ $primary }};

        border-radius: 12px;

        text-align: center;

        padding:
            10px
            20px
            11px;

        position: relative;
    }


    .contribution-box:before {

        content: "";

        position: absolute;

        top: 0;

        left: 0;

        width: 100%;

        height: 5px;

        background:
            {{ $gold }};

        border-radius:
            10px
            10px
            0
            0;
    }


    .contribution-box .label {

        font-size: 16px;

        font-weight: 700;

        color: {{ $dark }};

        text-transform: uppercase;

        letter-spacing: 1px;
    }


    .contribution-box .amount {

        font-size: 37px;

        font-weight: 900;

        color: {{ $primary }};

        line-height: 1.1;

        margin-top: 2px;
    }


    .contribution-box .target {

        font-size: 16px;

        font-weight: 700;

        color: {{ $dark }};

        margin-top: 3px;
    }


    /* =========================================================
       PAYMENT TITLE
    ========================================================= */

    .payment-title {

        text-align: center;

        margin-top: 9px;

        margin-bottom: 8px;

        font-size: 21px;

        font-weight: 900;

        color: {{ $primary }};

        text-transform: uppercase;

        letter-spacing: 1px;
    }


    /* =========================================================
       PAYMENT ROW
    ========================================================= */

    .payment-row {

        width:
            calc(100% - 110px);

        margin:
            0 auto;

        display: table;

        table-layout: fixed;

        border-spacing:
            10px
            0;
    }


    .payment-column {

        display: table-cell;

        width: 50%;

        vertical-align: top;
    }


    /* =========================================================
       PAYMENT CARD
    ========================================================= */

    .payment-card {

        width: 100%;

        min-height: 116px;

        background: #ffffff;

        border:
            2px solid
            {{ $border }};

        border-radius: 12px;

        text-align: center;

        overflow: hidden;

        position: relative;

        box-shadow:
            0
            2px
            8px
            rgba(
                16,
                35,
                63,
                0.06
            );
    }


    /* =========================================================
       PAYMENT CARD HEADER
    ========================================================= */

    .payment-card .top {

        background:
            {{ $primary }};

        color: #ffffff;

        padding:
            7px
            10px;

        font-size: 15px;

        font-weight: 900;

        letter-spacing: 1.2px;

        text-transform: uppercase;
    }


    /* =========================================================
       PAYMENT CARD BODY
    ========================================================= */

    .payment-card .payment-body {

        padding:
            8px
            12px
            9px;

        text-align: center;
    }


    .payment-card .number {

        font-size: 26px;

        line-height: 1.1;

        font-weight: 900;

        letter-spacing: 1px;

        color:
            {{ $primary }};

        margin-top: 1px;
    }


    .payment-card .name {

        margin-top: 3px;

        font-size: 14px;

        line-height: 1.2;

        font-weight: 700;

        color:
            {{ $dark }};

        text-transform: uppercase;
    }


    .payment-card .type {

        margin-top: 3px;

        font-size: 11px;

        font-weight: 600;

        color: #667085;

        text-transform: uppercase;

        letter-spacing: 0.7px;
    }


    /* =========================================================
       QR SECTION
    ========================================================= */

    .qr-section {

        text-align: center;

        margin-top: 9px;
    }


    .qr-title {

        font-size: 15px;

        font-weight: 700;

        color: {{ $dark }};

        margin-bottom: 4px;
    }


    .qr {

        display: inline-block;

        padding: 5px;

        background: #ffffff;

        border:
            2px solid
            {{ $primary }};

        border-radius: 8px;
    }


    .qr img {

        display: block;

        width: 96px;

        height: 96px;

        object-fit: contain;
    }


    /* =========================================================
       MOTTO
    ========================================================= */

    .motto {

        text-align: center;

        margin:
            7px
            50px
            0;

        font-size: 16px;

        font-weight: 900;

        color:
            {{ $primary }};

        font-style: italic;
    }


    /* =========================================================
       FOOTER
    ========================================================= */

    .footer {

        margin-top: 10px;

        background:
            {{ $primary }};

        color: #ffffff;

        padding:
            8px
            35px;

        text-align: center;

        border-top:
            4px solid
            {{ $gold }};
    }


    .footer .contact {

        font-size: 15px;

        font-weight: 900;

        letter-spacing: 0.8px;
    }


    .footer .bottom {

        margin-top: 2px;

        font-size: 12px;

        font-weight: 600;
    }

</style>


<!-- =========================================================
     PDF PAGE
========================================================= -->

<div class="pdf-page">


    <!-- BACKGROUND OVERLAY -->

    @if($bgUrl)

        <div class="scrim"></div>

    @endif


    <div class="content">


        <!-- =====================================================
             LOGO
        ====================================================== -->

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


        <!-- =====================================================
             ORGANIZATION
        ====================================================== -->

        <div class="organization">

            <div class="main">

                UMOJA WA VYUO KARISMATIKI
                KATOLIKI TANZANIA

            </div>


            <div class="sub">

                JIMBO KUU KATOLIKI LA ARUSHA
                NA JIMBO LA MOSHI

            </div>

        </div>


        <!-- =====================================================
             CAMPAIGN HEADER
        ====================================================== -->

        <div class="campaign-header">

            <div class="small">

                OPEN GATE CAMP

            </div>


            <div class="large">

                SEASON THREE

            </div>

        </div>


        <!-- =====================================================
             DONOR
        ====================================================== -->

        <div class="donor-section">

            <div class="donor-title">

                Ask./Prof./Mch./Mhe./Dkt./Bw. & Bi.

            </div>


            <div class="donor-name">

                {{ $recipientName ?? '[JINA LA MCHANGIAJI]' }}

            </div>

        </div>


        <!-- =====================================================
             BODY
        ====================================================== -->

        <div class="body">


            <div class="paragraph">

                Tunayo furaha kukualika kushiriki katika

                <b>
                    Open Gate Camp Season Three
                </b>,

                tukio linalolenga kuwaunganisha na kuwajenga

                vijana wa vyuo katika

                <b>
                    imani, umoja na maendeleo.
                </b>

            </div>


            <div class="paragraph">

                Mwaka huu tunalenga kukusanya

                <b>
                    TZS 20,000,000/=
                </b>

                kwa ajili ya kugharamia mahitaji muhimu ya Camp.

                Tunaomba mchango wako wa

                <b>
                    TZS 15,000/= au zaidi
                </b>

                ili kwa pamoja tufanikishe huduma hii.

            </div>


            <div class="paragraph">

                Mchango wako ni sehemu ya mafanikio ya

                <b>
                    Open Gate Camp Season Three.
                </b>

                Kila kiasi kina thamani na kina mchango katika

                kuwafikia, kuwaunganisha na kuwajenga vijana.

            </div>

        </div>


        <!-- =====================================================
             CONTRIBUTION BOX
        ====================================================== -->

        <div class="contribution-box">


            <div class="label">

                Mchango Unaopendekezwa

            </div>


            <div class="amount">

                TZS 15,000/=

            </div>


            <div class="target">

                Lengo la Camp:
                TZS 20,000,000/=

            </div>


        </div>


        <!-- =====================================================
             PAYMENT TITLE
        ====================================================== -->

        <div class="payment-title">

            CHANGIA KUPITIA

        </div>


        <!-- =====================================================
             PAYMENT METHODS
             TWO CARDS — SAME ROW
        ====================================================== -->

        <div class="payment-row">


            <!-- =================================================
                 M-PESA
            ================================================== -->

            <div class="payment-column">

                <div class="payment-card">


                    <div class="top">

                        M-PESA

                    </div>


                    <div class="payment-body">


                        <div class="number">

                            0756 112 102

                        </div>


                        <div class="name">

                            TAFES MoCU

                        </div>


                        <div class="type">

                            Namba ya Simu ya Malipo

                        </div>


                    </div>

                </div>

            </div>


            <!-- =================================================
                 NMB ACCOUNT
            ================================================== -->

            <div class="payment-column">

                <div class="payment-card">


                    <div class="top">

                        NMB ACCOUNT

                    </div>


                    <div class="payment-body">


                        <div class="number">

                            40310079853

                        </div>


                        <div class="name">

                            TAFES MoCU

                        </div>


                        <div class="type">

                            Namba ya Akaunti ya Benki

                        </div>


                    </div>

                </div>

            </div>


        </div>


        <!-- =====================================================
             QR CODE
        ====================================================== -->

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


        <!-- =====================================================
             MOTTO
        ====================================================== -->

        <div class="motto">

            “Wahubirije Wasipopelekwa?...”
            — Warumi 10:15

        </div>


        <!-- =====================================================
             FOOTER
        ====================================================== -->

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
