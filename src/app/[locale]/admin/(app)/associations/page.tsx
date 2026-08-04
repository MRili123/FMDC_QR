import { notFound } from "next/navigation";
import { prisma } from "@/lib/db";
import { currentUser } from "@/server/session";
import { serverT } from "@/i18n/server";
import { CATEGORIES, REGIONS, SECTEURS } from "@/lib/taxonomy";
import { createAssociation, createRule, deleteRule } from "./actions";

export default async function AssociationsPage() {
  const { locale, t } = await serverT();

  const user = (await currentUser())!;
  if (user.role !== "FMDC_ADMIN") notFound();

  const associations = await prisma.association.findMany({
    include: { rules: { orderBy: { categorie: "asc" } } },
    orderBy: { nom: "asc" },
  });

  return (
    <div className="grid gap-5 lg:grid-cols-3">
      <div className="space-y-5 lg:col-span-2">
        <h1 className="text-xl font-bold">{t("admin.associations.title")}</h1>

        {associations.map((association) => (
          <div key={association.id} className="rounded-2xl bg-surface p-5 ring-1 ring-line">
            <h2 className="font-semibold">{association.nom}</h2>
            {association.contact ? (
              <p className="text-sm text-muted">{association.contact}</p>
            ) : null}

            <div className="mt-2 flex flex-wrap gap-1.5">
              {association.regions.map((region) => (
                <span
                  key={region}
                  className="rounded-full bg-primary-soft px-2.5 py-1 text-xs font-medium text-primary"
                >
                  {t(`region.${region}`)}
                </span>
              ))}
              {association.secteurs.map((secteur) => (
                <span
                  key={secteur}
                  className="rounded-full bg-line px-2.5 py-1 text-xs font-medium text-muted"
                >
                  {t(`secteur.${secteur}`)}
                </span>
              ))}
            </div>

            <h3 className="mt-4 text-sm font-semibold">{t("admin.associations.rules")}</h3>
            <ul className="mt-2 space-y-1.5">
              {association.rules.map((rule) => (
                <li key={rule.id} className="flex items-center gap-2 text-sm">
                  <span className="font-medium">{t(`categorie.${rule.categorie}`)}</span>
                  <span className="text-muted">
                    {rule.region ? t(`region.${rule.region}`) : t("admin.associations.allRegions")}
                  </span>
                  <form action={deleteRule} className="ms-auto">
                    <input type="hidden" name="id" value={rule.id} />
                    <button type="submit" className="text-xs font-medium text-danger underline">
                      {t("admin.associations.delete")}
                    </button>
                  </form>
                </li>
              ))}
            </ul>

            <form action={createRule} className="mt-3 flex flex-wrap gap-2">
              <input type="hidden" name="associationId" value={association.id} />
              <select
                name="categorie"
                className="min-w-32 flex-1 rounded-lg border-2 border-line bg-surface p-2 text-sm"
              >
                {CATEGORIES.map((categorie) => (
                  <option key={categorie} value={categorie}>
                    {t(`categorie.${categorie}`)}
                  </option>
                ))}
              </select>
              <select
                name="region"
                className="min-w-32 flex-1 rounded-lg border-2 border-line bg-surface p-2 text-sm"
              >
                <option value="">{t("admin.associations.allRegions")}</option>
                {REGIONS.map((region) => (
                  <option key={region} value={region}>
                    {t(`region.${region}`)}
                  </option>
                ))}
              </select>
              <button
                type="submit"
                className="rounded-lg border-2 border-primary px-3 py-2 text-sm font-semibold text-primary"
              >
                {t("admin.associations.addRule")}
              </button>
            </form>
          </div>
        ))}
      </div>

      <form action={createAssociation} className="h-fit rounded-2xl bg-surface p-5 ring-1 ring-line">
        <h2 className="font-semibold">{t("admin.associations.create")}</h2>

        <label className="mt-3 block">
          <span className="text-sm text-muted">{t("admin.associations.nom")}</span>
          <input
            name="nom"
            required
            className="mt-1 w-full rounded-lg border-2 border-line bg-surface p-2.5 text-sm"
          />
        </label>

        <label className="mt-3 block">
          <span className="text-sm text-muted">{t("admin.associations.contact")}</span>
          <input
            name="contact"
            className="mt-1 w-full rounded-lg border-2 border-line bg-surface p-2.5 text-sm"
          />
        </label>

        <fieldset className="mt-4">
          <legend className="text-sm text-muted">{t("admin.associations.regions")}</legend>
          <div className="mt-1.5 max-h-40 space-y-1 overflow-y-auto rounded-lg border-2 border-line p-2">
            {REGIONS.map((region) => (
              <label key={region} className="flex items-center gap-2 text-sm">
                <input type="checkbox" name="regions" value={region} />
                {t(`region.${region}`)}
              </label>
            ))}
          </div>
        </fieldset>

        <fieldset className="mt-4">
          <legend className="text-sm text-muted">{t("admin.associations.secteurs")}</legend>
          <div className="mt-1.5 max-h-40 space-y-1 overflow-y-auto rounded-lg border-2 border-line p-2">
            {SECTEURS.map((secteur) => (
              <label key={secteur} className="flex items-center gap-2 text-sm">
                <input type="checkbox" name="secteurs" value={secteur} />
                {t(`secteur.${secteur}`)}
              </label>
            ))}
          </div>
        </fieldset>

        <button
          type="submit"
          className="mt-5 w-full rounded-lg bg-primary px-4 py-2.5 font-semibold text-white"
        >
          {t("admin.associations.create")}
        </button>
      </form>
    </div>
  );
}
