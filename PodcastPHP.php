<?php
/**
 * Copyright (c) 2020. Sébastien Rinsoz (rinsoz.org)
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


// config section
define("CHANNEL_JSON", "channel.json");
define("CACHEFILE", "cache.xml");


// misc definitions
define('NC_CONTENT', "http://purl.org/rss/1.0/modules/content/");
define('NC_WFW', "http://wellformedweb.org/CommentAPI/");
define('NC_DC', "http://purl.org/dc/elements/1.1/");
define('NC_ATOM', "http://www.w3.org/2005/Atom");
define('NC_SY', "http://purl.org/rss/1.0/modules/syndication/");
define('NC_SLASH', "http://purl.org/rss/1.0/modules/slash/");
define('NC_ITUNES', "http://www.itunes.com/dtds/podcast-1.0.dtd");
define('NC_RAWVOICE', "http://www.rawvoice.com/rawvoiceRssModule/");
define('NC_GOOGLEPLAY', "http://www.google.com/schemas/play-podcasts/1.0");
define('NC_GEORSS', "http://www.georss.org/georss");
define('NC_GEO', "http://www.w3.org/2003/01/geo/wgs84_pos#");

function relative_url(string $path)
{

    $url = $_SERVER['REQUEST_URI']; //returns the current URL
    $parts = explode('/', $url);
    $dir = '';
    for ($i = 0; $i < count($parts) - 1; $i++) {
        $dir .= $parts[$i] . "/";
    }
    return "https://{$_SERVER['HTTP_HOST']}{$dir}{$path}";
}

function format_date($timestamp = null)
{
    if ($timestamp == null) {
        $timestamp = time();
    }
    return date(DATE_RSS, $timestamp);

}

function report_error(string $message)
{
    $errmessage = date('c') . ":" . rtrim($message) . "\n";
    file_put_contents("error.txt", $errmessage, FILE_APPEND);
    die($message);

}

class PodcastItem
{
    private $mp3;
    private $title;
    private $date;
    private $author;
    private $season;
    private $duration;
    private $summary;
    private $image;
    private $subtitle;
    private $link;

    /**
     * @param mixed $link
     */
    public function setLink($link): void
    {
        $this->link = $link;
    }

    public function __construct()
    {
        $this->season = 1;
    }

    /**
     * @param string $mp3
     */
    public function setMp3($mp3): void
    {
        $this->mp3 = $mp3;
    }

    /**
     * @param string $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }


    /**
     * @param mixed $date
     */
    public function setDate($date): void
    {
        $this->date = $date;
    }

    /**
     * @param string $image
     */
    public function setImage(string $image)
    {
        $this->image = $image;
    }


    /**
     * @param string $author
     */
    public function setAuthor($author): void
    {
        $this->author = $author;
    }

    /**
     * @param int $season
     */
    public function setSeason(int $season): void
    {
        $this->season = $season;
    }

    /**
     * @param mixed $duration
     */
    public function setDuration($duration): void
    {
        $this->duration = $duration;
    }

    /**
     * @param mixed $summary
     */
    public function setSummary($summary): void
    {
        $this->summary = $summary;
    }

    /**
     * @param mixed $subtitle
     */
    public function setSubtitle($subtitle): void
    {
        $this->subtitle = $subtitle;
    }


    /**
     * @param SimpleXMLElement $channel
     */
    public function toXML($channel)
    {
        $relative_url = relative_url($this->mp3);
        $item = $channel->addChild('item');
        $item->addChild('title', $this->title);
        $item->addChild('pubDate', format_date($this->date));
        $item->addChild('guid', "$relative_url");

        $item->addChild('link', $this->link);
        $item->addChild('description', $this->summary);
        $enclosure = $item->addChild('enclosure');
        $enclosure->addAttribute("url", $relative_url);
        $size = filesize($this->mp3);
        $enclosure->addAttribute("length", $size);
        $enclosure->addAttribute("type", "audio/mpeg");

        if ($this->subtitle != '') {
            $item->addChild('itunes:subtitle', $this->subtitle, NC_ITUNES);
        }
        $item->addChild('itunes:summary', $this->summary, NC_ITUNES);
        $item->addChild('itunes:author', $this->author, NC_ITUNES);
        $img = $item->addChild('itunes:image', null, NC_ITUNES);
        $img->addAttribute('href', relative_url($this->image));

        $item->addChild('itunes:season', $this->season, NC_ITUNES);
        $item->addChild('itunes:duration', $this->duration, NC_ITUNES);

    }

    public function getDate()
    {
        return $this->date;
    }

}

/**
 * @param PodcastItem $obja
 * @param PodcastItem $objb
 * @return int
 */
function cmp($obja, $objb)
{
    $a = $obja->getDate();
    $b = $objb->getDate();
    if ($a == $b) {
        return 0;
    }
    return ($a > $b) ? -1 : 1;
}


class Podcast
{

    private $title;
    private $subtitle;
    private $descr;
    private $site;
    private $author;
    private $xml_url;
    private $lang;
    private $owner_name;
    private $owner_email;
    private $copyright;
    /**
     * @var array
     */
    private $episodes;
    private $img_width;
    private $img_height;
    /**
     * @var string
     */
    private $img;
    /**
     * @var string
     */
    private $img_big;
    private $category;

    public function __construct()
    {
        $this->title = "";
        $this->subtitle = "";
        $this->descr = "";
        $this->site = "";
        $this->author = "";
        $this->xml_url = "";
        $this->lang = "fr-FR";
        $this->owner_name = "";
        $this->owner_email = "";
        $this->copyright = "";
        $this->episodes = array();

    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @param string $subtitle
     */
    public function setSubtitle(string $subtitle): void
    {
        $strlen = strlen($subtitle);
        if ($strlen > 255) {
            report_error("Itunes subtitle is too long ($strlen > 255)");
            $this->subtitle = substr($subtitle, 0, 255);
            return;
        }
        $this->subtitle = $subtitle;
    }

    /**
     * @param string $descr
     */
    public function setDescr(string $descr): void
    {
        $this->descr = $descr;
    }

    /**
     * @param string $site
     */
    public function setSite(string $site): void
    {
        $this->site = $site;
    }

    /**
     * @param string $author
     */
    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }


    public function setImg(string $filename)
    {
        if (!file_exists($filename)) {
            report_error("Podcast image {$filename} does not exist");
            return;
        }
        $this->img = $filename;
        $getimagesize = getimagesize($filename);
        $this->img_width = $getimagesize[0];
        $this->img_height = $getimagesize[1];
    }


    /**
     * @param string $xml_url
     */
    public function setXmlUrl(string $xml_url): void
    {
        $this->xml_url = $xml_url;
    }

    /**
     * @param string $lang
     */
    public function setLang(string $lang): void
    {
        $this->lang = $lang;
    }

    /**
     * @param string $owner_name
     */
    public function setOwnerName($owner_name): void
    {
        $this->owner_name = $owner_name;
    }

    /**
     * @param string $owner_email
     */
    public function setOwnerEmail($owner_email): void
    {
        $this->owner_email = $owner_email;
    }

    /**
     * @param string $copyright
     */
    public function setCopyright($copyright): void
    {
        $this->copyright = $copyright;
    }


    public function addEpisode(PodcastItem $param)
    {
        $this->episodes[] = $param;
    }

    public function getOutput()
    {

        uasort($this->episodes, 'cmp');

        $rootrsss = '<rss version="2.0" ' .
            'xmlns:content="' . NC_CONTENT . '" ' .
            'xmlns:wfw="' . NC_WFW . '" ' .
            'xmlns:dc="' . NC_DC . '" ' .
            'xmlns:atom="' . NC_ATOM . '" ' .
            'xmlns:sy="' . NC_SY . '" ' .
            'xmlns:slash="' . NC_SLASH . '" ' .
            'xmlns:itunes="' . NC_ITUNES . '" ' .
            'xmlns:rawvoice="' . NC_RAWVOICE . '" ' .
            'xmlns:googleplay="' . NC_GOOGLEPLAY . '" ' .
            'xmlns:georss="' . NC_GEORSS . '" ' .
            'xmlns:geo="' . NC_GEO . '" ' .
            '/>';

        $xml = new SimpleXMLElement($rootrsss);

        $channel = $xml->addChild('channel');
        $channel->addChild('title', $this->title);
        $atom_link = $channel->addChild('atom:link', null, NC_ATOM);
        $atom_link->addAttribute("href", $this->xml_url);
        $atom_link->addAttribute("rel", "self");
        $atom_link->addAttribute("type", "application/rss+xml");

        $channel->addChild('link', $this->site);
        $channel->addChild('description', $this->descr);

        //Wed, 02 Oct 2002 08:00:00 EST
        $channel->addChild('lastBuildDate', format_date());
        $channel->addChild('language', $this->lang);
        //$channel->addChild('sy:updatePeriod', "hourly", "sy");
        //sa$channel->addChild('sy:updateFrequency', 1, "sy");

        //add podcast image
        $podcast_img_xml = $channel->addChild('image');
        $podcast_img_xml->addChild('url', relative_url($this->img));
        $podcast_img_xml->addChild('title', $this->title);
        $podcast_img_xml->addChild('link', $this->site);
        $podcast_img_xml->addChild('width', $this->img_width);
        $podcast_img_xml->addChild('height', $this->img_height);

        //itunes stuff
        $channel->addChild('itunes:summary', $this->descr, NC_ITUNES);
        $channel->addChild('itunes:author', $this->author, NC_ITUNES);
        $channel->addChild('itunes:explicit', "clean", NC_ITUNES);
        $img = $channel->addChild('itunes:image', null, NC_ITUNES);
        $img->addAttribute('href', relative_url($this->img_big));
        $owner = $channel->addChild('itunes:owner', null, NC_ITUNES);
        $owner->addChild('itunes:name', $this->owner_name, NC_ITUNES);
        if ($this->owner_email != '') {
            $owner->addChild('itunes:email', $this->owner_email, NC_ITUNES);
        }
        $channel->addChild('managingEditor', "$this->owner_email ({$this->owner_name})");
        $channel->addChild('copyright', $this->copyright);
        $channel->addChild('itunes:subtitle', $this->subtitle, NC_ITUNES);
        $categ = explode('|', $this->category);
        $main_categ = $channel->addChild('itunes:category', null, NC_ITUNES);
        $main_categ->addAttribute('text', trim($categ[0]));
        if (sizeof($categ) > 1) {
            $sub_categ = $main_categ->addChild('itunes:category', null, NC_ITUNES);
            $sub_categ->addAttribute('text', trim($categ[1]));
        }
        /** @var PodcastItem $item */
        foreach ($this->episodes as $item) {
            $item->toXML($channel);
        }
        return $xml->asXML();
    }

    /**
     * @return string
     */
    public function getOwnerName(): string
    {
        return $this->owner_name;
    }


    public function setImgBig(string $string)
    {
        $this->img_big = $string;
    }

    public function setCategory($category)
    {
        $this->category = $category;
    }


}

function rebuild_podcast()
{
    $json_raw = file_get_contents(CHANNEL_JSON);
    $json = json_decode($json_raw, true);
    // todo validate json file

    $podcast = new Podcast();
    $podcast->setTitle($json['title']);
    $podcast->setSubtitle($json['subtitle']);
    $podcast->setSite($json['website']);
    $podcast->setAuthor($json['author']);
    $podcast->setDescr($json['description']);
    $podcast->setImg($json['image_small']);
    $podcast->setImgBig($json['image_itunes']);
    $podcast->setXmlUrl("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
    $podcast->setLang($json['language']);
    $podcast->setOwnerEmail($json['owner_email']);
    $podcast->setOwnerName($json['owner_name']);
    $podcast->setCopyright($json['copyright']);
    $podcast->setCategory($json['category']);


    $episodes_dir = "episodes";
    $scandir = scandir($episodes_dir);
    foreach ($scandir as $dir) {
        $basepath = $episodes_dir . "/" . $dir . "/";
        $json_file = $basepath . "info.json";
        if (!file_exists($json_file)) {
            continue;
        }
        $json_raw = file_get_contents($json_file);
        $json_ep = json_decode($json_raw, true);
        $episode = new PodcastItem();
        $episode->setTitle($json_ep['title']);
        $episode->setSubtitle($json_ep['subtitle']);
        $episode->setSummary($json_ep['description']);
        $author = $json_ep['author'];
        if ($author == '') {
            $episode->setAuthor($json['author']);
        } else {
            $episode->setAuthor($author);
        }
        $link = $json_ep['link'];
        if ($link == '') {
            $episode->setLink($json['website']);
        } else {
            $episode->setLink($link);
        }
        $episode->setDate(strtotime($json_ep['pubdate']));
        $episode->setMp3($basepath . $json_ep['mp3']);
        $episode->setDuration($json_ep['duration']);
        $episode->setImage($basepath . $json_ep['image']);
        $podcast->addEpisode($episode);

    }

    return $podcast->getOutput();
}


if (!file_exists(CHANNEL_JSON)) {
    die("No channel.json file found");
}
//header("content-type: application/rss+xml; charset=UTF-8");
header('Content-Type: text/xml');
if (isset($_GET['force_refresh']) || !file_exists(CACHEFILE) || (filemtime(CACHEFILE) < filemtime(CHANNEL_JSON))) {
    $res = rebuild_podcast();
    file_put_contents(CACHEFILE, $res);
} else {
    $res = file_get_contents(CACHEFILE);
}
print($res);





