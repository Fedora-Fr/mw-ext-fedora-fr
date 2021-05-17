# FedoraFr

Legacy and historical extension used by Fedora-Fr documentation.

## Features

### Parser

* path (or chemin) tag: `<path>/var/log/messages</path>`
* key (or touche) tag: `<touche>CTRL + M</touche>`
* app tag: `<app>Firefox</app>`
* packet (or paquet) tag: `<paquet>firefox-1.5.0.7</paquet>`
* menu tag: `<menu>Item</menu>`
* cmd tag: `<cmd>dnf upgrade</cmd>`
* envrac tag: `<envrac nbre="10" categories="a,b" />`

## Installation

* Copy and rename folder in FedoraFr in `extensions` MediaWiki directory.
* Add `wfLoadExtension( 'FedoraFr' );` in `localsettings.php`

## ChangeLog

### Version 1.0

- Port to MW 1.35.

### Version 0.1

- First version
