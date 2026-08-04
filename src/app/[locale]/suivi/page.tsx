import { serverT } from "@/i18n/server";
import { TrackForm } from "@/components/track-form";

export default async function TrackSearchPage() {
  const { locale, t } = await serverT();

  return (
    <div className="mx-auto max-w-lg px-4 py-8">
      <div className="rounded-2xl bg-surface p-6 shadow-sm ring-1 ring-line">
        <h1 className="text-xl font-bold sm:text-2xl">{t("track.title")}</h1>
        <p className="mt-1.5 text-muted">{t("track.hint")}</p>
        <TrackForm locale={locale} />
      </div>
    </div>
  );
}
