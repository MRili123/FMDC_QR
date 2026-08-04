import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";
import { currentUser } from "@/server/session";
import { renderQrSvg } from "@/server/qr";

function escapeXml(value: string): string {
  return value.replace(/[<>&'"]/g, (char) =>
    char === "<" ? "&lt;" : char === ">" ? "&gt;" : char === "&" ? "&amp;" : char === "'" ? "&apos;" : "&quot;",
  );
}

export async function GET(_request: Request, context: RouteContext<"/api/qr/[code]/poster">) {
  const user = await currentUser();
  if (!user) return NextResponse.json({ error: "unauthorised" }, { status: 401 });

  const { code } = await context.params;
  const qr = await prisma.qrCode.findUnique({ where: { code: decodeURIComponent(code) } });
  if (!qr) return NextResponse.json({ error: "not_found" }, { status: 404 });

  const qrSvg = await renderQrSvg(qr.code);
  // On retire l'enveloppe <svg> du QR pour l'imbriquer dans l'affiche.
  const inner = qrSvg.replace(/^[\s\S]*?<svg[^>]*>/, "").replace(/<\/svg>\s*$/, "");
  const viewBox = /viewBox="([^"]+)"/.exec(qrSvg)?.[1] ?? "0 0 33 33";

  const domain = process.env.NEXT_PUBLIC_QR_DOMAIN ?? "qr.consommateurs.ma";
  const title = escapeXml(qr.etablissement ?? qr.libelle);

  const poster = `<svg xmlns="http://www.w3.org/2000/svg" width="600" height="820" viewBox="0 0 600 820">
  <rect width="600" height="820" fill="#ffffff"/>
  <rect x="0" y="0" width="600" height="110" fill="#1d4e89"/>
  <text x="300" y="48" text-anchor="middle" font-family="system-ui, sans-serif" font-size="26" font-weight="bold" fill="#ffffff">Un problème de consommation ?</text>
  <text x="300" y="82" text-anchor="middle" font-family="system-ui, sans-serif" font-size="19" fill="#e8eff7">Scannez pour défendre vos droits</text>

  <svg x="120" y="150" width="360" height="360" viewBox="${viewBox}">${inner}</svg>

  <text x="300" y="562" text-anchor="middle" font-family="system-ui, sans-serif" font-size="15" fill="#4a5768">Vérifiez que votre navigateur affiche bien</text>
  <text x="300" y="592" text-anchor="middle" font-family="monospace" font-size="24" font-weight="bold" fill="#16202e">${escapeXml(domain)}</text>

  <rect x="60" y="620" width="480" height="60" rx="10" fill="#fdf3e0"/>
  <text x="300" y="647" text-anchor="middle" font-family="system-ui, sans-serif" font-size="15" font-weight="bold" fill="#8a5a00">La FMDC ne vous demandera jamais</text>
  <text x="300" y="668" text-anchor="middle" font-family="system-ui, sans-serif" font-size="15" font-weight="bold" fill="#8a5a00">vos coordonnées bancaires.</text>

  <text x="300" y="720" text-anchor="middle" font-family="system-ui, sans-serif" font-size="17" font-weight="bold" fill="#16202e">${title}</text>
  <text x="300" y="748" text-anchor="middle" font-family="monospace" font-size="14" fill="#4a5768">${escapeXml(qr.code)} · ${escapeXml(qr.type)}</text>
  <text x="300" y="790" text-anchor="middle" font-family="system-ui, sans-serif" font-size="13" fill="#4a5768">Fédération Marocaine des Droits du Consommateur</text>
</svg>`;

  return new NextResponse(poster, {
    headers: {
      "Content-Type": "image/svg+xml; charset=utf-8",
      "Content-Disposition": `attachment; filename="qr-fmdc-${qr.code}.svg"`,
    },
  });
}
