# Unity package version history to RSS Converter

This PHP script converts Unity Package Manager (UPM) package data from JSON format to an RSS 2.0 feed. It is specifically designed to fetch package data from the Unity package registry and transform it into a standardized RSS feed.

## Requirements

- PHP 7.4 or higher
- Access to the internet to fetch package data from Unity's package registry

## Installation

No specific installation steps are required. Simply place `upm2rss.php` on your web server where PHP is enabled.

## Usage

To use the script, navigate to the location of `upm2rss.php` on your web server and append the query parameter `name` with the Unity package name you wish to convert to RSS. For example:

`http://yourserver.com/upm2rss.php?name=com.unity.xr.hands`


## Sample
https://yamaguchimasahiro.com/apps/upm2rss.php?name=com.unity.xr.hands
