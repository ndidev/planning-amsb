# Descriptif du planning (utilisation)


## Généralités

Le planning sert à gérer :
- les rendez-vous de camions (pour certains chargements vrac et tous les camions de bois)
- les escales de navires
- les affrètements de navires
- extraire les rendez-vous pour alimenter le registre d'affrètement routier

Pour les utilisateurs, le planning n'est accessible que via une authentification par login + mot de passe.

## Utilisation détaillée

### Authentification

La page d'accueil du planning propose une authentification à l'utilisateur.  
Si celui-ci a déjà activé son compte (c-à-d qu'il a déjà créé son mot de passe), il renseigne ses identifiants de façon classique.

Si l'utilisateur se connecte pour la prmeière fois, il renseigne son identifiant et laisse le mdp vide.  
Un nouvel écran l'invite alors à créer son mot de passe.  
Après la création du mot de passe, l'utilisateur revient à l'écran d'authentification classique.

Après une authentification réussie, l'utilisateur voit le menu des rubriques auxquelles il a accès.

En cas d'erreur d'authentification (mauvais login ou mdp), un message est affiché à l'utilisateur.  
L'utilisateur a un nombre maximal d'essais pour se connecter (avec un login correct). Ce nombre est actuellement fixé à 10 tentatives.
Au-délà du nombre maximal de tentatives avec un mauvais mdp, le compte est bloqué ; seul un administrateur peut le débloquer (voir [`Administration`](#Administration)).


### Administration

L'espace d'administration est réservé aux utilisateurs ayant le rôle d'administrateur.

Cet espace sert à gérer les comptes des utilisateurs :
- ajouter un compte
- désactiver un compte
- réinitialiser un compte
- définir les rôles de chaque utilisateur
- modifier un login ou le nom d'un utilisateur
- voir le statut et l'historique d'un compte

### Utilisateur

Chaque utilisateur a accès à une page basique lui permettant de modifier son nom et son mot de passe.  
L'accès à cette page se fait en cliquant sur le nom de l'utilisateur en bas du menu (présent sur chaque page).

### Planning bois

Cette rubrique sert à la gestion des camions chargeant et déchargeant des colis de bois.  
Il est possible d'y ajouter des rendez-vous (ainsi que les modifier/supprimer).

L'ajout de RDV se fait via un formulaire, ou par 

### Planning vrac



### Planning consignation



### Affrètement maritime



### Tiers



### Configuration


