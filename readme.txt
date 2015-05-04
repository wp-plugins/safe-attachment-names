=== Safe Attachment Names ===
Contributors: nuagelab
Tags: admin, attachment, accents
Requires at least: 3.0
Tested up to: 4.2.1
Stable tag: trunk
License: GPLv2 or later

Automatically detect and change the name of attachments containing special characters such as accented letters.

== Description ==

Accented file names can cause various problems when hosting or transferring a web site from a server to another.

This plugin automatically detects and changes the name of attachments containing special characters such as accented letters.

For example: if a user uploads a file named "L'École de Éric Rémillard.png", the file name will be transformed and the file will be stored as "L_Ecole_de_Eric_Remillard.png".

= Features =

* Detects accents in newly uploaded files' names, and change them for their unaccented version (ie. é -> e)

== Installation ==

This section describes how to install the plugin and get it working.

= Installing the Plugin =

*(using the Wordpress Admin Console)*

1. From your dashboard, click on "Plugins" in the left sidebar
1. Add a new plugin
1. Search for "Safe Attachment Names"
1. Install "Safe Attachment Names"

*(manually via FTP)*

1. Delete any existing 'safe-attachment-names' folder from the '/wp-content/plugins/' directory
1. Upload the 'auto-domain-change' folder to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Do you plan to localize this plugin in a near future? =

The plugin is currently available in English, French and Spanish. If you want to help with translation in other languages, we'll be happy to hear from you.

== Changelog ==
= 0.0.1 =
* First released version. Tested internally with about 10 sites.

== Upgrade Notice ==