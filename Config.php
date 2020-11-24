<?php 

namespace Lib\auth;

class Config{
    
    // BDD
    const DB_HOST = "localhost";
    const DB_PORT = "3308";
    const DB_NAME = "ecommercesymfonyproject";
    const DB_USER = "root";
    const DB_PASSWORD = "";
    // VALIDATION
    const SIZE_PSEUDO = 3;
    const SIZE_LASTNAME= 3;
    const SIZE_FIRSTNAME = 3;
    const SIZE_PASSWORD = 8;
    const TOKEN_SIZE = 60;
    // URL
    const LINK_HOME = "http://127.0.0.1:8000";
    const LINK_AUTHORIZED = "http://127.0.0.1:8000/confirmed";
    const LINK_REDIRECT = "http://127.0.0.1:8000/connexion";
    const LINK_REINIT = "http://127.0.0.1:8000/reinitialisation";
    const LINK_REINIT_FORM = "http://127.0.0.1:8000/reinitmdp";
}