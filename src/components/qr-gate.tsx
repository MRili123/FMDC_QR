"use client";

import { useState } from "react";
import { Wizard, type WizardContext } from "./wizard";

interface VerifiedCopy {
  title: string;
  establishmentLabel: string;
  establishment: string | null;
  sectorLabel: string;
  sector: string | null;
  regionLabel: string;
  region: string | null;
  checkDomain: string;
  continueLabel: string;
  neverBank: string;
}

/**
 * Écran intercalaire du §9 : le consommateur voit d'abord quel établissement le
 * QR a désigné, avant qu'aucun champ ne lui soit demandé.
 */
export function QrGate({ context, verified }: { context: WizardContext; verified: VerifiedCopy }) {
  const [started, setStarted] = useState(false);

  if (started) return <Wizard context={context} />;

  const rows = [
    { label: verified.establishmentLabel, value: verified.establishment },
    { label: verified.sectorLabel, value: verified.sector },
    { label: verified.regionLabel, value: verified.region },
  ].filter((row) => row.value);

  return (
    <div className="mx-auto max-w-lg px-4 py-8">
      <div className="rounded-2xl bg-success-soft p-6 ring-1 ring-success">
        <div className="flex items-center gap-3">
          <span aria-hidden className="text-2xl">🛡️</span>
          <h1 className="font-bold text-success">{verified.title}</h1>
        </div>

        {rows.length > 0 ? (
          <dl className="mt-4 space-y-2 rounded-xl bg-surface p-4">
            {rows.map((row) => (
              <div key={row.label} className="flex gap-2 text-sm">
                <dt className="text-muted">{row.label} :</dt>
                <dd className="font-semibold">{row.value}</dd>
              </div>
            ))}
          </dl>
        ) : null}

        <p className="mt-4 text-sm text-muted">{verified.checkDomain}</p>

        <button
          type="button"
          onClick={() => setStarted(true)}
          className="mt-5 w-full rounded-xl bg-primary px-6 py-4 text-lg font-semibold text-white hover:bg-primary-hover"
        >
          {verified.continueLabel}
        </button>
      </div>
      <p className="mt-4 text-center text-xs font-medium text-muted">{verified.neverBank}</p>
    </div>
  );
}
