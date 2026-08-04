export interface DraftAttachment {
  id: string;
  kind: string;
  originalName: string;
  mimeType: string;
}

export interface WizardDraft {
  draftId: string;
  categorie?: string;
  motif?: string;
  description?: string;
  professionnel?: string;
  resultatAttendu?: string;
  demarche?: "CONSEIL" | "SIGNALEMENT" | "RECLAMATION";
  attachments: DraftAttachment[];
  qrCode?: string;
  etablissement?: string;
  region?: string;
}

const STORAGE_KEY = "qrconso.draft.v1";

export function emptyDraft(): WizardDraft {
  return { draftId: crypto.randomUUID(), attachments: [] };
}

/**
 * Le brouillon vit dans localStorage : sur réseau faible (§6.2) une coupure ne
 * doit pas coûter au consommateur ce qu'il vient d'écrire.
 */
export function loadDraft(): WizardDraft {
  if (typeof window === "undefined") return emptyDraft();
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    if (!raw) return emptyDraft();
    const parsed = JSON.parse(raw) as WizardDraft;
    if (!parsed.draftId) return emptyDraft();
    return { ...parsed, attachments: parsed.attachments ?? [] };
  } catch {
    return emptyDraft();
  }
}

export function saveDraft(draft: WizardDraft): void {
  if (typeof window === "undefined") return;
  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(draft));
  } catch {
    // Quota plein ou mode privé : le parcours reste utilisable sans persistance.
  }
}

export function clearDraft(): void {
  if (typeof window === "undefined") return;
  window.localStorage.removeItem(STORAGE_KEY);
}
