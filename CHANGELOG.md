# MapBBCode Share Changelog

Versions are numbered `N.M-S`, where `N.M` is the MapBBCode library version, and `S` is the Share build, independent of the library version. There are no "stable" versions and releases: the latest version is always the one to install.

### 1.2-13, 2014-03-06

* Fix processing of delimiters inside quotes for CSV.

### 1.2-12, 2013-12-28

* Move default position, zoom and layers to `config.php` (by [@aaronpk](https://github.com/aaronpk)). [#23](https://github.com/MapBBCode/share.mapbbcode.org/pull/23)
* Support for nginx with php-fpm (by [@aaronpk](https://github.com/aaronpk)). [#20](https://github.com/MapBBCode/share.mapbbcode.org/pull/20)
* Swap MyOpenID for Vkontakte OpenID provider. [#21](https://github.com/MapBBCode/share.mapbbcode.org/issues/21)
* Fix paths to `auth.php` for cases when Share is not in a root directory.

### 1.2-11, 2013-12-27

* `?direct` parameter does not add attachment headers (useful for HTML export).
* Updated L.PopupIcon in HTML format exporter.
* Host detection in `auth.php` instead of a hardcoded address. [#15](https://github.com/MapBBCode/share.mapbbcode.org/issues/15)
* IFrame format. [#17](https://github.com/MapBBCode/share.mapbbcode.org/issues/17)

### 1.2-10, 2013-12-20

* Updated MapBBCode to 1.2.0-dev.
* Process colors in GPX.

### 1.1-9, 2013-12-12

* Fixed exporting empty maps and single marker maps to HTML. [#10](https://github.com/MapBBCode/share.mapbbcode.org/issues/10)
* Exported HTML now uses Leaflet 0.7.1. [#11](https://github.com/MapBBCode/share.mapbbcode.org/issues/11)
* WPT import uses description field if it's not empty. [#12](https://github.com/MapBBCode/share.mapbbcode.org/issues/12)
* Quotes in WPT and PLT are now screened, and are read properly.
* Export tries to create filename out of map title before resorting to code id (sadly, cyrillic letters are dropped).
* Increased displayed library size to 100 maps.

### 1.1-8, 2013-12-01

* Updated MapBBCode to 1.1.2.
* Extracted help contents to `help.txt`.

### 1.1-7, 2013-11.29

* Updated MapBBCode to 1.1.2-dev.
* Added attribution edit link
* Fixed cache purging.

### 1.1-6, 2013-11-26

* Updated MapBBCode to 1.1.2-dev.
* Added length measurement with a new plugin.

### 1.1-5, 2013-11-23

* Website now works in subfolders and without mod_rewrite. [#4](https://github.com/MapBBCode/share.mapbbcode.org/issues/4)
* Fixed edit id reset after importing a file. [#7](https://github.com/MapBBCode/share.mapbbcode.org/issues/7)
* OziExplorer formats now can have their own encoding (usually iso or cp1251). [#6](https://github.com/MapBBCode/share.mapbbcode.org/issues/6)

### 1.1-4, 2013-11-16

* Updated MapBBCode to version 1.1.1.

### 1.1-3, 2013-11-13

* Updated MapBBCode to version 1.1.0.
* Updated MapBBCode parsing functions, semicolons in titles are now processed correctly.
* Forgot to replace one bbcode specification link.

### 1.0-2, 2013-11-12

* Empty CSV now doesn't have a header to not confuse users; single path is saved as csv.
* BBCode tags can be omitted in `?bbcode=` parameter.
* Moved Bing key to a configuration file.
* Ability to work without a database. [#3](https://github.com/MapBBCode/share.mapbbcode.org/issues/3)
* Merge imported traces with existing data. [#2](https://github.com/MapBBCode/share.mapbbcode.org/issues/2)
* Highlight "Save" button on map change. [#1](https://github.com/MapBBCode/share.mapbbcode.org/issues/1)
* Rephrased the message with a link for sharing.
* New OpenMapSurfer tile URL.
* New configuration keys: `BING_KEY`, `IMPORT_SINGLE`.

### 1.0-1, 2013-11-01

* Added Cycle Map layer.
* Rephrased the message with a link for sharing.
* Restricted bots from editing pages.

### 1.0-0, 2013-10-31

Initial release.
