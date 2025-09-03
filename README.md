# Smart Image Canvas

**Automatically generate beautiful CSS-based featured images when no featured image is set.**

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## 🚀 Features

### Core Functionality
- **Dynamic CSS-based Images**: Generate featured images without creating actual image files
- **Live Preview**: Real-time preview in WordPress Customizer and admin settings
- **Template System**: 6 professional template styles with customizable colors and fonts
- **Responsive Design**: Works seamlessly across all device sizes
- **Aspect Ratio Control**: Support for various aspect ratios (16:9, 4:3, 1:1, etc.)

### Performance & Security
- **Advanced Caching**: HTML and CSS caching with automatic invalidation
- **Enhanced Security**: Comprehensive input validation, nonce protection, and permission checks
- **Database Optimization**: Optimized queries with transient caching
- **CSS Minification**: Automatic CSS compression in production

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Modern web browser with CSS Grid support

## 🛠 Installation

### From GitHub (Development)

1. Clone this repository:
   ```bash
   git clone https://github.com/your-username/smart-image-canvas.git
   ```

2. Upload the `smart-image-canvas` folder to your `/wp-content/plugins/` directory

3. Activate the plugin through the 'Plugins' menu in WordPress

4. Configure the plugin via **Settings → Smart Image Canvas**

### Manual Installation

1. Download the latest release from the [Releases page](https://github.com/your-username/smart-image-canvas/releases)
2. Upload the plugin files to `/wp-content/plugins/smart-image-canvas/`
3. Activate the plugin through the WordPress admin

## 🎨 Usage

1. **Activate the Plugin**: Go to Plugins → Installed Plugins and activate "Smart Image Canvas"

2. **Configure Settings**: Navigate to Settings → Smart Image Canvas to customize:
   - Colors and gradients
   - Typography settings
   - Template styles
   - Post types to target

3. **Live Preview**: Use the WordPress Customizer for real-time preview of your settings

4. **Automatic Generation**: The plugin automatically generates featured images for posts without them

## 🔧 Configuration

The plugin offers extensive customization options:

- **Background Colors**: Solid colors or CSS gradients
- **Typography**: Font family, size, weight, and alignment
- **Templates**: Choose from 6 pre-designed styles
- **Category Colors**: Automatic color assignment based on post categories
- **Custom CSS**: Add your own styling for advanced customization

## 🎯 Supported Themes

The plugin includes specific compatibility enhancements for:

- **Premium Themes**: Divi, Avada, Elementor, X/Pro, BeTheme, Bridge, Salient
- **Page Builders**: Elementor, Divi Builder, Beaver Builder, Visual Composer  
- **Popular Free Themes**: Astra, GeneratePress, OceanWP, Neve, Kadence

## 🏗 File Structure

```
smart-image-canvas/
├── smart-image-canvas.php          # Main plugin file
├── includes/                       # Core functionality
│   ├── class-admin-settings.php    # Admin interface
│   ├── class-image-generator.php   # Image generation logic
│   ├── class-frontend-display.php  # Frontend output
│   ├── class-customizer.php        # WordPress Customizer integration
│   ├── class-cache-manager.php     # Caching system
│   ├── class-template-manager.php  # Template handling
│   ├── class-theme-compatibility.php # Theme compatibility
│   └── class-hook-manager.php      # WordPress hooks management
├── assets/                         # CSS and JavaScript files
│   ├── css/                        # Stylesheets
│   └── js/                         # JavaScript files
├── templates/                      # Template files
└── uninstall.php                   # Clean uninstall
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

### Development Setup

1. Clone the repository
2. Create a feature branch: `git checkout -b feature-name`
3. Make your changes
4. Test thoroughly
5. Submit a pull request

### Coding Standards

- Follow WordPress coding standards
- Use proper PHPDoc comments
- Include security checks (nonces, capability checks, input validation)
- Test with latest WordPress version

## 📝 Changelog

### Version 1.0.0
- Initial release
- Dynamic CSS-based image generation
- WordPress Customizer integration
- Advanced caching system
- Theme compatibility layer
- Security enhancements

## 🐛 Bug Reports

If you find a bug, please create an issue on GitHub with:
- WordPress version
- PHP version
- Theme being used
- Steps to reproduce
- Expected vs actual behavior

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- WordPress community for guidelines and best practices
- All theme developers for compatibility testing
- Contributors and testers

## 📞 Support

- **GitHub Issues**: [Report bugs or request features](https://github.com/your-username/smart-image-canvas/issues)
- **WordPress Forums**: [Plugin support forum](https://wordpress.org/support/plugin/smart-image-canvas/)
- **Documentation**: [Full documentation](https://github.com/your-username/smart-image-canvas/wiki)

---

**Made with ❤️ for the WordPress community**
- ✅ **WordPress Default Themes**: Twenty Twenty-Three, Twenty Twenty-Two, etc.

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → Auto Featured Image to configure
4. Use Appearance → Customize for live preview

## Configuration

### Basic Settings
- **Enable Plugin**: Turn the feature on/off
- **Post Types**: Select which post types should have auto-generated images
- **Aspect Ratio**: Choose from 10 different aspect ratios
- **Template Style**: Select from 6 pre-designed templates

### Styling Options
- **Background Colors**: Set primary and secondary colors
- **Gradients**: Enable gradient backgrounds
- **Typography**: Choose from 11 font families
- **Text Effects**: Add shadows and overlays

### Advanced Features
- **Category Colors**: Assign specific colors to post categories
- **Custom CSS**: Add your own styling
- **Theme Compatibility**: Automatic theme-specific optimizations

## Troubleshooting

If the plugin doesn't work with your theme:

1. Go to **Settings → Auto Featured Image → Debug & Troubleshooting**
2. Check your **Theme Compatibility Status**
3. Run the **Test Generation** tool
4. Review the **Debug Report** for specific issues
5. Use the compatibility fixes or contact support

## Technical Details

### WordPress Hooks Used
- `post_thumbnail_html`
- `get_post_metadata`
- `has_post_thumbnail`
- `wp_get_attachment_image`
- `the_post_thumbnail`
- Additional theme-specific hooks

### File Structure
```
smart-image-canvas/
├── smart-image-canvas.php          # Main plugin file
├── includes/
│   ├── class-image-generator.php       # Core image generation
│   ├── class-admin-settings.php        # Admin interface
│   ├── class-frontend-display.php      # Frontend display
│   ├── class-customizer.php           # WordPress Customizer
│   ├── class-theme-compatibility.php   # Theme compatibility
│   └── class-debug.php                # Debug utilities
├── assets/
│   ├── css/                           # Stylesheets
│   ├── js/                            # JavaScript files
│   └── templates/                     # Template styles
└── uninstall.php                      # Cleanup on uninstall
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Active WordPress theme with post thumbnail support

## License

GPL v2 or later

## Support

For support, feature requests, or bug reports, please use the WordPress plugin directory support forums or create a debug report using the built-in tools.

## Changelog

### Version 1.0.0
- Initial release
- Core featured image generation
- Live preview functionality
- Theme compatibility system
- Debug and troubleshooting tools
- Support for 10 aspect ratios
- 6 template styles
- Category-based color assignment
