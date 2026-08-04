import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/db";
import { hmac, safeEqual } from "@/lib/crypto";
import { serverT } from "@/i18n/server";
import { PrintButton } from "@/components/print-button";

export default async function ConfirmationPage({
  searchParams,
}: PageProps<"/[locale]/reclamation/confirmation">) {
  const { locale, t } = await serverT();

  const { ref, t: token } = await searchParams;
  if (typeof ref !== "string" || typeof token !== "string") notFound();

  const dossier = await prisma.dossier.findUnique({
    where: { reference: ref },
    include: { assignedAssociation: true, requerant: { select: { id: true } } },
  });
  if (!dossier || !safeEqual(dossier.trackingTokenHash, hmac(token))) notFound();

  const trackHref = `/${locale}/suivi/${encodeURIComponent(dossier.reference)}?t=${encodeURIComponent(token)}`;

  const nextStep = !dossier.requerant
    ? t("wizard.step7.nextAnonymous")
    : dossier.assignedAssociation
      ? t("wizard.step7.nextAssigned", { association: dossier.assignedAssociation.nom })
      : t("wizard.step7.nextNational");

  return (
    <div className="mx-auto max-w-2xl px-4 py-8">
      <div className="rounded-2xl bg-surface p-6 shadow-sm ring-1 ring-line">
        <div className="flex items-center gap-3">
          <span aria-hidden className="text-3xl">✅</span>
          <h1 className="text-xl font-bold sm:text-2xl">{t("wizard.step7.title")}</h1>
        </div>

        <div className="mt-5 rounded-xl bg-primary-soft p-4 text-center">
          <p className="text-sm font-medium text-muted">{t("wizard.step7.reference")}</p>
          <p className="mt-1 font-mono text-2xl font-bold tracking-wider text-primary">
            {dossier.reference}
          </p>
          <p className="mt-2 text-sm text-muted">{t("wizard.step7.keepIt")}</p>
        </div>

        <div className="mt-5">
          <h2 className="text-sm font-semibold">{t("wizard.step7.next")}</h2>
          <p className="mt-1 text-muted">{nextStep}</p>
        </div>

        <div className="mt-6 space-y-3 no-print">
          <Link
            href={trackHref}
            className="block rounded-xl bg-primary px-6 py-4 text-center text-lg font-semibold text-white hover:bg-primary-hover"
          >
            {t("wizard.step7.track")}
          </Link>
          <PrintButton label={t("wizard.step7.print")} />
          <Link
            href={`/${locale}/reclamation`}
            className="block text-center text-sm font-medium text-primary underline"
          >
            {t("wizard.step7.newRequest")}
          </Link>
        </div>

        <p className="mt-6 border-t border-line pt-4 text-xs text-muted">
          {t("wizard.step7.trackLink")} : <span className="break-all">{trackHref}</span>
        </p>
      </div>
    </div>
  );
}
