<?php
namespace App\Core;

class Controller {
    protected function view($viewPath, $data = [], $layout = 'app') {
        require_once __DIR__ . '/../Helpers/Lang.php';
        \App\Helpers\Lang::init();

        extract($data);
        
        ob_start();
        require __DIR__ . '/../../views/' . $viewPath . '.php';
        $content = ob_get_clean();
        
        if ($layout) {
            require __DIR__ . '/../../views/layouts/' . $layout . '.php';
        } else {
            echo $content;
        }
    }
}
