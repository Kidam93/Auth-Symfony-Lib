<?php

// IN YOUR CLASS JUST USE THIS :
// $inscription = new Inscription($this->requestStack, $this->em);
// FOR INSCRIPTION
// $inscription->isCorrectly();
// FOR CONFIRMATION
// $inscription->isConfirmed($id, $token, $this->session);

namespace Lib\auth;

use PDO;
use DateTime;
use App\Entity\User;
use Lib\auth\Config;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class Inscription extends Config{

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
        $lastname = $request->get('lastname');
        $firstname = $request->get('firstname');
        $email = $request->get('email');
        $password = $request->get('password');
        $confirmed = $request->get('confirmed');
        $errors = [];
        if(strlen($pseudo) <= self::SIZE_PSEUDO){
            $errors['pseudo'] = "Votre pseudo est trop court";
            // Ce pseudo existe deja !
            // IF
        }
        if(strlen($lastname) <= self::SIZE_LASTNAME){
            $errors['lastname'] = "Votre nom est trop court";
        }
        if(strlen($firstname) <= self::SIZE_FIRSTNAME){
            $errors['firstname'] = "Votre prenom est trop court";
        }
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $errors['email'] = "Votre email est incorrecte";
        }
        if(strlen($password) <= self::SIZE_PASSWORD){
            $errors['password'] = "Votre mot de passe est trop court";
        }
        if($password !== $confirmed){
            $errors['confirmed'] = "Les mots de passe ne correspondent pas";
        }
        return $errors;
    }

    public function isCorrectly(){
        $request = $this->requestStack->getCurrentRequest()->request;
        $password = password_hash($request->get('password'), PASSWORD_BCRYPT);
        $token = $this->generateToken();
        $submitValid = $this->submitValid();
        if(empty($submitValid)){
            // REGISTER IN BDD
            $user = new User();
            $user->setPseudo($request->get('pseudo'))
                ->setLastname($request->get('lastname'))
                ->setFirstname($request->get('firstname'))
                ->setEmail($request->get('email'))
                ->setPassword($password)
                ->setToken($token)
                ->setCreated(new DateTime())
                ->setCreatedToken(new DateTime());
            $this->em->persist($user);
            $this->em->flush();
            // SEND MAIL WITH ID AND TOKEN
            $this->sendMailInscription($user->getId(), $token);
        }else{
            return $submitValid;
        }
    }

    public function isConfirmed($id, $token, $session){
        // VERIFY ID AND TOKEN
        $data = $this->SQLConfirmedUser($id);
        $idBDD = (int)$data[0];
        $tokenBDD = (string)$data[6];
        if((int)$id === $idBDD && (string)$token === $tokenBDD){
            $this->SQLUpdateTokenUser($idBDD);
            $session->set('user_id', $idBDD);
            header('Location: '.self::LINK_HOME);
            exit();
        }else{
            // expiration (durée 2h)
            header('Location: '.self::LINK_REDIRECT);
            exit();
        }
    }

    private function generateToken(){
        $alphabet = "azertyuiopqsdfghjklmwxcvbnAZERTYUIOPQSDFGHJKLMWXCVBN0123456789";
        $repeat = str_repeat($alphabet, self::TOKEN_SIZE);
        $shuffle = str_shuffle($repeat);
        return substr($shuffle, 0, self::TOKEN_SIZE);
    }

    private function sendMailInscription($id, $token){
        $to      = $this->requestStack->getCurrentRequest()->request->get('email');
        $subject = 'Inscription';
        $message = 'Afin de valider votre compte veuillez cliquer sur ce lien: '.
                    self::LINK_AUTHORIZED.
                    '-'.
                    $id.
                    '-'.
                    $token;
        return mail($to, $subject, $message);
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

    private function SQLUpdateTokenUser($id){
        $dbh = new PDO('mysql:host='.self::DB_HOST.
                        ';port='.self::DB_PORT.
                        ';dbname='.self::DB_NAME, self::DB_USER, self::DB_PASSWORD);
        $stmt = $dbh->prepare("UPDATE User 
                                SET token = NULL,
                                created_token = NULL
                                WHERE id = ?");
        return $stmt->execute([$id]);
    }
}