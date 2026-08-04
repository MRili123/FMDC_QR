import { NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/db";
import { createDossier } from "@/server/dossiers";
import { CATEGORIES, MOTIFS, RESULTATS } from "@/lib/taxonomy";

const schema = z.object({
  draftId: z.string().min(1).max(100),
  demarche: z.enum(["CONSEIL", "SIGNALEMENT", "RECLAMATION"]),
  categorie: z.enum(CATEGORIES),
  motif: z.enum(MOTIFS),
  description: z.string().max(5000).nullish(),
  resultatAttendu: z.enum(RESULTATS).nullish(),
  professionnel: z.string().max(200).nullish(),
  qrCode: z.string().max(50).nullish(),
  contact: z
    .object({
      telephone: z.string().max(20).nullish(),
      email: z.email().max(200).nullish(),
      nom: z.string().max(150).nullish(),
    })
    .nullish(),
});

/** Une vérification OTP n'est retenue que si elle est fraîche. */
const OTP_VALIDITY_MINUTES = 30;

export async function POST(request: Request) {
  const parsed = schema.safeParse(await request.json());
  if (!parsed.success) {
    return NextResponse.json({ error: "invalid_request" }, { status: 400 });
  }

  const input = parsed.data;

  // L'établissement, la région et le secteur viennent du QR côté serveur : le
  // client pourrait sinon s'attribuer n'importe quel établissement.
  const qrCode = input.qrCode
    ? await prisma.qrCode.findFirst({ where: { code: input.qrCode, active: true } })
    : null;

  let phoneVerified = false;
  const telephone = input.contact?.telephone?.replace(/\s+/g, "") || null;
  if (telephone) {
    const consumed = await prisma.otpChallenge.findFirst({
      where: {
        phone: telephone,
        consumedAt: { gte: new Date(Date.now() - OTP_VALIDITY_MINUTES * 60 * 1000) },
      },
    });
    phoneVerified = Boolean(consumed);
  }

  const { dossier, trackingToken } = await createDossier({
    demarche: input.demarche,
    categorie: input.categorie,
    motif: input.motif,
    description: input.description ?? null,
    resultatAttendu: input.resultatAttendu ?? null,
    professionnel: input.professionnel ?? null,
    etablissement: qrCode?.etablissement ?? null,
    region: qrCode?.region ?? null,
    qrCodeId: qrCode?.id ?? null,
    draftId: input.draftId,
    contact: input.contact
      ? {
          telephone,
          email: input.contact.email ?? null,
          nom: input.contact.nom ?? null,
          phoneVerified,
        }
      : null,
  });

  return NextResponse.json({ reference: dossier.reference, token: trackingToken });
}
