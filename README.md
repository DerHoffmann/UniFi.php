# UniFi.php

Liest Daten aus UniFi-Controller aus, um daraus JSON-Dateien für Meshviewer zu generieren.

+ Meshviewer: https://doc.meshviewer.org/
+ UniFi-API-client: https://github.com/Art-of-WiFi/UniFi-API-client

---


    unifi.php
    |- data
       |- nodelist.json
       |- meshviewer.json
    |- src
       |- Client.php


Das Script liest die Daten aus einem zu definierenden UniFi-Controller (z.B. UC‑CK) aus und generiert daraus zwei JSON-Dateien. Der URL wird als zusätzliche Quelle im Abschnitt "dataPath" der config.js angegeben:
https://doc.meshviewer.org/config_js.html#datapath-stringarray
