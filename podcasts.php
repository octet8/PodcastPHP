<?php

//header("content-type: application/rss+xml; charset=UTF-8");
header('Content-Type: text/xml');

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

class PodcastItem
{
    private $mp3;
    private $title;
    private $date;
    private $comment;
    private $author;
    private $season;
    private $duration;
    private $summary;
    private $image;
    private $subtitle;

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
     * @param string $comment
     */
    public function setComment($comment): void
    {
        $this->comment = $comment;
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
     * @param SimpleXMLElement $channel
     */
    public function toXML($channel)
    {
        $relative_url = relative_url($this->mp3);
        $item = $channel->addChild('item');
        $item->addChild('title', $this->title);
        //$item->addChild('link', "fixme");
        $item->addChild('pubDate', format_date($this->date));

        $item->addChild('guid', "$relative_url");

        $item->addChild('description', $this->summary);
        $enclosure = $item->addChild('enclosure');
        $enclosure->addAttribute("url", $relative_url);
        $size = filesize($this->mp3);
        $enclosure->addAttribute("length", $size);
        $enclosure->addAttribute("type", "audio/mpeg");

        if ($this->subtitle!='') {
            $item->addChild('itunes:subtitle', $this->subtitle, NC_ITUNES);
        }
        $item->addChild('itunes:summary', $this->summary, NC_ITUNES);
        $item->addChild('itunes:author', $this->author, NC_ITUNES);
        $img = $item->addChild('itunes:image', null, NC_ITUNES);
        $img->addAttribute('href', relative_url($this->image));

        $item->addChild('itunes:season', $this->season, NC_ITUNES);
        $item->addChild('itunes:duration', $this->duration, NC_ITUNES);

    }

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
    private $last_build;
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
     * @throws Exception
     */
    public function setSubtitle(string $subtitle): void
    {
        $strlen = strlen($subtitle);
        if ($strlen > 255) {
            $this->report_error("Itunes subtitle is too long ($strlen > 255)");
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
            $this->report_error("Podcast image {$filename} does not exist");
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
        /*
        $podcast_img_xml = $channel->addChild('image');
        $podcast_img_xml->addChild('url', relative_url($this->img));
        $podcast_img_xml->addChild('title', $this->title);
        $podcast_img_xml->addChild('link', $this->site);
        $podcast_img_xml->addChild('width', $this->img_width);
        $podcast_img_xml->addChild('height', $this->img_height);
        */


        //itunes stuff
        $channel->addChild('itunes:summary', $this->descr, NC_ITUNES);
        $channel->addChild('itunes:author', $this->author, NC_ITUNES);
        $channel->addChild('itunes:explicit', "clean", NC_ITUNES);
        $img = $channel->addChild('itunes:image', null, NC_ITUNES);
        $img->addAttribute('href', relative_url($this->img_big));

        $owner = $channel->addChild('itunes:owner', null, NC_ITUNES);
        $owner->addChild('itunes:name', $this->owner_name, NC_ITUNES);
        $owner->addChild('itunes:email', $this->owner_email, NC_ITUNES);

        $channel->addChild('managingEditor', "$this->owner_email ({$this->owner_name})");
        $channel->addChild('copyright', $this->copyright);

        $channel->addChild('itunes:subtitle', $this->subtitle, NC_ITUNES);

        //fixme hardcoded category
        $categ = $channel->addChild('itunes:category', null, NC_ITUNES);
        $categ->addAttribute('text', "Technology");
        //$subcateg= $categ->addChild('itunes:category', null, NC_ITUNES);
        //$subcateg->addAttribute('text', "Technology");


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

    private function report_error(string $message)
    {
        throw new Exception($message);
    }

    public function setImgBig(string $string)
    {
        $this->img_big = $string;
    }


}

function rebuild_podcast()
{
    $podcast = new Podcast();

    $podcast->setTitle("SDS Podcasts");
    $podcast->setSubtitle("Podcasts de la Faculté des Sciences de la Société de l'Université de Genève");
    $podcast->setSite("https://www.unige.ch/sciences-societe/servicecollect/podcast/");
    $podcast->setAuthor("Faculté des Sciences de la Société, UNIGE");
    $podcast->setDescr("Les podcasts de la Faculté des Sciences de la Société proposent des entretiens avec nos chercheuses et chercheurs sur des thématiques de sciences sociales, des éclairages et des idées pour faire réfléchir au monde dans lequel nous vivons.");
    $podcast->setImg("sds_podcast_32.jpg");
    $podcast->setImgBig("sds_podcast.jpg");
    $podcast->setXmlUrl("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
    $podcast->setLang("fr-FR");
    $podcast->setOwnerEmail("elearning-sds@unige.ch");
    $podcast->setOwnerName("Faculté des Sciences de la Société, UNIGE");
    $podcast->setCopyright("&#xA9; Faculté des Sciences de la Société, UNIGE");


    $episode = new PodcastItem();
    $episode->setTitle("Inscription de l'alpinisme au patrimoine immatériel de l'Unesco, avec Prof. Bernard Debarbieux");
    $episode->setAuthor($podcast->getOwnerName());
    $episode->setDate(strtotime("10 Feb 2020"));
    $episode->setMp3("episodes/2020_02_10/alpinisme_unesco.mp3");
    $episode->setSummary(
        "L'alpinisme vient d'être inscrit au patrimoine culturel immatériel de l'humanité. Qui a porté ce projet ? Qui a-t-il fallu convaincre ? Avec quels arguments, et quels concepts ? De son côté, le massif du Mont-Blanc attend toujours aux portes du patrimoine mondial une inscription dont il est question depuis plus de vingt-cinq ans.");
    $episode->setDuration("00:37:35");
    $episode->setImage("episodes/2020_02_10/unnamed.jpg");
    $podcast->addEpisode($episode);

    $episode = new PodcastItem();
    $episode->setTitle("Les mouvements sociaux, avec Prof. Marco Giugni");
    $episode->setAuthor($podcast->getOwnerName());
    $episode->setDate(strtotime("27 Jan 2020"));
    $episode->setMp3("episodes/2020_01_27/mouvements_sociaux.mp3");
    $episode->setSummary(
        "Marco Giugni aborde le concept de mouvement social et questionne les facteurs et structures de mobilisation, et les formes d’actions. C’est l’ensemble des caractéristiques d’un régime ou de ses institutions qui facilitent ou entravent l’action collective des citoyens et citoyennes. Une des caractéristiques les plus importantes qui permet la mobilisation sociale est le degré de concentration du pouvoir étatique et la capacité de ce même État à accepter les changements qui émanent des contre-pouvoirs, notamment les mouvements sociaux.");
    $episode->setDuration("00:36:35");
    $episode->setImage("episodes/2020_01_27/24rtg245g2.jpg");
    $podcast->addEpisode($episode);
    //fixme save the output for the cache
    return $podcast->getOutput();
}

$res = rebuild_podcast();
/*
$fixedrss="<rss version=\"2.0\"
     xmlns:content=\"http://purl.org/rss/1.0/modules/content/\"
     xmlns:wfw=\"http://wellformedweb.org/CommentAPI/\"
     xmlns:dc=\"http://purl.org/dc/elements/1.1/\"
     xmlns:atom=\"http://www.w3.org/2005/Atom\"
     xmlns:sy=\"http://purl.org/rss/1.0/modules/syndication/\"
     xmlns:slash=\"http://purl.org/rss/1.0/modules/slash/\"
     xmlns:itunes=\"http://www.itunes.com/dtds/podcast-1.0.dtd\"
     xmlns:rawvoice=\"http://www.rawvoice.com/rawvoiceRssModule/\"
     xmlns:googleplay=\"http://www.google.com/schemas/play-podcasts/1.0\"
     xmlns:georss=\"http://www.georss.org/georss\"
     xmlns:geo=\"http://www.w3.org/2003/01/geo/wgs84_pos#\"
>";
$res = str_replace('<rss>', $fixedrss, $res);
*/
print($res);





