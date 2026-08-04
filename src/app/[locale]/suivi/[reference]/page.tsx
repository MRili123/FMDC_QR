import Link from "next/link";
import { prisma } from "@/lib/db";
import { hmac, safeEqual } from "@/lib/crypto";
import { serverT } from "@/i18n/server";
import { StatusBadge } from "@/components/status-badge";

export default async function TrackDossierPage({
  params,
  searchParams,
}: PageProps<"/[locale]/suivi/[reference]">) {
  const { locale, t } = await serverT();

  const { reference } = await params;
  const { t: token } = await searchParams;

  const dossier =
    typeof token === "string"
      ? await prisma.dossier.findUnique({
          where: { reference: decodeURIComponent(reference) },
          include: {
            assignedAssociation: true,
            attachments: { select: { id: true, kind: true, originalName: true } },
            events: { where: { publicNote: true }, orderBy: { createdAt: "asc" } },
          },
        })
      : null;

  // Une référence seule ne suffit pas : sans le jeton, le dossier reste invisible.
  const authorised = dossier && safeEqual(dossier.trackingTokenHash, hmac(token as string));

  if (!authorised || !dossier) {
    return (
      <div className="mx-auto max-w-lg px-4 py-8">
        <div className="rounded-2xl bg-danger-soft p-6 ring-1 ring-line">
          <p className="font-medium text-danger">{t("track.notFound")}</p>
          <Link
            href={`/${locale}/suivi`}
            className="mt-4 inline-block font-medium text-primary underline"
          >
            {t("track.title")}
          </Link>
        </div>
      </div>
    );
  }

  const dateFormat = new Intl.DateTimeFormat(locale === "ar" ? "ar-MA" : "fr-MA", {
    dateStyle: "long",
    timeStyle: "short",
  });

  return (
    <div className="mx-auto max-w-2xl px-4 py-8">
      <div className="rounded-2xl bg-surface p-6 shadow-sm ring-1 ring-line">
        <p className="font-mono text-lg font-bold text-primary">{dossier.reference}</p>

        <dl className="mt-4 grid gap-3 sm:grid-cols-2">
          <div>
            <dt className="text-sm text-muted">{t("track.status")}</dt>
            <dd className="mt-1">
              <StatusBadge status={dossier.status} label={t(`status.${dossier.status}`)} />
            </dd>
          </div>
          <div>
            <dt className="text-sm text-muted">{t("track.opened")}</dt>
            <dd className="mt-1 font-medium">{dateFormat.format(dossier.createdAt)}</dd>
          </div>
          <div className="sm:col-span-2">
            <dt className="text-sm text-muted">{t("track.assigned")}</dt>
            <dd className="mt-1 font-medium">
              {dossier.assignedAssociation?.nom ?? t("track.assignedNational")}
            </dd>
          </div>
        </dl>

        {dossier.attachments.length > 0 ? (
          <div className="mt-5 border-t border-line pt-4">
            <h2 className="text-sm font-semibold">{t("track.attachments")}</h2>
            <ul className="mt-2 space-y-1 text-sm text-muted">
              {dossier.attachments.map((attachment) => (
                <li key={attachment.id}>
                  {t(`attachment.${attachment.kind}`)} — {attachment.originalName}
                </li>
              ))}
            </ul>
          </div>
        ) : null}
      </div>

      <div className="mt-5 rounded-2xl bg-surface p-6 shadow-sm ring-1 ring-line">
        <h2 className="text-lg font-semibold">{t("track.timeline")}</h2>
        {dossier.events.length === 0 ? (
          <p className="mt-2 text-muted">{t("track.noEvents")}</p>
        ) : (
          <ol className="mt-4 space-y-4">
            {dossier.events.map((event) => (
              <li key={event.id} className="border-s-2 border-primary-soft ps-4">
                <p className="text-sm text-muted">{dateFormat.format(event.createdAt)}</p>
                {event.toStatus ? (
                  <p className="font-medium">{t(`status.${event.toStatus}`)}</p>
                ) : null}
                {event.note ? <p className="mt-0.5 text-muted">{event.note}</p> : null}
              </li>
            ))}
          </ol>
        )}
      </div>

      <div className="mt-5 rounded-2xl bg-warning-soft p-6 ring-1 ring-line">
        <h2 className="font-semibold text-warning">{t("track.recourse")}</h2>
        <p className="mt-1.5 text-sm text-muted">{t("track.recourseBody")}</p>
      </div>
    </div>
  );
}
