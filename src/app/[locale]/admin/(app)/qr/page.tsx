import { prisma } from "@/lib/db";
import { currentUser } from "@/server/session";
import { serverT } from "@/i18n/server";
import { REGIONS, SECTEURS } from "@/lib/taxonomy";
import { createQr, toggleQr } from "./actions";

export default async function QrAdminPage() {
  const { locale, t } = await serverT();

  const user = (await currentUser())!;
  const isAdmin = user.role === "FMDC_ADMIN";
  const codes = await prisma.qrCode.findMany({ orderBy: { createdAt: "desc" } });

  return (
    <div className="grid gap-5 lg:grid-cols-3">
      <div className="lg:col-span-2">
        <h1 className="text-xl font-bold">{t("admin.qr.title")}</h1>
        <div className="mt-4 space-y-3">
          {codes.map((qr) => (
            <div
              key={qr.id}
              className="flex flex-wrap items-center gap-3 rounded-xl bg-surface p-4 ring-1 ring-line"
            >
              <div className="min-w-0 flex-1">
                <p className="font-semibold">{qr.libelle}</p>
                <p className="mt-0.5 text-sm text-muted">
                  {qr.type} · <span className="font-mono">{qr.code}</span>
                  {qr.active ? "" : ` · ${t("admin.qr.inactive")}`}
                </p>
                <p className="mt-0.5 text-sm text-muted">
                  {t("admin.qr.scans")} : {qr.scanCount} · {t("admin.qr.reported")} :{" "}
                  <span className={qr.reportedCount > 0 ? "font-semibold text-danger" : ""}>
                    {qr.reportedCount}
                  </span>
                </p>
              </div>
              <a
                href={`/api/qr/${qr.code}/poster`}
                target="_blank"
                rel="noreferrer"
                className="rounded-lg border-2 border-primary px-3 py-2 text-sm font-semibold text-primary"
              >
                {t("admin.qr.download")}
              </a>
              {isAdmin ? (
                <form action={toggleQr}>
                  <input type="hidden" name="id" value={qr.id} />
                  <button
                    type="submit"
                    className="rounded-lg border-2 border-line px-3 py-2 text-sm font-semibold text-muted"
                  >
                    {qr.active ? t("admin.qr.deactivate") : t("admin.qr.activate")}
                  </button>
                </form>
              ) : null}
            </div>
          ))}
        </div>
      </div>

      {isAdmin ? (
        <form action={createQr} className="h-fit rounded-2xl bg-surface p-5 ring-1 ring-line">
          <h2 className="font-semibold">{t("admin.qr.create")}</h2>

          <label className="mt-3 block">
            <span className="text-sm text-muted">{t("admin.qr.type")}</span>
            <select
              name="type"
              className="mt-1 w-full rounded-lg border-2 border-line bg-surface p-2.5 text-sm"
            >
              <option value="NATIONAL">NATIONAL</option>
              <option value="SECTORIEL">SECTORIEL</option>
              <option value="ETABLISSEMENT">ETABLISSEMENT</option>
            </select>
          </label>

          <label className="mt-3 block">
            <span className="text-sm text-muted">{t("admin.qr.libelle")}</span>
            <input
              name="libelle"
              className="mt-1 w-full rounded-lg border-2 border-line bg-surface p-2.5 text-sm"
            />
          </label>

          <label className="mt-3 block">
            <span className="text-sm text-muted">{t("admin.qr.etablissement")}</span>
            <input
              name="etablissement"
              className="mt-1 w-full rounded-lg border-2 border-line bg-surface p-2.5 text-sm"
            />
          </label>

          <label className="mt-3 block">
            <span className="text-sm text-muted">{t("admin.qr.secteur")}</span>
            <select
              name="secteur"
              className="mt-1 w-full rounded-lg border-2 border-line bg-surface p-2.5 text-sm"
            >
              <option value="">{t("admin.qr.none")}</option>
              {SECTEURS.map((secteur) => (
                <option key={secteur} value={secteur}>
                  {t(`secteur.${secteur}`)}
                </option>
              ))}
            </select>
          </label>

          <label className="mt-3 block">
            <span className="text-sm text-muted">{t("admin.qr.region")}</span>
            <select
              name="region"
              className="mt-1 w-full rounded-lg border-2 border-line bg-surface p-2.5 text-sm"
            >
              <option value="">{t("admin.qr.none")}</option>
              {REGIONS.map((region) => (
                <option key={region} value={region}>
                  {t(`region.${region}`)}
                </option>
              ))}
            </select>
          </label>

          <label className="mt-3 block">
            <span className="text-sm text-muted">{t("admin.qr.support")}</span>
            <input
              name="support"
              className="mt-1 w-full rounded-lg border-2 border-line bg-surface p-2.5 text-sm"
            />
          </label>

          <button
            type="submit"
            className="mt-5 w-full rounded-lg bg-primary px-4 py-2.5 font-semibold text-white"
          >
            {t("admin.qr.create")}
          </button>
        </form>
      ) : null}
    </div>
  );
}
