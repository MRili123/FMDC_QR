import { locale as localeParam } from "next/root-params";
import { DEFAULT_LOCALE, isLocale, type Locale } from "./config";
import { getTranslator, type Translator } from "./translate";

export async function currentLocale(): Promise<Locale> {
  const value = await localeParam();
  return value && isLocale(value) ? value : DEFAULT_LOCALE;
}

/** Point d'entrée unique des Server Components pour la langue et les libellés. */
export async function serverT(): Promise<{ locale: Locale; t: Translator }> {
  const locale = await currentLocale();
  return { locale, t: getTranslator(locale) };
}
