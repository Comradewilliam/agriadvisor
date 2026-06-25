<?php
namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller {
    public function index() {
        $db = \App\Core\Database::getInstance()->getConnection();
        $cmsData = $db->query("SELECT * FROM cms_content")->fetchAll();
        $cms = [];
        foreach ($cmsData as $item) {
            $cms[$item['key_name']] = $item['content_value'];
        }
        $this->view('home', ['cms' => $cms], null);
    }
}
