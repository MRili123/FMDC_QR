"use server";

import { refresh } from "next/cache";
import { prisma } from "@/lib/db";
import { currentUser, recordAudit } from "@/server/session";
import { DOSSIER_STATUSES } from "@/lib/taxonomy";
import type { DossierStatus } from "@/generated/prisma/enums";

/** Un agent ne peut agir que sur un dossier de son périmètre (§9). */
async function authorise(dossierId: string) {
  const user = await currentUser();
  if (!user) throw new Error("unauthorised");

  const dossier = await prisma.dossier.findUnique({
    where: { id: dossierId },
    select: { id: true, status: true, assignedAssociationId: true, firstHandledAt: true },
  });
  if (!dossier) throw new Error("not_found");

  if (user.role === "ASSOCIATION_AGENT" && dossier.assignedAssociationId !== user.associationId) {
    throw new Error("unauthorised");
  }
  return { user, dossier };
}

export async function changeStatus(formData: FormData) {
  const dossierId = String(formData.get("dossierId"));
  const { user, dossier } = await authorise(dossierId);

  const next = String(formData.get("status"));
  if (!DOSSIER_STATUSES.includes(next as DossierStatus)) throw new Error("invalid_status");

  const note = String(formData.get("note") ?? "").trim() || null;
  const publicNote = formData.get("publicNote") === "on";

  await prisma.$transaction([
    prisma.dossier.update({
      where: { id: dossierId },
      data: {
        status: next as DossierStatus,
        // Le délai de prise en charge se mesure au premier geste humain, donc
        // seule la toute première transition l'enregistre.
        firstHandledAt: dossier.firstHandledAt ?? new Date(),
      },
    }),
    prisma.dossierEvent.create({
      data: {
        dossierId,
        fromStatus: dossier.status,
        toStatus: next as DossierStatus,
        note,
        publicNote,
        actorId: user.id,
        actorLabel: user.nom,
      },
    }),
  ]);

  await recordAudit(user, "dossier.status_change", "Dossier", dossierId);
  refresh();
}

export async function reassign(formData: FormData) {
  const dossierId = String(formData.get("dossierId"));
  const { user } = await authorise(dossierId);

  const associationId = String(formData.get("associationId")) || null;
  const association = associationId
    ? await prisma.association.findUnique({ where: { id: associationId } })
    : null;

  await prisma.$transaction([
    prisma.dossier.update({
      where: { id: dossierId },
      data: { assignedAssociationId: association?.id ?? null },
    }),
    prisma.dossierEvent.create({
      data: {
        dossierId,
        note: association
          ? `Dossier réaffecté à ${association.nom}`
          : "Dossier renvoyé au bureau national",
        publicNote: true,
        actorId: user.id,
        actorLabel: user.nom,
      },
    }),
  ]);

  await recordAudit(user, "dossier.reassign", "Dossier", dossierId);
  refresh();
}
