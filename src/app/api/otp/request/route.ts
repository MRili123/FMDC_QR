import { NextResponse } from "next/server";
import { randomInt } from "node:crypto";
import { z } from "zod";
import { prisma } from "@/lib/db";
import { hmac } from "@/lib/crypto";
import { providers } from "@/server/providers";

const schema = z.object({ phone: z.string().min(6).max(20) });

const TTL_MINUTES = 10;
const MAX_PER_HOUR = 5;

export async function POST(request: Request) {
  const parsed = schema.safeParse(await request.json());
  if (!parsed.success) {
    return NextResponse.json({ error: "invalid_request" }, { status: 400 });
  }

  const phone = parsed.data.phone.replace(/\s+/g, "");

  const recent = await prisma.otpChallenge.count({
    where: { phone, createdAt: { gte: new Date(Date.now() - 60 * 60 * 1000) } },
  });
  if (recent >= MAX_PER_HOUR) {
    return NextResponse.json({ error: "rate_limited" }, { status: 429 });
  }

  const code = String(randomInt(0, 1_000_000)).padStart(6, "0");
  await prisma.otpChallenge.create({
    data: {
      phone,
      codeHash: hmac(code),
      expiresAt: new Date(Date.now() + TTL_MINUTES * 60 * 1000),
    },
  });

  await providers.sms.send(phone, `FMDC — votre code de vérification : ${code}`);

  // Le code ne repart jamais dans la réponse, même en développement : le
  // prestataire SMS de substitution l'écrit dans les logs serveur.
  return NextResponse.json({ ok: true });
}
