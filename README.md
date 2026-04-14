# Panth Product Attachments

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange)]()
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue)]()
[![License: Proprietary](https://img.shields.io/badge/License-Proprietary-red)]()

**Attach files, links, and documents to products, categories, and CMS
pages** with full support for both Luma and Hyva themes.

Panth_ProductAttachments gives store administrators a powerful file-
management system right inside the Magento admin, allowing customers
to download user manuals, spec sheets, warranty documents, and any
other type of file directly from product, category, and CMS pages.

---

## Features

- **Multi-file attachments** -- upload multiple files per attachment
  record with drag-and-drop support.
- **File versioning** -- every time a file is replaced the old version
  is kept so you can roll back or let customers download earlier
  revisions.
- **Product, category, and CMS page assignment** -- attach files to any
  entity via intuitive admin grids and category tree selectors.
- **Attachment types** -- define custom file types (Manual, Spec Sheet,
  Warranty, etc.) with their own icons and labels.
- **Customer-group restrictions** -- limit downloads to specific
  customer groups; optionally require login.
- **Download analytics** -- built-in download log with admin grid so
  you know which files are most popular.
- **Secure file storage** -- files are stored in `var/` (outside the
  web root) and served through a controller with proper access checks.
- **Widget support** -- display attachments anywhere via the Magento
  widget system.
- **Multi-store / multi-language** -- full store-view scoping.
- **Luma + Hyva** -- dedicated templates for both theme stacks.
- **Sample data CLI** -- `bin/magento panth:attachments:install-sampledata`
  for quick demos.
- **Cron cleanup** -- automatic housekeeping of download logs.

---

## Requirements

| Requirement          | Version              |
|----------------------|----------------------|
| Magento Open Source / Adobe Commerce | 2.4.4 -- 2.4.8 |
| PHP                  | 8.1 / 8.2 / 8.3     |
| Panth_Core           | ^1.0                 |

---

## Installation

### Via Composer (recommended)

```bash
composer require mage2kishan/module-product-attachments
bin/magento module:enable Panth_ProductAttachments
bin/magento setup:upgrade
bin/magento cache:flush
```

### Manual installation

1. Download the extension package.
2. Extract to `app/code/Panth/ProductAttachments/`.
3. Run the following commands:

```bash
bin/magento module:enable Panth_ProductAttachments
bin/magento setup:upgrade
bin/magento cache:flush
```

---

## Configuration

Navigate to **Stores > Configuration > Panth Extensions > Product
Attachments** to configure:

- Enable/disable the module
- Allowed file extensions
- Maximum upload file size
- Guest download policy
- Default view mode (list or table)
- Custom CSS
- Download notification email
- Download log retention period

---

## Support

| Channel    | Details                              |
|------------|--------------------------------------|
| Email      | kishansavaliyakb@gmail.com           |
| Website    | https://kishansavaliya.com           |
| Company    | Panth Infotech                       |

---

## License

Proprietary -- see [LICENSE.txt](LICENSE.txt) for full terms.

Copyright (c) 2026 Kishan Savaliya / Panth Infotech. All rights
reserved.
