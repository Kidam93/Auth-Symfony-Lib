<?php

// IN YOUR CLASS JUST USE THIS :
// $connexion = new Connexion($this->requestStack, $this->em);
// FOR CONNEXION
// $connexion->connectingUser($this->session);

namespace Lib\auth;

use PDO;
use Lib\auth\Config;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class Connexion extends Config{

    private $requestStack;

    private $em;
    
    public function __construct(RequestStack $requestStack, 
                                EntityManagerInterface $em){
        $this->requestStack = $requestStack;
        $this->em = $em;
    }

    public function submitValid(){
        $request = $this->requestStack->getCurrentRequest()->request;
        $pseudo = $request->get('pseudo');
        $email = $request->get('email');
        $password = $request->get('password');
        $errors = [];
        if(strlen($pseudo) <= self::SIZE_PSEUDO){
            $errors['pseudo'] = "Votre identifiant est invalide";
        }
        if(strlen($password) <= self::SIZE_PASSWORD){
            $errors['password'] = "Votre mot de passe est trop court";
        }
        return $errors;
    }

    public function connectingUser($session){
        $request = $this->requestStack->getCurrentRequest()->request;
        $pseudo = $request->get('pseudo');
        $email = $request->get('email');
        $password = $request->get('password');
        $submitValid = $this->submitValid();
        if(empty($submitValid)){
            $resultUser = $this->SQLSearchUser($pseudo, $email);
            $idBDD = $resultUser[0];
            $pseudoBDD = $resultUser[1];
            $emailBDD = $resultUser[4];
            // connecting with email
            $passwordBDD = $resultUser[5];
            $hash = password_verify($password, $passwordBDD);
            if($pseudo === $pseudoBDD && $hash === true){
                $session->set('user_id', $idBDD);
                header('Location: '.self::LINK_HOME);
                exit();
            }else{
                header('Location: '.self::LINK_REDIRECT);
                exit();
            }
        }
    }

    private function SQLSearchUser($pseudo, $email = null){
        $dbh = new PDO('mysql:host='.self::DB_HOST.
                        ';port='.self::DB_PORT.
                        ';dbname='.self::DB_NAME, self::DB_USER, self::DB_PASSWORD);
        $stmt = $dbh->prepare("SELECT * 
                                FROM user 
                                WHERE pseudo = ?
                                OR email = ?");
        $stmt->execute([$pseudo, $email]);
        return $stmt->fetch();
    }
}