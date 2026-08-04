import { prisma } from "@/lib/db";
import { currentUser } from "@/server/session";
import { serverT } from "@/i18n/server";
import { TERMINAL_STATUSES } from "@/lib/taxonomy";
import type { Prisma } from "@/generated/prisma/client";

const COLLECTIVE_WINDOW_DAYS = 30;
const COLLECTIVE_THRESHOLD = 2;

export default async function DashboardPage() {
  const { locale, t } = await serverT();

  const user = (await currentUser())!;
  // Le cloisonnement du §9 vaut aussi pour les statistiques.
  const scope: Prisma.DossierWhereInput =
    user.role === "ASSOCIATION_AGENT" ? { assignedAssociationId: user.associationId } : {};

  const [total, resolved, byCategory, byStatus, byRegion, handled] = await Promise.all([
    prisma.dossier.count({ where: scope }),
    prisma.dossier.count({ where: { ...scope, status: { in: [...TERMINAL_STATUSES] } } }),
    prisma.dossier.groupBy({ by: ["categorie"], where: scope, _count: true }),
    prisma.dossier.groupBy({ by: ["status"], where: scope, _count: true }),
    prisma.dossier.groupBy({ by: ["region"], where: scope, _count: true }),
    prisma.dossier.findMany({
      where: { ...scope, firstHandledAt: { not: null } },
      select: { createdAt: true, firstHandledAt: true },
    }),
  ]);

  const delays = handled
    .map((row) => (row.firstHandledAt!.getTime() - row.createdAt.getTime()) / 3_600_000)
    .sort((a, b) => a - b);
  const median =
    delays.length === 0
      ? null
      : delays.length % 2 === 1
        ? delays[(delays.length - 1) / 2]
        : (delays[delays.length / 2 - 1] + delays[delays.length / 2]) / 2;

  // Détection des signaux collectifs (§5) : plusieurs dossiers de même catégorie
  // visant le même professionnel sur une fenêtre glissante.
  const recent = await prisma.dossier.findMany({
    where: {
      ...scope,
      professionnel: { not: null },
      createdAt: { gte: new Date(Date.now() - COLLECTIVE_WINDOW_DAYS * 86_400_000) },
    },
    select: { professionnel: true, categorie: true },
  });

  const clusters = new Map<string, { professionnel: string; categorie: string; count: number }>();
  for (const row of recent) {
    const key = `${row.professionnel!.trim().toLowerCase()}|${row.categorie}`;
    const existing = clusters.get(key);
    if (existing) existing.count += 1;
    else clusters.set(key, { professionnel: row.professionnel!, categorie: row.categorie, count: 1 });
  }
  const collective = [...clusters.values()]
    .filter((item) => item.count >= COLLECTIVE_THRESHOLD)
    .sort((a, b) => b.count - a.count);

  return (
    <div>
      <h1 className="text-xl font-bold">{t("admin.dashboard.title")}</h1>

      <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <Metric label={t("admin.dashboard.total")} value={String(total)} />
        <Metric label={t("admin.dashboard.open")} value={String(total - resolved)} />
        <Metric label={t("admin.dashboard.resolved")} value={String(resolved)} />
        <Metric
          label={t("admin.dashboard.medianFirstHandling")}
          value={
            median === null
              ? t("admin.dashboard.noData")
              : t("admin.dashboard.hours", { value: median.toFixed(1) })
          }
        />
      </div>

      <div className="mt-5 grid gap-5 lg:grid-cols-3">
        <Breakdown
          title={t("admin.dashboard.byCategory")}
          rows={byCategory.map((row) => ({
            label: t(`categorie.${row.categorie}`),
            count: row._count,
          }))}
          total={total}
        />
        <Breakdown
          title={t("admin.dashboard.byStatus")}
          rows={byStatus.map((row) => ({ label: t(`status.${row.status}`), count: row._count }))}
          total={total}
        />
        <Breakdown
          title={t("admin.dashboard.byRegion")}
          rows={byRegion.map((row) => ({
            label: row.region ? t(`region.${row.region}`) : t("admin.dashboard.noData"),
            count: row._count,
          }))}
          total={total}
        />
      </div>

      <div className="mt-5 rounded-2xl bg-surface p-5 ring-1 ring-line">
        <h2 className="font-semibold">{t("admin.dashboard.duplicates")}</h2>
        <p className="mt-1 text-sm text-muted">{t("admin.dashboard.duplicatesHint")}</p>
        {collective.length === 0 ? (
          <p className="mt-3 text-muted">{t("admin.dashboard.duplicatesNone")}</p>
        ) : (
          <ul className="mt-3 space-y-2">
            {collective.map((item) => (
              <li
                key={`${item.professionnel}-${item.categorie}`}
                className="flex flex-wrap items-center gap-2 rounded-lg bg-danger-soft p-3"
              >
                <span className="font-semibold">{item.professionnel}</span>
                <span className="text-sm text-muted">{t(`categorie.${item.categorie}`)}</span>
                <span className="ms-auto rounded-full bg-danger px-3 py-1 text-sm font-semibold text-white">
                  {t("admin.dashboard.occurrences", { count: item.count })}
                </span>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}

function Metric({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-2xl bg-surface p-4 ring-1 ring-line">
      <p className="text-sm text-muted">{label}</p>
      <p className="mt-1 text-2xl font-bold text-primary">{value}</p>
    </div>
  );
}

function Breakdown({
  title,
  rows,
  total,
}: {
  title: string;
  rows: { label: string; count: number }[];
  total: number;
}) {
  const sorted = [...rows].sort((a, b) => b.count - a.count);
  return (
    <div className="rounded-2xl bg-surface p-5 ring-1 ring-line">
      <h2 className="font-semibold">{title}</h2>
      <ul className="mt-3 space-y-2">
        {sorted.map((row) => (
          <li key={row.label}>
            <div className="flex justify-between text-sm">
              <span className="text-muted">{row.label}</span>
              <span className="font-semibold">{row.count}</span>
            </div>
            <div className="mt-1 h-1.5 overflow-hidden rounded-full bg-line">
              <div
                className="h-full bg-primary"
                style={{ width: `${total > 0 ? (row.count / total) * 100 : 0}%` }}
              />
            </div>
          </li>
        ))}
      </ul>
    </div>
  );
}
