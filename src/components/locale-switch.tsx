"use client";

import { Suspense } from "react";
import Link from "next/link";
import { usePathname, useSearchParams } from "next/navigation";
import { useLocale } from "@/i18n/client";
import { LOCALES, type Locale } from "@/i18n/config";

function useOtherLocale(): { other: Locale; path: string } {
  const { locale } = useLocale();
  const pathname = usePathname();
  const other = LOCALES.find((value) => value !== locale) ?? "fr";
  const segments = pathname.split("/");
  segments[1] = other;
  return { other, path: segments.join("/") || `/${other}` };
}

const CLASS =
  "rounded border border-line px-2 py-2 font-medium hover:bg-primary-soft";

/**
 * `useSearchParams` force le rendu client de tout ce qui l'englobe : isolé ici
 * derrière Suspense, il ne coûte que ce lien au lieu de toute la page.
 */
function WithQuery() {
  const { t } = useLocale();
  const { other, path } = useOtherLocale();
  // La page de suivi porte son jeton en query : le perdre au changement de
  // langue afficherait « dossier introuvable ».
  const query = useSearchParams().toString();

  return (
    <Link href={`${path}${query ? `?${query}` : ""}`} lang={other} className={CLASS}>
      {t("nav.language")}
    </Link>
  );
}

function Fallback() {
  const { t } = useLocale();
  const { other, path } = useOtherLocale();
  return (
    <Link href={path} lang={other} className={CLASS}>
      {t("nav.language")}
    </Link>
  );
}

export function LocaleSwitch() {
  return (
    <Suspense fallback={<Fallback />}>
      <WithQuery />
    </Suspense>
  );
}
