# Changelog

All notable changes to Smart Image Canvas will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2] - 2025-09-03

### Fixed
- Fixed GitHub token field not saving properly when entered
- Resolved duplicate form field names that prevented token persistence
- Improved JavaScript logic for token field management
- Fixed edit/cancel token functionality

## [1.0.1] - 2025-09-03

### Added
- Enhanced GitHub token field with visual status indicators
- "Clear Token" button with confirmation dialog for token management
- "Edit Token" button to modify existing tokens securely
- Visual feedback showing whether token is configured (green checkmark) or missing (warning icon)
- Improved user experience with placeholder dots for set tokens

### Changed
- Updated auto-updater to use GitHub fine-grained personal access tokens (Bearer authentication)
- Improved GitHub API compatibility with X-GitHub-Api-Version header
- Enhanced security with modern GitHub token standards

### Security
- Token field now shows placeholder dots instead of actual token value
- Implemented proper token masking for better security
- Added confirmation dialog before token deletion

## [1.0.0] - 2025-09-03

### Added
- Initial release of Smart Image Canvas
- Automatic featured image generation for posts without featured images
- CSS-based image rendering with customizable styles
- Live preview functionality in WordPress admin
- Support for multiple aspect ratios (16:9, 4:3, 1:1, 3:2)
- Gradient background support
- Category-based color schemes
- Template style options (modern, classic, minimal, bold)
- Custom CSS override capability
- WordPress multisite compatibility
- Comprehensive caching system for performance
- Auto-updater system for private GitHub repository
- Fine-grained GitHub token support
- Complete admin interface with advanced settings
- Theme compatibility and responsive design
- Security features and input sanitization

### Technical Features
- Object-oriented PHP architecture with singleton patterns
- WordPress hooks and filters integration
- Proper internationalization (i18n) support
- Database optimization with transient caching
- Error handling and logging
- Code standards compliance (WordPress Coding Standards)
