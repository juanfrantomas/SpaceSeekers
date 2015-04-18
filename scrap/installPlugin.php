<?php
/**
 * Date: 15/04/2015
 * Time: 13:27
 * Resources:
 * https://codex.wordpress.org/Creating_Tables_with_Plugins
 *
 */
include (dirname(dirname(__FILE__))."/wp-load.php");

class installPlugin {

    private $wpdb;

    function installPlugin(){
        global $wpdb;
        $this->wpdb = $wpdb;
        if(!$this->checkTables('ss_objects')){
            $this->createStellarObjects();
        }

        if(!$this->checkTables('ss_historic')){
            $this->createEventRegistry();
        }

        if(!$this->checkTables('ss_origen')){
            $this->createOrigines();
        }
        if(!$this->checkTables('ss_cronlog')){
            $this->createCronLog();
        }
    }

    private function checkTables($transientEvents){
        //global $wpdb;
        if($this->wpdb->get_var("SHOW TABLES LIKE '". $transientEvents ."'") == $transientEvents)
            return true;
        return false;
    }

    private function createStellarObjects(){
        //global $wpdb;
        $charset = $this->wpdb->get_charset_collate();
        $tableName = "ss_objects";

        $sqlTable = "CREATE TABLE `". $tableName ."` (
              `idobject` INT NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(50) NOT NULL,
              `name2` VARCHAR(75) NULL,
              `ra` VARCHAR(25) NOT NULL,
              `dec` VARCHAR(25) NOT NULL,
              `orbital_period` VARCHAR(50) NOT NULL,
              PRIMARY KEY (`idobject`),
              UNIQUE INDEX `idobject_UNIQUE` (`idobject` ASC)) ". $charset .";";

        require_once( '../wp-admin/includes/upgrade.php' );
        dbDelta( $sqlTable );
    }

    private function createOrigines(){
        //global $wpdb;
        $charset =  $this->wpdb->get_charset_collate();
        $tableName = 'ss_origen';

        $sqlTable = "CREATE TABLE `".$tableName."` (
              `idorigen` INT NOT NULL AUTO_INCREMENT,
              `instrumento` VARCHAR(50) NULL,
              `ud` VARCHAR(45) NULL,
              PRIMARY KEY (`idorigen`),
              UNIQUE INDEX `idorigen_UNIQUE` (`idorigen` ASC)) ". $charset .";";

        $inserts = "INSERT INTO `ss_origen` (`instrumento`, `ud`) VALUES ('MAXI', 'mCrab');
                INSERT INTO `ss_origen` (`instrumento`, `ud`) VALUES ('Fermi/GBM', 'keV cm^-2 s^-1');
                INSERT INTO `ss_origen` (`instrumento`, `ud`) VALUES ('Swift/BAT', 'mCrab')";

        require_once( '../wp-admin/includes/upgrade.php' );
        dbDelta( $sqlTable );
        dbDelta($inserts);

    }

private function createEventRegistry(){
        /**
         * Prob  0 Decrising - 1 rising,
         */
        //global $wpdb;
        $charset =  $this->wpdb->get_charset_collate();
        $tableName = 'ss_historic';


        $sqlTable = "CREATE TABLE `".$tableName."` (
              `idhistoric` INT NOT NULL AUTO_INCREMENT,
              `prob_value` DECIMAL(6, 3) NOT NULL,
              `average_value` DECIMAL(6, 3) NOT NULL,
              `prob` TINYINT(1) NOT NULL,
              `moment` TIMESTAMP NOT NULL,
              `origen` INT NOT NULL,
              `object` INT NOT NULL,
              PRIMARY KEY (`idhistoric`),
              UNIQUE INDEX `idhistoric_UNIQUE` (`idhistoric` ASC)) ". $charset .";";
        require_once( '../wp-admin/includes/upgrade.php' );
        dbDelta( $sqlTable );
    }

private function createCronLog(){
        //global $wpdb;
        $charset =  $this->wpdb->get_charset_collate();
        $tableName = 'ss_cronlog';


        $sqlTable = "CREATE TABLE `".$tableName."` (
              `idcronlog` INT NOT NULL AUTO_INCREMENT,
              `moment` TIMESTAMP NOT NULL,
              PRIMARY KEY (`idcronlog`),
              UNIQUE INDEX `idcronlog_UNIQUE` (`idcronlog` ASC)) ". $charset .";";
        require_once( '../wp-admin/includes/upgrade.php' );
        dbDelta( $sqlTable );
    }
}
