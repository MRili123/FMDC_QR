import { prisma } from "@/lib/db";

export interface RoutingInput {
  categorie: string;
  region?: string | null;
}

/**
 * Principe « aucune mauvaise porte » (§5) : cette fonction ne rejette jamais un
 * dossier. Faute de règle correspondante elle renvoie null, ce qui laisse le
 * dossier dans la file nationale FMDC plutôt que de bloquer la soumission.
 *
 * La signature accepte exactement ce qu'un classifieur IA produirait, pour que la
 * phase 4 change la source de la suggestion sans toucher à l'affectation.
 */
export async function resolveAssociation(input: RoutingInput): Promise<string | null> {
  const rules = await prisma.routingRule.findMany({
    where: {
      categorie: input.categorie,
      association: { active: true },
      ...(input.region ? { OR: [{ region: input.region }, { region: null }] } : {}),
    },
    orderBy: { priority: "asc" },
  });

  if (rules.length === 0) return null;

  // Une règle qui nomme la région l'emporte sur une règle nationale de même priorité.
  const regional = input.region ? rules.find((rule) => rule.region === input.region) : undefined;
  return (regional ?? rules[0]).associationId;
}
