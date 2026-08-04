import { serverT } from "@/i18n/server";
import { resolveQr } from "@/server/qr";
import { QrGate } from "@/components/qr-gate";
import { ReportQrButton } from "@/components/report-qr-button";

export default async function QrLandingPage({ params }: PageProps<"/[locale]/r/[code]"> ) {
  const { locale, t } = await serverT();

  const { code } = await params;
  const qr = await resolveQr(decodeURIComponent(code));
  const domain = process.env.NEXT_PUBLIC_QR_DOMAIN ?? "qr.consommateurs.ma";

  // Code non vérifiable : on montre l'avertissement au lieu du formulaire, pour
  // qu'un autocollant frauduleux ne récolte jamais de données (§9).
  if (!qr) {
    return (
      <div className="mx-auto max-w-lg px-4 py-8">
        <div className="rounded-2xl bg-danger-soft p-6 ring-1 ring-danger">
          <div className="flex items-start gap-3">
            <span aria-hidden className="text-3xl">⚠️</span>
            <div>
              <h1 className="text-lg font-bold text-danger">{t("qr.invalidTitle")}</h1>
              <p className="mt-2 text-sm text-muted">{t("qr.invalidBody")}</p>
            </div>
          </div>
          <div className="mt-5 space-y-3">
            <ReportQrButton
              code={decodeURIComponent(code)}
              label={t("qr.invalidReport")}
              doneLabel={t("qr.invalidReported")}
            />
            <a
              href={`/${locale}/reclamation`}
              className="block text-center text-sm font-medium text-primary underline"
            >
              {t("qr.invalidContinue")}
            </a>
          </div>
        </div>
        <p className="mt-4 text-center text-xs font-medium text-muted">{t("qr.neverBank")}</p>
      </div>
    );
  }

  return (
    <QrGate
      context={{
        qrCode: qr.code,
        etablissement: qr.etablissement ?? undefined,
        region: qr.region ?? undefined,
        secteur: qr.secteur ?? undefined,
      }}
      verified={{
        title: t("qr.verifiedTitle"),
        establishmentLabel: t("qr.establishment"),
        establishment: qr.etablissement,
        sectorLabel: t("qr.sector"),
        sector: qr.secteur ? t(`secteur.${qr.secteur}`) : null,
        regionLabel: t("qr.region"),
        region: qr.region ? t(`region.${qr.region}`) : null,
        checkDomain: t("qr.checkDomain", { domain }),
        continueLabel: t("qr.continue"),
        neverBank: t("qr.neverBank"),
      }}
    />
  );
}
