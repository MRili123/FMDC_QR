"use client";

import { useState } from "react";

export function ReportQrButton({
  code,
  label,
  doneLabel,
}: {
  code: string;
  label: string;
  doneLabel: string;
}) {
  const [reported, setReported] = useState(false);

  if (reported) {
    return <p className="rounded-lg bg-success-soft p-3 text-sm font-medium text-success">{doneLabel}</p>;
  }

  return (
    <button
      type="button"
      onClick={async () => {
        await fetch("/api/qr/report", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ code }),
        });
        setReported(true);
      }}
      className="w-full rounded-xl bg-danger px-6 py-3 font-semibold text-white"
    >
      {label}
    </button>
  );
}
