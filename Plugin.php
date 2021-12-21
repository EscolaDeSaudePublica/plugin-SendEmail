<?php

namespace SendEmail;

use MapasCulturais\App;

class Plugin extends \MapasCulturais\Plugin
{


    public function __construct(array $config = [])
    {
        $app = App::i();


        $config += [];

        parent::__construct($config);
    }

    public function _init()
    {
        $app = App::i();

        $plugin = $this;
        $config = $plugin->_config;
        $this->registerAssets();

    }

    public function register(){
        
        $app = App::i();
        

        $app->registerController('sendemail', 'SendEmail\Controller');    
    }

    public function registerAssets(){
        $app = App::i();
        $app->view->enqueueStyle('app', 'sendemail', 'sendemail/style.css');
    }
}