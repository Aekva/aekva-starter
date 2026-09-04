<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Nouvelle réservation - {{ $businessName }}
    </title>
</head>

<body style="
    margin:0;
    padding:0;
    background-color:#f4f4f5;
    font-family:Arial, Helvetica, sans-serif;
    color:#18181b;
">

<table
    role="presentation"
    width="100%"
    cellspacing="0"
    cellpadding="0"
    border="0"
    style="background-color:#f4f4f5; padding:40px 20px;"
>
    <tr>
        <td align="center">

            <table
                role="presentation"
                width="100%"
                cellspacing="0"
                cellpadding="0"
                border="0"
                style="
                    max-width:600px;
                    background-color:#ffffff;
                    border-radius:16px;
                    overflow:hidden;
                    border:1px solid #e4e4e7;
                "
            >

                {{-- HEADER --}}
                <tr>
                    <td style="padding:28px 32px; background-color:#ffffff;">

                        @php
                            $logoAbsolutePath = null;

                            $emailLogoPath = \Illuminate\Support\Facades\Storage::disk('public')
                                ->path('branding/logo-email.png');

                            if (file_exists($emailLogoPath)) {
                                $logoAbsolutePath = $emailLogoPath;
                            } elseif ($logoPath) {
                                $candidate = \Illuminate\Support\Facades\Storage::disk('public')
                                    ->path($logoPath);

                                if (file_exists($candidate)) {
                                    $logoAbsolutePath = $candidate;
                                }
                            }
                        @endphp

                        @if ($logoAbsolutePath)

                            <img
                                src="{{ $message->embed($logoAbsolutePath) }}"
                                alt="{{ $businessName }}"
                                width="180"
                                height="48"
                                style="
                                    display:block;
                                    width:180px;
                                    height:48px;
                                    max-width:100%;
                                    border:0;
                                "
                            >

                        @else

                            <div style="
                                font-size:20px;
                                font-weight:700;
                                letter-spacing:-0.3px;
                            ">
                                {{ $businessName }}
                            </div>

                        @endif

                    </td>
                </tr>

                {{-- CONTENT --}}
                <tr>
                    <td style="padding:40px 32px;">

                        <div style="
                            display:inline-block;
                            padding:7px 12px;
                            border-radius:999px;
                            background-color:{{ $primaryColor }};
                            color:{{ $primaryTextColor }};
                            font-size:12px;
                            font-weight:700;
                            margin-bottom:20px;
                        ">
                            NOUVELLE RÉSERVATION
                        </div>

                        <h1 style="
                            margin:0 0 12px 0;
                            font-size:28px;
                            line-height:1.2;
                            color:#18181b;
                        ">
                            Une nouvelle réservation vient d’être enregistrée
                        </h1>

                        <p style="
                            margin:0 0 30px 0;
                            color:#71717a;
                            font-size:16px;
                            line-height:1.6;
                        ">
                            Voici les informations du rendez-vous.
                        </p>

                        <table
                            role="presentation"
                            width="100%"
                            cellspacing="0"
                            cellpadding="0"
                            border="0"
                            style="
                                background-color:#fafafa;
                                border:1px solid #e4e4e7;
                                border-radius:12px;
                            "
                        >
                            <tr>
                                <td style="padding:24px;">

                                    <div style="margin-bottom:18px;">
                                        <div style="
                                            font-size:12px;
                                            color:#a1a1aa;
                                            text-transform:uppercase;
                                            margin-bottom:5px;
                                        ">
                                            Client
                                        </div>

                                        <div style="
                                            font-size:16px;
                                            font-weight:700;
                                        ">
                                            {{ $reservation->customer_name }}
                                        </div>
                                    </div>

                                    <div style="margin-bottom:18px;">
                                        <div style="
                                            font-size:12px;
                                            color:#a1a1aa;
                                            text-transform:uppercase;
                                            margin-bottom:5px;
                                        ">
                                            Prestation
                                        </div>

                                        <div style="
                                            font-size:16px;
                                            font-weight:700;
                                        ">
                                            {{ $reservation->service?->name ?? '—' }}
                                        </div>
                                    </div>

                                    @if ($reservation->resource)
                                        <div style="margin-bottom:18px;">
                                            <div style="
                                                font-size:12px;
                                                color:#a1a1aa;
                                                text-transform:uppercase;
                                                margin-bottom:5px;
                                            ">
                                                Ressource
                                            </div>

                                            <div style="
                                                font-size:16px;
                                                font-weight:700;
                                            ">
                                                {{ $reservation->resource->name }}
                                            </div>
                                        </div>
                                    @endif

                                    <div style="margin-bottom:18px;">
                                        <div style="
                                            font-size:12px;
                                            color:#a1a1aa;
                                            text-transform:uppercase;
                                            margin-bottom:5px;
                                        ">
                                            Date
                                        </div>

                                        <div style="
                                            font-size:16px;
                                            font-weight:700;
                                        ">
                                            {{ \Carbon\Carbon::parse($reservation->starts_at)->format('d/m/Y') }}
                                        </div>
                                    </div>

                                    <div style="margin-bottom:18px;">
                                        <div style="
                                            font-size:12px;
                                            color:#a1a1aa;
                                            text-transform:uppercase;
                                            margin-bottom:5px;
                                        ">
                                            Heure
                                        </div>

                                        <div style="
                                            font-size:16px;
                                            font-weight:700;
                                        ">
                                            {{ \Carbon\Carbon::parse($reservation->starts_at)->format('H:i') }}
                                        </div>
                                    </div>

                                    @if ($reservation->customer_phone)
                                        <div style="margin-bottom:18px;">
                                            <div style="
                                                font-size:12px;
                                                color:#a1a1aa;
                                                text-transform:uppercase;
                                                margin-bottom:5px;
                                            ">
                                                Téléphone
                                            </div>

                                            <div style="
                                                font-size:16px;
                                                font-weight:700;
                                            ">
                                                {{ $reservation->customer_phone }}
                                            </div>
                                        </div>
                                    @endif

                                    <div style="margin-bottom:18px;">
                                        <div style="
                                            font-size:12px;
                                            color:#a1a1aa;
                                            text-transform:uppercase;
                                            margin-bottom:5px;
                                        ">
                                            Email
                                        </div>

                                        <div style="
                                            font-size:16px;
                                            font-weight:700;
                                        ">
                                            {{ $reservation->customer_email }}
                                        </div>
                                    </div>

                                    @if ($reservation->notes)
                                        <div style="margin-bottom:18px;">
                                            <div style="
                                                font-size:12px;
                                                color:#a1a1aa;
                                                text-transform:uppercase;
                                                margin-bottom:5px;
                                            ">
                                                Note
                                            </div>

                                            <div style="
                                                font-size:15px;
                                                line-height:1.5;
                                            ">
                                                {{ $reservation->notes }}
                                            </div>
                                        </div>
                                    @endif

                                    @if ($reservation->total_price !== null)
                                        <div>
                                            <div style="
                                                font-size:12px;
                                                color:#a1a1aa;
                                                text-transform:uppercase;
                                                margin-bottom:5px;
                                            ">
                                                Prix
                                            </div>

                                            <div style="
                                                font-size:16px;
                                                font-weight:700;
                                            ">
                                                {{ number_format((float) $reservation->total_price, 2, ',', ' ') }} €
                                            </div>
                                        </div>
                                    @endif

                                </td>
                            </tr>
                        </table>

                        <p style="
                            margin:30px 0 0 0;
                            color:#71717a;
                            font-size:14px;
                            line-height:1.5;
                        ">
                            Vous pouvez répondre directement à cet email pour contacter le client.
                        </p>

                    </td>
                </tr>

                {{-- FOOTER --}}
                <tr>
                    <td style="
                        padding:22px 32px;
                        background-color:#fafafa;
                        border-top:1px solid #e4e4e7;
                        text-align:center;
                        color:#a1a1aa;
                        font-size:12px;
                    ">
                        © {{ date('Y') }} {{ $businessName }}
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>