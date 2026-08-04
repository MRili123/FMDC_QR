"use server";

import { refresh } from "next/cache";
import { prisma } from "@/lib/db";
import { currentUser, recordAudit } from "@/server/session";
import { CATEGORIES, REGIONS, SECTEURS } from "@/lib/taxonomy";

async function requireAdmin() {
  const user = await currentUser();
  if (!user || user.role !== "FMDC_ADMIN") throw new Error("unauthorised");
  return user;
}

function pickMany(formData: FormData, field: string, allowed: readonly string[]): string[] {
  return formData.getAll(field).map(String).filter((value) => allowed.includes(value));
}

export async function createAssociation(formData: FormData) {
  const user = await requireAdmin();

  const nom = String(formData.get("nom") ?? "").trim();
  if (!nom) throw new Error("invalid_request");

  const association = await prisma.association.create({
    data: {
      nom,
      contact: String(formData.get("contact") ?? "").trim() || null,
      regions: pickMany(formData, "regions", REGIONS),
      secteurs: pickMany(formData, "secteurs", SECTEURS),
    },
  });

  await recordAudit(user, "association.create", "Association", association.id);
  refresh();
}

export async function createRule(formData: FormData) {
  const user = await requireAdmin();

  const associationId = String(formData.get("associationId"));
  const categorie = String(formData.get("categorie"));
  const region = String(formData.get("region") ?? "") || null;

  if (!CATEGORIES.includes(categorie as never)) throw new Error("invalid_request");
  if (region && !REGIONS.includes(region as never)) throw new Error("invalid_request");

  const rule = await prisma.routingRule.create({
    data: {
      associationId,
      categorie,
      region,
      // Une règle régionale doit primer sur une règle nationale de même catégorie.
      priority: region ? 10 : 100,
    },
  });

  await recordAudit(user, "routing_rule.create", "RoutingRule", rule.id);
  refresh();
}

export async function deleteRule(formData: FormData) {
  const user = await requireAdmin();
  const id = String(formData.get("id"));

  await prisma.routingRule.delete({ where: { id } });
  await recordAudit(user, "routing_rule.delete", "RoutingRule", id);
  refresh();
}
