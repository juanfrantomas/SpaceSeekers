<?php
/**
 * rss_feed (simple rss 2.0 feed creator php class)
 *
 * @author     Christos Pontikis http://pontikis.net
 * @copyright  Christos Pontikis
 * @license    MIT http://opensource.org/licenses/MIT
 * @version    0.1.0 (28 July 2013)
 *
 */

include (dirname(dirname(__FILE__))."/wp-load.php");

class rss_feed  {



    /**
     * Constructor
     *
     * @param string $xmlns XML namespace
     * @param array $a_channel channel properties
     * @param string $site_url the URL of your site
     * @param string $site_name the name of your site
     * @param bool $full_feed flag for full feed (all topic content)
     */
    public function __construct($xmlns, $a_channel, $site_url, $site_name, $full_feed = false) {
      // initialize
      $this->xmlns = ($xmlns ? ' ' . $xmlns : '');
      $this->channel_properties = $a_channel;
      $this->site_url = $site_url;
      $this->site_name = $site_name;
      $this->full_feed = $full_feed;
    }

    /**
     * Generate RSS 2.0 feed
     *
     * @return string RSS 2.0 xml
     */
    public function create_feed() {

        $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";

        $xml .= '<rss version="2.0" ' . $this->xmlns . '>' . "\n";

        // channel required properties
        $xml .= '<channel>' . "\n";
        $xml .= '<title>' . $this->channel_properties["title"] . '</title>' . "\n";
        $xml .= '<link>' . $this->channel_properties["link"] . '</link>' . "\n";
        $xml .= '<description>' . $this->channel_properties["description"] . '</description>' . "\n";

        // channel optional properties
        if(array_key_exists("language", $this->channel_properties)) {
          $xml .= '<language>' . $this->channel_properties["language"] . '</language>' . "\n";
        }
        if(array_key_exists("image_title", $this->channel_properties)) {
          $xml .= '<image>' . "\n";
          $xml .= '<title>' . $this->channel_properties["image_title"] . '</title>' . "\n";
          $xml .= '<link>' . $this->channel_properties["image_link"] . '</link>' . "\n";
          $xml .= '<url>' . $this->channel_properties["image_url"] . '</url>' . "\n";
          $xml .= '</image>' . "\n";
        }

        $rss_items = $this->get_feed_items();

        $x = 1;
        foreach($rss_items as $rss_item) {
              $xml .= '<item>' . "\n";
              $xml .= '    <name>' . $rss_item->name . '</name>' . "\n";
              $xml .= '    <ra>' . $rss_item->ra . '</ra>' . "\n";
              $xml .= '    <dec>' . $rss_item->dec . '</dec>' . "\n";
              $xml .= '    <orbital-period>' . $rss_item->orbital_period . '</orbital-period>' . "\n";
              $xml .= '    <status>' . $rss_item->status . '</status>' . "\n";
              $xml .= '    <prob>' . $rss_item->prob . '</prob>' . "\n";
              $xml .= '    <flux>' . $rss_item->flux . '</flux>' . "\n";
              $xml .= '    <unit>' . $rss_item->unit . '</unit>' . "\n";
              $xml .= '    <source>' . $rss_item->source . '</source>' . "\n";
              $xml .= '    <moment>' . $rss_item->moment . '</moment>' . "\n";
              $xml .= '</item>' . "\n";
              $x++;
        }

        $xml .= '</channel>';

        $xml .= '</rss>';

        return $xml;
    }


    /*public function get_feed_items(){
        $json = file_get_contents('data.json');
        $json_obj = json_decode($json);
        return $json_obj;
    }*/

    /**
     * @return array
    */
    public function get_feed_items() {
        global $wpdb;
        $res = $wpdb->get_results("SELECT
                                ob.name, ob.ra, ob.dec, ob.orbital_period,
                                hi.prob_value AS prob, hi.average_value AS flux, hi.moment,
                                ori.ud AS unit, ori.instrumento AS source
                                FROM
                                ss_objects ob INNER JOIN ss_historic hi INNER JOIN ss_origen ori
                                ON ob.idobject = hi.object AND hi.origen = ori.idorigen
                                ORDER BY moment DESC");

        $max_events = array();
        for($x = 0; $x < count($res); $x++){
            $max_event = array();
            if($res[$x]->prob > 0){
                $res[$x]->status = "Rising flux";
            }else if($res[$x]->prob < 0){
                $res[$x]->status = "Decreasing flux";
            }else{
                $res[$x]->status = "";
            }

            if(!isset($max_event[$res[$x]->name])){
                $max_event[$res[$x]->name] = $res[$x];
            }else{
                if($max_event[$res[$x]->name]->moment < $res[$x]->moment){
                    $max_event[$res[$x]->name] = $res[$x];
                }
            }
            $max_events[] = $max_event;
        }

        return $res;
    }
}