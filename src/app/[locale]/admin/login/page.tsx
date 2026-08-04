import { redirect } from "next/navigation";
import bcrypt from "bcryptjs";
import { prisma } from "@/lib/db";
import { createSession, currentUser } from "@/server/session";
import { serverT } from "@/i18n/server";
import type { Locale } from "@/i18n/config";

async function signIn(formData: FormData) {
  "use server";

  const locale = String(formData.get("locale") ?? "fr") as Locale;
  const email = String(formData.get("email") ?? "").trim().toLowerCase();
  const password = String(formData.get("password") ?? "");

  const user = await prisma.adminUser.findFirst({ where: { email, active: true } });
  // bcrypt.compare est appelé même sans utilisateur pour ne pas révéler par le
  // temps de réponse quelles adresses existent.
  const fallback = "$2b$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvali";
  const valid = await bcrypt.compare(password, user?.passwordHash ?? fallback);

  if (!user || !valid) {
    redirect(`/${locale}/admin/login?error=1`);
  }

  await createSession(user.id);
  redirect(`/${locale}/admin`);
}

export default async function LoginPage({ searchParams }: PageProps<"/[locale]/admin/login">) {
  const { locale, t } = await serverT();

  if (await currentUser()) redirect(`/${locale}/admin`);

  const { error } = await searchParams;

  return (
    <div className="mx-auto max-w-sm px-4 py-10">
      <form action={signIn} className="rounded-2xl bg-surface p-6 shadow-sm ring-1 ring-line">
        <h1 className="text-xl font-bold">{t("admin.login.title")}</h1>
        <input type="hidden" name="locale" value={locale} />

        {error ? (
          <p className="mt-4 rounded-lg bg-danger-soft p-3 text-sm font-medium text-danger">
            {t("admin.login.invalid")}
          </p>
        ) : null}

        <label className="mt-4 block">
          <span className="text-sm font-medium">{t("admin.login.email")}</span>
          <input
            name="email"
            type="email"
            required
            autoComplete="username"
            className="mt-1 w-full rounded-xl border-2 border-line bg-surface p-3"
          />
        </label>

        <label className="mt-4 block">
          <span className="text-sm font-medium">{t("admin.login.password")}</span>
          <input
            name="password"
            type="password"
            required
            autoComplete="current-password"
            className="mt-1 w-full rounded-xl border-2 border-line bg-surface p-3"
          />
        </label>

        <button
          type="submit"
          className="mt-6 w-full rounded-xl bg-primary px-6 py-3 font-semibold text-white hover:bg-primary-hover"
        >
          {t("admin.login.submit")}
        </button>
      </form>
    </div>
  );
}
