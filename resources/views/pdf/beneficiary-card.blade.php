<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page {
        margin: 0;
        size: 241.89pt 153.07pt;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: Helvetica, Arial, sans-serif;
        width: 241.89pt;
        height: 153.07pt;
        background-color: #ffffff;
    }

    /* FRONT CARD */
    .card-page {
        width: 241.89pt;
        height: 153.07pt;
        page-break-after: always;
        position: relative;
    }

    .card-front {
        width: 241.89pt;
        height: 153.07pt;
        background-color: #660000;
        color: #ffffff;
    }

    .header-table {
        width: 100%;
        background-color: #4d0000;
        padding: 4pt 6pt;
        border-bottom: 1pt solid #990000;
    }

    .logo-td {
        width: 24pt;
        vertical-align: middle;
    }

    .logo-circle {
        width: 20pt;
        height: 20pt;
        background-color: #ffffff;
        color: #800000;
        font-weight: bold;
        font-size: 6.5pt;
        text-align: center;
        line-height: 20pt;
        border-radius: 10pt;
    }

    .header-text-td {
        vertical-align: middle;
        padding-left: 4pt;
    }

    .agency {
        font-size: 5pt;
        color: #fca5a5;
        letter-spacing: 0.3pt;
    }

    .program {
        font-size: 7pt;
        font-weight: bold;
        color: #ffffff;
    }

    .badge-td {
        vertical-align: middle;
        text-align: right;
    }

    .badge {
        font-size: 5pt;
        background-color: #ffffff;
        color: #800000;
        padding: 2pt 4pt;
        font-weight: bold;
        border-radius: 2pt;
    }

    .body-table {
        width: 100%;
        padding: 6pt;
    }

    .photo-td {
        width: 50pt;
        vertical-align: top;
    }

    .photo-box {
        width: 48pt;
        height: 58pt;
        border: 1.5pt solid #fca5a5;
        background-color: #330000;
        text-align: center;
    }

    .photo-box img {
        width: 48pt;
        height: 58pt;
    }

    .photo-placeholder {
        font-size: 6pt;
        color: #fca5a5;
        padding-top: 20pt;
        line-height: 1.2;
    }

    .info-td {
        vertical-align: top;
        padding-left: 6pt;
    }

    .name {
        font-size: 8.5pt;
        font-weight: bold;
        color: #ffffff;
        text-transform: uppercase;
    }

    .middle-name {
        font-size: 6.5pt;
        color: #fca5a5;
    }

    .label {
        font-size: 4.5pt;
        color: #fca5a5;
        text-transform: uppercase;
        margin-top: 3pt;
    }

    .val {
        font-size: 6.5pt;
        color: #ffffff;
        font-weight: 500;
    }

    .footer-table {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        background-color: #330000;
        padding: 3pt 6pt;
    }

    .uid-label {
        font-size: 4.5pt;
        color: #fca5a5;
    }

    .uid-val {
        font-size: 7.5pt;
        font-weight: bold;
        color: #ffffff;
        font-family: 'Courier New', monospace;
    }

    .city-val {
        font-size: 6pt;
        font-weight: bold;
        color: #ffffff;
        text-align: right;
    }

    /* BACK CARD */
    .card-back {
        width: 241.89pt;
        height: 153.07pt;
        background-color: #ffffff;
        color: #333333;
    }

    .back-header {
        background-color: #4d0000;
        color: #ffffff;
        font-size: 5.5pt;
        text-align: center;
        padding: 3pt;
        font-weight: bold;
    }

    .qr-td {
        width: 80pt;
        vertical-align: top;
        text-align: center;
    }

    .qr-box {
        width: 72pt;
        height: 72pt;
        border: 1.5pt solid #800000;
        background-color: #ffffff;
        margin: 0 auto;
        padding: 2pt;
    }

    .qr-box img {
        width: 68pt;
        height: 68pt;
    }

    .qr-text {
        font-size: 5.5pt;
        color: #800000;
        font-weight: bold;
        margin-top: 2pt;
    }

    .creds-td {
        vertical-align: top;
        padding-left: 6pt;
    }

    .cred-lbl {
        font-size: 4.5pt;
        color: #666666;
        text-transform: uppercase;
    }

    .cred-box {
        font-size: 7pt;
        font-weight: bold;
        color: #800000;
        font-family: 'Courier New', monospace;
        background-color: #fff1f2;
        border: 0.5pt solid #fecdd3;
        padding: 1.5pt 3pt;
        margin-bottom: 3pt;
    }

    .cred-pass {
        color: #990000;
    }

    .notice {
        font-size: 4pt;
        color: #777777;
        line-height: 1.2;
        margin-top: 4pt;
    }

    .back-footer {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        background-color: #800000;
        color: #ffffff;
        font-size: 4.5pt;
        text-align: center;
        padding: 2.5pt;
    }
</style>
</head>
<body>

<!-- FRONT SIDE -->
<div class="card-page">
    <div class="card-front">
        <table class="header-table" cellspacing="0" cellpadding="0">
            <tr>
                <td class="logo-td">
                    <div class="logo-circle">DSWD</div>
                </td>
                <td class="header-text-td">
                    <div class="agency">Republic of the Philippines &mdash; DSWD</div>
                    <div class="program">Pantawid Pamilyang Pilipino Program (4Ps)</div>
                </td>
                <td class="badge-td">
                    <span class="badge">BENEFICIARY ID</span>
                </td>
            </tr>
        </table>

        <table class="body-table" cellspacing="0" cellpadding="0">
            <tr>
                <td class="photo-td">
                    <div class="photo-box">
                        @if($photoBase64)
                            <img src="{{ $photoBase64 }}" alt="Photo">
                        @else
                            <div class="photo-placeholder">NO<br>PHOTO</div>
                        @endif
                    </div>
                </td>
                <td class="info-td">
                    <div class="name">{{ strtoupper($beneficiary->last_name) }}, {{ $beneficiary->first_name }}</div>
                    @if($beneficiary->middle_name)
                        <div class="middle-name">{{ $beneficiary->middle_name }} {{ $beneficiary->suffix }}</div>
                    @endif

                    <div class="label">Birthdate</div>
                    <div class="val">{{ $beneficiary->birthdate ? date('F d, Y', strtotime($beneficiary->birthdate)) : '—' }}</div>

                    <div class="label">Address</div>
                    <div class="val">Brgy. {{ $beneficiary->barangay }}, {{ $beneficiary->city ?? 'Lipa City' }}, Batangas</div>
                </td>
            </tr>
        </table>

        <table class="footer-table" cellspacing="0" cellpadding="0">
            <tr>
                <td>
                    <div class="uid-label">UNIQUE ID</div>
                    <div class="uid-val">{{ $beneficiary->unique_id }}</div>
                </td>
                <td class="city-val">
                    <div>LIPA CITY</div>
                    <div style="font-size:4.5pt; opacity:0.8; font-weight:normal;">Batangas</div>
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- BACK SIDE -->
<div class="card-page" style="page-break-after: avoid;">
    <div class="card-back">
        <div class="back-header">
            SECURE 4Ps &mdash; System for Eligibility Checking, Unified Records, and Evaluation
        </div>

        <table class="body-table" cellspacing="0" cellpadding="0">
            <tr>
                <td class="qr-td">
                    <div class="qr-box">
                        @if($qrImageBase64)
                            <img src="{{ $qrImageBase64 }}" alt="QR Code">
                        @else
                            <div style="font-size:5pt; color:#800000; padding-top:20pt;">QR CODE</div>
                        @endif
                    </div>
                    <div class="qr-text">SCAN TO VERIFY</div>
                </td>
                <td class="creds-td">
                    <div class="cred-lbl">Card Number</div>
                    <div class="cred-box">{{ $card->card_number ?? 'CARD-LPA-00001' }}</div>

                    <div class="cred-lbl">Unique ID</div>
                    <div class="cred-box">{{ $beneficiary->unique_id }}</div>

                    <div class="cred-lbl">Default Password (First Login)</div>
                    <div class="cred-box cred-pass">{{ $defaultPassword }}</div>

                    <div class="notice">
                        This card is government property. If found, please return to DSWD Lipa City, Batangas. Portal: secure4ps.dswd.gov.ph
                    </div>
                </td>
            </tr>
        </table>

        <div class="back-footer">
            Issued by: DSWD Lipa City SWDO &bull; Card No: {{ $card->card_number ?? '—' }} &bull; Issued: {{ date('m/d/Y') }}
        </div>
    </div>
</div>

</body>
</html>
