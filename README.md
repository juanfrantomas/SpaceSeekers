# SpaceSeekers
Transient Watch – Daily news on active neutron stars and black holes on your mobile phone

This is a brief explanation about the projecto, and it's different parts.

At the **scrap folder** you will find source code of the NASA dataset scraper and the 
data feed of the different events for the registered objects detected by MAXI, Swift/BAT and Fermi/GBM
instruments.

At the **JuanFran folder** --- Escribir texto en castellano aqui y lo traduzco.

# Scrap Folder

We use the PHP Simple Html DOM Parser for the web scraping process, and modified
version of rss_feed library. The hole data connector is coded using PHP, but using 
the Wordpress core as database management framework.

The event detection is based on a 100% rising flux change probability.
 
The server cron job will call cron.php several times a day, and the feed wil always
return the last registered events.
 
 
# JuanFran folder
 