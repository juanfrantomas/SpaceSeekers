#Scrap Data Connector

All resources used in the project are Open Source.

As prototype, we are using only one dataset and the event detection may not be correct, scientifically speaking.

The files contained in this folder are classified as:

- Libraries:
**installPLugin.php** creates the tables at the database the first time it's fired.
**rss_feed.php** is a modified library for generating rss feeds from database.
**simple_html_dom.php** is a html parser for scraping the data from the table in the challenge proposed dataset.

- Functional files
**cron.php** it's fired by the server cronjob each hour, and read data from the sources.
**feed.php** generates an xml structure for rss and any other data reader, with the lastest measures observed by the
instruments.

