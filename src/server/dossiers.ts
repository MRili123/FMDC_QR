import { Prisma, type Demarche } from "@/generated/prisma/client";
import { prisma } from "@/lib/db";
import { hmac, randomToken } from "@/lib/crypto";
import { resolveAssociation } from "./routing";

export interface CreateDossierInput {
  demarche: Demarche;
  categorie: string;
  motif: string;
  description?: string | null;
  resultatAttendu?: string | null;
  region?: string | null;
  etablissement?: string | null;
  professionnel?: string | null;
  qrCodeId?: string | null;
  draftId?: string | null;
  contact?: {
    telephone?: string | null;
    whatsapp?: string | null;
    email?: string | null;
    nom?: string | null;
    adresse?: string | null;
    phoneVerified?: boolean;
  } | null;
}

async function nextReference(): Promise<string> {
  const year = new Date().getFullYear();
  const countThisYear = await prisma.dossier.count({
    where: { createdAt: { gte: new Date(Date.UTC(year, 0, 1)) } },
  });
  return `FMDC-${year}-${String(countThisYear + 1).padStart(6, "0")}`;
}

export async function createDossier(input: CreateDossierInput) {
  const trackingToken = randomToken();
  const associationId = await resolveAssociation({
    categorie: input.categorie,
    region: input.region,
  });

  // Deux soumissions simultanées peuvent calculer la même référence ; l'index
  // unique les départage et on recalcule.
  for (let attempt = 0; attempt < 5; attempt += 1) {
    try {
      const dossier = await prisma.$transaction(async (tx) => {
        const created = await tx.dossier.create({
          data: {
            reference: await nextReference(),
            demarche: input.demarche,
            categorie: input.categorie,
            motif: input.motif,
            description: input.description ?? null,
            resultatAttendu: input.resultatAttendu ?? null,
            region: input.region ?? null,
            etablissement: input.etablissement ?? null,
            professionnel: input.professionnel ?? null,
            qrCodeId: input.qrCodeId ?? null,
            trackingTokenHash: hmac(trackingToken),
            assignedAssociationId: associationId,
            status: associationId ? "ORIENTE_ASSOCIATION" : "RECU",
          },
        });

        await tx.dossierEvent.create({
          data: {
            dossierId: created.id,
            toStatus: created.status,
            note: associationId ? "Dossier orienté automatiquement" : "Dossier reçu",
            actorLabel: "Système",
          },
        });

        if (input.contact) {
          const { telephone, whatsapp, email, nom, adresse, phoneVerified } = input.contact;
          if (telephone || whatsapp || email || nom) {
            await tx.requerant.create({
              data: {
                dossierId: created.id,
                telephone: telephone ?? null,
                whatsapp: whatsapp ?? null,
                email: email ?? null,
                nom: nom ?? null,
                adresse: adresse ?? null,
                phoneVerifiedAt: phoneVerified ? new Date() : null,
              },
            });
          }
        }

        if (input.draftId) {
          await tx.attachment.updateMany({
            where: { draftId: input.draftId, dossierId: null },
            data: { dossierId: created.id, draftId: null },
          });
        }

        return created;
      });

      return { dossier, trackingToken };
    } catch (error) {
      const isReferenceCollision =
        error instanceof Prisma.PrismaClientKnownRequestError && error.code === "P2002";
      if (!isReferenceCollision) throw error;
    }
  }

  throw new Error("Could not allocate a dossier reference");
}
