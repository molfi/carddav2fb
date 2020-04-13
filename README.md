# CardDAV contacts import for AVM FRITZ!Box
This is a fork of https://github.com/andig/carddav2fb adjusted to personal needs. Maybe its interesting for others as well.

## Features
- Make carddav2fb work with Synology Diskstation package "CardDAV Server" (https://github.com/andig/carddav2fb/issues/167)
- Enhance solution for Docker to handle multiple phonebooks in one container (https://github.com/andig/carddav2fb/pull/141)
- maybe more in the future ...

## Howto
- Create folder structure on Synology, e.g. ...
  - /docker/carddav2fb/data
  - /docker/carddav2fb/srv/src/CardDav/
- ... and place files of this master branch:
  - /docker/carddav2fb/srv/src/CardDav/Backend.php
  - /docker/carddav2fb/srv/docker-entrypoint
  - /docker/carddav2fb/srv/config.example.cron
- Create new container based on "andig/carddav2fb" - do not start it yet
- Configure Volume mappings
  - /srv/src/CardDav/Backend.php --> /docker/carddav2fb/srv/src/CardDav/Backend.php
  - /srv/docker-entrypoint --> /docker/carddav2fb/srv/docker-entrypoint
  - /srv/config.example.cron --> /docker/carddav2fb/srv/config.example.cron
  - /data --> /docker/carddav2fb/data
- Start container. Protocol should show error messages and go into endless loop
  - "Copied config.example.php to /data volume. Please edit and rename to config.php"
  - "For more than one phonebook please copy config.php to e.g. config_1.php, config_2.php etc. and later adjust config.cron"
- Copy config.example.php to config.php and adjust to own needs
- Protocol should show next error message while still looping
  - "No cron config found!"
  - "Copied config.example.cron to /data volume. Please edit and rename to config.cron"
- Copy config.example.cron to config.cron and adjust to own needs
- Protocol should show Success message:
  - "Successful uploaded new FRITZ!Box phonebook"
- To be able to handle more than one phonebook please proceed creating more config files as stated above and adjusting config.cron:
  - Number of PHONEBOOKS
  - RUN_OPTIONs options for carddav2fb runs
  - If needed adjust WAIT time and execution INTERVAL

Here you can find commands and options of carddav2fb:
https://github.com/blacksenator/carddav2fb/wiki/Kommandos-und-Optionen-zum-Programmaufruf

.... there is for sure a better way to achieve this ... but for me it was a good excercise ...


## License
This script is released under Public Domain, some parts under GNU AGPL or MIT license. Make sure you understand which parts are which.

## Authors
Copyright (c) 2012-2019 Andreas Götz, Volker Püschel, Karl Glatz, Christian Putzke, Martin Rost, Jens Maus, Johannes Freiburger
