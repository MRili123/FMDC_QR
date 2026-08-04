"use client";

import Link from "next/link";
import { useLocale } from "@/i18n/client";
import { LocaleSwitch } from "./locale-switch";

export function SiteHeader() {
  const { locale, t } = useLocale();

  return (
    <header className="no-print border-b border-line bg-surface">
      <div className="mx-auto flex max-w-3xl items-center gap-3 px-4 py-3">
        <Link href={`/${locale}`} className="flex items-center gap-2 font-semibold text-primary">
          <span aria-hidden className="text-xl">🛡️</span>
          <span className="text-sm leading-tight sm:text-base">{t("app.name")}</span>
        </Link>
        <nav className="ms-auto flex items-center gap-1 text-sm">
          <Link
            href={`/${locale}/suivi`}
            className="rounded px-2 py-2 text-muted hover:bg-primary-soft hover:text-primary"
          >
            {t("nav.track")}
          </Link>
          <LocaleSwitch />
        </nav>
      </div>
    </header>
  );
}
