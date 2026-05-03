# Unity package version history to RSS / Atom Converter

PHP scripts that convert Unity Package Manager (UPM) package data from JSON format into syndication feeds. They fetch package metadata from the Unity package registry and transform it into standardized feed formats.

- `upm2rss.php` — outputs an **RSS 2.0** feed.
- `upm2atom.php` — outputs an **Atom 1.0** feed (structured similarly to GitHub's `releases.atom`, with the changelog rendered as HTML).

In both scripts, each entry links to the package's Unity manual page (the `documentationUrl` from the UPM metadata).

## Requirements

- PHP 7.4 or higher

## Installation

No specific installation steps are required. Simply place `upm2rss.php` and/or `upm2atom.php` on your web server where PHP is enabled.

## Usage

Navigate to the script on your web server and append the query parameter `name` with the Unity package name you wish to convert. For example:

- RSS:  `http://yourserver.com/upm2rss.php?name=com.unity.xr.hands`
- Atom: `http://yourserver.com/upm2atom.php?name=com.unity.xr.hands`


## Sample

- RSS:  https://yamaguchimasahiro.com/apps/upm2rss.php?name=com.unity.xr.hands
- Atom: https://yamaguchimasahiro.com/apps/upm2atom.php?name=com.unity.xr.hands
