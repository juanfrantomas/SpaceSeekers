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
define( 'SPACE_SEEKERS_PERCENT_ALERT', 100);
define( 'SPACE_SEEKERS_DATASOURCE', "http://integral.esac.esa.int/bexrbmonitor/webpage_oneplot.php");
define( 'MAXI_INSTRUMENT', "MAXI");
define( 'SWIFT_INSTRUMENT', "Swift/BAT");
define( 'FERMI_INSTRUMENT', "Fermi/GBM");


include 'installPlugin.php';
require_once('simple_html_dom.php');
include (dirname(dirname(__FILE__))."/wp-load.php");

/** Create tables */
new installPlugin();


global $wpdb;



$html = new simple_html_dom();
$str_html = file_get_contents(SPACE_SEEKERS_DATASOURCE);
$html = $html->load($str_html);

/** Col names $tables */
$table = $html->find('table', 0);

$thead = $table->find('thead', 0);

$indices = array();
foreach($thead->find('th') as $th){
    $indices[] = trim($th->plaintext);
}


$tbody = $table->find('tbody', 0);


$tbl_origenes = $wpdb->get_results( 'SELECT idorigen, instrumento FROM ss_origen' );
$origenes = array();
foreach($tbl_origenes as $origen){
    $origenes[$origen->idorigen] = $origen->instrumento;
}

$lecturas = array();


$objetos = 0;
foreach($tbody->find('tr') as $tr){
    $x = 0;
    foreach($tr->find('td') as $td){
        $col_value = trim($td->plaintext);
        /** Datos del objeto estelar */
        switch($indices[$x]){
            case 'Name':
                $ss_object['name'] = $col_value;
                $ss_object['name2'] = "";
                continue;
            case 'Ra':
                $ss_object['ra'] = $col_value;
                continue;
            case 'Dec':
                $ss_object['dec'] = $col_value;
                continue;
            case 'Orbital Period [d]':
                if($col_value == "NO DATA")
                    $ss_object['orbital_period'] = "";
                else
                    $ss_object['orbital_period'] = $col_value;
                continue;
        }

        /** Datos del la medición */

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

        if(strpos($indices[$x], 'flux') !== FALSE) { //Flux measures cols
            $span = $td->find( 'span' );
            if (count($span) > 0) {
                //Flux change prob.
                $prob_value = (float)str_replace('%', '', $col_value);
                $updownstatus = $span[0]->style;
                $updownstatus = explode(':', $updownstatus);
                $updownstatus = trim($updownstatus[1]);


                switch($updownstatus){
                    case 'red':
                        $ss_historic[$flag]['prob'] = 0;
                        $ss_historic[$flag]['prob_value'] = -1 * $prob_value;
                        break;
                    case 'yellow':
                        if( $prob_value >= SPACE_SEEKERS_PERCENT_ALERT )
                            $ss_historic[$flag]['prob'] = 1;
                        else
                            $ss_historic[$flag]['prob'] = 0;

                        $ss_historic[$flag]['prob_value'] = $prob_value;
                        break;
                    case 'white':
                        $ss_historic[$flag]['prob'] = 0;
                        if($prob_value == '' || $prob_value == '-')
                            $prob_value = 0;
                        $ss_historic[$flag]['prob_value'] = $prob_value;
                        break;
                }

            }else{
                if($col_value == "" || $col_value == "-"){
                    $ss_historic[$flag]['average_value'] = "";
                }else{
                    //average flux value
                    $ss_historic[$flag]['average_value'] = (float)$col_value;;
                }

            }
        }else{

            if(strpos($indices[$x], 'Data') !== FALSE){
                //Date read
                if ($col_value != "" && $col_value != "NO DATA") {
                    $moment = DateTime::createFromFormat('F dS', $col_value);
                } else {
                    $moment = new DateTime('now');
                }
                $ss_historic[$flag]['moment'] = $moment->format('Y-m-d');
            }
        }
        $x++;
    }
    $lecturas[] = array(
        'objetos' => $ss_object,
        'historico' => $ss_historic
    );
    unset($ss_historic);

}

for($x = 0; $x < count($lecturas); $x++){

    $sql = $wpdb->prepare("SELECT idobject FROM ss_objects WHERE name = %s", $lecturas[$x]['objetos']['name']);
    $codObject = $wpdb->get_results($sql);
    if(count($codObject) > 0){
        $lecturas[$x]['historico'][MAXI_INSTRUMENT]['object'] = $codObject[0]->idobject;
        $lecturas[$x]['historico'][SWIFT_INSTRUMENT]['object'] = $codObject[0]->idobject;
        $lecturas[$x]['historico'][FERMI_INSTRUMENT]['object'] = $codObject[0]->idobject;
    }else{
        $wpdb->insert('ss_objects', $lecturas[$x]['objetos']);
        $lecturas[$x]['historico'][MAXI_INSTRUMENT]['object'] = $wpdb->insert_id;
        $lecturas[$x]['historico'][SWIFT_INSTRUMENT]['object'] = $wpdb->insert_id;
        $lecturas[$x]['historico'][FERMI_INSTRUMENT]['object'] = $wpdb->insert_id;
    }

    foreach($lecturas[$x]['historico'] as $instrumento => $new_event){

        $sql = $wpdb->prepare("SELECT * FROM ss_historic WHERE object = %d AND origen = %d ORDER BY moment DESC", $new_event['object'], $new_event['origen']);
        $events = $wpdb->get_results($sql);
        if(count($events) > 0){
            $event = $events[0];
            if($new_event['moment'] > $event->moment){
                $wpdb->insert('ss_historic', $new_event);
            }
        }else{
            $wpdb->insert('ss_historic', $new_event);
        }
    }
}

$wpdb->insert(
    'ss_cronlog',
    array(
        'moment' => (new DateTime('now'))->format('Y-m-d')
    )
);