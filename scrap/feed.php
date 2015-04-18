<?php

//header('Content-type: application/xml');
require_once "rss_feed.php";

$xmlns = 'xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:wfw="http://wellformedweb.org/CommentAPI/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"';

$a_channel = array(
    "title" => "Space Seekers",
    "link" => "http://www.spaceseekers.org",
    "description" => "Transient watch - Daily news on active neutron start and black holes on your mobile phone",
    "language" => "en",
    "image_title" => "spaceseekers.org",
    "image_link" => "http://www.spaceseekers.org",
    "image_url" => "http://www.spaceseekers.org/feed/rss.png",
);
$site_url = 'http://www.spaceseekers.org'; // configure appropriately
$site_name = 'Space Seekers'; // configure appropriately

$rss = new rss_feed($xmlns, $a_channel, $site_url, $site_name);

echo $rss->create_feed();