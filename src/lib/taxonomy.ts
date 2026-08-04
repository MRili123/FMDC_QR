/**
 * Les libellés vivent dans les fichiers de messages (fr/ar) : ici on ne garde que
 * les clés stables, qui sont ce qui est écrit en base et exploité en statistiques.
 */

export const CATEGORIES = [
  "achat_magasin",
  "achat_internet",
  "telecom",
  "banque_assurance",
  "eau_energie",
  "transport_livraison",
  "sante",
  "logement",
  "education",
  "tourisme_restauration",
  "service_public",
  "autre",
] as const;

export const MOTIFS = [
  "non_recu",
  "defectueux",
  "prix_errone",
  "refus_remboursement",
  "garantie_refusee",
  "mauvaise_qualite",
  "tromperie",
  "securite_menacee",
  "donnees_personnelles",
  "autre",
] as const;

export const RESULTATS = [
  "remboursement",
  "remplacement",
  "livraison",
  "annulation",
  "correction_facture",
  "arret_pratique",
  "conseil_juridique",
  "controle_alerte",
] as const;

export const REGIONS = [
  "tanger_tetouan_al_hoceima",
  "oriental",
  "fes_meknes",
  "rabat_sale_kenitra",
  "beni_mellal_khenifra",
  "casablanca_settat",
  "marrakech_safi",
  "draa_tafilalet",
  "souss_massa",
  "guelmim_oued_noun",
  "laayoune_sakia_el_hamra",
  "dakhla_oued_ed_dahab",
] as const;

export const SECTEURS = [
  "commerce",
  "telecom",
  "banque_assurance",
  "ecommerce",
  "transport",
  "sante",
  "eau_energie",
  "tourisme",
  "enseignement",
] as const;

export type Categorie = (typeof CATEGORIES)[number];
export type Motif = (typeof MOTIFS)[number];
export type Resultat = (typeof RESULTATS)[number];
export type Region = (typeof REGIONS)[number];
export type Secteur = (typeof SECTEURS)[number];

/** Emoji par catégorie : les cartes du §7 doivent être reconnaissables sans lire. */
export const CATEGORY_ICONS: Record<Categorie, string> = {
  achat_magasin: "🛒",
  achat_internet: "📦",
  telecom: "📱",
  banque_assurance: "🏦",
  eau_energie: "💡",
  transport_livraison: "🚚",
  sante: "🏥",
  logement: "🏠",
  education: "🎓",
  tourisme_restauration: "🍽️",
  service_public: "🏛️",
  autre: "❓",
};

export const DOSSIER_STATUSES = [
  "RECU",
  "A_VERIFIER",
  "INFOS_DEMANDEES",
  "ORIENTE_ASSOCIATION",
  "TRANSMIS_PROFESSIONNEL",
  "EN_MEDIATION",
  "TRANSMIS_AUTORITE",
  "RESOLU",
  "CLOTURE",
  "SIGNALEMENT_COLLECTIF",
] as const;

/** Un dossier dans un de ces états n'attend plus d'action de la FMDC. */
export const TERMINAL_STATUSES = ["RESOLU", "CLOTURE"] as const;
