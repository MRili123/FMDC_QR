import Link from "next/link";
import { serverT } from "@/i18n/server";

export default async function HomePage() {
  const { locale, t } = await serverT();

  const steps = [t("home.how1"), t("home.how2"), t("home.how3")];
  const reassurances = [t("home.reassure1"), t("home.reassure2"), t("home.reassure3")];

  return (
    <div className="mx-auto max-w-3xl px-4 py-8">
      <section className="rounded-2xl bg-surface p-6 shadow-sm ring-1 ring-line">
        <p className="text-sm font-medium text-primary">{t("app.org")}</p>
        <h1 className="mt-2 text-2xl font-bold sm:text-3xl">{t("home.title")}</h1>
        <p className="mt-3 text-muted">{t("home.subtitle")}</p>

        <div className="mt-6 flex flex-col gap-3 sm:flex-row">
          <Link
            href={`/${locale}/reclamation`}
            className="flex items-center justify-center rounded-xl bg-primary px-6 py-4 text-center text-lg font-semibold text-white hover:bg-primary-hover"
          >
            {t("home.cta")}
          </Link>
          <Link
            href={`/${locale}/suivi`}
            className="flex items-center justify-center rounded-xl border-2 border-primary px-6 py-4 text-center font-semibold text-primary hover:bg-primary-soft"
          >
            {t("home.ctaTrack")}
          </Link>
        </div>

        <ul className="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted">
          {reassurances.map((item) => (
            <li key={item} className="flex items-center gap-1.5">
              <span aria-hidden className="text-success">✓</span>
              {item}
            </li>
          ))}
        </ul>
      </section>

      <section className="mt-6 rounded-2xl bg-surface p-6 shadow-sm ring-1 ring-line">
        <h2 className="text-lg font-semibold">{t("home.howTitle")}</h2>
        <ol className="mt-4 space-y-4">
          {steps.map((step, index) => (
            <li key={step} className="flex gap-3">
              <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-soft font-semibold text-primary">
                {index + 1}
              </span>
              <span className="pt-1 text-muted">{step}</span>
            </li>
          ))}
        </ol>
      </section>

      <p className="mt-6 text-center text-xs text-muted">{t("qr.neverBank")}</p>
    </div>
  );
}
