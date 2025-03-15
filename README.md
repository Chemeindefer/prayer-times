# Prayer Times WordPress Plugin

![Prayer Times Plugin](assets/plugin-promo.png)

[![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.7.2%2B-blue)](https://wordpress.org/)

Prayer Times is a powerful plugin that allows you to display Islamic prayer times on your WordPress site. It provides up-to-date and accurate prayer times using the Namaz Vakti API.

## ℹ️ Technical Information

- **Plugin Version:** 1.0.0
- **WordPress Version:** 6.7.2
- **PHP Version:** 7.4.33
- **Last Update:** March 15, 2025

## 🚀 Features

- 📱 Responsive design (mobile-friendly)
- 🌍 Multi-language support
- 🔄 Automatic data updates
- 🌐 Support for almost all countries, cities, and districts worldwide
- 💫 Performance-oriented caching system
- 🎯 3 different display options (Widget, Table, Banner)
- 🕒 Countdown feature

## 📋 Requirements

- WordPress 6.7.2 or higher
- PHP 7.4 or higher

## 💻 Installation

1. Upload the plugin files to `/wp-content/plugins/prayer-times` directory
2. Activate the plugin through the WordPress admin panel
3. Configure the necessary settings from Settings > Prayer Times menu

## 🔧 Configuration

1. Go to "Prayer Times" menu in WordPress admin panel
2. In the "Settings" tab:
   - Select country
   - Select city
   - Select district
3. Save changes


#### Widget View
```
[prayer_widget]
```

#### Table View
```
[prayer_times heading="true" days="30"]
```
Parameters:
- `heading`: Show heading (true/false)
- `days`: Number of days to display (1-30)

#### Banner View
```
[prayer_banner heading="true"]
```
Parameters:
- `heading`: Show heading (true/false)

## 🔄 Caching

The plugin uses a caching system to improve performance. For cache management:

1. Go to "Prayer Times > Info" in admin panel
2. Clear cache from the "Cache Management" section
3. Cache automatically refreshes every 24 hours

## 🌐 Data Source

This plugin retrieves prayer times from the following open-source API:

- **API URL:** [vakit.vercel.app](https://vakit.vercel.app)
- **API Developer:** Yusuf Sait Canbaz
- **Source Code:** [GitHub Repository](https://github.com/canbax/namaz-vakti-api)

## 🤝 Contributing

1. Fork this repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Create a Pull Request

## 💝 Support Us

*Your support ensures the continuity of open-source projects.*

### Batuhan Delice (Plugin Developer)
- **Name:** Batuhan Delice
- **IBAN:** TR20 0011 1000 0000 0098 0222 37

### Yusuf Sait Canbaz (API Developer)
- **Name:** Yusuf Sait Canbaz
- **IBAN:** TR49 0020 6001 3401 9074 7100 01

*Thanks*

## 📝 License

This project is licensed under the GPL-3.0 License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Developer

**Batuhan Delice**
- Website: [batuhandelice.com](https://batuhandelice.com)
- GitHub: [@batuhandelice](https://github.com/chemeindefer)
- Email: developer@batuhandelice.com

## 🌟 Special Thanks

Special thanks to Yusuf Sait Canbaz for his valuable contributions and providing the open-source API. Also, thanks to all users who tested this plugin and provided feedback.

## ⚠️ Disclaimer

This plugin retrieves prayer times from another open-source project. The accuracy of the times is not guaranteed. Please verify with official sources before use.
