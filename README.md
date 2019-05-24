# Shift8 CDN
* Contributors: shift8
* Donate link: https://www.shift8web.ca
* Tags: cdn, free cdn, speed, performance, wordpress cache, wordpress, wordpress automation, wordpress deploy, wordpress build, build, deployment, deploy, content delivery network, free, free content delivery, free content delivery network
* Requires at least: 3.0.1
* Tested up to: 5.0.2
* Stable tag: 1.09
* License: GPLv3
* License URI: http://www.gnu.org/licenses/gpl-3.0.html

This is a plugin that integrates a 100% free CDN service operated by Shift8, for your Wordpress site. What this means is that you can simply install this plugin, activate and register with our CDN service and all of your static assets on your website will be served through our global content delivery network.

== Free Content Delivery Network for your Static Content ==

[Shift8](https://www.shift8web.ca) has rolled out a consistently-growing CDN with endpoints all over the world. This plugin will make your site load way faster by using latency-based and geographic-based DNS resolution for requests made to your site to be served by an endpoint closest to the user making the request. This means that a user making a request in Mumbai, India will hit the Mumbai server to download the static content from your website, improving performance remarkably.

Current endpoints in use (more added regularly) :

1. USA - Northern California
2. USA - Northern Virginia
3. Canada - Toronto
4. Europe - London, England
5. Europe - Stockholm, Sweden
6. Asia Pacific - Hong Kong, China
7. Asia Pacific - Tokyo, Japan
8. Asia Pacific - Sydney, Australia
9. Asia Pacific - Mumbai, India
10. Latin America - Sao Paulo, Brazil

You can learn more about how the CDN was setup and how it works by reading our [blog post](https://www.shift8web.ca/2019/05/how-we-created-our-own-free-content-delivery-network-for-wordpress-users/).

## Want to see the plugin in action?

You can view three example sites where this plugin is live :

- Example Site 1 : [Wordpress Hosting](https://www.stackstar.com "Wordpress Hosting")
- Example Site 2 : [Web Design in Toronto](https://www.shift8web.ca "Web Design in Toronto")

## Features

- 100% Free CDN for static assets on your site (CSS, JS, Images, Font files and more)
- Geographic and latency based DNS routing of requests to the nearest endpoint server across the globe
- Super easy set up : just install plugin, activate and register to start using within minutes.

## Installation

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/shif8-cdn` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to the plugin settings page and define your settings
4. Hit the "Register" button to register your site with our CDN network.
5. Within a few minutes the change will propagate to all our systems across the network.

## Frequently Asked Questions 

### I tested it on my site and its not working for me!

Send us an email to cdnhelp@shift8web.com or post in the support forums here and we will help. We are constnatly improving and updating the plugin also!

## Screenshots 

1. Geographic Endpoints for Content Delivery Network
2. Admin area settings
3. Before / After CDN performance improvement, taken from Pingdom

## Changelog 

### 1.00
* Stable version created

### 1.01
* Wordpress 5 compatibility

### 1.02
* Got rewrite working for CDN

### 1.03
* Cleanup

### 1.04 
* Fixed str_replace function

### 1.05
* Now rewriting wp-includes static assets

### 1.06
* Updated readme

### 1.07
* Added DNS prefetch for CDN

### 1.08
* Added on/off switch to assist troubleshooting

### 1.09
* Fixed bug in ruleset where undefined constant was being used
