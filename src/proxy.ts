import { NextResponse, type NextRequest } from "next/server";
import { DEFAULT_LOCALE, LOCALES } from "@/i18n/config";

const PREFIXED = new Set<string>(LOCALES);

/**
 * Toutes les routes vivent sous /[locale]. Le QR imprimé pointe vers /r/<code>
 * sans langue : on négocie ici, sinon un scan tomberait sur un 404.
 */
export function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;
  const [, first] = pathname.split("/");
  if (PREFIXED.has(first)) return NextResponse.next();

  const preferred = request.headers.get("accept-language")?.toLowerCase() ?? "";
  const locale = preferred.startsWith("ar") ? "ar" : DEFAULT_LOCALE;

  const url = request.nextUrl.clone();
  url.pathname = `/${locale}${pathname === "/" ? "" : pathname}`;
  return NextResponse.redirect(url);
}

export const config = {
  matcher: ["/((?!api|_next|uploads|.*\\..*).*)"],
};
