import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/db";
import { currentUser, recordAudit } from "@/server/session";
import { serverT } from "@/i18n/server";
import { DOSSIER_STATUSES } from "@/lib/taxonomy";
import { StatusBadge } from "@/components/status-badge";
import { changeStatus, reassign } from "./actions";

export default async function DossierDetailPage({
  params,
  searchParams,
}: PageProps<"/[locale]/admin/dossiers/[id]">) {
  const { locale, t } = await serverT();

  const user = (await currentUser())!;
  const { id } = await params;
  const { identity } = await searchParams;

  const dossier = await prisma.dossier.findUnique({
    where: { id },
    include: {
      assignedAssociation: true,
      qrCode: true,
      attachments: true,
      requerant: { select: { id: true } },
      events: { orderBy: { createdAt: "desc" } },
    },
  });
  if (!dossier) notFound();

  if (user.role === "ASSOCIATION_AGENT" && dossier.assignedAssociationId !== user.associationId) {
    notFound();
  }

  // Chaque affichage de l'identité est journalisé, y compris un simple rechargement.
  const showIdentity = identity === "1" && Boolean(dossier.requerant);
  const requerant = showIdentity
    ? await prisma.requerant.findUnique({ where: { dossierId: dossier.id } })
    : null;
  if (showIdentity) {
    await recordAudit(user, "dossier.identity_reveal", "Dossier", dossier.id);
  }

  const associations = await prisma.association.findMany({
    where: { active: true },
    orderBy: { nom: "asc" },
  });

  const dateFormat = new Intl.DateTimeFormat(locale === "ar" ? "ar-MA" : "fr-MA", {
    dateStyle: "medium",
    timeStyle: "short",
  });

  return (
    <div className="grid gap-5 lg:grid-cols-3">
      <div className="lg:col-span-2 space-y-5">
        <div className="rounded-2xl bg-surface p-5 ring-1 ring-line">
          <div className="flex flex-wrap items-center gap-3">
            <h1 className="font-mono text-lg font-bold text-primary">{dossier.reference}</h1>
            <StatusBadge status={dossier.status} label={t(`status.${dossier.status}`)} />
            <span className="rounded-full bg-primary-soft px-3 py-1 text-sm font-medium text-primary">
              {t(`demarche.${dossier.demarche}`)}
            </span>
          </div>

          <dl className="mt-4 grid gap-3 sm:grid-cols-2">
            <div>
              <dt className="text-sm text-muted">{t("admin.dossier.category")}</dt>
              <dd className="font-medium">{t(`categorie.${dossier.categorie}`)}</dd>
            </div>
            <div>
              <dt className="text-sm text-muted">{t("admin.dossier.motif")}</dt>
              <dd className="font-medium">{t(`motif.${dossier.motif}`)}</dd>
            </div>
            {dossier.resultatAttendu ? (
              <div>
                <dt className="text-sm text-muted">{t("admin.dossier.expected")}</dt>
                <dd className="font-medium">{t(`resultat.${dossier.resultatAttendu}`)}</dd>
              </div>
            ) : null}
            {dossier.professionnel ? (
              <div>
                <dt className="text-sm text-muted">{t("admin.dossier.professionnel")}</dt>
                <dd className="font-medium">{dossier.professionnel}</dd>
              </div>
            ) : null}
            {dossier.etablissement ? (
              <div>
                <dt className="text-sm text-muted">{t("admin.dossier.etablissement")}</dt>
                <dd className="font-medium">{dossier.etablissement}</dd>
              </div>
            ) : null}
            {dossier.region ? (
              <div>
                <dt className="text-sm text-muted">{t("admin.dossier.region")}</dt>
                <dd className="font-medium">{t(`region.${dossier.region}`)}</dd>
              </div>
            ) : null}
            <div>
              <dt className="text-sm text-muted">{t("admin.dossier.source")}</dt>
              <dd className="font-medium">
                {dossier.qrCode
                  ? t("admin.dossier.sourceQr", { libelle: dossier.qrCode.libelle })
                  : t("admin.dossier.sourceDirect")}
              </dd>
            </div>
          </dl>

          <div className="mt-4 border-t border-line pt-4">
            <h2 className="text-sm font-semibold">{t("admin.dossier.description")}</h2>
            <p className="mt-1 whitespace-pre-wrap text-muted">
              {dossier.description || t("admin.dossier.noDescription")}
            </p>
          </div>

          <div className="mt-4 border-t border-line pt-4">
            <h2 className="text-sm font-semibold">{t("admin.dossier.attachments")}</h2>
            {dossier.attachments.length === 0 ? (
              <p className="mt-1 text-muted">{t("admin.dossier.noAttachments")}</p>
            ) : (
              <ul className="mt-2 space-y-1">
                {dossier.attachments.map((attachment) => (
                  <li key={attachment.id}>
                    <a
                      href={`/api/attachments/${attachment.id}`}
                      className="text-primary underline"
                      target="_blank"
                      rel="noreferrer"
                    >
                      {t(`attachment.${attachment.kind}`)} — {attachment.originalName}
                    </a>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>

        <div className="rounded-2xl bg-surface p-5 ring-1 ring-line">
          <h2 className="font-semibold">{t("admin.dossier.history")}</h2>
          <ol className="mt-3 space-y-3">
            {dossier.events.map((event) => (
              <li key={event.id} className="border-s-2 border-primary-soft ps-3 text-sm">
                <p className="text-muted">
                  {dateFormat.format(event.createdAt)} — {event.actorLabel ?? "—"}
                  {event.publicNote ? "" : ` · ${t("admin.dossier.notePrivate")}`}
                </p>
                {event.toStatus ? (
                  <p className="font-medium">{t(`status.${event.toStatus}`)}</p>
                ) : null}
                {event.note ? <p className="text-muted">{event.note}</p> : null}
              </li>
            ))}
          </ol>
        </div>
      </div>

      <div className="space-y-5">
        <div className="rounded-2xl bg-surface p-5 ring-1 ring-line">
          <h2 className="font-semibold">{t("admin.dossier.identity")}</h2>
          {!dossier.requerant ? (
            <p className="mt-2 text-sm text-muted">{t("admin.dossier.identityAnonymous")}</p>
          ) : requerant ? (
            <dl className="mt-3 space-y-2 text-sm">
              {requerant.nom ? (
                <div>
                  <dt className="text-muted">{t("admin.dossier.name")}</dt>
                  <dd className="font-medium">{requerant.nom}</dd>
                </div>
              ) : null}
              {requerant.telephone ? (
                <div>
                  <dt className="text-muted">{t("admin.dossier.phone")}</dt>
                  <dd className="font-medium">
                    {requerant.telephone}
                    {requerant.phoneVerifiedAt ? (
                      <span className="ms-2 text-success">✓ {t("admin.dossier.verified")}</span>
                    ) : null}
                  </dd>
                </div>
              ) : null}
              {requerant.email ? (
                <div>
                  <dt className="text-muted">{t("admin.dossier.email")}</dt>
                  <dd className="font-medium">{requerant.email}</dd>
                </div>
              ) : null}
              {requerant.adresse ? (
                <div>
                  <dt className="text-muted">{t("admin.dossier.address")}</dt>
                  <dd className="font-medium">{requerant.adresse}</dd>
                </div>
              ) : null}
            </dl>
          ) : (
            <>
              <p className="mt-2 text-sm text-muted">{t("admin.dossier.identityHidden")}</p>
              <Link
                href={`/${locale}/admin/dossiers/${dossier.id}?identity=1`}
                className="mt-3 inline-block rounded-lg border-2 border-primary px-4 py-2 text-sm font-semibold text-primary"
              >
                {t("admin.dossier.identityReveal")}
              </Link>
            </>
          )}
        </div>

        <form action={changeStatus} className="rounded-2xl bg-surface p-5 ring-1 ring-line">
          <h2 className="font-semibold">{t("admin.dossier.changeStatus")}</h2>
          <input type="hidden" name="dossierId" value={dossier.id} />

          <label className="mt-3 block">
            <span className="text-sm text-muted">{t("admin.dossier.newStatus")}</span>
            <select
              name="status"
              defaultValue={dossier.status}
              className="mt-1 w-full rounded-lg border-2 border-line bg-surface p-2.5 text-sm"
            >
              {DOSSIER_STATUSES.map((item) => (
                <option key={item} value={item}>
                  {t(`status.${item}`)}
                </option>
              ))}
            </select>
          </label>

          <label className="mt-3 block">
            <span className="text-sm text-muted">{t("admin.dossier.note")}</span>
            <textarea
              name="note"
              rows={3}
              className="mt-1 w-full rounded-lg border-2 border-line bg-surface p-2.5 text-sm"
            />
          </label>

          <label className="mt-2 flex items-start gap-2 text-sm">
            <input type="checkbox" name="publicNote" defaultChecked className="mt-1" />
            <span className="text-muted">{t("admin.dossier.noteHint")}</span>
          </label>

          <button
            type="submit"
            className="mt-4 w-full rounded-lg bg-primary px-4 py-2.5 font-semibold text-white"
          >
            {t("admin.dossier.apply")}
          </button>
        </form>

        <form action={reassign} className="rounded-2xl bg-surface p-5 ring-1 ring-line">
          <h2 className="font-semibold">{t("admin.dossier.reassign")}</h2>
          <input type="hidden" name="dossierId" value={dossier.id} />
          <select
            name="associationId"
            defaultValue={dossier.assignedAssociationId ?? ""}
            className="mt-3 w-full rounded-lg border-2 border-line bg-surface p-2.5 text-sm"
          >
            <option value="">{t("admin.queue.unassigned")}</option>
            {associations.map((association) => (
              <option key={association.id} value={association.id}>
                {association.nom}
              </option>
            ))}
          </select>
          <button
            type="submit"
            className="mt-4 w-full rounded-lg border-2 border-primary px-4 py-2.5 font-semibold text-primary"
          >
            {t("admin.dossier.apply")}
          </button>
        </form>
      </div>
    </div>
  );
}
