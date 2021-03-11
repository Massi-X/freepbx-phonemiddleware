# freepbx-phonemiddleware
Simple module to read a carddav server and return inbound CNAM, Outbound CNAM and XML phonebook.<br>
Module for FreePBX systems, tested with 15+ (default distro) and baikal as the backend.

## PURPOSE:
Do you have a carddav phone book and phones compatible only with LDAP? Well that's the solution! I've done lot of researches but never found what i was looking for, so i made this.

## DONATION:
If you like to support me, you can donate. Any help is greatly appreciated. Thank you!<br>
Bitcoin: <b>1Pig6XJxXGBj1v3r6uLrFUSHwyX8GPopRs</b>
<br>
Monero: <b>89qdmpDsMm9MUvpsG69hbRMcGWeG2D26BdATw1iXXZwi8DqSEstJGcWNkenrXtThCAAJTpjkUNtZuQvxK1N5xSyb18eXzPD</b>
<br>
Donate to PayPal: [![paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/donate?hosted_button_id=JG8QUZPEH3KBG)

## TODO:
- [x] ~~Create a user interface, possibly integrated with standard FreePBX GUI~~
- [ ] Better usage section with pictures
- [ ] Encrypt data in some way?

## BUGS:
- [ ] Settings get lost with each update

## USAGE:
1. Upload the zip (find it [here](https://github.com/Massi-X/freepbx-phonemiddleware/releases)) in "Module Admin" on your FreePBX station and install it
2. Go to Applications > Phone MiddleWare and fill everything with your data)
3. Download outbound-CNAM from [here](http://pbxossa.org/files/outcnam/) and install it through Module Admin, then enable all the options and leave the Scheme on 'ALL'

_**Read the XML phonebook:**_
- Open the module interface on the bottom link to open a dialog where you can see the url to input on your phone

_**Enable inbound CNAM:**_
- To enable inbound CNAM create a Caller ID Lookup source and set the parameters (you can find them in the module interface on the bottom link), then match it with your inbound route(s) (Inbound route->your_route->Other->CID Lookup Source):

_**Enable outbound CNAM:**_
 - To enable outbound CNAM follow this steps:
    - Go into CID Superfecta, delete all the existing entries and create a new one with this settings:
      ```
      - Scheme Name: As you want
      - Lookup timeout: 5 (it's usually enough)
      - Superfecta Processor: SINGLE
      ```
  - Save and enter the configuration by clicking on the scheme name, then turn OFF all the schemes but Regular Expressions 1, click the gear icon and set the parameters (you can find them in the module interface on the bottom link

## NOTES:
- !!! WARNING !!! USE IT ONLY INSIDE AN INTERNAL TRUSTED NETWORK, IT DOES NOT HAVE ANY TYPE OF SECURITY AS OF NOW
- The module implements a caching sistem to improve performance and reduce network usage
- It always return a CNAM in CID superfecta (even if it doesn't find a match), so it's not compatible with other schemes (for now)
- If a vcard number field is empty, it won't be displayed at all (not an issue)

## LICENSE:
`SPDX-License-Identifier: The-Prosperity-Public-License-3.0.0`<br>
This work is licensed under The Prosperity Public License 3.0.0.<br>
You have to agree if you use this module.<br>
In short this means: You can't use this lib for commercial use without clear agreements with the author but you can freely use it for personal use.<br>
Licenses for the included modules are available below. Credits go to the original author only.<br>
- [Carddav-PHP](https://github.com/christian-putzke/CardDAV-PHP/)
- [vCard-parser](https://github.com/nuovo/vCard-parser/)
- [libphonenumber for PHP](https://github.com/giggsey/libphonenumber-for-php/)
- [Composer](https://github.com/composer/composer/)
