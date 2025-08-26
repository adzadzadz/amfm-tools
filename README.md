# AMFM Tools - WordPress Plugin

A comprehensive WordPress plugin for AMFM custom functionalities, rebuilt using the ADZ WordPress Plugin Framework.

## Features

### Core Components (Always Active)
- **ACF Helper**: Manages ACF keyword cookies and enhances ACF functionality for dynamic content delivery
- **Import/Export Tools**: CSV import/export functionality for keywords, categories, and other data management

### Optional Components
- **Text Utilities**: Provides text processing shortcodes like `[limit_words]` for content formatting
- **Performance Optimization**: Gravity Forms optimization and performance enhancements for faster page loading
- **Shortcode System**: DKV shortcode and other dynamic content shortcodes with advanced filtering options
- **Elementor Widgets**: Custom Elementor widgets including Related Posts and other dynamic content widgets

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Advanced Custom Fields (ACF) plugin

## Installation

1. Download or clone the plugin to your WordPress plugins directory
2. Run `composer install` to install dependencies
3. Activate the plugin in your WordPress admin
4. Configure components in the AMFM Tools dashboard

## Framework

This plugin is built using the [ADZ WordPress Plugin Framework](https://github.com/adzadzadz/wp-plugin-framework), which provides:

- Modern PHP architecture with MVC pattern
- Automated dependency management
- Plugin lifecycle management
- Easy testing capabilities
- PSR-4 autoloading

## Plugin Structure

```
amfm-tools-v2/
├── src/
│   └── Controllers/          # Plugin controllers
│       ├── ACFController.php
│       ├── AdminController.php
│       ├── TextController.php
│       ├── OptimizationController.php
│       ├── ShortcodeController.php
│       └── ElementorController.php
├── src/Views/
│   └── admin/               # Admin interface templates
├── assets/                  # CSS, JS, images
├── vendor/                  # Composer dependencies
├── tests/                   # Unit tests
└── amfm-tools-v2.php       # Main plugin file
```

## Usage

### Admin Interface
Access the plugin settings via **AMFM → Tools** in your WordPress admin.

### Shortcodes
- `[limit_words text="field_name" words="20"]` - Limits text from ACF fields

### ACF Integration
The plugin automatically manages ACF keyword cookies for enhanced functionality.

## Development

### Running Tests
```bash
composer test
```

### Framework Commands
```bash
./vendor/bin/adz make:controller ControllerName
./vendor/bin/adz make:model ModelName
```

## Version History

- **v3.0.0**: Complete dashboard redesign with interactive cards and improved user experience
- **v2.2.1**: Migrated to ADZ Framework architecture
- **v2.2.0**: Transform General page into comprehensive Dashboard with component management
- **v2.1.0**: Major code refactoring and enhanced UI
- **v2.0.0**: Major UI overhaul with Import/Export consolidation and enhanced functionality

## Author

Adrian T. Saycon - [adzbyte.com](https://adzbyte.com/)

## License

GPL2