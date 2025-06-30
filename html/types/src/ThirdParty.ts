/**
 * Tiers.
 */
export type Tiers = {
  /**
   * Identifiant du tiers.
   */
  id: number;

  /**
   * Nom abbrégé du tiers.
   */
  nom_court: string;

  /**
   * Nom complet du tiers.
   */
  nom_complet: string;

  /**
   * Adresse du tiers (ligne 1).
   */
  adresse_ligne_1: string;

  /**
   * Adresse du tiers (ligne 2).
   */
  adresse_ligne_2: string;

  /**
   * Code postal du tiers.
   */
  cp: string;

  /**
   * Ville du tiers.
   */
  ville: string;

  /**
   * Pays du tiers.
   */
  pays: string;

  /**
   * Numéro de téléphone du tiers.
   */
  telephone: string;

  /**
   * Commentaire du tiers.
   */
  commentaire: string;

  /**
   * Rôles du tiers.
   */
  roles: {
    /**
     * Le tiers est fournisseur bois.
     */
    bois_fournisseur: boolean;

    /**
     * Le tiers est client bois.
     */
    bois_client: boolean;

    /**
     * Le tiers est transporteur bois.
     */
    bois_transporteur: boolean;

    /**
     * Le tiers est affréteur bois.
     */
    bois_affreteur: boolean;

    /**
     * Le tiers est fournisseur vrac.
     */
    vrac_fournisseur: boolean;

    /**
     * Le tiers est client vrac.
     */
    vrac_client: boolean;

    /**
     * Le tiers est transporteur vrac.
     */
    vrac_transporteur: boolean;

    /**
     * Le tiers est armateur.
     */
    maritime_armateur: boolean;

    /**
     * Le tiers est affréteur maritime.
     */
    maritime_affreteur: boolean;

    /**
     * Le tiers est courtier maritime.
     */
    maritime_courtier: boolean;
  };

  /**
   * Le tiers n'est pas modifiable.
   */
  non_modifiable: boolean;

  /**
   * Le tiers représente l'agence.
   */
  lie_agence: boolean;

  /**
   * Nom du fichier logo du tiers
   * ou données du fichier nouvellement choisi.
   */
  logo?: string | { type: string; data: string };

  /**
   * Le tiers est activé.
   */
  actif: boolean;

  /**
   * Nombre de RDV du tiers.
   */
  nombre_rdv?: number;

  /**
   * Contacts associés au tiers.
   */
  contacts: Contact[];
};

/**
 * Contact associé à un tiers.
 */
export type Contact = {
  /**
   * Identifiant du contact.
   */
  id: number;

  /**
   * Nom du contact.
   */
  nom: string;

  /**
   * Email du contact.
   */
  email: string;

  /**
   * Téléphone du contact.
   */
  telephone: string;

  /**
   * Fonction du contact.
   */
  fonction: string;

  /**
   * Commentaires.
   */
  commentaire: string;

  /**
   * Indique si le contact est nouveau (ajouté dans le modal)
   */
  new?: boolean;
};
