# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.1.4] - 2026-10-03

### Fixed
- Attachment blocks given an `entity_type` and `entity_id` (product, category, page or cms_page) no longer fail with a fatal error; the attachment collection gained `addEntityFilter()`, which applies the matching product, category or page filter.
- Replacing the file of an existing attachment now stores the previous file as a version instead of failing with a type error.
- The attachments widget cache key no longer reads an undefined store manager property.
- The attachment edit Files and Category Tree tabs read the attachment from the registry key that the edit page actually sets.
