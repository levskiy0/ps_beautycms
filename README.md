# BeautyCMS Module for PrestaShop

BeautyCMS is a PrestaShop module that extends the CMS Pages functionality by adding custom pretty URLs with multilingual support.

## Features

- **Custom Pretty URLs**: Create custom, SEO-friendly URLs for CMS pages
- **Multilingual Support**: Different URLs for each language
- **Easy Integration**: Works seamlessly with PrestaShop's existing CMS system
- **Clean URLs**: Support for nested paths (e.g., `company/about-us`)

## Requirements

- PrestaShop 8.0.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Installation

1. Copy the `ps_beautycms` folder to your PrestaShop's `modules/` directory
2. Go to **Modules > Module Manager** in your PrestaShop admin panel
3. Search for "Beauty CMS"
4. Click **Install**
5. The module will automatically create the necessary database table

## Configuration

### Enable URL Rewriting

Before using the module, make sure URL rewriting is enabled:

1. Go to **Shop Parameters > Traffic & SEO**
2. Enable **Friendly URL**
3. Save

### Using Custom Pretty URLs

1. Go to **Design > Pages** in your PrestaShop admin
2. Click on any CMS page to edit it
3. You'll see two new fields:
   - **Use pretty URL** (checkbox)
   - **Page URL** (multilingual text field)

4. Check **Use pretty URL** to enable custom routing
   - When checked, the **Page URL** field will be shown
   - The default **Friendly URL** field will be hidden

5. Enter your custom URL for each language:
   - Example: `about-us`
   - Example: `company/about`
   - Example: `services/consulting`

6. Save the page

## License

MIT License