# Serveur SSE (Server-Sent Events)

Chaque client authentifié sur l'application se connecte au serveur SSE pour être mis à jour des modifications de la base de données.

Lors de sa connexion, le client souscrit à zéro, un ou plusieurs événements. Chaque modification de la base de données associée à cet événement sera transmise au client par le serveur SSE.  
Dans tous les cas (même en cas de connexion avec aucune souscription), le client sera notifié des modifications apportées au compte utilisateur actuellement authentifié sur ce client.

Toutes les informations nécessaires au traitement des modifications par le client sont envoyées dans chaque notification (voir [plus bas](#notifications-de-lapi)). De cette manière, le client n'a (en général) pas besoin d'effectuer de requête à l'API pour récupérer les informations mises à jour.

## Architecture

Le serveur est en réalité composé de 2 serveurs HTTP écoutant sur 2 ports différents :

- un serveur gérant les connexions clients
- un serveur recevant les notifications de modification de la base de données

### Serveur "clients"

Le serveur "clients" reçoit les connexions des clients et, pour chaque connexion, si elle correspond à un utilisateur authentifié (= session active ou clé API valide), l'enregistre dans un tableau des connexions actives.

La vérification de la session/clé API s'effectue par un appel à la base Redis.

### Serveur "base de données"

Le serveur "base de données" reçoit les notifications de l'API en cas de mise à jour de la base de données.

Chaque notification est transmise aux connexions actives ayant souscrit à l'événement associé.

## Notifications de l'API

Les notifications reçue de l'API sont un object JSON au format suivant :

```ts
{
  /**
   * Nom de l'événement.
   */
  name: string;

  /**
   * Type de l'événement (update, create, etc.).
   */
  type: string;

  /**
   * Id de la ressource modifiée.
   */
  id: number | string;

  /**
   * Données de la ressource modifiée.
   */
  data?: any;

  /**
   * Identifiant unique de la connexion SSE associée à la fenêtre du client.
   */
  origin?: string | null;
}
```

## Événements envoyés aux clients

Le serveur envoie les événements suivants aux clients :

```
event: db
data: ${body}
```

`body` est la notification reçue de l'API, transmise telle quelle (format JSON).
