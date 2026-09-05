<style>
  @font-face { font-family: Manrope; src: url("{{ ($web ?? false) ? asset('fonts/Manrope-Regular.ttf') : storage_path('fonts/Manrope-Regular.ttf') }}"); font-weight: normal; font-style: normal; }
  @font-face { font-family: Manrope; src: url("{{ ($web ?? false) ? asset('fonts/Manrope-Bold.ttf') : storage_path('fonts/Manrope-Bold.ttf') }}"); font-weight: bold; font-style: normal; }
  @font-face { font-family: Manrope; src: url("{{ ($web ?? false) ? asset('fonts/Manrope-ExtraBold.ttf') : storage_path('fonts/Manrope-ExtraBold.ttf') }}"); font-weight: 800; font-style: normal; }
  @php
    $bgUrl = null;
    if ($card->image_path) {
        if ($web ?? false) {
            $bgUrl = asset('storage/'.$card->image_path);
        } else {
            $bgUrl = file_exists(storage_path('app/public/'.$card->image_path))
                ? str_replace('\\', '/', storage_path('app/public/'.$card->image_path))
                : null;
        }
    }
    $bc = (string) ($card->background_color ?: '#ffffff');
    if ($bgUrl === null) {
        $bc = '#ffffff';
    }
    $navy = '#1a3a6e';
    $navyDark = '#0f2a52';
  @endphp
  .pdf-page{font-family:Manrope,Arial,sans-serif;color:{{ $navy }};width:1080px;height:1350px;position:relative;line-height:1.32;background-color:{{ $bc }};@if($bgUrl) background-image:url("{{ $bgUrl }}");background-size:cover;background-position:center;@endif}
  .pdf-page *{box-sizing:border-box;margin:0;padding:0;}
  .pdf-page .scrim{position:absolute;top:0;left:0;width:1080px;height:1350px;background:rgba(255,255,255,.06);}
  .pdf-page .sheet{padding:0 46px;position:relative;overflow:hidden;}
  .pdf-page .logo{text-align:center;padding-top:20px;margin-bottom:6px;}
  .pdf-page .logo img{width:96px;height:96px;}
  .pdf-page .hline{width:100%;height:3px;background:{{ $card->accent_color }};margin-top:8px;}
  .pdf-page .org-head{text-align:center;font-size:34px;font-weight:800;color:{{ $navy }};letter-spacing:1px;margin-top:10px;line-height:1.15;text-transform:uppercase;}
  .pdf-page .org-jimbo{text-align:center;font-size:26px;font-weight:800;color:{{ $navy }};letter-spacing:1px;margin-top:4px;line-height:1.2;text-transform:uppercase;}
  .pdf-page .org-season{text-align:center;font-size:30px;font-weight:800;color:{{ $navy }};letter-spacing:2px;margin-top:6px;line-height:1.1;text-transform:uppercase;}
  .pdf-page .title{font-size:30px;font-weight:800;text-align:center;letter-spacing:5px;border-top:2px dashed {{ $navy }};border-bottom:2px dashed {{ $navy }};padding:7px 0;margin:12px 0;color:{{ $navy }};text-transform:uppercase;}
  .pdf-page table.head{width:100%;border-collapse:collapse;font-size:19px;}
  .pdf-page table.head td{padding:2px 5px;}
  .pdf-page table.head td.lbl{color:{{ $navy }};font-weight:bold;}
  .pdf-page table.head td.r{text-align:right;font-weight:bold;white-space:normal;color:{{ $navy }};}
  .pdf-page .ruled{border-top:2px dashed {{ $navy }};margin:9px 0;}
  .pdf-page .letter-title{font-size:26px;font-weight:800;text-align:center;color:{{ $navy }};margin:12px 0 2px;line-height:1.2;text-transform:uppercase;}
  .pdf-page .title-line{width:100px;height:2px;background:{{ $card->accent_color }};margin:4px auto 10px;}
  .pdf-page .donor-title{font-size:20px;font-weight:bold;color:{{ $navy }};text-align:center;margin:2px 0;line-height:1.2;}
  .pdf-page .donor-name{font-size:22px;font-weight:800;color:{{ $navy }};text-align:center;margin:6px 0;line-height:1.2;}
  .pdf-page .donor-name .no{font-weight:bold;color:{{ $navy }};}
  .pdf-page .aff-line{font-size:18px;font-weight:bold;color:{{ $navy }};text-align:left;margin:6px 0 0;line-height:1.3;}
  .pdf-page .body-text{font-size:17.5px;color:{{ $navy }};font-weight:600;text-align:justify;margin-top:8px;line-height:1.42;}
  .pdf-page .body-text b{font-weight:800;}
  .pdf-page .amt-note{font-size:18px;font-weight:800;color:{{ $navy }};text-align:center;margin:8px 0 2px;line-height:1.3;}
  .pdf-page .qr{text-align:center;margin:8px 0 0;}
  .pdf-page .qr img{width:100px;height:100px;}
  .pdf-page .qr-cap{text-align:center;font-size:16px;font-weight:bold;color:{{ $navy }};margin-bottom:2px;}
  .pdf-page .foot{margin-top:8px;text-align:center;font-size:16px;line-height:1.4;border-top:2px dashed {{ $navy }};padding-top:6px;color:{{ $navy }};font-weight:600;}
</style>
<div class="pdf-page">
  @if($bgUrl)<div class="scrim"></div>@endif
  <div class="sheet">

  <div class="logo">
    @if($web ?? false)
    <img src="{{ asset('logo.png') }}" alt="OpenGate Camp Connect">
    @elseif(file_exists(public_path('logo.png')))
    <img src="{{ public_path('logo.png') }}" alt="OpenGate Camp Connect">
    @endif
  </div>

  <div class="hline"></div>
  <div class="org-head">UMOJA WA VYUO &mdash; KARISMATIKI KATOLIKI TANZANIA</div>
  <div class="org-jimbo">JIMBO KUU KATOLIKI LA ARUSHA NA JIMBO LA MOSHI</div>
  <div class="org-season">OPEN GATE SEASON THREE</div>

  <div class="title">DIGITAL CARD</div>

  <table class="head" cellpadding="0" cellspacing="0">
    <tr><td class="lbl">Card No</td><td class="r"><b>{{ $card->card_no }}</b></td></tr>
    <tr><td class="lbl">Card Link</td><td class="r" style="font-weight:normal">{{ $card->public_url }}</td></tr>
  </table>

  <div class="ruled"></div>

  <div class="letter-title">MCHANGO WA OPEN GATE CAMP SEASON THREE</div>
  <div class="title-line"></div>

  <div class="donor-title">Ask./Prof./Mch./Mhe./Dkt./Bw. &amp; Bi.</div>
  <div class="donor-name"><span class="no">[</span>JINA LA MCHANGIAJI<span class="no">]</span></div>

  <div class="aff-line">Umoja wa Vyuo &ndash; Karismatiki Katoliki Tanzania</div>
  <div class="aff-line">Jimbo la Moshi na Arusha</div>

  <div class="body-text">
    Tunayo furaha kukualika kushiriki katika Open Gate Camp Season Three, tukio linalolenga kuwaunganisha, kuwajenga na kuwawezesha vijana wa vyuo katika imani, mahusiano, uongozi na maendeleo ya maisha.
  </div>

  <div class="body-text">
    Kwa mwaka huu, Open Gate Camp Season Three inalenga kukusanya jumla ya <b>TZS 20,000,000/=</b> kwa ajili ya kugharamia mahitaji mbalimbali ya Camp, ikiwemo usafiri, chakula, malazi, vifaa, mafunzo na shughuli za huduma. Ukiwa mwanachama, mshirika, rafiki au mdau wa Open Gate, tunaomba mchango wako wa <b>TZS 15,000/=</b> au zaidi ili kwa pamoja tufanikishe jambo hili. Kila mchango una thamani na kila mmoja ana nafasi katika mafanikio ya Camp hii.
  </div>

  <div class="amt-note">TZS 15,000/= au zaidi · Kila mchango una thamani</div>

  @if($qrData)
  <div class="qr">
    <div class="qr-cap">Tuma kwa kuchanganua hii QR / scan to contribute online:</div>
    <img src="{{ $qrData }}" alt="QR">
  </div>
  @endif

  <div class="foot">
    {{ $card->card_no }}@if($card->title) · {{ $card->title }}@endif · OpenGate Camp Connect · Mchango wako una thamani
  </div>

  </div>
</div>