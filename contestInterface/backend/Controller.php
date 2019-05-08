<?php

class Controller
{

    function __construct()
    {
        global $db;
        $this->db = $db;
    }
}
