# ABDO IPTV Canada – Full PHP Website

Modern, responsive IPTV landing page + admin panel ("abdo_admin") built in vanilla PHP for 2025-ready IPTV sales. Users choose an offer, tap "Acheter" and get redirected to WhatsApp with a pre-filled order message. Admin can manage hero content, sliders, offers, themes, providers, highlight video, and contact/support inbox with Cloudinary-backed uploads.

## Tech stack
- PHP 8.1+ (strict types, password hashing, JSON-LD SEO)
- MySQL 5.7+/MariaDB (tables auto-created on first run)
- Vanilla JS + CSS (Space Grotesk font, glassmorphism + animations)
- Cloudinary API for asset storage (images/videos)

## Structure
```
config/            # DB + Cloudinary credentials
includes/          # reusable helpers + database bootstrap
public/            # customer-facing site (set as document root on Hostinger)
abdo_admin/        # admin login + dashboard (visit yourdomain.com/abdo_admin)
```

## Quick start (local or Hostinger)
1. **Create a MySQL database** (e.g. `iptv_abdo`).
2. **Update `config/config.php`** with your DB host/user/pass, WhatsApp number, brand name, and (optional) custom admin credentials.
3. Ensure PHP has the **PDO, cURL, and OpenSSL** extensions enabled (Hostinger PHP 8.2 works out of the box).
4. Upload everything to the server and point the document root to the `public` folder (Hostinger → Advanced → Manage -> Change Document Root). Keep `config`, `includes`, and `abdo_admin` one level above the public folder for extra safety.
5. Visit the domain once; tables + default seed data are created automatically via `initializeDatabase()`.
6. Access the admin panel by adding `/abdo_admin` to the domain (example: `https://example.com/abdo_admin`). Default login comes from `config/config.php` (`admin@iptvabdo.com` / `Canada#2025`).
7. Use the dashboard to upload sliders/offers/providers, switch among the 6 monochrome themes, view visitor analytics, and read/mark contact messages.

## WhatsApp + Cloudinary
- WhatsApp CTA links are generated via `getWhatsappLink()`; set `WHATSAPP_NUMBER` in `config/config.php` (E.164 format, e.g. `+15145550000`).
- Cloudinary credentials from the prompt are preloaded. You can override them via environment variables or by editing `config/config.php`.
- File uploads in the admin area (sliders, providers) are sent directly to Cloudinary so Hostinger storage stays clean.

## Security & SEO
- CSRF protection for forms (public + admin).
- Passwords stored with `password_hash` (bcrypt).
- Admin-only actions gated by session-based guard.
- SEO ready: meta tags, Open Graph/Twitter cards, structured data, keywords.
- Visitor analytics log IP + country (via `ipapi.co`) and display aggregated stats in admin.

## Deploy tips for Hostinger
1. Upload the entire project (via FTP or File Manager).
2. In `public_html`, keep only the contents of `public/`; move `config`, `includes`, and `abdo_admin` outside the public directory if possible.
3. Update PHP version to 8.2 in Hostinger control panel.
4. If you need HTTPS, add an SSL certificate from Hostinger; the base URL auto-detects HTTP vs HTTPS.
5. Set proper file permissions (644 for files, 755 for folders).

## Default admin login
```
Email: admin@iptvabdo.com
Password: Canada#2025
```
Change these in `config/config.php` before going live.

## Database tables
- `users`, `settings`, `sliders`, `offers`, `providers`, `videos`
- `contact_messages`: support inbox
- `visits`: analytics by IP/country

You can export/import data using phpMyAdmin once Hostinger is configured. No manual migrations are necessary—just keep backups of the database and `config/config.php`.
