<?php
/* 
 * @author Erdal Arslan <info@seeprof.com>
 * @version 1.1
 * Sayın Tayfun Erbilen'e Desteklerinden Dolayı Çok Teşekkür Ederiz İyiki Varsınız
*/


function backup($pdo_object = false){
	if($pdo_object !== false){
/* Function'larımızı Alabilmek İçin Bağlantı Kurduğumuz Veri Tabanı İsmini Alıyoruz */
		$databasename = $pdo_object->query("SELECT DATABASE()")->fetch(PDO::FETCH_ASSOC)["DATABASE()"];
/* Mysql İle Import Ederken Hata Almamanız İçin Gereken Kodlar */
		$sql = '-- Start 
-- Erdal Arslan Database Yedek Alma
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

';
/* Tablolarımızı Buluyoruz */
		$tables = $pdo_object->query("SHOW TABLES")->fetchAll(PDO::FETCH_ASSOC);
/* Tablolarımızın İçinde Dolaşalım */
		foreach($tables as $table){
		$tablename = current($table);
		$rows = $pdo_object->query("SELECT * FROM $tablename")->fetchAll(PDO::FETCH_ASSOC);
/* Tabloları Sql İle Oluşturmak İçin Gerekli Kodları "Show Create Table" Sorgusuyla Ulaşıyoruz */
		$tabledetail = $pdo_object->query("SHOW CREATE TABLE $tablename")->fetch(PDO::FETCH_ASSOC);
		$sql .= $tabledetail["Create Table"].";".str_repeat(PHP_EOL,3);
		if(count($rows) > 0){
/* Tablolarımızın İçinde Dolaşırken İçeriği Boş Değilse Sutun İsimleri İle Birlikte Data'ları Okuyoruz */
		$columns = 	$pdo_object->query("SHOW COLUMNS FROM $tablename")->fetchAll(PDO::FETCH_ASSOC);
		$columns = array_map(function($column){
			return $column["Field"];
		},$columns);
/* Sütünlara Ait Data'ları Sql Kullanarak Import Etmek İçin Sorgumuzu Oluşturuyoruz */
		$sql .= 'INSERT INTO `'.$tablename.'`(`'.implode("`,`",$columns).'`) VALUES'.PHP_EOL;
		$columnsdata = array();
		foreach($rows as $row){
			$row = array_map(function($item){
			/* PDO ' nun Quote Fonksiyonu Bazı Durumlarda İşimizi Çözmediğini ve Hata Almaya Devam Ettiğimizi Gördük
			   O Yüzden Kaçmak İstediğiniz Karakterleri $inject Dizisene Ekleyip Replace Dizisinede Neyle 
			   Değiştirilmesi Gerektiğini Ekleyebilirsiniz */
			$inject = array("'",";","&");
			$replace = array("","","");
			return "'".str_ireplace($inject,$replace,$item)."'";
		},$row);
		$columnsdata[] = '('.implode(",",$row).')';
		}
		$sql .= implode(",".PHP_EOL,$columnsdata).";".str_repeat(PHP_EOL,3);
		}		
		}// Tablo Döngümüz Bitti
/* Database İçerisinde Trigger Varsa Onları Alıyoruz */
		$triggers = $pdo_object->query("SHOW TRIGGERS")->fetchAll(PDO::FETCH_ASSOC);
		if(count($triggers) > 0){
		$sql.= "DELIMITER $$".PHP_EOL;
		foreach($triggers as $trigger){
		$triggername = $trigger["Trigger"];
		$triggerquery = $pdo_object->query("SHOW CREATE TRIGGER $triggername")->fetch(PDO::FETCH_ASSOC);
		$sql .= $triggerquery["SQL Original Statement"]." $$".PHP_EOL;
		}
		$sql.= "DELIMITER ;".str_repeat(PHP_EOL,3);
		}	
/* Database İçerisinde FUNCTION Varsa Onları  Alıyoruz */
		$functions = $pdo_object->query("SHOW FUNCTION STATUS WHERE Db='$databasename'")->fetchAll(PDO::FETCH_ASSOC);
		if(count($functions) > 0){
		$sql.= "DELIMITER $$".PHP_EOL;
		foreach($functions as $function){
		$functionname = $function["Name"];
		$functionquery = $pdo_object->query("SHOW CREATE FUNCTION $functionname")->fetch(PDO::FETCH_ASSOC);
		$sql .= $functionquery["Create Function"]." $$".PHP_EOL;
		}
		$sql.= "DELIMITER ;".str_repeat(PHP_EOL,3);
		}
/* Database İçerisinde PROCEDURE Varsa Onları  Alıyoruz */
		$procedures = $pdo_object->query("SHOW PROCEDURE STATUS WHERE Db='$databasename'")->fetchAll(PDO::FETCH_ASSOC);
		if(count($procedures) > 0){
		$sql.= "DELIMITER $$".PHP_EOL;
		foreach($procedures as $procedure){
		$procedurename = $procedure["Name"];
		$procedurequery = $pdo_object->query("SHOW CREATE PROCEDURE $procedurename")->fetch(PDO::FETCH_ASSOC);
		$sql .= $procedurequery["Create Procedure"]." $$".PHP_EOL;
		}
		$sql.= "DELIMITER ;".str_repeat(PHP_EOL,3);
		}			
/* Oluşturduğumuz $sql Değişkeninin İçeriğini Tarih ve Saat adıyla Kaydediyoruz */
		$file = fopen (__DIR__ . "/".date("Y-m-d-H-i-s").".sql","w");
		fwrite($file,$sql."-- End");
		fclose($file);
	}
}



/* Kullanımı Aşağıda Gösterilmiştir 
   Foksiyonun Tek Bağımlılığı Bir Pdo Object Gönderilmesi Gerekiyor
*/
$connect = new PDO ("mysql:host=localhost;dbname=xxx;charset=utf8;","root","");
backup($connect);
