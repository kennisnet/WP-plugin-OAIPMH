=== WP OAIPMH ===
Contributors: ramonfincken
Donate link: https://www.kennisnet.nl/
Description: https://github.com/kennisnet/WP-plugin-OAIPMH
Tags: wp bridge, oaipmh, oai, pmh
Requires at least: 4.5
Tested up to: 6.4.3
Stable tag: 2.2.7
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Update URI: false


= Changelog =
2.2.7
* [CLEANUP] we do not need public facing css and js files

2.2.6
* Prevent auto-drafts from entering the oai tables

2.2.4
* Better documentation how to set orphaned records to deleted state

2.2.2
* Updated class from protected to public to ensure PHP 8 compatiblity

2.2.1
* Updated composer to "picturae/oai-pmh": "0.5.20" to ensure 200 vs 400 http code

2.2.0
* Updated for zend => laminas
* Bugfix until/from unset

2.1.5
* Hook into trash/deleted actions

2.1.4
* Find orphaned (deleted) records on import
* More filters

2.1.3
* set record header date as first created (published) instead of modified, set processRecord to protected

2.1.2
* Classification

2.1.1
* Add access rights

2.1.0
* Purpose

2.0.9
* More filters

2.0.8
* Some bugfixes when using custom taxonomies

2.0.7
* More filters

2.0.6
* Backwards compatibility for Emitter class

1.0.3
* Changed readme

1.0.2
* Implemented httphandlerrunner<br>
* Added filters

1.0.1
* Changed some lom
