<?php

class gamelist extends Controller {

    public function index () {
        App()->redirect('gamelist/latest');
    }

    public function latest () {
        $view = new Template('gamelist');
        $view->load_data('list', Game::latest());
        $view->render();
    }

    public function server ($serverid, $page = false) {
        $page = ($page ? (int) $page : 1);
        $games = Game::gamelist($serverid, $page);
        $view = new Template('gamelist');
        $view->load_data('list', $games['page']);
        $view->load_data('pagination',
            self::pagination(
                $page,
                $games['total'],
                App()->site_url("gamelist/server/" . (int)$serverid)
            )
        );
        $view->render();
    }

    public static function pagination ($page, $count, $baseurl) {
        $buttons = array();

        if ($page > 1) {
            $pages[] = $page - 1;
        }
        $show = 4;
        $max = ceil($count / Game::$limit);
        $first = floor($page - $show / 2);
        $first = ($first < 1 ? 1 : $first);
        $last = ceil($page + $show / 2);
        $last = ($last > $max ? $max : $last);

        $pag = new Template('pagination');

        $pag->load_data(array(
            'baseurl' => $baseurl,
            'current' => $page,
            'max' => $max,
            'pages' => range($first, $last)
        ));

        return $pag->render(true);
    }

}