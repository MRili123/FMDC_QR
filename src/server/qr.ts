import QRCode from "qrcode";
import { prisma } from "@/lib/db";
import { hmac, randomSlug, safeEqual } from "@/lib/crypto";
import type { QrType } from "@/generated/prisma/enums";

export function qrTargetUrl(code: string): string {
  const base = process.env.QR_BASE_URL ?? "http://localhost:3000";
  return `${base}/r/${code}`;
}

export async function createQrCode(input: {
  type: QrType;
  libelle: string;
  secteur?: string | null;
  region?: string | null;
  etablissement?: string | null;
  support?: string | null;
}) {
  const code = randomSlug();
  return prisma.qrCode.create({
    data: {
      code,
      // La signature est vérifiée au scan : un code inventé ou recopié depuis un
      // autre établissement ne passera pas (§9, lutte contre le quishing).
      signature: hmac(code),
      type: input.type,
      libelle: input.libelle,
      secteur: input.secteur ?? null,
      region: input.region ?? null,
      etablissement: input.etablissement ?? null,
      support: input.support ?? null,
    },
  });
}

export interface ResolvedQr {
  id: string;
  code: string;
  type: QrType;
  libelle: string;
  secteur: string | null;
  region: string | null;
  etablissement: string | null;
}

/**
 * Renvoie null pour un code inconnu, désactivé ou dont la signature ne
 * correspond pas — l'appelant doit alors afficher l'avertissement, jamais le
 * formulaire.
 */
export async function resolveQr(code: string): Promise<ResolvedQr | null> {
  const record = await prisma.qrCode.findUnique({ where: { code } });
  if (!record || !record.active) return null;
  if (!safeEqual(record.signature, hmac(record.code))) return null;

  await prisma.qrCode.update({
    where: { id: record.id },
    data: { scanCount: { increment: 1 } },
  });

  return {
    id: record.id,
    code: record.code,
    type: record.type,
    libelle: record.libelle,
    secteur: record.secteur,
    region: record.region,
    etablissement: record.etablissement,
  };
}

export function renderQrSvg(code: string): Promise<string> {
  return QRCode.toString(qrTargetUrl(code), {
    type: "svg",
    errorCorrectionLevel: "M",
    margin: 1,
  });
}
