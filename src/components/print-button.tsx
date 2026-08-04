"use client";

export function PrintButton({ label }: { label: string }) {
  return (
    <button
      type="button"
      onClick={() => window.print()}
      className="w-full rounded-xl border-2 border-primary px-6 py-3 font-semibold text-primary hover:bg-primary-soft"
    >
      {label}
    </button>
  );
}
