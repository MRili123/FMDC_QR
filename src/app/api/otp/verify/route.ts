import { NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/db";
import { hmac, safeEqual } from "@/lib/crypto";

const schema = z.object({ phone: z.string().min(6).max(20), code: z.string().min(4).max(10) });

const MAX_ATTEMPTS = 5;

export async function POST(request: Request) {
  const parsed = schema.safeParse(await request.json());
  if (!parsed.success) {
    return NextResponse.json({ error: "invalid_request" }, { status: 400 });
  }

  const phone = parsed.data.phone.replace(/\s+/g, "");
  const challenge = await prisma.otpChallenge.findFirst({
    where: { phone, consumedAt: null, expiresAt: { gt: new Date() } },
    orderBy: { createdAt: "desc" },
  });

  if (!challenge || challenge.attempts >= MAX_ATTEMPTS) {
    return NextResponse.json({ error: "invalid_code" }, { status: 400 });
  }

  if (!safeEqual(challenge.codeHash, hmac(parsed.data.code))) {
    await prisma.otpChallenge.update({
      where: { id: challenge.id },
      data: { attempts: { increment: 1 } },
    });
    return NextResponse.json({ error: "invalid_code" }, { status: 400 });
  }

  await prisma.otpChallenge.update({
    where: { id: challenge.id },
    data: { consumedAt: new Date() },
  });

  return NextResponse.json({ ok: true });
}
