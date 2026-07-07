export const LOCALES = ["en", "sw", "fr"] as const;
export type Locale = (typeof LOCALES)[number];

export const LOCALE_LABELS: Record<Locale, string> = {
  en: "English",
  sw: "Kiswahili",
  fr: "Français",
};

const en = {
  home: "Home",
  aboutUs: "About Us",
  ourPurpose: "Our Purpose",
  ourMission: "Our Mission",
  whatWeDo: "What We Do",
  coverageArea: "Coverage Area",
  heatmap: "Heatmap",
  gallery: "Gallery",
  news: "News",
  podcasts: "Podcasts",
  videos: "Videos",
  documents: "Documents",
  login: "Login",
  getInvolved: "Get Involved",
  language: "Language",
  countryKenya: "Kenya",
  countryEthiopia: "Ethiopia",
  countryDrc: "DR Congo",
};

export type TranslationKey = keyof typeof en;

const sw: Record<TranslationKey, string> = {
  home: "Nyumbani",
  aboutUs: "Kuhusu Sisi",
  ourPurpose: "Kusudi Letu",
  ourMission: "Dhamira Yetu",
  whatWeDo: "Tunachofanya",
  coverageArea: "Eneo la Huduma",
  heatmap: "Ramani ya Joto",
  gallery: "Picha",
  news: "Habari",
  podcasts: "Podikasti",
  videos: "Video",
  documents: "Nyaraka",
  login: "Ingia",
  getInvolved: "Shiriki",
  language: "Lugha",
  countryKenya: "Kenya",
  countryEthiopia: "Ethiopia",
  countryDrc: "DRC",
};

const fr: Record<TranslationKey, string> = {
  home: "Accueil",
  aboutUs: "À propos",
  ourPurpose: "Notre objectif",
  ourMission: "Notre mission",
  whatWeDo: "Ce que nous faisons",
  coverageArea: "Zone de couverture",
  heatmap: "Carte thermique",
  gallery: "Galerie",
  news: "Actualités",
  podcasts: "Podcasts",
  videos: "Vidéos",
  documents: "Documents",
  login: "Connexion",
  getInvolved: "S'impliquer",
  language: "Langue",
  countryKenya: "Kenya",
  countryEthiopia: "Éthiopie",
  countryDrc: "RD Congo",
};

export const translations: Record<Locale, Record<TranslationKey, string>> = {
  en,
  sw,
  fr,
};
