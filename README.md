# freepbx-phonemiddleware
Simple library to read a carddav server and return inbound CNAM, Outbound CNAM and XML phonebook.<br>
Module for FreePBX sistems, tested with 15+ with the default distro.

## PURPOSE:
Do you have a carddav phone book and phones compatible only with LDAP? Well that's the solution! I've done lot of researches but never found what i was looking for, so i made this.

## TODO:
- [ ] Create a user interface, possibly integrated with standard FreePBX GUI
- [ ] Better usage section with pictures
- [ ] Encrypt data in some way?
- [ ] User/password for access

## BUGS:
None for now

## USAGE:
1. Connect to your FreePBX server via SSH
2. cd into /var/www/html/
3. Copy the folder 'phoneMiddleware' here
4. Open the file config.ini and set your desired values
5. Done!

_**Reading the phonebook:**_
- You can connect from http<span>://IP_OF_YOUR_SERVER:PORT/phoneMiddleware/carddavtoXML.php from your phones to read the the entire phonebook

_**Enabling inbound CNAM:**_
- To enable inbound CNAM create a Caller ID Lookup source and set the parameters like below, then match it with your inbound route(s) (Inbound route->your_route->Other->CID Lookup Source):
  - Source Description: As you want
  - Source type: http(s)
  - host: localhost
  - port: if you have a port, set it up here
  - path: phoneMiddleware/numberToCNAM.php
  - query: number=\[NUMBER]

_**Enabling outbound CNAM:**_
 - To enable outbound CNAM follow this steps:
    - Go into CID Superfecta, delete all the existing entries and create a new one with this settings:
      - Scheme Name: As you want
      - Lookup timeout: 5 (it's usually enough)
      - Superfecta Processor: SINGLE
  - Save and enter the configuration by clicking on the scheme name, then turn OFF all the schemes but Regular Expressions 1, click the gear icon and set:
    - url: http<span>://localhost:PORT/phoneMiddleware/numberToCNAM.php?number=$thenumber
    - reg exp: (.*)
  - Finally download outbound-CNAM from [here](https://github.com/Massi-X/freepbx-Outbound_CNAM/releases/tag/0.0.4_beta1) (all credits to the original author, waiting for them to approve my PR) and install it through Module Admin, then enable all the options and leave the Scheme on 'ALL'

## NOTES:
- The module implements a caching sistem to improve performance and reduce network usage
- It always return a number in CID superfecta, so it's not compatible with other schemes (for now)

## LICENSE:
This work is licensed under Apache 2.0, MIT, GPL 3.0 (or any later version) and The Prosperity Public License 3.0.0.<br>
You have to agree to all of them if you use this work.<br>
In short this means: You can't use this lib for commercial use without clear agreements with the author, you only can use it for personal use.<br>
`SPDX-License-Identifier: Apache-2.0 AND MIT AND GPL-2.0-or-later AND The Prosperity Public License 3.0.0`<br>
Licenses for the included modules are available below.<br>
- [Carddav-PHP](https://github.com/christian-putzke/CardDAV-PHP/blob/master/LICENSE)
- [vCard-parser](https://github.com/nuovo/vCard-parser/blob/master/LICENSE)
- [libphonenumber for PHP](https://github.com/giggsey/libphonenumber-for-php/blob/master/LICENSE)
- [Composer](https://github.com/composer/composer/blob/master/LICENSE)
