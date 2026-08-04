import { NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/db";

const schema = z.object({ code: z.string().min(1).max(50) });

export async function POST(request: Request) {
  const parsed = schema.safeParse(await request.json());
  if (!parsed.success) {
    return NextResponse.json({ error: "invalid_request" }, { status: 400 });
  }

  // Un code inconnu est justement le cas le plus inquiétant (autocollant
  // frauduleux) : on le trace dans le journal, sans créer d'entrée QrCode.
  const updated = await prisma.qrCode.updateMany({
    where: { code: parsed.data.code },
    data: { reportedCount: { increment: 1 } },
  });

  await prisma.auditLog.create({
    data: {
      action: "qr.reported",
      entityType: "QrCode",
      entityId: parsed.data.code,
      actorLabel: updated.count > 0 ? "Consommateur (code connu)" : "Consommateur (code inconnu)",
    },
  });

  return NextResponse.json({ ok: true });
}
