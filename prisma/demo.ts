import "dotenv/config";
import { PrismaMariaDb } from "@prisma/adapter-mariadb";
import { createHmac, randomBytes } from "node:crypto";
import { PrismaClient } from "../src/generated/prisma/client";
import type { Demarche, DossierStatus } from "../src/generated/prisma/enums";

/** Dossiers de démonstration, pour présenter le tableau de bord au bureau de la FMDC. */
const prisma = new PrismaClient({
  adapter: new PrismaMariaDb(process.env.DATABASE_URL!),
});

const DEMO = [
  { categorie: "telecom", motif: "prix_errone", professionnel: "TelcoMaroc", region: "rabat_sale_kenitra", status: "TRANSMIS_PROFESSIONNEL", demarche: "RECLAMATION", nom: "Amina Tazi" },
  { categorie: "telecom", motif: "prix_errone", professionnel: "TelcoMaroc", region: "casablanca_settat", status: "EN_MEDIATION", demarche: "RECLAMATION", nom: "Karim Idrissi" },
  { categorie: "telecom", motif: "tromperie", professionnel: "TelcoMaroc", region: "rabat_sale_kenitra", status: "RECU", demarche: "SIGNALEMENT", nom: null },
  { categorie: "achat_magasin", motif: "garantie_refusee", professionnel: "ElectroPlus", region: "marrakech_safi", status: "RESOLU", demarche: "RECLAMATION", nom: "Fatima Zahra Alaoui" },
  { categorie: "achat_magasin", motif: "defectueux", professionnel: "ElectroPlus", region: "marrakech_safi", status: "CLOTURE", demarche: "RECLAMATION", nom: "Hassan Berrada" },
  { categorie: "transport_livraison", motif: "non_recu", professionnel: "RapidColis", region: "casablanca_settat", status: "A_VERIFIER", demarche: "RECLAMATION", nom: "Nadia Chraibi" },
  { categorie: "banque_assurance", motif: "refus_remboursement", professionnel: "Banque du Sud", region: "fes_meknes", status: "INFOS_DEMANDEES", demarche: "CONSEIL", nom: "Omar Filali" },
  { categorie: "eau_energie", motif: "prix_errone", professionnel: null, region: "oriental", status: "RECU", demarche: "SIGNALEMENT", nom: null },
] as const;

async function main() {
  const associations = await prisma.association.findMany({ include: { rules: true } });
  const year = new Date().getFullYear();
  let counter = await prisma.dossier.count({
    where: { createdAt: { gte: new Date(Date.UTC(year, 0, 1)) } },
  });

  for (const [index, item] of DEMO.entries()) {
    counter += 1;
    const token = randomBytes(24).toString("base64url");
    const rule = associations
      .flatMap((association) => association.rules.map((r) => ({ r, association })))
      .find(({ r }) => r.categorie === item.categorie);

    // Étalé sur trois semaines pour que le délai médian ait du sens.
    const createdAt = new Date(Date.now() - (index + 1) * 2 * 86_400_000);
    const firstHandledAt =
      item.status === "RECU" ? null : new Date(createdAt.getTime() + (index + 2) * 3_600_000);

    const dossier = await prisma.dossier.create({
      data: {
        reference: `FMDC-${year}-${String(counter).padStart(6, "0")}`,
        demarche: item.demarche as Demarche,
        categorie: item.categorie,
        motif: item.motif,
        description: `Dossier de démonstration — ${item.professionnel ?? "professionnel non identifié"}.`,
        professionnel: item.professionnel,
        region: item.region,
        status: item.status as DossierStatus,
        trackingTokenHash: createHmac("sha256", process.env.APP_SECRET ?? "dev")
          .update(token)
          .digest("hex"),
        assignedAssociationId: rule?.association.id ?? null,
        createdAt,
        firstHandledAt,
      },
    });

    await prisma.dossierEvent.create({
      data: {
        dossierId: dossier.id,
        toStatus: dossier.status,
        note: "Dossier de démonstration",
        actorLabel: "Système",
        createdAt,
      },
    });

    if (item.nom) {
      await prisma.requerant.create({
        data: {
          dossierId: dossier.id,
          nom: item.nom,
          telephone: `06${String(10_000_000 + index * 111_111)}`,
          phoneVerifiedAt: createdAt,
        },
      });
    }

    console.info(`${dossier.reference} — ${item.categorie} — jeton ${token}`);
  }
}

main()
  .catch((error) => {
    console.error(error);
    process.exit(1);
  })
  .finally(() => prisma.$disconnect());
