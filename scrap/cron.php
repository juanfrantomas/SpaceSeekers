<?php
/**
 * @Space_Seekers
 * Last Update 20150417
 * Data connector.
 *
 * Datos obtenidos de http://integral.esac.esa.int/bexrbmonitor/webpage_oneplot.php
 *
 * Para el prototipo no se incluyen:
 * - La comparativa entre los valores indicados por cada uno de los instrumentos.
 * - Fuentes de datos diversas de otras agencias ni instrumentos, por limitaciones de tiempo y científicas
 * - Tratamiento científico de los datos.
 *
 * Con el desarrollo del proyecto, estas funcionalidades serán incluidas para
 * arrojar auténticos datos de valor.
 *
 */

define( 'WP_USE_THEMES', false );
define( 'SPACE_SEEKERS_PERCENT_ALERT', 100);                                                //Event detection value
define( 'SPACE_SEEKERS_DATASOURCE',
        "http://integral.esac.esa.int/bexrbmonitor/webpage_oneplot.php");                   //Dataset
                                                                                            //Instruments
define( 'MAXI_INSTRUMENT', "MAXI");
define( 'SWIFT_INSTRUMENT', "Swift/BAT");
define( 'FERMI_INSTRUMENT', "Fermi/GBM");

//Database configuration
include 'installPlugin.php';
//HTML parser
require_once('simple_html_dom.php');
//Wordpress Core
require_once (dirname(dirname(__FILE__))."/wp-load.php");


// Create tables if they don't exist
new installPlugin();


global $wpdb;                                                                               //Wordpress Core object


// Web scraping

$html = new simple_html_dom();
$str_html = file_get_contents(SPACE_SEEKERS_DATASOURCE);
$html = $html->load($str_html);


//Column names used as index for ordering data.

$table = $html->find('table', 0);
$thead = $table->find('thead', 0);
$indices = array();
foreach($thead->find('th') as $th){
    $indices[] = trim($th->plaintext);
}


//Instruments for each measures readings in the source dataset.

$tbl_origenes = $wpdb->get_results( 'SELECT idorigen, instrumento FROM ss_origen' );
$origenes = array();
foreach($tbl_origenes as $origen){
    $origenes[$origen->idorigen] = $origen->instrumento;
}


//Reading and sorting data row by row. There are two kind of data:
// - Object location
// - Last measures

$tbody = $table->find('tbody', 0);
$lecturas = array();
$objetos = 0;
foreach($tbody->find('tr') as $tr){                                                     //Row by row
    $x = 0;
    foreach($tr->find('td') as $td){                                                    //Cell by cell
        $col_value = trim($td->plaintext);

        // ******************************** Stellar object information
        switch($indices[$x]){
            case 'Name':                                                //Name
                $ss_object['name'] = $col_value;
                $ss_object['name2'] = "";
                continue;
            case 'Ra':                                                  //Right ascension
                $ss_object['ra'] = $col_value;
                continue;
            case 'Dec':                                                 //Declination
                $ss_object['dec'] = $col_value;
                continue;
            case 'Orbital Period [d]':                                  // Orbital period
                if($col_value == "NO DATA")
                    $ss_object['orbital_period'] = "";
                else
                    $ss_object['orbital_period'] = $col_value;
                continue;
        }


        // ******************************** Measure information

        // Grouping data for each instrument in dataset

        if(strpos($indices[$x], MAXI_INSTRUMENT) !== FALSE){
            $flag = MAXI_INSTRUMENT;
            $ss_historic[$flag]['origen'] = array_search(MAXI_INSTRUMENT, $origenes);
        }else if(strpos($indices[$x], SWIFT_INSTRUMENT) !== FALSE){
            $flag = SWIFT_INSTRUMENT;
            $ss_historic[$flag]['origen'] = array_search(SWIFT_INSTRUMENT, $origenes);
        }else if(strpos($indices[$x], FERMI_INSTRUMENT) !== FALSE){
            $flag = FERMI_INSTRUMENT;
            $ss_historic[$flag]['origen'] = array_search(FERMI_INSTRUMENT, $origenes);
        }


        //Flux measures columns

        if(strpos($indices[$x], 'flux') !== FALSE) {
            $span = $td->find( 'span' );
            if (count($span) > 0) {

                //Flux change prob - obtained by span.style.color property of test wrapper (<span>)
                $prob_value = (float)str_replace('%', '', $col_value);
                $updownstatus = $span[0]->style;
                $updownstatus = explode(':', $updownstatus);
                $updownstatus = trim($updownstatus[1]);


                switch($updownstatus){
                    case 'red':                                                         //Decreasing flux
                        $ss_historic[$flag]['prob'] = 0;
                        $ss_historic[$flag]['prob_value'] = -1 * $prob_value;               //Change flux probability
                        break;
                    case 'yellow':                                                      //Rising Flux
                        if( $prob_value >= SPACE_SEEKERS_PERCENT_ALERT )                    //Event Detected!
                            $ss_historic[$flag]['prob'] = 1;
                        else
                            $ss_historic[$flag]['prob'] = 0;

                        $ss_historic[$flag]['prob_value'] = $prob_value;                    //Change flux probability
                        break;
                    case 'white':                                                       //No data
                        $ss_historic[$flag]['prob'] = 0;
                        if($prob_value == '' || $prob_value == '-')
                            $prob_value = 0;
                        $ss_historic[$flag]['prob_value'] = $prob_value;                    //Change flux probability
                        break;

                }

            }else{
                //Only average flux AND change flux prob if empty and no empty white span
                if($col_value == "" || $col_value == "-")
                    $ss_historic[$flag]['average_value'] = "0";
                else
                    $ss_historic[$flag]['average_value'] = (float)$col_value;           //average flux value

                //Filling the gaps.
                if(!isset($ss_historic[$flag]['prob']))
                    $ss_historic[$flag]['prob'] = 0;

                if(!isset($ss_historic[$flag]['prob_value']))
                    $ss_historic[$flag]['prob_value'] = "0";

            }
        }else{
            if(strpos($indices[$x], 'Data') !== FALSE){
                //Measure reading date, format <Month> <Day of month>. E.g. Apr 16th
                if ($col_value != "" && $col_value != "NO DATA") {
                    $moment = DateTime::createFromFormat('F dS', $col_value);
                } else {
                    $moment = new DateTime('now');
                }
                $ss_historic[$flag]['moment'] = $moment->format('Y-m-d H:i:s');
            }
        }
        $x++;
    }
    //Saving row and measures
    $lecturas[] = array(
        'objetos' => $ss_object,
        'historico' => $ss_historic
    );
    unset($ss_historic);

}


//Saving to Database
for($x = 0; $x < count($lecturas); $x++){

    //Is the object alredy registed?

    $sql = $wpdb->prepare("SELECT idobject FROM ss_objects WHERE name = %s", $lecturas[$x]['objetos']['name']);
    $codObject = $wpdb->get_results($sql);

    if(count($codObject) > 0){

        //Actually, it is. Measures will be assigned to it.
        $lecturas[$x]['historico'][MAXI_INSTRUMENT]['object'] = $codObject[0]->idobject;
        $lecturas[$x]['historico'][SWIFT_INSTRUMENT]['object'] = $codObject[0]->idobject;
        $lecturas[$x]['historico'][FERMI_INSTRUMENT]['object'] = $codObject[0]->idobject;

    }else{
        //No, it's not. Saving new object.
        $wpdb->insert('ss_objects', $lecturas[$x]['objetos']);
        $lecturas[$x]['historico'][MAXI_INSTRUMENT]['object'] = $wpdb->insert_id;
        $lecturas[$x]['historico'][SWIFT_INSTRUMENT]['object'] = $wpdb->insert_id;
        $lecturas[$x]['historico'][FERMI_INSTRUMENT]['object'] = $wpdb->insert_id;
    }


    //Saving measures
    foreach($lecturas[$x]['historico'] as $instrumento => $new_event){

        //check if collected data is alredy recorded.
        $sql = $wpdb
            ->prepare("SELECT * FROM ss_historic ".
                              "WHERE object = %d ".
                                "AND origen = %d ".
                                "AND prob_value = ". $new_event['prob_value'] ." ".             //Prototype version
                                "AND average_value = ". $new_event['average_value'] ." ".       //Prototype version
                              "ORDER BY moment DESC",
            $new_event['object'], $new_event['origen']);

        $events = $wpdb->get_results($sql);

        if(count($events) > 0){
            //Data exists, but is it a new measure with the same values?
            $event = $events[0]; //Most recent reading

            $now_date = new DateTime($new_event['moment']);
            $event_date = new DateTime($event->moment);

            if($now_date->format('Ymd') > $event_date->format('Ymd')){
                //It is.
                $wpdb->insert('ss_historic', $new_event);
            }

        }else{
            //New data.
            $wpdb->insert('ss_historic', $new_event);
        }
    }
}

//Cron log.
$wpdb->insert(
    'ss_cronlog',
    array(
        'moment' => (new DateTime('now'))->format('Y-m-d')
    )
);
