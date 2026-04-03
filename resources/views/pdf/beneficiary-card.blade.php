<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: Arial, sans-serif;
        width: 3.375in;
        height: 2.125in;
        background: #fff;
        overflow: hidden;
    }

    /* FRONT CARD */
    .card-front {
        width: 3.375in;
        height: 2.125in;
        position: relative;
        background: linear-gradient(135deg, #003087 0%, #0051a8 60%, #1a69c8 100%);
        color: white;
        display: flex;
        flex-direction: column;
        page-break-after: always;
    }

    .card-front .card-header {
        display: flex;
        align-items: center;
        padding: 6pt 8pt 4pt;
        border-bottom: 1.5pt solid rgba(255,255,255,0.3);
        background: rgba(0,0,0,0.15);
    }

    .card-front .card-header .logo-circle {
        width: 22pt;
        height: 22pt;
        border-radius: 50%;
        background: #fcd116;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 7pt;
        color: #003087;
        flex-shrink: 0;
    }

    .card-front .card-header .header-text {
        margin-left: 5pt;
    }

    .card-front .card-header .header-text .agency {
        font-size: 5.5pt;
        opacity: 0.85;
        letter-spacing: 0.3pt;
    }

    .card-front .card-header .header-text .program {
        font-size: 7.5pt;
        font-weight: bold;
        letter-spacing: 0.5pt;
    }

    .card-front .card-header .card-type {
        margin-left: auto;
        font-size: 5pt;
        background: #fcd116;
        color: #003087;
        padding: 2pt 4pt;
        border-radius: 2pt;
        font-weight: bold;
    }

    .card-front .card-body {
        display: flex;
        flex: 1;
        padding: 6pt 8pt;
        gap: 8pt;
    }

    .card-front .photo-section {
        flex-shrink: 0;
    }

    .card-front .photo-box {
        width: 50pt;
        height: 55pt;
        border: 2pt solid rgba(255,255,255,0.5);
        border-radius: 3pt;
        overflow: hidden;
        background: rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .card-front .photo-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .card-front .photo-placeholder {
        font-size: 6pt;
        opacity: 0.6;
        text-align: center;
    }

    .card-front .info-section {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .card-front .info-section .name {
        font-size: 9pt;
        font-weight: bold;
        line-height: 1.2;
        text-transform: uppercase;
    }

    .card-front .info-section .label {
        font-size: 5pt;
        opacity: 0.7;
        text-transform: uppercase;
        letter-spacing: 0.4pt;
        margin-top: 4pt;
        margin-bottom: 1pt;
    }

    .card-front .info-section .value {
        font-size: 7pt;
        font-weight: 500;
    }

    .card-front .card-footer {
        padding: 4pt 8pt;
        background: rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-front .card-footer .uid {
        font-size: 7.5pt;
        font-weight: bold;
        letter-spacing: 1pt;
        font-family: 'Courier New', monospace;
    }

    .card-front .card-footer .city-label {
        font-size: 5.5pt;
        opacity: 0.8;
    }

    /* BACK CARD */
    .card-back {
        width: 3.375in;
        height: 2.125in;
        position: relative;
        background: #f8f9fa;
        display: flex;
        flex-direction: column;
    }

    .card-back .back-header {
        background: #003087;
        color: white;
        padding: 5pt 8pt;
        font-size: 6pt;
        text-align: center;
        letter-spacing: 0.5pt;
    }

    .card-back .back-body {
        display: flex;
        flex: 1;
        padding: 6pt 8pt;
        gap: 10pt;
    }

    .card-back .qr-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3pt;
    }

    .card-back .qr-box {
        width: 65pt;
        height: 65pt;
        border: 1.5pt solid #003087;
        border-radius: 3pt;
        overflow: hidden;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .card-back .qr-box img {
        width: 100%;
        height: 100%;
    }

    .card-back .qr-label {
        font-size: 5pt;
        color: #003087;
        font-weight: bold;
        text-align: center;
    }

    .card-back .credentials {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4pt;
    }

    .card-back .cred-label {
        font-size: 5pt;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.4pt;
    }

    .card-back .cred-value {
        font-size: 7.5pt;
        font-weight: bold;
        color: #003087;
        font-family: 'Courier New', monospace;
        background: #e8edf8;
        padding: 2pt 4pt;
        border-radius: 2pt;
        letter-spacing: 1pt;
    }

    .card-back .notice {
        font-size: 4.5pt;
        color: #888;
        line-height: 1.4;
        margin-top: auto;
    }

    .card-back .back-footer {
        background: #ce1126;
        color: white;
        padding: 3pt 8pt;
        font-size: 5pt;
        text-align: center;
    }
</style>
</head>
<body>

<!-- FRONT SIDE -->
<div class="card-front">
    <div class="card-header">
        <div class="logo-circle">DSWD</div>
        <div class="header-text">
            <div class="agency">Republic of the Philippines — DSWD</div>
            <div class="program">Pantawid Pamilyang Pilipino Program (4Ps)</div>
        </div>
        <div class="card-type">BENEFICIARY ID</div>
    </div>

    <div class="card-body">
        <div class="photo-section">
            <div class="photo-box">
                @if($photoBase64)
                    <img src="{{ $photoBase64 }}" alt="Photo">
                @else
                    <div class="photo-placeholder">NO<br>PHOTO</div>
                @endif
            </div>
        </div>

        <div class="info-section">
            <div>
                <div class="name">{{ strtoupper($beneficiary->last_name) }}, {{ $beneficiary->first_name }}</div>
                @if($beneficiary->middle_name)
                    <div style="font-size:6.5pt; opacity:0.85;">{{ $beneficiary->middle_name }} {{ $beneficiary->suffix }}</div>
                @endif
            </div>

            <div>
                <div class="label">Birthdate</div>
                <div class="value">{{ $beneficiary->birthdate->format('F d, Y') }}</div>
            </div>

            <div>
                <div class="label">Address</div>
                <div class="value" style="font-size:6pt; line-height:1.3;">
                    Brgy. {{ $beneficiary->barangay }}, {{ $beneficiary->city }}, {{ $beneficiary->province }}
                </div>
            </div>
        </div>
    </div>

    <div class="card-footer">
        <div>
            <div style="font-size:5pt; opacity:0.7; margin-bottom:1pt;">UNIQUE ID</div>
            <div class="uid">{{ $beneficiary->unique_id }}</div>
        </div>
        <div class="city-label" style="text-align:right;">
            <div style="font-weight:bold;">LIPA CITY</div>
            <div>Batangas</div>
        </div>
    </div>
</div>

<!-- BACK SIDE -->
<div class="card-back">
    <div class="back-header">
        SECURE 4Ps — System for Eligibility Checking, Unified Records, and Evaluation
    </div>

    <div class="back-body">
        <div class="qr-section">
            <div class="qr-box">
                @if($qrImageBase64)
                    <img src="{{ $qrImageBase64 }}" alt="QR Code">
                @else
                    <div style="font-size:5pt; color:#003087; text-align:center; padding:4pt;">QR CODE</div>
                @endif
            </div>
            <div class="qr-label">SCAN TO VERIFY</div>
        </div>

        <div class="credentials">
            <div>
                <div class="cred-label">Card Number</div>
                <div class="cred-value" style="font-size:6.5pt;">{{ $card->card_number }}</div>
            </div>
            <div>
                <div class="cred-label">Unique ID</div>
                <div class="cred-value">{{ $beneficiary->unique_id }}</div>
            </div>
            <div>
                <div class="cred-label">Default Password (Change on first login)</div>
                <div class="cred-value" style="color:#ce1126;">{{ $defaultPassword }}</div>
            </div>
            <div class="notice">
                This card is government property. If found, please return to the nearest
                DSWD office in Lipa City, Batangas. Unauthorized use is punishable by law.
                Portal: secure4ps.dswd.gov.ph
            </div>
        </div>
    </div>

    <div class="back-footer">
        Issued by: DSWD Lipa City SWDO • Card No: {{ $card->card_number }} • Issued: {{ $card->issued_at?->format('m/d/Y') ?? now()->format('m/d/Y') }}
    </div>
</div>

</body>
</html>
