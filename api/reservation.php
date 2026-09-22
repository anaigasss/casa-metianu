<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function respond(int $status, bool $success, string $message): void
{
    http_response_code($status);
    echo json_encode(
        ['success' => $success, 'message' => $message],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function textLength(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function field(string $name, int $maxLength): string
{
    $value = trim((string) ($_POST[$name] ?? ''));
    if (textLength($value) > $maxLength) {
        respond(422, false, 'Unul dintre câmpuri este prea lung.');
    }
    return $value;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, false, 'Metodă nepermisă.');
}

if (field('website', 200) !== '') {
    respond(200, true, 'Cererea a fost înregistrată.');
}

$name = field('name', 120);
$phone = field('phone', 30);
$email = field('email', 254);
$date = field('date', 10);
$time = field('time', 5);
$guests = field('guests', 10);
$message = field('message', 2000);
$consent = field('consent', 10);

if ($name === '' || $phone === '' || $email === '' || $date === '' || $time === '' || $guests === '') {
    respond(422, false, 'Completează toate câmpurile obligatorii.');
}

if (textLength($name) < 2 || textLength($phone) < 6) {
    respond(422, false, 'Numele sau numărul de telefon nu este valid.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) {
    respond(422, false, 'Adresa de email nu este validă.');
}

$timezone = new DateTimeZone('Europe/Bucharest');
$requestedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $timezone);
$dateErrors = DateTimeImmutable::getLastErrors();
$dateIsInvalid = $requestedDate === false
    || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
    || $requestedDate->format('Y-m-d') !== $date;

if ($dateIsInvalid || $requestedDate < new DateTimeImmutable('today', $timezone)) {
    respond(422, false, 'Data rezervării nu poate fi în trecut.');
}

if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
    respond(422, false, 'Ora preferată nu este validă.');
}

if (!in_array($guests, ['1', '2', '3', '4', '5', '6', '7', '8', '9+'], true)) {
    respond(422, false, 'Numărul de persoane nu este valid.');
}

if ($consent !== 'yes') {
    respond(422, false, 'Este necesar acordul pentru prelucrarea cererii.');
}

$recipient1 = getenv('RESTAURANT_RESERVATION_EMAIL_1') ?: '';
$recipient2 = getenv('RESTAURANT_RESERVATION_EMAIL_2') ?: '';
$fromEmail = getenv('RESTAURANT_RESERVATION_FROM_EMAIL') ?: '';

foreach ([$recipient1, $recipient2, $fromEmail] as $configuredEmail) {
    if (!filter_var($configuredEmail, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $configuredEmail)) {
        error_log('Casa Metianu reservation email configuration is missing or invalid.');
        respond(500, false, 'Serviciul de rezervări este momentan indisponibil. Încearcă din nou mai târziu.');
    }
}

$guestLabel = $guests === '1' ? '1 persoană' : ($guests === '9+' ? '9+ persoane' : $guests . ' persoane');
$safeMessage = $message !== '' ? $message : '—';
$subjectText = "Nouă cerere de rezervare – {$name} – {$date} – {$time}";
$subject = '=?UTF-8?B?' . base64_encode($subjectText) . '?=';
$body = implode("\r\n", [
    'CERERE NOUĂ DE REZERVARE',
    '',
    "Nume și prenume: {$name}",
    "Telefon: {$phone}",
    "Email: {$email}",
    "Data: {$date}",
    "Ora preferată: {$time}",
    "Număr persoane: {$guestLabel}",
    "Mesaj / preferințe: {$safeMessage}",
    '',
    'Rezervarea nu este confirmată. Aceasta este o cerere de rezervare care trebuie verificată și confirmată manual.',
]);

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: base64',
    'From: Casa Mețianu Rezervări <' . $fromEmail . '>',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . PHP_VERSION,
];

$sent = mail(
    $recipient1 . ', ' . $recipient2,
    $subject,
    chunk_split(base64_encode($body)),
    implode("\r\n", $headers)
);

if (!$sent) {
    error_log('Casa Metianu reservation email could not be queued.');
    respond(502, false, 'Cererea nu a putut fi trimisă. Te rugăm să încerci din nou.');
}

respond(200, true, 'Cererea de rezervare a fost trimisă.');
