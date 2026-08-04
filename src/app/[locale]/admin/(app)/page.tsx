import Link from "next/link";
import { prisma } from "@/lib/db";
import { currentUser } from "@/server/session";
import { serverT } from "@/i18n/server";
import { CATEGORIES, DOSSIER_STATUSES } from "@/lib/taxonomy";
import { StatusBadge } from "@/components/status-badge";
import type { DossierStatus } from "@/generated/prisma/enums";

export default async function QueuePage({ searchParams }: PageProps<"/[locale]/admin">) {
  const { locale, t } = await serverT();

  const user = (await currentUser())!;
  const { status, categorie, q } = await searchParams;

  const dossiers = await prisma.dossier.findMany({
    where: {
      // Cloisonnement du §9 : un agent ne voit que le périmètre de son association.
      ...(user.role === "ASSOCIATION_AGENT"
        ? { assignedAssociationId: user.associationId }
        : {}),
      ...(typeof status === "string" && DOSSIER_STATUSES.includes(status as DossierStatus)
        ? { status: status as DossierStatus }
        : {}),
      ...(typeof categorie === "string" && CATEGORIES.includes(categorie as never)
        ? { categorie }
        : {}),
      ...(typeof q === "string" && q.trim()
        ? { reference: { contains: q.trim(), mode: "insensitive" as const } }
        : {}),
    },
    include: { assignedAssociation: { select: { nom: true } } },
    orderBy: { createdAt: "desc" },
    take: 100,
  });

  const dateFormat = new Intl.DateTimeFormat(locale === "ar" ? "ar-MA" : "fr-MA", {
    dateStyle: "medium",
  });

  return (
    <div>
      <h1 className="text-xl font-bold">{t("admin.queue.title")}</h1>

      <form className="mt-4 flex flex-wrap gap-2">
        <input
          name="q"
          defaultValue={typeof q === "string" ? q : ""}
          placeholder={t("admin.queue.search")}
          className="min-w-40 flex-1 rounded-lg border-2 border-line bg-surface px-3 py-2 text-sm"
        />
        <select
          name="status"
          defaultValue={typeof status === "string" ? status : ""}
          className="rounded-lg border-2 border-line bg-surface px-3 py-2 text-sm"
        >
          <option value="">{t("admin.queue.filterStatus")}</option>
          {DOSSIER_STATUSES.map((item) => (
            <option key={item} value={item}>
              {t(`status.${item}`)}
            </option>
          ))}
        </select>
        <select
          name="categorie"
          defaultValue={typeof categorie === "string" ? categorie : ""}
          className="rounded-lg border-2 border-line bg-surface px-3 py-2 text-sm"
        >
          <option value="">{t("admin.queue.filterCategory")}</option>
          {CATEGORIES.map((item) => (
            <option key={item} value={item}>
              {t(`categorie.${item}`)}
            </option>
          ))}
        </select>
        <button
          type="submit"
          className="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white"
        >
          {t("admin.queue.search")}
        </button>
      </form>

      {dossiers.length === 0 ? (
        <p className="mt-6 text-muted">{t("admin.queue.empty")}</p>
      ) : (
        <div className="mt-4 overflow-x-auto rounded-xl ring-1 ring-line">
          <table className="w-full min-w-3xl border-collapse bg-surface text-sm">
            <thead>
              <tr className="border-b border-line text-start">
                <th className="p-3 text-start font-semibold">{t("admin.queue.reference")}</th>
                <th className="p-3 text-start font-semibold">{t("admin.queue.category")}</th>
                <th className="p-3 text-start font-semibold">{t("admin.queue.status")}</th>
                <th className="p-3 text-start font-semibold">{t("admin.queue.assigned")}</th>
                <th className="p-3 text-start font-semibold">{t("admin.queue.created")}</th>
              </tr>
            </thead>
            <tbody>
              {dossiers.map((dossier) => (
                <tr key={dossier.id} className="border-b border-line last:border-0">
                  <td className="p-3">
                    <Link
                      href={`/${locale}/admin/dossiers/${dossier.id}`}
                      className="font-mono font-semibold text-primary underline"
                    >
                      {dossier.reference}
                    </Link>
                  </td>
                  <td className="p-3">{t(`categorie.${dossier.categorie}`)}</td>
                  <td className="p-3">
                    <StatusBadge status={dossier.status} label={t(`status.${dossier.status}`)} />
                  </td>
                  <td className="p-3 text-muted">
                    {dossier.assignedAssociation?.nom ?? t("admin.queue.unassigned")}
                  </td>
                  <td className="p-3 text-muted">{dateFormat.format(dossier.createdAt)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
