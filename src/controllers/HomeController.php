<?php

class HomeController {

  function index($req, $res) {
    $res->send("<h1>Olá API!</h1>");
  }

}