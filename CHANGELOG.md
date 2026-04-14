# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.1.0] -- 2026-04-13

### Fixed
- `Controller/Adminhtml/Attachment/TempUpload.php` -- replaced
  `$_FILES` superglobal with `$this->getRequest()->getFiles()` to
  satisfy Magento2.Security.Superglobal.SuperglobalUsageError.
- `Controller/Adminhtml/Attachment/TempUpload.php` -- replaced `md5()`
  with `hash('sha256', ...)` to satisfy
  Magento2.Functions.DiscouragedFunction.
- `Controller/Adminhtml/Attachment/UploadFiles.php` -- replaced
  `$_FILES` superglobal with `$this->getRequest()->getFiles()`.
- `Controller/Adminhtml/Attachment/UploadFiles.php` -- replaced
  `@unlink()` with `try/catch` block.
- `Controller/Adminhtml/Attachment/Save.php` -- replaced `$_FILES`
  superglobal with `$this->getRequest()->getFiles()` in three
  locations.
- `Controller/Adminhtml/Attachment/Save.php` -- replaced `@unlink()`
  with `try/catch` block.
- `view/adminhtml/templates/attachment/upload_notice.phtml` -- wrapped
  three `__()` outputs in `$block->escapeHtml()`.
- `view/adminhtml/templates/attachment/filemanager.phtml` -- cast
  `$files->getSize()` to `(int)`.
- `view/adminhtml/templates/attachment/edit/tab/page.phtml` -- wrapped
  `$block->getGridId()` outputs in `$block->escapeJs()`.
- `view/adminhtml/templates/attachment/edit/tab/product.phtml` --
  wrapped `$block->getGridId()` outputs in `$block->escapeJs()`.
- `view/adminhtml/templates/attachment/category_tree.phtml` -- moved
  JSON data to `data-*` attributes with `escapeHtmlAttr()` and parsed
  in JavaScript.

### Changed
- `composer.json` -- vendor changed from `panth/` to `mage2kishan/`,
  added `mage2kishan/module-core` dependency, switched version
  constraints from `>=` to `^` (semver caret).

## [1.0.0] -- 2025-12-01

### Added
- Initial release of Panth_ProductAttachments.
- Multi-file attachment management for products, categories, CMS pages.
- Attachment type system with icons.
- File versioning.
- Download analytics with admin grid.
- Customer-group access restrictions.
- Luma and Hyva theme templates.
- Widget support.
- Sample data CLI command.
- Cron-based download log cleanup.
- Unused file manager.
