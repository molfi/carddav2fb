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
- Start container
- Copy config.example.cron to config.cron and config.example.php to config.php
- Adjust both config files to own needs


## License
This script is released under Public Domain, some parts under GNU AGPL or MIT license. Make sure you understand which parts are which.

## Authors
Copyright (c) 2012-2019 Andreas Götz, Volker Püschel, Karl Glatz, Christian Putzke, Martin Rost, Jens Maus, Johannes Freiburger
