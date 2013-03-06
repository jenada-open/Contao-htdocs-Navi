<?php

/**
 * Contao - htdocs - Navi
 * Copyright (C) 2013 Jens Adam
 *
 * Dieses Script wurde für folgende Umgebung geschrieben:
 * In mehreren VM's ist je eine XAMPP Installation 
 * mit je mehreren Contao Installtionen untergebracht.
 * Die Maschinen werden jeweils durch Port-Forwarding erreicht.
 * Beispiel: 
 * Test.de:82 landet auf 192.168.112.82 Port 80
 * Test.de:83 landet auf 192.168.112.83 Port 80
 * Test.de:84 landet auf 192.168.112.84 Port 80
 * usw...
 * 
 * je VM gibt es mehrere Contao Installationen unter htdocs
 * Beispiel:
 * htdocs/cto1
 * htdocs/cto2
 * htdocs/cto3
 * usw...
 * 
 * im jeweiligen Contao Root kann eine info.txt gespeichert werden.
 * NEU: die info.txt ist nun eine Config-Datei für Multidomainbetrieb. 
 * Aufbau:
 * sub1=BeschreibungSubdomain1
 * sub2=BeschreibungSubdomain2
 * sub3=BeschreibungSubdomain3
 * usw...
 * 
 * Das Script selbst soll aufgerufen werden, wenn man htdocs-root aufruft
 * Dann erhält man eine Übersicht der bestehenden Installtionen mit je 
 * einem Link zur Contao Administration und zum Aufruf der Web-Seite
 * 
 * Eine httpd-vhosts.conf wird erzeugt und kann von XAMPP eingebunden werden.
 * Außerdem kann man sich eine Kopier-Vorlage für die httpd-vhosts.conf
 * anzeigen lassen.
 * 
 * Ein Neustart kann durchgeführt werden.
 * Besonders interessant, wenn die httpd-vhosts.conf geändert wurde.
 *
 * PHP version 5
 * @copyright  Jens Adam
 * @author     Jens Adam
 * @version    0.2    alpha
 * @license    LGPL
 * 
 */
////////////////////////////////////////////////////////////////////////////////
/**
 * TODO: Sicherheitsabfrage vor Neustart
 * TODO: bessere Neustartmethode; evt. nur Apache neustarten.
 * TODO: Zugriffsbeschränkung für Fernzugriff insbesondere für System-Neustart
 * 
 */
////////////////////////////////////////////////////////////////////////////////
define('EOL', "\r\n");

class htdocsNavi {

    private $Folders = array();
    private $Dir = '/';
    private $Host;
    private $Domain = 'dynsys.de';
    private $XamppDocumentRoot = 'C:/xampp/htdocs/';
    private $httpdVhosts = 'C:/xampp/htdocs/htdocsNavi/httpd-vhosts.conf';

    public function __construct($Dir) {
        $this->Dir = $Dir;
        $this->Host = $this->getHost();
    }

    /**
     * Ermittelt alle Unterordner
     * Schliesst '.', '..', und 'htdocsNavi' aus
     * 
     * @return array 
     */
    public function getFolders() {
        $hdl = opendir($this->Dir);
        $this->Folders = NULL;
        while (false !== ($entry = readdir($hdl))) {

            if ($entry == '.' or $entry == '..') {
                continue;
            }

            if ($entry == 'htdocsNavi') {
                continue;
            }

            if (is_dir($this->Dir . $entry)) {
                $this->Folders[] = $entry;
            }
        }
        closedir($hdl);
        return $this->Folders;
    }

    /**
     * Ermittelt den Host
     * ...schmunzeln erlaubt :-)
     * 
     * TODO: universeller schreiben für andere Konfigurationen
     * 
     * @return string 
     */
    public function getHost() {
        $host = $_SERVER['HTTP_HOST'];
        if (!StrPos('.' . $host, $this->Domain)) {
            $port = (substr($_SERVER['HTTP_HOST'], 12) * 1) + 70;
            if ($port < 82 or $port > 89)
                $port = 80;
            $host = $this->Domain . ':' . $port;
        }
        return $host;
    }

    /**
     * Info Text aus dem jeweiligen doc-root Ordner lesen.
     * info.txt 
     * 
     * @param String $Folder
     * @return array
     */
    private function getInfoText($Folder) {
        $filename = $Folder . '/info.txt';
        if (!file_exists($filename))
            return false;
        return file_get_contents($filename);
    }

    /**
     * Schlüssel-Werte Paare aus Info Text extrahieren
     * info.txt 
     * 
     * @param String $Folder
     * @return array
     */
    private function getInfoConfig($Folder) {
        $filename = $Folder . '/info.txt';
        if (!file_exists($filename))
            return array();
        $lines = file($filename);
        if (!is_array($lines))
            return array();
        $resarr = NULL;
        foreach ($lines as $line) {
            $p = StriPos($line, '=');
            $key = SubStr($line, 0, $p);
            $val = SubStr($line, $p + 1);
            if ($key <> '' and $val <> '') {
                $resarr[$key . '.'] = trim($val);
            } else {
                $resarr[''] = trim($line);
            }
        }
        return $resarr;
    }

    /**
     * Aufbau der Linkliste als Tabelle
     * 
     * @return string 
     */
    public function getLinkList() {
        $htm = '<table >';
        foreach ($this->getFolders() as $val) {
            $subdom = $this->getInfoConfig($this->Dir . $val);
            foreach ($subdom as $k => $v) {
            $htm.='<tr style="background-color: #c0c0c0;">';
            $htm.='<td>' . $k. $val . '.' . $this->Host . '</td>';
            $htm.='<td>&nbsp;<a href="http://' . $k . $val . '.' . $this->Host . '/contao/index.php" target="_blank">Admin</a>&nbsp;</td>';
            $htm.='<td>&nbsp;<a href="http://' . $k . $val . '.' . $this->Host . '/" target="_blank">Show</a>&nbsp;</td>';
            $htm.='<td>' . $v . '</td>';
            $htm.='</tr>';
        }}
        $htm.='</table>';
        return $htm;
    }

    /**
     * Erzeugt eine Vorlage der httpd-vhosts.conf
     * -passend zu der vorhandenen Verzeichnisstruktur.
     * -passend für unsere XAMPP-VM's
     * 
     * 
     * @return String 
     */
    public function getHttpdVhosts() {
        $htm = 'NameVirtualHost *:80' . EOL;
        $htm.=EOL;
        $htm.='<VirtualHost *:80>' . EOL;
        $htm.='    DocumentRoot "' . $this->XamppDocumentRoot . '"' . EOL;
        $htm.='    ServerName ' . $this->Domain . EOL;
        $htm.='    ServerAlias ' . $this->Domain . EOL;
        $htm.='</VirtualHost>' . EOL;
        foreach ($this->getFolders() as $val) {
            $subdom = $this->getInfoConfig($this->Dir . $val);
            foreach ($subdom as $k => $v) {
                $htm.=EOL;
//            if ($v <> '') {
                $htm.='##' . EOL;
                //$htm.='# ' . $k . EOL;
                $htm.='# ' . $v . EOL;
                $htm.='##' . EOL;
//            }
                $htm.='<VirtualHost *:80>' . EOL;
                $htm.='    DocumentRoot "' . $this->XamppDocumentRoot . $val . '"' . EOL;
                $htm.='    ServerName ' . $k . $val . '.' . $this->Domain . EOL;
                $htm.='    ServerAlias ' . $k . $val . '.' . $this->Domain . EOL;
                $htm.='</VirtualHost>' . EOL;
            }
        }
        return $htm;
    }

    public function showHttpdVhosts() {
        $htm = nl2br(htmlspecialchars($this->getHttpdVhosts()));
        echo $htm;
    }

    public function saveHttpdVhosts() {
        $oldcontent = file_get_contents($this->httpdVhosts);
        $newcontent = $this->getHttpdVhosts();
        if ($oldcontent <> $newcontent) {
            $fh = fopen($this->httpdVhosts, 'w');
            fwrite($fh, $newcontent);
        }
    }

    public function getHttpdLink() {
        $htm = '<a href="http://' . $this->getHost() . '/?ACT=1" target="_blank">httpd-vhosts.conf Kopier-Vorlage</a>';
        return $htm;
    }

    public function getRestartLink() {
        $htm = '<a href="http://' . $this->getHost() . '/?ACT=2">Server neu starten</a>';
        return $htm;
    }

    public function getHostLink() {
        $htm = '<a href="http://' . $this->getHost() . '/">' . $this->getHost() . '</a>';
        return $htm;
    }

    public function RestartWindows() {
        $cmd = 'shutdown -r -t 1';
        $res = shell_exec($cmd);
        return trim($res);
    }

}

$hdn = new htdocsNavi('C:/xampp/htdocs/');

if (isset($_GET['ACT'])) {
    $ACT = $_GET['ACT'] * 1;
} else
    $ACT = 0;

switch ($ACT) {
    case 0:
        echo '<h1>Contao - htdocs - Navigation</h1>';
        echo $hdn->getLinkList();
        $hdn->saveHttpdVhosts();
        echo '<hr>' . $hdn->getHttpdLink() . '&nbsp;&nbsp;&nbsp;' . $hdn->getRestartLink();
        break;
    case 1:
        $hdn->showHttpdVhosts();
        break;
    case 2:
        $hdn->saveHttpdVhosts();
        echo '<meta http-equiv="refresh" content="90; url=http://' . $hdn->getHost() . '">';
        echo '<h1>htdocs-Navi</h1>';
        echo '<br>versuche Server neu zu starten...';
        $res = $hdn->RestartWindows();
        if ($res <> '') {
            echo '<br>Neustart fehlgeschlagen!';
            echo '<br>Hinweis: nach Systemstart ist diese Funktion etwa 10 Minuten blockiert.';
            echo '<hr>' . $hdn->getHostLink();
        } else {
            echo '<br>Starte neu... ';
            echo '<br>Bitte 2 Minuten warten... ';
            echo '<hr>';
        }
        break;
}
?>