"use client";

import { createContext, useContext, useMemo, type ReactNode } from "react";
import { createTranslator, getMessages, type Translator } from "./translate";
import type { Locale } from "./config";

const LocaleContext = createContext<{ locale: Locale; t: Translator } | null>(null);

export function LocaleProvider({ locale, children }: { locale: Locale; children: ReactNode }) {
  const value = useMemo(
    () => ({ locale, t: createTranslator(getMessages(locale)) }),
    [locale],
  );
  return <LocaleContext.Provider value={value}>{children}</LocaleContext.Provider>;
}

export function useLocale() {
  const context = useContext(LocaleContext);
  if (!context) throw new Error("useLocale must be used inside LocaleProvider");
  return context;
}
