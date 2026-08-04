import fr from "./messages/fr.json";
import ar from "./messages/ar.json";
import type { Locale } from "./config";

type Messages = typeof fr;

const BUNDLES: Record<Locale, Messages> = { fr, ar: ar as Messages };

export type Translator = (key: string, params?: Record<string, string | number>) => string;

export function getMessages(locale: Locale): Messages {
  return BUNDLES[locale];
}

function lookup(messages: Messages, key: string): string | undefined {
  let node: unknown = messages;
  for (const part of key.split(".")) {
    if (typeof node !== "object" || node === null) return undefined;
    node = (node as Record<string, unknown>)[part];
  }
  return typeof node === "string" ? node : undefined;
}

/**
 * Renvoie la clé elle-même lorsqu'une traduction manque : une clé visible à
 * l'écran se repère immédiatement en relecture, là où un texte vide passerait.
 */
export function createTranslator(messages: Messages): Translator {
  return (key, params) => {
    const template = lookup(messages, key);
    if (template === undefined) return key;
    if (!params) return template;
    return template.replace(/\{(\w+)\}/g, (match, name: string) =>
      name in params ? String(params[name]) : match,
    );
  };
}

export function getTranslator(locale: Locale): Translator {
  return createTranslator(getMessages(locale));
}
