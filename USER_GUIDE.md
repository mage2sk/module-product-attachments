# Panth Product Attachments -- User Guide

This guide walks store administrators through the complete setup and
daily use of Panth_ProductAttachments.

---

## Table of contents

1. [Installation](#1-installation)
2. [General configuration](#2-general-configuration)
3. [Managing attachment types](#3-managing-attachment-types)
4. [Creating and editing attachments](#4-creating-and-editing-attachments)
5. [Assigning attachments to products](#5-assigning-attachments-to-products)
6. [Assigning attachments to categories](#6-assigning-attachments-to-categories)
7. [Assigning attachments to CMS pages](#7-assigning-attachments-to-cms-pages)
8. [Multi-file management](#8-multi-file-management)
9. [File versioning](#9-file-versioning)
10. [Download analytics](#10-download-analytics)
11. [Unused file cleanup](#11-unused-file-cleanup)
12. [Widget usage](#12-widget-usage)
13. [Customer-group restrictions](#13-customer-group-restrictions)
14. [Sample data](#14-sample-data)
15. [Troubleshooting](#15-troubleshooting)

---

## 1. Installation

See the [README](README.md) for composer and manual installation
instructions.

---

## 2. General configuration

Go to **Stores > Configuration > Panth Extensions > Product
Attachments**.

| Setting                  | Description                                     |
|--------------------------|-------------------------------------------------|
| Enable Module            | Master on/off switch                             |
| Allowed Extensions       | Comma-separated list of file extensions          |
| Max Upload Size (MB)     | Maximum file size for uploads                    |
| Allow Guest Downloads    | Whether guests can download without logging in   |
| Default View Mode        | `list` or `table` layout on the frontend         |
| Custom CSS               | Inject custom styles into attachment blocks      |
| Download Notification    | Send email when a file is downloaded             |
| Log Retention Days       | Cron removes download logs older than N days     |

---

## 3. Managing attachment types

Navigate to **Panth Infotech > Product Attachments > Attachment Types**.

Attachment types let you categorise files (e.g. User Manual, Spec
Sheet, Warranty Card, Brochure). Each type has:

- **Name** -- displayed on the frontend
- **Icon** -- Bootstrap Icons class for the icon
- **Sort Order** -- controls display order
- **Is Active** -- enable or disable the type

Create, edit, or delete types from the admin grid. Types are used when
creating attachments.

---

## 4. Creating and editing attachments

Navigate to **Panth Infotech > Product Attachments > Manage
Attachments**.

Click **Add New Attachment** to create a new record.

### Basic fields

| Field              | Description                                      |
|--------------------|--------------------------------------------------|
| Title              | Public-facing name for the attachment             |
| Description        | Optional description shown on the frontend        |
| Attachment Type    | Select from the types you created                 |
| Is Active          | Enable or disable the attachment                  |
| Sort Order         | Controls display order                            |
| Store Views        | Multi-store scoping                               |
| Customer Groups    | Restrict to specific customer groups              |
| Expiration Date    | Optionally set an expiry date                     |

### Link attachments

Toggle **Is Link** to create a link-based attachment (URL instead of
a file). Provide the URL and choose the link target (`_blank` or
`_self`).

---

## 5. Assigning attachments to products

On the attachment edit page, open the **Products** tab. Use the product
grid to select which products should display this attachment. Changes
take effect immediately after saving.

You can also assign attachments from the product edit page under the
**Attachments** tab.

---

## 6. Assigning attachments to categories

On the attachment edit page, open the **Categories** tab. Use the
category tree with checkboxes to select which categories should display
this attachment.

You can also assign from the category edit page.

---

## 7. Assigning attachments to CMS pages

On the attachment edit page, open the **CMS Pages** tab. Select pages
from the grid. Attachments will appear on the chosen CMS pages.

---

## 8. Multi-file management

Each attachment can hold multiple files. After creating an attachment,
open the **Files** tab or use the **File Manager** button in the grid.

- Drag and drop or click to upload multiple files at once
- Set a **primary file** (used as the default download)
- Reorder files via sort order
- Delete individual files
- Preview images and PDFs inline

---

## 9. File versioning

When you replace a file on an attachment, the previous file is
automatically saved as a version. View version history from the
**Versions** link in the attachment grid.

---

## 10. Download analytics

Navigate to **Panth Infotech > Product Attachments > Analytics**.

The download log grid shows:

- Attachment name
- File downloaded
- Customer name (or Guest)
- IP address
- Download date/time
- Store view

Download logs are automatically cleaned up based on the retention
period configured in system settings.

---

## 11. Unused file cleanup

Navigate to **Panth Infotech > Product Attachments > Unused Files**.

This utility shows files in the attachment storage directory that are
not linked to any attachment record. Use mass-delete or delete-all to
reclaim disk space.

---

## 12. Widget usage

You can place attachments anywhere via **Content > Widgets**.

1. Create a new widget of type **Product Attachments**.
2. Configure the display settings and layout updates.
3. Save. The widget renders using the default view mode or the mode
   you select.

---

## 13. Customer-group restrictions

When editing an attachment, select one or more **Customer Groups** to
restrict who can download the file. If **Allow Guest Downloads** is
disabled in the configuration, non-logged-in visitors will see a login
prompt instead of the download link.

---

## 14. Sample data

Install sample attachments for quick testing:

```bash
bin/magento panth:attachments:install-sampledata
```

This creates sample attachment types and attachment records with demo
PDF, image, and document files.

---

## 15. Troubleshooting

| Issue                           | Solution                                    |
|---------------------------------|---------------------------------------------|
| Attachments not showing         | Check Is Active, store view, customer group  |
| Upload fails                    | Verify PHP `upload_max_filesize` and `post_max_size` |
| Files not downloading           | Check `var/panth/productattachments/` permissions |
| Hyva template not loading       | Ensure Panth_Core is installed and active     |

---

## Support

| Channel    | Details                              |
|------------|--------------------------------------|
| Email      | kishansavaliyakb@gmail.com           |
| Website    | https://kishansavaliya.com           |
| Company    | Panth Infotech                       |
