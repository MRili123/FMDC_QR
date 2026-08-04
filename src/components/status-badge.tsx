import type { DossierStatus } from "@/generated/prisma/enums";

const TONE: Record<DossierStatus, string> = {
  RECU: "bg-primary-soft text-primary",
  A_VERIFIER: "bg-primary-soft text-primary",
  INFOS_DEMANDEES: "bg-warning-soft text-warning",
  ORIENTE_ASSOCIATION: "bg-primary-soft text-primary",
  TRANSMIS_PROFESSIONNEL: "bg-primary-soft text-primary",
  EN_MEDIATION: "bg-warning-soft text-warning",
  TRANSMIS_AUTORITE: "bg-warning-soft text-warning",
  RESOLU: "bg-success-soft text-success",
  CLOTURE: "bg-success-soft text-success",
  SIGNALEMENT_COLLECTIF: "bg-danger-soft text-danger",
};

export function StatusBadge({ status, label }: { status: DossierStatus; label: string }) {
  return (
    <span
      className={`inline-block rounded-full px-3 py-1 text-sm font-semibold ${TONE[status]}`}
    >
      {label}
    </span>
  );
}
