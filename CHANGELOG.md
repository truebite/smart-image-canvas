# Changelog

All notable changes to Smart Image Canvas will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

# Changelog

All notable changes to this project will be documented in this file.

## [1.1.1] - 2024-01-XX

### Fixed
- Fixed plugin update download mechanism to use GitHub release assets
- Improved update reliability by checking for ZIP assets before falling back to zipball
- Enhanced download URL handling for both release assets and automatic zipballs
- Fixed "Not Found" error during plugin updates

### Enhanced
- Better error handling during update process
- Automatic detection of release assets vs zipball downloads
- Improved authentication handling for different download methods

## [1.1.0] - 2024-01-XX

### Fixed
- Resolved GitHub API 404 errors by creating proper GitHub releases
- Update system now works correctly with GitHub releases API
- Fixed "No releases found" issue that was blocking updates

### Enhanced
- Complete update system functionality now operational
- GitHub releases properly integrated with WordPress update mechanism

## [1.0.9] - 2024-01-XX

### Added
- GitHub token testing functionality with comprehensive diagnostics
- "Test Token" button in Updates tab for troubleshooting
- Multi-step token validation (authentication, repository access, releases access)
- Enhanced logging for GitHub API interactions

### Enhanced
- Improved debugging information for GitHub API requests
- Better error reporting with specific status codes and messages
- Visual test results with emoji indicators for easy understanding

## [1.0.8] - 2024-01-XX

### Fixed
- Improved GitHub API error handling for update checks
- Enhanced error messages for 404, 401, and 403 GitHub API responses
- Added repository access validation before attempting update checks
- Better token format validation and debugging information

### Enhanced
- Added GitHub token status display in Updates tab
- Improved error diagnostics for GitHub API connectivity issues
- Enhanced token format detection (classic vs fine-grained)
- Better user guidance for token permission issues

## [1.0.7] - 2024-01-XX

### Added
- Manual update check functionality in admin settings
- New "Updates" tab in plugin settings for managing updates
- One-click update installation from GitHub releases
- Visual update status indicators and release notes display
- Enhanced user interface for update management

### Enhanced
- Improved update workflow with better user feedback
- Added confirmation dialogs for update installation
- Enhanced error handling for update operations
- Better integration with WordPress update system

## [1.0.6] - 2024-01-XX

### Enhanced
- Improved GitHub token validation with proper format checking
- Added support for both classic (ghp_...) and fine-grained (github_pat_...) GitHub token formats
- Enhanced error messages for invalid GitHub tokens with clear format requirements
- Better logging for GitHub token validation events

### Security
- Strengthened input validation for GitHub tokens to prevent malformed tokens
- Added regex pattern matching for GitHub token format verification

## [1.0.5] - 2024-01-XX

### Fixed
- Fixed fatal error with SIC_Debug_Logger::instance() being called before class was loaded
- Fixed menu slug inconsistency preventing access to settings pages
- Plugin updater now properly checks for debug logger availability before using it
- Corrected navigation links to use proper 'smart-image-canvas' slug instead of old 'wp-auto-featured-image'
- Moved plugin updater initialization to constructor to ensure dependencies are loaded

### Enhanced
- Added safety checks for debug logger throughout plugin updater
- Improved error handling when debug logger is not available

## [1.0.4] - 2024-01-XX

### Enhanced
- Added comprehensive debug logging to plugin updater
- Auto-updater now logs initialization, GitHub API calls, and version checks
- Improved error tracking for GitHub API responses
- Better visibility into update checking process

### Fixed
- Enhanced GitHub token validation logging
- More detailed error reporting for failed API requests

## [1.0.3] - 2024-01-XX

### Added
- Comprehensive debug logging system for troubleshooting
- New Debug Logs tab in plugin settings
- SIC_Debug_Logger class with multiple log levels (ERROR, WARNING, INFO, DEBUG)
- Log export functionality for easier bug reporting
- Auto-log rotation to prevent excessive log storage
- Enhanced GitHub token saving process with detailed logging
- Real-time log viewing with auto-refresh capability

### Enhanced
- Improved settings sanitization with detailed debug logging
- Better error tracking for GitHub token operations
- More robust debugging tools for plugin troubleshooting

## [1.0.2] - 2024-01-XX

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
