# 🔌 Konektor — WordPress Plugin

> **CS Rotator, Lead Management & Multi-Platform Pixel Tracking for WordPress**

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.1%2B-8892BF.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL--3.0-green.svg)](https://www.gnu.org/licenses/gpl-3.0.html)
[![Author](https://img.shields.io/badge/author-Hanif%20Pramono-2563eb.svg)](https://hanifprm.my.id)

---

## ✨ Features

| Feature | Description |
|---------|-------------|
| 🔄 **CS Rotator** | Weighted or round-robin operator assignment |
| 📋 **Lead Manager** | Full lead CRUD with status tracking, blocking, and CSV export |
| 📝 **Form Builder** | 7 visual themes, custom fields, drag-and-drop style editor |
| 🎯 **Meta CAPI v21** | Server-side + conditional browser pixel (skips browser when CAPI token exists) |
| 🎵 **TikTok Events API v1.3** | Server-side + conditional browser pixel |
| 🍿 **SnackVideo / Kwai** | Server-side + browser pixel support |
| 🔍 **Google Ads / GTM / GA4** | Browser-side conversion + remarketing tags |
| 🤖 **Telegram Bot** | Real-time lead notifications with operator detail |
| 📊 **Analytics** | Lead trends, campaign performance, source/referrer breakdown |
| 🧪 **Test Pixel** | Built-in API test panel with payload preview and log history |
| 🔒 **Security** | Domain blocking, duplicate lead detection, optional AES encryption |
| 🌍 **Embed Anywhere** | Pure HTML embed code — works on any site, any builder |

---

## 📸 Screenshots

> *Add screenshots to `plugin-wordpress/assets/screenshot-*.png` and update the links below.*

| Admin Dashboard | Campaign Editor | Test Pixel Panel |
|-----------------|-----------------|------------------|
| ![Dashboard](assets/screenshot-1.png) | ![Editor](assets/screenshot-2.png) | ![Test](assets/screenshot-3.png) |

---

## 🚀 Installation

### Method 1: WordPress Admin (Recommended)

1. Download the latest release ZIP
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose `konektor.zip` and click **Install Now**
4. Click **Activate Plugin**
5. Go to **Konektor → Settings** to configure

### Method 2: FTP / Git

```bash
cd wp-content/plugins/
git clone https://github.com/hanifprm/konektor.git konektor
# Or upload the plugin-wordpress/ folder contents
```

Then activate **Konektor** from the Plugins menu.

---

## ⚙️ Configuration

### 1. General Settings

Navigate to **Konektor → Settings**:

| Setting | Description |
|---------|-------------|
| Telegram Bot Token | For instant lead notifications |
| Allowed Domains | Restrict embed usage to specific domains |
| CS Panel Slug | Customize the customer service panel URL (default: `cs-panel`) |
| Base Slug | Customize campaign URL prefix (default: `konektor`) |
| Encrypt Lead Data | Enable AES encryption for sensitive lead fields |

### 2. Campaign Setup

1. Go to **Konektor → Campaigns → Add New**
2. Choose campaign type: **Form Lead** or **WA Link**
3. Configure form fields, themes, and thank-you page
4. Add **Operators** with weights
5. Configure **Pixel Config** for each platform:
   - **Meta**: Pixel ID + Access Token
   - **TikTok**: Pixel ID + Access Token
   - **SnackVideo**: Pixel ID + Access Token
   - **Google**: Conversion ID / GTM ID / GA4 ID
6. Save and copy the **embed code**

### 3. Pixel Event Mapping

Per campaign, you can customize event names for:

| Event | Meta Default | TikTok Default | Snack Default |
|-------|-------------|----------------|---------------|
| Page Load | `PageView` | `PageView` | `PageView` |
| Form Submit | `Lead` | `SubmitForm` | `Lead` |
| Thanks Page | `Purchase` | `CompletePayment` | `Purchase` |

---

## 🔗 Embed Usage

Copy the embed code from any campaign and paste it into:

- WordPress pages/posts (HTML block)
- Landing page builders (Elementor, Divi, Brizy, etc.)
- External websites (any HTML-capable platform)

> **No shortcodes required.** The embed is pure HTML + JavaScript.

---

## 🧪 Testing Pixels

1. Go to **Konektor → Settings → Test Pixel**
2. Select an active campaign
3. Choose event type: Page Load / Form Submit / Thanks Page
4. Select platforms to test
5. Click **Kirim Test Event**
6. Review the API response and payload in real-time
7. Check the **Log History** table for recent API calls

---

## 📁 Project Structure

```
plugin-wordpress/
├── admin/              # Admin UI, AJAX handlers, settings pages
├── includes/           # Core classes, router, API, models
│   ├── integrations/   # Meta, TikTok, Snack, Google, Telegram
│   └── blocks/         # Gutenberg blocks (if any)
├── public/             # Front-end templates, CSS, JS
├── konektor.php        # Main plugin file
└── README.md           # This file
```

---

## 🙏 Credit

**Dibuat Oleh [Hanif Pramono](https://hanifprm.my.id)**  
🔗 [https://hanifprm.my.id](https://hanifprm.my.id)

---

## 📄 License

GPL-3.0-or-later. See [LICENSE](../LICENSE) for details.
