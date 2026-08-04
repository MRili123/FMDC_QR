"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useLocale } from "@/i18n/client";
import type { Locale } from "@/i18n/config";

export function TrackForm({ locale }: { locale: Locale }) {
  const { t } = useLocale();
  const router = useRouter();
  const [reference, setReference] = useState("");
  const [token, setToken] = useState("");

  return (
    <form
      className="mt-5 space-y-4"
      onSubmit={(event) => {
        event.preventDefault();
        router.push(
          `/${locale}/suivi/${encodeURIComponent(reference.trim())}?t=${encodeURIComponent(token.trim())}`,
        );
      }}
    >
      <label className="block">
        <span className="text-sm font-medium">{t("track.reference")}</span>
        <input
          required
          value={reference}
          onChange={(event) => setReference(event.target.value)}
          placeholder="FMDC-2026-000001"
          className="mt-1 w-full rounded-xl border-2 border-line bg-surface p-3 font-mono"
        />
      </label>
      <label className="block">
        <span className="text-sm font-medium">{t("track.token")}</span>
        <input
          required
          value={token}
          onChange={(event) => setToken(event.target.value)}
          className="mt-1 w-full rounded-xl border-2 border-line bg-surface p-3 font-mono"
        />
      </label>
      <button
        type="submit"
        className="w-full rounded-xl bg-primary px-6 py-4 text-lg font-semibold text-white hover:bg-primary-hover"
      >
        {t("track.submit")}
      </button>
    </form>
  );
}
