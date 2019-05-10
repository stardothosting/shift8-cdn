# Shift8 Jenkins Integration
* Contributors: shift8
* Donate link: https://www.shift8web.ca
* Tags: jenkins, wordpress, wordpress automation, staging wordpress, staging, push, production push, jenkins push, wordpress deploy, wordpress build, build, deployment, deploy
* Requires at least: 3.0.1
* Tested up to: 5.0.2
* Stable tag: 1.01
* License: GPLv3
* License URI: http://www.gnu.org/licenses/gpl-3.0.html

Plugin that allows you to trigger a Jenkins hook straight from the Wordpress interface. This is intended for end-users to trigger a "push" for jenkins to push a staging site to production (for example). For full instructions and an in-dep
th overview of how the plugin works, you can check out our detailed [blog post about this plugin](https://www.shift8web.ca/blog/wordpress-plugin-to-integrate-jenkins-build-api/).

## Want to see the plugin in action?

You can view three example sites where this plugin is live :

- Example Site 1 : [Wordpress Hosting](https://www.stackstar.com "Wordpress Hosting")
- Example Site 2 : [Web Design in Toronto](https://www.shift8web.ca "Web Design in Toronto")
- Example Site 3 : [Dope Mail](https://dopemail.com "Buy Weed Online")

## Features

- Settings area to allow you to define the Jenkins push URL including the authentication key

## Installation 

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/shif8-jenkins` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to the plugin settings page and define your settings

## Frequently Asked Questions 

### I tested it on myself and its not working for me! 

You should monitor the Jenkins log to see if it is able to hit the site. Also monitor the server logs of your Wordpress site to identify if any problems (i.e. curl) are happening.

## Screenshots 

1. Admin area

## Changelog 

### 1.00
* Stable version created

### 1.01
* Wordpress 5 compatibility
