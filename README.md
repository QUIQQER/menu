![QUIQQER Menu](bin/images/Readme.jpg)

# QUIQQER Menu

`quiqqer/menu` provides menu controls for QUIQQER projects and templates.

## Features

- Mega menu variants for multi-level navigation
- Elastic and slide-out menus for mobile-friendly navigation
- Independent menus that can be managed separately from the site tree
- Navigation controls such as tabs, one-page navigation, sidebar menus, and URL lists
- REST and MCP endpoints for independent menu management

## Installation

Install the package in your QUIQQER environment:

```bash
composer require quiqqer/menu
```

## Usage

The package provides multiple frontend controls and menu renderers for:

- project navigation based on the site tree
- independent menus with custom entries
- mobile navigation with slide-out variants
- backend management for independent menus

Depending on the integration point, configure the provided controls,
menu templates, or independent menu administration in the QUIQQER
backend.

## Technical Notes

- Requires PHP `^8.2`
- Requires `quiqqer/core ^2`
- Uses package-local development tools from `./tools/`

### MCP menu writes

Create, update and item tools validate the complete nested tree before writing.
Localized item fields accept language objects or legacy JSON strings and are
serialized to the existing storage format. `get.data` can be passed back to
`update.data`; it replaces the complete tree. Omit `data` for title-only updates.
The top-level menu title is separate from each item's localized title.

Validation errors include the field path and report `No changes saved`.
Writes and their verification run in one database transaction. Events and cache
invalidation run after commit; failures at that stage return `saved: true` with
warnings instead of reporting the write as failed.

## Support

- Issues: https://dev.quiqqer.com/quiqqer/menu/-/issues
- Source: https://dev.quiqqer.com/quiqqer/menu
- Community: https://community.quiqqer.com
- Email: info@quiqqer.com

## License

This package is dual-licensed under:

- `GPL-3.0-or-later`
- `PCSG QL-1.0`
