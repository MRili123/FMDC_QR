/**
 * Capacités reportées après le MVP (note de cadrage : IA §8, WhatsApp §5).
 * Chaque interface a ici une implémentation neutre ; brancher un vrai
 * prestataire ne doit toucher que ce fichier.
 */

export interface SmsProvider {
  send(to: string, message: string): Promise<void>;
}

export interface TranscriptionProvider {
  transcribe(storageKey: string, mimeType: string): Promise<string | null>;
}

export interface ClassificationProvider {
  classify(input: {
    description: string;
    categorie: string;
    motif: string;
  }): Promise<{ categorie: string; motif: string; confidence: number }>;
}

export interface OcrProvider {
  extract(storageKey: string): Promise<Record<string, string> | null>;
}

export interface AntivirusProvider {
  scan(storageKey: string): Promise<"SKIPPED" | "CLEAN" | "INFECTED">;
}

const consoleSms: SmsProvider = {
  async send(to, message) {
    console.info(`[sms:stub] -> ${to}: ${message}`);
  },
};

const noopTranscription: TranscriptionProvider = {
  async transcribe() {
    return null;
  },
};

/** Renvoie le choix du consommateur inchangé : c'est lui qui fait foi tant qu'aucun modèle n'est branché. */
const passthroughClassification: ClassificationProvider = {
  async classify(input) {
    return { categorie: input.categorie, motif: input.motif, confidence: 1 };
  },
};

const noopOcr: OcrProvider = {
  async extract() {
    return null;
  },
};

const noopAntivirus: AntivirusProvider = {
  async scan() {
    return "SKIPPED";
  },
};

export const providers = {
  sms: consoleSms,
  transcription: noopTranscription,
  classification: passthroughClassification,
  ocr: noopOcr,
  antivirus: noopAntivirus,
};
