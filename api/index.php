<?php

require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

/* parse functions */
$app->get('/teams', 'getTeams');
$app->post('/teams', 'addTeam');
$app->post('/players', 'addPlayers');
$app->get('/players', 'getPlayers');

/* chat functions */
//$app->get('chat', 'getChat');
$app->post('/chat', 'addChat');
$app->get('/status', 'getStatus');
$app->get('/chat/:state', 'updateChat');

$app->run();

/* Chat Functions */
function addChat() {
    $request = \Slim\Slim::getInstance()->request();
    $chat = json_decode($request->getBody());
    $sql = "INSERT INTO Chat (email, message) VALUES (:email, :message)";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);  
        $stmt->bindParam("email", $chat->email);
        $stmt->bindParam("message", $chat->message);
        $stmt->execute();
        $db = null;
        echo json_encode($chat); 
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
}

function getStatus() {
    $sql = "SELECT max(create_dt) as state FROM Chat";
    try {
        $db = getConnection();
        $stmt = $db->query($sql);  
        $status = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($status);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
}

function updateChat($state) {
    $request = \Slim\Slim::getInstance()->request();
    $chat = json_decode($request->getBody());
    //echo json_encode($state);
    $sql = "SELECT * FROM Chat WHERE create_dt > :state";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);  
        $stmt->bindParam("state", $state);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($messages); 
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
}
/* End Chat Functions */

/* NCAA.org Parse Functions */
function getTeams() {
    $url = "http://web1.ncaa.org/stats/StatsSrv/careersearch";
    $schools = array();
    $school = array();
    $dom = new DOMDocument('1.0');
    @$dom->loadHTMLFile($url);
    $sels = $dom->getElementsByTagName('select');
    $count = 0;
    foreach ($sels as $element) {
        $name = $element->getAttribute('name');
        if ($name == 'searchOrg') { //found list of schools
            while($element->hasChildNodes()){
                $opt = $element->removeChild($element->childNodes->item(0));
                //$name = $opt->nodeValue;
                $name = $opt->textContent;
                $id = $opt->getAttribute('value');
                if ($id != 'X') {
                    $school["id"] = $id;
                    $school["name"] = $name;
                    $schools[$count] = $school;
                    $count++;
                }
            }
        }
    }
    echo json_encode($schools);
}

function addTeam() {
    $request = \Slim\Slim::getInstance()->request();
    $team = json_decode($request->getBody());
    $sql = "INSERT INTO Teams (team_id, name) VALUES (:id, :name)";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);  
        $stmt->bindParam("id", $team->id);
        $stmt->bindParam("name", $team->name);
        $stmt->execute();
        $db = null;
        echo json_encode($team); 
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
}

function addPlayers() {
    $sql = "select * FROM Teams where team_id = 416";
    try {
        $db = getConnection();
        $stmt = $db->query($sql);  
        $teams = $stmt->fetchAll(PDO::FETCH_OBJ);
        $sql = "INSERT INTO Players (player_id, team_id, name, ppg, position) values (:player_id, :team_id, :name, :ppg, :position)";
        foreach ($teams as $team) {
            //$team_id = 416;
            $players = json_decode(getPlayers($team->team_id));
            foreach($players as $player){
                echo $player[1] . " : " . $player[2] . "<br/>";
                $stmt = $db->prepare($sql);
                $stmt->bindParam("player_id", intval($player[1]));
                $stmt->bindParam("team_id", intval($team_id));
                $stmt->bindParam("name", $player[2]);
                $stmt->bindParam("ppg", floatval($player[19]));
                $stmt->bindParam("position", $player[4]);
                $stmt->execute();
            }
            //$db = null;
            //echo $team->team_id . "<br/>";
        }
        $db = null;
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
}

function getPlayers($team_id) {
    $url = "http://stats.ncaa.org/team/stats";//?org_id=416&sport_year_ctl_id=11220
    //$team_id = 416;
    $args = array('sport_year_ctl_id' => 11220,  
        'org_id' => $team_id);

    $args_string = '';
    //url-ify the data for the GET
    foreach($args as $key=>$value) { $args_string .= $key.'='.$value.'&'; }
    $args_string = rtrim($args_string,'&');
    $full_url = $url.'?'.$args_string;

    //open connection
    $ch = curl_init();

    curl_setopt($ch,CURLOPT_URL,$url.'?'.$args_string);
    //curl_setopt($ch,CURLOPT_URL,$full_url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    $result = curl_exec($ch);
    //close connection
    curl_close($ch);

    $dom = new DOMDocument('1.0');
    @$dom->loadHTML($result);
    $xpath = new DomXPath($dom);
    $i = 0;
    // collect data
    $players = array();
    foreach ($xpath->query('//table[@id="stat_grid"]/tbody/tr') as $node) {
        $rowData = array();
        foreach ($xpath->query('td', $node) as $cell) {
            if ($i == 1) {
                foreach($xpath->query('a', $cell) as $link) {
                    parse_str($link->getAttribute('href'));
                    $rowData[] = $stats_player_seq;
                    //echo $cell->nodeValue . " : " . $link->getAttribute('href') . " : " . $stats_player_seq . "<br/>";
                    //$a->getAttribute('href')
                }
            }

            $rowData[] = $cell->nodeValue;

            $i++;
        }
        $i=0;
        $players[] = $rowData;
        //echo "id : " . $rowData[1] . " name : " . $rowData[2] . "Position : " . $rowData[4] . " PPG : " . $rowData[19] . "<br/>";
    }
    $json = json_encode($players);
    return str_replace('\u00a0', "",$json);
}
/* End Parse Functions */

function getConnection() {
    $dbhost="localhost";
    $dbuser="they4810";
    $dbpass="Brickey!2";
    $dbname="they4810_draft";
    $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);  
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbh;
}
?>
