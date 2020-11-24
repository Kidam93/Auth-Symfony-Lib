<?php

// IN YOUR CLASS JUST USE THIS :
// $reinitialisation = new Reinitialisation($this->requestStack, $this->em);
// FOR REINIT URL
// $reinitialisation->reinitPass();
// FOR URL CONFIRMED
// $reinitialisation->isConfirmed($id, $token, $this->session);
// FOR CHANGE MDP
// $reinitialisation->changeMdp($this->session);

namespace Lib\auth;

use DateTime;
use PDO;
use Lib\auth\Config;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class Reinitialisation extends Config{

    private $requestStack;

    private $em;
    
    public function __construct(RequestStack $requestStack, 
                                EntityManagerInterface $em){
        $this->requestStack = $requestStack;
        $this->em = $em;
    }

    public function submitValid(){
        $request = $this->requestStack->getCurrentRequest()->request;
        $email = $request->get('email');
        $errors = [];
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $errors['email'] = "Votre email est incorrecte";
        }
        return $errors;
    }

    public function reinitPass(){
        $request = $this->requestStack->getCurrentRequest()->request;
        $email = $request->get('email');
        $submitValid = $this->submitValid();
        if(empty($submitValid)){
            $data = $this->SQLSearchUserEmail($email);
            $idBDD = (int)$data[0];
            $token = $this->generateToken();
            $this->SQLUpdateTokenUser($idBDD, $token);
            $this->sendMailReinitialisation($idBDD, $token);
        }
    }

    public function isConfirmed($id, $token, $session){
        // VERIFY ID AND TOKEN
        $data = $this->SQLConfirmedUser($id);
        $idBDD = (int)$data[0];
        $tokenBDD = (string)$data[6];
        if((int)$id === $idBDD && (string)$token === $tokenBDD){
            // $this->SQLResetTokenUser($idBDD);
            $session->set('user_id', $idBDD);
            $session->set('user_token', $tokenBDD);
            header('Location: '.self::LINK_REINIT_FORM);
            exit();
        }else{
            // expiration (durée 2h)
            header('Location: '.self::LINK_REDIRECT);
            exit();
        }
    }

    public function changeMdp($session){
        $userId = (int)$session->get('user_id');
        $userToken = (string)$session->get('user_token');
        $data = $this->SQLConfirmedUserToken($userId, $userToken);
        if(!empty($data)){
            $formData = $this->submitValidReinitMDP();
            if(empty($formData)){
                $request = $this->requestStack->getCurrentRequest()->request;
                $password = password_hash($request->get('password'), PASSWORD_BCRYPT);
                $this->SQLUpdatedUserMDP((int)$data[0], $password);
                $this->SQLResetTokenUser((int)$data[0]);
                header('Location: '.self::LINK_HOME);
                exit();
            }
        }else{
            header('Location: '.self::LINK_REDIRECT);
            exit();
        }
    }

    private function submitValidReinitMDP(){
        $request = $this->requestStack->getCurrentRequest()->request;
        $password = $request->get('password');
        $confirmed = $request->get('confirmed');
        $errors = [];
        if(strlen($password) <= self::SIZE_PASSWORD){
            $errors['password'] = "Votre mot de passe est trop court";
        }
        if($password !== $confirmed){
            $errors['confirmed'] = "Les mots de passe ne correspondent pas";
        }
        return $errors;
    }

    private function generateToken(){
        $alphabet = "azertyuiopqsdfghjklmwxcvbnAZERTYUIOPQSDFGHJKLMWXCVBN0123456789";
        $repeat = str_repeat($alphabet, self::TOKEN_SIZE);
        $shuffle = str_shuffle($repeat);
        return substr($shuffle, 0, self::TOKEN_SIZE);
    }

    private function sendMailReinitialisation($id, $token){
        $to      = $this->requestStack->getCurrentRequest()->request->get('email');
        $subject = 'Inscription';
        $message = 'Afin de reinitialiser votre mot de passe veuillez cliquer sur ce lien: '.
                    self::LINK_REINIT.
                    '-'.
                    $id.
                    '-'.
                    $token;
        return mail($to, $subject, $message);
    }

    private function SQLSearchUserEmail($email){
        $dbh = new PDO('mysql:host='.self::DB_HOST.
                        ';port='.self::DB_PORT.
                        ';dbname='.self::DB_NAME, self::DB_USER, self::DB_PASSWORD);
        $stmt = $dbh->prepare("SELECT * 
                                FROM user 
                                WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    private function SQLUpdateTokenUser($id, $token){
        $date = (new DateTime())->format('Y-m-d H:i:s');
        $dbh = new PDO('mysql:host='.self::DB_HOST.
                        ';port='.self::DB_PORT.
                        ';dbname='.self::DB_NAME, self::DB_USER, self::DB_PASSWORD);
        $stmt = $dbh->prepare("UPDATE User 
                                SET token = ?,
                                created_token = ?
                                WHERE id = ?");
        return $stmt->execute([$token, $date, $id]);
    }

    private function SQLResetTokenUser($id){
        $dbh = new PDO('mysql:host='.self::DB_HOST.
                        ';port='.self::DB_PORT.
                        ';dbname='.self::DB_NAME, self::DB_USER, self::DB_PASSWORD);
        $stmt = $dbh->prepare("UPDATE User 
                                SET token = NULL,
                                created_token = NULL
                                WHERE id = ?");
        return $stmt->execute([$id]);
    }

    private function SQLConfirmedUser($id){
        $dbh = new PDO('mysql:host='.self::DB_HOST.
                        ';port='.self::DB_PORT.
                        ';dbname='.self::DB_NAME, self::DB_USER, self::DB_PASSWORD);
        $stmt = $dbh->prepare("SELECT * 
                                FROM user 
                                WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private function SQLConfirmedUserToken($id, $token){
        $dbh = new PDO('mysql:host='.self::DB_HOST.
                        ';port='.self::DB_PORT.
                        ';dbname='.self::DB_NAME, self::DB_USER, self::DB_PASSWORD);
        $stmt = $dbh->prepare("SELECT * 
                                FROM user 
                                WHERE (id = ? AND token = ?)");
        $stmt->execute([$id, $token]);
        return $stmt->fetch();
    }

    private function SQLUpdatedUserMDP($id, $password){
        $dbh = new PDO('mysql:host='.self::DB_HOST.
                        ';port='.self::DB_PORT.
                        ';dbname='.self::DB_NAME, self::DB_USER, self::DB_PASSWORD);
        $stmt = $dbh->prepare("UPDATE User 
                                SET password = ?
                                WHERE id = ?");
        return $stmt->execute([$password, $id]);
    }
}