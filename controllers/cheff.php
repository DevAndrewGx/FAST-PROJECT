<?php
class Cheff extends SessionController
{

    function __construct()
    {
        parent::__construct();
    }


    function render()
    {
        // $stats = $this->getStatistics();
        $this->view->render('cheff/index', []);
    }
}