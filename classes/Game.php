<?php

class Game {
    public static $db;
    public static $terrs = array("NA", "SA", "EU", "RU", "AS", "AF");

    public static $limit = 16;

    public static function latest () {
        $sql = "
            SELECT s.id           AS serverid,
                   s.name         AS servername,
                   g.*
            FROM   (SELECT server,
                           Max(id) AS id
                    FROM   game
                    GROUP  BY server) AS latest
                   INNER JOIN server s
                           ON latest.server = s.id
                   INNER JOIN game AS g
                           ON g.id = latest.id
            ORDER  BY g.starttime DESC";
        $sth = Database::get_instance()->query($sql);
        return $sth->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    public static function gamelist ($server, $page, $limit = false) {
        if (!self::$db) {
            self::$db = Database::get_instance();
        }

        $limit = ($limit === false ? self::$limit : (int) $limit);
        $offset = ($page - 1) * self::$limit;

        $sql_q = "from server as s inner join game as g on g.server=s.id where s.id = :sid order by g.starttime desc";
        $sql_count = "select count(*) as cnt " . $sql_q;
        $sql_page = "select g.* " . $sql_q . " limit :offset, :limit";

        $sthc = self::$db->prepare($sql_count);
        $sthc->execute(array(':sid' => $server));
        $total = (int) $sthc->fetch()->cnt;

        $sth = self::$db->prepare($sql_page);
        $sth->bindParam(':sid', $server, PDO::PARAM_INT);
        $sth->bindParam(':offset', $offset, PDO::PARAM_INT);
        $sth->bindParam(':limit', $limit, PDO::PARAM_INT);
        $sth->execute();

        return array('total' => $total, 'page' => $sth->fetchAll(PDO::FETCH_CLASS, __CLASS__));
    }

    public static function getgame ($gameid) {
        self::dbconnect();
        $sql = "select * from game where id = :id";
        $sth = self::$db->prepare($sql);
        $sth->execute(array(':id' => (int)$gameid));
        return $sth->fetchObject( __CLASS__ );
    }

    private static function dbconnect () {
        if (!self::$db) {
            self::$db = Database::get_instance();
        }
        return self::$db;
    }

    private $id;
    private $starttime;
    private $endtime;
    private $server;
    private $eventlog_filename;
    private $eventlog_md5;
    private $dcrec_filename;
    private $dcrec_md5;
    private $players;

    public function __construct () {
        self::dbconnect();
        $this->players = $this->getPlayers();
        $this->link = App()->site_url("singlegame/view/" . $this->id);
        $this->title = Navigation::$servers[$this->server];
        $this->title = preg_replace("/^.Muricon[\s:]*/", "", $this->title);
        $timeformat = "H:i:s";
        $timediff = strtotime($this->endtime) - strtotime($this->starttime);
        $this->duration = gmdate($timeformat, $timediff);

        if ($this->dcrec_readable()) {
            $this->dcrec_href = App()->site_url('singlegame/dcrec/'.$this->id);
        } else {
            $this->dcrec_href = false;
        }
    }

    public function dcrec_readable () {
        return is_readable($this->dcrec_filename) || is_readable($this->dcrec_filename.".gz");
    }

    public function get_dcrec () {
        return $this->dcrec_filename;
    }

    private function getPlayers () {
        $sql = "select * from player where game = :g order by score desc";
        $sth = self::$db->prepare($sql);
        $sth->execute(array(':g' => $this->id));
        return $sth->fetchAll();
    }

    public function render () {
        (new Template('game', get_object_vars($this)))->render();
    }

    //public function __toString () {
    //    return $this->render();
    //}

}