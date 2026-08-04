import type { Metadata, Viewport } from "next";
import { notFound } from "next/navigation";
import "../globals.css";
import { LOCALES, direction, isLocale } from "@/i18n/config";
import { serverT } from "@/i18n/server";
import { locale as localeParam } from "next/root-params";
import { LocaleProvider } from "@/i18n/client";
import { SiteHeader } from "@/components/site-header";
import { ServiceWorker } from "@/components/service-worker";

export function generateStaticParams() {
  return LOCALES.map((value) => ({ locale: value }));
}

export async function generateMetadata(): Promise<Metadata> {
  const { locale, t } = await serverT();
  return {
    title: `${t("app.name")} — ${t("app.org")}`,
    description: t("home.subtitle"),
    manifest: "/manifest.webmanifest",
    appleWebApp: { capable: true, title: t("app.name"), statusBarStyle: "default" },
  };
}

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  themeColor: "#1d4e89",
};

export default async function RootLayout({ children }: LayoutProps<"/[locale]">) {
  const value = await localeParam();
  if (!value || !isLocale(value)) notFound();

  return (
    <html lang={value} dir={direction(value)} className="h-full antialiased">
      <body className="min-h-full flex flex-col">
        <LocaleProvider locale={value}>
          <SiteHeader />
          <main className="flex-1">{children}</main>
          <ServiceWorker />
        </LocaleProvider>
      </body>
    </html>
  );
}
