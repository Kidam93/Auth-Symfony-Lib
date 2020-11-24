IN YOUR CLASS/CONTROLER JUST USE THIS :

FOR INSCRIPTION
(class: User)
 - $inscription = new Inscription($this->requestStack, $this->em);
 - $inscription->isCorrectly();

FOR CONFIRMATION
(url: /confirmed-{id}-{token})
 - $inscription = new Inscription($this->requestStack, $this->em);
 - $inscription->isConfirmed($id, $token, $this->session);

FOR CONNEXION
 - $connexion = new Connexion($this->requestStack, $this->em);
 - $connexion->connectingUser($this->session);

FOR REINIT URL
 - $reinitialisation = new Reinitialisation($this->requestStack, $this->em);
 - $reinitialisation->reinitPass();

FOR URL CONFIRMED
(url: /reinitialisation-{id}-{token})
 - $reinitialisation = new Reinitialisation($this->requestStack, $this->em);
 - $reinitialisation->isConfirmed($id, $token, $this->session);

FOR CHANGE MDP
 - $reinitialisation = new Reinitialisation($this->requestStack, $this->em);
 - $reinitialisation->changeMdp($this->session);