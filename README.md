# Magento 2 Product Attachments

Product Attachments lets store administrators upload files (PDF, Office documents, images, archives, text) or register external links and attach them to products, categories and CMS pages. Customers see the attachments on the product page, the category page or the CMS page and can download or preview them. The module also ships a widget, attachment types, customer group restrictions, download logging with an admin analytics grid, optional email notification on download and an unused file scanner.

It is aimed at merchants who publish manuals, datasheets, brochures, certificates or similar documents next to their catalogue. Templates are provided for both Hyva and Luma; the Hyva templates are selected automatically when a Hyva theme is active.

Product page: [Magento 2 Product Attachments](https://kishansavaliya.com/magento-2-product-attachments.html)

## Features

- Attach one or more files, or a single external link, to an attachment record, and assign that record to any number of products, catalog categories and CMS pages.
- Files are uploaded from the admin and stored below `var/panth/productattachments/`, outside the web root; downloads are streamed through a frontend controller.
- Allowed upload extensions: pdf, doc, docx, xls, xlsx, ppt, pptx, txt, zip, rar, jpg, jpeg, png, gif. Uploads whose detected content type is HTML, SVG, XML, JavaScript or PHP are rejected. An optional "Maximum Upload Size (MB)" setting rejects larger files on the server; PHP `upload_max_filesize` and `post_max_size` always apply.
- Attachment types ("User Manual", "Datasheet", "Brochure", "Certificate" and 17 more are created on install) with an icon class, store view assignment and sort order.
- Per-attachment "Customer Groups" selection; an empty selection means all groups. A global "Allow Guest Downloads" switch controls whether visitors who are not logged in may download.
- Per-attachment "Access Level": "Public", "Logged-in Customers" or "Purchasers Only". Purchasers Only is checked on the server against the logged-in customer's processing or complete orders in the current store view that contain one of the products the attachment is assigned to.
- Store view assignment per attachment and per attachment type.
- "Expires At" date per attachment.
- Frontend display as a list or a table, with optional file size, description and inline preview of PDF and image files.
- Product page tab "Attachments & Downloads" on Luma; on Hyva the block is placed in `product.info.main`.
- Attachment blocks on category pages and CMS pages, plus a "Product Attachments Widget" for CMS content and layout containers.
- Download logging (attachment, customer ID, IP address, user agent, time) shown in a "Download Analytics" admin grid, with a daily cron job that deletes logs older than the configured retention period.
- Optional email to a configured address on every download.
- "Unused Files" admin grid that scans the upload directory for files that are not referenced by any attachment and lets you delete them (selected rows through the Actions menu, or all of them with the "Delete All Unused Files" button in the page actions bar after a confirmation). Temporary uploads in `var/panth/productattachments/tmp/` are only removed by "Delete All" once they are older than one day.
- Custom CSS field in configuration, output after the module stylesheet.
- Console command that installs sample files, a sample product and a sample CMS page.

## Compatibility

| Platform | Versions |
|---|---|
| Magento Open Source | 2.4.4 to 2.4.8 |
| Adobe Commerce | 2.4.4 to 2.4.8 |
| PHP | 8.1, 8.2, 8.3, 8.4 (`~8.1.0||~8.2.0||~8.3.0||~8.4.0` in `composer.json`) |
| Themes | Hyva, Luma |

Composer constraints on Magento packages: `magento/framework` ^103.0, `magento/module-catalog` ^104.0, `magento/module-customer` ^103.0, `magento/module-sales` ^103.0, `magento/module-cms` ^104.0, `magento/module-store` ^101.1, `magento/module-widget` ^101.2, `magento/module-backend` ^102.0, `magento/module-media-storage` ^100.4, `magento/module-ui` ^101.2.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.4 to 2.4.8.
- PHP 8.1, 8.2, 8.3 or 8.4.
- `mage2kishan/module-core` ^1.0 (module `Panth_Core`). It is a Composer dependency and is installed automatically. It provides the admin menu group, the theme detection used to switch to the Hyva templates and the theme configuration hook registered in `etc/frontend/di.xml`.
- Bootstrap Icons 1.11.3 (MIT licence) are shipped in `view/frontend/web/css/bootstrap-icons/` and loaded from the store's static files; no third-party host is contacted.

## Installation

```bash
composer require mage2kishan/module-product-attachments
bin/magento module:enable Panth_Core Panth_ProductAttachments
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

`setup:di:compile` is only needed in production mode. `setup:static-content:deploy` is needed because the module ships CSS and JavaScript under `view/frontend/web` and `view/adminhtml/web`.

Check that the module is enabled:

```bash
bin/magento module:status Panth_ProductAttachments
```

`setup:upgrade` creates the database tables and runs a data patch that inserts the default attachment types.

## Configuration

Admin path: Stores > Configuration > Panth Extensions > Product Attachments. The same page is linked from the module's admin menu as "Configuration". Every field can be set at default, website and store view scope.

### General Settings

| Setting | Default | What it does |
|---|---|---|
| Enable Module | Yes | Master switch. When disabled, nothing is rendered on the frontend. |
| Show on Product Pages | Yes | Renders the attachments block on product pages. |
| Show on Category Pages | Yes | Renders the attachments block on category pages. |
| Show on CMS Pages | Yes | Renders the attachments block on CMS pages. |

### Display Settings

| Setting | Default | What it does |
|---|---|---|
| Show File Size | Yes | Shows the file size next to each file. |
| Show Description | Yes | Shows the attachment description under the title. Basic formatting tags (p, br, strong, em, b, i, u, ul, ol, li, span) are kept, all other markup is escaped. |
| Enable File Preview | Yes | Shows a preview button for PDF and image files. When set to No, the button is hidden and the preview URLs return 404. |
| Default View Mode | List View | "List View" or "Table View" for the frontend blocks. |

### Access Control Settings

| Setting | Default | What it does |
|---|---|---|
| Allow Guest Downloads | Yes | When set to No, visitors who are not logged in get a login prompt instead of the download link, and the download controller redirects them to the login page. |

### Upload Settings

| Setting | Default | What it does |
|---|---|---|
| Maximum Upload Size (MB) | 0 | Largest file an admin upload may have. Larger files are rejected by the server with an error message. 0 means no module limit; PHP `upload_max_filesize` and `post_max_size` still apply. Default scope only. |

### Download Analytics

| Setting | Default | What it does |
|---|---|---|
| Enable Download Tracking | Yes | Writes a row to the download log for every download. |
| Log Retention Days | 90 | Age in days after which the cron job deletes download log rows. 0 keeps logs forever. |

### Email Notifications

| Setting | Default | What it does |
|---|---|---|
| Notify on Download | No | Sends the "Product Attachment Download Notification" email for every download. |
| Notification Email | (empty) | Recipient of the notification. Shown only when "Notify on Download" is Yes. |

### Custom CSS

| Setting | Default | What it does |
|---|---|---|
| Enable Custom CSS | No | Outputs the custom CSS block after the module stylesheet. |
| Custom CSS Code | (empty) | CSS to output. Shown only when "Enable Custom CSS" is Yes. Prefix selectors with `.panth-product-attachment-module` to scope them. |

Configuration paths:

- `panth_productattachments/general/enabled`, `show_on_product`, `show_on_category`, `show_on_cms`
- `panth_productattachments/display/show_file_size`, `show_description`, `enable_preview`, `default_view_mode`
- `panth_productattachments/display/show_download_count` (default 1 in `etc/config.xml`, not shown in the admin form)
- `panth_productattachments/access/guest_download`
- `panth_productattachments/upload/max_file_size`
- `panth_productattachments/analytics/enabled`, `log_retention_days`
- `panth_productattachments/email/notify_on_download`, `notification_email`
- `panth_productattachments/custom_css/enabled`, `custom_styles`

Saving this configuration section invalidates the `block_html` and `full_page` caches.

Admin menu: the "Product Attachments" group under the Panth extensions menu provided by `Panth_Core` contains "Manage Attachments", "Attachment Types", "Unused Files", "Download Analytics" and "Configuration".

## Usage

### Creating attachments

1. Go to "Manage Attachments" and click the add button, or open an existing row.
2. Fill in "Title", "Description", "Attachment Type", "Store View", "Access Level", "Sort Order" and optionally "Expires At" and "Customer Groups".
3. Either upload files in the "File Management" section (several files per attachment; the first uploaded file becomes the primary file, and each file can be set as primary, previewed, downloaded or deleted), or tick "Is Link Attachment" and enter "Link URL" and "Link Target" ("New Tab" or "Same Tab").
4. Assign the attachment in the "Assign to Products", "Assign to Catalog Categories" and "Assign to CMS Pages" sections.

Saving the assignments keeps the existing per-product, per-category and per-page sort order of rows that stay assigned; only removed rows are deleted and new rows are added with sort order 0. The frontend blocks order product, category and CMS page attachments by that relation sort order first, then by the attachment "Sort Order".

Attachments can also be assigned from the other side: the product form gets a "Product Attachments" section, the category form a "Category Attachments" section and the CMS page form a "CMS Page Attachments" section.

The attachment grid supports mass delete and mass status change. Attachment types are managed under "Attachment Types" with "Name", "Code", "Icon Class", "Store View", "Sort Order" and "Enable Type"; the grid supports inline editing.

### Frontend display

- Product page: block `product.attachments`, on Luma rendered as the "Attachments & Downloads" tab in the product details section; on Hyva moved to `product.info.main` by `view/frontend/layout/default_hyva.xml`.
- Category page: block `category.attachments` at the start of the content container.
- CMS page: block `cms.attachments` at the end of the content container.
- Each block only renders when the module is enabled, the matching "Show on ..." setting is Yes and the current store view has at least one active, not expired attachment assigned to the entity that is visible to the customer's group. The blocks add `panth_product_attachment_<id>` cache tags so that saving an attachment refreshes the cached pages that show it.

Downloads use the frontend route `productattachments/download/file` with parameters `id` (attachment ID) and optionally `file_id`. The controller checks that the module is enabled and that the attachment is active, not expired, assigned to the current store view or to all store views, visible to the customer's group and allowed by the guest download rule and the access level. "Logged-in Customers" and "Purchasers Only" require a customer login; "Purchasers Only" also requires a processing or complete order placed by that customer in the current store view that contains one of the products the attachment is assigned to (an attachment assigned only to categories or CMS pages can therefore not be downloaded at this level). Guests that fail the checks are redirected to the login page; logged-in customers get a 404. After the file is found, the controller writes the download log row, increments the attachment download counter, sends the notification email if enabled and streams the file from `var/` as an attachment with `X-Content-Type-Options: nosniff`. Link attachments point directly to the external URL and are not routed through this controller. Previews use `productattachments/preview/file` with the same parameters and the same checks; PDF, JPEG, PNG and GIF files are returned inline, other types as a download. The older `productattachments/download/preview` URL behaves exactly like `productattachments/preview/file`. Link attachments open the external URL directly, so the access level cannot restrict them.

### Widget

"Product Attachments Widget" (`Panth\ProductAttachments\Block\Widget\Attachments`) is available in Content > Widgets and in the `{{widget}}` directive. Parameters: "Widget Title", "Attachment IDs" (comma separated, empty shows all), "Filter by Type", "Limit", "Display Mode" ("Table View" default, "List View") and "Template" ("Default Template" or "Hyva Theme Template"). The block also accepts `product_id`, `category_id` and `page_id` data values to restrict the list to one entity. The widget applies the active, store view, not-expired and customer group filters. A directive without a `template` parameter uses the default widget template.

### Cron

| Job | Schedule | Class |
|---|---|---|
| `panth_productattachments_cleanup_logs` | `0 2 * * *` | `Panth\ProductAttachments\Cron\CleanupDownloadLogs` |

The job deletes rows from the download log older than "Log Retention Days" and does nothing when the value is 0.

### Sample data

```bash
bin/magento panth:attachments:install-sample-data
```

Copies the files from `Setup/SampleData/files` to `var/panth/attachments/samples/`, and creates sample attachments, a sample product and a sample CMS page.

### Templates

Luma templates: `view/frontend/templates/attachment/renderer.phtml`, `attachment/view-modes/list.phtml`, `attachment/view-modes/table.phtml`, `widget/attachments.phtml`. Hyva templates: `attachment/renderer_hyva.phtml`, `attachment/view-modes/list_hyva.phtml`, `attachment/view-modes/table_hyva.phtml`, `widget/attachments_hyva.phtml`. `Panth\ProductAttachments\Observer\SwitchTemplateForHyva` swaps the Luma templates for the Hyva ones on `layout_generate_blocks_after` when `Panth\Core\Helper\Theme::isHyva()` is true. The email template `view/frontend/email/panth_download_notification.html` and `view/frontend/templates/custom-css.phtml` can be overridden in a theme as usual. Luma styles come from `view/frontend/web/css/source/_module.less`; Hyva loads `view/frontend/web/css/attachments.css`, which reads the `--color-primary` and `--color-primary-darker` CSS variables when the theme defines them.

## Developer Notes

- Module name: `Panth_ProductAttachments`
- Composer package: `mage2kishan/module-product-attachments`
- PHP namespace: `Panth\ProductAttachments`
- Admin route front name: `productattachments` (router `admin`); frontend route front name: `productattachments` (router `standard`).
- Service contracts: `Api\AttachmentRepositoryInterface` (`save`, `getById`, `delete`, `deleteById`, `getByProductId`, `getByCategoryId`, `getByPageId`) and `Api\AttachmentTypeRepositoryInterface` (`save`, `getById`, `getByCode`, `delete`, `deleteById`, `getActiveTypes`), with data interfaces `Api\Data\AttachmentInterface`, `AttachmentTypeInterface`, `VersionInterface` and `DownloadLogInterface`. Preferences are declared in `etc/di.xml`.
- Helpers: `Helper\Config` (all configuration getters), `Helper\File` (upload path helpers, icon and MIME lookups), `Helper\Data` (`canDownload`, `hasPurchasedAttachment`, `hasPurchased`, `isExpired`, `formatFileSize`).
- Blocks: `Block\Attachment\Renderer` is the base frontend block; `Block\Product\Attachments`, `Block\Category\Attachments`, `Block\Cms\Attachments` and `Block\Widget\Attachments` extend it. `Block\CustomCss` outputs the custom CSS.
- Collection filters on `Model\ResourceModel\Attachment\Collection`: `addActiveFilter`, `addStoreFilter`, `addProductFilter`, `addCategoryFilter`, `addPageFilter`, `addTypeFilter`, `addNotExpiredFilter`, `addAccessLevelFilter`.
- Plugin: `Plugin\AddStoreToGrid` (before `load` on the UI `SearchResult`) joins the store table into the attachment grid and maps the `attachment_id` filter to the main table.
- The frontend blocks read the customer group and login state from the HTTP context (`Magento\Customer\Model\Context`), which the full page cache varies on, so cached pages show the attachments of the visitor's group.
- Observers: `Observer\ConfigSaveAfter` on `admin_system_config_changed_section_panth_productattachments`; `Observer\SwitchTemplateForHyva` on `layout_generate_blocks_after` (frontend).
- Data patches: `Setup\Patch\Data\AddDefaultAttachmentTypes`, `Setup\Patch\Data\UpdateAttachmentTypesWithBootstrapIcons`.
- Email template ID: `panth_productattachments_download_notification`.
- ACL resources: `Panth_ProductAttachments::productattachments`, `::attachment`, `::attachment_save`, `::attachment_delete`, `::type`, `::type_save`, `::type_delete`, `::unusedfiles`, `::analytics`, `::config`.
- Database tables (`etc/db_schema.xml`): `panth_product_attachment`, `panth_product_attachment_file`, `panth_product_attachment_type`, `panth_product_attachment_version`, `panth_product_attachment_download_log`, `panth_product_attachment_store`, `panth_product_attachment_product`, `panth_product_attachment_category`, `panth_product_attachment_page`, `panth_product_attachment_type_store`. Relation rows are deleted by foreign key when the attachment, product, category, page or store is deleted.

## Uninstallation

```bash
bin/magento module:disable Panth_ProductAttachments
composer remove mage2kishan/module-product-attachments
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

The `panth_product_attachment*` tables, the `panth_productattachments/*` rows in `core_config_data` and the uploaded files under `var/panth/productattachments/` (and `var/panth/attachments/samples/` if sample data was installed) are not removed. Drop the tables and delete the directories manually if they are no longer needed.

## Support

- Product page: [Magento 2 Product Attachments](https://kishansavaliya.com/magento-2-product-attachments.html)
- Contact form: [kishansavaliya.com/contact](https://kishansavaliya.com/contact)
- Email: kishansavaliyakb@gmail.com
- Issues: [GitHub issues](https://github.com/mage2sk/module-product-attachments/issues)

## Documentation

[USER_GUIDE.md](USER_GUIDE.md) walks through configuration, attachment types, creating attachments, assigning them to products, categories and CMS pages, multi-file management, download analytics, unused file cleanup, the widget, customer group restrictions, sample data and troubleshooting.

## License

Commercial software license. See [LICENSE.txt](LICENSE.txt) in this repository.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Links

- Website: [kishansavaliya.com](https://kishansavaliya.com)
- All extensions: [Magento extensions catalogue](https://kishansavaliya.com/magento-extensions.html)
- GitHub: [mage2sk/module-product-attachments](https://github.com/mage2sk/module-product-attachments)
- Packagist: [mage2kishan/module-product-attachments](https://packagist.org/packages/mage2kishan/module-product-attachments)
