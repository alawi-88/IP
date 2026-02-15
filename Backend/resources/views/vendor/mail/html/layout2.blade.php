<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<style>
@media only screen and (max-width: 600px) {
.inner-body {
width: 100% !important;
}

.footer {
width: 100% !important;
}
}

@media only screen and (max-width: 500px) {
.button {
width: 100% !important;
}
}
</style>
</head>
<body>
<table
    role="presentation"
    class="main-table"
>
    <!-- Header -->
    <tr>
        <td>
            <table
                role="presentation"
                class="header-table"
            >
                <tr>
                    <td class="logo-cell">
                        <img
                            src="{{ url('mails/logo.svg') }}"
                            alt="Filmathon Logo"
                            class="logo"
                        />
                    </td>
                    <td class="geometric-cell">
                        <img
                            src="{{ url('mails/pattern.svg') }}"
                            alt="geometric design"
                            class="geometric-img"
                        />
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <!-- Content section -->
    <tr>
        <td class="content-cell">
            {{ Illuminate\Mail\Markdown::parse($slot) }}

            {{ $subcopy ?? '' }}
        </td>
    </tr>
    <!-- Footer -->
    <tr>
        <td class="footer-cell">
            <table
                role="presentation"
                class="footer-table"
            >
                <tr>
                    <td class="footer-logo-cell">
                        <img
                            src="{{ url('mails/footer.svg') }}"
                            alt="Film Commission Logo"
                            class="footer-logo"
                        />
                    </td>
                    <td class="footer-text-cell">
                        <p class="footer-text">
                            FilmMOC
                            <br />
                            Film.moc.gov.sa
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
