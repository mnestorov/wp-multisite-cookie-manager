<p align="center"><a href="https://wordpress.org" target="_blank"><img src="https://raw.githubusercontent.com/github/explore/80688e429a7d4ef2fca1e82350fe8e3517d3494d/topics/wordpress/wordpress.png" width="100" alt="WordPress Logo"></a></p>

# WordPress - Custom Multisite Cookie Manager

[![Licence](https://img.shields.io/github/license/Ileriayo/markdown-badges?style=for-the-badge)](./LICENSE)

## Overview

**_Manage cookies across a WordPress multisite network with the Custom Multisite Cookie Manager plugin._**

This plugin allows network administrators to manage cookie expiration settings, scan, and identify all cookies being set across the multisite network. It provides a network admin settings page where you can specify cookie expiration times, view cookie usage reports, and see a list of all unique cookies being set across the network. A unique cookie will be set for each site in the network based on the specified settings.

## Installation

1. Download the plugin files to your computer.
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to the `/wp-content/plugins/` directory of your WordPress multisite installation.
3. Navigate to the **Network Admin -> Plugins** page within your WordPress multisite network.
4. Locate **Custom Multisite Cookie Manager** in the list of available plugins and click **Network Activate**.

## Usage

1. After activating the plugin, navigate to the **Network Admin -> Settings** page.
2. Click on **Cookie Settings** in the menu.
3. On the **Cookie Settings** page, you'll find a form to manage cookie expirations:
   - Under **Cookie Expirations**, enter the expiration time (in seconds) for cookies on each site. You can specify different expiration times for different sites.
4. Click **Save Settings** to save your changes.
5. The plugin will automatically set cookies with the specified expiration times for each site in your network.
6. To view cookie usage reports or see a list of all unique cookies being set across the network, click on **Cookie Usage Reports** in the submenu under **Cookie Settings**.

## Frequently Asked Questions

### How do I set custom cookie expiration times?

Navigate to the **Network Admin -> Settings -> Cookie Settings** page and enter the desired expiration times in the form provided. Click **Save Settings** to save your changes.

### How are cookies named?

Each cookie is named `custom_cookie_[BLOG_ID]`, where `[BLOG_ID]` is the ID of the site within the network.

### How can I view all cookies being set across the network?

Navigate to the **Network Admin -> Settings -> Cookie Settings -> Cookie Usage Reports** to view a list of all unique cookies being set across the network along with the number of sites on which each cookie has been found.

## Changelog

### 1.0
- Initial release.

### 1.1
- Add an option for encrypting cookie values for added security.

### 1.2
- Implement the cookie import/export feature.

### 1.3
- Log cookie usage, and provide reporting tools for administrators.

### 1.4
- Reduce the code duplication and make the code more maintainable.

### 1.5
- Clean up and remove any database tables on uninstall that were created by the plugin.

### 1.6
- Added 'mn_' prefix for all custom function names.

### 1.7
- Implement the feature to automatically scan and identify cookies being set on the websites within the multisite network.

### 1.7.1
- Resolve the PHP Fatal error, bug fixes.

### 1.8
- Added error handling and logging features.
   - **WP_DEBUG** configurations are mentioned at the top as a reminder to enable them during development.
   - A `mn_log_error` function has been introduced for standardized error logging throughout the plugin.
   - Error handling has been added to the `mn_get_cookie_expiration`, `mn_create_cookie_usage_table`, and `mn_log_cookie_usage` functions to log database errors and other issues to the error log.
   - In the `mn_log_cookie_usage` function, a check has been added on the result of the `$wpdb->insert()` method call to log any errors that occur during the database insert operation.

---

## License

This project is released under the MIT License.
