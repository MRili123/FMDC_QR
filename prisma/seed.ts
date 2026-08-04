import "dotenv/config";
import { PrismaPg } from "@prisma/adapter-pg";
import { createHmac, randomBytes } from "node:crypto";
import bcrypt from "bcryptjs";
import { PrismaClient } from "../src/generated/prisma/client";

const prisma = new PrismaClient({
  adapter: new PrismaPg({ connectionString: process.env.DATABASE_URL }),
});

function hmac(payload: string) {
  return createHmac("sha256", process.env.APP_SECRET ?? "dev").update(payload).digest("hex");
}

function slug(length = 10) {
  const alphabet = "23456789ABCDEFGHJKLMNPQRSTUVWXYZ";
  const bytes = randomBytes(length);
  let out = "";
  for (let i = 0; i < length; i += 1) out += alphabet[bytes[i] % alphabet.length];
  return out;
}

/**
 * Associations et règles fictives : la liste réelle des associations affiliées,
 * de leurs régions et de leurs secteurs reste à confirmer par la FMDC.
 */
const ASSOCIATIONS = [
  {
    nom: "FMDC — Bureau national",
    regions: [],
    secteurs: [],
    contact: "contact@consommateurs.ma",
    categories: ["autre", "service_public", "education", "logement"],
  },
  {
    nom: "Association Casablanca-Settat des consommateurs",
    regions: ["casablanca_settat"],
    secteurs: ["commerce", "ecommerce", "banque_assurance"],
    contact: "casablanca@consommateurs.ma",
    categories: ["achat_magasin", "achat_internet", "banque_assurance"],
  },
  {
    nom: "Association Rabat-Salé-Kénitra des consommateurs",
    regions: ["rabat_sale_kenitra"],
    secteurs: ["telecom", "transport", "eau_energie"],
    contact: "rabat@consommateurs.ma",
    categories: ["telecom", "transport_livraison", "eau_energie"],
  },
  {
    nom: "Association Marrakech-Safi des consommateurs",
    regions: ["marrakech_safi"],
    secteurs: ["tourisme", "commerce", "sante"],
    contact: "marrakech@consommateurs.ma",
    categories: ["tourisme_restauration", "sante", "achat_magasin"],
  },
];

async function main() {
  const created = new Map<string, string>();

  for (const item of ASSOCIATIONS) {
    const association = await prisma.association.upsert({
      where: { id: item.nom },
      update: {},
      create: {
        id: item.nom,
        nom: item.nom,
        regions: item.regions,
        secteurs: item.secteurs,
        contact: item.contact,
      },
    });
    created.set(item.nom, association.id);

    for (const categorie of item.categories) {
      const region = item.regions[0] ?? null;
      const exists = await prisma.routingRule.findFirst({
        where: { categorie, region, associationId: association.id },
      });
      if (!exists) {
        await prisma.routingRule.create({
          data: {
            categorie,
            region,
            associationId: association.id,
            priority: region ? 10 : 100,
          },
        });
      }
    }
  }

  await prisma.adminUser.upsert({
    where: { email: "admin@consommateurs.ma" },
    update: {},
    create: {
      email: "admin@consommateurs.ma",
      passwordHash: await bcrypt.hash("Fmdc2026!", 10),
      nom: "Administrateur FMDC",
      role: "FMDC_ADMIN",
    },
  });

  await prisma.adminUser.upsert({
    where: { email: "agent.casa@consommateurs.ma" },
    update: {},
    create: {
      email: "agent.casa@consommateurs.ma",
      passwordHash: await bcrypt.hash("Fmdc2026!", 10),
      nom: "Agent Casablanca",
      role: "ASSOCIATION_AGENT",
      associationId: created.get("Association Casablanca-Settat des consommateurs")!,
    },
  });

  const existingNational = await prisma.qrCode.findFirst({ where: { type: "NATIONAL" } });
  if (!existingNational) {
    const code = slug();
    await prisma.qrCode.create({
      data: {
        code,
        signature: hmac(code),
        type: "NATIONAL",
        libelle: "QR national FMDC",
        support: "Affiche nationale",
      },
    });
  }

  const existingEtab = await prisma.qrCode.findFirst({ where: { type: "ETABLISSEMENT" } });
  if (!existingEtab) {
    const code = slug();
    await prisma.qrCode.create({
      data: {
        code,
        signature: hmac(code),
        type: "ETABLISSEMENT",
        libelle: "Supermarché Al Manar — Maârif",
        etablissement: "Supermarché Al Manar — Maârif",
        secteur: "commerce",
        region: "casablanca_settat",
        support: "Autocollant caisse",
      },
    });
  }

  const qrCodes = await prisma.qrCode.findMany();
  console.info(`Seed terminé. Associations: ${created.size}, QR: ${qrCodes.length}`);
  console.info("Connexion back-office : admin@consommateurs.ma / Fmdc2026!");
  for (const qr of qrCodes) console.info(`  QR ${qr.type}: /r/${qr.code} (${qr.libelle})`);
}

main()
  .catch((error) => {
    console.error(error);
    process.exit(1);
  })
  .finally(() => prisma.$disconnect());
