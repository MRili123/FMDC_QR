"use server";

import { refresh } from "next/cache";
import { prisma } from "@/lib/db";
import { currentUser, recordAudit } from "@/server/session";
import { createQrCode } from "@/server/qr";
import { REGIONS, SECTEURS } from "@/lib/taxonomy";
import type { QrType } from "@/generated/prisma/enums";

const TYPES: QrType[] = ["NATIONAL", "SECTORIEL", "ETABLISSEMENT"];

async function requireAdmin() {
  const user = await currentUser();
  if (!user || user.role !== "FMDC_ADMIN") throw new Error("unauthorised");
  return user;
}

export async function createQr(formData: FormData) {
  const user = await requireAdmin();

  const type = String(formData.get("type")) as QrType;
  if (!TYPES.includes(type)) throw new Error("invalid_type");

  const secteur = String(formData.get("secteur") ?? "") || null;
  const region = String(formData.get("region") ?? "") || null;
  const etablissement = String(formData.get("etablissement") ?? "").trim() || null;
  const libelle = String(formData.get("libelle") ?? "").trim() || etablissement || "QR FMDC";

  const qr = await createQrCode({
    type,
    libelle,
    secteur: secteur && SECTEURS.includes(secteur as never) ? secteur : null,
    region: region && REGIONS.includes(region as never) ? region : null,
    etablissement: type === "ETABLISSEMENT" ? etablissement : null,
    support: String(formData.get("support") ?? "").trim() || null,
  });

  await recordAudit(user, "qr.create", "QrCode", qr.id);
  refresh();
}

export async function toggleQr(formData: FormData) {
  const user = await requireAdmin();
  const id = String(formData.get("id"));

  const qr = await prisma.qrCode.findUnique({ where: { id } });
  if (!qr) throw new Error("not_found");

  await prisma.qrCode.update({ where: { id }, data: { active: !qr.active } });
  await recordAudit(user, qr.active ? "qr.deactivate" : "qr.activate", "QrCode", id);
  refresh();
}
