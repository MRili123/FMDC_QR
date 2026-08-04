import Link from "next/link";
import { redirect } from "next/navigation";
import { currentUser, destroySession } from "@/server/session";
import { serverT } from "@/i18n/server";
import type { Locale } from "@/i18n/config";

async function signOut(formData: FormData) {
  "use server";
  const locale = String(formData.get("locale") ?? "fr") as Locale;
  await destroySession();
  redirect(`/${locale}/admin/login`);
}

export default async function AdminLayout({ children }: LayoutProps<"/[locale]/admin">) {
  const { locale, t } = await serverT();

  const user = await currentUser();
  if (!user) redirect(`/${locale}/admin/login`);

  const links = [
    { href: `/${locale}/admin`, label: t("admin.nav.queue") },
    { href: `/${locale}/admin/tableau-de-bord`, label: t("admin.nav.dashboard") },
    { href: `/${locale}/admin/qr`, label: t("admin.nav.qr") },
    ...(user.role === "FMDC_ADMIN"
      ? [{ href: `/${locale}/admin/associations`, label: t("admin.nav.associations") }]
      : []),
  ];

  return (
    <div className="mx-auto max-w-6xl px-4 py-6">
      <div className="mb-6 flex flex-wrap items-center gap-3 border-b border-line pb-4">
        <nav className="flex flex-wrap gap-1">
          {links.map((link) => (
            <Link
              key={link.href}
              href={link.href}
              className="rounded-lg px-3 py-2 text-sm font-medium text-muted hover:bg-primary-soft hover:text-primary"
            >
              {link.label}
            </Link>
          ))}
        </nav>
        <form action={signOut} className="ms-auto flex items-center gap-3">
          <span className="text-sm text-muted">{user.nom}</span>
          <input type="hidden" name="locale" value={locale} />
          <button type="submit" className="text-sm font-medium text-primary underline">
            {t("admin.nav.logout")}
          </button>
        </form>
      </div>
      {children}
    </div>
  );
}
