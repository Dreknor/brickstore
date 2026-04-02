<?php
/**
 * VAPID-Key-Generator für Web Push Notifications.
 * Führe dieses Skript einmalig aus und trage die Werte in deine .env ein.
 * Danach kann die Datei gelöscht werden.
 *
 * Verwendung:  php generate-vapid-keys.php
 */

// EC-Schlüsselpaar mit prime256v1 (P-256) direkt über PHP-OpenSSL erzeugen
$key = openssl_pkey_new([
    'curve_name'       => 'prime256v1',
    'private_key_type' => OPENSSL_KEYTYPE_EC,
]);

if ($key === false) {
    fwrite(STDERR, 'Fehler: openssl_pkey_new() fehlgeschlagen. Ist die OpenSSL-Extension aktiv?' . PHP_EOL);
    exit(1);
}

$details = openssl_pkey_get_details($key);

if ($details === false || !isset($details['ec'])) {
    fwrite(STDERR, 'Fehler: EC-Key-Details konnten nicht gelesen werden.' . PHP_EOL);
    exit(1);
}

$x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
$y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
$d = str_pad($details['ec']['d'], 32, "\x00", STR_PAD_LEFT);

$publicKeyUncompressed = "\x04" . $x . $y;

$base64url = static fn(string $data): string =>
    rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

echo 'VAPID_PUBLIC_KEY='  . $base64url($publicKeyUncompressed) . PHP_EOL;
echo 'VAPID_PRIVATE_KEY=' . $base64url($d) . PHP_EOL;
echo 'VAPID_SUBJECT=mailto:admin@example.com' . PHP_EOL;
